<?php
// pages/dashboard/farms.php
if (!in_array($_SESSION['role'], ['farms_dd', 'administrator', 'provincial_director'])) {
    die("Access denied");
}

require_once 'config/db_connect.php';

$role = $_SESSION['role'] ?? '';
$farm_id = $_SESSION['farm_id'] ?? null;

// Determine if we scope to a specific farm or show aggregate data
$scoped = ($role === 'farms_dd' && !empty($farm_id));

// 1. Parent Stock Population (Sum of pullets and cockerels from the latest collection for each active batch/cage combination)
$pop_where = $scoped ? " AND u.farm_id = " . (int)$farm_id : "";
$flock_pop_query = "SELECT IFNULL(SUM(dep.pullets + dep.cockerels), 0) AS pop 
                    FROM daily_egg_production dep
                    JOIN vaccine_batches b ON dep.batch_id = b.id
                    JOIN users u ON b.user_id = u.id
                    WHERE (dep.batch_id, dep.cage_id, dep.collection_date) IN (
                        SELECT batch_id, cage_id, MAX(collection_date)
                        FROM daily_egg_production
                        GROUP BY batch_id, cage_id
                    )" . $pop_where;
$flock_pop = 0;
if ($flock_pop_res = $mysqli->query($flock_pop_query)) {
    $flock_pop = (int)$flock_pop_res->fetch_assoc()['pop'];
}

// 2. Today's Egg Collection (total_eggs from daily_egg_production)
$egg_where = $scoped ? " AND u.farm_id = " . (int)$farm_id : "";
$egg_today_query = "SELECT IFNULL(SUM(dep.total_eggs), 0) AS total_eggs 
                    FROM daily_egg_production dep
                    JOIN vaccine_batches b ON dep.batch_id = b.id
                    JOIN users u ON b.user_id = u.id
                    WHERE dep.collection_date = CURDATE()" . $egg_where;
$egg_today = 0;
if ($egg_today_res = $mysqli->query($egg_today_query)) {
    $egg_today = (int)$egg_today_res->fetch_assoc()['total_eggs'];
}

if ($egg_today === 0) {
    // Fallback: show count from the latest collection date
    $egg_latest_query = "SELECT IFNULL(SUM(dep.total_eggs), 0) AS total_eggs 
                         FROM daily_egg_production dep
                         JOIN vaccine_batches b ON dep.batch_id = b.id
                         JOIN users u ON b.user_id = u.id
                         WHERE dep.collection_date = (
                             SELECT MAX(collection_date) FROM daily_egg_production d
                             JOIN vaccine_batches bt ON d.batch_id = bt.id
                             JOIN users us ON bt.user_id = us.id
                             " . ($scoped ? " WHERE us.farm_id = " . (int)$farm_id : "") . "
                         )" . $egg_where;
    if ($egg_latest_res = $mysqli->query($egg_latest_query)) {
        $egg_today = (int)$egg_latest_res->fetch_assoc()['total_eggs'];
    }
}

// 3. Average Hatchability (calculated from hatchery_batches)
$hatchery_where = $scoped ? " WHERE farm_id = " . (int)$farm_id : "";
$hatch_query = "SELECT IFNULL(SUM(chicks_hatched), 0) AS total_chicks,
                       IFNULL(SUM(hatchable_count), 0) AS total_hatchable
                FROM hatchery_batches" . $hatchery_where;
$hatch_rate = 0.0;
if ($hatch_res = $mysqli->query($hatch_query)) {
    $hatch_data = $hatch_res->fetch_assoc();
    $hatch_rate = $hatch_data['total_hatchable'] > 0
        ? round(($hatch_data['total_chicks'] / $hatch_data['total_hatchable']) * 100, 1)
        : 0.0;
}

// 4. Outlet Sales Revenue
$sales_where = $scoped ? " WHERE farm_id = " . (int)$farm_id : "";
$revenue_query = "SELECT IFNULL(SUM(total_revenue), 0) AS total_sales FROM hatchery_sales" . $sales_where;
$revenue_total = 0.0;
if ($revenue_res = $mysqli->query($revenue_query)) {
    $revenue_total = (float)$revenue_res->fetch_assoc()['total_sales'];
}

