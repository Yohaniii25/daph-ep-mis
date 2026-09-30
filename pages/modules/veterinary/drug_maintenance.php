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

// View routing: 'hub' (Main Navigation Level), 'batches' (Batch Maintain View), 'maintenance' (Drug Maintain View)
$view = $_GET['view'] ?? 'hub';
$active_tab_param = $_GET['tab'] ?? 'records';
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
        t.target_animal,
        t.description,
        t.expiry_date,
        COUNT(r.id) AS total_records,
        COALESCE(SUM(r.starter_count_month), 0) AS starter_sum,
        COALESCE(SUM(r.during_month_received), 0) AS received_sum,
        COALESCE(SUM(r.used_doses_count), 0) AS used_sum,
        COALESCE(SUM(r.doses_damaged), 0) AS damaged_sum,
        COALESCE(SUM(r.starter_count_month + r.during_month_received - r.used_doses_count - r.doses_damaged), 0) AS balance_sum
    FROM drug_types t
    LEFT JOIN drug_records r ON t.id = r.drug_type_id
    GROUP BY t.id, t.brand_name, t.vaccine_name, t.chemical_composition, t.target_animal, t.description, t.expiry_date
    ORDER BY brand_name ASC
";
$drug_types_res = $mysqli->query($drug_types_sql);
$all_drug_types = [];
if ($drug_types_res) {
    while ($dt = $drug_types_res->fetch_assoc()) {
        $all_drug_types[$dt['id']] = $dt;
    }
}

