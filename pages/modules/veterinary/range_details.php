<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../index.php");
    exit();
}

// Extract base operational keys from the live user session wrapper
$user_id = $_SESSION['user_id'] ?? null;
$range_id = $_SESSION['range_id'] ?? null;

$range_name = 'Your Range';
$district_name = 'Your District';
$iframe_url = '';

// Step 1: Query the user's data profile if it's missing from the active session context
if (empty($range_id) && !empty($user_id)) {
    $user_query = $mysqli->prepare("SELECT range_id FROM users WHERE id = ?");
    if ($user_query) {
        $user_query->bind_param("i", $user_id);
        $user_query->execute();
        $user_result = $user_query->get_result();
        if ($row = $user_result->fetch_assoc()) {
            $_SESSION['range_id'] = $row['range_id'];
            $range_id = $row['range_id'];
        }
        $user_query->close();
    }
}

// Step 2: Extract Range Name, District Name, and Map URL using a clean, relational JOIN
if (!empty($range_id)) {
    $details_sql = "
        SELECT 
            vr.name AS range_name,
            d.name AS district_name,
            vrm.iframe_url
        FROM veterinary_ranges vr
        LEFT JOIN districts d ON vr.district_id = d.id
        LEFT JOIN veterinary_range_maps vrm ON vr.id = vrm.range_id
        WHERE vr.id = ?
    ";

    $details_query = $mysqli->prepare($details_sql);
    if ($details_query) {
        $details_query->bind_param("i", $range_id);
        $details_query->execute();
        $details_result = $details_query->get_result();
        if ($data = $details_result->fetch_assoc()) {
            $range_name = $data['range_name'] ?? 'Your Assigned Range';
            $district_name = $data['district_name'] ?? 'Your District';
            $iframe_url = $data['iframe_url'] ?? '';
        }
        $details_query->close();
    }
}

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

<style>
    .gov-card {
        border: none !important;
        border-radius: 12px !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03) !important;
    }

    .map-frame-wrapper {
        position: relative;
        width: 100%;
        height: 420px;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #cbd5e1;
    }

    .map-frame-wrapper iframe {
        width: 100%;
        height: 100%;
        border: 0;
    }

    .table-profile th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 600;
        width: 35%;
    }

    #ethnicityDropdownBtn {
        cursor: pointer;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    #ethnicityDropdownBtn::after {
        content: "";
        display: inline-block;
        float: right;
        margin-top: 7px;
        border-top: 5px solid #64748b;
        border-right: 5px solid transparent;
        border-left: 5px solid transparent;
    }

    .ethnicity-dropdown-menu {
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 1050;
        display: none;
        min-width: 220px;
        margin-top: 4px;
        padding: 8px;
        background: #fff;
        border: 1px solid #d0d7de;
        border-radius: 8px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        max-height: 220px;
        overflow-y: auto;
    }

    .ethnicity-dropdown-menu.show {
        display: block;
    }

    .animal-dropdown-menu {
        display: none;
        top: 100%;
        left: 0;
        margin-top: 4px;
        z-index: 1050;
        width: 100%;
        max-height: 250px;
        overflow-y: auto;
    }

    .animal-dropdown-menu.show {
        display: block;
    }