// Doughnut chart queries: Stock breakdown by batch
$chart_flocks = [];
$chart_counts = [];
$chart_total = 0;
$chart_query = "SELECT b.batch_number AS batch_name, IFNULL(SUM(dep.pullets + dep.cockerels), 0) AS current_count 
                FROM daily_egg_production dep
                JOIN vaccine_batches b ON dep.batch_id = b.id
                JOIN users u ON b.user_id = u.id
                WHERE (dep.batch_id, dep.cage_id, dep.collection_date) IN (
                    SELECT batch_id, cage_id, MAX(collection_date)
                    FROM daily_egg_production
                    GROUP BY batch_id, cage_id
                )" . $pop_where . "
                GROUP BY b.id, b.batch_number";
if ($chart_res = $mysqli->query($chart_query)) {
    while ($row = $chart_res->fetch_assoc()) {
        $chart_flocks[] = $row['batch_name'];
        $chart_counts[] = (int)$row['current_count'];
        $chart_total += (int)$row['current_count'];
    }
}
if (empty($chart_flocks)) {
    $chart_flocks = ['No Active Batches'];
    $chart_counts = [0];
}

// Commercial Hatchery Batches
$batch_query = "SELECT hb.*, rf.farm_name, rf.location 
                FROM hatchery_batches hb
                JOIN regional_farms rf ON hb.farm_id = rf.id" .
    ($scoped ? " WHERE hb.farm_id = " . (int)$farm_id : "") . "
                ORDER BY hb.batch_date DESC LIMIT 5";
$batches = [];
if ($batch_res = $mysqli->query($batch_query)) {
    while ($row = $batch_res->fetch_assoc()) {
        $batches[] = $row;
    }
}

// Sales by Category
$outlets_query = "SELECT egg_category, SUM(total_revenue) AS total_revenue, SUM(quantity_sold) AS qty 
                  FROM hatchery_sales" . $sales_where . "
                  GROUP BY egg_category";
$outlets = [];
if ($outlets_res = $mysqli->query($outlets_query)) {
    while ($row = $outlets_res->fetch_assoc()) {
        $outlets[] = $row;
    }
}

// Month-by-month sales data for line chart
$chart_months = [];
$chart_revenues = [];
$month_query = "SELECT DATE_FORMAT(sales_date, '%b') AS month_name, SUM(total_revenue) AS total_rev 
                FROM hatchery_sales" . $sales_where . "
                GROUP BY DATE_FORMAT(sales_date, '%m'), DATE_FORMAT(sales_date, '%b') 
                ORDER BY DATE_FORMAT(sales_date, '%m') ASC LIMIT 6";
