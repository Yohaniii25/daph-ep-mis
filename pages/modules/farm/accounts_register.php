<?php
// pages/modules/farm/accounts_register.php -> Farm Accounts & Sub-Modules Financial Integration Register
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['role'])) {
    header("Location: ../../../index.php");
    exit();
}

// -------------------------------------------------------------
// Filter Date / Month Parameters
// -------------------------------------------------------------
$filter_period = $_GET['period'] ?? 'all'; // 'all', 'daily', 'monthly'
$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_month = $_GET['month'] ?? date('Y-m');

function getSqlDateFilter($column, $period, $date, $month) {
    if ($period === 'daily' && !empty($date)) {
        return " AND DATE($column) = '" . $date . "'";
    } elseif ($period === 'monthly' && !empty($month)) {
        return " AND $column LIKE '" . $month . "%'";
    }
    return "";
}

$all_transactions = [];

// -------------------------------------------------------------
// 1. Direct Farm Accounts Ledger (farm_accounts)
// -------------------------------------------------------------
$direct_income = 0.00;
$direct_expense = 0.00;
$direct_count = 0;

$where_fa = getSqlDateFilter('transaction_date', $filter_period, $filter_date, $filter_month);
$res_fa = $mysqli->query("SELECT * FROM farm_accounts WHERE 1=1 $where_fa ORDER BY transaction_date DESC, id DESC");
if ($res_fa) {
    while ($r = $res_fa->fetch_assoc()) {
        $amt = floatval($r['amount']);
        $type = ($r['transaction_type'] === 'Expense') ? 'Expense' : 'Income';
        if ($type === 'Income') {
            $direct_income += $amt;
        } else {
            $direct_expense += $amt;
        }
        $direct_count++;

        $all_transactions[] = [
            'id' => 'fa_' . $r['id'],
            'raw_id' => $r['id'],
            'source_key' => 'direct',
            'source_label' => 'Direct Ledger',
            'source_badge' => 'bg-secondary',
            'source_icon' => 'bi-wallet2',
            'date' => $r['transaction_date'],
            'voucher_no' => $r['voucher_no'],
            'category' => $r['account_category'],
            'type' => $type,
            'description' => $r['description'],
            'cash_book_ref' => $r['cash_book_ref'] ?: '-',
            'amount' => $amt,
            'is_direct' => true,
            'link' => null
        ];
    }
}

// -------------------------------------------------------------
// 2. Animal Disposals & Livestock Sales (animal_disposal_register)
// -------------------------------------------------------------
$livestock_income = 0.00;
$livestock_count = 0;

$where_ad = getSqlDateFilter('disposal_date', $filter_period, $filter_date, $filter_month);
$res_ad = $mysqli->query("SELECT * FROM animal_disposal_register WHERE amount_realized > 0 $where_ad ORDER BY disposal_date DESC, id DESC");
if ($res_ad) {
    while ($r = $res_ad->fetch_assoc()) {
        $amt = floatval($r['amount_realized']);
        $livestock_income += $amt;
        $livestock_count++;
        $animals_total = intval($r['total_animals']);
        $species = !empty($r['species']) ? $r['species'] : 'Livestock';
        
        $desc = "Animal Disposal (" . htmlspecialchars($r['how_disposed_of']) . ") - " . $animals_total . " Head (" . $species . ")";
        if (!empty($r['remarks'])) {
            $desc .= ". " . htmlspecialchars($r['remarks']);
        }

        $all_transactions[] = [
            'id' => 'ad_' . $r['id'],
            'raw_id' => $r['id'],
            'source_key' => 'livestock',
            'source_label' => 'Livestock Sales',
            'source_badge' => 'bg-primary',
            'source_icon' => 'bi-activity',
            'date' => $r['disposal_date'],
            'voucher_no' => $r['voucher_no'] ?: 'LIV-' . $r['id'],
            'category' => 'Livestock Sales (' . $species . ')',
            'type' => 'Income',
            'description' => $desc,
            'cash_book_ref' => $r['cash_receipt_info'] ?: '-',
            'amount' => $amt,
            'is_direct' => false,
            'link' => 'white_cattle_register.php'
        ];
    }
}

// -------------------------------------------------------------
// 3. Daily Egg Sales (daily_egg_sales)
// -------------------------------------------------------------
$egg_sales_income = 0.00;
$egg_sales_count = 0;

