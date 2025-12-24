<?php
require_once '../../../includes/header.php';

if ($_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

// Demo data only
$message = '<div class="alert alert-info text-center">Demo mode - Real fodder distribution will be implemented in Phase 2</div>';

$demo_distributions = [
    ['date' => '2025-12-20', 'district' => 'Amparai', 'material' => 'Napier Grass Cuttings', 'quantity' => '500 kg', 'farmers' => 25, 'status' => 'Distributed'],
    ['date' => '2025-12-18', 'district' => 'Batticaloa', 'material' => 'CO-3 Fodder Seeds', 'quantity' => '200 kg', 'farmers' => 18, 'status' => 'Distributed'],
    ['date' => '2025-12-15', 'district' => 'Trincomalee', 'material' => 'Guinea Grass Slips', 'quantity' => '800 units', 'farmers' => 32, 'status' => 'Distributed'],
    ['date' => '2025-12-22', 'district' => 'Amparai', 'material' => 'Maize Silage', 'quantity' => '1,200 kg', 'farmers' => 40, 'status' => 'Planned'],
];

$stats = [
    'total_farmers' => 115,
    'total_material' => '3,700 kg/units',
    'this_month' => 4,
    'pending' => 1
];
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Pasture and Fodder Development Operations</h2>

        <!-- <?= $message ?> -->

        <!-- Quick Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Farmers Benefited This Month</h6>
                    <h2 class="text-primary"><?= $stats['total_farmers'] ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Total Material Distributed</h6>
                    <h2 class="text-info"><?= $stats['total_material'] ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Distributions This Month</h6>
                    <h2 class="text-success"><?= $stats['this_month'] ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Pending Distributions</h6>
                    <h2 class="text-warning"><?= $stats['pending'] ?></h2>
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
                            Plan New Distribution
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100 py-3" disabled>
                            <i class="bi bi-truck"></i><br>
                            Record Distribution
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-info w-100 py-3" disabled>
                            <i class="bi bi-graph-up"></i><br>
                            View Reports
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-warning w-100 py-3" disabled>
                            <i class="bi bi-people"></i><br>
                            Farmer Training Schedule
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Distribution Log Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 style="color: white;">Fodder & Pasture Material Distribution Log</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>DATE</th>
                                <th>DISTRICT</th>
                                <th>MATERIAL</th>
                                <th>QUANTITY</th>
                                <th>FARMERS BENEFITED</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($demo_distributions as $dist): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($dist['date'])) ?></td>
                                <td><strong><?= htmlspecialchars($dist['district']) ?></strong></td>
                                <td><?= htmlspecialchars($dist['material']) ?></td>
                                <td><?= $dist['quantity'] ?></td>
                                <td><?= $dist['farmers'] ?></td>
                                <td>
                                    <span class="badge bg-<?= $dist['status'] === 'Distributed' ? 'success' : 'warning' ?>">
                                        <?= $dist['status'] ?>
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