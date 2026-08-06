<?php
// pages/modules/farm/feed_management.php -> Daily Feed Distribution & Annex 4: Mash Details Module
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

if ($_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;

// Active tab determination
$active_tab = $_GET['tab'] ?? 'daily'; // 'daily' or 'annex4'

// Selected filter month & date (default to today's date YYYY-MM-DD)
$selected_date = $_GET['date'] ?? date('Y-m-d');
$selected_month = $_GET['month'] ?? date('Y-m', strtotime($selected_date));
$first_day_of_month = date('Y-m-01', strtotime($selected_month . '-01'));
$last_day_of_month = date('Y-m-t', strtotime($selected_month . '-01'));
$month_label = date('F Y', strtotime($first_day_of_month));
$date_label = date('d F Y', strtotime($selected_date));

// Fetch available Cages for dropdowns
$cages_res = $mysqli->query("SELECT id, cage_name FROM cages ORDER BY cage_name");
$cages = [];
if ($cages_res) {
    while ($row = $cages_res->fetch_assoc()) {
        $cages[] = $row;
    }
}

// -------------------------------------------------------------
// 1. Fetch Daily Feed Distribution Records for Selected Month
// -------------------------------------------------------------
$sql_daily = "SELECT df.*, c.cage_name 
              FROM daily_feed_distribution df
              LEFT JOIN cages c ON df.cage_id = c.id
              WHERE df.distribution_date BETWEEN ? AND ?
              ORDER BY df.distribution_date DESC, df.id DESC";

$stmt_daily = $mysqli->prepare($sql_daily);
$stmt_daily->bind_param("ss", $first_day_of_month, $last_day_of_month);
$stmt_daily->execute();
$res_daily = $stmt_daily->get_result();

$daily_records = [];
$total_feed_needed = 0;
$total_feed_distributed = 0;
$total_chicks_fed = 0;

if ($res_daily) {
    while ($r = $res_daily->fetch_assoc()) {
        $daily_records[] = $r;
        $total_feed_needed += floatval($r['amount_needed_kg']);
        $total_feed_distributed += floatval($r['amount_distributed_kg']);
        $total_chicks_fed += intval($r['no_of_chicks']);
    }
}
$stmt_daily->close();

// -------------------------------------------------------------
// 2. Fetch & Auto-Sync Daily Mash Details Records
// -------------------------------------------------------------
$feed_types = ['Layer', 'Starter', 'Grower'];

// Ensure feed types exist for $selected_date & auto-populate opening stock from immediate previous date
foreach ($feed_types as $ft) {
    // 1. Fetch closing balance from immediate previous date strictly before selected_date
    $stmt_prev = $mysqli->prepare("SELECT balance_stock_kg FROM monthly_mash_details WHERE record_month < ? AND feed_type = ? ORDER BY record_month DESC LIMIT 1");
    $stmt_prev->bind_param("ss", $selected_date, $ft);
    $stmt_prev->execute();
    $prev_res = $stmt_prev->get_result();
    $prev_closing = 0.00;
    if ($prev_res && $prev_res->num_rows > 0) {
        $prev_closing = floatval($prev_res->fetch_assoc()['balance_stock_kg']);
    }
    $stmt_prev->close();

    // 2. Check if record exists for selected_date
    $stmt_chk = $mysqli->prepare("SELECT id, opening_stock_kg FROM monthly_mash_details WHERE record_month = ? AND feed_type = ? LIMIT 1");
    $stmt_chk->bind_param("ss", $selected_date, $ft);
    $stmt_chk->execute();
    $chk_res = $stmt_chk->get_result();

    if ($chk_res->num_rows === 0) {
        $stmt_ins = $mysqli->prepare("INSERT INTO monthly_mash_details (record_month, feed_type, opening_stock_kg, received_kg, consumption_kg, issued_other_farm_kg, balance_stock_kg) VALUES (?, ?, ?, 0.00, 0.00, 0.00, ?)");
        $stmt_ins->bind_param("ssdd", $selected_date, $ft, $prev_closing, $prev_closing);
        $stmt_ins->execute();
        $stmt_ins->close();
    } else {
        $row_curr = $chk_res->fetch_assoc();
        // Update opening_stock_kg to match previous day's closing balance
        $stmt_upd_open = $mysqli->prepare("UPDATE monthly_mash_details SET opening_stock_kg = ? WHERE id = ?");
        $stmt_upd_open->bind_param("di", $prev_closing, $row_curr['id']);
        $stmt_upd_open->execute();
        $stmt_upd_open->close();
    }
    $stmt_chk->close();
}

// Fetch records for selected_date & auto-calculate daily consumption
$mash_records = [];
$sql_mash = "SELECT * FROM monthly_mash_details WHERE record_month = ? ORDER BY FIELD(feed_type, 'Layer', 'Starter', 'Grower')";
$stmt_mash = $mysqli->prepare($sql_mash);
$stmt_mash->bind_param("s", $selected_date);
$stmt_mash->execute();
$res_mash = $stmt_mash->get_result();

$total_opening_stock = 0;
$total_received_stock = 0;
$total_consumption = 0;
$total_issued_other = 0;
$total_balance_stock = 0;

if ($res_mash) {
    while ($m = $res_mash->fetch_assoc()) {
        $ft = $m['feed_type'];

        // Auto-sum daily consumption from daily feed distribution log for this date
        $stmt_sum = $mysqli->prepare("SELECT COALESCE(SUM(amount_distributed_kg), 0) AS total_consumed FROM daily_feed_distribution WHERE feed_type = ? AND distribution_date = ?");
        $stmt_sum->bind_param("ss", $ft, $selected_date);
        $stmt_sum->execute();
        $sum_row = $stmt_sum->get_result()->fetch_assoc();
        $auto_consumption = floatval($sum_row['total_consumed'] ?? 0);
        $stmt_sum->close();

        // Calculate balance: (opening + received) - (consumption + issued_other)
        $opening = floatval($m['opening_stock_kg']);
        $received = floatval($m['received_kg']);
        $issued_other = floatval($m['issued_other_farm_kg']);
        $auto_balance = ($opening + $received) - ($auto_consumption + $issued_other);

        if (abs(floatval($m['consumption_kg']) - $auto_consumption) > 0.001 || abs(floatval($m['balance_stock_kg']) - $auto_balance) > 0.001) {
            $stmt_upd = $mysqli->prepare("UPDATE monthly_mash_details SET consumption_kg = ?, balance_stock_kg = ? WHERE id = ?");
            $stmt_upd->bind_param("ddi", $auto_consumption, $auto_balance, $m['id']);
            $stmt_upd->execute();
            $stmt_upd->close();

            $m['consumption_kg'] = $auto_consumption;
            $m['balance_stock_kg'] = $auto_balance;
        }

        $mash_records[] = $m;

        $total_opening_stock += $opening;
        $total_received_stock += $received;
        $total_consumption += $auto_consumption;
        $total_issued_other += $issued_other;
        $total_balance_stock += $auto_balance;
    }
}
$stmt_mash->close();
?>

<!-- Header -->
<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h3 class="fw-bold text-dark m-0">
            <i class="bi bi-basket-fill me-2" style="color: #820100;"></i>Feed Management
        </h3>
        <p class="text-muted mb-0 small">Manage daily feed distribution to cages and review monthly Mash inventory summaries.</p>
    </div>
    <div class="col-md-5 d-flex justify-content-end align-items-center gap-2">
        <label class="fw-bold mb-0 text-nowrap"><i class="bi bi-calendar3 me-1"></i>Select Month:</label>
        <input type="month" id="filter_month" class="form-control form-control-sm w-auto shadow-sm" value="<?= $selected_month ?>">
        <button type="button" id="btn_apply_filter" class="btn btn-sm btn-apply-filter px-3 fw-bold" style="background-color: #370709; color: #ffffff;">
            <i class="bi bi-funnel me-1"></i>Filter
        </button>
    </div>
</div>

<!-- Notification Status SweetAlert -->
<?php if (isset($_GET['status']) && isset($_GET['msg'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: '<?= ($_GET['status'] === 'success') ? 'success' : 'error' ?>',
                    title: '<?= ($_GET['status'] === 'success') ? 'Success!' : 'Error!' ?>',
                    text: <?= json_encode($_GET['msg'] ?? '') ?>,
                    confirmButtonColor: '#820100',
                    timer: 3500,
                    timerProgressBar: true
                });
            }
        });
    </script>
