<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'veterinary_surgeon') die("Access denied");

// Demo
$projects = [
    ['name' => 'Dairy Development Project', 'allocation' => 5000000, 'spent' => 3500000, 'progress' => 70],
    ['name' => 'FMD Control Program', 'allocation' => 3000000, 'spent' => 3000000, 'progress' => 100],
];
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Development Project Progress Reporting</h2>

        <div class="card shadow-sm" id="projectList">
            
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 style="color: white;" class="mb-0">Ongoing Projects</h5>

                <a href="?export=csv" class="btn btn-sm btn-success">
                    <i class="fas fa-file-csv me-1"></i> Export CSV
                </a>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Project Name</th>
                                <th>Allocation (LKR)</th>
                                <th>Spent (LKR)</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $p): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                    <td>Rs <?= number_format($p['allocation']) ?></td>
                                    <td>Rs <?= number_format($p['spent']) ?></td>
                                    <td>
                                        <div class="progress" style="height: 28px;">
                                            <div class="progress-bar bg-<?= $p['progress'] >= 80 ? 'success' : 'warning' ?>" style="width: <?= $p['progress'] ?>%">
                                                <?= $p['progress'] ?>%
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