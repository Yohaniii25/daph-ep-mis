<?php
// pages/dashboard/training.php -> Training Center Hub & Analytics Dashboard
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_roles = ['training_officer', 'administrator', 'provincial_director', 'district_dd'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied");
}

require_once './includes/header.php';
require_once './includes/sidebar.php';
require_once './config/db_connect.php';

// Resolve Training Centre Data Isolation
$all_centers = [];
$centers_res = $mysqli->query("SELECT id, center_name, location FROM training_centers WHERE is_active = 1 ORDER BY id ASC");
if ($centers_res) {
    while ($row = $centers_res->fetch_assoc()) {
        $all_centers[] = $row;
    }
}

$current_center_id = $_SESSION['training_center_id'] ?? null;
if (empty($current_center_id) && isset($_GET['center_id'])) {
    $current_center_id = intval($_GET['center_id']);
}
if (empty($current_center_id) && !empty($all_centers)) {
    $current_center_id = $all_centers[0]['id'];
}

$current_training_center = null;
foreach ($all_centers as $c) {
    if ($c['id'] == $current_center_id) {
        $current_training_center = $c;
        break;
    }
}

// Date References
$cur_month = intval(date('m'));
$cur_year = intval(date('Y'));
$cur_month_label = date('F Y');

// ==========================================
// 1. TOP KPI METRIC CARDS (CURRENT MONTH)
// ==========================================

// Metric 1: Total Income (Current Month) from training_income_receipts
$month_income = 0.00;
$stmt1 = $mysqli->prepare("SELECT IFNULL(SUM(amount), 0) AS total_income FROM training_income_receipts WHERE training_center_id = ? AND MONTH(receipt_date) = ? AND YEAR(receipt_date) = ?");
if ($stmt1) {
    $stmt1->bind_param("iii", $current_center_id, $cur_month, $cur_year);
    $stmt1->execute();
    $month_income = floatval($stmt1->get_result()->fetch_assoc()['total_income'] ?? 0);
    $stmt1->close();
}

// Metric 2: Total Produce Sold (Rs) (Current Month) from training_produce_register
$month_produce_sold = 0.00;
$stmt2 = $mysqli->prepare("SELECT IFNULL(SUM(full_sum_realized), 0) AS total_produce_sold FROM training_produce_register WHERE training_center_id = ? AND MONTH(record_date) = ? AND YEAR(record_date) = ?");
if ($stmt2) {
    $stmt2->bind_param("iii", $current_center_id, $cur_month, $cur_year);
    $stmt2->execute();
    $month_produce_sold = floatval($stmt2->get_result()->fetch_assoc()['total_produce_sold'] ?? 0);
    $stmt2->close();
}

