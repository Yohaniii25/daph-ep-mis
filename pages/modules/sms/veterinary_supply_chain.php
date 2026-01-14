<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'sms') die("Access denied");

// Demo supply chain data
$supplies = [
    ['item' => 'FMD Vaccine', 'quantity' => 1200, 'unit' => 'Doses', 'stock_level' => 'Sufficient', 'expiry' => '2026-06-30'],
    ['item' => 'Rabies Vaccine', 'quantity' => 450, 'unit' => 'Doses', 'stock_level' => 'Low', 'expiry' => '2026-04-15'],
    ['item' => 'Antibiotics (Oxytetracycline)', 'quantity' => 800, 'unit' => 'Vials', 'stock_level' => 'Sufficient', 'expiry' => '2026-07-20'],
    ['item' => 'Dewormers', 'quantity' => 600, 'unit' => 'Bottles', 'stock_level' => 'Medium', 'expiry' => '2026-09-10'],
];

$low_stock = count(array_filter($supplies, fn($s) => $s['stock_level'] === 'Low'));
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Veterinary Supply Chain Management</h2>

        <!-- Quick Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Total Items in Stock</h6>
                    <h2 class="text-primary"><?= count($supplies) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Low Stock Alerts</h6>
                    <h2 class="text-warning"><?= $low_stock ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Expiring Soon</h6>
                    <h2 class="text-danger">1</h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Last Updated</h6>
                    <h2 class="text-info">Today</h2>
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
                            Add New Supply
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100 py-3" disabled>
                            <i class="bi bi-search"></i><br>
                            Search Stock
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-info w-100 py-3" disabled>
                            <i class="bi bi-truck"></i><br>
                            Request Replenishment
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-warning w-100 py-3" disabled>
                            <i class="bi bi-file-earmark-text"></i><br>
                            View Stock Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Supply Chain Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Current Veterinary Supplies</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                                <th>Stock Level</th>
                                <th>Expiry Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($supplies as $s): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($s['item']) ?></strong></td>
                                <td><?= number_format($s['quantity']) ?></td>
                                <td><?= $s['unit'] ?></td>
                                <td>
                                    <span class="badge bg-<?= $s['stock_level'] === 'Sufficient' ? 'success' : ($s['stock_level'] === 'Low' ? 'danger' : 'warning') ?>">
                                        <?= $s['stock_level'] ?>
                                    </span>
                                </td>
                                <td><?= date('d M Y', strtotime($s['expiry'])) ?></td>
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