$where_es = getSqlDateFilter('es.sale_date', $filter_period, $filter_date, $filter_month);
$res_es = $mysqli->query("SELECT es.*, c.cage_name FROM daily_egg_sales es LEFT JOIN cages c ON es.cage_id = c.id WHERE es.grand_total_sales > 0 $where_es ORDER BY es.sale_date DESC, es.id DESC");
if ($res_es) {
    while ($r = $res_es->fetch_assoc()) {
        $amt = floatval($r['grand_total_sales']);
        $egg_sales_income += $amt;
        $egg_sales_count++;

        $desc = "Cage: " . htmlspecialchars($r['cage_name'] ?? 'N/A');
        $details = [];
        if (floatval($r['table_eggs_no'] ?? 0) > 0) {
            $details[] = "Table Eggs: " . number_format($r['table_eggs_no']) . " @ LKR " . number_format($r['table_eggs_unit_price'] ?? 0, 2);
        }
        if (floatval($r['cracked_eggs_no'] ?? 0) > 0) {
            $details[] = "Cracked Eggs: " . number_format($r['cracked_eggs_no']) . " @ LKR " . number_format($r['cracked_eggs_unit_price'] ?? 0, 2);
        }
        if (!empty($details)) {
            $desc .= " (" . implode(", ", $details) . ")";
        }
        if (!empty($r['remarks'])) {
            $desc .= ". " . htmlspecialchars($r['remarks']);
        }

        $all_transactions[] = [
            'id' => 'es_' . $r['id'],
            'raw_id' => $r['id'],
            'source_key' => 'egg_sales',
            'source_label' => 'Egg Sales',
            'source_badge' => 'bg-info text-dark',
            'source_icon' => 'bi-egg-fill',
            'date' => $r['sale_date'],
            'voucher_no' => 'EGG-' . $r['id'],
            'category' => 'Egg Sales Revenue',
            'type' => 'Income',
            'description' => $desc,
            'cash_book_ref' => '-',
            'amount' => $amt,
            'is_direct' => false,
            'link' => 'sales_of_eggs.php'
        ];
    }
}

// -------------------------------------------------------------
// 4. Hatchery Day-Old Chicks Sales (hatchery_sales)
// -------------------------------------------------------------
$hatchery_income = 0.00;
$hatchery_count = 0;

$where_hs = getSqlDateFilter('sales_date', $filter_period, $filter_date, $filter_month);
$res_hs = $mysqli->query("SELECT * FROM hatchery_sales WHERE total_revenue > 0 $where_hs ORDER BY sales_date DESC, id DESC");
if ($res_hs) {
    while ($r = $res_hs->fetch_assoc()) {
        $amt = floatval($r['total_revenue']);
        $hatchery_income += $amt;
        $hatchery_count++;

        $chick_type = !empty($r['egg_category']) ? htmlspecialchars($r['egg_category']) : 'Day-Old Chicks';
        $desc = "Hatchery Sales (" . $chick_type . " - Qty: " . number_format($r['quantity_sold']) . " @ LKR " . number_format($r['actual_rate'] ?? 0, 2) . ")";

        $all_transactions[] = [
            'id' => 'hs_' . $r['id'],
            'raw_id' => $r['id'],
            'source_key' => 'hatchery',
            'source_label' => 'Hatchery Sales',
            'source_badge' => 'bg-warning text-dark',
            'source_icon' => 'bi-sun-fill',
            'date' => $r['sales_date'],
            'voucher_no' => 'HAT-' . $r['id'],
            'category' => 'Hatchery Chick Sales',
            'type' => 'Income',
            'description' => $desc,
            'cash_book_ref' => '-',
            'amount' => $amt,
            'is_direct' => false,
            'link' => 'hatchery_register.php'
        ];
    }
}

// -------------------------------------------------------------
// 5. Day-Old Chicks Distribution (day_old_chicks_distribution)
// -------------------------------------------------------------
$day_old_chick_income = 0.00;
$day_old_chick_count = 0;

$where_doc = getSqlDateFilter('record_date', $filter_period, $filter_date, $filter_month);
$res_doc = $mysqli->query("SELECT * FROM day_old_chicks_distribution WHERE total_amount > 0 $where_doc ORDER BY record_date DESC, id DESC");
if ($res_doc) {
    while ($r = $res_doc->fetch_assoc()) {
        $amt = floatval($r['total_amount']);
        $day_old_chick_income += $amt;
        $day_old_chick_count++;

        $place = !empty($r['sent_to_place']) ? htmlspecialchars($r['sent_to_place']) : 'N/A';
        $chicks_sent = intval($r['no_of_chicks_sent']);
        $price = floatval($r['price_per_chick']);

        $desc = "Day-Old Chick Sales to " . $place . " (Qty: " . number_format($chicks_sent) . " @ LKR " . number_format($price, 2) . ")";

        $all_transactions[] = [
            'id' => 'doc_' . $r['id'],
            'raw_id' => $r['id'],
            'source_key' => 'hatchery',
            'source_label' => 'Day-Old Chick Sales',
            'source_badge' => 'bg-warning text-dark',
            'source_icon' => 'bi-sun-fill',
            'date' => $r['record_date'],
            'voucher_no' => 'DOC-' . $r['id'],
            'category' => 'Day-Old Chick Sales Revenue',
            'type' => 'Income',
            'description' => $desc,
            'cash_book_ref' => 'DOC-' . $r['id'],
            'amount' => $amt,
            'is_direct' => false,
            'link' => 'hatchery_register.php'
        ];
    }
}

// -------------------------------------------------------------
// 6. Month-Old Chicks Distribution (month_old_chicks_distribution)
// -------------------------------------------------------------
$chick_dist_income = 0.00;
$chick_dist_count = 0;

