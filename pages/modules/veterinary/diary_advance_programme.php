<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'veterinary_surgeon') die("Access denied");

// Demo entries
$entries = [
    ['type' => 'Diary', 'title' => 'Field Visit - Amparai', 'notes' => 'Visited 5 farms, treated 12 animals', 'date' => '2026-01-09'],
    ['type' => 'Task', 'title' => 'Immunization Campaign', 'notes' => 'Prepare for FMD vaccination drive', 'date' => '2026-01-15'],
    ['type' => 'Diary', 'title' => 'Office Meeting', 'notes' => 'Monthly review with staff', 'date' => '2026-01-08'],
];
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">My Diary & Advance Programme</h2>

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
                    <h6 class="text-muted">Diary Entries</h6>
                    <h2 class="text-info"><?= count(array_filter($entries, fn($e) => $e['type'] === 'Diary')) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Tasks</h6>
                    <h2 class="text-warning"><?= count(array_filter($entries, fn($e) => $e['type'] === 'Task')) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">This Month</h6>
                    <h2 class="text-success"><?= count($entries) ?></h2>
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
                        <button class="btn btn-success w-100 py-3" disabled>
                            <i class="bi bi-journal-plus"></i><br>
                            Add Diary Entry
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100 py-3" disabled>
                            <i class="bi bi-calendar-plus"></i><br>
                            Add Task
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-info w-100 py-3" disabled>
                            <i class="bi bi-graph-up"></i><br>
                            View Summary
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-warning w-100 py-3" disabled>
                            <i class="bi bi-clock-history"></i><br>
                            Past Entries
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5>Diary & Programme Entries</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>TYPE</th>
                                <th>DATE</th>
                                <th>TITLE</th>
                                <th>NOTES</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entries as $e): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-<?= $e['type'] === 'Task' ? 'warning' : 'info' ?>">
                                        <?= $e['type'] ?>
                                    </span>
                                </td>
                                <td><?= date('d M Y', strtotime($e['date'])) ?></td>
                                <td><strong><?= htmlspecialchars($e['title']) ?></strong></td>
                                <td><?= htmlspecialchars($e['notes']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once '../../../includes/footer.php'; ?>