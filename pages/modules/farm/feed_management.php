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

// Selected filter month (default to current month YYYY-MM)
$selected_month = $_GET['month'] ?? date('Y-m');
$first_day_of_month = date('Y-m-01', strtotime($selected_month . '-01'));
$last_day_of_month = date('Y-m-t', strtotime($selected_month . '-01'));
$month_label = date('F Y', strtotime($first_day_of_month));

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
// 2. Fetch & Auto-Sync Annex 4: Mash Details Records
// -------------------------------------------------------------
$feed_types = ['Layer', 'Starter', 'Grower', 'Cattle Feed'];

// Ensure all 4 feed types exist in monthly_mash_details for this month
foreach ($feed_types as $ft) {
    $stmt_chk = $mysqli->prepare("SELECT id FROM monthly_mash_details WHERE record_month = ? AND feed_type = ?");
    $stmt_chk->bind_param("ss", $first_day_of_month, $ft);
    $stmt_chk->execute();
    $chk_res = $stmt_chk->get_result();
    if ($chk_res->num_rows === 0) {
        $stmt_ins = $mysqli->prepare("INSERT INTO monthly_mash_details (record_month, feed_type, opening_stock_kg, received_kg, consumption_kg, issued_other_farm_kg, balance_stock_kg) VALUES (?, ?, 0.00, 0.00, 0.00, 0.00, 0.00)");
        $stmt_ins->bind_param("ss", $first_day_of_month, $ft);
        $stmt_ins->execute();
        $stmt_ins->close();
    }
    $stmt_chk->close();
}

// Fetch Annex 4 records & auto-calculate consumption from daily feed log
$mash_records = [];
$sql_mash = "SELECT * FROM monthly_mash_details WHERE record_month = ? ORDER BY FIELD(feed_type, 'Layer', 'Starter', 'Grower', 'Cattle Feed')";
$stmt_mash = $mysqli->prepare($sql_mash);
$stmt_mash->bind_param("s", $first_day_of_month);
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

        // Auto-sum consumption from daily feed distribution log for this feed type and month
        $stmt_sum = $mysqli->prepare("SELECT COALESCE(SUM(amount_distributed_kg), 0) AS total_consumed FROM daily_feed_distribution WHERE feed_type = ? AND distribution_date BETWEEN ? AND ?");
        $stmt_sum->bind_param("sss", $ft, $first_day_of_month, $last_day_of_month);
        $stmt_sum->execute();
        $sum_row = $stmt_sum->get_result()->fetch_assoc();
        $auto_consumption = floatval($sum_row['total_consumed'] ?? 0);
        $stmt_sum->close();

        // Calculate balance: (opening + received) - (consumption + issued_other)
        $opening = floatval($m['opening_stock_kg']);
        $received = floatval($m['received_kg']);
        $issued_other = floatval($m['issued_other_farm_kg']);
        $auto_balance = ($opening + $received) - ($auto_consumption + $issued_other);

        // Update record in database if consumption or balance changed
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
            <i class="bi bi-basket-fill me-2" style="color: var(--color-c1);"></i>Daily Feed Distribution & Annex 4: Mash Details
        </h3>
        <p class="text-muted mb-0 small">Manage daily feed distribution to cages and review monthly Mash inventory summaries.</p>
    </div>
    <div class="col-md-5 d-flex justify-content-end align-items-center gap-2">
        <label class="fw-bold mb-0 text-nowrap"><i class="bi bi-calendar3 me-1"></i>Select Month:</label>
        <input type="month" id="filter_month" class="form-control form-control-sm w-auto shadow-sm" value="<?= $selected_month ?>">
        <button type="button" id="btn_apply_filter" class="btn btn-sm btn-apply-filter px-3 fw-bold">
            <i class="bi bi-funnel me-1"></i>Filter
        </button>
    </div>
</div>

