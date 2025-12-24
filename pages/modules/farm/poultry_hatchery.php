<?php
require_once '../../../includes/header.php';

if ($_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

// Demo data only
$message = '<div class="alert alert-info text-center">Demo mode - Real hatchery operations will be implemented in Phase 2</div>';

$demo_hatcheries = [
    ['location' => 'Amparai Hatchery', 'capacity' => 50000, 'eggs_set' => 45000, 'hatched' => 40500, 'hatch_rate' => 90, 'status' => 'Active'],
    ['location' => 'Batticaloa Hatchery', 'capacity' => 40000, 'eggs_set' => 38000, 'hatched' => 34200, 'hatch_rate' => 90, 'status' => 'Active'],
    ['location' => 'Trincomalee Hatchery', 'capacity' => 30000, 'eggs_set' => 28000, 'hatched' => 25200, 'hatch_rate' => 90, 'status' => 'Maintenance'],
];

$daily_production = [
    'chicks_today' => 125700,
    'eggs_collected' => 140000,
    'chicks_sold' => 120000,
    'revenue_today' => 12570000
];
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Poultry Farming & Hatchery Operations</h2>

        <!-- <?= $message ?> -->

        <!-- Quick Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Chicks Hatched Today</h6>
                    <h2 class="text-primary"><?= number_format($daily_production['chicks_today']) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Eggs Collected Today</h6>
                    <h2 class="text-info"><?= number_format($daily_production['eggs_collected']) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Chicks Sold Today</h6>
                    <h2 class="text-success"><?= number_format($daily_production['chicks_sold']) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Today's Revenue (Rs)</h6>
                    <h2 class="text-warning"><?= number_format($daily_production['revenue_today']) ?></h2>
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
                        <button class="btn btn-primary w-100 py-3" disabled>
                            <i class="bi bi-egg"></i><br>
                            Record Egg Setting
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3" disabled>
                            <i class="bi bi-activity"></i><br>
                            Log Hatching Results
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-info w-100 py-3" disabled>
                            <i class="bi bi-truck"></i><br>
                            Record Chick Sales
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-warning w-100 py-3" disabled>
                            <i class="bi bi-graph-up"></i><br>
                            View Hatchery Reports
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hatchery Status Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 style="color: white;">Poultry Hatchery Status</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>HATCHERY LOCATION</th>
                                <th>CAPACITY (Eggs)</th>
                                <th>EGGS SET</th>
                                <th>CHICKS HATCHED</th>
                                <th>HATCH RATE (%)</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($demo_hatcheries as $hatchery): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($hatchery['location']) ?></strong></td>
                                <td><?= number_format($hatchery['capacity']) ?></td>
                                <td><?= number_format($hatchery['eggs_set']) ?></td>
                                <td><?= number_format($hatchery['hatched']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $hatchery['hatch_rate'] >= 90 ? 'success' : 'warning' ?>">
                                        <?= $hatchery['hatch_rate'] ?>%
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $hatchery['status'] === 'Active' ? 'success' : 'secondary' ?>">
                                        <?= $hatchery['status'] ?>
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