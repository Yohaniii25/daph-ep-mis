<?php
if ($_SESSION['role'] !== 'administrator') die("Access denied");
require_once './config/db_connect.php';
require_once './includes/approval_helper.php';
require_once './includes/header.php';

$total_staff = 0;
$staff_res = $mysqli->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
if ($staff_res && $row = $staff_res->fetch_row()) {
    $total_staff = intval($row[0]);
}

$pending_transfers_count = get_pending_transfers_count($mysqli);
$pending_approvals_count = get_pending_approvals_count($mysqli);
?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1 text-dark fw-bold">Administrator Executive Dashboard</h2>
                <p class="text-muted small mb-0">Provincial HR & Administration Control Center • Eastern Province</p>
            </div>
            <div>
                <a href="<?= BASE_PATH ?>pages/modules/hr/employee_managment.php" class="btn btn-outline-danger btn-sm shadow-sm">
                    <i class="bi bi-people me-1"></i> Global Staff Directory
                </a>
            </div>
        </div>

        <!-- 4 Metric Cards -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4" style="border-left: 4px solid #500707 !important;">
                    <h6 class="text-muted mb-2 text-uppercase fw-semibold" style="font-size: 11px;">Total Active Staff</h6>
                    <h2 class="text-dark fw-bold mb-2"><?= $total_staff ?></h2>
                    <small class="text-muted"><i class="bi bi-building me-1 text-danger"></i> Across all units & ranges</small>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <a href="<?= BASE_PATH ?>pages/modules/hr/transfer_management.php" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 p-4" style="border-left: 4px solid <?= $pending_transfers_count > 0 ? '#dc3545' : '#198754' ?> !important;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="text-muted mb-2 text-uppercase fw-semibold" style="font-size: 11px;">Pending Transfer Requests</h6>
                                <h2 class="<?= $pending_transfers_count > 0 ? 'text-danger' : 'text-success' ?> fw-bold mb-2"><?= $pending_transfers_count ?></h2>
                                <small class="text-muted"><i class="bi bi-arrow-left-right me-1 text-warning"></i> Maker-Checker Queue</small>
                            </div>
                            <?php if ($pending_transfers_count > 0): ?>
                                <span class="badge bg-danger rounded-pill">Action Req</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xl-3 col-md-6">
                <a href="<?= BASE_PATH ?>pages/modules/pd/pending_approvals.php" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 p-4" style="border-left: 4px solid #b08723 !important;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="text-muted mb-2 text-uppercase fw-semibold" style="font-size: 11px;">All Staged Approvals</h6>
                                <h2 class="fw-bold mb-2 text-warning"><?= $pending_approvals_count ?></h2>
                                <small class="text-muted"><i class="bi bi-shield-check me-1 text-warning"></i> Staged system edits</small>
                            </div>
                            <?php if ($pending_approvals_count > 0): ?>
                                <span class="badge bg-warning text-dark rounded-pill">Pending</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4" style="border-left: 4px solid #0d6efd !important;">
                    <h6 class="text-muted mb-2 text-uppercase fw-semibold" style="font-size: 11px;">To-Do Tasks</h6>
                    <h2 class="text-info fw-bold mb-2">36</h2>
                    <small class="text-success"><i class="bi bi-arrow-up"></i> 1.8% Up from yesterday</small>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-muted small text-uppercase"><i class="bi bi-lightning-charge me-2 text-warning"></i>Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="<?= BASE_PATH ?>pages/modules/hr/employee_managment.php" class="btn w-100 py-3 shadow-sm border-0 text-light d-block" style="background-color: #500707;">
                            <i style="color: white;" class="bi bi-people-fill fs-4"></i><br>
                            <span style="color:white">Global HR Directory</span>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="<?= BASE_PATH ?>pages/modules/pd/pending_approvals.php" class="btn w-100 py-3 shadow-sm border-0 text-light d-block position-relative" style="background-color: #721c24;">
                            <i style="color: white;" class="bi bi-shield-check fs-4"></i><br>
                            <span style="color:white">All Pending Approvals</span>
                            <?php if ($pending_approvals_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light">
                                    <?= $pending_approvals_count ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="<?= BASE_PATH ?>pages/modules/hr/leave_management.php" class="btn w-100 py-3 shadow-sm border-0 text-light d-block" style="background-color: #198754;">
                            <i style="color: white;" class="bi bi-calendar-check fs-4"></i><br>
                            <span style="color:white">Leave Management</span>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="<?= BASE_PATH ?>pages/modules/hr/inquiry_management.php" class="btn w-100 py-3 shadow-sm border-0 text-light d-block" style="background-color: #0d6efd;">
                            <i style="color: white;" class="bi bi-file-earmark-text fs-4"></i><br>
                            <span style="color:white">HR Documents</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-journal-check me-2 text-primary"></i>Daily Task Diary & Advance Programmes</h6>
                        <span class="badge bg-light text-dark border"><?= date('F Y') ?></span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border-0">
                                <thead class="bg-light small text-uppercase text-muted">
                                    <tr>
                                        <th>Date</th>
                                        <th>Place</th>
                                        <th>Activity</th>
                                        <th class="text-center">Priority</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="small fw-bold"><?= date('d M, Y') ?></td>
                                        <td><small>Conference Hall</small></td>
                                        <td class="fw-medium text-dark">Monthly HR Staff Meeting</td>
                                        <td class="text-center"><span class="badge bg-danger rounded-pill px-3" style="font-size: 10px;">Critical</span></td>
                                    </tr>
                                    <tr>
                                        <td class="small fw-bold"><?= date('d M, Y', strtotime('+1 day')) ?></td>
                                        <td><small>Director's Office</small></td>
                                        <td class="fw-medium text-dark">Annual Performance Review</td>
                                        <td class="text-center"><span class="badge bg-primary rounded-pill px-3" style="font-size: 10px;">Normal</span></td>
                                    </tr>
                                    <tr>
                                        <td class="small fw-bold"><?= date('d M, Y', strtotime('+2 days')) ?></td>
                                        <td><small>Kantalai Range</small></td>
                                        <td class="fw-medium text-dark">Field Staff Training Session</td>
                                        <td class="text-center"><span class="badge bg-info text-light rounded-pill px-3" style="font-size: 10px;">Medium</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 text-center pb-3">
                        <button class="btn btn-sm btn-link text-decoration-none">View Full Diary</button>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-pie-chart-fill me-2 text-success"></i>Annual Performance Plan (2027)</h6>
                    </div>
                    <div class="card-body d-flex flex-column align-items-center justify-content-center">
                        <div style="width: 220px; height: 220px;">
                            <canvas id="performancePieChart"></canvas>
                        </div>
                        <div class="mt-4 w-100">
                            <div class="d-flex justify-content-between mb-1 small">
                                <span class="text-muted">Overall Drafting Progress</span>
                                <span class="fw-bold text-primary">65%</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: 65%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('performancePieChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut', // Doughnut looks cleaner for modern dashboards
            data: {
                labels: ['Completed', 'In Progress', 'To-Do'],
                datasets: [{
                    data: [40, 25, 35],
                    backgroundColor: ['#198754', '#0d6efd', '#e9ecef'],
                    hoverOffset: 4,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: { size: 12 }
                        }
                    }
                },
                cutout: '70%' // Makes it a ring
            }
        });
    });
</script>

<?php require_once './includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
