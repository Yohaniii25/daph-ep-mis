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

$project_id = intval($_GET['project_id'] ?? 0);
if ($project_id <= 0) {
    // If not provided, fetch the latest active project
    $q_first = $mysqli->query("SELECT id FROM `projects_progress` ORDER BY id DESC LIMIT 1");
    if ($q_first && $r_first = $q_first->fetch_assoc()) {
        $project_id = intval($r_first['id']);
    } else {
        header("Location: projects_progress.php");
        exit();
    }
}

// Fetch Project Details
$p_stmt = $mysqli->prepare("SELECT * FROM `projects_progress` WHERE id = ?");
$p_stmt->bind_param("i", $project_id);
$p_stmt->execute();
$project = $p_stmt->get_result()->fetch_assoc();
$p_stmt->close();

if (!$project) {
    die('<div class="alert alert-danger text-center p-5 m-5">Error: Project not found. <a href="projects_progress.php" class="btn btn-secondary btn-sm ms-2">Back to Projects</a></div>');
}

$range_name = $project['range_office_name'] ?: 'Range Office';

// Fetch All Individual Beneficiaries for this Project
$b_stmt = $mysqli->prepare("SELECT * FROM `project_individual_beneficiaries` WHERE project_id = ? ORDER BY id DESC");
$b_stmt->bind_param("i", $project_id);
$b_stmt->execute();
$beneficiaries = $b_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$b_stmt->close();

// Metric Calculations
$target_count    = intval($project['target_beneficiaries'] ?? 0);
$enrolled_count  = count($beneficiaries);
$completed_count = count(array_filter($beneficiaries, fn($b) => in_array($b['status'], ['Completed', 'Payment Received', 'Finished'])));

