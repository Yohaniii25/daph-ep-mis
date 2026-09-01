<?php
session_start();
require_once '../../../config/db_connect.php';

// 1. Session and Role Guard
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

if (!isset($_SESSION['full_name'])) {
    $_SESSION['full_name'] = $_SESSION['username'] ?? 'Veterinary Surgeon';
}

$full_name   = $_SESSION['full_name'];
$range_id    = $_SESSION['range_id'] ?? null;
$district_id = $_SESSION['district_id'] ?? null;

if (empty($range_id)) {
    die('<div class="alert alert-danger text-center p-5 m-5">Error: Your account is not assigned to any Veterinary Range.</div>');
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
                            <i class="bi bi-people-fill fs-3"></i><br>
                            Vaccination Targets
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="production_activities.php" class="btn w-100 py-3" style="background-color: #370709; color: #fff; border-color: #370709;">
                            <i class="bi bi-building-fill fs-3"></i><br>
                            Production Activities Plan
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="strategic_indicators.php" class="btn w-100 py-3" style="background-color: #b08723; color: #fff; border-color: #b08723;">
                            <i class="bi bi-car-front-fill fs-3"></i><br>
                            Strategic Indicators
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

                        <div class="row g-4 mb-4">
                            <div class="col-12 col-lg-5 d-flex justify-content-center align-items-center position-relative">
                                <div style="position: relative; width: 100%; max-width: 320px; height: 320px;">
                                    <canvas id="productionActivityPieChart"></canvas>
                                </div>
                            </div>
                            <div class="col-12 col-lg-7">
                                <div class="table-responsive">
                                    <table id="productionActivityTable" class="table table-striped table-hover table-bordered align-middle w-100 m-0">
                                        <thead class="table-light text-secondary small">
                                            <tr>
                                                <th>Activity Name</th>
                                                <th>Category</th>
                                                <th class="text-right">Target Quantity</th>
                                                <th class="text-right">Achieved Quantity</th>
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
            </div>
        </div>

    </main>
</div>

<?php include 'models/asset_modals.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    $(document).ready(function() {
        let productionActivityTableInstance = null;
        let productionPieChartInstance = null;

        // Register Center Text Plugin Layout Rules for Chart.js
        if (typeof Chart !== 'undefined' && !Chart.registry.plugins.get('centerTotalText')) {
            const centerTotalTextPlugin = {
                id: 'centerTotalText',
                afterDraw: function(chart) {
                    if (chart.config.options.plugins.centerTotalText) {
                        const ctx = chart.ctx;
                        const chartArea = chart.chartArea;
                        const configOptions = chart.config.options.plugins.centerTotalText;

                        ctx.save();
                        ctx.font = "bold 11px system-ui, sans-serif";
                        ctx.fillStyle = "#64748b";
                        ctx.textAlign = "center";
                        ctx.textBaseline = "middle";
                        const centerX = (chartArea.left + chartArea.right) / 2;
                        const centerY = (chartArea.top + chartArea.bottom) / 2;
                        ctx.fillText(configOptions.text.toUpperCase(), centerX, centerY - 10);

                        ctx.font = "bold 20px system-ui, sans-serif";
                        ctx.fillStyle = "#370709"; // Maroon Accent Indicator Text
                        ctx.fillText(configOptions.value.toLocaleString(), centerX, centerY + 12);
                        ctx.restore();
                    }
                }
            };
            Chart.register(centerTotalTextPlugin);
        }

        function fetchProductionTargetsData() {
            const targetYear = $('#filterYearProduction').val();
            const targetCategory = $('#filterCategoryProduction').val();

            const urlParams = new URLSearchParams({
                year: targetYear,
                animal_category: targetCategory
            });

            fetch(`get_production_activity_targets.php?${urlParams.toString()}`)
                .then(response => response.json())
                .then(data => {
                    let runningTotalSum = 0;
                    data.forEach(item => {
                        runningTotalSum += item.target_quantity;
                    });

                    const processedTableRows = data.map(item => [
                        item.activity_name,
                        `<span class="badge text-dark" style="background-color: #d4c7b7;">${item.animal_category}</span>`,
                        item.target_quantity.toLocaleString(),
                        item.achieved_quantity.toLocaleString()
                    ]);

                    if (productionActivityTableInstance) {
                        productionActivityTableInstance.clear().rows.add(processedTableRows).draw();
                    } else {
                        productionActivityTableInstance = $('#productionActivityTable').DataTable({
                            data: processedTableRows,
                            responsive: true,
                            pageLength: 5,
                            lengthChange: false,
                            ordering: false,
                            language: {
                                search: "_INPUT_",
                                searchPlaceholder: "Search records..."
                            }
                        });
                    }

                    const chartLabels = data.map(item => item.activity_name);
                    const chartValues = data.map(item => item.target_quantity);

                    if (productionPieChartInstance) {
                        productionPieChartInstance.data.labels = chartLabels;
                        productionPieChartInstance.data.datasets[0].data = chartValues;
                        productionPieChartInstance.options.plugins.centerTotalText.text = targetCategory + ' Targets';
                        productionPieChartInstance.options.plugins.centerTotalText.value = runningTotalSum;
                        productionPieChartInstance.update();
                    } else {
                        const ctxCanvas = document.getElementById('productionActivityPieChart').getContext('2d');
                        productionPieChartInstance = new Chart(ctxCanvas, {
                            type: 'doughnut',
                            data: {
                                labels: chartLabels,
                                datasets: [{
                                    data: chartValues,
                                    backgroundColor: ['#370709', '#820100', '#a07174', '#94a3b8', '#f59e0b', '#10b981', '#1e3a8a', '#10b981'],
                                    borderWidth: 2,
                                    borderColor: '#ffffff'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '70%',
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            boxWidth: 12
                                        }
                                    },
                                    centerTotalText: {
                                        text: targetCategory + ' Targets',
                                        value: runningTotalSum
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