<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$allowed_roles = [
    'veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon',
    'district_dd', 'deputy_director_district', 'sms', 'provincial_director', 'administrator'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    header("Location: ../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? '';
$is_supervisory = in_array($user_role, ['district_dd', 'deputy_director_district', 'sms', 'provincial_director', 'administrator']);
$district_id = $_SESSION['district_id'] ?? null;

// Fetch Officer Details & Designation
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

// Fetch list of accessible ranges for selection
$available_ranges = [];
if ($district_id && !in_array($user_role, ['provincial_director', 'administrator'])) {
    $r_stmt = $mysqli->prepare("SELECT id, name FROM veterinary_ranges WHERE district_id = ? ORDER BY name ASC");
    if ($r_stmt) {
        $r_stmt->bind_param("i", $district_id);
        $r_stmt->execute();
        $r_res = $r_stmt->get_result();
        while ($r_row = $r_res->fetch_assoc()) {
            $available_ranges[] = $r_row;
        }
        $r_stmt->close();
    }
} else {
    $r_res = $mysqli->query("SELECT id, name FROM veterinary_ranges ORDER BY name ASC");
    if ($r_res) {
        while ($r_row = $r_res->fetch_assoc()) {
            $available_ranges[] = $r_row;
        }
    }
}

// Resolve Range ID
$requested_range_id = isset($_GET['range_id']) && is_numeric($_GET['range_id']) ? intval($_GET['range_id']) : null;
$range_id = $requested_range_id ?: ($_SESSION['range_id'] ?? null);

if (empty($range_id) && !empty($available_ranges)) {
    $range_id = intval($available_ranges[0]['id']);
}

$range_name = 'Your Range';
$district_name = 'Your District';
$selected_year  = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('n'));

// Fetch Range and District Info using a JOIN query
if (!empty($range_id)) {
    $details_sql = "
        SELECT 
            vr.name AS range_name,
            d.name AS district_name,
            vr.district_id
        FROM veterinary_ranges vr
        LEFT JOIN districts d ON vr.district_id = d.id
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
            if (empty($district_id) && !empty($data['district_id'])) {
                $district_id = $data['district_id'];
            }
        }
        $details_query->close();
    }
}

// Month names helper
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

$report_period_label = ($month_names[$selected_month] ?? '') . ' ' . $selected_year;

// Fetch Vaccine Balance records matching year filter and range
$records = [];
$monthly_vaccine_matrix = []; // [vaccine_name][month] => ['received' => X, 'used' => Y, 'closing' => Z]
$all_vaccine_names = [];
$monthly_used_totals = array_fill(1, 12, 0);
$monthly_received_totals = array_fill(1, 12, 0);

if (!empty($range_id)) {
    $records_sql = "
        SELECT id, report_year, report_month, vaccine_name, opening_balance, 
               received_doses, used_doses, spoilt_damaged_doses, transferred_doses, 
               closing_balance, batch_no, expiry_date, remarks 
        FROM monthly_vaccine_balances 
        WHERE range_id = ? AND report_year = ? 
        ORDER BY report_month DESC, id DESC
    ";
    $stmt = $mysqli->prepare($records_sql);
    if ($stmt) {
        $stmt->bind_param("ii", $range_id, $selected_year);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $records[] = $row;
            $v_name = trim($row['vaccine_name']);
            $m = intval($row['report_month']);
            $all_vaccine_names[$v_name] = true;

            if (!isset($monthly_vaccine_matrix[$v_name][$m])) {
                $monthly_vaccine_matrix[$v_name][$m] = ['received' => 0, 'used' => 0, 'closing' => 0];
            }
            $monthly_vaccine_matrix[$v_name][$m]['received'] += intval($row['received_doses']);
            $monthly_vaccine_matrix[$v_name][$m]['used']     += intval($row['used_doses']);
            $monthly_vaccine_matrix[$v_name][$m]['closing']   = intval($row['closing_balance']);

            if ($m >= 1 && $m <= 12) {
                $monthly_used_totals[$m]     += intval($row['used_doses']);
                $monthly_received_totals[$m] += intval($row['received_doses']);
            }
        }
        $stmt->close();
    }
}
ksort($all_vaccine_names);

