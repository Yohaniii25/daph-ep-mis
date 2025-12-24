<?php
require_once '../../../includes/header.php';

if ($_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

// Demo data only
$message = '<div class="alert alert-info text-center">Demo mode - Real inputs supply and revenue tracking will be implemented in Phase 2</div>';

$demo_inputs = [
    ['date' => '2025-12-20', 'item' => 'Day-Old Chicks', 'quantity' => '50,000', 'supplier' => 'Private Hatchery', 'cost' => '7500000'],
    ['date' => '2025-12-18', 'item' => 'Poultry Feed (Tons)', 'quantity' => '120', 'supplier' => 'Local Supplier', 'cost' => '9600000'],
    ['date' => '2025-12-15', 'item' => 'Veterinary Medicines', 'quantity' => 'Various', 'supplier' => 'DAPH Stores', 'cost' => '2800000'],
    ['date' => '2025-12-10', 'item' => 'Fodder Seeds', 'quantity' => '500 kg', 'supplier' => 'Agriculture Dept', 'cost' => '1200000'],
];

$revenue_summary = [
    'poultry_sales' => 18500000,
    'milk_sales' => 14200000,
    'other' => 3500000,
    'total_month' => 36200000,
    'target' => 40000000
];
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Inputs Supply & Revenue Management</h2>

        <!-- <?= $message ?> -->

        <!-- Revenue Summary -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Poultry Sales (This Month)</h6>
                    <h2 class="text-primary">Rs <?= number_format($revenue_summary['poultry_sales']) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Milk Sales (This Month)</h6>
                    <h2 class="text-info">Rs <?= number_format($revenue_summary['milk_sales']) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Other Revenue</h6>
                    <h2 class="text-warning">Rs <?= number_format($revenue_summary['other']) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Total Revenue vs Target</h6>
                    <h2 class="text-success">Rs <?= number_format($revenue_summary['total_month']) ?></h2>
                    <small>Target: Rs <?= number_format($revenue_summary['target']) ?> (90% achieved)</small>
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
                            <i class="bi bi-box-seam"></i><br>
                            Record New Input Supply
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100 py-3" disabled>
                            <i class="bi bi-cash-coin"></i><br>
                            Log Daily Revenue
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-info w-100 py-3" disabled>
                            <i class="bi bi-graph-up"></i><br>
                            View Revenue Reports
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-warning w-100 py-3" disabled>
                            <i class="bi bi-file-earmark-spreadsheet"></i><br>
                            Generate Monthly Statement
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inputs Supply Log -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5>Recent Inputs Supply Records</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>DATE</th>
                                <th>INPUT ITEM</th>
                                <th>QUANTITY</th>
                                <th>SUPPLIER</th>
                                <th>COST (Rs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($demo_inputs as $input): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($input['date'])) ?></td>
                                <td><strong><?= htmlspecialchars($input['item']) ?></strong></td>
                                <td><?= $input['quantity'] ?></td>
                                <td><?= htmlspecialchars($input['supplier']) ?></td>
                                <td>Rs <?= number_format($input['cost']) ?></td>
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