$where_mc = getSqlDateFilter('record_date', $filter_period, $filter_date, $filter_month);
$res_mc = $mysqli->query("SELECT * FROM month_old_chicks_distribution WHERE total_amount > 0 $where_mc ORDER BY record_date DESC, id DESC");
if ($res_mc) {
    while ($r = $res_mc->fetch_assoc()) {
        $amt = floatval($r['total_amount']);
        $chick_dist_income += $amt;
        $chick_dist_count++;

        $place = !empty($r['sent_to_place']) ? htmlspecialchars($r['sent_to_place']) : 'N/A';
        $desc = "Sent to: " . $place . " (Month-Old Chicks - Qty: " . number_format($r['no_of_chicks_sent'] ?? 0) . " @ LKR " . number_format($r['price_per_chick'] ?? 0, 2) . ")";

        $all_transactions[] = [
            'id' => 'mc_' . $r['id'],
            'raw_id' => $r['id'],
            'source_key' => 'chick_dist',
            'source_label' => 'Chick Distribution',
            'source_badge' => 'bg-dark text-light',
            'source_icon' => 'bi-box-seam',
            'date' => $r['record_date'],
            'voucher_no' => 'CHK-' . $r['id'],
            'category' => 'Month-Old Chick Sales',
            'type' => 'Income',
            'description' => $desc,
            'cash_book_ref' => '-',
            'amount' => $amt,
            'is_direct' => false,
            'link' => 'chick_details.php'
        ];
    }
}

// -------------------------------------------------------------
// 7. Farm Produce Sales Annex 6 (farm_produce_register_annex6)
// -------------------------------------------------------------
$produce_income = 0.00;
$produce_count = 0;

$where_pr = getSqlDateFilter('pr.record_date', $filter_period, $filter_date, $filter_month);
$res_pr = $mysqli->query("SELECT pr.*, c.commodity_name FROM farm_produce_register_annex6 pr LEFT JOIN farm_commodities c ON pr.commodity_id = c.id WHERE pr.full_sum_realized > 0 $where_pr ORDER BY pr.record_date DESC, pr.id DESC");
if ($res_pr) {
    while ($r = $res_pr->fetch_assoc()) {
        $amt = floatval($r['full_sum_realized']);
        $produce_income += $amt;
        $produce_count++;

        $com_name = !empty($r['commodity_name']) ? htmlspecialchars($r['commodity_name']) : 'Farm Produce';
        $issued_to = !empty($r['issued_to']) ? htmlspecialchars($r['issued_to']) : 'N/A';
        $method = !empty($r['disposal_method']) ? htmlspecialchars($r['disposal_method']) : '-';
        
        $desc = "Issued to: " . $issued_to . " (Qty: " . floatval($r['issued_qty']) . " @ LKR " . number_format($r['unit_price'], 2) . ", Method: " . $method . ")";
        if (!empty($r['remarks'])) {
            $desc .= ". " . htmlspecialchars($r['remarks']);
        }

        $all_transactions[] = [
            'id' => 'pr_' . $r['id'],
            'raw_id' => $r['id'],
            'source_key' => 'produce',
            'source_label' => 'Produce Sales',
            'source_badge' => 'bg-success',
            'source_icon' => 'bi-basket-fill',
            'date' => $r['record_date'],
            'voucher_no' => $r['receipt_no_or_page'] ?: 'PRD-' . $r['id'],
            'category' => 'Produce Sales (' . $com_name . ')',
            'type' => 'Income',
            'description' => $desc,
            'cash_book_ref' => $r['receipt_no_or_page'] ?: '-',
            'amount' => $amt,
            'is_direct' => false,
            'link' => 'production_details.php'
        ];
    }
}

// -------------------------------------------------------------
// 8. Vehicle Repair & Maintenance Expenses (vehicle_repairs)
// -------------------------------------------------------------
$vehicle_expense = 0.00;
$vehicle_count = 0;

$where_vr = getSqlDateFilter('vr.repair_date', $filter_period, $filter_date, $filter_month);
$res_vr = $mysqli->query("SELECT vr.*, v.vehicle_number, v.vehicle_type FROM vehicle_repairs vr LEFT JOIN registered_vehicles v ON vr.vehicle_id = v.id WHERE vr.amount > 0 $where_vr ORDER BY vr.repair_date DESC, vr.id DESC");
if ($res_vr) {
    while ($r = $res_vr->fetch_assoc()) {
        $amt = floatval($r['amount'] ?? 0);
        if ($amt > 0) {
            $vehicle_expense += $amt;
            $vehicle_count++;

            $v_num = !empty($r['vehicle_number']) ? htmlspecialchars($r['vehicle_number']) : 'Vehicle';
            $v_type = !empty($r['vehicle_type']) ? htmlspecialchars($r['vehicle_type']) : 'Asset';
            $repair_nature = !empty($r['repair_description']) ? htmlspecialchars($r['repair_description']) : (!empty($r['repair_done']) ? htmlspecialchars($r['repair_done']) : 'Maintenance');
            $garage = !empty($r['place_of_repair']) ? htmlspecialchars($r['place_of_repair']) : 'Garage';

            $desc = "Vehicle: " . $v_num . " (" . $v_type . ") - Repair: " . $repair_nature . " @ " . $garage;

            $all_transactions[] = [
                'id' => 'vr_' . $r['id'],
                'raw_id' => $r['id'],
                'source_key' => 'vehicles',
                'source_label' => 'Vehicle Repair',
                'source_badge' => 'bg-danger',
                'source_icon' => 'bi-truck',
                'date' => $r['repair_date'],
                'voucher_no' => !empty($r['invoice_ref']) ? $r['invoice_ref'] : 'VR-' . $r['id'],
                'category' => 'Vehicle Repairs & Maintenance',
                'type' => 'Expense',
                'description' => $desc,
                'cash_book_ref' => !empty($r['invoice_ref']) ? $r['invoice_ref'] : '-',
                'amount' => $amt,
                'is_direct' => false,
                'link' => 'vehicles.php'
            ];
        }
    }
}

