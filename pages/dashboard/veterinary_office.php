<?php

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../index.php");
    exit();
}

// full name 
if (!isset($_SESSION['full_name'])) {
    $_SESSION['full_name'] = $_SESSION['username'] ?? 'Veterinary Surgeon';
}

$full_name = $_SESSION['full_name'];

require_once __DIR__ . '/../../config/db_connect.php';

$district_name = 'Your District';
$range_name    = 'Your Assigned Range';

// Ensure we have district_id & range_id
if (empty($_SESSION['district_id'])) {
    $user_query = $mysqli->prepare("SELECT district_id, range_id FROM users WHERE id = ?");
    if ($user_query) {
        $user_query->bind_param("i", $_SESSION['user_id']);
        $user_query->execute();
        $user_result = $user_query->get_result();
        if ($user_result->num_rows > 0) {
            $user_data = $user_result->fetch_assoc();
            $_SESSION['district_id'] = $user_data['district_id'];
            $_SESSION['range_id'] = $user_data['range_id'];
        }
        $user_query->close();
    }
}

// Fetch district name
if (!empty($_SESSION['district_id'])) {
    $district_query = $mysqli->prepare("SELECT name FROM districts WHERE id = ?");
    if ($district_query) {
        $district_query->bind_param("i", $_SESSION['district_id']);
        $district_query->execute();
        $district_result = $district_query->get_result();
        if ($district_result->num_rows > 0) {
            $district_data = $district_result->fetch_assoc();
            $district_name = $district_data['name'] ?? 'Your District';
        }
        $district_query->close();
    }
}

// Fetch range name
if (!empty($_SESSION['range_id'])) {
    $range_query = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
    if ($range_query) {
        $range_query->bind_param("i", $_SESSION['range_id']);
        $range_query->execute();
        $range_result = $range_query->get_result();
        if ($range_result->num_rows > 0) {
            $range_data = $range_result->fetch_assoc();
            $range_name = $range_data['name'] ?? 'Your Assigned Range';
        }
        $range_query->close();
    }
}

$stats = [
    'health'     => 18,
    'breeding'   => 7,
    'regulatory' => 4,
    'office'     => 12
];

require_once __DIR__ . '/../../config/constants.php';
?>

<div class="content-wrapper">
    <div class="container-fluid py-4">

        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm" style="color: black;">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h4 style="color: #370709; text-align: left;" class="mb-0 font">Veterinary Office Dashboard</h4>
                            <p class="mb-0">
                                <strong><?= htmlspecialchars($full_name) ?></strong><br>
                                <small>District: <strong><?= htmlspecialchars($district_name) ?></strong> | 
                                       Range: <strong><?= htmlspecialchars($range_name) ?></strong></small>
                            </p>
                        </div>
                    
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Animal Health</h6>
                    <h2 class="text-primary mb-2"><?= $stats['health'] ?></h2>
                    <small class="text-success"><i class="bi bi-arrow-up"></i> 12% Up from last month</small>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Animal Breeding</h6>
                    <h2 class="text-warning mb-2"><?= $stats['breeding'] ?></h2>
                    <small class="text-success"><i class="bi bi-arrow-up"></i> 5% Up from last month</small>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Regulatory Functions</h6>
                    <h2 class="text-danger mb-2"><?= $stats['regulatory'] ?></h2>
                    <small class="text-danger"><i class="bi bi-arrow-down"></i> 2% Down from last month</small>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Office Details</h6>
                    <h2 class="text-info mb-2"><?= $stats['office'] ?></h2>
                    <small class="text-success"><i class="bi bi-arrow-up"></i> 3% Up from last month</small>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold text-muted small text-uppercase"><i class="bi bi-lightning-charge me-2 text-warning"></i>Quick Actions</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <a href="<?= BASE_PATH ?>pages/modules/veterinary/animal_health.php" class="btn btn-success w-100 py-3 shadow-sm border-0 text-white d-block">
                    <i class="bi bi-journal-medical fs-4"></i><br>
                    <span style="color:white" >Health Records</span>
                </a>
            </div>
            <div class="col-md-3">
                <a href="<?= BASE_PATH ?>pages/modules/veterinary/animal_breeding.php" class="btn btn-primary w-100 py-3 shadow-sm border-0 text-white d-block">
                    <i class="bi bi-egg fs-4"></i><br>
                    <span style="color:white" >Animal Breeding</span>
                </a>
            </div>
            <div class="col-md-3">
                <a href="<?= BASE_PATH ?>pages/modules/veterinary/regulatory_functions.php" class="btn btn-info w-100 py-3 shadow-sm border-0 text-white d-block">
                    <i class="bi bi-file-earmark-medical fs-4"></i><br>
                    <span style="color:white" >Regulatory</span>
                </a>
            </div>
            <div class="col-md-3">
                <a href="<?= BASE_PATH ?>pages/modules/veterinary/office_details_view.php" class="btn btn-warning w-100 py-3 shadow-sm border-0 text-white d-block">
                    <i class="bi bi-building fs-4"></i><br>
                    <span style="color:white" >Office Inventory</span>
                </a>
            </div>
        </div>
    </div>