<?php endif; ?>

<!-- Primary Navigation Tabs -->
<ul class="nav nav-tabs border-bottom-0 mb-4" id="feedTabs" role="tablist" style="gap: 5px;">
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold <?= ($active_tab === 'daily') ? 'active text-light' : 'text-dark bg-white' ?> border-0 py-3 px-4" 
                id="daily-tab" data-bs-toggle="tab" data-bs-target="#daily-pane" type="button" role="tab" 
                style="<?= ($active_tab === 'daily') ? 'background-color: #820100; color: #ffffff; border-radius: 8px 8px 0 0;' : 'border-radius: 8px 8px 0 0;' ?>">
            <i class="bi bi-calendar-check me-2"></i>Daily Feed Distribution Log
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold <?= ($active_tab === 'annex4') ? 'active text-light' : 'text-dark bg-white' ?> border-0 py-3 px-4" 
                id="annex4-tab" data-bs-toggle="tab" data-bs-target="#annex4-pane" type="button" role="tab"
                style="<?= ($active_tab === 'annex4') ? 'background-color: #185dbd; color: #ffffff; border-radius: 8px 8px 0 0;' : 'border-radius: 8px 8px 0 0;' ?>">
            <i class="bi bi-file-earmark-spreadsheet me-2"></i>Mash Details (Monthly Summary)
        </button>
    </li>
