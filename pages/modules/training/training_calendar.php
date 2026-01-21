<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'training_officer') die("Access denied");

// Demo training activities for calendar
$activities = [
    ['date' => '2026-01-15', 'topic' => 'Modern Dairy Farming Techniques', 'farmers' => 45, 'location' => 'Amparai Training Centre', 'status' => 'Completed'],
    ['date' => '2026-01-10', 'topic' => 'Animal Health & Biosecurity', 'farmers' => 38, 'location' => 'Batticaloa Training Centre', 'status' => 'Completed'],
    ['date' => '2026-01-05', 'topic' => 'Fodder Production & Conservation', 'farmers' => 52, 'location' => 'Trincomalee Training Centre', 'status' => 'Scheduled'],
    ['date' => '2025-12-28', 'topic' => 'Artificial Insemination Practices', 'farmers' => 30, 'location' => 'Amparai Training Centre', 'status' => 'Completed'],
];
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Training Calendar</h2>
            <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#addActivityModal">
                <i class="bi bi-plus-circle me-2"></i>Add Activity
            </button>
        </div>

        <!-- Calendar View (Demo as Table) -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark">
                <h5 style="color: white;" class="mb-0">Training Programmes Calendar (Demo View)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Topic</th>
                                <th>Farmers</th>
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