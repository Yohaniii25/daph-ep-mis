<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'veterinary_surgeon') die("Access denied");

// Demo data
$stock = [
    ['item' => 'FMD Vaccine', 'current' => 850, 'min' => 500, 'status' => 'Normal'],
    ['item' => 'Rabies Vaccine', 'current' => 320, 'min' => 400, 'status' => 'Low'],
    ['item' => 'Antibiotics', 'current' => 1200, 'min' => 800, 'status' => 'Normal'],
    ['item' => 'Semen Straws', 'current' => 450, 'min' => 600, 'status' => 'Low'],
];

$low = count(array_filter($stock, fn($s) => $s['status'] === 'Low'));
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Stock & Supply Management</h2>

        <!-- Quick Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Total Items</h6>
                    <h2 class="text-primary"><?= count($stock) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Normal Stock</h6>
                    <h2 class="text-success"><?= count($stock) - $low ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Low Stock</h6>
                    <h2 class="text-warning"><?= $low ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Reorder Needed</h6>
                    <h2 class="text-danger"><?= $low ?></h2>
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
                            <i class="bi bi-plus-circle"></i><br>
                            Add Stock
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100 py-3" disabled>
                            <i class="bi bi-box-arrow-out-right"></i><br>
                            Issue Stock
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-info w-100 py-3" disabled>
                            <i class="bi bi-graph-up"></i><br>
                            Stock Reports
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-warning w-100 py-3" disabled>
                            <i class="bi bi-exclamation-triangle"></i><br>
                            Low Stock Alert
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5>Current Stock Levels</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ITEM</th>
                                <th>CURRENT STOCK</th>
                                <th>MINIMUM LEVEL</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stock as $s): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($s['item']) ?></strong></td>
                                <td><?= number_format($s['current']) ?></td>
                                <td><?= number_format($s['min']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $s['status'] === 'Normal' ? 'success' : 'warning' ?>">
                                        <?= $s['status'] ?>
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

<?php require_once '../../../includes/footer.php'; ?>