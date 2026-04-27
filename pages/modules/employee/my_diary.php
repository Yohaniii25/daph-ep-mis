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


<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">

        <div class="row align-items-center mb-4">
            <div class="col-md-8">
                <h3 class="fw-bold mb-0">Diary Hub</h3>
                <p class="text-muted">Manage your field activities, upcoming schedules, and performance goals.</p>
            </div>
        </div>

        <div style="background-color: #ffffff; border-radius: 12px; padding: 20px;" class="row g-3 mb-4">
            <div class="col-md-3">
                <h6 class="fw-bold mb-3">Quick Actions</h6>
                <a href="daily_diary.php" class="btn action-card shadow-sm w-100 py-4 border-0" style="background-color: #efbe2c; color: #332b00;">
                    <i class="bi bi-journal-check fs-2 mb-2 d-block"></i>
                    <span class="small fw-bold text-uppercase d-block">Daily Diary Task</span>
                    <span class="extra-small opacity-75">Log today's work</span>
                </a>
            </div>

        </div>

    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
 
</script>

<?php require_once '../../../includes/footer.php'; ?>