$total_dept_spent = array_sum(array_map(fn($b) => floatval($b['dept_contribution_amount'] ?? 0), $beneficiaries));
$total_ben_equity = array_sum(array_map(fn($b) => floatval($b['beneficiary_contribution_amount'] ?? 0), $beneficiaries));

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">

        <!-- Top Breadcrumb & Actions -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="projects_progress.php" class="text-decoration-none text-primary"><i class="bi bi-folder me-1"></i>Projects &amp; Monitoring</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Beneficiary Sub-Page</li>
                    </ol>
                </nav>
                <h4 class="mb-0 fw-bold text-dark text-uppercase d-flex align-items-center">
                    <span class="badge bg-primary me-2"><?= htmlspecialchars($project['project_type'] ?: 'PSDG') ?></span>
                    <?= htmlspecialchars($project['project_name'] ?: $project['activity']) ?>
                </h4>
                <small class="text-muted">
                    <i class="bi bi-bank me-1"></i><?= htmlspecialchars($project['funding_agent'] ?: 'Department of Animal Production & Health') ?> &nbsp;|&nbsp;
                    <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($range_name) ?> &nbsp;|&nbsp;
                    <i class="bi bi-calendar-range me-1"></i><?= htmlspecialchars($project['start_date']) ?> to <?= htmlspecialchars($project['end_date']) ?>
                </small>
            </div>
            <div class="d-flex gap-2">
                <a href="projects_progress.php" class="btn btn-outline-secondary shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to Overview
                </a>
                <button class="btn btn-success shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#addBeneficiaryModal">
                    <i class="bi bi-person-plus-fill me-1"></i> Register Beneficiary
                </button>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-3 border-start border-primary border-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-bold text-uppercase">Enrolled / Target</span>
                            <h3 class="mb-0 fw-bold text-dark mt-1"><?= $enrolled_count ?> <span class="fs-6 text-muted">/ <?= $target_count > 0 ? $target_count : 'Open' ?></span></h3>
                        </div>
                        <div class="display-6 text-primary"><i class="bi bi-people"></i></div>
                    </div>
                    <div class="progress mt-2" style="height: 5px;">
                        <?php $pct = ($target_count > 0) ? min(100, round(($enrolled_count / $target_count) * 100)) : 100; ?>
                        <div class="progress-bar bg-primary" style="width: <?= $pct ?>%;"></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-3 border-start border-success border-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-bold text-uppercase">Total Dept Contribution</span>
                            <h4 class="mb-0 fw-bold text-success mt-1">Rs. <?= number_format($total_dept_spent, 2) ?></h4>
                        </div>
                        <div class="display-6 text-success"><i class="bi bi-cash-stack"></i></div>
                    </div>
                    <small class="text-muted mt-1 d-block"><?= floatval($project['dept_subsidy_percentage']) ?>% Standard Subsidy Rate</small>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-3 border-start border-info border-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-bold text-uppercase">Farmer Counterpart Equity</span>
                            <h4 class="mb-0 fw-bold text-info-emphasis mt-1">Rs. <?= number_format($total_ben_equity, 2) ?></h4>
                        </div>
                        <div class="display-6 text-info"><i class="bi bi-person-check"></i></div>
                    </div>
                    <small class="text-muted mt-1 d-block">Materials &amp; Labor Contribution</small>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-3 border-start border-warning border-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-bold text-uppercase">Disbursed / Completed</span>
                            <h3 class="mb-0 fw-bold text-warning-emphasis mt-1"><?= $completed_count ?> <span class="fs-6 text-muted">Beneficiaries</span></h3>
                        </div>
                        <div class="display-6 text-warning"><i class="bi bi-check2-circle"></i></div>
                    </div>
                    <small class="text-muted mt-1 d-block">Passed Final Document Verification</small>
                </div>
            </div>
        </div>

        <!-- Individual Beneficiaries Register -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-person-lines-fill me-2 text-primary"></i>Individual Beneficiary Profiles &amp; Milestone Tracking
                </h5>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addBeneficiaryModal">
                    <i class="bi bi-plus-circle me-1"></i> Add Individual Profile
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="beneficiariesTable">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th>Beneficiary Details</th>
                                <th>Location (District &amp; GN)</th>
                                <th>Contribution Breakdown</th>
                                <th>Milestones &amp; Check Tracking</th>
                                <th>Status</th>
                                <th class="text-end no-export" style="min-width: 130px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $b_idx = 1;
                            foreach ($beneficiaries as $b): 
                            ?>
                            <tr>
                                <td class="text-center fw-bold text-secondary"><?= $b_idx++ ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($b['beneficiary_name']) ?></div>
                                    <div class="small text-muted font-monospace"><i class="bi bi-card-text me-1"></i><?= htmlspecialchars($b['nic_no']) ?></div>
                                    <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($b['phone_no'] ?: 'N/A') ?> &nbsp;|&nbsp; <?= htmlspecialchars($b['gender']) ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-secondary"><?= htmlspecialchars($b['gn_division'] ?: 'GN Division') ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($b['district'] ?: 'District') ?> &bull; <?= htmlspecialchars($b['range_name'] ?: $range_name) ?></div>
                                    <small class="text-muted d-block text-truncate" style="max-width: 180px;"><?= htmlspecialchars($b['address']) ?></small>
                                </td>
                                <td>
                                    <div class="small">
                                        <span class="text-success fw-bold">Dept: Rs. <?= number_format($b['dept_contribution_amount'], 2) ?></span>
                                        <div class="text-muted text-truncate" style="max-width: 190px;"><?= htmlspecialchars($b['dept_contribution_activity'] ?: 'Subsidy') ?></div>
                                    </div>
                                    <div class="small mt-1 border-top pt-1">
                                        <span class="text-primary fw-bold">Farmer: Rs. <?= number_format($b['beneficiary_contribution_amount'], 2) ?></span>
                                        <div class="text-muted text-truncate" style="max-width: 190px;"><?= htmlspecialchars($b['beneficiary_contribution_activity'] ?: 'Counterpart') ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        <div><span class="text-muted">Agreement:</span> <?= $b['agreement_date'] ?: '-' ?></div>
                                        <div><span class="text-muted">Doc Submission:</span> <?= $b['doc_submission_date'] ?: '-' ?></div>
                                        <?php if (!empty($b['check_no'])): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    <i class="bi bi-check2-circle me-1"></i>Chk #<?= htmlspecialchars($b['check_no']) ?>
                                                </span>
                                                <div class="text-muted x-small" style="font-size: 11px;">Recv: <?= $b['check_received_date'] ?: '-' ?> (Rs. <?= number_format($b['final_amount'], 2) ?>)</div>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border">Check Pending</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $st = $b['status'];
                                    $st_badge = 'bg-primary';
                                    if ($st === 'Completed' || $st === 'Payment Received') $st_badge = 'bg-success';
                                    elseif ($st === 'Delayed') $st_badge = 'bg-danger';
                                    elseif ($st === 'Agreed') $st_badge = 'bg-info text-dark';
                                    ?>
                                    <span class="badge rounded-pill <?= $st_badge ?> px-2 py-1"><?= htmlspecialchars($st) ?></span>
                                </td>
                                <td class="text-end no-export">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" title="View Dossier Profile" onclick='viewBeneficiaryProfile(<?= json_encode($b) ?>)'>
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary" title="Edit Profile" onclick='editBeneficiary(<?= json_encode($b) ?>)'>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-success" title="Record Scheduled Visit" onclick='openMonitoringModal(<?= $b['id'] ?>, "<?= htmlspecialchars($b['beneficiary_name']) ?>", "<?= htmlspecialchars($b['nic_no']) ?>")'>
                                            <i class="bi bi-journal-plus"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" title="Remove" onclick="deleteBeneficiary(<?= $b['id'] ?>, '<?= htmlspecialchars($b['beneficiary_name']) ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- ========================================================================= -->
<!-- MODAL: ADD / EDIT BENEFICIARY INDIVIDUAL PROFILE -->
<!-- ========================================================================= -->
<div class="modal fade" id="addBeneficiaryModal" tabindex="-1" aria-labelledby="addBeneficiaryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title text-white" id="addBeneficiaryModalLabel">
                    <i class="bi bi-person-plus-fill me-2"></i>Register Beneficiary Profile
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="beneficiaryForm">
                <input type="hidden" name="action" value="save_beneficiary">
                <input type="hidden" name="project_id" value="<?= $project_id ?>">
                <input type="hidden" name="beneficiary_id" id="ben_id" value="0">
                
                <div class="modal-body p-4">
                    
                    <!-- 1. Demographic & Location Fields -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-success border-bottom pb-2">
                            <i class="bi bi-person-badge me-1"></i>Demographic &amp; Location Information
                        </h6>
                        <div class="row g-3 mt-1">
                            
                            <div class="col-md-8">
                                <label class="form-label fw-bold small text-uppercase">Beneficiary Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="beneficiary_name" id="ben_name" required placeholder="e.g. S. M. Farook / K. Perera">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase">NIC Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control font-monospace" name="nic_no" id="ben_nic" required placeholder="e.g. 198512345678">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase">Phone Number</label>
                                <input type="text" class="form-control" name="phone_no" id="ben_phone" placeholder="e.g. 0771234567">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase">Gender</label>
                                <select class="form-select" name="gender" id="ben_gender">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase">District</label>
                                <input type="text" class="form-control" name="district" id="ben_district" value="Batticaloa">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase">Range Office</label>
                                <input type="text" class="form-control" name="range_name" id="ben_range" value="<?= htmlspecialchars($range_name) ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase">GN Division</label>
                                <input type="text" class="form-control" name="gn_division" id="ben_gn" placeholder="e.g. Kallady 12">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small text-uppercase">Residential / Farm Address</label>
                                <textarea class="form-control" name="address" id="ben_address" rows="2" placeholder="Full address of the farm/residence..."></textarea>
                            </div>

                        </div>
                    </div>

                    <!-- 2. Specific Contribution Breakdown -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary border-bottom pb-2">
                            <i class="bi bi-pie-chart me-1"></i>Specific Contribution Breakdown
                        </h6>
                        <div class="row g-3 mt-1">
                            
                            <!-- Dept Share -->
                            <div class="col-md-6">
                                <div class="card border border-success-subtle bg-light p-3">
                                    <span class="fw-bold text-success small text-uppercase mb-2 d-block">Department Contribution</span>
                                    <div class="mb-2">
                                        <label class="form-label x-small text-muted fw-bold">Amount (Rs.)</label>
                                        <input type="number" class="form-control form-control-sm" name="dept_contribution_amount" id="ben_dept_amt" step="0.01" value="<?= floatval($project['dept_contribution_amount']) ?>">
                                    </div>
                                    <div>
                                        <label class="form-label x-small text-muted fw-bold">Specific Department Activity</label>
                                        <textarea class="form-control form-control-sm" name="dept_contribution_activity" id="ben_dept_act" rows="2"><?= htmlspecialchars($project['dept_contribution_activity'] ?: 'Subsidy material supply') ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Beneficiary Share -->
                            <div class="col-md-6">
                                <div class="card border border-primary-subtle bg-light p-3">
                                    <span class="fw-bold text-primary small text-uppercase mb-2 d-block">Beneficiary Contribution</span>
                                    <div class="mb-2">
                                        <label class="form-label x-small text-muted fw-bold">Amount (Rs.)</label>
                                        <input type="number" class="form-control form-control-sm" name="beneficiary_contribution_amount" id="ben_farmer_amt" step="0.01" value="<?= floatval($project['beneficiary_contribution_amount']) ?>">
                                    </div>
                                    <div>
                                        <label class="form-label x-small text-muted fw-bold">Specific Farmer Activity</label>
                                        <textarea class="form-control form-control-sm" name="beneficiary_contribution_activity" id="ben_farmer_act" rows="2"><?= htmlspecialchars($project['beneficiary_contribution_activity'] ?: 'Labor and timber rafters') ?></textarea>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- 3. Individual Project Parameters / Milestone Tracking -->
                    <div class="mb-3">
                        <h6 class="fw-bold text-warning-emphasis border-bottom pb-2">
                            <i class="bi bi-flag me-1"></i>Individual Project Parameters &amp; Milestones
                        </h6>
                        <div class="row g-3 mt-1">
                            
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">Start Date</label>
                                <input type="date" class="form-control form-control-sm" name="start_date" id="ben_sdate" value="<?= date('Y-m-d') ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">Agreement Date</label>
                                <input type="date" class="form-control form-control-sm" name="agreement_date" id="ben_agreedate" value="<?= date('Y-m-d') ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">Completion Date</label>
                                <input type="date" class="form-control form-control-sm" name="completion_date" id="ben_cdate">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">Doc Submission Date</label>
                                <input type="date" class="form-control form-control-sm" name="doc_submission_date" id="ben_docdate">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">Final Amount (Rs.)</label>
                                <input type="number" class="form-control form-control-sm" name="final_amount" id="ben_final_amt" step="0.01" placeholder="0.00">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">Current Status</label>
                                <select class="form-select form-select-sm" name="status" id="ben_status">
                                    <option value="In Progress" selected>In Progress</option>
                                    <option value="Agreed">Agreement Signed</option>
                                    <option value="Completed">Works Completed</option>
                                    <option value="Payment Received">Payment / Check Received</option>
                                    <option value="Delayed">Delayed</option>
                                </select>
                            </div>

                            <!-- Check Received Tracking -->
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-uppercase">Check Received Date</label>
                                <input type="date" class="form-control form-control-sm" name="check_received_date" id="ben_chkdate">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-uppercase">Check Number</label>
                                <input type="text" class="form-control form-control-sm font-monospace" name="check_no" id="ben_chkno" placeholder="e.g. CHK-774912">
                            </div>

                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 fw-bold">
                        <i class="bi bi-save me-1"></i> Save Beneficiary
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: SCHEDULED SITE MONITORING VISIT LOG (VET SURGEON) -->
<!-- ========================================================================= -->
<div class="modal fade" id="monitoringVisitModal" tabindex="-1" aria-labelledby="monitoringVisitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white" id="monitoringVisitModalLabel">
                    <i class="bi bi-journal-check me-2"></i>Scheduled Monitoring Site Visit
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="monitoringForm">
                <input type="hidden" name="action" value="save_monitoring_visit">
                <input type="hidden" name="source_module" value="Project">
                <input type="hidden" name="project_id" value="<?= $project_id ?>">
                <input type="hidden" name="beneficiary_id" id="mv_ben_id" value="0">
                <input type="hidden" name="range_name" value="<?= htmlspecialchars($range_name) ?>">
                
                <div class="modal-body p-4">
                    
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle me-1"></i> Recording site evaluation for beneficiary: <strong id="mv_display_name">Farmer</strong> (<span id="mv_display_nic">NIC</span>)
                    </div>

                    <div class="row g-3">
                        
                        <!-- Visit Date -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-uppercase">Visit Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="visit_date" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <!-- Interval Selection -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-uppercase">Scheduled Interval <span class="text-danger">*</span></label>
                            <select class="form-select" name="visit_interval" required>
                                <option value="1 Month">1 Month Inspection</option>
                                <option value="3 Months">3 Months Inspection</option>
                                <option value="6 Months">6 Months Inspection</option>
                                <option value="Bi-Annual (12 Mo)">Bi-Annual (12 Months)</option>
                                <option value="Bi-Annual (18 Mo)">Bi-Annual (18 Months)</option>
                                <option value="Bi-Annual (24 Mo)">Bi-Annual (24 Months)</option>
                                <option value="Routine / Ad-Hoc">Routine / Emergency Visit</option>
                            </select>
                        </div>

                        <!-- Surgeon Name -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-uppercase">Inspecting Surgeon</label>
                            <input type="text" class="form-control" name="veterinary_surgeon_name" value="<?= htmlspecialchars($_SESSION['full_name'] ?? 'Veterinary Surgeon') ?>" required>
                        </div>

                        <!-- Farm Conditions -->
                        <div class="col-12">
                            <label class="form-label fw-bold small text-uppercase">Farm Conditions Evaluation <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="farm_conditions" rows="3" required placeholder="Evaluate shed ventilation, hygiene, bio-security, water access, and physical infrastructure..."></textarea>
                        </div>

                        <!-- Animal Illnesses -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Animal Illnesses Observed</label>
                            <textarea class="form-control" name="animal_illnesses" rows="2" placeholder="Record symptoms, diagnoses, or preventive treatments administered..."></textarea>
                        </div>

                        <!-- Natural Disasters -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Natural Disasters / Climate Impact</label>
                            <textarea class="form-control" name="natural_disasters" rows="2" placeholder="Note drought, flash floods, extreme heat, or predator attacks..."></textarea>
                        </div>

                        <!-- Mortality / Deaths -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-uppercase">Animal Deaths Count</label>
                            <input type="number" class="form-control" name="deaths_count" min="0" value="0">
                        </div>

                        <div class="col-md-9">
                            <label class="form-label fw-bold small text-uppercase">Mortality Details &amp; Post-Mortem Notes</label>
                            <input type="text" class="form-control" name="deaths_details" placeholder="Species, tag number, suspected cause of death...">
                        </div>

                        <!-- Recommendations -->
                        <div class="col-12">
                            <label class="form-label fw-bold small text-uppercase">Veterinary Surgeon Recommendations</label>
                            <textarea class="form-control" name="recommendations" rows="2" placeholder="Feed adjustments, vaccination schedules, management directives..."></textarea>
                        </div>

                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                        <i class="bi bi-save me-1"></i> Save Site Inspection Log
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: VIEW BENEFICIARY PROFILE DOSSIER -->
<!-- ========================================================================= -->
<div class="modal fade" id="viewProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title text-white">
                    <i class="bi bi-person-circle me-2"></i>Beneficiary Individual Dossier
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="profileModalBody">
                <!-- Dynamically populated -->
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#beneficiariesTable').DataTable({
        pageLength: 15,
        order: [[0, 'asc']]
    });

    // Save Beneficiary AJAX
    $('#beneficiaryForm').on('submit', function(e) {
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

    // Save Monitoring Visit AJAX
    $('#monitoringForm').on('submit', function(e) {
        e.preventDefault();
        $.post('processors/project_monitoring_crud.php', $(this).serialize(), function(res) {
            if (res.success) {
                alert(res.message);
                $('#monitoringVisitModal').modal('hide');
            } else {
                alert('Error: ' + res.message);
            }
        }, 'json');
    });
});

