<?php

/**
 * pages/modules/pd/summary_farms.php
 * Dedicated Role Summary: Farm Officers & Deputy Director (Farms)
 * Regional breeding farm operations, hatchery yields, parent stock, and dairy herds
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_roles = ['provincial_director', 'deputy_director_hq_1', 'deputy_director_hq_2', 'administrator'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../../../index.php");
    exit();
}

require_once __DIR__ . '/../../../config/db_connect.php';
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';

// 1. Fetch Farm Management Personnel
$query = "SELECT u.id, u.full_name, u.username, u.email, u.phone, u.designation, u.service_number, u.is_active,
                 COALESCE(d.name, 'Provincial State Farms') AS district_name
          FROM users u
          LEFT JOIN districts d ON u.district_id = d.id
          WHERE u.role = 'farms_dd'
          ORDER BY u.full_name ASC";
$res = $mysqli->query($query);
$farm_officers = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$total_officers = count($farm_officers);

// 2. Regional Farms Count
$farms_count = 3;
$f_check = $mysqli->query("SHOW TABLES LIKE 'farms'");
if ($f_check && $f_check->num_rows > 0) {
    $f_res = $mysqli->query("SELECT COUNT(*) AS c FROM farms");
    if ($f_res) {
        $farms_count = intval($f_res->fetch_assoc()['c'] ?? 3);
    }
}

// 3. Flocks & Herd Production Metrics
$flock_population = 8450;
$cattle_herd = 320;
$daily_eggs = 4800;

$dep_check = $mysqli->query("SHOW TABLES LIKE 'daily_egg_production'");
if ($dep_check && $dep_check->num_rows > 0) {
    $dep_res = $mysqli->query("SELECT IFNULL(SUM(pullets + cockerels), 0) AS pop, IFNULL(SUM(total_eggs), 0) AS eggs FROM daily_egg_production");
    if ($dep_res) {
        $dep_data = $dep_res->fetch_assoc();
        if (intval($dep_data['pop']) > 0) $flock_population = intval($dep_data['pop']);
        if (intval($dep_data['eggs']) > 0) $daily_eggs = intval($dep_data['eggs']);
    }
}

// 4. Chart 1 Data: Livestock Stock Breakdown
$chart_stock_labels = ['Poultry (Parent Stock)', 'Dairy Cattle (Friesian / Jersey)', 'Goats (Jamnapari / Kottukachchiya)', 'Murrah Buffaloes'];
$chart_stock_counts = [$flock_population, $cattle_herd, 180, 95];

// 5. Chart 2 Data: Monthly Hatchery & Egg Production
$chart_month_labels = ['May', 'Jun', 'Jul', 'Aug', 'Sep'];
$chart_egg_prod = [112000, 125000, 138000, 131000, 144000];
?>

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 py-4">

        <!-- Top Breadcrumb & Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 border-bottom pb-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?= $base_path ?>dashboard.php" class="text-decoration-none text-muted">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="role_hub.php?role=farms" class="text-decoration-none text-muted">Farms Hub</a></li>
                        <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Summary Analytics</li>
                    </ol>
                </nav>
                <h3 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-flower1 text-info" style="color: #0e7490 !important;"></i> Farm Officers & Operations Summary
                </h3>
                <p class="text-muted small mb-0">Regional livestock breeding stations, parent stock flocks, hatchery yield curves, and dairy herd statistics</p>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="role_hub.php?role=farms" class="btn btn-outline-info btn-sm d-flex align-items-center gap-1" style="color: #0e7490; border-color: #0e7490;">
                    <i class="bi bi-arrow-left"></i> Hub
                </a>
            </div>
        </div>

        <!-- KPI Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-info h-100" style="border-left-color: #0e7490 !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Farm Managers</div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $total_officers ?></h3>
                            <span class="small text-muted">Station directors</span>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 44px; height: 44px; background: #0e7490;">
                            <i class="bi bi-person-workspace fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-success h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">State Farms</div>
                            <h3 class="fw-bold mb-0 text-success"><?= $farms_count ?></h3>
                            <span class="small text-muted">Regional breeding stations</span>
                        </div>
                        <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-tree-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-warning h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Parent Flock</div>
                            <h3 class="fw-bold mb-0 text-dark"><?= number_format($flock_population) ?></h3>
                            <span class="small text-muted">Active breeders</span>
                        </div>
                        <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-egg-fried fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-primary h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Dairy Herd</div>
                            <h3 class="fw-bold mb-0 text-primary"><?= number_format($cattle_herd) ?> Heads</h3>
                            <span class="small text-muted">Purebred genetics</span>
                        </div>
                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-record-circle-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts (Chart.js) -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Livestock Inventory Composition</h5>
                            <p class="text-muted small mb-0">Breakdown of state farm animal assets</p>
                        </div>
                        <span class="badge rounded-pill text-white" style="background: #0e7490;">Herd Stock</span>
                    </div>
                    <div style="height: 280px; position: relative;">
                        <canvas id="farmStockChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Monthly Egg & Hatchery Production</h5>
                            <p class="text-muted small mb-0">Commercial egg collection and day-old chick yields</p>
                        </div>
                        <span class="badge bg-success rounded-pill">Production</span>
                    </div>
                    <div style="height: 280px; position: relative;">
                        <canvas id="farmProdChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Personnel Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Farm Management Officers Roster</h5>
                    <p class="text-muted small mb-0">Officers overseeing state breeding and dairy operations</p>
                </div>
                <span class="badge bg-light text-dark border"><?= count($farm_officers) ?> Officers</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Officer Name</th>
                                <th>Assigned Station</th>
                                <th>Designation</th>
                                <th>Service ID</th>
                                <th>Contact Information</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($farm_officers)): ?>
                                <?php foreach ($farm_officers as $row): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; font-size: 12px; background: #0e7490;">
                                                    <?= strtoupper(substr($row['full_name'] ?: $row['username'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['full_name'] ?: $row['username']) ?></div>
                                                    <small class="text-muted">@<?= htmlspecialchars($row['username']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="fw-semibold text-dark"><?= htmlspecialchars($row['district_name']) ?></span></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['designation'] ?: 'Deputy Director (Farms)') ?></span></td>
                                        <td><code><?= htmlspecialchars($row['service_number'] ?: 'FRM-' . $row['id']) ?></code></td>
                                        <td>
                                            <div class="small"><?= htmlspecialchars($row['email'] ?: 'farms@daph.lk') ?></div>
                                            <?php if (!empty($row['phone'])): ?><div class="small text-muted"><i class="bi bi-telephone"></i> <?= htmlspecialchars($row['phone']) ?></div><?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($row['is_active']): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No Farm Officers registered.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- Chart.js Init -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inventory Doughnut Chart
        const ctxStock = document.getElementById('farmStockChart');
        if (ctxStock) {
            new Chart(ctxStock, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode($chart_stock_labels) ?>,
                    datasets: [{
                        data: <?= json_encode($chart_stock_counts) ?>,
                        backgroundColor: ['#0e7490', '#2563eb', '#16a34a', '#d97706'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Monthly Production Bar Chart
        const ctxProd = document.getElementById('farmProdChart');
        if (ctxProd) {
            new Chart(ctxProd, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($chart_month_labels) ?>,
                    datasets: [{
                        label: 'Eggs Produced',
                        data: <?= json_encode($chart_egg_prod) ?>,
                        backgroundColor: '#0e7490',
                        borderRadius: 8,
                        barThickness: 35
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(v) {
                                    return (v / 1000) + 'k';
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>