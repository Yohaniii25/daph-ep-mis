<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'training_officer') die("Access denied");

// Demo training activities
$activities = [
    ['date' => '2026-01-15', 'topic' => 'Modern Dairy Farming Techniques', 'farmers' => 45, 'location' => 'Amparai Training Centre', 'status' => 'Completed'],
    ['date' => '2026-01-10', 'topic' => 'Animal Health & Biosecurity', 'farmers' => 38, 'location' => 'Batticaloa Training Centre', 'status' => 'Completed'],
    ['date' => '2026-01-05', 'topic' => 'Fodder Production & Conservation', 'farmers' => 52, 'location' => 'Trincomalee Training Centre', 'status' => 'Scheduled'],
    ['date' => '2025-12-28', 'topic' => 'Artificial Insemination Practices', 'farmers' => 30, 'location' => 'Amparai Training Centre', 'status' => 'Completed'],
];

$total_farmers = array_sum(array_column($activities, 'farmers'));
$completed = count(array_filter($activities, fn($a) => $a['status'] === 'Completed'));
$scheduled = count(array_filter($activities, fn($a) => $a['status'] === 'Scheduled'));
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Training Activities</h2>

        </div>

        <!-- Quick Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Total Farmers Trained</h6>
                    <h2 class="text-primary"><?= number_format($total_farmers) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Training Sessions</h6>
                    <h2 class="text-success"><?= count($activities) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Completed Trainings</h6>
                    <h2 class="text-info"><?= $completed ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Scheduled Trainings</h6>
                    <h2 class="text-warning"><?= $scheduled ?></h2>
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
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#addActivityModal">
                            <i class="bi bi-plus-circle"></i><br>
                            Schedule New Training
                        </button>
                    </div>
                    <!-- link to search activities page -->
                     
                    <div class="col-md-3">
                        <a href="training_calendar.php" class="btn btn-primary w-100 py-3">
                            <i class="bi bi-search"></i><br>
                            Search Activities
                        </a>
                    </div>


                </div>
            </div>
        </div>

        <!-- Training Activities Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 style="color: white;" class="mb-0">Recent Training Activities</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Topic</th>
                                <th>Farmers Attended</th>
                                <th>Location</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities as $a): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($a['date'])) ?></td>
                                <td><strong><?= htmlspecialchars($a['topic']) ?></strong></td>
                                <td><?= $a['farmers'] ?></td>
                                <td><?= htmlspecialchars($a['location']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $a['status'] === 'Completed' ? 'success' : 'warning' ?>">
                                        <?= $a['status'] ?>
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

<!-- Add New Training Modal -->
<div class="modal fade" id="addActivityModal" tabindex="-1">
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
                            <input type="text" class="form-control" placeholder="e.g., Modern Dairy Farming Techniques">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expected Farmers</label>
                            <input type="number" class="form-control" placeholder="e.g., 45">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Location / Centre</label>
                            <input type="text" class="form-control" placeholder="e.g., Amparai Training Centre">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Objectives / Agenda</label>
                            <textarea class="form-control" rows="4" placeholder="Brief description of training content..."></textarea>
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