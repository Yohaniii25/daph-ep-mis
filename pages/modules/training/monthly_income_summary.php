<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../includes/header.php';

$allowed_roles = ['training_officer', 'administrator', 'provincial_director', 'district_dd'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied");
}

require_once '../../../config/db_connect.php';

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

// Active Tab Parameter
$active_tab = isset($_GET['tab']) && $_GET['tab'] === 'matrix' ? 'matrix' : 'daily';

// Categories Map
$categories = [
    'accommodation'           => 'Accommodation',
    'hall_charge'             => 'Hall Charge',
    'usage_multimedia'        => 'Usage of Multimedia',
    'usage_sound_system'      => 'Usage of Sound System',
    'sales_grass'             => 'Grass',
    'sales_banana'            => 'Banana',
    'sales_vegetable'         => 'Vegetable',
    'sales_coconut'           => 'Coconut',
    'sales_bag'               => 'Bag',
    'sales_tamarind'          => 'Tamarind',
    'sales_pasture_cuttings'  => 'Pasture Cuttings'
];

// TAB 1: DAILY RECEIPT TRANSACTIONS LOG FILTERS
$from_date       = trim($_GET['from_date'] ?? '');
$to_date         = trim($_GET['to_date'] ?? '');
$filter_category = trim($_GET['filter_category'] ?? '');

$where_clauses = ["training_center_id = ?"];
$params = [$current_center_id];
$types  = "i";

if (!empty($from_date)) {
    $where_clauses[] = "receipt_date >= ?";
    $params[] = $from_date;
    $types  .= "s";
}
if (!empty($to_date)) {
    $where_clauses[] = "receipt_date <= ?";
    $params[] = $to_date;
    $types  .= "s";
}
if (!empty($filter_category) && isset($categories[$filter_category])) {
    $where_clauses[] = "category = ?";
    $params[] = $filter_category;
    $types  .= "s";
}

$where_sql = implode(" AND ", $where_clauses);
$receipts_list = [];

$rec_sql = "SELECT * FROM training_income_receipts WHERE $where_sql ORDER BY receipt_date DESC, id DESC";
$rec_stmt = $mysqli->prepare($rec_sql);

if ($rec_stmt) {
    $rec_stmt->bind_param($types, ...$params);
    $rec_stmt->execute();
    $rec_res = $rec_stmt->get_result();
    while ($row = $rec_res->fetch_assoc()) {
        $receipts_list[] = $row;
    }
    $rec_stmt->close();
}


// TAB 2: FINANCIAL INCOME MATRIX (YEAR FILTER)
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
if ($selected_year < 2020 || $selected_year > 2035) {
    $selected_year = intval(date('Y'));
}

// Initialize 12 months matrix
$months_data = [];
$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

for ($m = 1; $m <= 12; $m++) {
    $months_data[$m] = [];
    foreach ($categories as $cat_key => $cat_label) {
        $months_data[$m][$cat_key] = 0.00;
    }
}

