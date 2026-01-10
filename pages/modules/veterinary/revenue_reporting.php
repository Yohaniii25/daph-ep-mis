<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'veterinary_surgeon') die("Access denied");

// Demo data
$revenue = [
    ['date' => '2026-01-10', 'source' => 'Drug Sales', 'amount' => 45000],
    ['date' => '2026-01-09', 'source' => 'Health Certificates', 'amount' => 28000],
    ['date' => '2026-01-08', 'source' => 'Breeding Materials', 'amount' => 35000],
    ['date' => '2026-01-07', 'source' => 'Treatment Fees', 'amount' => 18000],
];

$total = array_sum(array_column($revenue, 'amount'));
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Revenue Reporting</h2>

        <!-- Quick Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">This Week Revenue</h6>
                    <h2 class="text-primary">Rs <?= number_format($total) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Transactions</h6>
                    <h2 class="text-success"><?= count($revenue) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Monthly Target</h6>
                    <h2 class="text-info">Rs 1,200,000</h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Achievement</h6>
                    <h2 class="text-warning">9%</h2>
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
                            Record Revenue
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100 py-3" disabled>
                            <i class="bi bi-search"></i><br>
                            Search Records
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-info w-100 py-3" disabled>
                            <i class="bi bi-graph-up"></i><br>
                            Monthly Report
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-warning w-100 py-3" disabled>
                            <i class="bi bi-file-earmark-pdf"></i><br>
                            Export PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5>Recent Revenue Entries</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>DATE</th>
                                <th>SOURCE</th>
                                <th>AMOUNT (LKR)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($revenue as $r): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($r['date'])) ?></td>
                                <td><strong><?= htmlspecialchars($r['source']) ?></strong></td>
                                <td class="text-success fw-bold">Rs <?= number_format($r['amount']) ?></td>
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