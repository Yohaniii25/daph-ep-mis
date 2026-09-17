<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

// 1. Session and Role Guard
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

if (!isset($_SESSION['full_name'])) {
    $_SESSION['full_name'] = $_SESSION['username'] ?? 'Veterinary Surgeon';
}

$full_name   = $_SESSION['full_name'];
$range_id    = $_SESSION['range_id'] ?? null;
$district_id = $_SESSION['district_id'] ?? null;

if (empty($range_id)) {
    die('<div class="alert alert-danger text-center p-5 m-5">Error: Your account is not assigned to any Veterinary Range.</div>');
}

// 2. Fallback Definitions & Active Tab Resolution
$district_name = 'Unknown District';
$range_name    = 'Unknown Range';
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$active_tab    = isset($_GET['tab']) ? trim($_GET['tab']) : 'sessions';
if (!in_array($active_tab, ['sessions', 'charts', 'targets', 'summaries', 'vaccinators'], true)) {
    $active_tab = 'sessions';
}

// 3. Fetch Core Structural Meta Information
if ($district_id) {
    $stmt = $mysqli->prepare("SELECT name FROM districts WHERE id = ?");
    $stmt->bind_param("i", $district_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $district_name = $row['name'];
    }
    $stmt->close();
}

if ($range_id) {
    $stmt = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
    $stmt->bind_param("i", $range_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $range_name = $row['name'];
    }
    $stmt->close();
}

// 4. Dynamic Data Fetch: Live Lookups against existing animal_populations table (Range Statistics Baseline)
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

// 5. Fetch Target Data from annual_vaccination_targets (Map by species)
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

// 6. Fetch Deployed Casual Vaccinators
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