// Aggregation Query for Year Matrix
$agg_stmt = $mysqli->prepare("
    SELECT 
        MONTH(receipt_date) AS month_num,
        category,
        SUM(amount) AS total_amount
    FROM training_income_receipts
    WHERE training_center_id = ? AND YEAR(receipt_date) = ?
    GROUP BY MONTH(receipt_date), category
");

if ($agg_stmt) {
    $agg_stmt->bind_param("ii", $current_center_id, $selected_year);
    $agg_stmt->execute();
    $agg_res = $agg_stmt->get_result();
    while ($row = $agg_res->fetch_assoc()) {
        $m_num = intval($row['month_num']);
        $cat = $row['category'];
        if (isset($months_data[$m_num][$cat])) {
            $months_data[$m_num][$cat] = floatval($row['total_amount']);
        }
    }
    $agg_stmt->close();
}
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4 pb-5">

        <!-- Top Page Header & Controls -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h2 class="mb-1 text-dark fw-bold">Monthly Income Summary &amp; Receipts Ledger</h2>
                <p class="text-muted small mb-0">
                    Financial reporting matrix &amp; daily transactions for 
                    <strong class="text-dark"><?= htmlspecialchars($current_training_center['center_name'] ?? 'Training Centre') ?></strong>
                    (Location: <?= htmlspecialchars($current_training_center['location'] ?? 'N/A') ?>)
                </p>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- Training Centre Selector (for Admins / Multi-center users) -->
                <?php if (in_array($_SESSION['role'], ['administrator', 'provincial_director', 'district_dd']) && count($all_centers) > 1): ?>
                    <form method="GET" action="" class="d-inline-block">
                        <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
                        <input type="hidden" name="year" value="<?= $selected_year ?>">
                        <input type="hidden" name="from_date" value="<?= htmlspecialchars($from_date) ?>">
                        <input type="hidden" name="to_date" value="<?= htmlspecialchars($to_date) ?>">
                        <input type="hidden" name="filter_category" value="<?= htmlspecialchars($filter_category) ?>">
                        <select name="center_id" class="form-select form-select-sm shadow-sm border-secondary fw-semibold" onchange="this.form.submit()">
                            <?php foreach ($all_centers as $tc): ?>
                                <option value="<?= $tc['id'] ?>" <?= $tc['id'] == $current_center_id ? 'selected' : '' ?>>
                                    🏢 <?= htmlspecialchars($tc['center_name']) ?> (<?= htmlspecialchars($tc['location']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>

                <button class="btn btn-sm text-light shadow-sm fw-semibold rounded-3 px-3 py-1.5" style="background-color: #370709;" data-bs-toggle="modal" data-bs-target="#addIncomeReceiptModal">
                    <i class="bi bi-plus-circle me-1"></i> Record Receipt
                </button>
            </div>
        </div>

        <!-- Session Flash Messages via SweetAlert2 -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: <?= json_encode($_SESSION['success_message']) ?>,
                        confirmButtonColor: '#370709',
                        timer: 3500,
                        timerProgressBar: true
                    });
                });
            </script>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Action Failed',
                        text: <?= json_encode($_SESSION['error_message']) ?>,
                        confirmButtonColor: '#370709'
                    });
                });
            </script>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- MAIN CONTAINER CARD WITH TAB NAVIGATION -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <ul class="nav nav-pills card-header-pills" id="incomeTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $active_tab === 'daily' ? 'active' : '' ?> fw-bold px-4 py-2" id="daily-log-tab" data-bs-toggle="tab" data-bs-target="#daily-log-view" type="button" role="tab" aria-controls="daily-log-view" aria-selected="<?= $active_tab === 'daily' ? 'true' : 'false' ?>">
                            <i class="bi bi-journal-text me-2"></i>Daily Receipt Transactions Log
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $active_tab === 'matrix' ? 'active' : '' ?> fw-bold px-4 py-2" id="matrix-tab" data-bs-toggle="tab" data-bs-target="#matrix-view" type="button" role="tab" aria-controls="matrix-view" aria-selected="<?= $active_tab === 'matrix' ? 'true' : 'false' ?>">
                            <i class="bi bi-grid-3x3-gap-fill me-2"></i>Financial Income Matrix - Year
                        </button>
                    </li>
                </ul>

                <div class="text-muted small">
                    Location Isolation: <span class="badge bg-secondary bg-opacity-10 text-dark border px-2 py-1"><?= htmlspecialchars($current_training_center['center_name'] ?? 'Training Centre') ?></span>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="tab-content" id="incomeTabsContent">

                    <!-- ================================================================= -->
                    <!-- TAB 1: DAILY RECEIPT TRANSACTIONS LOG -->
                    <!-- ================================================================= -->
                    <div class="tab-pane fade <?= $active_tab === 'daily' ? 'show active' : '' ?>" id="daily-log-view" role="tabpanel" aria-labelledby="daily-log-tab">
                        
                        <!-- Filter Section -->
                        <div class="card border bg-light shadow-sm rounded-3 mb-4">
                            <div class="card-body py-3 px-4">
                                <form method="GET" action="" class="row g-3 align-items-end">
                                    <input type="hidden" name="tab" value="daily">
                                    <?php if (!empty($current_center_id)): ?>
                                        <input type="hidden" name="center_id" value="<?= $current_center_id ?>">
                                    <?php endif; ?>

                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold text-dark mb-1"><i class="bi bi-calendar-range me-1"></i>From Date</label>
                                        <input type="date" name="from_date" class="form-control form-control-sm bg-white" value="<?= htmlspecialchars($from_date) ?>">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold text-dark mb-1"><i class="bi bi-calendar-range me-1"></i>To Date</label>
                                        <input type="date" name="to_date" class="form-control form-control-sm bg-white" value="<?= htmlspecialchars($to_date) ?>">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold text-dark mb-1"><i class="bi bi-funnel me-1"></i>Income Category</label>
                                        <select name="filter_category" class="form-select form-select-sm bg-white">
                                            <option value="">-- All Categories --</option>
                                            <optgroup label="Facilitation Services">
                                                <option value="accommodation" <?= $filter_category === 'accommodation' ? 'selected' : '' ?>>Accommodation</option>
                                                <option value="hall_charge" <?= $filter_category === 'hall_charge' ? 'selected' : '' ?>>Hall Charge</option>
                                                <option value="usage_multimedia" <?= $filter_category === 'usage_multimedia' ? 'selected' : '' ?>>Usage of Multimedia</option>
                                                <option value="usage_sound_system" <?= $filter_category === 'usage_sound_system' ? 'selected' : '' ?>>Usage of Sound System</option>
                                            </optgroup>
                                            <optgroup label="Sales Categories">
                                                <option value="sales_grass" <?= $filter_category === 'sales_grass' ? 'selected' : '' ?>>Sales: Grass</option>
                                                <option value="sales_banana" <?= $filter_category === 'sales_banana' ? 'selected' : '' ?>>Sales: Banana</option>
                                                <option value="sales_vegetable" <?= $filter_category === 'sales_vegetable' ? 'selected' : '' ?>>Sales: Vegetable</option>
                                                <option value="sales_coconut" <?= $filter_category === 'sales_coconut' ? 'selected' : '' ?>>Sales: Coconut</option>
                                                <option value="sales_bag" <?= $filter_category === 'sales_bag' ? 'selected' : '' ?>>Sales: Bag</option>
                                                <option value="sales_tamarind" <?= $filter_category === 'sales_tamarind' ? 'selected' : '' ?>>Sales: Tamarind</option>
                                                <option value="sales_pasture_cuttings" <?= $filter_category === 'sales_pasture_cuttings' ? 'selected' : '' ?>>Sales: Pasture Cuttings</option>
                                            </optgroup>
                                        </select>
                                    </div>

                                    <div class="col-md-3 d-flex gap-2">
                                        <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">
                                            <i class="bi bi-filter me-1"></i> Filter Records
                                        </button>
                                        <a href="?tab=daily<?= !empty($current_center_id) ? '&center_id=' . $current_center_id : '' ?>" class="btn btn-sm btn-outline-secondary w-100">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Data Table Ledger -->
                        <div class="table-responsive">
                            <table id="receiptsLedgerTable" class="table table-hover align-middle table-striped border row-border w-100 small">
                                <thead class="table-light text-secondary">
                                    <tr>
                                        <th style="width: 12%;">Date</th>
                                        <th style="width: 15%;">Receipt No.</th>
                                        <th style="width: 20%;">Income Category</th>
                                        <th style="width: 20%;">Received From / Payer</th>
                                        <th class="text-end" style="width: 13%;">Amount (Rs.)</th>
                                        <th style="width: 10%;">Remarks</th>
                                        <th class="text-center" style="width: 10%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $filtered_total = 0.00;
                                    foreach ($receipts_list as $rec): 
                                        $filtered_total += floatval($rec['amount']);
                                    ?>
                                        <tr>
                                            <td class="font-monospace text-nowrap"><?= htmlspecialchars($rec['receipt_date']) ?></td>
                                            <td class="fw-bold font-monospace text-dark"><?= htmlspecialchars($rec['receipt_no']) ?></td>
                                            <td>
                                                <span class="badge bg-secondary bg-opacity-10 text-dark border px-2 py-1">
                                                    <?= htmlspecialchars($categories[$rec['category']] ?? $rec['category']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($rec['payer_name'] ?: '-') ?></td>
                                            <td class="text-end font-monospace fw-bold text-dark">
                                                <?= number_format($rec['amount'], 2) ?>
                                            </td>
                                            <td class="text-muted small"><?= htmlspecialchars($rec['remarks'] ?: '-') ?></td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-primary py-0 px-2 me-1 edit-receipt-btn" 
                                                        data-id="<?= $rec['id'] ?>"
                                                        data-date="<?= htmlspecialchars($rec['receipt_date']) ?>"
                                                        data-no="<?= htmlspecialchars($rec['receipt_no']) ?>"
                                                        data-category="<?= htmlspecialchars($rec['category']) ?>"
                                                        data-amount="<?= htmlspecialchars($rec['amount']) ?>"
                                                        data-payer="<?= htmlspecialchars($rec['payer_name']) ?>"
                                                        data-remarks="<?= htmlspecialchars($rec['remarks']) ?>"
                                                        data-bs-toggle="modal" data-bs-target="#editIncomeReceiptModal">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="./processors/income_receipt_crud.php?action=delete&id=<?= $rec['id'] ?>" 
                                                   class="btn btn-sm btn-outline-danger py-0 px-2 delete-receipt-btn"
                                                   data-no="<?= htmlspecialchars($rec['receipt_no']) ?>">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light border-top border-2 border-secondary font-monospace fw-bold">
                                    <tr>
                                        <td colspan="4" class="text-end pe-3 text-dark">Filtered Ledger Total (Rs.):</td>
                                        <td class="text-end text-primary" style="font-size: 13.5px;"><?= number_format($filtered_total, 2) ?></td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>


                    <!-- ================================================================= -->
                    <!-- TAB 2: FINANCIAL INCOME MATRIX - YEAR -->
                    <!-- ================================================================= -->
                    <div class="tab-pane fade <?= $active_tab === 'matrix' ? 'show active' : '' ?>" id="matrix-view" role="tabpanel" aria-labelledby="matrix-tab">
                        
                        <!-- Matrix Controls Bar (Year Filter & Details) -->
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center bg-light border p-3 rounded-3 mb-4 gap-3">
                            <div>
                                <h6 class="fw-bold mb-1 text-dark"><i class="bi bi-grid-3x3-gap me-2 text-primary"></i>Annual Aggregated Financial Matrix</h6>
                                <small class="text-muted">Calculated automatically from raw daily receipt entries</small>
                            </div>

                            <form method="GET" action="" class="d-inline-block">
                                <input type="hidden" name="tab" value="matrix">
                                <?php if (!empty($current_center_id)): ?>
                                    <input type="hidden" name="center_id" value="<?= $current_center_id ?>">
                                <?php endif; ?>
                                <div class="input-group input-group-sm shadow-sm">
                                    <span class="input-group-text bg-white fw-bold text-secondary"><i class="bi bi-calendar-event me-1"></i> Select Matrix Year:</span>
                                    <select name="year" class="form-select fw-bold text-dark" onchange="this.form.submit()">
                                        <?php for ($y = 2024; $y <= 2030; $y++): ?>
                                            <option value="<?= $y ?>" <?= $y == $selected_year ? 'selected' : '' ?>><?= $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </form>
                        </div>

                        <!-- COMPLEX FINANCIAL MATRIX TABLE -->
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle text-center mb-0 small" style="border-color: #dee2e6;">
                                <thead class="text-light align-middle" style="background-color: #370709;">
                                    <!-- ROW 1 OF HEADERS -->
                                    <tr>
                                        <th rowspan="3" style="width: 10%; vertical-align: middle;" >Month</th>
                                        <th colspan="11" class="text-center text-uppercase tracking-wider py-2">Income</th>
                                        <th rowspan="3" style="width: 11%; vertical-align: middle;" class="text-nowrap">Total (Rs.)</th>
                                    </tr>

                                    <!-- ROW 2 OF HEADERS -->
                                    <tr>
                                        <th rowspan="2" style="vertical-align: middle; min-width: 100px;">Accommodation</th>
                                        <th rowspan="2" style="vertical-align: middle; min-width: 100px;">Hall Charge</th>
                                        <th rowspan="2" style="vertical-align: middle; min-width: 100px;">Usage of<br>Multimedia</th>
                                        <th rowspan="2" style="vertical-align: middle; min-width: 100px;">Usage of<br>Sound System</th>
                                        <th colspan="7" class="text-center py-1.5" >Sales</th>
                                    </tr>

                                    <!-- ROW 3 OF HEADERS (Sales Sub-columns) -->
                                    <tr style="background-color: #570c10;">
                                        <th style="min-width: 80px;">Grass</th>
                                        <th style="min-width: 80px;">Banana</th>
                                        <th style="min-width: 80px;">Vegetable</th>
                                        <th style="min-width: 80px;">Coconut</th>
                                        <th style="min-width: 80px;">Bag</th>
                                        <th style="min-width: 80px;">Tamarind</th>
                                        <th style="min-width: 105px;">Pasture<br>Cuttings</th>
                                    </tr>
                                </thead>

                                <tbody class="bg-white">
                                    <?php
                                    $col_totals = [
                                        'accommodation'          => 0.00,
                                        'hall_charge'            => 0.00,
                                        'usage_multimedia'       => 0.00,
                                        'usage_sound_system'     => 0.00,
                                        'sales_grass'            => 0.00,
                                        'sales_banana'           => 0.00,
                                        'sales_vegetable'        => 0.00,
                                        'sales_coconut'          => 0.00,
                                        'sales_bag'              => 0.00,
                                        'sales_tamarind'         => 0.00,
                                        'sales_pasture_cuttings' => 0.00
                                    ];
                                    $grand_total = 0.00;

                                    for ($m = 1; $m <= 12; $m++):
                                        $row_total = 0.00;
                                    ?>
                                        <tr>
                                            <!-- Month Name Column -->
                                            <td class="fw-bold text-start ps-3 text-dark bg-light" style="font-size: 13px;">
                                                <?= $month_names[$m] ?>
                                            </td>

                                            <!-- Category Value Columns -->
                                            <?php foreach ($categories as $cat_key => $cat_label): 
                                                $val = $months_data[$m][$cat_key];
                                                $row_total += $val;
                                                $col_totals[$cat_key] += $val;
                                            ?>
                                                <td class="text-end pe-2 font-monospace <?= $val > 0 ? 'fw-semibold text-dark' : 'text-muted' ?>" style="font-size: 12px;">
                                                    <?= $val > 0 ? number_format($val, 2) : '-' ?>
                                                </td>
                                            <?php endforeach; ?>

                                            <!-- Horizontal Month Row Total -->
                                            <td class="text-end pe-3 font-monospace fw-bold text-primary bg-light" style="font-size: 12.5px;">
                                                <?php $grand_total += $row_total; ?>
                                                <?= number_format($row_total, 2) ?>
                                            </td>
                                        </tr>
                                    <?php endfor; ?>
                                </tbody>

                                <!-- BOTTOM TOTAL (Rs.) ROW -->
                                <tfoot class="fw-bold align-middle" style="background-color: #f8f9fa;">
                                    <tr class="border-top border-2 border-dark" style="font-size: 13px;">
                                        <td class="text-center bg-dark text-light py-2.5">
                                            TOTAL (Rs.)
                                        </td>

                                        <?php foreach ($categories as $cat_key => $cat_label): ?>
                                            <td class="text-end pe-2 font-monospace text-dark py-2.5" style="background-color: #eaecef;">
                                                <?= number_format($col_totals[$cat_key], 2) ?>
                                            </td>
                                        <?php endforeach; ?>

                                        <!-- GRAND TOTAL IN BOTTOM RIGHT CORNER -->
                                        <td class="text-end pe-3 font-monospace text-light py-2.5" style="background-color: #370709; font-size: 13.5px;">
                                            <?= number_format($grand_total, 2) ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                    </div>

                </div>
            </div>
        </div>

    </main>
</div>

<?php include './models/add_income_receipt.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
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
    $(document).ready(function() {
        // Initialize DataTables with CSV, PDF, and Print export capabilities for Tab 1
        var table = $('#receiptsLedgerTable').DataTable({
            "order": [[0, "desc"]],
            "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search ledger entries..."
            },
            "buttons": [
                { extend: 'csv', text: '<i class="bi bi-filetype-csv me-1"></i> CSV', className: 'btn btn-sm btn-success me-1 rounded shadow-sm' },
                { extend: 'pdf', text: '<i class="bi bi-file-earmark-pdf me-1"></i> PDF', className: 'btn btn-sm btn-danger me-1 rounded shadow-sm', title: 'Training Center Daily Receipt Transactions Log' },
                { extend: 'print', text: '<i class="bi bi-printer me-1"></i> Print', className: 'btn btn-sm btn-warning text-dark rounded shadow-sm' }
            ]
        });

        // Recalculate columns on tab switch
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        });

        // Edit receipt button handler
        $('.edit-receipt-btn').on('click', function() {
            $('#edit_receipt_id').val($(this).data('id'));
            $('#edit_receipt_date').val($(this).data('date'));
            $('#edit_receipt_no').val($(this).data('no'));
            $('#edit_category').val($(this).data('category'));
            $('#edit_amount').val($(this).data('amount'));
            $('#edit_payer_name').val($(this).data('payer'));
            $('#edit_remarks').val($(this).data('remarks'));
        });

        // Delete confirmation with SweetAlert2
        $('.delete-receipt-btn').on('click', function(e) {
            e.preventDefault();
            var deleteUrl = $(this).attr('href');
            var recNo = $(this).data('no') || 'this record';
            Swal.fire({
                title: 'Delete Receipt Entry?',
                text: "Are you sure you want to delete receipt " + recNo + "? This action cannot be undone.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-trash me-1"></i> Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then(function(result) {
                if (result.isConfirmed) {
                    window.location.href = deleteUrl;
                }
            });
        });
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>