</style>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">

        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">Range Details</h2>
                <p class="text-muted small mb-0">Official mapping profile metrics dynamically captured for <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong></p>
            </div>
            <?php if (isset($_SESSION['msg'])): ?>
                <div class="alert alert-<?= $_SESSION['msg_type'] ?> py-2 px-3 mb-0 small">
                    <?= $_SESSION['msg'] ?>
                </div>
                <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-4">
                <div class="card gov-card h-100">
                    <div class="card-header bg-white pt-4 px-4 border-0">
                        <h5 class="fw-bold mb-1" style="color: #370709;"><i class="bi bi-geo-fill me-2"></i>Range Profile</h5>
                        <p class="text-muted small mb-0">Operational indicators assigned to your profile identity.</p>
                    </div>
                    <div class="card-body px-4">
                        <div class="table-responsive">
                            <table class="table table-bordered table-profile align-middle m-0">
                                <tbody>
                                    <tr>
                                        <th>Range ID</th>
                                        <td class="font-monospace fw-bold text-secondary"><?= htmlspecialchars($range_id ?? '-') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Range Name</th>
                                        <td class="fw-bold" style="color: #370709;"><?= htmlspecialchars($range_name) ?></td>
                                    </tr>
                                    <tr>
                                        <th>District</th>
                                        <td><?= htmlspecialchars($district_name) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-8">
                <div class="card gov-card h-100">
                    <div class="card-header bg-white pt-4 px-4 border-0">
                        <h5 class="fw-bold mb-1" style="color: #370709;"><i class="bi bi-map-fill me-2"></i>Range Map View</h5>
                        <p class="text-muted small mb-0">Live interactive coordinate reference tracking viewport.</p>
                    </div>
                    <div class="card-body px-4 pb-4 pt-1">

                        <div class="map-frame-wrapper shadow-sm">
                            <?php if (!empty($iframe_url) && filter_var($iframe_url, FILTER_VALIDATE_URL)): ?>

                                <iframe
                                    src="<?= htmlspecialchars($iframe_url, ENT_QUOTES, 'UTF-8') ?>"
                                    width="100%"
                                    height="420"
                                    style="border:0;"
                                    allowfullscreen=""
                                    loading="lazy">
                                </iframe>

                            <?php else: ?>
                                <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center bg-light text-muted" style="min-height: 420px;">
                                    <i class="bi bi-exclamation-triangle mb-2 h3 text-warning"></i>
                                    <span class="small fw-semibold">No valid map URL configured for this range.</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-5 mt-2">
                <div class="col-12">
                    <div class="card gov-card">
                        <div class="card-header bg-white pt-4 px-4 border-0">
                            <h5 class="fw-bold mb-1" style="color: #370709;"><i class="bi bi-people-fill me-2"></i>Human Population</h5>
                            <p class="text-muted small mb-0">Demographic composition tracking and sector breakdown analytics from database.</p>
                        </div>
                        <div class="card-body px-4 pb-4">

                            <div class="row g-3 mb-4 p-3 rounded text-dark" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-bold text-secondary">Year Selection</label>
                                    <select id="filterYear" class="form-select form-select-sm filter-control">
                                        <option value="2026">2026</option>
                                        <option value="2025" selected>2025</option>
                                        <option value="2024">2024</option>
                                        <option value="2023">2023</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-bold text-secondary">Ethnicity Focus</label>
                                    <div class="position-relative" id="ethnicityDropdownWrapper">
                                        <button type="button"
                                            class="form-select form-select-sm text-start filter-control"
                                            id="ethnicityDropdownBtn"
                                            aria-expanded="false">
                                            All Ethnicities
                                        </button>
                                        <div class="ethnicity-dropdown-menu" id="ethnicityDropdownMenu">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="All" id="ethAll" checked>
                                                <label class="form-check-label small fw-bold" for="ethAll">All</label>
                                            </div>
                                            <hr class="dropdown-divider my-1">
                                            <div class="form-check">
                                                <input class="form-check-input ethnicity-option" type="checkbox" value="Sinhala" id="ethSinhala" checked>
                                                <label class="form-check-label small" for="ethSinhala">Sinhala</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input ethnicity-option" type="checkbox" value="Tamil" id="ethTamil" checked>
                                                <label class="form-check-label small" for="ethTamil">Tamil</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input ethnicity-option" type="checkbox" value="Muslim" id="ethMuslim" checked>
                                                <label class="form-check-label small" for="ethMuslim">Muslim</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-bold text-secondary">Population Type Metric</label>
                                    <select id="filterPopType" class="form-select form-select-sm filter-control">
                                        <option value="Total Population" selected>Total Population (Male + Female)</option>
                                        <option value="Male">Male Only</option>
                                        <option value="Female">Female Only</option>
                                        <option value="Households">Households Count</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-12 col-lg-5 d-flex justify-content-center align-items-center position-relative">
                                    <div style="position: relative; width: 100%; max-width: 320px; height: 320px;">
                                        <canvas id="humanPopulationPieChart"></canvas>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-7">
                                    <div class="table-responsive">
                                        <table id="humanPopulationTable" class="table table-striped table-hover table-bordered align-middle w-100 m-0">
                                            <thead class="table-light text-secondary small">
                                                <tr>
                                                    <th>Year</th>
                                                    <th>Ethnicity</th>
                                                    <th>Population Split</th>
                                                    <th>Total Population Group</th>
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

            <!-- SECTION 2 – ANIMAL POPULATION DASHBOARD MATRIX LAYER -->
            <div class="row g-4 mb-5 mt-2">
                <div class="col-12">
                    <div class="card gov-card">
                        <div class="card-header bg-white pt-4 px-4 border-0">
                            <h5 class="fw-bold mb-1" style="color: #370709;"><i class="bi bi-bug-fill me-2"></i>Animal Population</h5>
                            <p class="text-muted small mb-0">Livestock demographics composition tracking and sector breakdown analytics from database.</p>
                        </div>
                        <div class="card-body px-4 pb-4">

                            <!-- Dynamic Filters Control Toolbar Row Layout -->
                            <div class="row g-3 mb-4 p-3 rounded text-dark" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-bold text-secondary">Year Selection</label>
                                    <select id="filterYearAnimal" class="form-select form-select-sm filter-control-animal">
                                        <option value="2026">2026</option>
                                        <option value="2025" selected>2025</option>
                                        <option value="2024">2024</option>
                                        <option value="2023">2023</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-bold text-secondary">Livestock Category Focus</label>
                                    <div class="position-relative" id="animalDropdownWrapper">
                                        <button type="button"
                                            class="form-select form-select-sm text-start bg-white"
                                            id="animalDropdownBtn"
                                            aria-expanded="false">
                                            All Animals Selected (6)
                                        </button>
                                        <div class="animal-dropdown-menu p-3 border shadow-sm bg-white rounded position-absolute" id="animalDropdownMenu">
                                            <div class="form-check mb-1">
                                                <input class="form-check-input" type="checkbox" value="All" id="animAll" checked>
                                                <label class="form-check-label small fw-bold" for="animAll">All</label>
                                            </div>
                                            <hr class="dropdown-divider my-1">
                                            <div class="form-check mb-1">
                                                <input class="form-check-input animal-option" type="checkbox" value="Cow" id="animCow" checked>
                                                <label class="form-check-label small" for="animCow">Cow</label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input animal-option" type="checkbox" value="Buffalo" id="animBuffalo" checked>
                                                <label class="form-check-label small" for="animBuffalo">Buffalo</label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input animal-option" type="checkbox" value="Goat" id="animGoat" checked>
                                                <label class="form-check-label small" for="animGoat">Goat</label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input animal-option" type="checkbox" value="Chicken" id="animChicken" checked>
                                                <label class="form-check-label small" for="animChicken">Chicken</label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input animal-option" type="checkbox" value="Pig" id="animPig" checked>
                                                <label class="form-check-label small" for="animPig">Pig</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input animal-option" type="checkbox" value="Others" id="animOthers" checked>
                                                <label class="form-check-label small" for="animOthers">Others</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- Interactive Visualization Framework Grid Block -->
                            <div class="row g-4 mb-4">
                                <div class="col-12 col-lg-5 d-flex justify-content-center align-items-center position-relative">
                                    <div style="position: relative; width: 100%; max-width: 320px; height: 320px;">
                                        <canvas id="animalPopulationPieChart"></canvas>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-7">
                                    <div class="table-responsive">
                                        <table id="animalPopulationTable" class="table table-striped table-hover table-bordered align-middle w-100 m-0">
                                            <thead class="table-light text-secondary small">
                                                <tr>
                                                    <th>Year</th>
                                                    <th>Animal Type</th>
                                                    <th>Count Split</th>
                                                    <th>Total Selected Group</th>
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
        </div>

    </main>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        let humanDatasetTableInstance = null;
        let animalDatasetTableInstance = null;
        let humanPieChartInstance = null;
        let animalPieChartInstance = null;

        // Register Center Text Plugin Layout Rules for Chart.js
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

        // Returns the currently checked ethnicity values (excludes the "All" master checkbox)
        function getSelectedEthnicities() {
            return Array.from(document.querySelectorAll(".ethnicity-option:checked"))
                .map(checkbox => checkbox.value);
        }

        // Keeps the dropdown button label in sync with what's checked
        function updateEthnicityButtonLabel() {
            const btn = document.getElementById("ethnicityDropdownBtn");
            const selected = getSelectedEthnicities();
            const totalOptions = document.querySelectorAll(".ethnicity-option").length;

            if (selected.length === 0) {
                btn.textContent = "None Selected";
            } else if (selected.length === totalOptions) {
                btn.textContent = "All Ethnicities";
            } else {
                btn.textContent = selected.join(", ");
            }
        }

        // Primary Core Database Fetcher Implementation
        function fetchFilteredPopulationData() {
            const targetYear = document.getElementById("filterYear").value;
            const targetPopType = document.getElementById("filterPopType").value;
            const targetEthnicities = getSelectedEthnicities();

            const urlParams = new URLSearchParams({
                year: targetYear,
                pop_type: targetPopType,
                ethnicities: JSON.stringify(targetEthnicities)
            });

            fetch(`get_population_data.php?${urlParams.toString()}`)
                .then(response => response.json())
                .then(data => {
                    let runningTotalSum = 0;

                    // Calculate runtime column sum
                    data.forEach(item => {
                        runningTotalSum += item.count;
                    });

                    // Restructure values into DataTable rows
                    const processedTableRows = data.map(item => [
                        item.year,
                        item.ethnicity,
                        item.count.toLocaleString(),
                        runningTotalSum.toLocaleString()
                    ]);

                    // Sync data rows seamlessly into your existing DataTables instance
                    if (humanDatasetTableInstance) {
                        humanDatasetTableInstance.clear().rows.add(processedTableRows).draw();
                    } else {
                        humanDatasetTableInstance = $('#humanPopulationTable').DataTable({
                            data: processedTableRows,
                            responsive: true,
                            dom: "<'row mb-2'<'col-sm-12 col-md-6'B><'col-sm-12 col-md-6'f>>" +
                                "<'row'<'col-sm-12'tr>>" +
                                "<'row mt-2'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                            buttons: [{
                                    extend: 'excelHtml5',
                                    className: 'btn btn-sm btn-success',
                                    text: '<i class="bi bi-file-earmark-spreadsheet"></i> CSV'
                                },
                                {
                                    extend: 'print',
                                    className: 'btn btn-sm btn-danger',
                                    text: '<i class="bi bi-printer me-1"></i> Print'
                                },
                                {
                                    extend: 'pdfHtml5',
                                    className: 'btn btn-sm btn-dark',
                                    text: '<i class="bi bi-file-earmark-pdf me-1"></i> PDF'
                                }
                            ],
                            pageLength: 5,
                            lengthChange: false,
                            ordering: false,
                            language: {
                                search: "_INPUT_",
                                searchPlaceholder: "Search records..."
                            }
                        });
                    }

                    // Isolate vectors to map directly onto the Chart labels object tracking matrices
                    const chartLabels = data.map(item => item.ethnicity);
                    const chartValues = data.map(item => item.count);

                    if (humanPieChartInstance) {
                        humanPieChartInstance.data.labels = chartLabels;
                        humanPieChartInstance.data.datasets[0].data = chartValues;
                        humanPieChartInstance.options.plugins.centerTotalText.text = targetPopType;
                        humanPieChartInstance.options.plugins.centerTotalText.value = runningTotalSum;
                        humanPieChartInstance.update();
                    } else {
                        const ctxCanvas = document.getElementById('humanPopulationPieChart').getContext('2d');
                        humanPieChartInstance = new Chart(ctxCanvas, {
                            type: 'doughnut',
                            data: {
                                labels: chartLabels,
                                datasets: [{
                                    data: chartValues,
                                    backgroundColor: ['#370709', '#a07174', '#e2e8f0'],
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
                                        text: targetPopType,
                                        value: runningTotalSum
                                    }
                                }
                            }
                        });
                    }
                })
                .catch(error => console.error('Error fetching dynamic dashboard profiles:', error));
        }

        // Attach simple listener hooks for the filter controls
        document.getElementById("filterYear").addEventListener("change", fetchFilteredPopulationData);
        document.getElementById("filterPopType").addEventListener("change", fetchFilteredPopulationData);

        const ethnicityDropdownBtn = document.getElementById("ethnicityDropdownBtn");
        const ethnicityDropdownMenu = document.getElementById("ethnicityDropdownMenu");
        const ethnicityDropdownWrapper = document.getElementById("ethnicityDropdownWrapper");

        ethnicityDropdownBtn.addEventListener("click", function(event) {
            event.stopPropagation();
            const isOpen = ethnicityDropdownMenu.classList.toggle("show");
            ethnicityDropdownBtn.setAttribute("aria-expanded", isOpen ? "true" : "false");
        });

        ethnicityDropdownMenu.addEventListener("click", function(event) {
            event.stopPropagation();
        });

        document.addEventListener("click", function(event) {
            if (!ethnicityDropdownWrapper.contains(event.target)) {
                ethnicityDropdownMenu.classList.remove("show");
                ethnicityDropdownBtn.setAttribute("aria-expanded", "false");
            }
        });

        // "All" master checkbox toggles every ethnicity option
        document.getElementById("ethAll").addEventListener("change", function() {
            document.querySelectorAll(".ethnicity-option").forEach(cb => cb.checked = this.checked);
            updateEthnicityButtonLabel();
            fetchFilteredPopulationData();
        });

        // Individual ethnicity checkboxes keep "All" in sync and trigger a refetch
        document.querySelectorAll(".ethnicity-option").forEach(function(checkbox) {
            checkbox.addEventListener("change", function() {
                const allChecked = Array.from(document.querySelectorAll(".ethnicity-option")).every(cb => cb.checked);
                document.getElementById("ethAll").checked = allChecked;
                updateEthnicityButtonLabel();
                fetchFilteredPopulationData();
            });
        });

        function getSelectedAnimals() {
            return Array.from(document.querySelectorAll('.animal-option:checked'))
                .map(checkbox => checkbox.value);
        }

        function updateAnimalButtonLabel() {
            const btn = document.getElementById('animalDropdownBtn');
            const selected = getSelectedAnimals();
            const totalOptions = document.querySelectorAll('.animal-option').length;

            if (selected.length === 0) {
                btn.textContent = 'None Selected';
            } else if (selected.length === totalOptions) {
                btn.textContent = 'All Animals Selected (6)';
            } else {
                btn.textContent = selected.join(', ');
            }
        }

        function fetchFilteredAnimalPopulationData() {
            const targetYear = document.getElementById('filterYearAnimal').value;
            const targetAnimals = getSelectedAnimals();

            const urlParams = new URLSearchParams({
                year: targetYear,
                animals: JSON.stringify(targetAnimals)
            });

            fetch(`get_animal_population_data.php?${urlParams.toString()}`)
                .then(response => response.json())
                .then(data => {
                    let runningTotalSum = 0;
                    data.forEach(item => {
                        runningTotalSum += item.count;
                    });

                    const processedTableRows = data.map(item => [
                        item.year,
                        item.animal_type,
                        item.count.toLocaleString(),
                        runningTotalSum.toLocaleString()
                    ]);

                    if (animalDatasetTableInstance) {
                        animalDatasetTableInstance.clear().rows.add(processedTableRows).draw();
                    } else {
                        animalDatasetTableInstance = $('#animalPopulationTable').DataTable({
                            data: processedTableRows,
                            responsive: true,
                            dom: "<'row mb-2'<'col-sm-12 col-md-6'B><'col-sm-12 col-md-6'f>>" +
                                "<'row'<'col-sm-12'tr>>" +
                                "<'row mt-2'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                            buttons: [{
                                    extend: 'excelHtml5',
                                    className: 'btn btn-sm btn-success',
                                    text: '<i class="bi bi-file-earmark-spreadsheet"></i> CSV'
                                },
                                {
                                    extend: 'print',
                                    className: 'btn btn-sm btn-danger',
                                    text: '<i class="bi bi-printer me-1"></i> Print'
                                },
                                {
                                    extend: 'pdfHtml5',
                                    className: 'btn btn-sm btn-dark',
                                    text: '<i class="bi bi-file-earmark-pdf me-1"></i> PDF'
                                }
                            ],
                            pageLength: 5,
                            lengthChange: false,
                            ordering: false,
                            language: {
                                search: '_INPUT_',
                                searchPlaceholder: 'Search records...'
                            }
                        });
                    }

                    const chartLabels = data.map(item => item.animal_type);
                    const chartValues = data.map(item => item.count);

                    if (animalPieChartInstance) {
                        animalPieChartInstance.data.labels = chartLabels;
                        animalPieChartInstance.data.datasets[0].data = chartValues;
                        animalPieChartInstance.options.plugins.centerTotalText.text = 'Total Population';
                        animalPieChartInstance.options.plugins.centerTotalText.value = runningTotalSum;
                        animalPieChartInstance.update();
                    } else {
                        const ctxCanvas = document.getElementById('animalPopulationPieChart').getContext('2d');
                        animalPieChartInstance = new Chart(ctxCanvas, {
                            type: 'doughnut',
                            data: {
                                labels: chartLabels,
                                datasets: [{
                                    data: chartValues,
                                    backgroundColor: ['#370709', '#a07174', '#e2e8f0', '#94a3b8', '#f59e0b', '#10b981'],
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
                                        text: 'Total Population',
                                        value: runningTotalSum
                                    }
                                }
                            }
                        });
                    }
                })
                .catch(error => console.error('Error fetching animal dashboard profiles:', error));
        }

        document.getElementById('filterYearAnimal').addEventListener('change', fetchFilteredAnimalPopulationData);

        const animalDropdownBtn = document.getElementById('animalDropdownBtn');
        const animalDropdownMenu = document.getElementById('animalDropdownMenu');
        const animalDropdownWrapper = document.getElementById('animalDropdownWrapper');

        animalDropdownBtn.addEventListener('click', function(event) {
            event.stopPropagation();
            const isOpen = animalDropdownMenu.classList.toggle('show');
            animalDropdownBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        animalDropdownMenu.addEventListener('click', function(event) {
            event.stopPropagation();
        });

        document.addEventListener('click', function(event) {
            if (!animalDropdownWrapper.contains(event.target)) {
                animalDropdownMenu.classList.remove('show');
                animalDropdownBtn.setAttribute('aria-expanded', 'false');
            }
        });

        document.getElementById('animAll').addEventListener('change', function() {
            document.querySelectorAll('.animal-option').forEach(cb => cb.checked = this.checked);
            updateAnimalButtonLabel();
            fetchFilteredAnimalPopulationData();
        });

        document.querySelectorAll('.animal-option').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const allChecked = Array.from(document.querySelectorAll('.animal-option')).every(cb => cb.checked);
                document.getElementById('animAll').checked = allChecked;
                updateAnimalButtonLabel();
                fetchFilteredAnimalPopulationData();
            });
        });

        updateAnimalButtonLabel();
        fetchFilteredAnimalPopulationData();
        fetchFilteredPopulationData();
    });
</script>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php require_once '../../../includes/footer.php'; ?>