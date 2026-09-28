<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['veterinary_surgeon', 'sms'])) {
    header("Location: ../../../index.php");
    exit();
}

$user_id  = $_SESSION['user_id'] ?? null;
$range_id = $_SESSION['range_id'] ?? null;

$range_name = 'Your Range';
$district_name = 'Your District';
$district_id = null;

// Extract Range Name, District Name, and IDs using standard relational JOIN
if (!empty($range_id)) {
    $details_sql = "
        SELECT 
            vr.name AS range_name,
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
            $district_name = $data['district_name'] ?? 'Your District';
            $district_id = $data['district_id'] ?? null;
        }
        $details_query->close();
    }
}

// Active year and active tab
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$active_tab = isset($_GET['tab']) && in_array($_GET['tab'], ['programs', 'summary']) ? $_GET['tab'] : 'programs';

$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

// 1. Fetch Tagging Programs for the selected year and range
$tagging_programs = [];
$total_programs_tags_used = 0;
$total_programs_tags_spoiled = 0;

if (!empty($range_id)) {
    $prog_sql = "
        SELECT p.*, 
               (SELECT COUNT(*) FROM cattle_vouchers cv WHERE cv.tag_program_id = p.id) AS voucher_count 
        FROM ear_tag_programs p 
        WHERE p.range_id = ? AND YEAR(p.program_date) = ? 
        ORDER BY p.program_date DESC, p.id DESC
    ";
    $p_stmt = $mysqli->prepare($prog_sql);
    if ($p_stmt) {
        $p_stmt->bind_param("ii", $range_id, $selected_year);
        $p_stmt->execute();
        $p_res = $p_stmt->get_result();
        while ($row = $p_res->fetch_assoc()) {
            $tagging_programs[] = $row;
            $total_programs_tags_used += intval($row['tags_used']);
            $total_programs_tags_spoiled += intval($row['tags_spoiled']);
        }
        $p_stmt->close();
    }
}

// 2. Fetch Real-Time Aggregated Program Usage per Month
$realtime_month_usage = []; // [month => ['used' => int, 'spoiled' => int]]
for ($m = 1; $m <= 12; $m++) {
    $realtime_month_usage[$m] = ['used' => 0, 'spoiled' => 0];
}

if (!empty($range_id)) {
    $agg_sql = "
        SELECT MONTH(program_date) AS m, 
               COALESCE(SUM(tags_used), 0) AS total_used, 
               COALESCE(SUM(tags_spoiled), 0) AS total_spoiled 
        FROM ear_tag_programs 
        WHERE range_id = ? AND YEAR(program_date) = ? 
        GROUP BY MONTH(program_date)
    ";
    $a_stmt = $mysqli->prepare($agg_sql);
    if ($a_stmt) {
        $a_stmt->bind_param("ii", $range_id, $selected_year);
        $a_stmt->execute();
        $a_res = $a_stmt->get_result();
        while ($row = $a_res->fetch_assoc()) {
            $m = intval($row['m']);
            $realtime_month_usage[$m] = [
                'used' => intval($row['total_used']),
                'spoiled' => intval($row['total_spoiled'])
            ];
        }
        $a_stmt->close();
    }
}

// 3. Fetch Stock Baseline & Movements from ear_tag_usage
$raw_usage_records = [];
if (!empty($range_id)) {
    $u_sql = "
        SELECT id, report_year, report_month, opening_balance, received_qty, used_qty, spoilt_qty, transferred_qty, closing_balance 
        FROM ear_tag_usage 
        WHERE range_id = ? AND report_year = ? 
        ORDER BY report_month ASC
    ";
    $u_stmt = $mysqli->prepare($u_sql);
    if ($u_stmt) {
        $u_stmt->bind_param("ii", $range_id, $selected_year);
        $u_stmt->execute();
        $u_res = $u_stmt->get_result();
        while ($row = $u_res->fetch_assoc()) {
            $raw_usage_records[intval($row['report_month'])] = $row;
        }
        $u_stmt->close();
    }
}

// 4. Build Automated Dynamic 12-Month Real-Time Summary
$monthly_summary = [];
$running_balance = 0;
$grand_total_received = 0;
$grand_total_used = 0;
$grand_total_spoiled = 0;
$grand_total_transferred = 0;
$first_opening_balance = 0;

