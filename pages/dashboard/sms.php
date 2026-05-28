<?php
// pages/dashboard/sms.php
if ($_SESSION['role'] !== 'sms') die("Access denied");
require_once './includes/header.php';
require_once './includes/sidebar.php';
?>

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1 text-dark">Subject Matter Specialist</h2>
                <p class="text-muted small mb-0">Manage daily field dairies, clinical campaigns, drug balances, and disease containment.</p>
            </div>

        </div>

        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Today's Agenda</h6>
                    <h2 class="text-primary mb-2">04 <span class="fs-6 fw-normal text-success">Tasks Active</span></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Total Immunization Programs</h6>
                    <h2 class="text-info mb-2">10 <span class="fs-6 fw-normal text-success">Programs</span></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Total Mobile Clinic Visits</h6>
                    <h2 class="text-warning mb-2">12 <span class="fs-6 fw-normal text-success">Visits</span></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Total Outbreaks</h6>
                    <h2 class="text-danger mb-2">02 <span class="fs-6 fw-normal text-success">Outbreaks</span></h2>
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
                    <div class="col-md-4">
                        <a href="<?= BASE_PATH ?>pages/modules/sms/my_diary.php" class="btn btn-success w-100 py-3 shadow-sm border-0 text-white d-block">
                            <i style="color: white;" class="bi bi-calendar-check fs-4"></i><br>
                            <span style="color:white">Diary Management</span>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="<?= BASE_PATH ?>pages/modules/sms/immunization.php" style="background-color: #b08723;" class="btn btn-primary w-100 py-3 shadow-sm border-0 text-white d-block">
                            <i style="color: white;" class="bi bi-shield-check fs-4"></i><br>
                            <span style="color:white">Immunization</span>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="<?= BASE_PATH ?>pages/modules/sms/mobile_clinic.php" style="background-color: #689ccf;" class="btn btn-info w-100 py-3 shadow-sm border-0 text-white d-block">
                            <i style="color: white;" class="bi bi-capsule-pill fs-4"></i><br>
                            <span style="color:white">Mobile Clinic</span>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="<?= BASE_PATH ?>pages/modules/sms/drug_maintenance.php" style="background-color: #370709;" class="btn w-100 py-3 shadow-sm border-0 text-white d-block">
                            <i style="color: white; " class="bi bi-capsule fs-4"></i><br>
                            <span style="color:white">Drug Maintenance</span>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="<?= BASE_PATH ?>pages/modules/sms/outbreak_report.php" style="background-color: #a07174;" class="btn btn-info w-100 py-3 shadow-sm border-0 text-white d-block">
                            <i style="color: white;" class="bi bi-envelope-plus fs-4"></i><br>
                            <span style="color:white">Outbreak Report</span>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="<?= BASE_PATH ?>pages/modules/sms/disease_control.php" style="background-color: #8d170e;" class="btn w-100 py-3 shadow-sm border-0 text-white d-block">
                            <i style="color: white; " class="bi bi-bandaid fs-4"></i><br>
                            <span style="color:white">Disease Control</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 py-3">
                        <h6 class="m-0 fw-bold text-dark"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Target Fulfillment Rates (Clinical Campaigns)</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Immunization Programs</span><span class="fw-bold">82%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: 82%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Mastitis Control Campaigns</span><span class="fw-bold">60%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-primary" style="width: 60%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Infertility Screenings</span><span class="fw-bold">45%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-warning" style="width: 45%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100 bg-dark text-white">
                    <div class="card-header bg-transparent border-0 py-3">
                        <h6 class="m-0 fw-bold text-warning"><i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i>Emergency Outbreak Watch</h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="alert alert-danger bg-danger border-0 text-white p-2 mb-2 small d-flex justify-content-between">
                            <span><strong>Foot-and-Mouth Disease</strong> - Sathurukondan</span>
                            <span class="badge bg-white text-danger">Active</span>
                        </div>
                        <div class="alert alert-warning bg-warning border-0 text-dark p-2 mb-0 small d-flex justify-content-between">
                            <span><strong>Brucellosis Case Log</strong> - Morawewa</span>
                            <span class="badge bg-dark text-warning">Monitoring</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>



    </main>
</div>

<?php require_once './includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    new Chart(document.getElementById('projectChart'), {
        type: 'bar',
        data: {
            labels: ['10/01', '10/02', '10/03', '10/04', '10/05', '10/06', '10/07', '10/08', '10/09', '10/10', '10/11', '10/12'],
            datasets: [{
                    label: 'Pending',
                    data: [50, 60, 70, 55, 80, 90, 70, 75, 65, 50, 45, 60],
                    backgroundColor: '#6B0F1A'
                },
                {
                    label: 'Rejected',
                    data: [20, 25, 15, 30, 20, 15, 25, 20, 15, 25, 30, 20],
                    backgroundColor: '#ffc1cc'
                },
                {
                    label: 'Approved',
                    data: [90, 85, 95, 80, 90, 95, 85, 90, 95, 85, 80, 90],
                    backgroundColor: '#d4edda'
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
</script>