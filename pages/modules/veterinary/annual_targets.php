<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

// 1. Session and Role Guard
$allowed_roles = [
    'veterinary_surgeon',
    'government_veterinary_surgeon',
    'additional_veterinary_surgeon',
    'deputy_director_hq_1',
    'district_dd',
    'deputy_director_district',
    'provincial_director',
    'admin',
    'super_admin'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    header("Location: ../../../../index.php");
    exit();
}

if (!isset($_SESSION['full_name'])) {
    $_SESSION['full_name'] = $_SESSION['username'] ?? 'Officer';
}

$full_name   = $_SESSION['full_name'];
$range_id    = isset($_GET['range_id']) && intval($_GET['range_id']) > 0 ? intval($_GET['range_id']) : ($_SESSION['range_id'] ?? null);
$district_id = $_SESSION['district_id'] ?? null;

if (empty($range_id)) {
    if ($district_id) {
        $r_stmt = $mysqli->prepare("SELECT id FROM veterinary_ranges WHERE district_id = ? LIMIT 1");
        $r_stmt->bind_param("i", $district_id);
        $r_stmt->execute();
        if ($r_row = $r_stmt->get_result()->fetch_assoc()) {
            $range_id = $r_row['id'];
        }
        $r_stmt->close();
    } else {
        $r_res = $mysqli->query("SELECT id FROM veterinary_ranges LIMIT 1");
        if ($r_res && $r_row = $r_res->fetch_assoc()) {
            $range_id = $r_row['id'];
        }
    }
}

// 2. Fallback Definitions
$district_name = 'Unknown District';
$range_name    = 'Unknown Range';
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : 2026;

// 3. Fetch Core Structural Meta Information
if ($district_id) {
    $stmt = $mysqli->prepare("SELECT name FROM districts WHERE id = ?");
    $stmt->bind_param("i", $district_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $district_name = $row['name'];
    }
    $stmt->close();
}

if ($range_id) {
    $stmt = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
    $stmt->bind_param("i", $range_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $range_name = $row['name'];
    }
    $stmt->close();
}

// 4. Dynamic Data Fetch: Live Lookups against existing animal_populations table
$total_population = 0;
$pop_stmt = $mysqli->prepare("SELECT SUM(quantity) as total FROM animal_populations WHERE range_id = ? AND year = ?");
$pop_stmt->bind_param("ii", $range_id, $selected_year);
$pop_stmt->execute();
$pop_result = $pop_stmt->get_result()->fetch_assoc();
if ($pop_result && $pop_result['total']) {
    $total_population = $pop_result['total'];
}
$pop_stmt->close();

// 5. Fetch Target Data from annual_vaccination_targets
$vax_targets = [
    'id' => null,
    'target_fmd' => 0,
    'target_bq' => 0,
    'target_hs' => 0,
    'available_ldo_count' => 0,
    'allocated_ldo_target' => 0,
    'casual_vaccinators_needed' => 0,
    'allocated_man_days' => 0,
    'syringes_10cc_req' => 0,
    'needles_14g_dozen_req' => 0,
    'fuel_liters_per_month' => 0.00
];