// Sort all consolidated transactions by date DESC, then id DESC
usort($all_transactions, function ($a, $b) {
    $t1 = strtotime($a['date']);
    $t2 = strtotime($b['date']);
    if ($t1 === $t2) {
        return strcmp($b['id'], $a['id']);
    }
    return $t2 <=> $t1;
});

// Consolidated Financial Totals
$submodule_income = $livestock_income + $egg_sales_income + $hatchery_income + $day_old_chick_income + $chick_dist_income + $produce_income;
$total_income = $direct_income + $submodule_income;
$total_expense = $direct_expense + $vehicle_expense;
$net_surplus = $total_income - $total_expense;

$status = $_GET['status'] ?? '';
$msg = $_GET['msg'] ?? '';

// Period Filter Display Label
$period_label = "All Time Revenue & Ledger";
if ($filter_period === 'daily') {
    $period_label = "Daily Filter: " . date('d F Y', strtotime($filter_date));
} elseif ($filter_period === 'monthly') {
    $period_label = "Monthly Filter: " . date('F Y', strtotime($filter_month . '-01'));
}
?>

<link rel="stylesheet" href="../../../assets/css/farm.css">

<!-- Header Section -->
<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h3 class="fw-bold text-dark m-0">
            <i class="bi bi-wallet2 me-2" style="color: #820100;"></i>Farm Accounts &amp; Financial Integration Register
        </h3>
        <p class="text-muted mb-0 small">Consolidated cash book ledger integrating direct accounts, animal sales, egg sales, hatchery, chick distribution, produce sales, and vehicle repairs.</p>
    </div>
    <div class="col-md-5 text-end d-flex justify-content-end align-items-center gap-2">
        <span class="badge bg-white text-dark border p-2 shadow-sm fw-bold">
            <i class="bi bi-calendar-event me-1 text-danger"></i><?= htmlspecialchars($period_label) ?>
        </span>
        <button class="btn btn-log-feed fw-bold px-3 text-light shadow-sm" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addAccountModal">
            <i class="bi bi-plus-lg me-1"></i>New Financial Voucher
        </button>
    </div>
</div>

<!-- Notification Status Alert -->
<?php if (!empty($status) && !empty($msg)): ?>
    <div class="alert alert-<?= ($status === 'success') ? 'success' : 'danger' ?> alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-<?= ($status === 'success') ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2"></i>
        <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- DATE & MONTH FILTER BAR -->
<form method="GET" action="accounts_register.php" class="card border-0 shadow-sm mb-4 p-3 bg-white" style="border-radius: 12px;">
    <div class="row align-items-center g-3">
        <div class="col-md-3">
            <label class="form-label fw-bold small text-muted mb-1"><i class="bi bi-calendar-range me-1"></i>Filter Period Mode</label>
            <select name="period" id="filter_period_select" class="form-select shadow-sm" onchange="togglePeriodInputs()">
                <option value="all" <?= ($filter_period === 'all') ? 'selected' : '' ?>>All Time Revenue &amp; Ledger</option>
                <option value="daily" <?= ($filter_period === 'daily') ? 'selected' : '' ?>>Daily Filter (Specific Date)</option>
                <option value="monthly" <?= ($filter_period === 'monthly') ? 'selected' : '' ?>>Monthly Filter (Specific Month)</option>
            </select>
        </div>
        
        <div class="col-md-3" id="daily_input_group" style="<?= ($filter_period === 'daily') ? '' : 'display: none;' ?>">
            <label class="form-label fw-bold small text-muted mb-1"><i class="bi bi-calendar-day me-1"></i>Select Date</label>
            <input type="date" name="date" class="form-control shadow-sm" value="<?= htmlspecialchars($filter_date) ?>">
        </div>

        <div class="col-md-3" id="monthly_input_group" style="<?= ($filter_period === 'monthly') ? '' : 'display: none;' ?>">
            <label class="form-label fw-bold small text-muted mb-1"><i class="bi bi-calendar-month me-1"></i>Select Month</label>
            <input type="month" name="month" class="form-control shadow-sm" value="<?= htmlspecialchars($filter_month) ?>">
        </div>

        <div class="col-md-3 d-flex align-items-end gap-2">
            <button type="submit" class="btn text-light fw-bold px-4 shadow-sm w-100" style="background-color: #820100;">
                <i class="bi bi-funnel-fill me-1"></i>Apply Filter
            </button>
            <?php if ($filter_period !== 'all'): ?>
                <a href="accounts_register.php" class="btn btn-outline-secondary px-3 shadow-sm" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
</form>

