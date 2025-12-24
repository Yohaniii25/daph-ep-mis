<?php
require_once '../../../includes/header.php';

if ($_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

// Demo data only
$message = '';

$demo_farms = [
    ['reg_no' => 'LF-2025-001', 'farmer' => 'Ahmed Rizwan', 'type' => 'Dairy', 'animals' => 45, 'daily_milk' => 480, 'status' => 'Active'],
    ['reg_no' => 'LF-2025-002', 'farmer' => 'Priya Kumari', 'type' => 'Beef', 'animals' => 32, 'daily_milk' => 0, 'status' => 'Active'],
    ['reg_no' => 'LF-2025-003', 'farmer' => 'Saman Fernando', 'type' => 'Dairy', 'animals' => 38, 'daily_milk' => 420, 'status' => 'Inactive'],
];

$daily_production = [
    'today_milk' => 1420,
    'avg_per_cow' => 12.5,
    'total_cows' => 113,
    'revenue_today' => 284000
];
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Livestock & Dairy Farm Operations</h2>

        <?= $message ?>

        <!-- Quick Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Today's Milk Production (Litres)</h6>
                    <h2 class="text-primary"><?= number_format($daily_production['today_milk']) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Average per Cow (Litres)</h6>
                    <h2 class="text-info"><?= $daily_production['avg_per_cow'] ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Total Registered Cows</h6>
                    <h2 class="text-success"><?= $daily_production['total_cows'] ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Today's Revenue (Rs)</h6>
                    <h2 class="text-warning">₹ <?= number_format($daily_production['revenue_today']) ?></h2>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="livestock_farms_certificate_reg.php" class="btn btn-primary w-100 py-3">
                            <i class="bi bi-file-earmark-plus"></i><br>
                            Register New Livestock Farm
                        </a>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3" disabled>
                            <i class="bi bi-droplet"></i><br>
                            Record Daily Milk Production
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-info w-100 py-3" disabled>
                            <i class="bi bi-graph-up"></i><br>
                            View Production Reports
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-warning w-100 py-3" disabled>
                            <i class="bi bi-truck"></i><br>
                            Milk Sales & Distribution
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Registered Farms Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 style="color: white;">Registered Livestock Farms</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>REG NO.</th>
                                <th>FARMER NAME</th>
                                <th>FARM TYPE</th>
                                <th>NO. OF ANIMALS</th>
                                <th>DAILY MILK (L)</th>
                                <th>STATUS</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($demo_farms as $farm): ?>
                            <tr>
                                <td><strong><?= $farm['reg_no'] ?></strong></td>
                                <td><?= htmlspecialchars($farm['farmer']) ?></td>
                                <td><?= $farm['type'] ?></td>
                                <td><?= $farm['animals'] ?></td>
                                <td><?= $farm['daily_milk'] ?: '-' ?></td>
                                <td>
                                    <span class="badge bg-<?= $farm['status'] === 'Active' ? 'success' : 'secondary' ?>">
                                        <?= $farm['status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="../veterinary/print_certificate.php?id=1" target="_blank" class="btn btn-sm btn-outline-primary">
                                        View Certificate
                                    </a>
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