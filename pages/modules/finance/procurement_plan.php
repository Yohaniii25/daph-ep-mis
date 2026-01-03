<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'finance_admin') die("Access denied");

// Demo data - numeric values
$procurements = [
    ['item' => 'Veterinary Vaccines', 'quantity' => '10,000 doses', 'estimated_cost' => 5000000, 'status' => 'Planned', 'quarter' => 'Q1 2026'],
    ['item' => 'Poultry Feed', 'quantity' => '200 tons', 'estimated_cost' => 12000000, 'status' => 'In Progress', 'quarter' => 'Q1 2026'],
    ['item' => 'Medical Equipment', 'quantity' => 'Various', 'estimated_cost' => 8000000, 'status' => 'Approved', 'quarter' => 'Q2 2026'],
    ['item' => 'Office Renovation', 'quantity' => '1 project', 'estimated_cost' => 15000000, 'status' => 'Planned', 'quarter' => 'Q3 2026'],
];

$total_cost = array_sum(array_column($procurements, 'estimated_cost'));
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4 text-primary fw-bold">Procurement Planning & Management</h2>

        <!-- Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-gradient text-white" style="background: linear-gradient(135deg, #11998e, #38ef7d);">
                    <div class="card-body text-center p-5">
                        <i class="bi bi-cart-plus fs-1 mb-3"></i>
                        <h5>Planned Items</h5>
                        <h2 class="display-5 fw-bold"><?= count($procurements) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-gradient text-white" style="background: linear-gradient(135deg, #ff512f, #dd2476);">
                    <div class="card-body text-center p-5">
                        <i class="bi bi-currency-rupee fs-1 mb-3"></i>
                        <h5>Total Estimated Cost</h5>
                        <h2 class="display-5 fw-bold">Rs <?= number_format($total_cost) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-gradient text-white" style="background: linear-gradient(135deg, #4568dc, #b06ab3);">
                    <div class="card-body text-center p-5">
                        <i class="bi bi-clock-history fs-1 mb-3"></i>
                        <h5>Current Year Plan</h5>
                        <h2 class="display-5 fw-bold">2026</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Procurement Table -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Procurement Items - 2026 Plan</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Estimated Cost (LKR)</th>
                                <th>Quarter</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($procurements as $item): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($item['item']) ?></strong></td>
                                <td><?= $item['quantity'] ?></td>
                                <td class="fw-bold">Rs <?= number_format($item['estimated_cost']) ?></td>
                                <td><span class="badge bg-info"><?= $item['quarter'] ?></span></td>
                                <td>
                                    <span class="badge bg-<?= $item['status'] === 'Approved' ? 'success' : ($item['status'] === 'In Progress' ? 'warning' : 'secondary') ?>">
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