<!-- Alert messages -->
<?php if (isset($_GET['status']) && isset($_GET['msg'])): ?>
    <div class="alert alert-<?= ($_GET['status'] === 'success') ? 'success' : 'danger' ?> alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-<?= ($_GET['status'] === 'success') ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2"></i>
        <?= htmlspecialchars($_GET['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Primary Navigation Tabs -->
<ul class="nav nav-tabs border-bottom-0 mb-4" id="feedTabs" role="tablist" style="gap: 5px;">
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold <?= ($active_tab === 'daily') ? 'active text-light' : 'text-dark bg-white' ?> border-0 py-3 px-4" 
                id="daily-tab" data-bs-toggle="tab" data-bs-target="#daily-pane" type="button" role="tab" 
                style="<?= ($active_tab === 'daily') ? 'background-color: var(--color-c1); border-radius: 8px 8px 0 0;' : 'border-radius: 8px 8px 0 0;' ?>">
            <i class="bi bi-calendar-check me-2"></i>1. Daily Feed Distribution Log
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold <?= ($active_tab === 'annex4') ? 'active text-light' : 'text-dark bg-white' ?> border-0 py-3 px-4" 
                id="annex4-tab" data-bs-toggle="tab" data-bs-target="#annex4-pane" type="button" role="tab"
                style="<?= ($active_tab === 'annex4') ? 'background-color: var(--color-c10); border-radius: 8px 8px 0 0;' : 'border-radius: 8px 8px 0 0;' ?>">
            <i class="bi bi-file-earmark-spreadsheet me-2"></i>2. Annex 4: Mash Details (Monthly Summary)
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
                <div class="card border-0 shadow-sm p-3 bg-white card-kpi-distributed" style="border-radius: 12px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Total Feed Distributed</small>
                            <span class="fs-3 fw-bold text-color-c11"><?= number_format($total_feed_distributed, 2) ?> <small class="fs-6">kg</small></span>
                        </div>
                        <div class="p-3 rounded-circle bg-color-c11-light">
                            <i class="bi bi-basket-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-3 bg-white card-kpi-chicks" style="border-radius: 12px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Total Chicks / Stock Fed</small>
                            <span class="fs-3 fw-bold text-color-c2"><?= number_format($total_chicks_fed) ?></span>
                        </div>
                        <div class="p-3 rounded-circle bg-color-c2-light">
                            <i class="bi bi-people-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-3 bg-white card-kpi-needed" style="border-radius: 12px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Total Feed Needed</small>
                            <span class="fs-3 fw-bold text-color-c8"><?= number_format($total_feed_needed, 2) ?> <small class="fs-6">kg</small></span>
                        </div>
                        <div class="p-3 rounded-circle bg-color-c3-light">
                            <i class="bi bi-calculator-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark m-0"><i class="bi bi-list-check me-2 text-color-c11"></i>Daily Feed Distribution Entries for <?= $month_label ?></h5>
                <button class="btn btn-log-feed fw-bold px-4 text-light" data-bs-toggle="modal" data-bs-target="#addDailyFeedModal">
                    <i class="bi bi-plus-circle me-1"></i>Log Daily Feed
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center" id="dailyFeedTable">
                        <thead class="table-header-dark">
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
                                            <?= ($r['feed_type'] === 'Grower') ? 'badge-feed-grower' : '' ?>
                                            <?= ($r['feed_type'] === 'Cattle Feed') ? 'badge-feed-cattle' : '' ?>">
                                            <?= htmlspecialchars($r['feed_type']) ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($r['cage_name'] ?? 'All Cages / Unspecified') ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['batch_no'] ?? '-') ?></span></td>
                                    <td><?= number_format($r['no_of_chicks']) ?></td>
                                    <td><?= number_format($r['amount_needed_kg'], 2) ?></td>
                                    <td class="fw-bold text-color-c11"><?= number_format($r['amount_distributed_kg'], 2) ?></td>
                                    <td class="small"><?= htmlspecialchars($r['remarks'] ?? '-') ?></td>
                                    <td class="text-nowrap">
                                        <button class="btn btn-sm btn-edit-action me-1 btn-edit-feed"
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
                                        <a href="processors/daily_feed_distribution_crud.php?action=delete&id=<?= $r['id'] ?>" class="btn btn-sm btn-delete-action btn-delete">
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
                <div class="card border-0 shadow-sm p-3 bg-white card-kpi-opening" style="border-radius: 12px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Total Opening Stock</small>
                            <span class="fs-4 fw-bold text-color-c10"><?= number_format($total_opening_stock, 2) ?> kg</span>
                        </div>
                        <div class="p-3 bg-color-c10-light rounded-circle"><i class="bi bi-box-seam-fill fs-4"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 bg-white card-kpi-received" style="border-radius: 12px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Total Received</small>
                            <span class="fs-4 fw-bold text-color-c10"><?= number_format($total_received_stock, 2) ?> kg</span>
                        </div>
                        <div class="p-3 bg-color-c5-light rounded-circle"><i class="bi bi-arrow-down-left-circle-fill fs-4"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 bg-white card-kpi-consumption" style="border-radius: 12px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Total Monthly Consumption</small>
                            <span class="fs-4 fw-bold text-color-c6"><?= number_format($total_consumption, 2) ?> kg</span>
                        </div>
                        <div class="p-3 bg-color-c6-light rounded-circle"><i class="bi bi-fire fs-4"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 bg-white card-kpi-balance" style="border-radius: 12px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Total Balance Stock</small>
                            <span class="fs-4 fw-bold text-color-c8"><?= number_format($total_balance_stock, 2) ?> kg</span>
                        </div>
                        <div class="p-3 rounded-circle bg-color-c3-light"><i class="bi bi-pie-chart-fill fs-4"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold text-dark m-0"><i class="bi bi-file-earmark-spreadsheet me-2 text-color-c10"></i>Annex 4: Mash Details Monthly Inventory Register (<?= $month_label ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center" id="mashTable">
                        <thead class="table-header-dark">
                            <tr>
                                <th class="py-3">Feed Type</th>
                                <th class="py-3">Opening Stock (kg)</th>
                                <th class="py-3">Received (kg)</th>
                                <th class="py-3 table-header-consumption">Consumption (Auto-Calculated kg)</th>
                                <th class="py-3">Issued to Other Farm (kg)</th>
                                <th class="py-3 table-header-balance">Balance Stock (Auto-Calculated kg)</th>
                                <th class="py-3">Remarks</th>
                                <th class="py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mash_records as $m): ?>
                                <tr>
                                    <td class="fw-bold text-start fs-6">
                                        <i class="bi bi-arrow-right-short text-color-c10 me-1"></i><?= htmlspecialchars($m['feed_type']) ?>
                                    </td>
                                    <td class="fw-bold"><?= number_format($m['opening_stock_kg'], 2) ?></td>
                                    <td class="fw-bold text-color-c10"><?= number_format($m['received_kg'], 2) ?></td>
                                    <td class="fw-bold text-color-c6 bg-color-c6-light"><?= number_format($m['consumption_kg'], 2) ?></td>
                                    <td class="fw-bold text-color-c8"><?= number_format($m['issued_other_farm_kg'], 2) ?></td>
                                    <td class="fw-bold text-color-c10 bg-color-c10-light"><?= number_format($m['balance_stock_kg'], 2) ?></td>
                                    <td class="small"><?= htmlspecialchars($m['remarks'] ?? '-') ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-edit-action btn-edit-mash fw-bold px-3"
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
                        <tfoot class="tfoot-summary fw-bold">
                            <tr>
                                <td class="text-start">TOTAL SUMMARY</td>
                                <td><?= number_format($total_opening_stock, 2) ?> kg</td>
                                <td><?= number_format($total_received_stock, 2) ?> kg</td>
                                <td class="text-color-c6"><?= number_format($total_consumption, 2) ?> kg</td>
                                <td><?= number_format($total_issued_other, 2) ?> kg</td>
                                <td class="text-color-c10"><?= number_format($total_balance_stock, 2) ?> kg</td>
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

<?php require_once '../../../includes/footer.php'; ?>