<!-- CONSOLIDATED KPI SUMMARY CARDS -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white card-kpi-opening" style="border-radius: 12px;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold uppercase d-block">Total Realized Revenue</small>
                    <span class="fs-4 fw-bold text-success">LKR <?= number_format($total_income, 2) ?></span>
                    <small class="text-muted d-block mt-1">Direct: LKR <?= number_format($direct_income, 2) ?></small>
                </div>
                <div class="p-3 rounded-circle bg-success-subtle text-success">
                    <i class="bi bi-arrow-down-left-circle-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white card-kpi-consumption" style="border-radius: 12px;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold uppercase d-block">Total Operational Expenses</small>
                    <span class="fs-4 fw-bold text-danger">LKR <?= number_format($total_expense, 2) ?></span>
                    <small class="text-muted d-block mt-1">Direct: LKR <?= number_format($direct_expense, 2) ?></small>
                </div>
                <div class="p-3 rounded-circle bg-danger-subtle text-danger">
                    <i class="bi bi-arrow-up-right-circle-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white card-kpi-needed" style="border-radius: 12px;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold uppercase d-block">Net Financial Position</small>
                    <span class="fs-4 fw-bold <?= ($net_surplus >= 0) ? 'text-primary' : 'text-danger' ?>">
                        LKR <?= number_format($net_surplus, 2) ?>
                    </span>
                    <small class="text-muted d-block mt-1"><?= ($net_surplus >= 0) ? 'Net Operating Surplus' : 'Net Deficit' ?></small>
                </div>
                <div class="p-3 rounded-circle bg-primary-subtle text-primary">
                    <i class="bi bi-bank fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white card-kpi-opening" style="border-radius: 12px; border-left: 4px solid #820100;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold uppercase d-block">Sub-Modules Total Revenue</small>
                    <span class="fs-4 fw-bold" style="color: #820100;">LKR <?= number_format($submodule_income, 2) ?></span>
                    <small class="text-muted d-block mt-1">Aggregated from Sub-Modules</small>
                </div>
                <div class="p-3 rounded-circle text-light" style="background-color: #820100;">
                    <i class="bi bi-diagram-3-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- AUTOMATED SUB-MODULE FINANCIAL SUMMARY MATRIX -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="fw-bold text-dark m-0">
                <i class="bi bi-diagram-3-fill me-2" style="color: #820100;"></i>Automated Farm Sub-Modules Accounts Summary
            </h5>
            <small class="text-muted">Real-time accounts summary automatically generated from farm sub-modules with zero manual calculation required.</small>
        </div>
        <span class="badge bg-success-subtle text-success border px-3 py-2 fw-bold">
            <i class="bi bi-check-circle-fill me-1"></i>Live Automated Data Integration
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center mb-0" style="width:100%">
                <thead class="table-dark" style="background-color: #820100;">
                    <tr>
                        <th class="text-start ps-3" style="width: 22%;">Farm Sub-Module</th>
                        <th style="width: 14%;">Account Class</th>
                        <th style="width: 12%;">Log Count</th>
                        <th style="width: 15%;">Realized Income (LKR)</th>
                        <th style="width: 15%;">Operational Expenses (LKR)</th>
                        <th style="width: 14%;">Net Surplus / (Deficit)</th>
                        <th style="width: 8%;">Module Link</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Direct Ledger -->
                    <tr>
                        <td class="text-start ps-3 fw-bold">
                            <i class="bi bi-wallet2 text-secondary me-2"></i>Direct Cash Book Ledger
                        </td>
                        <td><span class="badge bg-secondary">Manual Voucher</span></td>
                        <td class="fw-bold"><?= number_format($direct_count) ?> Records</td>
                        <td class="text-success fw-bold">LKR <?= number_format($direct_income, 2) ?></td>
                        <td class="text-danger fw-bold">LKR <?= number_format($direct_expense, 2) ?></td>
                        <td class="fw-bold <?= ($direct_income - $direct_expense >= 0) ? 'text-primary' : 'text-danger' ?>">
                            LKR <?= number_format($direct_income - $direct_expense, 2) ?>
                        </td>
                        <td><span class="badge bg-light text-muted border">Main</span></td>
                    </tr>
                    <!-- Livestock Disposals -->
                    <tr>
                        <td class="text-start ps-3 fw-bold">
                            <i class="bi bi-activity text-primary me-2"></i>Livestock Animal Sales
                        </td>
                        <td><span class="badge bg-primary">Sales Revenue</span></td>
                        <td class="fw-bold"><?= number_format($livestock_count) ?> Sales Logs</td>
                        <td class="text-success fw-bold">LKR <?= number_format($livestock_income, 2) ?></td>
                        <td class="text-muted">-</td>
                        <td class="text-success fw-bold">+ LKR <?= number_format($livestock_income, 2) ?></td>
                        <td>
                            <a href="white_cattle_register.php" class="btn btn-sm btn-outline-primary" title="Open Livestock Sub-Module">
                                <i class="bi bi-arrow-right-circle"></i>
                            </a>
                        </td>
                    </tr>
                    <!-- Egg Sales -->
                    <tr>
                        <td class="text-start ps-3 fw-bold">
                            <i class="bi bi-egg-fill text-info me-2"></i>Daily Egg Sales Revenue
                        </td>
                        <td><span class="badge bg-info text-dark">Poultry Revenue</span></td>
                        <td class="fw-bold"><?= number_format($egg_sales_count) ?> Sales Logs</td>
                        <td class="text-success fw-bold">LKR <?= number_format($egg_sales_income, 2) ?></td>
                        <td class="text-muted">-</td>
                        <td class="text-success fw-bold">+ LKR <?= number_format($egg_sales_income, 2) ?></td>
                        <td>
                            <a href="sales_of_eggs.php" class="btn btn-sm btn-outline-primary" title="Open Egg Sales Sub-Module">
                                <i class="bi bi-arrow-right-circle"></i>
                            </a>
                        </td>
                    </tr>
                    <!-- Hatchery & Day-Old Chick Sales -->
                    <tr>
                        <td class="text-start ps-3 fw-bold">
                            <i class="bi bi-sun-fill text-warning me-2"></i>Hatchery Sub-Module (Day-Old Chicks)
                            <small class="text-muted d-block ms-4 font-normal">Hatchery Day-Old Chicks Sales &amp; Distribution</small>
                        </td>
                        <td><span class="badge bg-warning text-dark">Hatchery Revenue</span></td>
                        <td class="fw-bold"><?= number_format($hatchery_count + $day_old_chick_count) ?> Sales Logs</td>
                        <td class="text-success fw-bold">LKR <?= number_format($hatchery_income + $day_old_chick_income, 2) ?></td>
                        <td class="text-muted">-</td>
                        <td class="text-success fw-bold">+ LKR <?= number_format($hatchery_income + $day_old_chick_income, 2) ?></td>
                        <td>
                            <a href="hatchery_register.php" class="btn btn-sm btn-outline-primary" title="Open Hatchery Register Sub-Module">
                                <i class="bi bi-arrow-right-circle"></i>
                            </a>
                        </td>
                    </tr>
                    <!-- Month-Old Chicks -->
                    <tr>
                        <td class="text-start ps-3 fw-bold">
                            <i class="bi bi-box-seam text-dark me-2"></i>Month-Old Chick Distribution
                        </td>
                        <td><span class="badge bg-dark">Poultry Distribution</span></td>
                        <td class="fw-bold"><?= number_format($chick_dist_count) ?> Distribution Logs</td>
                        <td class="text-success fw-bold">LKR <?= number_format($chick_dist_income, 2) ?></td>
                        <td class="text-muted">-</td>
                        <td class="text-success fw-bold">+ LKR <?= number_format($chick_dist_income, 2) ?></td>
                        <td>
                            <a href="chick_details.php" class="btn btn-sm btn-outline-primary" title="Open Chick Details Sub-Module">
                                <i class="bi bi-arrow-right-circle"></i>
                            </a>
                        </td>
                    </tr>
                    <!-- Produce Sales Annex 6 -->
                    <tr>
                        <td class="text-start ps-3 fw-bold">
                            <i class="bi bi-basket-fill text-success me-2"></i>Farm Produce Sales (Annex 6)
                        </td>
                        <td><span class="badge bg-success">Commodity Revenue</span></td>
                        <td class="fw-bold"><?= number_format($produce_count) ?> Produce Sales</td>
                        <td class="text-success fw-bold">LKR <?= number_format($produce_income, 2) ?></td>
                        <td class="text-muted">-</td>
                        <td class="text-success fw-bold">+ LKR <?= number_format($produce_income, 2) ?></td>
                        <td>
                            <a href="production_details.php" class="btn btn-sm btn-outline-primary" title="Open Produce Sub-Module">
                                <i class="bi bi-arrow-right-circle"></i>
                            </a>
                        </td>
                    </tr>
                    <!-- Vehicle Repairs -->
                    <tr>
                        <td class="text-start ps-3 fw-bold">
                            <i class="bi bi-truck text-danger me-2"></i>Vehicle Repair &amp; Maintenance
                        </td>
                        <td><span class="badge bg-danger">Maintenance Expense</span></td>
                        <td class="fw-bold"><?= number_format($vehicle_count) ?> Repair Logs</td>
                        <td class="text-muted">-</td>
                        <td class="text-danger fw-bold">LKR <?= number_format($vehicle_expense, 2) ?></td>
                        <td class="text-danger fw-bold">- LKR <?= number_format($vehicle_expense, 2) ?></td>
                        <td>
                            <a href="vehicles.php" class="btn btn-sm btn-outline-primary" title="Open Vehicles Sub-Module">
                                <i class="bi bi-arrow-right-circle"></i>
                            </a>
                        </td>
                    </tr>
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="3" class="text-start ps-3">AUTOMATED TOTALS ACROSS ALL SUB-MODULES</td>
                        <td class="text-success fs-6">LKR <?= number_format($total_income, 2) ?></td>
                        <td class="text-danger fs-6">LKR <?= number_format($total_expense, 2) ?></td>
                        <td class="fs-6 <?= ($net_surplus >= 0) ? 'text-primary' : 'text-danger' ?>">
                            LKR <?= number_format($net_surplus, 2) ?>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- DETAILED CONSOLIDATED LEDGER CARD -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="fw-bold text-dark m-0">
                <i class="bi bi-receipt-cutoff me-2" style="color: #820100;"></i>Consolidated Cash Book Ledger
            </h5>
            <small class="text-muted">Detailed view of all manual and automated transactions across all farm sub-modules.</small>
        </div>
        <!-- Table Filter Controls -->
        <div class="d-flex align-items-center gap-2">
            <select id="filter_source" class="form-select form-select-sm shadow-sm" style="width: 180px;" onchange="filterAccountsTable()">
                <option value="all">All Sources</option>
                <option value="direct">Direct Ledger</option>
                <option value="livestock">Livestock Sales</option>
                <option value="egg_sales">Egg Sales</option>
                <option value="hatchery">Hatchery &amp; Chick Sales</option>
                <option value="chick_dist">Chick Distribution</option>
                <option value="produce">Produce Sales</option>
                <option value="vehicles">Vehicle Repair</option>
            </select>
            <select id="filter_type" class="form-select form-select-sm shadow-sm" style="width: 140px;" onchange="filterAccountsTable()">
                <option value="all">All Types</option>
                <option value="Income">Income Only</option>
                <option value="Expense">Expense Only</option>
            </select>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="accountsRegisterTable" class="table table-hover align-middle text-center mb-0" style="width:100%">
                <thead class="table-dark" style="background-color: #820100;">
                    <tr>
                        <th style="width: 10%;">Date</th>
                        <th style="width: 12%;">Source</th>
                        <th style="width: 12%;">Voucher / Ref</th>
                        <th style="width: 15%;">Category</th>
                        <th style="width: 25%;" class="text-start">Description / Particulars</th>
                        <th style="width: 8%;">Folio Ref</th>
                        <th style="width: 10%;">Amount (LKR)</th>
                        <th style="width: 8%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($all_transactions)): ?>
                        <tr>
                            <td colspan="8" class="text-muted py-4">No transactions found for the selected period.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($all_transactions as $t): ?>
                            <tr class="account-row" data-source="<?= htmlspecialchars($t['source_key']) ?>" data-type="<?= htmlspecialchars($t['type']) ?>">
                                <td class="fw-bold text-nowrap"><?= date('Y-m-d', strtotime($t['date'])) ?></td>
                                <td>
                                    <span class="badge <?= htmlspecialchars($t['source_badge']) ?> d-inline-flex align-items-center gap-1">
                                        <i class="bi <?= htmlspecialchars($t['source_icon']) ?>"></i>
                                        <?= htmlspecialchars($t['source_label']) ?>
                                    </span>
                                </td>
                                <td><span class="badge bg-light text-dark border px-2"><?= htmlspecialchars($t['voucher_no']) ?></span></td>
                                <td class="fw-medium text-dark"><?= htmlspecialchars($t['category']) ?></td>
                                <td class="text-start small">
                                    <?= htmlspecialchars($t['description']) ?>
                                    <?php if (!empty($t['link'])): ?>
                                        <a href="<?= htmlspecialchars($t['link']) ?>" class="ms-1 text-decoration-none" title="Go to sub-module">
                                            <i class="bi bi-box-arrow-up-right small"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-light text-muted border px-2"><?= htmlspecialchars($t['cash_book_ref']) ?></span></td>
                                <td class="fw-bold <?= ($t['type'] === 'Income') ? 'text-success' : 'text-danger' ?>">
                                    <?= ($t['type'] === 'Income' ? '+' : '-') ?> LKR <?= number_format($t['amount'], 2) ?>
                                </td>
                                <td>
                                    <?php if ($t['is_direct']): ?>
                                        <button class="btn btn-sm btn-outline-primary btn-edit-account me-1"
                                                data-id="<?= htmlspecialchars($t['raw_id']) ?>"
                                                data-date="<?= htmlspecialchars($t['date']) ?>"
                                                data-voucher="<?= htmlspecialchars($t['voucher_no']) ?>"
                                                data-category="<?= htmlspecialchars($t['category']) ?>"
                                                data-type="<?= htmlspecialchars($t['type']) ?>"
                                                data-description="<?= htmlspecialchars($t['description']) ?>"
                                                data-amount="<?= htmlspecialchars($t['amount']) ?>"
                                                data-ref="<?= htmlspecialchars($t['cash_book_ref']) ?>"
                                                title="Edit Voucher">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-delete-account"
                                                data-id="<?= htmlspecialchars($t['raw_id']) ?>"
                                                data-voucher="<?= htmlspecialchars($t['voucher_no']) ?>"
                                                title="Delete Voucher">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small italic">Automated</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>



