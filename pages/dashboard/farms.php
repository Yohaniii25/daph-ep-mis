<?php
// pages/dashboard/farms.php -> Farm Operations Dashboard
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['farms_dd', 'administrator', 'provincial_director'])) {
    die("Access denied");
}

require_once 'config/db_connect.php';

$user_id = $_SESSION['user_id'] ?? 1;
$role = $_SESSION['role'] ?? '';

// 1. Parent Stock Population (Sum of pullets and cockerels from latest collection for each active batch/cage)
$flock_pop_query = "SELECT IFNULL(SUM(dep.pullets + dep.cockerels), 0) AS pop 
                    FROM daily_egg_production dep
                    JOIN vaccine_batches b ON dep.batch_id = b.id
                    WHERE (dep.batch_id, dep.cage_id, dep.collection_date) IN (
                        SELECT batch_id, cage_id, MAX(collection_date)
                        FROM daily_egg_production
                        GROUP BY batch_id, cage_id
                    )";
$flock_pop = 0;
if ($flock_pop_res = $mysqli->query($flock_pop_query)) {
    $flock_pop = (int)$flock_pop_res->fetch_assoc()['pop'];
}

// 2. Latest Daily Egg Collection (total_eggs)
$egg_latest_query = "SELECT IFNULL(SUM(total_eggs), 0) AS total_eggs, MAX(collection_date) AS latest_date 
                     FROM daily_egg_production 
                     WHERE collection_date = (SELECT MAX(collection_date) FROM daily_egg_production)";
$egg_today = 0;
$latest_egg_date = null;
if ($egg_res = $mysqli->query($egg_latest_query)) {
    $row = $egg_res->fetch_assoc();
    $egg_today = (int)($row['total_eggs'] ?? 0);
    $latest_egg_date = $row['latest_date'] ?? null;
}

// 3. Average Hatchability Rate (%)
$hatch_query = "SELECT IFNULL(AVG(hatchability_percentage), 0) AS avg_hatchability 
                FROM daily_egg_production 
                WHERE eggs_loaded > 0 OR hatchability_percentage > 0";
$hatch_rate = 0.0;
if ($hatch_res = $mysqli->query($hatch_query)) {
    $hatch_rate = round((float)$hatch_res->fetch_assoc()['avg_hatchability'], 1);
}

// 4. Current Month Chick Mortality Total
$cur_year = date('Y');
$cur_month = date('m');
$mortality_query = "SELECT IFNULL(SUM(deaths), 0) AS total_deaths 
                    FROM chicks_death_details 
                    WHERE YEAR(record_month) = $cur_year AND MONTH(record_month) = $cur_month";
$monthly_deaths = 0;
if ($mort_res = $mysqli->query($mortality_query)) {
    $monthly_deaths = (int)$mort_res->fetch_assoc()['total_deaths'];
}

// ================= CHART DATA PREPARATION =================

// Chart 1: Monthly/Daily Egg Production (Grouped Bar Chart)
$prod_dates = [];
$bar_hatchable = [];
$bar_table = [];
$bar_cracked = [];

$bar_query = "SELECT collection_date, 
                     SUM(hatchable_eggs) AS hatchable, 
                     SUM(table_eggs) AS table_e, 
                     SUM(cracked_eggs) AS cracked 
              FROM daily_egg_production 
              GROUP BY collection_date 
              ORDER BY collection_date ASC 
              LIMIT 14";
if ($bar_res = $mysqli->query($bar_query)) {
    while ($row = $bar_res->fetch_assoc()) {
        $prod_dates[] = date('d M', strtotime($row['collection_date']));
        $bar_hatchable[] = (int)$row['hatchable'];
        $bar_table[] = (int)$row['table_e'];
        $bar_cracked[] = (int)$row['cracked'];
    }
}
if (empty($prod_dates)) {
    $prod_dates = ['No Data'];
    $bar_hatchable = [0];
    $bar_table = [0];
    $bar_cracked = [0];
}

// Chart 2: Hatchability Trend (Line Chart)
$hatch_dates = [];
$hatch_rates = [];

