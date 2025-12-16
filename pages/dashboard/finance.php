<?php
// Finance Admin Dashboard - Only for finance_admin role
if ($_SESSION['role'] !== 'finance_admin') {
    die("Access denied");
}
require_once './includes/header.php';
require_once './includes/sidebar.php';
?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-5 text-dark">Finance Dashboard</h2>

        <!-- 4 Finance Cards -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Total Assets</h6>
                    <h2 class="text-primary mb-2">156</h2>
                    <small class="text-success"><i class="bi bi-arrow-up"></i> 8.5% Up from yesterday</small>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Pending Procurement Requests</h6>
                    <h2 class="text-warning mb-2">16</h2>
                    <small class="text-success"><i class="bi bi-arrow-up"></i> 1.3% Up from past week</small>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Total Funds Allocated (Rs)</h6>
                    <h2 class="text-info mb-2">5,507,080.00</h2>
                    <small class="text-danger"><i class="bi bi-arrow-down"></i> 4.3% Down from yesterday</small>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Veterinary Store Stock Level</h6>
                    <h2 class="text-success mb-2">87%</h2>
                    <small class="text-success"><i class="bi bi-arrow-up"></i> 1.8% Up from yesterday</small>
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