// Calculate summary metrics
$tot_received = 0;
$tot_used = 0;
$tot_closing = 0;
foreach ($records as $r) {
    $tot_received += intval($r['received_doses']);
    $tot_used += intval($r['used_doses']);
    $tot_closing += intval($r['closing_balance']);
}

// Progressive Cumulative YTD totals for Chart
$chart_months = [];
$chart_used_totals = [];
$chart_received_totals = [];
$chart_cumulative_used = [];
$running_cum_used = 0;

for ($m = 1; $m <= 12; $m++) {
    $chart_months[] = $month_shorts[$m];
    $u = $monthly_used_totals[$m];
    $r = $monthly_received_totals[$m];
    $chart_used_totals[] = $u;
    $chart_received_totals[] = $r;
    $running_cum_used += $u;
    $chart_cumulative_used[] = $running_cum_used;
}

// Cumulative YTD up to selected month
$current_ytd_used = 0;
for ($m = 1; $m <= $selected_month; $m++) {
    $current_ytd_used += $monthly_used_totals[$m];
}

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">

<style>
    .table-ytd {
        background-color: #fef8f8 !important;
        font-weight: 700;
        color: #820100;
    }
    .table-midyear {
        background-color: #f0f7ff !important;
        font-weight: 700;
        color: #0d6efd;
    }
</style>