$line_query = "SELECT collection_date, hatching_date, hatchability_percentage 
               FROM daily_egg_production 
               WHERE hatchability_percentage > 0 OR eggs_loaded > 0 
               ORDER BY collection_date ASC 
               LIMIT 14";
if ($line_res = $mysqli->query($line_query)) {
    while ($row = $line_res->fetch_assoc()) {
        $date_label = !empty($row['hatching_date']) ? date('d M', strtotime($row['hatching_date'])) : date('d M', strtotime($row['collection_date']));
        $hatch_dates[] = $date_label;
        $hatch_rates[] = (float)$row['hatchability_percentage'];
    }
}
if (empty($hatch_dates)) {
    $hatch_dates = ['No Data'];
    $hatch_rates = [0];
}

// Chart 3: Chick Mortality by Batch (Pie / Doughnut Chart)
$pie_batches = [];
$pie_deaths = [];

$pie_query = "SELECT batch_no, SUM(deaths) AS total_deaths 
              FROM chicks_death_details 
              WHERE YEAR(record_month) = $cur_year AND MONTH(record_month) = $cur_month 
              GROUP BY batch_no 
              ORDER BY total_deaths DESC";
$pie_res = $mysqli->query($pie_query);
if ($pie_res && $pie_res->num_rows > 0) {
    while ($row = $pie_res->fetch_assoc()) {
        $pie_batches[] = $row['batch_no'];
        $pie_deaths[] = (int)$row['total_deaths'];
    }
} else {
    // Fallback if current month has no data: fetch all available mortality data
    $pie_all = $mysqli->query("SELECT batch_no, SUM(deaths) AS total_deaths FROM chicks_death_details GROUP BY batch_no ORDER BY total_deaths DESC LIMIT 7");
    if ($pie_all) {
        while ($row = $pie_all->fetch_assoc()) {
            $pie_batches[] = $row['batch_no'];
            $pie_deaths[] = (int)$row['total_deaths'];
        }
    }
}
if (empty($pie_batches)) {
    $pie_batches = ['No Mortality Recorded'];
    $pie_deaths = [0];
}

require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Detect local Chart.js path
$chart_js_path = 'assets/js/chart.min.js';
if (!file_exists($chart_js_path)) {
    if (file_exists('../assets/js/chart.min.js')) {
        $chart_js_path = '../assets/js/chart.min.js';
    } elseif (file_exists('../../assets/js/chart.min.js')) {
        $chart_js_path = '../../assets/js/chart.min.js';
    } else {
        $chart_js_path = '../../../assets/js/chart.min.js';
    }
}
?>

