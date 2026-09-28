<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

// 1. Session and Role Guard
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../index.php");
    exit();
}

$user_id     = $_SESSION['user_id'] ?? null;
$range_id    = $_SESSION['range_id'] ?? null;
$district_id = $_SESSION['district_id'] ?? null;

// Resolve Range ID from User Profiler if empty in session
if (empty($range_id) && !empty($user_id)) {
    $user_query = $mysqli->prepare("SELECT range_id, district_id FROM users WHERE id = ?");
    if ($user_query) {
        $user_query->bind_param("i", $user_id);
        $user_query->execute();
        $user_result = $user_query->get_result();
        if ($row = $user_result->fetch_assoc()) {
            $_SESSION['range_id'] = $row['range_id'];
            $range_id = $row['range_id'];
            if (!empty($row['district_id'])) {
                $_SESSION['district_id'] = $row['district_id'];
                $district_id = $row['district_id'];
            }
        }
        $user_query->close();
    }
}

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

// 5. Fetch the 4 Main Categories for Letter H
$categories_query = $mysqli->query("SELECT id, category_name, sort_order FROM letter_h_categories ORDER BY sort_order ASC, id ASC");
$all_categories   = $categories_query->fetch_all(MYSQLI_ASSOC);

// 6. Fetch All Subcategories (Revenue Items) grouped by Category
$items_query = $mysqli->query("SELECT id, category_id, item_name, sort_order FROM letter_h_items ORDER BY category_id ASC, sort_order ASC, id ASC");
$all_items_by_cat = [];
$total_items = 0;
while ($it = $items_query->fetch_assoc()) {
    $all_items_by_cat[$it['category_id']][] = $it;
    $total_items++;
}

