<?php
/**
 * Digitize Cattle Voucher & Counterfoil Generation (Animal Birth Certificate)
 * Department of Animal Production & Health (DAPH)
 */
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$allowed_roles = [
    'veterinary_surgeon',
    'government_veterinary_surgeon',
    'sms',
    'district_dd',
    'deputy_director_district',
    'administrator',
    'provincial_director',
    'deputy_director_hq_1',
    'deputy_director_hq_2',
    'admin'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    header("Location: ../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? '';
$session_range_id = $_SESSION['range_id'] ?? null;
$session_district_id = $_SESSION['district_id'] ?? null;

$requested_range_id = isset($_GET['range_id']) && is_numeric($_GET['range_id']) ? (int)$_GET['range_id'] : null;
$range_id = $requested_range_id ?: ($session_range_id ?: 1);

$range_name = 'Your Range';
$district_name = 'Your District';
$district_id = 1;
$province_name = 'Eastern Province';

if (!empty($range_id)) {
    $r_stmt = $mysqli->prepare("
        SELECT vr.name AS range_name, vr.code AS range_code, d.name AS district_name, d.id AS district_id 
        FROM veterinary_ranges vr 
        LEFT JOIN districts d ON d.id = vr.district_id 
        WHERE vr.id = ? LIMIT 1
    ");
    if ($r_stmt) {
        $r_stmt->bind_param("i", $range_id);
        $r_stmt->execute();
        $r_res = $r_stmt->get_result();
        if ($row = $r_res->fetch_assoc()) {
            $range_name = $row['range_name'];
            $range_code = $row['range_code'] ?: '5314';
            $district_name = $row['district_name'] ?: 'Ampara';
            $district_id = (int)($row['district_id'] ?? 1);
        }
        $r_stmt->close();
    }
}

$selected_year = isset($_GET['year']) && is_numeric($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$selected_status = isset($_GET['status']) ? trim($_GET['status']) : 'All';

// Fetch all veterinary ranges for quick switch
$all_ranges = [];
$ranges_res = $mysqli->query("SELECT id, name FROM veterinary_ranges ORDER BY name ASC");
if ($ranges_res) {
    while ($r = $ranges_res->fetch_assoc()) {
        $all_ranges[] = $r;
    }
}

// Compute KPI Metrics
$kpi_total = 0;
$kpi_active = 0;
$kpi_transferred = 0;
$kpi_damaged = 0;

$kpi_stmt = $mysqli->prepare("
    SELECT 
        COUNT(*) AS total_count,
        SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) AS active_count,
        SUM(CASE WHEN status = 'Transferred' THEN 1 ELSE 0 END) AS transferred_count,
        SUM(CASE WHEN status IN ('Damaged', 'Dead', 'Slaughtered') THEN 1 ELSE 0 END) AS damaged_count
    FROM cattle_vouchers 
    WHERE (range_id = ? OR ? = 0)
");
if ($kpi_stmt) {
    $kpi_stmt->bind_param("ii", $range_id, $range_id);
    $kpi_stmt->execute();
    $kpi_res = $kpi_stmt->get_result();
    if ($k_row = $kpi_res->fetch_assoc()) {
        $kpi_total = (int)($k_row['total_count'] ?? 0);
        $kpi_active = (int)($k_row['active_count'] ?? 0);
        $kpi_transferred = (int)($k_row['transferred_count'] ?? 0);
        $kpi_damaged = (int)($k_row['damaged_count'] ?? 0);
    }
    $kpi_stmt->close();
}

// Fetch cattle vouchers list
$vouchers_list = [];
$where_clauses = ["(cv.range_id = {$range_id} OR {$range_id} = 0)"];
if ($selected_status !== 'All' && !empty($selected_status)) {
    $clean_st = $mysqli->real_escape_string($selected_status);
    $where_clauses[] = "cv.status = '{$clean_st}'";
}
$where_sql = implode(' AND ', $where_clauses);

$list_query = "
    SELECT cv.*, COALESCE(vr.name, 'Ampara') AS range_name
    FROM cattle_vouchers cv
    LEFT JOIN veterinary_ranges vr ON vr.id = cv.range_id
    WHERE {$where_sql}
    ORDER BY cv.id DESC
    LIMIT 1000
";
$list_res = $mysqli->query($list_query);
if ($list_res) {
    while ($row = $list_res->fetch_assoc()) {
        $vouchers_list[] = $row;
    }
}

// Fetch registered farmers for auto-complete datalists
$registered_farmers = [];
$rf_sql = "
    SELECT DISTINCT nic, registration_no, farmer_name 
    FROM farm_registration_renewals 
    WHERE nic IS NOT NULL AND nic != ''
    ORDER BY farmer_name ASC 
    LIMIT 300
";
$rf_res = $mysqli->query($rf_sql);
if ($rf_res) {
    while ($rf_row = $rf_res->fetch_assoc()) {
        $registered_farmers[] = $rf_row;
    }
}

// Fetch registered ear tags for auto-complete datalists
$registered_tags = [];
$rt_sql = "
    SELECT DISTINCT ear_tag_no, species, breed, farmer_name 
    FROM animal_identifications 
    WHERE ear_tag_no IS NOT NULL AND ear_tag_no != ''
    ORDER BY id DESC 
    LIMIT 300
";
$rt_res = $mysqli->query($rt_sql);
if ($rt_res) {
    while ($rt_row = $rt_res->fetch_assoc()) {
        $registered_tags[] = $rt_row;
    }
}

require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="container-fluid px-2 px-md-3 py-3">

    <!-- Top Breadcrumb & Jurisdiction Bar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="../../../dashboard.php" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i>Dashboard</a></li>
                <li class="breadcrumb-item"><a href="regulatory_functions.php" class="text-decoration-none text-muted">Veterinary Module</a></li>
                <li class="breadcrumb-item"><a href="ear_tag_balance.php" class="text-decoration-none text-muted">Ear Tag Balance</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">
                    Cattle Voucher &amp; Birth Certificate</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <form method="GET" class="d-flex align-items-center gap-2 m-0">
                <select name="range_id" class="form-select form-select-sm" onchange="this.form.submit();" style="max-width: 180px;">
                    <?php foreach ($all_ranges as $rng): ?>
                        <option value="<?= $rng['id'] ?>" <?= (int)$rng['id'] === (int)$range_id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($rng['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="year" class="form-select form-select-sm" onchange="this.form.submit();" style="max-width: 100px;">
                    <?php for ($y = date('Y') + 1; $y >= 2020; $y--): ?>
                        <option value="<?= $y ?>" <?= $y === $selected_year ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </form>
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="bi bi-geo-alt-fill text-danger me-1"></i>District: <strong><?= htmlspecialchars($district_name) ?></strong>
            </span>
        </div>
    </div>

    <!-- Hero Banner with Action Buttons -->
    <div class="card border-0 rounded-4 shadow-sm mb-4 text-white overflow-hidden" style="background: linear-gradient(135deg, #370709 0%, #5a1215 70%, #1e293b 100%);">
        <div class="card-body p-4 position-relative">
            <div class="row align-items-center g-3">
                <div class="col-lg-7">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-warning text-dark fw-bold px-2 py-1 font-monospace"></span>
                        <span class="badge bg-white bg-opacity-25 text-white">Animals Act Statutory Registry</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Animal Birth Certificate</span>
                    </div>
                    <h2 class="h3 fw-bold mb-2"> Cattle Voucher &amp; Counterfoil Registry</h2>
                    <p class="mb-0 text-white-50 small" style="max-width: 650px;">
                        Digitized statutory animal birth certificate, legal ownership voucher, and physical counterfoil generation for cattle and buffalo identification in <strong><?= htmlspecialchars($range_name) ?></strong>.
                    </p>
                </div>
                <div class="col-lg-5">
                    <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                        <button type="button" class="btn btn-warning text-dark fw-bold shadow-sm" onclick="openIssueVoucherModal();">
                            <i class="bi bi-plus-circle-fill me-1"></i>Issue  Voucher
                        </button>
                        <button type="button" class="btn btn-outline-light btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalCattleVoucherReturn">
                            <i class="bi bi-file-earmark-ruled me-1"></i>Monthly Return
                        </button>
                        <a href="ear_tag_balance.php" class="btn btn-outline-light btn-sm fw-semibold">
                            <i class="bi bi-tags me-1"></i>Ear Tag Registry
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4 KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 rounded-3 shadow-xs h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold text-uppercase">Total Vouchers Issued</span>
                        <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="bi bi-receipt-cutoff fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-0 text-dark font-monospace"><?= number_format($kpi_total) ?></h3>
                    <small class="text-muted" style="font-size: 0.74rem;">Birth certificates registered</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 rounded-3 shadow-xs h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold text-uppercase">Active Title Certificates</span>
                        <div class="bg-success text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="bi bi-shield-check fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-0 text-success font-monospace"><?= number_format($kpi_active) ?></h3>
                    <small class="text-success" style="font-size: 0.74rem;">Valid &amp; active titles</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 rounded-3 shadow-xs h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold text-uppercase">Transferred / Sold</span>
                        <div class="bg-warning text-dark rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="bi bi-arrow-left-right fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-0 text-warning font-monospace"><?= number_format($kpi_transferred) ?></h3>
                    <small class="text-muted" style="font-size: 0.74rem;">Ownership transferred</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 rounded-3 shadow-xs h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold text-uppercase">Damaged / Dead / Other</span>
                        <div class="bg-danger text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="bi bi-x-octagon fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-0 text-danger font-monospace"><?= number_format($kpi_damaged) ?></h3>
                    <small class="text-muted" style="font-size: 0.74rem;">Withdrawn or deceased</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Master Data Table Section -->
    <div class="card border-0 rounded-4 shadow-sm bg-white mb-4">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-card-checklist text-primary"></i>
                    <span>Digitized Cattle Vouchers &amp; Birth Certificates</span>
                    <span class="badge bg-primary text-white rounded-pill px-2 py-1 ms-1"><?= count($vouchers_list) ?> Records</span>
                </h5>
                <small class="text-muted">Statutory registry of vouchers and counterpart stubs issued under the Animals Act</small>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Status Filter Pills -->
                <div class="btn-group btn-group-sm" role="group">
                    <a href="?range_id=<?= $range_id ?>&year=<?= $selected_year ?>&status=All" class="btn btn-outline-secondary <?= $selected_status === 'All' ? 'active' : '' ?>">All</a>
                    <a href="?range_id=<?= $range_id ?>&year=<?= $selected_year ?>&status=Active" class="btn btn-outline-success <?= $selected_status === 'Active' ? 'active' : '' ?>">Active</a>
                    <a href="?range_id=<?= $range_id ?>&year=<?= $selected_year ?>&status=Transferred" class="btn btn-outline-warning <?= $selected_status === 'Transferred' ? 'active' : '' ?>">Transferred</a>
                    <a href="?range_id=<?= $range_id ?>&year=<?= $selected_year ?>&status=Damaged" class="btn btn-outline-danger <?= $selected_status === 'Damaged' ? 'active' : '' ?>">Damaged</a>
                </div>
                <button type="button" class="btn btn-success btn-sm fw-semibold" onclick="openIssueVoucherModal();">
                    <i class="bi bi-plus-lg me-1"></i>New Voucher
                </button>
            </div>
        </div>

        <!-- CUSTOM ACTION TOOLBAR (STANDARDIZED WITH EAR_TAG_BALANCE.PHP) -->
        <div class="p-3 bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <span class="fw-bold text-dark"><i class="bi bi-table me-1 text-primary"></i>Schedule-06 Cattle Vouchers &amp; Birth Certificate Data Grid</span>
            </div>
            <div class="custom-table-toolbar d-flex gap-2 flex-wrap align-items-center">
                <button type="button" class="btn btn-sm btn-table-export-csv" onclick="exportCattleVouchersCSV();" title="Export Cattle Voucher Records to CSV">
                    <i class="bi bi-file-earmark-spreadsheet"></i> <span>Export to CSV</span>
                </button>
                <button type="button" class="btn btn-sm btn-table-export-pdf" onclick="exportCattleVouchersPDF();" title="Export Cattle Voucher Records to PDF">
                    <i class="bi bi-file-earmark-pdf"></i> <span>Export to PDF</span>
                </button>
                <button type="button" class="btn btn-sm btn-table-print" onclick="printCattleVoucherTable();" title="Print Current Data Grid View">
                    <i class="bi bi-printer"></i> <span>Print</span>
                </button>
                <div class="dropdown">
                    <button class="btn btn-sm btn-table-sort dropdown-toggle" type="button" id="cvSortMenuBtn" data-bs-toggle="dropdown" aria-expanded="false" title="Quick Sort Records">
                        <i class="bi bi-sort-down me-1"></i>Sort
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="cvSortMenuBtn">
                        <li><h6 class="dropdown-header">Voucher Serial No</h6></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="sortCattleVouchers(1, 'desc');"><i class="bi bi-sort-numeric-down-alt me-2 text-danger"></i>Serial (Newest first)</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="sortCattleVouchers(1, 'asc');"><i class="bi bi-sort-numeric-down me-2 text-secondary"></i>Serial (Oldest first)</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">Issue Date</h6></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="sortCattleVouchers(2, 'desc');"><i class="bi bi-sort-numeric-down-alt me-2 text-danger"></i>Date (Newest first)</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="sortCattleVouchers(2, 'asc');"><i class="bi bi-sort-numeric-down me-2 text-secondary"></i>Date (Oldest first)</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">Owner Name</h6></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="sortCattleVouchers(3, 'asc');"><i class="bi bi-sort-alpha-down me-2 text-success"></i>Owner Name (A to Z)</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="sortCattleVouchers(3, 'desc');"><i class="bi bi-sort-alpha-down-alt me-2 text-success"></i>Owner Name (Z to A)</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">Ear Tag No</h6></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="sortCattleVouchers(5, 'asc');"><i class="bi bi-sort-numeric-down me-2 text-primary"></i>Ear Tag (Ascending)</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="sortCattleVouchers(5, 'desc');"><i class="bi bi-sort-numeric-down-alt me-2 text-primary"></i>Ear Tag (Descending)</a></li>
                    </ul>
                </div>
                <span class="badge table-record-count-badge" id="cattleVoucherRecordCountBadge" data-entity="Cattle Vouchers">
                    <i class="bi bi-check2-circle me-1"></i><span id="cattleVoucherRecordCountText"><?= count($vouchers_list) ?> Cattle Vouchers</span>
                </span>
            </div>
        </div>

        <div class="card-body p-3">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0 w-100" id="tblCattleVouchers">
                    <thead class="table-light text-secondary small">
                        <tr>
                            <th style="width: 40px;" class="text-center">#</th>
                            <th style="width: 140px;">Voucher Serial No</th>
                            <th>Issue Date</th>
                            <th>Owner Details</th>
                            <th>Holding / Location</th>
                            <th>Animal Description (Ear Tag)</th>
                            <th>Age / DOB</th>
                            <th>Status</th>
                            <th style="width: 150px;" class="text-end text-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($vouchers_list)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-file-earmark-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    No Cattle Vouchers found for the selected range and status filter.<br>
                                    <button type="button" class="btn btn-primary btn-sm mt-3" onclick="openIssueVoucherModal();">
                                        <i class="bi bi-plus-circle me-1"></i>Issue First  Cattle Voucher
                                    </button>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($vouchers_list as $idx => $v): ?>
                                <?php
                                $status_badge_class = 'bg-success-subtle text-success border border-success-subtle';
                                if ($v['status'] === 'Transferred') $status_badge_class = 'bg-warning-subtle text-warning border border-warning-subtle';
                                elseif ($v['status'] === 'Dead') $status_badge_class = 'bg-dark-subtle text-dark border border-dark-subtle';
                                elseif ($v['status'] === 'Slaughtered') $status_badge_class = 'bg-info-subtle text-info border border-info-subtle';
                                elseif ($v['status'] === 'Damaged') $status_badge_class = 'bg-danger-subtle text-danger border border-danger-subtle';
                                ?>
                                <tr>
                                    <td class="text-center text-muted fw-semibold"><?= $idx + 1 ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge bg-dark font-monospace fw-bold text-white px-2 py-1"><?= htmlspecialchars($v['voucher_number']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="font-monospace text-muted"><?= htmlspecialchars($v['issue_date']) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">
                                            <?= htmlspecialchars(($v['owner_title'] ? $v['owner_title'] . ' ' : '') . ($v['farmer_name'] ?: 'N/A')) ?>
                                        </div>
                                        <div class="small text-muted font-monospace">
                                            <i class="bi bi-card-heading me-1 text-primary"></i><?= htmlspecialchars($v['farmer_nic'] ?: '—') ?>
                                        </div>
                                        <?php if (!empty($v['farmer_address'])): ?>
                                            <div class="text-muted text-truncate" style="max-width: 180px; font-size: 0.72rem;">
                                                <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($v['farmer_address']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div>
                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle font-monospace">
                                                Reg: <?= htmlspecialchars($v['farm_reg_no'] ?: '—') ?>
                                            </span>
                                        </div>
                                        <div class="small text-muted" style="font-size: 0.74rem;">
                                            <?= htmlspecialchars($v['vs_division'] ?: $v['range_name'] ?: '') ?>
                                            <?= !empty($v['ds_division']) ? ' / ' . htmlspecialchars($v['ds_division']) : '' ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <i class="bi bi-tag-fill text-success"></i>
                                            <strong class="font-monospace text-success"><?= htmlspecialchars($v['ear_tag_number']) ?></strong>
                                        </div>
                                        <div class="text-muted" style="font-size: 0.74rem;">
                                            <strong><?= htmlspecialchars($v['species'] ?: 'Cattle') ?></strong> - <?= htmlspecialchars($v['breed'] ?: '—') ?>
                                            <span class="badge bg-light text-dark border px-1"><?= htmlspecialchars($v['sex'] ?: 'Female') ?></span>
                                        </div>
                                        <?php if (!empty($v['colour'])): ?>
                                            <div class="text-muted" style="font-size: 0.70rem;">
                                                Colour: <?= htmlspecialchars($v['colour']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($v['age'] ?: '—') ?></div>
                                        <?php if (!empty($v['date_of_birth'])): ?>
                                            <small class="text-muted font-monospace" style="font-size: 0.72rem;">DOB: <?= htmlspecialchars($v['date_of_birth']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $status_badge_class ?>"><?= htmlspecialchars($v['status']) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <!-- Print / Preview Two-Part Certificate -->
                                            <button type="button" class="btn btn-warning btn-sm text-dark fw-bold" onclick="openPrintVoucherModal(<?= $v['id'] ?>);" title="Print Official  Voucher & Counterfoil">
                                                <i class="bi bi-printer"></i>
                                            </button>
                                            <!-- Edit Voucher Details -->
                                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="openIssueVoucherModal(<?= $v['id'] ?>);" title="Edit Voucher Data">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <!-- Status Update Dropdown -->
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-outline-dark btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Change Status">
                                                    <i class="bi bi-gear"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end small shadow-sm">
                                                    <li><h6 class="dropdown-header">Update Status</h6></li>
                                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="quickUpdateVoucherStatus(<?= $v['id'] ?>, 'Active');"><i class="bi bi-check-circle text-success me-2"></i>Active Title</a></li>
                                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="quickUpdateVoucherStatus(<?= $v['id'] ?>, 'Transferred');"><i class="bi bi-arrow-left-right text-warning me-2"></i>Transferred (Sold)</a></li>
                                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="quickUpdateVoucherStatus(<?= $v['id'] ?>, 'Slaughtered');"><i class="bi bi-scissors text-info me-2"></i>Slaughtered</a></li>
                                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="quickUpdateVoucherStatus(<?= $v['id'] ?>, 'Dead');"><i class="bi bi-x-circle text-secondary me-2"></i>Dead (Deceased)</a></li>
                                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="quickUpdateVoucherStatus(<?= $v['id'] ?>, 'Damaged');"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Damaged / Withdrawn</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteVoucher(<?= $v['id'] ?>, '<?= htmlspecialchars($v['voucher_number'], ENT_QUOTES) ?>');"><i class="bi bi-trash me-2"></i>Delete Record</a></li>
                                                </ul>
                                            </div>
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

<!-- Modal 1: Digitize Cattle Voucher Form -->
<?php include __DIR__ . '/models/modal_issue_cattle_voucher.php'; ?>

<!-- Modal 2: Official Printable Two-Part Certificate (Voucher & Counterfoil) -->
<?php include __DIR__ . '/models/modal_print_cattle_voucher.php'; ?>

<!-- Modal 3: Cattle Voucher Return Register -->
<?php include __DIR__ . '/models/modal_cattle_voucher_return.php'; ?>

<!-- Modal 4: Monthly Ledger Usage Modal -->
<?php include __DIR__ . '/models/add_cattle_voucher_modal.php'; ?>

<!-- Client-side Logic -->
<?php
ob_start();
?>
<script>
$(document).ready(function() {
    // -------------------------------------------------------------------------
    // STANDARDIZED DATATABLE INITIALIZATION (EXPORTS, SEARCH & PAGINATION)
    // -------------------------------------------------------------------------
    var cvTable = null;
    if ($.fn.DataTable) {
        if ($.fn.DataTable.isDataTable('#tblCattleVouchers')) {
            $('#tblCattleVouchers').DataTable().destroy();
        }
        cvTable = $('#tblCattleVouchers').DataTable({
            pageLength: 15,
            lengthMenu: [10, 15, 25, 50, 100],
            order: [[1, 'desc']],
            columnDefs: [
                { orderable: false, targets: [0, 8] }
            ],
            responsive: false,
            buttons: [
                {
                    extend: 'csvHtml5',
                    className: 'd-none',
                    filename: function() {
                        return 'Cattle_Vouchers_Registry_' + new Date().toISOString().slice(0, 10);
                    },
                    title: 'Department of Animal Production & Health - Schedule-06 Cattle Voucher & Birth Certificate Registry',
                    exportOptions: {
                        columns: ':visible:not(:first-child):not(:last-child)'
                    }
                },
                {
                    extend: 'pdfHtml5',
                    className: 'd-none',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    filename: function() {
                        return 'Cattle_Vouchers_Registry_' + new Date().toISOString().slice(0, 10);
                    },
                    title: 'Department of Animal Production & Health - Schedule-06 Cattle Voucher & Birth Certificate Registry',
                    exportOptions: {
                        columns: ':visible:not(:first-child):not(:last-child)'
                    },
                    customize: function(doc) {
                        doc.defaultStyle.fontSize = 8;
                        if (doc.styles && doc.styles.tableHeader) {
                            doc.styles.tableHeader.fontSize = 9;
                            doc.styles.tableHeader.fillColor = '#370709';
                            doc.styles.tableHeader.color = '#ffffff';
                        }
                    }
                },
                {
                    extend: 'print',
                    className: 'd-none',
                    title: 'Department of Animal Production & Health — Schedule-06 Cattle Voucher Registry',
                    messageTop: 'Official Cattle Voucher & Animal Birth Certificate Registry — Veterinary Range: <?= htmlspecialchars($range_name) ?> (<?= htmlspecialchars($district_name) ?> District)',
                    exportOptions: {
                        columns: ':visible:not(:last-child)'
                    },
                    customize: function(win) {
                        $(win.document.body).css('font-size', '10pt').css('padding', '20px');
                        $(win.document.body).find('table')
                            .addClass('compact')
                            .css('font-size', 'inherit')
                            .css('width', '100%');
                        $(win.document.body).find('h1')
                            .css('text-align', 'center')
                            .css('font-size', '16pt')
                            .css('margin-bottom', '10px');
                    }
                }
            ],
            language: {
                search: '',
                searchPlaceholder: 'Search serial, NIC, owner, ear tag, status...',
                emptyTable: 'No Cattle Voucher records found in this view.'
            }
        });
        window.cvTableInstance = cvTable;

        // Dynamic Record Count sync
        cvTable.on('draw.dt init.dt', function() {
            const info = cvTable.page.info();
            const count = info.recordsDisplay;
            const total = info.recordsTotal;
            const badgeText = $('#cattleVoucherRecordCountText');
            if (badgeText.length) {
                badgeText.text(count === total ? `${count} Cattle Vouchers` : `${count} of ${total} Cattle Vouchers`);
            }
        });
    }

    // Export & Print Global Handlers
    window.exportCattleVouchersCSV = function() {
        if (window.cvTableInstance && typeof window.cvTableInstance.button === 'function') {
            window.cvTableInstance.button('.buttons-csv').trigger();
        }
    };

    window.exportCattleVouchersPDF = function() {
        if (window.cvTableInstance && typeof window.cvTableInstance.button === 'function') {
            window.cvTableInstance.button('.buttons-pdf').trigger();
        }
    };

    window.printCattleVoucherTable = function() {
        if (window.cvTableInstance && typeof window.cvTableInstance.button === 'function') {
            window.cvTableInstance.button('.buttons-print').trigger();
        } else {
            window.print();
        }
    };

    window.sortCattleVouchers = function(colIndex, dir) {
        if (window.cvTableInstance) {
            window.cvTableInstance.order([colIndex, dir]).draw();
        }
    };

    // -------------------------------------------------------------------------
    // HELPER: MODAL ALERT NOTIFICATIONS
    // -------------------------------------------------------------------------
    window.showModalAlert = function(elemId, type, html) {
        var $el = $('#' + elemId);
        $el.removeClass('d-none alert-success alert-danger alert-warning alert-info')
           .addClass('alert-' + type)
           .html(html);
    };

    // -------------------------------------------------------------------------
    // AUTO-POPULATION 1: LOOKUP OWNER BY NIC
    // -------------------------------------------------------------------------
    let nicLookupTimer = null;
    let activeNicXhr = null;

    window.triggerLookupOwnerByNic = function() {
        var nic = $('#s06_farmer_nic').val().trim();
        if (!nic || nic.length < 3) return;

        $('#btnLookupNic').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        if (activeNicXhr) {
            activeNicXhr.abort();
        }

        activeNicXhr = $.ajax({
            url: 'processors/cattle_voucher_crud.php',
            type: 'GET',
            data: { action: 'lookup_owner', nic: nic },
            dataType: 'json',
            success: function(res) {
                $('#btnLookupNic').prop('disabled', false).html('<i class="bi bi-search"></i>');
                activeNicXhr = null;
                if (res.success && res.owner) {
                    var o = res.owner;
                    if (o.owner_title) $('#s06_owner_title').val(o.owner_title);
                    if (o.farmer_name) $('#s06_farmer_name').val(o.farmer_name);
                    if (o.farmer_address) $('#s06_farmer_address').val(o.farmer_address);
                    if (o.farm_reg_no) $('#s06_farm_reg_no').val(o.farm_reg_no);
                    if (o.province) $('#s06_province').val(o.province);
                    if (o.district) $('#s06_district').val(o.district);
                    if (o.ds_division) $('#s06_ds_division').val(o.ds_division);
                    if (o.vs_division) $('#s06_vs_division').val(o.vs_division);
                    if (o.gn_division) $('#s06_gn_division').val(o.gn_division);

                    showModalAlert('s06ModalAlert', 'success', '<i class="bi bi-check-circle-fill me-1"></i> Owner profile & holding details auto-populated for <strong>' + (o.farmer_name || nic) + '</strong> (' + o.source + ').');
                } else {
                    showModalAlert('s06ModalAlert', 'info', '<i class="bi bi-info-circle me-1"></i> ' + (res.message || 'Owner details not found in registry. You may enter manually.'));
                }
            },
            error: function(xhr, status) {
                if (status === 'abort') return;
                $('#btnLookupNic').prop('disabled', false).html('<i class="bi bi-search"></i>');
                activeNicXhr = null;
            }
        });
    };

    // NIC event triggers: click, input (debounced), change, blur, keydown
    $(document).on('click', '#btnLookupNic', function() {
        triggerLookupOwnerByNic();
    });

    $(document).on('input', '#s06_farmer_nic', function() {
        clearTimeout(nicLookupTimer);
        var val = $(this).val().trim();
        if (val.length >= 4) {
            nicLookupTimer = setTimeout(triggerLookupOwnerByNic, 350);
        }
    });

    $(document).on('change blur', '#s06_farmer_nic', function() {
        triggerLookupOwnerByNic();
    });

    $(document).on('keydown', '#s06_farmer_nic', function(e) {
        if (e.key === 'Enter' || e.key === 'Tab') {
            if (e.key === 'Enter') e.preventDefault();
            triggerLookupOwnerByNic();
        }
    });

    // -------------------------------------------------------------------------
    // AUTO-POPULATION 2: LOOKUP HOLDING BY FARM REG NO
    // -------------------------------------------------------------------------
    let farmLookupTimer = null;
    let activeFarmXhr = null;

    window.triggerLookupOwnerByFarmReg = function() {
        var reg = $('#s06_farm_reg_no').val().trim();
        if (!reg || reg.length < 2) return;

        $('#btnLookupFarmReg').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        if (activeFarmXhr) {
            activeFarmXhr.abort();
        }

        activeFarmXhr = $.ajax({
            url: 'processors/cattle_voucher_crud.php',
            type: 'GET',
            data: { action: 'lookup_owner', farm_reg_no: reg },
            dataType: 'json',
            success: function(res) {
                $('#btnLookupFarmReg').prop('disabled', false).html('<i class="bi bi-arrow-repeat"></i>');
                activeFarmXhr = null;
                if (res.success && res.owner) {
                    var o = res.owner;
                    if (o.farmer_nic && !$('#s06_farmer_nic').val()) $('#s06_farmer_nic').val(o.farmer_nic);
                    if (o.owner_title) $('#s06_owner_title').val(o.owner_title);
                    if (o.farmer_name && !$('#s06_farmer_name').val()) $('#s06_farmer_name').val(o.farmer_name);
                    if (o.farmer_address && !$('#s06_farmer_address').val()) $('#s06_farmer_address').val(o.farmer_address);
                    if (o.province) $('#s06_province').val(o.province);
                    if (o.district) $('#s06_district').val(o.district);
                    if (o.ds_division) $('#s06_ds_division').val(o.ds_division);
                    if (o.vs_division) $('#s06_vs_division').val(o.vs_division);
                    if (o.gn_division) $('#s06_gn_division').val(o.gn_division);

                    showModalAlert('s06ModalAlert', 'success', '<i class="bi bi-check-circle-fill me-1"></i> Farm holding location auto-populated for Reg: <strong>' + reg + '</strong>.');
                }
            },
            error: function(xhr, status) {
                if (status === 'abort') return;
                $('#btnLookupFarmReg').prop('disabled', false).html('<i class="bi bi-arrow-repeat"></i>');
                activeFarmXhr = null;
            }
        });
    };

    $(document).on('click', '#btnLookupFarmReg', function() {
        triggerLookupOwnerByFarmReg();
    });

    $(document).on('input', '#s06_farm_reg_no', function() {
        clearTimeout(farmLookupTimer);
        var val = $(this).val().trim();
        if (val.length >= 3) {
            farmLookupTimer = setTimeout(triggerLookupOwnerByFarmReg, 350);
        }
    });

    $(document).on('change blur', '#s06_farm_reg_no', function() {
        triggerLookupOwnerByFarmReg();
    });

    $(document).on('keydown', '#s06_farm_reg_no', function(e) {
        if (e.key === 'Enter' || e.key === 'Tab') {
            if (e.key === 'Enter') e.preventDefault();
            triggerLookupOwnerByFarmReg();
        }
    });

    // -------------------------------------------------------------------------
    // AUTO-POPULATION 3: LOOKUP ANIMAL BY EAR TAG NO
    // -------------------------------------------------------------------------
    let tagLookupTimer = null;
    let activeTagXhr = null;

    window.triggerLookupAnimalByTag = function() {
        var tag = $('#s06_ear_tag_number').val().trim();
        if (!tag || tag.length < 3) return;

        $('#btnLookupAnimal').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        if (activeTagXhr) {
            activeTagXhr.abort();
        }

        activeTagXhr = $.ajax({
            url: 'processors/cattle_voucher_crud.php',
            type: 'GET',
            data: { action: 'lookup_animal', ear_tag_no: tag },
            dataType: 'json',
            success: function(res) {
                $('#btnLookupAnimal').prop('disabled', false).html('<i class="bi bi-search"></i>');
                activeTagXhr = null;
                if (res.success && res.animal) {
                    var a = res.animal;
                    if (a.species) $('#s06_species').val(a.species);
                    if (a.breed) $('#s06_breed').val(a.breed);
                    if (a.gender) $('#s06_sex').val(a.gender);
                    if (a.colour) $('#s06_colour').val(a.colour);
                    if (a.age) $('#s06_age').val(a.age);
                    if (a.date_of_birth) $('#s06_date_of_birth').val(a.date_of_birth);
                    if (a.identification_date) $('#s06_identification_date').val(a.identification_date);
                    if (a.specific_marks) $('#s06_specific_marks').val(a.specific_marks);
                    if (a.voucher_serial_no && !$('#s06_voucher_number').val()) {
                        $('#s06_voucher_number').val(a.voucher_serial_no);
                    }

                    // Also sync owner and holding if empty
                    if (a.farmer_name && !$('#s06_farmer_name').val()) $('#s06_farmer_name').val(a.farmer_name);
                    if (a.farm_reg_no && !$('#s06_farm_reg_no').val()) $('#s06_farm_reg_no').val(a.farm_reg_no);
                    if (a.vs_range && !$('#s06_vs_division').val()) $('#s06_vs_division').val(a.vs_range);
                    if (a.district && !$('#s06_district').val()) $('#s06_district').val(a.district);

                    showModalAlert('s06ModalAlert', 'success', '<i class="bi bi-check-circle-fill me-1"></i> Animal particulars &amp; birth data auto-populated for Tag <strong>' + tag + '</strong> (' + (a.species || 'Cattle') + ' - ' + (a.breed || 'Breed') + ').');
                } else {
                    showModalAlert('s06ModalAlert', 'info', '<i class="bi bi-info-circle me-1"></i> ' + (res.message || 'Tag not found in tagging registry; please fill animal particulars.'));
                }
            },
            error: function(xhr, status) {
                if (status === 'abort') return;
                $('#btnLookupAnimal').prop('disabled', false).html('<i class="bi bi-search"></i>');
                activeTagXhr = null;
            }
        });
    };

    $(document).on('click', '#btnLookupAnimal', function() {
        triggerLookupAnimalByTag();
    });

    $(document).on('input', '#s06_ear_tag_number', function() {
        clearTimeout(tagLookupTimer);
        var val = $(this).val().trim();
        if (val.length >= 4) {
            tagLookupTimer = setTimeout(triggerLookupAnimalByTag, 350);
        }
    });

    $(document).on('change blur', '#s06_ear_tag_number', function() {
        triggerLookupAnimalByTag();
    });

    $(document).on('keydown', '#s06_ear_tag_number', function(e) {
        if (e.key === 'Enter' || e.key === 'Tab') {
            if (e.key === 'Enter') e.preventDefault();
            triggerLookupAnimalByTag();
        }
    });

    // -------------------------------------------------------------------------
    // AUTO-POPULATION 4: AUTO-GENERATE NEXT VOUCHER SERIAL
    // -------------------------------------------------------------------------
    window.generateNextSerial = function() {
        $('#btnAutoGenerateSerial').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: 'processors/cattle_voucher_crud.php',
            type: 'GET',
            data: { action: 'get_next_serial', year: <?= (int)$selected_year ?> },
            dataType: 'json',
            success: function(res) {
                $('#btnAutoGenerateSerial').prop('disabled', false).html('<i class="bi bi-magic me-1"></i>Auto Generate');
                if (res.success && res.serial) {
                    $('#s06_voucher_number').val(res.serial);
                    showModalAlert('s06ModalAlert', 'success', '<i class="bi bi-check-circle-fill me-1"></i> Suggested unique voucher serial generated: <strong>' + res.serial + '</strong>');
                }
            },
            error: function() {
                $('#btnAutoGenerateSerial').prop('disabled', false).html('<i class="bi bi-magic me-1"></i>Auto Generate');
            }
        });
    };

    $(document).on('click', '#btnAutoGenerateSerial', function() {
        generateNextSerial();
    });

    // -------------------------------------------------------------------------
    // DOB CHANGE: AUTO CALCULATE AGE
    // -------------------------------------------------------------------------
    $(document).on('change', '#s06_date_of_birth', function() {
        var dobStr = $(this).val();
        if (!dobStr) return;
        var dob = new Date(dobStr);
        var now = new Date();
        var years = now.getFullYear() - dob.getFullYear();
        var months = now.getMonth() - dob.getMonth();
        if (months < 0) {
            years--;
            months += 12;
        }
        if (years > 0) {
            $('#s06_age').val(years + ' Yrs' + (months > 0 ? ' ' + months + ' Mo' : ''));
        } else {
            $('#s06_age').val(Math.max(1, months) + ' Months');
        }
    });

    // -------------------------------------------------------------------------
    // SUBMIT CATTLE VOUCHER FORM
    // -------------------------------------------------------------------------
    $(document).on('submit', '#formCattleVoucher', function(e) {
        e.preventDefault();
        var $btn = $('#btnSubmitCattleVoucher');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        $.ajax({
            url: 'processors/cattle_voucher_crud.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                $btn.prop('disabled', false).html('<i class="bi bi-check-circle-fill me-1"></i>Issue Cattle Voucher');
                if (res.success) {
                    showModalAlert('s06ModalAlert', 'success', '<i class="bi bi-check-circle-fill me-1"></i> ' + res.message);
                    setTimeout(function() {
                        var modalEl = document.getElementById('modalIssueCattleVoucher');
                        var modalInst = bootstrap.Modal.getInstance(modalEl);
                        if (modalInst) modalInst.hide();

                        // Open printable preview directly
                        if (res.voucher_id) {
                            openPrintVoucherModal(res.voucher_id);
                        } else {
                            location.reload();
                        }
                    }, 800);
                } else {
                    showModalAlert('s06ModalAlert', 'danger', '<i class="bi bi-exclamation-triangle-fill me-1"></i> ' + (res.message || 'Error saving voucher.'));
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="bi bi-check-circle-fill me-1"></i>Issue Cattle Voucher');
                showModalAlert('s06ModalAlert', 'danger', '<i class="bi bi-x-circle-fill me-1"></i> Network or server error occurred.');
            }
        });
    });

    // Save and Print immediately shortcut
    $(document).on('click', '#btnSaveAndPrintVoucher', function() {
        $('#formCattleVoucher').trigger('submit');
    });

});

// -----------------------------------------------------------------------------
// OPEN ISSUE / EDIT VOUCHER MODAL (GLOBAL FUNCTION)
// -----------------------------------------------------------------------------
window.openIssueVoucherModal = function(voucherId = 0) {
    var form = document.getElementById('formCattleVoucher');
    if (form) form.reset();

    $('#s06_voucher_id').val(voucherId || 0);
    $('#s06ModalAlert').addClass('d-none').html('');
    $('#s06_range_id').val(<?= (int)$range_id ?>);
    $('#s06_province').val('Eastern Province');
    $('#s06_district').val('<?= htmlspecialchars($district_name, ENT_QUOTES) ?>');
    $('#s06_vs_division').val('<?= htmlspecialchars($range_name, ENT_QUOTES) ?>');
    $('#s06_identification_date').val('<?= date('Y-m-d') ?>');
    $('#s06_issue_date').val('<?= date('Y-m-d') ?>');

    if (voucherId > 0) {
        $('#modalIssueCattleVoucherLabel').text('Edit Cattle Voucher & Birth Certificate');
        $('#btnSubmitCattleVoucher').html('<i class="bi bi-save me-1"></i>Update Voucher Data');
        // Fetch existing data
        $.ajax({
            url: 'processors/cattle_voucher_crud.php',
            type: 'GET',
            data: { action: 'get_voucher', id: voucherId },
            dataType: 'json',
            success: function(res) {
                if (res.success && res.voucher) {
                    var v = res.voucher;
                    $('#s06_voucher_number').val(v.voucher_number || '');
                    $('#s06_owner_title').val(v.owner_title || 'Mr.');
                    $('#s06_farmer_name').val(v.farmer_name || '');
                    $('#s06_farmer_address').val(v.farmer_address || '');
                    $('#s06_farmer_nic').val(v.farmer_nic || '');
                    $('#s06_farm_reg_no').val(v.farm_reg_no || '');
                    $('#s06_province').val(v.province || 'Eastern Province');
                    $('#s06_district').val(v.district || '<?= htmlspecialchars($district_name, ENT_QUOTES) ?>');
                    $('#s06_ds_division').val(v.ds_division || '');
                    $('#s06_vs_division').val(v.vs_division || '<?= htmlspecialchars($range_name, ENT_QUOTES) ?>');
                    $('#s06_gn_division').val(v.gn_division || '');
                    $('#s06_ear_tag_number').val(v.ear_tag_number || '');
                    $('#s06_identification_date').val(v.identification_date || '<?= date('Y-m-d') ?>');
                    $('#s06_species').val(v.species || 'Cattle');
                    $('#s06_breed').val(v.breed || '');
                    $('#s06_sex').val(v.sex || 'Female');
                    $('#s06_date_of_birth').val(v.date_of_birth || '');
                    $('#s06_age').val(v.age || '');
                    $('#s06_colour').val(v.colour || '');
                    $('#s06_specific_marks').val(v.specific_marks || '');
                    $('#s06_issue_date').val(v.issue_date || '<?= date('Y-m-d') ?>');
                    $('#s06_vs_name').val(v.vs_name || '');
                    $('#s06_status').val(v.status || 'Active');
                    $('#s06_remarks').val(v.remarks || '');
                }
            }
        });
    } else {
        $('#modalIssueCattleVoucherLabel').text('Cattle Voucher / Animal Birth Certificate');
        $('#btnSubmitCattleVoucher').html('<i class="bi bi-check-circle-fill me-1"></i>Issue Cattle Voucher');
        // Auto populate next serial for new voucher
        generateNextSerial();
    }

    var modalEl = document.getElementById('modalIssueCattleVoucher');
    var modalInst = bootstrap.Modal.getOrCreateInstance(modalEl);
    modalInst.show();

    setTimeout(function() {
        $('#s06_farmer_nic').focus();
    }, 450);
};

// -----------------------------------------------------------------------------
// OPEN PRINT MODAL (TWO-PART CERTIFICATE)
// -----------------------------------------------------------------------------
window.openPrintVoucherModal = function(voucherId) {
    $.ajax({
        url: 'processors/cattle_voucher_crud.php',
        type: 'GET',
        data: { action: 'get_voucher', id: voucherId },
        dataType: 'json',
        success: function(res) {
            if (res.success && res.voucher) {
                var v = res.voucher;
                var serial = v.voucher_number || 'APH/CV/—';

                $('#printPreviewSerialBadge').text('Serial: ' + serial);

                // Counterfoil (Part 1 - Stub)
                $('#prt_cf_serial').text(serial);
                $('#prt_cf_owner_name').text((v.owner_title ? v.owner_title + ' ' : '') + (v.farmer_name || '—'));
                $('#prt_cf_nic').text(v.farmer_nic || '—');
                $('#prt_cf_address').text(v.farmer_address || '—');
                $('#prt_cf_farm_reg').text(v.farm_reg_no || '—');
                $('#prt_cf_vs_area').text(v.vs_division || '<?= htmlspecialchars($range_name, ENT_QUOTES) ?>');
                $('#prt_cf_ds_area').text(v.ds_division || '—');
                $('#prt_cf_district').text(v.district || '<?= htmlspecialchars($district_name, ENT_QUOTES) ?>');
                $('#prt_cf_gn_div').text(v.gn_division || '—');
                $('#prt_cf_tag_no').text(v.ear_tag_number || '—');
                $('#prt_cf_species').text(v.species || 'Cattle');
                $('#prt_cf_breed').text(v.breed || '—');
                $('#prt_cf_sex').text(v.sex || 'Female');
                $('#prt_cf_dob').text(v.date_of_birth || 'N/A');
                $('#prt_cf_age').text(v.age || '—');
                $('#prt_cf_colour').text(v.colour || '—');
                $('#prt_cf_marks').text(v.specific_marks || 'None specified');
                $('#prt_cf_date').text(v.issue_date || '<?= date('Y-m-d') ?>');
                $('#prt_cf_status').text(v.status || 'Active');

                // Main Voucher (Part 2 - Animal Birth Certificate)
                $('#prt_main_serial').text(serial);
                $('#prt_main_title').text(v.owner_title || 'Mr.');
                $('#prt_main_owner_name').text(v.farmer_name || '—');
                $('#prt_main_nic').text(v.farmer_nic || '—');
                $('#prt_main_phone').text('—');
                $('#prt_main_address').text(v.farmer_address || '—');
                $('#prt_main_province').text(v.province || 'Eastern Province');
                $('#prt_main_district').text(v.district || '<?= htmlspecialchars($district_name, ENT_QUOTES) ?>');
                $('#prt_main_ds_area').text(v.ds_division || '—');
                $('#prt_main_vs_area').text(v.vs_division || '<?= htmlspecialchars($range_name, ENT_QUOTES) ?>');
                $('#prt_main_farm_reg').text(v.farm_reg_no || '—');
                $('#prt_main_gn_div').text(v.gn_division || '—');
                $('#prt_main_tag_no').text(v.ear_tag_number || '—');
                $('#prt_main_id_date').text(v.identification_date || v.issue_date || '—');
                $('#prt_main_species').text(v.species || 'Neat Cattle');
                $('#prt_main_breed').text(v.breed || '—');
                $('#prt_main_sex').text(v.sex || 'Female');
                $('#prt_main_colour').text(v.colour || '—');
                $('#prt_main_dob').text(v.date_of_birth ? v.date_of_birth : 'Approx. ' + (v.age || '—'));
                $('#prt_main_age').text(v.age || '—');
                $('#prt_main_marks').text(v.specific_marks || 'No special marks recorded');
                $('#prt_main_date').text(v.issue_date || '<?= date('Y-m-d') ?>');
                $('#prt_main_vs_name').text(v.vs_name || 'Government Veterinary Surgeon');

                var modalEl = document.getElementById('modalPrintCattleVoucher');
                var modalInst = bootstrap.Modal.getOrCreateInstance(modalEl);
                modalInst.show();
            } else {
                alert(res.message || 'Unable to retrieve voucher record for printing.');
            }
        },
        error: function() {
            alert('Server error loading voucher details.');
        }
    });
};

// -----------------------------------------------------------------------------
// PRINT DOCUMENT TRIGGER
// -----------------------------------------------------------------------------
window.printCattleVoucherDocument = function() {
    window.print();
};

// -----------------------------------------------------------------------------
// QUICK STATUS UPDATE
// -----------------------------------------------------------------------------
window.quickUpdateVoucherStatus = function(id, newStatus) {
    if (!confirm("Are you sure you want to update this voucher status to '" + newStatus + "'?")) return;

    $.ajax({
        url: 'processors/cattle_voucher_crud.php',
        type: 'POST',
        data: { action: 'update_status', id: id, status: newStatus },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.message || 'Error updating status.');
            }
        },
        error: function() {
            alert('Server error.');
        }
    });
};

// -----------------------------------------------------------------------------
// DELETE VOUCHER RECORD
// -----------------------------------------------------------------------------
window.deleteVoucher = function(id, voucherNumber) {
    if (!confirm("Are you sure you want to delete Voucher '" + voucherNumber + "'? This action cannot be undone.")) return;

    $.ajax({
        url: 'processors/cattle_voucher_crud.php',
        type: 'POST',
        data: { action: 'delete_voucher', id: id },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.message || 'Error deleting voucher.');
            }
        },
        error: function() {
            alert('Server error.');
        }
    });
};
</script>
<?php
$pageScripts = ob_get_clean();

require_once __DIR__ . '/../../../includes/footer.php';
?>
