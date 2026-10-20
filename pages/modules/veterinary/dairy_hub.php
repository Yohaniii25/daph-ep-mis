<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db_connect.php';
require_once __DIR__ . '/processors/dairy_db_migration.php';

/** @var mysqli $mysqli */
global $mysqli;
ensure_dairy_project_tables($mysqli);

// 1. Session and Role Guard
$allowed_roles = [
    'veterinary_surgeon',
    'government_veterinary_surgeon',
    'additional_veterinary_surgeon',
    'deputy_director_hq_1',
    'district_dd',
    'deputy_director_district',
    'provincial_director',
    'admin',
    'super_admin',
    'administrator',
    'sms',
    'planning_officer'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    header("Location: ../../../../index.php");
    exit();
}

$user_role   = $_SESSION['role'] ?? '';
$user_id     = intval($_SESSION['user_id'] ?? 0);
$session_range_id    = !empty($_SESSION['range_id']) ? intval($_SESSION['range_id']) : null;
$session_district_id = !empty($_SESSION['district_id']) ? intval($_SESSION['district_id']) : null;

// Determine active range_id (support GET parameter e.g. dairy_hub.php?range_id=1, fallback to session)
$requested_range_id = isset($_GET['range_id']) && is_numeric($_GET['range_id']) ? intval($_GET['range_id']) : null;
$range_id = $requested_range_id !== null ? $requested_range_id : ($session_range_id ?: 1);

// Active Tab
$active_tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'approvals'; // 'approvals', 'collections', or 'society'

