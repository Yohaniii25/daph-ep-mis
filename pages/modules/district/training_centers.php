<?php
// pages/modules/district/training_centers.php -> Master Training Centers Summary Dashboard for District Deputy Directors & Leadership
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_roles = ['district_dd', 'deputy_director_district', 'administrator', 'provincial_director', 'deputy_director_hq_1', 'deputy_director_hq_2'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied");
}

require_once '../../../config/db_connect.php';
require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';

// =========================================================================
// ROLE & JURISDICTION ISOLATION
// =========================================================================
$logged_role = $_SESSION['role'] ?? '';
$is_district_level_dd = in_array($logged_role, ['district_dd', 'deputy_director_district']);

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

// Training Center to District Mapping
$tc_district_mapping = [
    'uppuveli'     => 3, // Trincomalee
    'uppuweli'     => 3, // Trincomalee
    'kallady'      => 2, // Batticaloa
    'kanchirankuda'=> 1  // Ampara
];

// Fetch all centers
$centers_sql = "SELECT tc.*, 
                       u.id AS officer_id, u.full_name AS officer_name, u.username AS officer_username, u.email AS officer_email, u.phone AS officer_phone,
                       (SELECT COUNT(*) FROM training_advanced_programmes tap WHERE tap.training_center_id = tc.id) AS total_programmes,
                       (SELECT IFNULL(SUM(tir.amount), 0) FROM training_income_receipts tir WHERE tir.training_center_id = tc.id) AS total_revenue,
                       (SELECT IFNULL(SUM(tpr.full_sum_realized), 0) FROM training_produce_register tpr WHERE tpr.training_center_id = tc.id) AS total_produce_revenue
                FROM training_centers tc
                LEFT JOIN users u ON (u.training_center_id = tc.id OR u.training_center_location LIKE tc.location) AND u.role = 'training_officer' AND u.is_active = 1
                ORDER BY tc.id ASC";

$all_training_centers = [];
$tc_query_res = $mysqli->query($centers_sql);
if ($tc_query_res) {
    while ($row = $tc_query_res->fetch_assoc()) {
        $loc = strtolower(trim($row['location'] ?? ''));
        $c_dist_id = $tc_district_mapping[$loc] ?? 0;
        $row['mapped_district_id'] = $c_dist_id;
        $row['district_name'] = ($c_dist_id === 1) ? 'Ampara' : (($c_dist_id === 2) ? 'Batticaloa' : (($c_dist_id === 3) ? 'Trincomalee' : 'Provincial'));
        $all_training_centers[] = $row;
    }
}

// Filter training centers by jurisdiction if viewing as District DD
$available_centers = [];
$district_tc_ids = [];
foreach ($all_training_centers as $c) {
    if ($is_district_level_dd) {
        if ($c['mapped_district_id'] == $district_id) {
            $available_centers[] = $c;
            $district_tc_ids[] = $c['id'];
        }
    } else {
        $available_centers[] = $c;
        $district_tc_ids[] = $c['id'];
    }
}

// Center filter param
$selected_center_id = isset($_GET['center_id']) ? intval($_GET['center_id']) : 0;
if ($selected_center_id > 0 && !in_array($selected_center_id, array_column($available_centers, 'id')) && $is_district_level_dd) {
    $selected_center_id = 0;
}

$active_center_scope_name = "All " . htmlspecialchars($district_name) . " Training Centers";
if ($selected_center_id > 0) {
    foreach ($available_centers as $ac) {
        if ($ac['id'] == $selected_center_id) {
            $active_center_scope_name = $ac['center_name'] . ' (' . $ac['location'] . ')';
            break;
        }
    }
}

// Active View & Sub-Tabs
$current_view = isset($_GET['view']) ? trim($_GET['view']) : 'centers_list';
$allowed_views = ['centers_list', 'programmes', 'monthly_income', 'produce', 'counter_foilage'];
if (!in_array($current_view, $allowed_views)) {
    $current_view = 'centers_list';
}

// =========================================================================
// KPI SUMMARY METRICS COMPUTATION
// =========================================================================
$kpi_total_centers = count($available_centers);

$tc_id_filter_sql = "";
if ($selected_center_id > 0) {
    $tc_id_filter_sql = " WHERE training_center_id = " . intval($selected_center_id);
} elseif (!empty($district_tc_ids) && $is_district_level_dd) {
    $tc_id_filter_sql = " WHERE training_center_id IN (" . implode(',', array_map('intval', $district_tc_ids)) . ")";
}

// Total Programmes
$kpi_total_programmes = 0;
$prog_sql = "SELECT COUNT(*) AS total FROM training_advanced_programmes" . $tc_id_filter_sql;
if ($pr_res = $mysqli->query($prog_sql)) {
    $kpi_total_programmes = (int)($pr_res->fetch_assoc()['total'] ?? 0);
}

// Total Income Receipts
$kpi_total_income = 0.00;
$inc_sql = "SELECT IFNULL(SUM(amount), 0) AS total FROM training_income_receipts" . $tc_id_filter_sql;
if ($in_res = $mysqli->query($inc_sql)) {
    $kpi_total_income = (float)($in_res->fetch_assoc()['total'] ?? 0);
}

