<?php
// pages/dashboard/planning_officer.php
if ($_SESSION['role'] !== 'planning_officer') {
    die("Access denied");
}
require_once './includes/header.php';
require_once './includes/sidebar.php';
?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-5 text-dark">Planning Officer Dashboard</h2>

        <!-- 4 Key Metrics Cards -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Total Active Projects</h6>
                    <h2 class="text-primary mb-2">42</h2>
                    <small class="text-success"><i class="bi bi-arrow-up"></i> 12% from last quarter</small>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Projects On Track</h6>
                    <h2 class="text-success mb-2">31</h2>
                    <small class="text-success"><i class="bi bi-arrow-up"></i> 5% improvement</small>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Delayed Projects</h6>
                    <h2 class="text-danger mb-2">8</h2>
                    <small class="text-danger"><i class="bi bi-arrow-down"></i> 3 delayed this month</small>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Total Budget Allocated</h6>
                    <h2 class="text-info mb-2">Rs 1.2B</h2>
                    <small class="text-success"><i class="bi bi-arrow-up"></i> 18% from last year</small>
                </div>
            </div>
        </div>

        <!-- Chart -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Project Progress</h5>
            </div>
            <div class="card-body">
                <canvas id="projectChart" height="120"></canvas>
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
        labels: ['10/01','10/02','10/03','10/04','10/05','10/06','10/07','10/08','10/09','10/10','10/11','10/12'],
        datasets: [
            { label: 'Pending', data: [50,60,70,55,80,90,70,75,65,50,45,60], backgroundColor: '#6B0F1A' },
            { label: 'Rejected', data: [20,25,15,30,20,15,25,20,15,25,30,20], backgroundColor: '#ffc1cc' },
            { label: 'Approved', data: [90,85,95,80,90,95,85,90,95,85,80,90], backgroundColor: '#d4edda' }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, max: 100 } }
    }
});
</script>