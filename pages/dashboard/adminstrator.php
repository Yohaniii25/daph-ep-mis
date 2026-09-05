<?php

if ($_SESSION['role'] !== 'administrator') die("Access denied");
require_once './includes/header.php';

?>

        <h2 class="mb-5 text-dark">Administration Dashboard</h2>

        <!-- 4 Cards -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Staff On Leave Today</h6>
                    <h2 class="text-primary mb-2">15</h2>
                    <small class="text-success"><i class="bi bi-arrow-up"></i> 8.5% Up from yesterday</small>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Pending Leave Requests</h6>
                    <h2 class="text-warning mb-2">05</h2>
                    <small class="text-success"><i class="bi bi-arrow-up"></i> 1.3% Up from past week</small>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Pending RTI Requests</h6>
                    <h2 class="text-danger mb-2">154</h2>
                    <small class="text-danger"><i class="bi bi-arrow-down"></i> 4.3% Down from yesterday</small>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">To-Do Tasks</h6>
                    <h2 class="text-info mb-2">36</h2>
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
                        <a href="<?= BASE_PATH ?>pages/modules/hr/employee_managment.php" class="btn btn-success w-100 py-3 shadow-sm border-0 text-light d-block">
                            <i style="color: white;" class="bi bi-person-add fs-4"></i><br>
                            <span style="color:white">Employee Management</span>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="<?= BASE_PATH ?>pages/modules/hr/animal_breeding.php" style="background-color: #b08723;" class="btn btn-primary w-100 py-3 shadow-sm border-0 text-light d-block">
                            <i style="color: white;" class="bi bi-card-checklist fs-4"></i><br>
                            <span style="color:white">Leave Management</span>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="<?= BASE_PATH ?>pages/modules/hr/regulatory_functions.php" class="btn btn-info w-100 py-3 shadow-sm border-0 text-light d-block">
                            <i style="color: white;" class="bi bi-envelope-plus fs-4"></i><br>
                            <span style="color:white">Inquiry Management</span>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="<?= BASE_PATH ?>pages/modules/hr/office_details_view.php" style="background-color: #370709;" class="btn w-100 py-3 shadow-sm border-0 text-light d-block">
                            <i style="color: white; " class="bi bi-people-fill fs-4"></i><br>
                            <span style="color:white">Advance Programmes</span>
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