// 7. Fetch all Letter H entries for this range and year (all 12 months)
$lh_query = $mysqli->prepare("
    SELECT category_id, item_id, report_month, amount 
    FROM letter_h_entries 
    WHERE range_id = ? AND report_year = ?
");
$lh_query->bind_param("ii", $range_id, $selected_year);
$lh_query->execute();
$lh_res = $lh_query->get_result();

$monthly_data = []; // [item_id][month] => amount
$monthly_overall_totals = array_fill(1, 12, 0.0);

while ($row = $lh_res->fetch_assoc()) {
    $iid = intval($row['item_id']);
    $m   = intval($row['report_month']);
    $amt = floatval($row['amount']);

    $monthly_data[$iid][$m] = $amt;
    $monthly_overall_totals[$m] += $amt;
}
$lh_query->close();

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">

<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h3 class="fw-bold mb-1" style="color: #370709;">Letter 'H' Account</h3>
        <p class="text-muted small mb-0">Cash Settlement in Bank records, revenue breakdowns, horizontal category tabs & custom date summaries for <strong class="text-dark"><?= htmlspecialchars($range_name) ?> Range</strong>.</p>
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
                        <i class="bi bi-bank me-1"></i> LETTER 'H' ACCOUNT
                    </span>
                    <h5 class="fw-bold mb-0 text-dark">Cash Settlement in Bank - Official Financial Return</h5>
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
<!-- 2. CUSTOM DATE RANGE SUMMARY FILTER & KPI CARDS -->
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
            <button type="button" class="btn btn-sm btn-outline-dark" id="btnPrintLetterHReport">
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
                <select id="selectEntryMonth" class="form-select" onchange="window.location.href='letter_h_record.php?year=' + $('#rangeYear').val() + '&from_month=' + $('#rangeFromMonth').val() + '&to_month=' + $('#rangeToMonth').val() + '&month=' + this.value;">
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

        <!-- KPI Summary Cards with Master Grand Total -->
        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <div class="stat-pill d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Reporting Period Interval</div>
                        <div class="fw-bold text-dark fs-6" id="kpiPeriodInterval"><?= htmlspecialchars($range_label) ?></div>
                    </div>
                    <i class="bi bi-calendar3 fs-3 text-secondary opacity-50"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-pill d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">Active Month Total (<?= $month_shorts[$active_month] ?>)</div>
                        <div class="fw-bold fs-5 text-dark" id="kpiActiveMonthTotal">0.00</div>
                    </div>
                    <i class="bi bi-cash-stack fs-3 text-success opacity-50"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-pill d-flex align-items-center justify-content-between border-danger-subtle bg-danger-subtle bg-opacity-10">
                    <div>
                        <div class="text-danger small fw-bold text-uppercase">Master Grand Total (All Tabs)</div>
                        <div class="fw-bold fs-4 text-danger" id="kpiGrandTotal">0.00</div>
                    </div>
                    <i class="bi bi-bank2 fs-2 text-danger opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- 3. TABBED UI STRUCTURE & GRID LAYOUT -->
<!-- ============================================================ -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white p-3 border-bottom tabs-scroller">
        <ul class="nav nav-pills nav-pills-letter-h" id="letterHTabs" role="tablist">
            <?php foreach ($all_categories as $c_idx => $cat): ?>
                <?php 
                    $cat_id = $cat['id'];
                    $tab_id = 'tab_cat_' . $cat_id;
                    $is_active = ($c_idx === 0);
                    $item_cnt = count($all_items_by_cat[$cat_id] ?? []);
                ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $is_active ? 'active' : '' ?>" id="<?= $tab_id ?>-btn" data-bs-toggle="pill" data-bs-target="#<?= $tab_id ?>" type="button" role="tab">
                        <span class="cat-badge-circle"><?= $cat['sort_order'] ?></span>
                        Tab <?= $cat['sort_order'] ?>: <?= htmlspecialchars($cat['category_name']) ?>
                        <span class="badge-tab-count"><?= $item_cnt ?></span>
                    </button>
                </li>
            <?php endforeach; ?>
            <li class="nav-item ms-auto" role="presentation">
                <button class="nav-link text-danger border-danger bg-white" id="tab_consolidated-btn" data-bs-toggle="pill" data-bs-target="#tab_consolidated" type="button" role="tab">
                    <i class="bi bi-table me-1"></i> Consolidated Range Summary
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body p-4">
        <form id="letterHGridForm" method="POST" action="processors/save_letter_h_grid.php">
            <input type="hidden" name="report_year" value="<?= $selected_year ?>">
            <input type="hidden" name="report_month" value="<?= $active_month ?>">

            <div class="tab-content" id="letterHTabsContent">
                <?php foreach ($all_categories as $c_idx => $cat): ?>
                    <?php 
                        $cat_id = $cat['id'];
                        $tab_id = 'tab_cat_' . $cat_id;
                        $is_active = ($c_idx === 0);
                        $cat_items = $all_items_by_cat[$cat_id] ?? [];
                    ?>
                    <div class="tab-pane fade <?= $is_active ? 'show active' : '' ?>" id="<?= $tab_id ?>" role="tabpanel">
                        <!-- Category Header Banner with Tab Sub-totals & Exports -->
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-3 bg-light rounded-3 border">
                            <div class="d-flex align-items-center gap-2">
                                <span class="cat-badge-circle"><?= $cat['sort_order'] ?></span>
                                <div>
                                    <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($cat['category_name']) ?></h5>
                                    <span class="text-muted small">Viewing revenue metrics from <?= $month_names[$from_month] ?> to <?= $month_names[$to_month] ?> <?= $selected_year ?></span>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-success btn-export-tab-csv" data-tab-id="<?= $tab_id ?>" data-tab-name="<?= htmlspecialchars($cat['category_name']) ?>" title="Export this tab to CSV">
                                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> CSV
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-export-tab-pdf" data-tab-id="<?= $tab_id ?>" data-tab-name="<?= htmlspecialchars($cat['category_name']) ?>" title="Export this tab to PDF">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                                </button>
                            </div>
                        </div>

                        <!-- Data Grid with Columns: Detail of Revenue, Amount, Total -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border mb-0">
                                <thead class="table-light small text-uppercase">
                                    <tr>
                                        <th style="width: 50px;" class="text-center">#</th>
                                        <th style="width: 50%;">Detail of Revenue</th>
                                        <th style="width: 25%;" class="text-end table-active-entry">
                                            Amount (LKR)<br><small class="text-danger fw-bold" style="font-size:0.75rem;">(<?= $month_names[$active_month] ?> <?= $selected_year ?>)</small>
                                        </th>
                                        <th style="width: 25%;" class="text-end font-monospace table-period-total">
                                            Total (LKR)<br><small style="font-size:0.68rem; font-weight:normal;" class="lblPeriodInterval">(<?= $month_shorts[$from_month] ?>–<?= $month_shorts[$to_month] ?>)</small>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($cat_items)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">No revenue line items registered under this category.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($cat_items as $i_idx => $item): 
                                            $item_id = $item['id'];
                                            $item_name = $item['item_name'];
                                            $active_val = floatval($monthly_data[$item_id][$active_month] ?? 0);
                                        ?>
                                            <tr>
                                                <td class="text-center text-muted fw-semibold"><?= $i_idx + 1 ?></td>
                                                <td>
                                                    <input type="hidden" name="items[<?= $item_id ?>][item_id]" value="<?= $item_id ?>">
                                                    <input type="hidden" name="items[<?= $item_id ?>][category_id]" value="<?= $cat_id ?>">
                                                    <strong><?= htmlspecialchars($item_name) ?></strong>
                                                </td>
                                                <td class="text-end table-active-entry">
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-light text-muted small">Rs.</span>
                                                        <input type="number" step="0.01" min="0" 
                                                            name="items[<?= $item_id ?>][amount]" 
                                                            id="amount_<?= $item_id ?>"
                                                            value="<?= $active_val > 0 ? htmlspecialchars($active_val) : '' ?>" 
                                                            class="form-control form-control-sm text-end input-currency input-revenue-amount"
                                                            data-cat-id="<?= $cat_id ?>"
                                                            data-item-id="<?= $item_id ?>"
                                                            placeholder="0.00">
                                                    </div>
                                                </td>
                                                <td class="text-end font-monospace fw-bold text-danger table-period-total" id="period_total_<?= $item_id ?>">
                                                    0.00
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <!-- Automated Sub-Total Calculation Row for Tab -->
                                        <tr class="table-light fw-bold" style="border-top: 2px solid #cbd5e1;">
                                            <td colspan="2" class="text-end text-uppercase small text-dark">
                                                <i class="bi bi-calculator me-1 text-danger"></i>Tab <?= $cat['sort_order'] ?> Sub-Total:
                                            </td>
                                            <td class="text-end font-monospace text-dark table-active-entry" id="tab_subtotal_amount_<?= $cat_id ?>">0.00</td>
                                            <td class="text-end font-monospace text-danger table-period-total" id="tab_subtotal_period_<?= $cat_id ?>">0.00</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- TAB 5: CONSOLIDATED RANGE SUMMARY (ALL TABS) -->
                <div class="tab-pane fade" id="tab_consolidated" role="tabpanel">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-3 bg-light rounded-3 border">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Consolidated Letter 'H' Summary (All Categories)</h5>
                            <span class="text-muted small">Complete settlement breakdown across all 4 categories between <?= $month_names[$from_month] ?> and <?= $month_names[$to_month] ?> <?= $selected_year ?></span>
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
                        <table class="table table-hover align-middle border w-100" id="consolidatedLetterHTable">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th style="width: 50px;" class="text-center">#</th>
                                    <th style="width: 240px;">Category</th>
                                    <th>Detail of Revenue</th>
                                    <th style="width: 170px;" class="text-end">Amount (<?= $month_shorts[$active_month] ?>)</th>
                                    <th style="width: 170px;" class="text-end font-monospace table-period-total">Total (Period)</th>
                                </tr>
                            </thead>
                            <tbody id="consolidatedLetterHBody">
                                <!-- Populated dynamically by veterinary.js -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Sticky Bottom Action Bar with Master Grand Total Display & Save Button -->
            <div class="mt-4 pt-3 border-top d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted small">
                        <i class="bi bi-info-circle me-1"></i> Active Editing: <strong class="text-danger"><?= $month_names[$active_month] ?> <?= $selected_year ?></strong>
                    </span>
                    <span class="badge bg-danger-subtle text-danger px-3 py-2 fw-bold" style="font-size: 0.85rem;">
                        Consolidated Grand Total: <span id="bannerGrandTotal">LKR 0.00</span>
                    </span>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4 py-2 shadow-sm fw-bold" style="background-color: #820100; border-color: #820100;" id="btnSaveAllLetterH">
                        <i class="bi bi-cloud-arrow-up me-2"></i> Save <?= $month_names[$active_month] ?> Letter 'H' Records
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
<div id="printableLetterHReport" class="print-only p-4" style="display: none; background: #fff; color: #000; font-family: 'Times New Roman', serif;">
    <div class="text-center pb-3 mb-3 border-bottom border-2 border-dark">
        <h4 class="fw-bold mb-1 text-uppercase">Department of Animal Production & Health</h4>
        <h5 class="fw-bold mb-1">Eastern Province, Sri Lanka</h5>
        <h6 class="fw-bold mb-2">LETTER 'H' ACCOUNT (CASH SETTLEMENT IN BANK)</h6>
        <div class="small">
            <strong>Range:</strong> <?= htmlspecialchars($range_name) ?> &nbsp;|&nbsp; 
            <strong>District:</strong> <?= htmlspecialchars($district_name) ?> &nbsp;|&nbsp; 
            <strong>Consolidated Period:</strong> <span id="printPeriodLabel"><?= htmlspecialchars($range_label) ?></span>
        </div>
    </div>

    <table class="table table-bordered table-sm align-middle mb-4" style="border-color: #333; font-size: 0.95rem;">
        <thead class="table-light text-uppercase" style="border-bottom: 2px solid #000;">
            <tr>
                <th style="width: 50px;" class="text-center">#</th>
                <th style="width: 220px;">Category</th>
                <th>Detail of Revenue</th>
                <th style="width: 130px;" class="text-end">Amount (LKR)</th>
                <th style="width: 140px;" class="text-end">Total (LKR)</th>
            </tr>
        </thead>
        <tbody id="printLetterHBody">
            <!-- Populated dynamically by veterinary.js -->
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; border-top: 2px solid #000;">
                <td colspan="3" class="text-end text-uppercase">Master Grand Total:</td>
                <td class="text-end" id="printActiveTotal">0.00</td>
                <td class="text-end" id="printGrandTotal">0.00</td>
            </tr>
        </tfoot>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
window.LetterHConfig = {
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
    categories: <?= json_encode($all_categories) ?>,
    itemsByCat: <?= json_encode($all_items_by_cat) ?>,
    monthlyData: <?= json_encode($monthly_data) ?>
};
</script>
<script src="../../../assets/js/veterinary.js"></script>