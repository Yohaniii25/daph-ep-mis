<?php
// pages/modules/district/regional_farms.php -> Master Regional Farms Operations Dashboard
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_roles = ['district_dd', 'deputy_director_district', 'administrator', 'provincial_director', 'deputy_director_hq_1', 'deputy_director_hq_2', 'farms_dd'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied");
}

require_once '../../../config/db_connect.php';
require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';

// Resolve District context
$user_role = $_SESSION['role'] ?? '';
$is_district_level_dd = in_array($user_role, ['district_dd', 'deputy_director_district']);
$district_id = $_SESSION['district_id'] ?? null;
$district_name = $_SESSION['district'] ?? '';

if (empty($district_id) && !empty($district_name)) {
    if (strcasecmp($district_name, 'Amparai') === 0 || strcasecmp($district_name, 'Ampara') === 0) {
        $district_id = 1;
    } elseif (strcasecmp($district_name, 'Batticaloa') === 0) {
        $district_id = 2;
    } elseif (strcasecmp($district_name, 'Trincomalee') === 0) {
        $district_id = 3;
    }
}
if (empty($district_id)) $district_id = 1;

// Fetch official district name
$dist_stmt = $mysqli->prepare("SELECT name FROM districts WHERE id = ? LIMIT 1");
if ($dist_stmt) {
    $dist_stmt->bind_param("i", $district_id);
    $dist_stmt->execute();
    $dist_res = $dist_stmt->get_result();
    if ($row = $dist_res->fetch_assoc()) {
        $district_name = $row['name'];
    }
    $dist_stmt->close();
}

// Active Tab / View Selection
$current_view = isset($_GET['view']) ? trim($_GET['view']) : 'farms_list';
$selected_farm_id = isset($_GET['farm_id']) ? intval($_GET['farm_id']) : 0;

// Farm Location to District Mapping
$location_district_map = [
    'uppuveli' => 3,     // Trincomalee
    'kantalai' => 3,     // Trincomalee
    'morawewa' => 3,     // Trincomalee
    'mandoor' => 2,      // Batticaloa
    'sathurukonda' => 2, // Batticaloa
    'thumpankerny' => 2, // Batticaloa
    'thirukkovil' => 1   // Ampara
];

// Fetch all regional farms
$all_farms_res = $mysqli->query("SELECT * FROM regional_farms ORDER BY id ASC");
$raw_farms = $all_farms_res ? $all_farms_res->fetch_all(MYSQLI_ASSOC) : [];

$district_farms = [];
$farm_names_lookup = [];
$district_farm_ids = [];

foreach ($raw_farms as $rf) {
    $loc = strtolower(trim($rf['location'] ?? ''));
    $f_dist_id = $location_district_map[$loc] ?? 0;
    $farm_names_lookup[$rf['id']] = $rf['farm_name'] . ' (' . $rf['location'] . ')';

    // If viewing as District DD, only include farms in assigned district
    if ($is_district_level_dd) {
        if ($f_dist_id == $district_id) {
            $district_farms[] = $rf;
            $district_farm_ids[] = $rf['id'];
        }
    } else {
        // HQ or Provincial Director: include all, tag district
        $rf['mapped_district_id'] = $f_dist_id;
        $district_farms[] = $rf;
        $district_farm_ids[] = $rf['id'];
    }
}

// If no local farms exist in district (or for safety fallback), keep farm list clean
$active_farm_scope_name = 'All Farms in ' . $district_name;
if ($selected_farm_id > 0 && isset($farm_names_lookup[$selected_farm_id])) {
    $active_farm_scope_name = $farm_names_lookup[$selected_farm_id];
}

// =========================================================================
// AGGREGATED METRICS ACROSS FARM SUB-MODULES
// =========================================================================

// Metric 1: Total Parent Stock Flocks & Bird Count
$flocks_cnt = 0;
$flocks_birds = 0;
$flock_sql = "SELECT COUNT(id) AS total_flocks, IFNULL(SUM(current_count), 0) AS total_birds FROM parent_stock_flocks";
if ($selected_farm_id > 0) {
    $flock_sql .= " WHERE farm_id = " . intval($selected_farm_id);
} elseif (!empty($district_farm_ids) && $is_district_level_dd) {
    $flock_sql .= " WHERE farm_id IN (" . implode(',', array_map('intval', $district_farm_ids)) . ")";
}
if ($f_res = $mysqli->query($flock_sql)) {
    if ($f_row = $f_res->fetch_assoc()) {
        $flocks_cnt = (int)$f_row['total_flocks'];
        $flocks_birds = (int)$f_row['total_birds'];
    }
}

// Metric 2: Total Hatchery Eggs Harvested / Set
$total_eggs_collected = 0;
$egg_coll_sql = "SELECT IFNULL(SUM(total_eggs), 0) AS total_eggs FROM daily_egg_production";
if ($e_res = $mysqli->query($egg_coll_sql)) {
    if ($e_row = $e_res->fetch_assoc()) {
        $total_eggs_collected = (int)$e_row['total_eggs'];
    }
}

// Metric 3: Total Revenue Realized (Egg Sales + Produce Sales + Chick Sales + Livestock Disposal)
$total_egg_sales = 0.00;
$sales_sql = "SELECT IFNULL(SUM(grand_total_sales), 0) AS rev FROM daily_egg_sales";
if ($s_res = $mysqli->query($sales_sql)) {
    $total_egg_sales = (float)($s_res->fetch_assoc()['rev'] ?? 0);
}

$total_produce_sales = 0.00;
$prod_sales_sql = "SELECT IFNULL(SUM(full_sum_realized), 0) AS rev FROM farm_produce_register_annex6";
if ($ps_res = $mysqli->query($prod_sales_sql)) {
    $total_produce_sales = (float)($ps_res->fetch_assoc()['rev'] ?? 0);
}

$total_chick_sales = 0.00;
$chk_sql = "SELECT (IFNULL((SELECT SUM(total_amount) FROM day_old_chicks_distribution), 0) + 
                    IFNULL((SELECT SUM(total_amount) FROM month_old_chicks_distribution), 0)) AS rev";
if ($chk_res = $mysqli->query($chk_sql)) {
    $total_chick_sales = (float)($chk_res->fetch_assoc()['rev'] ?? 0);
}

$total_livestock_sales = 0.00;
$ls_sql = "SELECT IFNULL(SUM(amount_realized), 0) AS rev FROM animal_disposal_register";
if ($ls_res = $mysqli->query($ls_sql)) {
    $total_livestock_sales = (float)($ls_res->fetch_assoc()['rev'] ?? 0);
}

$total_revenue_all = $total_egg_sales + $total_produce_sales + $total_chick_sales + $total_livestock_sales;

// =========================================================================
// DATASET FETCHING PER ACTIVE VIEW
// =========================================================================
$report_records = [];

// 1. Farms Directory
if ($current_view === 'farms_list') {
    $fd_sql = "SELECT rf.id AS farm_id, rf.farm_name, rf.location, rf.is_active,
                      u.id AS officer_id, u.full_name AS officer_name, u.username AS officer_username, u.email AS officer_email, u.phone AS officer_phone,
                      (SELECT COUNT(*) FROM users fu WHERE fu.farm_id = rf.id AND fu.is_active = 1) AS total_farm_staff,
                      (SELECT COUNT(*) FROM parent_stock_flocks psf WHERE psf.farm_id = rf.id) AS total_flocks,
                      (SELECT IFNULL(SUM(psf2.current_count), 0) FROM parent_stock_flocks psf2 WHERE psf2.farm_id = rf.id) AS total_flock_birds
               FROM regional_farms rf
               LEFT JOIN users u ON u.farm_id = rf.id AND u.role = 'farms_dd' AND u.is_active = 1";
    if ($selected_farm_id > 0) {
        $fd_sql .= " WHERE rf.id = " . intval($selected_farm_id);
    } elseif (!empty($district_farm_ids) && $is_district_level_dd) {
        $fd_sql .= " WHERE rf.id IN (" . implode(',', array_map('intval', $district_farm_ids)) . ")";
    }
    $fd_sql .= " ORDER BY rf.id ASC";
    if ($fd_res = $mysqli->query($fd_sql)) {
        $report_records = $fd_res->fetch_all(MYSQLI_ASSOC);
    }
}

