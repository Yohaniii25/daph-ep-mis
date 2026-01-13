<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'veterinary_surgeon') die("Access denied");


$staff = [
    ['name' => 'Dr. Ahmed Rizwan', 'position' => 'Veterinary Surgeon', 'today' => 'Present', 'leave' => 'None'],
    ['name' => 'Mr. Saman Perera', 'position' => 'Livestock Development Officer', 'today' => 'Present', 'leave' => 'None'],
    ['name' => 'Ms. Fathima Hassan', 'position' => 'Technical Officer', 'today' => 'On Leave', 'leave' => 'Annual'],
    ['name' => 'Mr. Ravi Fernando', 'position' => 'Driver', 'today' => 'Present', 'leave' => 'None'],
];

$present = count(array_filter($staff, fn($s) => $s['today'] === 'Present'));
$total = count($staff);
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Staff Attendance & Leave Reporting</h2>

        <!-- Quick Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Total Staff</h6>
                    <h2 class="text-primary"><?= $total ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Present Today</h6>
                    <h2 class="text-success"><?= $present ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">On Leave</h6>
                    <h2 class="text-warning"><?= $total - $present ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Attendance Rate</h6>
                    <h2 class="text-info"><?= round(($present / $total) * 100) ?>%</h2>
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
                    <div class="col-md-4">
                        <a href="<?= $base_path ?>pages/modules/veterinary/veterinary-staff.php" class="btn w-100 py-3" style="background-color: #820100; color: white;">
                            <i class="bi bi-people"></i><br>
                            Staff List
                        </a>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-success w-100 py-3">
                            <i class="bi bi-person-plus"></i><br>
                            Attendance
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100 py-3">
                            <i class="bi bi-calendar-check"></i><br>
                            Leave Reporting
                        </button>
                    </div>
                    <!-- <div class="col-md-3">
                        <button class="btn btn-info w-100 py-3" disabled>
                            <i class="bi bi-graph-up"></i><br>
                            View Reports
                        </button>
                    </div> -->

                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 style="color: white;">Today's Staff Attendance</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>NAME</th>
                                <th>POSITION</th>
                                <th>TODAY STATUS</th>
                                <th>LEAVE TYPE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff as $s): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                                    <td><?= $s['position'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= $s['today'] === 'Present' ? 'success' : 'danger' ?>">
                                            <?= $s['today'] ?>
                                        </span>
                                    </td>
                                    <td><?= $s['leave'] ?></td>
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