// Check if previous year month 12 has a closing balance to seed January opening
if (!empty($range_id)) {
    $prev_y = $selected_year - 1;
    $seed_sql = "SELECT closing_balance FROM ear_tag_usage WHERE range_id = ? AND report_year = ? AND report_month = 12 LIMIT 1";
    $s_stmt = $mysqli->prepare($seed_sql);
    if ($s_stmt) {
        $s_stmt->bind_param("ii", $range_id, $prev_y);
        $s_stmt->execute();
        $s_res = $s_stmt->get_result();
        if ($sr = $s_res->fetch_assoc()) {
            $running_balance = intval($sr['closing_balance']);
        }
        $s_stmt->close();
    }
}

for ($m = 1; $m <= 12; $m++) {
    $has_record = isset($raw_usage_records[$m]);
    $rec_id = $has_record ? $raw_usage_records[$m]['id'] : 0;
    
    // Opening balance: either recorded or roll-over from previous month's ending
    if ($has_record && $raw_usage_records[$m]['opening_balance'] > 0) {
        $opening = intval($raw_usage_records[$m]['opening_balance']);
    } else {
        $opening = $running_balance;
    }

    if ($m === 1) {
        $first_opening_balance = $opening;
    }

    $received = $has_record ? intval($raw_usage_records[$m]['received_qty']) : 0;
    $transferred = $has_record ? intval($raw_usage_records[$m]['transferred_qty']) : 0;

    // Real-time dynamic calculation from ear_tag_programs even mid-month!
    $real_used = $realtime_month_usage[$m]['used'];
    $real_spoiled = $realtime_month_usage[$m]['spoiled'];

    // If no programs logged yet for this month but a manual usage was logged previously in ear_tag_usage, respect it as fallback
    if ($real_used === 0 && $real_spoiled === 0 && $has_record) {
        $real_used = intval($raw_usage_records[$m]['used_qty']);
        $real_spoiled = intval($raw_usage_records[$m]['spoilt_qty']);
    }

    $ending = ($opening + $received) - ($real_used + $real_spoiled + $transferred);
    $running_balance = $ending;

    $grand_total_received += $received;
    $grand_total_used += $real_used;
    $grand_total_spoiled += $real_spoiled;
    $grand_total_transferred += $transferred;

    $monthly_summary[$m] = [
        'month'           => $m,
        'month_name'      => $month_names[$m],
        'id'              => $rec_id,
        'opening_balance' => $opening,
        'received_qty'    => $received,
        'used_qty'        => $real_used,
        'spoilt_qty'      => $real_spoiled,
        'transferred_qty' => $transferred,
        'ending_balance'  => $ending,
        'has_entry'       => ($has_record || $real_used > 0 || $real_spoiled > 0 || $received > 0 || $transferred > 0)
    ];
}

$latest_inventory_balance = $running_balance;

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">

<style>
/* Refined styling matching letter_h_record */
.nav-pills-letter-h {
    flex-wrap: nowrap;
    gap: 8px;
}
.nav-pills-letter-h .nav-link {
    font-size: 0.88rem;
    font-weight: 600;
    color: #475569;
    padding: 0.65rem 1.25rem;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background-color: #f8fafc;
    transition: all 0.2s ease-in-out;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.nav-pills-letter-h .nav-link:hover {
    background-color: #f1f5f9;
    color: #820100;
    border-color: #cbd5e1;
}
.nav-pills-letter-h .nav-link.active {
    background-color: #820100 !important;
    color: #ffffff !important;
    border-color: #820100 !important;
    box-shadow: 0 3px 8px rgba(130, 1, 0, 0.25);
}
.nav-pills-letter-h .nav-link .badge-tab-count {
    font-size: 0.72rem;
    padding: 2px 7px;
    border-radius: 10px;
    background-color: rgba(0, 0, 0, 0.08);
    color: inherit;
}
.nav-pills-letter-h .nav-link.active .badge-tab-count {
    background-color: rgba(255, 255, 255, 0.25);
    color: #fff;
}
.table-period-total {
    background-color: #fef2f2 !important;
    color: #820100;
    font-weight: 700;
}
</style>

<!-- MAIN CONTENT CONTAINER -->
<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h2 class="h4 fw-bold mb-1" style="color: #370709;">Ear Tag Balance & Program Management</h2>
        <p class="text-muted small mb-0">
            Log tagging programs, interconnect cattle identity vouchers, and track dynamic monthly returns for 
            <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District)
        </p>
    </div>
    
    <div class="d-flex align-items-center gap-2">
        <form method="GET" class="d-flex align-items-center gap-2">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
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
        <a href="monthly-annual-reports.php" class="btn btn-secondary shadow-sm text-nowrap">
            <i class="bi bi-arrow-left me-2"></i>Back
        </a>
    </div>
</div>