// 2. Parent Stock & Egg Production Operations Summary
elseif ($current_view === 'parent_stock') {
    $ps_subtab = isset($_GET['ps_tab']) ? trim($_GET['ps_tab']) : 'collections';

    // 2.1 Daily Egg Collections
    $ps_col_sql = "SELECT dep.*, b.batch_number AS batch_name, c.cage_name, rf.farm_name, rf.location
                   FROM daily_egg_production dep
                   LEFT JOIN vaccine_batches b ON dep.batch_id = b.id
                   LEFT JOIN cages c ON dep.cage_id = c.id
                   LEFT JOIN users u ON b.user_id = u.id
                   LEFT JOIN regional_farms rf ON u.farm_id = rf.id";
    if ($selected_farm_id > 0) {
        $ps_col_sql .= " WHERE (u.farm_id = " . intval($selected_farm_id) . " OR u.farm_id IS NULL)";
    } elseif (!empty($district_farm_ids) && $is_district_level_dd) {
        $ps_col_sql .= " WHERE (u.farm_id IN (" . implode(',', array_map('intval', $district_farm_ids)) . ") OR u.farm_id IS NULL)";
    }
    $ps_col_sql .= " ORDER BY dep.collection_date DESC, dep.id DESC";
    $ps_collections = [];
    if ($psc_res = $mysqli->query($ps_col_sql)) {
        $ps_collections = $psc_res->fetch_all(MYSQLI_ASSOC);
    }

    // 2.2 Parent Stock Flocks & Bird Count
    $ps_flk_sql = "SELECT psf.*, rf.farm_name, rf.location, u.full_name AS manager_name
                   FROM parent_stock_flocks psf
                   LEFT JOIN regional_farms rf ON psf.farm_id = rf.id
                   LEFT JOIN users u ON psf.user_id = u.id";
    if ($selected_farm_id > 0) {
        $ps_flk_sql .= " WHERE psf.farm_id = " . intval($selected_farm_id);
    } elseif (!empty($district_farm_ids) && $is_district_level_dd) {
        $ps_flk_sql .= " WHERE psf.farm_id IN (" . implode(',', array_map('intval', $district_farm_ids)) . ")";
    }
    $ps_flk_sql .= " ORDER BY psf.id ASC";
    $ps_flocks = [];
    if ($psf_res = $mysqli->query($ps_flk_sql)) {
        $ps_flocks = $psf_res->fetch_all(MYSQLI_ASSOC);
    }

    // 2.3 Active Cages & Infrastructure Matrix
    $ps_cage_sql = "SELECT c.id, c.cage_name, 
                           COUNT(dep.id) AS total_logs, 
                           IFNULL(SUM(dep.total_eggs), 0) AS total_eggs_produced,
                           IFNULL(SUM(dep.total_eggs_kg), 0) AS total_eggs_weight,
                           IFNULL(AVG(dep.pullets), 0) AS avg_pullets,
                           IFNULL(AVG(dep.cockerels), 0) AS avg_cockerels
                    FROM cages c
                    LEFT JOIN daily_egg_production dep ON c.id = dep.cage_id
                    GROUP BY c.id, c.cage_name
                    ORDER BY c.cage_name ASC";
    $ps_cages = [];
    if ($pscg_res = $mysqli->query($ps_cage_sql)) {
        $ps_cages = $pscg_res->fetch_all(MYSQLI_ASSOC);
    }

    // 2.4 Annex 01 Monthly Aggregated Register Summary
    $ps_annex_sql = "SELECT DATE_FORMAT(dep.collection_date, '%Y-%m') AS report_month,
                            COUNT(dep.id) AS total_days_logged,
                            IFNULL(AVG(dep.pullets), 0) AS avg_pullets,
                            IFNULL(AVG(dep.cockerels), 0) AS avg_cockerels,
                            IFNULL(SUM(dep.hatchable_eggs), 0) AS sum_hatchable,
                            IFNULL(SUM(dep.hatchable_eggs_kg), 0) AS sum_hatchable_kg,
                            IFNULL(SUM(dep.table_eggs), 0) AS sum_table,
                            IFNULL(SUM(dep.table_eggs_kg), 0) AS sum_table_kg,
                            IFNULL(SUM(dep.cracked_eggs), 0) AS sum_cracked,
                            IFNULL(SUM(dep.cracked_eggs_kg), 0) AS sum_cracked_kg,
                            IFNULL(SUM(dep.total_eggs), 0) AS sum_total_eggs,
                            IFNULL(SUM(dep.total_eggs_kg), 0) AS sum_total_kg,
                            IFNULL(SUM(dep.eggs_loaded), 0) AS sum_eggs_loaded,
                            IFNULL(SUM(dep.hatched_eggs), 0) AS sum_hatched_eggs,
                            IFNULL(AVG(dep.hatchability_percentage), 0) AS avg_hatchability
                     FROM daily_egg_production dep
                     GROUP BY DATE_FORMAT(dep.collection_date, '%Y-%m')
                     ORDER BY report_month DESC";
    $ps_monthly_annex = [];
    if ($psa_res = $mysqli->query($ps_annex_sql)) {
        $ps_monthly_annex = $psa_res->fetch_all(MYSQLI_ASSOC);
    }

    // 2.5 Egg Sales & Returns Summary
    $ps_sales_sql = "SELECT es.*, u.full_name AS recorded_by
                     FROM daily_egg_sales es
                     LEFT JOIN users u ON es.user_id = u.id
                     ORDER BY es.sale_date DESC";
    $ps_sales = [];
    if ($pss_res = $mysqli->query($ps_sales_sql)) {
        $ps_sales = $pss_res->fetch_all(MYSQLI_ASSOC);
    }

    // Primary record set based on sub-tab
    if ($ps_subtab === 'flocks') {
        $report_records = $ps_flocks;
    } elseif ($ps_subtab === 'cages') {
        $report_records = $ps_cages;
    } elseif ($ps_subtab === 'annex01') {
        $report_records = $ps_monthly_annex;
    } elseif ($ps_subtab === 'sales') {
        $report_records = $ps_sales;
    } else {
        $report_records = $ps_collections;
    }

    // Parent stock specific KPIs
    $ps_kpi_flocks = count($ps_flocks);
    $ps_kpi_birds = array_sum(array_column($ps_flocks, 'current_count'));
    $ps_kpi_total_eggs = array_sum(array_column($ps_collections, 'total_eggs'));
    $ps_kpi_total_kg = array_sum(array_column($ps_collections, 'total_eggs_kg'));
    $ps_kpi_hatchable = array_sum(array_column($ps_collections, 'hatchable_eggs'));
    $ps_kpi_table = array_sum(array_column($ps_collections, 'table_eggs'));
    $ps_kpi_cracked = array_sum(array_column($ps_collections, 'cracked_eggs'));
    $ps_kpi_hatchability = count($ps_collections) > 0 ? (array_sum(array_column($ps_collections, 'hatchability_percentage')) / count($ps_collections)) : 0;
}

