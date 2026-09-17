<?php
session_start();

$allowed_roles = [
    'veterinary_surgeon'

];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    header("Location: ../../../index.php");
    exit();
}

if (!isset($_SESSION['full_name'])) {
    $_SESSION['full_name'] = $_SESSION['username'] ?? 'Veterinary Surgeon';
}

$full_name   = $_SESSION['full_name'];
$requested_range_id = isset($_GET['range_id']) && is_numeric($_GET['range_id']) ? (int)$_GET['range_id'] : null;
$range_id    = $requested_range_id ?: ($_SESSION['range_id'] ?? null);
$district_id = $_SESSION['district_id'] ?? null;

if (empty($range_id)) {
    die('<div class="alert alert-danger text-center p-5 m-5">Error: No Veterinary Range specified or assigned.</div>');
}

require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$district_name = 'Unknown District';
$range_name    = 'Unknown Range';

// Fetch District and Range Names
if ($range_id) {
    $stmt = $mysqli->prepare("SELECT vr.name AS range_name, vr.district_id, d.name AS district_name 
                             FROM veterinary_ranges vr 
                             LEFT JOIN districts d ON vr.district_id = d.id 
                             WHERE vr.id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $range_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $range_name = $row['range_name'];
            if (!empty($row['district_name'])) {
                $district_name = $row['district_name'];
            }
        }
        $stmt->close();
    }
}

// Fetch distinct recorded years for human population
$pop_years = [2026, 2025, 2024, 2023];
if ($range_id) {
    $stmt_yr = $mysqli->prepare("SELECT DISTINCT year FROM human_populations WHERE range_id = ? ORDER BY year DESC");
    if ($stmt_yr) {
        $stmt_yr->bind_param("i", $range_id);
        $stmt_yr->execute();
        $yr_res = $stmt_yr->get_result();
        while ($yrow = $yr_res->fetch_assoc()) {
            $pop_years[] = intval($yrow['year']);
        }
        $stmt_yr->close();
    }
}
$pop_years = array_unique($pop_years);
rsort($pop_years);

// Fetch distinct recorded years for animal population
$animal_pop_years = [2026, 2025, 2024, 2023];
if ($range_id) {
    $stmt_ayr = $mysqli->prepare("SELECT DISTINCT year FROM animal_populations WHERE range_id = ? ORDER BY year DESC");
    if ($stmt_ayr) {
        $stmt_ayr->bind_param("i", $range_id);
        $stmt_ayr->execute();
        $ayr_res = $stmt_ayr->get_result();
        while ($ayrow = $ayr_res->fetch_assoc()) {
            $animal_pop_years[] = intval($ayrow['year']);
        }
        $stmt_ayr->close();
    }
}
// ==========================================
// AUTOMATED SUMMARY CALCULATOR & AGGREGATOR
// ==========================================
$prod_summary_year = isset($_GET['prod_year']) ? ($_GET['prod_year'] === 'all' ? 'all' : intval($_GET['prod_year'])) : intval(date('Y'));

// 1. Milk Collection Aggregator
$milk_summary = ['centers' => 0, 'cow_milk' => 0, 'buffalo_milk' => 0, 'goat_milk' => 0, 'total_milk' => 0, 'chilling_cap' => 0];
$milk_query = "
    SELECT 
        COUNT(*) as centers,
        COALESCE(SUM(cow_milk_lit_month), 0) as cow_milk,
        COALESCE(SUM(buffalo_milk_lit_month), 0) as buffalo_milk,
        COALESCE(SUM(goat_milk_lit_month), 0) as goat_milk,
        COALESCE(SUM(milk_collection_lit_per_month), 0) as total_milk,
        COALESCE(SUM(milk_chilling_capacity), 0) as chilling_cap
    FROM milk_collecting_centers
    WHERE (range_id = ? OR vs_range = ?) " . ($prod_summary_year !== 'all' ? "AND report_year = ?" : "");
$stmt_milk = $mysqli->prepare($milk_query);
if ($stmt_milk) {
    if ($prod_summary_year !== 'all') {
        $stmt_milk->bind_param("isi", $range_id, $range_name, $prod_summary_year);
    } else {
        $stmt_milk->bind_param("is", $range_id, $range_name);
    }
    $stmt_milk->execute();
    $res_milk = $stmt_milk->get_result();
    if ($row_milk = $res_milk->fetch_assoc()) {
        $milk_summary = $row_milk;
    }
    $stmt_milk->close();
}

// 2. Feed Production Mills Aggregator
$feed_summary = ['mills' => 0, 'total_mt' => 0, 'categories' => []];
$feed_query = "
    SELECT 
        COUNT(DISTINCT feed_mill_name) as mills,
        COALESCE(SUM(produced_qty_mt_month), 0) as total_mt
    FROM annual_feed_production
    WHERE (range_id = ?) " . ($prod_summary_year !== 'all' ? "AND report_year = ?" : "");
$stmt_feed = $mysqli->prepare($feed_query);
if ($stmt_feed) {
    if ($prod_summary_year !== 'all') {
        $stmt_feed->bind_param("ii", $range_id, $prod_summary_year);
    } else {
        $stmt_feed->bind_param("i", $range_id);
    }
    $stmt_feed->execute();
    $res_feed = $stmt_feed->get_result();
    if ($row_feed = $res_feed->fetch_assoc()) {
        $feed_summary['mills'] = $row_feed['mills'];
        $feed_summary['total_mt'] = $row_feed['total_mt'];
    }
    $stmt_feed->close();
}

// Feed Categories
$feed_cat_query = "
    SELECT category_type, COALESCE(SUM(produced_qty_mt_month), 0) as cat_mt
    FROM annual_feed_production
    WHERE (range_id = ?) " . ($prod_summary_year !== 'all' ? "AND report_year = ?" : "") . "
    GROUP BY category_type";
