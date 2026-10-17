<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

$range_id = $_SESSION['range_id'] ?? null;
if (empty($range_id)) {
    die('<div class="alert alert-danger text-center p-5 m-5">Error: Account not assigned to a Range.</div>');
}

require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$range_name = 'Unknown Range';
$stmt = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
$stmt->bind_param("i", $range_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if ($res) $range_name = $res['name'];
$stmt->close();

$active_tab = ($_GET['tab'] ?? '') === 'monitoring' ? 'monitoring' : 'projects';

$counts = [
    'PSDG'    => 0,
    'LMP'     => 0,
    'CBG'     => 0,
    'Special' => 0,
    'Other'   => 0
];

$count_query = "SELECT project_type, COUNT(*) as total 
                FROM projects_progress 
                WHERE range_id = ? 
                GROUP BY project_type";

$c_stmt = $mysqli->prepare($count_query);
$c_stmt->bind_param("i", $range_id);
$c_stmt->execute();
$count_result = $c_stmt->get_result();

while ($row = $count_result->fetch_assoc()) {
    if (isset($counts[$row['project_type']])) {
        $counts[$row['project_type']] = $row['total'];
    }
}
$c_stmt->close();

$psdg_count    = $counts['PSDG'];
$lmp_count     = $counts['LMP'];
$cbg_count     = $counts['CBG'];
$special_count = $counts['Special'];
$other_count   = $counts['Other'];

// Fetch Projects
$list_stmt = $mysqli->prepare("SELECT * FROM projects_progress WHERE range_id = ? ORDER BY id DESC");
$list_stmt->bind_param("i", $range_id);
$list_stmt->execute();
$projects = $list_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$list_stmt->close();

// Fetch Beneficiary Counts per Project
$b_counts = [];
$b_res = $mysqli->query("SELECT project_id, COUNT(*) as cnt FROM `project_individual_beneficiaries` GROUP BY project_id");
if ($b_res) {
    while ($br = $b_res->fetch_assoc()) {
        $b_counts[$br['project_id']] = intval($br['cnt']);
    }
}

// Fetch Scheduled Monitoring Visits
$v_stmt = $mysqli->prepare("SELECT * FROM `project_monitoring_visits` WHERE range_id = ? OR range_id IS NULL ORDER BY visit_date DESC, id DESC");
$v_stmt->bind_param("i", $range_id);
$v_stmt->execute();
$monitoring_visits = $v_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$v_stmt->close();

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">

        <!-- Unified Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h4 mb-0 fw-bold text-uppercase">Projects and Monitoring</h2>
                <small class="text-muted"><?= htmlspecialchars($range_name) ?> | Unified Projects Registry &amp; Scheduled Field Monitoring</small>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#scheduledVisitModal">
                    <i class="bi bi-journal-plus me-1"></i> Record Site Visit
                </button>
                <button class="btn btn-primary shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#addProjectModal">
                    <i class="bi bi-plus-circle me-1"></i> Add Project
                </button>
            </div>
        </div>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- KPI Metrics Ribbon -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-3 border-start border-primary border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">PSDG Projects (23 Schemes)</h6>
                    <h3 class="mb-0 text-dark"><?= $psdg_count ?></h3>
                    <small class="text-muted">Goat, Cattle, Poultry, Forage</small>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-3 border-start border-success border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">LMP Projects</h6>
                    <h3 class="mb-0 text-dark"><?= $lmp_count ?></h3>
                    <small class="text-muted">Livestock Modernization</small>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-3 border-start border-warning border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">CBG &amp; Special</h6>
                    <h3 class="mb-0 text-dark"><?= $cbg_count + $special_count ?></h3>
                    <small class="text-muted">Community &amp; Special Grants</small>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-3 border-start border-info border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">Monitoring Site Visits</h6>
                    <h3 class="mb-0 text-dark"><?= count($monitoring_visits) ?></h3>
                    <small class="text-muted">1M, 3M, 6M &amp; Bi-Annual Inspections</small>
                </div>
            </div>
        </div>

        <!-- Unified Navigation Tabs -->
        <ul class="nav nav-tabs nav-fill mb-4 bg-white rounded shadow-sm p-1 border">
            <li class="nav-item">
                <a class="nav-link fw-bold py-3 <?= ($active_tab === 'projects') ? 'active text-primary' : 'text-secondary' ?>" id="projects-tab" data-bs-toggle="tab" href="#tab-projects" role="tab">
                    <i class="bi bi-kanban me-2"></i>1. Projects Registry &amp; Beneficiaries Overview
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link fw-bold py-3 <?= ($active_tab === 'monitoring') ? 'active text-primary' : 'text-secondary' ?>" id="monitoring-tab" data-bs-toggle="tab" href="#tab-monitoring" role="tab">
                    <i class="bi bi-clipboard2-pulse me-2"></i>2. Scheduled Monitoring Logs &amp; Site Visits
                </a>
            </li>
        </ul>

        <div class="tab-content" id="projectsMonitoringTabContent">
            
            <!-- ============================================================= -->
            <!-- TAB 1: PROJECTS REGISTRY (EXACT 8 COLUMNS + SUB-PAGE LINK)   -->
            <!-- ============================================================= -->
            <div class="tab-pane fade <?= ($active_tab === 'projects') ? 'show active' : '' ?>" id="tab-projects" role="tabpanel">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-list-task me-2 text-primary"></i>Projects Progress &amp; Beneficiary Registers</h5>
                        <div id="exportButtons"></div>
                    </div>

                    <div class="card-body">
                        <div class="row g-3 mb-4 bg-light p-3 rounded border">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">Filter by Funding Agent / Type</label>
                                <select id="filterType" class="form-select form-select-sm">
                                    <option value="">All Funding Agents</option>
                                    <option value="PSDG">PSDG</option>
                                    <option value="LMP">LMP</option>
                                    <option value="CBG">CBG</option>
                                    <option value="Special">Special Project</option>
                                    <option value="Ministry">Ministry Works</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">From Start Date</label>
                                <input type="date" id="minDate" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">To Expected End Date</label>
                                <input type="date" id="maxDate" class="form-control form-control-sm">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="projectsTable">
                                <thead class="table-light small text-uppercase">
                                    <tr>
                                        <th class="text-center" style="width: 70px;">Serial Number</th>
                                        <th>Activity</th>
                                        <th>Funding Agent</th>
                                        <th>Department Contribution (Amount &amp; Activity)</th>
                                        <th>Beneficiary Contribution (Amount &amp; Activity)</th>
                                        <th>Target Numbers</th>
                                        <th>Start Date</th>
                                        <th>Expected End Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $serial_no = 1;
                                    foreach ($projects as $proj): 
                                        $activity = !empty($proj['activity']) ? $proj['activity'] : (!empty($proj['project_name']) ? $proj['project_name'] : 'Livestock Development Activity');
                                        $funding_agent = !empty($proj['funding_agent']) ? $proj['funding_agent'] : (!empty($proj['project_type']) ? $proj['project_type'] . ' Programme' : 'Department Funding');
                                        
                                        $dept_amount = floatval($proj['dept_contribution_amount'] ?? 0);
                                        $dept_subsidy = floatval($proj['dept_subsidy_percentage'] ?? 100);
                                        $dept_activity = !empty($proj['dept_contribution_activity']) ? $proj['dept_contribution_activity'] : 'Department Subsidy / Material Grant';
                                        
                                        $ben_amount = floatval($proj['beneficiary_contribution_amount'] ?? 0);
                                        $ben_activity = !empty($proj['beneficiary_contribution_activity']) ? $proj['beneficiary_contribution_activity'] : 'Farmer Equity / Material & Labor';
                                        
                                        $target_num = intval($proj['target_beneficiaries'] ?? 0);
                                        $enrolled_bens = $b_counts[$proj['id']] ?? 0;
                                        $start_date = !empty($proj['start_date']) ? $proj['start_date'] : '-';
                                        $end_date = !empty($proj['end_date']) ? $proj['end_date'] : '-';
                                    ?>
                                        <tr>
                                            <td class="text-center fw-bold text-secondary"><?= $serial_no++ ?></td>
                                            <td>
                                                <div class="fw-bold text-primary"><?= htmlspecialchars($activity) ?></div>
                                                <div class="mt-1 d-flex align-items-center gap-1">
                                                    <?php if (!empty($proj['project_type'])): ?>
                                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($proj['project_type']) ?></span>
                                                    <?php endif; ?>
                                                    <!-- Sub-Page Link Button -->
                                                    <a href="project_beneficiaries.php?project_id=<?= $proj['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 11px;">
                                                        <i class="bi bi-people me-1"></i>Beneficiaries (<?= $enrolled_bens ?>/<?= $target_num ?: 'Open' ?>) &rarr;
                                                    </a>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1">
                                                    <i class="bi bi-bank me-1"></i><?= htmlspecialchars($funding_agent) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-success">
                                                    Rs. <?= number_format($dept_amount, 2) ?>
                                                    <?php if ($dept_subsidy > 0): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle ms-1"><?= $dept_subsidy ?>% Subsidy</span>
                                                    <?php endif; ?>
                                                </div>
                                                <small class="text-muted d-block mt-1"><?= htmlspecialchars($dept_activity) ?></small>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-primary">
                                                    Rs. <?= number_format($ben_amount, 2) ?>
                                                </div>
                                                <small class="text-muted d-block mt-1"><?= htmlspecialchars($ben_activity) ?></small>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-people text-secondary me-2"></i>
                                                    <span class="fw-semibold"><?= $target_num > 0 ? number_format($target_num) . ' Beneficiaries' : 'Not Specified' ?></span>
                                                </div>
                                                <small class="text-muted d-block mt-1"><?= $enrolled_bens ?> Enrolled</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><i class="bi bi-calendar-event me-1 text-primary"></i><?= htmlspecialchars($start_date) ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><i class="bi bi-calendar-check me-1 text-danger"></i><?= htmlspecialchars($end_date) ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================= -->
            <!-- TAB 2: SCHEDULED MONITORING LOGS (1M, 3M, 6M, BI-ANNUAL)      -->
            <!-- ============================================================= -->
            <div class="tab-pane fade <?= ($active_tab === 'monitoring') ? 'show active' : '' ?>" id="tab-monitoring" role="tabpanel">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold text-dark">
                                <i class="bi bi-clipboard2-pulse me-2 text-danger"></i>Scheduled Site Monitoring Visits (Veterinary Surgeon Logs)
                            </h5>
                            <small class="text-muted">Mandatory milestone visits at 1 Month, 3 Months, 6 Months, and Bi-Annually</small>
                        </div>
                        <button class="btn btn-dark btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#scheduledVisitModal">
                            <i class="bi bi-plus-lg me-1"></i> Log Field Inspection
                        </button>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="monitoringLogsTable">
                                <thead class="table-light small text-uppercase">
                                    <tr>
                                        <th>Date &amp; Scheduled Interval</th>
                                        <th>Farm / Beneficiary Details</th>
                                        <th>Farm Conditions Evaluation</th>
                                        <th>Animal Illnesses</th>
                                        <th>Mortality (Deaths)</th>
                                        <th>Natural Disasters</th>
                                        <th>Surgeon Recommendations</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($monitoring_visits)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted">
                                                <i class="bi bi-journal-x fs-1 d-block mb-2"></i>
                                                No scheduled monitoring visits logged yet. Use the "Record Site Visit" button to add milestone inspections.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($monitoring_visits as $mv): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 fw-bold">
                                                        <i class="bi bi-clock-history me-1"></i><?= htmlspecialchars($mv['visit_interval']) ?>
                                                    </span>
                                                    <div class="small text-muted mt-1 font-monospace"><i class="bi bi-calendar me-1"></i><?= htmlspecialchars($mv['visit_date']) ?></div>
                                                    <small class="text-secondary d-block"><i class="bi bi-person-check me-1"></i><?= htmlspecialchars($mv['veterinary_surgeon_name']) ?></small>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($mv['farmer_name'] ?: 'Registered Farmer') ?></div>
                                                    <?php if (!empty($mv['farm_reg_no'])): ?>
                                                        <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($mv['farm_reg_no']) ?></span>
                                                    <?php endif; ?>
                                                    <span class="badge bg-secondary-subtle text-secondary border"><?= htmlspecialchars($mv['source_module']) ?></span>
                                                </td>
                                                <td>
                                                    <div class="small text-dark" style="max-width: 260px;">
                                                        <?= nl2br(htmlspecialchars($mv['farm_conditions'])) ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if (!empty($mv['animal_illnesses'])): ?>
                                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle mb-1">Illness Noted</span>
                                                        <div class="small text-muted" style="max-width: 200px;"><?= htmlspecialchars($mv['animal_illnesses']) ?></div>
                                                    <?php else: ?>
                                                        <span class="badge bg-success-subtle text-success border">Healthy</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (intval($mv['deaths_count']) > 0): ?>
                                                        <span class="badge bg-danger"><?= $mv['deaths_count'] ?> Deaths</span>
                                                        <div class="small text-danger mt-1"><?= htmlspecialchars($mv['deaths_details']) ?></div>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-muted border">0 Mortality</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($mv['natural_disasters'])): ?>
                                                        <span class="badge bg-danger-subtle text-danger border"><i class="bi bi-cloud-lightning me-1"></i>Disaster Alert</span>
                                                        <div class="small text-muted mt-1" style="max-width: 180px;"><?= htmlspecialchars($mv['natural_disasters']) ?></div>
                                                    <?php else: ?>
                                                        <span class="text-muted small">None Reported</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="small text-secondary" style="max-width: 220px;">
                                                        <?= nl2br(htmlspecialchars($mv['recommendations'] ?: 'Routine follow-up per standard operating protocol.')) ?>
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

    </main>
</div>

<!-- ========================================================================= -->
<!-- MODAL: RECORD SCHEDULED MONITORING VISIT (DIRECT FROM PROJECTS PROGRESS) -->
<!-- ========================================================================= -->
<div class="modal fade" id="scheduledVisitModal" tabindex="-1" aria-labelledby="scheduledVisitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title text-white" id="scheduledVisitModalLabel">
                    <i class="bi bi-clipboard2-pulse me-2"></i>Record Scheduled Site Monitoring Visit
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="unifiedMonitoringForm">
                <input type="hidden" name="action" value="save_monitoring_visit">
                <input type="hidden" name="source_module" value="Project">
                <input type="hidden" name="range_name" value="<?= htmlspecialchars($range_name) ?>">
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        
                        <!-- Link to Project -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Associated Project</label>
                            <select class="form-select" name="project_id" id="mv_project_id">
                                <option value="">Select Project (Optional)...</option>
                                <?php foreach ($projects as $pj): ?>
                                    <option value="<?= $pj['id'] ?>"><?= htmlspecialchars($pj['project_name'] ?: $pj['activity']) ?> (<?= $pj['project_type'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Beneficiary / Farmer Name -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Farmer / Beneficiary Name</label>
                            <input type="text" class="form-control" name="farmer_name" placeholder="e.g. Kumara Perera / S. Mohamed">
                        </div>

                        <!-- Farm Reg No -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-uppercase">Farm Reg No (If Any)</label>
                            <input type="text" class="form-control font-monospace" name="farm_reg_no" placeholder="e.g. FR-EP-1049">
                        </div>

                        <!-- Scheduled Interval -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-uppercase">Scheduled Interval <span class="text-danger">*</span></label>
                            <select class="form-select" name="visit_interval" required>
                                <option value="1 Month" selected>1 Month Inspection</option>
                                <option value="3 Months">3 Months Inspection</option>
                                <option value="6 Months">6 Months Inspection</option>
                                <option value="Bi-Annual (12 Mo)">Bi-Annual (12 Months)</option>
                                <option value="Bi-Annual (18 Mo)">Bi-Annual (18 Months)</option>
                                <option value="Bi-Annual (24 Mo)">Bi-Annual (24 Months)</option>
                                <option value="Routine / Ad-Hoc">Routine / Emergency Visit</option>
                            </select>
                        </div>

                        <!-- Visit Date -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-uppercase">Inspection Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="visit_date" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <!-- Inspecting Surgeon -->
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-uppercase">Inspecting Veterinary Surgeon</label>
                            <input type="text" class="form-control" name="veterinary_surgeon_name" value="<?= htmlspecialchars($_SESSION['full_name'] ?? 'Veterinary Surgeon') ?>" required>
                        </div>

                        <!-- Farm Conditions -->
                        <div class="col-12">
                            <label class="form-label fw-bold small text-uppercase">Farm Conditions Evaluation <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="farm_conditions" rows="3" required placeholder="Assess shed structure, ventilation, clean water access, bio-security, fodder supply, and waste handling..."></textarea>
                        </div>

                        <!-- Animal Illnesses -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Animal Illnesses Observed</label>
                            <textarea class="form-control" name="animal_illnesses" rows="2" placeholder="Record symptoms, clinical diagnosis, and treatments administered..."></textarea>
                        </div>

                        <!-- Natural Disasters -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Natural Disasters / Flood / Drought</label>
                            <textarea class="form-control" name="natural_disasters" rows="2" placeholder="Note flash floods, prolonged drought, storm damage, or predator attacks..."></textarea>
                        </div>

                        <!-- Deaths / Mortality -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-uppercase">Deaths Count</label>
                            <input type="number" class="form-control" name="deaths_count" min="0" value="0">
                        </div>

                        <div class="col-md-9">
                            <label class="form-label fw-bold small text-uppercase">Mortality Cause &amp; Details</label>
                            <input type="text" class="form-control" name="deaths_details" placeholder="Species, animal ID/tag, post-mortem findings...">
                        </div>

                        <!-- Recommendations -->
                        <div class="col-12">
                            <label class="form-label fw-bold small text-uppercase">Veterinary Surgeon Directives &amp; Next Action</label>
                            <textarea class="form-control" name="recommendations" rows="2" placeholder="Prescribe management adjustments, vaccination requirements, next visit target..."></textarea>
                        </div>

                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                        <i class="bi bi-save me-1"></i> Save Inspection Log
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<?php include 'model/add_project_modal.php'; ?>

<script>
    $(document).ready(function() {
        var table = $('#projectsTable').DataTable({
            dom: 'rtip',
            pageLength: 15,
            order: [[0, 'asc']],
            buttons: [
                {
                    extend: 'print',
                    className: 'btn btn-sm btn-success shadow-sm',
                    exportOptions: { columns: ':visible' }
                },
                {
                    extend: 'csv',
                    className: 'btn btn-sm btn-danger shadow-sm',
                    exportOptions: { columns: ':visible' }
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-sm btn-warning shadow-sm',
                    exportOptions: { columns: ':visible' }
                }
            ]
        });

        // Move Export Buttons to the Card Header
        table.buttons().container().appendTo('#exportButtons');

        // Custom Type Filter Logic on Funding Agent column (index 2)
        $('#filterType').on('change', function() {
            table.column(2).search(this.value).draw();
        });

        // Custom Date Range Filter Logic on Start Date (index 6) and Expected End Date (index 7)
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                var min = $('#minDate').val();
                var max = $('#maxDate').val();
                var startDate = data[6] ? data[6].trim() : ''; 
                var endDate = data[7] ? data[7].trim() : '';

                if (min === "" && max === "") return true;
                if (min !== "" && startDate !== "" && startDate < min) return false;
                if (max !== "" && endDate !== "" && endDate > max) return false;
                return true;
            }
        );

        $('#minDate, #maxDate').on('change', function() {
            table.draw();
        });

        $('#monitoringLogsTable').DataTable({
            pageLength: 10,
            order: [[0, 'desc']]
        });

        // Save Unified Monitoring Visit AJAX
        $('#unifiedMonitoringForm').on('submit', function(e) {
            e.preventDefault();
            $.post('processors/project_monitoring_crud.php', $(this).serialize(), function(res) {
                if (res.success) {
                    alert(res.message);
                    location.reload();
                } else {
                    alert('Error: ' + res.message);
                }
            }, 'json');
        });
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>