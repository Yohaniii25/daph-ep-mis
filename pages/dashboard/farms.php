<?php
// pages/dashboard/farms.php
if ($_SESSION['role'] !== 'farms_dd') die("Access denied");
require_once './includes/header.php';
require_once './includes/sidebar.php';
?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-5 text-dark">Farms Operations Dashboard</h2>

        <!-- 4 Key Cards -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Poultry Production (Birds/Day)</h6>
                    <h2 class="text-primary mb-2">8,450</h2>
                    <small class="text-success"><i class="bi bi-arrow-up"></i> 6.2% Up from yesterday</small>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Daily Milk Production (Litres)</h6>
                    <h2 class="text-info mb-2">12,300</h2>
                    <small class="text-success"><i class="bi bi-arrow-up"></i> 4.8% Up from yesterday</small>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Fodder Distribution (Tons)</h6>
                    <h2 class="text-warning mb-2">145</h2>
                    <small class="text-danger"><i class="bi bi-arrow-down"></i> 2.1% Down from yesterday</small>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Monthly Revenue (Rs)</h6>
                    <h2 class="text-success mb-2">28,450,000</h2>
                    <small class="text-success"><i class="bi bi-arrow-up"></i> 15.3% Up from last month</small>
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