// Filters for Beneficiary Approvals
$selected_year     = isset($_GET['year']) && is_numeric($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$selected_category = trim($_GET['category'] ?? '');
$selected_status   = trim($_GET['status'] ?? '');

// Fetch Ranges list for range selector / range context
$ranges_list = [];
$ranges_q = $mysqli->query("SELECT r.id, r.name, r.district_id, d.name AS district_name FROM veterinary_ranges r LEFT JOIN districts d ON r.district_id = d.id ORDER BY d.name ASC, r.name ASC");
if ($ranges_q) {
    while ($r = $ranges_q->fetch_assoc()) {
        $ranges_list[] = $r;
    }
}

// Current Range details
$current_range_name = 'Range #' . $range_id;
$current_district_name = 'District';
foreach ($ranges_list as $rng) {
    if ($rng['id'] == $range_id) {
        $current_range_name = $rng['name'];
        $current_district_name = $rng['district_name'];
        break;
    }
}

// -----------------------------------------------------------------------------
// 2. Fetch Beneficiaries Summary Report Records
// -----------------------------------------------------------------------------
$sum_where = ["1=1"];
$sum_params = [];
$sum_types  = "";

if ($selected_year > 0) {
    $sum_where[] = "b.project_year = ?";
    $sum_params[] = $selected_year;
    $sum_types .= "i";
}

if ($range_id > 0) {
    $sum_where[] = "(b.range_id = ? OR b.range_id IS NULL OR b.range_id = 0)";
    $sum_params[] = $range_id;
    $sum_types .= "i";
}

if (!empty($selected_category)) {
    $sum_where[] = "b.category = ?";
    $sum_params[] = $selected_category;
    $sum_types .= "s";
}

if (!empty($selected_status)) {
    $sum_where[] = "b.approval_status = ?";
    $sum_params[] = $selected_status;
    $sum_types .= "s";
}

$sum_where_sql = implode(" AND ", $sum_where);

$sum_query = "
    SELECT b.*, 
           COALESCE(vr.name, 'All Ranges') AS range_name, 
           COALESCE(d.name, 'Eastern Province') AS district_name,
           app.id AS detailed_app_id,
           app.survey_date,
           app.updated_at AS app_updated_at
    FROM `dairy_project_beneficiaries` b
    LEFT JOIN `veterinary_ranges` vr ON b.range_id = vr.id
    LEFT JOIN `districts` d ON b.district_id = d.id
    LEFT JOIN `dairy_farm_applications` app ON (b.farm_reg_no = app.farm_reg_no OR b.id = app.summary_id)
    WHERE {$sum_where_sql}
    GROUP BY b.id
    ORDER BY b.id DESC
";

$stmt_sum = $mysqli->prepare($sum_query);
if (!empty($sum_params)) {
    $stmt_sum->bind_param($sum_types, ...$sum_params);
}
$stmt_sum->execute();
$sum_result = $stmt_sum->get_result();

$beneficiaries = [];
$total_beneficiaries = 0;
$total_estimated_cost = 0.0;
$total_dept_contribution = 0.0;
$total_ben_contribution = 0.0;
$total_milk_current = 0.0;
$total_milk_expected = 0.0;
$total_forms_completed = 0;

while ($row = $sum_result->fetch_assoc()) {
    $beneficiaries[] = $row;
    $total_beneficiaries++;
    $total_estimated_cost += floatval($row['total_cost']);
    $total_dept_contribution += floatval($row['department_contribution']);
    $total_ben_contribution += floatval($row['beneficiary_contribution']);
    $total_milk_current += floatval($row['daily_milk_current']);
    $total_milk_expected += floatval($row['daily_milk_expected']);
    if (!empty($row['detailed_app_id'])) {
        $total_forms_completed++;
    }
}
$stmt_sum->close();

// -----------------------------------------------------------------------------
// 3. Fetch Existing Milk Collection Records (for Collections Tab)
// -----------------------------------------------------------------------------
$records = [];
$collection_stmt = $mysqli->prepare("
    SELECT * FROM dairy_hub_records 
    WHERE (range_id = ? OR range_id IS NULL) AND YEAR(collection_date) = ?
    ORDER BY collection_date DESC
");
if ($collection_stmt) {
    $collection_stmt->bind_param("ii", $range_id, $selected_year);
    $collection_stmt->execute();
    $records = $collection_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $collection_stmt->close();
}

// -----------------------------------------------------------------------------
// 4. Fetch Dairy Hub Society & Enrolled Members (for Society Administration Tab)
// -----------------------------------------------------------------------------
$society_data = null;
$soc_stmt = $mysqli->prepare("SELECT * FROM `dairy_hub_societies` WHERE range_id = ? LIMIT 1");
if ($soc_stmt) {
    $soc_stmt->bind_param("i", $range_id);
    $soc_stmt->execute();
    $society_data = $soc_stmt->get_result()->fetch_assoc();
    $soc_stmt->close();
}
if (empty($society_data)) {
    $def_soc_name = "Dairy Hub Cooperative Society - " . $current_range_name;
    $society_data = [
        'id' => 0,
        'range_id' => $range_id,
        'society_name' => $def_soc_name,
        'reg_no' => 'DHS/EP/' . sprintf("%02d", $range_id),
        'established_date' => '2026-01-15',
        'code_of_conduct_title' => 'Dairy Hub Society Official Code of Conduct & Operational Ethics',
        'code_of_conduct_text' => null,
        'status' => 'Active'
    ];
}

$society_members = [];
$mem_stmt = $mysqli->prepare("
    SELECT m.*, a.milk_total_liters, a.main_category, a.shed_capacity_adults, a.cultivating_grass_own_land
    FROM `dairy_hub_society_members` m
    LEFT JOIN `dairy_farm_applications` a ON m.farm_reg_no = a.farm_reg_no
    WHERE m.range_id = ?
    ORDER BY 
        CASE 
            WHEN m.executive_position = 'President' THEN 1
            WHEN m.executive_position = 'Vice President' THEN 2
            WHEN m.executive_position = 'Secretary' THEN 3
            WHEN m.executive_position = 'Treasurer' THEN 4
            WHEN m.executive_position = 'Assistant Secretary' THEN 5
            WHEN m.executive_position = 'Committee Member' THEN 6
            ELSE 10
        END, m.member_name ASC
");
if ($mem_stmt) {
    $mem_stmt->bind_param("i", $range_id);
    $mem_stmt->execute();
    $society_members = $mem_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $mem_stmt->close();
}

$current_president = null;
$current_vice_president = null;
$current_secretary = null;
$current_treasurer = null;
$stat_exec_count = 0;

foreach ($society_members as $sm) {
    if ($sm['executive_position'] === 'President') $current_president = $sm;
    if ($sm['executive_position'] === 'Vice President') $current_vice_president = $sm;
    if ($sm['executive_position'] === 'Secretary') $current_secretary = $sm;
    if ($sm['executive_position'] === 'Treasurer') $current_treasurer = $sm;
    if ($sm['is_executive'] == 1 || $sm['executive_position'] !== 'Member') $stat_exec_count++;
}

// Fetch Scheduled Monitoring Visits for Dairy Hub farms
$dh_visits = [];
$dh_v_stmt = $mysqli->prepare("SELECT * FROM `project_monitoring_visits` WHERE source_module = 'Dairy Hub' OR range_id = ? ORDER BY visit_date DESC, id DESC");
if ($dh_v_stmt) {
    $dh_v_stmt->bind_param("i", $range_id);
    $dh_v_stmt->execute();
    $dh_visits = $dh_v_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $dh_v_stmt->close();
}

// Fetch Operational Training Sessions for Dairy Hub
$dairy_trainings = [];
$dt_stmt = $mysqli->prepare("
    SELECT * FROM `operational_trainings` 
    WHERE (range_id = ? OR ? = 0) AND module_context = 'dairy_hub' 
    ORDER BY training_date DESC, id DESC
");
if ($dt_stmt) {
    $dt_stmt->bind_param("ii", $range_id, $range_id);
    $dt_stmt->execute();
    $dairy_trainings = $dt_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $dt_stmt->close();
}

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">

<style>
    @keyframes autoFillPulse {
        0% { background-color: #d1e7dd; border-color: #198754; box-shadow: 0 0 8px rgba(25, 135, 84, 0.4); }
        50% { background-color: #d1e7dd; border-color: #198754; }
        100% { background-color: inherit; border-color: inherit; }
    }
    .auto-fill-flash {
        animation: autoFillPulse 1.4s ease-in-out !important;
    }
    .dairy-hub-header {
        background: linear-gradient(135deg, #370709 0%, #631215 50%, #820100 100%);
        color: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(55, 7, 9, 0.15);
    }
    .kpi-stat-card {
        border-radius: 10px;
        border: none;
        border-left: 4px solid #820100;
        background: #ffffff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .kpi-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.08);
    }
    /* Modern Segmented Capsule Tabs for Dairy Hub */
    .nav-capsule-dairy {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 5px;
        display: flex;
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none; /* Firefox */
        gap: 6px;
    }
    .nav-capsule-dairy::-webkit-scrollbar {
        display: none; /* Chrome, Safari, Edge */
    }
    .nav-capsule-dairy .nav-item {
        flex: 1 1 0;
        min-width: max-content;
    }
    .nav-capsule-dairy .nav-link {
        font-weight: 600;
        font-size: 0.885rem;
        color: #475569;
        padding: 0.65rem 1rem;
        border-radius: 8px;
        border: 1px solid transparent;
        background: transparent;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        width: 100%;
        text-decoration: none;
    }
    .nav-capsule-dairy .nav-link:hover {
        color: #1e293b;
        background: #e2e8f0;
    }
    .nav-capsule-dairy .nav-link.active {
        color: #ffffff !important;
        background: linear-gradient(135deg, #820100 0%, #a30100 100%) !important;
        box-shadow: 0 4px 12px rgba(130, 1, 0, 0.22);
        border-color: #820100;
    }
    .nav-capsule-dairy .nav-link.active .badge {
        background-color: rgba(255, 255, 255, 0.25) !important;
        color: #ffffff !important;
        border: 1px solid rgba(255, 255, 255, 0.35);
    }
    .nav-capsule-dairy .nav-link:not(.active) .badge {
        background-color: #e2e8f0 !important;
        color: #334155 !important;
        border: 1px solid #cbd5e1;
    }
    .table-dairy thead {
        background-color: #d4c7b7;
        color: #370709;
        font-weight: 700;
    }
    .badge-cat-5l { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
    .badge-cat-10l { background-color: #e3f2fd; color: #1565c0; border: 1px solid #bbdefb; }
    .badge-cat-20l { background-color: #fff3e0; color: #e65100; border: 1px solid #ffe0b2; }
    .badge-cat-50l { background-color: #f3e5f5; color: #7b1fa2; border: 1px solid #e1bee7; }
    .badge-cat-100l { background-color: #fbe9e7; color: #d84315; border: 1px solid #ffccbc; }
    .action-btn-group .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.825rem;
    }
    .auto-fetch-spinner {
        display: none;
    }
</style>

<div class="container-fluid px-4 py-3">

    <!-- Top Breadcrumb & Page Title -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="../../../dashboard.php" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="range_details.php?range_id=<?= $range_id ?>" class="text-decoration-none text-muted">Range Details</a></li>
                    <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">Dairy Hub Operations</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0" style="color: #370709;">
                <i class="bi bi-patch-check-fill me-2 text-danger"></i>Dairy Hub Operations &amp; Beneficiary Approvals
            </h4>
            <small class="text-muted">
                Veterinary Range: <strong class="text-dark"><?= htmlspecialchars($current_range_name) ?></strong> (<?= htmlspecialchars($current_district_name) ?>) &middot; Year <?= htmlspecialchars($selected_year) ?>
            </small>
        </div>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <!-- Range Switcher if user has multiple or supervisory scope -->
            <form method="GET" action="dairy_hub.php" class="d-flex align-items-center gap-2">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
                <select name="range_id" class="form-select form-select-sm border-secondary shadow-sm" onchange="this.form.submit()" style="min-width: 180px;">
                    <?php foreach ($ranges_list as $rng): ?>
                        <option value="<?= $rng['id'] ?>" <?= ($range_id == $rng['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($rng['district_name'] . ' - ' . $rng['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <button class="btn text-light fw-bold text-nowrap shadow-sm" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addBeneficiaryModal">
                <i class="bi bi-person-plus-fill me-1"></i> Enroll Beneficiary
            </button>
            <a href="dairy_application_form.php?range_id=<?= $range_id ?>" class="btn btn-outline-dark fw-semibold shadow-sm text-nowrap">
                <i class="bi bi-file-earmark-plus me-1"></i> New Application Form
            </a>
        </div>
    </div>



    <!-- Modern Segmented Navigation Tabs -->
    <ul class="nav nav-capsule-dairy mb-4 shadow-sm" id="dairyHubTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($active_tab === 'approvals') ? 'active' : '' ?>" id="approvals-tab" data-bs-toggle="tab" data-bs-target="#approvals-pane" type="button" role="tab" title="Beneficiary Summary &amp; Approvals">
                <!-- <i class="bi bi-card-checklist me-1.5"></i> -->
                <span>Beneficiary Approvals</span>
                <span class="badge rounded-pill ms-2"><?= $total_beneficiaries ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($active_tab === 'collections') ? 'active' : '' ?>" id="collections-tab" data-bs-toggle="tab" data-bs-target="#collections-pane" type="button" role="tab" title="Milk Collection &amp; Quality Logs">
                <!-- <i class="bi bi-droplet-half me-1.5"></i> -->
                <span>Milk Collections</span>
                <span class="badge rounded-pill ms-2"><?= count($records) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($active_tab === 'society') ? 'active' : '' ?>" id="society-tab" data-bs-toggle="tab" data-bs-target="#society-pane" type="button" role="tab" title="Society Administration &amp; Governance">
                <!-- <i class="bi bi-people-fill me-1.5"></i> -->
                <span>Society Governance</span>
                <span class="badge rounded-pill ms-2"><?= count($society_members) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($active_tab === 'monitoring') ? 'active' : '' ?>" id="monitoring-tab" data-bs-toggle="tab" data-bs-target="#monitoring-pane" type="button" role="tab" title="Scheduled Farm Monitoring Logs">
                <!-- <i class="bi bi-clipboard2-pulse me-1.5"></i> -->
                <span>Farm Monitoring</span>
                <span class="badge rounded-pill ms-2"><?= count($dh_visits) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($active_tab === 'trainings') ? 'active' : '' ?>" id="trainings-tab" data-bs-toggle="tab" data-bs-target="#trainings-pane" type="button" role="tab" title="Dairy Farmer &amp; Extension Trainings">
                <!-- <i class="bi bi-mortarboard-fill me-1.5"></i> -->
                <span>Farmer Trainings</span>
                <span class="badge rounded-pill ms-2"><?= count($dairy_trainings) ?></span>
            </button>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="dairyHubTabContent">

        <!-- ========================================================================= -->
        <!-- TAB 1: BENEFICIARY SUMMARY & APPROVALS (PRIMARY REQUIREMENT)              -->
        <!-- ========================================================================= -->
        <div class="tab-pane fade <?= ($active_tab === 'approvals') ? 'show active' : '' ?>" id="approvals-pane" role="tabpanel">

            <!-- Summary KPI Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-6 col-xl-3">
                    <div class="card kpi-stat-card h-100 p-3" style="border-left-color: #820100;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase">Total Beneficiaries</div>
                                <div class="fs-4 fw-bold text-dark mt-1"><?= number_format($total_beneficiaries) ?></div>
                                <small class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i><?= $total_forms_completed ?> Surveys Done</small>
                            </div>
                            <div class="rounded-circle p-3 text-white" style="background-color: #820100;">
                                <i class="bi bi-people-fill fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card kpi-stat-card h-100 p-3" style="border-left-color: #198754;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase">Total Estimated Cost</div>
                                <div class="fs-4 fw-bold text-success mt-1">Rs. <?= number_format($total_estimated_cost, 2) ?></div>
                                <small class="text-muted">Subsidized Package Value</small>
                            </div>
                            <div class="rounded-circle p-3 text-white bg-success">
                                <i class="bi bi-cash-stack fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card kpi-stat-card h-100 p-3" style="border-left-color: #0d6efd;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase">Dept. Contribution</div>
                                <div class="fs-4 fw-bold text-primary mt-1">Rs. <?= number_format($total_dept_contribution, 2) ?></div>
                                <small class="text-muted">Farmer: Rs. <?= number_format($total_ben_contribution, 2) ?></small>
                            </div>
                            <div class="rounded-circle p-3 text-white bg-primary">
                                <i class="bi bi-building-check fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card kpi-stat-card h-100 p-3" style="border-left-color: #fd7e14;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase">Expected Daily Milk</div>
                                <div class="fs-4 fw-bold text-warning mt-1"><?= number_format($total_milk_expected, 1) ?> L</div>
                                <small class="text-muted">Current: <?= number_format($total_milk_current, 1) ?> L/day</small>
                            </div>
                            <div class="rounded-circle p-3 text-white bg-warning">
                                <i class="bi bi-droplet-half fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="card shadow-sm border-0 mb-4 bg-light">
                <div class="card-body py-3 px-4">
                    <form method="GET" action="dairy_hub.php" class="row g-2 align-items-center">
                        <input type="hidden" name="tab" value="approvals">
                        <input type="hidden" name="range_id" value="<?= $range_id ?>">

                        <div class="col-md-2">
                            <label class="form-label small fw-bold mb-0 text-muted">Project Year</label>
                            <select name="year" class="form-select form-select-sm">
                                <?php for ($y = date('Y') + 1; $y >= 2020; $y--): ?>
                                    <option value="<?= $y ?>" <?= ($selected_year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold mb-0 text-muted">Category</label>
                            <select name="category" class="form-select form-select-sm">
                                <option value="">-- All Categories --</option>
                                <option value="5L" <?= ($selected_category === '5L') ? 'selected' : '' ?>>5 Liters</option>
                                <option value="10L" <?= ($selected_category === '10L') ? 'selected' : '' ?>>10 Liters</option>
                                <option value="20L" <?= ($selected_category === '20L') ? 'selected' : '' ?>>20 Liters</option>
                                <option value="50L" <?= ($selected_category === '50L') ? 'selected' : '' ?>>50 Liters</option>
                                <option value="100L" <?= ($selected_category === '100L') ? 'selected' : '' ?>>100 Liters</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold mb-0 text-muted">Approval Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">-- All Statuses --</option>
                                <option value="Pending" <?= ($selected_status === 'Pending') ? 'selected' : '' ?>>Pending Review</option>
                                <option value="Recommended" <?= ($selected_status === 'Recommended') ? 'selected' : '' ?>>Recommended by VS</option>
                                <option value="Approved" <?= ($selected_status === 'Approved') ? 'selected' : '' ?>>Approved</option>
                                <option value="Rejected" <?= ($selected_status === 'Rejected') ? 'selected' : '' ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex gap-2 align-items-end mt-md-4">
                            <button type="submit" class="btn btn-sm btn-dark w-100 fw-bold">
                                <i class="bi bi-funnel-fill me-1"></i> Filter
                            </button>
                            <a href="dairy_hub.php?range_id=<?= $range_id ?>&tab=approvals" class="btn btn-sm btn-outline-secondary w-50" title="Reset Filters">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Beneficiary Summary Table Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white pt-4 px-4 border-0 d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-1" style="color: #370709;">
                            <i class="bi bi-table me-2 text-danger"></i>Summary Report for the Approval of Beneficiaries
                        </h5>
                        <p class="text-muted small mb-0">
                            Auto-fetches master registry details by Farm Reg No, records manual production metrics, and dynamically connects to the comprehensive survey application.
                        </p>
                    </div>
                    <div class="mt-2 mt-md-0 d-flex gap-2">
                        <button class="btn btn-sm btn-outline-danger" onclick="window.print()">
                            <i class="bi bi-printer me-1"></i> Print Summary
                        </button>
                        <button class="btn btn-sm text-light fw-bold shadow-sm" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addBeneficiaryModal">
                            <i class="bi bi-plus-circle me-1"></i> Enroll Beneficiary
                        </button>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="table-responsive">
                        <table id="beneficiarySummaryTable" class="table table-striped table-hover table-bordered align-middle small bg-white text-dark m-0 w-100">
                            <thead class="table-dairy">
                                <tr>
                                    <th class="text-center" style="width: 35px;">#</th>
                                    <th style="min-width: 160px;">Farm Reg No &amp; Status</th>
                                    <th style="min-width: 190px;">Beneficiary Information</th>
                                    <th style="min-width: 160px;">Address &amp; Contact</th>
                                    <th class="text-center" style="min-width: 125px;">Daily Milk (L/day)</th>
                                    <th class="text-center" style="width: 75px;">Category</th>
                                    <th style="min-width: 170px;">Activities</th>
                                    <th style="min-width: 170px;">Financial Breakdown (Rs.)</th>
                                    <th class="text-center" style="width: 100px;">Application</th>
                                    <th class="text-center" style="min-width: 110px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($beneficiaries)): ?>
                                    <?php $idx = 1; foreach ($beneficiaries as $b): ?>
                                        <tr>
                                            <td class="text-center fw-bold text-muted"><?= $idx++ ?></td>
                                            <td>
                                                <div class="fw-bold text-dark font-monospace mb-1">
                                                    <i class="bi bi-postcard me-1 text-danger"></i><?= htmlspecialchars($b['farm_reg_no']) ?>
                                                </div>
                                                <div>
                                                    <?php
                                                    $st = $b['approval_status'] ?? 'Pending';
                                                    $st_class = match($st) {
                                                        'Approved' => 'bg-success text-white',
                                                        'Recommended' => 'bg-primary text-white',
                                                        'Rejected' => 'bg-danger text-white',
                                                        default => 'bg-warning text-dark'
                                                    };
                                                    ?>
                                                    <span class="badge <?= $st_class ?> px-2 py-1"><?= htmlspecialchars($st) ?></span>
                                                    <span class="badge bg-light text-muted border"><?= htmlspecialchars($b['range_name']) ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark fs-6 mb-1">
                                                    <i class="bi bi-person-fill text-danger me-1"></i><?= htmlspecialchars($b['beneficiary_name']) ?>
                                                </div>
                                                <div class="text-secondary small">
                                                    <i class="bi bi-person-vcard me-1"></i>NIC: <strong><?= htmlspecialchars($b['nic']) ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark mb-1">
                                                    <i class="bi bi-telephone-fill me-1 text-success"></i><?= htmlspecialchars($b['phone']) ?>
                                                </div>
                                                <div class="text-muted small" title="<?= htmlspecialchars($b['address']) ?>">
                                                    <i class="bi bi-geo-alt-fill me-1 text-danger"></i><?= htmlspecialchars(mb_strimwidth($b['address'], 0, 40, '...')) ?>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="small">
                                                    <span class="text-muted">Current:</span> <strong class="text-dark"><?= number_format($b['daily_milk_current'], 1) ?> L</strong>
                                                </div>
                                                <div class="small mt-1">
                                                    <span class="text-muted">Expected:</span> <strong class="text-success"><?= number_format($b['daily_milk_expected'], 1) ?> L</strong>
                                                </div>
                                                <?php if ($b['daily_milk_expected'] > $b['daily_milk_current']): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle mt-1" style="font-size: 0.7rem;">
                                                        +<?= number_format($b['daily_milk_expected'] - $b['daily_milk_current'], 1) ?> L
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php
                                                $cat = $b['category'] ?? '5L';
                                                $cat_badge = match($cat) {
                                                    '10L' => 'badge-cat-10l',
                                                    '20L' => 'badge-cat-20l',
                                                    '50L' => 'badge-cat-50l',
                                                    '100L' => 'badge-cat-100l',
                                                    default => 'badge-cat-5l'
                                                };
                                                ?>
                                                <span class="badge <?= $cat_badge ?> fw-bold px-2 py-1"><?= htmlspecialchars($cat) ?></span>
                                            </td>
                                            <td>
                                                <div class="text-dark small" style="white-space: pre-wrap;"><?= !empty($b['activities']) ? htmlspecialchars($b['activities']) : '<span class="text-muted fst-italic">Not specified</span>' ?></div>
                                            </td>
                                            <td>
                                                <div class="small d-flex justify-content-between mb-1">
                                                    <span class="text-muted">Est. Cost:</span>
                                                    <strong class="text-dark">Rs. <?= number_format($b['total_cost'], 2) ?></strong>
                                                </div>
                                                <div class="small d-flex justify-content-between text-success mb-1">
                                                    <span>Dept:</span>
                                                    <strong>Rs. <?= number_format($b['department_contribution'], 2) ?></strong>
                                                </div>
                                                <div class="small d-flex justify-content-between text-primary">
                                                    <span>Farmer:</span>
                                                    <strong>Rs. <?= number_format($b['beneficiary_contribution'], 2) ?></strong>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <?php if (!empty($b['detailed_app_id'])): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle p-1 mb-1 d-inline-block">
                                                        <i class="bi bi-check2-circle me-1"></i>Completed
                                                    </span>
                                                    <div class="small text-muted font-monospace" style="font-size: 0.725rem;">
                                                        <?= date('d-M-Y', strtotime($b['survey_date'] ?? $b['app_updated_at'])) ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle p-1 d-inline-block">
                                                        <i class="bi bi-clock-history me-1"></i>Pending
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center text-nowrap action-btn-group">
                                                <!-- Action Button linking dynamically to Detailed Application Form -->
                                                <a href="dairy_application_form.php?farm_reg_no=<?= urlencode($b['farm_reg_no']) ?>&summary_id=<?= $b['id'] ?>&range_id=<?= $range_id ?>" 
                                                   class="btn btn-sm btn-outline-success me-1" 
                                                   title="Open Detailed Application Form for this Farmer">
                                                    <i class="bi bi-file-earmark-text-fill"></i> Form
                                                </a>
                                                <!-- Scheduled Site Monitoring Visit Button -->
                                                <button class="btn btn-sm btn-outline-warning me-1" 
                                                        title="Record Scheduled Site Monitoring Visit" 
                                                        onclick="openDairyFarmVisitModal('<?= htmlspecialchars(addslashes($b['farm_reg_no'])) ?>', '<?= htmlspecialchars(addslashes($b['beneficiary_name'])) ?>', <?= $b['id'] ?>)">
                                                    <i class="bi bi-clipboard2-pulse"></i> Visit
                                                </button>
                                                <!-- Quick Edit Modal -->
                                                <button class="btn btn-sm btn-outline-primary me-1 edit-summary-btn" 
                                                        data-id="<?= $b['id'] ?>"
                                                        title="Edit Summary Record">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <!-- Delete Button -->
                                                <button class="btn btn-sm btn-outline-danger delete-summary-btn" 
                                                        data-id="<?= $b['id'] ?>"
                                                        data-name="<?= htmlspecialchars($b['beneficiary_name'], ENT_QUOTES) ?>"
                                                        data-farm_reg="<?= htmlspecialchars($b['farm_reg_no'], ENT_QUOTES) ?>"
                                                        title="Delete Entry">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: MILK COLLECTION & QUALITY LOGS (PRESERVED DAIRY HUB LOGS)          -->
        <!-- ========================================================================= -->
        <div class="tab-pane fade <?= ($active_tab === 'collections') ? 'show active' : '' ?>" id="collections-pane" role="tabpanel">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold mb-0" style="color: #370709;">Milk Collection &amp; Quality Monitoring Records</h5>
                    <small class="text-muted">Daily intake volume, Fat %, SNF %, and total payout metrics</small>
                </div>
                <div>
                    <button class="btn btn-success btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addDairyModal">
                        <i class="bi bi-plus-circle me-1"></i> Record Milk Collection
                    </button>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <table id="dairyTable" class="table table-hover align-middle w-100">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Date</th>
                                <th>Month</th>
                                <th>Farmer Reg No</th>
                                <th class="text-center">Qty (L)</th>
                                <th class="text-center">Fat %</th>
                                <th class="text-center">SNF %</th>
                                <th class="text-end">Rate (Rs)</th>
                                <th class="text-end">Total (Rs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $row):
                                $mName = date('F', strtotime($row['collection_date']));
                            ?>
                                <tr>
                                    <td data-order="<?= strtotime($row['collection_date']) ?>">
                                        <?= date('d-M-Y', strtotime($row['collection_date'])) ?>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= $mName ?></span></td>
                                    <td class="fw-bold"><?= htmlspecialchars($row['farmer_reg_no']) ?></td>
                                    <td class="text-center"><?= number_format($row['milk_quantity_liters'], 2) ?></td>
                                    <td class="text-center"><?= $row['fat_percentage'] ?>%</td>
                                    <td class="text-center"><?= $row['snf_percentage'] ?>%</td>
                                    <td class="text-end"><?= number_format($row['price_per_liter'], 2) ?></td>
                                    <td class="text-end fw-bold text-primary">
                                        <?= number_format($row['total_amount'], 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- TAB 3: SOCIETY ADMINISTRATION & GOVERNANCE                                -->
        <!-- ========================================================================= -->
        <div class="tab-pane fade <?= ($active_tab === 'society') ? 'show active' : '' ?>" id="society-pane" role="tabpanel">

            <!-- Society Header Banner -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: var(--border-radius); background: linear-gradient(135deg, #1e3a8a 0%, #1e1b4b 100%); color: #ffffff;">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background: rgba(59, 130, 246, 0.2); border: 2px solid #3b82f6;">
                                <i class="bi bi-people-fill fs-2 text-info"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-primary text-white fw-bold text-uppercase px-2 py-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">Dairy Hub Governance</span>
                                    <span class="badge bg-light text-dark border px-2 py-1" style="font-size: 0.7rem;">Reg: <?= htmlspecialchars($society_data['reg_no'] ?? 'Pending') ?></span>
                                </div>
                                <h3 class="fw-bold mb-1"><?= htmlspecialchars($society_data['society_name'] ?? 'Dairy Hub Cooperative Society') ?></h3>
                                <p class="mb-0 text-light text-opacity-75 small">
                                    Official producer society for <?= htmlspecialchars($current_range_name) ?> &bull; Automated membership enrollment, executive committee management, and binding code of conduct.
                                </p>
                            </div>
                        </div>

                        <!-- Header Action Buttons -->
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-light btn-sm fw-bold px-3 shadow-sm" id="btnOpenCodeOfConductModal">
                                <i class="bi bi-pencil-square me-1 text-primary"></i> Edit Code of Conduct
                            </button>
                            <button type="button" class="btn btn-outline-light btn-sm px-3 shadow-sm" onclick="window.print()">
                                <i class="bi bi-printer me-1"></i> Print Society Roster
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Society Executive Committee Roster (Leadership Cards) -->
            <div class="row g-3 mb-4">
                <!-- 1. President -->
                <div class="col-md-6 col-xl-3">
                    <div class="card h-100 p-3 shadow-sm border-0" style="border-left: 4px solid #f59e0b !important; border-radius: var(--border-radius);">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge bg-warning text-dark fw-bold px-2 py-1" style="font-size: 0.7rem;">
                                    <i class="bi bi-award-fill me-1"></i> PRESIDENT
                                </span>
                                <h5 class="fw-bold text-dark mt-2 mb-0">
                                    <?= $current_president ? htmlspecialchars($current_president['member_name']) : '<span class="text-muted fw-normal fs-6">Position Vacant</span>' ?>
                                </h5>
                            </div>
                            <div class="rounded-circle p-2 bg-warning-subtle text-warning-emphasis">
                                <i class="bi bi-star-fill fs-5"></i>
                            </div>
                        </div>
                        <?php if ($current_president): ?>
                            <small class="text-muted d-block font-monospace"><i class="bi bi-telephone text-primary me-1"></i><?= htmlspecialchars($current_president['phone'] ?: 'No phone') ?></small>
                            <small class="text-muted d-block font-monospace"><i class="bi bi-house-check text-success me-1"></i>Farm: <?= htmlspecialchars($current_president['farm_reg_no'] ?: '—') ?></small>
                            <button type="button" class="btn btn-xs btn-outline-warning text-dark mt-3 w-100 btn-assign-exec-modal" 
                                    data-id="<?= $current_president['id'] ?>" data-name="<?= htmlspecialchars($current_president['member_name']) ?>" data-position="President">
                                <i class="bi bi-arrow-repeat me-1"></i> Change President
                            </button>
                        <?php else: ?>
                            <small class="text-muted d-block">No executive president assigned yet.</small>
                            <button type="button" class="btn btn-xs btn-outline-secondary mt-3 w-100" onclick="$('#societyMembersTable').DataTable().search('Member').draw();">
                                <i class="bi bi-plus-circle me-1"></i> Assign from Members
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 2. Vice President -->
                <div class="col-md-6 col-xl-3">
                    <div class="card h-100 p-3 shadow-sm border-0" style="border-left: 4px solid #3b82f6 !important; border-radius: var(--border-radius);">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge bg-primary text-white fw-bold px-2 py-1" style="font-size: 0.7rem;">
                                    <i class="bi bi-shield-check me-1"></i> VICE PRESIDENT
                                </span>
                                <h5 class="fw-bold text-dark mt-2 mb-0">
                                    <?= $current_vice_president ? htmlspecialchars($current_vice_president['member_name']) : '<span class="text-muted fw-normal fs-6">Position Vacant</span>' ?>
                                </h5>
                            </div>
                            <div class="rounded-circle p-2 bg-primary-subtle text-primary">
                                <i class="bi bi-shield-shaded fs-5"></i>
                            </div>
                        </div>
                        <?php if ($current_vice_president): ?>
                            <small class="text-muted d-block font-monospace"><i class="bi bi-telephone text-primary me-1"></i><?= htmlspecialchars($current_vice_president['phone'] ?: 'No phone') ?></small>
                            <small class="text-muted d-block font-monospace"><i class="bi bi-house-check text-success me-1"></i>Farm: <?= htmlspecialchars($current_vice_president['farm_reg_no'] ?: '—') ?></small>
                            <button type="button" class="btn btn-xs btn-outline-primary mt-3 w-100 btn-assign-exec-modal" 
                                    data-id="<?= $current_vice_president['id'] ?>" data-name="<?= htmlspecialchars($current_vice_president['member_name']) ?>" data-position="Vice President">
                                <i class="bi bi-arrow-repeat me-1"></i> Change Vice President
                            </button>
                        <?php else: ?>
                            <small class="text-muted d-block">No vice president assigned yet.</small>
                            <button type="button" class="btn btn-xs btn-outline-secondary mt-3 w-100" onclick="$('#societyMembersTable').DataTable().search('Member').draw();">
                                <i class="bi bi-plus-circle me-1"></i> Assign from Members
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3. Secretary -->
                <div class="col-md-6 col-xl-3">
                    <div class="card h-100 p-3 shadow-sm border-0" style="border-left: 4px solid #10b981 !important; border-radius: var(--border-radius);">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge bg-success text-white fw-bold px-2 py-1" style="font-size: 0.7rem;">
                                    <i class="bi bi-journal-text me-1"></i> SECRETARY
                                </span>
                                <h5 class="fw-bold text-dark mt-2 mb-0">
                                    <?= $current_secretary ? htmlspecialchars($current_secretary['member_name']) : '<span class="text-muted fw-normal fs-6">Position Vacant</span>' ?>
                                </h5>
                            </div>
                            <div class="rounded-circle p-2 bg-success-subtle text-success">
                                <i class="bi bi-pencil-fill fs-5"></i>
                            </div>
                        </div>
                        <?php if ($current_secretary): ?>
                            <small class="text-muted d-block font-monospace"><i class="bi bi-telephone text-primary me-1"></i><?= htmlspecialchars($current_secretary['phone'] ?: 'No phone') ?></small>
                            <small class="text-muted d-block font-monospace"><i class="bi bi-house-check text-success me-1"></i>Farm: <?= htmlspecialchars($current_secretary['farm_reg_no'] ?: '—') ?></small>
                            <button type="button" class="btn btn-xs btn-outline-success mt-3 w-100 btn-assign-exec-modal" 
                                    data-id="<?= $current_secretary['id'] ?>" data-name="<?= htmlspecialchars($current_secretary['member_name']) ?>" data-position="Secretary">
                                <i class="bi bi-arrow-repeat me-1"></i> Change Secretary
                            </button>
                        <?php else: ?>
                            <small class="text-muted d-block">No secretary assigned yet.</small>
                            <button type="button" class="btn btn-xs btn-outline-secondary mt-3 w-100" onclick="$('#societyMembersTable').DataTable().search('Member').draw();">
                                <i class="bi bi-plus-circle me-1"></i> Assign from Members
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 4. Total Enrolled Members Summary -->
                <div class="col-md-6 col-xl-3">
                    <div class="card h-100 p-3 shadow-sm border-0" style="border-left: 4px solid #6366f1 !important; border-radius: var(--border-radius);">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge bg-indigo text-white fw-bold px-2 py-1" style="font-size: 0.7rem; background-color: #6366f1;">
                                    <i class="bi bi-people me-1"></i> TOTAL ENROLLED
                                </span>
                                <h3 class="fw-bold text-dark mt-2 mb-0"><?= count($society_members) ?></h3>
                            </div>
                            <div class="rounded-circle p-2 bg-indigo-subtle text-indigo" style="background-color: #e0e7ff; color: #4338ca;">
                                <i class="bi bi-person-check-fill fs-5"></i>
                            </div>
                        </div>
                        <small class="text-success fw-semibold d-block"><i class="bi bi-check2-all me-1"></i><?= $stat_exec_count ?> Executive Officers</small>
                        <small class="text-muted d-block">Auto-enrolled via application forms</small>
                    </div>
                </div>
            </div>

            <!-- Auto-Enrollment System Banner -->
            <div class="alert alert-info border-0 shadow-sm d-flex align-items-center gap-3 p-3 mb-4 rounded-3">
                <i class="bi bi-info-circle-fill fs-3 text-primary flex-shrink-0"></i>
                <div class="small">
                    <strong>Automated Dairy Hub Society Enrollment Active:</strong>
                    Whenever a farmer's survey details are entered and submitted through the Dairy Hub application form, the system automatically registers that individual as a formal member of this society. Executive positions (such as <strong>President</strong> and <strong>Vice President</strong>) can be designated directly from the membership directory below.
                </div>
            </div>

            <!-- Formal Code of Conduct Panel -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: var(--border-radius);">
                <div class="card-header bg-white py-3 border-0 d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-shield-fill-check fs-4 text-success"></i>
                        <div>
                            <h5 class="mb-0 fw-bold text-dark" id="displayCodeTitle"><?= htmlspecialchars($society_data['code_of_conduct_title'] ?? 'Official Code of Conduct & Operational Ethics') ?></h5>
                            <small class="text-muted">Formally established code of conduct binding upon all enrolled society members &bull; Established <?= htmlspecialchars($society_data['established_date'] ?? '2026-01-15') ?></small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success-subtle text-success border border-success px-2 py-1 fw-bold">
                            <i class="bi bi-check-circle-fill me-1"></i> Formally Enacted &amp; Active
                        </span>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnEditCodeInline">
                            <i class="bi bi-pencil-square me-1"></i> Edit Charter
                        </button>
                    </div>
                </div>
                <div class="card-body p-4 bg-light bg-opacity-50">
                    <div class="p-3 bg-white rounded border" style="line-height: 1.7; font-size: 0.92rem; max-height: 280px; overflow-y: auto;" id="displayCodeBody">
                        <?= !empty($society_data['code_of_conduct_text']) ? nl2br(htmlspecialchars($society_data['code_of_conduct_text'])) : '<em>Default Code of Conduct is established and automatically displayed from system governance parameters.</em>' ?>
                    </div>
                </div>
            </div>

            <!-- Enrolled Society Members Directory Table -->
            <div class="card border-0 shadow-sm" style="border-radius: var(--border-radius);">
                <div class="card-header bg-white py-3 border-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-person-lines-fill text-primary me-2"></i>Enrolled Society Membership Directory
                        </h5>
                        <small class="text-muted">
                            Complete list of active members registered under <?= htmlspecialchars($current_range_name) ?> Dairy Hub Society
                        </small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark border px-3 py-2 fw-semibold">
                            <i class="bi bi-people-fill me-1 text-primary"></i> <?= count($society_members) ?> Members Registered
                        </span>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0" id="societyMembersTable">
                            <thead class="table-dark small text-uppercase">
                                <tr>
                                    <th class="text-center" style="min-width: 130px;">Membership No.</th>
                                    <th style="min-width: 170px;">Member / Farmer Name</th>
                                    <th style="min-width: 120px;">Farm Reg No.</th>
                                    <th style="min-width: 120px;">NIC &amp; Phone</th>
                                    <th style="min-width: 160px;">Address / GN</th>
                                    <th style="min-width: 105px;" class="text-center">Enrolled Date</th>
                                    <th style="min-width: 140px;" class="text-center">Executive Position</th>
                                    <th style="min-width: 80px;" class="text-center">Status</th>
                                    <th style="min-width: 140px;" class="text-center">Administration Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($society_members)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                            No enrolled members registered in this range yet. Submitting a Dairy Farm Development Survey Application will automatically register farmers here.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($society_members as $m): ?>
                                        <tr>
                                            <!-- Membership No -->
                                            <td class="text-center font-monospace">
                                                <span class="badge bg-light text-dark border fw-bold px-2 py-1">
                                                    <?= htmlspecialchars($m['membership_no']) ?>
                                                </span>
                                            </td>

                                            <!-- Member Name -->
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($m['member_name']) ?></div>
                                                <small class="text-muted d-block" style="font-size: 0.75rem;">Source: <?= htmlspecialchars($m['enrollment_source']) ?></small>
                                            </td>

                                            <!-- Farm Reg No -->
                                            <td>
                                                <?php if (!empty($m['farm_reg_no'])): ?>
                                                    <a href="dairy_application_form.php?farm_reg_no=<?= urlencode($m['farm_reg_no']) ?>&range_id=<?= $range_id ?>" 
                                                       class="badge bg-success-subtle text-success border border-success font-monospace text-decoration-none px-2 py-1" title="View Farm Survey Application">
                                                        <i class="bi bi-file-earmark-text me-1"></i><?= htmlspecialchars($m['farm_reg_no']) ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- NIC & Phone -->
                                            <td>
                                                <div class="small font-monospace fw-semibold text-dark"><?= htmlspecialchars($m['nic']) ?></div>
                                                <?php if (!empty($m['phone'])): ?>
                                                    <small class="text-muted font-monospace d-block">
                                                        <i class="bi bi-telephone text-primary me-1"></i><?= htmlspecialchars($m['phone']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Address -->
                                            <td>
                                                <div class="small text-truncate" style="max-width: 180px;" title="<?= htmlspecialchars($m['address']) ?>">
                                                    <?= htmlspecialchars($m['address'] ?: '—') ?>
                                                </div>
                                                <?php if (!empty($m['ds_gn_division'])): ?>
                                                    <small class="text-muted d-block"><?= htmlspecialchars($m['ds_gn_division']) ?></small>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Enrollment Date -->
                                            <td class="text-center small">
                                                <?= date('d-M-Y', strtotime($m['enrollment_date'])) ?>
                                            </td>

                                            <!-- Executive Position Badge -->
                                            <td class="text-center">
                                                <?php if ($m['executive_position'] === 'President'): ?>
                                                    <span class="badge bg-warning text-dark border border-warning px-2 py-1 fw-bold">
                                                        <i class="bi bi-star-fill me-1"></i> President
                                                    </span>
                                                <?php elseif ($m['executive_position'] === 'Vice President'): ?>
                                                    <span class="badge bg-primary text-white border border-primary px-2 py-1 fw-bold">
                                                        <i class="bi bi-shield-check me-1"></i> Vice President
                                                    </span>
                                                <?php elseif ($m['executive_position'] === 'Secretary'): ?>
                                                    <span class="badge bg-success text-white border border-success px-2 py-1 fw-bold">
                                                        <i class="bi bi-journal-check me-1"></i> Secretary
                                                    </span>
                                                <?php elseif ($m['executive_position'] === 'Treasurer'): ?>
                                                    <span class="badge bg-info text-dark border border-info px-2 py-1 fw-bold">
                                                        <i class="bi bi-cash-coin me-1"></i> Treasurer
                                                    </span>
                                                <?php elseif ($m['executive_position'] === 'Committee Member'): ?>
                                                    <span class="badge bg-purple text-white border px-2 py-1 fw-semibold" style="background-color: #7c3aed;">
                                                        Committee Member
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border px-2 py-1">Member</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Status -->
                                            <td class="text-center">
                                                <span class="badge bg-success-subtle text-success border border-success">Active</span>
                                            </td>

                                            <!-- Action: Assign Executive Position -->
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-primary fw-semibold btn-assign-exec-modal"
                                                        data-id="<?= $m['id'] ?>"
                                                        data-name="<?= htmlspecialchars($m['member_name'], ENT_QUOTES) ?>"
                                                        data-position="<?= htmlspecialchars($m['executive_position'], ENT_QUOTES) ?>"
                                                        data-membership="<?= htmlspecialchars($m['membership_no'], ENT_QUOTES) ?>">
                                                    <i class="bi bi-person-gear me-1"></i> Assign Position
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- TAB 4: SCHEDULED DAIRY HUB FARM MONITORING LOGS (VET SURGEON)            -->
        <!-- ========================================================================= -->
        <div class="tab-pane fade <?= ($active_tab === 'monitoring') ? 'show active' : '' ?>" id="monitoring-pane" role="tabpanel">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-clipboard2-pulse me-2 text-danger"></i>Dairy Hub Scheduled Site Monitoring Visits
                        </h5>
                        <small class="text-muted">Mandatory Veterinary Surgeon inspection logs at 1 month, 3 months, 6 months, and bi-annually</small>
                    </div>
                    <button class="btn btn-sm text-light fw-bold shadow-sm" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#dairyFarmVisitModal">
                        <i class="bi bi-journal-plus me-1"></i> Log Farm Inspection
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle small table-striped table-bordered" id="dairyMonitoringTable">
                            <thead class="table-dairy">
                                <tr>
                                    <th>Date &amp; Interval</th>
                                    <th>Farm &amp; Beneficiary</th>
                                    <th>Farm Conditions Evaluation</th>
                                    <th>Animal Illnesses</th>
                                    <th>Mortality (Deaths)</th>
                                    <th>Natural Disasters</th>
                                    <th>Surgeon Directives</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($dh_visits)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="bi bi-journal-x fs-1 d-block mb-2 text-danger"></i>
                                            No scheduled monitoring visits recorded yet for Dairy Hub farms. Click "Log Farm Inspection" to record milestone checks.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($dh_visits as $dv): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 fw-bold">
                                                    <?= htmlspecialchars($dv['visit_interval']) ?>
                                                </span>
                                                <div class="small text-muted font-monospace mt-1"><?= htmlspecialchars($dv['visit_date']) ?></div>
                                                <small class="text-secondary d-block"><i class="bi bi-person-check me-1"></i><?= htmlspecialchars($dv['veterinary_surgeon_name']) ?></small>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($dv['farmer_name'] ?: 'Dairy Farmer') ?></div>
                                                <?php if (!empty($dv['farm_reg_no'])): ?>
                                                    <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($dv['farm_reg_no']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="max-width: 250px;">
                                                    <?= nl2br(htmlspecialchars($dv['farm_conditions'])) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!empty($dv['animal_illnesses'])): ?>
                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle mb-1">Illness Found</span>
                                                    <div class="text-muted" style="max-width: 180px;"><?= htmlspecialchars($dv['animal_illnesses']) ?></div>
                                                <?php else: ?>
                                                    <span class="badge bg-success-subtle text-success border">No Illness</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (intval($dv['deaths_count']) > 0): ?>
                                                    <span class="badge bg-danger"><?= $dv['deaths_count'] ?> Deaths</span>
                                                    <div class="text-danger mt-1 small"><?= htmlspecialchars($dv['deaths_details']) ?></div>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border">0 Deaths</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($dv['natural_disasters'])): ?>
                                                    <span class="badge bg-danger-subtle text-danger border"><i class="bi bi-cloud-lightning me-1"></i>Impact Noted</span>
                                                    <div class="text-muted mt-1" style="max-width: 180px;"><?= htmlspecialchars($dv['natural_disasters']) ?></div>
                                                <?php else: ?>
                                                    <span class="text-muted">None</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="max-width: 220px;">
                                                    <?= nl2br(htmlspecialchars($dv['recommendations'] ?: 'Standard operational hygiene and feeding maintenance.')) ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 5: DAIRY HUB OPERATIONAL TRAININGS & FARMER EDUCATION                 -->
        <!-- ========================================================================= -->
        <div class="tab-pane fade <?= ($active_tab === 'trainings') ? 'show active' : '' ?>" id="trainings-pane" role="tabpanel">
            <?php 
            $tr_context      = 'dairy_hub';
            $tr_title        = 'Dairy Farmer & Extension Trainings';
            $tr_subtitle     = 'Clean milk production, mastitis management, artificial insemination, and cooperative governance';
            $tr_records      = $dairy_trainings;
            $tr_theme_color  = '#820100';
            $tr_table_id     = 'dairyTrainingsTable';
            $tr_export_id    = 'dairyTrainingExports';
            include __DIR__ . '/partials/embedded_training_view.php';
            ?>
        </div>

    </div>

</div>

<!-- ========================================================================= -->
<!-- Modal: Record Scheduled Dairy Hub Farm Monitoring Visit                   -->
<!-- ========================================================================= -->
<div class="modal fade" id="dairyFarmVisitModal" tabindex="-1" aria-labelledby="dairyFarmVisitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #820100;">
                <h5 class="modal-title text-white" id="dairyFarmVisitModalLabel">
                    <i class="bi bi-clipboard2-pulse me-2"></i>Dairy Hub Scheduled Site Monitoring Visit
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="dairyMonitoringVisitForm">
                <input type="hidden" name="action" value="save_monitoring_visit">
                <input type="hidden" name="source_module" value="Dairy Hub">
                <input type="hidden" name="dairy_hub_id" id="dh_visit_dh_id" value="0">
                <input type="hidden" name="range_name" value="<?= htmlspecialchars($current_range_name) ?>">
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        
                        <!-- Farm Reg No -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Farm Registration Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control font-monospace" name="farm_reg_no" id="dh_visit_farm_reg_no" placeholder="e.g. 530112345" required>
                        </div>

                        <!-- Farmer / Beneficiary Name -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Farmer Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="farmer_name" id="dh_visit_farmer_name" placeholder="e.g. Kumara Perera" required>
                        </div>

                        <!-- Scheduled Interval -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-uppercase">Scheduled Interval <span class="text-danger">*</span></label>
                            <select class="form-select" name="visit_interval" required>
                                <option value="1 Month" selected>1 Month Inspection</option>
                                <option value="3 Months">3 Months Inspection</option>
                                <option value="6 Months">6 Months Inspection</option>
                                <option value="Bi-Annual (12 Mo)">Bi-Annual (12 Months)</option>
                                <option value="Bi-Annual (18 Mo)">Bi-Annual (18 Months)</option>
                                <option value="Bi-Annual (24 Mo)">Bi-Annual (24 Months)</option>
                                <option value="Routine / Ad-Hoc">Routine / Emergency Check</option>
                            </select>
                        </div>

                        <!-- Visit Date -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-uppercase">Visit Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="visit_date" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <!-- Inspecting Surgeon -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-uppercase">Inspecting Veterinary Surgeon</label>
                            <input type="text" class="form-control" name="veterinary_surgeon_name" value="<?= htmlspecialchars($_SESSION['full_name'] ?? 'Veterinary Surgeon') ?>" required>
                        </div>

                        <!-- Farm Conditions -->
                        <div class="col-12">
                            <label class="form-label fw-bold small text-uppercase">Farm Conditions Evaluation <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="farm_conditions" rows="3" required placeholder="Assess cattle shed cleanliness, ventilation, clean water access, bio-security, silage/fodder availability, and milk hygiene..."></textarea>
                        </div>

                        <!-- Animal Illnesses -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Animal Illnesses Observed</label>
                            <textarea class="form-control" name="animal_illnesses" rows="2" placeholder="Record symptoms, mastitis checks, parasitic burden, clinical diagnoses, and treatments..."></textarea>
                        </div>

                        <!-- Natural Disasters -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Natural Disasters / Drought / Flooding</label>
                            <textarea class="form-control" name="natural_disasters" rows="2" placeholder="Note seasonal flooding, drought pasture impact, or environmental stresses..."></textarea>
                        </div>

                        <!-- Deaths / Mortality -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-uppercase">Animal Deaths Count</label>
                            <input type="number" class="form-control" name="deaths_count" min="0" value="0">
                        </div>

                        <div class="col-md-9">
                            <label class="form-label fw-bold small text-uppercase">Mortality Cause &amp; Tag Details</label>
                            <input type="text" class="form-control" name="deaths_details" placeholder="Ear tag no, breed, suspected cause, post-mortem findings...">
                        </div>

                        <!-- Recommendations -->
                        <div class="col-12">
                            <label class="form-label fw-bold small text-uppercase">Veterinary Surgeon Directives &amp; Action Plan</label>
                            <textarea class="form-control" name="recommendations" rows="2" placeholder="Vaccination recall, nutritional directives, milking hygiene protocols..."></textarea>
                        </div>

                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-white px-4 fw-bold" style="background-color: #820100;">
                        <i class="bi bi-save me-1"></i> Save Farm Inspection Log
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal: Add / Enroll Beneficiary to Summary (With Master Registry Fetch)    -->
<!-- ========================================================================= -->
<div class="modal fade" id="addBeneficiaryModal" tabindex="-1" aria-labelledby="addBeneficiaryLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-2" style="background-color: #370709;">
                <h6 class="modal-title" id="addBeneficiaryLabel">
                    <i class="bi bi-person-plus-fill me-2"></i> Enroll Beneficiary &middot; Summary Report Entry
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addSummaryForm">
                <input type="hidden" name="action" value="save_summary">
                <input type="hidden" name="id" value="0">
                <input type="hidden" name="project_year" value="<?= $selected_year ?>">
                <input type="hidden" name="range_id" value="<?= $range_id ?>">

                <div class="modal-body p-4 small">

                    <!-- Section A: Farm Registration Number & Auto-Fetch -->
                    <div class="p-3 mb-3 rounded-3 border" style="background-color: #fdfaf7; border-color: #e2d7ce !important;">
                        <label class="form-label fw-bold text-dark mb-1">
                            <i class="bi bi-search me-1 text-danger"></i> Farm Registration Number <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="text" id="add_farm_reg_no" name="farm_reg_no" class="form-control font-monospace fw-bold" placeholder="Enter Farm Registration Number (e.g. 530112345 or FRN-AMP-001)" required autocomplete="off">
                            <button class="btn text-light fw-bold px-3 d-flex align-items-center" type="button" id="btnAutoFetch" style="background-color: #820100;">
                                <span class="spinner-border spinner-border-sm auto-fetch-spinner me-1" role="status" aria-hidden="true" style="display: none;"></span>
                                <i class="bi bi-cloud-arrow-down-fill me-1 fetch-icon"></i> <span class="fetch-btn-label">Auto-Fetch</span>
                            </button>
                        </div>
                        <div id="fetchFeedback" class="small mt-1 text-muted">
                            <i class="bi bi-info-circle me-1"></i>Enter Farm Registration No or NIC. Details will automatically fetch from Master Registry upon typing, leaving the field, or pressing Enter.
                        </div>
                    </div>

                    <!-- Section B: Auto-Populated Basic Details -->
                    <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-3">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-person-vcard-fill me-1 text-danger"></i> Beneficiary Details (Auto-Fetched)
                        </h6>
                        <span class="badge bg-light text-dark border px-2 py-1" id="regSourceBadge">Master Registry</span>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Beneficiary's Full Name <span class="text-danger">*</span></label>
                            <input type="text" id="add_beneficiary_name" name="beneficiary_name" class="form-control form-control-sm border-secondary" placeholder="Farmer Name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">NIC Number <span class="text-danger">*</span></label>
                            <input type="text" id="add_nic" name="nic" class="form-control form-control-sm border-secondary" placeholder="e.g. 198512345678" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" id="add_phone" name="phone" class="form-control form-control-sm border-secondary" placeholder="e.g. 0771234567" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Range Profile</label>
                            <input type="text" class="form-control form-control-sm bg-light" value="<?= htmlspecialchars($current_range_name) ?>" readonly>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold mb-1">Address <span class="text-danger">*</span></label>
                            <textarea id="add_address" name="address" rows="2" class="form-control form-control-sm border-secondary" placeholder="Physical Address of Farmer / Farm" required></textarea>
                        </div>
                    </div>

                    <!-- Section C: Manual Inputs (Production, Category, Activities, Finance) -->
                    <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-3">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-sliders me-1 text-primary"></i> Manual Inputs &amp; Subsidies
                        </h6>
                        <span class="badge bg-secondary-subtle text-secondary px-2 py-1">Manual Input Required</span>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold mb-1 text-dark">
                                Daily Milk Production (Current) [L]
                            </label>
                            <input type="number" step="0.1" min="0" id="add_daily_milk_current" name="daily_milk_current" class="form-control form-control-sm border-secondary" value="0.0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold mb-1 text-success">
                                Daily Milk Production (Expected) [L]
                            </label>
                            <input type="number" step="0.1" min="0" id="add_daily_milk_expected" name="daily_milk_expected" class="form-control form-control-sm border-success fw-bold" value="0.0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold mb-1 text-dark">
                                Category <span class="text-danger">*</span>
                            </label>
                            <select id="add_category" name="category" class="form-select form-select-sm border-secondary fw-semibold" required>
                                <option value="5L">5 Liters</option>
                                <option value="10L">10 Liters</option>
                                <option value="20L">20 Liters</option>
                                <option value="50L">50 Liters</option>
                                <option value="100L">100 Liters</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold mb-1">
                                Activities <span class="text-danger">*</span>
                            </label>
                            <textarea id="add_activities" name="activities" rows="2" class="form-control form-control-sm border-secondary" placeholder="e.g. Shed construction, pasture establishment (CO3 grass), milking machine, biosecurity..." required></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold mb-1 text-dark">
                                Total Estimated Cost (Rs) <span class="text-danger">*</span>
                            </label>
                            <input type="number" step="0.01" min="0" id="add_total_cost" name="total_cost" class="form-control form-control-sm border-secondary fw-bold" value="0.00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold mb-1 text-primary">
                                Beneficiary's Contribution (Rs)
                            </label>
                            <input type="number" step="0.01" min="0" id="add_beneficiary_contribution" name="beneficiary_contribution" class="form-control form-control-sm border-primary fw-bold" value="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold mb-1 text-success">
                                Department Contribution (Rs)
                            </label>
                            <input type="number" step="0.01" min="0" id="add_department_contribution" name="department_contribution" class="form-control form-control-sm border-success fw-bold" value="0.00">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Approval Status</label>
                            <select id="add_approval_status" name="approval_status" class="form-select form-select-sm border-secondary">
                                <option value="Pending">Pending Review</option>
                                <option value="Recommended">Recommended</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Remarks / Internal Notes</label>
                            <input type="text" id="add_remarks" name="remarks" class="form-control form-control-sm border-secondary" placeholder="Optional comments">
                        </div>
                    </div>

                </div>

                <div class="modal-footer py-2 border-top-0">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnSubmitSummary" class="btn text-light btn-sm px-4 shadow-sm fw-bold" style="background-color: #820100;">
                        <i class="bi bi-save me-1"></i> Save to Summary Register
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal: Edit Existing Summary Entry                                       -->
<!-- ========================================================================= -->
<div class="modal fade" id="editSummaryModal" tabindex="-1" aria-labelledby="editSummaryLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-2" style="background-color: #370709;">
                <h6 class="modal-title" id="editSummaryLabel">
                    <i class="bi bi-pencil-square me-2"></i> Update Beneficiary Summary Record
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editSummaryForm">
                <input type="hidden" name="action" value="save_summary">
                <input type="hidden" name="id" id="edit_id" value="0">
                <input type="hidden" name="project_year" id="edit_project_year" value="<?= $selected_year ?>">
                <input type="hidden" name="range_id" id="edit_range_id" value="<?= $range_id ?>">

                <div class="modal-body p-4 small">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Farm Registration Number <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <input type="text" id="edit_farm_reg_no" name="farm_reg_no" class="form-control form-control-sm font-monospace fw-bold" required autocomplete="off">
                                <button class="btn btn-outline-secondary" type="button" id="btnEditAutoFetch" title="Auto-fetch from Master Registry">
                                    <span class="spinner-border spinner-border-sm edit-fetch-spinner me-1" role="status" aria-hidden="true" style="display: none;"></span>
                                    <i class="bi bi-cloud-arrow-down-fill edit-fetch-icon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Beneficiary Name <span class="text-danger">*</span></label>
                            <input type="text" id="edit_beneficiary_name" name="beneficiary_name" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">NIC <span class="text-danger">*</span></label>
                            <input type="text" id="edit_nic" name="nic" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" id="edit_phone" name="phone" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold mb-1">Address <span class="text-danger">*</span></label>
                            <textarea id="edit_address" name="address" rows="2" class="form-control form-control-sm" required></textarea>
                        </div>
                    </div>

                    <div class="row g-3 mb-2">
                        <div class="col-md-4">
                            <label class="form-label fw-bold mb-1">Daily Milk (Current) [L]</label>
                            <input type="number" step="0.1" min="0" id="edit_daily_milk_current" name="daily_milk_current" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold mb-1 text-success">Daily Milk (Expected) [L]</label>
                            <input type="number" step="0.1" min="0" id="edit_daily_milk_expected" name="daily_milk_expected" class="form-control form-control-sm border-success fw-bold">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold mb-1">Category</label>
                            <select id="edit_category" name="category" class="form-select form-select-sm">
                                <option value="5L">5 Liters</option>
                                <option value="10L">10 Liters</option>
                                <option value="20L">20 Liters</option>
                                <option value="50L">50 Liters</option>
                                <option value="100L">100 Liters</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold mb-1">Activities <span class="text-danger">*</span></label>
                            <textarea id="edit_activities" name="activities" rows="2" class="form-control form-control-sm" required></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold mb-1">Total Estimated Cost (Rs)</label>
                            <input type="number" step="0.01" min="0" id="edit_total_cost" name="total_cost" class="form-control form-control-sm fw-bold">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold mb-1 text-primary">Beneficiary Contribution (Rs)</label>
                            <input type="number" step="0.01" min="0" id="edit_beneficiary_contribution" name="beneficiary_contribution" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold mb-1 text-success">Dept Contribution (Rs)</label>
                            <input type="number" step="0.01" min="0" id="edit_department_contribution" name="department_contribution" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Approval Status</label>
                            <select id="edit_approval_status" name="approval_status" class="form-select form-select-sm">
                                <option value="Pending">Pending Review</option>
                                <option value="Recommended">Recommended</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Remarks</label>
                            <input type="text" id="edit_remarks" name="remarks" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>

                <div class="modal-footer py-2 border-top-0">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnUpdateSummary" class="btn text-light btn-sm px-4 shadow-sm fw-bold" style="background-color: #820100;">
                        <i class="bi bi-check2-circle me-1"></i> Update Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal: Assign Society Executive Position                                  -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalAssignExecutive" tabindex="-1" aria-labelledby="modalAssignExecutiveLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white py-2" style="background-color: #370709;">
                <h6 class="modal-title fw-bold" id="modalAssignExecutiveLabel">
                    <i class="bi bi-person-gear me-2"></i> Assign Society Executive Position
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAssignExecutive">
                <input type="hidden" name="action" value="assign_executive_position">
                <input type="hidden" name="member_id" id="assign_member_id" value="">
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="small text-muted mb-1">Selected Society Member:</div>
                        <div class="fs-5 fw-bold text-dark" id="assign_member_name_display">—</div>
                        <div class="small text-secondary font-monospace" id="assign_membership_no_display">—</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">
                            Executive Designation / Office <span class="text-danger">*</span>
                        </label>
                        <select name="position" id="assign_executive_position" class="form-select" required>
                            <option value="President">President (Society Leader)</option>
                            <option value="Vice President">Vice President (Deputy Society Leader)</option>
                            <option value="Secretary">Secretary (Administrative Officer)</option>
                            <option value="Treasurer">Treasurer (Financial Officer)</option>
                            <option value="Committee Member">Executive Committee Member</option>
                            <option value="Member">General Member (Revoke Executive Status)</option>
                        </select>
                    </div>

                    <div class="alert alert-warning py-2 px-3 small border-0 mb-0">
                        <i class="bi bi-shield-exclamation me-1"></i>
                        <strong>Single-Holder Office Rule:</strong> Designating a <em>President</em>, <em>Vice President</em>, <em>Secretary</em>, or <em>Treasurer</em> will automatically transition any prior office holder in this range to General Member.
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light border-top-0">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnSaveExecutive" class="btn btn-sm text-white fw-bold px-3 shadow-sm" style="background-color: #820100;">
                        <i class="bi bi-check2-circle me-1"></i> Save Executive Position
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal: Establish / Edit Society Code of Conduct                           -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalEditCodeOfConduct" tabindex="-1" aria-labelledby="modalEditCodeOfConductLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white py-2" style="background-color: #1e3a8a;">
                <h6 class="modal-title fw-bold" id="modalEditCodeOfConductLabel">
                    <i class="bi bi-shield-check me-2"></i> Establish &amp; Formalize Society Code of Conduct
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditCodeOfConduct">
                <input type="hidden" name="action" value="update_code_of_conduct">
                <input type="hidden" name="range_id" value="<?= $range_id ?>">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">
                            Charter / Code of Conduct Title <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="code_of_conduct_title" id="edit_code_title" class="form-control" 
                               value="<?= htmlspecialchars($society_data['code_of_conduct_title'] ?? "Dairy Hub Farmers' Society Code of Conduct & Operational Guidelines") ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">
                            Formal Code of Conduct Clauses &amp; Operational Regulations <span class="text-danger">*</span>
                        </label>
                        <textarea name="code_of_conduct_text" id="edit_code_text" class="form-control font-monospace" rows="12" required style="font-size: 0.9rem; line-height: 1.5;"><?= htmlspecialchars($society_data['code_of_conduct_text'] ?? '') ?></textarea>
                        <small class="text-muted">Enter the formal clauses, ethical rules, milk delivery standards, and operational guidelines binding upon all enrolled society members.</small>
                    </div>

                    <div class="alert alert-info py-2 px-3 small border-0 mb-0">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        Updating this charter updates the official society regulations displayed across all member portals and administrative registers in this range.
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light border-top-0">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnSaveCodeOfConduct" class="btn btn-sm btn-primary fw-bold px-3 shadow-sm">
                        <i class="bi bi-save me-1"></i> Save Formal Code of Conduct
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
// Include existing Milk Collection modal if present
if (file_exists(__DIR__ . '/models/add_dairy_record_modal.php')) {
    include __DIR__ . '/models/add_dairy_record_modal.php';
}
if (file_exists(__DIR__ . '/models/modal_log_training.php')) {
    include __DIR__ . '/models/modal_log_training.php';
}
?>
<script src="../../../assets/js/training_manager.js"></script>

<?php
// Buffer page-specific scripts to be injected by includes/footer.php after jQuery & Bootstrap load
ob_start();
?>
<script>
$(document).ready(function() {
    // 1. Initialize Beneficiaries Summary Table
    if ($('#beneficiarySummaryTable').length) {
        $('#beneficiarySummaryTable').DataTable({
            pageLength: 20,
            responsive: true,
            order: [[0, 'asc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search beneficiaries..."
            }
        });
    }

    // 1b. Initialize Dairy Trainings Table
    if ($('#dairyTrainingsTable').length) {
        var dairyTrTable = $('#dairyTrainingsTable').DataTable({
            pageLength: 10,
            responsive: true,
            order: [[1, 'desc']],
            buttons: [
                {
                    extend: 'print',
                    className: 'btn btn-sm btn-success shadow-sm',
                    exportOptions: { columns: ':visible:not(:last-child)' }
                },
                {
                    extend: 'csv',
                    className: 'btn btn-sm btn-danger shadow-sm',
                    exportOptions: { columns: ':visible:not(:last-child)' }
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-sm btn-warning shadow-sm',
                    exportOptions: { columns: ':visible:not(:last-child)' }
                }
            ],
            language: {
                emptyTable: '<div class="py-4 text-center text-muted"><i class="bi bi-mortarboard fs-2 d-block mb-2 text-secondary"></i>No dairy farmer training sessions recorded yet. Use the "Log Training Session" button above.</div>'
            }
        });
        dairyTrTable.buttons().container().appendTo('#dairyTrainingExports');
    }

    // 2. Initialize Milk Collection Table
    if ($('#dairyTable').length) {
        $('#dairyTable').DataTable({
            pageLength: 15,
            responsive: true,
            order: [[0, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search collections..."
            }
        });
    }

    // 3. Initialize Dairy Hub Society Members Table
    if ($('#societyMembersTable').length) {
        const societyDt = $('#societyMembersTable').DataTable({
            pageLength: 15,
            responsive: true,
            order: [[5, 'desc']], // Enrolled Date
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search members..."
            }
        });

        // Auto adjust table columns when Society tab becomes active
        $('button[data-bs-toggle="tab"], a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
            societyDt.columns.adjust().responsive.recalc();
        });
    }

    // =========================================================================
    // Auto-Fetch for Enroll Beneficiary (Add Modal)
    // =========================================================================
    let addLastFetchedReg = '';
    let addAutoFetchTimer = null;

    function triggerAutoFetch(isManual = false) {
        const regVal = $('#add_farm_reg_no').val().trim();
        if (!regVal) {
            if (isManual) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Registration Number Required',
                    text: 'Please input a Farm Registration Number or NIC before fetching.',
                    confirmButtonColor: '#820100'
                });
            }
            return;
        }

        // Avoid re-fetching identical value unless manually triggered
        if (!isManual && regVal === addLastFetchedReg) {
            return;
        }

        $('.auto-fetch-spinner').show();
        $('.fetch-icon').hide();
        $('.fetch-btn-label').text('Fetching...');
        $('#fetchFeedback').html('<span class="text-primary"><i class="bi bi-hourglass-split me-1"></i>Searching master registry...</span>');

        $.ajax({
            url: 'processors/dairy_project_crud.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'lookup_farmer',
                farm_reg_no: regVal
            },
            success: function(res) {
                $('.auto-fetch-spinner').hide();
                $('.fetch-icon').show();
                $('.fetch-btn-label').text('Auto-Fetch');

                if (res.success && res.found) {
                    addLastFetchedReg = regVal;
                    const f = res.farmer;
                    $('#add_beneficiary_name').val(f.farmer_name || '');
                    $('#add_nic').val(f.nic || '');
                    $('#add_phone').val(f.phone || '');
                    $('#add_address').val(f.address || '');

                    if (f.daily_milk > 0 && parseFloat($('#add_daily_milk_current').val() || 0) === 0) {
                        $('#add_daily_milk_current').val(f.daily_milk);
                    }

                    // Auto suggest category
                    const milk = parseFloat(f.daily_milk || 0);
                    if (milk >= 50) {
                        $('#add_category').val('50L');
                    } else if (milk >= 20) {
                        $('#add_category').val('20L');
                    } else if (milk >= 10) {
                        $('#add_category').val('10L');
                    } else {
                        $('#add_category').val('5L');
                    }

                    // Flash animation on auto-filled elements
                    const $flashed = $('#add_beneficiary_name, #add_nic, #add_phone, #add_address, #add_daily_milk_current, #add_category');
                    $flashed.addClass('auto-fill-flash');
                    setTimeout(() => { $flashed.removeClass('auto-fill-flash'); }, 1400);

                    $('#regSourceBadge').text(f.source).removeClass('bg-light text-dark').addClass('bg-success text-white');
                    $('#fetchFeedback').html(`<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Found: <strong>${f.farmer_name}</strong> &bull; Auto-populated from ${f.source}!</span>`);

                    if (isManual) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Farmer Located!',
                            text: `Auto-populated data for ${f.farmer_name} from ${f.source}.`,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }

                    if (res.existing_summary) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Existing Summary Found',
                            text: 'A summary record already exists for this registration number. You can update or view their existing file.',
                            confirmButtonColor: '#820100'
                        });
                    }
                } else {
                    $('#regSourceBadge').text('Manual Entry').removeClass('bg-success text-white').addClass('bg-light text-dark');
                    $('#fetchFeedback').html(`<span class="text-warning fw-semibold"><i class="bi bi-exclamation-triangle-fill me-1"></i>${res.message || 'Farmer not found in master registry. Please enter details manually.'}</span>`);

                    if (isManual) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Not Found in Registry',
                            text: res.message || 'No matching farmer found for this registration number. You may enter details manually.',
                            confirmButtonColor: '#820100'
                        });
                    }
                }
            },
            error: function() {
                $('.auto-fetch-spinner').hide();
                $('.fetch-icon').show();
                $('.fetch-btn-label').text('Auto-Fetch');
                $('#fetchFeedback').html('<span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i>Network error connecting to master registry.</span>');
                if (isManual) {
                    Swal.fire('Network Error', 'Could not connect to master registry service.', 'error');
                }
            }
        });
    }

    // Auto-fetch triggers for Add Modal
    $('#btnAutoFetch').on('click', function(e) {
        e.preventDefault();
        triggerAutoFetch(true);
    });

    $('#add_farm_reg_no').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            triggerAutoFetch(true);
        }
    });

    $('#add_farm_reg_no').on('blur', function() {
        const val = $(this).val().trim();
        if (val.length >= 3) {
            triggerAutoFetch(false);
        }
    });

    $('#add_farm_reg_no').on('input', function() {
        clearTimeout(addAutoFetchTimer);
        const val = $(this).val().trim();
        if (val.length >= 4) {
            addAutoFetchTimer = setTimeout(function() {
                triggerAutoFetch(false);
            }, 600);
        }
    });

    // =========================================================================
    // Auto-Fetch for Update Beneficiary (Edit Modal)
    // =========================================================================
    let editLastFetchedReg = '';
    let editAutoFetchTimer = null;

    function triggerEditAutoFetch(isManual = false) {
        const regVal = $('#edit_farm_reg_no').val().trim();
        if (!regVal) return;

        if (!isManual && regVal === editLastFetchedReg) return;

        $('.edit-fetch-spinner').show();
        $('.edit-fetch-icon').hide();

        $.ajax({
            url: 'processors/dairy_project_crud.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'lookup_farmer',
                farm_reg_no: regVal
            },
            success: function(res) {
                $('.edit-fetch-spinner').hide();
                $('.edit-fetch-icon').show();

                if (res.success && res.found) {
                    editLastFetchedReg = regVal;
                    const f = res.farmer;
                    $('#edit_beneficiary_name').val(f.farmer_name || '');
                    $('#edit_nic').val(f.nic || '');
                    $('#edit_phone').val(f.phone || '');
                    $('#edit_address').val(f.address || '');

                    if (f.daily_milk > 0 && parseFloat($('#edit_daily_milk_current').val() || 0) === 0) {
                        $('#edit_daily_milk_current').val(f.daily_milk);
                    }

                    const $flashed = $('#edit_beneficiary_name, #edit_nic, #edit_phone, #edit_address, #edit_daily_milk_current');
                    $flashed.addClass('auto-fill-flash');
                    setTimeout(() => { $flashed.removeClass('auto-fill-flash'); }, 1400);

                    if (isManual) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Details Updated',
                            text: `Populated details for ${f.farmer_name} from ${f.source}.`,
                            timer: 1400,
                            showConfirmButton: false
                        });
                    }
                } else if (isManual) {
                    Swal.fire('Not Found', 'No farmer match in master registry.', 'info');
                }
            },
            error: function() {
                $('.edit-fetch-spinner').hide();
                $('.edit-fetch-icon').show();
            }
        });
    }

    $('#btnEditAutoFetch').on('click', function(e) {
        e.preventDefault();
        triggerEditAutoFetch(true);
    });

    $('#edit_farm_reg_no').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            triggerEditAutoFetch(true);
        }
    });

    $('#edit_farm_reg_no').on('blur', function() {
        const val = $(this).val().trim();
        if (val.length >= 3) {
            triggerEditAutoFetch(false);
        }
    });

    $('#edit_farm_reg_no').on('input', function() {
        clearTimeout(editAutoFetchTimer);
        const val = $(this).val().trim();
        if (val.length >= 4) {
            editAutoFetchTimer = setTimeout(function() {
                triggerEditAutoFetch(false);
            }, 600);
        }
    });

    // =========================================================================
    // Auto-calculate Department Contribution
    // =========================================================================
    $('#add_total_cost, #add_beneficiary_contribution').on('input', function() {
        const total = parseFloat($('#add_total_cost').val()) || 0;
        const ben = parseFloat($('#add_beneficiary_contribution').val()) || 0;
        if (total >= ben && ben >= 0) {
            $('#add_department_contribution').val((total - ben).toFixed(2));
        }
    });

    $('#edit_total_cost, #edit_beneficiary_contribution').on('input', function() {
        const total = parseFloat($('#edit_total_cost').val()) || 0;
        const ben = parseFloat($('#edit_beneficiary_contribution').val()) || 0;
        if (total >= ben && ben >= 0) {
            $('#edit_department_contribution').val((total - ben).toFixed(2));
        }
    });

    // =========================================================================
    // Submit Add Summary Form via AJAX
    // =========================================================================
    $('#addSummaryForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $('#btnSubmitSummary').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        $.ajax({
            url: 'processors/dairy_project_crud.php',
            method: 'POST',
            dataType: 'json',
            data: formData,
            success: function(res) {
                $('#btnSubmitSummary').prop('disabled', false).html('<i class="bi bi-save me-1"></i> Save to Summary Register');
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: res.message,
                        confirmButtonColor: '#820100'
                    }).then(() => {
                        window.location.href = `dairy_hub.php?range_id=<?= $range_id ?>&tab=approvals`;
                    });
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: function() {
                $('#btnSubmitSummary').prop('disabled', false).html('<i class="bi bi-save me-1"></i> Save to Summary Register');
                Swal.fire('System Error', 'Failed to communicate with server.', 'error');
            }
        });
    });

    // =========================================================================
    // Open Edit Summary Modal
    // =========================================================================
    $(document).on('click', '.edit-summary-btn', function() {
        const id = $(this).data('id');
        $.ajax({
            url: 'processors/dairy_project_crud.php',
            method: 'GET',
            dataType: 'json',
            data: { action: 'get_summary', id: id },
            success: function(res) {
                if (res.success && res.data) {
                    const d = res.data;
                    $('#edit_id').val(d.id);
                    $('#edit_farm_reg_no').val(d.farm_reg_no);
                    $('#edit_beneficiary_name').val(d.beneficiary_name);
                    $('#edit_nic').val(d.nic);
                    $('#edit_phone').val(d.phone);
                    $('#edit_address').val(d.address);
                    $('#edit_daily_milk_current').val(d.daily_milk_current);
                    $('#edit_daily_milk_expected').val(d.daily_milk_expected);
                    $('#edit_category').val(d.category);
                    $('#edit_activities').val(d.activities);
                    $('#edit_total_cost').val(d.total_cost);
                    $('#edit_beneficiary_contribution').val(d.beneficiary_contribution);
                    $('#edit_department_contribution').val(d.department_contribution);
                    $('#edit_approval_status').val(d.approval_status);
                    $('#edit_remarks').val(d.remarks);

                    editLastFetchedReg = d.farm_reg_no;
                    $('#editSummaryModal').modal('show');
                } else {
                    Swal.fire('Error', res.message || 'Could not fetch record.', 'error');
                }
            }
        });
    });

    // =========================================================================
    // Submit Edit Summary Form via AJAX
    // =========================================================================
    $('#editSummaryForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $('#btnUpdateSummary').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Updating...');

        $.ajax({
            url: 'processors/dairy_project_crud.php',
            method: 'POST',
            dataType: 'json',
            data: formData,
            success: function(res) {
                $('#btnUpdateSummary').prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Update Record');
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        text: res.message,
                        confirmButtonColor: '#820100'
                    }).then(() => {
                        window.location.href = `dairy_hub.php?range_id=<?= $range_id ?>&tab=approvals`;
                    });
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: function() {
                $('#btnUpdateSummary').prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Update Record');
                Swal.fire('Error', 'Server communication failure.', 'error');
            }
        });
    });

    // =========================================================================
    // Delete Summary Entry
    // =========================================================================
    $(document).on('click', '.delete-summary-btn', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const reg = $(this).data('farm_reg');

        Swal.fire({
            title: 'Remove Beneficiary?',
            html: `Are you sure you want to remove <strong>${name}</strong> (${reg}) from the Dairy Project Summary Register?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#820100',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete Entry'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'processors/dairy_project_crud.php',
                    method: 'POST',
                    dataType: 'json',
                    data: { action: 'delete_summary', id: id },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Deleted!', res.message, 'success').then(() => {
                                window.location.href = `dairy_hub.php?range_id=<?= $range_id ?>&tab=approvals`;
                            });
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    }
                });
            }
        });
    });

    // =========================================================================
    // Open Assign Executive Position Modal
    // =========================================================================
    $(document).on('click', '.btn-assign-exec-modal', function() {
        const id = $(this).data('id');
        const name = $(this).data('name') || '';
        const membership = $(this).data('membership') || '';
        const position = $(this).data('position') || 'Member';

        $('#assign_member_id').val(id);
        $('#assign_member_name_display').text(name);
        $('#assign_membership_no_display').text(membership ? 'Membership No: ' + membership : '');
        $('#assign_executive_position').val(position);

        $('#modalAssignExecutive').modal('show');
    });

    // =========================================================================
    // Submit Assign Executive Position Form via AJAX
    // =========================================================================
    $('#formAssignExecutive').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $('#btnSaveExecutive').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        $.ajax({
            url: 'processors/dairy_project_crud.php',
            method: 'POST',
            dataType: 'json',
            data: formData,
            success: function(res) {
                $('#btnSaveExecutive').prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Save Executive Position');
                if (res.success) {
                    $('#modalAssignExecutive').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Position Assigned',
                        text: res.message,
                        confirmButtonColor: '#820100'
                    }).then(() => {
                        window.location.href = `dairy_hub.php?range_id=<?= $range_id ?>&tab=society`;
                    });
                } else {
                    Swal.fire('Error', res.message || 'Failed to update executive position.', 'error');
                }
            },
            error: function() {
                $('#btnSaveExecutive').prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Save Executive Position');
                Swal.fire('Error', 'Server communication failure.', 'error');
            }
        });
    });

    // =========================================================================
    // Open Edit Code of Conduct Modal
    // =========================================================================
    $('#btnEditCodeInline').on('click', function() {
        $('#modalEditCodeOfConduct').modal('show');
    });

    // =========================================================================
    // Submit Edit Code of Conduct Form via AJAX
    // =========================================================================
    $('#formEditCodeOfConduct').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $('#btnSaveCodeOfConduct').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        $.ajax({
            url: 'processors/dairy_project_crud.php',
            method: 'POST',
            dataType: 'json',
            data: formData,
            success: function(res) {
                $('#btnSaveCodeOfConduct').prop('disabled', false).html('<i class="bi bi-save me-1"></i> Save Formal Code of Conduct');
                if (res.success) {
                    $('#modalEditCodeOfConduct').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Charter Enacted & Saved',
                        text: res.message,
                        confirmButtonColor: '#1e3a8a'
                    }).then(() => {
                        window.location.href = `dairy_hub.php?range_id=<?= $range_id ?>&tab=society`;
                    });
                } else {
                    Swal.fire('Error', res.message || 'Failed to update Code of Conduct.', 'error');
                }
            },
            error: function() {
                $('#btnSaveCodeOfConduct').prop('disabled', false).html('<i class="bi bi-save me-1"></i> Save Formal Code of Conduct');
                Swal.fire('Error', 'Server communication failure.', 'error');
            }
        });
    // =========================================================================
    // Dairy Hub Scheduled Farm Monitoring Visits Handlers
    // =========================================================================
    window.openDairyFarmVisitModal = function(farmRegNo, farmerName, dhId) {
        $('#dh_visit_dh_id').val(dhId || 0);
        $('#dh_visit_farm_reg_no').val(farmRegNo || '');
        $('#dh_visit_farmer_name').val(farmerName || '');
        $('#dairyFarmVisitModal').modal('show');
    };

    $('#dairyMonitoringVisitForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        $.post('processors/project_monitoring_crud.php', formData, function(res) {
            if (res.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Visit Logged',
                    text: res.message,
                    confirmButtonColor: '#820100'
                }).then(() => {
                    $('#dairyFarmVisitModal').modal('hide');
                    location.reload();
                });
            } else {
                Swal.fire('Error', res.message || 'Failed to record visit log.', 'error');
            }
        }, 'json').fail(function() {
            Swal.fire('Error', 'Communication error saving inspection log.', 'error');
        });
    });
});
</script>
<?php
$pageScripts = ob_get_clean();
require_once __DIR__ . '/../../../includes/footer.php';
?>