<!-- MODAL: ADD DIRECT FINANCIAL ENTRY -->
<div class="modal fade" id="addAccountModal" tabindex="-1" aria-labelledby="addAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #820100;">
                <h5 class="modal-title fw-bold" id="addAccountModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Log New Financial Voucher Entry
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/accounts_crud.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Transaction Date <span class="text-danger">*</span></label>
                            <input type="date" name="transaction_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Voucher / Ref No <span class="text-danger">*</span></label>
                            <input type="text" name="voucher_no" class="form-control" placeholder="e.g. VOU-2026-0801" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Transaction Type <span class="text-danger">*</span></label>
                            <select name="transaction_type" class="form-select" required>
                                <option value="Income">Income (+ Realized)</option>
                                <option value="Expense">Expense (- Operational)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Account Category <span class="text-danger">*</span></label>
                            <input type="text" name="account_category" class="form-control" placeholder="e.g. Feed Procurement, Utility, Maintenance" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Amount (LKR) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="0.00" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Cash Book Reference / Folio</label>
                            <input type="text" name="cash_book_ref" class="form-control" placeholder="e.g. CR-901 / PV-502">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Description / Particulars <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Details of voucher transaction..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: #820100;">Save Voucher Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: EDIT DIRECT FINANCIAL ENTRY -->
