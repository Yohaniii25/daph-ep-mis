<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'training_officer') die("Access denied");

// Demo revenue from training activities
$revenue = [
    ['date' => '2026-01-15', 'source' => 'Training Fees - Dairy Farming', 'amount' => 45000, 'status' => 'Received'],
    ['date' => '2026-01-10', 'source' => 'Sponsorship - Animal Health Training', 'amount' => 75000, 'status' => 'Received'],
    ['date' => '2026-01-05', 'source' => 'Material Sale - Fodder Cultivation', 'amount' => 32000, 'status' => 'Pending'],
    ['date' => '2025-12-28', 'source' => 'Registration Fees - AI Training', 'amount' => 18000, 'status' => 'Received'],
];

$total = array_sum(array_column($revenue, 'amount'));
$pending = count(array_filter($revenue, fn($r) => $r['status'] === 'Pending'));
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Revenue Management (Training Centre)</h2>

        <!-- Quick Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Total Revenue</h6>
                    <h2 class="text-primary">Rs <?= number_format($total) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Pending Payments</h6>
                    <h2 class="text-warning"><?= $pending ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Received</h6>
                    <h2 class="text-success">Rs <?= number_format($total - array_sum(array_column(array_filter($revenue, fn($r) => $r['status'] === 'Pending'), 'amount'))) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Transactions</h6>
                    <h2 class="text-info"><?= count($revenue) ?></h2>
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
                        <button class="btn btn-success w-100 py-3" >
                            <i class="bi bi-plus-circle"></i><br>
                            Record New Revenue
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100 py-3" >
                            <i class="bi bi-search"></i><br>
                            Search Records
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-info w-100 py-3" >
                            <i class="bi bi-graph-up"></i><br>
                            View Monthly Summary
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-warning w-100 py-3" >
                            <i class="bi bi-check2-all"></i><br>
                            Approve Pending
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Training Revenue Records</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Source</th>
                                <th>Amount (LKR)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($revenue as $r): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($r['date'])) ?></td>
                                <td><strong><?= htmlspecialchars($r['source']) ?></strong></td>
                                <td class="text-success fw-bold">Rs <?= number_format($r['amount']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $r['status'] === 'Received' ? 'success' : 'warning' ?>">
                                        <?= $r['status'] ?>
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