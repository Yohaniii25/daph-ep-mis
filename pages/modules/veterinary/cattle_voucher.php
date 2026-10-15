<?php
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

$user_id  = (int)($_SESSION['user_id'] ?? 0);
$user_role = $_SESSION['role'] ?? '';
$session_range_id = (int)($_SESSION['range_id'] ?? 0);

$is_supervisory = in_array($user_role, [
    'district_dd',
    'deputy_director_district',
    'administrator',
    'provincial_director',
    'deputy_director_hq_1',
    'deputy_director_hq_2',
    'admin'
], true);

// Requested range from GET, or session range
$requested_range_id = isset($_GET['range_id']) && is_numeric($_GET['range_id']) ? (int)$_GET['range_id'] : null;
$range_id = ($is_supervisory && $requested_range_id) ? $requested_range_id : ($session_range_id ?: 1);

$range_name = 'Ampara';
$range_code = '01';
$district_name = 'Ampara';
$district_id = 1;

// Extract Range Name, Range Code, District Name, and IDs using standard relational JOIN
if (!empty($range_id)) {
    $details_sql = "
        SELECT 
            vr.name AS range_name,
            vr.code AS range_code,
            d.name AS district_name,
            d.id AS district_id
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
            $range_code = !empty($data['range_code']) ? $data['range_code'] : sprintf('%02d', $range_id);
            $district_name = $data['district_name'] ?? 'Your District';
            $district_id = (int)($data['district_id'] ?? 1);
        }
        $details_query->close();
    }
}

// Active year filter
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

// =========================================================================
// CATTLE VOUCHER MONTHLY SUMMARY DATA AGGREGATION & LIVE RECONCILIATION
// =========================================================================

