<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'veterinary_surgeon') die("Access denied");

// Demo data
$diseases = [
    ['date' => '2026-01-08', 'disease' => 'FMD Suspected', 'animals' => 5, 'location' => 'Amparai', 'status' => 'Reported'],
    ['date' => '2026-01-06', 'disease' => 'Mastitis', 'animals' => 3, 'location' => 'Sainthamaruthu', 'status' => 'Under Investigation'],
    ['date' => '2025-12-30', 'disease' => 'Rabies Case', 'animals' => 1, 'location' => 'Karaitivu', 'status' => 'Confirmed'],
];
$reported = count($diseases);
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Disease Reporting</h2>
        <!-- Quick Stats -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4 text-center">
                    <h6 class="text-muted">Reports This Month</h6>
                    <h2 class="text-primary"><?= $reported ?></h2>
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

        <!-- Quick Actions -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#reportNewCaseModal">
                            <i class="bi bi-plus-circle"></i><br>
                            Report New Case
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100 py-3">
                            <i class="bi bi-search"></i><br>
                            Search Reports
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-info w-100 py-3">
                            <i class="bi bi-graph-up"></i><br>
                            View Statistics
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-warning w-100 py-3">
                            <i class="bi bi-file-medical"></i><br>
                            Lab Results
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 style="color: white;">Recent Disease Reports</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>DATE REPORTED</th>
                                <th>DISEASE</th>
                                <th>AFFECTED ANIMALS</th>
                                <th>LOCATION</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($diseases as $d): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($d['date'])) ?></td>
                                <td><strong><?= htmlspecialchars($d['disease']) ?></strong></td>
                                <td><?= $d['animals'] ?></td>
                                <td><?= htmlspecialchars($d['location']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $d['status'] === 'Confirmed' ? 'danger' : ($d['status'] === 'Under Investigation' ? 'warning' : 'info') ?>">
                                        <?= $d['status'] ?>
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

<!-- Report New Case Modal -->
<div class="modal fade" id="reportNewCaseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Report New Case</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <form>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Date Reported</label>
                            <input type="date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Disease / Case Type</label>
                            <input type="text" class="form-control" placeholder="e.g., FMD Suspected">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Affected Animals</label>
                            <input type="number" class="form-control" placeholder="e.g., 5">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" placeholder="e.g., Amparai">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Details / Description</label>
                            <textarea class="form-control" rows="4" placeholder="Describe the case, symptoms, etc."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" disabled>Submit Report</button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>