<!-- Locally Hosted Chart.js (NO CDN) -->
<script src="<?= $chart_js_path ?>"></script>

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark m-0">Farm Operations Dashboard</h2>
                <small class="text-muted">Real-time statistics for daily egg collection, hatchability performance, and chick mortality.</small>
            </div>
            <span class="badge bg-secondary p-2 fs-6">Logged in: <b><?= htmlspecialchars($_SESSION['username']) ?></b></span>
        </div>

        <!-- 4 Key Stat Cards -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 border-start border-primary border-4 farm-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-muted m-0 fw-bold">Parent Stock Population</h6>
                        <i class="bi bi-pie-chart-fill text-primary fs-4"></i>
                    </div>
                    <h2 class="text-primary mb-1 fw-bold"><?= number_format($flock_pop) ?></h2>
                    <small class="text-muted">Total Live Breeders (Pullets & Cockerels)</small>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 border-start border-info border-4 farm-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-muted m-0 fw-bold">Latest Egg Collection</h6>
                        <i class="bi bi-egg-fried text-info fs-4"></i>
                    </div>
                    <h2 class="text-info mb-1 fw-bold"><?= number_format($egg_today) ?></h2>
                    <small class="text-muted"><?= $latest_egg_date ? 'Recorded on ' . date('d-M-Y', strtotime($latest_egg_date)) : 'Intake Collection' ?></small>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 border-start border-warning border-4 farm-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-muted m-0 fw-bold">Average Hatchability</h6>
                        <i class="bi bi-graph-up-arrow text-warning fs-4"></i>
                    </div>
                    <h2 class="text-warning mb-1 fw-bold"><?= number_format($hatch_rate, 1) ?>%</h2>
                    <small class="text-muted">Incubation Success Rate</small>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 border-start border-danger border-4 farm-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-muted m-0 fw-bold">Monthly Chick Mortality</h6>
                        <i class="bi bi-heart-pulse-fill text-danger fs-4"></i>
                    </div>
                    <h2 class="text-danger mb-1 fw-bold"><?= number_format($monthly_deaths) ?></h2>
                    <small class="text-muted">Total Deaths for <?= date('F Y') ?></small>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row g-4 mb-4">
            <!-- Chart 1: Monthly Egg Production (Grouped Bar Chart) -->
            <div class="col-xl-7">
                <div class="card border-0 shadow-sm h-100 farm-card">
                    <div class="card-header bg-white border-0 pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-bar-chart-line-fill me-2 text-primary"></i>Daily Egg Production Breakdown</h5>
                            <small class="text-muted">Hatchable, Table, and Cracked eggs per collection date</small>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="farm-chart-container">
                            <canvas id="eggProductionBarChart" height="150"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart 3: Chick Mortality by Batch (Doughnut / Pie Chart) -->
            <div class="col-xl-5">
                <div class="card border-0 shadow-sm h-100 farm-card">
                    <div class="card-header bg-white border-0 pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-pie-chart-fill me-2 text-danger"></i>Chick Mortality by Batch</h5>
                            <small class="text-muted">Distribution of deaths per batch for <?= date('F Y') ?></small>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4 d-flex flex-column align-items-center">
                        <div class="position-relative mx-auto mb-3" style="width: 220px; height: 220px;">
                            <canvas id="mortalityPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart 2: Hatchability Trend (Line Chart) -->
        <div class="row g-4 mb-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm farm-card">
                    <div class="card-header bg-white border-0 pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-activity me-2 text-warning"></i>Hatchability % Trend</h5>
                            <small class="text-muted">Incubation success percentage across hatching dates</small>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="farm-chart-container">
                            <canvas id="hatchabilityLineChart" height="90"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Chart 1: Monthly Egg Production (Grouped Bar Chart)
    const ctxBar = document.getElementById('eggProductionBarChart').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: <?= json_encode($prod_dates) ?>,
            datasets: [
                {
                    label: 'Hatchable Eggs',
                    data: <?= json_encode($bar_hatchable) ?>,
                    backgroundColor: '#370709',
                    borderRadius: 4
                },
                {
                    label: 'Table Eggs',
                    data: <?= json_encode($bar_table) ?>,
                    backgroundColor: '#10b981',
                    borderRadius: 4
                },
                {
                    label: 'Cracked Eggs',
                    data: <?= json_encode($bar_cracked) ?>,
                    backgroundColor: '#ef4444',
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: { display: false }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' }
                }
            },
            plugins: {
                legend: { position: 'top' },
                tooltip: { padding: 10 }
            }
        }
    });

    // 2. Chart 3: Chick Mortality by Batch (Doughnut Chart)
    const ctxPie = document.getElementById('mortalityPieChart').getContext('2d');
    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($pie_batches) ?>,
            datasets: [{
                data: <?= json_encode($pie_deaths) ?>,
                backgroundColor: ['#dc3545', '#fd7e14', '#ffc107', '#20c997', '#0d6efd', '#6f42c1', '#d63384'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // 3. Chart 2: Hatchability Trend (Line Chart)
    const ctxLine = document.getElementById('hatchabilityLineChart').getContext('2d');
    new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: <?= json_encode($hatch_dates) ?>,
            datasets: [{
                label: 'Hatchability Rate (%)',
                data: <?= json_encode($hatch_rates) ?>,
                borderColor: '#ffc107',
                backgroundColor: 'rgba(255, 193, 7, 0.15)',
                fill: true,
                tension: 0.3,
                pointRadius: 5,
                pointBackgroundColor: '#370709',
                pointBorderColor: '#ffc107',
                pointBorderWidth: 2,
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { grid: { display: false } },
                y: {
                    min: 0,
                    max: 100,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    ticks: {
                        callback: function(value) { return value + '%'; }
                    }
                }
            },
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function(context) { return 'Hatchability: ' + context.parsed.y + '%'; }
                    }
                }
            }
        }
    });

});
</script>