// Total Produce Sales
$kpi_total_produce = 0.00;
$prd_sql = "SELECT IFNULL(SUM(full_sum_realized), 0) AS total FROM training_produce_register" . $tc_id_filter_sql;
if ($prd_res = $mysqli->query($prd_sql)) {
    $kpi_total_produce = (float)($prd_res->fetch_assoc()['total'] ?? 0);
}

// Combined Total Realized
$kpi_grand_revenue = $kpi_total_income + $kpi_total_produce;

// =========================================================================
// DATASET FETCHING PER ACTIVE VIEW
// =========================================================================
$report_records = [];

// 1. Centers Directory
if ($current_view === 'centers_list') {
    $report_records = $available_centers;
    if ($selected_center_id > 0) {
        $report_records = array_values(array_filter($available_centers, fn($c) => $c['id'] == $selected_center_id));
    }
}

// 2. Advanced Programmes
elseif ($current_view === 'programmes') {
    $ap_sql = "SELECT tap.*, tc.center_name, tc.location
               FROM training_advanced_programmes tap
               LEFT JOIN training_centers tc ON tap.training_center_id = tc.id";
    if ($selected_center_id > 0) {
        $ap_sql .= " WHERE tap.training_center_id = " . intval($selected_center_id);
    } elseif (!empty($district_tc_ids) && $is_district_level_dd) {
        $ap_sql .= " WHERE tap.training_center_id IN (" . implode(',', array_map('intval', $district_tc_ids)) . ")";
    }
    $ap_sql .= " ORDER BY tap.date DESC, tap.id DESC";
    if ($ap_res = $mysqli->query($ap_sql)) {
        $report_records = $ap_res->fetch_all(MYSQLI_ASSOC);
    }
}

// 3. Monthly Income Summary
elseif ($current_view === 'monthly_income') {
    $inc_subtab = isset($_GET['inc_tab']) ? trim($_GET['inc_tab']) : 'daily';

    // 3.1 Daily Receipts Log
    $ir_sql = "SELECT tir.*, tc.center_name, tc.location
               FROM training_income_receipts tir
               LEFT JOIN training_centers tc ON tir.training_center_id = tc.id";
    if ($selected_center_id > 0) {
        $ir_sql .= " WHERE tir.training_center_id = " . intval($selected_center_id);
    } elseif (!empty($district_tc_ids) && $is_district_level_dd) {
        $ir_sql .= " WHERE tir.training_center_id IN (" . implode(',', array_map('intval', $district_tc_ids)) . ")";
    }
    $ir_sql .= " ORDER BY tir.receipt_date DESC, tir.id DESC";
    $inc_receipts = [];
    if ($ir_res = $mysqli->query($ir_sql)) {
        $inc_receipts = $ir_res->fetch_all(MYSQLI_ASSOC);
    }

    // 3.2 Monthly Financial Income Matrix
    $mat_sql = "SELECT DATE_FORMAT(receipt_date, '%Y-%m') AS report_month,
                       COUNT(id) AS total_receipts,
                       IFNULL(SUM(CASE WHEN category = 'accommodation' THEN amount ELSE 0 END), 0) AS cat_accommodation,
                       IFNULL(SUM(CASE WHEN category = 'hall_charge' THEN amount ELSE 0 END), 0) AS cat_hall,
                       IFNULL(SUM(CASE WHEN category = 'usage_multimedia' THEN amount ELSE 0 END), 0) AS cat_multimedia,
                       IFNULL(SUM(CASE WHEN category = 'usage_sound_system' THEN amount ELSE 0 END), 0) AS cat_sound,
                       IFNULL(SUM(CASE WHEN category = 'sales_grass' THEN amount ELSE 0 END), 0) AS cat_grass,
                       IFNULL(SUM(CASE WHEN category = 'sales_banana' THEN amount ELSE 0 END), 0) AS cat_banana,
                       IFNULL(SUM(CASE WHEN category = 'sales_vegetable' THEN amount ELSE 0 END), 0) AS cat_vegetable,
                       IFNULL(SUM(CASE WHEN category = 'sales_coconut' THEN amount ELSE 0 END), 0) AS cat_coconut,
                       IFNULL(SUM(CASE WHEN category = 'sales_bag' THEN amount ELSE 0 END), 0) AS cat_bag,
                       IFNULL(SUM(CASE WHEN category = 'sales_tamarind' THEN amount ELSE 0 END), 0) AS cat_tamarind,
                       IFNULL(SUM(CASE WHEN category = 'sales_pasture_cuttings' THEN amount ELSE 0 END), 0) AS cat_pasture,
                       IFNULL(SUM(amount), 0) AS month_grand_total
                FROM training_income_receipts";
    if ($selected_center_id > 0) {
        $mat_sql .= " WHERE training_center_id = " . intval($selected_center_id);
    } elseif (!empty($district_tc_ids) && $is_district_level_dd) {
        $mat_sql .= " WHERE training_center_id IN (" . implode(',', array_map('intval', $district_tc_ids)) . ")";
    }
    $mat_sql .= " GROUP BY DATE_FORMAT(receipt_date, '%Y-%m') ORDER BY report_month DESC";
    $inc_matrix = [];
    if ($mat_res = $mysqli->query($mat_sql)) {
        $inc_matrix = $mat_res->fetch_all(MYSQLI_ASSOC);
    }

    if ($inc_subtab === 'matrix') {
        $report_records = $inc_matrix;
    } else {
        $report_records = $inc_receipts;
    }

    // Income KPIs
    $kpi_inc_accommodation = array_sum(array_column($inc_receipts, 'category') === 'accommodation' ? [1] : [0]);
    $kpi_inc_halls = 0.00;
    $kpi_inc_multimedia = 0.00;
    $kpi_inc_farm_produce = 0.00;
    foreach ($inc_receipts as $ir) {
        if (in_array($ir['category'], ['accommodation', 'hall_charge'])) {
            $kpi_inc_halls += (float)$ir['amount'];
        } elseif (in_array($ir['category'], ['usage_multimedia', 'usage_sound_system'])) {
            $kpi_inc_multimedia += (float)$ir['amount'];
        } else {
            $kpi_inc_farm_produce += (float)$ir['amount'];
        }
    }
}

