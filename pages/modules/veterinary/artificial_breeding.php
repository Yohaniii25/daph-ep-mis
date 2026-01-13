<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'veterinary_surgeon') die("Access denied");

// Demo
$breeding = [
    ['date' => '2026-01-10', 'animal_code' => 'COW-045', 'procedure' => 'Artificial Insemination', 'semen' => 'Jersey', 'result' => 'Success'],
    ['date' => '2026-01-09', 'animal_code' => 'BUF-112', 'procedure' => 'Castration', 'semen' => 'N/A', 'result' => 'Completed'],
    ['date' => '2026-01-08', 'animal_code' => 'GOAT-078', 'procedure' => 'Artificial Insemination', 'semen' => 'Boer', 'result' => 'Pending'],
];
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Artificial Breeding & Castration</h2>

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
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4 text-center">
                    <h6 class="text-muted">Procedures This Month</h6>
                    <h2 class="text-primary"><?= count($breeding) ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4 text-center">
                    <h6 class="text-muted">AI Procedures</h6>
                    <h2 class="text-success">2</h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4 text-center">
                    <h6 class="text-muted">Castrations</h6>
                    <h2 class="text-info">1</h2>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Recent Procedures</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Animal Code</th>
                                <th>Procedure</th>
                                <th>Semen/Breed</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($breeding as $b): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($b['date'])) ?></td>
                                <td><strong><?= $b['animal_code'] ?></strong></td>
                                <td><?= $b['procedure'] ?></td>
                                <td><?= $b['semen'] ?></td>
                                <td>
                                    <span class="badge bg-<?= $b['result'] === 'Success' ? 'success' : ($b['result'] === 'Pending' ? 'warning' : 'info') ?>">
                                        <?= $b['result'] ?>
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
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add New Record</h5>
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
                            <label class="form-label">Animal Code</label>
                            <input type="text" class="form-control" placeholder="e.g., COW-045">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Procedure</label>
                            <select class="form-select">
                                <option>Artificial Insemination</option>
                                <option>Embryo Transfer</option>
                                <option>Castration</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Semen/Breed (if applicable)</label>
                            <input type="text" class="form-control" placeholder="e.g., Jersey">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Result</label>
                            <select class="form-select">
                                <option>Success</option>
                                <option>Completed</option>
                                <option>Pending</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" disabled>Save Record</button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>