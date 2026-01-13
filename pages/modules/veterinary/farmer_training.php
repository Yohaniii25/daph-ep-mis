<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'veterinary_surgeon') die("Access denied");

// Demo data
$trainings = [
    ['date' => '2026-01-15', 'topic' => 'Modern Dairy Farming', 'farmers' => 35, 'location' => 'Amparai'],
    ['date' => '2026-01-10', 'topic' => 'Animal Health Management', 'farmers' => 28, 'location' => 'Sainthamaruthu'],
    ['date' => '2025-12-20', 'topic' => 'Fodder Cultivation', 'farmers' => 42, 'location' => 'Karaitivu'],
    ['date' => '2025-12-15', 'topic' => 'Artificial Insemination', 'farmers' => 20, 'location' => 'Office Hall'],
];

$total_farmers = array_sum(array_column($trainings, 'farmers'));
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Farmer Training & Registration</h2>

        <!-- Quick Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Farmers Trained This Month</h6>
                    <h2 class="text-primary"><?= $total_farmers ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Training Sessions</h6>
                    <h2 class="text-success"><?= count($trainings) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Average Attendance</h6>
                    <h2 class="text-info"><?= round($total_farmers / count($trainings)) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Registered Farmers</h6>
                    <h2 class="text-warning">1,245</h2>
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
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#scheduleTrainingModal">
                            <i class="bi bi-plus-circle"></i><br>
                            Schedule Training
                        </button>
                    </div>
                    <div class="col-md-3">
                        <a href="<?= $base_path ?>pages/modules/veterinary/farmer_registration.php" class="btn btn-primary w-100 py-3">
                            <i class="bi bi-person-plus"></i><br>
                            Register Farmer
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="<?= $base_path ?>pages/modules/veterinary/training_reports.php" class="btn btn-info w-100 py-3">
                            <i class="bi bi-graph-up"></i><br>
                            Training Reports
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="<?= $base_path ?>pages/modules/veterinary/farmer_list.php" class="btn btn-warning w-100 py-3">
                            <i class="bi bi-people"></i><br>
                            Farmer List
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5>Recent Training Sessions</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>DATE</th>
                                <th>TOPIC</th>
                                <th>FARMERS ATTENDED</th>
                                <th>LOCATION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($trainings as $t): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($t['date'])) ?></td>
                                <td><strong><?= htmlspecialchars($t['topic']) ?></strong></td>
                                <td><?= $t['farmers'] ?></td>
                                <td><?= htmlspecialchars($t['location']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Schedule Training Modal -->
<div class="modal fade" id="scheduleTrainingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Schedule New Training</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Training Date</label>
                            <input type="date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Topic</label>
                            <input type="text" class="form-control" placeholder="e.g., Modern Dairy Farming">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expected Farmers</label>
                            <input type="number" class="form-control" placeholder="e.g., 35">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" placeholder="e.g., Amparai">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Details / Notes</label>
                            <textarea class="form-control" rows="4" placeholder="Describe the training agenda, objectives, etc."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" disabled>Schedule Training</button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>