<!-- TOP STATS CARDS (DYNAMIC REAL-TIME METRICS) -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 border-start border-primary border-4">
            <div class="card-body py-3">
                <span class="text-muted small text-uppercase fw-bold">Active Year</span>
                <h4 class="mb-0 fw-bold math-numeric text-primary mt-1"><?= $selected_year ?></h4>
                <small class="text-muted"><?= count($tagging_programs) ?> Programs Logged</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 border-start border-success border-4">
            <div class="card-body py-3">
                <span class="text-muted small text-uppercase fw-bold">Total Received</span>
                <h4 class="mb-0 fw-bold math-numeric text-success mt-1"><?= number_format($grand_total_received) ?></h4>
                <small class="text-muted">Stock received in <?= $selected_year ?></small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 border-start border-info border-4">
            <div class="card-body py-3">
                <span class="text-muted small text-uppercase fw-bold">Total Used (Tagged)</span>
                <h4 class="mb-0 fw-bold math-numeric text-info mt-1"><?= number_format($grand_total_used) ?></h4>
                <small class="text-muted">Real-time aggregate total</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 border-start border-danger border-4">
            <div class="card-body py-3">
                <span class="text-muted small text-uppercase fw-bold">Current Ending Balance</span>
                <h4 class="mb-0 fw-bold math-numeric <?= $latest_inventory_balance < 0 ? 'text-danger' : 'text-dark' ?> mt-1">
                    <?= number_format($latest_inventory_balance) ?>
                </h4>
                <small class="text-muted">Available stock in range</small>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- TWO-SECTION INTERFACE SPLIT (TABS) -->
