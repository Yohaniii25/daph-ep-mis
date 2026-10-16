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
$active_tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'approvals'; // 'approvals' (default) or 'collections'

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

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">

<style>
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
    .nav-tabs-dairy .nav-link {
        font-weight: 600;
        color: #4b5563;
        padding: 0.75rem 1.25rem;
        border: none;
        border-bottom: 3px solid transparent;
        transition: all 0.2s ease;
    }
    .nav-tabs-dairy .nav-link:hover {
        color: #820100;
        border-color: #f3f4f6;
    }
    .nav-tabs-dairy .nav-link.active {
        color: #820100;
        background: transparent;
        border-bottom: 3px solid #820100;
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

    <!-- Hero Header Banner -->
    <div class="card dairy-hub-header mb-4 border-0">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="badge bg-light text-dark fw-bold px-3 py-1">
                            <i class="bi bi-geo-alt-fill me-1 text-danger"></i> <?= htmlspecialchars($current_range_name) ?>
                        </span>
                        <span class="badge bg-warning text-dark fw-bold px-3 py-1">
                            <i class="bi bi-award-fill me-1"></i> National Dairy Production Programme
                        </span>
                        <span class="badge bg-success-subtle text-white border border-light px-3 py-1">
                            <i class="bi bi-shield-check me-1"></i> Master Registry Auto-Sync
                        </span>
                    </div>
                    <h3 class="fw-bold text-white mb-1">Dairy Development &amp; Beneficiary Approval Hub</h3>
                    <p class="mb-0 text-light opacity-90 small">
                        Centrally approve farmers for dairy subsidies, verify production quotas, and link directly to detailed farm potential surveys under the National Dairy Production Programme.
                    </p>
                </div>
                <div class="col-lg-4 mt-3 mt-lg-0 text-lg-end">
                    <div class="bg-white bg-opacity-10 p-3 rounded-3 d-inline-block text-start w-100" style="max-width: 320px;">
                        <div class="d-flex justify-content-between text-light small mb-1">
                            <span>Beneficiaries Enrolled:</span>
                            <span class="fw-bold text-white"><?= number_format($total_beneficiaries) ?> Farmers</span>
                        </div>
                        <div class="d-flex justify-content-between text-light small mb-1">
                            <span>Surveys Completed:</span>
                            <span class="fw-bold text-warning"><?= number_format($total_forms_completed) ?> / <?= number_format($total_beneficiaries) ?></span>
                        </div>
                        <div class="d-flex justify-content-between text-light small pt-1 border-top border-light border-opacity-25">
                            <span>Target Milk Increase:</span>
                            <span class="badge bg-light text-dark fw-bold">
                                <?= number_format($total_milk_current, 1) ?>L &rarr; <?= number_format($total_milk_expected, 1) ?>L /day
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs between Approvals and Collections -->
    <ul class="nav nav-tabs nav-tabs-dairy mb-4 border-bottom" id="dairyHubTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($active_tab === 'approvals') ? 'active' : '' ?>" id="approvals-tab" data-bs-toggle="tab" data-bs-target="#approvals-pane" type="button" role="tab">
                <i class="bi bi-card-checklist me-2"></i> Beneficiary Summary &amp; Approvals
                <span class="badge bg-danger rounded-pill ms-2"><?= $total_beneficiaries ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($active_tab === 'collections') ? 'active' : '' ?>" id="collections-tab" data-bs-toggle="tab" data-bs-target="#collections-pane" type="button" role="tab">
                <i class="bi bi-droplet-half me-2"></i> Milk Collection &amp; Quality Logs
                <span class="badge bg-secondary rounded-pill ms-2"><?= count($records) ?></span>
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
                            <button class="btn text-light fw-bold px-3" type="button" id="btnAutoFetch" style="background-color: #820100;">
                                <span class="spinner-border spinner-border-sm auto-fetch-spinner me-1" role="status" aria-hidden="true"></span>
                                <i class="bi bi-cloud-arrow-down-fill me-1 fetch-icon"></i> Auto-Fetch
                            </button>
                        </div>
                        <div id="fetchFeedback" class="small mt-1 text-muted">
                            <i class="bi bi-info-circle me-1"></i>Enter Farm Registration No or NIC and click Auto-Fetch. Beneficiary's Name, Address, NIC, and Phone will automatically populate from the Master Registry.
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
                            <input type="text" id="edit_farm_reg_no" name="farm_reg_no" class="form-control form-control-sm font-monospace fw-bold" required>
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

<?php 
// Include existing Milk Collection modal if present
if (file_exists(__DIR__ . '/model/add_dairy_record_modal.php')) {
    include __DIR__ . '/model/add_dairy_record_modal.php';
}
?>

<script src="../../../assets/js/jquery.min.js"></script>
<script src="../../../assets/js/bootstrap.bundle.min.js"></script>
<script src="../../../assets/js/jquery.dataTables.min.js"></script>
<script src="../../../assets/js/dataTables.bootstrap5.min.js"></script>
<script src="../../../assets/js/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    // 1. Initialize Beneficiaries Summary Table
    $('#beneficiarySummaryTable').DataTable({
        pageLength: 20,
        responsive: true,
        order: [[0, 'asc']],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search beneficiaries..."
        }
    });

    // 2. Initialize Milk Collection Table
    $('#dairyTable').DataTable({
        pageLength: 15,
        responsive: true,
        order: [[0, 'desc']],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search collections..."
        }
    });

    // Auto-Fetch implementation for Add Modal
    function triggerAutoFetch() {
        const regVal = $('#add_farm_reg_no').val().trim();
        if (!regVal) {
            Swal.fire({
                icon: 'warning',
                title: 'Registration Number Required',
                text: 'Please input a Farm Registration Number or NIC before fetching.',
                confirmButtonColor: '#820100'
            });
            return;
        }

        $('.auto-fetch-spinner').show();
        $('.fetch-icon').hide();
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

                if (res.success && res.found) {
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

                    $('#regSourceBadge').text(f.source).removeClass('bg-light text-dark').addClass('bg-success text-white');
                    $('#fetchFeedback').html(`<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Farmer details populated from ${f.source}!</span>`);

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
                }
            },
            error: function() {
                $('.auto-fetch-spinner').hide();
                $('.fetch-icon').show();
                $('#fetchFeedback').html('<span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i>Network error connecting to master registry.</span>');
            }
        });
    }

    $('#btnAutoFetch').on('click', triggerAutoFetch);

    // Auto-calculate Department Contribution when Total Cost and Beneficiary Contribution change
    $('#add_total_cost, #add_beneficiary_contribution').on('input', function() {
        const total = parseFloat($('#add_total_cost').val()) || 0;
        const ben = parseFloat($('#add_beneficiary_contribution').val()) || 0;
        if (total >= ben && ben > 0) {
            $('#add_department_contribution').val((total - ben).toFixed(2));
        }
    });

    $('#edit_total_cost, #edit_beneficiary_contribution').on('input', function() {
        const total = parseFloat($('#edit_total_cost').val()) || 0;
        const ben = parseFloat($('#edit_beneficiary_contribution').val()) || 0;
        if (total >= ben && ben > 0) {
            $('#edit_department_contribution').val((total - ben).toFixed(2));
        }
    });

    // Submit Add Summary Form via AJAX
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

    // Open Edit Summary Modal
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

                    $('#editSummaryModal').modal('show');
                } else {
                    Swal.fire('Error', res.message || 'Could not fetch record.', 'error');
                }
            }
        });
    });

    // Submit Edit Summary Form via AJAX
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

    // Delete Summary Entry
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
});
</script>

<?php require_once '../../../includes/footer.php'; ?>