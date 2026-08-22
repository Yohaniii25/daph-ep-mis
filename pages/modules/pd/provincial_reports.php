<?php
require_once '../../../includes/header.php';
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['provincial_director', 'deputy_director_hq_1', 'deputy_director_hq_2', 'administrator'])) {
    die("Access denied");
}
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Provincial Reports - All Department Activities</h2>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card shadow-sm text-center p-4">
                    <h5 class="text-primary">Total Revenue (2025)</h5>
                    <h2>Rs 485,000,000</h2>
                    <small class="text-success">+18% vs 2024</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm text-center p-4">
                    <h5 class="text-info">Animals Treated</h5>
                    <h2>124,500</h2>
                    <small class="text-success">+12% vs last year</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm text-center p-4">
                    <h5 class="text-warning">Active Projects</h5>
                    <h2>42</h2>
                    <small class="text-success">89% on track</small>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mt-4">
            <div class="card-header">
                <h5>Key Activity Summary</h5>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Farm Registrations</span>
                        <strong>1,245</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Immunization Campaigns</span>
                        <strong>89 completed</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Farmer Training Sessions</span>
                        <strong>156 conducted</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>RTI Requests Processed</span>
                        <strong>48</strong>
                    </li>
                </ul>
            </div>
        </div>
    </main>
</div>

<?php require_once '../../../includes/footer.php'; ?>