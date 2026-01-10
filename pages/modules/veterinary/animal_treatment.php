<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'veterinary_surgeon') die("Access denied");

// Demo treatments
$treatments = [
    ['date' => '2026-01-10', 'animal' => 'Cow', 'owner' => 'Farmer A', 'treatment' => 'Antibiotic injection', 'type' => 'Outdoor'],
    ['date' => '2026-01-09', 'animal' => 'Buffalo', 'owner' => 'Farmer B', 'treatment' => 'Wound dressing', 'type' => 'Indoor'],
    ['date' => '2026-01-08', 'animal' => 'Goat', 'owner' => 'Farmer C', 'treatment' => 'Deworming', 'type' => 'Outdoor'],
];

$outdoor = count(array_filter($treatments, fn($t) => $t['type'] === 'Outdoor'));
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Animal Treatment Records</h2>

        <!-- Quick Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Treatments This Week</h6>
                    <h2 class="text-primary"><?= count($treatments) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Outdoor Treatments</h6>
                    <h2 class="text-success"><?= $outdoor ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Indoor Treatments</h6>
                    <h2 class="text-info"><?= count($treatments) - $outdoor ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">This Month</h6>
                    <h2 class="text-warning">12</h2>
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
                            Record Treatment
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
                            View Statistics
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-warning w-100 py-3" disabled>
                            <i class="bi bi-file-medical"></i><br>
                            Prescriptions
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5>Recent Treatments</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>DATE</th>
                                <th>ANIMAL</th>
                                <th>OWNER</th>
                                <th>TREATMENT</th>
                                <th>TYPE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($treatments as $t): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($t['date'])) ?></td>
                                <td><strong><?= $t['animal'] ?></strong></td>
                                <td><?= htmlspecialchars($t['owner']) ?></td>
                                <td><?= htmlspecialchars($t['treatment']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $t['type'] === 'Indoor' ? 'info' : 'success' ?>">
                                        <?= $t['type'] ?>
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