</ul>

<div class="tab-content" id="feedTabsContent">

    <!-- ========================================================= -->
    <!-- TAB 1: DAILY FEED DISTRIBUTION LOG -->
    <!-- ========================================================= -->
    <div class="tab-pane fade <?= ($active_tab === 'daily') ? 'show active' : '' ?>" id="daily-pane" role="tabpanel">
        
        <!-- KPI Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-3 bg-white card-kpi-distributed" style="border-radius: 12px; border-left: 5px solid #8d170e !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Total Feed Distributed</small>
                            <span class="fs-3 fw-bold text-color-c11" style="color: #8d170e;"><?= number_format($total_feed_distributed, 2) ?> <small class="fs-6">kg</small></span>
                        </div>
                        <div class="p-3 rounded-circle bg-color-c11-light" style="background-color: #fce8e6; color: #8d170e;">
                            <i class="bi bi-basket-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-3 bg-white card-kpi-chicks" style="border-radius: 12px; border-left: 5px solid #003ddc !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Total Chicks / Stock Fed</small>
                            <span class="fs-3 fw-bold text-color-c2" style="color: #003ddc;"><?= number_format($total_chicks_fed) ?></span>
                        </div>
                        <div class="p-3 rounded-circle bg-color-c2-light" style="background-color: #e6ecfc; color: #003ddc;">
                            <i class="bi bi-people-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-3 bg-white card-kpi-needed" style="border-radius: 12px; border-left: 5px solid #efbe2c !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Total Feed Needed</small>
                            <span class="fs-3 fw-bold text-color-c8" style="color: #b08723;"><?= number_format($total_feed_needed, 2) ?> <small class="fs-6">kg</small></span>
                        </div>
                        <div class="p-3 rounded-circle bg-color-c3-light" style="background-color: #fdf8e9; color: #b08723;">
                            <i class="bi bi-calculator-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark m-0"><i class="bi bi-list-check me-2 text-color-c11" style="color: #8d170e;"></i>Daily Feed Distribution Entries for <?= $month_label ?></h5>
                <button class="btn btn-log-feed fw-bold px-4 text-light" style="background-color: #820100; color: #ffffff;" data-bs-toggle="modal" data-bs-target="#addDailyFeedModal">
                    <i class="bi bi-plus-circle me-1"></i>Log Daily Feed
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center" id="dailyFeedTable">
                        <thead class="table-header-dark" style="background-color: #370709; color: #ffffff;">
                            <tr>
                                <th>Date</th>
                                <th>Feed Type</th>
                                <th>Cage Name</th>
                                <th>Batch No.</th>
                                <th>No. of Chicks</th>
                                <th>Amount Needed (kg)</th>
                                <th>Amount Distributed (kg)</th>
                                <th>Remarks</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daily_records as $r): ?>
                                <tr>
                                    <td class="fw-bold text-nowrap"><?= date('Y-m-d', strtotime($r['distribution_date'])) ?></td>
                                    <td>
                                        <span class="badge px-3 py-2 fs-6 
                                            <?= ($r['feed_type'] === 'Layer') ? 'badge-feed-layer' : '' ?>
                                            <?= ($r['feed_type'] === 'Starter') ? 'badge-feed-starter' : '' ?>
                                            <?= ($r['feed_type'] === 'Grower') ? 'badge-feed-grower' : '' ?>"
                                            style="
                                            <?= ($r['feed_type'] === 'Layer') ? 'background-color: #820100; color: #ffffff;' : '' ?>
                                            <?= ($r['feed_type'] === 'Starter') ? 'background-color: #efbe2c; color: #370709;' : '' ?>
                                            <?= ($r['feed_type'] === 'Grower') ? 'background-color: #003ddc; color: #ffffff;' : '' ?>
                                            ">
                                            <?= htmlspecialchars($r['feed_type']) ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($r['cage_name'] ?? 'All Cages / Unspecified') ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['batch_no'] ?? '-') ?></span></td>
                                    <td><?= number_format($r['no_of_chicks']) ?></td>
                                    <td><?= number_format($r['amount_needed_kg'], 2) ?></td>
                                    <td class="fw-bold text-color-c11" style="color: #8d170e;"><?= number_format($r['amount_distributed_kg'], 2) ?></td>
                                    <td class="small"><?= htmlspecialchars($r['remarks'] ?? '-') ?></td>
                                    <td class="text-nowrap">
                                        <button class="btn btn-sm btn-edit-action me-1 btn-edit-feed" style="border-color: #185dbd; color: #185dbd;"
                                                data-id="<?= $r['id'] ?>"
                                                data-distribution_date="<?= $r['distribution_date'] ?>"
                                                data-cage_id="<?= $r['cage_id'] ?>"
                                                data-batch_no="<?= htmlspecialchars($r['batch_no'] ?? '') ?>"
                                                data-feed_type="<?= htmlspecialchars($r['feed_type']) ?>"
                                                data-no_of_chicks="<?= $r['no_of_chicks'] ?>"
                                                data-amount_needed_kg="<?= $r['amount_needed_kg'] ?>"
                                                data-amount_distributed_kg="<?= $r['amount_distributed_kg'] ?>"
                                                data-remarks="<?= htmlspecialchars($r['remarks'] ?? '') ?>"
                                                data-bs-toggle="modal" data-bs-target="#editDailyFeedModal">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="processors/daily_feed_distribution_crud.php?action=delete&id=<?= $r['id'] ?>" class="btn btn-sm btn-delete-action btn-delete" style="border-color: #ef4016; color: #ef4016;">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- ========================================================= -->
    <!-- TAB 2: ANNEX 4: MASH DETAILS (MONTHLY SUMMARY) -->
    <!-- ========================================================= -->
    <div class="tab-pane fade <?= ($active_tab === 'annex4') ? 'show active' : '' ?>" id="annex4-pane" role="tabpanel">

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 bg-white card-kpi-opening" style="border-radius: 12px; border-left: 5px solid #185dbd !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Total Opening Stock</small>
                            <span class="fs-4 fw-bold text-color-c10" style="color: #185dbd;"><?= number_format($total_opening_stock, 2) ?> kg</span>
                        </div>
                        <div class="p-3 bg-color-c10-light rounded-circle" style="background-color: #e8f0fa; color: #185dbd;"><i class="bi bi-box-seam-fill fs-4"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 bg-white card-kpi-received" style="border-radius: 12px; border-left: 5px solid #689ccf !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Total Received</small>
                            <span class="fs-4 fw-bold text-color-c10" style="color: #185dbd;"><?= number_format($total_received_stock, 2) ?> kg</span>
                        </div>
                        <div class="p-3 bg-color-c5-light rounded-circle" style="background-color: #eff5fa; color: #185dbd;"><i class="bi bi-arrow-down-left-circle-fill fs-4"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 bg-white card-kpi-consumption" style="border-radius: 12px; border-left: 5px solid #ef4016 !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Total Monthly Consumption</small>
                            <span class="fs-4 fw-bold text-color-c6" style="color: #ef4016;"><?= number_format($total_consumption, 2) ?> kg</span>
                        </div>
                        <div class="p-3 bg-color-c6-light rounded-circle" style="background-color: #fdece8; color: #ef4016;"><i class="bi bi-fire fs-4"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 bg-white card-kpi-balance" style="border-radius: 12px; border-left: 5px solid #b08723 !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Total Balance Stock</small>
                            <span class="fs-4 fw-bold text-color-c8" style="color: #b08723;"><?= number_format($total_balance_stock, 2) ?> kg</span>
                        </div>
                        <div class="p-3 rounded-circle bg-color-c3-light" style="background-color: #fdf8e9; color: #b08723;"><i class="bi bi-pie-chart-fill fs-4"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="fw-bold text-dark m-0">
                    <i class="bi bi-file-earmark-spreadsheet me-2 text-color-c10" style="color: #185dbd;"></i>Mash Details Inventory Register (<span id="mash_date_display"><?= $date_label ?></span>)
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <label for="mash_date_filter" class="fw-bold mb-0 text-nowrap small text-muted"><i class="bi bi-calendar-date me-1"></i>Mash Date:</label>
                    <input type="date" id="mash_date_filter" class="form-control form-control-sm w-auto shadow-sm fw-bold" value="<?= $selected_date ?>">
                    <button type="button" id="btn_auto_fetch_opening" class="btn btn-sm text-white px-3 fw-bold shadow-sm" style="background-color: #185dbd; border-color: #185dbd;" title="Auto-fetch closing stock from immediate previous date as opening stock">
                        <i class="bi bi-arrow-repeat me-1"></i>Auto-Fetch Opening Stock
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center" id="mashTable">
                        <thead class="table-header-dark" style="background-color: #370709; color: #ffffff;">
                            <tr>
                                <th class="py-3">Feed Type</th>
                                <th class="py-3">Opening Stock (kg)</th>
                                <th class="py-3">Received (kg)</th>
                                <th class="py-3 table-header-consumption" style="background-color: #ef4016; color: #ffffff;">Consumption (Auto-Calculated kg)</th>
                                <th class="py-3">Issued to Other Farm (kg)</th>
                                <th class="py-3 table-header-balance" style="background-color: #185dbd; color: #ffffff;">Balance Stock (Auto-Calculated kg)</th>
                                <th class="py-3">Remarks</th>
                                <th class="py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mash_records as $m): ?>
                                <tr id="mash_row_<?= strtolower(str_replace(' ', '_', $m['feed_type'])) ?>" data-feed_type="<?= htmlspecialchars($m['feed_type']) ?>">
                                    <td class="fw-bold text-start fs-6">
                                        <i class="bi bi-arrow-right-short text-color-c10 me-1" style="color: #185dbd;"></i><?= htmlspecialchars($m['feed_type']) ?>
                                    </td>
                                    <td class="fw-bold cell-opening-stock"><?= number_format($m['opening_stock_kg'], 2) ?></td>
                                    <td class="fw-bold text-color-c10 cell-received" style="color: #185dbd;"><?= number_format($m['received_kg'], 2) ?></td>
                                    <td class="fw-bold text-color-c6 bg-color-c6-light cell-consumption" style="color: #ef4016; background-color: #fdece8;"><?= number_format($m['consumption_kg'], 2) ?></td>
                                    <td class="fw-bold text-color-c8 cell-issued-other" style="color: #b08723;"><?= number_format($m['issued_other_farm_kg'], 2) ?></td>
                                    <td class="fw-bold text-color-c10 bg-color-c10-light cell-balance" style="color: #185dbd; background-color: #e8f0fa;"><?= number_format($m['balance_stock_kg'], 2) ?></td>
                                    <td class="small cell-remarks"><?= htmlspecialchars($m['remarks'] ?? '-') ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-edit-action btn-edit-mash fw-bold px-3" style="border-color: #185dbd; color: #185dbd;"
                                                data-id="<?= $m['id'] ?>"
                                                data-feed_type="<?= htmlspecialchars($m['feed_type']) ?>"
                                                data-opening_stock_kg="<?= $m['opening_stock_kg'] ?>"
                                                data-received_kg="<?= $m['received_kg'] ?>"
                                                data-consumption_kg="<?= $m['consumption_kg'] ?>"
                                                data-issued_other_farm_kg="<?= $m['issued_other_farm_kg'] ?>"
                                                data-balance_stock_kg="<?= $m['balance_stock_kg'] ?>"
                                                data-remarks="<?= htmlspecialchars($m['remarks'] ?? '') ?>"
                                                data-bs-toggle="modal" data-bs-target="#editMashModal">
                                            <i class="bi bi-pencil-square me-1"></i>Edit Stock
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="tfoot-summary fw-bold" style="background-color: #d4c7b7; color: #370709;">
                            <tr>
                                <td class="text-start">TOTAL SUMMARY</td>
                                <td id="foot_total_opening"><?= number_format($total_opening_stock, 2) ?> kg</td>
                                <td id="foot_total_received"><?= number_format($total_received_stock, 2) ?> kg</td>
                                <td id="foot_total_consumption" class="text-color-c6" style="color: #ef4016;"><?= number_format($total_consumption, 2) ?> kg</td>
                                <td id="foot_total_issued"><?= number_format($total_issued_other, 2) ?> kg</td>
                                <td id="foot_total_balance" class="text-color-c10" style="color: #185dbd;"><?= number_format($total_balance_stock, 2) ?> kg</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- Modals -->
