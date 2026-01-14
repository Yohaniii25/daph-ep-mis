<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'sms') die("Access denied");

// Demo diary & programme entries
$entries = [
    ['date' => '2026-01-10', 'title' => 'Provincial FMD Surveillance Meeting', 'type' => 'Diary', 'status' => 'Completed'],
    ['date' => '2026-01-15', 'title' => 'Training on Rabies Control Strategies', 'type' => 'Programme', 'status' => 'Scheduled'],
    ['date' => '2026-01-08', 'title' => 'Field Visit - Mastitis Cases in Amparai', 'type' => 'Diary', 'status' => 'Completed'],
    ['date' => '2026-01-20', 'title' => 'Brucellosis Screening Programme', 'type' => 'Programme', 'status' => 'Pending'],
];

$completed = count(array_filter($entries, fn($e) => $e['status'] === 'Completed'));
$pending = count(array_filter($entries, fn($e) => $e['status'] === 'Pending'));
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">My Diary & Advance Programmes</h2>

        <!-- Quick Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Total Entries</h6>
                    <h2 class="text-primary"><?= count($entries) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Completed</h6>
                    <h2 class="text-success"><?= $completed ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Pending / Scheduled</h6>
                    <h2 class="text-warning"><?= $pending ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">This Week Entries</h6>
                    <h2 class="text-info">2</h2>
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
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#addEntryModal">
                            <i class="bi bi-plus-circle"></i><br>
                            Add Diary / Programme
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100 py-3" disabled>
                            <i class="bi bi-search"></i><br>
                            Search Entries
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-info w-100 py-3" disabled>
                            <i class="bi bi-graph-up"></i><br>
                            View Monthly Summary
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Entries Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">My Diary & Advance Programmes</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Title</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entries as $e): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($e['date'])) ?></td>
                                <td>
                                    <span class="badge <?= $e['type'] === 'Diary' ? 'bg-info' : 'bg-warning' ?>">
                                        <?= $e['type'] ?>
                                    </span>
                                </td>
                                <td><strong><?= htmlspecialchars($e['title']) ?></strong></td>
                                <td>
                                    <span class="badge bg-<?= $e['status'] === 'Completed' ? 'success' : 'warning' ?>">
                                        <?= $e['status'] ?>
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

<!-- Add Entry Modal -->
<div class="modal fade" id="addEntryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add Diary / Advance Programme</h5>
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
                            <label class="form-label">Type</label>
                            <select class="form-select">
                                <option>Diary</option>
                                <option>Advance Programme</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" placeholder="e.g., Provincial FMD Surveillance Meeting">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Details / Notes</label>
                            <textarea class="form-control" rows="4" placeholder="Describe activities, outcomes, participants..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" disabled>Save Entry</button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>