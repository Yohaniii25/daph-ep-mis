<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'veterinary_surgeon') die("Access denied");

// Demo
$projects = [
    ['name' => 'Dairy Development Project', 'allocation' => 5000000, 'spent' => 3500000, 'progress' => 70],
    ['name' => 'FMD Control Program', 'allocation' => 3000000, 'spent' => 3000000, 'progress' => 100],
];
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Development Project Progress Reporting</h2>

        <!-- Quick Actions -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#addProjectModal">
                            <i class="bi bi-plus-circle"></i><br>
                            Add New Project
                        </button>
                    </div>
                    <div class="col-md-3">
                        <a href="./projectList.php" class="btn btn-primary w-100 py-3">
                            <i class="bi bi-list-ul"></i><br>
                            Project List
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects Table (with id for link) -->
        <div class="card shadow-sm" id="projectList">
            <div class="card-header bg-dark text-white">
                <h5 style="color: white;" class="mb-0">Ongoing Projects</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Project Name</th>
                                <th>Allocation (LKR)</th>
                                <th>Spent (LKR)</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $p): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                <td>Rs <?= number_format($p['allocation']) ?></td>
                                <td>Rs <?= number_format($p['spent']) ?></td>
                                <td>
                                    <div class="progress" style="height: 28px;">
                                        <div class="progress-bar bg-<?= $p['progress'] >= 80 ? 'success' : 'warning' ?>" style="width: <?= $p['progress'] ?>%">
                                            <?= $p['progress'] ?>%
                                        </div>
                                    </div>
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

<!-- Add New Project Modal -->
<div class="modal fade" id="addProjectModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add New Project</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <form>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Project Name</label>
                            <input type="text" class="form-control" placeholder="e.g., Dairy Development Project">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Allocation (LKR)</label>
                            <input type="number" class="form-control" placeholder="5000000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expected End Date</label>
                            <input type="date" class="form-control">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" disabled>Save Project</button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>