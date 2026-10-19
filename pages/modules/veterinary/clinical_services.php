<?php
/**
 * pages/modules/veterinary/clinical_services.php
 * Clinical Services OPD Registers - Dual Workflow Interface
 * Large Animal Clinics (Farm Reg Auto-Population) vs. Small Animal Clinics
 * Physical Register Grid & Overarching Counter File Revenue Synchronization
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db_connect.php';
require_once __DIR__ . '/processors/clinical_db_migration.php';
require_once __DIR__ . '/processors/dairy_db_migration.php';
require_once __DIR__ . '/processors/clinical_services_helper.php';
require_once __DIR__ . '/../../../includes/counterfoil_module_helper.php';

/** @var mysqli $mysqli */
global $mysqli;
ensure_clinical_opd_tables($mysqli);
ensure_dairy_project_tables($mysqli);

// 1. Session and Role Clearance Guard
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
$session_range_id    = !empty($_SESSION['range_id']) ? intval($_SESSION['range_id']) : null;
$session_district_id = !empty($_SESSION['district_id']) ? intval($_SESSION['district_id']) : null;

// Determine active range_id (support GET parameter e.g. clinical_services.php?range_id=1, fallback to session)
$requested_range_id = isset($_GET['range_id']) && is_numeric($_GET['range_id']) ? intval($_GET['range_id']) : null;
$range_id = $requested_range_id !== null ? $requested_range_id : ($session_range_id ?: 1);

// Active Tab ('large_animal', 'small_animal', 'services_register', or 'monthly_report')
$requested_tab = $_GET['tab'] ?? 'large_animal';
if ($requested_tab === 'citizen_charter') {
    $requested_tab = 'services_register';
}
$active_tab = in_array($requested_tab, ['large_animal', 'small_animal', 'services_register', 'monthly_report'], true) 
    ? $requested_tab 
    : 'large_animal';

