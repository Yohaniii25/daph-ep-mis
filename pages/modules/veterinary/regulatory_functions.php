<?php
/**
 * Regulatory & Enforcement Dashboard
 * Live Livestock & Poultry Census Demographics from Animal Branding Registry
 * Department of Animal Production & Health (DAPH)
 */
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$allowed_roles = [
    'veterinary_surgeon',
    'government_veterinary_surgeon',
    'sms',
    'district_dd',
    'deputy_director_district',
    'administrator',
    'provincial_director',
    'deputy_director_hq_1',
    'deputy_director_hq_2',
    'admin'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    header("Location: ../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? '';
$session_range_id = $_SESSION['range_id'] ?? null;

$requested_range_id = isset($_GET['range_id']) && is_numeric($_GET['range_id']) ? (int)$_GET['range_id'] : null;
$range_id = $requested_range_id !== null ? $requested_range_id : ($session_range_id ?: 1);

$range_name = 'Your Range';
$range_code = '5314';
$district_name = 'Your District';
$district_id = 1;
$province_name = 'Eastern Province';

// Fetch Range and District Details
if (!empty($range_id)) {
    $r_stmt = $mysqli->prepare("
        SELECT vr.name AS range_name, vr.code AS range_code, d.name AS district_name, d.id AS district_id 
        FROM veterinary_ranges vr 
        LEFT JOIN districts d ON d.id = vr.district_id 
        WHERE vr.id = ? LIMIT 1
    ");
    if ($r_stmt) {
        $r_stmt->bind_param("i", $range_id);
        $r_stmt->execute();
        $r_res = $r_stmt->get_result();
        if ($row = $r_res->fetch_assoc()) {
            $range_name = $row['range_name'];
            $range_code = $row['range_code'] ?: '5314';
            $district_name = $row['district_name'] ?: 'Ampara';
            $district_id = (int)($row['district_id'] ?? 1);
        }
        $r_stmt->close();
    }
} else {
    $range_name = 'All Veterinary Ranges';
}

// Fetch all veterinary ranges for dropdown selector
$all_ranges = [];
$ranges_res = $mysqli->query("SELECT id, name, code FROM veterinary_ranges ORDER BY name ASC");
if ($ranges_res) {
    while ($r = $ranges_res->fetch_assoc()) {
        $all_ranges[] = $r;
    }
}

// -----------------------------------------------------------------------------
// LIVE LIVESTOCK & POULTRY AGGREGATION FROM animal_branding.php MASTER TABLE
// -----------------------------------------------------------------------------
$where_range = ($range_id > 0) ? "WHERE (range_id = {$range_id} OR range_id IS NULL OR range_id = 0)" : "";

$records_query = "
    SELECT * 
    FROM `farm_registration_renewals` 
    {$where_range}
    ORDER BY `date_of_registration_renewal` DESC, `id` DESC
";
$res_farms = $mysqli->query($records_query);

$registered_farms = [];
$total_farms = 0;
$total_livestock_farms = 0;
$total_poultry_farms = 0;

$livestock_totals = [
    'cattle'  => 0,
    'buffalo' => 0,
    'goat'    => 0,
    'sheep'   => 0,
    'swine'   => 0
];

$poultry_totals = [
    'chicks'        => 0,
    'broiler'       => 0,
    'layer'         => 0,
    'other_poultry' => 0,
    'total_poultry' => 0
];

$farm_type_counts = [
    'Dairy / Cattle' => 0,
    'Buffaloes'      => 0,
    'Goats / Sheep'  => 0,
    'Swine / Piggery'=> 0,
    'Poultry'        => 0,
    'Mixed Farming'  => 0,
    'Other'          => 0
];

if ($res_farms) {
    while ($r = $res_farms->fetch_assoc()) {
        $total_farms++;
        
        $c  = (int)($r['total_neat_cattle'] ?? 0);
        $b  = (int)($r['total_buffaloes'] ?? 0);
        $g  = (int)($r['goat_total_no'] ?? 0);
        $sh = (int)($r['sheep_total_no'] ?? 0);
        $sw = (int)($r['swine_total_no'] ?? 0);
        $farm_livestock_total = $c + $b + $g + $sh + $sw;

        $livestock_totals['cattle']  += $c;
        $livestock_totals['buffalo'] += $b;
        $livestock_totals['goat']    += $g;
        $livestock_totals['sheep']   += $sh;
        $livestock_totals['swine']   += $sw;

        if ($farm_livestock_total > 0) {
            $total_livestock_farms++;
        }

        // Parse Poultry Data
        $p_count = 0;
        $p_type = '';
        $has_poultry = false;

        if (!empty($r['poultry_data'])) {
            $p_data = json_decode($r['poultry_data'], true);
            if (is_array($p_data)) {
                $p_type = strtolower($p_data['poultry_farm_type'] ?? '');
                if (!empty($p_data['flock_age_groups']) && is_array($p_data['flock_age_groups'])) {
                    foreach ($p_data['flock_age_groups'] as $fg) {
                        $qty = (int)($fg['quantity'] ?? 0);
                        $grp = strtolower($fg['age_group'] ?? '');
                        if ($qty > 0) {
                            $has_poultry = true;
                            $p_count += $qty;
                            $poultry_totals['total_poultry'] += $qty;
                            
                            if (strpos($grp, 'chick') !== false) {
                                $poultry_totals['chicks'] += $qty;
                            } elseif (strpos($grp, 'layer') !== false || $p_type === 'layer') {
                                $poultry_totals['layer'] += $qty;
                            } elseif (strpos($grp, 'broiler') !== false || $p_type === 'broiler') {
                                $poultry_totals['broiler'] += $qty;
                            } else {
                                $poultry_totals['other_poultry'] += $qty;
                            }
                        }
                    }
                }
            }
        }

        $is_poultry_farm = ($r['farm_type'] === 'Poultry') || $has_poultry || (isset($r['registration_no']) && preg_match('/^[Pp]/', $r['registration_no']));
        if ($is_poultry_farm) {
            $total_poultry_farms++;
        }

        // Categorize farm type for demographics
        $ft = trim($r['farm_type'] ?? '');
        if ($ft === 'Poultry' || $is_poultry_farm) {
            $farm_type_counts['Poultry']++;
        } elseif ($ft === 'Dairy' || $c > 0) {
            $farm_type_counts['Dairy / Cattle']++;
        } elseif ($ft === 'Buffaloes' || $b > 0) {
            $farm_type_counts['Buffaloes']++;
        } elseif ($ft === 'Goats' || $g > 0 || $sh > 0) {
            $farm_type_counts['Goats / Sheep']++;
        } elseif ($ft === 'Swine' || $sw > 0) {
            $farm_type_counts['Swine / Piggery']++;
        } elseif ($ft === 'Mixed') {
            $farm_type_counts['Mixed Farming']++;
        } else {
            $farm_type_counts['Other']++;
        }

        $r['computed_livestock_total'] = $farm_livestock_total;
        $r['computed_poultry_total'] = $p_count;
        $registered_farms[] = $r;
    }
}

$grand_total_livestock = array_sum($livestock_totals);
$grand_total_poultry = $poultry_totals['total_poultry'];
$grand_total_animals = $grand_total_livestock + $grand_total_poultry;

// Query certified / ear-tagged count for this range
$tagged_count = 0;
$t_query = "SELECT COUNT(*) AS tagged FROM `animal_identifications` " . (($range_id > 0) ? "WHERE (range_id = {$range_id} OR range_id IS NULL OR range_id = 0)" : "");
$t_res = $mysqli->query($t_query);
if ($t_res && $t_row = $t_res->fetch_assoc()) {
    $tagged_count = (int)$t_row['tagged'];
}

require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="container-fluid px-2 px-md-3 py-3">

    <!-- Top Breadcrumb & Range Switcher -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="../../../dashboard.php" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i>Dashboard</a></li>
                <li class="breadcrumb-item text-muted">Veterinary Module</li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Regulatory &amp; Enforcement Dashboard</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <form method="GET" class="d-flex align-items-center gap-2 m-0">
                <label class="form-label small mb-0 fw-bold text-muted d-none d-sm-inline">Select Range:</label>
                <select name="range_id" class="form-select form-select-sm shadow-xs" onchange="this.form.submit();" style="min-width: 200px;">
                    <option value="0" <?= $range_id === 0 ? 'selected' : '' ?>>-- All Veterinary Ranges --</option>
                    <?php foreach ($all_ranges as $rng): ?>
                        <option value="<?= $rng['id'] ?>" <?= (int)$rng['id'] === (int)$range_id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($rng['name']) ?> (<?= htmlspecialchars($rng['code'] ?: 'Range') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="bi bi-geo-alt-fill text-danger me-1"></i>District: <strong><?= htmlspecialchars($district_name) ?></strong>
            </span>
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="bi bi-calendar3 text-primary me-1"></i>Year: <strong><?= date('Y') ?></strong>
            </span>
        </div>
    </div>

    <!-- Hero Banner with Live Range Context -->
    <div class="card border-0 rounded-4 shadow-sm mb-4 text-white overflow-hidden" style="background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 70%, #370709 100%);">
        <div class="card-body p-4 position-relative">
            <div class="row align-items-center g-3">
                <div class="col-lg-7">
                    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                        <span class="badge bg-warning text-dark fw-bold px-2 py-1"><i class="bi bi-shield-check me-1"></i>DAPH Regulatory Oversight</span>
                        <span class="badge bg-white bg-opacity-25 text-white">Animal Branding &amp; Census Analytics</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Range ID: <?= $range_id ?: 'All' ?></span>
                    </div>
                    <h2 class="h3 fw-bold mb-2">Regulatory &amp; Enforcement Dashboard</h2>
                    <p class="mb-0 text-white-50 small" style="max-width: 680px;">
                        Managing statutory veterinary enforcement, mandatory animal branding, census registry, and total livestock &amp; poultry demographics for <strong><?= htmlspecialchars($range_name) ?></strong> under the Animals Act.
                    </p>
                </div>
                <div class="col-lg-5">
                    <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                        <a href="animal_branding.php<?= $range_id ? '?range_id=' . $range_id : '' ?>" class="btn btn-warning text-dark fw-bold shadow-sm">
                            <i class="bi bi-patch-check-fill me-1"></i>Animal Branding Registry
                        </a>
                        <a href="cattle_voucher.php<?= $range_id ? '?range_id=' . $range_id : '' ?>" class="btn btn-outline-light btn-sm fw-semibold">
                            <i class="bi bi-receipt-cutoff me-1"></i>Cattle Vouchers
                        </a>
                        <a href="ear_tag_balance.php<?= $range_id ? '?range_id=' . $range_id : '' ?>" class="btn btn-outline-light btn-sm fw-semibold">
                            <i class="bi bi-tags me-1"></i>Ear Tag Balance
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4 Live Demographic KPI Cards -->
    <div class="row g-3 mb-4">
        <!-- KPI 1: Total Livestock Animals -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 rounded-3 shadow-xs h-100 bg-white border-start border-4 border-primary">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="text-uppercase fw-bold text-primary" style="font-size: 0.72rem; letter-spacing: 0.05em;">Total Livestock Animals</span>
                            <h3 class="fw-bold mb-0 text-dark font-monospace mt-1"><?= number_format($grand_total_livestock) ?></h3>
                        </div>
                        <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                            <i class="bi bi-collection-fill fs-5"></i>
                        </div>
                    </div>
                    <div class="small text-muted" style="font-size: 0.74rem;">
                        <i class="bi bi-info-circle text-primary me-1"></i>
                        Cattle: <strong><?= number_format($livestock_totals['cattle']) ?></strong> | 
                        Buffalo: <strong><?= number_format($livestock_totals['buffalo']) ?></strong> | 
                        Goat: <strong><?= number_format($livestock_totals['goat']) ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 2: Total Poultry Animals -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 rounded-3 shadow-xs h-100 bg-white border-start border-4 border-warning">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="text-uppercase fw-bold text-warning-emphasis" style="font-size: 0.72rem; letter-spacing: 0.05em;">Total Poultry Birds</span>
                            <h3 class="fw-bold mb-0 text-dark font-monospace mt-1"><?= number_format($grand_total_poultry) ?></h3>
                        </div>
                        <div class="bg-warning-subtle text-warning-emphasis rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                            <i class="bi bi-egg-fried fs-5"></i>
                        </div>
                    </div>
                    <div class="small text-muted" style="font-size: 0.74rem;">
                        <i class="bi bi-feather text-warning me-1"></i>
                        Chicks: <strong><?= number_format($poultry_totals['chicks']) ?></strong> | 
                        Broiler: <strong><?= number_format($poultry_totals['broiler']) ?></strong> | 
                        Layers: <strong><?= number_format($poultry_totals['layer']) ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 3: Registered Holdings -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 rounded-3 shadow-xs h-100 bg-white border-start border-4 border-success">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="text-uppercase fw-bold text-success" style="font-size: 0.72rem; letter-spacing: 0.05em;">Registered Holdings</span>
                            <h3 class="fw-bold mb-0 text-dark font-monospace mt-1"><?= number_format($total_farms) ?></h3>
                        </div>
                        <div class="bg-success-subtle text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                            <i class="bi bi-houses-fill fs-5"></i>
                        </div>
                    </div>
                    <div class="small text-muted" style="font-size: 0.74rem;">
                        <i class="bi bi-check-circle-fill text-success me-1"></i>
                        Livestock Farms: <strong><?= $total_livestock_farms ?></strong> | Poultry: <strong><?= $total_poultry_farms ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 4: Certified / Ear Tagged Animals -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 rounded-3 shadow-xs h-100 bg-white border-start border-4 border-danger">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="text-uppercase fw-bold text-danger" style="font-size: 0.72rem; letter-spacing: 0.05em;">Ear Tagged &amp; Certified</span>
                            <h3 class="fw-bold mb-0 text-danger font-monospace mt-1"><?= number_format($tagged_count) ?></h3>
                        </div>
                        <div class="bg-danger-subtle text-danger rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                            <i class="bi bi-tag-fill fs-5"></i>
                        </div>
                    </div>
                    <div class="small text-muted" style="font-size: 0.74rem;">
                        <i class="bi bi-shield-check text-danger me-1"></i>Official statutory ear tagged cattle registry
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- CHARTS SECTION: TOTAL LIVESTOCK & TOTAL POULTRY VISUALIZATIONS -->
    <!-- ============================================================== -->
    <div class="row g-3 mb-4">
        
        <!-- CHART 1: TOTAL LIVESTOCK ANIMALS BREAKDOWN -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 rounded-4 shadow-sm bg-white h-100">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-pie-chart-fill text-primary"></i>
                            <span>Total Livestock Animals Breakdown</span>
                            <span class="badge bg-primary text-white rounded-pill px-2 py-1 font-monospace"><?= number_format($grand_total_livestock) ?> Head</span>
                        </h6>
                        <small class="text-muted">Live census breakdown by species in <?= htmlspecialchars($range_name) ?></small>
                    </div>
                    <span class="badge bg-light text-muted border font-monospace">Animal Branding Data</span>
                </div>
                <div class="card-body p-3">
                    <?php if ($grand_total_livestock > 0): ?>
                        <div style="position: relative; height: 280px;">
                            <canvas id="chartLivestockSpecies"></canvas>
                        </div>
                        <!-- Mini species stats footer pills -->
                        <div class="row g-2 mt-3 pt-2 border-top text-center" style="font-size: 0.75rem;">
                            <div class="col-4 col-sm-2">
                                <span class="d-block text-muted">Neat Cattle</span>
                                <strong class="text-primary font-monospace"><?= $livestock_totals['cattle'] ?></strong>
                            </div>
                            <div class="col-4 col-sm-2">
                                <span class="d-block text-muted">Buffaloes</span>
                                <strong class="text-purple font-monospace" style="color: #7c3aed;"><?= $livestock_totals['buffalo'] ?></strong>
                            </div>
                            <div class="col-4 col-sm-2">
                                <span class="d-block text-muted">Goats</span>
                                <strong class="text-teal font-monospace" style="color: #0d9488;"><?= $livestock_totals['goat'] ?></strong>
                            </div>
                            <div class="col-4 col-sm-2">
                                <span class="d-block text-muted">Sheep</span>
                                <strong class="text-amber font-monospace" style="color: #d97706;"><?= $livestock_totals['sheep'] ?></strong>
                            </div>
                            <div class="col-4 col-sm-2">
                                <span class="d-block text-muted">Swine</span>
                                <strong class="text-rose font-monospace" style="color: #e11d48;"><?= $livestock_totals['swine'] ?></strong>
                            </div>
                            <div class="col-4 col-sm-2">
                                <span class="d-block text-muted">Total</span>
                                <strong class="text-dark font-monospace"><?= $grand_total_livestock ?></strong>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-pie-chart text-secondary opacity-50 fs-1 d-block mb-2"></i>
                            No livestock animals registered yet in this range.<br>
                            <a href="animal_branding.php<?= $range_id ? '?range_id=' . $range_id : '' ?>" class="btn btn-primary btn-sm mt-2">
                                <i class="bi bi-plus-circle me-1"></i>Register Livestock in Animal Branding
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- CHART 2: TOTAL POULTRY ANIMALS BREAKDOWN -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 rounded-4 shadow-sm bg-white h-100">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-egg-fill text-warning"></i>
                            <span>Total Poultry Flock &amp; Category Breakdown</span>
                            <span class="badge bg-warning text-dark rounded-pill px-2 py-1 font-monospace"><?= number_format($grand_total_poultry) ?> Birds</span>
                        </h6>
                        <small class="text-muted">Poultry population census from flock registers in <?= htmlspecialchars($range_name) ?></small>
                    </div>
                    <span class="badge bg-light text-muted border font-monospace">Poultry Registry</span>
                </div>
                <div class="card-body p-3">
                    <?php if ($grand_total_poultry > 0): ?>
                        <div style="position: relative; height: 280px;">
                            <canvas id="chartPoultryFlock"></canvas>
                        </div>
                        <!-- Mini poultry stats footer pills -->
                        <div class="row g-2 mt-3 pt-2 border-top text-center" style="font-size: 0.75rem;">
                            <div class="col-3">
                                <span class="d-block text-muted">Chicks (&lt;8 wk)</span>
                                <strong class="text-warning font-monospace"><?= $poultry_totals['chicks'] ?></strong>
                            </div>
                            <div class="col-3">
                                <span class="d-block text-muted">Broilers</span>
                                <strong class="font-monospace" style="color: #ea580c;"><?= $poultry_totals['broiler'] ?></strong>
                            </div>
                            <div class="col-3">
                                <span class="d-block text-muted">Layers</span>
                                <strong class="font-monospace" style="color: #84cc16;"><?= $poultry_totals['layer'] ?></strong>
                            </div>
                            <div class="col-3">
                                <span class="d-block text-muted">Backyard / Other</span>
                                <strong class="font-monospace" style="color: #06b6d4;"><?= $poultry_totals['other_poultry'] ?></strong>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-egg text-secondary opacity-50 fs-1 d-block mb-2"></i>
                            No poultry flock animals registered yet in this range.<br>
                            <a href="animal_branding.php?view_poultry=1<?= $range_id ? '&range_id=' . $range_id : '' ?>" class="btn btn-warning btn-sm text-dark fw-bold mt-2">
                                <i class="bi bi-plus-circle me-1"></i>Register Poultry in Animal Branding
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- ============================================================== -->
    <!-- CHARTS ROW 2: LIVESTOCK VS POULTRY COMPARATIVE ANALYSIS        -->
    <!-- ============================================================== -->
    <div class="row g-3 mb-4">
        
        <!-- Comparative Bar Chart -->
        <div class="col-12 col-lg-7">
            <div class="card border-0 rounded-4 shadow-sm bg-white h-100">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-bar-chart-fill text-info"></i>
                            <span>Livestock vs Poultry Population Comparison</span>
                        </h6>
                        <small class="text-muted">Total animals census comparison for <?= htmlspecialchars($range_name) ?></small>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="badge bg-primary text-white font-monospace"><?= number_format($grand_total_livestock) ?> Livestock</span>
                        <span class="badge bg-warning text-dark font-monospace"><?= number_format($grand_total_poultry) ?> Poultry</span>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div style="position: relative; height: 260px;">
                        <canvas id="chartComparativeAnimals"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Holding Distribution by Farm Type -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 rounded-4 shadow-sm bg-white h-100">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-diagram-3-fill text-success"></i>
                            <span>Holding Distribution by Sector</span>
                        </h6>
                        <small class="text-muted">Registered farm classifications</small>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace"><?= $total_farms ?> Total Holdings</span>
                </div>
                <div class="card-body p-3">
                    <div style="position: relative; height: 260px;">
                        <canvas id="chartFarmTypes"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- ============================================================== -->
    <!-- QUICK ACTIONS REGULATORY MODULES GRID                          -->
    <!-- ============================================================== -->
    <div class="card border-0 rounded-4 shadow-sm bg-white mb-4">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-grid-fill text-primary"></i>
                <span>Veterinary Regulatory &amp; Statutory Modules</span>
            </h6>
            <small class="text-muted">Quick access to livestock registration, branding, identification, and certification workflows</small>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                
                <!-- Action 1: Animal Branding -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="p-3 border rounded-3 bg-light h-100 d-flex flex-column justify-content-between transition-hover shadow-xs">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-patch-check-fill fs-5"></i>
                                </div>
                                <span class="badge bg-primary text-white font-monospace"><?= $total_farms ?> Farms</span>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Livestock Farm Registration &amp; Animal Branding</h6>
                            <p class="small text-muted mb-3" style="font-size: 0.76rem;">
                                Official registry for farm renewals, livestock census demographics, milk production, and fodder land area.
                            </p>
                        </div>
                        <a href="animal_branding.php<?= $range_id ? '?range_id=' . $range_id : '' ?>" class="btn btn-primary btn-sm w-100 fw-semibold">
                            Open Animal Branding <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Action 2: Cattle Vouchers & Birth Certificates -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="p-3 border rounded-3 bg-light h-100 d-flex flex-column justify-content-between transition-hover shadow-xs">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="bg-danger text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-file-earmark-medical-fill fs-5"></i>
                                </div>
                                <span class="badge bg-danger text-white font-monospace">Schedule-06</span>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Cattle Voucher &amp; Birth Certificate</h6>
                            <p class="small text-muted mb-3" style="font-size: 0.76rem;">
                                Digitize Schedule-06 cattle vouchers, animal birth certificates, and physical printable counterfoil stubs.
                            </p>
                        </div>
                        <a href="cattle_voucher.php<?= $range_id ? '?range_id=' . $range_id : '' ?>" class="btn btn-danger btn-sm w-100 fw-semibold">
                            Open Cattle Vouchers <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Action 3: Ear Tag Balance -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="p-3 border rounded-3 bg-light h-100 d-flex flex-column justify-content-between transition-hover shadow-xs">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="bg-success text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-tags-fill fs-5"></i>
                                </div>
                                <span class="badge bg-success text-white font-monospace"><?= $tagged_count ?> Tagged</span>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Ear Tag Stock &amp; Animal Identification</h6>
                            <p class="small text-muted mb-3" style="font-size: 0.76rem;">
                                Individual animal identification registry, ear tag stocks, distribution to farmers, and monthly ledger balance.
                            </p>
                        </div>
                        <a href="ear_tag_balance.php<?= $range_id ? '?range_id=' . $range_id : '' ?>" class="btn btn-success btn-sm w-100 fw-semibold">
                            Open Ear Tag Registry <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Action 4: Health Certificates -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="p-3 border rounded-3 bg-light h-100 d-flex flex-column justify-content-between transition-hover shadow-xs">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-truck fs-5"></i>
                                </div>
                                <span class="badge bg-dark text-white font-monospace">Schedule-03</span>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Health Certificate &amp; Vehicle Fitness</h6>
                            <p class="small text-muted mb-3" style="font-size: 0.76rem;">
                                Issue Schedule-03 animal transportation health certificates and Schedule-04 vehicle fitness certificates.
                            </p>
                        </div>
                        <a href="health_certificate.php<?= $range_id ? '?range_id=' . $range_id : '' ?>" class="btn btn-dark btn-sm w-100 fw-semibold">
                            Open Health Certificates <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- DETAILED REGISTERED HOLDINGS TABLE                             -->
    <!-- ============================================================== -->
    <div class="card border-0 rounded-4 shadow-sm bg-white mb-4">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-table text-primary"></i>
                    <span>Registered Farm Holdings &amp; Animal Census in <?= htmlspecialchars($range_name) ?></span>
                    <span class="badge bg-primary text-white rounded-pill px-2 py-1 ms-1"><?= count($registered_farms) ?> Holdings</span>
                </h6>
                <small class="text-muted">Master database records registered in animal_branding.php</small>
            </div>
            <a href="animal_branding.php<?= $range_id ? '?range_id=' . $range_id : '' ?>" class="btn btn-outline-primary btn-sm fw-semibold">
                <i class="bi bi-pencil-square me-1"></i>Manage in Animal Branding
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0" id="tblRegulatoryFarms">
                    <thead class="table-light small">
                        <tr>
                            <th style="width: 40px;" class="text-center">#</th>
                            <th>Farm Reg. No.</th>
                            <th>Owner / Farmer Name</th>
                            <th>NIC</th>
                            <th>Farm Classification</th>
                            <th class="text-center">Neat Cattle</th>
                            <th class="text-center">Buffaloes</th>
                            <th class="text-center">Goats &amp; Sheep</th>
                            <th class="text-center">Swine</th>
                            <th class="text-center bg-primary-subtle text-primary fw-bold">Total Livestock</th>
                            <th class="text-center bg-warning-subtle text-dark fw-bold">Total Poultry</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($registered_farms)): ?>
                            <tr>
                                <td colspan="12" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    No registered holdings found for <strong><?= htmlspecialchars($range_name) ?></strong> in the animal branding registry.<br>
                                    <a href="animal_branding.php<?= $range_id ? '?range_id=' . $range_id : '' ?>" class="btn btn-primary btn-sm mt-3">
                                        <i class="bi bi-plus-circle me-1"></i>Register New Farm in Animal Branding
                                    </a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($registered_farms as $idx => $rf): ?>
                                <tr>
                                    <td class="text-center text-muted fw-semibold"><?= $idx + 1 ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace fw-bold">
                                            <?= htmlspecialchars($rf['registration_no'] ?: '—') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="text-dark d-block"><?= htmlspecialchars($rf['farmer_name'] ?: '—') ?></strong>
                                        <small class="text-muted text-truncate d-block" style="max-width: 170px;">
                                            <?= htmlspecialchars($rf['farmer_address'] ?: $rf['farm_address'] ?: '—') ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="font-monospace text-muted"><?= htmlspecialchars($rf['nic'] ?: '—') ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary border">
                                            <?= htmlspecialchars($rf['farm_type'] ?: 'Mixed') ?>
                                        </span>
                                    </td>
                                    <td class="text-center font-monospace text-primary fw-semibold">
                                        <?= (int)$rf['total_neat_cattle'] ?>
                                    </td>
                                    <td class="text-center font-monospace text-purple fw-semibold" style="color: #7c3aed;">
                                        <?= (int)$rf['total_buffaloes'] ?>
                                    </td>
                                    <td class="text-center font-monospace text-teal fw-semibold" style="color: #0d9488;">
                                        <?= (int)$rf['goat_total_no'] + (int)$rf['sheep_total_no'] ?>
                                    </td>
                                    <td class="text-center font-monospace text-rose fw-semibold" style="color: #e11d48;">
                                        <?= (int)$rf['swine_total_no'] ?>
                                    </td>
                                    <td class="text-center font-monospace fw-bold bg-primary-subtle text-primary">
                                        <?= (int)$rf['computed_livestock_total'] ?>
                                    </td>
                                    <td class="text-center font-monospace fw-bold bg-warning-subtle text-dark">
                                        <?= (int)$rf['computed_poultry_total'] ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="animal_branding.php?edit_id=<?= $rf['id'] ?><?= $range_id ? '&range_id=' . $range_id : '' ?>" class="btn btn-outline-primary btn-sm py-1 px-2" title="View / Edit in Animal Branding">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
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

<?php
ob_start();
?>
<!-- Pass dynamic data from PHP to Chart.js -->
<script>
window.regulatoryCensusData = {
    livestock: {
        labels: ['Neat Cattle', 'Buffaloes', 'Goats', 'Sheep', 'Swine (Pigs)'],
        data: [
            <?= (int)$livestock_totals['cattle'] ?>,
            <?= (int)$livestock_totals['buffalo'] ?>,
            <?= (int)$livestock_totals['goat'] ?>,
            <?= (int)$livestock_totals['sheep'] ?>,
            <?= (int)$livestock_totals['swine'] ?>
        ],
        colors: ['#2563eb', '#7c3aed', '#0d9488', '#d97706', '#e11d48']
    },
    poultry: {
        labels: ['Chicks (<8 weeks)', 'Commercial Broilers', 'Commercial Layers', 'Backyard / Other'],
        data: [
            <?= (int)$poultry_totals['chicks'] ?>,
            <?= (int)$poultry_totals['broiler'] ?>,
            <?= (int)$poultry_totals['layer'] ?>,
            <?= (int)$poultry_totals['other_poultry'] ?>
        ],
        colors: ['#f59e0b', '#ea580c', '#84cc16', '#06b6d4']
    },
    comparative: {
        labels: ['Total Livestock Animals', 'Total Poultry Birds'],
        data: [<?= (int)$grand_total_livestock ?>, <?= (int)$grand_total_poultry ?>],
        colors: ['#2563eb', '#f59e0b']
    },
    farmTypes: {
        labels: <?= json_encode(array_keys($farm_type_counts)) ?>,
        data: <?= json_encode(array_values($farm_type_counts)) ?>,
        colors: ['#2563eb', '#7c3aed', '#0d9488', '#e11d48', '#f59e0b', '#06b6d4', '#64748b']
    }
};

// Initialize Chart.js once library is ready
function initRegulatoryCharts() {
    if (typeof Chart === 'undefined') {
        setTimeout(initRegulatoryCharts, 100);
        return;
    }

    var data = window.regulatoryCensusData;

    // 1. Livestock Species Doughnut Chart
    var ctxLivestock = document.getElementById('chartLivestockSpecies');
    if (ctxLivestock) {
        new Chart(ctxLivestock.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: data.livestock.labels,
                datasets: [{
                    data: data.livestock.data,
                    backgroundColor: data.livestock.colors,
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            font: { size: 11, family: "'Segoe UI', sans-serif" },
                            padding: 12
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(item) {
                                var total = item.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                                var val = item.raw || 0;
                                var pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                return ' ' + item.label + ': ' + val.toLocaleString() + ' (' + pct + '%)';
                            }
                        }
                    }
                },
                cutout: '62%'
            }
        });
    }

    // 2. Poultry Flock Doughnut Chart
    var ctxPoultry = document.getElementById('chartPoultryFlock');
    if (ctxPoultry) {
        new Chart(ctxPoultry.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: data.poultry.labels,
                datasets: [{
                    data: data.poultry.data,
                    backgroundColor: data.poultry.colors,
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            font: { size: 11, family: "'Segoe UI', sans-serif" },
                            padding: 12
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(item) {
                                var total = item.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                                var val = item.raw || 0;
                                var pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                return ' ' + item.label + ': ' + val.toLocaleString() + ' (' + pct + '%)';
                            }
                        }
                    }
                },
                cutout: '62%'
            }
        });
    }

    // 3. Livestock vs Poultry Comparative Bar Chart
    var ctxComp = document.getElementById('chartComparativeAnimals');
    if (ctxComp) {
        new Chart(ctxComp.getContext('2d'), {
            type: 'bar',
            data: {
                labels: data.comparative.labels,
                datasets: [{
                    label: 'Population Count',
                    data: data.comparative.data,
                    backgroundColor: data.comparative.colors,
                    borderRadius: 6,
                    barThickness: 45
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(item) {
                                return ' ' + item.label + ': ' + item.raw.toLocaleString() + ' animals';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            font: { size: 10 }
                        },
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11, weight: '600' } }
                    }
                }
            }
        });
    }

    // 4. Holding Sector Distribution Chart
    var ctxFarms = document.getElementById('chartFarmTypes');
    if (ctxFarms) {
        new Chart(ctxFarms.getContext('2d'), {
            type: 'polarArea',
            data: {
                labels: data.farmTypes.labels,
                datasets: [{
                    data: data.farmTypes.data,
                    backgroundColor: data.farmTypes.colors.map(function(c) { return c + 'CC'; }),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 10,
                            font: { size: 10 }
                        }
                    }
                },
                scales: {
                    r: {
                        ticks: { display: false }
                    }
                }
            }
        });
    }
}

// Ensure execution after page and script loads
if (document.readyState === 'complete') {
    initRegulatoryCharts();
} else {
    window.addEventListener('load', initRegulatoryCharts);
}
</script>
<?php
$pageScripts = ob_get_clean();

require_once __DIR__ . '/../../../includes/footer.php';
?>