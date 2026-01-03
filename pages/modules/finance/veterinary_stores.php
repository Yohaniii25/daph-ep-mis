<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'finance_admin') die("Access denied");

// Demo data
$stores = [
    ['item' => 'FMD Vaccine', 'stock' => 8500, 'min_level' => 5000, 'status' => 'Normal'],
    ['item' => 'Rabies Vaccine', 'stock' => 3200, 'min_level' => 4000, 'status' => 'Low'],
    ['item' => 'Antibiotics (Various)', 'stock' => 12000, 'min_level' => 8000, 'status' => 'Normal'],
    ['item' => 'Dewormers', 'stock' => 4500, 'min_level' => 6000, 'status' => 'Low'],
    ['item' => 'Surgical Instruments', 'stock' => 180, 'min_level' => 200, 'status' => 'Low'],
];
$low_stock_count = count(array_filter($stores, fn($s) => $s['status'] === 'Low'));
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4 text-primary fw-bold">Veterinary Stores Management</h2>

        <!-- Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-gradient text-white" style="background: linear-gradient(135deg, #2196f3, #21cbf3);">
                    <div class="card-body text-center p-5">
                        <i class="bi bi-boxes fs-1 mb-3"></i>
                        <h5>Total Items</h5>
                        <h2 class="display-5 fw-bold"><?= count($stores) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-gradient text-white" style="background: linear-gradient(135deg, #4caf50, #8bc34a);">
                    <div class="card-body text-center p-5">
                        <i class="bi bi-check-circle fs-1 mb-3"></i>
                        <h5>Normal Stock</h5>
                        <h2 class="display-5 fw-bold"><?= count($stores) - $low_stock_count ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-gradient text-white" style="background: linear-gradient(135deg, #ff9800, #ffc107);">
                    <div class="card-body text-center p-5">
                        <i class="bi bi-exclamation-triangle fs-1 mb-3"></i>
                        <h5>Low Stock Alert</h5>
                        <h2 class="display-5 fw-bold"><?= $low_stock_count ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-gradient text-white" style="background: linear-gradient(135deg, #f44336, #ff5722);">
                    <div class="card-body text-center p-5">
                        <i class="bi bi-bell fs-1 mb-3"></i>
                        <h5>Critical Items</h5>
                        <h2 class="display-5 fw-bold">0</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Table -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-archive me-2"></i>Current Stock Levels</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Item Name</th>
                                <th>Current Stock</th>
                                <th>Minimum Level</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stores as $item): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($item['item']) ?></strong></td>
                                <td class="fw-bold"><?= number_format($item['stock']) ?></td>
                                <td><?= number_format($item['min_level']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $item['status'] === 'Normal' ? 'success' : 'warning' ?> fs-6">
                                        <?= $item['status'] ?>
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