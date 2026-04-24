<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../../../index.php");
    exit();
}

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">

<style>
    body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
    .action-card { transition: transform 0.2s, box-shadow 0.2s; border-radius: 12px; }
    .action-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
    .task-item { transition: all 0.2s; border-left: 4px solid transparent; }
    .task-item:hover { border-left-color: #efbe2c; background-color: #fff9e6 !important; }
    .calendar-container { background: #fff; border-radius: 12px; padding: 20px; }
    .fc .fc-button-primary { background-color: #0d6efd; border-color: #0d6efd; border-radius: 8px; }
    /* Demo Strikethrough Effect */
    .form-check-input:checked ~ div h6 { text-decoration: line-through; color: #adb5bd; }
</style>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">

        <div class="row align-items-center mb-4">
            <div class="col-md-8">
                <h3 class="fw-bold mb-0">Programmes & Diary Hub</h3>
                <p class="text-muted">Manage your field activities, upcoming schedules, and performance goals.</p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="badge bg-white text-dark border p-2 px-3 shadow-sm">
                    <i class="bi bi-calendar-check me-2 text-primary"></i> 
                    Week 16: April 2026
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <a href="daily_diary.php" class="btn action-card shadow-sm w-100 py-4 border-0" style="background-color: #efbe2c; color: #332b00;">
                    <i class="bi bi-journal-check fs-2 mb-2 d-block"></i>
                    <span class="small fw-bold text-uppercase d-block">Daily Diary Task</span>
                    <span class="extra-small opacity-75">Log today's work</span>
                </a>
            </div>
            <div class="col-md-3">
                <a href="advanced_programme.php" class="btn action-card shadow-sm w-100 py-4 border-0 text-white" style="background-color: #370709;">
                    <i class="bi bi-calendar-week fs-2 mb-2 d-block text-warning"></i>
                    <span class="small fw-bold text-uppercase d-block">Advanced Programme</span>
                    <span class="extra-small opacity-75">Planning Ahead</span>
                </a>
            </div>
            <div class="col-md-3">
                <a href="amend_programme.php" class="btn action-card shadow-sm w-100 py-4 border-0 text-white" style="background-color: #ef4016;">
                    <i class="bi bi-pencil-square fs-2 mb-2 d-block"></i>
                    <span class="small fw-bold text-uppercase d-block">Amendments</span>
                    <span class="extra-small opacity-75">Adjust Schedule</span>
                </a>
            </div>
            <div class="col-md-3">
                <a href="annual_plan_editor.php" class="btn action-card shadow-sm w-100 py-4 border-0 btn-dark">
                    <i class="bi bi-trophy fs-2 mb-2 d-block text-info"></i>
                    <span class="small fw-bold text-uppercase d-block">Annual Perf. Plan</span>
                    <span class="extra-small opacity-75">2026/27 Goals</span>
                </a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-4">
                <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px; overflow: hidden;">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">Daily Priority Tasks</h6>
                        <span class="badge bg-soft-warning text-warning border border-warning px-2">2 Pending</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <label class="list-group-item task-item p-3 border-0 border-bottom">
                                <div class="d-flex align-items-center">
                                    <input class="form-check-input me-3" type="checkbox" style="width: 20px; height: 20px;">
                                    <div>
                                        <h6 class="fw-bold mb-0">Monthly Staff Review</h6>
                                        <small class="text-muted"><i class="bi bi-clock me-1"></i>09:30 AM | Conf. Hall</small>
                                    </div>
                                </div>
                            </label>

                            <label class="list-group-item task-item p-3 border-0">
                                <div class="d-flex align-items-center">
                                    <input class="form-check-input me-3" type="checkbox" style="width: 20px; height: 20px;">
                                    <div>
                                        <h6 class="fw-bold mb-0">RTI Verification (Mutur)</h6>
                                        <small class="text-muted"><i class="bi bi-geo-alt me-1"></i>HR Office, Level 1</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="card border-0 text-white shadow-sm" style="background: linear-gradient(135deg, #370709 0%, #5a0c0f 100%); border-radius: 12px;">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-4 d-flex align-items-center">
                            <i class="bi bi-award-fill me-2 text-info"></i> Performance Plan (2027)
                        </h6>
                        <div class="mb-4">
                            <div class="d-flex justify-content-between small mb-2">
                                <span>Drafting Progress</span>
                                <span class="fw-bold">65%</span>
                            </div>
                            <div class="progress" style="height: 10px; background-color: rgba(255,255,255,0.15);">
                                <div class="progress-bar bg-info progress-bar-striped progress-bar-animated" style="width: 65%"></div>
                            </div>
                        </div>
                        <div class="small opacity-75 mb-3">Next Action:</div>
                        <div class="p-2 rounded border border-info border-opacity-25 bg-white bg-opacity-10 mb-2">
                            <i class="bi bi-arrow-right-short text-info"></i> Final Approval from Director
                        </div>
                        <button class="btn btn-info btn-sm w-100 mt-3 fw-bold">Update Details</button>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card shadow-sm border-0 calendar-container">
                    <div class="d-flex justify-content-between align-items-center mb-4 px-2">
                        <h5 class="fw-bold mb-0 text-primary">Advanced Programme Diary</h5>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary active">Month</button>
                            <button class="btn btn-sm btn-outline-primary">Week</button>
                        </div>
                    </div>
                    <div id="calendar"></div>
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
            height: 650,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: ''
            },
            themeSystem: 'bootstrap5',
            dayMaxEvents: true, // allow "more" link when too many events
            events: [
                {
                    title: 'Field Inspection - Mutur',
                    start: '2026-04-12',
                    className: 'bg-primary border-0 text-white rounded-2 px-2'
                },
                {
                    title: 'Vaccination Drive',
                    start: '2026-04-15',
                    className: 'bg-success border-0 text-white rounded-2 px-2'
                },
                {
                    title: 'Annual Plan Deadline',
                    start: '2026-04-20',
                    className: 'bg-danger border-0 text-white rounded-2 px-2'
                }
            ],
            eventMouseEnter: function(info) {
                info.el.style.cursor = 'pointer';
            }
        });
        calendar.render();
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>