// Fetch master list of drug names specifically for Tab 2
$master_drugs_sql = "SELECT * FROM `drug_types` ORDER BY COALESCE(brand_name, vaccine_name) ASC";
$master_drugs_res = $mysqli->query($master_drugs_sql);
$master_drugs = [];
if ($master_drugs_res) {
    while ($md = $master_drugs_res->fetch_assoc()) {
        $master_drugs[] = $md;
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
if ($ledger_res) {
    while ($rec = $ledger_res->fetch_assoc()) {
        $all_records[] = $rec;
    }
}

// Fetch all vaccine batches for the Batch Maintain view
$batches_sql = "SELECT id, batch_number, is_active, remarks, expiry_date, created_at FROM `vaccine_batches` ORDER BY id DESC";
$batches_res = $mysqli->query($batches_sql);
$all_batches = [];
$active_batches_count = 0;
if ($batches_res) {
    while ($b_row = $batches_res->fetch_assoc()) {
        $all_batches[] = $b_row;
        if ($b_row['is_active'] == 1) {
            $active_batches_count++;
        }
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
.nav-option-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    border-radius: 12px;
}
.nav-option-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.12) !important;
}
.custom-nav-pills .nav-link {
    font-weight: 600;
    color: #495057;
    border-radius: 8px;
    padding: 10px 20px;
    transition: all 0.2s ease;
}
.custom-nav-pills .nav-link.active {
    background-color: #370709;
    color: #ffffff;
    box-shadow: 0 4px 10px rgba(55, 7, 9, 0.25);
}
.custom-nav-pills .nav-link:not(.active):hover {
    background-color: #f1f3f5;
    color: #212529;
}
.badge-tab-count {
    font-size: 0.78rem;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 20px;
    margin-left: 6px;
    background: rgba(255,255,255,0.25);
}
.custom-nav-pills .nav-link:not(.active) .badge-tab-count {
    background: #e9ecef;
    color: #495057;
}
</style>

        <!-- ============================================================ -->
        <!-- HEADER & SCOPE BAR -->
        <!-- ============================================================ -->
        <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">
                    <?php if ($view === 'batches'): ?>
                        Batch Maintain - Master Batches Register
                    <?php elseif ($view === 'maintenance'): ?>
                        Drug Maintain - Stock Ledgers & Drug Master Register
                    <?php else: ?>
                        Therapeutic Drug & Vaccine Operations
                    <?php endif; ?>
                </h2>
                <p class="text-muted small mb-0">
                    Manage stock balances, batches & ledgers for <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District)
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if ($is_supervisory && !empty($supervisory_ranges)): ?>
                    <form method="GET" class="d-flex align-items-center gap-2">
                        <?php if ($view !== 'hub'): ?>
                            <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
                        <?php endif; ?>
                        <?php if (!empty($active_tab_param) && $view === 'maintenance'): ?>
                            <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab_param) ?>">
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

                <?php if ($view !== 'hub'): ?>
                    <a href="drug_maintenance.php<?= $range_id ? '?range_id=' . $range_id : '' ?>" class="btn btn-secondary shadow-sm text-nowrap">
                        <i class="bi bi-arrow-left me-1"></i>Back to Main Menu
                    </a>
                <?php else: ?>
                    <a href="monthly-annual-reports.php" class="btn btn-secondary shadow-sm text-nowrap">
                        <i class="bi bi-arrow-left me-2"></i>Back to Reports
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- KEY METRIC CARDS (ALL VIEWS)                                 -->
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

        <?php if ($view === 'hub'): ?>
        <!-- =================================================================================== -->
        <!-- 1. MAIN NAVIGATION LEVEL (TOP LEVEL OF DRUG MAINTENANCE INTERFACE)                  -->
        <!-- Displays 3 distinct navigation options/buttons:                                     -->
        <!--   1. Add Vaccine Record                                                            -->
        <!--   2. Batch Maintain                                                                -->
        <!--   3. Drug Maintain                                                                 -->
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
            <div class="card-body pt-0 pb-4">
                <div class="row g-4">

                    <!-- Option 1: Add Vaccine Record -->
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100 border-0 shadow-sm nav-option-card" style="border-top: 4px solid #820100 !important; background: #ffffff;">
                            <div class="card-body p-4 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; background: rgba(130, 1, 0, 0.1); color: #820100;">
                                            <i class="bi bi-file-earmark-plus fs-3"></i>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold mb-0 text-dark">Add Vaccine Record</h5>
                                            <span class="badge bg-danger-subtle text-danger small">Immunization Stock</span>
                                        </div>
                                    </div>
                                    <p class="text-muted small mb-3">
                                        Log monthly vaccine returns, opening balance doses, receipt consignments, field utilization, and wastage counts.
                                    </p>
                                </div>
                                <div>
                                    <button type="button" class="btn w-100 text-light fw-bold shadow-sm py-2 mb-2" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addVaccineBalanceModal">
                                        <i class="bi bi-plus-circle me-1"></i>Add Vaccine Record
                                    </button>
                                    <div class="text-center">
                                        <a href="vaccine_balance.php" class="small text-muted text-decoration-none">
                                            <i class="bi bi-table me-1"></i>View Vaccine Balances Register &rarr;
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Option 2: Batch Maintain -->
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100 border-0 shadow-sm nav-option-card" style="border-top: 4px solid #b08723 !important; background: #ffffff;">
                            <div class="card-body p-4 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; background: rgba(176, 135, 35, 0.12); color: #b08723;">
                                            <i class="bi bi-box-seam fs-3"></i>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold mb-0 text-dark">Batch Maintain</h5>
                                            <span class="badge bg-warning-subtle text-warning small"><?= count($all_batches) ?> Registered Batches</span>
                                        </div>
                                    </div>
                                    <p class="text-muted small mb-3">
                                        Access centralized master list of batches, register new batch identity codes, manage expiration dates and active stock availability.
                                    </p>
                                </div>
                                <div>
                                    <a href="drug_maintenance.php?view=batches" class="btn w-100 text-light fw-bold shadow-sm py-2 mb-2" style="background-color: #b08723;">
                                        <i class="bi bi-box-seam me-1"></i>Batch Maintain
                                    </a>
                                    <div class="text-center">
                                        <button type="button" class="btn btn-link p-0 small text-muted text-decoration-none" data-bs-toggle="modal" data-bs-target="#addVaccineBatchModal">
                                            <i class="bi bi-plus-circle me-1"></i>Quick Add Batch &rarr;
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Option 3: Drug Maintain -->
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100 border-0 shadow-sm nav-option-card" style="border-top: 4px solid #2b3a4a !important; background: #ffffff;">
                            <div class="card-body p-4 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; background: rgba(43, 58, 74, 0.1); color: #2b3a4a;">
                                            <i class="bi bi-capsule-pill fs-3"></i>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold mb-0 text-dark">Drug Maintain</h5>
                                            <span class="badge bg-secondary-subtle text-secondary small"><?= count($all_drug_types) ?> Registered Types</span>
                                        </div>
                                    </div>
                                    <p class="text-muted small mb-3">
                                        Access consolidated drug inventory records, itemized formulation ledgers, and maintain master list of drug names.
                                    </p>
                                </div>
                                <div>
                                    <a href="drug_maintenance.php?view=maintenance" class="btn w-100 text-light fw-bold shadow-sm py-2 mb-2" style="background-color: #2b3a4a;">
                                        <i class="bi bi-journal-medical me-1"></i>Drug Maintain
                                    </a>
                                    <div class="text-center">
                                        <a href="drug_maintenance.php?view=maintenance&tab=drug_names" class="small text-muted text-decoration-none">
                                            <i class="bi bi-capsule me-1"></i>Name of the Drugs Master &rarr;
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Drug Stock Summary by Formulation Overview -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="m-0 fw-bold text-dark"><i class="bi bi-shield-plus me-2 text-success"></i>Drug Formulation Stock Balances Overview</h5>
                    <small class="text-muted">Summary of stock quantities across all registered drug formulations.</small>
                </div>
                <a href="drug_maintenance.php?view=maintenance" class="btn btn-sm text-light fw-bold shadow-sm" style="background-color: #820100;">
                    <i class="bi bi-journal-text me-1"></i>Open Drug Maintain &rarr;
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
                                        <a href="drug_maintenance.php?view=maintenance&tab=records" class="btn btn-sm btn-outline-primary" title="View records for <?= htmlspecialchars($drug['brand_name']) ?>">
                                            <i class="bi bi-eye me-1"></i>View Records
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php elseif ($view === 'batches'): ?>
        <!-- =================================================================================== -->
        <!-- 2. BATCH MAINTAIN VIEW (LIST OF BATCH + ADD BATCH MODAL/BUTTON)                     -->
        <!-- =================================================================================== -->
        
        <!-- Navigation Ribbon -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="drug_maintenance.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-grid me-1"></i>Main Menu
                    </a>
                    <a href="drug_maintenance.php?view=batches" class="btn btn-sm active fw-bold text-light" style="background-color: #b08723;">
                        <i class="bi bi-box-seam me-1"></i>Batch Maintain (Active)
                    </a>
                    <a href="drug_maintenance.php?view=maintenance" class="btn btn-sm btn-outline-dark">
                        <i class="bi bi-capsule-pill me-1"></i>Drug Maintain
                    </a>
                </div>
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1 text-primary"></i>Track master batches for vaccines and pharmaceutical formulations.
                </div>
            </div>
        </div>

        <!-- Action Bar: Add Batch Button -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn text-light fw-bold px-3 py-2 shadow-sm" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addVaccineBatchModal">
                            <i class="bi bi-plus-circle me-1"></i>Add Batch
                        </button>
                        <a href="drug_maintenance.php" class="btn btn-outline-secondary px-3 py-2 shadow-sm">
                            <i class="bi bi-arrow-left me-1"></i>Back to Navigation Menu
                        </a>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-secondary border px-3 py-2">
                            <i class="bi bi-box-seam me-1 text-warning"></i>Total Batches: <?= count($all_batches) ?>
                        </span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                            <i class="bi bi-check-circle me-1"></i>Active: <?= $active_batches_count ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- List of Batch Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-5">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="m-0 fw-bold text-dark"><i class="bi bi-list-check me-2 text-warning"></i>List of Batch</h5>
                    <small class="text-muted">Master register of all vaccine & therapeutic drug stock batch codes, active status, and expiry schedules.</small>
                </div>
                <button type="button" class="btn btn-sm text-light fw-bold shadow-sm" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addVaccineBatchModal">
                    <i class="bi bi-plus-circle me-1"></i>Add Batch
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="batchTable" class="table table-striped table-bordered align-middle row-border small" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 8%;">ID</th>
                                <th style="width: 25%;">Batch Identity Code</th>
                                <th style="width: 15%;">Expiration Date</th>
                                <th style="width: 14%;">Status</th>
                                <th style="width: 20%;">Remarks / Log Notes</th>
                                <th style="width: 15%;">Date Registered</th>
                                <th style="width: 8%;" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($all_batches)): ?>
                                <?php foreach ($all_batches as $row): 
                                    $formatted_expiry = !empty($row['expiry_date']) ? date('Y-m-d', strtotime($row['expiry_date'])) : 'N/A';
                                ?>
                                    <tr>
                                        <td class="fw-bold text-secondary">#<?= $row['id'] ?></td>
                                        <td>
                                            <div class="fw-bold text-dark">
                                                <i class="bi bi-qr-code-scan me-2 text-muted"></i><?= htmlspecialchars($row['batch_number']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="small fw-semibold <?= $formatted_expiry !== 'N/A' ? 'text-danger font-monospace' : 'text-muted' ?>">
                                                <i class="bi bi-calendar-event me-1"></i><?= $formatted_expiry ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($row['is_active'] == 1): ?>
                                                <span class="badge bg-success-subtle text-success px-2.5 py-1.5 rounded-pill fw-semibold">
                                                    <i class="bi bi-check-circle-fill me-1"></i>Active Stock
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger px-2.5 py-1.5 rounded-pill fw-semibold">
                                                    <i class="bi bi-x-circle-fill me-1"></i>Archived
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted text-wrap d-block text-break">
                                                <?= htmlspecialchars($row['remarks'] ?: 'No operational remarks added.') ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="small text-dark fw-semibold">
                                                <i class="bi bi-calendar3 me-1.5 text-muted"></i><?= date('Y-m-d g:i A', strtotime($row['created_at'])) ?>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-secondary edit-batch-btn"
                                                    data-id="<?= $row['id'] ?>"
                                                    data-batch="<?= htmlspecialchars($row['batch_number'], ENT_QUOTES) ?>"
                                                    data-status="<?= $row['is_active'] ?>"
                                                    data-expiry="<?= !empty($row['expiry_date']) ? date('Y-m-d', strtotime($row['expiry_date'])) : '' ?>"
                                                    data-remarks="<?= htmlspecialchars($row['remarks'], ENT_QUOTES) ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="processors/vaccine_batch_crud.php?action=delete&id=<?= $row['id'] ?>&return_url=<?= urlencode('../drug_maintenance.php?view=batches') ?>"
                                                    class="btn btn-outline-danger btn-delete-batch"
                                                    data-batch="<?= htmlspecialchars($row['batch_number'], ENT_QUOTES) ?>">
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
        </div>

        <?php elseif ($view === 'maintenance'): ?>
        <!-- =================================================================================== -->
        <!-- 3. DRUG MAINTAIN VIEW (TWO-TAB STRUCTURE)                                           -->
        <!--   Tab 1: "All drug records" (Consolidated list/grid + "add record" modal/button)   -->
        <!--   Tab 2: "Name of the drugs" (List of drugs master list + "add" modal/button)      -->
        <!-- =================================================================================== -->
        
        <!-- Navigation Ribbon -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="drug_maintenance.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-grid me-1"></i>Main Menu
                    </a>
                    <a href="drug_maintenance.php?view=batches" class="btn btn-sm btn-outline-dark">
                        <i class="bi bi-box-seam me-1"></i>Batch Maintain
                    </a>
                    <a href="drug_maintenance.php?view=maintenance" class="btn btn-sm active fw-bold text-light" style="background-color: #2b3a4a;">
                        <i class="bi bi-capsule-pill me-1"></i>Drug Maintain (Active)
                    </a>
                </div>
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1 text-primary"></i>Use the two tabs below to manage drug records or the master catalog of drug names.
                </div>
            </div>
        </div>

        <!-- Two-Tab Header Navigation -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white p-3 border-bottom">
                <ul class="nav nav-pills custom-nav-pills" id="drugMaintainTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= ($active_tab_param !== 'drug_names') ? 'active' : '' ?>" id="tab-all-records-nav" data-bs-toggle="pill" data-bs-target="#tab-all-records" type="button" role="tab">
                            <i class="bi bi-journal-medical me-2"></i>All drug records
                            <span class="badge-tab-count"><?= count($all_records) ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= ($active_tab_param === 'drug_names') ? 'active' : '' ?>" id="tab-drug-names-nav" data-bs-toggle="pill" data-bs-target="#tab-drug-names" type="button" role="tab">
                            <i class="bi bi-capsule me-2"></i>Name of the drugs
                            <span class="badge-tab-count"><?= count($master_drugs) ?></span>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content" id="drugMaintainTabContent">
                    
                    <!-- ============================================================== -->
                    <!-- TAB 1: ALL DRUG RECORDS                                         -->
                    <!-- Consolidated list/grid of all drug records                     -->
                    <!-- Includes "add record" modal/button specific to adding records  -->
                    <!-- ============================================================== -->
                    <div class="tab-pane fade <?= ($active_tab_param !== 'drug_names') ? 'show active' : '' ?>" id="tab-all-records" role="tabpanel">
                        
                        <!-- Tab 1 Action Strip -->
                        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 p-3 bg-light rounded-3 border">
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">
                                    <i class="bi bi-journal-medical me-2 text-primary"></i>All Drug Records
                                </h5>
                                <small class="text-muted">Consolidated historical and live inventory log entries across all drug formulations.</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn text-light fw-bold px-3 py-2 shadow-sm" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addDrugRecordModal">
                                    <i class="bi bi-plus-circle me-1"></i>Add Drug Record
                                </button>
                            </div>
                        </div>

                        <!-- Consolidated Table Grid -->
                        <div class="table-responsive">
                            <table id="drugTableAll" class="table table-bordered table-striped align-middle row-border" style="width:100%">
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
                                                        <a href="processors/drug_record_crud.php?action=delete&id=<?= $row['id'] ?>&return_url=<?= urlencode('../drug_maintenance.php?view=maintenance&tab=records') ?>" 
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

                    <!-- ============================================================== -->
                    <!-- TAB 2: NAME OF THE DRUGS                                        -->
                    <!-- List of drugs (master list available in the system)            -->
                    <!-- Includes "add model" (modal/button) for adding new drug names  -->
                    <!-- ============================================================== -->
                    <div class="tab-pane fade <?= ($active_tab_param === 'drug_names') ? 'show active' : '' ?>" id="tab-drug-names" role="tabpanel">
                        
                        <!-- Tab 2 Action Strip -->
                        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 p-3 bg-light rounded-3 border">
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">
                                    <i class="bi bi-capsule me-2 text-success"></i>List of Drugs (Master Catalog)
                                </h5>
                                <small class="text-muted">Master catalogue of all registered drug names, formulations, chemical compositions, and target animal species.</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-success fw-bold px-3 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addDrugTypeModal">
                                    <i class="bi bi-plus-circle me-1"></i>Add New Drug Name
                                </button>
                            </div>
                        </div>

                        <!-- Specific List of Drugs Table -->
                        <div class="table-responsive">
                            <table id="masterDrugTypesTable" class="table table-bordered table-striped align-middle row-border" style="width:100%">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 6%;">ID</th>
                                        <th style="width: 18%;">Brand Name</th>
                                        <th style="width: 22%;">Chemical Composition</th>
                                        <th style="width: 20%;">Display Name</th>
                                        <th style="width: 20%;">Target Animals</th>
                                        <th style="width: 14%;" class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($master_drugs)): ?>
                                        <?php foreach ($master_drugs as $row): 
                                            $brand = !empty($row['brand_name']) ? $row['brand_name'] : $row['vaccine_name'];
                                            $chem = !empty($row['chemical_composition']) ? $row['chemical_composition'] : '—';
                                        ?>
                                            <tr>
                                                <td class="fw-bold text-secondary">#<?= $row['id'] ?></td>
                                                <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fw-bold"><?= htmlspecialchars($brand) ?></span></td>
                                                <td class="fw-semibold text-dark"><i class="bi bi-prescription2 text-secondary me-1"></i><?= htmlspecialchars($chem) ?></td>
                                                <td class="text-muted small"><?= htmlspecialchars($row['vaccine_name']) ?></td>
                                                <td>
                                                    <?php
                                                    $animals = array_filter(array_map('trim', explode(',', $row['target_animal'] ?? '')));
                                                    foreach ($animals as $animal): ?>
                                                        <span class="badge bg-secondary px-2 py-1 me-1 mb-1">
                                                            <i class="bi bi-tag me-1"></i><?= htmlspecialchars($animal) ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </td>
                                                <td class="text-end">
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-secondary edit-drug-type-btn"
                                                            data-id="<?= $row['id'] ?>"
                                                            data-brand="<?= htmlspecialchars($row['brand_name'] ?? '', ENT_QUOTES) ?>"
                                                            data-chem="<?= htmlspecialchars($row['chemical_composition'] ?? '', ENT_QUOTES) ?>"
                                                            data-name="<?= htmlspecialchars($row['vaccine_name'] ?? '', ENT_QUOTES) ?>"
                                                            data-expiry="<?= htmlspecialchars($row['expiry_date'] ?? '', ENT_QUOTES) ?>"
                                                            data-animal="<?= htmlspecialchars($row['target_animal'] ?? '', ENT_QUOTES) ?>"
                                                            data-desc="<?= htmlspecialchars($row['description'] ?? '', ENT_QUOTES) ?>">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <a href="processors/drug_type_crud.php?action=delete&id=<?= $row['id'] ?>&return_url=<?= urlencode('../drug_maintenance.php?view=maintenance&tab=drug_names') ?>"
                                                            class="btn btn-outline-danger btn-delete-drugtype"
                                                            data-name="<?= htmlspecialchars($row['vaccine_name'], ENT_QUOTES) ?>">
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

                </div>
            </div>
        </div>

        <?php endif; ?>

