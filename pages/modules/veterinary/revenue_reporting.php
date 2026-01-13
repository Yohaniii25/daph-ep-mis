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
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#recordRevenueModal">
                            <i class="bi bi-plus-circle"></i><br>
                            Record Revenue
                        </button>
                    </div>
                    <div class="col-md-3">
                        <a href="<?= $base_path ?>pages/modules/veterinary/revenue_reports.php" class="btn btn-primary w-100 py-3">
                            <i class="bi bi-search"></i><br>
                            Revenue Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 style="color : white" >Recent Revenue Entries</h5>
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

<!-- Record Revenue Modal -->
<div class="modal fade" id="recordRevenueModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Record New Revenue</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info text-center mb-4">
                    <i class="bi bi-info-circle me-2"></i>Demo Mode - Revenue recording in Phase 2
                </div>
                <form>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Source</label>
                            <select class="form-select">
                                <option>Drug Sales</option>
                                <option>Health Certificates</option>
                                <option>Breeding Materials</option>
                                <option>Treatment Fees</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount (LKR)</label>
                            <input type="number" class="form-control" placeholder="e.g., 45000">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" disabled>Save Revenue</button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>