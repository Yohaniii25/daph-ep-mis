<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'administrator') {
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
                    <div class="col-md-3">
                        <a style="background-color: #efbe2c; color: black;" class="btn w-100 py-3 border-2" href="daily_diary.php">
                            <i class="bi bi-journal-check fs-3"></i><br>
                            <span class="small fw-bold text-uppercase">Daily Diary Task</span>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a style="background-color: #370709; color: white;" href="advanced_programme.php" class="btn w-100 py-3 border-2">
                            <i class="bi bi-calendar-event fs-3"></i><br>
                            <span class="small fw-bold text-uppercase">Advanced Programme</span>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a style="background-color: #ef4016; color: white;" class="btn w-100 py-3 border-2" href="amend_programme.php">
                            <i class="bi bi-pencil-square fs-3"></i><br>
                            <span class="small fw-bold text-uppercase">Amendments Programme</span>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="annual_plan_editor.php" class="btn btn-dark w-100 py-3 border-2">
                            <i class="bi bi-file-earmark-bar-graph fs-3"></i><br>
                            <span class="small fw-bold text-uppercase">Annual Perf. Plan</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-5">
                <div style="background-color: #b08723;"  class="card shadow-sm border-0 mb-4">
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

                <div style="background-color: #370709; color: white;" class="card shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3"><i class="bi bi-calendar-check me-2"></i>Annual Performance Plan (2027)</h6>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Drafting Progress</span>
                                <span>65%</span>
                            </div>
                            <div class="progress" style="height: 8px; background-color: rgba(255,255,255,0.2);">
                                <div class="progress-bar bg-info" style="width: 65%"></div>
                            </div>
                        </div>
                        <ul class="list-unstyled small">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-info me-2"></i> Submit Unit Goals</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-info me-2"></i> Budget Estimation 2027</li>
                            <li class="mb-2"><i class="bi bi-circle me-2"></i> Final Approval from Provincial Director</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-xl-7">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-calendar3 me-2 text-primary"></i>Advanced Programme Diary</h6>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-secondary active">Month</button>
                            <button class="btn btn-outline-secondary">Week</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="calendar" style="min-height: 500px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: ''
            },
            themeSystem: 'bootstrap5',
            events: [{
                    title: 'Field Inspection - Mutur',
                    start: '2026-04-12',
                    backgroundColor: '#0d6efd',
                    borderColor: '#0d6efd'
                },
                {
                    title: 'Vaccination Drive Launch',
                    start: '2026-04-15',
                    backgroundColor: '#198754',
                    borderColor: '#198754'
                },
                {
                    title: 'Deadline: Annual Plan Draft',
                    start: '2026-04-20',
                    backgroundColor: '#dc3545',
                    borderColor: '#dc3545'
                }
            ]
        });
        calendar.render();
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>