<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'veterinary_surgeon') die("Access denied");

// Demo
$immunizations = [
    ['date' => '2026-01-05', 'vaccine' => 'Foot-and-Mouth Disease (FMD)', 'animals' => 450, 'location' => 'Amparai Division'],
    ['date' => '2026-01-03', 'vaccine' => 'Rabies', 'animals' => 120, 'location' => 'Karaitivu'],
    ['date' => '2025-12-28', 'vaccine' => 'Brucellosis', 'animals' => 280, 'location' => 'Sainthamaruthu'],
];
$total_animals = array_sum(array_column($immunizations, 'animals'));
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Immunization Activities</h2>

        <!-- Quick Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Animals Immunized This Month</h6>
                    <h2 class="text-primary"><?= number_format($total_animals) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Campaigns This Month</h6>
                    <h2 class="text-success"><?= count($immunizations) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Coverage Rate</h6>
                    <h2 class="text-info">92%</h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Target This Month</h6>
                    <h2 class="text-warning">1,000</h2>
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
                        <button class="btn btn-success w-100 py-3">
                            <i class="bi bi-plus-circle"></i><br>
                            Record Campaign
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100 py-3">
                            <i class="bi bi-calendar-event"></i><br>
                            Schedule Campaign
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-info w-100 py-3">
                            <i class="bi bi-graph-up"></i><br>
                            View Reports
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-warning w-100 py-3">
                            <i class="bi bi-list-check"></i><br>
                            Vaccine Stock
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5>Recent Immunization Campaigns</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>DATE</th>
                                <th>VACCINE</th>
                                <th>ANIMALS COVERED</th>
                                <th>LOCATION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($immunizations as $i): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($i['date'])) ?></td>
                                <td><strong><?= $i['vaccine'] ?></strong></td>
                                <td><?= number_format($i['animals']) ?></td>
                                <td><?= htmlspecialchars($i['location']) ?></td>
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