$vax_stmt = $mysqli->prepare("SELECT * FROM annual_vaccination_targets WHERE range_id = ? AND year = ?");
$vax_stmt->bind_param("ii", $range_id, $selected_year);
$vax_stmt->execute();
$vax_res = $vax_stmt->get_result()->fetch_assoc();
if ($vax_res) {
    $vax_targets = $vax_res;
}
$vax_stmt->close();

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">




        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">Annual Targets</h2>
                <p class="text-muted small mb-0">Annual Target Details</p>
            </div>
            <?php if (isset($_SESSION['msg'])): ?>
                <div class="alert alert-<?= $_SESSION['msg_type'] ?> py-2 px-3 mb-0 small">
                    <?= $_SESSION['msg'] ?>
                </div>
                <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
            <?php endif; ?>
        </div>
        <div class="card gov-card mb-4">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="mb-0 fw-bold" style="color: #370709;"><i class="bi bi-lightning-charge-fill me-2"></i>Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="vaccination_targets.php" class="btn w-100 py-3" style="background-color: #820100; color: #fff; border-color: #820100;">
                            <i class="bi bi-shield-check fs-3"></i><br>
                            Vaccination Targets
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="production_activities.php" class="btn w-100 py-3" style="background-color: #370709; color: #fff; border-color: #370709;">
                            <i class="bi bi-calendar-check fs-3"></i><br>
                            Production Activities Plan
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <!-- SECTION – PRODUCTION ACTIVITY TARGETS DATA VISUALIZATION -->
        <div class="row g-4 mb-5">
            <div class="col-12">
                <div class="card gov-card">
                    <div class="card-header bg-white pt-4 px-4 border-0">
                        <h5 class="fw-bold mb-1" style="color: #370709;"><i class="bi bi-bar-chart-fill me-2"></i>Production Activities Targets</h5>
                        <p class="text-muted small mb-0">Production activities target composition tracking and breakdown analytics.</p>
                    </div>
                    <div class="card-body px-4 pb-4">

                        <div class="row g-3 mb-4 p-3 rounded text-dark" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-secondary">Year Selection</label>
                                <select id="filterYearProduction" class="form-select form-select-sm filter-control-production">
                                    <option value="2026" <?= $selected_year == 2026 ? 'selected' : '' ?>>2026</option>
                                    <option value="2025" <?= $selected_year == 2025 ? 'selected' : '' ?>>2025</option>
                                    <option value="2024" <?= $selected_year == 2024 ? 'selected' : '' ?>>2024</option>
                                    <option value="2023" <?= $selected_year == 2023 ? 'selected' : '' ?>>2023</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-secondary">Livestock Category Focus</label>
                                <select id="filterCategoryProduction" class="form-select form-select-sm filter-control-production">
                                    <option value="All" selected>All Categories</option>
                                    <option value="Cow">Cow</option>
                                    <option value="Buffalo">Buffalo</option>
                                    <option value="Goat">Goat</option>
                                    <option value="Chicken">Chicken</option>
                                    <option value="Pig">Pig</option>
                                    <option value="Other">Other / General</option>
                                </select>
                            </div>
                        </div>

                        <!-- 1. Production Activities Bar Graph (Top) -->
                        <div class="mb-4 p-3 bg-white rounded-3 shadow-xs border" style="border-color: #e2e8f0 !important;">
                            <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                                <span class="small fw-bold text-dark text-uppercase tracking-wider">
                                    <i class="bi bi-bar-chart-steps me-1 text-danger"></i> Targets vs Achievements Comparison
                                </span>
                                <span class="badge bg-light text-muted border small" id="chartTotalSummaryBadge">Total Target: 0</span>
                            </div>
                            <div style="position: relative; width: 100%; height: 380px;">
                                <canvas id="productionActivityBarChart"></canvas>
                            </div>
                        </div>

                        <!-- 2. Detailed Data Table (Under Graph) -->
                        <div class="table-responsive">
                            <table id="productionActivityTable" class="table table-striped table-hover table-bordered align-middle w-100 m-0">
                                <thead class="table-light text-secondary small">
                                    <tr>
                                        <th>Activity Name</th>
                                        <th>Category</th>
                                        <th class="text-end">Target Quantity</th>
                                        <th class="text-end">Achieved Quantity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<?php include 'model/asset_modals.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    $(document).ready(function() {
        let productionActivityTableInstance = null;
        let productionBarChartInstance = null;

        function fetchProductionTargetsData() {
            const targetYear = $('#filterYearProduction').val();
            const targetCategory = $('#filterCategoryProduction').val();

            const urlParams = new URLSearchParams({
                year: targetYear,
                animal_category: targetCategory,
                range_id: <?= json_encode($range_id) ?>
            });

            fetch(`get_production_activity_targets.php?${urlParams.toString()}`)
                .then(response => response.json())
                .then(data => {
                    let runningTotalSum = 0;
                    let runningAchievedSum = 0;
                    if (Array.isArray(data)) {
                        data.forEach(item => {
                            runningTotalSum += (item.target_quantity || 0);
                            runningAchievedSum += (item.achieved_quantity || 0);
                        });
                    } else {
                        data = [];
                    }

                    // Update summary badge
                    $('#chartTotalSummaryBadge').text(`Total Target: ${runningTotalSum.toLocaleString()} | Total Achieved: ${runningAchievedSum.toLocaleString()}`);

                    const processedTableRows = data.map(item => [
                        item.activity_name,
                        `<span class="badge text-dark" style="background-color: #d4c7b7;">${item.animal_category}</span>`,
                        `<span class="fw-semibold">${Number(item.target_quantity).toLocaleString()}</span>`,
                        `<span class="fw-semibold text-success">${Number(item.achieved_quantity).toLocaleString()}</span>`
                    ]);

                    if (productionActivityTableInstance) {
                        productionActivityTableInstance.clear().rows.add(processedTableRows).draw();
                    } else {
                        productionActivityTableInstance = $('#productionActivityTable').DataTable({
                            data: processedTableRows,
                            responsive: true,
                            pageLength: 10,
                            lengthChange: false,
                            ordering: false,
                            language: {
                                search: "_INPUT_",
                                searchPlaceholder: "Search activities..."
                            },
                            columnDefs: [
                                { targets: [2, 3], className: 'text-end' }
                            ]
                        });
                    }

                    const chartLabels = data.map(item => item.activity_name);
                    const chartTargets = data.map(item => item.target_quantity);
                    const chartAchieved = data.map(item => item.achieved_quantity);

                    if (productionBarChartInstance) {
                        productionBarChartInstance.data.labels = chartLabels;
                        productionBarChartInstance.data.datasets[0].data = chartTargets;
                        productionBarChartInstance.data.datasets[1].data = chartAchieved;
                        productionBarChartInstance.update();
                    } else {
                        const ctxCanvas = document.getElementById('productionActivityBarChart').getContext('2d');
                        productionBarChartInstance = new Chart(ctxCanvas, {
                            type: 'bar',
                            data: {
                                labels: chartLabels,
                                datasets: [
                                    {
                                        label: 'Target Quantity',
                                        data: chartTargets,
                                        backgroundColor: 'rgba(130, 1, 0, 0.85)',
                                        borderColor: '#820100',
                                        borderWidth: 1.5,
                                        borderRadius: 6,
                                        barPercentage: 0.65,
                                        categoryPercentage: 0.7
                                    },
                                    {
                                        label: 'Achieved Quantity',
                                        data: chartAchieved,
                                        backgroundColor: 'rgba(16, 185, 129, 0.85)',
                                        borderColor: '#10b981',
                                        borderWidth: 1.5,
                                        borderRadius: 6,
                                        barPercentage: 0.65,
                                        categoryPercentage: 0.7
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: {
                                    mode: 'index',
                                    intersect: false
                                },
                                plugins: {
                                    legend: {
                                        position: 'top',
                                        align: 'end',
                                        labels: {
                                            boxWidth: 14,
                                            boxHeight: 14,
                                            font: {
                                                weight: '600',
                                                size: 12
                                            },
                                            color: '#334155'
                                        }
                                    },
                                    tooltip: {
                                        backgroundColor: 'rgba(30, 41, 59, 0.95)',
                                        titleFont: { weight: 'bold', size: 13 },
                                        bodyFont: { size: 12 },
                                        padding: 10,
                                        cornerRadius: 8,
                                        callbacks: {
                                            label: function(context) {
                                                return context.dataset.label + ': ' + Number(context.raw).toLocaleString();
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        grid: {
                                            display: false
                                        },
                                        ticks: {
                                            font: {
                                                weight: '600',
                                                size: 11
                                            },
                                            color: '#475569',
                                            maxRotation: 35,
                                            minRotation: 0
                                        }
                                    },
                                    y: {
                                        beginAtZero: true,
                                        grid: {
                                            color: '#f1f5f9'
                                        },
                                        ticks: {
                                            font: {
                                                size: 11
                                            },
                                            color: '#64748b',
                                            callback: function(value) {
                                                return Number(value).toLocaleString();
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                })
                .catch(error => console.error('Error fetching production activity targets:', error));
        }

        $('#filterYearProduction').change(fetchProductionTargetsData);
        $('#filterCategoryProduction').change(fetchProductionTargetsData);

        fetchProductionTargetsData();
    });
</script>

<?php
require_once '../../../includes/footer.php';
?>