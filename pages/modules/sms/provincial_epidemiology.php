<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'sms') die("Access denied");

// Demo epidemiological data
$cases = [
    ['date' => '2026-01-10', 'disease' => 'FMD', 'cases' => 12, 'district' => 'Amparai', 'status' => 'Under Control'],
    ['date' => '2026-01-08', 'disease' => 'Mastitis', 'cases' => 8, 'district' => 'Batticaloa', 'status' => 'Ongoing'],
    ['date' => '2026-01-05', 'disease' => 'Rabies', 'cases' => 3, 'district' => 'Trincomalee', 'status' => 'Contained'],
    ['date' => '2025-12-30', 'disease' => 'Brucellosis', 'cases' => 5, 'district' => 'Amparai', 'status' => 'Under Surveillance'],
];

$total_cases = array_sum(array_column($cases, 'cases'));
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Provincial Epidemiological Information</h2>

        <!-- Quick Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Total Reported Cases</h6>
                    <h2 class="text-danger"><?= $total_cases ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Active Outbreaks</h6>
                    <h2 class="text-warning">2</h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Surveillance Zones</h6>
                    <h2 class="text-info">5</h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">This Week Cases</h6>
                    <h2 class="text-primary">20</h2>
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
                            Report New Case
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100 py-3" disabled>
                            <i class="bi bi-search"></i><br>
                            Search Cases
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-info w-100 py-3" disabled>
                            <i class="bi bi-graph-up"></i><br>
                            View Trends
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-warning w-100 py-3" disabled>
                            <i class="bi bi-file-earmark-text"></i><br>
                            Export Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Epidemiology Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Recent Disease Cases</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Disease</th>
                                <th>Cases</th>
                                <th>District</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cases as $c): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($c['date'])) ?></td>
                                <td><strong><?= htmlspecialchars($c['disease']) ?></strong></td>
                                <td><?= $c['cases'] ?></td>
                                <td><?= htmlspecialchars($c['district']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $c['status'] === 'Contained' ? 'success' : 'warning' ?>">
                                        <?= $c['status'] ?>
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