// Date / Year / Month Filters
$selected_year  = isset($_GET['year']) && is_numeric($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$selected_month = isset($_GET['month']) && is_numeric($_GET['month']) ? intval($_GET['month']) : 0; // 0 for all

// Services Register / Citizen Charter Specific Filters
$charter_origin = trim($_GET['origin'] ?? 'all');
$charter_status = trim($_GET['status'] ?? 'all');

// Fetch Ranges list for range selector / range context
$ranges_list = [];
$ranges_q = $mysqli->query("SELECT r.id, r.name, r.district_id, d.name AS district_name FROM veterinary_ranges r LEFT JOIN districts d ON r.district_id = d.id ORDER BY d.name ASC, r.name ASC");
if ($ranges_q) {
    while ($r = $ranges_q->fetch_assoc()) {
        $ranges_list[] = $r;
    }
}

// Current Range & District details
$current_range_name = 'Range #' . $range_id;
$current_district_name = 'District';
$current_district_id = 1;
foreach ($ranges_list as $rng) {
    if ($rng['id'] == $range_id) {
        $current_range_name = $rng['name'];
        $current_district_name = $rng['district_name'];
        $current_district_id = intval($rng['district_id']);
        break;
    }
}

// -----------------------------------------------------------------------------
// 2. Fetch Clinical OPD Records for Active Range & Filters
// -----------------------------------------------------------------------------
$where_clauses = ["(range_id = ? OR range_id IS NULL OR range_id = 0)"];
$params = [$range_id];
$types  = "i";

if ($selected_year > 0) {
    $where_clauses[] = "YEAR(entry_date) = ?";
    $params[] = $selected_year;
    $types   .= "i";
}

if ($selected_month > 0) {
    $where_clauses[] = "MONTH(entry_date) = ?";
    $params[] = $selected_month;
    $types   .= "i";
}

$where_sql = implode(" AND ", $where_clauses);
$query = "SELECT * FROM clinical_opd_records WHERE {$where_sql} ORDER BY entry_date DESC, id DESC";

$stmt = $mysqli->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$all_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Split into Large Animal and Small Animal records and compute KPI metrics
$large_animal_records = [];
$small_animal_records = [];

$kpi_total_cases       = count($all_records);
$kpi_large_cases       = 0;
$kpi_small_cases       = 0;
$kpi_total_revenue     = 0.0;
$kpi_consult_revenue   = 0.0;
$kpi_drugs_revenue     = 0.0;
$kpi_others_revenue    = 0.0;
$kpi_total_affected    = 0;
$kpi_total_deaths      = 0;

foreach ($all_records as $rec) {
    $rev = floatval($rec['revenue_total']);
    $kpi_total_revenue   += $rev;
    $kpi_consult_revenue += floatval($rec['revenue_consultation']);
    $kpi_drugs_revenue   += floatval($rec['revenue_drugs']);
    $kpi_others_revenue  += floatval($rec['revenue_others']);
    $kpi_total_affected  += intval($rec['clinically_affected_count']);
    $kpi_total_deaths    += intval($rec['death_count']);

    if ($rec['clinic_type'] === 'large_animal') {
        $large_animal_records[] = $rec;
        $kpi_large_cases++;
    } else {
        $small_animal_records[] = $rec;
        $kpi_small_cases++;
    }
}

// -----------------------------------------------------------------------------
// 2.5 Automated Monthly Summary Reports Aggregation & Drug Types
// -----------------------------------------------------------------------------
$monthly_report_data = buildMonthlySummaryReportData($mysqli, $range_id, $selected_year, $selected_month);

$large_animal_drugs = [];
$dt_res = $mysqli->query("SELECT id, vaccine_name, brand_name, target_animal FROM drug_types ORDER BY id ASC");
if ($dt_res) {
    while ($drg = $dt_res->fetch_assoc()) {
        $large_animal_drugs[] = $drg;
    }
}

// -----------------------------------------------------------------------------
// 3. Fetch Active Physical Cash Receipt Books for Counterfoil Integration
// -----------------------------------------------------------------------------
$cash_receipt_books = [];
$cr_stmt = $mysqli->prepare("
    SELECT id, book_serial_no, page_count, available_quantity, initial_count, remarks
    FROM counterfoil_assets
    WHERE counterfoil_type = 'Cash Receipt Book'
      AND (range_id = ? OR range_id IS NULL OR range_id = 0)
      AND is_active = 1
    ORDER BY id DESC
");
if ($cr_stmt) {
    $cr_stmt->bind_param("i", $range_id);
    $cr_stmt->execute();
    $cr_res = $cr_stmt->get_result();
    while ($b = $cr_res->fetch_assoc()) {
        $cash_receipt_books[] = $b;
    }
    $cr_stmt->close();
}

if (empty($cash_receipt_books)) {
    $default_book_serial = 'CRB-R' . $range_id . '-' . date('Y');
    $auto_book_stmt = $mysqli->prepare("
        INSERT INTO counterfoil_assets 
        (district_id, range_id, counterfoil_type, book_serial_no, page_count, available_quantity, initial_count, is_active, remarks) 
        VALUES (?, ?, 'Cash Receipt Book', ?, '100', 100, 100, 1, 'Auto-provisioned for Clinical Services OPD Revenue Receipts')
    ");
    if ($auto_book_stmt) {
        $auto_book_stmt->bind_param("iis", $current_district_id, $range_id, $default_book_serial);
        $auto_book_stmt->execute();
        $new_id = $auto_book_stmt->insert_id;
        $auto_book_stmt->close();
        $cash_receipt_books[] = [
            'id' => $new_id,
            'book_serial_no' => $default_book_serial,
            'page_count' => '100',
            'available_quantity' => 100,
            'initial_count' => 100,
            'remarks' => 'Auto-provisioned'
        ];
    }
}

// -----------------------------------------------------------------------------
// 4. Fetch Citizen Charter / Services Register Data & Statistics
// -----------------------------------------------------------------------------
$charter_where = ["(c.range_id = ? OR c.range_id IS NULL OR c.range_id = 0)"];
$charter_params = [$range_id];
$charter_types  = "i";

if ($selected_year > 0) {
    $charter_where[] = "YEAR(c.request_date) = ?";
    $charter_params[] = $selected_year;
    $charter_types   .= "i";
}

if ($selected_month > 0) {
    $charter_where[] = "MONTH(c.request_date) = ?";
    $charter_params[] = $selected_month;
    $charter_types   .= "i";
}

$charter_base_where_sql = implode(' AND ', $charter_where);
$charter_count_stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM citizen_charter_requests c WHERE {$charter_base_where_sql}");
$charter_stat_total = 0;
if ($charter_count_stmt) {
    $charter_count_stmt->bind_param($charter_types, ...$charter_params);
    $charter_count_stmt->execute();
    $charter_stat_total = intval($charter_count_stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $charter_count_stmt->close();
}

$charter_records = [];
$charter_stat_direct_visits = 0;
$charter_stat_by_calls       = 0;
$charter_stat_completed      = 0;
$charter_stat_pending        = 0;

$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

if ($active_tab === 'services_register') {
    $full_charter_where = $charter_where;
    $full_charter_params = $charter_params;
    $full_charter_types  = $charter_types;

    if ($charter_origin !== 'all' && in_array($charter_origin, ['Direct Visit', 'By Call'], true)) {
        $full_charter_where[] = "c.request_origin = ?";
        $full_charter_params[] = $charter_origin;
        $full_charter_types   .= "s";
    }

    if ($charter_status !== 'all' && !empty($charter_status)) {
        $full_charter_where[] = "c.status = ?";
        $full_charter_params[] = $charter_status;
        $full_charter_types   .= "s";
    }

    $full_charter_where_sql = implode(' AND ', $full_charter_where);
    $charter_sql = "
        SELECT c.*, u.full_name AS officer_name 
        FROM citizen_charter_requests c
        LEFT JOIN users u ON c.created_by = u.id
        WHERE {$full_charter_where_sql}
        ORDER BY c.request_date DESC, c.id DESC
    ";

    $c_stmt = $mysqli->prepare($charter_sql);
    if ($c_stmt) {
        $c_stmt->bind_param($full_charter_types, ...$full_charter_params);
        $c_stmt->execute();
        $c_res = $c_stmt->get_result();
        while ($r = $c_res->fetch_assoc()) {
            $charter_records[] = $r;
            if ($r['request_origin'] === 'Direct Visit') $charter_stat_direct_visits++;
            if ($r['request_origin'] === 'By Call') $charter_stat_by_calls++;
            if ($r['status'] === 'Completed') $charter_stat_completed++;
            if ($r['status'] === 'Pending' || $r['status'] === 'In Progress') $charter_stat_pending++;
        }
        $c_stmt->close();
    }
}

require_once __DIR__ . '/../../../includes/header.php';
?>

<!-- DataTables & Button Styles -->
<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">

<style>
:root {
    --primary-color: #820100;
    --primary-dark: #5c0100;
    --primary-light: #fef2f2;
    --secondary-color: #2e7d32;
    --secondary-light: #e8f5e9;
    --accent-blue: #1565c0;
    --accent-blue-light: #e3f2fd;
    --card-shadow: 0 4px 18px rgba(0, 0, 0, 0.06);
    --border-radius: 12px;
}

body {
    background-color: #f7f9fb;
    color: #2c3e50;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

.clinical-header {
    background: linear-gradient(135deg, #820100 0%, #a81c1b 100%);
    color: #ffffff;
    border-radius: var(--border-radius);
    padding: 1.6rem 2rem;
    box-shadow: 0 10px 25px rgba(130, 1, 0, 0.18);
    position: relative;
    overflow: hidden;
    margin-bottom: 1.75rem;
}

.clinical-header::after {
    content: '';
    position: absolute;
    right: -25px;
    bottom: -35px;
    width: 170px;
    height: 170px;
    background: rgba(255, 255, 255, 0.07);
    border-radius: 50%;
    pointer-events: none;
}

.kpi-card {
    background: #ffffff;
    border-radius: var(--border-radius);
    border: 1px solid #eef2f6;
    padding: 1.25rem 1.4rem;
    box-shadow: var(--card-shadow);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    height: 100%;
}
.kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.1);
}
.kpi-icon-circle {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.45rem;
}

/* Tab Navigation Styling */
.workflow-nav-pills {
    background: #ffffff;
    padding: 6px;
    border-radius: 12px;
    border: 1px solid #e5e9f0;
    display: inline-flex;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
}
.workflow-nav-pills .nav-link {
    border-radius: 8px;
    padding: 0.65rem 1.4rem;
    font-weight: 600;
    color: #495057;
    transition: all 0.2s ease;
    border: none;
    display: flex;
    align-items: center;
    gap: 8px;
}
.workflow-nav-pills .nav-link.active-large {
    background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(46, 125, 50, 0.3);
}
.workflow-nav-pills .nav-link.active-small {
    background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(21, 101, 192, 0.3);
}
.workflow-nav-pills .nav-link.active-services {
    background: linear-gradient(135deg, #d97706 0%, #b45309 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(217, 119, 6, 0.35);
}
.workflow-nav-pills .nav-link.active-report {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.35);
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
    body { background-color: #ffffff !important; }
    .sidebar, .navbar, .top-navbar, .clinical-header, .workflow-nav-pills, .btn, .card-header button, form, .badge-pill, footer,
    .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_paginate {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .table-responsive {
        overflow: visible !important;
    }
}

/* Clinical Register Table Grid Styling */
.table-clinical {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
}
.table-clinical thead tr th {
    background-color: #f1f5f9;
    color: #334155;
    font-weight: 700;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    vertical-align: middle;
    text-align: center;
}
.table-clinical thead tr.group-header th {
    background-color: #e2e8f0;
    color: #1e293b;
    font-size: 0.82rem;
}
.table-clinical thead tr th.th-revenue-group {
    background-color: #fef3c7 !important;
    color: #92400e !important;
    border-bottom: 2px solid #f59e0b !important;
}
.table-clinical thead tr th.th-revenue-sub {
    background-color: #fffbeb !important;
    color: #78350f !important;
    font-size: 0.76rem;
}
.table-clinical tbody tr td {
    padding: 10px 12px;
    vertical-align: middle;
    font-size: 0.85rem;
    border: 1px solid #f1f5f9;
}
.table-clinical tbody tr:hover {
    background-color: #f8fafc;
}

/* Badge & Form Styling */
.badge-counterfoil {
    background-color: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
    font-size: 0.75rem;
    padding: 4px 8px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.badge-pending {
    background-color: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
    font-size: 0.75rem;
    padding: 4px 8px;
    border-radius: 6px;
}
.btn-primary-custom {
    background-color: #820100;
    color: white;
    border: none;
    font-weight: 600;
    border-radius: 8px;
    padding: 0.55rem 1.25rem;
    transition: all 0.2s ease;
}
.btn-primary-custom:hover {
    background-color: #5c0100;
    color: white;
    box-shadow: 0 4px 12px rgba(130, 1, 0, 0.25);
}
.btn-large-animal {
    background-color: #2e7d32;
    color: white;
    border: none;
}
.btn-large-animal:hover {
    background-color: #1b5e20;
    color: white;
}
.btn-small-animal {
    background-color: #1565c0;
    color: white;
    border: none;
}
.btn-small-animal:hover {
    background-color: #0d47a1;
    color: white;
}

/* Auto-fill indicator animation */
.auto-fill-flash {
    animation: autoFillGlow 1.2s ease;
}
@keyframes autoFillGlow {
    0% { background-color: #bbf7d0; }
    100% { background-color: transparent; }
}

/* Modal form sections */
.modal-section-title {
    font-size: 0.88rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #475569;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 5px;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}
</style>

<div class="container-fluid px-4 py-4">
    <!-- Breadcrumb & Top Bar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="range_details.php?range_id=<?= $range_id ?>" class="text-decoration-none text-muted"><i class="bi bi-geo-alt me-1"></i>Range Details</a></li>
                <li class="breadcrumb-item active fw-bold text-dark" aria-current="page">Clinical Services OPD Registers</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="bi bi-building me-1 text-primary"></i> <?= htmlspecialchars($current_range_name) ?> (<?= htmlspecialchars($current_district_name) ?>)
            </span>
            <a href="counter_foilage.php?range_id=<?= $range_id ?>" class="btn btn-outline-secondary btn-sm" title="View Overarching Counterfoil Register">
                <i class="bi bi-receipt-cutoff me-1"></i> Counterfoil Book
            </a>
            <a href="cash_book_summary.php?range_id=<?= $range_id ?>" class="btn btn-outline-secondary btn-sm" title="View Cash Book Summary">
                <i class="bi bi-wallet2 me-1"></i> Cash Book
            </a>
        </div>
    </div>

    <!-- Executive Module Header Banner -->
    <div class="clinical-header">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-3">
                    <div style="background: rgba(255,255,255,0.2); width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">
                        <i class="bi bi-clipboard2-pulse"></i>
                    </div>
                    <div>
                        <h2 class="mb-1 fw-bold text-white">Clinical Services OPD Registers</h2>
                        <p class="mb-0 text-white-50 small">
                            Separate intake workflows for Large Animals &amp; Small Animals &bull; Physical Register Diagnostics &bull; Automated Revenue Counterfoil Synchronization
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <?php if ($active_tab === 'services_register'): ?>
                    <button type="button" class="btn btn-warning fw-bold px-3 py-2 shadow-sm text-dark" id="btnOpenIntakeModal">
                        <i class="bi bi-plus-circle-fill me-1"></i> Log Non-Clinical Request
                    </button>
                <?php else: ?>
                    <button type="button" class="btn btn-light fw-bold px-3 py-2 shadow-sm" id="btnOpenNewOpdModal">
                        <i class="bi bi-plus-circle-fill text-danger me-1"></i> New Clinical OPD Entry
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick KPI Cards Grid -->
    <div class="row g-3 mb-4">
        <?php if ($active_tab === 'services_register'): ?>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="kpi-card" style="border-left: 4px solid #3b82f6;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Total Inquiries</div>
                            <h3 class="mb-0 fw-bold mt-1 text-dark"><?= number_format($charter_stat_total) ?></h3>
                            <div class="small text-muted mt-1"><?= $selected_month > 0 ? ($month_names[$selected_month] ?? 'Month') . ' ' : '' ?><?= $selected_year ?></div>
                        </div>
                        <div class="kpi-icon-circle bg-primary-subtle text-primary">
                            <i class="bi bi-folder2-open"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="kpi-card" style="border-left: 4px solid #1d4ed8;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Direct Visits</div>
                            <h3 class="mb-0 fw-bold mt-1 text-primary"><?= number_format($charter_stat_direct_visits) ?></h3>
                            <div class="small text-muted mt-1"><i class="bi bi-person-walking me-1 text-primary"></i>Walk-in Clients</div>
                        </div>
                        <div class="kpi-icon-circle bg-info-subtle text-info">
                            <i class="bi bi-person-check-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="kpi-card" style="border-left: 4px solid #d97706;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">By Call Requests</div>
                            <h3 class="mb-0 fw-bold mt-1 text-warning-emphasis"><?= number_format($charter_stat_by_calls) ?></h3>
                            <div class="small text-muted mt-1"><i class="bi bi-telephone me-1 text-warning"></i>Telephone Inquiries</div>
                        </div>
                        <div class="kpi-icon-circle bg-warning-subtle text-warning-emphasis">
                            <i class="bi bi-telephone-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="kpi-card" style="border-left: 4px solid #10b981;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Actions Completed</div>
                            <h3 class="mb-0 fw-bold mt-1 text-success"><?= number_format($charter_stat_completed) ?></h3>
                            <div class="small text-muted mt-1"><i class="bi bi-check2-circle me-1 text-success"></i>Resolved &amp; Attended</div>
                        </div>
                        <div class="kpi-icon-circle bg-success-subtle text-success">
                            <i class="bi bi-patch-check-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="kpi-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Total OPD Cases</div>
                            <h3 class="mb-0 fw-bold mt-1 text-dark"><?= number_format($kpi_total_cases) ?></h3>
                            <div class="small text-muted mt-1">Year <?= $selected_year ?> <?= $selected_month > 0 ? " | Month {$selected_month}" : "" ?></div>
                        </div>
                        <div class="kpi-icon-circle bg-danger-subtle text-danger">
                            <i class="bi bi-hospital"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="kpi-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Large Animal Clinic</div>
                            <h3 class="mb-0 fw-bold mt-1 text-success"><?= number_format($kpi_large_cases) ?></h3>
                            <div class="small text-muted mt-1"><i class="bi bi-check-circle-fill text-success me-1"></i>Farm Registry Auto-Linked</div>
                        </div>
                        <div class="kpi-icon-circle bg-success-subtle text-success">
                            <i class="bi bi-shield-shaded"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="kpi-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Small Animal Clinic</div>
                            <h3 class="mb-0 fw-bold mt-1 text-primary"><?= number_format($kpi_small_cases) ?></h3>
                            <div class="small text-muted mt-1"><i class="bi bi-tag-fill text-primary me-1"></i>Canine &amp; Feline Standard Reg</div>
                        </div>
                        <div class="kpi-icon-circle bg-primary-subtle text-primary">
                            <i class="bi bi-heart-pulse"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="kpi-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Total Revenue (Rs.)</div>
                            <h3 class="mb-0 fw-bold mt-1 text-warning-emphasis">Rs. <?= number_format($kpi_total_revenue, 2) ?></h3>
                            <div class="small text-muted mt-1"><i class="bi bi-link-45deg text-success me-1"></i>Cash Book &amp; Counter Synced</div>
                        </div>
                        <div class="kpi-icon-circle bg-warning-subtle text-warning-emphasis">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Filter & Range Switcher Bar -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: var(--border-radius);">
        <div class="card-body py-3">
            <form method="GET" action="clinical_services.php" class="row g-2 align-items-center">
                <input type="hidden" name="tab" id="filterTabInput" value="<?= htmlspecialchars($active_tab) ?>">

                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label small text-muted mb-1 fw-bold">Veterinary Range</label>
                    <select name="range_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php foreach ($ranges_list as $rng): ?>
                            <option value="<?= $rng['id'] ?>" <?= $rng['id'] == $range_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($rng['district_name']) ?> - <?= htmlspecialchars($rng['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-2 col-lg-2">
                    <label class="form-label small text-muted mb-1 fw-bold">Year</label>
                    <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php for ($y = date('Y') + 1; $y >= 2020; $y--): ?>
                            <option value="<?= $y ?>" <?= $y == $selected_year ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-muted mb-1 fw-bold">Month</label>
                    <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="0" <?= $selected_month == 0 ? 'selected' : '' ?>>All Months</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $m == $selected_month ? 'selected' : '' ?>>
                                <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-12 col-md-3 col-lg-5 text-md-end mt-3 mt-md-0 pt-md-3">
                    <button type="submit" class="btn btn-sm btn-dark px-3">
                        <i class="bi bi-filter me-1"></i> Apply Filter
                    </button>
                    <a href="clinical_services.php?range_id=<?= $range_id ?>&tab=<?= $active_tab ?>" class="btn btn-sm btn-outline-secondary px-2">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Dual-Workflow Split Tabs -->
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
        <ul class="nav workflow-nav-pills" id="clinicWorkflowTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $active_tab === 'large_animal' ? 'active-large' : '' ?>" 
                   id="tab-large-animal" 
                   href="clinical_services.php?range_id=<?= $range_id ?>&year=<?= $selected_year ?>&month=<?= $selected_month ?>&tab=large_animal">
                    <i class="bi bi-shield-shaded"></i> Large Animal Clinic
                    <span class="badge rounded-pill <?= $active_tab === 'large_animal' ? 'bg-white text-success' : 'bg-success text-white' ?> ms-1">
                        <?= count($large_animal_records) ?>
                    </span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $active_tab === 'small_animal' ? 'active-small' : '' ?>" 
                   id="tab-small-animal" 
                   href="clinical_services.php?range_id=<?= $range_id ?>&year=<?= $selected_year ?>&month=<?= $selected_month ?>&tab=small_animal">
                    <i class="bi bi-heart-pulse"></i> Small Animal Clinic
                    <span class="badge rounded-pill <?= $active_tab === 'small_animal' ? 'bg-white text-primary' : 'bg-primary text-white' ?> ms-1">
                        <?= count($small_animal_records) ?>
                    </span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $active_tab === 'services_register' ? 'active-services' : '' ?>" 
                   id="tab-services-register" 
                   href="clinical_services.php?range_id=<?= $range_id ?>&year=<?= $selected_year ?>&month=<?= $selected_month ?>&tab=services_register">
                    <i class="bi bi-file-earmark-person-fill"></i> Services Register
                    <span class="badge rounded-pill <?= $active_tab === 'services_register' ? 'bg-white text-dark' : 'bg-warning text-dark' ?> ms-1">
                        <?= $charter_stat_total ?>
                    </span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $active_tab === 'monthly_report' ? 'active-report' : '' ?>" 
                   id="tab-monthly-report" 
                   href="clinical_services.php?range_id=<?= $range_id ?>&year=<?= $selected_year ?>&month=<?= $selected_month ?>&tab=monthly_report">
                    <i class="bi bi-file-earmark-bar-graph-fill"></i> Monthly Summary Report
                    <span class="badge rounded-pill <?= $active_tab === 'monthly_report' ? 'bg-white text-dark' : 'bg-dark text-white' ?> ms-1">
                        <?= $monthly_report_data['totals']['grand_total_cases'] ?> Cases
                    </span>
                </a>
            </li>
        </ul>

        <div class="d-flex align-items-center gap-2">
            <?php if ($active_tab === 'services_register'): ?>
                <button type="button" class="btn btn-sm btn-warning fw-bold text-dark shadow-sm" id="btnOpenIntakeModalSub">
                    <i class="bi bi-plus-circle me-1"></i> Log Service Request
                </button>
            <?php else: ?>
                <a href="clinical_services.php?range_id=<?= $range_id ?>&year=<?= $selected_year ?>&month=<?= $selected_month ?>&tab=monthly_report" class="btn btn-sm btn-outline-dark fw-semibold">
                    <i class="bi bi-file-earmark-bar-graph me-1"></i> Monthly Summary
                </a>
            <?php endif; ?>
            <span class="text-muted small">
                Showing <?= $active_tab === 'large_animal' ? count($large_animal_records) . ' Large Animal' : ($active_tab === 'small_animal' ? count($small_animal_records) . ' Small Animal' : ($active_tab === 'services_register' ? count($charter_records) . ' Service Requests' : $monthly_report_data['totals']['grand_total_cases'] . ' Aggregated')) ?> records
            </span>
        </div>
    </div>

    <!-- ======================================================================= -->
    <!-- TAB 1: LARGE ANIMAL CLINICAL REGISTER                                  -->
    <!-- ======================================================================= -->
    <?php if ($active_tab === 'large_animal'): ?>
    <div class="card border-0 shadow-sm" style="border-radius: var(--border-radius);">
        <div class="card-header bg-white py-3 border-0 d-flex flex-wrap align-items-center justify-content-between">
            <div>
                <h5 class="mb-0 fw-bold text-success">
                    <i class="bi bi-journal-medical me-2"></i>Large Animal Clinic OPD Register
                </h5>
            </div>
            <div class="mt-2 mt-md-0">
                <button type="button" class="btn btn-sm btn-large-animal fw-semibold" id="btnAddLargeAnimalRecord">
                    <i class="bi bi-plus-circle me-1"></i> Add Large Animal Record
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-clinical table-hover mb-0" id="tableLargeAnimalOpd">
                    <thead>
                        <!-- 3-Part Physical Register Header Structure -->
                        <tr class="group-header">
                            <th rowspan="2" style="width: 35px;" class="align-middle text-center">#</th>
                            <th colspan="5" style="background-color: #dcfce7; color: #166534;" class="text-center">
                                <i class="bi bi-person-check-fill me-1"></i> Part 1: Farmer &amp; Herd Details
                            </th>
                            <th colspan="5" style="background-color: #e0f2fe; color: #0369a1;" class="text-center">
                                <i class="bi bi-upc-scan me-1"></i> Part 2: Affected Animal Details (Ear-Tag Auto-Fill)
                            </th>
                            <th colspan="3" style="background-color: #fee2e2; color: #991b1b;" class="text-center">
                                <i class="bi bi-clipboard2-pulse-fill me-1"></i> Part 3: Sequential Clinical Findings &amp; Action
                            </th>
                            <th colspan="3" class="th-revenue-group text-center">
                                <i class="bi bi-cash-stack me-1"></i> Revenue Collection (Rs.)
                            </th>
                            <th rowspan="2" style="min-width: 120px;" class="align-middle text-center">Cash Receipt No.</th>
                            <th rowspan="2" style="min-width: 100px;" class="align-middle">Remarks</th>
                        </tr>
                        <tr>
                            <!-- Part 1: Farmer Details -->
                            <th style="min-width: 90px;">Date</th>
                            <th style="min-width: 120px;">Farm Reg / NIC</th>
                            <th style="min-width: 135px;">Farmer Name</th>
                            <th style="min-width: 130px;">Address &amp; Phone</th>
                            <th style="min-width: 85px;" class="text-center" title="Herd Population (since whole herd exists even if only one animal is sick)">Herd Pop.</th>

                            <!-- Part 2: Affected Animal Details -->
                            <th style="min-width: 110px;">Ear-Tag No.</th>
                            <th style="min-width: 80px;">Species</th>
                            <th style="min-width: 85px;">Breed</th>
                            <th style="min-width: 75px;" class="text-center">Age / Sex</th>
                            <th style="min-width: 100px;">Color &amp; Marks</th>

                            <!-- Part 3: Sequential Flow -->
                            <th style="min-width: 150px;">1. Complaint (from Farm)</th>
                            <th style="min-width: 150px;">2. Tentative Diagnosis</th>
                            <th style="min-width: 180px;">3. Action Taken</th>

                            <!-- Revenue Breakdown into Consultation & Drugs + Total -->
                            <th class="th-revenue-sub" style="min-width: 85px;">Consultation</th>
                            <th class="th-revenue-sub" style="min-width: 85px;">Drugs</th>
                            <th class="th-revenue-sub fw-bold" style="min-width: 90px;">Total (Rs.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($large_animal_records)): ?>
                            <tr>
                                <td colspan="19" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                    No Large Animal clinical records logged for the selected period. Click <strong>Add Large Animal Record</strong> above to register a patient.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $idx = 1; foreach ($large_animal_records as $rec): ?>
                                <tr>
                                    <td class="text-center">
                                        <a href="javascript:void(0)" class="badge bg-success-subtle text-success border border-success fw-bold font-monospace px-2 py-1 text-decoration-none btn-edit-opd" data-id="<?= $rec['id'] ?>" title="Click to View / Edit OPD Record">
                                            #<?= $idx++ ?>
                                        </a>
                                    </td>

                                    <!-- Part 1: Farmer Details -->
                                    <td class="text-center">
                                        <div class="fw-semibold text-dark"><?= date('d-M-Y', strtotime($rec['entry_date'])) ?></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($rec['farm_reg_no'])): ?>
                                            <a href="javascript:void(0)" class="badge bg-success-subtle text-success border border-success fw-bold font-monospace d-block mb-1 text-truncate text-decoration-none btn-edit-opd" data-id="<?= $rec['id'] ?>" title="Farm Reg No: <?= htmlspecialchars($rec['farm_reg_no']) ?> (Click to Edit)">
                                                <i class="bi bi-shield-check me-1"></i><?= htmlspecialchars($rec['farm_reg_no']) ?>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($rec['owner_nic'])): ?>
                                            <span class="badge bg-light text-dark border font-monospace text-truncate" title="NIC: <?= htmlspecialchars($rec['owner_nic']) ?>">
                                                <i class="bi bi-card-heading me-1"></i><?= htmlspecialchars($rec['owner_nic']) ?>
                                            </span>
                                        <?php elseif (empty($rec['farm_reg_no'])): ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($rec['owner_name']) ?></div>
                                    </td>
                                    <td>
                                        <div class="small text-muted text-truncate" style="max-width: 140px;" title="<?= htmlspecialchars($rec['owner_address'] ?? '') ?>">
                                            <?= htmlspecialchars($rec['owner_address'] ?? '—') ?>
                                        </div>
                                        <?php if (!empty($rec['owner_phone'])): ?>
                                            <small class="text-primary d-block font-monospace"><i class="bi bi-telephone-fill me-1"></i><?= htmlspecialchars($rec['owner_phone']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success-subtle text-success border border-success fw-bold fs-6" title="Whole herd exists: <?= intval($rec['herd_total_population']) ?> animals">
                                            <?= intval($rec['herd_total_population']) ?>
                                        </span>
                                    </td>

                                    <!-- Part 2: Affected Animal Details -->
                                    <td>
                                        <?php if (!empty($rec['ear_tag_no'])): ?>
                                            <span class="badge bg-warning-subtle text-dark border border-warning fw-bold font-monospace d-block" title="Registered Ear-Tag Number">
                                                <i class="bi bi-tag-fill text-warning me-1"></i><?= htmlspecialchars($rec['ear_tag_no']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border font-monospace">Untagged</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($rec['species']) ?></td>
                                    <td><?= htmlspecialchars($rec['breed'] ?: '—') ?></td>
                                    <td class="text-center">
                                        <span class="small fw-semibold"><?= htmlspecialchars($rec['age_years'] ?: '—') ?></span>
                                        <span class="badge <?= ($rec['sex'] === 'Male') ? 'bg-info-subtle text-info' : 'bg-danger-subtle text-danger' ?> ms-1">
                                            <?= ($rec['sex'] === 'Male') ? 'M' : 'F' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small text-dark"><?= htmlspecialchars($rec['coat_colour'] ?: '—') ?></div>
                                        <?php if (!empty($rec['special_marks'])): ?>
                                            <small class="text-muted d-block text-truncate" style="max-width: 110px;" title="<?= htmlspecialchars($rec['special_marks']) ?>">
                                                <?= htmlspecialchars($rec['special_marks']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Part 3: Sequential Flow -->
                                    <td>
                                        <div class="small text-dark" style="max-width: 160px;" title="<?= htmlspecialchars($rec['complaint'] ?? '') ?>">
                                            <?= htmlspecialchars($rec['complaint'] ?: '—') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong class="text-danger d-block"><?= htmlspecialchars($rec['tentative_diagnosis']) ?></strong>
                                    </td>
                                    <td>
                                        <?php
                                        $actionType = $rec['action_type'] ?? 'Direct Medication / Medicine Supply';
                                        $badgeClass = 'bg-success text-white';
                                        $iconClass = 'bi-capsule';
                                        if ($actionType === 'Field Visit') {
                                            $badgeClass = 'bg-primary text-white';
                                            $iconClass = 'bi-geo-alt-fill';
                                        } elseif ($actionType === 'Prescriptions') {
                                            $badgeClass = 'bg-info text-dark';
                                            $iconClass = 'bi-file-earmark-medical';
                                        }
                                        ?>
                                        <span class="badge <?= $badgeClass ?> mb-1">
                                            <i class="bi <?= $iconClass ?> me-1"></i><?= htmlspecialchars($actionType) ?>
                                        </span>
                                        <div class="small text-secondary" style="max-width: 200px;" title="<?= htmlspecialchars($rec['action_taken']) ?>">
                                            <?= htmlspecialchars($rec['action_taken']) ?>
                                        </div>
                                    </td>

                                    <!-- Revenue Breakdown -->
                                    <td class="text-end font-monospace text-muted">Rs. <?= number_format(floatval($rec['revenue_consultation']), 2) ?></td>
                                    <td class="text-end font-monospace text-muted">Rs. <?= number_format(floatval($rec['revenue_drugs']), 2) ?></td>
                                    <td class="text-end font-monospace fw-bold text-success bg-light">
                                        Rs. <?= number_format(floatval($rec['revenue_consultation'] + $rec['revenue_drugs'] + $rec['revenue_others']), 2) ?>
                                    </td>

                                    <!-- Cash Receipt Number (Ensuring Linkage to Overarching Counter Files) -->
                                    <td class="text-center">
                                        <div class="fw-bold font-monospace text-primary mb-1"><?= htmlspecialchars($rec['cash_receipt_no']) ?></div>
                                        <?php if (!empty($rec['leaf_issue_id'])): ?>
                                            <a href="counter_foilage.php?range_id=<?= $range_id ?>&leaf_id=<?= $rec['leaf_issue_id'] ?>" 
                                                class="badge-counterfoil text-decoration-none" title="Linked to Overarching Counterfoil Book Leaf Issue #<?= $rec['leaf_issue_id'] ?>">
                                                <i class="bi bi-link-45deg"></i> Leaf #<?= $rec['leaf_issue_id'] ?> (Synced)
                                            </a>
                                        <?php else: ?>
                                            <span class="badge-pending" title="Cash receipt recorded; pending overarching counterfoil leaf synchronization">
                                                <i class="bi bi-clock"></i> Unlinked
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Remarks -->
                                    <td>
                                        <small class="text-muted"><?= htmlspecialchars($rec['remarks'] ?: '—') ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ======================================================================= -->
    <!-- TAB 2: SMALL ANIMAL CLINICAL REGISTER                                  -->
    <!-- ======================================================================= -->
    <?php elseif ($active_tab === 'small_animal'): ?>
    <div class="card border-0 shadow-sm" style="border-radius: var(--border-radius);">
        <div class="card-header bg-white py-3 border-0 d-flex flex-wrap align-items-center justify-content-between">
            <div>
                <h5 class="mb-0 fw-bold text-primary">
                    <i class="bi bi-heart-pulse me-2"></i>Small Animal Clinic OPD Register
                </h5>
            </div>
            <div class="mt-2 mt-md-0">
                <button type="button" class="btn btn-sm btn-small-animal fw-semibold" id="btnAddSmallAnimalRecord">
                    <i class="bi bi-plus-circle me-1"></i> Add Small Animal Record
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-clinical table-hover mb-0" id="tableSmallAnimalOpd">
                    <thead>
                        <!-- Sequential Physical Register Header Structure -->
                        <tr class="group-header">
                            <th rowspan="2" style="min-width: 95px;" class="align-middle text-center">Date</th>
                            <th rowspan="2" style="min-width: 120px;" class="align-middle text-center">Serial Number</th>
                            <th colspan="3" style="background-color: #e0f2fe; color: #0369a1;" class="text-center">Pet Owner Particulars</th>
                            <th colspan="5" style="background-color: #f1f5f9; color: #334155;" class="text-center">Patient Particulars</th>
                            <th colspan="3" style="background-color: #fee2e2; color: #991b1b;" class="text-center">
                                <i class="bi bi-clipboard2-pulse-fill me-1"></i> Sequential Diagnostic Flow
                            </th>
                            <th colspan="4" class="th-revenue-group text-center">Revenue Collection (Rs.)</th>
                            <th rowspan="2" style="min-width: 110px;" class="align-middle text-center">Cash Receipt No.</th>
                        </tr>
                        <tr>
                            <!-- Pet Owner Sub-columns: Name, Phone Number, Address in sequence -->
                            <th style="min-width: 140px;">Name</th>
                            <th style="min-width: 120px;">Phone Number</th>
                            <th style="min-width: 140px;">Address</th>

                            <!-- Patient Particulars Sub-columns -->
                            <th style="min-width: 85px;">Species</th>
                            <th style="min-width: 90px;">Breed</th>
                            <th style="min-width: 65px;" class="text-center">Age (Yrs)</th>
                            <th style="min-width: 55px;" class="text-center">Sex</th>
                            <th style="min-width: 95px;">Colour &amp; Marks</th>

                            <!-- 3 Sequential Clinical Fields (Complaint, Tentative Diagnosis, Action Taken) -->
                            <th style="min-width: 150px;">1. Complaint (from Farm)</th>
                            <th style="min-width: 150px;">2. Tentative Diagnosis</th>
                            <th style="min-width: 170px;">3. Action Taken</th>

                            <!-- Revenue 3 Manual Sub-columns + Total -->
                            <th class="th-revenue-sub" style="min-width: 75px;">Consultation</th>
                            <th class="th-revenue-sub" style="min-width: 75px;">Drugs</th>
                            <th class="th-revenue-sub" style="min-width: 75px;">Others</th>
                            <th class="th-revenue-sub fw-bold" style="min-width: 85px;">Total (Rs.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($small_animal_records)): ?>
                            <tr>
                                <td colspan="18" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                    No Small Animal clinical records logged for the selected period. Click <strong>Add Small Animal Record</strong> above to register a patient.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($small_animal_records as $rec): ?>
                                <tr>
                                    <!-- 1. Date -->
                                    <td class="text-center">
                                        <div class="fw-semibold text-dark"><?= date('d-M-Y', strtotime($rec['entry_date'])) ?></div>
                                    </td>

                                    <!-- 2. Serial Number (Clickable to Edit/View Entry) -->
                                    <td class="text-center">
                                        <a href="javascript:void(0)" class="badge bg-primary-subtle text-primary border border-primary fw-bold font-monospace px-2 py-1 text-decoration-none btn-edit-opd" data-id="<?= $rec['id'] ?>" title="Click to View / Edit OPD Record">
                                            <i class="bi bi-pencil-square me-1"></i><?= htmlspecialchars($rec['registration_no'] ?: sprintf('SA-%04d', $rec['id'])) ?>
                                        </a>
                                    </td>

                                    <!-- 3. Name -->
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($rec['owner_name']) ?></div>
                                        <?php if (!empty($rec['owner_nic'])): ?>
                                            <small class="text-muted d-block font-monospace">NIC: <?= htmlspecialchars($rec['owner_nic']) ?></small>
                                        <?php endif; ?>
                                    </td>

                                    <!-- 4. Phone Number -->
                                    <td>
                                        <?php if (!empty($rec['owner_phone'])): ?>
                                            <span class="fw-semibold text-dark font-monospace">
                                                <i class="bi bi-telephone text-primary me-1"></i><?= htmlspecialchars($rec['owner_phone']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- 5. Address -->
                                    <td>
                                        <div class="small text-muted" title="<?= htmlspecialchars($rec['owner_address'] ?? '') ?>">
                                            <?= htmlspecialchars($rec['owner_address'] ?: '—') ?>
                                        </div>
                                    </td>

                                    <!-- Patient Particulars -->
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($rec['species']) ?></td>
                                    <td><?= htmlspecialchars($rec['breed'] ?: '—') ?></td>
                                    <td class="text-center"><?= htmlspecialchars($rec['age_years'] ?: '—') ?></td>
                                    <td class="text-center">
                                        <?php if ($rec['sex'] === 'Male'): ?>
                                            <span class="badge bg-info-subtle text-info border">M</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border">F</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($rec['coat_colour'] ?: '—') ?></div>
                                        <?php if (!empty($rec['special_marks'])): ?>
                                            <small class="text-muted d-block" title="<?= htmlspecialchars($rec['special_marks']) ?>">
                                                <i class="bi bi-tag me-1"></i><?= htmlspecialchars(mb_strimwidth($rec['special_marks'], 0, 20, '...')) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Sequential Diagnostic Flow: 1) Complaint, 2) Tentative Diagnosis, 3) Action Taken -->
                                    <td>
                                        <div class="small text-dark" style="max-width: 160px;" title="<?= htmlspecialchars($rec['complaint'] ?? '') ?>">
                                            <?= htmlspecialchars($rec['complaint'] ?: '—') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong class="text-danger d-block"><?= htmlspecialchars($rec['tentative_diagnosis']) ?></strong>
                                    </td>
                                    <td>
                                        <div class="small text-secondary" style="max-width: 200px;" title="<?= htmlspecialchars($rec['action_taken']) ?>">
                                            <?= htmlspecialchars($rec['action_taken']) ?>
                                        </div>
                                    </td>

                                    <!-- Revenue Sub-columns -->
                                    <td class="text-end font-monospace text-muted"><?= number_format(floatval($rec['revenue_consultation']), 2) ?></td>
                                    <td class="text-end font-monospace text-muted"><?= number_format(floatval($rec['revenue_drugs']), 2) ?></td>
                                    <td class="text-end font-monospace text-muted"><?= number_format(floatval($rec['revenue_others']), 2) ?></td>
                                    <td class="text-end font-monospace fw-bold text-success bg-light">
                                        <?= number_format(floatval($rec['revenue_total']), 2) ?>
                                    </td>

                                    <!-- Cash Receipt No. & Counter Linkage -->
                                    <td class="text-center">
                                        <div class="fw-bold font-monospace text-primary mb-1"><?= htmlspecialchars($rec['cash_receipt_no']) ?></div>
                                        <?php if (!empty($rec['leaf_issue_id'])): ?>
                                            <a href="counter_foilage.php?range_id=<?= $range_id ?>&leaf_id=<?= $rec['leaf_issue_id'] ?>" 
                                               class="badge-counterfoil text-decoration-none" title="Linked to Overarching Counterfoil Book Leaf Issue #<?= $rec['leaf_issue_id'] ?>">
                                                <i class="bi bi-link-45deg"></i> Leaf #<?= $rec['leaf_issue_id'] ?> (Synced)
                                            </a>
                                        <?php else: ?>
                                            <span class="badge-pending" title="Cash receipt recorded; pending overarching counterfoil leaf synchronization">
                                                <i class="bi bi-clock"></i> Unlinked
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ======================================================================= -->
    <!-- TAB 3: SERVICES REGISTER (CITIZEN CHARTER NON-CLINICAL REQUESTS)        -->
    <!-- ======================================================================= -->
    <?php elseif ($active_tab === 'services_register'): ?>
    
    <!-- Filter Toolbar for Services Register -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: var(--border-radius);">
        <div class="card-body p-3">
            <form method="GET" action="clinical_services.php" class="row g-2 align-items-center">
                <input type="hidden" name="tab" value="services_register">
                <input type="hidden" name="range_id" value="<?= $range_id ?>">

                <!-- Request Origin Filter Tabs -->
                <div class="col-12 col-md-5">
                    <label class="form-label small fw-bold text-muted mb-1 d-block">Request Origin Filter:</label>
                    <div class="btn-group btn-group-sm w-100" role="group">
                        <a href="clinical_services.php?range_id=<?= $range_id ?>&year=<?= $selected_year ?>&month=<?= $selected_month ?>&tab=services_register&origin=all&status=<?= urlencode($charter_status) ?>"
                           class="btn <?= ($charter_origin === 'all') ? 'btn-dark' : 'btn-outline-secondary' ?>">
                            All (<?= $charter_stat_total ?>)
                        </a>
                        <a href="clinical_services.php?range_id=<?= $range_id ?>&year=<?= $selected_year ?>&month=<?= $selected_month ?>&tab=services_register&origin=Direct Visit&status=<?= urlencode($charter_status) ?>"
                           class="btn <?= ($charter_origin === 'Direct Visit') ? 'btn-primary' : 'btn-outline-primary' ?>">
                            <i class="bi bi-person-walking me-1"></i> Direct Visit (<?= $charter_stat_direct_visits ?>)
                        </a>
                        <a href="clinical_services.php?range_id=<?= $range_id ?>&year=<?= $selected_year ?>&month=<?= $selected_month ?>&tab=services_register&origin=By Call&status=<?= urlencode($charter_status) ?>"
                           class="btn <?= ($charter_origin === 'By Call') ? 'btn-warning text-dark' : 'btn-outline-warning text-dark' ?>">
                            <i class="bi bi-telephone me-1"></i> By Call (<?= $charter_stat_by_calls ?>)
                        </a>
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Status:</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" <?= ($charter_status === 'all') ? 'selected' : '' ?>>All Statuses</option>
                        <option value="Completed" <?= ($charter_status === 'Completed') ? 'selected' : '' ?>>Completed</option>
                        <option value="Pending" <?= ($charter_status === 'Pending') ? 'selected' : '' ?>>Pending</option>
                        <option value="In Progress" <?= ($charter_status === 'In Progress') ? 'selected' : '' ?>>In Progress</option>
                        <option value="Referred" <?= ($charter_status === 'Referred') ? 'selected' : '' ?>>Referred</option>
                    </select>
                </div>

                <!-- Reset / Export -->
                <div class="col-6 col-md-4 d-flex align-items-end gap-1">
                    <a href="clinical_services.php?range_id=<?= $range_id ?>&tab=services_register" class="btn btn-sm btn-outline-secondary w-50" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-success w-50" id="btnExportCharterCsv" title="Export to CSV">
                        <i class="bi bi-file-earmark-spreadsheet"></i> CSV
                    </button>
                    <button type="button" class="btn btn-sm btn-warning text-dark fw-bold w-100" id="btnOpenIntakeModalTable" title="Log Non-Clinical Request">
                        <i class="bi bi-plus-circle me-1"></i> New Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Citizen Charter Non-Clinical Requests Register Table Card -->
    <div class="card border-0 shadow-sm" style="border-radius: var(--border-radius);">
        <div class="card-header bg-white py-3 border-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-journal-bookmark-fill text-warning me-2"></i>Services Register: Citizen Charter Inquiries &amp; Action Log
                </h5>
                <small class="text-muted">
                    Non-Clinical Requests &bull; Range: <?= htmlspecialchars($current_range_name) ?> &bull; Period: <?= $selected_month > 0 ? ($month_names[$selected_month] ?? 'Month') . ' ' : 'All Months ' ?><?= $selected_year ?>
                </small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-dark border px-3 py-2 fw-semibold">
                    <i class="bi bi-card-checklist me-1 text-primary"></i> <?= count($charter_records) ?> Records Listed
                </span>
                <button type="button" class="btn btn-sm btn-warning text-dark fw-bold" id="btnOpenIntakeModalHeader">
                    <i class="bi bi-plus-circle me-1"></i> Log Request
                </button>
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
                        <?php if (empty($charter_records)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                    No Citizen Charter service requests logged for this period. Click <strong>Log Request</strong> to record an intake.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($charter_records as $r): ?>
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

    <!-- ======================================================================= -->
    <!-- TAB 4: MONTHLY SUMMARY REPORT (AUTOMATED TALLYING & ANIMAL CATEGORIZATION) -->
    <!-- ======================================================================= -->
    <?php else: ?>
    <div class="card border-0 shadow-sm" style="border-radius: var(--border-radius);">
        <div class="card-header bg-white py-3 border-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-file-earmark-bar-graph-fill text-primary me-2"></i>Monthly Clinical Summary Report
                </h5>
                <small class="text-muted">
                    Automated tallying aggregating Large Animal &amp; Small Animal Outpatient Registers &bull; <?= htmlspecialchars($current_range_name) ?> (<?= htmlspecialchars($monthly_report_data['period_label']) ?>)
                </small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Print Summary Report
                </button>
                <button type="button" class="btn btn-sm btn-outline-success" id="btnExportMonthlyCsv">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export to CSV
                </button>
                <button type="button" class="btn btn-sm btn-primary" id="btnOpenNewOpdModal">
                    <i class="bi bi-plus-circle me-1"></i> New OPD Intake
                </button>
            </div>
        </div>

        <div class="card-body p-4">
            <!-- Official DAPH Government Report Header (Print & Screen) -->
            <div class="p-3 mb-4 rounded border bg-light text-center">
                <div class="d-flex align-items-center justify-content-center gap-3 mb-2">
                    <i class="bi bi-hospital fs-2 text-primary"></i>
                    <div>
                        <h4 class="fw-bold mb-0 text-dark text-uppercase letter-spacing-1">Department of Animal Production &amp; Health</h4>
                        <div class="fw-semibold text-secondary">Eastern Province &bull; Government Veterinary Services</div>
                    </div>
                </div>
                <h5 class="fw-bold text-dark mb-1">Monthly Clinical Outpatient Morbidity &amp; Treatment Summary</h5>
                <div class="text-muted small">
                    <strong>Veterinary Range:</strong> <?= htmlspecialchars($current_range_name) ?> &bull; 
                    <strong>District:</strong> <?= htmlspecialchars($current_district_name) ?> &bull; 
                    <strong>Reporting Period:</strong> <span class="badge bg-primary text-white"><?= htmlspecialchars($monthly_report_data['period_label']) ?></span>
                </div>
            </div>

            <!-- Aggregated KPI Summary Counters -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="p-3 rounded border bg-white h-100 shadow-sm text-center">
                        <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.72rem;">Total OPD Cases</small>
                        <h3 class="fw-bold text-dark my-1"><?= $monthly_report_data['totals']['grand_total_cases'] ?></h3>
                        <small class="text-primary fw-semibold" style="font-size: 0.75rem;">
                            <?= $monthly_report_data['totals']['large_animal_cases'] ?> Large &bull; <?= $monthly_report_data['totals']['small_animal_cases'] ?> Small
                        </small>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="p-3 rounded border bg-white h-100 shadow-sm text-center">
                        <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.72rem;">Large Animals</small>
                        <h3 class="fw-bold text-success my-1"><?= $monthly_report_data['totals']['large_animal_cases'] ?></h3>
                        <small class="text-muted" style="font-size: 0.75rem;">Cattle, Buffalo, Goats</small>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="p-3 rounded border bg-white h-100 shadow-sm text-center">
                        <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.72rem;">Small Animals</small>
                        <h3 class="fw-bold text-primary my-1"><?= $monthly_report_data['totals']['small_animal_cases'] ?></h3>
                        <small class="text-muted" style="font-size: 0.75rem;">Canine, Feline &amp; Pets</small>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="p-3 rounded border bg-white h-100 shadow-sm text-center">
                        <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.72rem;">Animals Treated</small>
                        <h3 class="fw-bold text-info my-1"><?= $monthly_report_data['totals']['grand_total_affected'] ?></h3>
                        <small class="text-muted" style="font-size: 0.75rem;">Clinically affected</small>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="p-3 rounded border bg-white h-100 shadow-sm text-center">
                        <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.72rem;">Reported Deaths</small>
                        <h3 class="fw-bold text-danger my-1"><?= $monthly_report_data['totals']['grand_total_deaths'] ?></h3>
                        <?php 
                        $mortality_rate = ($monthly_report_data['totals']['grand_total_affected'] > 0) 
                            ? round(($monthly_report_data['totals']['grand_total_deaths'] / $monthly_report_data['totals']['grand_total_affected']) * 100, 1) 
                            : 0;
                        ?>
                        <small class="text-muted" style="font-size: 0.75rem;">Mortality: <?= $mortality_rate ?>%</small>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="p-3 rounded border bg-white h-100 shadow-sm text-center">
                        <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.72rem;">Total Revenue (Rs.)</small>
                        <h3 class="fw-bold text-warning-emphasis my-1">Rs. <?= number_format($monthly_report_data['totals']['grand_total_revenue'], 2) ?></h3>
                        <small class="text-muted" style="font-size: 0.72rem;">Consult: <?= number_format($monthly_report_data['totals']['grand_total_consultation'], 0) ?> | Drugs: <?= number_format($monthly_report_data['totals']['grand_total_drugs'], 0) ?></small>
                    </div>
                </div>
            </div>

            <!-- Inventory Sync Notice Box -->
            <div class="alert alert-info py-2 px-3 mb-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="small">
                    <i class="bi bi-box-seam me-1 text-primary"></i>
                    <strong>Drug Inventory Sync Status:</strong> 
                    Large animal revenue drug entries automatically link &amp; deduct from master government balances (<span class="fw-bold text-success"><?= $monthly_report_data['totals']['govt_drug_deductions_count'] ?> cases synced</span>). 
                    Small animal cases are strictly bypassed (<span class="fw-bold text-primary"><?= $monthly_report_data['totals']['private_drugs_bypassed_count'] ?> private purchases</span>).
                </div>
                <span class="badge bg-primary">Conditional Sync Verified</span>
            </div>

            <!-- Categorized Monthly Summary Table -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-hover align-middle mb-0" id="tableMonthlySummaryReport">
                    <thead class="table-dark text-center">
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="min-width: 170px;" class="text-start">Animal Category</th>
                            <th style="min-width: 130px;">Clinic Workflow</th>
                            <th style="min-width: 90px;">Total Cases</th>
                            <th style="min-width: 100px;">Clinically Affected</th>
                            <th style="min-width: 80px;">Deaths</th>
                            <th style="min-width: 220px;" class="text-start">Common Tentative Diagnoses</th>
                            <th style="min-width: 105px;" class="text-end">Consultation (Rs.)</th>
                            <th style="min-width: 100px;" class="text-end">Drugs (Rs.)</th>
                            <th style="min-width: 110px;" class="text-end">Total Revenue (Rs.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $c_idx = 1; 
                        foreach ($monthly_report_data['categories'] as $cat): 
                            if ($cat['cases'] === 0 && in_array($cat['key'], ['swine', 'horses', 'rabbits', 'others'])) {
                                // Keep major species visible; others collapse if zero
                                continue;
                            }
                        ?>
                        <tr>
                            <td class="text-center fw-bold text-muted"><?= $c_idx++ ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge <?= $cat['badge'] ?> px-2 py-1 fs-6">
                                        <i class="bi <?= $cat['icon'] ?>"></i>
                                    </span>
                                    <div>
                                        <strong class="text-dark d-block"><?= htmlspecialchars($cat['name']) ?></strong>
                                        <small class="text-muted" style="font-size: 0.72rem;">Category: <?= htmlspecialchars($cat['key']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if ($cat['large_cases'] > 0 && $cat['small_cases'] > 0): ?>
                                    <span class="badge bg-secondary-subtle text-secondary border">Dual Clinic</span>
                                <?php elseif ($cat['large_cases'] > 0 || strpos($cat['clinic'], 'Large') !== false): ?>
                                    <span class="badge bg-success-subtle text-success border border-success">Large Animal</span>
                                <?php else: ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary">Small Animal</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $cat['cases'] > 0 ? 'bg-dark' : 'bg-light text-muted border' ?> fs-6 px-2 py-1">
                                    <?= $cat['cases'] ?>
                                </span>
                            </td>
                            <td class="text-center font-monospace fw-semibold"><?= $cat['affected'] ?></td>
                            <td class="text-center font-monospace <?= $cat['deaths'] > 0 ? 'fw-bold text-danger' : 'text-muted' ?>">
                                <?= $cat['deaths'] ?>
                            </td>
                            <td>
                                <?php if (!empty($cat['top_diagnoses_list'])): ?>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach ($cat['top_diagnoses_list'] as $d_name => $d_cnt): ?>
                                            <span class="badge bg-light text-dark border small" title="<?= htmlspecialchars($d_name) ?>: <?= $d_cnt ?> case(s)">
                                                <?= htmlspecialchars(mb_strimwidth($d_name, 0, 22, '...')) ?> (<?= $d_cnt ?>)
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">— No cases logged —</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end font-monospace text-muted">Rs. <?= number_format($cat['revenue_consultation'], 2) ?></td>
                            <td class="text-end font-monospace text-muted">Rs. <?= number_format($cat['revenue_drugs'], 2) ?></td>
                            <td class="text-end font-monospace fw-bold <?= $cat['revenue_total'] > 0 ? 'text-success' : 'text-muted' ?>">
                                Rs. <?= number_format($cat['revenue_total'], 2) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr class="align-middle">
                            <td colspan="3" class="text-uppercase text-end fw-bold">Grand Totals:</td>
                            <td class="text-center fs-6 text-dark"><?= $monthly_report_data['totals']['grand_total_cases'] ?></td>
                            <td class="text-center font-monospace"><?= $monthly_report_data['totals']['grand_total_affected'] ?></td>
                            <td class="text-center font-monospace text-danger"><?= $monthly_report_data['totals']['grand_total_deaths'] ?></td>
                            <td class="small text-muted">Across all animal categories</td>
                            <td class="text-end font-monospace text-dark">Rs. <?= number_format($monthly_report_data['totals']['grand_total_consultation'], 2) ?></td>
                            <td class="text-end font-monospace text-dark">Rs. <?= number_format($monthly_report_data['totals']['grand_total_drugs'], 2) ?></td>
                            <td class="text-end font-monospace fs-6 text-success">Rs. <?= number_format($monthly_report_data['totals']['grand_total_revenue'], 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Morbidity & Action Mechanisms Breakdown Grid -->
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <div class="card border h-100 shadow-none">
                        <div class="card-header bg-white py-2 fw-bold text-success small text-uppercase">
                            <i class="bi bi-shield-shaded me-1"></i> Top Large Animal Diagnoses (Cattle &amp; Buffaloes)
                        </div>
                        <div class="card-body p-3">
                            <?php if (empty($monthly_report_data['diagnoses_large'])): ?>
                                <div class="text-muted text-center py-3 small">No large animal diagnoses recorded for this period.</div>
                            <?php else: ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($monthly_report_data['diagnoses_large'] as $diag_name => $diag_count): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                            <span class="text-dark fw-semibold small"><?= htmlspecialchars($diag_name) ?></span>
                                            <span class="badge bg-success rounded-pill"><?= $diag_count ?> cases</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="card border h-100 shadow-none">
                        <div class="card-header bg-white py-2 fw-bold text-primary small text-uppercase">
                            <i class="bi bi-heart-pulse me-1"></i> Top Small Animal Diagnoses (Dogs, Cats &amp; Pets)
                        </div>
                        <div class="card-body p-3">
                            <?php if (empty($monthly_report_data['diagnoses_small'])): ?>
                                <div class="text-muted text-center py-3 small">No small animal diagnoses recorded for this period.</div>
                            <?php else: ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($monthly_report_data['diagnoses_small'] as $diag_name => $diag_count): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                            <span class="text-dark fw-semibold small"><?= htmlspecialchars($diag_name) ?></span>
                                            <span class="badge bg-primary rounded-pill"><?= $diag_count ?> cases</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Print Signature Block (Visible on print) -->
            <div class="row mt-5 pt-4 border-top text-center" style="margin-top: 3rem;">
                <div class="col-4">
                    <div class="small text-muted mb-4">Prepared By</div>
                    <div class="border-top pt-2 fw-semibold text-dark">Clinical Records Clerk / Data Officer</div>
                </div>
                <div class="col-4">
                    <div class="small text-muted mb-4">Verified By</div>
                    <div class="border-top pt-2 fw-semibold text-dark">Veterinary Surgeon in Charge</div>
                </div>
                <div class="col-4">
                    <div class="small text-muted mb-4">Approved By</div>
                    <div class="border-top pt-2 fw-semibold text-dark">Deputy Director (Veterinary Services)</div>
                </div>
            </div>

        </div>
    </div>
    <?php endif; ?>

</div>

<!-- =========================================================================== -->
<!-- MODAL: ADD / EDIT CLINICAL OPD ENTRY                                        -->
<!-- =========================================================================== -->
<div class="modal fade" id="modalOpdEntry" tabindex="-1" aria-labelledby="modalOpdEntryLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form id="formClinicalOpd" class="modal-content border-0 shadow-lg" method="POST" autocomplete="off" style="border-radius: var(--border-radius);">
            <input type="hidden" name="action" value="save_opd_record">
            <input type="hidden" name="id" id="opd_id" value="0">
            <input type="hidden" name="range_id" value="<?= $range_id ?>">
            <input type="hidden" name="district_id" value="<?= $current_district_id ?>">

                <div class="modal-header text-white" id="modalOpdHeader" style="background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-journal-plus fs-4"></i>
                        <div>
                            <h5 class="modal-title fw-bold mb-0" id="modalOpdEntryLabel">New Clinical OPD Record</h5>
                            <small class="text-white-50">Physical Clinical Register &bull; Overarching Counterfoil Link</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Workflow Selection Bar -->
                    <div class="card border mb-3 bg-light">
                        <div class="card-body p-3">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-2">1. Select Clinic Workflow</label>
                            <div class="d-flex flex-wrap gap-4 align-items-center">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="clinic_type" id="clinicTypeLarge" value="large_animal" checked>
                                    <label class="form-check-label fw-bold text-success fs-6" for="clinicTypeLarge">
                                        <i class="bi bi-shield-shaded me-1"></i> Large Animal Clinic (3-Part Layout &bull; Cattle, Buffalo, Goat, Swine)
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="clinic_type" id="clinicTypeSmall" value="small_animal">
                                    <label class="form-check-label fw-bold text-primary fs-6" for="clinicTypeSmall">
                                        <i class="bi bi-heart-pulse me-1"></i> Small Animal Clinic (Streamlined Diagnostic Flow)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ======================================================= -->
                    <!-- PART 1: FARMER / CLIENT DETAILS                         -->
                    <!-- ======================================================= -->
                    <div class="card border mb-3 shadow-none">
                        <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between border-bottom">
                            <span class="fw-bold text-dark small text-uppercase">
                                <i class="bi bi-person-check-fill text-success me-1"></i>
                                <span id="hdrPart1Title">Part 1: Farmer &amp; Herd Details</span>
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success section-large-animal">
                                <i class="bi bi-lightning-charge-fill me-1"></i>Dual-Trigger Auto-Population (NIC / Farm Reg No)
                            </span>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3 mb-3">
                                <!-- Date -->
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold" id="lblEntryDate">Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="entry_date" id="entry_date" value="<?= date('Y-m-d') ?>" required>
                                </div>

                                <!-- Large Animal Trigger Fields: Farm Reg No & NIC -->
                                <div class="col-md-9 section-large-animal">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">
                                                Farm Registration Number <span class="badge bg-secondary-subtle text-dark">Trigger 1</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white"><i class="bi bi-shield-check text-success"></i></span>
                                                <input type="text" class="form-control" name="farm_reg_no" id="farm_reg_no" 
                                                       placeholder="e.g. 530112345 or Reg No..." 
                                                       style="font-family: monospace; font-weight: 600;">
                                                <button class="btn btn-outline-success" type="button" id="btnAutoFetchFarmer" title="Auto-Fetch from Registry by Farm Reg No">
                                                    <span id="spinnerFetch" class="spinner-border spinner-border-sm d-none me-1" role="status"></span>
                                                    <i class="bi bi-lightning-charge-fill me-1" id="iconFetch"></i> Fetch
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">
                                                Identity Card Number (NIC) <span class="badge bg-secondary-subtle text-dark">Trigger 2</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white"><i class="bi bi-card-heading text-success"></i></span>
                                                <input type="text" class="form-control" name="owner_nic" id="owner_nic" 
                                                       placeholder="e.g. 198512345678 or 851234567V..." 
                                                       style="font-family: monospace; font-weight: 600;">
                                                <button class="btn btn-outline-success" type="button" id="btnAutoFetchNic" title="Auto-Fetch from Registry by NIC">
                                                    <i class="bi bi-search"></i> Fetch
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="fetchFeedback" class="small mt-2" style="display: none;"></div>
                                </div>

                                <!-- Small Animal Sequential Identification: Serial Number & Optional NIC -->
                                <div class="col-md-5 section-small-animal" style="display: none;">
                                    <label class="form-label small fw-bold" id="lblSerialNo">
                                        Serial Number <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-hash text-primary"></i></span>
                                        <input type="text" class="form-control" name="registration_no" id="registration_no" 
                                               placeholder="e.g. SA-<?= date('Y') ?>-001 or Serial No..." 
                                               style="font-family: monospace; font-weight: 600;">
                                    </div>
                                    <div class="form-text small">Clinic serial number or patient register case number.</div>
                                </div>
                                <div class="col-md-4 section-small-animal" style="display: none;">
                                    <label class="form-label small fw-bold">Pet Owner NIC (Optional)</label>
                                    <input type="text" class="form-control" name="small_owner_nic" id="small_owner_nic" placeholder="e.g. 198512345678 or 851234567V">
                                </div>
                            </div>

                            <div class="row g-3">
                                <!-- Farmer's Name -->
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold" id="lblOwnerName">
                                        Farmer's Name <span class="text-danger">*</span>
                                        <span class="badge bg-light text-muted border ms-1 fw-normal">Auto-Populated</span>
                                    </label>
                                    <input type="text" class="form-control" name="owner_name" id="owner_name" placeholder="Full Name of Farmer / Client" required>
                                </div>
                                <!-- Address -->
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold" id="lblOwnerAddress">
                                        Address <span class="badge bg-light text-muted border ms-1 fw-normal">Auto-Populated</span>
                                    </label>
                                    <input type="text" class="form-control" name="owner_address" id="owner_address" placeholder="Residential or Farm Address">
                                </div>
                                <!-- Phone Number -->
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold" id="lblOwnerPhone">
                                        Phone Number <span class="badge bg-light text-muted border ms-1 fw-normal">Auto-Populated</span>
                                    </label>
                                    <input type="text" class="form-control" name="owner_phone" id="owner_phone" placeholder="e.g. 0771234567">
                                </div>
                                <!-- Herd Population (Large Animal specific) -->
                                <div class="col-md-2 section-large-animal" id="colHerdTotalPopulation">
                                    <label class="form-label small fw-bold text-success" id="lblTotalPop">
                                        Herd Population <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control fw-bold border-success" name="herd_total_population" id="herd_total_population" min="1" value="1" required>
                                    <div class="form-text small" style="font-size: 0.72rem; line-height: 1.1;">
                                        Since whole herd exists even if only one animal is sick.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ======================================================= -->
                    <!-- PART 2: AFFECTED ANIMAL DETAILS                         -->
                    <!-- ======================================================= -->
                    <div class="card border mb-3 shadow-none">
                        <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between border-bottom">
                            <span class="fw-bold text-dark small text-uppercase">
                                <i class="bi bi-upc-scan text-primary me-1"></i>
                                <span id="hdrPart2Title">Part 2: Affected Animal Details</span>
                            </span>
                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle section-large-animal">
                                <i class="bi bi-cpu-fill me-1"></i>Auto-Fill via Ear-Tag Number
                            </span>
                        </div>
                        <div class="card-body p-3">
                            <!-- Ear-Tag Auto-Fill Bar (Large Animal Only) -->
                            <div class="p-3 border rounded mb-3 bg-light section-large-animal border-warning">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-7">
                                        <label class="form-label small fw-bold text-dark mb-1">
                                            <i class="bi bi-tag-fill text-warning me-1"></i>Tag / Ear-Tag Number <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white font-monospace"><i class="bi bi-upc"></i></span>
                                            <input type="text" class="form-control font-monospace fw-bold" name="ear_tag_no" id="ear_tag_no" 
                                                   list="farmEarTagsList" placeholder="e.g. 531412345001 or select from farm tag list...">
                                            <datalist id="farmEarTagsList"></datalist>
                                            <button class="btn btn-warning fw-semibold" type="button" id="btnFetchEarTag">
                                                <span id="spinnerTag" class="spinner-border spinner-border-sm d-none me-1" role="status"></span>
                                                <i class="bi bi-search me-1" id="iconTag"></i> Auto-Fill Animal
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="small text-muted ps-md-2 pt-2 pt-md-0" style="font-size: 0.78rem; line-height: 1.25;">
                                            <i class="bi bi-info-circle-fill text-primary me-1"></i>
                                            <strong>Auto-Fill Logic:</strong> Entering or selecting Ear-Tag pulls species, breed, age, sex, color &amp; marks from initial farm registration automatically.
                                        </div>
                                    </div>
                                </div>
                                <div id="tagFeedback" class="small mt-2" style="display: none;"></div>
                            </div>

                            <div class="row g-3 mb-3">
                                <!-- Species -->
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Species <span class="text-danger">*</span></label>
                                    <select class="form-select" name="species" id="species" required>
                                        <option value="" disabled selected>-- Select Species --</option>
                                        <optgroup label="Large Animals" id="optGroupLarge">
                                            <option value="Cattle">Cattle (Neat Cattle)</option>
                                            <option value="Buffalo">Buffalo</option>
                                            <option value="Goat">Goat</option>
                                            <option value="Sheep">Sheep</option>
                                            <option value="Swine / Pig">Swine / Pig</option>
                                            <option value="Equine / Horse">Equine / Horse</option>
                                        </optgroup>
                                        <optgroup label="Small Animals" id="optGroupSmall">
                                            <option value="Canine / Dog">Canine / Dog</option>
                                            <option value="Feline / Cat">Feline / Cat</option>
                                            <option value="Rabbit">Rabbit</option>
                                            <option value="Avian / Pet Bird">Avian / Pet Bird</option>
                                            <option value="Other Pet">Other Pet</option>
                                        </optgroup>
                                    </select>
                                </div>
                                <!-- Breed -->
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Breed <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="breed" id="breed" placeholder="e.g. Jersey, Friesian, Labrador, Persian" required>
                                </div>
                                <!-- Age -->
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold">Age <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="age_years" id="age_years" placeholder="e.g. 2.5 yrs, 4 mos" required>
                                </div>
                                <!-- Sex -->
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold">Sex <span class="text-danger">*</span></label>
                                    <select class="form-select" name="sex" id="sex" required>
                                        <option value="Female" selected>Female (F)</option>
                                        <option value="Male">Male (M)</option>
                                        <option value="Unknown">Unknown</option>
                                    </select>
                                </div>
                                <!-- Color -->
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold">Color</label>
                                    <input type="text" class="form-control" name="coat_colour" id="coat_colour" placeholder="e.g. Black & White, Brown">
                                </div>
                            </div>

                            <div class="row g-3">
                                <!-- Special Marks -->
                                <div class="col-md-12">
                                    <label class="form-label small fw-bold">Special Marks</label>
                                    <input type="text" class="form-control" name="special_marks" id="special_marks" placeholder="e.g. Ear notch right ear, star on forehead, brand mark...">
                                </div>
                            </div>

                            <!-- Hidden counters for DB integrity (Total population removed/hidden in Small Animal) -->
                            <input type="hidden" name="clinically_affected_count" id="clinically_affected_count" value="1">
                            <input type="hidden" name="death_count" id="death_count" value="0">
                        </div>
                    </div>

                    <!-- ======================================================= -->
                    <!-- PART 3: CLINICAL FINDINGS & ACTION TAKEN                -->
                    <!-- ======================================================= -->
                    <div class="card border mb-3 shadow-none">
                        <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between border-bottom">
                            <span class="fw-bold text-dark small text-uppercase">
                                <i class="bi bi-clipboard2-pulse-fill text-danger me-1"></i>
                                <span id="hdrPart3Title">Part 3: Clinical Findings &amp; Action Taken (Sequential Flow)</span>
                            </span>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                <i class="bi bi-123 me-1"></i>Sequential Flow: 1) Complaint &rarr; 2) Tentative Diagnosis &rarr; 3) Action Taken
                            </span>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <!-- 1) Complaint (received from the farm) -->
                                <div class="col-md-12">
                                    <label class="form-label small fw-bold text-dark">
                                        <span class="badge bg-danger text-white me-1">1</span> Complaint (Received from the Farm / Client) <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control" name="complaint" id="complaint" rows="2" 
                                              placeholder="Complaint received from the farm / client (e.g. sudden drop in milk production, anorexia, acute bloat, high pyrexia, recumbency, limping)..." required></textarea>
                                    <div class="form-text small">Primary owner complaint and history observed on the farm or presented at the clinic.</div>
                                </div>

                                <!-- 2) Tentative Diagnosis -->
                                <div class="col-md-12">
                                    <label class="form-label small fw-bold text-dark">
                                        <span class="badge bg-danger text-white me-1">2</span> Tentative Diagnosis <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control fw-semibold" name="tentative_diagnosis" id="tentative_diagnosis" 
                                           placeholder="e.g. Acute Mastitis, Babesiosis, Ruminal Tympany, Foot Rot, Canine Parvovirus, Enteritis..." required>
                                    <div class="form-text small">Clinical evaluation &amp; tentative diagnosis determined by the Veterinary Officer.</div>
                                </div>

                                <!-- 3) Action Taken with Selector for Large Animal -->
                                <div class="col-md-12">
                                    <label class="form-label small fw-bold text-dark">
                                        <span class="badge bg-danger text-white me-1">3</span> Action Taken <span class="text-danger">*</span>
                                    </label>

                                    <!-- Action Mechanisms Selector (Field Visit, Prescriptions, Direct Medication) -->
                                    <div class="mb-2 p-2 rounded bg-light border section-large-animal">
                                        <label class="form-label small fw-bold text-primary mb-1">
                                            <i class="bi bi-gear-wide-connected me-1"></i>Action Mechanism Choice:
                                        </label>
                                        <select class="form-select fw-semibold" name="action_type" id="action_type">
                                            <option value="Direct Medication / Medicine Supply" selected>3) Direct Medication / Medicine Supply</option>
                                            <option value="Field Visit">1) Field Visit (e.g., for large buffaloes / immobile livestock)</option>
                                            <option value="Prescriptions">2) Prescriptions</option>
                                        </select>
                                    </div>

                                    <textarea class="form-control" name="action_taken" id="action_taken" rows="2" 
                                              placeholder="Details of procedures performed, medications administered, prescription dosage or management advice..." required></textarea>
                                    <div class="form-text small">Detailed action protocol, medicines dispensed, or clinical instructions.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ======================================================= -->
                    <!-- COUNTER FILE & RECEIPT INTEGRATION                      -->
                    <!-- ======================================================= -->
                    <div class="card border mb-3 shadow-none bg-warning-subtle" style="border-color: #fde68a !important;">
                        <div class="card-header bg-transparent py-2 d-flex align-items-center justify-content-between border-bottom border-warning">
                            <span class="fw-bold text-dark small text-uppercase">
                                <i class="bi bi-cash-stack text-warning-emphasis me-1"></i>
                                Counter File &amp; Cash Receipt Integration
                            </span>
                            <span class="badge bg-warning text-dark border">
                                <i class="bi bi-link-45deg me-1"></i>Multi-Book Leaf Synchronization
                            </span>
                        </div>
                        <div class="card-body p-3">
                            <!-- Revenue Breakdown -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Consultation (Rs.)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">Rs.</span>
                                        <input type="number" step="0.01" min="0" class="form-control revenue-calc" 
                                               name="revenue_consultation" id="revenue_consultation" value="0.00">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Drugs (Rs.)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">Rs.</span>
                                        <input type="number" step="0.01" min="0" class="form-control revenue-calc" 
                                               name="revenue_drugs" id="revenue_drugs" value="0.00">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-success">Total Revenue (Rs.)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white fw-bold text-success">Rs.</span>
                                        <input type="text" class="form-control fw-bold text-success bg-white" 
                                               id="revenue_total_display" value="0.00" readonly style="font-size: 1.1rem;">
                                    </div>
                                </div>
                                <input type="hidden" name="revenue_others" id="revenue_others" value="0.00">
                            </div>

                            <!-- Large Animal: Master Government Drug Inventory Sync Panel -->
                            <div class="p-3 border rounded bg-white border-success mb-3 section-large-animal" id="boxLargeAnimalDrugSync">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="small fw-bold text-success">
                                        <i class="bi bi-box-seam me-1"></i>Master Government Drug &amp; Vaccine Inventory Linkage
                                    </span>
                                    <span class="badge bg-success-subtle text-success border border-success small">
                                        <i class="bi bi-arrow-repeat me-1"></i>Real-Time Govt Stock Deduction
                                    </span>
                                </div>
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-7">
                                        <label class="form-label small text-muted mb-1">Government Drug Stock Item:</label>
                                        <select class="form-select form-select-sm font-monospace" name="drug_type_id" id="drug_type_id">
                                            <?php if (!empty($large_animal_drugs)): ?>
                                                <?php foreach ($large_animal_drugs as $drg): ?>
                                                    <option value="<?= $drg['id'] ?>">
                                                        <?= htmlspecialchars($drg['vaccine_name']) ?><?= !empty($drg['brand_name']) ? ' (' . htmlspecialchars($drg['brand_name']) . ')' : '' ?> &bull; Govt Stock
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <option value="2">Xyaject Inj &bull; Govt Stock</option>
                                                <option value="4">Vitamin B &bull; Govt Stock</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted mb-1">Doses to Deduct:</label>
                                        <input type="number" min="1" class="form-control form-control-sm text-center fw-bold" name="drug_doses" id="drug_doses" value="1">
                                    </div>
                                    <div class="col-md-2 pt-md-3">
                                        <small class="text-muted d-block" style="font-size: 0.72rem; line-height: 1.15;">
                                            <i class="bi bi-check-circle-fill text-success me-1"></i>Auto-deducts on revenue entry
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Small Animal: Private Purchase Notice (Inventory Linkage Strictly Disabled) -->
                            <div class="p-2 px-3 border rounded bg-white text-muted small mb-3 section-small-animal" style="display: none;">
                                <i class="bi bi-shield-slash text-secondary me-1"></i>
                                <strong>Small Animal Workflow:</strong> Drug inventory linkage is disabled. Drugs are purchased privately by owners and do not affect government stock balances.
                            </div>

                            <!-- Counterfoil Book and Cash Receipt Integration -->
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">
                                        <i class="bi bi-book me-1 text-danger"></i>Physical Cash Receipt Book <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select font-monospace" name="counterfoil_book_id" id="counterfoil_book_id" required>
                                        <?php foreach ($cash_receipt_books as $book): ?>
                                            <option value="<?= $book['id'] ?>" data-serial="<?= htmlspecialchars($book['book_serial_no']) ?>" data-available="<?= $book['available_quantity'] ?>">
                                                Book: <?= htmlspecialchars($book['book_serial_no']) ?> (Leaves: <?= htmlspecialchars($book['page_count'] ?: '100') ?>, Available: <?= intval($book['available_quantity']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text small">
                                        Accounts for multiple physical receipt books mapping specific leaves to individual clients.
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">
                                        <i class="bi bi-receipt me-1 text-primary"></i>Cash Receipt Number / Leaf Serial <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-file-earmark-medical text-primary"></i></span>
                                        <input type="text" class="form-control font-monospace fw-bold text-primary" name="cash_receipt_no" id="cash_receipt_no" 
                                               placeholder="e.g. CR-10492" required>
                                    </div>
                                    <div class="form-text small">
                                        Ties directly into overarching backend counter files &amp; individual client leaf register.
                                    </div>
                                </div>

                                <div class="col-md-12 section-large-animal" id="rowRemarks">
                                    <label class="form-label small fw-bold">Remarks (Optional)</label>
                                    <input type="text" class="form-control" name="remarks" id="remarks" placeholder="Follow-up instructions, referral notes, or administrative remarks">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold px-4" id="btnSaveOpdRecord">
                        <span id="spinnerSave" class="spinner-border spinner-border-sm d-none me-1" role="status"></span>
                        <i class="bi bi-check2-circle me-1" id="iconSave"></i> Save &amp; Sync to Counterfoil
                    </button>
                </div>
        </form>
    </div>
</div>

<!-- =========================================================================== -->
<!-- MODAL: VIEW CLINICAL OPD DETAILS & PHYSICAL REGISTER SLIP                   -->
<!-- =========================================================================== -->
<div class="modal fade" id="modalViewOpdDetails" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: var(--border-radius);">
            <div class="modal-header bg-dark text-white">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-card-checklist fs-4 text-warning"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Clinical OPD Register Record Details</h5>
                        <small class="text-white-50" id="viewModalSubTitle">Physical Register Slip</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="viewModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-dark" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Print Register Slip
                </button>
            </div>
        </div>
    </div>
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
                <input type="hidden" name="district_id" value="<?= $current_district_id ?>">

                <div class="modal-body p-4">

                    <!-- Top Row: Date & Request Origin Dropdown (Required) -->
                    <div class="row g-3 mb-3 p-3 rounded bg-light border">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">
                                <i class="bi bi-calendar-event me-1 text-primary"></i> Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" name="request_date" id="charter_request_date" value="<?= date('Y-m-d') ?>" required>
                            <div class="form-text small">Date the non-clinical request was received.</div>
                        </div>

                        <!-- Specific "Request Origin" Dropdown with Direct Visit and By Call -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">
                                <i class="bi bi-telephone-inbound-fill me-1 text-warning"></i> Request Origin <span class="text-danger">*</span>
                            </label>
                            <select class="form-select fw-semibold" name="request_origin" id="charter_request_origin" required>
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
                                        <input type="text" class="form-control font-monospace text-uppercase" name="nic_no" id="charter_nic_no" placeholder="e.g. 198512345678">
                                        <button class="btn btn-outline-primary" type="button" id="btnAutoFetchCharterNic" title="Auto-Fetch from master registry">
                                            <i class="bi bi-search" id="iconFetchCharterNic"></i>
                                            <span class="spinner-border spinner-border-sm d-none" id="spinnerFetchCharterNic" role="status"></span>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Farm Registration Number (If applicable)</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control font-monospace" name="farm_reg_no" id="charter_farm_reg_no" placeholder="e.g. 530112345">
                                        <button class="btn btn-outline-success" type="button" id="btnAutoFetchCharterFarm" title="Auto-Fetch from master registry">
                                            <i class="bi bi-search" id="iconFetchCharterFarm"></i>
                                            <span class="spinner-border spinner-border-sm d-none" id="spinnerFetchCharterFarm" role="status"></span>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-12" id="charterFetchFeedback" style="display: none;"></div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Beneficiary / Farmer Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="beneficiary_name" id="charter_beneficiary_name" placeholder="Full name of beneficiary or farmer" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Phone Number</label>
                                    <input type="text" class="form-control font-monospace" name="phone_no" id="charter_phone_no" placeholder="e.g. 0771234567">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-dark">Address / Location</label>
                                    <input type="text" class="form-control" name="address" id="charter_address" placeholder="Residential / Farm address and GN division">
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
                                    <textarea class="form-control" name="purpose_of_visit" id="charter_purpose_of_visit" rows="2" 
                                              placeholder="Specify purpose of visit (e.g., Request for bank loan recommendation letter, inquiries regarding government cattle subsidy, issuance of replacement ear tags)..." required></textarea>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold text-dark">Immediate Action Taken <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="immediate_action_taken" id="charter_immediate_action_taken" rows="2" 
                                              placeholder="Describe immediate steps executed (e.g., Issued bank recommendation letter Ref: BK-2026/04; Dispensed 10 ear tags with tag register entry; Guided beneficiary on submission guidelines)..." required></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Final Remarks and Status -->
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-dark">Final Remarks (Optional)</label>
                            <input type="text" class="form-control" name="final_remarks" id="charter_final_remarks" placeholder="Follow-up deadline, referral officer, or concluding administrative remarks">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Service Status</label>
                            <select class="form-select fw-semibold" name="status" id="charter_status_input">
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
                        <span id="spinnerCharterSave" class="spinner-border spinner-border-sm d-none me-1" role="status"></span>
                        <i class="bi bi-check2-circle me-1" id="iconCharterSave"></i> Save Citizen Charter Record
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
// =============================================================================
// GLOBAL MODAL CONTROLLER & WORKFLOW UTILITIES
// =============================================================================
window.openModal = function(selectorOrEl) {
    const el = (typeof selectorOrEl === 'string') ? document.querySelector(selectorOrEl) : selectorOrEl;
    if (!el) return;
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(el).show();
    } else if (typeof $ !== 'undefined' && typeof $(el).modal === 'function') {
        $(el).modal('show');
    }
};

window.hideModal = function(selectorOrEl) {
    const el = (typeof selectorOrEl === 'string') ? document.querySelector(selectorOrEl) : selectorOrEl;
    if (!el) return;
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(el).hide();
    } else if (typeof $ !== 'undefined' && typeof $(el).modal === 'function') {
        $(el).modal('hide');
    }
};

window.applyWorkflowLayout = function(type) {
    const isLarge = (type === 'large_animal');
    if (isLarge) {
        $('.section-large-animal').show();
        $('.section-small-animal').hide();
        $('#colHerdTotalPopulation').show();
        $('#herd_total_population').prop('required', true);
        $('#rowRemarks').show();
        $('#hdrPart1Title').html('Part 1: Farmer &amp; Herd Details');
        $('#hdrPart2Title').html('Part 2: Affected Animal Details');
        $('#hdrPart3Title').html('Part 3: Clinical Findings &amp; Action Taken (Sequential Flow)');
        $('#modalOpdHeader').css('background', 'linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%)');
        $('#modalOpdEntryLabel').text($('#opd_id').val() > 0 ? 'Edit Large Animal OPD Record' : 'New Large Animal OPD Record');
        $('#lblEntryDate').html('Date <span class="text-danger">*</span>');
        $('#lblOwnerName').html('Farmer\'s Name <span class="text-danger">*</span> <span class="badge bg-light text-muted border ms-1 fw-normal">Auto-Populated</span>');
        $('#lblOwnerPhone').html('Phone Number <span class="badge bg-light text-muted border ms-1 fw-normal">Auto-Populated</span>');
        $('#lblOwnerAddress').html('Address <span class="badge bg-light text-muted border ms-1 fw-normal">Auto-Populated</span>');
        $('#lblTotalPop').html('Herd Population <span class="text-danger">*</span>');
        $('#btnSaveOpdRecord').removeClass('btn-primary').addClass('btn-success');
        $('#optGroupLarge').prop('disabled', false);
    } else {
        $('.section-large-animal').hide();
        $('.section-small-animal').show();
        $('#colHerdTotalPopulation').hide();
        $('#herd_total_population').prop('required', false).val('1');
        $('#rowRemarks').hide();
        $('#hdrPart1Title').html('Part 1: Pet Owner Particulars');
        $('#hdrPart2Title').html('Part 2: Patient Particulars');
        $('#hdrPart3Title').html('Part 3: Sequential Diagnostic Flow (3-Step Flow)');
        $('#modalOpdHeader').css('background', 'linear-gradient(135deg, #1565c0 0%, #0d47a1 100%)');
        $('#modalOpdEntryLabel').text($('#opd_id').val() > 0 ? 'Edit Small Animal OPD Record' : 'New Small Animal OPD Record');
        $('#lblEntryDate').html('Date <span class="text-danger">*</span>');
        $('#lblSerialNo').html('Serial Number <span class="text-danger">*</span>');
        $('#lblOwnerName').html('Name <span class="text-danger">*</span>');
        $('#lblOwnerPhone').html('Phone Number');
        $('#lblOwnerAddress').html('Address');
        $('#btnSaveOpdRecord').removeClass('btn-success').addClass('btn-primary');
    }
};

window.openNewOpdModalWithPreset = function(type) {
    const formEl = document.getElementById('formClinicalOpd');
    if (formEl) formEl.reset();
    $('#opd_id').val('0');
    $('#fetchFeedback').hide();
    $('#tagFeedback').hide();
    $('#revenue_total_display').val('0.00');

    if (type === 'large_animal') {
        $('#clinicTypeLarge').prop('checked', true);
        applyWorkflowLayout('large_animal');
        $('#species').val('Cattle');
    } else {
        $('#clinicTypeSmall').prop('checked', true);
        applyWorkflowLayout('small_animal');
        $('#species').val('Canine / Dog');
    }

    openModal('#modalOpdEntry');
};

window.performEarTagLookup = function() {
    const tagVal = $('#ear_tag_no').val().trim();
    const farmVal = $('#farm_reg_no').val().trim();
    if (!tagVal) {
        Swal.fire('Enter Ear-Tag', 'Please enter or select an Ear-Tag Number to auto-fill animal details.', 'info');
        return;
    }

    $('#spinnerTag').removeClass('d-none');
    $('#iconTag').addClass('d-none');
    $('#btnFetchEarTag').prop('disabled', true);
    $('#tagFeedback').html(`<span class="text-muted"><i class="spinner-border spinner-border-sm me-1"></i>Searching animal registry for Tag #${tagVal}...</span>`).show();

    $.ajax({
        url: 'processors/clinical_services_crud.php',
        method: 'POST',
        dataType: 'json',
        data: {
            action: 'lookup_ear_tag',
            ear_tag_no: tagVal,
            farm_reg_no: farmVal
        },
        success: function(res) {
            $('#spinnerTag').addClass('d-none');
            $('#iconTag').removeClass('d-none');
            $('#btnFetchEarTag').prop('disabled', false);

            if (res.success && res.data) {
                const a = res.data;
                // Auto-fill Species, Breed, Age, Sex, Color, Special Marks
                if (a.species) $('#species').val(a.species).addClass('auto-fill-flash');
                if (a.breed) $('#breed').val(a.breed).addClass('auto-fill-flash');
                if (a.age) $('#age_years').val(a.age).addClass('auto-fill-flash');
                if (a.gender) $('#sex').val(a.gender).addClass('auto-fill-flash');
                if (a.colour) $('#coat_colour').val(a.colour).addClass('auto-fill-flash');
                if (a.special_marks) $('#special_marks').val(a.special_marks).addClass('auto-fill-flash');

                // If owner particulars are empty, cross-populate from linked farm
                if (a.farmer_name && !$('#owner_name').val().trim()) {
                    $('#owner_name').val(a.farmer_name).addClass('auto-fill-flash');
                }
                if (a.farm_reg_no && !$('#farm_reg_no').val().trim()) {
                    $('#farm_reg_no').val(a.farm_reg_no).addClass('auto-fill-flash');
                }

                setTimeout(() => {
                    $('.auto-fill-flash').removeClass('auto-fill-flash');
                }, 1200);

                $('#tagFeedback').html(
                    `<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Auto-Filled: Tag #${a.ear_tag_no} &bull; ${a.species} (${a.breed || 'Local'}) &bull; Age: ${a.age || '—'} &bull; Sex: ${a.gender || '—'}</span>`
                ).show();
            } else {
                $('#tagFeedback').html(
                    `<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>${res.message || 'Tag not found in registered animal database.'}</span>`
                ).show();
            }
        },
        error: function() {
            $('#spinnerTag').addClass('d-none');
            $('#iconTag').removeClass('d-none');
            $('#btnFetchEarTag').prop('disabled', false);
            $('#tagFeedback').html('<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Server error looking up ear tag.</span>').show();
        }
    });
};

window.viewOpdDetails = function(id) {
    openModal('#modalViewOpdDetails');
    $('#viewModalBody').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>');

    $.ajax({
        url: 'processors/clinical_services_crud.php',
        method: 'GET',
        dataType: 'json',
        data: { action: 'get_opd_record', id: id },
        success: function(res) {
            if (res.success && res.data) {
                const d = res.data;
                const isLarge = (d.clinic_type === 'large_animal');
                $('#viewModalSubTitle').text(isLarge ? 'Large Animal Clinical Register Sheet' : 'Small Animal Clinical Register Sheet');

                let actionTypeBadge = '';
                if (isLarge) {
                    const actionType = d.action_type || 'Direct Medication / Medicine Supply';
                    if (actionType === 'Field Visit') {
                        actionTypeBadge = '<span class="badge bg-primary text-white mb-2"><i class="bi bi-geo-alt-fill me-1"></i>1) Field Visit (e.g. for large buffaloes)</span>';
                    } else if (actionType === 'Prescriptions') {
                        actionTypeBadge = '<span class="badge bg-info text-dark mb-2"><i class="bi bi-file-earmark-medical me-1"></i>2) Prescriptions</span>';
                    } else {
                        actionTypeBadge = '<span class="badge bg-success text-white mb-2"><i class="bi bi-capsule me-1"></i>3) Direct Medication / Medicine Supply</span>';
                    }
                }

                const html = `
                    <div class="border rounded p-3 mb-3 bg-light">
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <small class="text-muted text-uppercase fw-bold d-block">Intake Date</small>
                                <strong class="text-dark">${d.entry_date}</strong>
                            </div>
                            <div class="col-6 col-md-3">
                                <small class="text-muted text-uppercase fw-bold d-block">${isLarge ? 'Farm Reg No / Serial' : 'Serial Number'}</small>
                                <span class="badge ${isLarge ? 'bg-success' : 'bg-primary'} font-monospace">${d.registration_no || d.farm_reg_no || '—'}</span>
                            </div>
                            <div class="col-6 col-md-3">
                                <small class="text-muted text-uppercase fw-bold d-block">Cash Receipt No</small>
                                <strong class="text-danger font-monospace">${d.cash_receipt_no}</strong>
                            </div>
                            <div class="col-6 col-md-3">
                                <small class="text-muted text-uppercase fw-bold d-block">Counterfoil Leaf Link</small>
                                ${d.leaf_issue_id ? '<span class="badge bg-success-subtle text-success border border-success">Leaf #' + d.leaf_issue_id + ' Synced</span>' : '<span class="badge bg-secondary">Unlinked</span>'}
                            </div>
                        </div>
                    </div>

                    <!-- Part 1: Farmer Details -->
                    <div class="card border mb-3">
                        <div class="card-header bg-white fw-bold small text-muted text-uppercase py-2">
                            <i class="bi bi-person-check-fill text-success me-1"></i> ${isLarge ? 'Part 1: Farmer &amp; Herd Details' : 'Part 1: Pet Owner Particulars'}
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-2">
                                <div class="col-md-6"><strong>Name:</strong> ${d.owner_name}</div>
                                <div class="col-md-6"><strong>Phone:</strong> ${d.owner_phone || '—'}</div>
                                <div class="col-md-6"><strong>Address:</strong> ${d.owner_address || '—'}</div>
                                <div class="col-md-6"><strong>NIC:</strong> ${d.owner_nic || '—'}</div>
                                ${isLarge ? `<div class="col-md-12 mt-2"><strong>Herd Population:</strong> <span class="badge bg-success-subtle text-success border border-success fw-bold fs-6">${d.herd_total_population}</span> <small class="text-muted ms-1">(Whole herd exists even if only one animal is sick)</small></div>` : ''}
                            </div>
                        </div>
                    </div>

                    <!-- Part 2: Affected Animal Details -->
                    <div class="card border mb-3">
                        <div class="card-header bg-white fw-bold small text-muted text-uppercase py-2">
                            <i class="bi bi-upc-scan text-primary me-1"></i> ${isLarge ? 'Part 2: Affected Animal Details' : 'Part 2: Patient Particulars'}
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-2">
                                ${isLarge ? `<div class="col-md-6"><strong>Tag / Ear-Tag No:</strong> ${d.ear_tag_no ? '<span class="badge bg-warning text-dark font-monospace fw-bold">' + d.ear_tag_no + '</span>' : '<span class="text-muted">Untagged</span>'}</div>` : ''}
                                <div class="col-md-6"><strong>Species &amp; Breed:</strong> ${d.species} (${d.breed || 'Local'})</div>
                                <div class="col-md-6"><strong>Age &amp; Sex:</strong> ${d.age_years || '—'} &bull; ${d.sex}</div>
                                <div class="col-md-6"><strong>Coat Color:</strong> ${d.coat_colour || '—'}</div>
                                <div class="col-md-12"><strong>Special Marks:</strong> ${d.special_marks || '—'}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Part 3: Sequential Clinical Findings & Action Taken -->
                    <div class="card border mb-3">
                        <div class="card-header bg-white fw-bold small text-muted text-uppercase py-2 text-danger">
                            <i class="bi bi-clipboard2-pulse-fill text-danger me-1"></i> Part 3: Clinical Findings &amp; Action Taken (Sequential Flow)
                        </div>
                        <div class="card-body p-3">
                            <div class="mb-3">
                                <span class="badge bg-danger text-white me-1">1</span> <strong>Complaint (Received from the Farm / Client):</strong>
                                <div class="p-2 mt-1 rounded bg-light border text-dark">${d.complaint || '—'}</div>
                            </div>
                            <div class="mb-3">
                                <span class="badge bg-danger text-white me-1">2</span> <strong>Tentative Diagnosis:</strong>
                                <div class="p-2 mt-1 rounded bg-danger-subtle text-danger fw-bold">${d.tentative_diagnosis}</div>
                            </div>
                            <div>
                                <span class="badge bg-danger text-white me-1">3</span> <strong>Action Taken:</strong>
                                <div class="mt-1">${actionTypeBadge}</div>
                                <div class="p-2 mt-1 rounded bg-light border text-secondary">${d.action_taken}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue & Counter File Linkage -->
                    <div class="card border bg-warning-subtle">
                        <div class="card-header bg-transparent fw-bold small text-uppercase py-2 text-warning-emphasis">
                            <i class="bi bi-cash me-1"></i> Revenue Breakdown &amp; Overarching Counterfoil Link
                        </div>
                        <div class="card-body p-3">
                            <div class="row text-center g-2">
                                <div class="col-4">
                                    <small class="text-muted d-block">Consultation</small>
                                    <strong class="font-monospace">Rs. ${parseFloat(d.revenue_consultation).toFixed(2)}</strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Drugs</small>
                                    <strong class="font-monospace">Rs. ${parseFloat(d.revenue_drugs).toFixed(2)}</strong>
                                </div>
                                <div class="col-4 border-start">
                                    <small class="text-success fw-bold d-block">TOTAL REVENUE</small>
                                    <strong class="font-monospace text-success fs-6">Rs. ${parseFloat(d.revenue_total).toFixed(2)}</strong>
                                </div>
                            </div>
                            ${d.remarks ? '<div class="mt-2 pt-2 border-top small text-muted"><strong>Remarks:</strong> ' + d.remarks + '</div>' : ''}
                        </div>
                    </div>
                `;
                $('#viewModalBody').html(html);
            } else {
                $('#viewModalBody').html('<div class="alert alert-danger">Failed to load record details.</div>');
            }
        },
        error: function() {
            $('#viewModalBody').html('<div class="alert alert-danger">Network or server error loading record details.</div>');
        }
    });
};

window.editOpdRecord = function(id) {
    $.ajax({
        url: 'processors/clinical_services_crud.php',
        method: 'GET',
        dataType: 'json',
        data: { action: 'get_opd_record', id: id },
        success: function(res) {
            if (res.success && res.data) {
                const d = res.data;
                $('#opd_id').val(d.id);
                $('#entry_date').val(d.entry_date);

                if (d.clinic_type === 'large_animal') {
                    $('#clinicTypeLarge').prop('checked', true);
                    applyWorkflowLayout('large_animal');
                    $('#farm_reg_no').val(d.farm_reg_no || d.registration_no);
                    $('#ear_tag_no').val(d.ear_tag_no || '');
                    $('#action_type').val(d.action_type || 'Direct Medication / Medicine Supply');
                } else {
                    $('#clinicTypeSmall').prop('checked', true);
                    applyWorkflowLayout('small_animal');
                    $('#registration_no').val(d.registration_no);
                    $('#small_owner_nic').val(d.owner_nic || '');
                }

                $('#owner_name').val(d.owner_name);
                $('#owner_nic').val(d.owner_nic || '');
                $('#owner_address').val(d.owner_address || '');
                $('#owner_phone').val(d.owner_phone || '');

                $('#herd_total_population').val(d.herd_total_population || 1);
                $('#species').val(d.species);
                $('#breed').val(d.breed || '');
                $('#age_years').val(d.age_years || '');
                $('#sex').val(d.sex);
                $('#coat_colour').val(d.coat_colour || '');
                $('#special_marks').val(d.special_marks || '');

                $('#clinically_affected_count').val(d.clinically_affected_count || 1);
                $('#death_count').val(d.death_count || 0);

                // Sequential flow: 1) Complaint, 2) Tentative Diagnosis, 3) Action Taken
                $('#complaint').val(d.complaint || '');
                $('#tentative_diagnosis').val(d.tentative_diagnosis);
                $('#action_taken').val(d.action_taken);

                $('#revenue_consultation').val(d.revenue_consultation);
                $('#revenue_drugs').val(d.revenue_drugs);
                $('#revenue_others').val(d.revenue_others || 0);
                $('#revenue_total_display').val(parseFloat(d.revenue_total).toFixed(2));

                if (d.counterfoil_book_id) {
                    $('#counterfoil_book_id').val(d.counterfoil_book_id);
                }
                $('#cash_receipt_no').val(d.cash_receipt_no);
                $('#remarks').val(d.remarks || '');

                $('#modalOpdEntryLabel').text(d.clinic_type === 'large_animal' ? 'Edit Large Animal OPD Record' : 'Edit Small Animal OPD Record');

                openModal('#modalOpdEntry');
            } else {
                Swal.fire('Error', res.message || 'Failed to retrieve record for editing.', 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Failed to communicate with server.', 'error');
        }
    });
};

window.deleteOpdRecord = function(id, name, receiptNo) {
    Swal.fire({
        title: 'Delete Clinical Record?',
        html: `Are you sure you want to remove OPD Record for <strong>${name}</strong> (Receipt: <strong>${receiptNo}</strong>)?<br><br><span class="text-danger small">This will also clean up the linked overarching counterfoil leaf &amp; cash book summary.</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#820100',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete Record'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'processors/clinical_services_crud.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'delete_opd_record',
                    id: id
                },
                success: function(res) {
                    if (res.success) {
                        Swal.fire('Deleted!', res.message, 'success').then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to communicate with server.', 'error');
                }
            });
        }
    });
};

// =============================================================================
// DOM READY EVENT BINDINGS
// =============================================================================
$(document).ready(function() {
    // 1. Initialize DataTables safely
    try {
        if ($.fn && $.fn.DataTable) {
            const dtConfig = {
                pageLength: 25,
                order: [[1, 'desc']],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search clinical records..."
                }
            };
            if ($('#tableLargeAnimalOpd').length && $('#tableLargeAnimalOpd tbody tr').length > 0 && $('#tableLargeAnimalOpd tbody tr td').length > 1) {
                $('#tableLargeAnimalOpd').DataTable(dtConfig);
            }
            if ($('#tableSmallAnimalOpd').length && $('#tableSmallAnimalOpd tbody tr').length > 0 && $('#tableSmallAnimalOpd tbody tr td').length > 1) {
                $('#tableSmallAnimalOpd').DataTable(dtConfig);
            }
        }
    } catch(e) {
        console.warn('DataTable non-fatal notice:', e);
    }

    // 2. Dynamic Workflow Switching in Modal
    $('input[name="clinic_type"]').on('change', function() {
        applyWorkflowLayout($(this).val());
    });

    // 3. Live Revenue Sum Calculation
    $('.revenue-calc').on('input', function() {
        calculateTotalRevenue();
    });

    function calculateTotalRevenue() {
        const consult = parseFloat($('#revenue_consultation').val()) || 0;
        const drugs   = parseFloat($('#revenue_drugs').val()) || 0;
        const others  = parseFloat($('#revenue_others').val()) || 0;
        const total   = consult + drugs + others;
        $('#revenue_total_display').val(total.toFixed(2));
    }

    // 4. Dual-Trigger Auto-Population (Large Animals):
    // Listens for inputs in either NIC or Farm Registration Number
    function performFarmerLookup(triggerSource) {
        let query = '';
        let triggerLabel = '';
        const nicVal = $('#owner_nic').val().trim();
        const farmVal = $('#farm_reg_no').val().trim();

        if (triggerSource === 'nic') {
            query = nicVal;
            triggerLabel = 'NIC';
        } else if (triggerSource === 'farm_reg_no') {
            query = farmVal;
            triggerLabel = 'Farm Reg No';
        } else {
            // Auto-detect which trigger has an active input
            if (nicVal !== '') {
                query = nicVal;
                triggerLabel = 'NIC';
            } else {
                query = farmVal;
                triggerLabel = 'Farm Reg No';
            }
        }

        if (!query) {
            Swal.fire({
                icon: 'info',
                title: 'Enter NIC or Farm Reg No',
                text: 'Please enter either the Farmer Identity Card Number (NIC) OR the Farm Registration Number to auto-fetch details from the master registry.'
            });
            return;
        }

        $('#spinnerFetch').removeClass('d-none');
        $('#iconFetch').addClass('d-none');
        $('#btnAutoFetchFarmer, #btnAutoFetchNic').prop('disabled', true);
        $('#fetchFeedback').html(`<span class="text-muted"><i class="spinner-border spinner-border-sm me-1"></i>Searching master registry by ${triggerLabel}...</span>`).show();

        $.ajax({
            url: 'processors/clinical_services_crud.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'lookup_farmer',
                query: query,
                nic: nicVal,
                farm_reg_no: farmVal
            },
            success: function(res) {
                $('#spinnerFetch').addClass('d-none');
                $('#iconFetch').removeClass('d-none');
                $('#btnAutoFetchFarmer, #btnAutoFetchNic').prop('disabled', false);

                if (res.success && res.data) {
                    const f = res.data;
                    // Auto-fetch farmer's Name, Address, and Phone Number from the master registry
                    $('#owner_name').val(f.farmer_name).addClass('auto-fill-flash');
                    $('#owner_address').val(f.address).addClass('auto-fill-flash');
                    $('#owner_phone').val(f.phone).addClass('auto-fill-flash');

                    // Cross-populate complementary trigger field if currently empty
                    if (f.nic && !$('#owner_nic').val().trim()) {
                        $('#owner_nic').val(f.nic).addClass('auto-fill-flash');
                    }
                    if (f.farm_reg_no && !$('#farm_reg_no').val().trim()) {
                        $('#farm_reg_no').val(f.farm_reg_no).addClass('auto-fill-flash');
                    }

                    // Auto-fill herd population from farm census if available
                    if (f.total_cattle_buffalo && parseInt(f.total_cattle_buffalo) > 0) {
                        $('#herd_total_population').val(f.total_cattle_buffalo).addClass('auto-fill-flash');
                    }

                    // Populate ear tag datalist from initial farm registration
                    if (f.registered_ear_tags && f.registered_ear_tags.length > 0) {
                        let tagOptions = '';
                        f.registered_ear_tags.forEach(function(tag) {
                            tagOptions += `<option value="${tag}">${tag}</option>`;
                        });
                        $('#farmEarTagsList').html(tagOptions);
                    }

                    setTimeout(() => {
                        $('.auto-fill-flash').removeClass('auto-fill-flash');
                    }, 1200);

                    $('#fetchFeedback').html(
                        `<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Auto-Fetched: ${f.farmer_name} &bull; ${f.phone || 'No phone'} (${f.source})</span>`
                    ).show();
                } else {
                    $('#fetchFeedback').html(
                        `<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>${res.message}</span>`
                    ).show();
                }
            },
            error: function() {
                $('#spinnerFetch').addClass('d-none');
                $('#iconFetch').removeClass('d-none');
                $('#btnAutoFetchFarmer, #btnAutoFetchNic').prop('disabled', false);
                $('#fetchFeedback').html(
                    '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Server error looking up farmer registry.</span>'
                ).show();
            }
        });
    }

    // Trigger 1: Identity Card Number (NIC) field events
    let nicTimer = null;
    $('#owner_nic').on('blur', function() {
        const isLarge = $('#clinicTypeLarge').is(':checked');
        const val = $(this).val().trim();
        if (isLarge && val.length >= 9 && $('#owner_name').val().trim() === '') {
            performFarmerLookup('nic');
        }
    }).on('input', function() {
        const isLarge = $('#clinicTypeLarge').is(':checked');
        const val = $(this).val().trim();
        clearTimeout(nicTimer);
        // Sri Lankan standard NIC format: 9 digits + letter or 12 digits
        if (isLarge && (val.length === 10 || val.length === 12) && $('#owner_name').val().trim() === '') {
            nicTimer = setTimeout(() => {
                performFarmerLookup('nic');
            }, 600);
        }
    });

    $('#btnAutoFetchNic').on('click', function(e) {
        e.preventDefault();
        performFarmerLookup('nic');
    });

    // Trigger 2: Farm Registration Number field events
    let farmTimer = null;
    $('#farm_reg_no').on('blur', function() {
        const isLarge = $('#clinicTypeLarge').is(':checked');
        const val = $(this).val().trim();
        if (isLarge && val.length >= 3 && $('#owner_name').val().trim() === '') {
            performFarmerLookup('farm_reg_no');
        }
    }).on('input', function() {
        const isLarge = $('#clinicTypeLarge').is(':checked');
        const val = $(this).val().trim();
        clearTimeout(farmTimer);
        if (isLarge && val.length >= 5 && $('#owner_name').val().trim() === '') {
            farmTimer = setTimeout(() => {
                performFarmerLookup('farm_reg_no');
            }, 600);
        }
    });

    $('#btnAutoFetchFarmer').on('click', function(e) {
        e.preventDefault();
        performFarmerLookup('farm_reg_no');
    });

    // Ear-Tag Auto-Fill: Listen for Ear-Tag Number input / selection
    let tagTimer = null;
    $('#ear_tag_no').on('change', function() {
        const isLarge = $('#clinicTypeLarge').is(':checked');
        const val = $(this).val().trim();
        if (isLarge && val.length >= 3) {
            performEarTagLookup();
        }
    }).on('input', function() {
        const isLarge = $('#clinicTypeLarge').is(':checked');
        const val = $(this).val().trim();
        clearTimeout(tagTimer);
        // If exact tag length (standard tags are usually 12 digits)
        if (isLarge && val.length >= 8) {
            tagTimer = setTimeout(() => {
                performEarTagLookup();
            }, 600);
        }
    });

    $('#btnFetchEarTag').on('click', function(e) {
        e.preventDefault();
        performEarTagLookup();
    });

    // 5. Delegated Modal Open & Action Triggers
    $(document).on('click', '#btnOpenNewOpdModal', function(e) {
        e.preventDefault();
        openNewOpdModalWithPreset('<?= $active_tab ?>');
    });

    $(document).on('click', '#btnAddLargeAnimalRecord', function(e) {
        e.preventDefault();
        openNewOpdModalWithPreset('large_animal');
    });

    $(document).on('click', '#btnAddSmallAnimalRecord', function(e) {
        e.preventDefault();
        openNewOpdModalWithPreset('small_animal');
    });

    $(document).on('click', '.btn-view-opd', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        if (id) viewOpdDetails(id);
    });

    $(document).on('click', '.btn-edit-opd', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        if (id) editOpdRecord(id);
    });

    $(document).on('click', '.btn-delete-opd', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const name = $(this).data('name');
        const receipt = $(this).data('receipt');
        if (id) deleteOpdRecord(id, name, receipt);
    });

    // 5b. Export Monthly Summary Report to CSV
    $(document).on('click', '#btnExportMonthlyCsv', function(e) {
        e.preventDefault();
        const table = document.getElementById('tableMonthlySummaryReport');
        if (!table) {
            Swal.fire('No Data', 'Monthly summary table not found on this view.', 'info');
            return;
        }
        let csv = [];
        const rows = table.querySelectorAll('tr');
        for (let i = 0; i < rows.length; i++) {
            let row = [];
            const cols = rows[i].querySelectorAll('th, td');
            for (let j = 0; j < cols.length; j++) {
                let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace(/\s+/g, ' ').trim();
                text = text.replace(/"/g, '""');
                row.push('"' + text + '"');
            }
            if (row.length > 0) {
                csv.push(row.join(','));
            }
        }
        const csvString = csv.join('\n');
        const filename = 'DAPH_Monthly_Clinical_Summary_<?= $selected_year ?>_<?= sprintf("%02d", $selected_month) ?>.csv';
        const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // 6. Save Clinical OPD Record via AJAX
    $('#formClinicalOpd').on('submit', function(e) {
        e.preventDefault();

        // If small animal workflow is active, sync small_owner_nic to owner_nic
        if ($('#clinicTypeSmall').is(':checked') && $('#small_owner_nic').val()) {
            $('#owner_nic').val($('#small_owner_nic').val().trim());
        }

        // Validation for cash receipt number
        const receiptNo = $('#cash_receipt_no').val().trim();
        if (!receiptNo) {
            Swal.fire('Required Field', 'Cash Receipt No. is mandatory for revenue registration and overarching counter file linkage.', 'warning');
            $('#cash_receipt_no').focus();
            return;
        }

        $('#spinnerSave').removeClass('d-none');
        $('#iconSave').addClass('d-none');
        $('#btnSaveOpdRecord').prop('disabled', true);

        const formData = $(this).serialize();

        $.ajax({
            url: 'processors/clinical_services_crud.php',
            method: 'POST',
            dataType: 'json',
            data: formData,
            success: function(res) {
                $('#spinnerSave').addClass('d-none');
                $('#iconSave').removeClass('d-none');
                $('#btnSaveOpdRecord').prop('disabled', false);

                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Record Saved Successfully!',
                        html: `${res.message}<br><br><span class="badge bg-success">Cash Receipt #${res.receipt_no}</span> &bull; <span class="badge bg-primary">Counterfoil Leaf #${res.leaf_id}</span>`,
                        confirmButtonColor: '#2e7d32'
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
                $('#btnSaveOpdRecord').prop('disabled', false);
                Swal.fire('Error', 'An unexpected network or server error occurred.', 'error');
            }
        });
    });

    // =========================================================================
    // SERVICES REGISTER (CITIZEN CHARTER NON-CLINICAL REQUESTS) LOGIC
    // =========================================================================

    // 1. Initialize Services Register DataTable safely
    try {
        if ($.fn && $.fn.DataTable && $('#tableCitizenCharter').length) {
            $('#tableCitizenCharter').DataTable({
                pageLength: 25,
                order: [[0, 'desc'], [1, 'desc']],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search services register..."
                }
            });
        }
    } catch(e) {
        console.warn('DataTable notice:', e);
    }

    // 2. Open Modal for New Citizen Charter Request
    function openNewCharterModal() {
        $('#formCitizenCharter')[0].reset();
        $('#charter_id').val('0');
        $('#charter_request_date').val('<?= date('Y-m-d') ?>');
        $('#charter_request_origin').val('Direct Visit');
        $('#charter_status_input').val('Completed');
        $('#charterFetchFeedback').hide();
        $('#modalCharterIntakeLabel').text('Log Non-Clinical Service Request');
        openModal('#modalCharterIntake');
    }

    $('#btnOpenIntakeModal, #btnOpenIntakeModalSub, #btnOpenIntakeModalTable, #btnOpenIntakeModalHeader').on('click', function() {
        openNewCharterModal();
    });

    // 3. Quick Purpose Presets
    $('.btn-preset-purpose').on('click', function() {
        const val = $(this).data('purpose');
        $('#charter_purpose_of_visit').val(val);
    });

    // 4. Dual-Trigger Beneficiary Lookup (NIC / Farm Reg No)
    function performCharterBeneficiaryLookup(term) {
        if (!term) {
            Swal.fire('Enter Search Term', 'Please enter an Identity Card Number (NIC) or Farm Registration Number.', 'info');
            return;
        }

        $('#spinnerFetchCharterNic, #spinnerFetchCharterFarm').removeClass('d-none');
        $('#iconFetchCharterNic, #iconFetchCharterFarm').addClass('d-none');
        $('#btnAutoFetchCharterNic, #btnAutoFetchCharterFarm').prop('disabled', true);
        $('#charterFetchFeedback').html('<span class="text-muted"><i class="spinner-border spinner-border-sm me-1"></i>Searching master registries...</span>').show();

        $.ajax({
            url: 'processors/citizen_charter_crud.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'lookup_beneficiary',
                query: term
            },
            success: function(res) {
                $('#spinnerFetchCharterNic, #spinnerFetchCharterFarm').addClass('d-none');
                $('#iconFetchCharterNic, #iconFetchCharterFarm').removeClass('d-none');
                $('#btnAutoFetchCharterNic, #btnAutoFetchCharterFarm').prop('disabled', false);

                if (res.success && res.data) {
                    const d = res.data;
                    $('#charter_beneficiary_name').val(d.name).addClass('auto-fill-flash');
                    if (d.nic) $('#charter_nic_no').val(d.nic).addClass('auto-fill-flash');
                    if (d.phone) $('#charter_phone_no').val(d.phone).addClass('auto-fill-flash');
                    if (d.address) $('#charter_address').val(d.address).addClass('auto-fill-flash');
                    if (d.farm_reg_no) $('#charter_farm_reg_no').val(d.farm_reg_no).addClass('auto-fill-flash');

                    setTimeout(() => {
                        $('.auto-fill-flash').removeClass('auto-fill-flash');
                    }, 1200);

                    $('#charterFetchFeedback').html(
                        `<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Auto-Fetched: ${d.name} &bull; ${d.phone || 'No Phone'} (${d.source})</span>`
                    ).show();
                } else {
                    $('#charterFetchFeedback').html(
                        `<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>${res.message}</span>`
                    ).show();
                }
            },
            error: function() {
                $('#spinnerFetchCharterNic, #spinnerFetchCharterFarm').addClass('d-none');
                $('#iconFetchCharterNic, #iconFetchCharterFarm').removeClass('d-none');
                $('#btnAutoFetchCharterNic, #btnAutoFetchCharterFarm').prop('disabled', false);
                $('#charterFetchFeedback').html('<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Server error looking up beneficiary.</span>').show();
            }
        });
    }

    $('#btnAutoFetchCharterNic').on('click', function() {
        performCharterBeneficiaryLookup($('#charter_nic_no').val().trim());
    });

    $('#btnAutoFetchCharterFarm').on('click', function() {
        performCharterBeneficiaryLookup($('#charter_farm_reg_no').val().trim());
    });

    // Auto-search on NIC input when 10 or 12 chars
    let charterNicTimer = null;
    $('#charter_nic_no').on('input', function() {
        const val = $(this).val().trim();
        clearTimeout(charterNicTimer);
        if ((val.length === 10 || val.length === 12) && $('#charter_beneficiary_name').val().trim() === '') {
            charterNicTimer = setTimeout(() => { performCharterBeneficiaryLookup(val); }, 600);
        }
    });

    // 5. Submit Citizen Charter Form via AJAX
    $('#formCitizenCharter').on('submit', function(e) {
        e.preventDefault();

        $('#spinnerCharterSave').removeClass('d-none');
        $('#iconCharterSave').addClass('d-none');
        $('#btnSaveCharterRequest').prop('disabled', true);

        $.ajax({
            url: 'processors/citizen_charter_crud.php',
            method: 'POST',
            dataType: 'json',
            data: $(this).serialize(),
            success: function(res) {
                $('#spinnerCharterSave').addClass('d-none');
                $('#iconCharterSave').removeClass('d-none');
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
                $('#spinnerCharterSave').addClass('d-none');
                $('#iconCharterSave').removeClass('d-none');
                $('#btnSaveCharterRequest').prop('disabled', false);
                Swal.fire('Error', 'Network or server error saving request.', 'error');
            }
        });
    });

    // 6. Edit Citizen Charter Request
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
                    $('#charter_request_date').val(d.request_date);
                    $('#charter_request_origin').val(d.request_origin);
                    $('#charter_beneficiary_name').val(d.beneficiary_name);
                    $('#charter_nic_no').val(d.nic_no || '');
                    $('#charter_phone_no').val(d.phone_no || '');
                    $('#charter_address').val(d.address || '');
                    $('#charter_farm_reg_no').val(d.farm_reg_no || '');
                    $('#charter_purpose_of_visit').val(d.purpose_of_visit);
                    $('#charter_immediate_action_taken').val(d.immediate_action_taken);
                    $('#charter_final_remarks').val(d.final_remarks || '');
                    $('#charter_status_input').val(d.status);

                    $('#modalCharterIntakeLabel').text('Edit Non-Clinical Request (' + d.reference_no + ')');
                    openModal('#modalCharterIntake');
                } else {
                    Swal.fire('Error', res.message || 'Failed to load request.', 'error');
                }
            }
        });
    });

    // 7. View & Print Citizen Charter Slip
    $(document).on('click', '.btn-view-request', function() {
        const id = $(this).data('id');
        openModal('#modalViewCharterSlip');

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
                                    ${(d.purpose_of_visit || '').replace(/\\n/g, '<br>')}
                                </div>
                            </div>

                            <div class="card border mb-3 bg-light">
                                <div class="card-header bg-white fw-bold small text-muted text-uppercase py-2 text-success">
                                    <i class="bi bi-check2-circle me-1"></i> Immediate Action Taken by Veterinary Office
                                </div>
                                <div class="card-body p-3 text-dark">
                                    ${(d.immediate_action_taken || '').replace(/\\n/g, '<br>')}
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

    // 8. Delete Citizen Charter Request
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

    // 9. Export Citizen Charter to CSV
    $('#btnExportCharterCsv').on('click', function() {
        const table = document.getElementById('tableCitizenCharter');
        if (!table) return;
        let csv = [];
        const rows = table.querySelectorAll('tr');
        for (let i = 0; i < rows.length; i++) {
            let row = [];
            const cols = rows[i].querySelectorAll('th, td');
            const maxCol = cols.length > 1 ? cols.length - 1 : cols.length;
            for (let j = 0; j < maxCol; j++) {
                let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace(/\s+/g, ' ').trim();
                text = text.replace(/"/g, '""');
                row.push('"' + text + '"');
            }
            if (row.length > 0) csv.push(row.join(','));
        }
        const csvString = csv.join('\n');
        const filename = 'Services_Register_Citizen_Charter_<?= $selected_year ?>_<?= sprintf("%02d", $selected_month) ?>.csv';
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