// 3. Hatchery Register
elseif ($current_view === 'hatchery') {
    $hr_sql = "SELECT hr.*, hb.batch_date, hb.total_collected, hb.chicks_hatched AS batch_hatched, u.full_name AS operator_name
               FROM hatchery_register hr
               LEFT JOIN hatchery_batches hb ON hr.batch_id = hb.id
               LEFT JOIN users u ON hb.user_id = u.id";
    if ($selected_farm_id > 0) {
        $hr_sql .= " WHERE hb.farm_id = " . intval($selected_farm_id);
    } elseif (!empty($district_farm_ids) && $is_district_level_dd) {
        $hr_sql .= " WHERE (hb.farm_id IN (" . implode(',', array_map('intval', $district_farm_ids)) . ") OR hb.farm_id IS NULL)";
    }
    $hr_sql .= " ORDER BY hr.record_date DESC";
    if ($hr_res = $mysqli->query($hr_sql)) {
        $report_records = $hr_res->fetch_all(MYSQLI_ASSOC);
    }
}

// 4. Chick Distribution
elseif ($current_view === 'chick_dist') {
    $cd_sql = "SELECT id, record_date, 'Day-Old Chick' AS chick_type, no_of_chicks_produced, sent_to_place, no_of_chicks_sent, price_per_chick, total_amount, created_at 
               FROM day_old_chicks_distribution
               UNION ALL
               SELECT id, record_date, 'Month-Old Chick' AS chick_type, no_of_chicks_produced, sent_to_place, no_of_chicks_sent, price_per_chick, total_amount, created_at 
               FROM month_old_chicks_distribution
               ORDER BY record_date DESC";
    if ($cd_res = $mysqli->query($cd_sql)) {
        $report_records = $cd_res->fetch_all(MYSQLI_ASSOC);
    }
}

// 5. Feed Management
elseif ($current_view === 'feed') {
    $feed_sql = "SELECT id, distribution_date AS record_date, batch_no, feed_type, no_of_chicks, amount_needed_kg, amount_distributed_kg, remarks, created_at
                 FROM daily_feed_distribution
                 ORDER BY distribution_date DESC";
    if ($feed_res = $mysqli->query($feed_sql)) {
        $report_records = $feed_res->fetch_all(MYSQLI_ASSOC);
    }
}

// 6. Sales of Eggs
elseif ($current_view === 'egg_sales') {
    $es_sql = "SELECT es.*, u.full_name AS recorded_by
               FROM daily_egg_sales es
               LEFT JOIN users u ON es.user_id = u.id
               ORDER BY es.sale_date DESC";
    if ($es_res = $mysqli->query($es_sql)) {
        $report_records = $es_res->fetch_all(MYSQLI_ASSOC);
    }
}

// 7. Farm Produce Register (Annex 6)
elseif ($current_view === 'produce') {
    $pr_sql = "SELECT pr.*, fc.commodity_name, fc.unit_of_measure, u.full_name AS recorded_by
               FROM farm_produce_register_annex6 pr
               LEFT JOIN farm_commodities fc ON pr.commodity_id = fc.id
               LEFT JOIN users u ON pr.user_id = u.id
               ORDER BY pr.record_date DESC";
    if ($pr_res = $mysqli->query($pr_sql)) {
        $report_records = $pr_res->fetch_all(MYSQLI_ASSOC);
    }
}

// 8. Livestock Register & Disposals
elseif ($current_view === 'livestock') {
    $ls_sql = "SELECT ad.*, u.full_name AS recorded_by
               FROM animal_disposal_register ad
               LEFT JOIN users u ON ad.user_id = u.id
               ORDER BY ad.disposal_date DESC";
    if ($ls_res = $mysqli->query($ls_sql)) {
        $report_records = $ls_res->fetch_all(MYSQLI_ASSOC);
    }
}

// 9. Drug Register (Annex 5)
elseif ($current_view === 'drugs') {
    $dr_sql = "SELECT dr.*, di.item_name, di.unit_of_measure, u.full_name AS recorded_by
               FROM farm_drug_register_annex5 dr
               LEFT JOIN farm_drug_items di ON dr.item_id = di.id
               LEFT JOIN users u ON dr.user_id = u.id
               ORDER BY dr.record_date DESC";
    if ($dr_res = $mysqli->query($dr_sql)) {
        $report_records = $dr_res->fetch_all(MYSQLI_ASSOC);
    }
}

// 10. Fuel Register
elseif ($current_view === 'fuel') {
    $fr_sql = "SELECT fr.*, fi.item_name, fi.unit_of_measure, u.full_name AS recorded_by
               FROM farm_fuel_register fr
               LEFT JOIN farm_fuel_items fi ON fr.item_id = fi.id
               LEFT JOIN users u ON fr.user_id = u.id
               ORDER BY fr.record_date DESC";
    if ($fr_res = $mysqli->query($fr_sql)) {
        $report_records = $fr_res->fetch_all(MYSQLI_ASSOC);
    }
}

// 11. Farm Financial Accounts
elseif ($current_view === 'accounts') {
    $fa_sql = "SELECT * FROM farm_accounts ORDER BY transaction_date DESC";
    if ($fa_res = $mysqli->query($fa_sql)) {
        $report_records = $fa_res->fetch_all(MYSQLI_ASSOC);
    }
}

$active_record_count = count($report_records);
?>