// 1. Fetch Real-Time Aggregated Used & Damaged Cattle Vouchers from Animal Identifications
$cv_realtime_used = array_fill(1, 12, 0);
$cv_realtime_damaged = array_fill(1, 12, 0);
if (!empty($range_id)) {
    $cv_agg_stmt = $mysqli->prepare("
        SELECT MONTH(identification_date) AS m, 
               COUNT(*) AS total_used,
               SUM(CASE WHEN present_status = 'Tag Damaged / Removed' THEN 1 ELSE 0 END) AS total_damaged
        FROM animal_identifications 
        WHERE (range_id = ? OR range_id IS NULL OR range_id = 0)
          AND YEAR(identification_date) = ? 
          AND voucher_serial_no IS NOT NULL AND voucher_serial_no != '' 
        GROUP BY MONTH(identification_date)
    ");
    if ($cv_agg_stmt) {
        $cv_agg_stmt->bind_param("ii", $range_id, $selected_year);
        $cv_agg_stmt->execute();
        $cv_res = $cv_agg_stmt->get_result();
        while ($row = $cv_res->fetch_assoc()) {
            $cv_realtime_used[intval($row['m'])] = intval($row['total_used']);
            $cv_realtime_damaged[intval($row['m'])] = intval($row['total_damaged']);
        }
        $cv_agg_stmt->close();
    }
}

// 2. Fetch Cattle Voucher Stock Baseline & Movements from cattle_voucher_usage
$raw_cv_records = [];
if (!empty($range_id)) {
    $cv_sql = "
        SELECT id, report_year, report_month, opening_balance, received_qty, used_qty, spoilt_qty, damaged_qty, transferred_qty, closing_balance 
        FROM cattle_voucher_usage 
        WHERE range_id = ? AND report_year = ? 
        ORDER BY report_month ASC
    ";
    $cv_stmt = $mysqli->prepare($cv_sql);
    if ($cv_stmt) {
        $cv_stmt->bind_param("ii", $range_id, $selected_year);
        $cv_stmt->execute();
        $cv_r_res = $cv_stmt->get_result();
        while ($row = $cv_r_res->fetch_assoc()) {
            $raw_cv_records[intval($row['report_month'])] = $row;
        }
        $cv_stmt->close();
    }
}

// 3. Fetch Sequential Distribution Blocks & Real-Time Consumptions
$cv_distributions = [];
$month_distributions = array_fill(1, 12, []);
$cv_grand_allocated = 0;
$cv_grand_dist_used = 0;
$cv_grand_dist_damaged = 0;
$cv_grand_dist_rem = 0;

if (!empty($range_id)) {
    // Collect all vouchers in animal_identifications for this range
    $range_vouchers_map = [];
    $rv_res = $mysqli->query("
        SELECT voucher_serial_no, present_status, farm_reg_no 
        FROM animal_identifications 
        WHERE (range_id = {$range_id} OR range_id IS NULL OR range_id = 0)
          AND voucher_serial_no IS NOT NULL AND voucher_serial_no != ''
    ");
    if ($rv_res) {
        while ($rv = $rv_res->fetch_assoc()) {
            $num = (int)preg_replace('/\D/', '', $rv['voucher_serial_no']);
            if ($num > 0) {
                $range_vouchers_map[$num] = $rv;
            }
        }
    }

    $d_stmt = $mysqli->prepare("SELECT * FROM cattle_voucher_distributions WHERE range_id = ? ORDER BY distribution_date DESC, id DESC");
    if ($d_stmt) {
        $d_stmt->bind_param("i", $range_id);
        $d_stmt->execute();
        $d_res = $d_stmt->get_result();
        while ($drow = $d_res->fetch_assoc()) {
            $s = (int)$drow['start_serial'];
            $e = (int)$drow['end_serial'];
            $p = $drow['prefix'] ?: 'A-';
            $tot = (int)$drow['total_allocated'];

            $used = 0;
            $damaged = 0;
            $next_avail = null;

            for ($curr = $s; $curr <= $e; $curr++) {
                if (isset($range_vouchers_map[$curr])) {
                    $used++;
                    if ($range_vouchers_map[$curr]['present_status'] === 'Tag Damaged / Removed') {
                        $damaged++;
                    }
                } else {
                    if ($next_avail === null) {
                        $next_avail = ($curr >= 100000) ? "{$p}{$curr}" : "{$p}" . str_pad((string)$curr, 6, '0', STR_PAD_LEFT);
                    }
                }
            }

            $rem = $tot - $used;
            $p_start = ($s >= 100000) ? $s : str_pad((string)$s, 6, '0', STR_PAD_LEFT);
            $p_end = ($e >= 100000) ? $e : str_pad((string)$e, 6, '0', STR_PAD_LEFT);

            $drow['used_count'] = $used;
            $drow['damaged_count'] = $damaged;
            $drow['remaining_balance'] = $rem;
            $drow['formatted_range'] = "{$p}{$p_start} – {$p}{$p_end}";
            $drow['next_available'] = $next_avail ?: 'Depleted (0 left)';
            $drow['pct_used'] = $tot > 0 ? round(($used / $tot) * 100, 1) : 0;
            
            $cv_grand_allocated += $tot;
            $cv_grand_dist_used += $used;
            $cv_grand_dist_damaged += $damaged;
            $cv_grand_dist_rem += $rem;

            $d_month = (int)date('n', strtotime($drow['distribution_date']));
            $d_year = (int)date('Y', strtotime($drow['distribution_date']));
            if ($d_year === $selected_year) {
                $month_distributions[$d_month][] = $drow['formatted_range'] . " (" . $drow['farm_reg_no'] . ")";
            }

            $cv_distributions[] = $drow;
        }
        $d_stmt->close();
    }
}

// 4. Build Automated Dynamic 12-Month Cattle Voucher Summary
$cv_monthly_summary = [];
$cv_running_balance = 0;
$cv_grand_received = 0;
$cv_grand_used = 0;
$cv_grand_spoiled = 0;
$cv_grand_damaged = 0;
$cv_grand_transferred = 0;
$cv_first_opening = 0;

// Seed January opening from previous year December if exists
if (!empty($range_id)) {
    $prev_y = $selected_year - 1;
    $cv_seed = $mysqli->prepare("SELECT closing_balance FROM cattle_voucher_usage WHERE range_id = ? AND report_year = ? AND report_month = 12 LIMIT 1");
    if ($cv_seed) {
        $cv_seed->bind_param("ii", $range_id, $prev_y);
        $cv_seed->execute();
        $sres = $cv_seed->get_result();
        if ($srow = $sres->fetch_assoc()) {
            $cv_running_balance = intval($srow['closing_balance']);
        }
        $cv_seed->close();
    }
}

for ($m = 1; $m <= 12; $m++) {
    $has_cv = isset($raw_cv_records[$m]);
    $rec_id = $has_cv ? $raw_cv_records[$m]['id'] : 0;

    if ($has_cv && $raw_cv_records[$m]['opening_balance'] > 0) {
        $op = intval($raw_cv_records[$m]['opening_balance']);
    } else {
        $op = $cv_running_balance;
    }

    if ($m === 1) {
        $cv_first_opening = $op;
    }

    $rec = $has_cv ? intval($raw_cv_records[$m]['received_qty']) : 0;
    $trans = $has_cv ? intval($raw_cv_records[$m]['transferred_qty']) : 0;
    $sp = $has_cv ? intval($raw_cv_records[$m]['spoilt_qty']) : 0;
    
    // Live damaged count (permanent pairing retained)
    $dam = $cv_realtime_damaged[$m];
    if ($dam === 0 && $has_cv && intval($raw_cv_records[$m]['damaged_qty']) > 0) {
        $dam = intval($raw_cv_records[$m]['damaged_qty']);
    }

    // Real-time used vouchers pulled from animal_identifications system entries
    $used = $cv_realtime_used[$m];
    if ($used === 0 && $has_cv) {
        $used = intval($raw_cv_records[$m]['used_qty']);
    }

    $closing = ($op + $rec) - ($used + $dam + $sp + $trans);
    $cv_running_balance = $closing;

    $cv_grand_received += $rec;
    $cv_grand_used += $used;
    $cv_grand_damaged += $dam;
    $cv_grand_spoiled += $sp;
    $cv_grand_transferred += $trans;

    $cv_monthly_summary[$m] = [
        'month'               => $m,
        'month_name'          => $month_names[$m],
        'id'                  => $rec_id,
        'opening_balance'     => $op,
        'received_qty'        => $rec,
        'used_qty'            => $used,
        'damaged_qty'         => $dam,
        'spoilt_qty'          => $sp,
        'transferred_qty'     => $trans,
        'ending_balance'      => $closing,
        'distributed_ranges'  => $month_distributions[$m] ?? [],
        'has_entry'           => ($has_cv || $used > 0 || $dam > 0 || $rec > 0)
    ];
}

$cv_latest_balance = $cv_running_balance;

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">

<style>
.math-numeric {
    font-feature-settings: "tnum";
    font-variant-numeric: tabular-nums;
}
.btn-maroon {
    background-color: #820100;
    color: #ffffff;
    border-color: #820100;
}
.btn-maroon:hover {
    background-color: #500707;
    color: #ffffff;
}
</style>

<!-- MAIN CONTENT CONTAINER -->
<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h2 class="h4 fw-bold mb-1" style="color: #370709;">Cattle Voucher - Monthly Summary &amp; Distribution</h2>
        <p class="text-muted small mb-0">
            Sequential voucher allocation tracking, automated monthly reconciliation, and statutory Schedule - 06 returns for 
            <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District)
        </p>
    </div>
    
    <div class="d-flex align-items-center gap-2">
        <form method="GET" class="d-flex align-items-center gap-2">
            <label class="small fw-bold text-muted mb-0">Year:</label>
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 105px;">
                <?php
                $curr_year = intval(date('Y'));
                for ($y = $curr_year - 4; $y <= $curr_year + 4; $y++) {
                    $sel = ($y === $selected_year) ? 'selected' : '';
                    echo "<option value=\"$y\" $sel>$y</option>";
                }
                ?>
            </select>
        </form>
        <a href="ear_tag_balance.php?tab=identification" class="btn btn-outline-primary shadow-sm text-nowrap">
            <i class="bi bi-tag-fill me-1"></i> Ear Tag Balance &amp; Animals
        </a>
        <a href="monthly-annual-reports.php" class="btn btn-secondary shadow-sm text-nowrap">
            <i class="bi bi-arrow-left me-2"></i>Back
        </a>
    </div>
</div>

<!-- TOP STATS CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 border-start border-primary border-4">
            <div class="card-body py-3">
                <span class="text-muted small text-uppercase fw-bold">Active Year</span>
                <h4 class="mb-0 fw-bold math-numeric text-primary mt-1"><?= $selected_year ?></h4>
                <small class="text-muted"><?= count($cv_distributions) ?> Sequential Blocks Assigned</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 border-start border-success border-4">
            <div class="card-body py-3">
                <span class="text-muted small text-uppercase fw-bold">Total Received</span>
                <h4 class="mb-0 fw-bold math-numeric text-success mt-1"><?= number_format($cv_grand_received) ?></h4>
                <small class="text-muted">Office stock logged in <?= $selected_year ?></small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 border-start border-info border-4">
            <div class="card-body py-3">
                <span class="text-muted small text-uppercase fw-bold">Total Issued / Used</span>
                <h4 class="mb-0 fw-bold math-numeric text-info mt-1"><?= number_format($cv_grand_used) ?></h4>
                <small class="text-muted">Live animal identifications tagged</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 border-start border-danger border-4">
            <div class="card-body py-3">
                <span class="text-muted small text-uppercase fw-bold">Closing Balance</span>
                <h4 class="mb-0 fw-bold math-numeric <?= $cv_latest_balance < 0 ? 'text-danger' : 'text-dark' ?> mt-1">
                    <?= number_format($cv_latest_balance) ?>
                </h4>
                <small class="text-muted">Current office voucher stock</small>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MAIN INTERFACE: CATTLE VOUCHER SUMMARY & DISTRIBUTION        -->
<!-- ============================================================ -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-0">
        
        <!-- SECTION 1: CATTLE VOUCHER SEQUENTIAL DISTRIBUTION TRACKER -->
        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-distribute-vertical me-2 text-primary"></i>1. Sequential Cattle Voucher Distribution &amp; Balance Tracker
                </h6>
                <small class="text-muted">Assign sequential voucher serial blocks (e.g. 59 to 100) to specific farmers &amp; track remaining balances in real-time</small>
            </div>
            <div>
                <button class="btn btn-sm btn-primary fw-semibold shadow-sm" id="btnOpenAssignDistModal" data-bs-toggle="modal" data-bs-target="#modalVoucherDistribution">
                    <i class="bi bi-plus-circle me-1"></i>Assign Sequential Block to Farmer
                </button>
            </div>
        </div>

        <!-- DISTRIBUTION KPI STATS -->
        <div class="p-3 bg-white border-bottom">
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="card shadow-sm p-3 h-100 border-0 border-start border-primary border-4" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                        <span class="text-uppercase fw-bold small text-muted">Total Block Allocations</span>
                        <h4 class="fw-bold my-1 text-primary font-monospace"><?= count($cv_distributions) ?> <small class="fs-6 text-muted fw-normal">Blocks</small></h4>
                        <small class="text-muted"><?= number_format($cv_grand_allocated) ?> total serial leaves</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card shadow-sm p-3 h-100 border-0 border-start border-info border-4" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);">
                        <span class="text-uppercase fw-bold small text-info">Consumed / Registered</span>
                        <h4 class="fw-bold my-1 text-info font-monospace"><?= number_format($cv_grand_dist_used) ?></h4>
                        <small class="text-muted">Live registrations consuming blocks</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card shadow-sm p-3 h-100 border-0 border-start border-danger border-4" style="background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%);">
                        <span class="text-uppercase fw-bold small text-danger">Damaged Tags (Retained)</span>
                        <h4 class="fw-bold my-1 text-danger font-monospace"><?= number_format($cv_grand_dist_damaged) ?></h4>
                        <small class="text-muted">Permanently paired with serial</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card shadow-sm p-3 h-100 border-0 border-start border-success border-4" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);">
                        <span class="text-uppercase fw-bold small text-success">Farmer Block Balance</span>
                        <h4 class="fw-bold my-1 text-success font-monospace"><?= number_format($cv_grand_dist_rem) ?></h4>
                        <small class="text-muted">Vouchers available across assigned blocks</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- DISTRIBUTION BLOCKS TABLE -->
        <div class="p-3 border-bottom">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="cattleVoucherDistTable" style="width: 100%;">
                    <thead class="table-light text-secondary small uppercase">
                        <tr>
                            <th style="width: 10%;">Date</th>
                            <th style="width: 20%;">Farmer &amp; Registration No.</th>
                            <th style="width: 10%;">Farmer NIC</th>
                            <th style="width: 16%;">Serial Number Block</th>
                            <th style="width: 8%;" class="text-end">Allocated</th>
                            <th style="width: 8%;" class="text-end">Used</th>
                            <th style="width: 8%;" class="text-end">Damaged</th>
                            <th style="width: 10%;" class="text-end">Remaining</th>
                            <th style="width: 10%;" class="text-center">Next Serial</th>
                            <th style="width: 10%;" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="small" id="cattleVoucherDistTableBody">
                        <?php if (empty($cv_distributions)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="bi bi-info-circle me-1"></i>No sequential voucher blocks assigned yet. Click <strong>"Assign Sequential Block to Farmer"</strong> above to allocate blocks.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($cv_distributions as $dist): ?>
                                <tr>
                                    <td><?= htmlspecialchars($dist['distribution_date']) ?></td>
                                    <td>
                                        <strong class="text-dark d-block"><?= htmlspecialchars($dist['farmer_name']) ?></strong>
                                        <span class="badge bg-light text-secondary border font-monospace"><?= htmlspecialchars($dist['farm_reg_no']) ?></span>
                                    </td>
                                    <td class="font-monospace text-muted"><?= htmlspecialchars($dist['farmer_nic'] ?: '—') ?></td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace fs-6 px-2 py-1">
                                            <?= htmlspecialchars($dist['formatted_range']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end font-monospace fw-bold"><?= number_format($dist['total_allocated']) ?></td>
                                    <td class="text-end font-monospace text-info fw-bold"><?= number_format($dist['used_count']) ?></td>
                                    <td class="text-end font-monospace text-danger fw-bold"><?= number_format($dist['damaged_count']) ?></td>
                                    <td class="text-end font-monospace">
                                        <span class="fw-bold <?= $dist['remaining_balance'] > 0 ? 'text-success' : 'text-secondary' ?>">
                                            <?= number_format($dist['remaining_balance']) ?>
                                        </span>
                                        <div class="progress mt-1" style="height: 4px;">
                                            <div class="progress-bar <?= $dist['remaining_balance'] > 0 ? 'bg-success' : 'bg-secondary' ?>" 
                                                 role="progressbar" 
                                                 style="width: <?= min(100, $dist['pct_used']) ?>%;"></div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($dist['remaining_balance'] > 0): ?>
                                            <span class="badge bg-dark font-monospace"><?= htmlspecialchars($dist['next_available']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary font-monospace">Depleted</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary btn-view-block-ledger py-0 px-2" 
                                                    data-id="<?= $dist['id'] ?>" 
                                                    title="View Leaf Ledger">
                                                <i class="bi bi-card-list me-1"></i>Ledger
                                            </button>
                                            <?php if ($dist['used_count'] == 0): ?>
                                                <button type="button" class="btn btn-outline-danger btn-delete-dist py-0 px-2" 
                                                        data-id="<?= $dist['id'] ?>" 
                                                        data-range="<?= htmlspecialchars($dist['formatted_range']) ?>" 
                                                        title="Delete Unconsumed Block">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SECTION 2: CATTLE VOUCHER MONTHLY RETURNS AUTOMATION (SCHEDULE - 06) -->
        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2">
            <div>
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-file-earmark-ruled me-2 text-primary"></i>2. Cattle Voucher Monthly Returns &amp; Statutory Reconciliation - <?= $selected_year ?>
                </h6>
                <small class="text-muted">
                    Manual data entry strictly restricted to <strong>Received Quantities</strong>; distributed blocks, used vouchers, and ending balances are auto-computed live.
                </small>
            </div>
            <div>
                <button class="btn btn-sm btn-success fw-semibold shadow-sm" id="btnLogVoucherStock" data-bs-toggle="modal" data-bs-target="#modalCattleVoucherAdjustment">
                    <i class="bi bi-box-arrow-in-down me-1"></i>Log Office Received Stock
                </button>
            </div>
        </div>

        <!-- VOUCHER 12-MONTH SUMMARY TABLE (SCHEDULE - 06 MAPPED) -->
        <div class="p-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="cattleVoucherSummaryTable" style="width: 100%;">
                    <thead class="table-light text-secondary small uppercase">
                        <tr>
                            <th style="width: 9%;" class="text-center">Month</th>
                            <th style="width: 9%;" class="text-end">Opening Stock</th>
                            <th style="width: 10%;" class="text-end text-success fw-bold">Received (+) <i class="bi bi-pencil-fill text-success" title="Manual Entry Allowed"></i></th>
                            <th style="width: 17%;">Distributed Serial Block(s)</th>
                            <th style="width: 10%;" class="text-end text-info fw-bold">Used (-) [Registrations]</th>
                            <th style="width: 9%;" class="text-end text-danger fw-bold">Damaged Tags (-)</th>
                            <th style="width: 8%;" class="text-end">Spoiled (-)</th>
                            <th style="width: 8%;" class="text-end">Transferred (-)</th>
                            <th style="width: 9%;" class="text-end fw-bold">Ending Stock</th>
                            <th style="width: 11%;" class="text-center">Statutory Return</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php foreach ($cv_monthly_summary as $m_num => $m_data): ?>
                            <tr data-month="<?= $m_num ?>" 
                                data-year="<?= $selected_year ?>"
                                data-id="<?= $m_data['id'] ?>"
                                data-opening="<?= $m_data['opening_balance'] ?>"
                                data-received="<?= $m_data['received_qty'] ?>"
                                data-used="<?= $m_data['used_qty'] ?>"
                                data-spoilt="<?= $m_data['spoilt_qty'] ?>"
                                data-damaged="<?= $m_data['damaged_qty'] ?>"
                                data-transferred="<?= $m_data['transferred_qty'] ?>"
                                class="<?= $m_data['has_entry'] ? '' : 'text-muted' ?>">
                                
                                <td class="text-center fw-bold text-dark">
                                    <?= $m_data['month_name'] ?>
                                </td>
                                <td class="text-end font-monospace">
                                    <?= number_format($m_data['opening_balance']) ?>
                                </td>
                                <td class="text-end font-monospace text-success fw-bold">
                                    <?= $m_data['received_qty'] > 0 ? '+' . number_format($m_data['received_qty']) : '0' ?>
                                </td>
                                <td>
                                    <?php if (!empty($m_data['distributed_ranges'])): ?>
                                        <?php foreach ($m_data['distributed_ranges'] as $drange): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-inline-block font-monospace mb-1" style="font-size: 0.72rem;">
                                                <i class="bi bi-link-45deg me-1"></i><?= htmlspecialchars($drange) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic" style="font-size: 0.72rem;">None</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end font-monospace text-info fw-bold">
                                    <?= $m_data['used_qty'] > 0 ? '-' . number_format($m_data['used_qty']) : '0' ?>
                                </td>
                                <td class="text-end font-monospace text-danger fw-bold">
                                    <?= $m_data['damaged_qty'] > 0 ? '-' . number_format($m_data['damaged_qty']) : '0' ?>
                                </td>
                                <td class="text-end font-monospace text-danger">
                                    <?= $m_data['spoilt_qty'] > 0 ? '-' . number_format($m_data['spoilt_qty']) : '0' ?>
                                </td>
                                <td class="text-end font-monospace text-secondary">
                                    <?= $m_data['transferred_qty'] > 0 ? '-' . number_format($m_data['transferred_qty']) : '0' ?>
                                </td>
                                <td class="text-end font-monospace fw-bold <?= $m_data['ending_balance'] < 0 ? 'text-danger' : 'text-dark' ?>">
                                    <?= number_format($m_data['ending_balance']) ?>
                                </td>
                                <td class="text-center text-nowrap">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-success btn-edit-cv-stock py-0 px-2" 
                                                data-month="<?= $m_num ?>" 
                                                data-monthname="<?= $m_data['month_name'] ?>"
                                                data-id="<?= $m_data['id'] ?>"
                                                data-opening="<?= $m_data['opening_balance'] ?>"
                                                data-received="<?= $m_data['received_qty'] ?>"
                                                data-used="<?= $m_data['used_qty'] ?>"
                                                data-spoilt="<?= $m_data['spoilt_qty'] ?>"
                                                data-damaged="<?= $m_data['damaged_qty'] ?>"
                                                data-transferred="<?= $m_data['transferred_qty'] ?>"
                                                title="Log / Update Office Stock Received for <?= $m_data['month_name'] ?>">
                                            <i class="bi bi-pencil-square me-1"></i>Stock
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-view-schedule06 py-0 px-2" 
                                                data-month="<?= $m_num ?>" 
                                                data-year="<?= $selected_year ?>"
                                                data-monthname="<?= $m_data['month_name'] ?>"
                                                title="View Statutory Schedule - 06 Cattle Voucher Return">
                                            <i class="bi bi-file-earmark-ruled me-1"></i>Schedule-06
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light border-top border-2">
                        <tr class="fw-bold table-period-total">
                            <td class="text-center text-uppercase">Grand Total / Balance</td>
                            <td class="text-end font-monospace"><?= number_format($cv_first_opening) ?></td>
                            <td class="text-end font-monospace text-success">+<?= number_format($cv_grand_received) ?></td>
                            <td class="text-center small text-muted">—</td>
                            <td class="text-end font-monospace text-info">-<?= number_format($cv_grand_used) ?></td>
                            <td class="text-end font-monospace text-danger">-<?= number_format($cv_grand_damaged) ?></td>
                            <td class="text-end font-monospace text-danger">-<?= number_format($cv_grand_spoiled) ?></td>
                            <td class="text-end font-monospace text-secondary">-<?= number_format($cv_grand_transferred) ?></td>
                            <td class="text-end font-monospace <?= $cv_latest_balance < 0 ? 'text-danger' : 'text-dark' ?>">
                                <?= number_format($cv_latest_balance) ?>
                            </td>
                            <td class="text-center small text-muted">—</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- ============================================================ -->
<!-- MODALS INCLUSION                                             -->
<!-- ============================================================ -->
<?php include __DIR__ . '/model/modal_cattle_voucher_adjustment.php'; ?>
<?php include __DIR__ . '/model/modal_cattle_voucher_distribution.php'; ?>
<?php include __DIR__ . '/model/modal_cattle_voucher_schedule06.php'; ?>

<?php
ob_start();
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {

    // 1. Cattle Voucher Distribution DataTable
    if ($.fn.DataTable.isDataTable("#cattleVoucherDistTable")) {
        $("#cattleVoucherDistTable").DataTable().destroy();
    }
    $("#cattleVoucherDistTable").DataTable({
        "ordering": false,
        "pageLength": 10,
        "dom": '<"d-flex justify-content-between align-items-center mb-2"fB>rt<"d-flex justify-content-between align-items-center mt-2"ip>',
        "buttons": [
            {
                extend: "csv",
                text: '<i class="bi bi-file-earmark-spreadsheet"></i> CSV',
                className: "btn btn-sm btn-outline-success me-1"
            },
            {
                extend: "print",
                text: '<i class="bi bi-printer"></i> Print',
                className: "btn btn-sm btn-outline-dark"
            }
        ]
    });

    // 2. Cattle Voucher Monthly Summary DataTable
    if ($.fn.DataTable.isDataTable("#cattleVoucherSummaryTable")) {
        $("#cattleVoucherSummaryTable").DataTable().destroy();
    }
    var cvTable = $("#cattleVoucherSummaryTable").DataTable({
        "ordering": false,
        "paging": false,
        "info": false,
        "searching": false,
        "dom": '<"d-flex justify-content-end mb-2"B>t',
        "buttons": [
            {
                extend: "csv",
                text: '<i class="bi bi-file-earmark-spreadsheet"></i> CSV Vouchers',
                className: "btn btn-sm btn-success me-2"
            },
            {
                extend: "pdf",
                text: '<i class="bi bi-file-pdf"></i> PDF Vouchers',
                className: "btn btn-sm btn-danger me-2"
            },
            {
                extend: "print",
                text: '<i class="bi bi-printer"></i> Print Vouchers',
                className: "btn btn-sm btn-dark"
            }
        ]
    });

    // Alert query checks
    var urlParams = new URLSearchParams(window.location.search);
    var status = urlParams.get("status");
    var msg = urlParams.get("msg") || "";

    if (status === "success") {
        Swal.fire({
            icon: "success",
            title: "Success!",
            text: msg ? msg : "Operation completed successfully.",
            confirmButtonColor: "#370709"
        });
        urlParams.delete("status");
        urlParams.delete("msg");
        var cleanUrl = window.location.pathname + (urlParams.toString() ? "?" + urlParams.toString() : "");
        window.history.replaceState({}, document.title, cleanUrl);
    } else if (status === "error") {
        Swal.fire({
            icon: "error",
            title: "Operation Failed",
            text: msg ? msg : "Could not process database action.",
            confirmButtonColor: "#370709"
        });
        urlParams.delete("status");
        urlParams.delete("msg");
        var cleanUrl = window.location.pathname + (urlParams.toString() ? "?" + urlParams.toString() : "");
        window.history.replaceState({}, document.title, cleanUrl);
    }

    // Modal Live Calculation for Cattle Voucher Received Adjustment
    function recalculateCvEndingPreview() {
        var op = parseInt($("#cva_opening_balance").val(), 10) || 0;
        var rec = parseInt($("#cva_received_qty").val(), 10) || 0;
        var used = parseInt($("#cva_used_qty").val(), 10) || 0;
        var dam = parseInt($("#cva_damaged_qty").val(), 10) || 0;
        var sp = parseInt($("#cva_spoilt_qty").val(), 10) || 0;
        var tr = parseInt($("#cva_transferred_qty").val(), 10) || 0;

        var ending = (op + rec) - (used + dam + sp + tr);
        $("#cva_ending_preview").text(ending.toLocaleString() + " Vouchers");

        if (ending < 0) {
            $("#cva_ending_preview").removeClass("text-success").addClass("text-danger");
            $("#btnSaveCvaStock").prop("disabled", true);
        } else {
            $("#cva_ending_preview").removeClass("text-danger").addClass("text-success");
            $("#btnSaveCvaStock").prop("disabled", false);
        }
    }

    $(".cva-calc-input").on("input change", function() {
        recalculateCvEndingPreview();
    });

    // Click: Log Office Received Stock
    $("#btnLogVoucherStock").on("click", function() {
        var currMonth = new Date().getMonth() + 1;
        var monthRow = $(`#cattleVoucherSummaryTable tr[data-month="${currMonth}"]`);
        if (!monthRow.length) {
            monthRow = $("#cattleVoucherSummaryTable tbody tr:first");
        }

        $("#cva_report_month").val(monthRow.data("month") || currMonth);
        $("#cva_report_month_display").val(monthRow.find("td:first").text().trim());
        $("#cva_opening_balance").val(monthRow.data("opening") || 0);
        $("#cva_received_qty").val(monthRow.data("received") || 0);
        $("#cva_used_qty").val(monthRow.data("used") || 0);
        $("#cva_damaged_qty").val(monthRow.data("damaged") || 0);
        $("#cva_spoilt_qty").val(monthRow.data("spoilt") || 0);
        $("#cva_transferred_qty").val(monthRow.data("transferred") || 0);
        $("#cva_edit_id").val(monthRow.data("id") || 0);

        recalculateCvEndingPreview();
    });

    // Click: Edit CV Stock row button
    $(document).on("click", ".btn-edit-cv-stock", function() {
        var btn = $(this);
        var row = btn.closest("tr");

        $("#cva_report_month").val(btn.data("month"));
        $("#cva_report_month_display").val(btn.data("monthname"));
        $("#cva_opening_balance").val(btn.data("opening") || 0);
        $("#cva_received_qty").val(btn.data("received") || 0);
        $("#cva_used_qty").val(btn.data("used") || 0);
        $("#cva_damaged_qty").val(btn.data("damaged") || 0);
        $("#cva_spoilt_qty").val(btn.data("spoilt") || 0);
        $("#cva_transferred_qty").val(btn.data("transferred") || 0);
        $("#cva_edit_id").val(btn.data("id") || 0);

        recalculateCvEndingPreview();
        var modalEl = document.getElementById("modalCattleVoucherAdjustment");
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });

    // Submit: Cattle Voucher Adjustment Form
    $("#formCattleVoucherAdjustment").on("submit", function(e) {
        e.preventDefault();
        var form = $(this);
        var submitBtn = $("#btnSaveCvaStock");
        submitBtn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        $.ajax({
            url: "processors/cattle_voucher_crud.php",
            type: "POST",
            data: form.serialize() + "&ajax=1",
            dataType: "json",
            success: function(resp) {
                if (resp.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Stock Movement Saved",
                        text: resp.message || "Monthly cattle voucher baseline updated.",
                        confirmButtonColor: "#370709"
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    Swal.fire("Error", resp.message || "Failed to save stock movement.", "error");
                    submitBtn.prop("disabled", false).html('<i class="bi bi-check-circle-fill me-1"></i>Save Stock Record');
                }
            },
            error: function() {
                Swal.fire("Error", "Could not reach server.", "error");
                submitBtn.prop("disabled", false).html('<i class="bi bi-check-circle-fill me-1"></i>Save Stock Record');
            }
        });
    });

    // Distribution: Sequential Allocation Calculations
    function recalcDistTotal() {
        var s = parseInt($("#vd_start_serial").val(), 10) || 0;
        var e = parseInt($("#vd_end_serial").val(), 10) || 0;
        var p = $("#vd_prefix").val().trim() || "A-";

        if (e >= s && s > 0) {
            var tot = (e - s) + 1;
            $("#vd_total_preview").text(tot.toLocaleString() + " Vouchers");
            var pStart = s >= 100000 ? s : String(s).padStart(6, "0");
            var pEnd = e >= 100000 ? e : String(e).padStart(6, "0");
            $("#vd_range_preview").text(p + pStart + " to " + p + pEnd);
            $("#btnSubmitVoucherDist").prop("disabled", false);
        } else {
            $("#vd_total_preview").text("0 Vouchers");
            $("#vd_range_preview").text("Invalid Serial Range");
            $("#btnSubmitVoucherDist").prop("disabled", true);
        }
    }

    $("#vd_start_serial, #vd_end_serial, #vd_prefix").on("input change", function() {
        recalcDistTotal();
    });

    // Auto-fill farmer details on picking farm reg no in distribution modal
    $("#vd_farm_reg_no").on("change blur", function() {
        var v = $(this).val().trim();
        if (!v) return;
        $.ajax({
            url: "processors/animal_identification_crud.php?action=get_farmer_by_reg",
            type: "GET",
            data: { farm_reg_no: v },
            dataType: "json",
            success: function(resp) {
                if (resp.success && resp.farmer) {
                    $("#vd_farmer_name").val(resp.farmer.farmer_name);
                }
            }
        });
    });

    // Submit: Assign Sequential Block Form
    $("#formVoucherDistribution").on("submit", function(e) {
        e.preventDefault();
        var form = $(this);
        var submitBtn = $("#btnSubmitVoucherDist");
        submitBtn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1"></span>Allocating...');

        $.ajax({
            url: "processors/cattle_voucher_crud.php",
            type: "POST",
            data: form.serialize() + "&ajax=1",
            dataType: "json",
            success: function(resp) {
                if (resp.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Block Assigned",
                        text: resp.message || "Voucher serial block successfully allocated.",
                        confirmButtonColor: "#370709"
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    Swal.fire("Allocation Failed", resp.message || "Could not allocate block.", "error");
                    submitBtn.prop("disabled", false).html('<i class="bi bi-check-circle-fill me-1"></i>Allocate Serial Block');
                }
            },
            error: function() {
                Swal.fire("Error", "Could not reach server.", "error");
                submitBtn.prop("disabled", false).html('<i class="bi bi-check-circle-fill me-1"></i>Allocate Serial Block');
            }
        });
    });

    // Delete Distribution Block
    $(document).on("click", ".btn-delete-dist", function() {
        var distId = $(this).data("id");
        var blockRange = $(this).data("range");

        Swal.fire({
            title: "Delete Sequential Block?",
            text: `Are you sure you want to delete block ${blockRange}? This block has not been consumed yet.`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#820100",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Yes, Delete"
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: "processors/cattle_voucher_crud.php",
                    type: "POST",
                    data: { action: "delete_distribution", id: distId, ajax: 1 },
                    dataType: "json",
                    success: function(res) {
                        if (res.success) {
                            Swal.fire("Deleted", res.message, "success").then(function() {
                                location.reload();
                            });
                        } else {
                            Swal.fire("Error", res.message, "error");
                        }
                    }
                });
            }
        });
    });

    // View Leaf Ledger Modal
    $(document).on("click", ".btn-view-block-ledger", function() {
        var distId = $(this).data("id");
        Swal.fire({
            title: "Loading Leaf Ledger...",
            allowOutsideClick: false,
            didOpen: function() {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: "processors/cattle_voucher_crud.php?action=get_distribution_ledger&id=" + distId,
            type: "GET",
            dataType: "json",
            success: function(resp) {
                Swal.close();
                if (resp.success && resp.distribution) {
                    var d = resp.distribution;
                    var rowsHtml = "";
                    resp.leaves.forEach(function(lf) {
                        var statusBadge = lf.status === "Used" 
                            ? '<span class="badge bg-info">Used / Tagged</span>'
                            : (lf.status === "Damaged" 
                                ? '<span class="badge bg-danger">Damaged (Retained)</span>' 
                                : '<span class="badge bg-success">Available</span>');

                        rowsHtml += `<tr>
                            <td class="font-monospace fw-bold text-dark">${lf.leaf_serial}</td>
                            <td>${statusBadge}</td>
                            <td class="font-monospace">${lf.ear_tag_no || "—"}</td>
                            <td>${lf.identification_date || "—"}</td>
                            <td>${lf.species || "—"}</td>
                        </tr>`;
                    });

                    var ledgerContent = `
                        <div class="text-start mb-3 small">
                            <strong>Farmer:</strong> ${d.farmer_name} (${d.farm_reg_no})<br>
                            <strong>Allocated Block:</strong> <span class="badge bg-primary font-monospace">${d.formatted_range}</span>
                            <span class="ms-2">(${d.total_allocated} total | <span class="text-success fw-bold">${d.remaining_balance} remaining</span>)</span>
                        </div>
                        <div class="table-responsive" style="max-height: 380px;">
                            <table class="table table-sm table-bordered table-striped small align-middle mb-0">
                                <thead class="table-dark sticky-top">
                                    <tr>
                                        <th>Voucher Serial</th>
                                        <th>Status</th>
                                        <th>Ear Tag No</th>
                                        <th>Issue Date</th>
                                        <th>Species</th>
                                    </tr>
                                </thead>
                                <tbody>${rowsHtml}</tbody>
                            </table>
                        </div>
                    `;

                    Swal.fire({
                        title: "Cattle Voucher Leaf Ledger",
                        html: ledgerContent,
                        width: "750px",
                        confirmButtonColor: "#370709",
                        confirmButtonText: "Close"
                    });
                } else {
                    Swal.fire("Error", resp.message || "Could not retrieve ledger.", "error");
                }
            },
            error: function() {
                Swal.fire("Error", "Could not reach server.", "error");
            }
        });
    });

    // Schedule - 06 Modal Generation
    var currentSched06Data = [];
    var currentSched06MonthName = "";
    var currentSched06Year = <?= $selected_year ?>;

    $(document).on("click", ".btn-view-schedule06", function() {
        var mNum = $(this).data("month");
        var mYear = $(this).data("year");
        var mName = $(this).data("monthname");
        currentSched06MonthName = mName;
        currentSched06Year = mYear;

        $("#sched06MonthName").text(mName);
        $("#sched06Year").text(mYear);
        $("#sched06TableBody").html('<tr><td colspan="15" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2 text-primary"></span>Generating Statutory Schedule - 06 Return...</td></tr>');

        var modalEl = document.getElementById("modalSchedule06");
        bootstrap.Modal.getOrCreateInstance(modalEl).show();

        $.ajax({
            url: "processors/cattle_voucher_crud.php?action=get_schedule_06",
            type: "GET",
            data: { range_id: <?= $range_id ?>, year: mYear, month: mNum },
            dataType: "json",
            success: function(resp) {
                if (resp.success && resp.records) {
                    currentSched06Data = resp.records;
                    $("#sched06TotalCount").text(resp.total_records || resp.records.length);

                    if (resp.records.length === 0) {
                        $("#sched06TableBody").html('<tr><td colspan="15" class="text-center py-4 text-muted"><i class="bi bi-inbox fs-4 d-block mb-1"></i>No cattle voucher transactions or animal identifications recorded for ' + mName + ' ' + mYear + '.</td></tr>');
                        return;
                    }

                    var html = "";
                    resp.records.forEach(function(r) {
                        var statusCls = r.present_status === "Tag Damaged / Removed" ? "badge bg-danger" : "badge bg-secondary";
                        html += `<tr>
                            <td class="text-center fw-bold">${r.index}</td>
                            <td class="font-monospace fw-bold text-dark text-nowrap">${r.voucher_serial_no}</td>
                            <td class="text-nowrap">${r.identification_date}</td>
                            <td class="fw-semibold">${r.farmer_name}</td>
                            <td class="font-monospace text-muted">${r.farmer_nic || "—"}</td>
                            <td class="font-monospace"><span class="badge bg-light text-secondary border">${r.farm_reg_no}</span></td>
                            <td class="font-monospace fw-bold text-primary text-nowrap">${r.ear_tag_no}</td>
                            <td>${r.species}</td>
                            <td>${r.breed || "Standard"}</td>
                            <td>${r.gender}</td>
                            <td>${r.age || "—"}</td>
                            <td>${r.colour || "—"}</td>
                            <td>${r.purpose || "—"}</td>
                            <td class="font-monospace">${r.io_number || "—"}</td>
                            <td><span class="${statusCls}">${r.present_status}</span></td>
                        </tr>`;
                    });
                    $("#sched06TableBody").html(html);
                } else {
                    $("#sched06TableBody").html('<tr><td colspan="15" class="text-center text-danger py-4">Error loading statutory Schedule - 06 data.</td></tr>');
                }
            },
            error: function() {
                $("#sched06TableBody").html('<tr><td colspan="15" class="text-center text-danger py-4">Error connecting to server.</td></tr>');
            }
        });
    });

    // Print Schedule - 06 Return
    $(document).on("click", "#btnPrintSchedule06", function() {
        window.print();
    });

    // Export Schedule - 06 CSV
    $(document).on("click", "#btnExportSchedule06Csv", function() {
        if (!currentSched06Data || currentSched06Data.length === 0) {
            Swal.fire("No Data", "There are no records to export for this month.", "info");
            return;
        }

        var csv = [];
        csv.push(["DEPARTMENT OF ANIMAL PRODUCTION & HEALTH - SCHEDULE - 06 MONTHLY RETURN"]);
        csv.push(["Veterinary Range: <?= htmlspecialchars($range_name) ?>", "District: <?= htmlspecialchars($district_name) ?>", "Period: " + currentSched06MonthName + " " + currentSched06Year]);
        csv.push([]);
        csv.push(["#", "Voucher Serial No", "Issue Date", "Farmer / Owner Name", "Owner NIC", "Farm Reg No", "Ear Tag No", "Species", "Breed", "Sex", "Age", "Colour", "Purpose", "IO No", "Present Status"]);

        currentSched06Data.forEach(function(r) {
            csv.push([
                r.index,
                r.voucher_serial_no,
                r.identification_date,
                `"${r.farmer_name}"`,
                `"${r.farmer_nic}"`,
                `"${r.farm_reg_no}"`,
                `"${r.ear_tag_no}"`,
                r.species,
                `"${r.breed}"`,
                r.gender,
                `"${r.age}"`,
                `"${r.colour}"`,
                `"${r.purpose}"`,
                `"${r.io_number}"`,
                r.present_status
            ]);
        });

        var csvContent = "data:text/csv;charset=utf-8," + csv.map(function(e) { return e.join(","); }).join("\n");
        var encodedUri = encodeURI(csvContent);
        var link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `Schedule_06_Cattle_Vouchers_${currentSched06MonthName}_${currentSched06Year}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

});
</script>
<?php
$pageScripts = ob_get_clean();

require_once '../../../includes/footer.php';
?>