</div>

        <!-- Live stock statistics -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5>Maintain Livestock Statistics</h5>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 400px;">
                    <canvas id="livestockChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Monitoring -->
<div class="row g-4">
    
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 text-primary"><i class="bi bi-eye me-2"></i> Monitoring Summary</h5>
                <a href="#" class="btn btn-sm btn-outline-primary">View Full Log</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-3">Date</th>
                                <th>Activity / Site</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="ps-3 small">2026-03-25</td>
                                <td>
                                    <div class="fw-bold">Vaccination Program Check</div>
                                    <small class="text-muted">Kantalai Range - Site A</small>
                                </td>
                                <td><span class="badge bg-success-soft text-success border border-success">Completed</span></td>
                                <td class="text-center"><button class="btn btn-sm btn-light"><i class="bi bi-info-circle"></i></button></td>
                            </tr>
                            <tr>
                                <td class="ps-3 small">2026-03-27</td>
                                <td>
                                    <div class="fw-bold">Cold Chain Monitoring</div>
                                    <small class="text-muted">Regional Vaccine Store</small>
                                </td>
                                <td><span class="badge bg-warning-soft text-warning border border-warning">In Progress</span></td>
                                <td class="text-center"><button class="btn btn-sm btn-light"><i class="bi bi-info-circle"></i></button></td>
                            </tr>
                            <tr>
                                <td class="ps-3 small">2026-03-28</td>
                                <td>
                                    <div class="fw-bold">Farm Registration Audit</div>
                                    <small class="text-muted">Trincomalee West</small>
                                </td>
                                <td><span class="badge bg-secondary-soft text-secondary border border-secondary">Scheduled</span></td>
                                <td class="text-center"><button class="btn btn-sm btn-light"><i class="bi bi-info-circle"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary"><i class="bi bi-mortarboard me-2"></i> Training Overview</h5>
            </div>
            <div class="card-body">
                <div class="p-3 mb-4 rounded" style="background-color: #f8f9fa; border-left: 5px solid #efbe2c;">
                    <h6 class="text-muted small text-uppercase fw-bold">Held Today</h6>
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="mb-0">Dairy Management</h4>
                            <small class="text-muted">Koddiyar Bay Sub-office</small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-primary rounded-pill">24 Farmers</span>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-3 border rounded text-center bg-light">
                            <i class="bi bi-people text-primary fs-3"></i>
                            <h3 class="mt-2 mb-0">342</h3>
                            <p class="text-muted small mb-0">Farmers Trained</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 border rounded text-center bg-light">
                            <i class="bi bi-calendar-event text-warning fs-3"></i>
                            <h3 class="mt-2 mb-0">18</h3>
                            <p class="text-muted small mb-0">Programs Held </p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-grid">
                    <button class="btn btn-outline-primary btn-sm">Schedule New Program</button>
                </div>
            </div>
        </div>
    </div>

</div>
    </div>
</div>

<!-- Chart.js Script for Livestock Bar Graph -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('livestockChart');
        
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Cattle', 'Buffalo', 'Goat', 'Sheep', 'Swine', 'Poultry', 'Ornamental Birds', 'Others'],
                    datasets: [{
                        label: 'Number of Animals',
                        data: [1245, 1187, 1312, 1154, 1119, 1280, 1136, 1108],
                        backgroundColor: ['#820100', '#efbe2c', '#370709', '#ef4016', '#d4c7b7', '#8d170e', '#b08723', '#a07174'],
                        
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            display: false 
                        },
                        title: { 
                            display: true, 
                            text: 'Livestock Population in Your Range',
                            font: { size: 16 }
                        }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }
    });
</script>
