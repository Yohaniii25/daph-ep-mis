<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'veterinary_surgeon') die("Access denied");

// Demo data
$trainings = [
    ['date' => '2026-01-15', 'topic' => 'Modern Dairy Farming', 'farmers' => 35, 'location' => 'Amparai'],
    ['date' => '2026-01-10', 'topic' => 'Animal Health Management', 'farmers' => 28, 'location' => 'Sainthamaruthu'],
    ['date' => '2025-12-20', 'topic' => 'Fodder Cultivation', 'farmers' => 42, 'location' => 'Karaitivu'],
    ['date' => '2025-12-15', 'topic' => 'Artificial Insemination', 'farmers' => 20, 'location' => 'Office Hall'],
];

$total_farmers = array_sum(array_column($trainings, 'farmers'));
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Farmer Training & Registration</h2>

        <!-- Quick Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Farmers Trained This Month</h6>
                    <h2 class="text-primary"><?= $total_farmers ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Training Sessions</h6>
                    <h2 class="text-success"><?= count($trainings) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Average Attendance</h6>
                    <h2 class="text-info"><?= round($total_farmers / count($trainings)) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Registered Farmers</h6>
                    <h2 class="text-warning">1,245</h2>
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
                            Schedule Training
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100 py-3" disabled>
                            <i class="bi bi-person-plus"></i><br>
                            Register Farmer
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-info w-100 py-3" disabled>
                            <i class="bi bi-graph-up"></i><br>
                            Training Reports
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-warning w-100 py-3" disabled>
                            <i class="bi bi-people"></i><br>
                            Farmer List
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5>Recent Training Sessions</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>DATE</th>
                                <th>TOPIC</th>
                                <th>FARMERS ATTENDED</th>
                                <th>LOCATION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($trainings as $t): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($t['date'])) ?></td>
                                <td><strong><?= htmlspecialchars($t['topic']) ?></strong></td>
                                <td><?= $t['farmers'] ?></td>
                                <td><?= htmlspecialchars($t['location']) ?></td>
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