<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h3 class="fw-bold mb-1" style="color: #370709;">Vaccine Balance - Monthly Returns & Cumulative YTD</h3>
        <p class="text-muted small mb-0">Track stock levels, field utilization, progressive cumulative summaries, and mid-year/annual exports for <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong>.</p>
    </div>
    
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <form method="GET" class="d-flex align-items-center gap-2">
            <?php if (!empty($available_ranges) && ($is_supervisory || count($available_ranges) > 1)): ?>
                <label class="small fw-bold text-muted mb-0">Range:</label>
                <select name="range_id" class="form-select form-select-sm border-secondary" onchange="this.form.submit()" style="min-width: 170px;">
                    <?php foreach ($available_ranges as $ar): ?>
                        <option value="<?= $ar['id'] ?>" <?= ($ar['id'] == $range_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ar['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <input type="hidden" name="range_id" value="<?= htmlspecialchars($range_id) ?>">
            <?php endif; ?>

            <label class="small fw-bold text-muted mb-0 ms-1">Month:</label>
            <select name="month" class="form-select form-select-sm border-secondary" onchange="this.form.submit()" style="min-width: 125px;">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= ($m === $selected_month) ? 'selected' : '' ?>>
                        <?= $month_names[$m] ?>
                    </option>
                <?php endfor; ?>
            </select>

            <label class="small fw-bold text-muted mb-0 ms-1">Year:</label>
            <select name="year" class="form-select form-select-sm border-secondary" onchange="this.form.submit()" style="width: 95px;">
                <?php
                $curr_year = intval(date('Y'));
                for ($y = $curr_year - 5; $y <= $curr_year + 5; $y++) {
                    $sel = ($y === $selected_year) ? 'selected' : '';
                    echo "<option value=\"$y\" $sel>$y</option>";
                }
                ?>
            </select>
        </form>
        <a href="monthly-annual-reports.php" class="btn btn-sm btn-secondary shadow-sm text-nowrap">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<?php if (isset($_SESSION['msg'])): ?>
    <div class="alert alert-<?= htmlspecialchars($_SESSION['msg_type'] ?? 'info') ?> alert-dismissible fade show shadow-sm py-2 px-3 mb-4 small" role="alert">
        <?= $_SESSION['msg'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
<?php endif; ?>

<!-- Metric KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #820100 !important;">
            <div class="card-body p-3">
                <span class="text-muted small fw-bold text-uppercase">Total Monthly Logs</span>
                <h4 class="fw-bold mb-0 text-dark"><?= count($records) ?></h4>
                <div class="text-muted small mt-1">Recorded for Year <?= htmlspecialchars($selected_year) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #10b981 !important;">
            <div class="card-body p-3">
                <span class="text-muted small fw-bold text-uppercase">Total Received Doses</span>
                <h4 class="fw-bold mb-0 text-success">+<?= number_format($tot_received) ?></h4>
                <div class="text-muted small mt-1">Delivered to <?= htmlspecialchars($range_name) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #3b82f6 !important;">
            <div class="card-body p-3">
                <span class="text-muted small fw-bold text-uppercase">Total Used (Annual)</span>
                <h4 class="fw-bold mb-0 text-primary">-<?= number_format($tot_used) ?></h4>
                <div class="text-muted small mt-1">Vaccinated in field sessions</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #f59e0b !important;">
            <div class="card-body p-3">
                <span class="text-muted small fw-bold text-uppercase">Cumulative YTD (Jan–<?= $month_shorts[$selected_month] ?>)</span>
                <h4 class="fw-bold mb-0 text-dark"><?= number_format($current_ytd_used) ?></h4>
                <div class="text-muted small mt-1">Progressive used doses up to <?= $month_shorts[$selected_month] ?></div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- QUICK ACTIONS & INTEGRATED BATCH TOOLS -->
<!-- ============================================================ -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap-fill me-2 text-danger"></i>Quick Actions & Integrated Tools</h6>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-success" id="btnExportMidYear">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export Mid-Year Summary (Jan–Jun)
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" id="btnExportAnnual">
                <i class="bi bi-file-earmark-pdf me-1"></i>Export Annual Summary (Jan–Dec)
            </button>
            <button type="button" class="btn btn-sm btn-dark" onclick="window.print()">
                <i class="bi bi-printer me-1"></i>Print Report
            </button>
        </div>
    </div>
    <div class="card-body pt-0">
        <div class="row g-3">
            <div class="col-md-4">
                <button class="btn w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #820100; min-height: 105px;" data-bs-toggle="modal" data-bs-target="#addVaccineBalanceModal">
                    <i class="bi bi-plus-circle fs-3 mb-1 text-warning"></i>
                    <span class="small fw-bold text-uppercase">Add Vaccine Record</span>
                </button>
            </div>
            <div class="col-md-4">
                <button type="button" class="btn w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center text-decoration-none" style="background-color: #b08723; min-height: 105px;" data-bs-toggle="modal" data-bs-target="#addVaccineBatchModal">
                    <i class="bi bi-box-seam fs-3 mb-1 text-light"></i>
                    <span class="small fw-bold text-uppercase">Register Vaccine Batch</span>
                </button>
            </div>
            <div class="col-md-4">
                <a href="drug_maintenance.php<?= $range_id ? '?range_id=' . $range_id : '' ?>" class="btn w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center text-decoration-none" style="background-color: #2b3a4a; min-height: 105px;">
                    <i class="bi bi-capsule fs-3 mb-1 text-light"></i>
                    <span class="small fw-bold text-uppercase">Drug Maintenance</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MONTHLY SUMMARY CHART WITH PROGRESSIVE CUMULATIVE YTD -->
<!-- ============================================================ -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h6 class="mb-0 fw-bold" style="color: #370709;">
                <i class="bi bi-graph-up me-2 text-danger"></i>Monthly Vaccine Utilization & Progressive Cumulative Trend (<?= $selected_year ?>)
            </h6>
            <small class="text-muted">Displays monthly doses administered vs. progressive cumulative Year-to-Date (YTD) total.</small>
        </div>
        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
            Currently Viewed Month: <strong><?= $month_names[$selected_month] ?></strong> (YTD: <?= number_format($current_ytd_used) ?> doses)
        </span>
    </div>
    <div class="card-body">
        <canvas id="vaccineYtdChart" height="75"></canvas>
    </div>
</div>

<!-- ============================================================ -->
<!-- MONTHLY VACCINE SUMMARY & CUMULATIVE YTD MATRIX TABLE -->
<!-- ============================================================ -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h6 class="mb-0 fw-bold" style="color: #370709;">
                <i class="bi bi-table me-2 text-danger"></i>Monthly Vaccine Summary & Progressive Cumulative YTD Matrix (<?= $selected_year ?>)
            </h6>
            <small class="text-muted">Progressive cumulative column automatically sums metrics from January up to <strong><?= $month_names[$selected_month] ?></strong>.</small>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-success" id="btnExportMidYear">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export Mid-Year (Jan–Jun)
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" id="btnExportAnnual">
                <i class="bi bi-file-earmark-pdf me-1"></i>Export Annual (Jan–Dec)
            </button>
        </div>
    </div>
    <div class="card-body p-3">
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle w-100 text-nowrap small" id="vaccineSummaryTable">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th style="min-width: 180px;">Vaccine Name</th>
                        <th class="text-center" style="width: 80px;">Metric</th>
                        <?php for ($m = 1; $m <= 6; $m++): ?>
                            <th class="text-end"><?= $month_shorts[$m] ?></th>
                        <?php endfor; ?>
                        <th class="text-end table-midyear">Mid-Year Total</th>
                        <?php for ($m = 7; $m <= 12; $m++): ?>
                            <th class="text-end"><?= $month_shorts[$m] ?></th>
                        <?php endfor; ?>
                        <th class="text-end table-ytd">Cumulative YTD (to <?= $month_shorts[$selected_month] ?>)</th>
                        <th class="text-end bg-dark-subtle text-dark fw-bold">Annual Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($all_vaccine_names)): ?>
                        <?php foreach (array_keys($all_vaccine_names) as $v_name): 
                            $mid_year_sum = 0;
                            $annual_sum   = 0;
                            $ytd_sum      = 0;

                            for ($m = 1; $m <= 6; $m++) {
                                $mid_year_sum += ($monthly_vaccine_matrix[$v_name][$m]['used'] ?? 0);
                            }
                            for ($m = 1; $m <= 12; $m++) {
                                $annual_sum += ($monthly_vaccine_matrix[$v_name][$m]['used'] ?? 0);
                            }
                            for ($m = 1; $m <= $selected_month; $m++) {
                                $ytd_sum += ($monthly_vaccine_matrix[$v_name][$m]['used'] ?? 0);
                            }
                        ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($v_name) ?></td>
                                <td class="text-center"><span class="badge bg-secondary-subtle text-secondary border">Used</span></td>
                                <?php for ($m = 1; $m <= 6; $m++): ?>
                                    <td class="text-end font-monospace"><?= number_format($monthly_vaccine_matrix[$v_name][$m]['used'] ?? 0) ?></td>
                                <?php endfor; ?>
                                <td class="text-end font-monospace table-midyear"><?= number_format($mid_year_sum) ?></td>
                                <?php for ($m = 7; $m <= 12; $m++): ?>
                                    <td class="text-end font-monospace"><?= number_format($monthly_vaccine_matrix[$v_name][$m]['used'] ?? 0) ?></td>
                                <?php endfor; ?>
                                <td class="text-end font-monospace table-ytd"><?= number_format($ytd_sum) ?></td>
                                <td class="text-end font-monospace bg-dark-subtle text-dark fw-bold"><?= number_format($annual_sum) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <!-- Grand Totals Row -->
                        <tr class="table-light fw-bold">
                            <td colspan="2" class="text-uppercase small text-muted">All Vaccines Total (Used):</td>
                            <?php 
                            $tot_mid_year = 0;
                            $tot_annual   = 0;
                            $tot_ytd      = 0;
                            for ($m = 1; $m <= 6; $m++): 
                                $tot_mid_year += $monthly_used_totals[$m];
                            ?>
                                <td class="text-end font-monospace"><?= number_format($monthly_used_totals[$m]) ?></td>
                            <?php endfor; ?>
                            <td class="text-end font-monospace table-midyear"><?= number_format($tot_mid_year) ?></td>
                            <?php for ($m = 7; $m <= 12; $m++): 
                                $tot_annual += $monthly_used_totals[$m];
                            ?>
                                <td class="text-end font-monospace"><?= number_format($monthly_used_totals[$m]) ?></td>
                            <?php endfor; ?>
                            <?php 
                            for ($m = 1; $m <= $selected_month; $m++) {
                                $tot_ytd += $monthly_used_totals[$m];
                            }
                            $tot_annual += $tot_mid_year;
                            ?>
                            <td class="text-end font-monospace table-ytd"><?= number_format($tot_ytd) ?></td>
                            <td class="text-end font-monospace bg-dark-subtle text-dark fw-bold"><?= number_format($tot_annual) ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- INDIVIDUAL MONTHLY LOGS TABLE -->
<!-- ============================================================ -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-bold" style="color: #370709;"><i class="bi bi-file-earmark-medical me-2 text-danger"></i>Individual Vaccine Stock Balance Records for <?= htmlspecialchars($selected_year) ?></h6>
        <span class="badge" style="background-color: #d4c7b7; color: #370709;"><?= htmlspecialchars($range_name) ?> Range</span>
    </div>
    <div class="card-body p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small" id="vaccineTable">
                <thead class="bg-light small text-uppercase" style="background-color: #d4c7b7; color: #370709;">
                    <tr>
                        <th>Month</th>
                        <th>Vaccine Name</th>
                        <th>Batch No.</th>
                        <th class="text-end">Opening (Doses)</th>
                        <th class="text-end">Received</th>
                        <th class="text-end">Used</th>
                        <th class="text-end">Spoilt</th>
                        <th class="text-end">Transferred</th>
                        <th class="text-end">Closing</th>
                        <th>Expiry</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($records)): ?>
                        <?php foreach ($records as $row): ?>
                            <tr
                                data-id="<?= $row['id'] ?>"
                                data-year="<?= htmlspecialchars($row['report_year']) ?>"
                                data-month="<?= htmlspecialchars($row['report_month']) ?>"
                                data-name="<?= htmlspecialchars($row['vaccine_name']) ?>"
                                data-batch="<?= htmlspecialchars($row['batch_no'] ?? '') ?>"
                                data-opening="<?= htmlspecialchars($row['opening_balance']) ?>"
                                data-received="<?= htmlspecialchars($row['received_doses']) ?>"
                                data-used="<?= htmlspecialchars($row['used_doses']) ?>"
                                data-spoilt="<?= htmlspecialchars($row['spoilt_damaged_doses']) ?>"
                                data-transferred="<?= htmlspecialchars($row['transferred_doses']) ?>"
                                data-closing="<?= htmlspecialchars($row['closing_balance']) ?>"
                                data-expiry="<?= htmlspecialchars($row['expiry_date'] ?? '') ?>"
                                data-remarks="<?= htmlspecialchars($row['remarks'] ?? '') ?>">
                                <td data-order="<?= $row['report_month'] ?>" class="fw-bold"><?= htmlspecialchars($month_names[$row['report_month']] ?? $row['report_month']) ?></td>
                                <td><strong><?= htmlspecialchars($row['vaccine_name']) ?></strong></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['batch_no'] ?? 'N/A') ?></span></td>
                                <td class="text-end font-monospace text-primary"><?= number_format($row['opening_balance']) ?></td>
                                <td class="text-end font-monospace text-success">+<?= number_format($row['received_doses']) ?></td>
                                <td class="text-end font-monospace text-danger">-<?= number_format($row['used_doses']) ?></td>
                                <td class="text-end font-monospace text-muted">-<?= number_format($row['spoilt_damaged_doses']) ?></td>
                                <td class="text-end font-monospace text-warning">-<?= number_format($row['transferred_doses']) ?></td>
                                <td class="text-end font-monospace fw-bold text-dark bg-light"><?= number_format($row['closing_balance']) ?></td>
                                <td><span class="small font-monospace text-danger"><?= htmlspecialchars($row['expiry_date'] ?? 'N/A') ?></span></td>
                                <td class="text-end">
                                    <button class="btn btn-xs btn-outline-primary btn-edit-vac" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                    <button class="btn btn-xs btn-outline-danger btn-delete-vac" title="Delete"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- OFFICIAL SIGNATURE & RUBBER STAMP SECTION (FOR PRINT & EXPORT) -->