// 7. Fetch Vaccination Session Logs for the Evaluation Year
$session_logs = [];
$total_livestock_achieved = 0;
$total_poultry_achieved = 0;
$monthly_achieved_livestock = array_fill(1, 12, 0);
$monthly_achieved_poultry   = array_fill(1, 12, 0);
$achieved_by_species_vax    = [];

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
        } else {
            $total_livestock_achieved += $cnt;
            if ($m >= 1 && $m <= 12) {
                $monthly_achieved_livestock[$m] += $cnt;
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
foreach ($vax_targets_map as $sp => $vt) {
    if (in_array($sp, ['Chicken', 'Broiler', 'Layer', 'Backyard Poultry', 'Duck'], true)) {
        $total_poultry_target += intval($vt['target_poultry_doses'] ?? 0);
    } else {
        $total_livestock_target += intval($vt['target_fmd'] ?? 0) + intval($vt['target_bq'] ?? 0) + intval($vt['target_hs'] ?? 0);
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
</style>

<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1" style="color: #370709;">Vaccination & Population Targets Module</h4>
        <span class="badge" style="background-color: #d4c7b7; color: #370709;"><i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($range_name) ?> Range</span>
        <span class="badge text-light" style="background-color: #a07174;"><i class="bi bi-building me-1"></i><?= htmlspecialchars($district_name) ?> District</span>
        <span class="badge bg-success text-light"><i class="bi bi-link-45deg me-1"></i>Range Statistics Baseline Synced</span>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <div class="input-group">
            <label class="input-group-text fw-bold text-light" style="background-color: #820100; border-color: #820100;">Evaluation Year</label>
            <select id="dashboardYearFilter" class="form-select border-secondary" onchange="switchYear(this.value);">
                <?php for ($y = date('Y') + 1; $y >= 2023; $y--): ?>
                    <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <button type="button" class="btn btn-sm btn-dark text-nowrap fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalVaccinationSession" onclick="prepareAddSessionModal();">
            <i class="bi bi-plus-circle me-1 text-warning"></i> Log Session
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
                    <span class="text-muted small fw-bold text-uppercase">Livestock Vaccination</span>
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
                    <span class="badge bg-light text-dark border"><?= count(array_filter($session_logs, fn($s) => $s['category'] === 'Livestock')) ?> sessions</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Poultry Target vs Achieved -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #f59e0b !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-bold text-uppercase">Poultry Vaccination</span>
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
                    <span class="badge bg-light text-dark border"><?= count(array_filter($session_logs, fn($s) => $s['category'] === 'Poultry')) ?> sessions</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Progressive Sessions Logged -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #10b981 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-bold text-uppercase">Total Sessions Logged</span>
                    <i class="bi bi-journal-check fs-5 text-success"></i>
                </div>
                <h4 class="fw-bold mb-0 text-dark"><?= count($session_logs) ?></h4>
                <div class="text-muted small mt-2">
                    Progressive manual logs for Year <?= htmlspecialchars($selected_year) ?>
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
                    Casual field staff assigned in <?= htmlspecialchars($range_name) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- NAVIGATION TABS                                                           -->
<!-- ========================================================================= -->
<ul class="nav nav-tabs mb-4 bg-white rounded-top shadow-xs px-2 pt-2 border-bottom" id="vaxModuleTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $active_tab === 'sessions' ? 'active' : '' ?>" id="tab-btn-sessions" data-bs-toggle="tab" data-bs-target="#tab-content-sessions" type="button" role="tab" onclick="updateTabUrl('sessions')">
            <i class="bi bi-journal-text me-2 text-danger"></i>Vaccination Sessions Log
            <span class="badge bg-secondary ms-1"><?= count($session_logs) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $active_tab === 'charts' ? 'active' : '' ?>" id="tab-btn-charts" data-bs-toggle="tab" data-bs-target="#tab-content-charts" type="button" role="tab" onclick="updateTabUrl('charts')">
            <i class="bi bi-bar-chart-line-fill me-2" style="color: #820100;"></i>Visual Tracking Charts
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $active_tab === 'targets' ? 'active' : '' ?>" id="tab-btn-targets" data-bs-toggle="tab" data-bs-target="#tab-content-targets" type="button" role="tab" onclick="updateTabUrl('targets')">
            <i class="bi bi-grid-3x3-gap-fill me-2 text-primary"></i>Annual Targets Matrix
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $active_tab === 'summaries' ? 'active' : '' ?>" id="tab-btn-summaries" data-bs-toggle="tab" data-bs-target="#tab-content-summaries" type="button" role="tab" onclick="updateTabUrl('summaries')">
            <i class="bi bi-calendar3-range me-2 text-warning"></i>Progress Summaries
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
    <!-- TAB 1: MANUAL VACCINATION SESSION REGISTER & FIELD LOGS              -->
    <!-- ───────────────────────────────────────────────────────────────────── -->
    <div class="tab-pane fade <?= $active_tab === 'sessions' ? 'show active' : '' ?>" id="tab-content-sessions" role="tabpanel">
        <div class="card gov-card mb-4 shadow-sm border-0">
            <div class="card-header bg-white pt-4 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1" style="color: #370709;">
                        <i class="bi bi-journal-text me-2 text-danger"></i>Manual Vaccination Sessions Register & Logs
                    </h5>
                    <p class="text-muted small mb-0">Progressively record and monitor individual vaccination field sessions by date, vaccinator, and head counts.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-dark fw-bold" data-bs-toggle="modal" data-bs-target="#modalVaccinationSession" onclick="prepareAddSessionModal();">
                        <i class="bi bi-plus-circle me-1 text-warning"></i> Log New Session
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
                        <input type="text" id="sessionFilterInput" class="form-control form-control-sm border-secondary" placeholder="Search date, species, vaccine, vaccinator...">
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="sessionLogsTable" class="table table-striped table-hover table-bordered align-middle small bg-white m-0">
                        <thead style="background-color: #d4c7b7; color: #370709;">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="width: 100px;">Date</th>
                                <th style="width: 90px;" class="text-center">Category</th>
                                <th>Species</th>
                                <th>Vaccine Type</th>
                                <th>Assigned Vaccinator / Personnel</th>
                                <th class="text-center">Animals Vaccinated</th>
                                <th class="text-center">Doses</th>
                                <th>Location & Remarks</th>
                                <th class="text-center" style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($session_logs)): ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-4 d-block mb-1"></i>No vaccination sessions logged yet for <?= htmlspecialchars($selected_year) ?>. Click <strong>"Log New Session"</strong> to begin.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $scnt = 1; foreach ($session_logs as $s): ?>
                                    <tr>
                                        <td><?= $scnt++ ?></td>
                                        <td class="text-nowrap fw-bold"><?= date('d M Y', strtotime($s['session_date'])) ?></td>
                                        <td class="text-center">
                                            <?php if ($s['category'] === 'Poultry'): ?>
                                                <span class="badge bg-warning-subtle text-dark border border-warning"><i class="bi bi-egg-fill me-1 text-warning"></i>Poultry</span>
                                            <?php else: ?>
                                                <span class="badge text-light" style="background-color: #820100;"><i class="bi bi-shield-fill me-1"></i>Livestock</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold"><?= htmlspecialchars($s['animal_type']) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($s['vaccine_name']) ?></span></td>
                                        <td><?= htmlspecialchars($s['vaccinator_name']) ?></td>
                                        <td class="text-center fw-bold fs-6 text-dark"><?= number_format($s['vaccinated_count']) ?></td>
                                        <td class="text-center"><?= number_format($s['doses_administered']) ?></td>
                                        <td class="small">
                                            <?php if (!empty($s['location_name'])): ?>
                                                <i class="bi bi-geo-alt text-danger me-1"></i><strong><?= htmlspecialchars($s['location_name']) ?></strong>
                                            <?php endif; ?>
                                            <?php if (!empty($s['batch_no'])): ?>
                                                <span class="badge bg-secondary ms-1">Batch: <?= htmlspecialchars($s['batch_no']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($s['remarks'])): ?>
                                                <div class="text-muted"><?= htmlspecialchars($s['remarks']) ?></div>
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
                                                data-vaccinator-name="<?= htmlspecialchars($s['vaccinator_name']) ?>"
                                                data-count="<?= intval($s['vaccinated_count']) ?>"
                                                data-doses="<?= intval($s['doses_administered']) ?>"
                                                data-batch="<?= htmlspecialchars($s['batch_no'] ?? '') ?>"
                                                data-location="<?= htmlspecialchars($s['location_name'] ?? '') ?>"
                                                data-remarks="<?= htmlspecialchars($s['remarks'] ?? '') ?>">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <form action="processors/vaccination_session_crud.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this vaccination session record?');">
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
    <!-- TAB 2: SEPARATE TRACKING CHARTS                                      -->
    <!-- ───────────────────────────────────────────────────────────────────── -->
    <div class="tab-pane fade <?= $active_tab === 'charts' ? 'show active' : '' ?>" id="tab-content-charts" role="tabpanel">
        <div class="row g-4 mb-4">
            <!-- Chart 1: Livestock Vaccination Tracking Chart -->
            <div class="col-12 col-lg-6">
                <div class="card gov-card shadow-sm border-0 h-100">
                    <div class="card-header bg-white pt-3 px-4 border-0 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-0" style="color: #370709;">
                                <i class="bi bi-bar-chart-fill me-2" style="color: #820100;"></i>Livestock Vaccination Tracking
                            </h6>
                            <p class="text-muted small mb-0">Target vs. Achieved doses for Cattle, Buffalo, Sheep, Goat & Pig.</p>
                        </div>
                        <span class="badge" style="background-color: #d4c7b7; color: #370709;">Livestock Only</span>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div style="position: relative; width: 100%; height: 320px;">
                            <canvas id="livestockVaxChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart 2: Poultry Vaccination Tracking Chart -->
            <div class="col-12 col-lg-6">
                <div class="card gov-card shadow-sm border-0 h-100">
                    <div class="card-header bg-white pt-3 px-4 border-0 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-0" style="color: #370709;">
                                <i class="bi bi-pie-chart-fill me-2 text-warning"></i>Poultry Vaccination Tracking
                            </h6>
                            <p class="text-muted small mb-0">Progressive doses administered vs. target across poultry diseases.</p>
                        </div>
                        <span class="badge bg-warning-subtle text-warning border border-warning">Poultry Only</span>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div style="position: relative; width: 100%; height: 320px;">
                            <canvas id="poultryVaxChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ───────────────────────────────────────────────────────────────────── -->
    <!-- TAB 3: TARGETS MATRIX & PROGRESS SUMMARIES (REORDERED)                -->
    <!-- ───────────────────────────────────────────────────────────────────── -->
    <div class="tab-pane fade <?= $active_tab === 'targets' ? 'show active' : '' ?>" id="tab-content-targets" role="tabpanel">

        <!-- 1. Annual Target Configurations & Deployment Matrix (BEFORE Summaries as requested) -->
        <div class="card gov-card mb-4 shadow-sm border-0">
            <div class="card-header bg-white pt-4 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1" style="color: #370709;">
                        <i class="bi bi-grid-3x3-gap-fill me-2 text-danger"></i>Annual Target Configurations & Deployment Matrix
                    </h5>
                    <p class="text-muted small mb-0">Configured annual targets and personnel assignments aligned with live Range Statistics baselines.</p>
                </div>
                <div>
                    <button class="btn btn-sm btn-dark fw-bold me-2" data-bs-toggle="modal" data-bs-target="#addTargetModal" id="addVaxTargetBtn">
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
    </div>

    <!-- ───────────────────────────────────────────────────────────────────── -->
    <!-- TAB 4: PROGRESS SUMMARIES: ANNUAL VS. MONTHLY AGGREGATES             -->
    <!-- ───────────────────────────────────────────────────────────────────── -->
    <div class="tab-pane fade <?= $active_tab === 'summaries' ? 'show active' : '' ?>" id="tab-content-summaries" role="tabpanel">
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
                    <!-- TAB 1: Annual Target vs Achieved Summary -->
                    <div class="tab-pane fade show active" id="pills-annual" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle small bg-white m-0">
                                <thead style="background-color: #d4c7b7; color: #370709;">
                                    <tr>
                                        <th>Animal Category / Species</th>
                                        <th class="text-center">Synchronized Live Population</th>
                                        <th class="text-center">Target Metric</th>
                                        <th class="text-center">Fixed Annual Target</th>
                                        <th class="text-center">Actual Vaccinated (Session Logs)</th>
                                        <th class="text-center" style="width: 200px;">Progress & Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sp_list = ['Cow', 'Buffalo', 'Goat', 'Sheep', 'Pig', 'Chicken'];
                                    foreach ($sp_list as $sp):
                                        $pop = $animal_pop_data[$sp] ?? 0;
                                        $vt = $vax_targets_map[$sp] ?? [];
                                        
                                        if ($sp === 'Chicken') {
                                            $target = intval($vt['target_poultry_doses'] ?? 0);
                                            $achieved = $total_poultry_achieved;
                                            $pct = ($target > 0) ? min(100, round(($achieved / $target) * 100, 1)) : ($achieved > 0 ? 100 : 0);
                                            $badge_class = ($pct >= 100) ? 'bg-success' : (($pct >= 50) ? 'bg-warning text-dark' : 'bg-danger');
                                            ?>
                                            <tr>
                                                <td class="fw-bold"><i class="bi bi-egg-fill text-warning me-1"></i>Chicken / Poultry</td>
                                                <td class="text-center fw-bold"><?= number_format($pop) ?></td>
                                                <td class="text-center"><span class="badge bg-light text-dark border">Poultry Vaccines</span></td>
                                                <td class="text-center fw-bold"><?= number_format($target) ?></td>
                                                <td class="text-center fw-bold fs-6 text-success"><?= number_format($achieved) ?></td>
                                                <td>
                                                    <div class="progress" style="height: 6px;">
                                                        <div class="progress-bar <?= $badge_class ?>" style="width: <?= $pct ?>%;"></div>
                                                    </div>
                                                    <div class="d-flex justify-content-between small mt-1">
                                                        <span><?= $pct ?>%</span>
                                                        <span class="badge <?= $badge_class ?>"><?= $pct >= 100 ? 'Exceeded/Met' : 'In Progress' ?></span>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php
                                        } else {
                                            $vax_items = [
                                                'FMD' => intval($vt['target_fmd'] ?? 0),
                                                'BQ'  => intval($vt['target_bq'] ?? 0),
                                                'HS'  => intval($vt['target_hs'] ?? 0)
                                            ];
                                            foreach ($vax_items as $vname => $target):
                                                $achieved = $achieved_by_species_vax[$sp][$vname] ?? 0;
                                                $pct = ($target > 0) ? min(100, round(($achieved / $target) * 100, 1)) : ($achieved > 0 ? 100 : 0);
                                                $badge_class = ($pct >= 100) ? 'bg-success' : (($pct >= 50) ? 'bg-warning text-dark' : 'bg-danger');
                                                ?>
                                                <tr>
                                                    <td class="fw-bold"><i class="bi bi-shield-fill text-primary me-1"></i><?= htmlspecialchars($sp) ?></td>
                                                    <td class="text-center fw-bold"><?= number_format($pop) ?></td>
                                                    <td class="text-center"><span class="badge bg-light text-dark border"><?= $vname ?></span></td>
                                                    <td class="text-center fw-bold"><?= number_format($target) ?></td>
                                                    <td class="text-center fw-bold fs-6 text-primary"><?= number_format($achieved) ?></td>
                                                    <td>
                                                        <div class="progress" style="height: 6px;">
                                                            <div class="progress-bar <?= $badge_class ?>" style="width: <?= $pct ?>%;"></div>
                                                        </div>
                                                        <div class="d-flex justify-content-between small mt-1">
                                                            <span><?= $pct ?>%</span>
                                                            <span class="badge <?= $badge_class ?>"><?= $pct >= 100 ? 'Exceeded/Met' : 'In Progress' ?></span>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php
                                            endforeach;
                                        }
                                    endforeach;
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 2: Monthly Breakdown Matrix (Jan - Dec) -->
                    <div class="tab-pane fade" id="pills-monthly" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle small bg-white text-center m-0">
                                <thead style="background-color: #d4c7b7; color: #370709;">
                                    <tr>
                                        <th class="text-start">Category / Metric</th>
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <th><?= date('M', mktime(0, 0, 0, $m, 1)) ?></th>
                                        <?php endfor; ?>
                                        <th class="table-dark">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-start fw-bold text-danger"><i class="bi bi-shield-fill me-1"></i>Livestock Doses</td>
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <td class="<?= $monthly_achieved_livestock[$m] > 0 ? 'fw-bold text-dark' : 'text-muted' ?>">
                                                <?= number_format($monthly_achieved_livestock[$m]) ?>
                                            </td>
                                        <?php endfor; ?>
                                        <td class="fw-bold table-light"><?= number_format($total_livestock_achieved) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-start fw-bold text-warning"><i class="bi bi-egg-fill me-1"></i>Poultry Doses</td>
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <td class="<?= $monthly_achieved_poultry[$m] > 0 ? 'fw-bold text-dark' : 'text-muted' ?>">
                                                <?= number_format($monthly_achieved_poultry[$m]) ?>
                                            </td>
                                        <?php endfor; ?>
                                        <td class="fw-bold table-light"><?= number_format($total_poultry_achieved) ?></td>
                                    </tr>
                                    <tr class="fw-bold" style="background-color: #f8fafc;">
                                        <td class="text-start">Combined Monthly Total</td>
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <?php $m_tot = $monthly_achieved_livestock[$m] + $monthly_achieved_poultry[$m]; ?>
                                            <td class="<?= $m_tot > 0 ? 'text-primary' : 'text-muted' ?>">
                                                <?= number_format($m_tot) ?>
                                            </td>
                                        <?php endfor; ?>
                                        <td class="table-dark"><?= number_format($total_livestock_achieved + $total_poultry_achieved) ?></td>
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
    <!-- TAB 4: CASUAL VACCINATOR DEPLOYMENTS TABLE                           -->
    <!-- ───────────────────────────────────────────────────────────────────── -->
    <div class="tab-pane fade <?= $active_tab === 'vaccinators' ? 'show active' : '' ?>" id="tab-content-vaccinators" role="tabpanel">
        <div class="card gov-card mb-4 shadow-sm border-0">
            <div class="card-header bg-white pt-3 px-4 border-0 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold mb-0" style="color: #370709;"><i class="bi bi-people-fill me-2 text-danger"></i>Casual Vaccinator Deployments</h6>
                    <p class="text-muted small mb-0">Manage registered casual vaccinators deployed for this range/year.</p>
                </div>
                <div>
                    <button class="btn btn-sm btn-dark fw-bold add-staff-btn" data-bs-toggle="modal" data-bs-target="#deployPersonnelModal"><i class="bi bi-plus-circle me-1"></i> Add / Deploy Vaccinator</button>
                </div>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="table-responsive">
                    <table id="casualVaccinatorsTable" class="table table-sm table-striped table-bordered align-middle small bg-white m-0">
                        <thead style="background-color: #d4c7b7; color: #370709;">
                            <tr>
                                <th style="width: 60px;">#</th>
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
    let chartPoultryInstance = null;

    function initCharts() {
        if (!chartLivestockInstance) {
            const ctxLivestock = document.getElementById('livestockVaxChart');
            if (ctxLivestock) {
                chartLivestockInstance = new Chart(ctxLivestock.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: ['Cattle (Cow)', 'Buffalo', 'Goat', 'Sheep', 'Pig', 'Combined Total'],
                        datasets: [
                            {
                                label: 'Fixed Annual Target',
                                data: [
                                    <?= intval(($vax_targets_map['Cow']['target_fmd'] ?? 0) + ($vax_targets_map['Cow']['target_bq'] ?? 0) + ($vax_targets_map['Cow']['target_hs'] ?? 0)) ?>,
                                    <?= intval(($vax_targets_map['Buffalo']['target_fmd'] ?? 0) + ($vax_targets_map['Buffalo']['target_bq'] ?? 0) + ($vax_targets_map['Buffalo']['target_hs'] ?? 0)) ?>,
                                    <?= intval(($vax_targets_map['Goat']['target_fmd'] ?? 0) + ($vax_targets_map['Goat']['target_bq'] ?? 0) + ($vax_targets_map['Goat']['target_hs'] ?? 0)) ?>,
                                    <?= intval(($vax_targets_map['Sheep']['target_fmd'] ?? 0) + ($vax_targets_map['Sheep']['target_bq'] ?? 0) + ($vax_targets_map['Sheep']['target_hs'] ?? 0)) ?>,
                                    <?= intval(($vax_targets_map['Pig']['target_fmd'] ?? 0) + ($vax_targets_map['Pig']['target_bq'] ?? 0) + ($vax_targets_map['Pig']['target_hs'] ?? 0)) ?>,
                                    <?= intval($total_livestock_target) ?>
                                ],
                                backgroundColor: '#d4c7b7',
                                borderColor: '#a07174',
                                borderWidth: 1,
                                borderRadius: 4
                            },
                            {
                                label: 'Progress / Achieved',
                                data: [
                                    <?= intval(array_sum($achieved_by_species_vax['Cow'] ?? [])) ?>,
                                    <?= intval(array_sum($achieved_by_species_vax['Buffalo'] ?? [])) ?>,
                                    <?= intval(array_sum($achieved_by_species_vax['Goat'] ?? [])) ?>,
                                    <?= intval(array_sum($achieved_by_species_vax['Sheep'] ?? [])) ?>,
                                    <?= intval(array_sum($achieved_by_species_vax['Pig'] ?? [])) ?>,
                                    <?= intval($total_livestock_achieved) ?>
                                ],
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
                if (chartPoultryInstance) chartPoultryInstance.resize();
            }, 150);
        }
    }

    function switchYear(y) {
        const url = new URL(window.location);
        url.searchParams.set('year', y);
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
                if (chartPoultryInstance) chartPoultryInstance.resize();
            }
        });

        // Edit target row behavior: populate target modal with identical grouped species dropdown matching session modal
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

        // Edit session button behavior
        $(document).on('click', '.edit-session-btn', function(e) {
            e.preventDefault();
            const d = $(this).data();
            const $m = $('#modalVaccinationSession');

            $m.find('#vaxSessionModalTitle').text('Edit Vaccination Session');
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

            $m.find('#vax_session_vaccinator_id').val(d.vaccinatorId || 0);
            $m.find('#vax_session_vaccinator_manual').val(d.vaccinatorName);
            $m.find('#vax_session_count').val(d.count);
            $m.find('#vax_session_doses').val(d.doses);
            $m.find('#vax_session_batch_no').val(d.batch);
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
        $m.find('#vaxSessionModalTitle').text('Log Manual Vaccination Session');
        $m.find('#vax_session_action').val('add');
        $m.find('#vax_session_id').val('');
        $m.find('#vax_session_date').val(new Date().toISOString().split('T')[0]);
        $('#vaxCatLivestock').prop('checked', true).trigger('change');
        $m.find('#vax_session_animal_type').val('');
        $m.find('#vax_session_vaccine_name').val('');
        $m.find('#vax_session_custom_vaccine').val('').addClass('d-none');
        $m.find('#vax_session_vaccinator_id').val('');
        $m.find('#vax_session_vaccinator_manual').val('');
        $m.find('#vax_session_count').val('');
        $m.find('#vax_session_doses').val('');
        $m.find('#vax_session_batch_no').val('');
        $m.find('#vax_session_location').val('');
        $m.find('#vax_session_remarks').val('');
    }
</script>

<?php
require_once '../../../includes/footer.php';
?>