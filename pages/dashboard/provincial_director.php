<?php
// pages/dashboard/provincial_director.php
// This file is loaded by dashboard.php → header + session already started
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}
if ($_SESSION['role'] !== 'provincial_director') {
    die("Access denied");
}
?>

<!-- Only load sidebar (header already loaded by dashboard.php) -->
<?php require_once './includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="bg-light" style="min-height:100vh;">
        <div class="container-fluid px-4 pt-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-dark mb-0">Provincial Director Dashboard</h2>
                <small class="text-muted"><?= date('l, F d, Y') ?></small>
            </div>

            <!-- 4 Stats Cards -->
            <div class="row g-4 mb-5">
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 text-center p-4">
                        <h5 class="text-muted">Development Projects</h5>
                        <h1 class="text-primary mb-0">24</h1>
                        <small class="text-success">8.5% Up from yesterday</small>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 text-center p-4">
                        <h5 class="text-muted">Animals Treated</h5>
                        <h1 class="text-danger mb-0">2,150</h1>
                        <small class="text-success">1.31% past week</small>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 text-center p-4">
                        <h5 class="text-muted">Vehicle Requests</h5>
                        <h1 class="text-warning mb-0">320</h1>
                        <small class="text-danger">4.3% Down from yesterday</small>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100 text-center p-4">
                        <h5 class="text-muted">Officers Active</h5>
                        <h1 class="text-success mb-0">12</h1>
                        <small class="text-success">8.6% Up from yesterday</small>
                    </div>
                </div>
            </div>

            <!-- Project Progress Chart -->
            <div class="card shadow">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Project Progress (Last 12 Months)</h5>
                </div>
                <div class="card-body">
                    <canvas id="projectChart" height="100"></canvas>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('projectChart'), {
    type: 'bar',
    data: {
        labels: ['10/01','10/02','10/03','10/04','10/05','10/06','10/07','10/08','10/09','10/10','10/11','10/12'],
        datasets: [
            { label: 'Pending',  data: [65,75,80,70,85,90,75,80,70,60,55,70], backgroundColor: '#6B0F1A' },
            { label: 'Rejected', data: [20,15,10,25,15,10,20,15,10,20,25,15], backgroundColor: '#dc3545' },
            { label: 'Approved', data: [95,90,95,90,95,100,95,90,95,90,95,95], backgroundColor: '#198754' }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, max: 100 } }
    }
});
</script>