<div class="modal fade" id="editAccountModal" tabindex="-1" aria-labelledby="editAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #820100;">
                <h5 class="modal-title fw-bold" id="editAccountModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit Financial Voucher Entry
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/accounts_crud.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Transaction Date <span class="text-danger">*</span></label>
                            <input type="date" name="transaction_date" id="edit_transaction_date" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Voucher / Ref No <span class="text-danger">*</span></label>
                            <input type="text" name="voucher_no" id="edit_voucher_no" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Transaction Type <span class="text-danger">*</span></label>
                            <select name="transaction_type" id="edit_transaction_type" class="form-select" required>
                                <option value="Income">Income (+ Realized)</option>
                                <option value="Expense">Expense (- Operational)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Account Category <span class="text-danger">*</span></label>
                            <input type="text" name="account_category" id="edit_account_category" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Amount (LKR) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" id="edit_amount" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Cash Book Reference / Folio</label>
                            <input type="text" name="cash_book_ref" id="edit_cash_book_ref" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Description / Particulars <span class="text-danger">*</span></label>
                            <textarea name="description" id="edit_description" class="form-control" rows="2" required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: #820100;">Update Voucher Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function togglePeriodInputs() {
    var mode = document.getElementById('filter_period_select').value;
    var dailyGrp = document.getElementById('daily_input_group');
    var monthlyGrp = document.getElementById('monthly_input_group');

    if (mode === 'daily') {
        dailyGrp.style.display = 'block';
        monthlyGrp.style.display = 'none';
    } else if (mode === 'monthly') {
        dailyGrp.style.display = 'none';
        monthlyGrp.style.display = 'block';
    } else {
        dailyGrp.style.display = 'none';
        monthlyGrp.style.display = 'none';
    }
}

