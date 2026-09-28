<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

// 1. Session and Role Guard
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

$user_id     = $_SESSION['user_id'] ?? null;
$range_id    = $_SESSION['range_id'] ?? null;
$district_id = $_SESSION['district_id'] ?? null;

if (empty($range_id)) {
    die('<div class="alert alert-danger text-center p-5 m-5">Error: Your account is not assigned to any Veterinary Range.</div>');
}

// 2. Fetch Officer Details & Designation
$officer_name        = $_SESSION['full_name'] ?? 'Veterinary Surgeon';
$officer_designation = 'Government Veterinary Surgeon';
$ministry_department = 'Ministry of Agriculture, Animal Production & Development / Department of Animal Production & Health (Eastern Province)';

if ($user_id) {
    $stmt = $mysqli->prepare("SELECT full_name, designation, role FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $u_res = $stmt->get_result();
        if ($u = $u_res->fetch_assoc()) {
            if (!empty($u['full_name'])) {
                $officer_name = $u['full_name'];
            }
            if (!empty($u['designation'])) {
                $officer_designation = $u['designation'];
            } elseif (!empty($u['role'])) {
                $officer_designation = ucwords(str_replace('_', ' ', $u['role']));
            }
        }
        $stmt->close();
    }
}

// 3. Structural Range & District Information
$district_name = 'Unknown District';
$range_name    = 'Unknown Range';

if ($district_id) {
    $stmt = $mysqli->prepare("SELECT name FROM districts WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $district_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $district_name = $row['name'];
        }
        $stmt->close();
    }
}

if ($range_id) {
    $stmt = $mysqli->prepare("SELECT name, district_id FROM veterinary_ranges WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $range_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $range_name = $row['name'];
            if (empty($district_id) && !empty($row['district_id'])) {
                $district_id = $row['district_id'];
                $d_res = $mysqli->query("SELECT name FROM districts WHERE id = " . intval($district_id));
                if ($d_row = $d_res->fetch_assoc()) {
                    $district_name = $d_row['name'];
                }
            }
        }
        $stmt->close();
    }
}

// 4. Report Period & Date Range Filter
$selected_year  = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$from_month     = isset($_GET['from_month']) ? intval($_GET['from_month']) : 1;
$to_month       = isset($_GET['to_month']) ? intval($_GET['to_month']) : 3;
$active_month   = isset($_GET['month']) ? intval($_GET['month']) : intval(date('n'));

// Boundary sanitization
if ($from_month < 1 || $from_month > 12) $from_month = 1;
if ($to_month < 1 || $to_month > 12)     $to_month = 12;
if ($to_month < $from_month)             $to_month = $from_month;
if ($active_month < 1 || $active_month > 12) $active_month = 1;

$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

$month_shorts = [
    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
    5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
    9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
];

$range_label = $month_names[$from_month] . ' – ' . $month_names[$to_month] . ' ' . $selected_year;
$report_period_label = ($month_names[$active_month] ?? '') . ' ' . $selected_year;

// 5. Fetch the 10 Main Production Categories
$categories_query = $mysqli->query("SELECT id, category_name, sort_order FROM production_categories ORDER BY sort_order ASC, id ASC");
$all_categories   = $categories_query->fetch_all(MYSQLI_ASSOC);

