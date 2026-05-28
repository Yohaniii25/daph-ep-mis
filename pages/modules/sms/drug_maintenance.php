<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sms') {
    header("Location: ../../../index.php");
    exit();
}

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1 text-dark">Subject Matter Specialist Workspace</h2>
                <p class="text-muted small mb-0">Manage daily field dairies, clinical campaigns, drug balances, and disease containment.</p>
            </div>
            <span class="badge bg-primary px-3 py-2">Role: SMS Admin</span>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body py-3 px-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Today's Agenda</h6>
                        <h3 class="fw-bold mb-0 text-primary">04 <span class="fs-6 fw-normal text-muted">Tasks Active</span></h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
                    <div class="card-body py-3 px-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Outbreak Emergencies</h6>
                        <h3 class="fw-bold mb-0 text-danger">02 <span class="fs-6 fw-normal text-muted">Active Areas</span></h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body py-3 px-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Monthly Beneficiaries</h6>
                        <h3 class="fw-bold mb-0 text-success">1,420 <span class="fs-6 fw-normal text-muted">Farmers</span></h3>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                    <div class="card-body py-3 px-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Depleted Pharmaceuticals</h6>
                        <h3 class="fw-bold mb-0 text-warning">05 <span class="fs-6 fw-normal text-dark">Low Stocks</span></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body py-3 bg-white rounded">
                <div class="row g-2">
                    <div class="col-md-3">
                        <button class="btn btn-outline-primary w-100 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#taskModal">
                            <i class="bi bi-calendar-plus me-2"></i> Log Diary / Advanced Plan
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-outline-success w-100 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#clinicModal">
                            <i class="bi bi-geo-alt me-2"></i> Record Mobile Clinic
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-outline-danger w-100 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#outbreakModal">
                            <i class="bi bi-shield-exclamation me-2"></i> Report New Outbreak
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-outline-warning text-dark w-100 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#drugModal">
                            <i class="bi bi-capsule me-2"></i> Update Drug Inventory
                        </button>
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
                            <div class="progress" style="height: 8px;"><div class="progress-bar bg-success" style="width: 82%"></div></div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Mastitis Control Campaigns</span><span class="fw-bold">60%</span>
                            </div>
                            <div class="progress" style="height: 8px;"><div class="progress-bar bg-primary" style="width: 60%"></div></div>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Infertility Screenings</span><span class="fw-bold">45%</span>
                            </div>
                            <div class="progress" style="height: 8px;"><div class="progress-bar bg-warning" style="width: 45%"></div></div>
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

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white pt-3 border-0">
                <ul class="nav nav-tabs card-header-tabs border-0" id="smsTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active fw-bold text-dark border-0 py-2" id="diary-tab" data-bs-toggle="tab" data-bs-target="#diaryView" type="button" role="tab"><i class="bi bi-journal-text me-2 text-primary"></i>Diary & Advanced Plans</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-bold text-dark border-0 py-2" id="clinics-tab" data-bs-toggle="tab" data-bs-target="#clinicsView" type="button" role="tab"><i class="bi bi-truck me-2 text-success"></i>Mobile Clinics</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-bold text-dark border-0 py-2" id="pharmacy-tab" data-bs-toggle="tab" data-bs-target="#pharmacyView" type="button" role="tab"><i class="bi bi-capsule-compartment me-2 text-warning"></i>Drug Inventories</button>
                    </li>
                </ul>
            </div>
            
            <div class="card-body tab-content" id="smsTabsContent">
                <div class="tab-pane fade show active" id="diaryView" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle" id="diaryDataTable" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Classification Type</th>
                                    <th>Place / Station</th>
                                    <th>Activity Breakdown Details</th>
                                    <th>Priority</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                </tbody>
                        </table>
                    </div>
                </div>
                
                </div>
        </div>

    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>


<?php require_once '../../../includes/footer.php'; ?>