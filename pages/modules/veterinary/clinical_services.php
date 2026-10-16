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
require_once __DIR__ . '/../../../includes/counterfoil_module_helper.php';

/** @var mysqli $mysqli */
global $mysqli;
ensure_clinical_opd_tables($mysqli);

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
    'planning_officer'
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

// Active Tab ('large_animal' or 'small_animal')
$active_tab = isset($_GET['tab']) && in_array($_GET['tab'], ['large_animal', 'small_animal'], true) 
    ? $_GET['tab'] 
    : 'large_animal';

// Date / Year / Month Filters
$selected_year  = isset($_GET['year']) && is_numeric($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$selected_month = isset($_GET['month']) && is_numeric($_GET['month']) ? intval($_GET['month']) : 0; // 0 for all

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
                <button type="button" class="btn btn-light fw-bold px-3 py-2 shadow-sm" id="btnOpenNewOpdModal">
                    <i class="bi bi-plus-circle-fill text-danger me-1"></i> New Clinical OPD Entry
                </button>
            </div>
        </div>
    </div>

    <!-- Quick KPI Cards Grid -->
    <div class="row g-3 mb-4">
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
        </ul>

        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small">
                Showing <?= $active_tab === 'large_animal' ? count($large_animal_records) : count($small_animal_records) ?> clinical records
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
                <small class="text-muted">Cattle, Buffalo, Goat, Sheep &amp; Farm Livestock &bull; Farm Registration Master Verification</small>
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
                        <!-- Two-tier Physical Register Header Structure -->
                        <tr class="group-header">
                            <th rowspan="2" style="width: 40px;">#</th>
                            <th rowspan="2" style="min-width: 95px;">Date</th>
                            <th colspan="3" style="background-color: #dcfce7; color: #166534;">Intake &amp; Farmer Registry Auto-Details</th>
                            <th colspan="6" style="background-color: #f1f5f9; color: #334155;">Animal Particulars (Physical Register)</th>
                            <th colspan="3" style="background-color: #fee2e2; color: #991b1b;">Clinical Findings &amp; Diagnostics</th>
                            <th colspan="4" class="th-revenue-group">Reasons with details for revenue collection (Rs.)</th>
                            <th rowspan="2" style="min-width: 110px;">Cash Receipt No.</th>
                            <th rowspan="2" style="min-width: 100px;">Remarks</th>
                            <th rowspan="2" style="width: 90px;">Action</th>
                        </tr>
                        <tr>
                            <!-- Farmer Sub-columns -->
                            <th style="min-width: 120px;">Farm Reg No</th>
                            <th style="min-width: 140px;">Farmer Name</th>
                            <th style="min-width: 130px;">Address &amp; Tel</th>

                            <!-- Animal Particulars Sub-columns -->
                            <th style="min-width: 80px;" title="Total Population of the herd">Herd Pop.</th>
                            <th style="min-width: 85px;">Species</th>
                            <th style="min-width: 90px;">Breed</th>
                            <th style="min-width: 65px;">Age (Yrs)</th>
                            <th style="min-width: 55px;">Sex</th>
                            <th style="min-width: 90px;">Colour &amp; Special Marks</th>

                            <!-- Clinical Sub-columns -->
                            <th style="min-width: 70px;" title="Clinically Affected">Affected</th>
                            <th style="min-width: 60px;" title="Deaths if any">Deaths</th>
                            <th style="min-width: 160px;">Tentative Diagnosis &amp; Action Taken</th>

                            <!-- Revenue 3 Manual Sub-columns + Total -->
                            <th class="th-revenue-sub" style="min-width: 75px;">Consultation</th>
                            <th class="th-revenue-sub" style="min-width: 75px;">Drugs</th>
                            <th class="th-revenue-sub" style="min-width: 75px;">Others</th>
                            <th class="th-revenue-sub fw-bold" style="min-width: 85px;">Total (Rs.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($large_animal_records)): ?>
                            <tr>
                                <td colspan="20" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                    No Large Animal clinical records logged for the selected period. Click <strong>Add Large Animal Record</strong> above to register a patient.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $idx = 1; foreach ($large_animal_records as $rec): ?>
                                <tr>
                                    <td class="text-center fw-bold text-muted"><?= $idx++ ?></td>
                                    <td class="text-center">
                                        <div class="fw-semibold text-dark"><?= date('d-M-Y', strtotime($rec['entry_date'])) ?></div>
                                    </td>
                                    <!-- Farmer Registry Info -->
                                    <td>
                                        <span class="badge bg-success-subtle text-success border border-success fw-bold font-monospace">
                                            <?= htmlspecialchars($rec['farm_reg_no'] ?: $rec['registration_no']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($rec['owner_name']) ?></div>
                                        <?php if (!empty($rec['owner_nic'])): ?>
                                            <small class="text-muted d-block">NIC: <?= htmlspecialchars($rec['owner_nic']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="small text-muted text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($rec['owner_address'] ?? '') ?>">
                                            <?= htmlspecialchars($rec['owner_address'] ?? '—') ?>
                                        </div>
                                        <?php if (!empty($rec['owner_phone'])): ?>
                                            <small class="text-primary d-block"><i class="bi bi-telephone-fill me-1"></i><?= htmlspecialchars($rec['owner_phone']) ?></small>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Animal Particulars -->
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border fw-bold"><?= intval($rec['herd_total_population']) ?></span>
                                    </td>
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

                                    <!-- Clinical Findings -->
                                    <td class="text-center">
                                        <span class="badge bg-warning-subtle text-warning-emphasis fw-bold">
                                            <?= intval($rec['clinically_affected_count']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if (intval($rec['death_count']) > 0): ?>
                                            <span class="badge bg-danger text-white fw-bold"><?= intval($rec['death_count']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="text-danger d-block"><?= htmlspecialchars($rec['tentative_diagnosis']) ?></strong>
                                        <small class="text-muted"><?= htmlspecialchars(mb_strimwidth($rec['action_taken'], 0, 50, '...')) ?></small>
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
                                        <div class="fw-bold font-monospace text-primary"><?= htmlspecialchars($rec['cash_receipt_no']) ?></div>
                                        <?php if (!empty($rec['leaf_issue_id'])): ?>
                                            <span class="badge-counterfoil" title="Overarching Counterfoil Leaf ID #<?= $rec['leaf_issue_id'] ?>">
                                                <i class="bi bi-check2-circle"></i> Leaf #<?= $rec['leaf_issue_id'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-pending">Pending Sync</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Remarks -->
                                    <td>
                                        <small class="text-muted"><?= htmlspecialchars($rec['remarks'] ?: '—') ?></small>
                                    </td>

                                    <!-- Action -->
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-info btn-view-opd" data-id="<?= $rec['id'] ?>" title="View Physical Register Card">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-primary btn-edit-opd" data-id="<?= $rec['id'] ?>" title="Edit Entry">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-delete-opd" data-id="<?= $rec['id'] ?>" data-name="<?= htmlspecialchars($rec['owner_name'], ENT_QUOTES) ?>" data-receipt="<?= htmlspecialchars($rec['cash_receipt_no'], ENT_QUOTES) ?>" title="Delete Record">
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
    <!-- TAB 2: SMALL ANIMAL CLINICAL REGISTER                                  -->
    <!-- ======================================================================= -->
    <?php else: ?>
    <div class="card border-0 shadow-sm" style="border-radius: var(--border-radius);">
        <div class="card-header bg-white py-3 border-0 d-flex flex-wrap align-items-center justify-content-between">
            <div>
                <h5 class="mb-0 fw-bold text-primary">
                    <i class="bi bi-heart-pulse me-2"></i>Small Animal Clinic OPD Register
                </h5>
                <small class="text-muted">Dogs (Canine), Cats (Feline), Avian &amp; Pets &bull; Standard Animal Registration Numbers</small>
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
                        <!-- Two-tier Physical Register Header Structure -->
                        <tr class="group-header">
                            <th rowspan="2" style="width: 40px;">#</th>
                            <th rowspan="2" style="min-width: 95px;">Date</th>
                            <th colspan="3" style="background-color: #e0f2fe; color: #0369a1;">Pet Owner &amp; Registration</th>
                            <th colspan="6" style="background-color: #f1f5f9; color: #334155;">Patient Particulars (Physical Register)</th>
                            <th colspan="3" style="background-color: #fee2e2; color: #991b1b;">Clinical Findings &amp; Diagnostics</th>
                            <th colspan="4" class="th-revenue-group">Reasons with details for revenue collection (Rs.)</th>
                            <th rowspan="2" style="min-width: 110px;">Cash Receipt No.</th>
                            <th rowspan="2" style="min-width: 100px;">Remarks</th>
                            <th rowspan="2" style="width: 90px;">Action</th>
                        </tr>
                        <tr>
                            <!-- Pet Owner Sub-columns -->
                            <th style="min-width: 120px;">Reg / Case No</th>
                            <th style="min-width: 140px;">Owner Name</th>
                            <th style="min-width: 130px;">Address &amp; Tel</th>

                            <!-- Patient Particulars Sub-columns -->
                            <th style="min-width: 75px;" title="Total Population in household/kennel">Total Pop.</th>
                            <th style="min-width: 85px;">Species</th>
                            <th style="min-width: 90px;">Breed</th>
                            <th style="min-width: 65px;">Age (Yrs)</th>
                            <th style="min-width: 55px;">Sex</th>
                            <th style="min-width: 90px;">Colour &amp; Special Marks</th>

                            <!-- Clinical Sub-columns -->
                            <th style="min-width: 70px;" title="Clinically Affected">Affected</th>
                            <th style="min-width: 60px;" title="Deaths if any">Deaths</th>
                            <th style="min-width: 160px;">Tentative Diagnosis &amp; Action Taken</th>

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
                                <td colspan="20" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                    No Small Animal clinical records logged for the selected period. Click <strong>Add Small Animal Record</strong> above to register a patient.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $idx = 1; foreach ($small_animal_records as $rec): ?>
                                <tr>
                                    <td class="text-center fw-bold text-muted"><?= $idx++ ?></td>
                                    <td class="text-center">
                                        <div class="fw-semibold text-dark"><?= date('d-M-Y', strtotime($rec['entry_date'])) ?></div>
                                    </td>
                                    <!-- Pet Owner Info -->
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border border-primary fw-bold font-monospace">
                                            <?= htmlspecialchars($rec['registration_no']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($rec['owner_name']) ?></div>
                                        <?php if (!empty($rec['owner_nic'])): ?>
                                            <small class="text-muted d-block">NIC: <?= htmlspecialchars($rec['owner_nic']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="small text-muted text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($rec['owner_address'] ?? '') ?>">
                                            <?= htmlspecialchars($rec['owner_address'] ?? '—') ?>
                                        </div>
                                        <?php if (!empty($rec['owner_phone'])): ?>
                                            <small class="text-primary d-block"><i class="bi bi-telephone-fill me-1"></i><?= htmlspecialchars($rec['owner_phone']) ?></small>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Patient Particulars -->
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border"><?= intval($rec['herd_total_population']) ?></span>
                                    </td>
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

                                    <!-- Clinical Findings -->
                                    <td class="text-center">
                                        <span class="badge bg-warning-subtle text-warning-emphasis fw-bold">
                                            <?= intval($rec['clinically_affected_count']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if (intval($rec['death_count']) > 0): ?>
                                            <span class="badge bg-danger text-white fw-bold"><?= intval($rec['death_count']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="text-danger d-block"><?= htmlspecialchars($rec['tentative_diagnosis']) ?></strong>
                                        <small class="text-muted"><?= htmlspecialchars(mb_strimwidth($rec['action_taken'], 0, 50, '...')) ?></small>
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
                                        <div class="fw-bold font-monospace text-primary"><?= htmlspecialchars($rec['cash_receipt_no']) ?></div>
                                        <?php if (!empty($rec['leaf_issue_id'])): ?>
                                            <span class="badge-counterfoil" title="Overarching Counterfoil Leaf ID #<?= $rec['leaf_issue_id'] ?>">
                                                <i class="bi bi-check2-circle"></i> Leaf #<?= $rec['leaf_issue_id'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-pending">Pending Sync</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Remarks -->
                                    <td>
                                        <small class="text-muted"><?= htmlspecialchars($rec['remarks'] ?: '—') ?></small>
                                    </td>

                                    <!-- Action -->
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-info btn-view-opd" data-id="<?= $rec['id'] ?>" title="View Physical Register Card">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-primary btn-edit-opd" data-id="<?= $rec['id'] ?>" title="Edit Entry">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-delete-opd" data-id="<?= $rec['id'] ?>" data-name="<?= htmlspecialchars($rec['owner_name'], ENT_QUOTES) ?>" data-receipt="<?= htmlspecialchars($rec['cash_receipt_no'], ENT_QUOTES) ?>" title="Delete Record">
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
                                        <i class="bi bi-shield-shaded me-1"></i> Large Animal Clinic (Cattle, Buffalo, Goat, Swine)
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="clinic_type" id="clinicTypeSmall" value="small_animal">
                                    <label class="form-check-label fw-bold text-primary fs-6" for="clinicTypeSmall">
                                        <i class="bi bi-heart-pulse me-1"></i> Small Animal Clinic (Canine / Dogs, Feline / Cats)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Date & Intake Information -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Date of Clinic Intake <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="entry_date" id="entry_date" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <!-- LARGE ANIMAL WORKFLOW: Farm Reg No with Auto-Population -->
                        <div class="col-md-5 section-large-animal">
                            <label class="form-label small fw-bold">
                                Farm Registration Number <span class="text-danger">*</span>
                                <span class="badge bg-success-subtle text-success ms-1">Auto-Fetches Farmer Details</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-search text-success"></i></span>
                                <input type="text" class="form-control" name="farm_reg_no" id="farm_reg_no" 
                                       placeholder="Enter Farm Reg No (e.g. NW-KUR-1042 or NIC)..." 
                                       style="font-family: monospace; font-weight: 600;">
                                <button class="btn btn-outline-success" type="button" id="btnAutoFetchFarmer" title="Fetch from Master Registry">
                                    <span id="spinnerFetch" class="spinner-border spinner-border-sm d-none me-1" role="status"></span>
                                    <i class="bi bi-lightning-charge-fill me-1" id="iconFetch"></i> Auto-Fetch
                                </button>
                            </div>
                            <div id="fetchFeedback" class="small mt-1" style="display: none;"></div>
                        </div>

                        <!-- SMALL ANIMAL WORKFLOW: Standard Registration No for Dogs / Cats -->
                        <div class="col-md-5 section-small-animal" style="display: none;">
                            <label class="form-label small fw-bold">
                                Standard Registration / Case No <span class="text-danger">*</span>
                                <span class="badge bg-primary-subtle text-primary ms-1">Dog / Cat Tag / Microchip</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-tag-fill text-primary"></i></span>
                                <input type="text" class="form-control" name="registration_no" id="registration_no" 
                                       placeholder="e.g. REG-SA-<?= date('Y') ?>-001 or Pet ID..." 
                                       style="font-family: monospace; font-weight: 600;">
                            </div>
                            <div class="form-text small">Enter standard clinic registration or pet license number.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold" id="lblOwnerNic">Owner / Farmer NIC</label>
                            <input type="text" class="form-control" name="owner_nic" id="owner_nic" placeholder="e.g. 198512345678 or 851234567V">
                        </div>
                    </div>

                    <!-- Auto-Populated Owner & Location Details -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold" id="lblOwnerName">Name of Farmer <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="owner_name" id="owner_name" placeholder="Full name of owner" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold" id="lblOwnerAddress">Address of Farmer</label>
                            <input type="text" class="form-control" name="owner_address" id="owner_address" placeholder="Residential or Farm Address">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold" id="lblOwnerPhone">Contact Tel. No</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-telephone"></i></span>
                                <input type="text" class="form-control" name="owner_phone" id="owner_phone" placeholder="e.g. 0771234567">
                            </div>
                        </div>
                    </div>

                    <!-- Clinical Data Grid Physical Register Columns -->
                    <div class="modal-section-title">
                        <i class="bi bi-clipboard2-data text-danger"></i> 2. Clinical Data Register Columns (Physical Book Layout)
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold" id="lblTotalPop">
                                Total Population of the herd <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" name="herd_total_population" id="herd_total_population" min="1" value="1" required>
                            <div class="form-text small" id="helpTotalPop">Total animal count in the farm herd.</div>
                        </div>
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
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Breed</label>
                            <input type="text" class="form-control" name="breed" id="breed" placeholder="e.g. Jersey, Friesian, Labrador, Local Cross">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Age (Yrs)</label>
                            <input type="text" class="form-control" name="age_years" id="age_years" placeholder="e.g. 2.5 yrs, 4 mos">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Sex (M/F) <span class="text-danger">*</span></label>
                            <select class="form-select" name="sex" id="sex" required>
                                <option value="Female" selected>Female (F)</option>
                                <option value="Male">Male (M)</option>
                                <option value="Unknown">Unknown</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Coat Colour</label>
                            <input type="text" class="form-control" name="coat_colour" id="coat_colour" placeholder="e.g. Black & White, Brown, Fawn, White">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Special Marks</label>
                            <input type="text" class="form-control" name="special_marks" id="special_marks" placeholder="e.g. Ear Tag #LK-902, Brand Mark 'DAPH', Microchip #, White Star">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-danger">No of Animals Clinically Affected <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="clinically_affected_count" id="clinically_affected_count" min="1" value="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-danger">No of Deaths (If Any)</label>
                            <input type="number" class="form-control" name="death_count" id="death_count" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tentative Diagnosis <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="tentative_diagnosis" id="tentative_diagnosis" 
                                   placeholder="e.g. Subclinical Mastitis, Ketosis, Parvovirus, Bloat..." required>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Action Taken &amp; Prescriptions <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="action_taken" id="action_taken" rows="2" 
                                      placeholder="Clinical treatments, medications administered, antibiotic protocols, surgical intervention or dietary advisories..." required></textarea>
                        </div>
                    </div>

                    <!-- Revenue Collection & Counter File Linkage Section -->
                    <div class="modal-section-title">
                        <i class="bi bi-cash-coin text-warning-emphasis"></i> 3. Revenue Collection (Rs.) &amp; Overarching Counter File Linkage
                    </div>

                    <div class="p-3 mb-3 border rounded bg-warning-subtle" style="border-color: #fde68a !important;">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-12">
                                <div class="fw-bold text-dark small mb-2">
                                    <i class="bi bi-info-circle me-1 text-primary"></i> Reasons with details for revenue collection (Rs.):
                                </div>
                            </div>
                            <!-- 3 Manual Input Sub-columns: Consultation, Drugs, Others -->
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Consultation (Rs.)</label>
                                <input type="number" step="0.01" min="0" class="form-control revenue-calc" 
                                       name="revenue_consultation" id="revenue_consultation" value="0.00">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Drugs (Rs.)</label>
                                <input type="number" step="0.01" min="0" class="form-control revenue-calc" 
                                       name="revenue_drugs" id="revenue_drugs" value="0.00">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Others (Rs.)</label>
                                <input type="number" step="0.01" min="0" class="form-control revenue-calc" 
                                       name="revenue_others" id="revenue_others" value="0.00">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-success">Total Revenue (Rs.)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white fw-bold text-success">Rs.</span>
                                    <input type="text" class="form-control fw-bold text-success bg-white" 
                                           id="revenue_total_display" value="0.00" readonly style="font-size: 1.1rem;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mandatory Receipt Tracking & Remarks -->
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">
                                Cash Receipt No. <span class="text-danger">*</span>
                                <span class="badge bg-danger-subtle text-danger ms-1">Mandatory Tracking</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-receipt text-primary"></i></span>
                                <input type="text" class="form-control" name="cash_receipt_no" id="cash_receipt_no" 
                                       placeholder="e.g. CR-10492" required style="font-family: monospace; font-weight: 700;">
                            </div>
                            <div class="form-text small text-muted">
                                <i class="bi bi-arrow-repeat text-success me-1"></i>Automatically links to <strong>Counterfoil Book Leaf Issues</strong> &amp; <strong>Cash Book</strong>.
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">General Remarks</label>
                            <input type="text" class="form-control" name="remarks" id="remarks" placeholder="Follow-up instructions, referral notes, or general observations">
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
        $('#modalOpdHeader').css('background', 'linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%)');
        $('#modalOpdEntryLabel').text($('#opd_id').val() > 0 ? 'Edit Large Animal OPD Record' : 'New Large Animal OPD Record');
        $('#lblOwnerName').text('Name of Farmer *');
        $('#lblOwnerAddress').text('Address of Farmer');
        $('#lblOwnerPhone').text('Contact Tel. No');
        $('#lblTotalPop').html('Total Population of the herd <span class="text-danger">*</span>');
        $('#helpTotalPop').text('Total animal count in the farm herd.');
        $('#btnSaveOpdRecord').removeClass('btn-primary').addClass('btn-success');
        $('#optGroupLarge').prop('disabled', false);
    } else {
        $('.section-large-animal').hide();
        $('.section-small-animal').show();
        $('#modalOpdHeader').css('background', 'linear-gradient(135deg, #1565c0 0%, #0d47a1 100%)');
        $('#modalOpdEntryLabel').text($('#opd_id').val() > 0 ? 'Edit Small Animal OPD Record' : 'New Small Animal OPD Record');
        $('#lblOwnerName').text('Name of Pet Owner *');
        $('#lblOwnerAddress').text('Address of Pet Owner');
        $('#lblOwnerPhone').text('Contact Tel. No');
        $('#lblTotalPop').html('Total Population (Household/Kennel) <span class="text-danger">*</span>');
        $('#helpTotalPop').text('Number of pets or kennel size.');
        $('#btnSaveOpdRecord').removeClass('btn-success').addClass('btn-primary');
    }
};

window.openNewOpdModalWithPreset = function(type) {
    const formEl = document.getElementById('formClinicalOpd');
    if (formEl) formEl.reset();
    $('#opd_id').val('0');
    $('#fetchFeedback').hide();
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

                const html = `
                    <div class="border rounded p-3 mb-3 bg-light">
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <small class="text-muted text-uppercase fw-bold d-block">Intake Date</small>
                                <strong class="text-dark">${d.entry_date}</strong>
                            </div>
                            <div class="col-6 col-md-3">
                                <small class="text-muted text-uppercase fw-bold d-block">Registration Identifier</small>
                                <span class="badge ${isLarge ? 'bg-success' : 'bg-primary'} font-monospace">${d.registration_no || d.farm_reg_no}</span>
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

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="card h-100 border">
                                <div class="card-header bg-white fw-bold small text-muted text-uppercase py-2">
                                    <i class="bi bi-person me-1"></i> Owner / Farmer Particulars
                                </div>
                                <div class="card-body p-3">
                                    <div class="mb-2"><strong>Name:</strong> ${d.owner_name}</div>
                                    <div class="mb-2"><strong>NIC:</strong> ${d.owner_nic || '—'}</div>
                                    <div class="mb-2"><strong>Address:</strong> ${d.owner_address || '—'}</div>
                                    <div><strong>Telephone:</strong> ${d.owner_phone || '—'}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 border">
                                <div class="card-header bg-white fw-bold small text-muted text-uppercase py-2">
                                    <i class="bi bi-shield me-1"></i> Animal Physical Particulars
                                </div>
                                <div class="card-body p-3">
                                    <div class="mb-2"><strong>Total Population of Herd:</strong> <span class="badge bg-dark">${d.herd_total_population}</span></div>
                                    <div class="mb-2"><strong>Species &amp; Breed:</strong> ${d.species} (${d.breed || 'Local'})</div>
                                    <div class="mb-2"><strong>Age &amp; Sex:</strong> ${d.age_years || '—'} &bull; ${d.sex}</div>
                                    <div class="mb-2"><strong>Coat Colour:</strong> ${d.coat_colour || '—'}</div>
                                    <div><strong>Special Marks:</strong> ${d.special_marks || '—'}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border mb-3">
                        <div class="card-header bg-white fw-bold small text-muted text-uppercase py-2 text-danger">
                            <i class="bi bi-heart-pulse me-1"></i> Clinical Diagnostics &amp; Treatment
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-2 mb-2">
                                <div class="col-md-6"><strong>Animals Clinically Affected:</strong> <span class="badge bg-warning-subtle text-warning-emphasis">${d.clinically_affected_count}</span></div>
                                <div class="col-md-6"><strong>Number of Deaths:</strong> <span class="badge bg-danger-subtle text-danger">${d.death_count}</span></div>
                            </div>
                            <div class="mb-2">
                                <strong>Tentative Diagnosis:</strong>
                                <div class="p-2 mt-1 rounded bg-danger-subtle text-danger fw-bold">${d.tentative_diagnosis}</div>
                            </div>
                            <div>
                                <strong>Action Taken:</strong>
                                <div class="p-2 mt-1 rounded bg-light border">${d.action_taken}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card border bg-warning-subtle">
                        <div class="card-header bg-transparent fw-bold small text-uppercase py-2 text-warning-emphasis">
                            <i class="bi bi-cash me-1"></i> Revenue Breakdown (Rs.)
                        </div>
                        <div class="card-body p-3">
                            <div class="row text-center g-2">
                                <div class="col-3">
                                    <small class="text-muted d-block">Consultation</small>
                                    <strong class="font-monospace">Rs. ${parseFloat(d.revenue_consultation).toFixed(2)}</strong>
                                </div>
                                <div class="col-3">
                                    <small class="text-muted d-block">Drugs</small>
                                    <strong class="font-monospace">Rs. ${parseFloat(d.revenue_drugs).toFixed(2)}</strong>
                                </div>
                                <div class="col-3">
                                    <small class="text-muted d-block">Others</small>
                                    <strong class="font-monospace">Rs. ${parseFloat(d.revenue_others).toFixed(2)}</strong>
                                </div>
                                <div class="col-3 border-start">
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
                } else {
                    $('#clinicTypeSmall').prop('checked', true);
                    applyWorkflowLayout('small_animal');
                    $('#registration_no').val(d.registration_no);
                }

                $('#owner_name').val(d.owner_name);
                $('#owner_nic').val(d.owner_nic || '');
                $('#owner_address').val(d.owner_address || '');
                $('#owner_phone').val(d.owner_phone || '');

                $('#herd_total_population').val(d.herd_total_population);
                $('#species').val(d.species);
                $('#breed').val(d.breed || '');
                $('#age_years').val(d.age_years || '');
                $('#sex').val(d.sex);
                $('#coat_colour').val(d.coat_colour || '');
                $('#special_marks').val(d.special_marks || '');

                $('#clinically_affected_count').val(d.clinically_affected_count);
                $('#death_count').val(d.death_count);
                $('#tentative_diagnosis').val(d.tentative_diagnosis);
                $('#action_taken').val(d.action_taken);

                $('#revenue_consultation').val(d.revenue_consultation);
                $('#revenue_drugs').val(d.revenue_drugs);
                $('#revenue_others').val(d.revenue_others);
                $('#revenue_total_display').val(parseFloat(d.revenue_total).toFixed(2));

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

    // 4. Auto-Population (Large Animals): Fetch Farmer Details by Farm Registration Number
    $('#btnAutoFetchFarmer').on('click', function(e) {
        e.preventDefault();
        performFarmerLookup();
    });

    $('#farm_reg_no').on('blur', function() {
        const val = $(this).val().trim();
        if (val.length >= 3 && $('#owner_name').val().trim() === '') {
            performFarmerLookup();
        }
    });

    function performFarmerLookup() {
        const query = $('#farm_reg_no').val().trim();
        if (!query) {
            Swal.fire({
                icon: 'info',
                title: 'Enter Farm Registration Number',
                text: 'Please enter a Farm Registration Number or Farmer NIC to auto-fetch details from the master registry.'
            });
            return;
        }

        $('#spinnerFetch').removeClass('d-none');
        $('#iconFetch').addClass('d-none');
        $('#btnAutoFetchFarmer').prop('disabled', true);
        $('#fetchFeedback').hide();

        $.ajax({
            url: 'processors/clinical_services_crud.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'lookup_farmer',
                farm_reg_no: query
            },
            success: function(res) {
                $('#spinnerFetch').addClass('d-none');
                $('#iconFetch').removeClass('d-none');
                $('#btnAutoFetchFarmer').prop('disabled', false);

                if (res.success && res.data) {
                    const f = res.data;
                    $('#owner_name').val(f.farmer_name).addClass('auto-fill-flash');
                    $('#owner_address').val(f.address).addClass('auto-fill-flash');
                    $('#owner_phone').val(f.phone).addClass('auto-fill-flash');
                    if (f.nic) $('#owner_nic').val(f.nic).addClass('auto-fill-flash');
                    if (f.herd_population) $('#herd_total_population').val(f.herd_population).addClass('auto-fill-flash');

                    setTimeout(() => {
                        $('.auto-fill-flash').removeClass('auto-fill-flash');
                    }, 1200);

                    $('#fetchFeedback').html(
                        `<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Found: ${f.farmer_name} (${f.source})</span>`
                    ).show();
                } else {
                    $('#fetchFeedback').html(
                        `<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>${res.message}</span>`
                    ).show();
                }
            },
            error: function(xhr, status, error) {
                $('#spinnerFetch').addClass('d-none');
                $('#iconFetch').removeClass('d-none');
                $('#btnAutoFetchFarmer').prop('disabled', false);
                $('#fetchFeedback').html(
                    '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Server error looking up farmer.</span>'
                ).show();
            }
        });
    }

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

    // 6. Save Clinical OPD Record via AJAX
    $('#formClinicalOpd').on('submit', function(e) {
        e.preventDefault();

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
});
</script>
<?php
$pageScripts = ob_get_clean();
require_once __DIR__ . '/../../../includes/footer.php';
?>