<!-- ============================================================ -->
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

</main>
</div>

<!-- Modals -->
<?php include 'model/add_vaccine_balance_modal.php'; ?>
<?php include 'model/edit_vaccine_balance_modal.php'; ?>
<?php include 'model/vaccine_batch_modal.php'; ?>

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
$(document).ready(function() {
    var officerName        = <?= json_encode($officer_name) ?>;
    var officerDesignation = <?= json_encode($officer_designation) ?>;
    var ministryDepartment = <?= json_encode($ministry_department) ?>;
    var reportPeriod       = <?= json_encode($report_period_label) ?>;
    var selectedYear       = <?= json_encode($selected_year) ?>;
    var selectedMonthName  = <?= json_encode($month_names[$selected_month]) ?>;

    // -------------------------------------------------------------
    // INITIALIZE CHART.JS (PROGRESSIVE CUMULATIVE YTD TREND)
    // -------------------------------------------------------------
    var ctx = document.getElementById('vaccineYtdChart').getContext('2d');
    var vaccineYtdChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_months) ?>,
            datasets: [
                {
                    label: 'Monthly Used (Administered)',
                    data: <?= json_encode($chart_used_totals) ?>,
                    backgroundColor: 'rgba(130, 1, 0, 0.55)',
                    borderColor: '#820100',
                    borderWidth: 1,
                    order: 2
                },
                {
                    label: 'Monthly Received Doses',
                    data: <?= json_encode($chart_received_totals) ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.45)',
                    borderColor: '#10b981',
                    borderWidth: 1,
                    order: 3
                },
                {
                    label: 'Progressive Cumulative YTD (Used)',
                    data: <?= json_encode($chart_cumulative_used) ?>,
                    type: 'line',
                    borderColor: '#2b3a4a',
                    backgroundColor: 'rgba(43, 58, 74, 0.1)',
                    fill: false,
                    tension: 0.3,
                    borderWidth: 2.5,
                    pointRadius: 4,
                    pointBackgroundColor: '#820100',
                    order: 1
                }
            ]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return Number(value).toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // -------------------------------------------------------------
    // INITIALIZE SUMMARY MATRIX DATATABLE
    // -------------------------------------------------------------
    var summaryTable = $('#vaccineSummaryTable').DataTable({
        "pageLength": 25,
        "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
        "buttons": [
            {
                extend: 'csv',
                className: 'btn btn-sm btn-success me-2',
                text: '<i class="bi bi-file-earmark-spreadsheet"></i> CSV'
            },
            {
                extend: 'pdf',
                className: 'btn btn-sm btn-danger me-2',
                text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                orientation: 'landscape',
                pageSize: 'A4',
                title: 'Vaccine Balance Summary - ' + selectedYear
            },
            {
                extend: 'print',
                className: 'btn btn-sm btn-dark',
                text: '<i class="bi bi-printer"></i> Print'
            }
        ],
        "language": {
            "emptyTable": "No vaccine records available to summarize for year " + selectedYear + ".",
            "search": "Search summary:"
        }
    });

    // Mid-Year and Annual Export Triggers
    $('#btnExportMidYear').on('click', function() {
        summaryTable.button('.buttons-pdf').trigger();
    });

    $('#btnExportAnnual').on('click', function() {
        summaryTable.button('.buttons-csv').trigger();
    });

    // -------------------------------------------------------------
    // INITIALIZE INDIVIDUAL RECORDS DATATABLE
    // -------------------------------------------------------------
    $('#vaccineTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 10,
        "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
        "buttons": [
            {
                extend: 'csvHtml5',
                text: '<i class="bi bi-file-earmark-spreadsheet"></i> CSV',
                className: 'btn btn-sm btn-success me-2'
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="bi bi-file-pdf"></i> PDF',
                className: 'btn btn-sm btn-danger me-2'
            },
            {
                extend: 'print',
                text: '<i class="bi bi-printer"></i> Print',
                className: 'btn btn-sm btn-dark'
            }
        ],
        "language": {
            "emptyTable": "No vaccine balance records found for year " + selectedYear + ". Click 'Add Vaccine Record' to begin.",
            "search": "Search records:",
            "lengthMenu": "Show _MENU_ records"
        }
    });

    // Check status redirects for SweetAlert feedback
    var urlParams = new URLSearchParams(window.location.search);
    var status = urlParams.get('status');

    if (status === 'success') {
        Swal.fire({
            icon: 'success',
            title: 'Record Saved!',
            text: 'Vaccine balance was processed successfully.',
            confirmButtonColor: '#820100'
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    } else if (status === 'db_error') {
        Swal.fire({
            icon: 'error',
            title: 'Operation Failed',
            text: 'Could not process database action. Check inputs and try again.',
            confirmButtonColor: '#820100'
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // Edit Modal Trigger Pre-fill
    $(document).on('click', '.btn-edit-vac', function() {
        var $row = $(this).closest('tr');
        $('#edit_id').val($row.data('id'));
        $('#edit_report_year').val($row.data('year'));
        $('#edit_report_month').val($row.data('month'));
        $('#edit_vaccine_name').val($row.data('name'));
        $('#edit_batch_no').val($row.data('batch'));
        $('#edit_opening_balance').val($row.data('opening'));
        $('#edit_received_doses').val($row.data('received'));
        $('#edit_used_doses').val($row.data('used'));
        $('#edit_spoilt_doses').val($row.data('spoilt'));
        $('#edit_transferred_doses').val($row.data('transferred'));
        $('#edit_closing_balance').val($row.data('closing'));
        $('#edit_expiry_date').val($row.data('expiry'));
        $('#edit_remarks').val($row.data('remarks'));

        new bootstrap.Modal(document.getElementById('editVaccineBalanceModal')).show();
    });

    // AJAX Delete Confirmation Click Handler
    $(document).on('click', '.btn-delete-vac', function() {
        var $row = $(this).closest('tr');
        var recordId = $row.data('id');
        var name = $row.data('name') || 'this record';

        Swal.fire({
            icon: 'warning',
            title: 'Delete Vaccine Stock Entry?',
            html: 'You are about to delete entry for "<strong>' + name + '</strong>".<br>This action cannot be undone.',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'processors/delete_vaccine_balance.php',
                    type: 'POST',
                    data: { id: recordId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'The record has been deleted.',
                                timer: 1500,
                                showConfirmButton: false
                            });
                            $row.fadeOut(400, function() {
                                $row.remove();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed',
                                text: response.message || 'Error occurred during deletion.'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: 'AJAX request execution failed.'
                        });
                    }
                });
            }
        });
    });

    // Handle inline batch registration AJAX submit
    $('#batchForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize() + '&ajax=1';
        $.ajax({
            url: 'processors/vaccine_batch_crud.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.success && res.batch) {
                    var bNum = res.batch.batch_number;
                    var bExp = res.batch.expiry_date || '';
                    var optText = bNum + (bExp ? ' (Exp: ' + bExp + ')' : '');
                    var newOpt = $('<option>', { value: bNum, text: optText, selected: true }).attr('data-expiry', bExp);
                    
                    $('#add_batch_no').append(newOpt).val(bNum);
                    $('#edit_batch_no').append(newOpt.clone());
                    if (bExp) {
                        $('#add_expiry_date').val(bExp);
                    }
                    
                    var modalEl = document.getElementById('addVaccineBatchModal');
                    var modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    Swal.fire({
                        icon: 'success',
                        title: 'Batch Created!',
                        text: 'Batch ' + bNum + ' registered and selected.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: res.message || 'Could not create batch.'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Request Failed',
                    text: 'Unable to communicate with batch processor.'
                });
            }
        });
    });
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
