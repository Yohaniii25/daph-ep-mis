<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'finance_admin') die("Access denied");

// Demo data - use numeric values
$assets = [
    ['id' => 'AST-001', 'description' => 'Toyota Hilux Double Cab', 'category' => 'Vehicle', 'value' => 8500000, 'purchase_date' => '2025-01-15', 'status' => 'Active'],
    ['id' => 'AST-002', 'description' => 'Desktop Computers (10 units)', 'category' => 'IT Equipment', 'value' => 2500000, 'purchase_date' => '2025-02-20', 'status' => 'Active'],
    ['id' => 'AST-003', 'description' => 'Office Furniture Set', 'category' => 'Furniture', 'value' => 1200000, 'purchase_date' => '2025-03-10', 'status' => 'Active'],
    ['id' => 'AST-004', 'description' => 'Laboratory Equipment', 'category' => 'Medical', 'value' => 3500000, 'purchase_date' => '2025-04-05', 'status' => 'Active'],
];

$total_value = array_sum(array_column($assets, 'value'));
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4 text-primary fw-bold">Assets Management</h2>

        <!-- Stats Cards -->
        <div class="row g-4 mb-5">
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-gradient text-white" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                    <div class="card-body text-center p-5">
                        <i class="bi bi-box-seam fs-1 mb-3"></i>
                        <h5 class="card-title">Total Assets</h5>
                        <h2 class="display-5 fw-bold"><?= count($assets) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-gradient text-white" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                    <div class="card-body text-center p-5">
                        <i class="bi bi-currency-rupee fs-1 mb-3"></i>
                        <h5 class="card-title">Total Value (LKR)</h5>
                        <h2 class="display-5 fw-bold"><?= number_format($total_value) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-gradient text-white" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
                    <div class="card-body text-center p-5">
                        <i class="bi bi-check-circle fs-1 mb-3"></i>
                        <h5 class="card-title">Active Assets</h5>
                        <h2 class="display-5 fw-bold"><?= count($assets) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Asset Table -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-table me-2"></i>Asset Register</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Asset ID</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Value (LKR)</th>
                                <th>Purchase Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assets as $asset): ?>
                            <tr>
                                <td><span class="badge bg-primary"><?= $asset['id'] ?></span></td>
                                <td><?= htmlspecialchars($asset['description']) ?></td>
                                <td><span class="badge bg-secondary"><?= $asset['category'] ?></span></td>
                                <td class="fw-bold">Rs <?= number_format($asset['value']) ?></td>
                                <td><?= date('d M Y', strtotime($asset['purchase_date'])) ?></td>
                                <td><span class="badge bg-success"><?= $asset['status'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-center mt-4 text-muted">
            <small>* Demo mode - Real asset registration and depreciation tracking in Phase 2</small>
        </div>
    </main>
</div>

<?php require_once '../../../includes/footer.php'; ?>