if (typeof $ !== 'undefined' && $.fn && $.fn.dataTable) {
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'accountsRegisterTable') {
                return true;
            }
            var sourceVal = $('#filter_source').val() || 'all';
            var typeVal = $('#filter_type').val() || 'all';

            var row = settings.aoData[dataIndex].nTr;
            if (!row) return true;

            var rowSource = $(row).attr('data-source');
            var rowType = $(row).attr('data-type');

            var matchSource = (sourceVal === 'all' || rowSource === sourceVal);
            var matchType = (typeVal === 'all' || rowType === typeVal);

            return matchSource && matchType;
        }
    );
}

function filterAccountsTable() {
    if (typeof $ !== 'undefined' && $.fn && $.fn.DataTable && $.fn.DataTable.isDataTable('#accountsRegisterTable')) {
        $('#accountsRegisterTable').DataTable().draw();
    } else {
        var sourceVal = document.getElementById('filter_source').value;
        var typeVal = document.getElementById('filter_type').value;
        var rows = document.querySelectorAll('.account-row');

        rows.forEach(function(row) {
            var rowSource = row.getAttribute('data-source');
            var rowType = row.getAttribute('data-type');

            var matchSource = (sourceVal === 'all' || rowSource === sourceVal);
            var matchType = (typeVal === 'all' || rowType === typeVal);

            if (matchSource && matchType) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Populate Edit Modal
    document.querySelectorAll('.btn-edit-account').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.getAttribute('data-id');
            document.getElementById('edit_transaction_date').value = this.getAttribute('data-date');
            document.getElementById('edit_voucher_no').value = this.getAttribute('data-voucher');
            document.getElementById('edit_account_category').value = this.getAttribute('data-category');
            document.getElementById('edit_transaction_type').value = this.getAttribute('data-type');
            document.getElementById('edit_description').value = this.getAttribute('data-description');
            document.getElementById('edit_amount').value = this.getAttribute('data-amount');
            document.getElementById('edit_cash_book_ref').value = this.getAttribute('data-ref');

            var modal = new bootstrap.Modal(document.getElementById('editAccountModal'));
            modal.show();
        });
    });

    // Handle Delete Action with Confirmation
    document.querySelectorAll('.btn-delete-account').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var voucher = this.getAttribute('data-voucher');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete Financial Voucher?',
                    text: 'Are you sure you want to delete voucher ' + voucher + '? This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Delete'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'processors/accounts_crud.php?action=delete&id=' + id;
                    }
                });
            } else {
                if (confirm('Are you sure you want to delete voucher ' + voucher + '?')) {
                    window.location.href = 'processors/accounts_crud.php?action=delete&id=' + id;
                }
            }
        });
    });
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