// 6. Fetch All Subcategories (Items) grouped by Category (Year-aware: active items, historical items, or items with records for this range & year)
$items_stmt = $mysqli->prepare("
    SELECT DISTINCT pi.id, pi.category_id, pi.item_name, pi.unit, pi.is_active, pi.archived_year 
    FROM production_items pi
    LEFT JOIN section_e se ON se.item_id = pi.id AND se.range_id = ? AND se.report_year = ? AND se.amount > 0
    WHERE pi.is_active = 1 
       OR (pi.archived_year IS NOT NULL AND pi.archived_year > ?)
       OR se.id IS NOT NULL
    ORDER BY pi.category_id ASC, pi.id ASC
");
$items_stmt->bind_param("iii", $range_id, $selected_year, $selected_year);
$items_stmt->execute();
$items_res = $items_stmt->get_result();

$all_items_by_cat = [];
$total_subcategories = 0;
while ($it = $items_res->fetch_assoc()) {
    $all_items_by_cat[$it['category_id']][] = $it;
    $total_subcategories++;
}
$items_stmt->close();

// 7. Fetch all production entries for this range and year (all 12 months)
$sec_e_query = $mysqli->prepare("
    SELECT category_id, item_id, report_month, amount 
    FROM section_e 
    WHERE range_id = ? AND report_year = ?
");
$sec_e_query->bind_param("ii", $range_id, $selected_year);
$sec_e_query->execute();
$sec_e_res = $sec_e_query->get_result();

$monthly_data = []; // [item_id][month] => amount
$monthly_cat_totals = []; // [category_id][month] => amount
$monthly_overall_totals = array_fill(1, 12, 0.0);

while ($row = $sec_e_res->fetch_assoc()) {
    $iid = intval($row['item_id']);
    $cid = intval($row['category_id']);
    $m   = intval($row['report_month']);
    $amt = floatval($row['amount']);

    $monthly_data[$iid][$m] = $amt;
    if (!isset($monthly_cat_totals[$cid][$m])) {
        $monthly_cat_totals[$cid][$m] = 0.0;
    }
    $monthly_cat_totals[$cid][$m] += $amt;
    $monthly_overall_totals[$m] += $amt;
}
$sec_e_query->close();

// Progressive Cumulative Monthly Totals for Chart
$chart_months = [];
$chart_monthly_totals = [];
$chart_cumulative_totals = [];
$running_sum = 0.0;

for ($m = 1; $m <= 12; $m++) {
    $chart_months[] = $month_shorts[$m];
    $val = $monthly_overall_totals[$m];
    $chart_monthly_totals[] = round($val, 2);
    $running_sum += $val;
    $chart_cumulative_totals[] = round($running_sum, 2);
}

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css?v=<?= time() ?>">

<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h3 class="fw-bold mb-1" style="color: #370709;">Section E: Production Details</h3>
        <p class="text-muted small mb-0">Production metrics, horizontal tabbed categories, custom date range consolidation & official reporting for <strong class="text-dark"><?= htmlspecialchars($range_name) ?> Range</strong>.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="production_balance.php" class="btn btn-secondary shadow-sm text-nowrap">
            <i class="bi bi-arrow-left me-2"></i>Back to Production Balance
        </a>
    </div>
</div>

<!-- ============================================================ -->
<!-- 1. STANDARD HEADER & SCOPE DETAILS -->
<!-- ============================================================ -->
<div class="card shadow-sm border-0 mb-4" style="border-left: 5px solid #820100 !important;">
    <div class="card-body p-4">
        <div class="row align-items-center gy-3">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span class="badge" style="background-color: #820100; font-size: 0.85rem; letter-spacing: 0.5px;">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> SECTION E
                    </span>
                    <h5 class="fw-bold mb-0 text-dark">Production Details & Custom Date Summaries</h5>
                </div>
                <div class="row g-2 text-muted small mt-1">
                    <div class="col-sm-6">
                        <i class="bi bi-person-badge text-danger me-1"></i><strong>Officer:</strong> <?= htmlspecialchars($officer_name) ?> (<?= htmlspecialchars($officer_designation) ?>)
                    </div>
                    <div class="col-sm-6">
                        <i class="bi bi-geo-alt-fill text-danger me-1"></i><strong>Range & District:</strong> <?= htmlspecialchars($range_name) ?> Range, <?= htmlspecialchars($district_name) ?> District
                    </div>
                    <div class="col-12">
                        <i class="bi bi-building text-danger me-1"></i><strong>Ministry / Dept:</strong> <?= htmlspecialchars($ministry_department) ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="p-3 bg-light rounded-3 border">
                    <div class="text-muted small fw-semibold text-uppercase">Consolidated Period</div>
                    <div class="h5 fw-bold mb-0 text-dark" id="pillPeriodLabel"><?= htmlspecialchars($range_label) ?></div>
                    <div class="text-muted small mt-1">
                        Active Entry Month: <strong class="text-danger"><?= $month_names[$active_month] ?> <?= $selected_year ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- 2. CUSTOM DATE RANGE SUMMARY FILTER & KPI SUMMARY -->
<!-- ============================================================ -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-calendar-range text-danger fs-5"></i>
            <h6 class="mb-0 fw-bold text-dark">Custom Date Range Summary Filter</h6>
            <span class="badge bg-secondary-subtle text-secondary small">Multi-Month Aggregation</span>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-success" id="btnTopExportCsv" title="Export Consolidated Summary to CSV">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" id="btnTopExportPdf" title="Export Consolidated Summary to PDF">
                <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addSubCategoryModal">
                <i class="bi bi-plus-circle me-1"></i> Add Subcategory
            </button>
            <button type="button" class="btn btn-sm btn-outline-dark" id="btnPrintSectionEReport">
                <i class="bi bi-printer me-1"></i> Print Official Report
            </button>
        </div>
    </div>
    <div class="card-body p-4 bg-light-subtle">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-3 col-sm-6">
                <label class="form-label small fw-bold text-muted mb-1">From Month</label>
                <select id="rangeFromMonth" class="form-select" style="border-radius: 8px;">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m === $from_month ? 'selected' : '' ?>>
                            <?= str_pad($m, 2, '0', STR_PAD_LEFT) ?> - <?= $month_names[$m] ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label small fw-bold text-muted mb-1">To Month</label>
                <select id="rangeToMonth" class="form-select" style="border-radius: 8px;">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m === $to_month ? 'selected' : '' ?>>
                            <?= str_pad($m, 2, '0', STR_PAD_LEFT) ?> - <?= $month_names[$m] ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-bold text-muted mb-1">Reporting Year</label>
                <select id="rangeYear" class="form-select" style="border-radius: 8px;">
                    <?php for ($y = date('Y') - 2; $y <= date('Y') + 2; $y++): ?>
                        <option value="<?= $y ?>" <?= $y === $selected_year ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-bold text-muted mb-1">Active Entry Month</label>
                <select id="selectEntryMonth" class="form-select" onchange="window.location.href='section_e.php?year=' + $('#rangeYear').val() + '&from_month=' + $('#rangeFromMonth').val() + '&to_month=' + $('#rangeToMonth').val() + '&month=' + this.value;">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m === $active_month ? 'selected' : '' ?>>
                            <?= $month_names[$m] ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-12">
                <button type="button" class="btn btn-primary w-100 shadow-sm" id="btnApplyRange" style="background-color: #820100; border-color: #820100; border-radius: 8px;">
                    <i class="bi bi-filter me-1"></i> Apply Range
                </button>
            </div>
        </div>

        <!-- Quick Interval Presets -->
        <div class="d-flex flex-wrap align-items-center gap-2 pt-2 border-top">
            <span class="small text-muted fw-bold me-1">Quick Presets:</span>
            <button type="button" class="btn btn-sm btn-outline-secondary btn-period-preset <?= ($from_month === 1 && $to_month === 3) ? 'active fw-bold' : '' ?>" data-from="1" data-to="3">Q1 (Jan – Mar)</button>
            <button type="button" class="btn btn-sm btn-outline-secondary btn-period-preset <?= ($from_month === 4 && $to_month === 6) ? 'active fw-bold' : '' ?>" data-from="4" data-to="6">Q2 (Apr – Jun)</button>
            <button type="button" class="btn btn-sm btn-outline-secondary btn-period-preset <?= ($from_month === 1 && $to_month === 6) ? 'active fw-bold' : '' ?>" data-from="1" data-to="6">Mid-Year (Jan – Jun)</button>
            <button type="button" class="btn btn-sm btn-outline-secondary btn-period-preset <?= ($from_month === 7 && $to_month === 9) ? 'active fw-bold' : '' ?>" data-from="7" data-to="9">Q3 (Jul – Sep)</button>
            <button type="button" class="btn btn-sm btn-outline-secondary btn-period-preset <?= ($from_month === 10 && $to_month === 12) ? 'active fw-bold' : '' ?>" data-from="10" data-to="12">Q4 (Oct – Dec)</button>
            <button type="button" class="btn btn-sm btn-outline-secondary btn-period-preset <?= ($from_month === 1 && $to_month === 12) ? 'active fw-bold' : '' ?>" data-from="1" data-to="12">Full Year (Jan – Dec)</button>
        </div>

        <!-- KPI Summary Badges for Date Range -->
        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <div class="stat-pill d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Reporting Period Interval</div>
                        <div class="fw-bold text-dark fs-6" id="kpiPeriodText"><?= htmlspecialchars($range_label) ?></div>
                    </div>
                    <i class="bi bi-calendar3 fs-3 text-secondary opacity-50"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-pill d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Active Metrics Tracked</div>
                        <div class="fw-bold fs-5 text-dark" id="kpiItemsActive">0</div>
                    </div>
                    <i class="bi bi-check-circle fs-3 text-success opacity-50"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-pill d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Consolidated Period Output Sum</div>
                        <div class="fw-bold fs-5 text-danger" id="kpiGrandTotal">0.00</div>
                    </div>
                    <i class="bi bi-graph-up-arrow fs-3 text-danger opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- 3. TABBED UI STRUCTURE (10 CATEGORIES + CONSOLIDATED SUMMARY + TREND CHART) -->
<!-- ============================================================ -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white p-3 border-bottom tabs-scroller">
        <ul class="nav nav-pills nav-pills-section-e" id="sectionETabs" role="tablist">
            <?php foreach ($all_categories as $c_idx => $cat): ?>
                <?php 
                    $cat_id = $cat['id'];
                    $tab_id = 'tab_cat_' . $cat_id;
                    $is_active = ($c_idx === 0);
                    $sub_count = count($all_items_by_cat[$cat_id] ?? []);
                ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $is_active ? 'active' : '' ?>" id="<?= $tab_id ?>-btn" data-bs-toggle="pill" data-bs-target="#<?= $tab_id ?>" type="button" role="tab">
                        <span class="cat-badge-circle" style="font-size: 0.72rem; width: 22px; height: 22px;"><?= $cat['sort_order'] ?></span>
                        <?= htmlspecialchars($cat['category_name']) ?>
                        <span class="badge-tab-count"><?= $sub_count ?></span>
                    </button>
                </li>
            <?php endforeach; ?>
            <li class="nav-item ms-auto" role="presentation">
                <button class="nav-link text-danger border-danger bg-white" id="tab_consolidated-btn" data-bs-toggle="pill" data-bs-target="#tab_consolidated" type="button" role="tab">
                    <i class="bi bi-table me-1"></i> Consolidated Range Summary
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link text-dark bg-light border" id="tab_chart-btn" data-bs-toggle="pill" data-bs-target="#tab_chart" type="button" role="tab">
                    <i class="bi bi-graph-up me-1"></i> Progressive YTD Trend
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body p-4">
        <form id="productionGridForm" method="POST" action="processors/save_production_grid.php">
            <input type="hidden" name="report_year" value="<?= $selected_year ?>">
            <input type="hidden" name="report_month" value="<?= $active_month ?>">

            <div class="tab-content" id="sectionETabsContent">
                <?php foreach ($all_categories as $c_idx => $cat): ?>
                    <?php 
                        $cat_id = $cat['id'];
                        $tab_id = 'tab_cat_' . $cat_id;
                        $is_active = ($c_idx === 0);
                        $cat_items = $all_items_by_cat[$cat_id] ?? [];
                    ?>
                    <div class="tab-pane fade <?= $is_active ? 'show active' : '' ?>" id="<?= $tab_id ?>" role="tabpanel">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-3 bg-light rounded-3 border">
                            <div class="d-flex align-items-center gap-2">
                                <span class="cat-badge-circle"><?= $cat['sort_order'] ?></span>
                                <div>
                                    <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($cat['category_name']) ?></h5>
                                    <span class="text-muted small">Viewing aggregated metrics from <?= $month_names[$from_month] ?> to <?= $month_names[$to_month] ?> <?= $selected_year ?></span>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-success btn-export-cat-csv" data-cat-id="<?= $cat_id ?>" data-cat-name="<?= htmlspecialchars($cat['category_name']) ?>" title="Export this category to CSV">
                                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> CSV
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-export-cat-pdf" data-cat-id="<?= $cat_id ?>" data-cat-name="<?= htmlspecialchars($cat['category_name']) ?>" title="Export this category to PDF">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                                </button>
                                <?php if ($cat_id == 11): ?>
                                    <span class="badge bg-warning-subtle text-dark border px-3 py-2">
                                        <i class="bi bi-calculator me-1"></i> Live Auto-calculated Semen Balance
                                    </span>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-manage-archived-quick" data-cat-id="<?= $cat_id ?>" data-cat-name="<?= htmlspecialchars($cat['category_name']) ?>" title="View & Reactivate Discontinued / Archived Items">
                                    <i class="bi bi-archive me-1"></i> Archived
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-add-subcat-quick" data-cat-id="<?= $cat_id ?>" data-cat-name="<?= htmlspecialchars($cat['category_name']) ?>">
                                    <i class="bi bi-plus-lg me-1"></i> Add Subcategory
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle border mb-0">
                                <thead class="table-light small text-uppercase">
                                    <tr>
                                        <th style="width: 50px;" class="text-center">#</th>
                                        <th style="width: 32%;">Sub-Category Name / Description</th>
                                        <th style="width: 100px;" class="text-center">Unit</th>
                                        <?php for ($m = $from_month; $m <= $to_month; $m++): ?>
                                            <th class="text-end font-monospace"><?= $month_shorts[$m] ?></th>
                                        <?php endfor; ?>
                                        <th class="text-end font-monospace table-period-sum" style="width: 140px;">
                                            Period Total<br><small style="font-size:0.68rem; font-weight:normal;">(<?= $month_shorts[$from_month] ?>–<?= $month_shorts[$to_month] ?>)</small>
                                        </th>
                                        <th class="text-end table-active-month" style="width: 160px;">
                                            Entry Input<br><small class="text-danger fw-bold" style="font-size:0.75rem;">(<?= $month_names[$active_month] ?>)</small>
                                        </th>
                                        <th class="text-end table-ytd" style="width: 140px;">
                                            YTD<br><small style="font-size:0.68rem; font-weight:normal;">(to <?= $month_shorts[$active_month] ?>)</small>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($cat_items)): ?>
                                        <tr>
                                            <td colspan="<?= 6 + ($to_month - $from_month + 1) ?>" class="text-center text-muted py-4">No items configured for this category. Click "+ Add Subcategory" above to register one.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php 
                                            $cat_period_sum = 0.0;
                                            $cat_active_sum = 0.0;
                                            $cat_ytd_sum = 0.0;
                                            foreach ($cat_items as $i_idx => $item): 
                                                $item_id = $item['id'];
                                                $unit = $item['unit'] ?? '';
                                                $item_name = $item['item_name'];
                                                $period_sum = 0.0;
                                                $active_val = floatval($monthly_data[$item_id][$active_month] ?? 0);
                                                $cat_active_sum += $active_val;

                                                $item_ytd = 0.0;
                                                for ($m = 1; $m <= $active_month; $m++) {
                                                    $item_ytd += floatval($monthly_data[$item_id][$m] ?? 0);
                                                }
                                                $cat_ytd_sum += $item_ytd;

                                                $is_semen_balance_row = ($cat_id == 11 && strpos($item_name, 'Balance available') !== false);
                                                $is_semen_input = ($cat_id == 11);
                                        ?>
                                            <tr class="<?= $is_semen_balance_row ? 'semen-balance-row' : '' ?>">
                                                <td class="text-center text-muted fw-semibold"><?= $i_idx + 1 ?></td>
                                                <td>
                                                    <input type="hidden" name="items[<?= $item_id ?>][item_id]" value="<?= $item_id ?>">
                                                    <input type="hidden" name="items[<?= $item_id ?>][category_id]" value="<?= $cat_id ?>">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div>
                                                            <strong><?= htmlspecialchars($item_name) ?></strong>
                                                            <?php if ($is_semen_balance_row): ?>
                                                                <span class="badge bg-warning text-dark ms-2 small">Auto-calculated</span>
                                                            <?php endif; ?>
                                                            <?php if (intval($item['is_active'] ?? 1) === 0): ?>
                                                                <span class="badge bg-secondary-subtle text-secondary border ms-2 small" title="Archived starting from <?= $item['archived_year'] ?>">
                                                                    <i class="bi bi-archive me-1"></i>Archived (<?= $item['archived_year'] ?>)
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if (!$is_semen_balance_row): ?>
                                                            <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2 btn-delete-item no-print" 
                                                                data-item-id="<?= $item_id ?>" 
                                                                data-item-name="<?= htmlspecialchars($item_name) ?>" 
                                                                data-year="<?= $selected_year ?>"
                                                                title="Delete or Archive Subcategory">
                                                                <i class="bi bi-trash3 opacity-50 hover-opacity-100"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-light text-dark border badge-unit"><?= htmlspecialchars($unit) ?></span>
                                                </td>
                                                <?php for ($m = $from_month; $m <= $to_month; $m++): ?>
                                                    <?php 
                                                        $m_val = floatval($monthly_data[$item_id][$m] ?? 0);
                                                        $period_sum += $m_val;
                                                    ?>
                                                    <td class="text-end font-monospace text-muted small">
                                                        <?= number_format($m_val, 2) ?>
                                                    </td>
                                                <?php endfor; ?>
                                                <?php $cat_period_sum += $period_sum; ?>
                                                <td class="text-end font-monospace fw-bold text-danger table-period-sum" id="period_sum_<?= $item_id ?>">
                                                    <?= number_format($period_sum, 2) ?>
                                                </td>
                                                <td class="text-end table-active-month">
                                                    <input type="number" step="0.01" min="0" 
                                                        name="items[<?= $item_id ?>][amount]" 
                                                        id="amount_<?= $item_id ?>"
                                                        value="<?= $active_val > 0 ? htmlspecialchars($active_val) : '' ?>" 
                                                        class="form-control form-control-sm text-end input-table-num <?= $is_semen_input ? 'semen-item-input' : '' ?>"
                                                        data-item-name="<?= htmlspecialchars($item_name) ?>"
                                                        placeholder="0.00"
                                                        <?= $is_semen_balance_row ? 'readonly style="background-color: #fef3c7; font-weight: bold;"' : '' ?>>
                                                </td>
                                                <td class="text-end font-monospace text-dark fw-bold table-ytd">
                                                    <?= number_format($item_ytd, 2) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-light fw-bold">
                                            <td colspan="3" class="text-end text-uppercase small text-muted">Category Subtotal:</td>
                                            <td colspan="<?= ($to_month - $from_month + 1) ?>" class="text-end text-muted small">—</td>
                                            <td class="text-end font-monospace text-danger table-period-sum"><?= number_format($cat_period_sum, 2) ?></td>
                                            <td class="text-end font-monospace text-dark"><?= number_format($cat_active_sum, 2) ?></td>
                                            <td class="text-end font-monospace text-dark"><?= number_format($cat_ytd_sum, 2) ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- TAB 11: CONSOLIDATED DATE RANGE SUMMARY -->
                <div class="tab-pane fade" id="tab_consolidated" role="tabpanel">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-3 bg-light rounded-3 border">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Consolidated Production Summary (All Categories)</h5>
                            <span class="text-muted small">Aggregated metrics for all production items between <?= $month_names[$from_month] ?> and <?= $month_names[$to_month] ?> <?= $selected_year ?></span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                            <button type="button" class="btn btn-sm btn-success shadow-sm" id="btnConsolidatedCsv">
                                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
                            </button>
                            <button type="button" class="btn btn-sm btn-danger shadow-sm" id="btnConsolidatedPdf">
                                <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                            </button>
                            <button type="button" class="btn btn-sm btn-dark shadow-sm" id="btnConsolidatedPrint">
                                <i class="bi bi-printer me-1"></i> Print Table
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle border w-100" id="consolidatedSummaryTable">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th style="width: 50px;" class="text-center">#</th>
                                    <th style="width: 200px;">Category</th>
                                    <th>Sub-Category Name</th>
                                    <th style="width: 90px;" class="text-center">Unit</th>
                                    <th class="text-end font-monospace table-period-sum" style="width: 150px;">Period Total</th>
                                    <th class="text-center" style="width: 280px;">Monthly Breakdown</th>
                                </tr>
                            </thead>
                            <tbody id="consolidatedSummaryBody">
                                <!-- Populated dynamically by veterinary.js -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 12: PROGRESSIVE CUMULATIVE TREND CHART -->
                <div class="tab-pane fade" id="tab_chart" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded-3 border">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Progressive Cumulative Output Trend (<?= $selected_year ?>)</h5>
                            <span class="text-muted small">Monthly output volume alongside progressive cumulative Year-to-Date totals.</span>
                        </div>
                        <span class="badge bg-white text-dark border px-3 py-2">
                            Total YTD up to <?= $month_names[$active_month] ?>: <strong><?= number_format(array_sum(array_slice($monthly_overall_totals, 0, $active_month)), 2) ?></strong>
                        </span>
                    </div>

                    <div class="p-3 bg-white rounded border">
                        <canvas id="secEYtdChart" height="90"></canvas>
                    </div>
                </div>
            </div>

            <!-- Sticky Bottom Action Bar for Active Month Batch Save -->
            <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i> Active editing applies to month: <strong class="text-danger"><?= $month_names[$active_month] ?> <?= $selected_year ?></strong>.
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4 py-2 shadow-sm fw-bold" style="background-color: #820100; border-color: #820100;">
                        <i class="bi bi-cloud-arrow-up me-2"></i> Save <?= $month_names[$active_month] ?> Production Data
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- 4. OFFICIAL SIGNATURE & RUBBER STAMP SECTION (FOR PRINT & VERIFICATION) -->
<!-- ============================================================ -->
<div class="card shadow-sm border-0 mb-4 p-4 bg-light">
    <h6 class="fw-bold text-dark text-uppercase mb-4 border-bottom pb-2">
        <i class="bi bi-award-fill text-danger me-2"></i>Official Certification & Verification
    </h6>
    <div class="row text-center mt-3 pt-2">
        <div class="col-md-6 mb-4 mb-md-0">
            <div style="min-height: 70px;"></div>
            <div class="border-top pt-2 mx-auto" style="max-width: 320px;">
                <div class="fw-bold text-dark"><?= htmlspecialchars($officer_name) ?></div>
                <div class="text-muted small"><?= htmlspecialchars($officer_designation) ?></div>
                <div class="text-muted small">Government Veterinary Office - <?= htmlspecialchars($range_name) ?></div>
                <div class="text-muted small mt-1">Signature of Veterinary Surgeon</div>
            </div>
        </div>
        <div class="col-md-6">
            <div style="min-height: 70px;"></div>
            <div class="border-top pt-2 mx-auto" style="max-width: 320px;">
                <div class="fw-bold text-dark">Official Rubber Stamp</div>
                <div class="text-muted small">Government Veterinary Office / Range</div>
                <div class="text-muted small"><?= htmlspecialchars($range_name) ?> Range, <?= htmlspecialchars($district_name) ?></div>
                <div class="text-muted small mt-1">Official Seal</div>
            </div>
        </div>
    </div>
</div>

</main>
</div>

<!-- ============================================================ -->
<!-- 5. ISOLATED OFFICIAL PRINTABLE REPORT (CUSTOM DATE RANGE) -->
<!-- ============================================================ -->
<div id="printableSectionEReport" class="print-only p-4" style="display: none; background: #fff; color: #000; font-family: 'Times New Roman', serif;">
    <div class="text-center pb-3 mb-3 border-bottom border-2 border-dark">
        <h4 class="fw-bold mb-1 text-uppercase">Department of Animal Production & Health</h4>
        <h5 class="fw-bold mb-1">Eastern Province, Sri Lanka</h5>
        <h6 class="fw-bold mb-2">SECTION E: PRODUCTION DETAILS CONSOLIDATED REPORT</h6>
        <div class="small">
            <strong>Range:</strong> <?= htmlspecialchars($range_name) ?> &nbsp;|&nbsp; 
            <strong>District:</strong> <?= htmlspecialchars($district_name) ?> &nbsp;|&nbsp; 
            <strong>Consolidated Period:</strong> <span id="printRangeLabel"><?= htmlspecialchars($range_label) ?></span>
        </div>
    </div>

    <table class="table table-bordered table-sm align-middle mb-4" style="border-color: #333; font-size: 0.95rem;">
        <thead class="table-light text-uppercase" style="border-bottom: 2px solid #000;">
            <tr>
                <th style="width: 50px;" class="text-center">#</th>
                <th style="width: 200px;">Category</th>
                <th>Sub-Category Description</th>
                <th style="width: 100px;" class="text-center">Unit</th>
                <th style="width: 140px;" class="text-end">Consolidated Period Output</th>
            </tr>
        </thead>
        <tbody id="printConsolidatedBody">
            <!-- Populated dynamically by veterinary.js -->
        </tbody>
    </table>

    <div class="mt-5 pt-4 row text-center" style="page-break-inside: avoid;">
        <div class="col-6">
            <div style="min-height: 60px;"></div>
            <div class="border-top pt-2 mx-auto" style="max-width: 280px; border-top: 1px solid #000 !important;">
                <div class="fw-bold"><?= htmlspecialchars($officer_name) ?></div>
                <div class="small"><?= htmlspecialchars($officer_designation) ?></div>
                <div class="small">Government Veterinary Office - <?= htmlspecialchars($range_name) ?></div>
                <div class="small mt-1">Signature of Veterinary Surgeon</div>
            </div>
        </div>
        <div class="col-6">
            <div style="min-height: 60px;"></div>
            <div class="border-top pt-2 mx-auto" style="max-width: 280px; border-top: 1px solid #000 !important;">
                <div class="fw-bold">Official Rubber Stamp</div>
                <div class="small">Government Veterinary Office / Range</div>
                <div class="small"><?= htmlspecialchars($range_name) ?> Range, <?= htmlspecialchars($district_name) ?></div>
                <div class="small mt-1">Official Seal</div>
            </div>
        </div>
    </div>
</div>

<!-- Add Subcategory Modal -->
<?php include 'model/add_subcategory_modal.php'; ?>
<!-- Manage Archived Subcategories Modal -->
<?php include 'model/manage_archived_modal.php'; ?>

<?php require_once '../../../includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
window.SectionEConfig = {
    officerName: <?= json_encode($officer_name) ?>,
    officerDesignation: <?= json_encode($officer_designation) ?>,
    ministryDepartment: <?= json_encode($ministry_department) ?>,
    rangeName: <?= json_encode($range_name) ?>,
    districtName: <?= json_encode($district_name) ?>,
    selectedYear: <?= json_encode($selected_year) ?>,
    fromMonth: <?= json_encode($from_month) ?>,
    toMonth: <?= json_encode($to_month) ?>,
    activeMonth: <?= json_encode($active_month) ?>,
    monthNames: <?= json_encode($month_names) ?>,
    monthShorts: <?= json_encode($month_shorts) ?>,
    chartMonths: <?= json_encode($chart_months) ?>,
    chartMonthlyTotals: <?= json_encode($chart_monthly_totals) ?>,
    chartCumulativeTotals: <?= json_encode($chart_cumulative_totals) ?>,
    categories: <?= json_encode($all_categories) ?>,
    itemsByCat: <?= json_encode($all_items_by_cat) ?>,
    monthlyData: <?= json_encode($monthly_data) ?>
};
</script>
<script src="../../../assets/js/veterinary.js?v=<?= time() ?>"></script>