$stmt_fcat = $mysqli->prepare($feed_cat_query);
if ($stmt_fcat) {
    if ($prod_summary_year !== 'all') {
        $stmt_fcat->bind_param("ii", $range_id, $prod_summary_year);
    } else {
        $stmt_fcat->bind_param("i", $range_id);
    }
    $stmt_fcat->execute();
    $res_fcat = $stmt_fcat->get_result();
    while ($rf = $res_fcat->fetch_assoc()) {
        $feed_summary['categories'][$rf['category_type']] = floatval($rf['cat_mt']);
    }
    $stmt_fcat->close();
}

// 3. Production Outlets (Producers & Processors) Aggregator
$outlets_summary = ['chicks' => 0, 'live_chicken_kg' => 0, 'dressed_chicken_kg' => 0, 'organic_fert_mt' => 0, 'organic_families' => 0];
$outlets_query = "
    SELECT 
        COALESCE(SUM(chicks_produced_month), 0) as chicks,
        COALESCE(SUM(chicken_sale_live_kg_month), 0) as live_chicken_kg,
        COALESCE(SUM(chicken_sale_dressed_kg_month), 0) as dressed_chicken_kg,
        COALESCE(SUM(organic_fert_prod_mt_year), 0) as organic_fert_mt,
        COALESCE(SUM(organic_fert_farm_families), 0) as organic_families
    FROM annual_producers_processors
    WHERE (range_id = ?) " . ($prod_summary_year !== 'all' ? "AND report_year = ?" : "");
$stmt_out = $mysqli->prepare($outlets_query);
if ($stmt_out) {
    if ($prod_summary_year !== 'all') {
        $stmt_out->bind_param("ii", $range_id, $prod_summary_year);
    } else {
        $stmt_out->bind_param("i", $range_id);
    }
    $stmt_out->execute();
    $res_out = $stmt_out->get_result();
    if ($row_out = $res_out->fetch_assoc()) {
        $outlets_summary = $row_out;
    }
    $stmt_out->close();
}

// 4. Meat Sales Aggregator
$meat_summary = ['total_kg' => 0, 'total_revenue' => 0, 'categories' => [], 'others_detail' => []];
$meat_query = "
    SELECT 
        meat_type,
        other_meat_name,
        COALESCE(SUM(sales_volume_kg), 0) as total_kg,
        COALESCE(SUM(total_sales_amount), 0) as total_revenue
    FROM meat_sales_records
    WHERE (range_id = ? OR vs_range = ?) " . ($prod_summary_year !== 'all' ? "AND report_year = ?" : "") . "
    GROUP BY meat_type, other_meat_name";
$stmt_meat = $mysqli->prepare($meat_query);
if ($stmt_meat) {
    if ($prod_summary_year !== 'all') {
        $stmt_meat->bind_param("isi", $range_id, $range_name, $prod_summary_year);
    } else {
        $stmt_meat->bind_param("is", $range_id, $range_name);
    }
    $stmt_meat->execute();
    $res_meat = $stmt_meat->get_result();
    while ($rm = $res_meat->fetch_assoc()) {
        $m_type = $rm['meat_type'];
        $m_kg = floatval($rm['total_kg']);
        $m_rev = floatval($rm['total_revenue']);
        $meat_summary['total_kg'] += $m_kg;
        $meat_summary['total_revenue'] += $m_rev;
        $meat_summary['categories'][$m_type] = ($meat_summary['categories'][$m_type] ?? 0) + $m_kg;
        if ($m_type === 'Other' && !empty($rm['other_meat_name'])) {
            $oname = trim($rm['other_meat_name']);
            $meat_summary['others_detail'][$oname] = ($meat_summary['others_detail'][$oname] ?? 0) + $m_kg;
        }
    }
    $stmt_meat->close();
}

