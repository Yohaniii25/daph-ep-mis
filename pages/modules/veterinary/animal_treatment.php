<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'veterinary_surgeon') die("Access denied");

// Demo treatments with animal code
$treatments = [
    ['date' => '2026-01-10', 'code' => 'DAPH-045', 'animal' => 'Cow', 'owner' => 'Farmer A', 'treatment' => 'Antibiotic injection', 'type' => 'Outdoor'],
    ['date' => '2026-01-09', 'code' => 'DAPH-112', 'animal' => 'Buffalo', 'owner' => 'Farmer B', 'treatment' => 'Wound dressing', 'type' => 'Indoor'],
    ['date' => '2026-01-08', 'code' => 'DAPH-078', 'animal' => 'Goat', 'owner' => 'Farmer C', 'treatment' => 'Deworming', 'type' => 'Outdoor'],
    ['date' => '2026-01-07', 'code' => 'DAPH-023', 'animal' => 'Cow', 'owner' => 'Farmer D', 'treatment' => 'Vaccination', 'type' => 'Outdoor'],
    ['date' => '2026-01-06', 'code' => 'DAPH-056', 'animal' => 'Sheep', 'owner' => 'Farmer E', 'treatment' => 'Deworming', 'type' => 'Outdoor'],
];

$outdoor = count(array_filter($treatments, fn($t) => $t['type'] === 'Outdoor'));
$unique_codes = count(array_unique(array_column($treatments, 'code')));
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Animal Treatment Records</h2>

        <!-- Quick Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Treatments This Week</h6>
                    <h2 class="text-primary"><?= count($treatments) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Outdoor Treatments</h6>
                    <h2 class="text-success"><?= $outdoor ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Indoor Treatments</h6>
                    <h2 class="text-info"><?= count($treatments) - $outdoor ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">This Month</h6>
                    <h2 class="text-warning">12</h2>
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
                    <div class="col-md-4">
                        <button class="btn btn-success w-100 py-3">
                            <i class="bi bi-plus-circle"></i><br>
                            Record Treatment
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100 py-3">
                            <i class="bi bi-search"></i><br>
                            Search Records
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-info w-100 py-3">
                            <i class="bi bi-graph-up"></i><br>
                            View Statistics
                        </button>
                    </div>
                    <!-- <div class="col-md-3">
                        <button class="btn btn-warning w-100 py-3">
                            <i class="bi bi-file-medical"></i><br>
                            Prescriptions
                        </button>
                    </div> -->
                </div>
            </div>
        </div>

<!-- Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Recent Treatments</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>DATE</th>
                                <th>ANIMAL CODE</th>
                                <th>ANIMAL TYPE</th>
                                <th>OWNER</th>
                                <th>TREATMENT</th>
                                <th>TYPE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($treatments as $t): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($t['date'])) ?></td>
                                <td><strong><?= htmlspecialchars($t['code']) ?></strong></td>
                                <td><?= htmlspecialchars($t['animal']) ?></td>
                                <td><?= htmlspecialchars($t['owner']) ?></td>
                                <td><?= htmlspecialchars($t['treatment']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $t['type'] === 'Indoor' ? 'info' : 'success' ?>">
                                        <?= $t['type'] ?>
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

<!-- Record Treatment Modal -->
<div class="modal fade" id="recordTreatmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Record New Treatment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info text-center mb-4">
                    <i class="bi bi-info-circle me-2"></i>Demo Mode - Real recording in Phase 2
                </div>
                <form>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Animal Code</label>
                            <input type="text" class="form-control" placeholder="e.g., COW-045">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Animal Type</label>
                            <select class="form-select">
                                <option>Cow</option>
                                <option>Buffalo</option>
                                <option>Goat</option>
                                <option>Sheep</option>
                                <option>Poultry</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Owner Name</label>
                            <input type="text" class="form-control" placeholder="Farmer Name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Treatment Date</label>
                            <input type="date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Treatment Details</label>
                            <textarea class="form-control" rows="4" placeholder="Describe treatment, medicine used, dosage, etc."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Treatment Type</label>
                            <select class="form-select">
                                <option>Outdoor</option>
                                <option>Indoor</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" disabled>Save Treatment</button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>