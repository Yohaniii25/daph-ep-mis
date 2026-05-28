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

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold">Diaries & Programme Management</h3>
                <p class="text-muted small">Daily Tasks, Advanced Programmes, and Annual Planning</p>
            </div>
        </div>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-lightning-charge me-2 text-primary"></i>Management Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <a href="./daily_diary.php" class="btn btn-success w-100 py-3 shadow-sm border-0 text-white d-block">
                            <i style="color: white;" class="bi bi-calendar-check fs-4"></i><br>
                            <span style="color:white">Daily Diary Task</span>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="./advanced_programme.php" style="background-color: #b08723;" class="btn btn-primary w-100 py-3 shadow-sm border-0 text-white d-block">
                            <i style="color: white;" class="bi bi-shield-check fs-4"></i><br>
                            <span style="color:white">Advanced Programme</span>
                        </a>
                    </div>

                </div>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-12">
                <div style="background-color: #b08723;" class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-list-stars me-2 text-warning"></i>Daily Tasks (To-Do)</h6>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item border-0 bg-light rounded mb-3 p-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="badge bg-danger mb-2">High Priority</span>
                                        <h6 class="fw-bold mb-1">Monthly Staff Performance Review</h6>
                                        <p class="text-muted small mb-0"><i class="bi bi-geo-alt me-1"></i>Conference Hall, Level 2</p>
                                        <p class="text-muted small"><i class="bi bi-clock me-1"></i>Today, 09:30 AM</p>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" style="transform: scale(1.5);">
                                    </div>
                                </div>
                            </div>

                            <div class="list-group-item border-0 rounded mb-3 p-3 border">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="badge bg-primary mb-2">Normal</span>
                                        <h6 class="fw-bold mb-1">Verify RTI Responses for Mutur Range</h6>
                                        <p class="text-muted small mb-0"><i class="bi bi-geo-alt me-1"></i>HR Office</p>
                                        <p class="text-muted small"><i class="bi bi-clock me-1"></i>Tomorrow, 11:00 AM</p>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" style="transform: scale(1.5);">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>


<?php require_once '../../../includes/footer.php'; ?>