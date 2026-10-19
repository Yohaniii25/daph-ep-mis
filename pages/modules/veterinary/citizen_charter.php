<?php
/**
 * pages/modules/veterinary/citizen_charter.php
 * Citizen Charter Module: Non-Clinical Requests Register
 * 
 * Tracks daily non-clinical service requests:
 * - Bank loan recommendation letters, ear tag issuances, project inquiries, transport permits.
 * - Captures: Date, Request Origin ("Direct Visit" vs "By Call"), Beneficiary particulars,
 *   Purpose of Visit, Immediate Action Taken, and Final Remarks.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Seamlessly forward to the unified Services Register tab in clinical_services.php
$forward_params = $_GET;
$forward_params['tab'] = 'services_register';
if (!isset($forward_params['range_id']) && !empty($_SESSION['range_id'])) {
    $forward_params['range_id'] = $_SESSION['range_id'];
}
$forward_query = http_build_query($forward_params);
header("Location: clinical_services.php?" . $forward_query);
exit();

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
    'planning_officer',
    'employee',
    'livestock_development_officer'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    header("Location: ../../../index.php");
    exit();
}

$user_role   = $_SESSION['role'] ?? '';
$user_id     = intval($_SESSION['user_id'] ?? 0);
$session_range_id    = !empty($_SESSION['range_id']) ? intval($_SESSION['range_id']) : 1;
$session_district_id = !empty($_SESSION['district_id']) ? intval($_SESSION['district_id']) : 1;

// Determine active range_id (support GET parameter e.g. citizen_charter.php?range_id=1, fallback to session)
$requested_range_id = isset($_GET['range_id']) && is_numeric($_GET['range_id']) ? intval($_GET['range_id']) : null;
$range_id = $requested_range_id !== null ? $requested_range_id : ($session_range_id ?: 1);

// Filters
$filter_origin = trim($_GET['origin'] ?? 'all'); // 'all', 'Direct Visit', 'By Call'
$filter_status = trim($_GET['status'] ?? 'all');
$filter_year   = isset($_GET['year']) && is_numeric($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$filter_month  = isset($_GET['month']) && is_numeric($_GET['month']) ? intval($_GET['month']) : intval(date('n'));

// Fetch Ranges list
$ranges_list = [];
$ranges_q = $mysqli->query("SELECT r.id, r.name, r.district_id, d.name AS district_name FROM veterinary_ranges r LEFT JOIN districts d ON r.district_id = d.id ORDER BY d.name ASC, r.name ASC");
if ($ranges_q) {
    while ($r = $ranges_q->fetch_assoc()) {
        $ranges_list[] = $r;
    }
}

// Current Range details
$current_range_name = 'Range #' . $range_id;
$current_district_name = 'Eastern Province';
$district_id = $session_district_id;
foreach ($ranges_list as $rng) {
    if ($rng['id'] == $range_id) {
        $current_range_name = $rng['name'];
        $current_district_name = $rng['district_name'];
        $district_id = intval($rng['district_id']);
        break;
    }
}

// -----------------------------------------------------------------------------
// 2. Fetch Non-Clinical Requests with Filters
// -----------------------------------------------------------------------------
$where_clauses = ["(c.range_id = ? OR c.range_id IS NULL OR c.range_id = 0)"];
$params = [$range_id];
$types  = "i";

if ($filter_year > 0) {
    $where_clauses[] = "YEAR(c.request_date) = ?";
    $params[] = $filter_year;
    $types   .= "i";
}

if ($filter_month > 0) {
    $where_clauses[] = "MONTH(c.request_date) = ?";
    $params[] = $filter_month;
    $types   .= "i";
}

if ($filter_origin !== 'all' && in_array($filter_origin, ['Direct Visit', 'By Call'], true)) {
    $where_clauses[] = "c.request_origin = ?";
    $params[] = $filter_origin;
    $types   .= "s";
}

if ($filter_status !== 'all' && !empty($filter_status)) {
    $where_clauses[] = "c.status = ?";
    $params[] = $filter_status;
    $types   .= "s";
}

$where_sql = implode(' AND ', $where_clauses);
$query_sql = "
    SELECT c.*, u.full_name AS officer_name 
    FROM citizen_charter_requests c
    LEFT JOIN users u ON c.created_by = u.id
    WHERE {$where_sql}
    ORDER BY c.request_date DESC, c.id DESC
";

$stmt = $mysqli->prepare($query_sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$records_result = $stmt->get_result();
$records = [];
while ($row = $records_result->fetch_assoc()) {
    $records[] = $row;
}
$stmt->close();

// -----------------------------------------------------------------------------
// 3. KPI Summary Statistics
// -----------------------------------------------------------------------------
$stat_total_requests = count($records);
$stat_direct_visits  = 0;
$stat_by_calls       = 0;
$stat_completed      = 0;
$stat_pending        = 0;

foreach ($records as $rec) {
    if ($rec['request_origin'] === 'Direct Visit') $stat_direct_visits++;
    if ($rec['request_origin'] === 'By Call') $stat_by_calls++;
    if ($rec['status'] === 'Completed') $stat_completed++;
    if ($rec['status'] === 'Pending' || $rec['status'] === 'In Progress') $stat_pending++;
}

$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

$pageTitle = "Citizen Charter: Other Services (Non-Clinical Requests) - " . $current_range_name;
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<style>
:root {
    --primary-color: #500707;
    --primary-gradient: linear-gradient(135deg, #500707 0%, #750d0d 100%);
    --accent-gold: #d97706;
    --border-radius: 12px;
}

.kpi-card {
    border-radius: var(--border-radius);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-left: 4px solid var(--accent-gold);
}
.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08) !important;
}

.table-charter {
    font-size: 0.88rem;
    border-collapse: separate;
    border-spacing: 0;
}
.table-charter thead th {
    background-color: #2b2b2b !important;
    color: #ffffff !important;
    font-weight: 600;
    vertical-align: middle;
    border-color: #404040 !important;
    font-size: 0.82rem;
    padding: 10px 8px;
    letter-spacing: 0.02em;
}
.table-charter tbody td {
    vertical-align: middle;
    padding: 10px 8px;
    border-color: #e5e7eb;
}
.table-charter tbody tr:hover {
    background-color: #f8fafc;
}

.badge-origin-visit {
    background-color: #dbeafe;
    color: #1e40af;
    border: 1px solid #bfdbfe;
    font-weight: 600;
}
.badge-origin-call {
    background-color: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
    font-weight: 600;
}

.auto-fill-flash {
    animation: flashBg 1.2s ease-in-out;
}
@keyframes flashBg {
    0% { background-color: #ecfdf5; }
    50% { background-color: #a7f3d0; }
    100% { background-color: #ffffff; }
}

@media print {
    body * { visibility: hidden; }
    #printSlipContent, #printSlipContent * { visibility: visible; }
    #printSlipContent {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 20px;
    }
    .no-print { display: none !important; }
}
</style>

<div id="layoutSidenav_content">
    <main class="p-3 p-md-4">
        <div class="container-fluid px-0">

            <!-- Breadcrumb Navigation -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb small">
                    <li class="breadcrumb-item"><a href="../../../dashboard.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="range_details.php?range_id=<?= $range_id ?>" class="text-decoration-none">Range Details</a></li>
                    <li class="breadcrumb-item">Services</li>
                    <li class="breadcrumb-item active" aria-current="page">Citizen Charter (Other Services)</li>
                </ol>
            </nav>

            <!-- Module Header Banner -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: var(--border-radius); background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #ffffff;">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background: rgba(217, 119, 6, 0.2); border: 2px solid #d97706;">
                                <i class="bi bi-file-earmark-person-fill fs-2 text-warning"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-warning text-dark fw-bold text-uppercase px-2 py-1" style="font-size: 0.7rem; letter-spacing: 0.05em;">Citizen Charter</span>
                                    <span class="badge bg-secondary-subtle text-light border px-2 py-1" style="font-size: 0.7rem;">Non-Clinical Register</span>
                                </div>
                                <h3 class="fw-bold mb-1">Other Services: Citizen Charter Inquiries &amp; Action Register</h3>
                                <p class="mb-0 text-light text-opacity-75 small">
                                    Official daily intake register for non-clinical requests &bull; Bank loan recommendation letters, ear tag issuances, project subsidies &amp; farmer queries.
                                </p>
                            </div>
                        </div>

                        <!-- Action Controls -->
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <!-- Range Context Selector -->
                            <form method="GET" action="citizen_charter.php" class="d-flex align-items-center gap-2">
                                <input type="hidden" name="origin" value="<?= htmlspecialchars($filter_origin) ?>">
                                <input type="hidden" name="status" value="<?= htmlspecialchars($filter_status) ?>">
                                <input type="hidden" name="year" value="<?= $filter_year ?>">
                                <input type="hidden" name="month" value="<?= $filter_month ?>">
                                <select name="range_id" class="form-select form-select-sm fw-semibold shadow-sm" onchange="this.form.submit()" style="min-width: 200px;">
                                    <?php foreach ($ranges_list as $rng): ?>
                                        <option value="<?= $rng['id'] ?>" <?= ($rng['id'] == $range_id) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($rng['name']) ?> (<?= htmlspecialchars($rng['district_name'] ?? 'Range') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>

                            <button type="button" class="btn btn-warning fw-bold btn-sm px-3 shadow-sm" id="btnOpenIntakeModal">
                                <i class="bi bi-plus-circle me-1"></i> Log Non-Clinical Request
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI Metric Counters -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card kpi-card shadow-sm h-100 p-3 bg-white" style="border-left-color: #3b82f6;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted fw-bold text-uppercase" style="font-size: 0.72rem;">Total Inquiries</small>
                                <h3 class="fw-bold text-dark my-1"><?= $stat_total_requests ?></h3>
                                <small class="text-muted" style="font-size: 0.75rem;"><?= $month_names[$filter_month] ?? 'Month' ?> <?= $filter_year ?></small>
                            </div>
                            <div class="rounded-circle p-3 bg-primary-subtle text-primary">
                                <i class="bi bi-folder2-open fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="card kpi-card shadow-sm h-100 p-3 bg-white" style="border-left-color: #1d4ed8;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted fw-bold text-uppercase" style="font-size: 0.72rem;">Direct Visits</small>
                                <h3 class="fw-bold text-primary my-1"><?= $stat_direct_visits ?></h3>
                                <small class="text-primary fw-semibold" style="font-size: 0.75rem;">
                                    <i class="bi bi-person-walking me-1"></i>Walk-in Clients
                                </small>
                            </div>
                            <div class="rounded-circle p-3 bg-info-subtle text-info">
                                <i class="bi bi-person-check-fill fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="card kpi-card shadow-sm h-100 p-3 bg-white" style="border-left-color: #d97706;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted fw-bold text-uppercase" style="font-size: 0.72rem;">By Call Requests</small>
                                <h3 class="fw-bold text-warning my-1"><?= $stat_by_calls ?></h3>
                                <small class="text-warning-emphasis fw-semibold" style="font-size: 0.75rem;">
                                    <i class="bi bi-telephone-inbound me-1"></i>Telephone Queries
                                </small>
                            </div>
                            <div class="rounded-circle p-3 bg-warning-subtle text-warning-emphasis">
                                <i class="bi bi-telephone-fill fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="card kpi-card shadow-sm h-100 p-3 bg-white" style="border-left-color: #10b981;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted fw-bold text-uppercase" style="font-size: 0.72rem;">Actions Completed</small>
                                <h3 class="fw-bold text-success my-1"><?= $stat_completed ?></h3>
                                <small class="text-success fw-semibold" style="font-size: 0.75rem;">
                                    <i class="bi bi-check2-circle me-1"></i>Resolved &amp; Attended
                                </small>
                            </div>
                            <div class="rounded-circle p-3 bg-success-subtle text-success">
                                <i class="bi bi-patch-check-fill fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Toolbar -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: var(--border-radius);">
                <div class="card-body p-3">
                    <form method="GET" action="citizen_charter.php" class="row g-2 align-items-center">
                        <input type="hidden" name="range_id" value="<?= $range_id ?>">

                        <!-- Request Origin Filter Tabs -->
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-bold text-muted mb-1 d-block">Request Origin Filter:</label>
                            <div class="btn-group btn-group-sm w-100" role="group">
                                <a href="citizen_charter.php?range_id=<?= $range_id ?>&year=<?= $filter_year ?>&month=<?= $filter_month ?>&origin=all&status=<?= htmlspecialchars($filter_status) ?>"
                                   class="btn <?= ($filter_origin === 'all') ? 'btn-dark' : 'btn-outline-secondary' ?>">
                                    All (<?= $stat_total_requests ?>)
                                </a>
                                <a href="citizen_charter.php?range_id=<?= $range_id ?>&year=<?= $filter_year ?>&month=<?= $filter_month ?>&origin=Direct Visit&status=<?= htmlspecialchars($filter_status) ?>"
                                   class="btn <?= ($filter_origin === 'Direct Visit') ? 'btn-primary' : 'btn-outline-primary' ?>">
                                    <i class="bi bi-person-walking me-1"></i> Direct Visit (<?= $stat_direct_visits ?>)
                                </a>
                                <a href="citizen_charter.php?range_id=<?= $range_id ?>&year=<?= $filter_year ?>&month=<?= $filter_month ?>&origin=By Call&status=<?= htmlspecialchars($filter_status) ?>"
                                   class="btn <?= ($filter_origin === 'By Call') ? 'btn-warning text-dark' : 'btn-outline-warning text-dark' ?>">
                                    <i class="bi bi-telephone me-1"></i> By Call (<?= $stat_by_calls ?>)
                                </a>
                            </div>
                        </div>

                        <!-- Reporting Month -->
                        <div class="col-6 col-md-2">
                            <label class="form-label small fw-bold text-muted mb-1">Month:</label>
                            <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= ($filter_month == $m) ? 'selected' : '' ?>><?= $month_names[$m] ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- Reporting Year -->
                        <div class="col-6 col-md-2">
                            <label class="form-label small fw-bold text-muted mb-1">Year:</label>
                            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                                <?php for ($y = date('Y') + 1; $y >= 2020; $y--): ?>
                                    <option value="<?= $y ?>" <?= ($filter_year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div class="col-6 col-md-2">
                            <label class="form-label small fw-bold text-muted mb-1">Status:</label>
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="all" <?= ($filter_status === 'all') ? 'selected' : '' ?>>All Statuses</option>
                                <option value="Completed" <?= ($filter_status === 'Completed') ? 'selected' : '' ?>>Completed</option>
                                <option value="Pending" <?= ($filter_status === 'Pending') ? 'selected' : '' ?>>Pending</option>
                                <option value="In Progress" <?= ($filter_status === 'In Progress') ? 'selected' : '' ?>>In Progress</option>
                                <option value="Referred" <?= ($filter_status === 'Referred') ? 'selected' : '' ?>>Referred</option>
                            </select>
                        </div>

                        <!-- Reset / Export -->
                        <div class="col-6 col-md-2 d-flex align-items-end gap-1">
                            <a href="citizen_charter.php?range_id=<?= $range_id ?>" class="btn btn-sm btn-outline-secondary w-50" title="Reset Filters">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-success w-50" id="btnExportCharterCsv" title="Export to CSV">
                                <i class="bi bi-file-earmark-spreadsheet"></i> CSV
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- =============================================================== -->
            <!-- CITIZEN CHARTER NON-CLINICAL REQUESTS REGISTER TABLE            -->
            <!-- =============================================================== -->
            <div class="card border-0 shadow-sm" style="border-radius: var(--border-radius);">
                <div class="card-header bg-white py-3 border-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-journal-bookmark-fill text-warning me-2"></i>Citizen Charter Daily Non-Clinical Intake Register
                        </h5>
                        <small class="text-muted">
                            Veterinary Range: <?= htmlspecialchars($current_range_name) ?> &bull; Period: <?= $month_names[$filter_month] ?> <?= $filter_year ?>
                        </small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark border px-3 py-2 fw-semibold">
                            <i class="bi bi-card-checklist me-1 text-primary"></i> <?= count($records) ?> Records Logged
                        </span>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-charter mb-0" id="tableCitizenCharter">
                            <thead>
                                <tr>
                                    <th style="min-width: 95px;" class="text-center">Date</th>
                                    <th style="min-width: 130px;" class="text-center">Ref. No.</th>
                                    <th style="min-width: 120px;" class="text-center">Request Origin</th>
                                    <th style="min-width: 180px;">Beneficiary / Farmer Details</th>
                                    <th style="min-width: 130px;">Contact &amp; Farm Reg</th>
                                    <th style="min-width: 220px;">Purpose of Visit</th>
                                    <th style="min-width: 220px;">Immediate Action Taken</th>
                                    <th style="min-width: 160px;">Final Remarks</th>
                                    <th style="min-width: 95px;" class="text-center">Status</th>
                                    <th style="min-width: 110px;" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($records)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                            No Citizen Charter service requests logged for this period. Click <strong>Log Non-Clinical Request</strong> to record an intake.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($records as $r): ?>
                                        <tr>
                                            <!-- 1. Date -->
                                            <td class="text-center">
                                                <div class="fw-semibold text-dark"><?= date('d-M-Y', strtotime($r['request_date'])) ?></div>
                                            </td>

                                            <!-- 2. Ref No. -->
                                            <td class="text-center font-monospace">
                                                <span class="badge bg-light text-dark border fw-bold px-2 py-1">
                                                    <?= htmlspecialchars($r['reference_no']) ?>
                                                </span>
                                            </td>

                                            <!-- 3. Request Origin Dropdown Value -->
                                            <td class="text-center">
                                                <?php if ($r['request_origin'] === 'By Call'): ?>
                                                    <span class="badge badge-origin-call px-2 py-1">
                                                        <i class="bi bi-telephone-fill me-1"></i> By Call
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-origin-visit px-2 py-1">
                                                        <i class="bi bi-person-walking me-1"></i> Direct Visit
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- 4. Beneficiary Details: Name, NIC, Address -->
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($r['beneficiary_name']) ?></div>
                                                <?php if (!empty($r['nic_no'])): ?>
                                                    <small class="text-muted font-monospace d-block">NIC: <?= htmlspecialchars($r['nic_no']) ?></small>
                                                <?php endif; ?>
                                                <?php if (!empty($r['address'])): ?>
                                                    <small class="text-muted d-block text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($r['address']) ?>">
                                                        <i class="bi bi-geo-alt me-1 text-danger"></i><?= htmlspecialchars($r['address']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>

                                            <!-- 5. Phone & Farm Reg No -->
                                            <td>
                                                <?php if (!empty($r['phone_no'])): ?>
                                                    <div class="fw-semibold font-monospace small mb-1">
                                                        <i class="bi bi-telephone text-primary me-1"></i><?= htmlspecialchars($r['phone_no']) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($r['farm_reg_no'])): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success font-monospace" style="font-size: 0.72rem;">
                                                        <i class="bi bi-house-check me-1"></i><?= htmlspecialchars($r['farm_reg_no']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted small">—</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- 6. Purpose of Visit -->
                                            <td>
                                                <div class="text-dark small fw-semibold" style="line-height: 1.35;">
                                                    <?= nl2br(htmlspecialchars($r['purpose_of_visit'])) ?>
                                                </div>
                                            </td>

                                            <!-- 7. Immediate Action Taken -->
                                            <td>
                                                <div class="text-secondary small" style="line-height: 1.35;">
                                                    <?= nl2br(htmlspecialchars($r['immediate_action_taken'])) ?>
                                                </div>
                                            </td>

                                            <!-- 8. Final Remarks -->
                                            <td>
                                                <div class="text-muted small">
                                                    <?= htmlspecialchars($r['final_remarks'] ?: '—') ?>
                                                </div>
                                            </td>

                                            <!-- 9. Status -->
                                            <td class="text-center">
                                                <?php if ($r['status'] === 'Completed'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success">Completed</span>
                                                <?php elseif ($r['status'] === 'Pending'): ?>
                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning">Pending</span>
                                                <?php elseif ($r['status'] === 'In Progress'): ?>
                                                    <span class="badge bg-info-subtle text-info border border-info">In Progress</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-subtle text-secondary border">Referred</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- 10. Actions -->
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-dark btn-view-request" data-id="<?= $r['id'] ?>" title="View &amp; Print Official Action Slip">
                                                        <i class="bi bi-printer"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-primary btn-edit-request" data-id="<?= $r['id'] ?>" title="Edit Request">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger btn-delete-request" data-id="<?= $r['id'] ?>" data-name="<?= htmlspecialchars($r['beneficiary_name']) ?>" data-ref="<?= htmlspecialchars($r['reference_no']) ?>" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
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
    </main>
</div>

<!-- =========================================================================== -->
<!-- MODAL: LOG / EDIT CITIZEN CHARTER REQUEST                                   -->
<!-- =========================================================================== -->
<div class="modal fade" id="modalCharterIntake" tabindex="-1" aria-labelledby="modalCharterIntakeLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: var(--border-radius);">
            <div class="modal-header text-white" id="modalCharterHeader" style="background: linear-gradient(135deg, #b45309 0%, #78350f 100%);">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-person-fill fs-4 text-warning"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="modalCharterIntakeLabel">Log Non-Clinical Service Request</h5>
                        <small class="text-white-50">Citizen Charter Intake Register &bull; <?= htmlspecialchars($current_range_name) ?></small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="formCitizenCharter" autocomplete="off">
                <input type="hidden" name="action" value="save_request">
                <input type="hidden" name="id" id="charter_id" value="0">
                <input type="hidden" name="range_id" value="<?= $range_id ?>">
                <input type="hidden" name="district_id" value="<?= $district_id ?>">

                <div class="modal-body p-4">

                    <!-- Top Row: Date & Request Origin Dropdown (Required) -->
                    <div class="row g-3 mb-3 p-3 rounded bg-light border">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">
                                <i class="bi bi-calendar-event me-1 text-primary"></i> Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" name="request_date" id="request_date" value="<?= date('Y-m-d') ?>" required>
                            <div class="form-text small">Date the non-clinical request was received.</div>
                        </div>

                        <!-- Specific "Request Origin" Dropdown with Direct Visit and By Call -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">
                                <i class="bi bi-telephone-inbound-fill me-1 text-warning"></i> Request Origin <span class="text-danger">*</span>
                            </label>
                            <select class="form-select fw-semibold" name="request_origin" id="request_origin" required>
                                <option value="Direct Visit" selected>Direct Visit (In-Person Walk-in)</option>
                                <option value="By Call">By Call (Telephone Inquiry)</option>
                            </select>
                            <div class="form-text small">Choose whether the client arrived in person or inquired via phone.</div>
                        </div>
                    </div>

                    <!-- Beneficiary / Farmer Particulars with Dual Auto-Fetch -->
                    <div class="card border mb-3 shadow-none">
                        <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between border-bottom">
                            <span class="fw-bold text-dark small text-uppercase">
                                <i class="bi bi-person-vcard text-primary me-1"></i> Beneficiary / Farmer Details
                            </span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle small">
                                <i class="bi bi-lightning-charge me-1"></i>Dual-Trigger Auto-Fetch (NIC / Farm Reg No)
                            </span>
                        </div>
                        <div class="card-body p-3">
                            <!-- Dual-Trigger Inputs -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Identity Card Number (NIC)</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control font-monospace text-uppercase" name="nic_no" id="nic_no" placeholder="e.g. 198512345678">
                                        <button class="btn btn-outline-primary" type="button" id="btnAutoFetchNic" title="Auto-Fetch from master registry">
                                            <i class="bi bi-search" id="iconFetchNic"></i>
                                            <span class="spinner-border spinner-border-sm d-none" id="spinnerFetchNic" role="status"></span>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Farm Registration Number (If applicable)</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control font-monospace" name="farm_reg_no" id="farm_reg_no" placeholder="e.g. 530112345">
                                        <button class="btn btn-outline-success" type="button" id="btnAutoFetchFarm" title="Auto-Fetch from master registry">
                                            <i class="bi bi-search" id="iconFetchFarm"></i>
                                            <span class="spinner-border spinner-border-sm d-none" id="spinnerFetchFarm" role="status"></span>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-12" id="fetchFeedback" style="display: none;"></div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Beneficiary / Farmer Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="beneficiary_name" id="beneficiary_name" placeholder="Full name of beneficiary or farmer" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Phone Number</label>
                                    <input type="text" class="form-control font-monospace" name="phone_no" id="phone_no" placeholder="e.g. 0771234567">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-dark">Address / Location</label>
                                    <input type="text" class="form-control" name="address" id="address" placeholder="Residential / Farm address and GN division">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Purpose of Visit & Presets -->
                    <div class="card border mb-3 shadow-none">
                        <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between border-bottom">
                            <span class="fw-bold text-dark small text-uppercase">
                                <i class="bi bi-chat-left-dots-fill text-warning me-1"></i> Purpose of Visit &amp; Immediate Action Taken
                            </span>
                        </div>
                        <div class="card-body p-3">
                            <!-- Quick Preset Badges -->
                            <div class="mb-2">
                                <small class="text-muted fw-bold d-block mb-1">Quick Select Common Purposes:</small>
                                <div class="d-flex flex-wrap gap-1">
                                    <button type="button" class="btn btn-xs btn-outline-secondary btn-preset-purpose" data-purpose="Bank Loan Recommendation Letter for Dairy Expansion">Bank Loan Letter</button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary btn-preset-purpose" data-purpose="Ear Tag Issuance &amp; Livestock Registration">Ear Tag Issuance</button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary btn-preset-purpose" data-purpose="Dairy Development Project Inquiry &amp; Subsidy Application">Dairy Project Inquiry</button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary btn-preset-purpose" data-purpose="Livestock Transport Permit &amp; Health Clearance">Transport Permit</button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary btn-preset-purpose" data-purpose="Fodder &amp; Pasture Cutting Material Request">Pasture Cuttings</button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary btn-preset-purpose" data-purpose="Livestock Farmer Training Inquiry">Farmer Training</button>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-dark">Purpose of Visit <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="purpose_of_visit" id="purpose_of_visit" rows="2" 
                                              placeholder="Specify purpose of visit (e.g., Request for bank loan recommendation letter, inquiries regarding government cattle subsidy, issuance of replacement ear tags)..." required></textarea>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold text-dark">Immediate Action Taken <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="immediate_action_taken" id="immediate_action_taken" rows="2" 
                                              placeholder="Describe immediate steps executed (e.g., Issued bank recommendation letter Ref: BK-2026/04; Dispensed 10 ear tags with tag register entry; Guided beneficiary on submission guidelines)..." required></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Final Remarks and Status -->
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-dark">Final Remarks (Optional)</label>
                            <input type="text" class="form-control" name="final_remarks" id="final_remarks" placeholder="Follow-up deadline, referral officer, or concluding administrative remarks">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Service Status</label>
                            <select class="form-select fw-semibold" name="status" id="status">
                                <option value="Completed" selected>Completed</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Pending">Pending</option>
                                <option value="Referred">Referred</option>
                            </select>
                        </div>
                    </div>

                </div>

                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning fw-bold px-4 text-dark" id="btnSaveCharterRequest">
                        <span id="spinnerSave" class="spinner-border spinner-border-sm d-none me-1" role="status"></span>
                        <i class="bi bi-check2-circle me-1" id="iconSave"></i> Save Citizen Charter Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =========================================================================== -->
<!-- MODAL: VIEW & PRINT OFFICIAL CITIZEN CHARTER ACTION SLIP                    -->
<!-- =========================================================================== -->
<div class="modal fade" id="modalViewCharterSlip" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: var(--border-radius);">
            <div class="modal-header bg-dark text-white">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-text fs-4 text-warning"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Citizen Charter Service Slip</h5>
                        <small class="text-white-50">Department of Animal Production &amp; Health &bull; Eastern Province</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="viewCharterSlipBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-warning" role="status"></div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Print Action Slip
                </button>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
$(document).ready(function() {
    // 1. Initialize DataTable safely
    try {
        if ($.fn && $.fn.DataTable) {
            $('#tableCitizenCharter').DataTable({
                pageLength: 25,
                order: [[0, 'desc'], [1, 'desc']],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search citizen charter register..."
                }
            });
        }
    } catch(e) {
        console.warn('DataTable notice:', e);
    }

    // 2. Open Modal for New Request
    $('#btnOpenIntakeModal').on('click', function() {
        $('#formCitizenCharter')[0].reset();
        $('#charter_id').val('0');
        $('#request_date').val('<?= date('Y-m-d') ?>');
        $('#request_origin').val('Direct Visit');
        $('#status').val('Completed');
        $('#fetchFeedback').hide();
        $('#modalCharterIntakeLabel').text('Log Non-Clinical Service Request');
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCharterIntake'));
        modal.show();
    });

    // 3. Quick Purpose Presets
    $('.btn-preset-purpose').on('click', function() {
        const val = $(this).data('purpose');
        $('#purpose_of_visit').val(val);
    });

    // 4. Dual-Trigger Beneficiary Lookup (NIC / Farm Reg No)
    function performBeneficiaryLookup(term) {
        if (!term) {
            Swal.fire('Enter Search Term', 'Please enter an Identity Card Number (NIC) or Farm Registration Number.', 'info');
            return;
        }

        $('#spinnerFetchNic, #spinnerFetchFarm').removeClass('d-none');
        $('#iconFetchNic, #iconFetchFarm').addClass('d-none');
        $('#btnAutoFetchNic, #btnAutoFetchFarm').prop('disabled', true);
        $('#fetchFeedback').html('<span class="text-muted"><i class="spinner-border spinner-border-sm me-1"></i>Searching master registries...</span>').show();

        $.ajax({
            url: 'processors/citizen_charter_crud.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'lookup_beneficiary',
                query: term
            },
            success: function(res) {
                $('#spinnerFetchNic, #spinnerFetchFarm').addClass('d-none');
                $('#iconFetchNic, #iconFetchFarm').removeClass('d-none');
                $('#btnAutoFetchNic, #btnAutoFetchFarm').prop('disabled', false);

                if (res.success && res.data) {
                    const d = res.data;
                    $('#beneficiary_name').val(d.name).addClass('auto-fill-flash');
                    if (d.nic) $('#nic_no').val(d.nic).addClass('auto-fill-flash');
                    if (d.phone) $('#phone_no').val(d.phone).addClass('auto-fill-flash');
                    if (d.address) $('#address').val(d.address).addClass('auto-fill-flash');
                    if (d.farm_reg_no) $('#farm_reg_no').val(d.farm_reg_no).addClass('auto-fill-flash');

                    setTimeout(() => {
                        $('.auto-fill-flash').removeClass('auto-fill-flash');
                    }, 1200);

                    $('#fetchFeedback').html(
                        `<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Auto-Fetched: ${d.name} &bull; ${d.phone || 'No Phone'} (${d.source})</span>`
                    ).show();
                } else {
                    $('#fetchFeedback').html(
                        `<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>${res.message}</span>`
                    ).show();
                }
            },
            error: function() {
                $('#spinnerFetchNic, #spinnerFetchFarm').addClass('d-none');
                $('#iconFetchNic, #iconFetchFarm').removeClass('d-none');
                $('#btnAutoFetchNic, #btnAutoFetchFarm').prop('disabled', false);
                $('#fetchFeedback').html('<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Server error looking up beneficiary.</span>').show();
            }
        });
    }

    $('#btnAutoFetchNic').on('click', function() {
        performBeneficiaryLookup($('#nic_no').val().trim());
    });

    $('#btnAutoFetchFarm').on('click', function() {
        performBeneficiaryLookup($('#farm_reg_no').val().trim());
    });

    // Auto-search on NIC input when 10 or 12 chars
    let nicTimer = null;
    $('#nic_no').on('input', function() {
        const val = $(this).val().trim();
        clearTimeout(nicTimer);
        if ((val.length === 10 || val.length === 12) && $('#beneficiary_name').val().trim() === '') {
            nicTimer = setTimeout(() => { performBeneficiaryLookup(val); }, 600);
        }
    });

    // 5. Submit Form via AJAX
    $('#formCitizenCharter').on('submit', function(e) {
        e.preventDefault();

        $('#spinnerSave').removeClass('d-none');
        $('#iconSave').addClass('d-none');
        $('#btnSaveCharterRequest').prop('disabled', true);

        $.ajax({
            url: 'processors/citizen_charter_crud.php',
            method: 'POST',
            dataType: 'json',
            data: $(this).serialize(),
            success: function(res) {
                $('#spinnerSave').addClass('d-none');
                $('#iconSave').removeClass('d-none');
                $('#btnSaveCharterRequest').prop('disabled', false);

                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Request Logged!',
                        html: `${res.message}<br><br><span class="badge bg-warning text-dark font-monospace">${res.reference_no || ''}</span>`,
                        confirmButtonColor: '#b45309'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: function() {
                $('#spinnerSave').addClass('d-none');
                $('#iconSave').removeClass('d-none');
                $('#btnSaveCharterRequest').prop('disabled', false);
                Swal.fire('Error', 'Network or server error saving request.', 'error');
            }
        });
    });

    // 6. Edit Request
    $(document).on('click', '.btn-edit-request', function() {
        const id = $(this).data('id');
        $.ajax({
            url: 'processors/citizen_charter_crud.php',
            method: 'GET',
            dataType: 'json',
            data: { action: 'get_request', id: id },
            success: function(res) {
                if (res.success && res.data) {
                    const d = res.data;
                    $('#charter_id').val(d.id);
                    $('#request_date').val(d.request_date);
                    $('#request_origin').val(d.request_origin);
                    $('#beneficiary_name').val(d.beneficiary_name);
                    $('#nic_no').val(d.nic_no || '');
                    $('#phone_no').val(d.phone_no || '');
                    $('#address').val(d.address || '');
                    $('#farm_reg_no').val(d.farm_reg_no || '');
                    $('#purpose_of_visit').val(d.purpose_of_visit);
                    $('#immediate_action_taken').val(d.immediate_action_taken);
                    $('#final_remarks').val(d.final_remarks || '');
                    $('#status').val(d.status);

                    $('#modalCharterIntakeLabel').text('Edit Non-Clinical Request (' + d.reference_no + ')');
                    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCharterIntake'));
                    modal.show();
                } else {
                    Swal.fire('Error', res.message || 'Failed to load request.', 'error');
                }
            }
        });
    });

    // 7. View & Print Slip
    $(document).on('click', '.btn-view-request', function() {
        const id = $(this).data('id');
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalViewCharterSlip'));
        modal.show();

        $('#viewCharterSlipBody').html('<div class="text-center py-4"><div class="spinner-border text-warning" role="status"></div></div>');

        $.ajax({
            url: 'processors/citizen_charter_crud.php',
            method: 'GET',
            dataType: 'json',
            data: { action: 'get_request', id: id },
            success: function(res) {
                if (res.success && res.data) {
                    const d = res.data;
                    const html = `
                        <div id="printSlipContent" class="p-3">
                            <div class="text-center border-bottom pb-3 mb-3">
                                <h4 class="fw-bold mb-0 text-dark">DEPARTMENT OF ANIMAL PRODUCTION AND HEALTH</h4>
                                <div class="fw-semibold text-secondary small">EASTERN PROVINCE &bull; GOVERNMENT OF SRI LANKA</div>
                                <h5 class="fw-bold text-warning-emphasis mt-2 mb-0">CITIZEN CHARTER SERVICE RECORD SLIP</h5>
                                <small class="text-muted">Government Veterinary Office &bull; ${d.range_name || 'Range Office'}</small>
                            </div>

                            <div class="row g-2 mb-3 bg-light p-3 rounded border">
                                <div class="col-6 col-md-3">
                                    <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Reference No</small>
                                    <strong class="font-monospace text-primary">${d.reference_no}</strong>
                                </div>
                                <div class="col-6 col-md-3">
                                    <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Date of Request</small>
                                    <strong>${d.request_date}</strong>
                                </div>
                                <div class="col-6 col-md-3">
                                    <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Request Origin</small>
                                    <span class="badge ${d.request_origin === 'By Call' ? 'bg-warning text-dark' : 'bg-primary'}">${d.request_origin}</span>
                                </div>
                                <div class="col-6 col-md-3">
                                    <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Service Status</small>
                                    <span class="badge bg-success">${d.status}</span>
                                </div>
                            </div>

                            <div class="card border mb-3">
                                <div class="card-header bg-white fw-bold small text-muted text-uppercase py-2">
                                    Beneficiary / Farmer Details
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-2">
                                        <div class="col-md-6"><strong>Name:</strong> ${d.beneficiary_name}</div>
                                        <div class="col-md-6"><strong>NIC:</strong> ${d.nic_no || '—'}</div>
                                        <div class="col-md-6"><strong>Phone:</strong> ${d.phone_no || '—'}</div>
                                        <div class="col-md-6"><strong>Farm Reg No:</strong> ${d.farm_reg_no || '—'}</div>
                                        <div class="col-12"><strong>Address:</strong> ${d.address || '—'}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="card border mb-3">
                                <div class="card-header bg-white fw-bold small text-muted text-uppercase py-2">
                                    Purpose of Visit / Inquiry
                                </div>
                                <div class="card-body p-3 text-dark">
                                    ${d.purpose_of_visit.replace(/\\n/g, '<br>')}
                                </div>
                            </div>

                            <div class="card border mb-3 bg-light">
                                <div class="card-header bg-white fw-bold small text-muted text-uppercase py-2 text-success">
                                    <i class="bi bi-check2-circle me-1"></i> Immediate Action Taken by Veterinary Office
                                </div>
                                <div class="card-body p-3 text-dark">
                                    ${d.immediate_action_taken.replace(/\\n/g, '<br>')}
                                </div>
                            </div>

                            ${d.final_remarks ? `
                            <div class="p-3 mb-3 border rounded bg-white">
                                <strong class="small text-muted d-block">Final Remarks:</strong>
                                <div>${d.final_remarks}</div>
                            </div>` : ''}

                            <div class="row mt-5 pt-4 text-center">
                                <div class="col-6">
                                    <div class="border-top pt-2">
                                        <small class="text-muted d-block">Client / Beneficiary Acknowledgment</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border-top pt-2">
                                        <small class="text-muted d-block">Government Veterinary Surgeon / Authorized Officer</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    $('#viewCharterSlipBody').html(html);
                } else {
                    $('#viewCharterSlipBody').html('<div class="alert alert-danger">Failed to load request slip.</div>');
                }
            }
        });
    });

    // 8. Delete Request
    $(document).on('click', '.btn-delete-request', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const ref = $(this).data('ref');

        Swal.fire({
            title: 'Delete Request?',
            html: `Are you sure you want to remove Citizen Charter request <strong>${ref}</strong> for <strong>${name}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#820100',
            confirmButtonText: 'Yes, Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'processors/citizen_charter_crud.php',
                    method: 'POST',
                    dataType: 'json',
                    data: { action: 'delete_request', id: id },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Deleted!', res.message, 'success').then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    }
                });
            }
        });
    });

    // 9. Export to CSV
    $('#btnExportCharterCsv').on('click', function() {
        const table = document.getElementById('tableCitizenCharter');
        if (!table) return;
        let csv = [];
        const rows = table.querySelectorAll('tr');
        for (let i = 0; i < rows.length; i++) {
            let row = [];
            const cols = rows[i].querySelectorAll('th, td');
            // Exclude action column
            const maxCol = cols.length > 1 ? cols.length - 1 : cols.length;
            for (let j = 0; j < maxCol; j++) {
                let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace(/\s+/g, ' ').trim();
                text = text.replace(/"/g, '""');
                row.push('"' + text + '"');
            }
            if (row.length > 0) csv.push(row.join(','));
        }
        const csvString = csv.join('\n');
        const filename = 'Citizen_Charter_Non_Clinical_Register_<?= $filter_year ?>_<?= sprintf("%02d", $filter_month) ?>.csv';
        const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.setAttribute('href', URL.createObjectURL(blob));
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>
<?php
$pageScripts = ob_get_clean();
require_once __DIR__ . '/../../../includes/footer.php';
?>
