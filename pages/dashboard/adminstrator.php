<?php

if ($_SESSION['role'] !== 'administrator') die("Access denied");
require_once './includes/sidebar.php';

?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
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

        <!-- Chart -->
        <div class="card shadow-sm">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">Departmental Activities Overview</h5>
            </div>
            <div class="card-body">
                <canvas id="activitiesChart" height="120"></canvas>
            </div>
        </div>
    </main>
</div>

<?php require_once './includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('activitiesChart'), {
    type: 'bar',
    data: {
        labels: ['10/01','10/02','10/03','10/04','10/05','10/06','10/07','10/08','10/09','10/10','10/11','10/12'],
        datasets: [
            { label: 'Pending',  data: [65, 75, 80, 70, 85, 90, 75, 80, 70, 60, 55, 70], backgroundColor: '#6B0F1A' },
            { label: 'Rejected', data: [20, 15, 10, 25, 15, 10, 20, 15, 10, 20, 25, 15], backgroundColor: '#ffc1cc' },
            { label: 'Approved', data: [95, 90, 95, 90, 95, 100, 95, 90, 95, 90, 95, 95], backgroundColor: '#d4edda' }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' }
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