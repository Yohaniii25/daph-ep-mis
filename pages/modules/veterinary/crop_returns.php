<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

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

// 1. Fetch Officer Details & Designation
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

// 2. Fetch Range and District Names
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
    $stmt = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $range_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $range_name = $row['name'];
        }
        $stmt->close();
    }
}

// 3. Date / Month / Year Filtering
$selected_year  = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('n'));

// 4. The 20 Standard Items specified in requirements
$standard_items = [
    'Ranikhet I',
    'Ranikhet II',
    'Fowl pox',
    'Gumboro',
    'HS(Oil)',
    'HS (Alum)',
    'BQ',
    'FMD',
    'Semen',
    'Sex semen',
    'Health Certificate',
    'D/O cockerals',
    'W/O cockerels',
    'D/O unsexed',
    'Stud Bull',
    'Stud goat',
    'Calf starter',
    'Cattle feed',
    'Mineral mixture',
    'Tetanus Toxoid'
];

// 5. Calculate Previous Month & Year for Auto-Pulling Balance
$prev_month = $selected_month === 1 ? 12 : $selected_month - 1;
$prev_year  = $selected_month === 1 ? $selected_year - 1 : $selected_year;

// Fetch Previous Month's Ending Balances for all items
$prev_balances = [];
$stmt_prev = $mysqli->prepare("
    SELECT item_name, balance_current_month 
    FROM crop_returns 
    WHERE range_id = ? AND report_year = ? AND report_month = ?
");
if ($stmt_prev) {
    $stmt_prev->bind_param("iii", $range_id, $prev_year, $prev_month);
    $stmt_prev->execute();
    $res_p = $stmt_prev->get_result();
    while ($row = $res_p->fetch_assoc()) {
        $prev_balances[$row['item_name']] = intval($row['balance_current_month']);
    }
    $stmt_prev->close();
}

// Fetch Current Month's Saved Records
$current_records = [];
$stmt_curr = $mysqli->prepare("
    SELECT id, item_name, balance_previous_month, received_current_month, issued_current_month, balance_current_month, remark 
    FROM crop_returns 
    WHERE range_id = ? AND report_year = ? AND report_month = ?
");
if ($stmt_curr) {
    $stmt_curr->bind_param("iii", $range_id, $selected_year, $selected_month);
    $stmt_curr->execute();
    $res_c = $stmt_curr->get_result();
    while ($row = $res_c->fetch_assoc()) {
        $current_records[$row['item_name']] = $row;
    }
    $stmt_curr->close();
}

// Fetch All 12 Months' Records for Cumulative YTD Aggregation
$annual_crop_data = []; // [item_name][month] => ['received' => ..., 'issued' => ..., 'balance' => ...]
$monthly_issued_totals = array_fill(1, 12, 0);
$monthly_received_totals = array_fill(1, 12, 0);

$stmt_annual = $mysqli->prepare("
    SELECT item_name, report_month, balance_previous_month, received_current_month, issued_current_month, balance_current_month 
    FROM crop_returns 
    WHERE range_id = ? AND report_year = ?
");
if ($stmt_annual) {
    $stmt_annual->bind_param("ii", $range_id, $selected_year);
    $stmt_annual->execute();
    $res_ann = $stmt_annual->get_result();
    while ($row = $res_ann->fetch_assoc()) {
        $m = intval($row['report_month']);
        $i_name = $row['item_name'];
        $prev = intval($row['balance_previous_month']);
        $rec = intval($row['received_current_month']);
        $iss = intval($row['issued_current_month']);
        $bal = intval($row['balance_current_month']);

        $annual_crop_data[$i_name][$m] = [
            'prev'     => $prev,
            'received' => $rec,
            'issued'   => $iss,
            'balance'  => $bal
        ];

        $monthly_issued_totals[$m] += $iss;
        $monthly_received_totals[$m] += $rec;
    }
    $stmt_annual->close();
}

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

// Range Summary Month Filters (Default: Jan to Mar as requested)
$from_month = isset($_GET['from_month']) ? max(1, min(12, intval($_GET['from_month']))) : 1;
$to_month   = isset($_GET['to_month']) ? max(1, min(12, intval($_GET['to_month']))) : 3;
if ($to_month < $from_month) {
    $to_month = $from_month;
}
$range_period_label = $month_names[$from_month] . ' – ' . $month_names[$to_month] . ' ' . $selected_year;

// Progressive Cumulative Monthly Totals for Chart
$chart_months = [];
$chart_monthly_issued = [];
$chart_cumulative_issued = [];
$running_issued = 0;

for ($m = 1; $m <= 12; $m++) {
    $chart_months[] = $month_shorts[$m];
    $val = $monthly_issued_totals[$m];
    $chart_monthly_issued[] = $val;
    $running_issued += $val;
    $chart_cumulative_issued[] = $running_issued;
}

// Progressive YTD Sum from January to Currently Viewed Month
$current_ytd_issued = 0;
for ($m = 1; $m <= $selected_month; $m++) {
    $current_ytd_issued += $monthly_issued_totals[$m];
}

$report_period_label = ($month_names[$selected_month] ?? '') . ' ' . $selected_year;

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">

<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h3 class="fw-bold mb-1" style="color: #370709;">Crop Return</h3>
        <p class="text-muted small mb-0">Monthly stock, issuance, and balance tracking of veterinary vaccines, biologics, certificates & breeding stock.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="production_balance.php" class="btn btn-secondary shadow-sm text-nowrap">
            <i class="bi bi-arrow-left me-2"></i>Back to Production Balance
        </a>
    </div>
</div>

<!-- ============================================================ -->
<!-- STANDARD MONTHLY CROP RETURN HEADER DETAILS -->
<!-- ============================================================ -->
<div class="card shadow-sm border-0 mb-4" style="border-left: 5px solid #820100 !important;">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-3 border-bottom">
            <div>
                <span class="badge bg-danger-subtle text-danger px-2 py-1 mb-1 fw-semibold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                    <i class="bi bi-file-earmark-medical me-1"></i>Official Biological & Stores Record
                </span>
                <h5 class="fw-bold text-dark mb-0">Monthly Crop Return Tracking Grid</h5>
            </div>
            <!-- Month & Year Selector -->
            <form method="GET" class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                <label class="form-label small text-muted mb-0 fw-bold text-nowrap"><i class="bi bi-funnel-fill me-1"></i>Period:</label>
                <select name="month" class="form-select form-select-sm" style="min-width: 135px;" onchange="this.form.submit()">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $selected_month === $m ? 'selected' : '' ?>>
                            <?= $month_names[$m] ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <select name="year" class="form-select form-select-sm" style="min-width: 95px;" onchange="this.form.submit()">
                    <?php for ($y = 2024; $y <= 2030; $y++): ?>
                        <option value="<?= $y ?>" <?= $selected_year === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>

        <div class="row g-3">
            <div class="col-sm-6 col-lg-3">
                <div class="p-3 rounded bg-light border h-100">
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Name of the Officer</div>
                    <div class="fw-bold text-dark mt-1 text-truncate" title="<?= htmlspecialchars($officer_name) ?>">
                        <i class="bi bi-person-badge-fill text-danger me-2"></i><?= htmlspecialchars($officer_name) ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="p-3 rounded bg-light border h-100">
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Designation</div>
                    <div class="fw-bold text-dark mt-1 text-truncate" title="<?= htmlspecialchars($officer_designation) ?>">
                        <i class="bi bi-briefcase-fill text-danger me-2"></i><?= htmlspecialchars($officer_designation) ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="p-3 rounded bg-light border h-100">
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Ministry / Department</div>
                    <div class="fw-bold text-dark mt-1" style="font-size: 0.88rem; line-height: 1.25;" title="<?= htmlspecialchars($ministry_department) ?>">
                        <i class="bi bi-building-fill text-danger me-2"></i><?= htmlspecialchars($ministry_department) ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="p-3 rounded bg-light border h-100">
                    <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Report Month & Range</div>
                    <div class="fw-bold text-dark mt-1 text-truncate">
                        <i class="bi bi-calendar-check-fill text-danger me-2"></i><?= htmlspecialchars($report_period_label) ?>
                    </div>
                    <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                        <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($range_name) ?> (<?= htmlspecialchars($district_name) ?>)
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- ============================================================ -->
<!-- NAVIGATION TABS -->
<!-- ============================================================ -->
<ul class="nav nav-pills nav-pills-daph mb-4 gap-2 p-2 bg-white rounded shadow-sm border no-print" id="cropReturnsTabNav" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-trend-grid" data-bs-toggle="pill" data-bs-target="#pane-trend-grid" type="button" role="tab" aria-controls="pane-trend-grid" aria-selected="true">
            <i class="bi bi-graph-up text-danger me-2"></i>Crop Return Distribution & Progressive Trend (<?= $selected_year ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-annual-matrix" data-bs-toggle="pill" data-bs-target="#pane-annual-matrix" type="button" role="tab" aria-controls="pane-annual-matrix" aria-selected="false">
            <i class="bi bi-table text-danger me-2"></i>Full Year Breakdown Matrix (Jan – Dec)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-range-summary" data-bs-toggle="pill" data-bs-target="#pane-range-summary" type="button" role="tab" aria-controls="pane-range-summary" aria-selected="false">
            <i class="bi bi-calendar-range text-danger me-2"></i>Custom Period Summary (e.g. Jan – Mar)
        </button>
    </li>
</ul>

<div class="tab-content" id="cropReturnsTabContent">
    <!-- ============================================================ -->
    <!-- TAB 1: Progressive Cumulative Trend Chart & Monthly Grid Form -->
    <!-- ============================================================ -->
    <div class="tab-pane fade show active" id="pane-trend-grid" role="tabpanel" aria-labelledby="tab-trend-grid">
        
        <!-- 1. MONTHLY SUMMARY CHART WITH PROGRESSIVE CUMULATIVE YTD -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h6 class="mb-0 fw-bold" style="color: #370709;">
                        <i class="bi bi-graph-up me-2 text-danger"></i>Crop Return Distribution & Progressive Cumulative Trend (<?= $selected_year ?>)
                    </h6>
                    <small class="text-muted">Displays monthly issuance volume alongside progressive cumulative Year-to-Date (YTD) total.</small>
                </div>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                    Currently Viewed Month: <strong><?= $month_names[$selected_month] ?></strong> (Cumulative YTD Issued: <?= number_format($current_ytd_issued) ?> units)
                </span>
            </div>
            <div class="card-body">
                <canvas id="cropYtdChart" height="75"></canvas>
            </div>
        </div>

        <!-- 2. CROP RETURN TRACKING GRID FORM (20 STANDARD ITEMS) -->
        <form id="cropGridForm" action="processors/save_crop_returns_grid.php" method="POST">
            <input type="hidden" name="report_year" value="<?= htmlspecialchars($selected_year) ?>">
            <input type="hidden" name="report_month" value="<?= htmlspecialchars($selected_month) ?>">

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h6 class="mb-0 fw-bold" style="color: #370709;">
                            <i class="bi bi-table me-2 text-danger"></i>Monthly Return Tracking Grid: 20 Standard Office & Veterinary Items
                        </h6>
                        <small class="text-muted">Previous month balance auto-pulls from <strong><?= $month_names[$prev_month] ?> <?= $prev_year ?></strong>. Current Balance recalculates automatically.</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-dark" onclick="window.print()">
                            <i class="bi bi-printer me-1"></i>Print Return
                        </button>
                        <button type="submit" class="btn btn-sm text-light fw-bold px-3 shadow-sm" style="background-color: #820100;" id="btnSaveAll">
                            <i class="bi bi-save me-1"></i>Save All 20 Items
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="cropReturnGridTable">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th style="width: 4%;" class="text-center">#</th>
                                    <th style="width: 24%;">Office / Veterinary Item Name</th>
                                    <th style="width: 14%;" class="text-end">Balance Previous Month</th>
                                    <th style="width: 14%;" class="text-end">Current Month Received</th>
                                    <th style="width: 14%;" class="text-end">Current Month Issued</th>
                                    <th style="width: 14%;" class="text-end">Balance (Ending)</th>
                                    <th style="width: 16%;">Remark</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($standard_items as $index => $item_name): 
                                    $idx = $index + 1;
                                    $saved_rec = $current_records[$item_name] ?? null;

                                    if ($saved_rec !== null) {
                                        $prev_bal = intval($saved_rec['balance_previous_month']);
                                        $received = intval($saved_rec['received_current_month']);
                                        $issued   = intval($saved_rec['issued_current_month']);
                                        $curr_bal = intval($saved_rec['balance_current_month']);
                                        $remark   = $saved_rec['remark'] ?? '';
                                        $is_saved = true;
                                    } else {
                                        $prev_bal = $prev_balances[$item_name] ?? 0;
                                        $received = 0;
                                        $issued   = 0;
                                        $curr_bal = $prev_bal;
                                        $remark   = '';
                                        $is_saved = false;
                                    }
                                ?>
                                    <tr data-item-idx="<?= $idx ?>">
                                        <td class="text-center text-muted fw-semibold"><?= $idx ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($item_name) ?></strong>
                                            <input type="hidden" name="items[<?= $idx ?>][item_name]" value="<?= htmlspecialchars($item_name) ?>">
                                            <?php if ($is_saved): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle ms-1 badge-auto-pull" title="Record already saved for this month">Saved</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle ms-1 badge-auto-pull" title="Auto-pulled from previous month">Auto-Pulled</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" 
                                                       name="items[<?= $idx ?>][balance_previous_month]" 
                                                       id="prev_<?= $idx ?>"
                                                       value="<?= $prev_bal ?>" 
                                                       min="0" 
                                                       class="form-control form-control-sm text-end input-table-num input-prev" 
                                                       data-idx="<?= $idx ?>">
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   name="items[<?= $idx ?>][received_current_month]" 
                                                   id="received_<?= $idx ?>"
                                                   value="<?= $received ?>" 
                                                   min="0" 
                                                   class="form-control form-control-sm text-end input-table-num input-received" 
                                                   data-idx="<?= $idx ?>">
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   name="items[<?= $idx ?>][issued_current_month]" 
                                                   id="issued_<?= $idx ?>"
                                                   value="<?= $issued ?>" 
                                                   min="0" 
                                                   class="form-control form-control-sm text-end input-table-num input-issued" 
                                                   data-idx="<?= $idx ?>">
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   name="items[<?= $idx ?>][balance_current_month]" 
                                                   id="balance_<?= $idx ?>"
                                                   value="<?= $curr_bal ?>" 
                                                   readonly 
                                                   class="form-control form-control-sm text-end input-table-num bg-light fw-bold input-balance" 
                                                   data-idx="<?= $idx ?>">
                                        </td>
                                        <td>
                                            <input type="text" 
                                                   name="items[<?= $idx ?>][remark]" 
                                                   value="<?= htmlspecialchars($remark) ?>" 
                                                   class="form-control form-control-sm" 
                                                   placeholder="e.g. Batch # / Notes">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <span class="text-muted small"><i class="bi bi-info-circle me-1"></i>All 20 standard items will be stored or updated for <strong><?= $report_period_label ?></strong>.</span>
                    <button type="submit" class="btn btn-sm text-light fw-bold px-4 shadow-sm" style="background-color: #820100;">
                        <i class="bi bi-check2-circle me-1"></i>Save Monthly Crop Return
                    </button>
                </div>
            </div>
        </form>

        <!-- 3. OFFICIAL SIGNATURE & RUBBER STAMP SECTION (FOR PRINT & EXPORT) -->
        <div class="card shadow-sm border-0 mb-4 p-4 bg-light">
            <h6 class="fw-bold text-dark text-uppercase mb-4 border-bottom pb-2">
                <i class="bi bi-award-fill text-danger me-2"></i>Official Certification & Verification
            </h6>
            <div class="row text-center mt-3 pt-2">
                <div class="col-md-6 mb-4 mb-md-0">
                    <div style="min-height: 70px;"></div>
                    <div class="border-top pt-2 mx-auto" style="max-width: 320px;">
                        <div class="fw-bold text-dark">Signature of the Veterinary Surgeon</div>
                        <div class="text-muted small"><?= htmlspecialchars($officer_name) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($officer_designation) ?></div>
                        <div class="text-muted small mt-1">Date: .................................................</div>
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

    </div>

    <!-- ============================================================ -->
    <!-- TAB 2: Full Year Breakdown Matrix Table (Jan – Dec) -->
    <!-- ============================================================ -->
    <div class="tab-pane fade" id="pane-annual-matrix" role="tabpanel" aria-labelledby="tab-annual-matrix">
        <!-- MONTHLY CROP RETURN SUMMARY & CUMULATIVE YTD MATRIX TABLE -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h6 class="mb-0 fw-bold" style="color: #370709;">
                        <i class="bi bi-table me-2 text-danger"></i>Monthly Crop Return Summary & Progressive Cumulative YTD Matrix (<?= $selected_year ?>)
                    </h6>
                    <small class="text-muted">Progressive cumulative column automatically sums metrics from January up to <strong><?= $month_names[$selected_month] ?></strong>.</small>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-success" id="btnExportMidYearCrop">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export Mid-Year (Jan–Jun)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="btnExportAnnualCrop">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Export Annual (Jan–Dec)
                    </button>
                </div>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100 text-nowrap small" id="cropSummaryTable">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th style="min-width: 180px;">Item Name (20 Standard Items)</th>
                                <th class="text-center" style="width: 80px;">Metric</th>
                                <?php for ($m = 1; $m <= 6; $m++): ?>
                                    <th class="text-end"><?= $month_shorts[$m] ?></th>
                                <?php endfor; ?>
                                <th class="text-end bg-primary-subtle text-primary fw-bold">Mid-Year Total</th>
                                <?php for ($m = 7; $m <= 12; $m++): ?>
                                    <th class="text-end"><?= $month_shorts[$m] ?></th>
                                <?php endfor; ?>
                                <th class="text-end bg-danger-subtle text-danger fw-bold">Cumulative YTD (to <?= $month_shorts[$selected_month] ?>)</th>
                                <th class="text-end bg-dark-subtle text-dark fw-bold">Annual Total</th>
                                <th class="text-end bg-light text-dark fw-bold">Current Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($standard_items as $item_name): 
                                $mid_year_issued = 0;
                                $annual_issued   = 0;
                                $ytd_issued      = 0;

                                for ($m = 1; $m <= 6; $m++) {
                                    $mid_year_issued += ($annual_crop_data[$item_name][$m]['issued'] ?? 0);
                                }
                                for ($m = 1; $m <= 12; $m++) {
                                    $annual_issued += ($annual_crop_data[$item_name][$m]['issued'] ?? 0);
                                }
                                for ($m = 1; $m <= $selected_month; $m++) {
                                    $ytd_issued += ($annual_crop_data[$item_name][$m]['issued'] ?? 0);
                                }
                                $curr_ending_bal = $annual_crop_data[$item_name][$selected_month]['balance'] ?? ($prev_balances[$item_name] ?? 0);
                            ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($item_name) ?></td>
                                    <td class="text-center"><span class="badge bg-secondary-subtle text-secondary border">Issued</span></td>
                                    <?php for ($m = 1; $m <= 6; $m++): ?>
                                        <td class="text-end font-monospace"><?= number_format($annual_crop_data[$item_name][$m]['issued'] ?? 0) ?></td>
                                    <?php endfor; ?>
                                    <td class="text-end font-monospace bg-primary-subtle text-primary fw-bold"><?= number_format($mid_year_issued) ?></td>
                                    <?php for ($m = 7; $m <= 12; $m++): ?>
                                        <td class="text-end font-monospace"><?= number_format($annual_crop_data[$item_name][$m]['issued'] ?? 0) ?></td>
                                    <?php endfor; ?>
                                    <td class="text-end font-monospace bg-danger-subtle text-danger fw-bold"><?= number_format($ytd_issued) ?></td>
                                    <td class="text-end font-monospace bg-dark-subtle text-dark fw-bold"><?= number_format($annual_issued) ?></td>
                                    <td class="text-end font-monospace fw-bold"><?= number_format($curr_ending_bal) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- TAB 3: Custom Period Summary (e.g. Jan – Mar) -->
    <!-- ============================================================ -->
    <div class="tab-pane fade" id="pane-range-summary" role="tabpanel" aria-labelledby="tab-range-summary">
        <!-- CUSTOM PERIOD / MULTI-MONTH RANGE SUMMARY (e.g. Jan – March) -->
        <div class="card shadow-sm border-0 mb-4" id="customRangeSummaryCard" style="border-left: 5px solid #370709 !important;">
            <div class="card-header bg-white py-3 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <span class="badge bg-danger-subtle text-danger px-2 py-1 mb-1 fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                        <i class="bi bi-calendar-range me-1"></i>Custom Date / Month Range Summary
                    </span>
                    <h5 class="fw-bold mb-0" style="color: #370709;">
                        <i class="bi bi-filter-square-fill me-2 text-danger"></i>Crop Returns Period Summary: <span id="rangeLabelText" class="text-danger"><?= htmlspecialchars($range_period_label) ?></span>
                    </h5>
                    <small class="text-muted">Generate instant aggregated stock, receipt, issuance, and closing balances for any interval between months (e.g., Jan – Mar).</small>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-dark shadow-sm" id="btnPrintRangeReport">
                        <i class="bi bi-printer me-1"></i>Print Period Report
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success shadow-sm" id="btnExportRangeCsv">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger shadow-sm" id="btnExportRangePdf">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
                    </button>
                </div>
            </div>
            <div class="card-body p-4 pt-2">
                <!-- Range Filter Controls & Quick Presets -->
                <div class="p-3 bg-light rounded border mb-4">
                    <div class="row g-3 align-items-center">
                        <div class="col-auto">
                            <label class="form-label small fw-bold mb-0 text-muted"><i class="bi bi-funnel-fill me-1"></i>Select Months:</label>
                        </div>
                        <div class="col-sm-3 col-md-2">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white fw-bold">From:</span>
                                <select id="rangeFromMonth" class="form-select form-select-sm">
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?= $m ?>" <?= $from_month === $m ? 'selected' : '' ?>><?= $month_names[$m] ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-3 col-md-2">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white fw-bold">To:</span>
                                <select id="rangeToMonth" class="form-select form-select-sm">
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?= $m ?>" <?= $to_month === $m ? 'selected' : '' ?>><?= $month_names[$m] ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-sm text-light fw-bold px-3 shadow-sm" style="background-color: #820100;" id="btnApplyRange">
                                <i class="bi bi-arrow-repeat me-1"></i>Update Summary
                            </button>
                        </div>
                        <div class="col-12 col-xl mt-2 mt-xl-0 text-xl-end">
                            <span class="small fw-semibold text-muted me-2">Quick Presets:</span>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-secondary btn-range-preset <?= ($from_month === 1 && $to_month === 3) ? 'active fw-bold' : '' ?>" data-from="1" data-to="3" title="January to March">Q1 (Jan–Mar)</button>
                                <button type="button" class="btn btn-outline-secondary btn-range-preset <?= ($from_month === 4 && $to_month === 6) ? 'active fw-bold' : '' ?>" data-from="4" data-to="6" title="April to June">Q2 (Apr–Jun)</button>
                                <button type="button" class="btn btn-outline-secondary btn-range-preset <?= ($from_month === 1 && $to_month === 6) ? 'active fw-bold' : '' ?>" data-from="1" data-to="6" title="January to June">Mid-Year (Jan–Jun)</button>
                                <button type="button" class="btn btn-outline-secondary btn-range-preset <?= ($from_month === 7 && $to_month === 9) ? 'active fw-bold' : '' ?>" data-from="7" data-to="9" title="July to September">Q3 (Jul–Sep)</button>
                                <button type="button" class="btn btn-outline-secondary btn-range-preset <?= ($from_month === 10 && $to_month === 12) ? 'active fw-bold' : '' ?>" data-from="10" data-to="12" title="October to December">Q4 (Oct–Dec)</button>
                                <button type="button" class="btn btn-outline-secondary btn-range-preset <?= ($from_month === 1 && $to_month === 12) ? 'active fw-bold' : '' ?>" data-from="1" data-to="12" title="January to December">Full Year</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Range Metrics Overview Badges -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-lg-3">
                        <div class="p-3 rounded bg-white border text-center shadow-xs">
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Reporting Interval</div>
                            <div class="h6 fw-bold text-dark mt-1 mb-0" id="pillInterval"><?= htmlspecialchars($range_period_label) ?></div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="p-3 rounded bg-white border text-center shadow-xs">
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Total Units Received</div>
                            <div class="h5 fw-bold text-success mt-1 mb-0 font-monospace" id="pillTotalReceived">0</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="p-3 rounded bg-white border text-center shadow-xs">
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Total Units Issued</div>
                            <div class="h5 fw-bold text-danger mt-1 mb-0 font-monospace" id="pillTotalIssued">0</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="p-3 rounded bg-white border text-center shadow-xs">
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Items with Activity</div>
                            <div class="h5 fw-bold text-primary mt-1 mb-0" id="pillActiveItems">20 Standard Items</div>
                        </div>
                    </div>
                </div>

                <!-- Period Summary Table -->
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle w-100 text-nowrap small" id="rangeSummaryTable">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th style="width: 4%;" class="text-center">#</th>
                                <th style="width: 26%;">Office / Veterinary Item Name</th>
                                <th style="width: 14%;" class="text-end bg-light fw-bold">Opening Balance (<span class="lblFromMonth"><?= $month_shorts[$from_month] ?></span>)</th>
                                <th style="width: 14%;" class="text-end text-success fw-bold">Total Received</th>
                                <th style="width: 14%;" class="text-end text-danger fw-bold">Total Issued</th>
                                <th style="width: 14%;" class="text-end bg-warning-subtle text-dark fw-bold">Closing Balance (<span class="lblToMonth"><?= $month_shorts[$to_month] ?></span>)</th>
                                <th style="width: 14%;" class="text-center">Monthly Breakdown</th>
                            </tr>
                        </thead>
                        <tbody id="rangeSummaryBody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- ISOLATED PRINTABLE SUMMARY REPORT (TRIGGERS VIA PRINT UTILITY) -->
<!-- ============================================================ -->
<div id="printableSummaryReport" class="p-4 bg-white" style="display: none;">
    <div class="text-center mb-4 pb-3 border-bottom border-2 border-dark">
        <h5 class="fw-bold mb-1 text-uppercase" style="letter-spacing: 0.5px;">Department of Animal Production & Health (Eastern Province)</h5>
        <h6 class="fw-bold mb-1">Government Veterinary Surgeon's Office - <?= htmlspecialchars($range_name) ?> Range</h6>
        <p class="small text-muted mb-2"><?= htmlspecialchars($district_name) ?> District | Official Stores & Biological Crop Return</p>
        <div class="badge bg-dark text-white fs-6 px-3 py-1 text-uppercase">
            Period Summary: <span id="printPeriodRangeText"><?= htmlspecialchars($range_period_label) ?></span>
        </div>
    </div>

    <div class="row mb-3 small">
        <div class="col-6"><strong>Officer:</strong> <?= htmlspecialchars($officer_name) ?> (<?= htmlspecialchars($officer_designation) ?>)</div>
        <div class="col-6 text-end"><strong>Report Period:</strong> <span id="printPeriodSub"><?= htmlspecialchars($range_period_label) ?></span></div>
    </div>

    <table class="table table-bordered align-middle small mb-4" id="printRangeTable">
        <thead class="table-secondary text-uppercase text-dark" style="font-size: 0.78rem;">
            <tr>
                <th style="width: 4%;" class="text-center">#</th>
                <th style="width: 32%;">Office / Veterinary Item Name</th>
                <th style="width: 16%;" class="text-end">Opening Balance</th>
                <th style="width: 16%;" class="text-end">Total Received</th>
                <th style="width: 16%;" class="text-end">Total Issued</th>
                <th style="width: 16%;" class="text-end">Closing Balance</th>
            </tr>
        </thead>
        <tbody id="printRangeBody">
            <!-- Dynamically populated -->
        </tbody>
    </table>

    <div class="row text-center mt-5 pt-4">
        <div class="col-6">
            <div style="min-height: 60px;"></div>
            <div class="border-top pt-2 mx-auto" style="max-width: 280px;">
                <div class="fw-bold">Signature of Veterinary Surgeon</div>
                <div class="small text-muted"><?= htmlspecialchars($officer_name) ?></div>
                <div class="small text-muted"><?= htmlspecialchars($officer_designation) ?></div>
                <div class="small text-muted mt-1">Date: .................................................</div>
            </div>
        </div>
        <div class="col-6">
            <div style="min-height: 60px;"></div>
            <div class="border-top pt-2 mx-auto" style="max-width: 280px;">
                <div class="fw-bold">Official Rubber Stamp</div>
                <div class="small text-muted">Government Veterinary Office / Range</div>
                <div class="small text-muted"><?= htmlspecialchars($range_name) ?> Range, <?= htmlspecialchars($district_name) ?></div>
                <div class="small text-muted mt-1">Official Seal</div>
            </div>
        </div>
    </div>
</div>
    </div>
</div>

</main>
</div>

<!-- Add / Edit Modals for individual records -->
<?php include 'model/add_crop_return.php'; ?>
<?php include 'model/edit_crop_return.php'; ?>

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
window.CropReturnsConfig = {
    officerName: <?= json_encode($officer_name) ?>,
    officerDesignation: <?= json_encode($officer_designation) ?>,
    ministryDepartment: <?= json_encode($ministry_department) ?>,
    reportPeriod: <?= json_encode($report_period_label) ?>,
    rangeName: <?= json_encode($range_name) ?>,
    districtName: <?= json_encode($district_name) ?>,
    selectedYear: <?= json_encode($selected_year) ?>,
    fromMonth: <?= json_encode($from_month) ?>,
    toMonth: <?= json_encode($to_month) ?>,
    chartMonths: <?= json_encode($chart_months) ?>,
    chartMonthlyIssued: <?= json_encode($chart_monthly_issued) ?>,
    chartCumulativeIssued: <?= json_encode($chart_cumulative_issued) ?>,
    annualCropData: <?= json_encode($annual_crop_data) ?>,
    standardItems: <?= json_encode($standard_items) ?>,
    prevBalances: <?= json_encode($prev_balances) ?>,
    monthNames: <?= json_encode($month_names) ?>,
    monthShorts: <?= json_encode($month_shorts) ?>
};
</script>
<script src="../../../assets/js/veterinary.js"></script>