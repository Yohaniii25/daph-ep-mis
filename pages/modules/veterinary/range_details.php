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
<link rel="stylesheet" href="../../../assets/css/veterinary.css">



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

            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Quick Actions</h6>
                </div>
                <div class="card-body pt-0">
                    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-3">

                        <div class="col">
                            <a href="range_statistics.php" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #820100; min-height: 105px;">
                                <i class="bi bi-graph-up fs-3 mb-1"></i>
                                <span class="text-center">Range Statistics</span>
                            </a>
                        </div>
                        <div class="col">
                            <a href="annual_targets.php" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #370709; min-height: 105px;">
                                <i class="bi bi-bar-chart fs-3 mb-1"></i>
                                <span class="text-center">Annual Targets</span>
                            </a>
                        </div>
                        <div class="col">
                            <a href="vehicles.php" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #b08723; min-height: 105px;">
                                <i class="bi bi-car-front-fill fs-3 mb-1"></i>
                                <span class="text-center">Monthly/Annual Reports</span>
                            </a>
                        </div>
                        <div class="col">
                            <a href="furniture.php" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #a07174; min-height: 105px;">
                                <i class="bi bi-file-earmark-plus fs-3 mb-1"></i>
                                <span class="text-center">Regulatory Functions</span>
                            </a>
                        </div>
                        <div class="col">
                            <a href="machineries.php" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #689ccf; min-height: 105px;">
                                <i class="bi bi-gear-fill fs-3 mb-1"></i>
                                <span class="text-center">Animal Health</span>
                            </a>
                        </div>

                        <div class="col">
                            <a href="instruments.php" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #2e7d32; min-height: 105px;">
                                <i class="bi bi-tools fs-3 mb-1"></i>
                                <span class="text-center">Clinical Services</span>
                            </a>
                        </div>
                        <div class="col">
                            <a href="counter_foilage.php" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #e65100; min-height: 105px;">
                                <i class="bi bi-file-earmark-text-fill fs-3 mb-1"></i>
                                <span class="text-center">Animal Breeding</span>
                            </a>
                        </div>
                        <div class="col">
                            <a href="human_population.php" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #455a64; min-height: 105px;">
                                <i class="bi bi-person-bounding-box fs-3 mb-1"></i>
                                <span class="text-center">Livestock Production</span>
                            </a>
                        </div>
                        <div class="col">
                            <a href="animal_population.php" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #1565c0; min-height: 105px;">
                                <i class="bi bi-patch-check-fill fs-3 mb-1"></i>
                                <span class="text-center">Dairy Hub</span>
                            </a>
                        </div>
                        <div class="col">
                            <a href="range_maps.php" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #00838f; min-height: 105px;">
                                <i class="bi bi-geo-alt-fill fs-3 mb-1"></i>
                                <span class="text-center">Projects</span>
                            </a>
                        </div>

                        <div class="col">
                            <a href="#" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #283593; min-height: 105px;">
                                <i class="bi bi-folder-fill fs-3 mb-1"></i>
                                <span class="text-center">Monitoring</span>
                            </a>
                        </div>
                        <div class="col">
                            <a href="#" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #ad1457; min-height: 105px;">
                                <i class="bi bi-bookmark-dash-fill fs-3 mb-1"></i>
                                <span class="text-center">Accounts</span>
                            </a>
                        </div>
                        <div class="col">
                            <a href="#" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #d84315; min-height: 105px;">
                                <i class="bi bi-graph-up-arrow fs-3 mb-1"></i>
                                <span class="text-center">Clean Sri Lanka</span>
                            </a>
                        </div>
                        <div class="col">
                            <a href="#" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #37474f; min-height: 105px;">
                                <i class="bi bi-sliders fs-3 mb-1"></i>
                                <span class="text-center">Trainings</span>
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<script src="../../../assets/js/veterinary.js"></script>
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