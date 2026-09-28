<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

// 1. Session and Role Clearance Guard
$allowed_roles = [
    'veterinary_surgeon',
    'government_veterinary_surgeon',
    'additional_veterinary_surgeon',
    'sms',
    'district_dd',
    'deputy_director_district',
    'deputy_director_hq_1',
    'provincial_director',
    'admin',
    'super_admin'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    header("Location: ../../../index.php");
    exit();
}

$user_id   = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? '';
$is_supervisory = in_array($user_role, ['sms', 'district_dd', 'deputy_director_district', 'deputy_director_hq_1', 'provincial_director', 'admin', 'super_admin'], true);

$session_range_id = $_SESSION['range_id'] ?? null;
$range_id = ($is_supervisory && isset($_GET['range_id']) && !empty($_GET['range_id'])) 
    ? intval($_GET['range_id']) 
    : ($session_range_id ?? 1);

$district_id = $_SESSION['district_id'] ?? null;
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

$range_name = 'Your Range';
$district_name = 'Your District';

// Extract Range Name and District Name
if (!empty($range_id)) {
    $stmt = $mysqli->prepare("
        SELECT vr.name AS range_name, d.name AS district_name 
        FROM veterinary_ranges vr 
        LEFT JOIN districts d ON vr.district_id = d.id 
        WHERE vr.id = ?
    ");
    if ($stmt) {
        $stmt->bind_param("i", $range_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $range_name = $row['range_name'] ?? 'Your Range';
            $district_name = $row['district_name'] ?? 'Your District';
        }
        $stmt->close();
    }
}

// 2. Define the 15 Extension Services Indicators (NO c.1, c.2 prefix tags)
$extension_indicators = [
    'trainings_conducted' => [
        'title' => 'Conducting trainings',
        'indicator' => 'No of training conducted',
        'default_target' => 25,
        'unit' => 'Trainings',
        'icon' => 'bi-easel-fill',
        'col' => 'target_trainings_conducted',
        'badge' => 'Training'
    ],
    'farmers_sent_training' => [
        'title' => 'Farmers send to training',
        'indicator' => 'No of Farmer send to training',
        'default_target' => 100,
        'unit' => 'Farmers',
        'icon' => 'bi-people-fill',
        'col' => 'target_farmers_sent_training',
        'badge' => 'Training'
    ],
    'attending_training' => [
        'title' => 'Attending training',
        'indicator' => 'No of training attended',
        'default_target' => 10,
        'unit' => 'Sessions',
        'icon' => 'bi-person-badge-fill',
        'col' => 'target_attending_training',
        'badge' => 'Training'
    ],
    'pasture_unit_est' => [
        'title' => 'Est of pasture unit',
        'indicator' => 'No of Acres Established',
        'default_target' => 3,
        'unit' => 'Acres',
        'icon' => 'bi-tree-fill',
        'col' => 'target_pasture_unit_est',
        'badge' => 'Pasture'
    ],
    'financial_access' => [
        'title' => 'Assisting financial Access',
        'indicator' => 'No of Bank loan approved',
        'default_target' => 0,
        'unit' => 'Loans',
        'icon' => 'bi-bank2',
        'col' => 'target_financial_access',
        'badge' => 'Finance'
    ],
    'farmer_society_est' => [
        'title' => 'Est of farmer society',
        'indicator' => 'No. of farmer societies established',
        'default_target' => 0,
        'unit' => 'Societies',
        'icon' => 'bi-diagram-3-fill',
        'col' => 'target_farmer_society_est',
        'badge' => 'Society'
    ],
    'mobile_clinic_conducted' => [
        'title' => 'Conducting mobile clinic',
        'indicator' => 'No of mobile clinic conducted',
        'default_target' => 24,
        'unit' => 'Clinics',
        'icon' => 'bi-truck-front-fill',
        'col' => 'target_mobile_clinic_conducted',
        'badge' => 'Clinical'
    ],
    'exhibition_conducted' => [
        'title' => 'Conducting exhibition',
        'indicator' => 'No of exhibition Conducted',
        'default_target' => 3,
        'unit' => 'Exhibitions',
        'icon' => 'bi-megaphone-fill',
        'col' => 'target_exhibition_conducted',
        'badge' => 'Outreach'
    ],
    'field_days_conducted' => [
        'title' => 'Conducting field days',
        'indicator' => 'No of field days conducted',
        'default_target' => 50,
        'unit' => 'Field Days',
        'icon' => 'bi-sun-fill',
        'col' => 'target_field_days_conducted',
        'badge' => 'Outreach'
    ],
    'poultry_farm_reg' => [
        'title' => 'Poultry farm registration',
        'indicator' => 'No of poultry farm registered',
        'default_target' => 100,
        'unit' => 'Farms',
        'icon' => 'bi-egg-fill',
        'col' => 'target_poultry_farm_reg',
        'badge' => 'Registration'
    ],
    'cattle_farm_reg' => [
        'title' => 'Cattle farm registration',
        'indicator' => 'No of cattle farm registered',
        'default_target' => 100,
        'unit' => 'Farms',
        'icon' => 'bi-shield-shaded',
        'col' => 'target_cattle_farm_reg',
        'badge' => 'Registration'
    ],
    'new_farm_reg' => [
        'title' => 'New farm registration',
        'indicator' => 'No of New farm registered',
        'default_target' => 12,
        'unit' => 'Farms',
        'icon' => 'bi-patch-plus-fill',
        'col' => 'target_new_farm_reg',
        'badge' => 'Registration'
    ],
    'monthly_reports' => [
        'title' => 'Monthly reports',
        'indicator' => 'No of Monthly report submitted',
        'default_target' => 12,
        'unit' => 'Reports',
        'icon' => 'bi-file-earmark-check-fill',
        'col' => 'target_monthly_reports',
        'badge' => 'Reporting'
    ],
    'animal_identification' => [
        'title' => 'Animal Identification',
        'indicator' => 'No. of Animal Identification done',
        'default_target' => 100,
        'unit' => 'Animals',
        'icon' => 'bi-tag-fill',
        'col' => 'target_animal_identification',
        'badge' => 'Livestock'
    ],
    'data_collection' => [
        'title' => 'Data Collection',
        'indicator' => 'No of Data Collection program done',
        'default_target' => 5,
        'unit' => 'Programs',
        'icon' => 'bi-clipboard-data-fill',
        'col' => 'target_data_collection',
        'badge' => 'M&E'
    ],
];

// 3. Query Extension Targets for Year & Range
$ext_targets_raw = [];
$ext_tgt_stmt = $mysqli->prepare("SELECT * FROM annual_extension_targets WHERE range_id = ? AND year = ?");
if ($ext_tgt_stmt) {
    $ext_tgt_stmt->bind_param("ii", $range_id, $selected_year);
    $ext_tgt_stmt->execute();
    $ext_targets_raw = $ext_tgt_stmt->get_result()->fetch_assoc() ?: [];
    $ext_tgt_stmt->close();
}

// 4. Query Achievements from Logged Records
$ext_achievements = [];
$ext_ach_stmt = $mysqli->prepare("
    SELECT indicator_code, SUM(quantity) as total_achieved 
    FROM extension_service_records 
    WHERE range_id = ? AND report_year = ? 
    GROUP BY indicator_code
");
if ($ext_ach_stmt) {
    $ext_ach_stmt->bind_param("ii", $range_id, $selected_year);
    $ext_ach_stmt->execute();
    $ext_ach_res = $ext_ach_stmt->get_result();
    while ($row = $ext_ach_res->fetch_assoc()) {
        $ext_achievements[$row['indicator_code']] = intval($row['total_achieved']);
    }
    $ext_ach_stmt->close();
}

// 5. Query Detailed Activity Records
$ext_records = [];
$ext_rec_stmt = $mysqli->prepare("
    SELECT * FROM extension_service_records 
    WHERE range_id = ? AND report_year = ? 
    ORDER BY activity_date DESC, id DESC
");
if ($ext_rec_stmt) {
    $ext_rec_stmt->bind_param("ii", $range_id, $selected_year);
    $ext_rec_stmt->execute();
    $ext_records = $ext_rec_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $ext_rec_stmt->close();
}

// 6. Compute Totals & Progress
$grand_ext_target = 0;
$grand_ext_achieved = 0;
$active_streams_count = 0;

foreach ($extension_indicators as $code => $meta) {
    $col = $meta['col'];
    $t_val = isset($ext_targets_raw[$col]) ? intval($ext_targets_raw[$col]) : $meta['default_target'];
    $a_val = $ext_achievements[$code] ?? 0;
    $grand_ext_target += $t_val;
    $grand_ext_achieved += $a_val;
    if ($t_val > 0 || $a_val > 0) {
        $active_streams_count++;
    }
}
$grand_ext_pct = ($grand_ext_target > 0) ? round(($grand_ext_achieved / $grand_ext_target) * 100, 1) : 0;

// Flash messaging
$flash_msg = $_SESSION['msg'] ?? $_GET['msg'] ?? null;
$flash_status = $_SESSION['msg_type'] ?? $_GET['status'] ?? 'info';
unset($_SESSION['msg'], $_SESSION['msg_type']);

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">

<style>
    .kpi-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border-radius: 12px;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08) !important;
    }
    .progress-bar-animated {
        transition: width 0.6s ease;
    }
    .indicator-icon-box {
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
    }
</style>

<div class="container-fluid px-4 py-4">

    <!-- PAGE HEADER -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge px-3 py-1 text-light fs-6" style="background-color: #185dbd;">
                    <i class="bi bi-people-fill me-1"></i> Extension Services
                </span>
                <h3 class="fw-bold mb-0 text-dark">Extension Services & Field Outreach</h3>
            </div>
            <p class="text-muted small mb-0">
                Annual target configurations, field activity deployment, and performance indicators tracking for 
                <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?>)
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <form method="GET" class="d-flex align-items-center gap-2 m-0">
                <?php if ($range_id): ?>
                    <input type="hidden" name="range_id" value="<?= htmlspecialchars($range_id) ?>">
                <?php endif; ?>
                <label class="small fw-bold text-muted mb-0">Year:</label>
                <select name="year" class="form-select form-select-sm fw-bold border-secondary shadow-xs" onchange="this.form.submit()" style="width: 105px;">
                    <?php
                    $curr_year = intval(date('Y'));
                    for ($y = $curr_year - 4; $y <= $curr_year + 2; $y++) {
                        $sel = ($y === $selected_year) ? 'selected' : '';
                        echo "<option value=\"$y\" $sel>$y</option>";
                    }
                    ?>
                </select>
            </form>

            <button class="btn btn-sm btn-outline-primary shadow-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#modalConfigureExtensionTargets">
                <i class="bi bi-gear-fill me-1"></i> Configure Targets
            </button>
            <button class="btn btn-sm text-light shadow-sm text-nowrap" style="background-color: #185dbd;" data-bs-toggle="modal" data-bs-target="#modalLogExtensionService" onclick="prepareAddExtensionModal()">
                <i class="bi bi-plus-circle-fill me-1"></i> Log Activity
            </button>
            <a href="annual_targets.php?year=<?= $selected_year ?><?= $range_id ? '&range_id=' . $range_id : '' ?>" class="btn btn-sm btn-secondary shadow-sm text-nowrap">
                <i class="bi bi-arrow-left me-1"></i> Back to Annual Targets
            </a>
        </div>
    </div>

    <!-- FLASH MESSAGE -->
    <?php if (!empty($flash_msg)): ?>
        <div class="alert <?= ($flash_status === 'error' || $flash_status === 'danger') ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show shadow-sm py-2 px-3 small d-flex align-items-center mb-4" role="alert">
            <i class="bi <?= ($flash_status === 'error' || $flash_status === 'danger') ? 'bi-exclamation-triangle-fill text-danger' : 'bi-check-circle-fill text-success' ?> fs-5 me-2"></i>
            <div><?= htmlspecialchars($flash_msg) ?></div>
            <button type="button" class="btn-close ms-auto py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- 4 KPI SUMMARY CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card kpi-card shadow-sm border-0 border-start border-primary border-4 bg-white h-100">
                <div class="card-body py-3 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small text-uppercase fw-bold">Total Annual Targets</span>
                        <div class="indicator-icon-box bg-primary-subtle text-primary">
                            <i class="bi bi-bullseye fs-5"></i>
                        </div>
                    </div>
                    <h3 class="mb-0 fw-bold text-primary mt-1"><?= number_format($grand_ext_target) ?></h3>
                    <small class="text-muted">Target units across 15 streams</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card kpi-card shadow-sm border-0 border-start border-success border-4 bg-white h-100">
                <div class="card-body py-3 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small text-uppercase fw-bold">Total Progress Logged</span>
                        <div class="indicator-icon-box bg-success-subtle text-success">
                            <i class="bi bi-check2-circle fs-5"></i>
                        </div>
                    </div>
                    <h3 class="mb-0 fw-bold text-success mt-1"><?= number_format($grand_ext_achieved) ?></h3>
                    <small class="text-muted">Completed field activities</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card kpi-card shadow-sm border-0 border-start border-info border-4 bg-white h-100">
                <div class="card-body py-3 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small text-uppercase fw-bold">Overall Delivery Rate</span>
                        <div class="indicator-icon-box bg-info-subtle text-info">
                            <i class="bi bi-speedometer2 fs-5"></i>
                        </div>
                    </div>
                    <h3 class="mb-0 fw-bold text-info mt-1"><?= $grand_ext_pct ?>%</h3>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar bg-info progress-bar-animated" style="width: <?= min(100, $grand_ext_pct) ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card kpi-card shadow-sm border-0 border-start border-dark border-4 bg-white h-100">
                <div class="card-body py-3 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small text-uppercase fw-bold">Active Performance Streams</span>
                        <div class="indicator-icon-box bg-light border text-dark">
                            <i class="bi bi-diagram-3-fill fs-5"></i>
                        </div>
                    </div>
                    <h3 class="mb-0 fw-bold text-dark mt-1"><?= count($extension_indicators) ?></h3>
                    <small class="text-muted"><?= $active_streams_count ?> streams configured with targets/data</small>
                </div>
            </div>
        </div>
    </div>

    <!-- 1. TARGETS & PERFORMANCE MATRIX TABLE -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-header bg-white py-3 px-4 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-grid-3x3-gap-fill me-2 text-primary"></i> Extension Targets & Performance Matrix (Year <?= $selected_year ?>)
                </h5>
                <small class="text-muted">Target allocation and real-time completion status for all 15 extension service indicators</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="input-group input-group-sm" style="max-width: 260px;">
                    <span class="input-group-text bg-light border-secondary"><i class="bi bi-search"></i></span>
                    <input type="text" id="extensionMatrixFilter" class="form-control form-control-sm border-secondary" placeholder="Search indicator stream...">
                </div>
            </div>
        </div>
        <div class="table-responsive px-4 pb-3">
            <table id="extensionMatrixTable" class="table table-hover table-bordered align-middle small bg-white text-dark m-0 w-100">
                <thead style="background-color: #e9eff8; color: #103c75;">
                    <tr>
                        <th style="width: 240px;">Activity Stream</th>
                        <th>Performance Indicator</th>
                        <th class="text-center" style="width: 110px;">Target</th>
                        <th class="text-center" style="width: 110px;">Achieved</th>
                        <th class="text-center" style="width: 150px;">Delivery Progress</th>
                        <th class="text-center" style="width: 100px;">Balance</th>
                        <th class="text-center" style="width: 130px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($extension_indicators as $code => $meta): 
                        $col = $meta['col'];
                        $t_qty = isset($ext_targets_raw[$col]) ? intval($ext_targets_raw[$col]) : $meta['default_target'];
                        $a_qty = $ext_achievements[$code] ?? 0;
                        
                        if ($t_qty > 0) {
                            $pct = round(($a_qty / $t_qty) * 100, 1);
                            $bal = max(0, $t_qty - $a_qty);
                        } else {
                            $pct = ($a_qty > 0) ? 100 : 0;
                            $bal = 0;
                        }
                        $rate_badge = ($pct >= 100) ? 'bg-success' : (($pct >= 50) ? 'bg-primary' : (($pct > 0) ? 'bg-warning text-dark' : 'bg-secondary'));
                    ?>
                        <tr>
                            <td class="fw-bold">
                                <i class="bi <?= $meta['icon'] ?> me-2 text-primary"></i>
                                <?= htmlspecialchars($meta['title']) ?>
                                <span class="badge bg-light text-muted border ms-1 small"><?= $meta['badge'] ?></span>
                            </td>
                            <td class="text-muted">
                                <?= htmlspecialchars($meta['indicator']) ?>
                            </td>
                            <td class="text-center fw-bold">
                                <?php if ($t_qty > 0): ?>
                                    <span class="badge bg-light text-dark border fs-6"><?= number_format($t_qty) ?></span>
                                    <small class="text-muted d-block"><?= $meta['unit'] ?></small>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border fs-6">--</span>
                                    <small class="text-muted d-block">Open / Demand</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-bold text-success fs-6">
                                <?= number_format($a_qty) ?>
                                <small class="text-muted d-block"><?= $meta['unit'] ?></small>
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 6px; max-width: 80px;">
                                        <div class="progress-bar <?= ($pct >= 100) ? 'bg-success' : 'bg-primary' ?>" style="width: <?= min(100, $pct) ?>%;"></div>
                                    </div>
                                    <span class="badge <?= $rate_badge ?>"><?= $pct ?>%</span>
                                </div>
                            </td>
                            <td class="text-center fw-semibold <?= ($bal > 0) ? 'text-danger' : 'text-muted' ?>">
                                <?= ($t_qty > 0) ? number_format($bal) : '--' ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-xs btn-outline-primary log-extension-for-indicator-btn"
                                        data-indicator="<?= $code ?>"
                                        data-title="<?= htmlspecialchars($meta['title']) ?>"
                                        title="Log Progress for <?= htmlspecialchars($meta['title']) ?>">
                                    <i class="bi bi-plus-circle me-1"></i>Log Progress
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot style="background-color: #f8fafc;" class="fw-bold">
                    <tr>
                        <td colspan="2" class="text-uppercase"><i class="bi bi-calculator text-primary me-1"></i>Grand Total Extension Targets</td>
                        <td class="text-center fs-6 text-dark"><?= number_format($grand_ext_target) ?></td>
                        <td class="text-center fs-6 text-success"><?= number_format($grand_ext_achieved) ?></td>
                        <td class="text-center">
                            <span class="badge bg-primary fs-6"><?= $grand_ext_pct ?>%</span>
                        </td>
                        <td class="text-center text-danger"><?= number_format(max(0, $grand_ext_target - $grand_ext_achieved)) ?></td>
                        <td class="text-center">
                            <button class="btn btn-xs btn-dark" data-bs-toggle="modal" data-bs-target="#modalConfigureExtensionTargets">
                                <i class="bi bi-gear-fill me-1"></i>Edit Targets
                            </button>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- 2. DETAILED ACTIVITY RECORDS TABLE -->
    <div class="card border-0 shadow-sm rounded-3 bg-white mb-5">
        <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-card-checklist me-2 text-primary"></i> Detailed Extension Activity Records (Year <?= $selected_year ?>)
                </h5>
                <small class="text-muted">Chronological log of verified extension events, training programs, registrations, and field services</small>
            </div>
            <span class="badge bg-light text-dark border px-3 py-2"><?= count($ext_records) ?> Logged Entries</span>
        </div>
        <div class="table-responsive px-4 pb-4">
            <table id="extensionRecordsTable" class="table table-striped table-hover table-bordered align-middle small bg-white text-dark m-0 w-100">
                <thead style="background-color: #e9eff8; color: #103c75;">
                    <tr>
                        <th style="width: 45px;">#</th>
                        <th style="width: 110px;">Date</th>
                        <th style="width: 70px;" class="text-center">Month</th>
                        <th>Activity & Indicator Stream</th>
                        <th class="text-center" style="width: 100px;">Qty / Count</th>
                        <th>Beneficiary / Organization</th>
                        <th>Location / Venue</th>
                        <th>Remarks</th>
                        <th class="text-center" style="width: 110px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($ext_records)): ?>
                        <?php 
                        $ecnt = 1;
                        foreach ($ext_records as $rec): 
                            $icode = $rec['indicator_code'];
                            $imeta = $extension_indicators[$icode] ?? null;
                            $m_name = date('M', mktime(0, 0, 0, intval($rec['report_month']), 10));
                        ?>
                            <tr>
                                <td><?= $ecnt++ ?></td>
                                <td class="fw-bold text-nowrap"><i class="bi bi-calendar-event me-1 text-primary"></i><?= date('d M Y', strtotime($rec['activity_date'])) ?></td>
                                <td class="text-center"><span class="badge bg-light text-dark border"><?= $m_name ?></span></td>
                                <td>
                                    <strong><?= htmlspecialchars($rec['indicator_name']) ?></strong>
                                    <?php if ($imeta): ?>
                                        <span class="badge bg-light text-muted border ms-1 small"><?= $imeta['badge'] ?></span>
                                        <div class="text-muted small"><?= htmlspecialchars($imeta['indicator']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center fw-bold text-success fs-6 font-monospace">
                                    <?= number_format($rec['quantity']) ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($rec['beneficiary_name'] ?: '-') ?></strong>
                                </td>
                                <td class="small"><?= htmlspecialchars($rec['location'] ?: '-') ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($rec['remarks'] ?: '-') ?></td>
                                <td class="text-center">
                                    <button class="btn btn-xs btn-outline-primary edit-extension-btn me-1"
                                            data-id="<?= $rec['id'] ?>"
                                            data-date="<?= htmlspecialchars($rec['activity_date']) ?>"
                                            data-month="<?= intval($rec['report_month']) ?>"
                                            data-indicator="<?= htmlspecialchars($rec['indicator_code']) ?>"
                                            data-quantity="<?= intval($rec['quantity']) ?>"
                                            data-beneficiary="<?= htmlspecialchars($rec['beneficiary_name'] ?? '') ?>"
                                            data-location="<?= htmlspecialchars($rec['location'] ?? '') ?>"
                                            data-remarks="<?= htmlspecialchars($rec['remarks'] ?? '') ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <form action="processors/extension_services_crud.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this extension service record?');">
                                        <input type="hidden" name="action" value="delete_record">
                                        <input type="hidden" name="id" value="<?= $rec['id'] ?>">
                                        <input type="hidden" name="year" value="<?= htmlspecialchars($selected_year) ?>">
                                        <input type="hidden" name="range_id" value="<?= htmlspecialchars($range_id) ?>">
                                        <button type="submit" class="btn btn-xs btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ───────────────────────────────────────────────────────────────────────── -->
<!-- MODAL 1: CONFIGURE EXTENSION SERVICES ANNUAL TARGETS                      -->
<!-- ───────────────────────────────────────────────────────────────────────── -->
<div class="modal fade" id="modalConfigureExtensionTargets" tabindex="-1" aria-labelledby="modalConfigureExtensionTargetsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header py-2 text-light" style="background: linear-gradient(135deg, #103c75 0%, #185dbd 100%);">
                <h6 class="modal-title fw-bold" id="modalConfigureExtensionTargetsLabel">
                    <i class="bi bi-gear-fill me-2 text-warning"></i> Configure Extension Services Annual Targets
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/extension_services_crud.php" method="POST" id="formExtensionTargets">
                <input type="hidden" name="action" value="save_targets">
                <input type="hidden" name="year" value="<?= htmlspecialchars($selected_year) ?>">
                <input type="hidden" name="range_id" value="<?= htmlspecialchars($range_id) ?>">

                <div class="modal-body p-4 bg-light">
                    <div class="alert alert-info py-2 px-3 small mb-3 border-0 shadow-xs">
                        <i class="bi bi-info-circle-fill me-1"></i> Configure annual target values for <strong><?= htmlspecialchars($range_name) ?></strong> for <strong>Year <?= $selected_year ?></strong>.
                    </div>

                    <div class="row g-3">
                        <?php foreach ($extension_indicators as $code => $meta): 
                            $col = $meta['col'];
                            $val = isset($ext_targets_raw[$col]) ? intval($ext_targets_raw[$col]) : $meta['default_target'];
                        ?>
                            <div class="col-md-6">
                                <div class="p-3 bg-white border rounded h-100 shadow-2xs">
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="bi <?= $meta['icon'] ?> me-2 text-primary"></i>
                                        <label class="form-label small fw-bold text-dark mb-0"><?= htmlspecialchars($meta['title']) ?></label>
                                    </div>
                                    <div class="text-muted small mb-2"><?= htmlspecialchars($meta['indicator']) ?></div>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="<?= $col ?>" class="form-control form-control-sm text-end fw-bold" value="<?= $val ?>" min="0" required>
                                        <span class="input-group-text bg-light text-muted"><?= $meta['unit'] ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="modal-footer py-2 border-top-0 bg-white">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm px-4 shadow-sm text-light fw-bold" style="background-color: #185dbd;">
                        <i class="bi bi-check-circle me-1"></i> Save Annual Targets
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ───────────────────────────────────────────────────────────────────────── -->
<!-- MODAL 2: LOG / RECORD EXTENSION SERVICE ACTIVITY                         -->
<!-- ───────────────────────────────────────────────────────────────────────── -->
<div class="modal fade" id="modalLogExtensionService" tabindex="-1" aria-labelledby="modalLogExtensionServiceLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header py-2 text-light" style="background: linear-gradient(135deg, #103c75 0%, #185dbd 100%);">
                <h6 class="modal-title fw-bold" id="extensionModalTitle">
                    <i class="bi bi-plus-circle-fill me-2 text-warning"></i> Log Extension Service Activity
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/extension_services_crud.php" method="POST" id="formLogExtensionService">
                <input type="hidden" name="action" id="ext_form_action" value="create_record">
                <input type="hidden" name="id" id="ext_form_id" value="">
                <input type="hidden" name="year" value="<?= htmlspecialchars($selected_year) ?>">
                <input type="hidden" name="range_id" value="<?= htmlspecialchars($range_id) ?>">

                <div class="modal-body p-4 bg-white">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Activity Date <span class="text-danger">*</span></label>
                            <input type="date" name="activity_date" id="ext_form_date" class="form-control form-control-sm border-secondary" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Report Month <span class="text-danger">*</span></label>
                            <select name="report_month" id="ext_form_month" class="form-select form-select-sm border-secondary" required>
                                <?php
                                $cur_m = intval(date('n'));
                                for ($m = 1; $m <= 12; $m++) {
                                    $m_name = date('F', mktime(0, 0, 0, $m, 10));
                                    $sel = ($m === $cur_m) ? 'selected' : '';
                                    echo "<option value=\"$m\" $sel>$m - $m_name</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold text-primary">Target Activity & Indicator Stream <span class="text-danger">*</span></label>
                            <select name="indicator_code" id="ext_form_indicator" class="form-select form-select-sm border-primary" required>
                                <option value="" disabled selected>-- Select Indicator Stream --</option>
                                <?php foreach ($extension_indicators as $code => $meta): ?>
                                    <option value="<?= $code ?>">
                                        <?= htmlspecialchars($meta['title']) ?> (Indicator: <?= htmlspecialchars($meta['indicator']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Quantity / Number Completed <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="ext_form_quantity" class="form-control form-control-sm border-secondary" min="1" placeholder="e.g. 5" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Beneficiary / Society / Organization</label>
                            <input type="text" name="beneficiary_name" id="ext_form_beneficiary" class="form-control form-control-sm border-secondary" placeholder="e.g. Kantalai Dairy Society / K. Perera">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Location / GN Division / Venue</label>
                            <input type="text" name="location" id="ext_form_location" class="form-control form-control-sm border-secondary" placeholder="e.g. District Training Center / GN 05">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Remarks / Activity Notes</label>
                            <input type="text" name="remarks" id="ext_form_remarks" class="form-control form-control-sm border-secondary" placeholder="e.g. Conducted successfully under PSDG">
                        </div>
                    </div>
                </div>

                <div class="modal-footer py-2 border-top-0 bg-light">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm px-4 shadow-sm text-light fw-bold" style="background-color: #185dbd;">
                        <i class="bi bi-check-circle me-1"></i> Save Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // 1. Matrix DataTable
    const extMatrixTable = $('#extensionMatrixTable').DataTable({
        responsive: true,
        pageLength: 15,
        ordering: false,
        lengthChange: false
    });

    $('#extensionMatrixFilter').on('keyup', function() {
        extMatrixTable.search(this.value).draw();
    });

    // 2. Records DataTable
    const extRecordsTable = $('#extensionRecordsTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[1, 'desc']],
        language: {
            emptyTable: "No extension service records logged for Year <?= $selected_year ?> yet."
        }
    });

    // 3. Date & Month Sync
    $('#ext_form_date').on('change', function() {
        const val = $(this).val();
        if (val) {
            const m = parseInt(val.split('-')[1], 10);
            if (m >= 1 && m <= 12) {
                $('#ext_form_month').val(m);
            }
        }
    });

    // 4. Quick Log button from matrix row
    $(document).on('click', '.log-extension-for-indicator-btn', function(e) {
        e.preventDefault();
        const code = $(this).data('indicator');
        prepareAddExtensionModal();
        $('#ext_form_indicator').val(code);
        const bsModal = new bootstrap.Modal(document.getElementById('modalLogExtensionService'));
        bsModal.show();
    });

    // 5. Edit Record button
    $(document).on('click', '.edit-extension-btn', function(e) {
        e.preventDefault();
        const d = $(this).data();
        const $m = $('#modalLogExtensionService');

        $m.find('#extensionModalTitle').html('<i class="bi bi-pencil-square me-2 text-warning"></i> Edit Extension Service Record');
        $m.find('#ext_form_action').val('update_record');
        $m.find('#ext_form_id').val(d.id);
        $m.find('#ext_form_date').val(d.date);
        $m.find('#ext_form_month').val(d.month);
        $m.find('#ext_form_indicator').val(d.indicator);
        $m.find('#ext_form_quantity').val(d.quantity);
        $m.find('#ext_form_beneficiary').val(d.beneficiary);
        $m.find('#ext_form_location').val(d.location);
        $m.find('#ext_form_remarks').val(d.remarks);

        const bsModal = new bootstrap.Modal(document.getElementById('modalLogExtensionService'));
        bsModal.show();
    });
});

function prepareAddExtensionModal() {
    const $m = $('#modalLogExtensionService');
    $m.find('#extensionModalTitle').html('<i class="bi bi-plus-circle-fill me-2 text-warning"></i> Log Extension Service Activity');
    $m.find('#ext_form_action').val('create_record');
    $m.find('#ext_form_id').val('');
    $m.find('#ext_form_date').val(new Date().toISOString().split('T')[0]);
    $m.find('#ext_form_month').val(new Date().getMonth() + 1);
    $m.find('#ext_form_indicator').val('');
    $m.find('#ext_form_quantity').val('');
    $m.find('#ext_form_beneficiary').val('');
    $m.find('#ext_form_location').val('');
    $m.find('#ext_form_remarks').val('');
}
</script>

<?php require_once '../../../includes/footer.php'; ?>
