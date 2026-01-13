<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'veterinary_surgeon') die("Access denied");

// Demo
$reports = [
    ['date' => '2026-01-08', 'case' => 'Illegal Transport', 'act_section' => 'Section 3', 'status' => 'Reported'],
    ['date' => '2026-01-06', 'case' => 'Animal Cruelty', 'act_section' => 'Section 2', 'status' => 'Investigation'],
    ['date' => '2025-12-30', 'case' => 'Post-mortem Examination', 'act_section' => 'Forensic', 'status' => 'Completed'],
];
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Animals Act & Forensic Reporting</h2>

        <!-- Quick Actions -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#addRecordModal">
                            <i class="bi bi-plus-circle"></i><br>
                            Add New Record
                        </button>
                    </div>
                    <div class="col-md-3">
                        <a href="reportsTable.php" class="btn btn-primary w-100 py-3">
                            <i class="bi bi-file-earmark-text"></i><br>
                            Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4 text-center">
                    <h6 class="text-muted">Reports This Month</h6>
                    <h2 class="text-primary"><?= count($reports) ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4 text-center">
                    <h6 class="text-muted">Under Investigation</h6>
                    <h2 class="text-warning">1</h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4 text-center">
                    <h6 class="text-muted">Completed</h6>
                    <h2 class="text-success">1</h2>
                </div>
            </div>
        </div>

        <div class="card shadow-sm" id="reportsTable">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Recent Reports</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Case Description</th>
                                <th>Act Section / Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $r): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($r['date'])) ?></td>
                                <td><strong><?= htmlspecialchars($r['case']) ?></strong></td>
                                <td><?= $r['act_section'] ?></td>
                                <td>
                                    <span class="badge bg-<?= $r['status'] === 'Completed' ? 'success' : ($r['status'] === 'Investigation' ? 'warning' : 'info') ?>">
                                        <?= $r['status'] ?>
                                    </span>
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

<!-- Add New Record Modal -->
<div class="modal fade" id="addRecordModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add New Report</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <form>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Case Type</label>
                            <select class="form-select">
                                <option>Animals Act Violation</option>
                                <option>Forensic Report</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Case Description</label>
                            <textarea class="form-control" rows="4" placeholder="Describe the case, section violated, etc."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Act Section</label>
                            <input type="text" class="form-control" placeholder="e.g., Section 3">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select">
                                <option>Reported</option>
                                <option>Investigation</option>
                                <option>Completed</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" disabled>Save Report</button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>