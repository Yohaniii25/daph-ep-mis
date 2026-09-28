<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$allowed_roles = [
    'veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon',
    'sms', 'district_dd', 'deputy_director_district', 'provincial_director', 'admin', 'super_admin', 'administrator'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? '';
$user_district_id = $_SESSION['district_id'] ?? null;
$is_supervisory = in_array($user_role, ['district_dd', 'deputy_director_district', 'sms', 'provincial_director', 'admin', 'super_admin', 'administrator']);

// View routing: 'hub' (Primary Navigation View) vs 'maintenance' (Specific Drug Maintenance Details View)
$view = $_GET['view'] ?? 'hub';
$is_maintenance_view = ($view === 'maintenance');
$active_tab_param = $_GET['tab'] ?? 'all';
$selected_year = intval($_GET['year'] ?? date('Y'));

// Supervisory range switching or session range
$range_id = $_SESSION['range_id'] ?? null;
if ($is_supervisory && isset($_GET['range_id']) && !empty($_GET['range_id'])) {
    $range_id = intval($_GET['range_id']);
}

// Fetch ranges for supervisory selector
$supervisory_ranges = [];
if ($is_supervisory) {
    if (!empty($user_district_id)) {
        $r_stmt = $mysqli->prepare("SELECT id, name FROM veterinary_ranges WHERE district_id = ? ORDER BY name ASC");
        $r_stmt->bind_param("i", $user_district_id);
        $r_stmt->execute();
        $supervisory_ranges = $r_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $r_stmt->close();
    } else {
        $supervisory_ranges = $mysqli->query("SELECT id, name FROM veterinary_ranges ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
    }
    if (empty($range_id) && !empty($supervisory_ranges)) {
        $range_id = $supervisory_ranges[0]['id'];
    }
}

$range_name = 'All Ranges';
$district_name = 'All Districts';
$iframe_url = '';

// Query the user's data profile if missing from active session context
if (empty($range_id) && !empty($user_id) && !$is_supervisory) {
    $user_query = $mysqli->prepare("SELECT range_id FROM users WHERE id = ?");
    if ($user_query) {
        $user_query->bind_param("i", $user_id);
        $user_query->execute();
        $user_result = $user_query->get_result();
        if ($row = $user_result->fetch_assoc()) {
            $_SESSION['range_id'] = $row['range_id'];
            $range_id = $row['range_id'];
        }
        $user_query->close();
    }
}

// Extract Range Name and District Name
if (!empty($range_id)) {
    $details_sql = "
        SELECT 
            vr.name AS range_name,
            d.name AS district_name,
            vrm.iframe_url
        FROM veterinary_ranges vr
        LEFT JOIN districts d ON vr.district_id = d.id
        LEFT JOIN veterinary_range_maps vrm ON vr.id = vrm.range_id
        WHERE vr.id = ?
    ";

    $details_query = $mysqli->prepare($details_sql);
    if ($details_query) {
        $details_query->bind_param("i", $range_id);
        $details_query->execute();
        $details_result = $details_query->get_result();
        if ($data = $details_result->fetch_assoc()) {
            $range_name = $data['range_name'] ?? 'Your Assigned Range';
            $district_name = $data['district_name'] ?? 'Your District';
            $iframe_url = $data['iframe_url'] ?? '';
        }
        $details_query->close();
    }
}

// Compute overall summary metrics
$summary = [
    'total_batches' => 0,
    'total_starter' => 0,
    'total_received' => 0,
    'total_used' => 0,
    'total_damaged' => 0,
    'total_balance' => 0
];

// Active Tracked Batches
$batch_count = $mysqli->query("SELECT COUNT(*) AS total FROM vaccine_batches WHERE is_active = 1");
if ($batch_count) {
    $summary['total_batches'] = $batch_count->fetch_assoc()['total'] ?? 0;
}

// Stats sums from drug_records
$stats_query = "
    SELECT 
        SUM(starter_count_month) AS total_starter,
        SUM(during_month_received) AS total_received,
        SUM(used_doses_count) AS total_used,
        SUM(doses_damaged) AS total_damaged,
        SUM(starter_count_month + during_month_received - used_doses_count - doses_damaged) AS total_balance
    FROM drug_records
";
$stats_res = $mysqli->query($stats_query);
if ($stats_res && $row = $stats_res->fetch_assoc()) {
    $summary['total_starter'] = intval($row['total_starter'] ?? 0);
    $summary['total_received'] = intval($row['total_received'] ?? 0);
    $summary['total_used'] = intval($row['total_used'] ?? 0);
    $summary['total_damaged'] = intval($row['total_damaged'] ?? 0);
    $summary['total_balance'] = intval($row['total_balance'] ?? 0);
}

// Fetch all registered Drug Types with per-drug aggregate sums
$drug_types_sql = "
    SELECT 
        t.id, 
        COALESCE(t.brand_name, t.vaccine_name) AS brand_name,
        t.vaccine_name,
        COALESCE(t.chemical_composition, '—') AS chemical_composition,
        t.expiry_date,
        COUNT(r.id) AS total_records,
        COALESCE(SUM(r.starter_count_month), 0) AS starter_sum,
        COALESCE(SUM(r.during_month_received), 0) AS received_sum,
        COALESCE(SUM(r.used_doses_count), 0) AS used_sum,
        COALESCE(SUM(r.doses_damaged), 0) AS damaged_sum,
        COALESCE(SUM(r.starter_count_month + r.during_month_received - r.used_doses_count - r.doses_damaged), 0) AS balance_sum
    FROM drug_types t
    LEFT JOIN drug_records r ON t.id = r.drug_type_id
    GROUP BY t.id, t.brand_name, t.vaccine_name, t.chemical_composition, t.expiry_date
    ORDER BY brand_name ASC
";
$drug_types_res = $mysqli->query($drug_types_sql);
$all_drug_types = [];
if ($drug_types_res) {
    while ($dt = $drug_types_res->fetch_assoc()) {
        $all_drug_types[$dt['id']] = $dt;
    }
}

// Fetch all ledger records joined with types and batches
$ledger_query = "
    SELECT r.*, 
           COALESCE(t.brand_name, t.vaccine_name) AS brand_name, 
           COALESCE(t.chemical_composition, '—') AS chemical_composition, 
           COALESCE(t.vaccine_name, 'Unknown Type') AS vaccine_name, 
           COALESCE(b.batch_number, 'Unknown Batch') AS batch_number, 
           COALESCE(b.expiry_date, t.expiry_date, 'N/A') AS expiry_date,
           (r.starter_count_month + r.during_month_received - r.used_doses_count - r.doses_damaged) AS balance_end_month 
    FROM `drug_records` r
    LEFT JOIN `drug_types` t ON r.drug_type_id = t.id
    LEFT JOIN `vaccine_batches` b ON r.vaccine_batch_id = b.id
    ORDER BY r.log_date DESC, r.id DESC
";
$ledger_res = $mysqli->query($ledger_query);
$all_records = [];
$records_by_drug = [];
if ($ledger_res) {
    while ($rec = $ledger_res->fetch_assoc()) {
        $all_records[] = $rec;
        $dt_id = intval($rec['drug_type_id']);
        $records_by_drug[$dt_id][] = $rec;
    }
}

require_once __DIR__ . '/../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">

<style>
.metric-card-hover {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.metric-card-hover:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.08) !important;
}
.nav-card-action {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-radius: 12px;
}
.nav-card-action:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 24px rgba(0,0,0,0.1) !important;
}
.tabs-scroller {
    overflow-x: auto;
    white-space: nowrap;
    -webkit-overflow-scrolling: touch;
}
.nav-pills-letter-h .nav-link {
    white-space: nowrap;
}
</style>

        <!-- ============================================================ -->
        <!-- HEADER & SCOPE BAR -->
        <!-- ============================================================ -->
        <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">
                    <?= $is_maintenance_view ? 'Drug Maintenance - Formulation Ledgers' : 'Therapeutic Drug & Vaccine Operations' ?>
                </h2>
                <p class="text-muted small mb-0">
                    Manage stock balances, batches & ledgers for <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District)
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if ($is_supervisory && !empty($supervisory_ranges)): ?>
                    <form method="GET" class="d-flex align-items-center gap-2">
                        <?php if ($is_maintenance_view): ?>
                            <input type="hidden" name="view" value="maintenance">
                        <?php endif; ?>
                        <label class="small fw-semibold text-secondary text-nowrap"><i class="bi bi-geo-alt-fill text-danger me-1"></i>Select Range:</label>
                        <select name="range_id" class="form-select form-select-sm shadow-sm" onchange="this.form.submit()">
                            <?php foreach ($supervisory_ranges as $sr): ?>
                                <option value="<?= $sr['id'] ?>" <?= $range_id == $sr['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sr['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>

                <?php if ($is_maintenance_view): ?>
                    <a href="drug_maintenance.php<?= $range_id ? '?range_id=' . $range_id : '' ?>" class="btn btn-secondary shadow-sm text-nowrap">
                        <i class="bi bi-arrow-left me-1"></i>Back to Navigation Menu
                    </a>
                <?php else: ?>
                    <a href="monthly-annual-reports.php" class="btn btn-secondary shadow-sm text-nowrap">
                        <i class="bi bi-arrow-left me-2"></i>Back to Reports
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- KEY METRIC CARDS -->
        <!-- ============================================================ -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-primary border-4 metric-card-hover">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted small text-uppercase fw-bold mb-1">Active Tracked Batches</h6>
                                <h3 class="text-primary mb-0 fw-bold"><?= number_format($summary['total_batches']) ?></h3>
                            </div>
                            <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width: 44px; height: 44px;">
                                <i class="bi bi-box-seam fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-warning border-4 metric-card-hover">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted small text-uppercase fw-bold mb-1">Doses Allocated (Starter)</h6>
                                <h3 class="text-warning mb-0 fw-bold"><?= number_format($summary['total_starter']) ?></h3>
                            </div>
                            <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning-subtle text-warning" style="width: 44px; height: 44px;">
                                <i class="bi bi-box-arrow-in-right fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-success border-4 metric-card-hover">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted small text-uppercase fw-bold mb-1">Total Doses Used</h6>
                                <h3 class="text-success mb-0 fw-bold"><?= number_format($summary['total_used']) ?></h3>
                            </div>
                            <div class="rounded-circle d-flex align-items-center justify-content-center bg-success-subtle text-success" style="width: 44px; height: 44px;">
                                <i class="bi bi-shield-check fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-info border-4 metric-card-hover">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted small text-uppercase fw-bold mb-1">Total Live Balance</h6>
                                <h3 class="text-info mb-0 fw-bold"><?= number_format($summary['total_balance']) ?></h3>
                            </div>
                            <div class="rounded-circle d-flex align-items-center justify-content-center bg-info-subtle text-info" style="width: 44px; height: 44px;">
                                <i class="bi bi-capsule fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$is_maintenance_view): ?>
        <!-- =================================================================================== -->
        <!-- VIEW 1: PRIMARY VIEW (MAIN NAVIGATION OPTIONS & HUB)                                -->
        <!-- =================================================================================== -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-0">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-grid-3x3-gap-fill text-danger fs-5"></i>
                    <div>
                        <h5 class="mb-0 fw-bold text-dark">Main Navigation Options</h5>
                        <small class="text-muted">Select an operational workflow to record vaccines, manage stock batches, or access itemized drug ledgers.</small>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="row g-4">


                    <!-- Option 2: Drug Maintenance -->
                    <div class="col-lg-6 col-md-6">
                        <div class="card h-100 border-0 shadow-sm nav-card-action" style="border-top: 4px solid #2b3a4a !important; background: #ffffff;">
                            <div class="card-body p-4 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; background: rgba(43, 58, 74, 0.1); color: #2b3a4a;">
                                            <i class="bi bi-capsule-pill fs-3"></i>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold mb-0 text-dark">Drug Maintenance</h5>
                                            <span class="badge bg-secondary-subtle text-secondary small"><?= count($all_drug_types) ?> Registered Types</span>
                                        </div>
                                    </div>
                                    <p class="text-muted small mb-3">
                                        Access comprehensive drug stock inventory ledger, horizontal drug-wise tabs, live balance computations, and ledger history.
                                    </p>
                                </div>
                                <div>
                                    <a href="drug_maintenance.php?view=maintenance" class="btn w-100 text-light fw-bold shadow-sm py-2 mb-2" style="background-color: #2b3a4a;">
                                        <i class="bi bi-journal-medical me-1"></i>Open Drug Maintenance
                                    </a>
                                    <div class="text-center">
                                        <a href="drug_types.php" class="small text-muted text-decoration-none">
                                            <i class="bi bi-search me-1"></i>Manage Drug Formulations &rarr;
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Drug Stock Summary by Formulation -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="m-0 fw-bold text-dark"><i class="bi bi-shield-plus me-2 text-success"></i>Drug Formulation Stock Balances Overview</h5>
                    <small class="text-muted">Summary of stock quantities across all registered drug formulations.</small>
                </div>
                <a href="drug_maintenance.php?view=maintenance" class="btn btn-sm text-light fw-bold shadow-sm" style="background-color: #820100;">
                    <i class="bi bi-journal-text me-1"></i>View Itemized Drug Tabs &rarr;
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="summaryDrugTable" class="table table-bordered table-striped align-middle" style="width:100%">
                        <thead class="table-light text-center">
                            <tr>
                                <th>Drug Formulation</th>
                                <th>Chemical Composition</th>
                                <th>Total Log Entries</th>
                                <th>Opening Stock</th>
                                <th>Receipts</th>
                                <th>Used Doses</th>
                                <th>Wasted / Damaged</th>
                                <th class="bg-light-success fw-bold">Live End Balance</th>
                                <th style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_drug_types as $dt_id => $drug): ?>
                                <tr>
                                    <td class="fw-bold text-dark">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                            <i class="bi bi-capsule me-1"></i><?= htmlspecialchars($drug['brand_name']) ?>
                                        </span>
                                    </td>
                                    <td class="text-secondary small">
                                        <?= htmlspecialchars($drug['chemical_composition']) ?>
                                    </td>
                                    <td class="text-center font-monospace">
                                        <span class="badge bg-light text-dark border"><?= intval($drug['total_records']) ?> entries</span>
                                    </td>
                                    <td class="text-center font-monospace"><?= number_format($drug['starter_sum']) ?></td>
                                    <td class="text-center font-monospace text-success">+<?= number_format($drug['received_sum']) ?></td>
                                    <td class="text-center font-monospace text-info">-<?= number_format($drug['used_sum']) ?></td>
                                    <td class="text-center font-monospace text-danger">-<?= number_format($drug['damaged_sum']) ?></td>
                                    <td class="text-center font-monospace fw-bold bg-light text-success"><?= number_format($drug['balance_sum']) ?></td>
                                    <td class="text-center">
                                        <a href="drug_maintenance.php?view=maintenance&tab=<?= $dt_id ?>" class="btn btn-sm btn-outline-primary" title="View records for <?= htmlspecialchars($drug['brand_name']) ?>">
                                            <i class="bi bi-eye me-1"></i>View Tab
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- =================================================================================== -->
        <!-- VIEW 2: SPECIFIC DRUG MAINTENANCE DETAILS VIEW (WITH HORIZONTAL DRUG TABS)         -->
        <!-- =================================================================================== -->
        
        <!-- Navigation Ribbon -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="drug_maintenance.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-grid me-1"></i>Main Menu
                    </a>
                    <a href="batches.php" class="btn btn-sm btn-outline-dark">
                        <i class="bi bi-box-seam me-1"></i>Batch Management
                    </a>
                    <a href="drug_maintenance.php?view=maintenance" class="btn btn-sm active fw-bold text-light" style="background-color: #370709;">
                        <i class="bi bi-capsule-pill me-1"></i>Drug Maintenance (Active)
                    </a>
                </div>
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1 text-primary"></i>Batches are managed via the dedicated Batch Management page.
                </div>
            </div>
        </div>

        <!-- Action Bar: Add New Record (Retained) + Name of the Drugs (Batches button REMOVED) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn text-light fw-bold px-3 py-2 shadow-sm" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addDrugRecordModal">
                            <i class="bi bi-plus-circle me-1"></i>Add New Record
                        </button>
                        <a href="drug_types.php" class="btn btn-outline-secondary px-3 py-2 shadow-sm">
                            <i class="bi bi-search me-1"></i>Name of the Drugs
                        </a>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-secondary border px-3 py-2">
                            <i class="bi bi-geo-alt-fill text-danger me-1"></i>Range: <?= htmlspecialchars($range_name) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Horizontal Tabbed Interface for the Data Grid -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white p-3 border-bottom tabs-scroller">
                <ul class="nav nav-pills nav-pills-letter-h flex-nowrap overflow-auto" id="drugMaintenanceTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= ($active_tab_param === 'all' || empty($active_tab_param)) ? 'active' : '' ?>" id="tab-all-drugs-btn" data-bs-toggle="pill" data-bs-target="#tab-all-drugs" type="button" role="tab">
                            <i class="bi bi-layers-fill me-1"></i>All Drugs
                            <span class="badge-tab-count"><?= count($all_records) ?></span>
                        </button>
                    </li>
                    <?php foreach ($all_drug_types as $dt_id => $drug): ?>
                        <?php 
                            $tab_key = 'drug_' . $dt_id;
                            $is_tab_active = ($active_tab_param == $dt_id);
                            $d_records_cnt = count($records_by_drug[$dt_id] ?? []);
                        ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $is_tab_active ? 'active' : '' ?>" id="tab-<?= $tab_key ?>-btn" data-bs-toggle="pill" data-bs-target="#tab-<?= $tab_key ?>" type="button" role="tab">
                                <i class="bi bi-capsule me-1"></i><?= htmlspecialchars($drug['brand_name']) ?>
                                <span class="badge-tab-count"><?= $d_records_cnt ?></span>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content" id="drugMaintenanceTabContent">
                    
                    <!-- TAB 1: ALL DRUGS CONSOLIDATED -->
                    <div class="tab-pane fade <?= ($active_tab_param === 'all' || empty($active_tab_param)) ? 'show active' : '' ?>" id="tab-all-drugs" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-journal-medical me-1 text-primary"></i>All Drug Formulations Stock Ledger</h6>
                                <small class="text-muted">Consolidated historical log entries for all drugs in inventory.</small>
                            </div>
                            <span class="badge bg-secondary-subtle text-secondary"><?= count($all_records) ?> Total Ledger Rows</span>
                        </div>

                        <div class="table-responsive">
                            <table id="drugTableAll" class="table table-bordered table-striped align-middle row-border drug-datatable" style="width:100%">
                                <thead class="table-light text-center align-middle">
                                    <tr>
                                        <th>Log Date</th>
                                        <th>Drug Formulation</th>
                                        <th>Chemical Composition</th>
                                        <th>Batch No</th>
                                        <th>Date of Expiry</th>
                                        <th>Opening Balance</th>
                                        <th>Mid-Month Receipts</th>
                                        <th>Quantity Used</th>
                                        <th>Wasted / Damaged</th>
                                        <th class="bg-light-success fw-bold">End Balance</th>
                                        <th style="width: 100px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($all_records)): ?>
                                        <?php foreach ($all_records as $row): 
                                            $formatted_expiry = (!empty($row['expiry_date']) && $row['expiry_date'] !== 'N/A') ? date('Y-m-d', strtotime($row['expiry_date'])) : 'N/A';
                                            $brand = !empty($row['brand_name']) ? $row['brand_name'] : $row['vaccine_name'];
                                            $chem = !empty($row['chemical_composition']) ? $row['chemical_composition'] : '—';
                                        ?>
                                            <tr>
                                                <td class="text-center font-monospace small"><?= htmlspecialchars($row['log_date']) ?></td>
                                                <td class="fw-bold text-dark"><span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><?= htmlspecialchars($brand) ?></span></td>
                                                <td class="fw-semibold text-secondary small"><i class="bi bi-prescription2 me-1"></i><?= htmlspecialchars($chem) ?></td>
                                                <td class="text-center"><span class="badge bg-dark font-monospace"><?= htmlspecialchars($row['batch_number']) ?></span></td>
                                                <td class="text-center small fw-semibold text-danger"><?= $formatted_expiry ?></td>
                                                <td class="text-center font-monospace"><?= number_format($row['starter_count_month']) ?></td>
                                                <td class="text-center font-monospace text-success">+<?= number_format($row['during_month_received']) ?></td>
                                                <td class="text-center font-monospace text-info">-<?= number_format($row['used_doses_count']) ?></td>
                                                <td class="text-center font-monospace text-danger">-<?= number_format($row['doses_damaged']) ?></td>
                                                <td class="text-center font-monospace fw-bold bg-light text-success"><?= number_format($row['balance_end_month']) ?></td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-primary edit-drug-record-btn"
                                                            data-id="<?= $row['id'] ?>"
                                                            data-date="<?= $row['log_date'] ?>"
                                                            data-drug="<?= htmlspecialchars($row['vaccine_name'], ENT_QUOTES) ?>"
                                                            data-drug-type-id="<?= $row['drug_type_id'] ?>"
                                                            data-batch="<?= $row['vaccine_batch_id'] ?>"
                                                            data-expiry="<?= $formatted_expiry ?>"
                                                            data-starter="<?= $row['starter_count_month'] ?>"
                                                            data-received="<?= $row['during_month_received'] ?>"
                                                            data-used="<?= $row['used_doses_count'] ?>"
                                                            data-damaged="<?= $row['doses_damaged'] ?>">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <a href="processors/drug_record_crud.php?action=delete&id=<?= $row['id'] ?>&return_url=<?= urlencode('../drug_maintenance.php?view=maintenance&tab=all') ?>" 
                                                           class="btn btn-outline-danger btn-delete-drug">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TABS FOR EACH SPECIFIC DRUG -->
                    <?php foreach ($all_drug_types as $dt_id => $drug): ?>
                        <?php 
                            $tab_key = 'drug_' . $dt_id;
                            $is_tab_active = ($active_tab_param == $dt_id);
                            $drug_recs = $records_by_drug[$dt_id] ?? [];
                        ?>
                        <div class="tab-pane fade <?= $is_tab_active ? 'show active' : '' ?>" id="tab-<?= $tab_key ?>" role="tabpanel">
                            
                            <!-- Drug Header KPI strip -->
                            <div class="p-3 bg-light rounded-3 border mb-3">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div>
                                        <div class="d-flex align-items-center gap-2">
                                            <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($drug['brand_name']) ?></h5>
                                            <?php if (!empty($drug['chemical_composition']) && $drug['chemical_composition'] !== '—'): ?>
                                                <span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($drug['chemical_composition']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted">Formulation identifier: #<?= $dt_id ?> | Total Recorded Ledger Entries: <?= count($drug_recs) ?></small>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="text-end">
                                            <span class="text-muted small d-block">Current Stock Balance</span>
                                            <span class="h5 fw-bold text-success mb-0"><?= number_format($drug['balance_sum']) ?> Units</span>
                                        </div>
                                        <button class="btn btn-sm text-light fw-bold add-drug-for-type-btn" style="background-color: #820100;" data-drug-type-id="<?= $dt_id ?>">
                                            <i class="bi bi-plus-circle me-1"></i>Add Record
                                        </button>
                                    </div>
                                </div>
                                <div class="row g-2 mt-2 pt-2 border-top text-center small">
                                    <div class="col-sm-3">
                                        <span class="text-muted">Opening Stock:</span> <strong><?= number_format($drug['starter_sum']) ?></strong>
                                    </div>
                                    <div class="col-sm-3">
                                        <span class="text-muted">Total Receipts:</span> <strong class="text-success">+<?= number_format($drug['received_sum']) ?></strong>
                                    </div>
                                    <div class="col-sm-3">
                                        <span class="text-muted">Total Used:</span> <strong class="text-info">-<?= number_format($drug['used_sum']) ?></strong>
                                    </div>
                                    <div class="col-sm-3">
                                        <span class="text-muted">Damaged / Wasted:</span> <strong class="text-danger">-<?= number_format($drug['damaged_sum']) ?></strong>
                                    </div>
                                </div>
                            </div>

                            <?php if (empty($drug_recs)): ?>
                                <div class="text-center py-5 bg-white rounded border">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px; background: rgba(130, 1, 0, 0.08); color: #820100;">
                                        <i class="bi bi-capsule fs-2"></i>
                                    </div>
                                    <h6 class="fw-bold text-secondary mb-1">No Maintenance Records Found for <?= htmlspecialchars($drug['brand_name']) ?></h6>
                                    <p class="text-muted small mb-3">No stock ledger entries have been logged for this specific drug yet.</p>
                                    <button class="btn btn-sm text-light fw-bold add-drug-for-type-btn" style="background-color: #820100;" data-drug-type-id="<?= $dt_id ?>">
                                        <i class="bi bi-plus-circle me-1"></i>Add First Record for <?= htmlspecialchars($drug['brand_name']) ?>
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table id="drugTable_<?= $dt_id ?>" class="table table-bordered table-striped align-middle row-border drug-datatable" style="width:100%">
                                        <thead class="table-light text-center align-middle">
                                            <tr>
                                                <th>Log Date</th>
                                                <th>Drug Formulation</th>
                                                <th>Chemical Composition</th>
                                                <th>Batch No</th>
                                                <th>Date of Expiry</th>
                                                <th>Opening Balance</th>
                                                <th>Mid-Month Receipts</th>
                                                <th>Quantity Used</th>
                                                <th>Wasted / Damaged</th>
                                                <th class="bg-light-success fw-bold">End Balance</th>
                                                <th style="width: 100px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($drug_recs as $row): 
                                                $formatted_expiry = (!empty($row['expiry_date']) && $row['expiry_date'] !== 'N/A') ? date('Y-m-d', strtotime($row['expiry_date'])) : 'N/A';
                                                $brand = !empty($row['brand_name']) ? $row['brand_name'] : $row['vaccine_name'];
                                                $chem = !empty($row['chemical_composition']) ? $row['chemical_composition'] : '—';
                                            ?>
                                                <tr>
                                                    <td class="text-center font-monospace small"><?= htmlspecialchars($row['log_date']) ?></td>
                                                    <td class="fw-bold text-dark"><span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><?= htmlspecialchars($brand) ?></span></td>
                                                    <td class="fw-semibold text-secondary small"><i class="bi bi-prescription2 me-1"></i><?= htmlspecialchars($chem) ?></td>
                                                    <td class="text-center"><span class="badge bg-dark font-monospace"><?= htmlspecialchars($row['batch_number']) ?></span></td>
                                                    <td class="text-center small fw-semibold text-danger"><?= $formatted_expiry ?></td>
                                                    <td class="text-center font-monospace"><?= number_format($row['starter_count_month']) ?></td>
                                                    <td class="text-center font-monospace text-success">+<?= number_format($row['during_month_received']) ?></td>
                                                    <td class="text-center font-monospace text-info">-<?= number_format($row['used_doses_count']) ?></td>
                                                    <td class="text-center font-monospace text-danger">-<?= number_format($row['doses_damaged']) ?></td>
                                                    <td class="text-center font-monospace fw-bold bg-light text-success"><?= number_format($row['balance_end_month']) ?></td>
                                                    <td class="text-center">
                                                        <div class="btn-group btn-group-sm">
                                                            <button type="button" class="btn btn-outline-primary edit-drug-record-btn"
                                                                data-id="<?= $row['id'] ?>"
                                                                data-date="<?= $row['log_date'] ?>"
                                                                data-drug="<?= htmlspecialchars($row['vaccine_name'], ENT_QUOTES) ?>"
                                                                data-drug-type-id="<?= $row['drug_type_id'] ?>"
                                                                data-batch="<?= $row['vaccine_batch_id'] ?>"
                                                                data-expiry="<?= $formatted_expiry ?>"
                                                                data-starter="<?= $row['starter_count_month'] ?>"
                                                                data-received="<?= $row['during_month_received'] ?>"
                                                                data-used="<?= $row['used_doses_count'] ?>"
                                                                data-damaged="<?= $row['doses_damaged'] ?>">
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                            <a href="processors/drug_record_crud.php?action=delete&id=<?= $row['id'] ?>&return_url=<?= urlencode('../drug_maintenance.php?view=maintenance&tab=' . $dt_id) ?>" 
                                                               class="btn btn-outline-danger btn-delete-drug">
                                                                <i class="bi bi-trash"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>

                        </div>
                    <?php endforeach; ?>

                </div>
            </div>
        </div>

        <?php endif; ?>

<?php 
// Modals for Drug Records
include __DIR__ . '/model/drug_record_modal.php'; 
?>

<?php
$pageScripts = '
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // Initialize DataTables for all table views
        $(".drug-datatable").each(function() {
            if (!$.fn.DataTable.isDataTable(this)) {
                $(this).DataTable({
                    "order": [[0, "desc"]],
                    "dom": \'<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>\',
                    "language": {
                        "search": "_INPUT_",
                        "searchPlaceholder": "Search ledger rows..."
                    },
                    "buttons": [
                        {
                            extend: "csv",
                            text: "<i class=\\"bi bi-file-earmark-spreadsheet\\"></i> CSV",
                            className: "btn btn-sm btn-success me-2 shadow-sm"
                        },
                        {
                            extend: "pdf",
                            text: "<i class=\\"bi bi-file-pdf\\"></i> PDF",
                            className: "btn btn-sm btn-danger me-2 shadow-sm",
                            title: "Drug Stock Inventory Ledger Balances"
                        },
                        {
                            extend: "print",
                            text: "<i class=\\"bi bi-printer\\"></i> Print",
                            className: "btn btn-sm btn-dark shadow-sm"
                        }
                    ]
                });
            }
        });

        // Initialize Summary Table on Hub View
        if ($("#summaryDrugTable").length && !$.fn.DataTable.isDataTable("#summaryDrugTable")) {
            $("#summaryDrugTable").DataTable({
                "order": [[0, "asc"]],
                "dom": \'<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>\',
                "language": {
                    "search": "_INPUT_",
                    "searchPlaceholder": "Search drug formulations..."
                },
                "buttons": [
                    {
                        extend: "csv",
                        text: "<i class=\\"bi bi-file-earmark-spreadsheet\\"></i> CSV",
                        className: "btn btn-sm btn-success me-2 shadow-sm"
                    },
                    {
                        extend: "print",
                        text: "<i class=\\"bi bi-printer\\"></i> Print",
                        className: "btn btn-sm btn-dark shadow-sm"
                    }
                ]
            });
        }

        // Adjust DataTables columns when horizontal tabs switch
        $(\'button[data-bs-toggle="pill"]\').on(\'shown.bs.tab\', function (e) {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        });

        // SweetAlert2 status messages
        var urlParams = new URLSearchParams(window.location.search);
        var status = urlParams.get(\'status\');
        var msg = urlParams.get(\'msg\') || \'\';

        if (status === \'success\') {
            Swal.fire({
                icon: \'success\',
                title: \'Success!\',
                text: msg ? msg : \'Operation completed successfully.\',
                confirmButtonColor: \'#820100\'
            });
            window.history.replaceState({}, document.title, window.location.pathname + (urlParams.get(\'view\') ? \'?view=\' + urlParams.get(\'view\') : \'\'));
        } else if (status === \'error\' || status === \'db_error\') {
            Swal.fire({
                icon: \'error\',
                title: \'Operation Failed\',
                text: msg ? msg : \'Could not process database action.\',
                confirmButtonColor: \'#820100\'
            });
            window.history.replaceState({}, document.title, window.location.pathname + (urlParams.get(\'view\') ? \'?view=\' + urlParams.get(\'view\') : \'\'));
        }

        // Edit Drug Record Pre-fill
        $(document).on(\'click\', \'.edit-drug-record-btn\', function() {
            $(\'#drugAction\').val(\'update\');
            $(\'#drugId\').val($(this).data(\'id\'));
            
            $(\'#logDate\').val($(this).data(\'date\'));
            var drugTypeId = $(this).data(\'drug-type-id\');
            $(\'#drugType\').val(drugTypeId);
            $(\'#vaccineBatchId\').val($(this).data(\'batch\'));
            $(\'#qtyStarter\').val($(this).data(\'starter\'));
            $(\'#qtyReceived\').val($(this).data(\'received\'));
            $(\'#qtyUsed\').val($(this).data(\'used\'));
            $(\'#qtyDamaged\').val($(this).data(\'damaged\'));
            
            $(\'#drugExpiryDisplay\').text($(this).data(\'expiry\'));
            
            // Set return_url to maintain active tab
            var activeTabId = $(\'#drugMaintenanceTabs .nav-link.active\').attr(\'id\') || \'\';
            var activeDrugId = activeTabId.replace(\'tab-drug_\', \'\').replace(\'-btn\', \'\');
            $(\'#drugReturnUrl\').val(\'../drug_maintenance.php?view=maintenance&tab=\' + (activeDrugId || \'all\'));

            // Trigger calculation
            $(\'.calc-trigger\').first().trigger(\'input\');
            
            $(\'#drugRecordModalTitle\').html(\'<i class="bi bi-pencil-square me-2 text-warning"></i>Modify Drug Stock Entry\');
            $(\'#immSubmitBtn\').text(\'Save Changes\');
            $(\'#addDrugRecordModal\').modal(\'show\');
        });

        // Quick Add Record for specific drug button
        $(document).on(\'click\', \'.add-drug-for-type-btn\', function() {
            var drugTypeId = $(this).data(\'drug-type-id\');
            $(\'#drugRecordForm\')[0].reset();
            $(\'#drugAction\').val(\'create\');
            $(\'#drugId\').val(\'\');
            $(\'#drugType\').val(drugTypeId).trigger(\'change\');
            $(\'#drugReturnUrl\').val(\'../drug_maintenance.php?view=maintenance&tab=\' + drugTypeId);
            $(\'#drugRecordModalTitle\').html(\'<i class="bi bi-capsule-compartment me-2"></i>Drug Stock Ledger Entry\');
            $(\'#immSubmitBtn\').prop(\'disabled\', false).text(\'Commit Ledger Entry\');
            $(\'#addDrugRecordModal\').modal(\'show\');
        });

        // Reset Drug Record Modal upon close
        $(\'#addDrugRecordModal\').on(\'hidden.bs.modal\', function() {
            $(\'#drugRecordForm\')[0].reset();
            $(\'#drugAction\').val(\'create\');
            $(\'#drugId\').val(\'\');
            $(\'#drugExpiryDisplay\').text(\'None selected\');
            $(\'#drugLiveBalanceDisplay\').text(\'0 Units\').removeClass(\'text-danger text-success\').addClass(\'text-dark\');
            $(\'#drugRecordModalTitle\').html(\'<i class="bi bi-capsule-compartment me-2"></i>Drug Stock Ledger Entry\');
            $(\'#immSubmitBtn\').prop(\'disabled\', false).text(\'Commit Ledger Entry\');
        });

        // Delete Drug Record Confirmation
        $(document).on(\'click\', \'.btn-delete-drug\', function(e) {
            e.preventDefault();
            var deleteUrl = $(this).attr(\'href\');
            Swal.fire({
                icon: \'warning\',
                title: \'Delete Stock Entry?\',
                html: \'Are you sure you want to permanently delete this stock record?<br>This action cannot be undone.\',
                showCancelButton: true,
                confirmButtonColor: \'#d33\',
                cancelButtonColor: \'#6c757d\',
                confirmButtonText: \'Yes, Delete\',
                cancelButtonText: \'Cancel\'
            }).then(function(result) {
                if (result.isConfirmed) {
                    window.location.href = deleteUrl;
                }
            });
        });

        // Expiry date viewer sync logic
        function syncExpiryDisplay() {
            const batchExpiry = $(\'#vaccineBatchId\').find(\':selected\').data(\'expiry\');
            const drugExpiry = $(\'#drugType\').find(\':selected\').data(\'expiry\');
            const selectedExpiry = batchExpiry || drugExpiry || \'None selected\';
            $(\'#drugExpiryDisplay\').text(selectedExpiry);
        }
        $(document).on(\'change\', \'#drugType, #vaccineBatchId\', syncExpiryDisplay);

        // Dynamic Balance Calculation Engine inside Modal
        $(document).on(\'input\', \'.calc-trigger\', function() {
            const starter  = parseInt($(\'#qtyStarter\').val()) || 0;
            const received = parseInt($(\'#qtyReceived\').val()) || 0;
            const used     = parseInt($(\'#qtyUsed\').val()) || 0;
            const damaged  = parseInt($(\'#qtyDamaged\').val()) || 0;

            const balance = (starter + received) - (used + damaged);
            const display = $(\'#drugLiveBalanceDisplay\');
            display.text(balance.toLocaleString() + \' Units\');

            if (balance < 0) {
                display.removeClass(\'text-dark text-success\').addClass(\'text-danger fw-bold\');
                $(\'#immSubmitBtn\').prop(\'disabled\', true).text(\'Error: Inventory Deficit\');
            } else {
                display.removeClass(\'text-danger\').addClass(\'text-success fw-bold\');
                $(\'#immSubmitBtn\').prop(\'disabled\', false).text($(\'#drugAction\').val() === \'update\' ? \'Save Changes\' : \'Commit Ledger Entry\');
            }
        });
    });
</script>
';
require_once __DIR__ . '/../../../includes/footer.php';
?>