// Available production summary years
$available_prod_years = array_unique(array_merge($pop_years, [intval(date('Y')), intval(date('Y')) - 1, 2025, 2024]));
rsort($available_prod_years);

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 mb-0 fw-bold" style="color: #370709;">Range Statistics & Overview</h2>
                <small class="text-muted"><?= htmlspecialchars($range_name) ?> | DAPH Eastern Province</small>
            </div>
        </div>

        <?php if (isset($_SESSION['msg'])): ?>
            <div class="alert alert-<?= htmlspecialchars($_SESSION['msg_type'] ?? 'info') ?> alert-dismissible fade show mb-4 shadow-sm" role="alert">
                <?= $_SESSION['msg'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
        <?php endif; ?>

        <!-- Human Population Dynamics section -->
        <div class="row g-4 mb-5 mt-2">
            <div class="col-12">
                <div class="card gov-card">
                    <div class="card-header bg-white pt-4 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-1" style="color: #370709;"><i class="bi bi-people-fill me-2"></i>Human Population</h5>
                            <p class="text-muted small mb-0">Demographic composition tracking and sector breakdown analytics from database.</p>
                        </div>
                        <div>
                            <button type="button" class="btn btn-sm btn-dark fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#manageHumanPopulationModal">
                                <i class="bi bi-gear-fill me-1"></i> Manage Population
                            </button>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4">

                        <div class="row g-3 mb-4 p-3 rounded text-dark" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-bold text-secondary">Year Selection</label>
                                <select id="filterYear" class="form-select form-select-sm filter-control">
                                    <?php foreach ($pop_years as $py): ?>
                                        <option value="<?= $py ?>" <?= $py === 2025 ? 'selected' : '' ?>><?= $py ?></option>
                                    <?php endforeach; ?>
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

        <!-- Animal Population Dynamics section -->
        <div class="row g-4 mb-5 mt-2">
            <div class="col-12">
                <div class="card gov-card">
                    <div class="card-header bg-white pt-4 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-1" style="color: #370709;"><i class="bi bi-shield-shaded me-2"></i>Animal Population</h5>
                            <p class="text-muted small mb-0">Livestock demographics composition tracking and sector breakdown analytics from database.</p>
                        </div>
                        <div>
                            <button type="button" class="btn btn-sm btn-dark fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#manageAnimalPopulationModal">
                                <i class="bi bi-gear-fill me-1"></i> Manage Population
                            </button>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4">

                        <div class="row g-3 mb-4 p-3 rounded text-dark" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-bold text-secondary">Year Selection</label>
                                <select id="filterYearAnimal" class="form-select form-select-sm filter-control-animal">
                                    <?php foreach ($animal_pop_years as $apy): ?>
                                        <option value="<?= $apy ?>" <?= $apy === 2025 ? 'selected' : '' ?>><?= $apy ?></option>
                                    <?php endforeach; ?>
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
                                            <input class="form-check-input animal-option" type="checkbox" value="Sheep" id="animSheep" checked>
                                            <label class="form-check-label small" for="animSheep">Sheep</label>
                                        </div>
                                        <div class="form-check mb-1">
                                            <input class="form-check-input animal-option" type="checkbox" value="Chicken" id="animChicken" checked>
                                            <label class="form-check-label small" for="animChicken">Poultry</label>
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

        <!-- Automated Production & Sales Summary Dashboard -->
        <div class="card gov-card mb-5">
            <div class="card-header bg-white pt-4 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1" style="color: #370709;">
                        <i class="bi bi-speedometer2 text-danger me-2"></i>Automated Production & Sales Summary Dashboards
                    </h5>
                    <p class="text-muted small mb-0">
                        Real-time compilation of manual entries across milk centers, feed mills, production outlets, and meat sales (Year: <strong class="text-dark"><?= $prod_summary_year === 'all' ? 'All Years Combined' : $prod_summary_year ?></strong>).
                    </p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <form method="GET" class="d-flex align-items-center gap-2 mb-0">
                        <?php if ($requested_range_id): ?>
                            <input type="hidden" name="range_id" value="<?= $requested_range_id ?>">
                        <?php endif; ?>
                        <label class="small fw-bold text-muted text-nowrap mb-0">Summary Year:</label>
                        <select name="prod_year" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 120px;">
                            <option value="all" <?= ($prod_summary_year === 'all') ? 'selected' : '' ?>>All Years</option>
                            <?php foreach ($available_prod_years as $py): ?>
                                <option value="<?= $py ?>" <?= ($prod_summary_year !== 'all' && intval($prod_summary_year) === $py) ? 'selected' : '' ?>><?= $py ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
            <div class="card-body px-4 pb-4">
                <!-- 4 Top Executive Summary Cards -->
                <div class="row g-3 mb-4">
                    <!-- Milk Collection Summary Card -->
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-left: 4px solid #0284c7 !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-uppercase fw-bold small text-primary">Milk Collection</span>
                                <span class="badge bg-primary text-white rounded-pill px-2 py-1"><?= number_format($milk_summary['centers']) ?> Centers</span>
                            </div>
                            <h3 class="fw-bold my-2 text-dark">
                                <?= number_format($milk_summary['total_milk']) ?> <small class="fs-6 text-muted">Liters</small>
                            </h3>
                            <div class="small text-muted mt-auto pt-2 border-top">
                                <div class="d-flex justify-content-between">
                                    <span>Cow: <strong><?= number_format($milk_summary['cow_milk']) ?></strong> L</span>
                                    <span>Buff: <strong><?= number_format($milk_summary['buffalo_milk']) ?></strong> L</span>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span>Goat: <strong><?= number_format($milk_summary['goat_milk']) ?></strong> L</span>
                                    <span>Chilling: <strong><?= number_format($milk_summary['chilling_cap']) ?></strong> L</span>
                                </div>
                            </div>
                            <a href="milk_collection_details.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-sm btn-outline-primary mt-3 w-100 fw-bold">
                                View Milk Log <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Feed Production Summary Card -->
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #fefce8 0%, #fef9c3 100%); border-left: 4px solid #ca8a04 !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-uppercase fw-bold small text-dark">Feed Production</span>
                                <span class="badge bg-warning text-dark rounded-pill px-2 py-1"><?= number_format($feed_summary['mills']) ?> Active Mills</span>
                            </div>
                            <h3 class="fw-bold my-2 text-dark">
                                <?= number_format($feed_summary['total_mt'], 2) ?> <small class="fs-6 text-muted">MT Total</small>
                            </h3>
                            <div class="small text-muted mt-auto pt-2 border-top">
                                <?php if (empty($feed_summary['categories'])): ?>
                                    <span>No mill entries logged for this period.</span>
                                <?php else: ?>
                                    <?php foreach (array_slice($feed_summary['categories'], 0, 2, true) as $fcat => $fmt): ?>
                                        <div class="d-flex justify-content-between">
                                            <span><?= ucfirst(htmlspecialchars($fcat)) ?>:</span>
                                            <strong><?= number_format($fmt, 1) ?> MT</strong>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <a href="annual_feed_production.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-sm btn-outline-warning text-dark mt-3 w-100 fw-bold">
                                View Feed Mills <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Production Outlets Summary Card -->
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #16a34a !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-uppercase fw-bold small text-success">Production Outlets</span>
                                <span class="badge bg-success text-white rounded-pill px-2 py-1"><?= number_format($outlets_summary['chicks']) ?> Chicks</span>
                            </div>
                            <h3 class="fw-bold my-2 text-dark">
                                <?= number_format($outlets_summary['live_chicken_kg'] + $outlets_summary['dressed_chicken_kg']) ?> <small class="fs-6 text-muted">kg Poultry</small>
                            </h3>
                            <div class="small text-muted mt-auto pt-2 border-top">
                                <div class="d-flex justify-content-between">
                                    <span>Live: <strong><?= number_format($outlets_summary['live_chicken_kg']) ?></strong> kg</span>
                                    <span>Dressed: <strong><?= number_format($outlets_summary['dressed_chicken_kg']) ?></strong> kg</span>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span>Organic Fert: <strong><?= number_format($outlets_summary['organic_fert_mt'], 1) ?></strong> MT</span>
                                </div>
                            </div>
                            <a href="annual_producers_processors.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-sm btn-outline-success mt-3 w-100 fw-bold">
                                View Outlets <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Meat Sales Summary Card -->
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%); border-left: 4px solid #e11d48 !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-uppercase fw-bold small text-danger">Meat Sales Turnover</span>
                                <span class="badge bg-danger text-white rounded-pill px-2 py-1"><?= number_format($meat_summary['total_kg'], 1) ?> kg</span>
                            </div>
                            <h3 class="fw-bold my-2 text-danger">
                                Rs. <?= number_format($meat_summary['total_revenue'], 2) ?>
                            </h3>
                            <div class="small text-muted mt-auto pt-2 border-top">
                                <div class="d-flex justify-content-between">
                                    <span>Beef: <strong><?= number_format($meat_summary['categories']['Beef'] ?? 0) ?></strong> kg</span>
                                    <span>Mutton: <strong><?= number_format($meat_summary['categories']['Mutton'] ?? 0) ?></strong> kg</span>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span>Chicken: <strong><?= number_format($meat_summary['categories']['Chicken'] ?? 0) ?></strong> kg</span>
                                    <span>Others: <strong><?= number_format($meat_summary['categories']['Other'] ?? 0) ?></strong> kg</span>
                                </div>
                            </div>
                            <a href="meat_sales.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-sm btn-outline-danger mt-3 w-100 fw-bold">
                                View Meat Sales <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Aggregated Sector Overview Table -->
                <div class="table-responsive rounded border">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light small text-secondary text-uppercase">
                            <tr>
                                <th>Operational Sector</th>
                                <th>Active Units / Outlets</th>
                                <th>Primary Volume Aggregation</th>
                                <th>Secondary Metrics & Categorization</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <tr>
                                <td class="fw-bold text-dark">
                                    <i class="bi bi-droplet-half text-primary me-2 fs-6"></i>Milk Collection Centers
                                </td>
                                <td><span class="badge bg-primary-subtle text-primary fw-bold"><?= number_format($milk_summary['centers']) ?> Centers</span></td>
                                <td><strong class="text-primary fs-6"><?= number_format($milk_summary['total_milk']) ?></strong> Liters Total</td>
                                <td>
                                    Cow: <?= number_format($milk_summary['cow_milk']) ?> L | Buffalo: <?= number_format($milk_summary['buffalo_milk']) ?> L | Goat: <?= number_format($milk_summary['goat_milk']) ?> L
                                </td>
                                <td class="text-center">
                                    <a href="milk_collection_details.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-xs btn-outline-primary py-1 px-2 fw-bold">
                                        Open Module
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark">
                                    <i class="bi bi-prescription2 text-warning me-2 fs-6"></i>Feed Production Mills
                                </td>
                                <td><span class="badge bg-warning-subtle text-dark fw-bold"><?= number_format($feed_summary['mills']) ?> Registered Mills</span></td>
                                <td><strong class="text-dark fs-6"><?= number_format($feed_summary['total_mt'], 2) ?></strong> Metric Tons (MT)</td>
                                <td>
                                    <?php 
                                        if (!empty($feed_summary['categories'])) {
                                            $cat_strs = [];
                                            foreach ($feed_summary['categories'] as $ck => $cv) {
                                                $cat_strs[] = ucfirst(htmlspecialchars($ck)) . ': ' . number_format($cv, 1) . ' MT';
                                            }
                                            echo implode(' | ', $cat_strs);
                                        } else {
                                            echo '<span class="text-muted">No feed production logs</span>';
                                        }
                                    ?>
                                </td>
                                <td class="text-center">
                                    <a href="annual_feed_production.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-xs btn-outline-warning text-dark py-1 px-2 fw-bold">
                                        Open Module
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark">
                                    <i class="bi bi-buildings text-success me-2 fs-6"></i>Production Outlets (Producers & Processors)
                                </td>
                                <td><span class="badge bg-success-subtle text-success fw-bold">Active Outlets</span></td>
                                <td><strong class="text-success fs-6"><?= number_format($outlets_summary['chicks']) ?></strong> Day-Old Chicks</td>
                                <td>
                                    Live Poultry: <?= number_format($outlets_summary['live_chicken_kg']) ?> kg | Dressed: <?= number_format($outlets_summary['dressed_chicken_kg']) ?> kg | Organic Fertilizer: <?= number_format($outlets_summary['organic_fert_mt'], 1) ?> MT
                                </td>
                                <td class="text-center">
                                    <a href="annual_producers_processors.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-xs btn-outline-success py-1 px-2 fw-bold">
                                        Open Module
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-dark">
                                    <i class="bi bi-basket3-fill text-danger me-2 fs-6"></i>Meat Sales Details
                                </td>
                                <td><span class="badge bg-danger-subtle text-danger fw-bold">Wholesale & Retail</span></td>
                                <td><strong class="text-danger fs-6"><?= number_format($meat_summary['total_kg'], 1) ?></strong> kg (Rs. <?= number_format($meat_summary['total_revenue'], 2) ?>)</td>
                                <td>
                                    Beef: <?= number_format($meat_summary['categories']['Beef'] ?? 0) ?> kg | Mutton: <?= number_format($meat_summary['categories']['Mutton'] ?? 0) ?> kg | Chicken: <?= number_format($meat_summary['categories']['Chicken'] ?? 0) ?> kg
                                    <?php if (!empty($meat_summary['others_detail'])): ?>
                                        | <span class="fw-bold">Others:</span> <?= implode(', ', array_map(fn($k, $v) => htmlspecialchars($k) . ' (' . number_format($v) . ' kg)', array_keys($meat_summary['others_detail']), $meat_summary['others_detail'])) ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="meat_sales.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-xs btn-outline-danger py-1 px-2 fw-bold">
                                        Open Module
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Annual Returns & Inventories Quick Actions -->
        <div class="card gov-card mb-5">
            <div class="card-header bg-white pt-4 px-4 border-0">
                <h5 class="fw-bold mb-1" style="color: #370709;"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Annual Returns & Inventory Management</h5>
                <p class="text-muted small mb-0">Quick access links to manage annual data logs, production levels, pasture details, and livestock societies.</p>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <a href="annual_production_levels.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-primary w-100 py-3 d-flex flex-column align-items-center justify-content-center h-100 border-2">
                            <i class="bi bi-graph-up-arrow fs-3 mb-2"></i>
                            <span class="text-center fw-bold">Production Levels</span>
                            <small class="text-white-50 small">Daily Range Rates</small>
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <a href="pasture.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-success w-100 py-3 d-flex flex-column align-items-center justify-content-center h-100 border-2">
                            <i class="bi bi-tree-fill fs-3 mb-2"></i>
                            <span class="text-center fw-bold">Pasture & Fodder</span>
                            <small class="text-white-50 small">Lands, Cultivation & Yields</small>
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <a href="meat_sales.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-danger w-100 py-3 d-flex flex-column align-items-center justify-content-center h-100 border-2">
                            <i class="bi bi-basket3-fill fs-3 mb-2"></i>
                            <span class="text-center fw-bold">Meat Sales Details</span>
                            <small class="text-white-50 small">Beef, Mutton, Poultry, Other</small>
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <a href="milk_collection_details.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-dark w-100 py-3 d-flex flex-column align-items-center justify-content-center h-100 border-2">
                            <i class="bi bi-droplet-half fs-3 mb-2 text-info"></i>
                            <span class="text-center fw-bold">Milk Collection Details</span>
                            <small class="text-white-50 small">Cow, Buffalo & Goat Breakdown</small>
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <a href="annual_producers_processors.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-warning w-100 py-3 d-flex flex-column align-items-center justify-content-center h-100 border-2 text-dark">
                            <i class="bi bi-buildings fs-3 mb-2"></i>
                            <span class="text-center fw-bold">Producers & Processors</span>
                            <small class="text-muted small">Private Sector & Income</small>
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <a href="annual_feed_production.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-secondary w-100 py-3 d-flex flex-column align-items-center justify-content-center h-100 border-2">
                            <i class="bi bi-prescription2 fs-3 mb-2"></i>
                            <span class="text-center fw-bold">Feed Production Mills</span>
                            <small class="text-white-50 small">Output & Outlets</small>
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <a href="annual_livestock_societies.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center justify-content-center h-100 border-2">
                            <i class="bi bi-people-fill fs-3 mb-2"></i>
                            <span class="text-center fw-bold">Livestock Societies</span>
                            <small class="text-muted small">Societies & Code of Conduct</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>