function editBeneficiary(data) {
    $('#addBeneficiaryModalLabel').html('<i class="bi bi-pencil-square me-2"></i>Edit Beneficiary Profile');
    $('#ben_id').val(data.id);
    $('#ben_name').val(data.beneficiary_name);
    $('#ben_nic').val(data.nic_no);
    $('#ben_phone').val(data.phone_no);
    $('#ben_gender').val(data.gender);
    $('#ben_district').val(data.district);
    $('#ben_range').val(data.range_name);
    $('#ben_gn').val(data.gn_division);
    $('#ben_address').val(data.address);
    
    $('#ben_dept_amt').val(data.dept_contribution_amount);
    $('#ben_dept_act').val(data.dept_contribution_activity);
    $('#ben_farmer_amt').val(data.beneficiary_contribution_amount);
    $('#ben_farmer_act').val(data.beneficiary_contribution_activity);

    $('#ben_sdate').val(data.start_date);
    $('#ben_agreedate').val(data.agreement_date);
    $('#ben_cdate').val(data.completion_date);
    $('#ben_docdate').val(data.doc_submission_date);
    $('#ben_final_amt').val(data.final_amount);
    $('#ben_chkdate').val(data.check_received_date);
    $('#ben_chkno').val(data.check_no);
    $('#ben_status').val(data.status);

    $('#addBeneficiaryModal').modal('show');
}