// Metric 3: Total Advance Programmes (Current Month) from training_advanced_programmes
$month_progs_count = 0;
$stmt3 = $mysqli->prepare("SELECT COUNT(*) AS total_progs FROM training_advanced_programmes WHERE training_center_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
if ($stmt3) {
    $stmt3->bind_param("iii", $current_center_id, $cur_month, $cur_year);
    $stmt3->execute();
    $month_progs_count = intval($stmt3->get_result()->fetch_assoc()['total_progs'] ?? 0);
    $stmt3->close();
}

// Metric 4: Annual Total Combined Income (Year <?= $cur_year 
$annual_total_income = 0.00;
$stmt4 = $mysqli->prepare("SELECT IFNULL(SUM(amount), 0) AS annual_income FROM training_income_receipts WHERE training_center_id = ? AND YEAR(receipt_date) = ?");
if ($stmt4) {
    $stmt4->bind_param("ii", $current_center_id, $cur_year);
    $stmt4->execute();
    $annual_total_income = floatval($stmt4->get_result()->fetch_assoc()['annual_income'] ?? 0);
    $stmt4->close();
}

// ==========================================
// 2. DATA VISUALIZATION: LAST 6 MONTHS TRENDS
// ==========================================
$chart_labels = [];
$chart_keys = [];
$chart_income_data = [];
$chart_produce_data = [];

for ($i = 5; $i >= 0; $i--) {
    $timestamp = strtotime("-$i months");
    $m = date('m', $timestamp);
    $y = date('Y', $timestamp);
    $key = "$y-$m";
    $chart_keys[$key] = [
        'label' => date('M Y', $timestamp),
        'income' => 0.00,
        'produce' => 0.00
    ];
}

// Fetch income aggregated by month for last 6 months
$inc_trend_stmt = $mysqli->prepare("
    SELECT DATE_FORMAT(receipt_date, '%Y-%m') AS ym, IFNULL(SUM(amount), 0) AS sum_amt 
    FROM training_income_receipts 
    WHERE training_center_id = ? AND receipt_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
    GROUP BY ym
");
if ($inc_trend_stmt) {
    $inc_trend_stmt->bind_param("i", $current_center_id);
    $inc_trend_stmt->execute();
    $res = $inc_trend_stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        if (isset($chart_keys[$r['ym']])) {
            $chart_keys[$r['ym']]['income'] = floatval($r['sum_amt']);
        }
    }
    $inc_trend_stmt->close();
}

// Fetch produce sales aggregated by month for last 6 months
$prod_trend_stmt = $mysqli->prepare("
    SELECT DATE_FORMAT(record_date, '%Y-%m') AS ym, IFNULL(SUM(full_sum_realized), 0) AS sum_sales 
    FROM training_produce_register 
    WHERE training_center_id = ? AND record_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
    GROUP BY ym
");
if ($prod_trend_stmt) {
    $prod_trend_stmt->bind_param("i", $current_center_id);
    $prod_trend_stmt->execute();
    $res = $prod_trend_stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        if (isset($chart_keys[$r['ym']])) {
            $chart_keys[$r['ym']]['produce'] = floatval($r['sum_sales']);
        }
    }
    $prod_trend_stmt->close();
}

foreach ($chart_keys as $k => $info) {
    $chart_labels[] = $info['label'];
    $chart_income_data[] = $info['income'];
    $chart_produce_data[] = $info['produce'];
}

// ==========================================
// 3. RECENT ACTIVITY TABLES
// ==========================================

// Recent 5 Income Receipts
$recent_receipts = [];
$r_stmt = $mysqli->prepare("SELECT id, receipt_date, receipt_no, category, amount, payer_name FROM training_income_receipts WHERE training_center_id = ? ORDER BY receipt_date DESC, id DESC LIMIT 5");
if ($r_stmt) {
    $r_stmt->bind_param("i", $current_center_id);
    $r_stmt->execute();
    $res = $r_stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $recent_receipts[] = $row;
    }
    $r_stmt->close();
}

// Fetch all Advance Programmes for the Training Center for the Calendar
$calendar_events = [];
$cal_stmt = $mysqli->prepare("SELECT id, date, task, place, time_duration FROM training_advanced_programmes WHERE training_center_id = ? ORDER BY date ASC");
if ($cal_stmt) {
    $cal_stmt->bind_param("i", $current_center_id);
    $cal_stmt->execute();
    $c_res = $cal_stmt->get_result();
    $colors = ['#370709', '#185dbd', '#198754', '#ef4016', '#b08723', '#6f42c1'];
    $idx = 0;
    while ($c_row = $c_res->fetch_assoc()) {
        $c_color = $colors[$idx % count($colors)];
        $calendar_events[] = [
            'id' => $c_row['id'],
            'title' => $c_row['task'],
            'start' => $c_row['date'],
            'extendedProps' => [
                'place' => $c_row['place'] ?? 'Main Center',
                'duration' => $c_row['time_duration'] ?? 'N/A'
            ],
            'backgroundColor' => $c_color,
            'borderColor' => $c_color,
            'textColor' => '#ffffff'
        ];
        $idx++;
    }
    $cal_stmt->close();
}