<?php
include 'models/add_health_record.php';
include 'models/manage_human_population_modal.php';
include 'models/manage_animal_population_modal.php';

ob_start();
?>
<script>
const CURRENT_RANGE_ID = <?= json_encode($range_id) ?>;
$(document).ready(function() {
    // Dynamic total calculation in manage population modal
    function updateManagePopTotal() {
        const male = parseInt($("#managePopMale").val()) || 0;
        const female = parseInt($("#managePopFemale").val()) || 0;
        $("#managePopTotalPreview").text((male + female).toLocaleString());
    }
    $(document).on("input", "#managePopMale, #managePopFemale", updateManagePopTotal);

    // Reset button in manage population modal
    $("#btnResetPopForm").on("click", function() {
        $("#manageHumanPopForm")[0].reset();
        const currentYear = $("#filterYear").val() || new Date().getFullYear();
        $("#managePopYear").val(currentYear);
        $("#managePopTotalPreview").text("0");
        $("#formTabLabel").text("Add / Update Record");
        $("#btnSavePopForm").html('<i class="bi bi-check-circle-fill me-1 text-success"></i> Save Demographics');
        $("#managePopAlertBox").empty();
    });

    // Fetch and populate recorded demographics list in modal
    function loadRecordedDemographics() {
        const rangeParam = typeof CURRENT_RANGE_ID !== 'undefined' && CURRENT_RANGE_ID ? `&range_id=${CURRENT_RANGE_ID}` : '';
        $.ajax({
            url: `processors/save_human_population.php?action=get_list${rangeParam}`,
            type: "GET",
            dataType: "json",
            success: function(res) {
                if (res.success) {
                    const tbody = $("#recordedDemographicsTable tbody");
                    tbody.empty();
                    $("#recordsCountBadge").text(res.data.length);
                    if (res.data.length === 0) {
                        tbody.append('<tr><td colspan="7" class="text-center py-3 text-muted">No population records found for this range.</td></tr>');
                        return;
                    }
                    res.data.forEach(function(item) {
                        const safeEth = $("<div>").text(item.ethnicity).html();
                        const row = `
                            <tr data-year="${item.year}" data-ethnicity="${safeEth}" data-male="${item.male}" data-female="${item.female}" data-households="${item.households}">
                                <td class="fw-bold">${item.year}</td>
                                <td><span class="badge bg-secondary">${safeEth}</span></td>
                                <td class="text-end text-primary font-monospace">${Number(item.male).toLocaleString()}</td>
                                <td class="text-end text-danger font-monospace">${Number(item.female).toLocaleString()}</td>
                                <td class="text-end fw-bold font-monospace" style="color: #370709;">${Number(item.total).toLocaleString()}</td>
                                <td class="text-end text-success font-monospace">${Number(item.households).toLocaleString()}</td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2 btn-edit-human-pop" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-xs py-0 px-2 btn-delete-human-pop" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                        tbody.append(row);
                    });
                }
            },
            error: function() {
                $("#recordedDemographicsTable tbody").html('<tr><td colspan="7" class="text-center py-3 text-danger">Failed to load records.</td></tr>');
            }
        });
    }

    // Modal open event: pre-fill current active year & refresh demographics list
    $("#manageHumanPopulationModal").on("show.bs.modal", function() {
        const currentYear = $("#filterYear").val() || new Date().getFullYear();
        if ($("#formTabLabel").text() === "Add / Update Record") {
            $("#managePopYear").val(currentYear);
        }
        loadRecordedDemographics();
    });

    $("#btnRefreshPopList").on("click", function() {
        loadRecordedDemographics();
    });

    // Form submission via AJAX
    $("#manageHumanPopForm").on("submit", function(e) {
        e.preventDefault();
        const btn = $("#btnSavePopForm");
        const originalBtnHtml = btn.html();
        btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...');

        $.ajax({
            url: "processors/save_human_population.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(res) {
                btn.prop("disabled", false).html(originalBtnHtml);
                if (res.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Saved Successfully",
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // Ensure saved year exists in filter dropdown and is selected
                    const savedYear = res.year;
                    if (savedYear) {
                        if ($(`#filterYear option[value="${savedYear}"]`).length === 0) {
                            $("#filterYear").prepend(new Option(savedYear, savedYear, true, true));
                        }
                        $("#filterYear").val(savedYear);
                    }

                    // Trigger direct reload of pie chart and datatable
                    if (typeof window.fetchFilteredPopulationData === 'function') {
                        window.fetchFilteredPopulationData();
                    }

                    // Refresh modal records list
                    loadRecordedDemographics();

                    // Close the modal so the updated pie chart and table are immediately visible
                    const modalEl = document.getElementById("manageHumanPopulationModal");
                    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                    }
                    $("#manageHumanPopulationModal").modal("hide");
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Save Failed",
                        text: res.message || "An error occurred while saving demographics."
                    });
                }
            },
            error: function() {
                btn.prop("disabled", false).html(originalBtnHtml);
                Swal.fire({
                    icon: "error",
                    title: "Request Error",
                    text: "Server communication failed. Check connection and try again."
                });
            }
        });
    });

    // Edit button click in modal records table
    $(document).on("click", ".btn-edit-human-pop", function() {
        const row = $(this).closest("tr");
        const year = row.data("year");
        const ethnicity = row.data("ethnicity");
        const male = row.data("male");
        const female = row.data("female");
        const households = row.data("households");

        $("#managePopYear").val(year);
        $("#managePopEthnicity").val(ethnicity);
        $("#managePopMale").val(male);
        $("#managePopFemale").val(female);
        $("#managePopHouseholds").val(households);
        updateManagePopTotal();

        $("#formTabLabel").text(`Edit Record: ${ethnicity} (${year})`);
        $("#btnSavePopForm").html('<i class="bi bi-pencil-square me-1"></i> Update Demographics');

        const addTabTrigger = new bootstrap.Tab(document.getElementById("add-pop-tab"));
        addTabTrigger.show();
    });

    // Delete button click in modal records table
    $(document).on("click", ".btn-delete-human-pop", function() {
        const row = $(this).closest("tr");
        const year = row.data("year");
        const ethnicity = row.data("ethnicity");

        Swal.fire({
            icon: "warning",
            title: "Delete Demographic Record?",
            html: `Are you sure you want to delete population data for <strong>${ethnicity}</strong> in year <strong>${year}</strong>?<br><small class="text-danger">This will remove Male, Female, and Household counts for this group.</small>`,
            showCancelButton: true,
            confirmButtonColor: "#370709",
            cancelButtonColor: "#6c757d",
            confirmButtonText: '<i class="bi bi-trash-fill me-1"></i> Yes, Delete',
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "processors/save_human_population.php",
                    type: "POST",
                    data: {
                        action: "delete",
                        year: year,
                        ethnicity: ethnicity,
                        range_id: typeof CURRENT_RANGE_ID !== 'undefined' && CURRENT_RANGE_ID ? CURRENT_RANGE_ID : ''
                    },
                    dataType: "json",
                    success: function(res) {
                        if (res.success) {
                            Swal.fire({
                                icon: "success",
                                title: "Deleted",
                                text: res.message,
                                timer: 1800,
                                showConfirmButton: false
                            });
                            row.fadeOut(300, function() {
                                $(this).remove();
                                const currentCount = parseInt($("#recordsCountBadge").text()) || 1;
                                $("#recordsCountBadge").text(Math.max(0, currentCount - 1));
                                if ($("#recordedDemographicsTable tbody tr").length === 0) {
                                    $("#recordedDemographicsTable tbody").append('<tr><td colspan="7" class="text-center py-3 text-muted">No population records found for this range.</td></tr>');
                                }
                            });

                            // Refresh main page chart & table
                            if (typeof window.fetchFilteredPopulationData === 'function') {
                                window.fetchFilteredPopulationData();
                            }
                            const filterYearEl = document.getElementById("filterYear");
                            if (filterYearEl) {
                                filterYearEl.dispatchEvent(new Event("change"));
                            }
                        } else {
                            Swal.fire({
                                icon: "error",
                                title: "Deletion Failed",
                                text: res.message
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: "Server communication error while attempting to delete."
                        });
                    }
                });
            }
        });
    });

    // ==========================================
    // ANIMAL POPULATION MANAGEMENT ROUTINES
    // ==========================================

    // Dynamic total calculation in manage animal population modal
    function updateManageAnimalPopTotal() {
        let total = 0;
        $(".animal-counter-input").each(function() {
            total += parseInt($(this).val()) || 0;
        });
        $("#manageAnimalPopTotalPreview").text(total.toLocaleString());
    }
    $(document).on("input", ".animal-counter-input", updateManageAnimalPopTotal);

    // Reset button in manage animal population modal
    $("#btnResetAnimalPopForm").on("click", function() {
        $("#manageAnimalPopForm")[0].reset();
        const currentYear = $("#filterYearAnimal").val() || new Date().getFullYear();
        $("#manageAnimalPopYear").val(currentYear);
        $("#manageAnimalPopTotalPreview").text("0");
        $("#formAnimalTabLabel").text("Add / Update Record");
        $("#btnSaveAnimalPopForm").html('<i class="bi bi-check-circle-fill me-1 text-success"></i> Save Animal Population');
        $("#manageAnimalPopAlertBox").empty();
    });

    // Helper to fetch and load counts for a specific year into the form
    function loadYearAnimalData(year) {
        const rangeParam = typeof CURRENT_RANGE_ID !== 'undefined' && CURRENT_RANGE_ID ? `&range_id=${CURRENT_RANGE_ID}` : '';
        $.ajax({
            url: `processors/save_animal_population.php?action=get_year_data&year=${year}${rangeParam}`,
            type: "GET",
            dataType: "json",
            success: function(res) {
                if (res.success && res.data) {
                    $("#animal_count_Cow").val(res.data.Cow || 0);
                    $("#animal_count_Buffalo").val(res.data.Buffalo || 0);
                    $("#animal_count_Goat").val(res.data.Goat || 0);
                    $("#animal_count_Sheep").val(res.data.Sheep || 0);
                    $("#animal_count_Chicken").val(res.data.Chicken || 0);
                    $("#animal_count_Pig").val(res.data.Pig || 0);
                    $("#animal_count_Others").val(res.data.Others || 0);
                    updateManageAnimalPopTotal();
                }
            }
        });
    }

    $("#btnLoadYearAnimalData").on("click", function() {
        const y = $("#manageAnimalPopYear").val();
        if (y) {
            loadYearAnimalData(y);
        }
    });

    $("#manageAnimalPopYear").on("change", function() {
        const y = $(this).val();
        if (y) {
            loadYearAnimalData(y);
        }
    });

    // Fetch and populate recorded livestock list in modal
    function loadRecordedAnimalDemographics() {
        const rangeParam = typeof CURRENT_RANGE_ID !== 'undefined' && CURRENT_RANGE_ID ? `&range_id=${CURRENT_RANGE_ID}` : '';
        $.ajax({
            url: `processors/save_animal_population.php?action=get_list${rangeParam}`,
            type: "GET",
            dataType: "json",
            success: function(res) {
                if (res.success) {
                    const tbody = $("#recordedAnimalDemographicsTable tbody");
                    tbody.empty();
                    $("#animalRecordsCountBadge").text(res.data.length);
                    if (res.data.length === 0) {
                        tbody.append('<tr><td colspan="10" class="text-center py-3 text-muted">No animal population records found for this range.</td></tr>');
                        return;
                    }
                    res.data.forEach(function(item) {
                        const row = `
                            <tr data-year="${item.year}" 
                                data-cow="${item.Cow}" 
                                data-buffalo="${item.Buffalo}" 
                                data-goat="${item.Goat}" 
                                data-sheep="${item.Sheep || 0}" 
                                data-chicken="${item.Chicken}" 
                                data-pig="${item.Pig}" 
                                data-others="${item.Others}">
                                <td class="fw-bold">${item.year}</td>
                                <td class="text-end font-monospace">${Number(item.Cow).toLocaleString()}</td>
                                <td class="text-end font-monospace">${Number(item.Buffalo).toLocaleString()}</td>
                                <td class="text-end font-monospace">${Number(item.Goat).toLocaleString()}</td>
                                <td class="text-end font-monospace">${Number(item.Sheep || 0).toLocaleString()}</td>
                                <td class="text-end font-monospace">${Number(item.Chicken).toLocaleString()}</td>
                                <td class="text-end font-monospace">${Number(item.Pig).toLocaleString()}</td>
                                <td class="text-end font-monospace">${Number(item.Others).toLocaleString()}</td>
                                <td class="text-end fw-bold font-monospace" style="color: #370709;">${Number(item.total).toLocaleString()}</td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2 btn-edit-animal-pop" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-xs py-0 px-2 btn-delete-animal-pop" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                        tbody.append(row);
                    });
                }
            },
            error: function() {
                $("#recordedAnimalDemographicsTable tbody").html('<tr><td colspan="10" class="text-center py-3 text-danger">Failed to load records.</td></tr>');
            }
        });
    }

    // Modal open event: pre-fill current active year & refresh livestock list
    $("#manageAnimalPopulationModal").on("show.bs.modal", function() {
        const currentYear = $("#filterYearAnimal").val() || new Date().getFullYear();
        if ($("#formAnimalTabLabel").text() === "Add / Update Record") {
            $("#manageAnimalPopYear").val(currentYear);
            loadYearAnimalData(currentYear);
        }
        loadRecordedAnimalDemographics();
    });

    $("#btnRefreshAnimalPopList").on("click", function() {
        loadRecordedAnimalDemographics();
    });

    // Form submission via AJAX for Animal Population
    $("#manageAnimalPopForm").on("submit", function(e) {
        e.preventDefault();
        const btn = $("#btnSaveAnimalPopForm");
        const originalBtnHtml = btn.html();
        btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...');

        $.ajax({
            url: "processors/save_animal_population.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(res) {
                btn.prop("disabled", false).html(originalBtnHtml);
                if (res.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Saved Successfully",
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // Ensure saved year exists in animal filter dropdown and is selected
                    const savedYear = res.year;
                    if (savedYear) {
                        if ($(`#filterYearAnimal option[value="${savedYear}"]`).length === 0) {
                            $("#filterYearAnimal").prepend(new Option(savedYear, savedYear, true, true));
                        }
                        $("#filterYearAnimal").val(savedYear);
                    }

                    // Trigger direct reload of animal pie chart and datatable
                    if (typeof window.fetchFilteredAnimalPopulationData === 'function') {
                        window.fetchFilteredAnimalPopulationData();
                    }

                    // Refresh modal records list
                    loadRecordedAnimalDemographics();

                    // Close modal
                    const modalEl = document.getElementById("manageAnimalPopulationModal");
                    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                    }
                    $("#manageAnimalPopulationModal").modal("hide");
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Save Failed",
                        text: res.message || "An error occurred while saving animal population."
                    });
                }
            },
            error: function() {
                btn.prop("disabled", false).html(originalBtnHtml);
                Swal.fire({
                    icon: "error",
                    title: "Request Error",
                    text: "Server communication failed. Check connection and try again."
                });
            }
        });
    });

    // Edit button click in modal records table
    $(document).on("click", ".btn-edit-animal-pop", function() {
        const row = $(this).closest("tr");
        const year = row.data("year");
        const cow = row.data("cow");
        const buffalo = row.data("buffalo");
        const goat = row.data("goat");
        const sheep = row.data("sheep");
        const chicken = row.data("chicken");
        const pig = row.data("pig");
        const others = row.data("others");

        $("#manageAnimalPopYear").val(year);
        $("#animal_count_Cow").val(cow);
        $("#animal_count_Buffalo").val(buffalo);
        $("#animal_count_Goat").val(goat);
        $("#animal_count_Sheep").val(sheep);
        $("#animal_count_Chicken").val(chicken);
        $("#animal_count_Pig").val(pig);
        $("#animal_count_Others").val(others);
        updateManageAnimalPopTotal();

        $("#formAnimalTabLabel").text(`Edit Record: Year ${year}`);
        $("#btnSaveAnimalPopForm").html('<i class="bi bi-pencil-square me-1"></i> Update Animal Population');

        const addTabTrigger = new bootstrap.Tab(document.getElementById("add-animal-pop-tab"));
        addTabTrigger.show();
    });

    // Delete button click in modal records table
    $(document).on("click", ".btn-delete-animal-pop", function() {
        const row = $(this).closest("tr");
        const year = row.data("year");
        const rangeParam = typeof CURRENT_RANGE_ID !== 'undefined' && CURRENT_RANGE_ID ? CURRENT_RANGE_ID : '';

        Swal.fire({
            icon: "warning",
            title: "Delete Animal Population Record?",
            html: `Are you sure you want to delete all animal population data for year <strong>${year}</strong>?<br><small class="text-danger">This will remove counts for all livestock categories in this year.</small>`,
            showCancelButton: true,
            confirmButtonColor: "#370709",
            cancelButtonColor: "#6c757d",
            confirmButtonText: '<i class="bi bi-trash-fill me-1"></i> Yes, Delete',
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "processors/save_animal_population.php",
                    type: "POST",
                    data: {
                        action: "delete",
                        year: year,
                        range_id: rangeParam
                    },
                    dataType: "json",
                    success: function(res) {
                        if (res.success) {
                            Swal.fire({
                                icon: "success",
                                title: "Deleted",
                                text: res.message,
                                timer: 1800,
                                showConfirmButton: false
                            });
                            row.fadeOut(300, function() {
                                $(this).remove();
                                const currentCount = parseInt($("#animalRecordsCountBadge").text()) || 1;
                                $("#animalRecordsCountBadge").text(Math.max(0, currentCount - 1));
                                if ($("#recordedAnimalDemographicsTable tbody tr").length === 0) {
                                    $("#recordedAnimalDemographicsTable tbody").append('<tr><td colspan="9" class="text-center py-3 text-muted">No animal population records found for this range.</td></tr>');
                                }
                            });

                            // Refresh main page animal chart & table
                            if (typeof window.fetchFilteredAnimalPopulationData === 'function') {
                                window.fetchFilteredAnimalPopulationData();
                            }
                            const filterYearAnimalEl = document.getElementById("filterYearAnimal");
                            if (filterYearAnimalEl) {
                                filterYearAnimalEl.dispatchEvent(new Event("change"));
                            }
                        } else {
                            Swal.fire({
                                icon: "error",
                                title: "Deletion Failed",
                                text: res.message
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: "Server communication error while attempting to delete."
                        });
                    }
                });
            }
        });
    });
});
</script>
<?php
$pageScripts = ob_get_clean();
require_once '../../../includes/footer.php';
?>