function openMonitoringModal(benId, name, nic) {
    $('#mv_ben_id').val(benId);
    $('#mv_display_name').text(name);
    $('#mv_display_nic').text(nic);
    $('#monitoringVisitModal').modal('show');
}

function deleteBeneficiary(id, name) {
    if (confirm('Are you sure you want to remove beneficiary ' + name + '?')) {
        $.post('processors/project_monitoring_crud.php', { action: 'delete_beneficiary', id: id }, function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.message);
            }
        }, 'json');
    }
}

function viewBeneficiaryProfile(b) {
    var html = `
        <div class="d-flex align-items-center mb-3">
            <div class="display-6 text-primary me-3"><i class="bi bi-person-bounding-box"></i></div>
            <div>
                <h4 class="mb-0 fw-bold">${b.beneficiary_name}</h4>
                <div class="text-muted small">NIC: <strong class="font-monospace">${b.nic_no}</strong> &bull; Phone: ${b.phone_no || 'N/A'} &bull; Gender: ${b.gender}</div>
            </div>
            <span class="badge bg-primary ms-auto fs-6">${b.status}</span>
        </div>
        
        <div class="card bg-light border-0 p-3 mb-3">
            <h6 class="fw-bold text-secondary text-uppercase x-small mb-1"><i class="bi bi-geo-alt me-1"></i>Geographic & Residential Info</h6>
            <div>${b.address || 'Address not recorded'}</div>
            <div class="small text-muted mt-1">GN Division: <strong>${b.gn_division || '-'}</strong> | Range: <strong>${b.range_name || '-'}</strong> | District: <strong>${b.district || '-'}</strong></div>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-md-6">
                <div class="border rounded p-3 bg-white h-100">
                    <span class="text-success small fw-bold text-uppercase d-block mb-1">Department Contribution</span>
                    <h5 class="text-success fw-bold mb-1">Rs. ${parseFloat(b.dept_contribution_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</h5>
                    <div class="small text-muted">${b.dept_contribution_activity || 'Specific Activity Details'}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded p-3 bg-white h-100">
                    <span class="text-primary small fw-bold text-uppercase d-block mb-1">Farmer Counterpart Share</span>
                    <h5 class="text-primary fw-bold mb-1">Rs. ${parseFloat(b.beneficiary_contribution_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</h5>
                    <div class="small text-muted">${b.beneficiary_contribution_activity || 'Material & Labor Equity'}</div>
                </div>
            </div>
        </div>

        <div class="card border p-3">
            <h6 class="fw-bold text-secondary text-uppercase x-small mb-2"><i class="bi bi-flag me-1"></i>Project Milestones & Financial Settlement</h6>
            <div class="row g-2 small">
                <div class="col-6"><strong>Start Date:</strong> ${b.start_date || '-'}</div>
                <div class="col-6"><strong>Agreement Date:</strong> ${b.agreement_date || '-'}</div>
                <div class="col-6"><strong>Completion Date:</strong> ${b.completion_date || '-'}</div>
                <div class="col-6"><strong>Doc Submission:</strong> ${b.doc_submission_date || '-'}</div>
                <div class="col-6"><strong>Check Received:</strong> ${b.check_received_date || '-'}</div>
                <div class="col-6"><strong>Check Number:</strong> <span class="font-monospace text-primary fw-bold">${b.check_no || 'Pending'}</span></div>
                <div class="col-12 mt-2 pt-2 border-top">
                    <strong>Final Disbursed Amount:</strong> <span class="text-dark fw-bold fs-6">Rs. ${parseFloat(b.final_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                </div>
            </div>
        </div>
    `;
    $('#profileModalBody').html(html);
    $('#viewProfileModal').modal('show');
}
</script>

<?php require_once '../../../includes/footer.php'; ?>