<!-- ============================================================ -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white p-3 border-bottom">
        <ul class="nav nav-pills nav-pills-letter-h" id="earTagTabs" role="tablist">
            <!-- TAB 1: TAGGING PROGRAMS -->
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $active_tab === 'programs' ? 'active' : '' ?>" 
                        id="tab-programs-btn" 
                        data-bs-toggle="pill" 
                        data-bs-target="#tab-programs-content" 
                        type="button" 
                        role="tab" 
                        aria-controls="tab-programs-content" 
                        aria-selected="<?= $active_tab === 'programs' ? 'true' : 'false' ?>">
                    <i class="bi bi-tags-fill me-1"></i>
                    Tagging Programs
                    <span class="badge-tab-count"><?= count($tagging_programs) ?></span>
                </button>
            </li>

            <!-- TAB 2: MONTHLY SUMMARY -->
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $active_tab === 'summary' ? 'active' : '' ?>" 
                        id="tab-summary-btn" 
                        data-bs-toggle="pill" 
                        data-bs-target="#tab-summary-content" 
                        type="button" 
                        role="tab" 
                        aria-controls="tab-summary-content" 
                        aria-selected="<?= $active_tab === 'summary' ? 'true' : 'false' ?>">
                    <i class="bi bi-calendar3 me-1"></i>
                    Monthly Summary
                    <span class="badge-tab-count">12 Months</span>
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content" id="earTagTabsContent">
        <!-- ============================================================ -->
        <!-- TAB 1 CONTENT: TAGGING PROGRAMS (INDIVIDUAL PROGRAM LOG) -->
        <!-- ============================================================ -->
        <div class="tab-pane fade <?= $active_tab === 'programs' ? 'show active' : '' ?>" 
             id="tab-programs-content" 
             role="tabpanel" 
             aria-labelledby="tab-programs-btn">
            
            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-list-check me-2 text-primary"></i>Logged Tagging Programs - <?= $selected_year ?></h6>
                    <small class="text-muted">Individual tagging events with cattle identity vouchers and farmer records</small>
                </div>
                <div>
                    <button type="button" class="btn btn-sm text-white shadow-sm fw-bold px-3" style="background-color: #820100;" id="btnOpenAddProgramModal" data-bs-toggle="modal" data-bs-target="#modalTaggingProgram">
                        <i class="bi bi-plus-circle me-1"></i>Log Tagging Program
                    </button>
                </div>
            </div>

            <div class="p-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="taggingProgramsTable" style="width: 100%;">
                        <thead class="table-light text-secondary small uppercase">
                            <tr>
                                <th style="width: 10%;">Date</th>
                                <th style="width: 16%;">Farmer's Name</th>
                                <th style="width: 12%;">IC Number</th>
                                <th style="width: 13%;">Farm Reg. No.</th>
                                <th style="width: 9%;" class="text-end">Tags Used</th>
                                <th style="width: 9%;" class="text-end">Spoiled</th>
                                <th style="width: 15%;">Staff / Vaccinators</th>
                                <th style="width: 10%;" class="text-center">Cattle Vouchers</th>
                                <th style="width: 6%;" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php foreach ($tagging_programs as $prog): ?>
                                <tr data-id="<?= $prog['id'] ?>">
                                    <td class="fw-semibold text-dark text-nowrap">
                                        <?= date('d M Y', strtotime($prog['program_date'])) ?>
                                        </td>
                                        <td>
                                            <strong class="text-dark"><?= htmlspecialchars($prog['farmer_name']) ?></strong>
                                            <?php if (!empty($prog['address'])): ?>
                                                <div class="text-muted text-truncate" style="max-width: 180px; font-size: 0.72rem;">
                                                    <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($prog['address']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border font-monospace">
                                                <?= htmlspecialchars($prog['nic_no']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-secondary small font-monospace">
                                                <?= htmlspecialchars($prog['farm_reg_no'] ?: '—') ?>
                                            </span>
                                        </td>
                                        <td class="text-end font-monospace text-info fw-bold">
                                            <?= number_format($prog['tags_used']) ?>
                                        </td>
                                        <td class="text-end font-monospace text-warning fw-bold">
                                            <?= number_format($prog['tags_spoiled']) ?>
                                        </td>
                                        <td>
                                            <span class="small text-muted"><?= htmlspecialchars($prog['staff_involved'] ?: '—') ?></span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-view-vouchers py-0 px-2" data-id="<?= $prog['id'] ?>" title="View Linked Animal Identity Vouchers">
                                                <i class="bi bi-card-checklist me-1"></i>
                                                <span class="badge bg-primary text-white py-0 px-1"><?= $prog['voucher_count'] ?></span> Animals
                                            </button>
                                        </td>
                                        <td class="text-center text-nowrap">
                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-program py-0 px-1 me-1" data-id="<?= $prog['id'] ?>" title="Edit Program">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-program py-0 px-1" data-id="<?= $prog['id'] ?>" title="Delete Program">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- TAB 2 CONTENT: AUTOMATED REAL-TIME MONTHLY SUMMARY -->
        <!-- ============================================================ -->
        <div class="tab-pane fade <?= $active_tab === 'summary' ? 'show active' : '' ?>" 
             id="tab-summary-content" 
             role="tabpanel" 
             aria-labelledby="tab-summary-btn">
            
            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-calendar-check me-2 text-success"></i>Monthly Ear Tag Stock Returns & Real-Time Balances - <?= $selected_year ?></h6>
                    <small class="text-muted">Dynamically aggregates Used and Spoiled quantities in real-time from Tagging Programs</small>
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-dark fw-semibold" data-bs-toggle="modal" data-bs-target="#modalStockAdjustment">
                        <i class="bi bi-box-arrow-in-down me-1"></i>Log Stock Movement / Baseline
                    </button>
                </div>
            </div>

            <div class="p-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="monthlySummaryTable" style="width: 100%;">
                        <thead class="table-light text-secondary small uppercase">
                            <tr>
                                <th style="width: 14%;" class="text-center">Month</th>
                                <th style="width: 12%;" class="text-end">Opening Balance</th>
                                <th style="width: 12%;" class="text-end">Received Quantity</th>
                                <th style="width: 12%;" class="text-end">Used Quantity</th>
                                <th style="width: 12%;" class="text-end">Spoiled Quantity</th>
                                <th style="width: 12%;" class="text-end">Transferred Amounts</th>
                                <th style="width: 14%;" class="text-end">Ending Balance</th>
                                <th style="width: 12%;" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php foreach ($monthly_summary as $m_num => $m_data): ?>
                                <tr data-month="<?= $m_num ?>" 
                                    data-year="<?= $selected_year ?>"
                                    data-id="<?= $m_data['id'] ?>"
                                    data-opening="<?= $m_data['opening_balance'] ?>"
                                    data-received="<?= $m_data['received_qty'] ?>"
                                    data-transferred="<?= $m_data['transferred_qty'] ?>"
                                    class="<?= $m_data['has_entry'] ? '' : 'text-muted' ?>">
                                    
                                    <td class="text-center fw-bold text-dark">
                                        <?= $m_data['month_name'] ?>
                                    </td>
                                    <td class="text-end font-monospace">
                                        <?= number_format($m_data['opening_balance']) ?>
                                    </td>
                                    <td class="text-end font-monospace text-success">
                                        <?= $m_data['received_qty'] > 0 ? '+' . number_format($m_data['received_qty']) : '0' ?>
                                    </td>
                                    <td class="text-end font-monospace text-info fw-bold">
                                        <?= $m_data['used_qty'] > 0 ? '-' . number_format($m_data['used_qty']) : '0' ?>
                                    </td>
                                    <td class="text-end font-monospace text-warning fw-bold">
                                        <?= $m_data['spoilt_qty'] > 0 ? '-' . number_format($m_data['spoilt_qty']) : '0' ?>
                                    </td>
                                    <td class="text-end font-monospace text-secondary">
                                        <?= $m_data['transferred_qty'] > 0 ? '-' . number_format($m_data['transferred_qty']) : '0' ?>
                                    </td>
                                    <td class="text-end font-monospace fw-bold <?= $m_data['ending_balance'] < 0 ? 'text-danger' : 'text-dark' ?>">
                                        <?= number_format($m_data['ending_balance']) ?>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-stock py-0 px-2" 
                                                data-month="<?= $m_num ?>" 
                                                data-monthname="<?= $m_data['month_name'] ?>"
                                                data-id="<?= $m_data['id'] ?>"
                                                data-opening="<?= $m_data['opening_balance'] ?>"
                                                data-received="<?= $m_data['received_qty'] ?>"
                                                data-transferred="<?= $m_data['transferred_qty'] ?>"
                                                title="Log / Edit Stock Details for <?= $m_data['month_name'] ?>">
                                            <i class="bi bi-sliders me-1"></i>Stock
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light border-top border-2">
                            <tr class="fw-bold table-period-total">
                                <td class="text-center text-uppercase">Grand Total / Balance</td>
                                <td class="text-end font-monospace"><?= number_format($first_opening_balance) ?></td>
                                <td class="text-end font-monospace text-success">+<?= number_format($grand_total_received) ?></td>
                                <td class="text-end font-monospace text-info">-<?= number_format($grand_total_used) ?></td>
                                <td class="text-end font-monospace text-warning">-<?= number_format($grand_total_spoiled) ?></td>
                                <td class="text-end font-monospace text-secondary">-<?= number_format($grand_total_transferred) ?></td>
                                <td class="text-end font-monospace <?= $latest_inventory_balance < 0 ? 'text-danger' : 'text-dark' ?>">
                                    <?= number_format($latest_inventory_balance) ?>
                                </td>
                                <td class="text-center small text-muted">—</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODALS INCLUSION -->
<!-- ============================================================ -->
<?php include __DIR__ . '/model/modal_add_tagging_program.php'; ?>
<?php include __DIR__ . '/model/modal_view_program_vouchers.php'; ?>
<?php include __DIR__ . '/model/modal_ear_tag_stock.php'; ?>

<?php
$pageScripts = '
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {

    // Preserve and switch tab parameter in URL without page refresh
    $(\'#earTagTabs button[data-bs-toggle="pill"]\').on(\'shown.bs.tab\', function (e) {
        var target = $(e.target).attr("data-bs-target");
        var tabParam = target.indexOf("programs") !== -1 ? "programs" : "summary";
        var newUrl = new URL(window.location.href);
        newUrl.searchParams.set("tab", tabParam);
        window.history.replaceState(null, "", newUrl.toString());
        
        // Adjust column layouts for DataTables
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });

    // 1. Tagging Programs DataTable
    if ($.fn.DataTable.isDataTable("#taggingProgramsTable")) {
        $("#taggingProgramsTable").DataTable().destroy();
    }
    var progTable = $("#taggingProgramsTable").DataTable({
        "order": [[0, "desc"]],
        "pageLength": 10,
        "language": {
            "emptyTable": "<div class=\\"py-4 text-center text-muted\\"><i class=\\"bi bi-tag fs-3 d-block mb-2 text-secondary\\"></i>No tagging programs logged for ' . $selected_year . ' yet. Click <strong>\\\"Log Tagging Program\\\"</strong> to add an entry.</div>"
        },
        "dom": \'<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>\',
        "buttons": [
            {
                extend: "csv",
                text: "<i class=\\"bi bi-file-earmark-spreadsheet\\"></i> CSV",
                className: "btn btn-sm btn-success me-2"
            },
            {
                extend: "pdf",
                text: "<i class=\\"bi bi-file-pdf\\"></i> PDF",
                className: "btn btn-sm btn-danger me-2"
            },
            {
                extend: "print",
                text: "<i class=\\"bi bi-printer\\"></i> Print",
                className: "btn btn-sm btn-dark"
            }
        ]
    });

    // 2. Monthly Summary DataTable
    if ($.fn.DataTable.isDataTable("#monthlySummaryTable")) {
        $("#monthlySummaryTable").DataTable().destroy();
    }
    var sumTable = $("#monthlySummaryTable").DataTable({
        "ordering": false,
        "paging": false,
        "info": false,
        "searching": false,
        "dom": \'<"d-flex justify-content-end mb-2"B>t\',
        "buttons": [
            {
                extend: "csv",
                text: "<i class=\\"bi bi-file-earmark-spreadsheet\\"></i> CSV Summary",
                className: "btn btn-sm btn-success me-2"
            },
            {
                extend: "pdf",
                text: "<i class=\\"bi bi-file-pdf\\"></i> PDF Summary",
                className: "btn btn-sm btn-danger me-2"
            },
            {
                extend: "print",
                text: "<i class=\\"bi bi-printer\\"></i> Print Summary",
                className: "btn btn-sm btn-dark"
            }
        ]
    });

    // Alert query checks from redirect
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

    // -------------------------------------------------------------
    // ANIMAL DETAILS & CATTLE VOUCHER DYNAMIC ROWS MANAGEMENT
    // -------------------------------------------------------------
    var animalRowIndex = 0;

    function addAnimalRow(earTagVal = "", voucherVal = "", breedVal = "", sexVal = "Female", ageVal = "") {
        animalRowIndex++;
        var tr = `
            <tr class="animal-item-row" data-row-index="${animalRowIndex}">
                <td class="text-center fw-bold text-muted row-number">${$("#animalVouchersBody tr").length + 1}</td>
                <td>
                    <input type="text" name="animals[${animalRowIndex}][ear_tag_number]" class="form-control form-control-sm font-monospace text-uppercase tag-input" placeholder="e.g. LK-EP-00123" value="${earTagVal}" required>
                </td>
                <td>
                    <input type="text" name="animals[${animalRowIndex}][voucher_number]" class="form-control form-control-sm font-monospace text-uppercase voucher-input" placeholder="e.g. CV-2026-981" value="${voucherVal}" required>
                </td>
                <td>
                    <input type="text" name="animals[${animalRowIndex}][breed]" class="form-control form-control-sm" placeholder="e.g. Friesian, Jersey" value="${breedVal}" list="breedOptions" required>
                </td>
                <td>
                    <select name="animals[${animalRowIndex}][sex]" class="form-select form-select-sm">
                        <option value="Female" ${sexVal === "Female" ? "selected" : ""}>Female</option>
                        <option value="Male" ${sexVal === "Male" ? "selected" : ""}>Male</option>
                    </select>
                </td>
                <td>
                    <input type="text" name="animals[${animalRowIndex}][age]" class="form-control form-control-sm" placeholder="e.g. 2 Yrs, 18 Mos" value="${ageVal}" required>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1 btn-remove-animal-row" title="Remove animal entry">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $("#animalVouchersBody").append(tr);
        recalcAnimalCounts();
    }

    function recalcAnimalCounts() {
        var count = $("#animalVouchersBody tr").length;
        $("#voucherCountBadge").html(`<i class="bi bi-link-45deg me-1"></i>${count} Animals Linked`);
        
        // Re-index displayed row numbers
        $("#animalVouchersBody tr").each(function(idx) {
            $(this).find(".row-number").text(idx + 1);
        });

        // Automatically sync Tags Used with number of animal entries if user has not set higher count
        var currentTagsUsed = parseInt($("#prog_tags_used").val()) || 0;
        if (count > 0 && currentTagsUsed < count) {
            $("#prog_tags_used").val(count);
        }
    }

    // Add animal row button handlers
    $("#btnAddAnimalRow, #btnAddAnimalRowBottom").on("click", function() {
        addAnimalRow();
    });

    // Remove animal row
    $(document).on("click", ".btn-remove-animal-row", function() {
        $(this).closest("tr").remove();
        recalcAnimalCounts();
    });

    // Open Modal for New Tagging Program
    $(document).on("click", "#btnOpenAddProgramModal", function(e) {
        $("#modalTaggingProgramLabel").text("Log Ear Tagging Program");
        $("#formTaggingProgram")[0].reset();
        $("#prog_id").val("0");
        $("#prog_date").val(new Date().toISOString().split("T")[0]);
        $("#animalVouchersBody").empty();
        $("#icLookupFeedback").text("Type IC Number to auto-populate").removeClass("text-success text-danger").addClass("text-muted");
        $("#btnSubmitProgram").html(\'<i class="bi bi-check-circle me-1"></i>Save Tagging Program\');
        
        // Add one initial default animal row
        addAnimalRow();

        var mEl = document.getElementById("modalTaggingProgram");
        if (mEl) {
            bootstrap.Modal.getOrCreateInstance(mEl).show();
        }
    });

    // Also handle show.bs.modal event if triggered via data-bs-target
    $("#modalTaggingProgram").on("show.bs.modal", function(e) {
        var related = e.relatedTarget;
        if (related && $(related).attr("id") === "btnOpenAddProgramModal") {
            if ($("#animalVouchersBody tr").length === 0) {
                addAnimalRow();
            }
        }
    });

    // -------------------------------------------------------------
    // FARMER IC NUMBER AUTO-POPULATION
    // -------------------------------------------------------------
    var icLookupTimer = null;
    $("#prog_nic_no").on("input change blur", function(e) {
        var nic = $(this).val().trim();
        if (nic.length < 5) {
            $("#icLookupFeedback").text("Type IC Number to auto-populate").removeClass("text-success text-danger").addClass("text-muted");
            return;
        }

        clearTimeout(icLookupTimer);
        var delay = (e.type === "blur") ? 0 : 400;

        icLookupTimer = setTimeout(function() {
            $("#icLookupSpinner").removeClass("d-none");
            $.ajax({
                url: "processors/lookup_farmer_by_nic.php",
                type: "GET",
                data: { nic_no: nic },
                dataType: "json",
                success: function(resp) {
                    $("#icLookupSpinner").addClass("d-none");
                    if (resp.success && resp.found && resp.farmer) {
                        var f = resp.farmer;
                        $("#prog_farm_reg_no").val(f.farm_registration_no || "");
                        $("#prog_address").val(f.location_address || "");
                        if (!$("#prog_farmer_name").val() && f.full_name) {
                            $("#prog_farmer_name").val(f.full_name);
                        }
                        $("#icLookupFeedback").html(\'<i class="bi bi-check-circle-fill text-success"></i> Registered Farmer: <strong>\' + f.full_name + \'</strong>\')
                            .removeClass("text-muted text-danger").addClass("text-success");
                    } else {
                        $("#icLookupFeedback").html(\'<i class="bi bi-info-circle text-info"></i> New IC - farmer will be registered with these details.\')
                            .removeClass("text-muted text-success").addClass("text-info");
                    }
                },
                error: function() {
                    $("#icLookupSpinner").addClass("d-none");
                }
            });
        }, delay);
    });

    // -------------------------------------------------------------
    // EDIT TAGGING PROGRAM
    // -------------------------------------------------------------
    $(document).on("click", ".btn-edit-program", function() {
        var progId = $(this).data("id");
        if (!progId) return;

        Swal.fire({
            title: "Loading...",
            text: "Fetching program and cattle identity records...",
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        $.ajax({
            url: "processors/get_program_details.php",
            type: "GET",
            data: { id: progId },
            dataType: "json",
            success: function(resp) {
                Swal.close();
                if (!resp.success || !resp.program) {
                    Swal.fire("Error", resp.message || "Failed to load program details", "error");
                    return;
                }

                var p = resp.program;
                $("#modalTaggingProgramLabel").text("Edit Ear Tagging Program");
                $("#prog_id").val(p.id);
                $("#prog_date").val(p.program_date);
                $("#prog_nic_no").val(p.nic_no);
                $("#prog_farmer_name").val(p.farmer_name);
                $("#prog_farm_reg_no").val(p.farm_reg_no || "");
                $("#prog_address").val(p.address || "");
                $("#prog_tags_used").val(p.tags_used);
                $("#prog_tags_spoiled").val(p.tags_spoiled);
                $("#prog_staff_involved").val(p.staff_involved || "");
                $("#prog_remarks").val(p.remarks || "");

                $("#icLookupFeedback").html(\'<i class="bi bi-check-circle-fill text-success"></i> Existing record linked to IC: <strong>\' + p.nic_no + \'</strong>\')
                    .removeClass("text-muted text-danger").addClass("text-success");

                // Populate vouchers
                $("#animalVouchersBody").empty();
                if (resp.vouchers && resp.vouchers.length > 0) {
                    $.each(resp.vouchers, function(i, v) {
                        addAnimalRow(v.ear_tag_number, v.voucher_number, v.breed, v.sex, v.age);
                    });
                } else {
                    addAnimalRow();
                }

                $("#btnSubmitProgram").html(\'<i class="bi bi-check-circle me-1"></i>Update Tagging Program\');
                var mEl = document.getElementById("modalTaggingProgram");
                if (mEl) {
                    bootstrap.Modal.getOrCreateInstance(mEl).show();
                }
            },
            error: function() {
                Swal.close();
                Swal.fire("Error", "Server error while communicating with database.", "error");
            }
        });
    });

    // -------------------------------------------------------------
    // VIEW LINKED ANIMAL IDENTITY VOUCHERS MODAL
    // -------------------------------------------------------------
    $(document).on("click", ".btn-view-vouchers", function() {
        var progId = $(this).data("id");
        if (!progId) return;

        Swal.fire({
            title: "Loading...",
            text: "Fetching registered animal vouchers...",
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        $.ajax({
            url: "processors/get_program_details.php",
            type: "GET",
            data: { id: progId },
            dataType: "json",
            success: function(resp) {
                Swal.close();
                if (!resp.success || !resp.program) {
                    Swal.fire("Error", resp.message || "Failed to load program details", "error");
                    return;
                }

                var p = resp.program;
                $("#viewProgDate").text(p.program_date);
                $("#viewProgFarmer").text(p.farmer_name);
                $("#viewProgNIC").text(p.nic_no);
                $("#viewProgRegNo").text(p.farm_reg_no || "N/A");
                $("#viewProgStaff").text(p.staff_involved || "N/A");
                $("#viewProgTags").text(p.tags_used + " Used / " + p.tags_spoiled + " Spoiled");
                $("#viewProgSubtitle").text("Program #" + p.id + " - " + p.farmer_name);

                var tbody = $("#viewVouchersBody");
                tbody.empty();

                if (resp.vouchers && resp.vouchers.length > 0) {
                    $.each(resp.vouchers, function(idx, v) {
                        var row = `
                            <tr>
                                <td class="text-center fw-bold text-muted">${idx + 1}</td>
                                <td><span class="badge bg-light text-dark border font-monospace fw-bold">${v.ear_tag_number}</span></td>
                                <td><span class="badge bg-primary-subtle text-primary border font-monospace fw-bold">${v.voucher_number}</span></td>
                                <td>${v.breed || "—"}</td>
                                <td><span class="badge ${v.sex === "Female" ? "bg-info-subtle text-info" : "bg-primary-subtle text-primary"}">${v.sex}</span></td>
                                <td>${v.age || "—"}</td>
                            </tr>
                        `;
                        tbody.append(row);
                    });
                } else {
                    tbody.html(\'<tr><td colspan="6" class="text-center text-muted py-3">No individual cattle vouchers registered for this program.</td></tr>\');
                }

                var mEl = document.getElementById("modalViewVouchers");
                if (mEl) {
                    bootstrap.Modal.getOrCreateInstance(mEl).show();
                }
            },
            error: function() {
                Swal.close();
                Swal.fire("Error", "Server error while fetching animal vouchers.", "error");
            }
        });
    });

    // -------------------------------------------------------------
    // DELETE TAGGING PROGRAM
    // -------------------------------------------------------------
    $(document).on("click", ".btn-delete-program", function() {
        var progId = $(this).data("id");
        if (!progId) return;

        Swal.fire({
            icon: "warning",
            title: "Delete Tagging Program?",
            html: "Are you sure you want to permanently delete this program and its interconnected cattle vouchers?<br><small class=\"text-danger\">Monthly summary balances will recalculate dynamically.</small>",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Yes, Delete",
            cancelButtonText: "Cancel"
        }).then(function(result) {
            if (result.isConfirmed) {
                window.location.href = "processors/delete_ear_tag_program.php?id=" + progId;
            }
        });
    });

    // -------------------------------------------------------------
    // MONTHLY SUMMARY: LOG / EDIT STOCK BUTTON
    // -------------------------------------------------------------
    $(document).on("click", ".btn-edit-stock", function() {
        var mId = $(this).data("id") || 0;
        var monthNum = $(this).data("month");
        var opening = $(this).data("opening") || 0;
        var received = $(this).data("received") || 0;
        var transferred = $(this).data("transferred") || 0;

        $("#stock_id").val(mId);
        $("#stock_year").val("<?= $selected_year ?>");
        $("#stock_month").val(monthNum);
        $("#stock_opening_balance").val(opening);
        $("#stock_received_qty").val(received);
        $("#stock_transferred_qty").val(transferred);

        var mEl = document.getElementById("modalStockAdjustment");
        if (mEl) {
            bootstrap.Modal.getOrCreateInstance(mEl).show();
        }
    });

});
</script>

<!-- Datalist for common cattle breeds -->
<datalist id="breedOptions">
    <option value="Friesian">
    <option value="Jersey">
    <option value="Sahiwal">
    <option value="Ayrshire">
    <option value="Friesian x Jersey Cross">
    <option value="Sahiwal Cross">
    <option value="Local / Zebu (Batu Harak)">
    <option value="Murrah Buffalo">
    <option value="Surti Buffalo">
    <option value="Indigenous Buffalo">
</datalist>
';
require_once '../../../includes/footer.php';
?>