// Recent 5 Produce Register Entries
$recent_produce = [];
$prod_recent_stmt = $mysqli->prepare("SELECT id, commodity, record_date, plot_no_crop, quantity, unit, disposal_method, full_sum_realized FROM training_produce_register WHERE training_center_id = ? ORDER BY record_date DESC, id DESC LIMIT 5");
if ($prod_recent_stmt) {
    $prod_recent_stmt->bind_param("i", $current_center_id);
    $prod_recent_stmt->execute();
    $res = $prod_recent_stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $recent_produce[] = $row;
    }
    $prod_recent_stmt->close();
}
?>

<link rel="stylesheet" href="<?= BASE_PATH ?? '/daph-ep-mis/' ?>assets/css/training.css">

<style>
    .metric-card-kpi {
        border-radius: 12px !important;
        background-color: #ffffff !important;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .metric-card-kpi:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
    }
    .chart-container-card {
        border-radius: 12px;
        background-color: #ffffff;
    }
</style>

<div class="container-fluid px-4 py-4 training-dashboard-shell">
    
    <!-- Top Header & Location Info -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2.5 py-1 rounded-2 fw-bold font-monospace">
                    Training Hub
                </span>
                <h2 class="training-page-title mb-0 fw-bold">
                    <?= !empty($current_training_center) ? htmlspecialchars($current_training_center['center_name']) . ' Dashboard' : 'Training Center Dashboard' ?>
                </h2>
            </div>
            <p class="text-muted small mb-0">
                Operational analytics, revenue statistics &amp; advance training activity metrics for 
                <strong class="text-dark"><?= htmlspecialchars($current_training_center['center_name'] ?? 'Training Centre') ?></strong>
                (Location: <?= htmlspecialchars($current_training_center['location'] ?? 'N/A') ?>)
            </p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <!-- Training Centre Selector for Admins / Multi-Center Roles -->
            <?php if (in_array($_SESSION['role'], ['administrator', 'provincial_director', 'district_dd']) && count($all_centers) > 1): ?>
                <form method="GET" action="" class="d-inline-block">
                    <select name="center_id" class="form-select form-select-sm shadow-sm border-secondary fw-semibold" onchange="this.form.submit()">
                        <?php foreach ($all_centers as $tc): ?>
                            <option value="<?= $tc['id'] ?>" <?= $tc['id'] == $current_center_id ? 'selected' : '' ?>>
                                🏢 <?= htmlspecialchars($tc['center_name']) ?> (<?= htmlspecialchars($tc['location']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>

            <?php if (!empty($current_training_center)): ?>
                <div class="training-hero-badge d-inline-flex align-items-center gap-2">
                    <i class="bi bi-geo-alt-fill text-danger"></i>
                    <span>
                        <strong><?= htmlspecialchars($current_training_center['center_name']) ?></strong>
                        - <?= htmlspecialchars($current_training_center['location']) ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 1. TOP KPI / METRIC CARDS (Farm Module Style with Left Borders) -->
    <div class="row g-3 mb-4">
        <!-- KPI 1: Total Income (Current Month) -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm p-3 metric-card-kpi h-100" style="border-left: 5px solid #185dbd !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-bold text-uppercase d-block">Total Income (<?= date('M') ?>)</small>
                        <span class="fs-3 fw-bold text-primary">Rs. <?= number_format($month_income, 2) ?></span>
                        <small class="text-muted d-block mt-1">Receipts for <?= $cur_month_label ?></small>
                    </div>
                    <div class="p-3 rounded-circle bg-primary-subtle text-primary">
                        <i class="bi bi-wallet2 fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 2: Total Produce Sold (Rs) (Current Month) -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm p-3 metric-card-kpi h-100" style="border-left: 5px solid #198754 !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-bold text-uppercase d-block">Produce Sold (<?= date('M') ?>)</small>
                        <span class="fs-3 fw-bold text-success">Rs. <?= number_format($month_produce_sold, 2) ?></span>
                        <small class="text-muted d-block mt-1">Form A.D.30 Perishables</small>
                    </div>
                    <div class="p-3 rounded-circle bg-success-subtle text-success">
                        <i class="bi bi-cart-check-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 3: Total Advance Programmes (Current Month) -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm p-3 metric-card-kpi h-100" style="border-left: 5px solid #ef4016 !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-bold text-uppercase d-block">Advance Programmes</small>
                        <span class="fs-3 fw-bold" style="color: #ef4016;"><?= $month_progs_count ?></span>
                        <small class="text-muted d-block mt-1">Scheduled for <?= date('M Y') ?></small>
                    </div>
                    <div class="p-3 rounded-circle" style="background-color: rgba(239, 64, 22, 0.1) !important; color: #ef4016 !important;">
                        <i class="bi bi-calendar-event-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 4: Annual Total Income (Year <?= $cur_year ?>) -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm p-3 metric-card-kpi h-100" style="border-left: 5px solid #370709 !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-bold text-uppercase d-block">Annual Total Income</small>
                        <span class="fs-3 fw-bold" style="color: #370709;">Rs. <?= number_format($annual_total_income, 2) ?></span>
                        <small class="text-muted d-block mt-1">Year <?= $cur_year ?> Gross Income</small>
                    </div>
                    <div class="p-3 rounded-circle" style="background-color: rgba(55, 7, 9, 0.1) !important; color: #370709 !important;">
                        <i class="bi bi-cash-stack fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Navigation Bar -->
    <div class="card quick-action-card shadow-sm border-0 mb-4 rounded-3">
        <div class="card-header bg-white py-3 border-0">
            <h6 class="mb-0 fw-bold text-muted small text-uppercase">
                <i class="bi bi-lightning-charge-fill me-2 text-warning"></i>Quick Actions &amp; Modules
            </h6>
        </div>
        <div class="card-body pt-0">
            <div class="row g-3">
                <div class="col-md-4 col-sm-6">
                    <a href="<?= BASE_PATH ?>pages/modules/training/monthly_income_summary.php" style="background-color: #370709;" class="btn w-100 py-3 shadow-sm border-0 text-light d-block rounded-3">
                        <i class="bi bi-receipt-cutoff fs-4 mb-1 d-block text-light"></i>
                        <span class="text-light fw-semibold">Monthly Income Summary</span>
                    </a>
                </div>
                <div class="col-md-4 col-sm-6">
                    <a href="<?= BASE_PATH ?>pages/modules/training/advanced_programme.php" style="background-color: #ef4016;" class="btn w-100 py-3 shadow-sm border-0 text-light d-block rounded-3">
                        <i class="bi bi-calendar-week fs-4 mb-1 d-block text-light"></i>
                        <span class="text-light fw-semibold">Advance Programme</span>
                    </a>
                </div>
                <div class="col-md-4 col-sm-6">
                    <a href="<?= BASE_PATH ?>pages/modules/training/produce_register.php" style="background-color: #1e3c72;" class="btn w-100 py-3 shadow-sm border-0 text-light d-block rounded-3">
                        <i class="bi bi-journal-text fs-4 mb-1 d-block text-light"></i>
                        <span class="text-light fw-semibold">Produce Register (Form A.D.30)</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. DATA VISUALIZATION: INCOME TRENDS CHART (LAST 6 MONTHS) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 chart-container-card">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
            <div>
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-bar-chart-line-fill me-2" style="color: #370709;"></i>Revenue &amp; Income Trends (Last 6 Months)
                </h5>
                <small class="text-muted">Dynamic breakdown of Monthly Receipts Income vs Produce Sales Realized</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2.5 py-1">
                    <i class="bi bi-dot"></i> Receipts Income
                </span>
                <span class="badge bg-success bg-opacity-10 text-success border border-success px-2.5 py-1">
                    <i class="bi bi-dot"></i> Produce Sales
                </span>
            </div>
        </div>
        <div class="card-body p-4">
            <div style="position: relative; height: 320px; width: 100%;">
                <canvas id="incomeTrendsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- 3. RECENT ACTIVITY TABLES (QUICK VIEWS - SIDE BY SIDE) -->
    <div class="row g-4 mb-4">
        <!-- COLUMN 1: RECENT INCOME RECEIPTS (5 latest) -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-3 p-2" style="background-color: rgba(55, 7, 9, 0.1); color: #370709;">
                            <i class="bi bi-receipt fs-6"></i>
                        </span>
                        <h6 class="mb-0 fw-bold text-dark">Recent Income Receipts</h6>
                    </div>
                    <a href="<?= BASE_PATH ?>pages/modules/training/monthly_income_summary.php" class="btn btn-sm btn-outline-primary fw-semibold rounded-pill px-3">
                        View All <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light text-secondary">
                                <tr>
                                    <th>Date</th>
                                    <th>Receipt No</th>
                                    <th>Category</th>
                                    <th class="text-end">Amount (Rs.)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_receipts)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox d-block fs-3 mb-1 text-secondary"></i>
                                            No recent receipts recorded.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_receipts as $rc): ?>
                                        <tr>
                                            <td class="font-monospace text-secondary"><?= htmlspecialchars($rc['receipt_date']) ?></td>
                                            <td>
                                                <span class="fw-bold font-monospace text-dark"><?= htmlspecialchars($rc['receipt_no']) ?></span>
                                                <?php if (!empty($rc['payer_name'])): ?>
                                                    <small class="text-muted d-block"><?= htmlspecialchars($rc['payer_name']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $rc['category']))) ?></span>
                                            </td>
                                            <td class="text-end font-monospace fw-bold text-success">
                                                Rs. <?= number_format(floatval($rc['amount']), 2) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLUMN 2: TRAINING CALENDAR & ADVANCE TASK SCHEDULE -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-3 p-2" style="background-color: rgba(239, 64, 22, 0.1); color: #ef4016;">
                            <i class="bi bi-calendar3 fs-6"></i>
                        </span>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark">Training Calendar &amp; Task Schedule</h6>
                        </div>
                    </div>
                    <a href="<?= BASE_PATH ?>pages/modules/training/advanced_programme.php" class="btn btn-sm btn-outline-danger fw-semibold rounded-pill px-3">
                        Manage Tasks <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-3">
                    <div id="trainingTaskCalendar" style="min-height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- RECENT PRODUCE REGISTER ENTRIES (Annex / Form A.D.30 Quick View) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success bg-opacity-10 text-success rounded-3 p-2">
                    <i class="bi bi-box-seam fs-6"></i>
                </span>
                <div>
                    <h6 class="mb-0 fw-bold text-dark">Recent Produce Register Entries (Perishables - Form A.D.30)</h6>
                    <small class="text-muted">Latest harvest receipts and disposal transactions</small>
                </div>
            </div>
            <a href="<?= BASE_PATH ?>pages/modules/training/produce_register.php" class="btn btn-sm btn-outline-success fw-semibold rounded-pill px-3">
                View Ledger <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light text-secondary">
                        <tr>
                            <th>Date</th>
                            <th>Commodity</th>
                            <th>Plot / Crop</th>
                            <th>Quantity</th>
                            <th>Disposal</th>
                            <th class="text-end">Sum Realized</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_produce)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bi bi-basket d-block fs-3 mb-1 text-secondary"></i>
                                    No produce entries logged.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_produce as $rp): ?>
                                <tr>
                                    <td class="font-monospace text-secondary"><?= htmlspecialchars($rp['record_date']) ?></td>
                                    <td><strong class="text-dark"><?= htmlspecialchars($rp['commodity']) ?></strong></td>
                                    <td><?= htmlspecialchars($rp['plot_no_crop'] ?? '-') ?></td>
                                    <td class="font-monospace fw-bold text-dark"><?= number_format(floatval($rp['quantity']), 2) ?> <?= htmlspecialchars($rp['unit']) ?></td>
                                    <td>
                                        <span class="badge <?= strcasecmp($rp['disposal_method'], 'Sold') === 0 ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= htmlspecialchars($rp['disposal_method'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-success">
                                        Rs. <?= number_format(floatval($rp['full_sum_realized']), 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once './includes/footer.php'; ?>

<!-- Chart.js, FullCalendar & SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Initialize Income Trends Chart
        var ctx = document.getElementById('incomeTrendsChart');
        if (ctx) {
            var labels = <?= json_encode($chart_labels) ?>;
            var incomeData = <?= json_encode($chart_income_data) ?>;
            var produceData = <?= json_encode($chart_produce_data) ?>;

            var chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Receipts Income (Rs.)',
                            data: incomeData,
                            backgroundColor: 'rgba(55, 7, 9, 0.85)',
                            borderColor: '#370709',
                            borderWidth: 1.5,
                            borderRadius: 6,
                            borderSkipped: false,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        },
                        {
                            label: 'Produce Sales (Rs.)',
                            data: produceData,
                            backgroundColor: 'rgba(25, 135, 84, 0.85)',
                            borderColor: '#198754',
                            borderWidth: 1.5,
                            borderRadius: 6,
                            borderSkipped: false,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                font: {
                                    family: "'Inter', sans-serif",
                                    weight: 'bold',
                                    size: 12
                                },
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(17, 24, 39, 0.95)',
                            titleFont: { size: 13, weight: 'bold' },
                            bodyFont: { size: 12 },
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    var label = context.dataset.label || '';
                                    var val = context.parsed.y || 0;
                                    return ' ' + label + ': Rs. ' + val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    family: "'Inter', sans-serif",
                                    weight: '600',
                                    size: 11
                                },
                                color: '#6b7280'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(229, 231, 235, 0.8)',
                                strokeDashArray: [4, 4]
                            },
                            ticks: {
                                font: {
                                    family: "'Inter', sans-serif",
                                    size: 11
                                },
                                color: '#6b7280',
                                callback: function(value) {
                                    if (value >= 1000000) return 'Rs. ' + (value / 1000000).toFixed(1) + 'M';
                                    if (value >= 1000) return 'Rs. ' + (value / 1000).toFixed(0) + 'k';
                                    return 'Rs. ' + value;
                                }
                            }
                        }
                    }
                }
            });
        }

        // 2. Initialize Training Calendar & Task Schedule
        var calendarEl = document.getElementById('trainingTaskCalendar');
        if (calendarEl) {
            var calEvents = <?= json_encode($calendar_events) ?>;
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,listMonth'
                },
                themeSystem: 'bootstrap5',
                height: 420,
                events: calEvents,
                eventClick: function(info) {
                    Swal.fire({
                        title: '<h5 class="fw-bold mb-0 text-dark">' + info.event.title + '</h5>',
                        html: `
                            <div class="text-start p-2">
                                <div class="mb-2 p-2 bg-light rounded border"><i class="bi bi-calendar3 me-2 text-primary"></i><strong>Scheduled Date:</strong> ${info.event.startStr}</div>
                                <div class="mb-2 p-2 bg-light rounded border"><i class="bi bi-geo-alt me-2 text-danger"></i><strong>Place / Venue:</strong> ${info.event.extendedProps.place || 'Main Center'}</div>
                                <div class="mb-0 p-2 bg-light rounded border"><i class="bi bi-clock me-2 text-success"></i><strong>Duration:</strong> ${info.event.extendedProps.duration || 'N/A'}</div>
                            </div>
                        `,
                        icon: 'info',
                        confirmButtonColor: '#370709',
                        confirmButtonText: 'Close',
                        showCancelButton: true,
                        cancelButtonText: '<i class="bi bi-box-arrow-up-right me-1"></i> Open Module',
                        cancelButtonColor: '#185dbd'
                    }).then(function(result) {
                        if (result.dismiss === Swal.DismissReason.cancel) {
                            window.location.href = '<?= BASE_PATH ?>pages/modules/training/advanced_programme.php';
                        }
                    });
                }
            });
            calendar.render();
        }
    });
</script>