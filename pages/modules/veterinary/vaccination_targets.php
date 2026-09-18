<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

// 1. Session and Role Guard (Include Veterinary Surgeon, District DD, and SMS)
$allowed_roles = ['veterinary_surgeon', 'district_dd', 'sms', 'admin', 'super_admin', 'deputy_director_district', 'provincial_director'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    header("Location: ../../../../index.php");
    exit();
}

$user_role = $_SESSION['role'] ?? '';
$user_id   = $_SESSION['user_id'] ?? null;
$is_supervisory = in_array($user_role, ['district_dd', 'sms', 'admin', 'super_admin', 'deputy_director_district', 'provincial_director'], true);

if (!isset($_SESSION['full_name'])) {
    $_SESSION['full_name'] = $_SESSION['username'] ?? 'Officer';
}
$full_name = $_SESSION['full_name'];

// 2. Supervisory Range Resolution & Jurisdiction
$district_id = $_SESSION['district_id'] ?? null;
$range_id    = $_SESSION['range_id'] ?? null;

if ($is_supervisory && isset($_GET['range_id']) && !empty($_GET['range_id'])) {
    $range_id = intval($_GET['range_id']);
}

// Fetch ranges for supervisory jurisdiction
$supervisory_ranges = [];
if ($is_supervisory) {
    if (!empty($district_id)) {
        $r_stmt = $mysqli->prepare("SELECT id, name FROM veterinary_ranges WHERE district_id = ? ORDER BY name ASC");
        $r_stmt->bind_param("i", $district_id);
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

if (empty($range_id)) {
    die('<div class="alert alert-danger text-center p-5 m-5">Error: Your account is not assigned to any Veterinary Range. Please select or assign a range.</div>');
}

// 3. Fallback Definitions & Active View Resolution
$district_name = 'Unknown District';
$range_name    = 'Unknown Range';
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$active_tab    = isset($_GET['tab']) ? trim($_GET['tab']) : 'charts';
if (!in_array($active_tab, ['charts', 'targets', 'sessions', 'vaccinators'], true)) {
    $active_tab = 'charts';
}

// 4. Fetch Core Structural Meta Information
if ($range_id) {
    $stmt = $mysqli->prepare("SELECT vr.name AS range_name, d.name AS district_name, vr.district_id FROM veterinary_ranges vr LEFT JOIN districts d ON vr.district_id = d.id WHERE vr.id = ?");
    $stmt->bind_param("i", $range_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $range_name = $row['range_name'];
        if (!empty($row['district_name'])) {
            $district_name = $row['district_name'];
        }
        if (!$district_id && !empty($row['district_id'])) {
            $district_id = intval($row['district_id']);
        }
    }
    $stmt->close();
}

// 5. Dynamic Data Fetch: Live Lookups against animal_populations table (Range Statistics Baseline)
$total_population = 0;
$animal_pop_data = [];
$anim_stmt = $mysqli->prepare("SELECT animal_type, SUM(quantity) as quantity FROM animal_populations WHERE range_id = ? AND year = ? GROUP BY animal_type");
if ($anim_stmt) {
    $anim_stmt->bind_param("ii", $range_id, $selected_year);
    $anim_stmt->execute();
    $anim_res = $anim_stmt->get_result();
    while ($row = $anim_res->fetch_assoc()) {
        $animal_pop_data[$row['animal_type']] = intval($row['quantity']);
        $total_population += intval($row['quantity']);
    }
    $anim_stmt->close();
}

// Master list of animal species supported
$species_options = ['Cow', 'Buffalo', 'Goat', 'Sheep', 'Pig', 'Chicken'];

// 6. Fetch Target Data from annual_vaccination_targets (Map by species)
$vax_targets_map = [];
foreach ($species_options as $sp_opt) {
    $vax_targets_map[$sp_opt] = [
        'id' => null,
        'target_fmd' => 0,
        'target_bq' => 0,
        'target_hs' => 0,
        'target_poultry_doses' => 0,
        'available_ldo_count' => 0,
        'allocated_ldo_target' => 0,
        'casual_vaccinators_needed' => 0,
        'allocated_man_days' => 0,
        'assigned_vaccinator_id' => null
    ];
}

$vax_stmt = $mysqli->prepare("SELECT * FROM annual_vaccination_targets WHERE range_id = ? AND year = ?");
if ($vax_stmt) {
    $vax_stmt->bind_param("ii", $range_id, $selected_year);
    $vax_stmt->execute();
    $vax_res = $vax_stmt->get_result();
    while ($row = $vax_res->fetch_assoc()) {
        $vax_targets_map[$row['animal_type']] = $row;
    }
    $vax_stmt->close();
}

// 7. Fetch Deployed Casual Vaccinators
$deployed_staff_records = [];
$assigned_vaccinator_lookup = [];
$staff_stmt = $mysqli->prepare("SELECT * FROM casual_vaccinator_deployments WHERE range_id = ? AND year = ? ORDER BY full_name ASC");
if ($staff_stmt) {
    $staff_stmt->bind_param("ii", $range_id, $selected_year);
    $staff_stmt->execute();
    $staff_res = $staff_stmt->get_result();
    while ($st = $staff_res->fetch_assoc()) {
        $deployed_staff_records[] = $st;
        $assigned_vaccinator_lookup[intval($st['id'])] = htmlspecialchars($st['full_name']) . ' (NIC: ' . htmlspecialchars($st['nic_no']) . ')';
    }
    $staff_stmt->close();
}

// 8. Fetch Vaccination Program Logs for the Evaluation Year
$session_logs = [];
$total_livestock_achieved = 0;
$total_poultry_achieved = 0;
$monthly_achieved_livestock = array_fill(1, 12, 0);
$monthly_achieved_poultry   = array_fill(1, 12, 0);
$achieved_by_species_vax    = [];

// Specific Disease Achieved breakdown for Livestock (HS, FMD, BQ)
$achieved_hs_total = 0;
$achieved_fmd_total = 0;
$achieved_bq_total = 0;
$achieved_other_livestock = 0;
$poultry_vax_breakdown = [];

$sess_stmt = $mysqli->prepare("SELECT * FROM vaccination_session_logs WHERE range_id = ? AND report_year = ? ORDER BY session_date DESC, id DESC");
if ($sess_stmt) {
    $sess_stmt->bind_param("ii", $range_id, $selected_year);
    $sess_stmt->execute();
    $sess_res = $sess_stmt->get_result();
    while ($srow = $sess_res->fetch_assoc()) {
        $session_logs[] = $srow;
        $cnt = intval($srow['vaccinated_count']);
        $m = intval($srow['report_month']);
        $cat = $srow['category'];
        $sp = $srow['animal_type'];
        $vx = $srow['vaccine_name'];

        if ($cat === 'Poultry' || in_array($sp, ['Chicken', 'Broiler', 'Layer', 'Backyard Poultry', 'Duck'], true)) {
            $total_poultry_achieved += $cnt;
            if ($m >= 1 && $m <= 12) {
                $monthly_achieved_poultry[$m] += $cnt;
            }
            if (!isset($poultry_vax_breakdown[$vx])) {
                $poultry_vax_breakdown[$vx] = 0;
            }
            $poultry_vax_breakdown[$vx] += $cnt;
        } else {
            $total_livestock_achieved += $cnt;
            if ($m >= 1 && $m <= 12) {
                $monthly_achieved_livestock[$m] += $cnt;
            }

            // Disease categorization for livestock
            $upper_vx = strtoupper($vx);
            if (strpos($upper_vx, 'HS') !== false || strpos($upper_vx, 'HEMORRHAGIC') !== false) {
                $achieved_hs_total += $cnt;
            } elseif (strpos($upper_vx, 'FMD') !== false || strpos($upper_vx, 'FOOT') !== false) {
                $achieved_fmd_total += $cnt;
            } elseif (strpos($upper_vx, 'BQ') !== false || strpos($upper_vx, 'BLACK') !== false) {
                $achieved_bq_total += $cnt;
            } else {
                $achieved_other_livestock += $cnt;
            }
        }

        if (!isset($achieved_by_species_vax[$sp])) {
            $achieved_by_species_vax[$sp] = [];
        }
        if (!isset($achieved_by_species_vax[$sp][$vx])) {
            $achieved_by_species_vax[$sp][$vx] = 0;
        }
        $achieved_by_species_vax[$sp][$vx] += $cnt;
    }
    $sess_stmt->close();
}

// Compute Target Totals
$total_livestock_target = 0;
$total_poultry_target = 0;
$target_fmd_total = 0;
$target_bq_total = 0;
$target_hs_total = 0;

foreach ($vax_targets_map as $sp => $vt) {
    if (in_array($sp, ['Chicken', 'Broiler', 'Layer', 'Backyard Poultry', 'Duck'], true)) {
        $total_poultry_target += intval($vt['target_poultry_doses'] ?? 0);
    } else {
        $fmd = intval($vt['target_fmd'] ?? 0);
        $bq  = intval($vt['target_bq'] ?? 0);
        $hs  = intval($vt['target_hs'] ?? 0);
        $target_fmd_total += $fmd;
        $target_bq_total  += $bq;
        $target_hs_total  += $hs;
        $total_livestock_target += ($fmd + $bq + $hs);
    }
}

// Completion Percentages
$livestock_pct = ($total_livestock_target > 0) ? min(100, round(($total_livestock_achieved / $total_livestock_target) * 100, 1)) : ($total_livestock_achieved > 0 ? 100 : 0);
$poultry_pct   = ($total_poultry_target > 0) ? min(100, round(($total_poultry_achieved / $total_poultry_target) * 100, 1)) : ($total_poultry_achieved > 0 ? 100 : 0);

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">

<style>
.nav-tabs .nav-link {
    color: #495057;
    font-weight: 600;
    font-size: 0.9rem;
    padding: 0.65rem 1.25rem;
    border-top: 3px solid transparent;
}
.nav-tabs .nav-link.active {
    color: #820100;
    border-top: 3px solid #820100;
    background-color: #fff;
}
.nav-tabs .nav-link:hover:not(.active) {
    border-top-color: #d4c7b7;
}
.section-badge-header {
    background: linear-gradient(135deg, #370709 0%, #820100 100%);
    color: #fff;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.95rem;
}
</style>

<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1" style="color: #370709;">Vaccination Program Tracking & Annual Targets</h4>
        <span class="badge" style="background-color: #d4c7b7; color: #370709;"><i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($range_name) ?> Range</span>
        <span class="badge text-light" style="background-color: #a07174;"><i class="bi bi-building me-1"></i><?= htmlspecialchars($district_name) ?> District</span>
        <span class="badge bg-success text-light"><i class="bi bi-link-45deg me-1"></i>Range Statistics Baseline Synced</span>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <?php if ($is_supervisory && !empty($supervisory_ranges)): ?>
            <div class="d-flex align-items-center gap-1">
                <label class="small fw-semibold text-secondary text-nowrap"><i class="bi bi-geo-alt-fill text-danger me-1"></i>Range:</label>
                <select class="form-select form-select-sm border-secondary shadow-sm" onchange="switchRange(this.value)">
                    <?php foreach ($supervisory_ranges as $sr): ?>
                        <option value="<?= $sr['id'] ?>" <?= $range_id == $sr['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sr['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="input-group input-group-sm">
            <label class="input-group-text fw-bold text-light" style="background-color: #820100; border-color: #820100;">Evaluation Year</label>
            <select id="dashboardYearFilter" class="form-select border-secondary" onchange="switchYear(this.value);">
                <?php for ($y = date('Y') + 1; $y >= 2023; $y--): ?>
                    <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <button type="button" class="btn btn-sm btn-dark text-nowrap fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalVaccinationSession" onclick="prepareAddSessionModal();">
            <i class="bi bi-plus-circle me-1 text-warning"></i> Log Vaccination Program
        </button>
        <a href="annual_targets.php" class="btn btn-sm btn-secondary shadow-sm text-nowrap">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<?php if (isset($_SESSION['msg'])): ?>
    <div class="alert alert-<?= htmlspecialchars($_SESSION['msg_type'] ?? 'info') ?> alert-dismissible fade show shadow-sm py-2 px-3 mb-4 small" role="alert">
        <?= $_SESSION['msg'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
<?php endif; ?>

<!-- Executive KPI Summary Cards -->
<div class="row g-3 mb-4">
    <!-- Livestock Target vs Achieved -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #820100 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-bold text-uppercase">Livestock Vaccinations</span>
                    <i class="bi bi-shield-shaded fs-5" style="color: #820100;"></i>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <h4 class="fw-bold mb-0" style="color: #370709;"><?= number_format($total_livestock_achieved) ?></h4>
                    <span class="text-muted small">/ <?= number_format($total_livestock_target) ?> Target</span>
                </div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar" role="progressbar" style="width: <?= $livestock_pct ?>%; background-color: #820100;" aria-valuenow="<?= $livestock_pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <small class="text-muted"><?= $livestock_pct ?>% Completed</small>
                    <span class="badge bg-light text-dark border"><?= count(array_filter($session_logs, fn($s) => $s['category'] === 'Livestock')) ?> programs</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Poultry Target vs Achieved -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #f59e0b !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-bold text-uppercase">Poultry Vaccinations</span>
                    <i class="bi bi-egg-fill fs-5 text-warning"></i>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <h4 class="fw-bold mb-0 text-dark"><?= number_format($total_poultry_achieved) ?></h4>
                    <span class="text-muted small">/ <?= number_format($total_poultry_target) ?> Target</span>
                </div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $poultry_pct ?>%;" aria-valuenow="<?= $poultry_pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <small class="text-muted"><?= $poultry_pct ?>% Completed</small>
                    <span class="badge bg-light text-dark border"><?= count(array_filter($session_logs, fn($s) => $s['category'] === 'Poultry')) ?> programs</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Progressive Programs Logged -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #10b981 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-bold text-uppercase">Vaccination Programs Logged</span>
                    <i class="bi bi-journal-check fs-5 text-success"></i>
                </div>
                <h4 class="fw-bold mb-0 text-dark"><?= count($session_logs) ?></h4>
                <div class="text-muted small mt-2">
                    Field programs recorded for Year <?= htmlspecialchars($selected_year) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Deployed Personnel -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #3b82f6 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-bold text-uppercase">Deployed Vaccinators</span>
                    <i class="bi bi-people-fill fs-5 text-primary"></i>
                </div>
                <h4 class="fw-bold mb-0 text-dark"><?= count($deployed_staff_records) ?></h4>
                <div class="text-muted small mt-2">
                    Personnel assigned in <?= htmlspecialchars($range_name) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- NAVIGATION TABS (REORDERED: 1. CHARTS TOP -> 2. TARGETS -> 3. LOGS BOTTOM) -->
<!-- ========================================================================= -->
<ul class="nav nav-tabs mb-4 bg-white rounded-top shadow-xs px-2 pt-2 border-bottom" id="vaxModuleTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $active_tab === 'charts' ? 'active' : '' ?>" id="tab-btn-charts" data-bs-toggle="tab" data-bs-target="#tab-content-charts" type="button" role="tab" onclick="updateTabUrl('charts')">
            <i class="bi bi-bar-chart-line-fill me-2" style="color: #820100;"></i>1. Visual Tracking Progress Charts (Top)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $active_tab === 'targets' ? 'active' : '' ?>" id="tab-btn-targets" data-bs-toggle="tab" data-bs-target="#tab-content-targets" type="button" role="tab" onclick="updateTabUrl('targets')">
            <i class="bi bi-grid-3x3-gap-fill me-2 text-primary"></i>2. Annual Target Metrics (Middle)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $active_tab === 'sessions' ? 'active' : '' ?>" id="tab-btn-sessions" data-bs-toggle="tab" data-bs-target="#tab-content-sessions" type="button" role="tab" onclick="updateTabUrl('sessions')">
            <i class="bi bi-journal-text me-2 text-danger"></i>3. Individual Program Logs (Bottom)
            <span class="badge bg-secondary ms-1"><?= count($session_logs) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $active_tab === 'vaccinators' ? 'active' : '' ?>" id="tab-btn-vaccinators" data-bs-toggle="tab" data-bs-target="#tab-content-vaccinators" type="button" role="tab" onclick="updateTabUrl('vaccinators')">
            <i class="bi bi-people-fill me-2 text-success"></i>Casual Vaccinators
            <span class="badge bg-secondary ms-1"><?= count($deployed_staff_records) ?></span>
        </button>
    </li>
</ul>

<!-- ========================================================================= -->
<!-- TAB CONTENT PANES                                                         -->
<!-- ========================================================================= -->
<div class="tab-content" id="vaxModuleTabsContent">

    <!-- ───────────────────────────────────────────────────────────────────── -->
    <!-- SECTION 1 (TOP): VISUAL TRACKING PROGRESS CHARTS                     -->
    <!-- Strictly Separated: Livestock (HS, FMD, BQ) vs Poultry                -->
    <!-- ───────────────────────────────────────────────────────────────────── -->
    <div class="tab-pane fade <?= $active_tab === 'charts' ? 'show active' : '' ?>" id="tab-content-charts" role="tabpanel">
        
        <!-- Subsection A: Livestock Vaccinations (HS, FMD, BQ) -->
        <div class="card gov-card shadow-sm border-0 mb-4">
            <div class="card-header bg-white pt-3 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-0" style="color: #370709;">
                        <i class="bi bi-shield-check me-2" style="color: #820100;"></i>Section 1A: Livestock Vaccinations Tracking (HS, FMD, BQ)
                    </h5>
                    <p class="text-muted small mb-0">Strict separation for large and small livestock vaccinations across major statutory diseases.</p>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge text-light px-3 py-2" style="background-color: #820100;"><i class="bi bi-shield-shaded me-1"></i>HS / FMD / BQ</span>
                    <span class="badge bg-light text-dark border px-3 py-2">Total Livestock Doses: <?= number_format($total_livestock_achieved) ?> / <?= number_format($total_livestock_target) ?></span>
                </div>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-4">
                    <!-- Chart 1A-1: Livestock Disease Breakdown (HS, FMD, BQ) -->
                    <div class="col-12 col-lg-6">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <h6 class="fw-bold text-dark mb-1"><i class="bi bi-bar-chart-fill text-danger me-2"></i>Disease Targets vs Achieved (HS, FMD, BQ)</h6>
                            <p class="text-muted small mb-3">Comparison by specific livestock disease program</p>
                            <div style="position: relative; width: 100%; height: 280px;">
                                <canvas id="livestockDiseaseChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Chart 1A-2: Livestock Species Coverage Breakdown -->
                    <div class="col-12 col-lg-6">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <h6 class="fw-bold text-dark mb-1"><i class="bi bi-diagram-3-fill text-primary me-2"></i>Livestock Species Progress</h6>
                            <p class="text-muted small mb-3">Cattle (Cow), Buffalo, Goat, Sheep & Pig target accomplishment</p>
                            <div style="position: relative; width: 100%; height: 280px;">
                                <canvas id="livestockVaxChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subsection B: Poultry Vaccinations -->
        <div class="card gov-card shadow-sm border-0 mb-4">
            <div class="card-header bg-white pt-3 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-egg-fill me-2 text-warning"></i>Section 1B: Poultry Vaccinations Tracking
                    </h5>
                    <p class="text-muted small mb-0">Strictly dedicated tracking for avian disease prevention programs (Newcastle, IBD, Fowl Pox, etc.).</p>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge bg-warning-subtle text-warning border border-warning px-3 py-2"><i class="bi bi-egg-fill me-1"></i>Poultry Birds Only</span>
                    <span class="badge bg-light text-dark border px-3 py-2">Total Poultry Doses: <?= number_format($total_poultry_achieved) ?> / <?= number_format($total_poultry_target) ?></span>
                </div>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-4 align-items-center">
                    <div class="col-12 col-lg-6">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <h6 class="fw-bold text-dark mb-1"><i class="bi bi-pie-chart-fill text-warning me-2"></i>Poultry Annual Coverage Completion</h6>
                            <p class="text-muted small mb-3">Total administered doses vs remaining annual target</p>
                            <div style="position: relative; width: 100%; height: 280px;">
                                <canvas id="poultryVaxChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="card border-0 bg-white shadow-sm p-3">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-list-check text-success me-2"></i>Poultry Vaccine Administered Breakdown</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped align-middle m-0 small">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Vaccine Type</th>
                                            <th class="text-center">Administered Doses</th>
                                            <th class="text-end">Share</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($poultry_vax_breakdown)): ?>
                                            <tr><td colspan="3" class="text-center text-muted py-3">No poultry vaccination programs logged yet for <?= htmlspecialchars($selected_year) ?>.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($poultry_vax_breakdown as $pv_name => $pv_cnt): 
                                                $share = $total_poultry_achieved > 0 ? round(($pv_cnt / $total_poultry_achieved) * 100, 1) : 0;
                                            ?>
                                                <tr>
                                                    <td class="fw-semibold text-dark"><i class="bi bi-check-circle-fill text-warning me-1"></i><?= htmlspecialchars($pv_name) ?></td>
                                                    <td class="text-center fw-bold font-monospace"><?= number_format($pv_cnt) ?></td>
                                                    <td class="text-end text-muted"><?= $share ?>%</td>
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

    </div>

    <!-- ───────────────────────────────────────────────────────────────────── -->
    <!-- SECTION 2 (MIDDLE): ANNUAL TARGET METRICS                             -->
    <!-- ───────────────────────────────────────────────────────────────────── -->
    <div class="tab-pane fade <?= $active_tab === 'targets' ? 'show active' : '' ?>" id="tab-content-targets" role="tabpanel">

        <!-- 1. Annual Target Configurations & Deployment Matrix -->
        <div class="card gov-card mb-4 shadow-sm border-0">
            <div class="card-header bg-white pt-4 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1" style="color: #370709;">
                        <i class="bi bi-grid-3x3-gap-fill me-2 text-danger"></i>Annual Target Configurations & Deployment Matrix
                    </h5>
                    <p class="text-muted small mb-0">Configured annual targets and personnel assignments aligned with live Range Statistics baselines.</p>
                </div>
                <div>
                    <button class="btn btn-sm btn-dark fw-bold me-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addTargetModal" id="addVaxTargetBtn">
                        <i class="bi bi-plus-circle me-1"></i> Configure Target
                    </button>
                </div>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row mb-3">
                    <div class="col-md-4 ms-auto">
                        <input type="text" id="matrixFilter" class="form-control form-control-sm border-secondary" placeholder="Search species or targets...">
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="combinedMatrixTable" class="table table-striped table-hover table-bordered align-middle small bg-white text-dark m-0">
                        <thead style="background-color: #d4c7b7; color: #370709;">
                            <tr>
                                <th>Animal Species</th>
                                <th class="text-center">Live Population Count</th>
                                <th class="text-center">FMD Target</th>
                                <th class="text-center">BQ Target</th>
                                <th class="text-center">HS Target</th>
                                <th class="text-center">Poultry Target</th>
                                <th class="text-center">Available LDO</th>
                                <th class="text-center">Allocated LDO Target</th>
                                <th class="text-center">Assigned Vaccinator</th>
                                <th class="text-center">Allocated Man-Days</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $all_species = array_unique(array_merge(array_keys($animal_pop_data), $species_options));
                            foreach ($all_species as $sp) {
                                $qty = isset($animal_pop_data[$sp]) ? intval($animal_pop_data[$sp]) : 0;
                                $safe_sp = htmlspecialchars($sp);

                                $vt = isset($vax_targets_map[$sp]) ? $vax_targets_map[$sp] : [
                                    'id' => null,
                                    'target_fmd' => 0,
                                    'target_bq' => 0,
                                    'target_hs' => 0,
                                    'target_poultry_doses' => 0,
                                    'available_ldo_count' => 0,
                                    'allocated_ldo_target' => 0,
                                    'casual_vaccinators_needed' => 0,
                                    'allocated_man_days' => 0,
                                    'assigned_vaccinator_id' => null
                                ];

                                echo '<tr>';
                                echo '<td class="fw-bold">' . $safe_sp . '</td>';
                                echo '<td class="text-center fw-bold"><span class="badge bg-success-subtle text-success border border-success">' . number_format($qty) . '</span></td>';
                                echo '<td class="text-center">' . number_format($vt['target_fmd'] ?? 0) . '</td>';
                                echo '<td class="text-center">' . number_format($vt['target_bq'] ?? 0) . '</td>';
                                echo '<td class="text-center">' . number_format($vt['target_hs'] ?? 0) . '</td>';
                                echo '<td class="text-center">' . ($sp === 'Chicken' ? '<span class="badge bg-warning text-dark">' . number_format($vt['target_poultry_doses'] ?? 0) . '</span>' : '<span class="text-muted">-</span>') . '</td>';
                                echo '<td class="text-center">' . number_format($vt['available_ldo_count'] ?? 0) . '</td>';
                                echo '<td class="text-center">' . number_format($vt['allocated_ldo_target'] ?? 0) . '</td>';
                                $assigned_personnel_display = '<span class="text-muted small">Not Assigned</span>';
                                $assigned_vaccinator_id = intval($vt['assigned_vaccinator_id'] ?? 0);
                                if ($assigned_vaccinator_id > 0 && isset($assigned_vaccinator_lookup[$assigned_vaccinator_id])) {
                                    $assigned_personnel_display = '<span class="fw-bold text-success">' . $assigned_vaccinator_lookup[$assigned_vaccinator_id] . '</span>';
                                }
                                echo '<td class="text-center">' . $assigned_personnel_display . '</td>';
                                echo '<td class="text-center">' . number_format($vt['allocated_man_days'] ?? 0) . '</td>';
                                echo '<td class="text-center">';
                                echo '<button class="btn btn-xs btn-outline-primary edit-target-row-btn" ' .
                                    ' data-species="' . $safe_sp . '"' .
                                    ' data-qty="' . $qty . '"' .
                                    ' data-fmd="' . intval($vt['target_fmd'] ?? 0) . '"' .
                                    ' data-bq="' . intval($vt['target_bq'] ?? 0) . '"' .
                                    ' data-hs="' . intval($vt['target_hs'] ?? 0) . '"' .
                                    ' data-poultry="' . intval($vt['target_poultry_doses'] ?? 0) . '"' .
                                    ' data-ldo-count="' . intval($vt['available_ldo_count'] ?? 0) . '"' .
                                    ' data-ldo-target="' . intval($vt['allocated_ldo_target'] ?? 0) . '"' .
                                    ' data-man-days="' . intval($vt['allocated_man_days'] ?? 0) . '"' .
                                    ' data-assigned-vaccinator="' . intval($vt['assigned_vaccinator_id'] ?? 0) . '"' .
                                    '><i class="bi bi-pencil-square me-1"></i>Configure</button>';
                                echo '</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. Progress Summaries: Annual vs. Monthly Aggregates -->
        <div class="card gov-card mb-4 shadow-sm border-0">
            <div class="card-header bg-white pt-4 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1" style="color: #370709;">
                        <i class="bi bi-calendar3-range me-2 text-danger"></i>Progress Summaries: Annual vs. Monthly Aggregates
                    </h5>
                    <p class="text-muted small mb-0">Progressive coverage tracking aggregated against fixed annual targets.</p>
                </div>
                <ul class="nav nav-pills" id="progressSummaryTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-1 px-3 fw-bold small" id="pills-annual-tab" data-bs-toggle="pill" data-bs-target="#pills-annual" type="button" role="tab" style="--bs-nav-pills-link-active-bg: #820100;">
                            Annual Target Progress
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-1 px-3 fw-bold small text-dark" id="pills-monthly-tab" data-bs-toggle="pill" data-bs-target="#pills-monthly" type="button" role="tab">
                            Monthly Breakdown (Jan - Dec)
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="tab-content" id="progressSummaryTabsContent">
                    <!-- TAB: Annual Target vs Achieved Summary -->
                    <div class="tab-pane fade show active" id="pills-annual" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle small bg-white m-0">
                                <thead style="background-color: #d4c7b7; color: #370709;">
                                    <tr>
                                        <th>Category / Stream</th>
                                        <th class="text-center">Annual Target</th>
                                        <th class="text-center">Administered / Achieved</th>
                                        <th class="text-center">Remaining</th>
                                        <th class="text-center" style="width: 200px;">Progress Bar</th>
                                        <th class="text-center">Completion %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-bold"><i class="bi bi-shield-shaded me-1" style="color: #820100;"></i>Livestock (HS, FMD, BQ)</td>
                                        <td class="text-center fw-bold"><?= number_format($total_livestock_target) ?></td>
                                        <td class="text-center fw-bold text-success"><?= number_format($total_livestock_achieved) ?></td>
                                        <td class="text-center fw-bold text-danger"><?= number_format(max(0, $total_livestock_target - $total_livestock_achieved)) ?></td>
                                        <td>
                                            <div class="progress" style="height: 12px;">
                                                <div class="progress-bar" role="progressbar" style="width: <?= $livestock_pct ?>%; background-color: #820100;" aria-valuenow="<?= $livestock_pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </td>
                                        <td class="text-center fw-bold"><?= $livestock_pct ?>%</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold"><i class="bi bi-egg-fill me-1 text-warning"></i>Poultry Birds</td>
                                        <td class="text-center fw-bold"><?= number_format($total_poultry_target) ?></td>
                                        <td class="text-center fw-bold text-success"><?= number_format($total_poultry_achieved) ?></td>
                                        <td class="text-center fw-bold text-danger"><?= number_format(max(0, $total_poultry_target - $total_poultry_achieved)) ?></td>
                                        <td>
                                            <div class="progress" style="height: 12px;">
                                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $poultry_pct ?>%;" aria-valuenow="<?= $poultry_pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </td>
                                        <td class="text-center fw-bold"><?= $poultry_pct ?>%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB: Monthly Breakdown -->
                    <div class="tab-pane fade" id="pills-monthly" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle small bg-white text-center m-0">
                                <thead style="background-color: #d4c7b7; color: #370709;">
                                    <tr>
                                        <th>Stream</th>
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <th><?= date('M', mktime(0, 0, 0, $m, 10)) ?></th>
                                        <?php endfor; ?>
                                        <th class="fw-bold">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-bold text-start text-nowrap"><i class="bi bi-shield-shaded me-1" style="color: #820100;"></i>Livestock</td>
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <td><?= number_format($monthly_achieved_livestock[$m]) ?></td>
                                        <?php endfor; ?>
                                        <td class="fw-bold text-success"><?= number_format($total_livestock_achieved) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-start text-nowrap"><i class="bi bi-egg-fill me-1 text-warning"></i>Poultry</td>
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <td><?= number_format($monthly_achieved_poultry[$m]) ?></td>
                                        <?php endfor; ?>
                                        <td class="fw-bold text-success"><?= number_format($total_poultry_achieved) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- ───────────────────────────────────────────────────────────────────── -->
    <!-- SECTION 3 (BOTTOM): INDIVIDUAL VACCINATION PROGRAM LOGS              -->
    <!-- ───────────────────────────────────────────────────────────────────── -->
    <div class="tab-pane fade <?= $active_tab === 'sessions' ? 'show active' : '' ?>" id="tab-content-sessions" role="tabpanel">
        <div class="card gov-card mb-4 shadow-sm border-0">
            <div class="card-header bg-white pt-4 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1" style="color: #370709;">
                        <i class="bi bi-journal-text me-2 text-danger"></i>Individual Vaccination Program Register & Logs
                    </h5>
                    <p class="text-muted small mb-0">Progressively record and monitor individual vaccination field programs by date, assigned vaccinators, batch number, and head counts.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-dark fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalVaccinationSession" onclick="prepareAddSessionModal();">
                        <i class="bi bi-plus-circle me-1 text-warning"></i> Log New Vaccination Program
                    </button>
                </div>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-2 mb-3">
                    <div class="col-12 col-md-3">
                        <select id="filterSessionCategory" class="form-select form-select-sm border-secondary">
                            <option value="">All Categories (Livestock & Poultry)</option>
                            <option value="Livestock">Livestock Only</option>
                            <option value="Poultry">Poultry Only</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4 ms-auto">
                        <input type="text" id="sessionFilterInput" class="form-control form-control-sm border-secondary" placeholder="Search program date, species, vaccine, vaccinator...">
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="sessionLogsTable" class="table table-striped table-hover table-bordered align-middle small bg-white m-0">
                        <thead style="background-color: #d4c7b7; color: #370709;">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="width: 130px;">Vaccination Program Date</th>
                                <th style="width: 90px;" class="text-center">Category</th>
                                <th>Species</th>
                                <th>Vaccine Type</th>
                                <th>Assigned Vaccinators / Personnel</th>
                                <th class="text-center">Animals Vaccinated</th>
                                <th class="text-center">Doses Administered</th>
                                <th>Location & Batch</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($session_logs)): ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-4 d-block mb-1"></i>No vaccination programs logged yet for <?= htmlspecialchars($selected_year) ?>. Click <strong>"Log New Vaccination Program"</strong> to begin.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $scnt = 1; foreach ($session_logs as $s): ?>
                                    <tr>
                                        <td><?= $scnt++ ?></td>
                                        <td class="text-nowrap fw-bold"><i class="bi bi-calendar-event me-1 text-danger"></i><?= date('d M Y', strtotime($s['session_date'])) ?></td>
                                        <td class="text-center">
                                            <?php if ($s['category'] === 'Poultry'): ?>
                                                <span class="badge bg-warning-subtle text-dark border border-warning"><i class="bi bi-egg-fill me-1 text-warning"></i>Poultry</span>
                                            <?php else: ?>
                                                <span class="badge text-light" style="background-color: #820100;"><i class="bi bi-shield-fill me-1"></i>Livestock</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold"><?= htmlspecialchars($s['animal_type']) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($s['vaccine_name']) ?></span></td>
                                        <td>
                                            <?php
                                            $v_names = array_filter(array_map('trim', explode(',', $s['vaccinator_name'])));
                                            foreach ($v_names as $vn): ?>
                                                <span class="badge bg-light text-dark border me-1 mb-1"><i class="bi bi-person me-1"></i><?= htmlspecialchars($vn) ?></span>
                                            <?php endforeach; ?>
                                        </td>
                                        <td class="text-center fw-bold fs-6 text-dark"><?= number_format($s['vaccinated_count']) ?></td>
                                        <td class="text-center fw-bold text-success font-monospace"><?= number_format($s['doses_administered']) ?></td>
                                        <td class="small">
                                            <?php if (!empty($s['location_name'])): ?>
                                                <div><i class="bi bi-geo-alt text-danger me-1"></i><strong><?= htmlspecialchars($s['location_name']) ?></strong></div>
                                            <?php endif; ?>
                                            <?php if (!empty($s['batch_no'])): ?>
                                                <span class="badge bg-dark font-monospace">Batch: <?= htmlspecialchars($s['batch_no']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($s['remarks'])): ?>
                                                <div class="text-muted text-truncate" style="max-width: 200px;"><?= htmlspecialchars($s['remarks']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-xs btn-outline-primary edit-session-btn me-1" 
                                                data-id="<?= $s['id'] ?>"
                                                data-date="<?= htmlspecialchars($s['session_date']) ?>"
                                                data-category="<?= htmlspecialchars($s['category']) ?>"
                                                data-species="<?= htmlspecialchars($s['animal_type']) ?>"
                                                data-vaccine="<?= htmlspecialchars($s['vaccine_name']) ?>"
                                                data-vaccinator-id="<?= intval($s['vaccinator_id'] ?? 0) ?>"
                                                data-vaccinator-ids="<?= htmlspecialchars($s['assigned_vaccinator_ids'] ?? '') ?>"
                                                data-vaccinator-name="<?= htmlspecialchars($s['vaccinator_name']) ?>"
                                                data-count="<?= intval($s['vaccinated_count']) ?>"
                                                data-doses="<?= intval($s['doses_administered']) ?>"
                                                data-batch="<?= htmlspecialchars($s['batch_no'] ?? '') ?>"
                                                data-location="<?= htmlspecialchars($s['location_name'] ?? '') ?>"
                                                data-remarks="<?= htmlspecialchars($s['remarks'] ?? '') ?>">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <form action="processors/vaccination_session_crud.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this vaccination program record? Administered doses will be restored to inventory.');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                                <input type="hidden" name="year" value="<?= htmlspecialchars($selected_year) ?>">
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

    <!-- ───────────────────────────────────────────────────────────────────── -->
    <!-- SECTION 4: CASUAL VACCINATORS                                        -->
    <!-- ───────────────────────────────────────────────────────────────────── -->
    <div class="tab-pane fade <?= $active_tab === 'vaccinators' ? 'show active' : '' ?>" id="tab-content-vaccinators" role="tabpanel">
        <div class="card gov-card shadow-sm border-0 mb-4">
            <div class="card-header bg-white pt-4 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1" style="color: #370709;">
                        <i class="bi bi-people-fill me-2 text-success"></i>Casual Vaccinator Field Personnel Registry
                    </h5>
                    <p class="text-muted small mb-0">Registered casual vaccinators deployed in <?= htmlspecialchars($range_name) ?> for Year <?= htmlspecialchars($selected_year) ?>.</p>
                </div>
                <button class="btn btn-sm btn-dark add-staff-btn shadow-sm" data-bs-toggle="modal" data-bs-target="#deployPersonnelModal">
                    <i class="bi bi-person-plus me-1"></i> Register New Vaccinator
                </button>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="table-responsive">
                    <table id="casualVaccinatorsTable" class="table table-striped table-hover table-bordered align-middle small bg-white m-0">
                        <thead style="background-color: #d4c7b7; color: #370709;">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Full Name</th>
                                <th class="text-center" style="width: 250px;">NIC Number</th>
                                <th class="text-center" style="width: 200px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $counter = 1;
                            foreach ($deployed_staff_records as $st) {
                                $safe_name = htmlspecialchars($st['full_name']);
                                $safe_nic = htmlspecialchars($st['nic_no']);
                                echo '<tr>';
                                echo '<td>' . $counter++ . '</td>';
                                echo '<td class="fw-bold">' . $safe_name . '</td>';
                                echo '<td class="text-center">' . $safe_nic . '</td>';
                                echo '<td class="text-center">';
                                echo '<button class="btn btn-xs btn-outline-primary edit-staff-btn me-2" data-id="' . $st['id'] . '" data-name="' . $safe_name . '" data-nic="' . $safe_nic . '"><i class="bi bi-pencil-square me-1"></i>Edit</button>';
                                echo '<form action="processors/delete_vaccinator_deployment.php" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this vaccinator record?\');">';
                                echo '<input type="hidden" name="id" value="' . $st['id'] . '">';
                                echo '<input type="hidden" name="year" value="' . htmlspecialchars($selected_year) . '">';
                                echo '<button type="submit" class="btn btn-xs btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete</button>';
                                echo '</form>';
                                echo '</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

</main>
</div>

<!-- Modals -->
<?php include 'models/modal_vaccination_session.php'; ?>
<?php include 'models/vaccination_staff.php'; ?>
<?php include 'models/add_animal_population.php'; ?>
<?php include 'models/add_target_modal.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Prepare Data for Charts
    const livestockTargetVal = <?= intval($total_livestock_target) ?>;
    const livestockAchievedVal = <?= intval($total_livestock_achieved) ?>;
    const poultryTargetVal = <?= intval($total_poultry_target) ?>;
    const poultryAchievedVal = <?= intval($total_poultry_achieved) ?>;

    let chartLivestockInstance = null;
    let chartLivestockDiseaseInstance = null;
    let chartPoultryInstance = null;

    function initCharts() {
        // Chart 1: Livestock Disease Breakdown (HS, FMD, BQ)
        if (!chartLivestockDiseaseInstance) {
            const ctxDisease = document.getElementById('livestockDiseaseChart');
            if (ctxDisease) {
                chartLivestockDiseaseInstance = new Chart(ctxDisease.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: ['HS (Hemorrhagic Septicemia)', 'FMD (Foot & Mouth)', 'BQ (Black Quarter)', 'Other Livestock'],
                        datasets: [
                            {
                                label: 'Annual Target Doses',
                                data: [<?= intval($target_hs_total) ?>, <?= intval($target_fmd_total) ?>, <?= intval($target_bq_total) ?>, 0],
                                backgroundColor: '#d4c7b7',
                                borderColor: '#a07174',
                                borderWidth: 1,
                                borderRadius: 4
                            },
                            {
                                label: 'Administered / Achieved',
                                data: [<?= intval($achieved_hs_total) ?>, <?= intval($achieved_fmd_total) ?>, <?= intval($achieved_bq_total) ?>, <?= intval($achieved_other_livestock) ?>],
                                backgroundColor: '#820100',
                                borderColor: '#370709',
                                borderWidth: 1,
                                borderRadius: 4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0 }
                            }
                        }
                    }
                });
            }
        }

        // Chart 2: Livestock Species Comparison
        if (!chartLivestockInstance) {
            const ctxLivestock = document.getElementById('livestockVaxChart');
            if (ctxLivestock) {
                chartLivestockInstance = new Chart(ctxLivestock.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: ['Cattle (Cow)', 'Buffalo', 'Goat', 'Sheep', 'Pig'],
                        datasets: [
                            {
                                label: 'Configured Target',
                                data: [
                                    <?= intval(($vax_targets_map['Cow']['target_fmd'] ?? 0) + ($vax_targets_map['Cow']['target_bq'] ?? 0) + ($vax_targets_map['Cow']['target_hs'] ?? 0)) ?>,
                                    <?= intval(($vax_targets_map['Buffalo']['target_fmd'] ?? 0) + ($vax_targets_map['Buffalo']['target_bq'] ?? 0) + ($vax_targets_map['Buffalo']['target_hs'] ?? 0)) ?>,
                                    <?= intval(($vax_targets_map['Goat']['target_fmd'] ?? 0) + ($vax_targets_map['Goat']['target_bq'] ?? 0) + ($vax_targets_map['Goat']['target_hs'] ?? 0)) ?>,
                                    <?= intval(($vax_targets_map['Sheep']['target_fmd'] ?? 0) + ($vax_targets_map['Sheep']['target_bq'] ?? 0) + ($vax_targets_map['Sheep']['target_hs'] ?? 0)) ?>,
                                    <?= intval(($vax_targets_map['Pig']['target_fmd'] ?? 0) + ($vax_targets_map['Pig']['target_bq'] ?? 0) + ($vax_targets_map['Pig']['target_hs'] ?? 0)) ?>
                                ],
                                backgroundColor: '#e2e8f0',
                                borderColor: '#94a3b8',
                                borderWidth: 1,
                                borderRadius: 4
                            },
                            {
                                label: 'Achieved Doses',
                                data: [
                                    <?= intval(array_sum($achieved_by_species_vax['Cow'] ?? [])) ?>,
                                    <?= intval(array_sum($achieved_by_species_vax['Buffalo'] ?? [])) ?>,
                                    <?= intval(array_sum($achieved_by_species_vax['Goat'] ?? [])) ?>,
                                    <?= intval(array_sum($achieved_by_species_vax['Sheep'] ?? [])) ?>,
                                    <?= intval(array_sum($achieved_by_species_vax['Pig'] ?? [])) ?>
                                ],
                                backgroundColor: '#1e3a8a',
                                borderColor: '#172554',
                                borderWidth: 1,
                                borderRadius: 4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0 }
                            }
                        }
                    }
                });
            }
        }

        // Chart 3: Poultry Vaccination Tracking
        if (!chartPoultryInstance) {
            const ctxPoultry = document.getElementById('poultryVaxChart');
            if (ctxPoultry) {
                chartPoultryInstance = new Chart(ctxPoultry.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Doses Achieved', 'Remaining Target'],
                        datasets: [{
                            data: [
                                poultryAchievedVal,
                                Math.max(0, poultryTargetVal - poultryAchievedVal)
                            ],
                            backgroundColor: ['#f59e0b', '#e2e8f0'],
                            borderColor: ['#d97706', '#cbd5e1'],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: { position: 'bottom' },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.label + ': ' + context.raw.toLocaleString() + ' birds';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }
    }

    function updateTabUrl(tabKey) {
        const url = new URL(window.location);
        url.searchParams.set('tab', tabKey);
        window.history.replaceState({}, '', url);

        if (tabKey === 'charts') {
            setTimeout(function() {
                initCharts();
                if (chartLivestockInstance) chartLivestockInstance.resize();
                if (chartLivestockDiseaseInstance) chartLivestockDiseaseInstance.resize();
                if (chartPoultryInstance) chartPoultryInstance.resize();
            }, 150);
        }
    }

    function switchYear(y) {
        const url = new URL(window.location);
        url.searchParams.set('year', y);
        window.location = url.toString();
    }

    function switchRange(r) {
        const url = new URL(window.location);
        url.searchParams.set('range_id', r);
        window.location = url.toString();
    }

    $(document).ready(function() {
        // Initialize DataTables
        const sessionTable = $('#sessionLogsTable').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[1, 'desc']]
        });

        $('#sessionFilterInput').on('keyup', function() {
            sessionTable.search(this.value).draw();
        });

        $('#filterSessionCategory').on('change', function() {
            sessionTable.column(2).search(this.value).draw();
        });

        const combinedMatrixTable = $('#combinedMatrixTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [5, 10, 25]
        });

        $('#matrixFilter').on('keyup', function() {
            combinedMatrixTable.search(this.value).draw();
        });

        $('#casualVaccinatorsTable').DataTable({
            responsive: true,
            pageLength: 10
        });

        // Initialize charts if charts tab is active initially
        <?php if ($active_tab === 'charts'): ?>
            initCharts();
        <?php endif; ?>

        // Handle tab change for charts resizing
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            if (e.target.id === 'tab-btn-charts') {
                initCharts();
                if (chartLivestockInstance) chartLivestockInstance.resize();
                if (chartLivestockDiseaseInstance) chartLivestockDiseaseInstance.resize();
                if (chartPoultryInstance) chartPoultryInstance.resize();
            }
        });

        // Edit target row behavior
        $(document).on('click', '.edit-target-row-btn', function(e) {
            e.preventDefault();
            const dataset = $(this).data();

            const $modal = $('#addTargetModal');
            $modal.find('select[name="animal_type"]').val(dataset.species).trigger('change');
            $modal.find('#target_species_pop_display').val(dataset.qty);
            $modal.find('input[name="target_fmd"]').val(dataset.fmd);
            $modal.find('input[name="target_bq"]').val(dataset.bq);
            $modal.find('input[name="target_hs"]').val(dataset.hs);
            $modal.find('input[name="target_poultry_doses"]').val(dataset.poultry || 0);
            $modal.find('input[name="available_ldo_count"]').val(dataset.ldoCount);
            $modal.find('input[name="allocated_ldo_target"]').val(dataset.ldoTarget);
            $modal.find('input[name="allocated_man_days"]').val(dataset.manDays);
            $modal.find('select[name="assigned_vaccinator_id"]').val(dataset.assignedVaccinator || '');

            const bsModal = new bootstrap.Modal(document.getElementById('addTargetModal'));
            bsModal.show();
        });

        // Add target button behavior: clear modal
        $(document).on('click', '#addVaxTargetBtn', function(e) {
            const $modal = $('#addTargetModal');
            $modal.find('select[name="animal_type"]').val('').trigger('change');
            $modal.find('#target_species_pop_display').val('');
            $modal.find('input[name="target_fmd"]').val(0);
            $modal.find('input[name="target_bq"]').val(0);
            $modal.find('input[name="target_hs"]').val(0);
            $modal.find('input[name="target_poultry_doses"]').val(0);
            $modal.find('input[name="available_ldo_count"]').val(0);
            $modal.find('input[name="allocated_ldo_target"]').val(0);
            $modal.find('input[name="allocated_man_days"]').val(0);
            $modal.find('select[name="assigned_vaccinator_id"]').val('');
        });

        // Edit program session button behavior
        $(document).on('click', '.edit-session-btn', function(e) {
            e.preventDefault();
            const d = $(this).data();
            const $m = $('#modalVaccinationSession');

            $m.find('#vaxSessionModalTitle').text('Edit Vaccination Program');
            $m.find('#vax_session_action').val('edit');
            $m.find('#vax_session_id').val(d.id);
            $m.find('#vax_session_date').val(d.date);

            if (d.category === 'Poultry') {
                $('#vaxCatPoultry').prop('checked', true).trigger('change');
            } else {
                $('#vaxCatLivestock').prop('checked', true).trigger('change');
            }

            $m.find('#vax_session_animal_type').val(d.species);
            $m.find('#vax_session_vaccine_name').val(d.vaccine);
            if (!$m.find('#vax_session_vaccine_name').val()) {
                $m.find('#vax_session_vaccine_name').val('Other').trigger('change');
                $m.find('#vax_session_custom_vaccine').val(d.vaccine).removeClass('d-none');
            }

            // Multi-vaccinator prefill
            const ids = d.vaccinatorIds ? String(d.vaccinatorIds).split(',').map(x => x.trim()) : (d.vaccinatorId ? [String(d.vaccinatorId)] : []);
            $m.find('#vax_session_vaccinator_ids').val(ids);
            $m.find('#vax_session_vaccinator_manual').val(d.vaccinatorName);

            $m.find('#vax_session_count').val(d.count);
            $m.find('#vax_session_doses').val(d.doses);
            
            // Batch prefill
            $m.find('#vax_session_batch_select').val(d.batch || '');
            $m.find('#vax_session_batch_no').val(d.batch || '');
            $m.find('#vax_session_batch_select').trigger('change');

            $m.find('#vax_session_location').val(d.location);
            $m.find('#vax_session_remarks').val(d.remarks);

            const bsModal = new bootstrap.Modal(document.getElementById('modalVaccinationSession'));
            bsModal.show();
        });

        // Edit staff button: populate deploy staff modal and show
        $(document).on('click', '.edit-staff-btn', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const name = $(this).data('name');
            const nic = $(this).data('nic');

            const $modal = $('#deployPersonnelModal');
            $modal.find('input[name="full_name"]').val(name);
            $modal.find('input[name="nic_no"]').val(nic);
            $modal.find('input#deploy_staff_id').val(id);

            const bs = new bootstrap.Modal(document.getElementById('deployPersonnelModal'));
            bs.show();
        });

        // Add staff button behavior: clear modal fields
        $(document).on('click', '.add-staff-btn', function(e) {
            const $modal = $('#deployPersonnelModal');
            $modal.find('input[name="full_name"]').val('');
            $modal.find('input[name="nic_no"]').val('');
            $modal.find('input#deploy_staff_id').val('');
        });
    });

    function prepareAddSessionModal() {
        const $m = $('#modalVaccinationSession');
        $m.find('#vaxSessionModalTitle').text('Log Manual Vaccination Program');
        $m.find('#vax_session_action').val('add');
        $m.find('#vax_session_id').val('');
        $m.find('#vax_session_date').val(new Date().toISOString().split('T')[0]);
        $('#vaxCatLivestock').prop('checked', true).trigger('change');
        $m.find('#vax_session_animal_type').val('');
        $m.find('#vax_session_vaccine_name').val('');
        $m.find('#vax_session_custom_vaccine').val('').addClass('d-none');
        $m.find('#vax_session_vaccinator_ids').val([]);
        $m.find('#vax_session_vaccinator_manual').val('');
        $m.find('#vax_session_count').val('');
        $m.find('#vax_session_doses').val('');
        $m.find('#vax_session_batch_select').val('');
        $m.find('#vax_session_batch_no').val('');
        $m.find('#vax_session_batch_expiry').val('');
        $m.find('#vax_session_location').val('');
        $m.find('#vax_session_remarks').val('');
    }
</script>

<?php
require_once '../../../includes/footer.php';
?>