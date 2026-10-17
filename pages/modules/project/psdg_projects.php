<?php
require_once __DIR__ . '/../../../config/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** @var mysqli $mysqli */
global $mysqli;

$allowed_roles = ['planning_officer', 'admin', 'super_admin', 'provincial_director'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    die("Access denied. Authorized planning personnel only.");
}

// Handle Add Project Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_project') {
    $p_range_name = trim($_POST['range_office_name'] ?? '');
    $p_type       = trim($_POST['project_type'] ?? 'PSDG');
    $p_name       = trim($_POST['project_name'] ?? '');
    $p_activity   = !empty($_POST['activity']) ? trim($_POST['activity']) : $p_name;
    $p_fund_agent = trim($_POST['funding_agent'] ?? '');
    $p_target_num = intval($_POST['target_beneficiaries'] ?? 0);
    $p_sdate      = !empty($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d');
    $p_edate      = !empty($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d', strtotime('+6 months'));
    $p_range      = !empty($_SESSION['range_id']) ? intval($_SESSION['range_id']) : 1;
    
    // Financial & Dual Contribution Tracking
    $dept_amount  = floatval($_POST['dept_contribution_amount'] ?? 0.00);
    $dept_sub_pct = floatval($_POST['dept_subsidy_percentage'] ?? 100.00);
    $dept_act     = trim($_POST['dept_contribution_activity'] ?? '');
    $ben_amount   = floatval($_POST['beneficiary_contribution_amount'] ?? 0.00);
    $ben_act      = trim($_POST['beneficiary_contribution_activity'] ?? '');
    $p_sum        = trim($_POST['summary'] ?? '');

    if (empty($p_fund_agent)) {
        if ($p_type === 'PSDG') {
            $p_fund_agent = 'PSDG / Provincial Ministry of Agriculture';
        } elseif ($p_type === 'LMP') {
            $p_fund_agent = 'LMP / Livestock Master Plan';
        } elseif ($p_type === 'CBG') {
            $p_fund_agent = 'CBG / Criteria Based Grant';
        } else {
            $p_fund_agent = 'Department of Animal Production & Health';
        }
    }

    if (!empty($p_name)) {
        $ins_p = $mysqli->prepare("
            INSERT INTO projects_progress 
            (range_id, range_office_name, project_type, project_name, activity, funding_agent,
             target_beneficiaries, start_date, end_date, 
             dept_contribution_amount, dept_subsidy_percentage, dept_contribution_activity, 
             beneficiary_contribution_amount, beneficiary_contribution_activity, 
             summary, priority, status, progress_percent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Medium', 'In Progress', 0)
        ");
        if ($ins_p) {
            $ins_p->bind_param(
                "isssssissddsdss", 
                $p_range, 
                $p_range_name, 
                $p_type, 
                $p_name, 
                $p_activity, 
                $p_fund_agent, 
                $p_target_num, 
                $p_sdate, 
                $p_edate, 
                $dept_amount, 
                $dept_sub_pct, 
                $dept_act, 
                $ben_amount, 
                $ben_act, 
                $p_sum
            );
            $ins_p->execute();
            $ins_p->close();
            $_SESSION['proj_msg'] = "Project '{$p_name}' registered successfully with dual contribution tracking!";
        }
    }
    header("Location: psdg_projects.php");
    exit();
}

// Fetch Projects from Database
$projects = [];
$res = $mysqli->query("SELECT * FROM projects_progress ORDER BY id DESC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $projects[] = $r;
    }
}

// Fallback seed project demonstration if database has fewer than 2
if (count($projects) === 0) {
    $projects[] = [
        'id'                               => 1,
        'project_type'                     => 'PSDG',
        'project_name'                     => 'Renovation of Cattle Shed & Milking Parlour',
        'activity'                         => 'Renovation of Cattle Shed & Milking Parlour',
        'funding_agent'                    => 'PSDG / Provincial Ministry of Agriculture',
        'range_office_name'                => 'Batticaloa Central Range Office',
        'target_beneficiaries'             => 35,
        'dept_contribution_amount'         => 175000.00,
        'dept_subsidy_percentage'          => 75.00,
        'dept_contribution_activity'       => 'Floor concreting, brick pillars, and partition walls subsidy',
        'beneficiary_contribution_amount'  => 58000.00,
        'beneficiary_contribution_activity'=> 'Covering remaining material needs like a roof & timber rafters',
        'start_date'                       => date('Y-m-01'),
        'end_date'                         => date('Y-m-d', strtotime('+6 months')),
        'status'                           => 'In Progress'
    ];
}

$total_count = count($projects);
$in_prog = count(array_filter($projects, fn($p) => ($p['status'] ?? '') === 'In Progress' || ($p['status'] ?? '') === 'Ongoing'));
$completed = count(array_filter($projects, fn($p) => ($p['status'] ?? '') === 'Completed'));

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <?php if (!empty($_SESSION['proj_msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['proj_msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['proj_msg']); ?>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 mb-0 fw-bold text-uppercase">Development Projects Management</h2>
                <small class="text-muted">PSDG / LMP / CBG / Special Projects &amp; Operational Monitoring Overview</small>
            </div>
            <button class="btn btn-primary shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#addProjectModal">
                <i class="bi bi-plus-circle me-2"></i>Add Project
            </button>
        </div>

        <!-- Metrics Overview -->
        <div class="row g-4 mb-4">
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 border-start border-primary border-4">
                    <div class="d-flex align-items-center">
                        <div class="display-6 me-3 text-primary"><i class="bi bi-folder2-open"></i></div>
                        <div>
                            <h6 class="text-muted small text-uppercase mb-0">Total Projects</h6>
                            <h3 class="fw-bold mb-0 text-dark"><?= $total_count ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 border-start border-warning border-4">
                    <div class="d-flex align-items-center">
                        <div class="display-6 me-3 text-warning"><i class="bi bi-hourglass-split"></i></div>
                        <div>
                            <h6 class="text-muted small text-uppercase mb-0">Active &amp; In Progress</h6>
                            <h3 class="fw-bold mb-0 text-dark"><?= $in_prog ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 border-start border-success border-4">
                    <div class="d-flex align-items-center">
                        <div class="display-6 me-3 text-success"><i class="bi bi-check2-circle"></i></div>
                        <div>
                            <h6 class="text-muted small text-uppercase mb-0">Completed Works</h6>
                            <h3 class="fw-bold mb-0 text-dark"><?= $completed ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Projects Overview Table (Strict 8 Columns) -->
        <div class="card shadow-sm border-0 mb-5">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-list-task me-2 text-primary"></i>Projects Overview Registry</h6>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addProjectModal">
                    <i class="bi bi-folder-plus me-1"></i> Register New Project
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="psdgOverviewTable">
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
                                $dept_activity = !empty($proj['dept_contribution_activity']) ? $proj['dept_contribution_activity'] : 'Department Subsidy / Grant';
                                
                                $ben_amount = floatval($proj['beneficiary_contribution_amount'] ?? 0);
                                $ben_activity = !empty($proj['beneficiary_contribution_activity']) ? $proj['beneficiary_contribution_activity'] : 'Farmer Equity / Material & Labor';
                                
                                $target_num = intval($proj['target_beneficiaries'] ?? 0);
                                $start_date = !empty($proj['start_date']) ? $proj['start_date'] : '-';
                                $end_date = !empty($proj['end_date']) ? $proj['end_date'] : '-';
                            ?>
                            <tr>
                                <td class="text-center fw-bold text-secondary"><?= $serial_no++ ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($activity) ?></div>
                                    <?php if (!empty($proj['project_type'])): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2">
                                            <?= htmlspecialchars($proj['project_type']) ?>
                                        </span>
                                    <?php endif; ?>
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
    </main>
</div>

<!-- Add Project Modal (Strictly Omitting Location & Assigned Officers) -->
<div class="modal fade" id="addProjectModal" tabindex="-1" aria-labelledby="addProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="addProjectModalLabel">
                    <i class="bi bi-folder-plus me-2"></i>Add Project Entry
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="psdg_projects.php" method="POST" id="addProjectForm">
                <input type="hidden" name="action" value="save_project">
                <div class="modal-body p-4">
                    
                    <!-- SECTION 1: CORE FIELDS -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary border-bottom pb-2">
                            <i class="bi bi-info-circle me-1"></i>Core Project Entry Fields
                        </h6>
                        <div class="row g-3 mt-1">
                            
                            <!-- Range Office Name -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase text-secondary">
                                    Range Office Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" name="range_office_name" value="Eastern Province Range Office" required placeholder="e.g. Batticaloa Range Office">
                            </div>

                            <!-- Project Type -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase text-secondary">
                                    Project Type <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="project_type" id="psdg_project_type" required>
                                    <option value="" selected disabled>Choose Project Type...</option>
                                    <option value="PSDG">PSDG (Provincial Specific Development Grant)</option>
                                    <option value="LMP">LMP (Livestock Master Plan)</option>
                                    <option value="CBG">CBG (Criteria Based Grant)</option>
                                    <option value="Special">Special Project Works</option>
                                    <option value="Other">Other Development Activity</option>
                                </select>
                            </div>

                            <!-- Project Name (e.g. renovation of cattle shed) -->
                            <div class="col-md-8">
                                <label class="form-label fw-bold small text-uppercase text-secondary">
                                    Project Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" name="project_name" id="psdg_project_name" placeholder="e.g. Renovation of cattle shed" required>
                            </div>

                            <!-- Target Number of Beneficiaries -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase text-secondary">
                                    Target Number of Beneficiaries <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" name="target_beneficiaries" min="1" step="1" required placeholder="e.g. 25">
                            </div>

                            <!-- Funding Agent -->
                            <div class="col-md-12">
                                <label class="form-label fw-bold small text-uppercase text-secondary">Funding Agent</label>
                                <input type="text" class="form-control" name="funding_agent" id="psdg_funding_agent" placeholder="e.g. Provincial Ministry of Agriculture, PSDG, Central Government">
                            </div>

                            <!-- Start Date -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase text-secondary">
                                    Start Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" name="start_date" value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <!-- Target End Date -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase text-secondary">
                                    Target End Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" name="end_date" value="<?= date('Y-m-d', strtotime('+6 months')) ?>" required>
                            </div>

                        </div>
                    </div>

                    <!-- SECTION 2: FINANCIAL & DUAL CONTRIBUTION TRACKING -->
                    <div class="mb-3">
                        <h6 class="fw-bold text-success border-bottom pb-2">
                            <i class="bi bi-cash-stack me-1"></i>Financial &amp; Contribution Tracking (Dual Tracking)
                        </h6>
                        <div class="row g-3 mt-1">
                            
                            <!-- Department Contribution Card -->
                            <div class="col-md-6">
                                <div class="card border border-success-subtle h-100 shadow-none bg-light p-3">
                                    <h6 class="fw-bold text-success mb-2">
                                        <i class="bi bi-building me-1"></i>Department Contribution
                                    </h6>
                                    
                                    <div class="mb-2">
                                        <label class="form-label small fw-bold text-uppercase">Contribution Amount (Rs.) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control form-control-sm" name="dept_contribution_amount" id="psdg_dept_amount" step="0.01" min="0" required placeholder="0.00">
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small fw-bold text-uppercase">Subsidy Rate (50% - 100%) <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" name="dept_subsidy_percentage" required>
                                            <option value="100" selected>100% Subsidy (Full Ministry Grant)</option>
                                            <option value="90">90% Subsidy</option>
                                            <option value="80">80% Subsidy</option>
                                            <option value="75">75% Subsidy</option>
                                            <option value="70">70% Subsidy</option>
                                            <option value="60">60% Subsidy</option>
                                            <option value="50">50% Subsidy (Matched Funding)</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="form-label small fw-bold text-uppercase">Specific Department Activity <span class="text-danger">*</span></label>
                                        <textarea class="form-control form-control-sm" name="dept_contribution_activity" rows="2" required placeholder="e.g. Shed foundation, brick walling, and concrete flooring subsidy"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Beneficiary Contribution Card -->
                            <div class="col-md-6">
                                <div class="card border border-primary-subtle h-100 shadow-none bg-light p-3">
                                    <h6 class="fw-bold text-primary mb-2">
                                        <i class="bi bi-person-check me-1"></i>Beneficiary Contribution (Farmer)
                                    </h6>
                                    
                                    <div class="mb-2">
                                        <label class="form-label small fw-bold text-uppercase">Contribution Amount (Rs.) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control form-control-sm" name="beneficiary_contribution_amount" step="0.01" min="0" required placeholder="0.00">
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small fw-bold text-uppercase">Contribution Type</label>
                                        <input type="text" class="form-control form-control-sm bg-white" readonly value="Farmer Equity / Material & Labor">
                                    </div>

                                    <div>
                                        <label class="form-label small fw-bold text-uppercase">Specific Beneficiary Activity <span class="text-danger">*</span></label>
                                        <textarea class="form-control form-control-sm" name="beneficiary_contribution_activity" rows="2" required placeholder="e.g. Covering remaining material needs like a roof, timber, and labor"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small text-uppercase text-secondary">Summary &amp; Operational Scope</label>
                                <textarea class="form-control form-control-sm" name="summary" rows="2" placeholder="Optional brief operational notes..."></textarea>
                            </div>

                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-5 shadow-sm fw-bold">
                        <i class="bi bi-save me-1"></i> Save Project
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('psdg_project_type').addEventListener('change', function() {
    const fundInput = document.getElementById('psdg_funding_agent');
    if (this.value === 'PSDG') {
        fundInput.value = 'PSDG / Provincial Ministry of Agriculture';
    } else if (this.value === 'LMP') {
        fundInput.value = 'LMP / Livestock Master Plan';
    } else if (this.value === 'CBG') {
        fundInput.value = 'CBG / Criteria Based Grant';
    } else if (this.value === 'Special') {
        fundInput.value = 'Special Ministry Works';
    } else if (this.value === 'Other') {
        fundInput.value = 'Department of Animal Production & Health';
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>