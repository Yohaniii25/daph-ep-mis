<?php
// pages/dashboard/veterinary_office.php - GVO Dashboard
if (!in_array($_SESSION['role'], ['veterinary_surgeon', 'ldo'])) {
    die("Access denied");
}
require_once './includes/header.php';
require_once './includes/sidebar.php';


?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-5 text-dark">Government Veterinary Office Dashboard</h2>

        <div class="row g-4 mb-5">
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm h-100 p-3 text-center">
                    <h6 class="text-muted">Today's Treatments</h6>
                    <h2 class="text-primary">28</h2>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm h-100 p-3 text-center">
                    <h6 class="text-muted">Immunizations Today</h6>
                    <h2 class="text-success">45</h2>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm h-100 p-3 text-center">
                    <h6 class="text-muted">Pending Diary Entries</h6>
                    <h2 class="text-warning">3</h2>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm h-100 p-3 text-center">
                    <h6 class="text-muted">Low Stock Items</h6>
                    <h2 class="text-danger">7</h2>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm h-100 p-3 text-center">
                    <h6 class="text-muted">Today's Revenue (Rs)</h6>
                    <h2 class="text-info">185,000</h2>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm h-100 p-3 text-center">
                    <h6 class="text-muted">Farmers Trained</h6>
                    <h2 class="text-secondary">12</h2>
                </div>
            </div>
        </div>

        <!-- Daily Activity Chart -->
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