// 4. Produce Register (Annex A.D.30)
elseif ($current_view === 'produce') {
    $prod_subtab = isset($_GET['prod_tab']) ? trim($_GET['prod_tab']) : 'log';

    // 4.1 Produce Harvest & Sales Log
    $pr_sql = "SELECT tpr.*, tc.center_name, tc.location
               FROM training_produce_register tpr
               LEFT JOIN training_centers tc ON tpr.training_center_id = tc.id";
    if ($selected_center_id > 0) {
        $pr_sql .= " WHERE tpr.training_center_id = " . intval($selected_center_id);
    } elseif (!empty($district_tc_ids) && $is_district_level_dd) {
        $pr_sql .= " WHERE tpr.training_center_id IN (" . implode(',', array_map('intval', $district_tc_ids)) . ")";
    }
    $pr_sql .= " ORDER BY tpr.record_date DESC, tpr.id DESC";
    $prod_log = [];
    if ($pr_res = $mysqli->query($pr_sql)) {
        $prod_log = $pr_res->fetch_all(MYSQLI_ASSOC);
    }

    // 4.2 Commodity Summary Aggregation
    $com_sum_sql = "SELECT commodity, unit,
                           COUNT(id) AS total_entries,
                           IFNULL(SUM(quantity), 0) AS total_quantity,
                           IFNULL(SUM(CASE WHEN disposal_method = 'Sold' THEN quantity ELSE 0 END), 0) AS total_sold_qty,
                           IFNULL(SUM(CASE WHEN disposal_method = 'Issued' THEN quantity ELSE 0 END), 0) AS total_issued_qty,
                           IFNULL(SUM(full_sum_realized), 0) AS total_realized_sum
                    FROM training_produce_register";
    if ($selected_center_id > 0) {
        $com_sum_sql .= " WHERE training_center_id = " . intval($selected_center_id);
    } elseif (!empty($district_tc_ids) && $is_district_level_dd) {
        $com_sum_sql .= " WHERE training_center_id IN (" . implode(',', array_map('intval', $district_tc_ids)) . ")";
    }
    $com_sum_sql .= " GROUP BY commodity, unit ORDER BY total_realized_sum DESC";
    $prod_summary = [];
    if ($cs_res = $mysqli->query($com_sum_sql)) {
        $prod_summary = $cs_res->fetch_all(MYSQLI_ASSOC);
    }

    if ($prod_subtab === 'summary') {
        $report_records = $prod_summary;
    } else {
        $report_records = $prod_log;
    }

    // Produce KPIs
    $kpi_prod_total_entries = count($prod_log);
    $kpi_prod_sold_entries = count(array_filter($prod_log, fn($p) => strcasecmp($p['disposal_method'] ?? '', 'Sold') === 0));
    $kpi_prod_realized = array_sum(array_column($prod_log, 'full_sum_realized'));
}

// 5. Counter Foilage Register
elseif ($current_view === 'counter_foilage') {
    $cf_sql = "SELECT cfa.*, tc.center_name, tc.location, u.full_name AS registered_by
               FROM counterfoil_assets cfa
               LEFT JOIN training_centers tc ON cfa.training_center_id = tc.id
               LEFT JOIN users u ON cfa.user_id = u.id";
    if ($selected_center_id > 0) {
        $cf_sql .= " WHERE (cfa.training_center_id = " . intval($selected_center_id) . " OR (cfa.user_category = 'training_centers'))";
    } elseif (!empty($district_tc_ids) && $is_district_level_dd) {
        $cf_sql .= " WHERE (cfa.training_center_id IN (" . implode(',', array_map('intval', $district_tc_ids)) . ") OR (cfa.user_category = 'training_centers'))";
    }
    $cf_sql .= " ORDER BY cfa.id DESC";
    if ($cf_res = $mysqli->query($cf_sql)) {
        $report_records = $cf_res->fetch_all(MYSQLI_ASSOC);
    }
}

$active_record_count = count($report_records);
?>

