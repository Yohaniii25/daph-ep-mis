<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'finance_admin') die("Access denied");

// Numeric values
$projects = [
    ['project' => 'PSDG Dairy Development', 'allocation' => 45000000, 'disbursed' => 32000000, 'progress' => 71],
    ['project' => 'CBG Poultry Expansion', 'allocation' => 28000000, 'disbursed' => 28000000, 'progress' => 100],
    ['project' => 'Line Ministry Equipment', 'allocation' => 15000000, 'disbursed' => 8500000, 'progress' => 57],
    ['project' => 'Provincial Fodder Project', 'allocation' => 20000000, 'disbursed' => 12000000, 'progress' => 60],
];

$total_allocation = array_sum(array_column($projects, 'allocation'));
$total_disbursed = array_sum(array_column($projects, 'disbursed'));
$overall_progress = $total_allocation > 0 ? round(($total_disbursed / $total_allocation) * 100) : 0;
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4 text-primary fw-bold">Project Finance Disbursement Plan and Management</h2>

        <!-- Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-gradient text-white" style="background: linear-gradient(135deg, #43cea2, #185a9d);">
                    <div class="card-body text-center p-5">
                        <i class="bi bi-wallet2 fs-1 mb-3"></i>
                        <h5>Total Allocation</h5>
                        <h2 class="display-5 fw-bold">Rs <?= number_format($total_allocation) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-gradient text-white" style="background: linear-gradient(135deg, #a8e063, #56ab2f);">
                    <div class="card-body text-center p-5">
                        <i class="bi bi-cash-stack fs-1 mb-3"></i>
                        <h5>Total Disbursed</h5>
                        <h2 class="display-5 fw-bold">Rs <?= number_format($total_disbursed) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-gradient text-white" style="background: linear-gradient(135deg, #f7971e, #ffd200);">
                    <div class="card-body text-center p-5">
                        <i class="bi bi-graph-up-arrow fs-1 mb-3"></i>
                        <h5>Overall Progress</h5>
                        <h2 class="display-5 fw-bold"><?= $overall_progress ?>%</h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-gradient text-white" style="background: linear-gradient(135deg, #ff6a00, #ee0979);">
                    <div class="card-body text-center p-5">
                        <i class="bi bi-piggy-bank fs-1 mb-3"></i>
                        <h5>Remaining Funds</h5>
                        <h2 class="display-5 fw-bold">Rs <?= number_format($total_allocation - $total_disbursed) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Project Table -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-projector me-2"></i>Project Disbursement Status</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Project Name</th>
                                <th>Allocation (LKR)</th>
                                <th>Disbursed (LKR)</th>
                                <th>Balance (LKR)</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $proj): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($proj['project']) ?></strong></td>
                                <td>Rs <?= number_format($proj['allocation']) ?></td>
                                <td>Rs <?= number_format($proj['disbursed']) ?></td>
                                <td>Rs <?= number_format($proj['allocation'] - $proj['disbursed']) ?></td>
                                <td>
                                    <div class="progress" style="height: 28px;">
                                        <div class="progress-bar <?= $proj['progress'] >= 80 ? 'bg-success' : ($proj['progress'] >= 50 ? 'bg-warning' : 'bg-danger') ?>" 
                                             style="width: <?= $proj['progress'] ?>%">
                                            <strong><?= $proj['progress'] ?>%</strong>
                                        </div>
                                    </div>
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