<?php
include './models/daily_feed_modals.php';
include './models/monthly_mash_modals.php';
?>

<!-- Auto-Fetch Opening Stock AJAX Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mashDateFilter = document.getElementById('mash_date_filter');
    const btnAutoFetchOpening = document.getElementById('btn_auto_fetch_opening');

    function autoFetchOpeningStock(selectedDate) {
        if (!selectedDate) return;

        const btn = btnAutoFetchOpening;
        const origHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Fetching...';
        }

        const formData = new FormData();
        formData.append('action', 'fetch_opening_stock');
        formData.append('date', selectedDate);

        fetch('processors/monthly_mash_details_crud.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = origHtml;
            }

            if (data.status === 'success') {
                const mashDateDisplay = document.getElementById('mash_date_display');
                if (mashDateDisplay && data.date_label) {
                    mashDateDisplay.innerText = data.date_label;
                }

                // Sync URL query if date changed
                const currentUrlParams = new URLSearchParams(window.location.search);
                if (currentUrlParams.get('date') !== selectedDate) {
                    window.location.href = 'feed_management.php?tab=annex4&date=' + encodeURIComponent(selectedDate);
                    return;
                }

                // Update table rows in real-time
                if (data.records && Array.isArray(data.records)) {
                    data.records.forEach(r => {
                        const feedKey = r.feed_type.toLowerCase().replace(/\s+/g, '_');
                        const row = document.getElementById('mash_row_' + feedKey);
                        if (row) {
                            const openCell = row.querySelector('.cell-opening-stock');
                            const recCell = row.querySelector('.cell-received');
                            const consCell = row.querySelector('.cell-consumption');
                            const issCell = row.querySelector('.cell-issued-other');
                            const balCell = row.querySelector('.cell-balance');
                            const editBtn = row.querySelector('.btn-edit-mash');

                            if (openCell) openCell.innerText = parseFloat(r.opening_stock_kg).toFixed(2);
                            if (recCell) recCell.innerText = parseFloat(r.received_kg).toFixed(2);
                            if (consCell) consCell.innerText = parseFloat(r.consumption_kg).toFixed(2);
                            if (issCell) issCell.innerText = parseFloat(r.issued_other_farm_kg).toFixed(2);
                            if (balCell) balCell.innerText = parseFloat(r.balance_stock_kg).toFixed(2);

                            if (editBtn) {
                                editBtn.dataset.id = r.id;
                                editBtn.dataset.opening_stock_kg = r.opening_stock_kg;
                                editBtn.dataset.received_kg = r.received_kg;
                                editBtn.dataset.consumption_kg = r.consumption_kg;
                                editBtn.dataset.issued_other_farm_kg = r.issued_other_farm_kg;
                                editBtn.dataset.balance_stock_kg = r.balance_stock_kg;
                            }
                        }
                    });
                }

                // Update footer summary
                const footOpening = document.getElementById('foot_total_opening');
                const footReceived = document.getElementById('foot_total_received');
                const footConsumption = document.getElementById('foot_total_consumption');
                const footIssued = document.getElementById('foot_total_issued');
                const footBalance = document.getElementById('foot_total_balance');

                if (footOpening) footOpening.innerText = parseFloat(data.total_opening_stock).toFixed(2) + ' kg';
                if (footReceived) footReceived.innerText = parseFloat(data.total_received_stock).toFixed(2) + ' kg';
                if (footConsumption) footConsumption.innerText = parseFloat(data.total_consumption).toFixed(2) + ' kg';
                if (footIssued) footIssued.innerText = parseFloat(data.total_issued_other).toFixed(2) + ' kg';
                if (footBalance) footBalance.innerText = parseFloat(data.total_balance_stock).toFixed(2) + ' kg';

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Daily Opening Stock Auto-Fetched!',
                        text: `Opening stock populated from ${data.prev_date_label} closing balance for ${data.date_label}.`,
                        confirmButtonColor: '#185dbd',
                        timer: 3000,
                        timerProgressBar: true
                    });
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to fetch opening stock.',
                        confirmButtonColor: '#820100'
                    });
                }
            }
        })
        .catch(err => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = origHtml;
            }
            console.error('AJAX Error:', err);
        });
    }

    if (mashDateFilter) {
        mashDateFilter.addEventListener('change', function() {
            autoFetchOpeningStock(this.value);
        });
    }

    if (btnAutoFetchOpening) {
        btnAutoFetchOpening.addEventListener('click', function() {
            const val = mashDateFilter ? mashDateFilter.value : '<?= $selected_date ?>';
            autoFetchOpeningStock(val);
        });
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