if ($month_res = $mysqli->query($month_query)) {
    while ($row = $month_res->fetch_assoc()) {
        $chart_months[] = $row['month_name'];
        $chart_revenues[] = round((float)$row['total_rev'] / 1000, 1); // In Thousands LKR
    }
}
if (empty($chart_months)) {
    $chart_months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    $chart_revenues = [0, 0, 0, 0, 0, 0];
}

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-dark m-0">Farms Operations Dashboard</h2>
            <?php if ($scoped): ?>
                <?php
                // Fetch assigned Farm info
                $farm_info = $mysqli->query("SELECT farm_name, location FROM regional_farms WHERE id = " . (int)$farm_id)->fetch_assoc();
                ?>
                <span class="badge bg-secondary p-2 fs-6">
                    Scoped to: <b><?= htmlspecialchars($farm_info['farm_name'] ?? 'Assigned Farm') ?></b> (<?= htmlspecialchars($farm_info['location'] ?? '') ?>)
                </span>
            <?php else: ?>
                <span class="badge bg-primary p-2 fs-6">
                    Region: <b>All Regional Farms (Consolidated)</b>
                </span>
            <?php endif; ?>
        </div>

        <!-- 4 Key Cards -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 border-start border-primary border-4">
                    <h6 class="text-muted mb-3">Parent Stock Population</h6>
                    <h2 class="text-primary mb-2 fw-bold"><?= number_format($flock_pop) ?></h2>
                    <small class="text-muted">Total Live Breeders</small>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 border-start border-info border-4">
                    <h6 class="text-muted mb-3">Latest Daily Egg Count</h6>
                    <h2 class="text-info mb-2 fw-bold"><?= number_format($egg_today) ?></h2>
                    <small class="text-muted">Intake Collection</small>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 border-start border-warning border-4">
                    <h6 class="text-muted mb-3">Average Hatchability</h6>
                    <h2 class="text-warning mb-2 fw-bold"><?= htmlspecialchars($hatch_rate) ?>%</h2>
                    <small class="text-muted">Incubation Success Rate</small>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 border-start border-success border-4">
                    <h6 class="text-muted mb-3">Total Outlet Revenue</h6>
                    <h2 class="text-success mb-2 fw-bold">LKR <?= number_format($revenue_total, 2) ?></h2>
                    <small class="text-muted">Cumulative Sales</small>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- 1. Stock Categorization (Donut Chart) -->
            <div class="col-xl-5">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
                    <div class="card-header bg-white border-0 pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0 fw-semibold text-dark" style="font-weight: bold !important; letter-spacing: .01em;">Flock Stock Distribution</h4>
                            <small class="text-muted">Parent Breeder flocks breakdown</small>
                        </div>
                        <i class="bi bi-pie-chart text-primary" style="font-size: 24px; opacity: 0.7;"></i>
                    </div>
                    <div class="card-body px-4 pb-4 d-flex flex-column align-items-center">
                        <div class="position-relative mx-auto mb-4" style="width:200px; height:200px;">
                            <canvas id="stockPieChart"></canvas>
                            <div class="position-absolute top-50 start-50 translate-middle text-center">
                                <div class="fw-bold" style="font-size:20px; line-height:1.1; color:#1e293b;"><?= number_format($chart_total) ?></div>
                                <div class="text-muted fw-500" style="font-size:12px;">total birds</div>
                            </div>
                        </div>
                        <!-- Legend -->
                        <div class="w-100 d-flex flex-column gap-2 mt-2" style="max-height: 160px; overflow-y: auto;">
                            <?php
                            $colors = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#14b8a6'];
                            foreach ($chart_flocks as $idx => $label):
                                $color = $colors[$idx % count($colors)];
                                $count = $chart_counts[$idx];
                            ?>
                                <div class="d-flex justify-content-between align-items-center px-2">
                                    <span class="d-flex align-items-center gap-2" style="font-size:13px; color:#475569; font-weight:500;">
                                        <span class="rounded-circle d-inline-block" style="width:10px;height:10px;background:<?= $color ?>;"></span><?= htmlspecialchars($label) ?>
                                    </span>
                                    <span class="fw-semibold" style="font-size:13px; color:#1e293b;"><?= number_format($count) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Commercial Production Records Table -->
            <div class="col-xl-7">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
                    <div class="card-header bg-white border-0 pt-3 pb-2 px-4 d-flex justify-content-between align-items-center">
                        <h4 class="mb-0 fw-semibold text-dark" style="font-weight: bold !important; letter-spacing: .01em;">Recent Hatchery Operations</h4>
                        <small class="text-muted">Last 5 active batches</small>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead>
                                <tr style="background:#f8fafc;">
                                    <th class="px-4 py-3 text-uppercase fw-semibold" style="font-size:11px; color:#94a3b8; letter-spacing:.05em;">Farm Name</th>
                                    <th class="py-3 text-uppercase fw-semibold" style="font-size:11px; color:#94a3b8; letter-spacing:.05em;">Batch Date</th>
                                    <th class="py-3 text-uppercase fw-semibold" style="font-size:11px; color:#94a3b8; letter-spacing:.05em;">Hatchable</th>
                                    <th class="py-3 text-uppercase fw-semibold" style="font-size:11px; color:#94a3b8; letter-spacing:.05em;">Chicks Born</th>
                                    <th class="py-3 text-uppercase fw-semibold" style="font-size:11px; color:#94a3b8; letter-spacing:.05em;">Rate %</th>
                                    <th class="py-3 text-uppercase fw-semibold" style="font-size:11px; color:#94a3b8; letter-spacing:.05em;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($batches)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No hatchery batch records found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($batches as $bt):
                                        $rate = 0.0;
                                        if (!is_null($bt['chicks_hatched']) && $bt['hatchable_count'] > 0) {
                                            $rate = round(($bt['chicks_hatched'] / $bt['hatchable_count']) * 100, 1);
                                        }
                                    ?>
                                        <tr style="border-top: 1px solid #f1f5f9;">
                                            <td class="px-4 py-3 fw-medium" style="font-size:13px;"><?= htmlspecialchars($bt['farm_name']) ?></td>
                                            <td style="font-size:12px; color:#64748b;"><?= htmlspecialchars($bt['batch_date']) ?></td>
                                            <td class="fw-bold" style="font-size:13px;"><?= number_format($bt['hatchable_count']) ?></td>
                                            <td style="font-size:13px;"><?= is_null($bt['chicks_hatched']) ? '-' : number_format($bt['chicks_hatched']) ?></td>
                                            <td class="fw-bold"><?= is_null($bt['chicks_hatched']) ? '-' : $rate . '%' ?></td>
                                            <td>
                                                <?php if (is_null($bt['chicks_hatched'])): ?>
                                                    <span class="d-inline-flex align-items-center gap-1 px-3 py-1 rounded-pill" style="background:#fef9c3; color:#a16207; font-size:11px; font-weight:500;">
                                                        <span class="rounded-circle" style="width:5px;height:5px;background:currentColor;display:inline-block;"></span>Incubating
                                                    </span>
                                                <?php else: ?>
                                                    <span class="d-inline-flex align-items-center gap-1 px-3 py-1 rounded-pill" style="background:#dcfce7; color:#15803d; font-size:11px; font-weight:500;">
                                                        <span class="rounded-circle" style="width:5px;height:5px;background:currentColor;display:inline-block;"></span>Completed
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- 3. Sales Outlet Stock Levels -->
            <div class="col-xl-5">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
                    <div class="card-header bg-white border-0 pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0 fw-semibold text-dark" style="font-weight: bold !important; letter-spacing: .01em;">Egg Sales Breakdown</h4>
                            <small class="text-muted">Outlet performance by category</small>
                        </div>
                        <i class="bi bi-shop text-primary" style="font-size: 24px; opacity: 0.7;"></i>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <?php if (empty($outlets)): ?>
                            <p class="text-muted text-center py-4">No sales records logged.</p>
                        <?php else: ?>
                            <?php
                            $colors_map = ['Table' => '#3b82f6', 'Cracked' => '#ef4444'];
                            $labels_map = ['Table' => 'Table Eggs', 'Cracked' => 'Cracked / Commercial'];

                            // Find max revenue for percentages
                            $max_rev = 0.01;
                            foreach ($outlets as $o) {
                                if ((float)$o['total_revenue'] > $max_rev) {
                                    $max_rev = (float)$o['total_revenue'];
                                }
                            }

                            foreach ($outlets as $o):
                                $pct = round(((float)$o['total_revenue'] / $max_rev) * 100);
                                $color = $colors_map[$o['egg_category']] ?? '#8b5cf6';
                                $label = $labels_map[$o['egg_category']] ?? $o['egg_category'];
                            ?>
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-baseline mb-2">
                                        <span class="fw-semibold" style="font-size:13px; color:#1e293b;"><?= htmlspecialchars($label) ?></span>
                                        <span style="font-size:13px; font-family:monospace; color:#64748b; font-weight:500;">LKR <?= number_format($o['total_revenue'], 2) ?></span>
                                    </div>
                                    <div class="rounded-pill overflow-hidden" style="height:8px; background:#f1f5f9;">
                                        <div class="rounded-pill" style="height:100%; width:<?= $pct ?>%; background:<?= $color ?>;"></div>
                                    </div>
                                    <div style="font-size:12px; color:#94a3b8; margin-top:4px;"><?= number_format($o['qty']) ?> items sold</div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 4. Revenue Generation Chart -->
            <div class="col-xl-7">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
                    <div class="card-header bg-white border-0 pt-4 pb-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h4 class="mb-0 fw-semibold text-dark" style="font-weight: bold !important; letter-spacing: .01em;">Sales Revenue Trend</h4>
                            <small class="text-muted">Monthly (LKR Thousands)</small>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <canvas id="revenueLineChart" height="130"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    // 1. Stock Categorization Donut Chart
    new Chart(document.getElementById('stockPieChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($chart_flocks) ?>,
            datasets: [{
                data: <?= json_encode($chart_counts) ?>,
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#14b8a6'],
                borderColor: '#ffffff',
                borderWidth: 3,
                cutout: '65%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 13,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 12
                    },
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed + ' birds';
                        }
                    }
                }
            }
        }
    });

    // 2. Revenue Line Chart
    new Chart(document.getElementById('revenueLineChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_months) ?>,
            datasets: [{
                label: 'Revenue (LKR k)',
                data: <?= json_encode($chart_revenues) ?>,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.08)',
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: {
                            size: 12
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 12
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 13,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 12
                    }
                }
            }
        }
    });
</script>