<!-- DataTables + Buttons CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<!-- Training Centers Hub CSS -->
<link rel="stylesheet" href="../../../assets/css/training_centers.css">

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4 pb-5">
        
        <!-- Header & District Context Bar -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-success-subtle text-success border border-success px-2 py-1 rounded-pill small fw-bold">
                        <i class="bi bi-easel-fill me-1 text-success"></i> Training Centers Hub
                    </span>
                    <span class="text-muted small">/</span>
                    <span class="text-muted small fw-medium"><?= htmlspecialchars($district_name) ?> District Jurisdiction</span>
                </div>
                <h2 class="text-dark fw-bold mb-0">Animal Husbandry &amp; Farmer Training Centers Summary</h2>
                <p class="text-muted small mb-0 mt-1">Consolidated training capacity, farmer courses, facility income, and produce registers for <strong><?= htmlspecialchars($district_name) ?></strong>.</p>
            </div>
            
            <div class="d-flex align-items-center flex-wrap gap-2">
                <!-- Training Center Selector Filter -->
                <form method="GET" action="" class="d-flex align-items-center gap-2 m-0">
                    <input type="hidden" name="view" value="<?= htmlspecialchars($current_view) ?>">
                    <?php if (isset($inc_subtab)): ?>
                        <input type="hidden" name="inc_tab" value="<?= htmlspecialchars($inc_subtab) ?>">
                    <?php endif; ?>
                    <?php if (isset($prod_subtab)): ?>
                        <input type="hidden" name="prod_tab" value="<?= htmlspecialchars($prod_subtab) ?>">
                    <?php endif; ?>
                    
                    <div class="input-group input-group-sm shadow-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-building"></i>
                        </span>
                        <select name="center_id" class="form-select border-start-0 fw-semibold text-dark" onchange="this.form.submit()">
                            <option value="0">All <?= htmlspecialchars($district_name) ?> Centers</option>
                            <?php foreach ($available_centers as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($selected_center_id == $c['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['center_name']) ?> (<?= htmlspecialchars($c['location']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>

                <a href="../../../dashboard.php" class="btn btn-sm btn-outline-secondary shadow-sm fw-bold">
                    <i class="bi bi-speedometer2 me-1"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Master KPI Metric Strip -->
        <div class="row g-3 mb-4">
            
            <!-- Card 1: Active Centers -->
            <div class="col-xl-3 col-md-6">
                <div class="card kpi-stat-card kpi-green p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Training Institutions</small>
                            <h3 class="fw-bold text-dark mb-0 mt-1"><?= number_format($kpi_total_centers) ?></h3>
                            <small class="text-success fw-medium"><i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($district_name) ?> District</small>
                        </div>
                        <div class="kpi-icon-wrapper bg-success-subtle text-success">
                            <i class="bi bi-easel"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Total Programmes -->
            <div class="col-xl-3 col-md-6">
                <div class="card kpi-stat-card kpi-teal p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Training Programmes</small>
                            <h3 class="fw-bold text-primary mb-0 mt-1"><?= number_format($kpi_total_programmes) ?></h3>
                            <small class="text-muted"><i class="bi bi-mortarboard-fill me-1"></i>Advanced farmer modules</small>
                        </div>
                        <div class="kpi-icon-wrapper bg-primary-subtle text-primary">
                            <i class="bi bi-mortarboard"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Facility Income -->
            <div class="col-xl-3 col-md-6">
                <div class="card kpi-stat-card kpi-orange p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Facility Income (LKR)</small>
                            <h3 class="fw-bold text-dark mb-0 mt-1">LKR <?= number_format($kpi_total_income, 2) ?></h3>
                            <small class="text-warning fw-medium"><i class="bi bi-receipt me-1"></i>Halls, lodging &amp; services</small>
                        </div>
                        <div class="kpi-icon-wrapper bg-warning-subtle text-warning">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 4: Farm Produce Revenue -->
            <div class="col-xl-3 col-md-6">
                <div class="card kpi-stat-card kpi-gold p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Produce Realization (LKR)</small>
                            <h3 class="fw-bold text-dark mb-0 mt-1">LKR <?= number_format($kpi_total_produce, 2) ?></h3>
                            <small class="text-success fw-medium"><i class="bi bi-tree-fill me-1"></i>Form A.D.30 perishables</small>
                        </div>
                        <div class="kpi-icon-wrapper bg-success-subtle text-success">
                            <i class="bi bi-flower1"></i>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Primary Training Centers Sub-Modules Navigation Strip -->
        <div class="card card-modern mb-4">
            <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap-fill text-success me-2"></i>Training Center Sub-Modules &amp; Operations</h6>
                <small class="text-muted d-none d-md-inline">Select an operational training module to view consolidated records</small>
            </div>
            <div class="card-body px-4 pt-0">
                <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-2">
                    
                    <!-- 1. Centers Directory -->
                    <div class="col">
                        <a href="?view=centers_list&center_id=<?= $selected_center_id ?>" class="btn-tc-action <?= $current_view === 'centers_list' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #1b5e20, #2e7d32);">
                            <i class="bi bi-building"></i>
                            <span class="text-center">Centers Directory</span>
                        </a>
                    </div>

                    <!-- 2. Advanced Programmes -->
                    <div class="col">
                        <a href="?view=programmes&center_id=<?= $selected_center_id ?>" class="btn-tc-action <?= $current_view === 'programmes' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #00695c, #00897b);">
                            <i class="bi bi-mortarboard-fill"></i>
                            <span class="text-center">Training Programmes</span>
                        </a>
                    </div>

                    <!-- 3. Monthly Income Summary -->
                    <div class="col">
                        <a href="?view=monthly_income&center_id=<?= $selected_center_id ?>" class="btn-tc-action <?= $current_view === 'monthly_income' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #e65100, #f57c00);">
                            <i class="bi bi-cash-stack"></i>
                            <span class="text-center">Monthly Income</span>
                        </a>
                    </div>

                    <!-- 4. Produce Register -->
                    <div class="col">
                        <a href="?view=produce&center_id=<?= $selected_center_id ?>" class="btn-tc-action <?= $current_view === 'produce' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #b08723, #c99c2e);">
                            <i class="bi bi-tree-fill"></i>
                            <span class="text-center">Produce Register</span>
                        </a>
                    </div>

                    <!-- 5. Counter Foilage Register -->
                    <div class="col">
                        <a href="?view=counter_foilage&center_id=<?= $selected_center_id ?>" class="btn-tc-action <?= $current_view === 'counter_foilage' ? 'active' : '' ?>" style="background: linear-gradient(145deg, #37474f, #455a64);">
                            <i class="bi bi-receipt-cutoff"></i>
                            <span class="text-center">Counter Foil Books</span>
                        </a>
                    </div>

                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- VIEW 3 SPECIALIZED HEADER: MONTHLY INCOME SUMMARY HUB -->
        <!-- ========================================================================= -->
        <?php if ($current_view === 'monthly_income'): ?>
        <div class="card card-modern mb-4 border-start border-4 border-warning shadow-sm">
            <div class="card-header bg-white py-3 px-4 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-cash-stack text-warning me-2"></i>Monthly Income Summary &amp; Financial Receipts</h6>
                    <small class="text-muted">Consolidated revenue from accommodation, auditorium halls, audiovisual equipment, and farm produce sales.</small>
                </div>
                <span class="badge bg-warning-subtle text-dark border border-warning px-3 py-2 rounded-pill small fw-bold">
                    <i class="bi bi-currency-exchange me-1"></i> LKR <?= number_format($kpi_total_income, 2) ?> Total Receipts
                </span>
            </div>
            
            <div class="card-body px-4 pt-0 pb-3">
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-4">
                        <div class="p-3 rounded-3 bg-light border text-center">
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Halls &amp; Accommodation</small>
                            <h5 class="fw-bold text-dark mt-1 mb-0">LKR <?= number_format($kpi_inc_halls, 2) ?></h5>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="p-3 rounded-3 bg-light border text-center">
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Multimedia &amp; Sound</small>
                            <h5 class="fw-bold text-primary mt-1 mb-0">LKR <?= number_format($kpi_inc_multimedia, 2) ?></h5>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="p-3 rounded-3 bg-light border text-center">
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Pasture &amp; Produce Income</small>
                            <h5 class="fw-bold text-success mt-1 mb-0">LKR <?= number_format($kpi_inc_farm_produce, 2) ?></h5>
                        </div>
                    </div>
                </div>

                <!-- Sub-Navigation Pills for Monthly Income -->
                <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                    <a href="?view=monthly_income&inc_tab=daily&center_id=<?= $selected_center_id ?>" class="btn btn-sm sub-stat-pill <?= ($inc_subtab === 'daily') ? 'btn-warning active-pill text-dark' : 'btn-outline-warning text-dark' ?>">
                        <i class="bi bi-journal-text me-1"></i> Daily Receipt Transactions Log
                    </a>
                    <a href="?view=monthly_income&inc_tab=matrix&center_id=<?= $selected_center_id ?>" class="btn btn-sm sub-stat-pill <?= ($inc_subtab === 'matrix') ? 'btn-success active-pill text-white' : 'btn-outline-success' ?>">
                        <i class="bi bi-grid-3x3 me-1"></i> Monthly Financial Income Matrix
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ========================================================================= -->
        <!-- VIEW 4 SPECIALIZED HEADER: PRODUCE REGISTER (ANNEX A.D.30) -->
        <!-- ========================================================================= -->
        <?php if ($current_view === 'produce'): ?>
        <div class="card card-modern mb-4 border-start border-4 border-success shadow-sm">
            <div class="card-header bg-white py-3 px-4 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-tree-fill text-success me-2"></i>Produce Register (Perishables) - Form A.D.30</h6>
                    <small class="text-muted">Consolidated harvest and disposal logs for agricultural crops, coconuts, fodder, and farm produce.</small>
                </div>
                <span class="badge bg-success-subtle text-success border border-success px-3 py-2 rounded-pill small fw-bold">
                    <i class="bi bi-flower1 me-1"></i> LKR <?= number_format($kpi_total_produce, 2) ?> Total Realized
                </span>
            </div>
            
            <div class="card-body px-4 pt-0 pb-3">
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-4">
                        <div class="p-3 rounded-3 bg-light border text-center">
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Harvest Log Entries</small>
                            <h5 class="fw-bold text-dark mt-1 mb-0"><?= number_format($kpi_prod_total_entries) ?> <span class="fs-6 text-muted font-monospace">(<?= number_format($kpi_prod_sold_entries) ?> Sold)</span></h5>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="p-3 rounded-3 bg-light border text-center">
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Total Realized Revenue</small>
                            <h5 class="fw-bold text-success mt-1 mb-0">LKR <?= number_format($kpi_prod_realized, 2) ?></h5>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="p-3 rounded-3 bg-light border text-center">
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Commodities Tracked</small>
                            <h5 class="fw-bold text-primary mt-1 mb-0"><?= count($prod_summary ?? []) ?> Categories</h5>
                        </div>
                    </div>
                </div>

                <!-- Sub-Navigation Pills for Produce Register -->
                <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                    <a href="?view=produce&prod_tab=log&center_id=<?= $selected_center_id ?>" class="btn btn-sm sub-stat-pill <?= ($prod_subtab === 'log') ? 'btn-success active-pill text-white' : 'btn-outline-success' ?>">
                        <i class="bi bi-journal-text me-1"></i> Harvest &amp; Sales Transactions Log
                    </a>
                    <a href="?view=produce&prod_tab=summary&center_id=<?= $selected_center_id ?>" class="btn btn-sm sub-stat-pill <?= ($prod_subtab === 'summary') ? 'btn-primary active-pill text-white' : 'btn-outline-primary' ?>">
                        <i class="bi bi-pie-chart-fill me-1"></i> Commodity Category Aggregation Matrix
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Dynamic Data Section with DataTables & Export Toolbars -->
        <div class="card card-modern mb-4">
            <div class="card-header bg-white py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="m-0 fw-bold text-dark">
                        <?php if ($current_view === 'centers_list'): ?>
                            <i class="bi bi-building me-2 text-success"></i>Training Centers &amp; Appointed Training Officers
                        <?php elseif ($current_view === 'programmes'): ?>
                            <i class="bi bi-mortarboard-fill me-2 text-primary"></i>Advanced Training Programmes &amp; Courses
                        <?php elseif ($current_view === 'monthly_income'): ?>
                            <?php if ($inc_subtab === 'matrix'): ?>
                                <i class="bi bi-grid-3x3 me-2 text-success"></i>Monthly Financial Income Cross-Tab Matrix
                            <?php else: ?>
                                <i class="bi bi-journal-text me-2 text-warning"></i>Daily Receipt Transactions &amp; Vouchers Log
                            <?php endif; ?>
                        <?php elseif ($current_view === 'produce'): ?>
                            <?php if ($prod_subtab === 'summary'): ?>
                                <i class="bi bi-pie-chart-fill me-2 text-primary"></i>Commodity Category Harvest &amp; Realization Breakdown
                            <?php else: ?>
                                <i class="bi bi-journal-text me-2 text-success"></i>Produce Register Harvest &amp; Disposal Records (Form A.D.30)
                            <?php endif; ?>
                        <?php elseif ($current_view === 'counter_foilage'): ?>
                            <i class="bi bi-receipt-cutoff me-2 text-secondary"></i>Counter Foil Books &amp; Receipt Registers
                        <?php endif; ?>
                    </h5>
                    <span class="badge bg-light text-dark border ms-1"><?= htmlspecialchars($active_center_scope_name) ?></span>
                    <span class="badge bg-secondary-subtle text-secondary border"><?= $active_record_count ?> <?= $active_record_count === 1 ? 'Record' : 'Records' ?></span>
                </div>
                <div class="small text-muted">
                    <span class="badge bg-light text-muted border"><i class="bi bi-download me-1"></i>Export Data Toolbar</span>
                </div>
            </div>
            
            <div class="card-body px-4 py-3">
                <div class="table-responsive">
                    
                    <!-- 1. Centers Directory Table -->
                    <?php if ($current_view === 'centers_list'): ?>
                        <table id="tcDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Training Center</th>
                                    <th>Location &amp; District</th>
                                    <th>Assigned Training Officer</th>
                                    <th>Contact Information</th>
                                    <th class="text-center">Programmes</th>
                                    <th class="text-end">Facility Revenue</th>
                                    <th class="text-end">Produce Revenue</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $tc): ?>
                                    <tr>
                                        <td class="fw-bold text-dark">
                                            <i class="bi bi-building me-1 text-success"></i><?= htmlspecialchars($tc['center_name']) ?>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-danger"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($tc['location'] ?? 'N/A') ?></span>
                                            <br><small class="text-muted"><?= htmlspecialchars($tc['district_name']) ?> District</small>
                                        </td>
                                        <td>
                                            <?php if (!empty($tc['officer_name'])): ?>
                                                <div class="fw-bold text-primary"><?= htmlspecialchars($tc['officer_name']) ?></div>
                                                <small class="text-muted font-monospace"><?= htmlspecialchars($tc['officer_username']) ?></small>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Vacant / Central Pool</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($tc['officer_email'])): ?>
                                                <div><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($tc['officer_email']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($tc['officer_phone'])): ?>
                                                <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($tc['officer_phone']) ?></small>
                                            <?php endif; ?>
                                            <?php if (empty($tc['officer_email']) && empty($tc['officer_phone'])): ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center font-monospace fw-bold text-primary"><?= (int)$tc['total_programmes'] ?></td>
                                        <td class="text-end font-monospace text-warning fw-bold">LKR <?= number_format($tc['total_revenue'], 2) ?></td>
                                        <td class="text-end font-monospace text-success fw-bold">LKR <?= number_format($tc['total_produce_revenue'], 2) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= !empty($tc['is_active']) ? 'success' : 'danger' ?>">
                                                <?= !empty($tc['is_active']) ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 2. Advanced Training Programmes Table -->
                    <?php elseif ($current_view === 'programmes'): ?>
                        <table id="tcDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Training Programme / Task</th>
                                    <th>Training Center &amp; Location</th>
                                    <th>Venue / Place</th>
                                    <th>Distance</th>
                                    <th>Time Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $prog): ?>
                                    <tr>
                                        <td class="fw-bold"><?= date('d M Y', strtotime($prog['date'])) ?></td>
                                        <td class="fw-bold text-primary">
                                            <i class="bi bi-mortarboard me-1"></i><?= htmlspecialchars($prog['task']) ?>
                                        </td>
                                        <td>
                                            <i class="bi bi-building me-1 text-success"></i><?= htmlspecialchars($prog['center_name'] ?? 'Training Center') ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($prog['location'] ?? '') ?></small>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($prog['place']) ?></span></td>
                                        <td class="font-monospace"><?= number_format($prog['distance'] ?? 0, 1) ?> km</td>
                                        <td class="text-muted font-monospace"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($prog['time_duration']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <!-- 3. Monthly Income Summary Tables -->
                    <?php elseif ($current_view === 'monthly_income'): ?>
                        
                        <!-- 3.1 Daily Receipts Sub-Tab -->
                        <?php if ($inc_subtab === 'daily'): ?>
                            <table id="tcDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Receipt Date</th>
                                        <th>Receipt Voucher No</th>
                                        <th>Training Center</th>
                                        <th>Income Category</th>
                                        <th>Description</th>
                                        <th>Payer Name</th>
                                        <th class="text-end">Amount (LKR)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_records as $rec): ?>
                                        <tr>
                                            <td><?= date('d M Y', strtotime($rec['receipt_date'])) ?></td>
                                            <td class="fw-bold font-monospace text-primary">
                                                <i class="bi bi-receipt me-1"></i><?= htmlspecialchars($rec['receipt_no']) ?>
                                            </td>
                                            <td><?= htmlspecialchars($rec['center_name'] ?? 'Center') ?></td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><?= ucwords(str_replace('_', ' ', $rec['category'])) ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($rec['description'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($rec['payer_name'] ?? '-') ?></td>
                                            <td class="text-end font-monospace text-success fw-bold">LKR <?= number_format($rec['amount'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                        <!-- 3.2 Monthly Matrix Sub-Tab -->
                        <?php elseif ($inc_subtab === 'matrix'): ?>
                            <table id="tcDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th>Month</th>
                                        <th>Receipts Count</th>
                                        <th class="text-end">Accommodation</th>
                                        <th class="text-end">Hall Charge</th>
                                        <th class="text-end">Multimedia</th>
                                        <th class="text-end">Sound System</th>
                                        <th class="text-end">Pasture &amp; Crops</th>
                                        <th class="text-end">Monthly Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_records as $m): ?>
                                        <tr>
                                            <td class="fw-bold text-primary text-start">
                                                <i class="bi bi-calendar-event me-1"></i><?= date('F Y', strtotime($m['report_month'] . '-01')) ?>
                                            </td>
                                            <td class="font-monospace"><?= (int)$m['total_receipts'] ?> Receipts</td>
                                            <td class="text-end font-monospace"><?= number_format($m['cat_accommodation'], 2) ?></td>
                                            <td class="text-end font-monospace"><?= number_format($m['cat_hall'], 2) ?></td>
                                            <td class="text-end font-monospace"><?= number_format($m['cat_multimedia'], 2) ?></td>
                                            <td class="text-end font-monospace"><?= number_format($m['cat_sound'], 2) ?></td>
                                            <td class="text-end font-monospace"><?= number_format($m['cat_grass'] + $m['cat_banana'] + $m['cat_vegetable'] + $m['cat_coconut'] + $m['cat_bag'] + $m['cat_tamarind'] + $m['cat_pasture'], 2) ?></td>
                                            <td class="text-end font-monospace text-success fw-bold">LKR <?= number_format($m['month_grand_total'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                    <!-- 4. Produce Register Tables -->
                    <?php elseif ($current_view === 'produce'): ?>
                        
                        <!-- 4.1 Daily Harvest & Sales Log -->
                        <?php if ($prod_subtab === 'log'): ?>
                            <table id="tcDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Commodity</th>
                                        <th>Plot / Crop Section</th>
                                        <th class="text-center">Quantity &amp; Unit</th>
                                        <th class="text-center">Disposal Method</th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-end">Full Sum Realized</th>
                                        <th>Receipt / Credit Page</th>
                                        <th>Initials</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_records as $p): ?>
                                        <tr>
                                            <td><?= date('d M Y', strtotime($p['record_date'])) ?></td>
                                            <td class="fw-bold text-dark"><i class="bi bi-flower1 me-1 text-success"></i><?= htmlspecialchars($p['commodity']) ?></td>
                                            <td><?= htmlspecialchars($p['plot_no_crop'] ?? '-') ?></td>
                                            <td class="text-center font-monospace fw-bold"><?= number_format($p['quantity'], 2) ?> <?= htmlspecialchars($p['unit'] ?? '') ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-<?= (strcasecmp($p['disposal_method'] ?? '', 'Sold') === 0) ? 'success' : 'info' ?>">
                                                    <?= htmlspecialchars($p['disposal_method'] ?? 'Sold') ?>
                                                </span>
                                            </td>
                                            <td class="text-end font-monospace">LKR <?= number_format($p['price_per_unit'], 2) ?></td>
                                            <td class="text-end font-monospace text-success fw-bold">LKR <?= number_format($p['full_sum_realized'], 2) ?></td>
                                            <td class="font-monospace"><?= htmlspecialchars($p['receipt_no_credit_page'] ?? '-') ?></td>
                                            <td class="text-muted"><?= htmlspecialchars($p['initials_user'] ?? '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                        <!-- 4.2 Commodity Summary Breakdown -->
                        <?php elseif ($prod_subtab === 'summary'): ?>
                            <table id="tcDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Commodity Name</th>
                                        <th class="text-center">Unit of Measure</th>
                                        <th class="text-center">Total Log Entries</th>
                                        <th class="text-center">Total Quantity Harvested</th>
                                        <th class="text-center">Quantity Sold</th>
                                        <th class="text-center">Quantity Issued / Free</th>
                                        <th class="text-end">Total Realized Revenue (LKR)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_records as $cs): ?>
                                        <tr>
                                            <td class="fw-bold text-primary"><i class="bi bi-tree me-1 text-success"></i><?= htmlspecialchars($cs['commodity']) ?></td>
                                            <td class="text-center font-monospace"><?= htmlspecialchars($cs['unit']) ?></td>
                                            <td class="text-center font-monospace"><?= (int)$cs['total_entries'] ?></td>
                                            <td class="text-center font-monospace text-dark fw-bold"><?= number_format($cs['total_quantity'], 2) ?></td>
                                            <td class="text-center font-monospace text-success fw-bold"><?= number_format($cs['total_sold_qty'], 2) ?></td>
                                            <td class="text-center font-monospace text-info"><?= number_format($cs['total_issued_qty'], 2) ?></td>
                                            <td class="text-end font-monospace text-success fw-bold">LKR <?= number_format($cs['total_realized_sum'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                    <!-- 5. Counter Foilage Register Table -->
                    <?php elseif ($current_view === 'counter_foilage'): ?>
                        <table id="tcDataTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Counter Foil / Book Category</th>
                                    <th>Training Center</th>
                                    <th class="text-center">Available Books</th>
                                    <th>Date Received / Opened</th>
                                    <th class="text-center">Current Status</th>
                                    <th>Serial Range / Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_records as $cf): ?>
                                    <tr>
                                        <td class="fw-bold font-monospace text-primary">
                                            <i class="bi bi-book-half me-1"></i><?= htmlspecialchars($cf['counterfoil_type']) ?>
                                        </td>
                                        <td><?= htmlspecialchars($cf['center_name'] ?? 'Training Center') ?></td>
                                        <td class="text-center font-monospace fw-bold">
                                            <span class="badge bg-light text-dark border">
                                                <?= intval($cf['available_quantity']) ?> Books
                                            </span>
                                        </td>
                                        <td><?= !empty($cf['purchase_date']) ? date('d M Y', strtotime($cf['purchase_date'])) : '-' ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= (stripos($cf['current_condition'] ?? '', 'Active') !== false || stripos($cf['current_condition'] ?? '', 'In Use') !== false) ? 'success' : 'secondary' ?>">
                                                <?= htmlspecialchars($cf['current_condition'] ?? 'Active') ?>
                                            </span>
                                        </td>
                                        <td class="text-muted"><?= htmlspecialchars($cf['remarks'] ?? '-') ?></td>
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

<!-- DataTables & Export Scripts -->
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
    $('#tcDataTable').DataTable({
        pageLength: 15,
        dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-3"Bf>rt<"d-flex flex-wrap justify-content-between align-items-center mt-3"lip>',
        buttons: [
            {
                extend: 'copy',
                text: '<i class="bi bi-clipboard me-1"></i>Copy',
                className: 'btn btn-secondary btn-sm'
            },
            {
                extend: 'excel',
                text: '<i class="bi bi-file-earmark-excel me-1"></i>Excel',
                className: 'btn btn-success btn-sm'
            },
            {
                extend: 'pdf',
                text: '<i class="bi bi-file-earmark-pdf me-1"></i>PDF',
                className: 'btn btn-danger btn-sm',
                orientation: 'landscape'
            },
            {
                extend: 'print',
                text: '<i class="bi bi-printer me-1"></i>Print',
                className: 'btn btn-dark btn-sm'
            }
        ],
        order: [],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search records..."
        }
    });
});
</script>
