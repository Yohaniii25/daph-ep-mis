<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'planning_officer') die("Access denied");

// Demo projects
$projects = [
    ['id' => 'PSDG-001', 'name' => 'Dairy Development Programme - Amparai', 'type' => 'PSDG', 'budget' => 8500000, 'spent' => 4200000, 'status' => 'Ongoing'],
    ['id' => 'CBG-002', 'name' => 'Fodder Cultivation & Distribution', 'type' => 'CBG', 'budget' => 3200000, 'spent' => 1800000, 'status' => 'Ongoing'],
    ['id' => 'NGO-003', 'name' => 'Goat Rearing Project - Batticaloa', 'type' => 'NGO', 'budget' => 2800000, 'spent' => 1200000, 'status' => 'Ongoing'],
    ['id' => 'INGO-004', 'name' => 'Poultry Health Improvement', 'type' => 'INGO', 'budget' => 4500000, 'spent' => 4500000, 'status' => 'Completed'],
];

$total_budget = array_sum(array_column($projects, 'budget'));
$total_spent = array_sum(array_column($projects, 'spent'));
$avg_progress = round(array_sum(array_column($projects, 'progress')) / count($projects));
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Development Projects (PSDG / CBG / NGO)</h2>
            <button style="font-size: 16px;" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#addProjectModal">
                <i class="bi bi-plus-circle me-2"></i>Add New Project
            </button>
        </div>

        <!-- Quick Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Total Projects</h6>
                    <h2 class="text-primary"><?= count($projects) ?></h2>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Total Budget (LKR)</h6>
                    <h2 class="text-success">Rs <?= number_format($total_budget) ?></h2>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Total Spent (LKR)</h6>
                    <h2 class="text-info">Rs <?= number_format($total_spent) ?></h2>
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
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#addProjectModal">
                            <i class="bi bi-plus-circle"></i><br>
                            Add New Project
                        </button>
                    </div>


                </div>
            </div>
        </div>

        <!-- Project Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 style="color: white;" class="mb-0">Development Projects List</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Project ID</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Budget (LKR)</th>
                                <th>Spent (LKR)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $p): ?>
                            <tr>
                                <td><strong><?= $p['id'] ?></strong></td>
                                <td><?= htmlspecialchars($p['name']) ?></td>
                                <td><?= $p['type'] ?></td>
                                <td>Rs <?= number_format($p['budget']) ?></td>
                                <td>Rs <?= number_format($p['spent']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $p['status'] === 'Completed' ? 'success' : 'warning' ?>">
                                        <?= $p['status'] ?>
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
                            <label class="form-label">Project ID</label>
                            <input type="text" class="form-control" placeholder="e.g., PSDG-001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Project Name</label>
                            <input type="text" class="form-control" placeholder="e.g., Dairy Development Programme">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Funding Type</label>
                            <select class="form-select">
                                <option>PSDG</option>
                                <option>CBG</option>
                                <option>NGO</option>
                                <option>INGO</option>
                                <option>IGO</option>
                                <option>Line Ministry</option>
                                <option>Maintenance</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Total Budget (LKR)</label>
                            <input type="number" class="form-control" placeholder="e.g., 8500000">
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