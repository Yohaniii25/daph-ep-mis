<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

// 1. Session and Role Clearance Profile
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

$range_name = 'Your Range';
$district_name = 'Your District';

// Extract Range Name and District Name
if (!empty($range_id)) {
    $details_sql = "
        SELECT 
            vr.name AS range_name,
            d.name AS district_name
        FROM veterinary_ranges vr
        LEFT JOIN districts d ON vr.district_id = d.id
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
        }
        $details_query->close();
    }
}

// Year and Tab Navigation
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$active_tab = isset($_GET['tab']) && in_array(strtolower($_GET['tab']), ['breeding', 'production'], true) 
    ? strtolower($_GET['tab']) 
    : 'breeding';

// Notice / Flash Messages
$flash_status = $_GET['status'] ?? '';
$flash_msg    = $_GET['msg'] ?? '';

// ─────────────────────────────────────────────────────────────────────────────
// 2. DATA RETRIEVAL FOR TAB 1: BREEDING
// ─────────────────────────────────────────────────────────────────────────────
$total_ai = 0;
$total_pd = 0;
$total_calving = 0;

// Breeding annual targets
$breeding_target_ai = 0;
$breeding_target_pd = 0;
$breeding_target_calving = 0;

if (!empty($range_id)) {
    // 1. AI count
    $ai_stmt = $mysqli->prepare("SELECT COUNT(*) FROM breeding_ai_performance WHERE range_id = ? AND report_year = ?");
    if ($ai_stmt) {
        $ai_stmt->bind_param("ii", $range_id, $selected_year);
        $ai_stmt->execute();
        $ai_stmt->bind_result($total_ai);
        $ai_stmt->fetch();
        $ai_stmt->close();
    }

    // 2. PD count
    $pd_stmt = $mysqli->prepare("SELECT COUNT(*) FROM breeding_pd_performance WHERE range_id = ? AND report_year = ?");
    if ($pd_stmt) {
        $pd_stmt->bind_param("ii", $range_id, $selected_year);
        $pd_stmt->execute();
        $pd_stmt->bind_result($total_pd);
        $pd_stmt->fetch();
        $pd_stmt->close();
    }

    // 3. Calving count
    $calving_stmt = $mysqli->prepare("SELECT COUNT(*) FROM breeding_calving_performance WHERE range_id = ? AND report_year = ?");
    if ($calving_stmt) {
        $calving_stmt->bind_param("ii", $range_id, $selected_year);
        $calving_stmt->execute();
        $calving_stmt->bind_result($total_calving);
        $calving_stmt->fetch();
        $calving_stmt->close();
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 3. DATA RETRIEVAL FOR TAB 2: PRODUCTION ISSUES
// ─────────────────────────────────────────────────────────────────────────────

// Definition of the 8 production issue targets & indicators
$production_indicators = [
    'b.7' => [
        'code'         => 'b.7',
        'title'        => 'Issue of she goats',
        'full_title'   => 'Issue of she goats',
        'indicator'    => 'No. of She goats issued',
        'target_field' => 'target_issue_she_goats',
        'species'      => 'Goat',
        'unit'         => 'Animals',
        'badge_bg'     => '#fef3c7',
        'badge_color'  => '#92400e',
        'icon'         => 'bi-shield-check'
    ],
    'b.8' => [
        'code'         => 'b.8',
        'title'        => 'Issue of stud goats',
        'full_title'   => 'Issue of stud goats',
        'indicator'    => 'No. of Stud goats Issued',
        'target_field' => 'target_issue_stud_goats',
        'species'      => 'Goat',
        'unit'         => 'Animals',
        'badge_bg'     => '#fef3c7',
        'badge_color'  => '#92400e',
        'icon'         => 'bi-award'
    ],
    'b.9' => [
        'code'         => 'b.9',
        'title'        => 'Issue of Heifers/Cows',
        'full_title'   => 'Issue of Heifers/Cows',
        'indicator'    => 'No. of Issue of heifers/cows issued',
        'target_field' => 'target_issue_heifers_cows',
        'species'      => 'Cattle',
        'unit'         => 'Animals',
        'badge_bg'     => '#e0f2fe',
        'badge_color'  => '#0369a1',
        'icon'         => 'bi-check2-circle'
    ],
    'b.10' => [
        'code'         => 'b.10',
        'title'        => 'Issue of bull calves',
        'full_title'   => 'Issue of bull calves',
        'indicator'    => 'No. of bull calves issued',
        'target_field' => 'target_issue_bull_calves',
        'species'      => 'Cattle',
        'unit'         => 'Animals',
        'badge_bg'     => '#e0f2fe',
        'badge_color'  => '#0369a1',
        'icon'         => 'bi-lightning'
    ],
    'b.11' => [
        'code'         => 'b.11',
        'title'        => 'Issue of heifers(Buffalo)',
        'full_title'   => 'Issue of heifers(Buffalo)',
        'indicator'    => 'No. of heifers/cows(Buffalo) Issued',
        'target_field' => 'target_issue_buffalo_heifers',
        'species'      => 'Buffalo',
        'unit'         => 'Animals',
        'badge_bg'     => '#f3e8ff',
        'badge_color'  => '#7e22ce',
        'icon'         => 'bi-gem'
    ],
    'b.12' => [
        'code'         => 'b.12',
        'title'        => 'Issue of bull calves (Buffalo)',
        'full_title'   => 'Issue of bull calves (Buffalo)',
        'indicator'    => 'No. of bull calves(Buffalo) issued',
        'target_field' => 'target_issue_buffalo_bull_calves',
        'species'      => 'Buffalo',
        'unit'         => 'Animals',
        'badge_bg'     => '#f3e8ff',
        'badge_color'  => '#7e22ce',
        'icon'         => 'bi-star'
    ],
    'b.13' => [
        'code'         => 'b.13',
        'title'        => 'Issue of D/O Chicks',
        'full_title'   => 'Issue of D/O Chicks',
        'indicator'    => 'No. of D/O chicks issued',
        'target_field' => 'target_issue_do_chicks',
        'species'      => 'Poultry',
        'unit'         => 'Birds',
        'badge_bg'     => '#fee2e2',
        'badge_color'  => '#b91c1c',
        'icon'         => 'bi-egg'
    ],
    'b.14' => [
        'code'         => 'b.14',
        'title'        => 'Issue of M/O Chicks',
        'full_title'   => 'Issue of M/O Chicks',
        'indicator'    => 'No. of M/O chicks issued',
        'target_field' => 'target_issue_mo_chicks',
        'species'      => 'Poultry',
        'unit'         => 'Birds',
        'badge_bg'     => '#fee2e2',
        'badge_color'  => '#b91c1c',
        'icon'         => 'bi-egg-fill'
    ],
];

// Fetch Target record from annual_breeding_targets
$annual_targets_row = [];
$t_stmt = $mysqli->prepare("SELECT * FROM annual_breeding_targets WHERE range_id = ? AND year = ?");
if ($t_stmt) {
    $t_stmt->bind_param("ii", $range_id, $selected_year);
    $t_stmt->execute();
    $annual_targets_row = $t_stmt->get_result()->fetch_assoc() ?: [];
    $t_stmt->close();
}

$breeding_target_ai      = intval($annual_targets_row['target_ai_service'] ?? 0);
$breeding_target_pd      = intval($annual_targets_row['target_pd_examination'] ?? 0);
$breeding_target_calving = intval($annual_targets_row['target_calf_register'] ?? 0);

// Fetch Production Issue Achievement Summaries
$production_achieved_totals = [];
$production_achieved_monthly = [];
foreach (array_keys($production_indicators) as $code) {
    $production_achieved_totals[$code] = 0;
    $production_achieved_monthly[$code] = array_fill(1, 12, 0);
}

$ach_stmt = $mysqli->prepare("
    SELECT indicator_code, report_month, SUM(quantity) as total_qty 
    FROM production_issue_records 
    WHERE range_id = ? AND report_year = ? 
    GROUP BY indicator_code, report_month
");
if ($ach_stmt) {
    $ach_stmt->bind_param("ii", $range_id, $selected_year);
    $ach_stmt->execute();
    $ach_res = $ach_stmt->get_result();
    while ($arow = $ach_res->fetch_assoc()) {
        $c = $arow['indicator_code'];
        $m = intval($arow['report_month']);
        $q = intval($arow['total_qty']);
        if (isset($production_achieved_totals[$c])) {
            $production_achieved_totals[$c] += $q;
            $production_achieved_monthly[$c][$m] = $q;
        }
    }
    $ach_stmt->close();
}

// Fetch all individual production issue records for the table registry
$issue_records = [];
$rec_stmt = $mysqli->prepare("
    SELECT * 
    FROM production_issue_records 
    WHERE range_id = ? AND report_year = ? 
    ORDER BY issue_date DESC, id DESC
");
if ($rec_stmt) {
    $rec_stmt->bind_param("ii", $range_id, $selected_year);
    $rec_stmt->execute();
    $rec_res = $rec_stmt->get_result();
    while ($r = $rec_res->fetch_assoc()) {
        $issue_records[] = $r;
    }
    $rec_stmt->close();
}

// Totals across all 8 production indicators
$grand_total_target = 0;
$grand_total_achieved = 0;
foreach ($production_indicators as $code => $meta) {
    $t_val = intval($annual_targets_row[$meta['target_field']] ?? 0);
    $a_val = intval($production_achieved_totals[$code] ?? 0);
    $grand_total_target += $t_val;
    $grand_total_achieved += $a_val;
}
$grand_coverage_pct = ($grand_total_target > 0) ? round(($grand_total_achieved / $grand_total_target) * 100, 1) : 0;

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<style>
    .gov-card {
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .gov-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(55, 7, 9, 0.08) !important;
    }
    .nav-tabs-gov {
        border-bottom: 2px solid #e2e8f0;
    }
    .nav-tabs-gov .nav-link {
        color: #475569;
        font-weight: 600;
        font-size: 0.95rem;
        padding: 0.75rem 1.5rem;
        border: none;
        border-bottom: 3px solid transparent;
        transition: all 0.2s ease;
    }
    .nav-tabs-gov .nav-link:hover {
        color: #820100;
        border-bottom-color: #cbd5e1;
    }
    .nav-tabs-gov .nav-link.active {
        color: #820100;
        background: transparent;
        border-bottom-color: #820100;
    }
</style>

<div class="container-fluid py-4 px-3 px-md-4">

    <!-- PAGE HEADER -->
    <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge" style="background-color: #820100; color: #fff;">
                    <i class="bi bi-shield-check me-1"></i> DAPH Veterinary MIS
                </span>
                <span class="badge bg-light text-dark border">Year <?= $selected_year ?></span>
            </div>
            <h2 class="h4 fw-bold mb-1" style="color: #370709;">Breeding and Production</h2>
            <p class="text-muted small mb-0">
                Performance tracking, Artificial Insemination, and Production issue indicators for 
                <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District)
            </p>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <form method="GET" class="d-flex align-items-center gap-2">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
                <?php if ($range_id): ?>
                    <input type="hidden" name="range_id" value="<?= htmlspecialchars($range_id) ?>">
                <?php endif; ?>
                <label class="small fw-bold text-muted mb-0">Year:</label>
                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 105px;">
                    <?php
                    $curr_year = intval(date('Y'));
                    for ($y = $curr_year - 5; $y <= $curr_year + 5; $y++) {
                        $sel = ($y === $selected_year) ? 'selected' : '';
                        echo "<option value=\"$y\" $sel>$y</option>";
                    }
                    ?>
                </select>
            </form>
            <a href="annual_targets.php" class="btn btn-sm btn-secondary shadow-sm text-nowrap">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <!-- FLASH ALERTS -->
    <?php if (!empty($flash_msg)): ?>
        <div class="alert <?= ($flash_status === 'error') ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show shadow-sm py-2 px-3 small d-flex align-items-center mb-4" role="alert">
            <i class="bi <?= ($flash_status === 'error') ? 'bi-exclamation-triangle-fill text-danger' : 'bi-check-circle-fill text-success' ?> fs-5 me-2"></i>
            <div><?= htmlspecialchars($flash_msg) ?></div>
            <button type="button" class="btn-close ms-auto py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- NAVIGATION TABS -->
    <ul class="nav nav-tabs nav-tabs-gov mb-4" id="breedingProductionTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= ($active_tab === 'breeding') ? 'active' : '' ?>" 
               id="tab-btn-breeding" 
               href="?tab=breeding&year=<?= $selected_year ?>&range_id=<?= $range_id ?>">
                <i class="bi bi-gender-ambiguous me-2"></i> Breeding
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= ($active_tab === 'production') ? 'active' : '' ?>" 
               id="tab-btn-production" 
               href="?tab=production&year=<?= $selected_year ?>&range_id=<?= $range_id ?>">
                <i class="bi bi-box-seam me-2"></i> Production
                <?php if ($grand_total_achieved > 0): ?>
                    <span class="badge bg-warning text-dark ms-1"><?= number_format($grand_total_achieved) ?></span>
                <?php endif; ?>
            </a>
        </li>
    </ul>

    <!-- TAB CONTENTS -->
    <div class="tab-content" id="breedingProductionTabsContent">

        <!-- ───────────────────────────────────────────────────────────────── -->
        <!-- TAB 1: BREEDING MODULE DETAILS                                    -->
        <!-- ───────────────────────────────────────────────────────────────── -->
        <div class="tab-pane fade <?= ($active_tab === 'breeding') ? 'show active' : '' ?>" id="tab-breeding" role="tabpanel">

            <!-- STATS CARD GROUP -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-4">
                    <div class="card gov-card shadow-sm border-0 border-start border-success border-4 bg-white">
                        <div class="card-body py-3">
                            <span class="text-muted small text-uppercase fw-bold">Total AI Performed</span>
                            <h4 class="mb-0 fw-bold text-success mt-1"><?= number_format($total_ai) ?></h4>
                            <?php if ($breeding_target_ai > 0): ?>
                                <small class="text-muted">Target: <?= number_format($breeding_target_ai) ?> (<?= round(($total_ai / $breeding_target_ai) * 100, 1) ?>%)</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-4">
                    <div class="card gov-card shadow-sm border-0 border-start border-info border-4 bg-white">
                        <div class="card-body py-3">
                            <span class="text-muted small text-uppercase fw-bold">Total PD Completed</span>
                            <h4 class="mb-0 fw-bold text-info mt-1"><?= number_format($total_pd) ?></h4>
                            <?php if ($breeding_target_pd > 0): ?>
                                <small class="text-muted">Target: <?= number_format($breeding_target_pd) ?> (<?= round(($total_pd / $breeding_target_pd) * 100, 1) ?>%)</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-4">
                    <div class="card gov-card shadow-sm border-0 border-start border-warning border-4 bg-white">
                        <div class="card-body py-3">
                            <span class="text-muted small text-uppercase fw-bold">Total Calvings Logged</span>
                            <h4 class="mb-0 fw-bold text-warning mt-1"><?= number_format($total_calving) ?></h4>
                            <?php if ($breeding_target_calving > 0): ?>
                                <small class="text-muted">Target: <?= number_format($breeding_target_calving) ?> (<?= round(($total_calving / $breeding_target_calving) * 100, 1) ?>%)</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- THREE ACTION PAGES LINKS -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-md-4">
                    <div class="card gov-card shadow-sm border-0 h-100 bg-white">
                        <div class="card-body p-4 d-flex flex-column justify-content-between align-items-center text-center">
                            <div class="bg-primary-subtle p-3 rounded-circle mb-3">
                                <i class="bi bi-activity text-primary display-5"></i>
                            </div>
                            <h5 class="fw-bold mb-2">Artificial Insemination</h5>
                            <p class="text-muted small mb-4">Record and manage Artificial Insemination (AI) activities: technicians, cow registry, semen codes, and AI types.</p>
                            <a href="ai_performance.php?year=<?= $selected_year ?>" class="btn btn-primary btn-sm w-100 py-2 fw-semibold">
                                <i class="bi bi-arrow-right-circle me-1"></i> Manage AI Records
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 col-md-4">
                    <div class="card gov-card shadow-sm border-0 h-100 bg-white">
                        <div class="card-body p-4 d-flex flex-column justify-content-between align-items-center text-center">
                            <div class="bg-info-subtle p-3 rounded-circle mb-3">
                                <i class="bi bi-gender-female text-info display-5"></i>
                            </div>
                            <h5 class="fw-bold mb-2">Pregnancy Diagnosis</h5>
                            <p class="text-muted small mb-4">Register pregnancy diagnosis tests: test dates, pregnant/non-pregnant results, and linking with initial AI date.</p>
                            <a href="pd_performance.php?year=<?= $selected_year ?>" class="btn btn-info text-white btn-sm w-100 py-2 fw-semibold">
                                <i class="bi bi-arrow-right-circle me-1"></i> Manage PD Records
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="card gov-card shadow-sm border-0 h-100 bg-white">
                        <div class="card-body p-4 d-flex flex-column justify-content-between align-items-center text-center">
                            <div class="bg-warning-subtle p-3 rounded-circle mb-3">
                                <i class="bi bi-clipboard-plus text-warning display-5"></i>
                            </div>
                            <h5 class="fw-bold mb-2">Calving Performance</h5>
                            <p class="text-muted small mb-4">Track calf registration logs: mapping parent cow, semen code details, calving date, sex, and calf ID tracking.</p>
                            <a href="calving_performance.php?year=<?= $selected_year ?>" class="btn btn-warning text-white btn-sm w-100 py-2 fw-semibold">
                                <i class="bi bi-arrow-right-circle me-1"></i> Manage Calving Records
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BREEDING TARGETS PROGRESS SUMMARY TABLE -->
            <div class="card gov-card shadow-sm border-0 mb-4 bg-white">
                <div class="card-header bg-white pt-4 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="fw-bold mb-1" style="color: #370709;">
                            <i class="bi bi-clipboard2-check me-2 text-danger"></i>Breeding Progress vs. Annual Targets
                        </h5>
                        <p class="text-muted small mb-0">Annual baseline coverage for Artificial Insemination, Pregnancy Diagnosis, and Calving registrations.</p>
                    </div>
                    <span class="badge bg-light text-dark border px-3 py-2">
                        <i class="bi bi-geo-alt me-1 text-danger"></i> <?= htmlspecialchars($range_name) ?>
                    </span>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle small bg-white text-dark m-0">
                            <thead style="background-color: #d4c7b7; color: #370709;">
                                <tr>
                                    <th>Breeding Activity Stream</th>
                                    <th class="text-center" style="width: 140px;">Annual Target</th>
                                    <th class="text-center" style="width: 140px;">Achieved Records</th>
                                    <th class="text-center" style="width: 180px;">Coverage Rate</th>
                                    <th class="text-center" style="width: 140px;">Remaining Balance</th>
                                    <th class="text-center" style="width: 150px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $breeding_streams = [
                                    [
                                        'name' => 'Artificial Insemination (AI)',
                                        'target' => $breeding_target_ai,
                                        'achieved' => $total_ai,
                                        'link' => "ai_performance.php?year={$selected_year}",
                                        'icon' => 'bi-activity text-primary'
                                    ],
                                    [
                                        'name' => 'Pregnancy Diagnosis (PD)',
                                        'target' => $breeding_target_pd,
                                        'achieved' => $total_pd,
                                        'link' => "pd_performance.php?year={$selected_year}",
                                        'icon' => 'bi-gender-female text-info'
                                    ],
                                    [
                                        'name' => 'Calf Registrations (Calving)',
                                        'target' => $breeding_target_calving,
                                        'achieved' => $total_calving,
                                        'link' => "calving_performance.php?year={$selected_year}",
                                        'icon' => 'bi-clipboard-plus text-warning'
                                    ]
                                ];

                                foreach ($breeding_streams as $bs):
                                    $t = $bs['target'];
                                    $a = $bs['achieved'];
                                    $rate = ($t > 0) ? round(($a / $t) * 100, 1) : 0;
                                    $bal = max(0, $t - $a);
                                    $rate_badge = ($rate >= 100) ? 'bg-success' : (($rate >= 50) ? 'bg-primary' : (($rate > 0) ? 'bg-warning text-dark' : 'bg-secondary'));
                                ?>
                                    <tr>
                                        <td class="fw-bold">
                                            <i class="bi <?= $bs['icon'] ?> me-2"></i><?= htmlspecialchars($bs['name']) ?>
                                        </td>
                                        <td class="text-center fw-bold">
                                            <span class="badge bg-light text-dark border fs-6"><?= number_format($t) ?></span>
                                        </td>
                                        <td class="text-center fw-bold text-success fs-6"><?= number_format($a) ?></td>
                                        <td class="text-center">
                                            <div class="d-flex align-items-center justify-content-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 6px; max-width: 80px;">
                                                    <div class="progress-bar <?= ($rate >= 100) ? 'bg-success' : 'bg-primary' ?>" style="width: <?= min(100, $rate) ?>%;"></div>
                                                </div>
                                                <span class="badge <?= $rate_badge ?>"><?= $rate ?>%</span>
                                            </div>
                                        </td>
                                        <td class="text-center fw-semibold <?= ($bal > 0) ? 'text-danger' : 'text-muted' ?>">
                                            <?= number_format($bal) ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= $bs['link'] ?>" class="btn btn-xs btn-outline-dark">
                                                <i class="bi bi-box-arrow-up-right me-1"></i> View Details
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <!-- ───────────────────────────────────────────────────────────────── -->
        <!-- TAB 2: PRODUCTION TARGETS & ISSUE TRACKING                        -->
        <!-- ───────────────────────────────────────────────────────────────── -->
        <div class="tab-pane fade <?= ($active_tab === 'production') ? 'show active' : '' ?>" id="tab-production" role="tabpanel">

            <!-- PRODUCTION KPI METRIC CARDS -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="card gov-card shadow-sm border-0 border-start border-danger border-4 bg-white">
                        <div class="card-body py-3">
                            <span class="text-muted small text-uppercase fw-bold">Total Issue Targets</span>
                            <h4 class="mb-0 fw-bold text-dark mt-1"><?= number_format($grand_total_target) ?></h4>
                            <small class="text-muted">Target Doses & Livestock Issues</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card gov-card shadow-sm border-0 border-start border-success border-4 bg-white">
                        <div class="card-body py-3">
                            <span class="text-muted small text-uppercase fw-bold">Total Issues Distributed</span>
                            <h4 class="mb-0 fw-bold text-success mt-1"><?= number_format($grand_total_achieved) ?></h4>
                            <small class="text-muted"><?= count($issue_records) ?> Logged Issue Sessions</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card gov-card shadow-sm border-0 border-start border-warning border-4 bg-white">
                        <div class="card-body py-3">
                            <span class="text-muted small text-uppercase fw-bold">Overall Achievement</span>
                            <h4 class="mb-0 fw-bold text-warning mt-1"><?= $grand_coverage_pct ?>%</h4>
                            <small class="text-muted">Balance: <?= number_format(max(0, $grand_total_target - $grand_total_achieved)) ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card gov-card shadow-sm border-0 border-start border-primary border-4 bg-white">
                        <div class="card-body py-3">
                            <span class="text-muted small text-uppercase fw-bold">Targeted Streams</span>
                            <h4 class="mb-0 fw-bold text-primary mt-1">8 Streams</h4>
                            <small class="text-muted">Livestock & Poultry Issues</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION: PRODUCTION ISSUE TARGETS & DEPLOYMENT MATRIX -->
            <div class="card gov-card shadow-sm border-0 mb-4 bg-white">
                <div class="card-header bg-white pt-4 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="fw-bold mb-1" style="color: #370709;">
                            <i class="bi bi-grid-3x3-gap-fill me-2 text-danger"></i>Production Issues Deployment & Target Tracking Matrix
                        </h5>
                        <p class="text-muted small mb-0">Annual target planning, distribution logs, and progressive indicators for production issues.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-outline-dark fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalConfigureProductionTargets">
                            <i class="bi bi-gear-fill me-1"></i> Configure Issue Targets
                        </button>
                        <button class="btn btn-sm text-light fw-bold shadow-sm" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#modalLogProductionIssue" onclick="prepareAddIssueModal();">
                            <i class="bi bi-plus-circle me-1"></i> Log Production Issue
                        </button>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="row mb-3">
                        <div class="col-md-4 ms-auto">
                            <input type="text" id="productionMatrixFilter" class="form-control form-control-sm border-secondary" placeholder="Search production indicators...">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="productionMatrixTable" class="table table-striped table-hover table-bordered align-middle small bg-white text-dark m-0">
                            <thead style="background-color: #d4c7b7; color: #370709;">
                                <tr>
                                    <th>Production Issue Stream</th>
                                    <th>Indicator Specification</th>
                                    <th class="text-center" style="width: 130px;">Annual Target</th>
                                    <th class="text-center" style="width: 130px;">Issued (Achieved)</th>
                                    <th class="text-center" style="min-width: 160px;">Coverage Rate</th>
                                    <th class="text-center" style="width: 130px;">Remaining Balance</th>
                                    <th class="text-center" style="width: 140px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($production_indicators as $code => $meta):
                                    $t_qty = intval($annual_targets_row[$meta['target_field']] ?? 0);
                                    $a_qty = intval($production_achieved_totals[$code] ?? 0);
                                    $bal   = max(0, $t_qty - $a_qty);
                                    $pct   = ($t_qty > 0) ? round(($a_qty / $t_qty) * 100, 1) : 0;
                                    $rate_badge = ($pct >= 100) ? 'bg-success' : (($pct >= 50) ? 'bg-primary' : (($pct > 0) ? 'bg-warning text-dark' : 'bg-secondary'));
                                ?>
                                    <tr>
                                        <td class="fw-bold">
                                            <i class="bi <?= $meta['icon'] ?> me-1 text-danger"></i>
                                            <?= htmlspecialchars($meta['full_title']) ?>
                                            <span class="badge bg-light text-muted border ms-1 small"><?= $meta['species'] ?></span>
                                        </td>
                                        <td class="text-muted">
                                            <?= htmlspecialchars($meta['indicator']) ?>
                                        </td>
                                        <td class="text-center fw-bold">
                                            <span class="badge bg-light text-dark border fs-6"><?= number_format($t_qty) ?></span>
                                        </td>
                                        <td class="text-center fw-bold text-success fs-6">
                                            <?= number_format($a_qty) ?>
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
                                            <?= number_format($bal) ?>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-xs btn-outline-danger me-1 log-issue-for-indicator-btn" 
                                                    data-indicator="<?= $code ?>" 
                                                    data-title="<?= htmlspecialchars($meta['full_title']) ?>"
                                                    title="Log Issue for <?= htmlspecialchars($meta['full_title']) ?>">
                                                <i class="bi bi-plus-circle me-1"></i>Log Issue
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot style="background-color: #f8fafc;" class="fw-bold">
                                <tr>
                                    <td colspan="2" class="text-uppercase"><i class="bi bi-calculator text-danger me-1"></i>Grand Total Production Issues</td>
                                    <td class="text-center fs-6 text-dark"><?= number_format($grand_total_target) ?></td>
                                    <td class="text-center fs-6 text-success"><?= number_format($grand_total_achieved) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-dark"><?= $grand_coverage_pct ?>%</span>
                                    </td>
                                    <td class="text-center text-danger"><?= number_format(max(0, $grand_total_target - $grand_total_achieved)) ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-xs btn-dark" data-bs-toggle="modal" data-bs-target="#modalConfigureProductionTargets">
                                            <i class="bi bi-gear-fill me-1"></i>Edit Targets
                                        </button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SECTION: RECENT PRODUCTION ISSUE & BENEFICIARY REGISTRY -->
            <div class="card gov-card shadow-sm border-0 mb-4 bg-white">
                <div class="card-header bg-white pt-4 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="fw-bold mb-1" style="color: #370709;">
                            <i class="bi bi-journal-text me-2 text-primary"></i>Production Issue & Distribution Registry
                        </h5>
                        <p class="text-muted small mb-0">Detailed event log of livestock, poultry, and animal issues distributed to farmers and groups in <?= htmlspecialchars($range_name) ?>.</p>
                    </div>
                    <button class="btn btn-sm btn-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#modalLogProductionIssue" onclick="prepareAddIssueModal();">
                        <i class="bi bi-plus-lg me-1"></i> Record New Issue
                    </button>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="table-responsive">
                        <table id="productionRecordsTable" class="table table-striped table-hover table-bordered align-middle small bg-white text-dark m-0">
                            <thead style="background-color: #d4c7b7; color: #370709;">
                                <tr>
                                    <th style="width: 45px;">#</th>
                                    <th style="width: 110px;">Issue Date</th>
                                    <th style="width: 70px;" class="text-center">Month</th>
                                    <th>Indicator / Activity Stream</th>
                                    <th class="text-center" style="width: 100px;">Qty Issued</th>
                                    <th>Beneficiary / Farmer Details</th>
                                    <th>Location / Address</th>
                                    <th>Remarks</th>
                                    <th class="text-center" style="width: 110px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($issue_records)): ?>
                                    <?php 
                                    $rcnt = 1;
                                    foreach ($issue_records as $rec): 
                                        $icode = $rec['indicator_code'];
                                        $imeta = $production_indicators[$icode] ?? null;
                                        $month_name = date('M', mktime(0, 0, 0, intval($rec['report_month']), 10));
                                    ?>
                                        <tr>
                                            <td><?= $rcnt++ ?></td>
                                            <td class="fw-bold text-nowrap"><i class="bi bi-calendar-event me-1 text-danger"></i><?= date('d M Y', strtotime($rec['issue_date'])) ?></td>
                                            <td class="text-center"><span class="badge bg-light text-dark border"><?= $month_name ?></span></td>
                                            <td>
                                                <strong><?= htmlspecialchars($rec['indicator_name']) ?></strong>
                                                <?php if ($imeta): ?>
                                                    <span class="badge bg-light text-muted border ms-1 small"><?= $imeta['species'] ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center fw-bold text-success fs-6 font-monospace">
                                                <?= number_format($rec['quantity']) ?>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($rec['beneficiary_name'] ?: 'Not Specified') ?></strong>
                                                <?php if (!empty($rec['beneficiary_nic'])): ?>
                                                    <div class="text-muted small">NIC: <?= htmlspecialchars($rec['beneficiary_nic']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small"><?= htmlspecialchars($rec['beneficiary_address'] ?: '-') ?></td>
                                            <td class="small text-muted"><?= htmlspecialchars($rec['remarks'] ?: '-') ?></td>
                                            <td class="text-center">
                                                <button class="btn btn-xs btn-outline-primary edit-issue-btn me-1"
                                                        data-id="<?= $rec['id'] ?>"
                                                        data-date="<?= htmlspecialchars($rec['issue_date']) ?>"
                                                        data-month="<?= intval($rec['report_month']) ?>"
                                                        data-indicator="<?= htmlspecialchars($rec['indicator_code']) ?>"
                                                        data-quantity="<?= intval($rec['quantity']) ?>"
                                                        data-beneficiary="<?= htmlspecialchars($rec['beneficiary_name'] ?? '') ?>"
                                                        data-nic="<?= htmlspecialchars($rec['beneficiary_nic'] ?? '') ?>"
                                                        data-address="<?= htmlspecialchars($rec['beneficiary_address'] ?? '') ?>"
                                                        data-remarks="<?= htmlspecialchars($rec['remarks'] ?? '') ?>">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <form action="processors/production_issue_crud.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this issue record?');">
                                                    <input type="hidden" name="action" value="delete">
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

        </div>

    </div>

</div>

<!-- ───────────────────────────────────────────────────────────────────────── -->
<!-- MODAL 1: CONFIGURE ANNUAL PRODUCTION ISSUE TARGETS                        -->
<!-- ───────────────────────────────────────────────────────────────────────── -->
<div class="modal fade" id="modalConfigureProductionTargets" tabindex="-1" aria-labelledby="modalConfigureProductionTargetsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header py-2 text-light" style="background: linear-gradient(135deg, #370709 0%, #820100 100%);">
                <h6 class="modal-title fw-bold" id="modalConfigureProductionTargetsLabel">
                    <i class="bi bi-gear-fill me-2 text-warning"></i> Configure Production Issue Targets
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/production_issue_crud.php" method="POST" id="formProductionTargets">
                <input type="hidden" name="action" value="save_targets">
                <input type="hidden" name="year" value="<?= htmlspecialchars($selected_year) ?>">
                <input type="hidden" name="range_id" value="<?= htmlspecialchars($range_id) ?>">

                <div class="modal-body p-4">
                    <div class="alert alert-warning py-2 px-3 small d-flex justify-content-between align-items-center mb-3 border">
                        <div>
                            <i class="bi bi-info-circle-fill text-warning me-1"></i> Target Evaluation Year: <strong><?= htmlspecialchars($selected_year) ?></strong> | Range: <strong><?= htmlspecialchars($range_name) ?></strong>
                        </div>
                        <span class="badge bg-white text-dark border">Official Production Indicators</span>
                    </div>

                    <div class="row g-3">
                        <!-- Goats -->
                        <div class="col-12">
                            <span class="badge bg-warning-subtle text-dark border border-warning px-2 py-1 mb-2">
                                <i class="bi bi-shield-check me-1 text-warning"></i> Goat Production Stream
                            </span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark mb-1">
                                Issue of she goats (Indicator: No. of She goats issued)
                            </label>
                            <input type="number" name="target_issue_she_goats" class="form-control form-control-sm border-secondary target-sum-calc" min="0" value="<?= intval($annual_targets_row['target_issue_she_goats'] ?? 0) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark mb-1">
                                Issue of stud goats (Indicator: No. of Stud goats Issued)
                            </label>
                            <input type="number" name="target_issue_stud_goats" class="form-control form-control-sm border-secondary target-sum-calc" min="0" value="<?= intval($annual_targets_row['target_issue_stud_goats'] ?? 0) ?>" required>
                        </div>

                        <!-- Cattle -->
                        <div class="col-12 mt-3">
                            <span class="badge bg-info-subtle text-dark border border-info px-2 py-1 mb-2">
                                <i class="bi bi-check2-circle me-1 text-info"></i> Cattle Production Stream
                            </span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark mb-1">
                                Issue of Heifers/Cows (Indicator: No. of Issue of heifers/cows issued)
                            </label>
                            <input type="number" name="target_issue_heifers_cows" class="form-control form-control-sm border-secondary target-sum-calc" min="0" value="<?= intval($annual_targets_row['target_issue_heifers_cows'] ?? 0) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark mb-1">
                                Issue of bull calves (Indicator: No. of bull calves issued)
                            </label>
                            <input type="number" name="target_issue_bull_calves" class="form-control form-control-sm border-secondary target-sum-calc" min="0" value="<?= intval($annual_targets_row['target_issue_bull_calves'] ?? 0) ?>" required>
                        </div>

                        <!-- Buffalo -->
                        <div class="col-12 mt-3">
                            <span class="badge bg-purple-subtle text-dark border px-2 py-1 mb-2" style="background-color: #f3e8ff;">
                                <i class="bi bi-gem me-1 text-purple"></i> Buffalo Production Stream
                            </span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark mb-1">
                                Issue of heifers(Buffalo) (Indicator: No. of heifers/cows(Buffalo) Issued)
                            </label>
                            <input type="number" name="target_issue_buffalo_heifers" class="form-control form-control-sm border-secondary target-sum-calc" min="0" value="<?= intval($annual_targets_row['target_issue_buffalo_heifers'] ?? 0) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark mb-1">
                                Issue of bull calves (Buffalo) (Indicator: No. of bull calves(Buffalo) issued)
                            </label>
                            <input type="number" name="target_issue_buffalo_bull_calves" class="form-control form-control-sm border-secondary target-sum-calc" min="0" value="<?= intval($annual_targets_row['target_issue_buffalo_bull_calves'] ?? 0) ?>" required>
                        </div>

                        <!-- Poultry -->
                        <div class="col-12 mt-3">
                            <span class="badge bg-danger-subtle text-dark border border-danger px-2 py-1 mb-2">
                                <i class="bi bi-egg me-1 text-danger"></i> Poultry Production Stream
                            </span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark mb-1">
                                Issue of D/O Chicks (Indicator: No. of D/O chicks issued)
                            </label>
                            <input type="number" name="target_issue_do_chicks" class="form-control form-control-sm border-secondary target-sum-calc" min="0" value="<?= intval($annual_targets_row['target_issue_do_chicks'] ?? 0) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark mb-1">
                                Issue of M/O Chicks (Indicator: No. of M/O chicks issued)
                            </label>
                            <input type="number" name="target_issue_mo_chicks" class="form-control form-control-sm border-secondary target-sum-calc" min="0" value="<?= intval($annual_targets_row['target_issue_mo_chicks'] ?? 0) ?>" required>
                        </div>
                    </div>
                </div>

                <div class="modal-footer py-2 border-top-0 bg-light">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm px-4 shadow-sm text-light fw-bold" style="background-color: #820100;">
                        <i class="bi bi-check-circle me-1"></i> Save Production Targets
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ───────────────────────────────────────────────────────────────────────── -->
<!-- MODAL 2: LOG PRODUCTION ISSUE DATA ENTRY MODAL                            -->
<!-- ───────────────────────────────────────────────────────────────────────── -->
<div class="modal fade" id="modalLogProductionIssue" tabindex="-1" aria-labelledby="modalLogProductionIssueLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header py-2 text-light" style="background: linear-gradient(135deg, #370709 0%, #820100 100%);">
                <h6 class="modal-title fw-bold" id="modalLogProductionIssueLabel">
                    <i class="bi bi-clipboard2-plus-fill me-2 text-warning"></i> <span id="issueModalTitle">Log Production Issue Event</span>
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/production_issue_crud.php" method="POST" id="formLogProductionIssue">
                <input type="hidden" name="action" id="issue_form_action" value="create">
                <input type="hidden" name="id" id="issue_form_id" value="">
                <input type="hidden" name="year" value="<?= htmlspecialchars($selected_year) ?>">
                <input type="hidden" name="range_id" value="<?= htmlspecialchars($range_id) ?>">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Issue Date <span class="text-danger">*</span></label>
                            <input type="date" name="issue_date" id="issue_form_date" class="form-control form-control-sm border-secondary" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Report Month <span class="text-danger">*</span></label>
                            <select name="report_month" id="issue_form_month" class="form-select form-select-sm border-secondary" required>
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
                            <label class="form-label small fw-bold text-danger">Production Indicator & Stream <span class="text-danger">*</span></label>
                            <select name="indicator_code" id="issue_form_indicator" class="form-select form-select-sm border-danger" required>
                                <option value="" disabled selected>-- Select Production Indicator Stream --</option>
                                <?php foreach ($production_indicators as $code => $meta): ?>
                                    <option value="<?= $code ?>">
                                        <?= htmlspecialchars($meta['full_title']) ?> (<?= htmlspecialchars($meta['indicator']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Quantity Issued <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="issue_form_quantity" class="form-control form-control-sm border-secondary" min="1" placeholder="e.g. 5" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Beneficiary / Recipient Name</label>
                            <input type="text" name="beneficiary_name" id="issue_form_beneficiary" class="form-control form-control-sm border-secondary" placeholder="e.g. K. Perera or Women Agri Society">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Beneficiary NIC</label>
                            <input type="text" name="beneficiary_nic" id="issue_form_nic" class="form-control form-control-sm border-secondary" placeholder="e.g. 198512345678">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Village / Distribution Location</label>
                            <input type="text" name="beneficiary_address" id="issue_form_address" class="form-control form-control-sm border-secondary" placeholder="e.g. Sammanthurai GN 04">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold text-dark">Remarks / Distribution Notes</label>
                            <textarea name="remarks" id="issue_form_remarks" class="form-control form-control-sm border-secondary" rows="2" placeholder="e.g. Distributed under Province Livestock Subsidy Scheme"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer py-2 border-top-0 bg-light">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm px-4 shadow-sm text-light fw-bold" style="background-color: #820100;">
                        <i class="bi bi-check-circle me-1"></i> Save Issue Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SCRIPTS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // 1. Production Matrix DataTable
    const prodMatrixTable = $('#productionMatrixTable').DataTable({
        responsive: true,
        pageLength: 10,
        ordering: false,
        lengthChange: false
    });

    $('#productionMatrixFilter').on('keyup', function() {
        prodMatrixTable.search(this.value).draw();
    });

    // 2. Production Records DataTable
    const prodRecordsTable = $('#productionRecordsTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[1, 'desc']],
        language: {
            emptyTable: "No production issue records logged for Year <?= $selected_year ?> yet."
        }
    });

    // Sync issue date with report month
    $('#issue_form_date').on('change', function() {
        const val = $(this).val();
        if (val) {
            const m = parseInt(val.split('-')[1], 10);
            if (m >= 1 && m <= 12) {
                $('#issue_form_month').val(m);
            }
        }
    });

    // Quick log button in matrix row
    $(document).on('click', '.log-issue-for-indicator-btn', function(e) {
        e.preventDefault();
        const code = $(this).data('indicator');
        prepareAddIssueModal();
        $('#issue_form_indicator').val(code);
        const bsModal = new bootstrap.Modal(document.getElementById('modalLogProductionIssue'));
        bsModal.show();
    });

    // Edit issue record button behavior
    $(document).on('click', '.edit-issue-btn', function(e) {
        e.preventDefault();
        const d = $(this).data();
        const $m = $('#modalLogProductionIssue');

        $m.find('#issueModalTitle').text('Edit Production Issue Record');
        $m.find('#issue_form_action').val('update');
        $m.find('#issue_form_id').val(d.id);
        $m.find('#issue_form_date').val(d.date);
        $m.find('#issue_form_month').val(d.month);
        $m.find('#issue_form_indicator').val(d.indicator);
        $m.find('#issue_form_quantity').val(d.quantity);
        $m.find('#issue_form_beneficiary').val(d.beneficiary);
        $m.find('#issue_form_nic').val(d.nic);
        $m.find('#issue_form_address').val(d.address);
        $m.find('#issue_form_remarks').val(d.remarks);

        const bsModal = new bootstrap.Modal(document.getElementById('modalLogProductionIssue'));
        bsModal.show();
    });
});

function prepareAddIssueModal() {
    const $m = $('#modalLogProductionIssue');
    $m.find('#issueModalTitle').text('Log Production Issue Event');
    $m.find('#issue_form_action').val('create');
    $m.find('#issue_form_id').val('');
    $m.find('#issue_form_date').val(new Date().toISOString().split('T')[0]);
    $m.find('#issue_form_month').val(new Date().getMonth() + 1);
    $m.find('#issue_form_indicator').val('');
    $m.find('#issue_form_quantity').val('');
    $m.find('#issue_form_beneficiary').val('');
    $m.find('#issue_form_nic').val('');
    $m.find('#issue_form_address').val('');
    $m.find('#issue_form_remarks').val('');
}
</script>

<?php require_once '../../../includes/footer.php'; ?>