<!-- DataTables + Buttons CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<!-- Regional Farms Master Hub CSS -->
<link rel="stylesheet" href="../../../assets/css/regional_farms.css">

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4 pb-5">
        
        <!-- Header & District Context Bar -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-warning-subtle text-dark border border-warning px-2 py-1 rounded-pill small fw-bold">
                        <i class="bi bi-diagram-3-fill me-1 text-warning"></i> Regional Farms Hub
                    </span>
                    <span class="text-muted small">/</span>
                    <span class="text-muted small fw-medium"><?= htmlspecialchars($district_name) ?> District Jurisdiction</span>
                </div>
                <h2 class="text-dark fw-bold mb-0">Regional Livestock Farms &amp; Operations Master Summary</h2>
                <p class="text-muted small mb-0 mt-1">Consolidated livestock breeding stations, parent stock flocks, hatcheries, and produce registers for <strong><?= htmlspecialchars($district_name) ?></strong>.</p>
            </div>
            
            <div class="d-flex align-items-center flex-wrap gap-2">
                <!-- Farm Selector Filter -->
                <form method="GET" action="" class="d-flex align-items-center gap-2 m-0">
                    <input type="hidden" name="view" value="<?= htmlspecialchars($current_view) ?>">
                    <div class="input-group input-group-sm shadow-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-funnel-fill text-warning"></i>
                        </span>
                        <select name="farm_id" class="form-select form-select-sm border-start-0 ps-0 fw-semibold" onchange="this.form.submit()" style="min-width: 220px;">
                            <option value="0">All District Farms (<?= count($district_farms) ?> Facilities)</option>
                            <?php foreach ($district_farms as $df): ?>
                                <option value="<?= $df['id'] ?>" <?= $selected_farm_id == $df['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($df['farm_name']) ?> (<?= htmlspecialchars($df['location']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>

                <?php if ($selected_farm_id > 0): ?>
                    <a href="?view=<?= urlencode($current_view) ?>&farm_id=0" class="btn btn-outline-secondary btn-sm shadow-sm" title="Show all district farms">
                        <i class="bi bi-x-circle me-1"></i> Clear Filter
                    </a>
                <?php endif; ?>

                <a href="../../../dashboard.php" class="btn btn-secondary btn-sm shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Metric KPI Cards Strip -->
        <div class="row g-3 mb-4">
            <!-- 1. District Regional Farms -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card card-modern kpi-stat-card kpi-gold h-100 p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">District Farm Stations</span>
                            <h3 class="fw-bold text-dark mt-1 mb-0"><?= number_format(count($district_farms)) ?></h3>
                            <small class="text-warning fw-medium"><i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($district_name) ?> Scope</small>
                        </div>
                        <div class="kpi-icon-wrapper bg-warning-subtle text-warning">
                            <i class="bi bi-buildings"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Parent Stock Flocks & Birds -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card card-modern kpi-stat-card kpi-info h-100 p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Parent Stock Birds</span>
                            <h3 class="fw-bold text-dark mt-1 mb-0"><?= number_format($flocks_birds) ?></h3>
                            <small class="text-info fw-medium"><?= number_format($flocks_cnt) ?> Active Flocks</small>
                        </div>
                        <div class="kpi-icon-wrapper bg-info-subtle text-info">
                            <i class="bi bi-feather"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Egg Production & Harvest -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card card-modern kpi-stat-card kpi-orange h-100 p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Egg Production</span>
                            <h3 class="fw-bold text-dark mt-1 mb-0"><?= number_format($total_eggs_collected) ?></h3>
                            <small class="text-danger fw-medium">Hatchable &amp; Table Harvest</small>
                        </div>
                        <div class="kpi-icon-wrapper bg-danger-subtle text-danger">
                            <i class="bi bi-egg-fried"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Realized Farm Sales & Produce Revenue -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card card-modern kpi-stat-card kpi-green h-100 p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Realized Revenue</span>
                            <h3 class="fw-bold text-dark mt-1 mb-0">LKR <?= number_format($total_revenue_all, 2) ?></h3>
                            <small class="text-success fw-medium"><i class="bi bi-cash-coin me-1"></i>Sales &amp; Produce Total</small>
                        </div>
                        <div class="kpi-icon-wrapper bg-success-subtle text-success">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Primary Farm Sub-Modules Navigation Strip -->
        <div class="card card-modern mb-4">
            <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap-fill text-warning me-2"></i>Regional Farm Sub-Modules &amp; Registers</h6>
                <small class="text-muted d-none d-md-inline">Select an operational farm module to view consolidated records</small>
            </div>
            <div class="card-body px-4 pt-0">
                <div class="row row-cols-2 row-cols-md-4 row-cols-lg-4 g-2">
                    
                    <!-- 1. Farms Directory -->
                    <div class="col">
                        <a href="?view=farms_list&farm_id=<?= $selected_farm_id ?>" class="btn-farm-action <?= $current_view === 'farms_list' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #b08723, #c99c2e);">
                            <i class="bi bi-buildings"></i>
                            <span class="text-center">Farms Directory</span>
                        </a>
                    </div>

                    <!-- 2. Parent Stock Operations -->
                    <div class="col">
                        <a href="?view=parent_stock&farm_id=<?= $selected_farm_id ?>" class="btn-farm-action <?= $current_view === 'parent_stock' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #820100, #a30100);">
                            <i class="bi bi-feather"></i>
                            <span class="text-center">Parent Stock</span>
                        </a>
                    </div>

                    <!-- 3. Hatchery Register -->
                    <div class="col">
                        <a href="?view=hatchery&farm_id=<?= $selected_farm_id ?>" class="btn-farm-action <?= $current_view === 'hatchery' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #e65100, #f57c00);">
                            <i class="bi bi-egg-fill"></i>
                            <span class="text-center">Hatchery Register</span>
                        </a>
                    </div>

                    <!-- 4. Chick Distribution -->
                    <div class="col">
                        <a href="?view=chick_dist&farm_id=<?= $selected_farm_id ?>" class="btn-farm-action <?= $current_view === 'chick_dist' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #f57f17, #fbc02d); color: #212529 !important;">
                            <i class="bi bi-send-check-fill text-dark"></i>
                            <span class="text-center text-dark">Chick Distribution</span>
                        </a>
                    </div>

                    <!-- 5. Feed Management -->
                    <div class="col">
                        <a href="?view=feed&farm_id=<?= $selected_farm_id ?>" class="btn-farm-action <?= $current_view === 'feed' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #2e7d32, #388e3c);">
                            <i class="bi bi-bag-check-fill"></i>
                            <span class="text-center">Feed Management</span>
                        </a>
                    </div>

                    <!-- 6. Sales of Eggs -->
                    <div class="col">
                        <a href="?view=egg_sales&farm_id=<?= $selected_farm_id ?>" class="btn-farm-action <?= $current_view === 'egg_sales' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #0288d1, #039be5);">
                            <i class="bi bi-cart-check-fill"></i>
                            <span class="text-center">Sales of Eggs</span>
                        </a>
                    </div>

                    <!-- 7. Produce Register (Perishables) -->
                    <div class="col">
                        <a href="?view=produce&farm_id=<?= $selected_farm_id ?>" class="btn-farm-action <?= $current_view === 'produce' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #00796b, #00897b);">
                            <i class="bi bi-tree-fill"></i>
                            <span class="text-center">Produce Register</span>
                        </a>
                    </div>

                    <!-- 8. Livestock & Disposals -->
                    <div class="col">
                        <a href="?view=livestock&farm_id=<?= $selected_farm_id ?>" class="btn-farm-action <?= $current_view === 'livestock' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #455a64, #546e7a);">
                            <i class="bi bi-shield-shaded"></i>
                            <span class="text-center">Livestock Registers</span>
                        </a>
                    </div>

                    <!-- 9. Drugs & Supplies -->
                    <div class="col">
                        <a href="?view=drugs&farm_id=<?= $selected_farm_id ?>" class="btn-farm-action <?= $current_view === 'drugs' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #7b1fa2, #8e24aa);">
                            <i class="bi bi-capsule"></i>
                            <span class="text-center">Drugs &amp; Supplies</span>
                        </a>
                    </div>

                    <!-- 10. Fuel Register -->
                    <div class="col">
                        <a href="?view=fuel&farm_id=<?= $selected_farm_id ?>" class="btn-farm-action <?= $current_view === 'fuel' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #c2185b, #d81b60);">
                            <i class="bi bi-fuel-pump-fill"></i>
                            <span class="text-center">Fuel Register</span>
                        </a>
                    </div>

                    <!-- 11. Farm Financial Accounts -->
                    <div class="col">
                        <a href="?view=accounts&farm_id=<?= $selected_farm_id ?>" class="btn-farm-action <?= $current_view === 'accounts' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #1565c0, #1e88e5);">
                            <i class="bi bi-journal-bookmark-fill"></i>
                            <span class="text-center">Farm Accounts</span>
                        </a>
                    </div>

                </div>
            </div>
        </div>

        <?php if ($current_view === 'parent_stock'): ?>
        <!-- Parent Stock Operations Module Functions Hub -->
        <div class="card card-modern mb-4 border-start border-4 border-danger shadow-sm">
            <div class="card-header bg-white py-3 px-4 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-feather text-danger me-2"></i>Parent Stock Operations &amp; Egg Register Functions</h6>
                    <small class="text-muted">Consolidated egg harvest logs, parent flocks inventory, cage matrix, and monthly Annex 01 register.</small>
                </div>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill small fw-bold">
                    <i class="bi bi-egg-fill me-1"></i> <?= number_format($ps_kpi_total_eggs) ?> Total Harvested
                </span>
            </div>
            
            <!-- Parent Stock Mini Metric Cards -->
            <div class="card-body px-4 pt-0 pb-3">
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded-3 bg-light border text-center">
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Flocks &amp; Bird Count</small>
                            <h5 class="fw-bold text-dark mt-1 mb-0"><?= number_format($ps_kpi_birds) ?> <span class="fs-6 text-muted font-monospace">(<?= $ps_kpi_flocks ?> Flocks)</span></h5>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded-3 bg-light border text-center">
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Egg Harvest &amp; Weight</small>
                            <h5 class="fw-bold text-primary mt-1 mb-0"><?= number_format($ps_kpi_total_eggs) ?> <span class="fs-6 text-muted font-monospace">(<?= number_format($ps_kpi_total_kg, 1) ?> Kg)</span></h5>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded-3 bg-light border text-center">
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Hatchable vs Table vs Cracked</small>
                            <h5 class="fw-bold text-success mt-1 mb-0"><?= number_format($ps_kpi_hatchable) ?> <span class="fs-6 text-muted font-monospace">/ <?= number_format($ps_kpi_table) ?> / <?= number_format($ps_kpi_cracked) ?></span></h5>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded-3 bg-light border text-center">
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Avg Hatchability Rate</small>
                            <h5 class="fw-bold text-warning mt-1 mb-0"><?= number_format($ps_kpi_hatchability, 1) ?>%</h5>
                        </div>
                    </div>
                </div>

                <!-- Sub-Navigation Pills for Parent Stock Functions -->
                <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                    <a href="?view=parent_stock&ps_tab=collections&farm_id=<?= $selected_farm_id ?>" class="btn btn-sm sub-stat-pill <?= ($ps_subtab === 'collections') ? 'btn-danger active-pill text-white' : 'btn-outline-danger' ?>">
                        <i class="bi bi-journal-text me-1"></i> Daily Egg Collections Log
                    </a>
                    <a href="?view=parent_stock&ps_tab=flocks&farm_id=<?= $selected_farm_id ?>" class="btn btn-sm sub-stat-pill <?= ($ps_subtab === 'flocks') ? 'btn-primary active-pill text-white' : 'btn-outline-primary' ?>">
                        <i class="bi bi-feather me-1"></i> Parent Flocks &amp; Bird Inventory
                    </a>
                    <a href="?view=parent_stock&ps_tab=cages&farm_id=<?= $selected_farm_id ?>" class="btn btn-sm sub-stat-pill <?= ($ps_subtab === 'cages') ? 'btn-success active-pill text-white' : 'btn-outline-success' ?>">
                        <i class="bi bi-grid-3x3 me-1"></i> Active Cages &amp; Infrastructure
                    </a>
                    <a href="?view=parent_stock&ps_tab=annex01&farm_id=<?= $selected_farm_id ?>" class="btn btn-sm sub-stat-pill <?= ($ps_subtab === 'annex01') ? 'btn-warning active-pill text-dark' : 'btn-outline-warning text-dark' ?>">
                        <i class="bi bi-calendar3-range me-1"></i> Annex 01 - Monthly Register Matrix
                    </a>
                    <a href="?view=parent_stock&ps_tab=sales&farm_id=<?= $selected_farm_id ?>" class="btn btn-sm sub-stat-pill <?= ($ps_subtab === 'sales') ? 'btn-info active-pill text-white' : 'btn-outline-info' ?>">
                        <i class="bi bi-cart-check me-1"></i> Egg Sales &amp; Returns Log
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Dynamic Data Section with DataTables & Toolbars -->
        <div class="card card-modern mb-4">
            <div class="card-header bg-white py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="m-0 fw-bold text-dark">
                        <?php if ($current_view === 'farms_list'): ?>
                            <i class="bi bi-buildings me-2 text-warning"></i>Regional Farm Stations &amp; Assigned Operations Officers
                        <?php elseif ($current_view === 'parent_stock'): ?>
                            <?php if ($ps_subtab === 'flocks'): ?>
                                <i class="bi bi-feather me-2 text-primary"></i>Parent Stock Flocks &amp; Bird Inventory
                            <?php elseif ($ps_subtab === 'cages'): ?>
                                <i class="bi bi-grid-3x3 me-2 text-success"></i>Active Cages &amp; Production Metrics
                            <?php elseif ($ps_subtab === 'annex01'): ?>
                                <i class="bi bi-calendar3-range me-2 text-warning"></i>Annex 01 - Monthly Consolidated Egg Register
                            <?php elseif ($ps_subtab === 'sales'): ?>
                                <i class="bi bi-cart-check me-2 text-info"></i>Egg Sales &amp; Commercial Realization
                            <?php else: ?>
                                <i class="bi bi-journal-text me-2 text-danger"></i>Daily Egg Collection Records Log
                            <?php endif; ?>
                        <?php elseif ($current_view === 'hatchery'): ?>
                            <i class="bi bi-egg-fill me-2 text-warning"></i>Hatchery Setting Batches &amp; Candling Discards
                        <?php elseif ($current_view === 'chick_dist'): ?>
                            <i class="bi bi-send-check-fill me-2 text-success"></i>Day-Old &amp; Month-Old Chicks Distribution Register
                        <?php elseif ($current_view === 'feed'): ?>
                            <i class="bi bi-bag-check-fill me-2 text-success"></i>Daily Chick Feed Allocation &amp; Mash Balance Log
                        <?php elseif ($current_view === 'egg_sales'): ?>
                            <i class="bi bi-cart-check-fill me-2 text-info"></i>Daily Egg Sales &amp; Revenue Realization
                        <?php elseif ($current_view === 'produce'): ?>
                            <i class="bi bi-tree-fill me-2 text-success"></i>Farm Perishable Produce Register (Annex 6)
                        <?php elseif ($current_view === 'livestock'): ?>
                            <i class="bi bi-shield-shaded me-2 text-secondary"></i>Livestock Disposals &amp; Species Registers
                        <?php elseif ($current_view === 'drugs'): ?>
                            <i class="bi bi-capsule me-2 text-primary"></i>Farm Drugs &amp; Veterinary Supplies Inventory (Annex 5)
                        <?php elseif ($current_view === 'fuel'): ?>
                            <i class="bi bi-fuel-pump-fill me-2 text-danger"></i>Farm Fuel &amp; Lubricants Register
                        <?php elseif ($current_view === 'accounts'): ?>
                            <i class="bi bi-journal-bookmark-fill me-2 text-primary"></i>Farm Income &amp; Expenditure Cash Flow Ledger
                        <?php endif; ?>
                    </h5>
                    <span class="badge bg-light text-dark border ms-1"><?= htmlspecialchars($active_farm_scope_name) ?></span>
                    <span class="badge bg-secondary-subtle text-secondary border"><?= $active_record_count ?> <?= $active_record_count === 1 ? 'Record' : 'Records' ?></span>
                </div>
                <div class="small text-muted">
                    <span class="badge bg-light text-muted border"><i class="bi bi-download me-1"></i>Export Data Toolbar</span>
                </div>
            </div>
            
            <div class="card-body px-4 py-3">
                <div class="table-responsive">
                    
                    <!-- 1. Farms Directory Table -->
                    <?php if ($current_view === 'farms_list'): ?>
                        <table id="farmsDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Farm Facility Name</th>
                                    <th>Location &amp; District</th>
                                    <th>Assigned Operations Officer</th>
                                    <th>Contact Details</th>
                                    <th class="text-center">Staff Count</th>
                                    <th class="text-center">Flocks Count</th>
                                    <th class="text-center">Flock Birds</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $f): ?>
                                    <tr>
                                        <td class="fw-bold text-dark">
                                            <i class="bi bi-building me-1 text-warning"></i><?= htmlspecialchars($f['farm_name']) ?>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-danger"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($f['location']) ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($f['officer_name'])): ?>
                                                <div class="fw-bold text-primary"><?= htmlspecialchars($f['officer_name']) ?></div>
                                                <small class="text-muted font-monospace"><?= htmlspecialchars($f['officer_username']) ?></small>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Central Operations Pool</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($f['officer_email'])): ?>
                                                <div><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($f['officer_email']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($f['officer_phone'])): ?>
                                                <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($f['officer_phone']) ?></small>
                                            <?php endif; ?>
                                            <?php if (empty($f['officer_email']) && empty($f['officer_phone'])): ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center font-monospace fw-bold"><?= (int)$f['total_farm_staff'] ?></td>
                                        <td class="text-center font-monospace text-primary fw-bold"><?= (int)$f['total_flocks'] ?></td>
                                        <td class="text-center font-monospace text-success fw-bold"><?= number_format($f['total_flock_birds']) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= !empty($f['is_active']) ? 'success' : 'danger' ?>">
                                                <?= !empty($f['is_active']) ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 2. Parent Stock Operations Hub Tables -->
                    <?php elseif ($current_view === 'parent_stock'): ?>
                        
                        <!-- 2.1 Daily Collections Sub-Tab -->
                        <?php if ($ps_subtab === 'collections'): ?>
                            <table id="farmsDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Collection Date</th>
                                        <th>Batch / Flock</th>
                                        <th>Cage Name</th>
                                        <th class="text-center">Pullets</th>
                                        <th class="text-center">Cockerels</th>
                                        <th class="text-center">Hatchable (No / Kg)</th>
                                        <th class="text-center">Table (No / Kg)</th>
                                        <th class="text-center">Cracked (No / Kg)</th>
                                        <th class="text-center">Total (No / Kg)</th>
                                        <th>Hatchery Destination</th>
                                        <th class="text-center">Hatchability %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_records as $rec): ?>
                                        <tr>
                                            <td><?= date('d M Y', strtotime($rec['collection_date'])) ?></td>
                                            <td>
                                                <span class="badge bg-primary-subtle text-primary border"><?= htmlspecialchars($rec['batch_name'] ?? 'Batch') ?></span>
                                            </td>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($rec['cage_name'] ?? 'Cage') ?></td>
                                            <td class="text-center font-monospace"><?= (int)$rec['pullets'] ?></td>
                                            <td class="text-center font-monospace"><?= (int)$rec['cockerels'] ?></td>
                                            <td class="text-center font-monospace text-success fw-bold">
                                                <?= number_format($rec['hatchable_eggs']) ?>
                                                <div class="small text-muted font-monospace"><?= number_format($rec['hatchable_eggs_kg'] ?? 0, 2) ?> Kg</div>
                                            </td>
                                            <td class="text-center font-monospace">
                                                <?= number_format($rec['table_eggs']) ?>
                                                <div class="small text-muted font-monospace"><?= number_format($rec['table_eggs_kg'] ?? 0, 2) ?> Kg</div>
                                            </td>
                                            <td class="text-center font-monospace text-danger">
                                                <?= number_format($rec['cracked_eggs']) ?>
                                                <div class="small text-muted font-monospace"><?= number_format($rec['cracked_eggs_kg'] ?? 0, 2) ?> Kg</div>
                                            </td>
                                            <td class="text-center font-monospace text-primary fw-bold">
                                                <?= number_format($rec['total_eggs']) ?>
                                                <div class="small fw-bold text-primary font-monospace"><?= number_format($rec['total_eggs_kg'] ?? 0, 2) ?> Kg</div>
                                            </td>
                                            <td><?= htmlspecialchars($rec['hatchery_name'] ?? '-') ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-<?= ($rec['hatchability_percentage'] >= 75) ? 'success' : 'warning text-dark' ?>">
                                                    <?= number_format($rec['hatchability_percentage'], 1) ?>%
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                        <!-- 2.2 Parent Flocks & Bird Inventory Sub-Tab -->
                        <?php elseif ($ps_subtab === 'flocks'): ?>
                            <table id="farmsDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Flock ID</th>
                                        <th>Flock Code / Batch</th>
                                        <th>Region</th>
                                        <th>Farm Facility</th>
                                        <th class="text-center">Current Bird Population</th>
                                        <th>Assigned Cages</th>
                                        <th>Managed By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_records as $rec): ?>
                                        <tr>
                                            <td class="font-monospace text-muted">#<?= $rec['id'] ?></td>
                                            <td class="fw-bold text-primary"><i class="bi bi-feather me-1"></i><?= htmlspecialchars($rec['flock_code']) ?></td>
                                            <td><?= htmlspecialchars($rec['region'] ?? 'General') ?></td>
                                            <td><i class="bi bi-building me-1 text-warning"></i><?= htmlspecialchars($rec['farm_name'] ?? 'Regional Farm') ?></td>
                                            <td class="text-center font-monospace text-success fw-bold"><?= number_format($rec['current_count']) ?> Birds</td>
                                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($rec['assigned_cages'] ?? 'All Cages') ?></span></td>
                                            <td><?= htmlspecialchars($rec['manager_name'] ?? 'Farm Manager') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                        <!-- 2.3 Active Cages Matrix Sub-Tab -->
                        <?php elseif ($ps_subtab === 'cages'): ?>
                            <table id="farmsDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Cage ID</th>
                                        <th>Cage Identifier</th>
                                        <th class="text-center">Collections Logged</th>
                                        <th class="text-center">Total Eggs Produced</th>
                                        <th class="text-center">Total Egg Weight (Kg)</th>
                                        <th class="text-center">Avg Pullets</th>
                                        <th class="text-center">Avg Cockerels</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_records as $rec): ?>
                                        <tr>
                                            <td class="font-monospace text-muted">#<?= $rec['id'] ?></td>
                                            <td class="fw-bold text-dark"><i class="bi bi-grid-3x3 me-1 text-success"></i><?= htmlspecialchars($rec['cage_name']) ?></td>
                                            <td class="text-center font-monospace"><?= number_format($rec['total_logs']) ?> Logs</td>
                                            <td class="text-center font-monospace text-primary fw-bold"><?= number_format($rec['total_eggs_produced']) ?> Eggs</td>
                                            <td class="text-center font-monospace text-success fw-bold"><?= number_format($rec['total_eggs_weight'], 2) ?> Kg</td>
                                            <td class="text-center font-monospace"><?= number_format($rec['avg_pullets'], 0) ?></td>
                                            <td class="text-center font-monospace"><?= number_format($rec['avg_cockerels'], 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                        <!-- 2.4 Annex 01 Monthly Aggregated Matrix Sub-Tab -->
                        <?php elseif ($ps_subtab === 'annex01'): ?>
                            <table id="farmsDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Production Month</th>
                                        <th class="text-center">Days Logged</th>
                                        <th class="text-center">Avg Pullets / Cockerels</th>
                                        <th class="text-center">Hatchable Eggs</th>
                                        <th class="text-center">Table Eggs</th>
                                        <th class="text-center">Cracked Eggs</th>
                                        <th class="text-center">Total Harvest (No / Kg)</th>
                                        <th class="text-center">Eggs Loaded</th>
                                        <th class="text-center">Hatched Chicks</th>
                                        <th class="text-center">Avg Hatchability</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_records as $rec): ?>
                                        <tr>
                                            <td class="fw-bold text-primary"><i class="bi bi-calendar-event me-1"></i><?= date('F Y', strtotime($rec['report_month'] . '-01')) ?></td>
                                            <td class="text-center font-monospace"><?= (int)$rec['total_days_logged'] ?> Days</td>
                                            <td class="text-center font-monospace"><?= number_format($rec['avg_pullets'], 0) ?> / <?= number_format($rec['avg_cockerels'], 0) ?></td>
                                            <td class="text-center font-monospace text-success fw-bold"><?= number_format($rec['sum_hatchable']) ?> (<?= number_format($rec['sum_hatchable_kg'], 1) ?> Kg)</td>
                                            <td class="text-center font-monospace"><?= number_format($rec['sum_table']) ?> (<?= number_format($rec['sum_table_kg'], 1) ?> Kg)</td>
                                            <td class="text-center font-monospace text-danger"><?= number_format($rec['sum_cracked']) ?></td>
                                            <td class="text-center font-monospace text-dark fw-bold">
                                                <?= number_format($rec['sum_total_eggs']) ?>
                                                <div class="small text-muted"><?= number_format($rec['sum_total_kg'], 2) ?> Kg</div>
                                            </td>
                                            <td class="text-center font-monospace text-info fw-bold"><?= number_format($rec['sum_eggs_loaded']) ?></td>
                                            <td class="text-center font-monospace text-success fw-bold"><?= number_format($rec['sum_hatched_eggs']) ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-<?= ($rec['avg_hatchability'] >= 75) ? 'success' : 'warning text-dark' ?>">
                                                    <?= number_format($rec['avg_hatchability'], 1) ?>%
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                        <!-- 2.5 Egg Sales & Commercial Realization Sub-Tab -->
                        <?php elseif ($ps_subtab === 'sales'): ?>
                            <table id="farmsDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sale Date</th>
                                        <th class="text-center">Table Eggs Qty</th>
                                        <th class="text-end">Table Unit Rate</th>
                                        <th class="text-end">Table Total (LKR)</th>
                                        <th class="text-center">Cracked Qty</th>
                                        <th class="text-end">Cracked Rate</th>
                                        <th class="text-end">Cracked Total (LKR)</th>
                                        <th class="text-end">Grand Total Sales Realized</th>
                                        <th>Recorded By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_records as $rec): ?>
                                        <tr>
                                            <td><?= date('d M Y', strtotime($rec['sale_date'])) ?></td>
                                            <td class="text-center font-monospace"><?= number_format($rec['table_eggs_no']) ?></td>
                                            <td class="text-end font-monospace">LKR <?= number_format($rec['table_eggs_unit_price'], 2) ?></td>
                                            <td class="text-end font-monospace text-primary fw-bold">LKR <?= number_format($rec['table_eggs_total_sales'], 2) ?></td>
                                            <td class="text-center font-monospace text-danger"><?= number_format($rec['cracked_eggs_no']) ?></td>
                                            <td class="text-end font-monospace">LKR <?= number_format($rec['cracked_eggs_unit_price'], 2) ?></td>
                                            <td class="text-end font-monospace text-danger">LKR <?= number_format($rec['cracked_eggs_total_sales'], 2) ?></td>
                                            <td class="text-end font-monospace text-success fw-bold">LKR <?= number_format($rec['grand_total_sales'], 2) ?></td>
                                            <td><?= htmlspecialchars($rec['recorded_by'] ?? 'Farm Cashier') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                    <!-- 3. Hatchery Register Table -->
                    <?php elseif ($current_view === 'hatchery'): ?>
                        <table id="farmsDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Record Date</th>
                                    <th class="text-center">Eggs Loaded</th>
                                    <th>Candling Date</th>
                                    <th class="text-center">Candling Discard</th>
                                    <th>Hatching Date</th>
                                    <th class="text-center">Hatched Eggs</th>
                                    <th class="text-center">Good Chicks</th>
                                    <th class="text-center">Hatchability %</th>
                                    <th>Operator / Remark</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($rec['record_date'])) ?></td>
                                        <td class="text-center font-monospace fw-bold text-primary"><?= number_format($rec['no_of_eggs_loaded']) ?></td>
                                        <td><?= !empty($rec['date_of_candling']) ? date('d M Y', strtotime($rec['date_of_candling'])) : '-' ?></td>
                                        <td class="text-center font-monospace text-danger"><?= number_format($rec['discarded_during_candling']) ?></td>
                                        <td><?= !empty($rec['date_of_hatching']) ? date('d M Y', strtotime($rec['date_of_hatching'])) : '-' ?></td>
                                        <td class="text-center font-monospace text-success fw-bold"><?= number_format($rec['no_of_hatched_eggs']) ?></td>
                                        <td class="text-center font-monospace text-dark fw-bold"><?= number_format($rec['no_of_good_chicks']) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= ($rec['hatching_percentage'] >= 75) ? 'success' : 'warning text-dark' ?>">
                                                <?= number_format($rec['hatching_percentage'], 1) ?>%
                                            </span>
                                        </td>
                                        <td><small class="text-muted"><?= htmlspecialchars($rec['remark'] ?? $rec['operator_name'] ?? '-') ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 4. Chick Distribution Table -->
                    <?php elseif ($current_view === 'chick_dist'): ?>
                        <table id="farmsDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Distribution Date</th>
                                    <th>Chick Category</th>
                                    <th class="text-center">Produced Qty</th>
                                    <th>Destination / Recipient</th>
                                    <th class="text-center">Chicks Sent</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Total Amount (LKR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($rec['record_date'])) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $rec['chick_type'] === 'Day-Old Chick' ? 'warning text-dark' : 'primary' ?>">
                                                <?= htmlspecialchars($rec['chick_type']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center font-monospace"><?= number_format($rec['no_of_chicks_produced']) ?></td>
                                        <td class="fw-semibold text-dark"><i class="bi bi-geo-alt me-1 text-danger"></i><?= htmlspecialchars($rec['sent_to_place']) ?></td>
                                        <td class="text-center font-monospace fw-bold text-primary"><?= number_format($rec['no_of_chicks_sent']) ?></td>
                                        <td class="text-end font-monospace">LKR <?= number_format($rec['price_per_chick'], 2) ?></td>
                                        <td class="text-end font-monospace text-success fw-bold">LKR <?= number_format($rec['total_amount'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 5. Feed Management Table -->
                    <?php elseif ($current_view === 'feed'): ?>
                        <table id="farmsDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Distribution Date</th>
                                    <th>Batch Reference</th>
                                    <th>Feed Mash Type</th>
                                    <th class="text-center">Chick Count</th>
                                    <th class="text-end">Amount Needed (Kg)</th>
                                    <th class="text-end">Distributed (Kg)</th>
                                    <th>Remarks / Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($rec['record_date'])) ?></td>
                                        <td class="font-monospace fw-bold"><?= htmlspecialchars($rec['batch_no']) ?></td>
                                        <td><span class="badge bg-success-subtle text-success border border-success"><?= htmlspecialchars($rec['feed_type']) ?></span></td>
                                        <td class="text-center font-monospace"><?= number_format($rec['no_of_chicks']) ?></td>
                                        <td class="text-end font-monospace"><?= number_format($rec['amount_needed_kg'], 2) ?> kg</td>
                                        <td class="text-end font-monospace text-primary fw-bold"><?= number_format($rec['amount_distributed_kg'], 2) ?> kg</td>
                                        <td><small class="text-muted"><?= htmlspecialchars($rec['remarks'] ?? '-') ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 6. Sales of Eggs Table -->
                    <?php elseif ($current_view === 'egg_sales'): ?>
                        <table id="farmsDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Sale Date</th>
                                    <th class="text-center">Table Eggs Qty</th>
                                    <th class="text-end">Table Unit Price</th>
                                    <th class="text-end">Table Total (LKR)</th>
                                    <th class="text-center">Cracked Eggs Qty</th>
                                    <th class="text-end">Cracked Unit Price</th>
                                    <th class="text-end">Cracked Total (LKR)</th>
                                    <th class="text-end">Grand Total Realized</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($rec['sale_date'])) ?></td>
                                        <td class="text-center font-monospace"><?= number_format($rec['table_eggs_no']) ?></td>
                                        <td class="text-end font-monospace">LKR <?= number_format($rec['table_eggs_unit_price'], 2) ?></td>
                                        <td class="text-end font-monospace text-primary fw-bold">LKR <?= number_format($rec['table_eggs_total_sales'], 2) ?></td>
                                        <td class="text-center font-monospace text-danger"><?= number_format($rec['cracked_eggs_no']) ?></td>
                                        <td class="text-end font-monospace">LKR <?= number_format($rec['cracked_eggs_unit_price'], 2) ?></td>
                                        <td class="text-end font-monospace text-danger">LKR <?= number_format($rec['cracked_eggs_total_sales'], 2) ?></td>
                                        <td class="text-end font-monospace text-success fw-bold">LKR <?= number_format($rec['grand_total_sales'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 7. Produce Register Table -->
                    <?php elseif ($current_view === 'produce'): ?>
                        <table id="farmsDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Record Date</th>
                                    <th>Commodity Name</th>
                                    <th>Plot / Field No</th>
                                    <th class="text-center">Received Qty</th>
                                    <th class="text-center">Issued Qty</th>
                                    <th class="text-center">Closing Stock</th>
                                    <th>Disposal Method</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Realized Sum (LKR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($rec['record_date'])) ?></td>
                                        <td class="fw-bold text-dark"><i class="bi bi-flower1 me-1 text-success"></i><?= htmlspecialchars($rec['commodity_name'] ?? 'Produce') ?></td>
                                        <td class="font-monospace text-primary"><?= htmlspecialchars($rec['plot_no'] ?? 'Plot A') ?></td>
                                        <td class="text-center font-monospace"><?= number_format($rec['received_qty'], 2) ?></td>
                                        <td class="text-center font-monospace text-danger"><?= number_format($rec['issued_qty'], 2) ?></td>
                                        <td class="text-center font-monospace fw-bold"><?= number_format($rec['closing_stock'], 2) ?> <?= htmlspecialchars($rec['unit_of_measure'] ?? 'Kg') ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($rec['disposal_method'] ?? 'Sold / Issued') ?></span></td>
                                        <td class="text-end font-monospace">LKR <?= number_format($rec['unit_price'], 2) ?></td>
                                        <td class="text-end font-monospace text-success fw-bold">LKR <?= number_format($rec['full_sum_realized'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 8. Livestock & Disposals Table -->
                    <?php elseif ($current_view === 'livestock'): ?>
                        <table id="farmsDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Disposal Date</th>
                                    <th>Livestock Species</th>
                                    <th>Voucher No</th>
                                    <th>How Disposed</th>
                                    <th class="text-center">Stud Bulls</th>
                                    <th class="text-center">Cows</th>
                                    <th class="text-center">Calves</th>
                                    <th class="text-center">Total Animals</th>
                                    <th class="text-end">Realized Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($rec['disposal_date'])) ?></td>
                                        <td><span class="badge bg-primary"><?= htmlspecialchars($rec['species']) ?></span></td>
                                        <td class="font-monospace"><?= htmlspecialchars($rec['voucher_no'] ?? '-') ?></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($rec['how_disposed_of']) ?></td>
                                        <td class="text-center font-monospace"><?= (int)$rec['stud_bulls'] ?></td>
                                        <td class="text-center font-monospace"><?= (int)$rec['cows'] ?></td>
                                        <td class="text-center font-monospace"><?= ((int)$rec['heifer_calves'] + (int)$rec['bull_calves']) ?></td>
                                        <td class="text-center font-monospace fw-bold text-primary"><?= (int)$rec['total_animals'] ?></td>
                                        <td class="text-end font-monospace text-success fw-bold">LKR <?= number_format($rec['amount_realized'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 9. Drug Register Table -->
                    <?php elseif ($current_view === 'drugs'): ?>
                        <table id="farmsDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Record Date</th>
                                    <th>Drug / Supply Item</th>
                                    <th>Party / Supplier Name</th>
                                    <th>Ref Doc No</th>
                                    <th>Expiry Date</th>
                                    <th class="text-center">Received Qty</th>
                                    <th class="text-center">Issued Qty</th>
                                    <th class="text-center">Balance Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($rec['record_date'])) ?></td>
                                        <td class="fw-bold text-dark"><i class="bi bi-capsule me-1 text-primary"></i><?= htmlspecialchars($rec['item_name'] ?? 'Drug Item') ?></td>
                                        <td><?= htmlspecialchars($rec['party_name'] ?? '-') ?></td>
                                        <td class="font-monospace"><small><?= htmlspecialchars($rec['ref_doc_no'] ?? '-') ?></small></td>
                                        <td><?= !empty($rec['exp_date']) ? date('d M Y', strtotime($rec['exp_date'])) : '-' ?></td>
                                        <td class="text-center font-monospace text-success"><?= number_format($rec['received_qty'], 2) ?></td>
                                        <td class="text-center font-monospace text-danger"><?= number_format($rec['issued_qty'], 2) ?></td>
                                        <td class="text-center font-monospace fw-bold text-primary"><?= number_format($rec['balance_qty'], 2) ?> <?= htmlspecialchars($rec['unit_of_measure'] ?? 'Units') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 10. Fuel Register Table -->
                    <?php elseif ($current_view === 'fuel'): ?>
                        <table id="farmsDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Record Date</th>
                                    <th>Fuel Commodity</th>
                                    <th>Party / Vehicle Reg</th>
                                    <th>Ref Doc No</th>
                                    <th class="text-center">Received Qty</th>
                                    <th class="text-center">Issued Qty</th>
                                    <th class="text-center">Tank Balance</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($rec['record_date'])) ?></td>
                                        <td class="fw-bold text-dark"><i class="bi bi-fuel-pump me-1 text-danger"></i><?= htmlspecialchars($rec['item_name'] ?? 'Diesel') ?></td>
                                        <td><?= htmlspecialchars($rec['party_name'] ?? 'Farm Station Tank') ?></td>
                                        <td class="font-monospace"><small><?= htmlspecialchars($rec['ref_doc_no'] ?? '-') ?></small></td>
                                        <td class="text-center font-monospace text-success"><?= number_format($rec['received_qty'], 2) ?></td>
                                        <td class="text-center font-monospace text-danger"><?= number_format($rec['issued_qty'], 2) ?></td>
                                        <td class="text-center font-monospace fw-bold text-primary"><?= number_format($rec['balance_qty'], 2) ?> L</td>
                                        <td><small class="text-muted"><?= htmlspecialchars($rec['remarks'] ?? '-') ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 11. Farm Financial Accounts Table -->
                    <?php elseif ($current_view === 'accounts'): ?>
                        <table id="farmsDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Transaction Date</th>
                                    <th>Voucher No</th>
                                    <th>Account Category</th>
                                    <th class="text-center">Transaction Type</th>
                                    <th>Description</th>
                                    <th>Cash Book Ref</th>
                                    <th class="text-end">Amount (LKR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $rec): ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($rec['transaction_date'])) ?></td>
                                        <td class="font-monospace fw-bold"><?= htmlspecialchars($rec['voucher_no']) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($rec['account_category']) ?></span></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= $rec['transaction_type'] === 'Income' ? 'success' : 'danger' ?>">
                                                <?= htmlspecialchars($rec['transaction_type']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($rec['description']) ?></td>
                                        <td><small class="text-muted"><?= htmlspecialchars($rec['cash_book_ref'] ?? '-') ?></small></td>
                                        <td class="text-end font-monospace fw-bold <?= $rec['transaction_type'] === 'Income' ? 'text-success' : 'text-danger' ?>">
                                            <?= $rec['transaction_type'] === 'Income' ? '+' : '-' ?> LKR <?= number_format($rec['amount'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                </div>
            </div>
        </div>

    </main>
</div>

<?php require_once '../../../includes/footer.php'; ?>

<!-- DataTables + Buttons JS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    $('#farmsDataTable').DataTable({
        dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"lip>',
        buttons: [
            {
                extend: 'copyHtml5',
                text: '<i class="bi bi-clipboard me-1"></i> Copy',
                className: 'btn btn-secondary btn-sm'
            },
            {
                extend: 'excelHtml5',
                text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                className: 'btn btn-success btn-sm',
                title: 'DAPH_Regional_Farms_<?= ucfirst($current_view) ?>_<?= preg_replace('/[^A-Za-z0-9]/', '_', $district_name) ?>'
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="bi bi-file-earmark-pdf me-1"></i> PDF',
                className: 'btn btn-danger btn-sm',
                orientation: 'landscape',
                pageSize: 'A4',
                title: 'DAPH - Regional Farms <?= strtoupper(str_replace('_', ' ', $current_view)) ?> - <?= $district_name ?> District'
            },
            {
                extend: 'print',
                text: '<i class="bi bi-printer me-1"></i> Print',
                className: 'btn btn-dark btn-sm'
            }
        ],
        pageLength: 15,
        order: [],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search records...",
            lengthMenu: "Show _MENU_ records",
            emptyTable: "No records found for the selected farm or filter criteria.",
            zeroRecords: "No matching records found."
        }
    });
});
</script>