<?php 
// Modals for the entire module
include __DIR__ . '/model/add_vaccine_balance_modal.php'; 
include __DIR__ . '/model/vaccine_batch_modal.php'; 
include __DIR__ . '/model/drug_record_modal.php'; 
include __DIR__ . '/model/drug_type_modal.php'; 
?>

<?php
$pageScripts = '
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // Initialize DataTables for Drug Records table
        if ($("#drugTableAll").length && !$.fn.DataTable.isDataTable("#drugTableAll")) {
            $("#drugTableAll").DataTable({
                "order": [[0, "desc"]],
                "dom": \'<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>\',
                "language": {
                    "search": "_INPUT_",
                    "searchPlaceholder": "Search ledger records..."
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

        // Initialize DataTables for Master Drug Types table
        if ($("#masterDrugTypesTable").length && !$.fn.DataTable.isDataTable("#masterDrugTypesTable")) {
            $("#masterDrugTypesTable").DataTable({
                "order": [[1, "asc"]],
                "dom": \'<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>\',
                "language": {
                    "search": "_INPUT_",
                    "searchPlaceholder": "Search drug names / active compounds..."
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
                        title: "Master List of Drug Classifications"
                    },
                    {
                        extend: "print",
                        text: "<i class=\\"bi bi-printer\\"></i> Print",
                        className: "btn btn-sm btn-dark shadow-sm"
                    }
                ]
            });
        }

        // Initialize DataTables for Batches table
        if ($("#batchTable").length && !$.fn.DataTable.isDataTable("#batchTable")) {
            $("#batchTable").DataTable({
                "order": [[0, "desc"]],
                "dom": \'<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>\',
                "language": {
                    "search": "_INPUT_",
                    "searchPlaceholder": "Search batch codes..."
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
                        title: "Master List of Vaccine Batches"
                    },
                    {
                        extend: "print",
                        text: "<i class=\\"bi bi-printer\\"></i> Print",
                        className: "btn btn-sm btn-dark shadow-sm"
                    }
                ]
            });
        }

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

        // Adjust DataTables columns when tabs switch
        $(\'button[data-bs-toggle="pill"], button[data-bs-toggle="tab"]\').on(\'shown.bs.tab\', function (e) {
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

        // ==============================================================
        // BATCH MAINTAIN EVENT HANDLERS
        // ==============================================================
        $(document).on(\'click\', \'.edit-batch-btn\', function() {
            $(\'#modalAction\').val(\'update\');
            $(\'#batchId\').val($(this).data(\'id\'));
            $(\'#batchNumber\').val($(this).data(\'batch\'));
            $(\'#is_active\').val($(this).data(\'status\'));
            $(\'#batchExpiryDate\').val($(this).data(\'expiry\') || \'\');
            $(\'#remarks\').val($(this).data(\'remarks\'));
            $(\'#batchReturnUrl\').val(\'../drug_maintenance.php?view=batches\');

            $(\'#modalTitle\').html(\'<i class="bi bi-pencil-square me-2 text-warning"></i>Modify Batch Details\');
            $(\'#submitBtn\').removeClass(\'btn-success\').addClass(\'btn-warning\').text(\'Save Changes\');
            $(\'#addVaccineBatchModal\').modal(\'show\');
        });

        $(\'#addVaccineBatchModal\').on(\'hidden.bs.modal\', function() {
            $(\'#modalAction\').val(\'create\');
            $(\'#batchId\').val(\'\');
            $(\'#batchExpiryDate\').val(\'\');
            $(\'#batchReturnUrl\').val(\'../drug_maintenance.php?view=batches\');
            $(\'#batchForm\')[0].reset();

            $(\'#modalTitle\').html(\'<i class="bi bi-box-seam me-2"></i>Register New Vaccine Stock Batch\');
            $(\'#submitBtn\').removeClass(\'btn-warning\').addClass(\'btn-success\').text(\'Save Batch\');
        });

        $(document).on(\'click\', \'.btn-delete-batch\', function(e) {
            e.preventDefault();
            var deleteUrl = $(this).attr(\'href\');
            var batchNum = $(this).data(\'batch\') || \'this batch\';

            Swal.fire({
                icon: \'warning\',
                title: \'Delete Vaccine Batch?\',
                html: \'You are about to delete batch "<strong>\' + batchNum + \'</strong>".<br>This action cannot be undone.\',
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

        // ==============================================================
        // DRUG RECORD EVENT HANDLERS (TAB 1)
        // ==============================================================
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
            $(\'#drugReturnUrl\').val(\'../drug_maintenance.php?view=maintenance&tab=records\');

            $(\'.calc-trigger\').first().trigger(\'input\');
            
            $(\'#drugRecordModalTitle\').html(\'<i class="bi bi-pencil-square me-2 text-warning"></i>Modify Drug Stock Entry\');
            $(\'#immSubmitBtn\').text(\'Save Changes\');
            $(\'#addDrugRecordModal\').modal(\'show\');
        });

        $(\'#addDrugRecordModal\').on(\'hidden.bs.modal\', function() {
            $(\'#drugRecordForm\')[0].reset();
            $(\'#drugAction\').val(\'create\');
            $(\'#drugId\').val(\'\');
            $(\'#drugReturnUrl\').val(\'../drug_maintenance.php?view=maintenance&tab=records\');
            $(\'#drugExpiryDisplay\').text(\'None selected\');
            $(\'#drugLiveBalanceDisplay\').text(\'0 Units\').removeClass(\'text-danger text-success\').addClass(\'text-dark\');
            $(\'#drugRecordModalTitle\').html(\'<i class="bi bi-capsule-compartment me-2"></i>Drug Stock Ledger Entry\');
            $(\'#immSubmitBtn\').prop(\'disabled\', false).text(\'Commit Ledger Entry\');
        });

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

        // ==============================================================
        // DRUG TYPE / MASTER NAME EVENT HANDLERS (TAB 2)
        // ==============================================================
        const selectedAnimals = new Set();

        function updateAnimalHidden() {
            const arr = [...selectedAnimals];
            $(\'#targetAnimalHidden\').val(arr.join(\',\'));

            const select = $(\'#targetAnimalArray\');
            select.empty();
            arr.forEach(value => {
                select.append($(\'<option>\').val(value).text(value).prop(\'selected\', true));
            });

            if (arr.length === 0) {
                $(\'#animalSelectedPills\').text(\'No animals selected.\');
            } else {
                $(\'#animalSelectedPills\').html(
                    arr.map(a => `<span class="badge bg-success me-1">${a}</span>`).join(\'\')
                );
            }
        }

        function resetAnimalSelection() {
            selectedAnimals.clear();
            $(\'.animal-toggle-btn\').removeClass(\'active\').find(\'.check-icon\').hide();
            $(\'#targetAnimalArray\').empty();
            updateAnimalHidden();
        }

        $(document).on(\'click\', \'.animal-toggle-btn\', function() {
            const value = $(this).data(\'value\');
            if (selectedAnimals.has(value)) {
                selectedAnimals.delete(value);
                $(this).removeClass(\'active\');
                $(this).find(\'.check-icon\').hide();
            } else {
                selectedAnimals.add(value);
                $(this).addClass(\'active\');
                $(this).find(\'.check-icon\').show();
            }
            updateAnimalHidden();
        });

        $(document).on(\'click\', \'.edit-drug-type-btn\', function() {
            $(\'#drugTypeForm\')[0].reset();
            $(\'#modalAction\').val(\'update\');
            $(\'#typeId\').val($(this).data(\'id\'));
            $(\'#brandName\').val($(this).data(\'brand\'));
            $(\'#chemComp\').val($(this).data(\'chem\'));
            $(\'#drugName\').val($(this).data(\'name\'));
            $(\'#expiry_date\').val($(this).data(\'expiry\'));
            $(\'#description\').val($(this).data(\'desc\'));
            $(\'#drugTypeReturnUrl\').val(\'../drug_maintenance.php?view=maintenance&tab=drug_names\');

            resetAnimalSelection();
            const animalData = $(this).data(\'animal\') || \'\';
            if (animalData.trim() !== \'\') {
                animalData.split(\',\').map(s => s.trim()).filter(Boolean).forEach(value => {
                    selectedAnimals.add(value);
                    const btn = $(`.animal-toggle-btn[data-value="${value}"]`);
                    btn.addClass(\'active\');
                    btn.find(\'.check-icon\').show();
                });
                updateAnimalHidden();
            }

            $(\'#drugModalTitle\').html(\'<i class="bi bi-pencil-square me-2 text-warning"></i>Modify Drug Type Configuration\');
            $(\'#submitBtn\').removeClass(\'btn-success\').addClass(\'btn-warning\').text(\'Save Modifications\');
            $(\'#addDrugTypeModal\').modal(\'show\');
        });

        $(\'#addDrugTypeModal\').on(\'hidden.bs.modal\', function() {
            $(\'#modalAction\').val(\'create\');
            $(\'#typeId\').val(\'\');
            $(\'#brandName\').val(\'\');
            $(\'#chemComp\').val(\'\');
            $(\'#drugName\').val(\'\');
            $(\'#expiry_date\').val(\'\');
            $(\'#drugTypeReturnUrl\').val(\'../drug_maintenance.php?view=maintenance&tab=drug_names\');
            $(\'#drugTypeForm\')[0].reset();
            resetAnimalSelection();

            $(\'#drugModalTitle\').html(\'<i class="bi bi-patch-plus me-2 text-success"></i>Add New Drug Classification Type\');
            $(\'#submitBtn\').removeClass(\'btn-warning\').addClass(\'btn-success\').text(\'Save Configuration\');
        });

        $(\'#drugTypeForm\').on(\'submit\', function() {
            updateAnimalHidden();
            return true;
        });

        $(document).on(\'click\', \'.btn-delete-drugtype\', function(e) {
            e.preventDefault();
            var deleteUrl = $(this).attr(\'href\');
            var name = $(this).data(\'name\') || \'this drug type\';

            Swal.fire({
                icon: \'warning\',
                title: \'Delete Drug Type?\',
                html: \'You are about to delete drug configuration for "<strong>\' + name + \'</strong>".<br>This action cannot be undone.\',
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
    });
</script>
';
require_once __DIR__ . '/../../../includes/footer.php';
?>