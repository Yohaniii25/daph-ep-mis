<?php
// pages/modules/training/produce_register.php -> Produce Register (Perishables) - Form A.D.30
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

// Auto-seed sample entries if table is empty for current center
$check_stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM training_produce_register WHERE training_center_id = ?");
if ($check_stmt) {
    $check_stmt->bind_param("i", $current_center_id);
    $check_stmt->execute();
    $cnt = $check_stmt->get_result()->fetch_assoc()['total'];
    if ($cnt == 0 && $current_center_id > 0) {
        $seed_samples = [
            ['Red Napier', '2026-04-02', 'Plot 01 - Fodder Section', 250.00, 'kg', 'Sold', 12.00, 3000.00, 'CR-10491', 'T.O.'],
            ['Coconut', '2026-04-05', 'Plot 04 - Coconut Block', 120.00, 'nos', 'Sold', 95.00, 11400.00, 'CR-10498', 'T.O.'],
            ['Mango', '2026-04-10', 'Plot 02 - Orchard', 85.00, 'kg', 'Sold', 180.00, 15300.00, 'CR-10512', 'T.O.'],
            ['Grass (Fodder)', '2026-04-12', 'Demonstration Plot A', 150.00, 'bundles', 'Issued', 0.00, 0.00, 'ISSUE-042', 'T.O.']
        ];
        $seed_insert = $mysqli->prepare("INSERT INTO training_produce_register (training_center_id, commodity, record_date, plot_no_crop, quantity, unit, disposal_method, price_per_unit, full_sum_realized, receipt_no_credit_page, initials_user) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($seed_insert) {
            foreach ($seed_samples as $s) {
                $seed_insert->bind_param("isssdssddss", $current_center_id, $s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $s[8], $s[9]);
                $seed_insert->execute();
            }
            $seed_insert->close();
        }
    }
    $check_stmt->close();
}

// Fetch all available commodities for filter dropdown
$available_commodities = [];
$com_stmt = $mysqli->prepare("SELECT DISTINCT commodity FROM training_produce_register WHERE training_center_id = ? AND commodity IS NOT NULL AND commodity != '' ORDER BY commodity ASC");
if ($com_stmt) {
    $com_stmt->bind_param("i", $current_center_id);
    $com_stmt->execute();
    $com_res = $com_stmt->get_result();
    while ($r = $com_res->fetch_assoc()) {
        $available_commodities[] = $r['commodity'];
    }
    $com_stmt->close();
}

// Filter Parameters
$selected_commodity = isset($_GET['commodity']) ? trim($_GET['commodity']) : 'all';
$from_date = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$to_date = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';

// Build Query
$where_clauses = ["training_center_id = ?"];
$params = [$current_center_id];
$types = "i";

if (!empty($selected_commodity) && $selected_commodity !== 'all') {
    $where_clauses[] = "commodity = ?";
    $params[] = $selected_commodity;
    $types .= "s";
}
if (!empty($from_date)) {
    $where_clauses[] = "record_date >= ?";
    $params[] = $from_date;
    $types .= "s";
}
if (!empty($to_date)) {
    $where_clauses[] = "record_date <= ?";
    $params[] = $to_date;
    $types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);
$records = [];
$total_qty = 0.00;
$total_realized = 0.00;
$total_sold_count = 0;
$total_issued_count = 0;

$stmt = $mysqli->prepare("SELECT * FROM training_produce_register WHERE $where_sql ORDER BY record_date DESC, id DESC");
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $records[] = $row;
        $total_qty += floatval($row['quantity']);
        $total_realized += floatval($row['full_sum_realized']);
        if (strcasecmp($row['disposal_method'] ?? '', 'Sold') === 0) {
            $total_sold_count++;
        } elseif (strcasecmp($row['disposal_method'] ?? '', 'Issued') === 0) {
            $total_issued_count++;
        }
    }
    $stmt->close();
}
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<!-- DataTables & SweetAlert2 Assets -->
<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">

<style>
    .produce-header-receipt {
        background: #23522f !important;
        color: #ffffff !important;
        letter-spacing: 0.5px;
        font-weight: 700;
    }
    .produce-header-disposal {
        background: #370709 !important;
        color: #ffffff !important;
        letter-spacing: 0.5px;
        font-weight: 700;
    }
    .produce-table th {
        vertical-align: middle;
    }
    .metric-card-farm {
        border-radius: 12px !important;
        background-color: #ffffff !important;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .metric-card-farm:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
    }
</style>

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4 pb-5">

        <!-- Top Header & Location Info -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2.5 py-1 rounded-2 fw-bold font-monospace">
                        Form A.D.30
                    </span>
                    <h3 class="fw-bold mb-0 text-dark">Produce Register (Perishables)</h3>
                </div>
                <p class="text-muted small mb-0">
                    Digital produce ledger for recording harvests, receipts, and disposals at
                    <strong class="text-dark"><?= htmlspecialchars($current_training_center['center_name'] ?? 'Training Centre') ?></strong>
                    (<?= htmlspecialchars($current_training_center['location'] ?? 'N/A') ?>)
                </p>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- Training Centre Selector for Admins -->
                <?php if (in_array($_SESSION['role'], ['administrator', 'provincial_director', 'district_dd']) && count($all_centers) > 1): ?>
                    <form method="GET" action="" class="d-inline-block">
                        <?php if (!empty($selected_commodity) && $selected_commodity !== 'all'): ?>
                            <input type="hidden" name="commodity" value="<?= htmlspecialchars($selected_commodity) ?>">
                        <?php endif; ?>
                        <select name="center_id" class="form-select form-select-sm shadow-sm border-secondary fw-semibold" onchange="this.form.submit()">
                            <?php foreach ($all_centers as $tc): ?>
                                <option value="<?= $tc['id'] ?>" <?= $tc['id'] == $current_center_id ? 'selected' : '' ?>>
                                    🏢 <?= htmlspecialchars($tc['center_name']) ?> (<?= htmlspecialchars($tc['location']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>

                <button type="button" class="btn btn-sm text-light shadow-sm fw-semibold rounded-3 px-3 py-1.5" style="background-color: #370709;" data-bs-toggle="modal" data-bs-target="#addProduceModal">
                    <i class="bi bi-plus-circle me-1"></i> New Produce Entry
                </button>
            </div>
        </div>

        <!-- 4 Metric Cards (Farm Module Design with Left Border) -->
        <div class="row g-3 mb-4">
            <!-- Metric 1: Total Receipts -->
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm p-3 metric-card-farm h-100" style="border-left: 5px solid #185dbd !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold text-uppercase d-block">Total Receipts</small>
                            <span class="fs-3 fw-bold text-primary"><?= number_format($total_qty, 2) ?></span>
                            <small class="text-muted d-block mt-1">Combined units recorded</small>
                        </div>
                        <div class="p-3 rounded-circle bg-primary-subtle text-primary">
                            <i class="bi bi-box-seam-fill fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Metric 2: Full Sum Realized -->
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm p-3 metric-card-farm h-100" style="border-left: 5px solid #198754 !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold text-uppercase d-block">Full Sum Realized</small>
                            <span class="fs-3 fw-bold text-success">Rs. <?= number_format($total_realized, 2) ?></span>
                            <small class="text-muted d-block mt-1">Revenue from disposals</small>
                        </div>
                        <div class="p-3 rounded-circle bg-success-subtle text-success">
                            <i class="bi bi-cash-stack fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Metric 3: Sold Transactions -->
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm p-3 metric-card-farm h-100" style="border-left: 5px solid #370709 !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold text-uppercase d-block">Sold Transactions</small>
                            <span class="fs-3 fw-bold" style="color: #370709;"><?= $total_sold_count ?></span>
                            <small class="text-muted d-block mt-1"><?= $total_issued_count ?> items issued / free</small>
                        </div>
                        <div class="p-3 rounded-circle" style="background-color: rgba(55, 7, 9, 0.1) !important; color: #370709 !important;">
                            <i class="bi bi-cart-check-fill fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Metric 4: Total Ledger Rows -->
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm p-3 metric-card-farm h-100" style="border-left: 5px solid #b08723 !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold text-uppercase d-block">Total Ledger Rows</small>
                            <span class="fs-3 fw-bold text-dark"><?= count($records) ?></span>
                            <small class="text-muted d-block mt-1"><?= count($available_commodities) ?> unique commodities</small>
                        </div>
                        <div class="p-3 rounded-circle bg-warning-subtle text-warning">
                            <i class="bi bi-receipt fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Bar Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3">
                <form method="GET" action="" class="row g-2 align-items-center">
                    <?php if (!empty($current_center_id)): ?>
                        <input type="hidden" name="center_id" value="<?= $current_center_id ?>">
                    <?php endif; ?>

                    <!-- Commodity Selector -->
                    <div class="col-md-4 col-sm-6">
                        <label class="form-label small fw-bold text-secondary mb-1">
                            <i class="bi bi-funnel-fill text-danger me-1"></i> Commodity:
                        </label>
                        <select name="commodity" class="form-select form-select-sm fw-semibold" onchange="this.form.submit()">
                            <option value="all" <?= $selected_commodity === 'all' ? 'selected' : '' ?>>-- All Commodities --</option>
                            <?php foreach ($available_commodities as $c_item): ?>
                                <option value="<?= htmlspecialchars($c_item) ?>" <?= $selected_commodity === $c_item ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c_item) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Date Range: From -->
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small fw-bold text-secondary mb-1">From Date:</label>
                        <input type="date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars($from_date) ?>" onchange="this.form.submit()">
                    </div>

                    <!-- Date Range: To -->
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small fw-bold text-secondary mb-1">To Date:</label>
                        <input type="date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars($to_date) ?>" onchange="this.form.submit()">
                    </div>

                    <!-- Reset / Action -->
                    <div class="col-md-2 col-sm-6 d-flex align-items-end gap-2 pt-md-4">
                        <a href="produce_register.php<?= !empty($current_center_id) ? '?center_id=' . $current_center_id : '' ?>" class="btn btn-sm btn-outline-secondary w-100 fw-semibold">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Main Ledger Table Card -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-danger bg-opacity-10 text-danger p-2 rounded-3">
                        <i class="bi bi-table fs-6"></i>
                    </span>
                    <div>
                        <h6 class="m-0 fw-bold text-dark">
                            Form A.D.30 - Produce Register (Perishables)
                        </h6>
                        <small class="text-muted">
                            Commodity Filter: <strong class="text-dark"><?= $selected_commodity === 'all' ? 'All Registered Commodities' : htmlspecialchars($selected_commodity) ?></strong>
                        </small>
                    </div>
                </div>

                <div>
                    <button type="button" class="btn btn-sm text-light shadow-sm fw-semibold rounded-3 px-3 py-1.5" style="background-color: #370709;" data-bs-toggle="modal" data-bs-target="#addProduceModal">
                        <i class="bi bi-plus-lg me-1"></i> Add Entry
                    </button>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle w-100 produce-table small" id="produceRegisterTable">
                        <thead>
                            <!-- ROW 1: Physical Form Grouped Super-Headers -->
                            <tr class="text-center">
                                <th colspan="4" class="produce-header-receipt py-2 text-uppercase" style="border-right: 2px solid #ffffff;">
                                    <i class="bi bi-box-arrow-in-down me-1"></i> RECEIPT
                                </th>
                                <th colspan="5" class="produce-header-disposal py-2 text-uppercase" style="border-right: 2px solid #ffffff;">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> DISPOSAL
                                </th>
                                <th rowspan="2" class="align-middle text-center text-light" style="background-color: #212529; width: 90px;">
                                    Actions
                                </th>
                            </tr>
                            <!-- ROW 2: Column Headers -->
                            <tr class="align-middle text-center fw-bold bg-light text-secondary">
                                <!-- RECEIPT COLUMNS -->
                                <th style="min-width: 95px;">Date</th>
                                <th style="min-width: 140px;">Plot No. / Crop</th>
                                <th style="min-width: 80px;">Quantity</th>
                                <th style="min-width: 65px; border-right: 2px solid #ced4da;">Unit</th>

                                <!-- DISPOSAL COLUMNS -->
                                <th style="min-width: 110px;">Method of Disposal</th>
                                <th style="min-width: 100px;">Price / Unit (Rs.)</th>
                                <th style="min-width: 115px;">Full Sum Realized (Rs.)</th>
                                <th style="min-width: 140px;">Cash Receipt / Credit Page</th>
                                <th style="min-width: 100px; border-right: 2px solid #ced4da;">Initials / User</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($records)): ?>
                                <tr>
                                    <td colspan="10" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary"></i>
                                        No produce entries found for the selected filter criteria.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($records as $row): 
                                    $disp_badge = 'bg-secondary';
                                    if (strcasecmp($row['disposal_method'] ?? '', 'Sold') === 0) $disp_badge = 'bg-success';
                                    elseif (strcasecmp($row['disposal_method'] ?? '', 'Issued') === 0) $disp_badge = 'bg-info text-dark';
                                    elseif (strcasecmp($row['disposal_method'] ?? '', 'Discarded') === 0) $disp_badge = 'bg-danger';
                                    elseif (strcasecmp($row['disposal_method'] ?? '', 'Transferred') === 0) $disp_badge = 'bg-warning text-dark';
                                ?>
                                    <tr data-id="<?= $row['id'] ?>"
                                        data-commodity="<?= htmlspecialchars($row['commodity']) ?>"
                                        data-date="<?= htmlspecialchars($row['record_date']) ?>"
                                        data-plot="<?= htmlspecialchars($row['plot_no_crop'] ?? '') ?>"
                                        data-quantity="<?= htmlspecialchars($row['quantity']) ?>"
                                        data-unit="<?= htmlspecialchars($row['unit']) ?>"
                                        data-disposal="<?= htmlspecialchars($row['disposal_method'] ?? '') ?>"
                                        data-price="<?= htmlspecialchars($row['price_per_unit']) ?>"
                                        data-sum="<?= htmlspecialchars($row['full_sum_realized']) ?>"
                                        data-receipt="<?= htmlspecialchars($row['receipt_no_credit_page'] ?? '') ?>"
                                        data-initials="<?= htmlspecialchars($row['initials_user'] ?? '') ?>"
                                        data-center-id="<?= $row['training_center_id'] ?>">
                                        
                                        <!-- RECEIPT DATA -->
                                        <td class="text-center font-monospace fw-semibold"><?= htmlspecialchars($row['record_date']) ?></td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($row['plot_no_crop'] ?? 'N/A') ?></div>
                                            <small class="badge bg-light text-secondary border"><?= htmlspecialchars($row['commodity']) ?></small>
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-dark"><?= number_format(floatval($row['quantity']), 2) ?></td>
                                        <td class="text-center text-secondary border-end"><?= htmlspecialchars($row['unit']) ?></td>

                                        <!-- DISPOSAL DATA -->
                                        <td class="text-center">
                                            <span class="badge <?= $disp_badge ?> px-2 py-1"><?= htmlspecialchars($row['disposal_method'] ?? 'N/A') ?></span>
                                        </td>
                                        <td class="text-end font-monospace text-secondary">
                                            <?= floatval($row['price_per_unit']) > 0 ? number_format(floatval($row['price_per_unit']), 2) : '-' ?>
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-success">
                                            <?= floatval($row['full_sum_realized']) > 0 ? number_format(floatval($row['full_sum_realized']), 2) : '0.00' ?>
                                        </td>
                                        <td class="text-center font-monospace"><?= htmlspecialchars($row['receipt_no_credit_page'] ?: '-') ?></td>
                                        <td class="text-center border-end">
                                            <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($row['initials_user'] ?: '-') ?></span>
                                        </td>

                                        <!-- ACTIONS -->
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-primary btn-edit-entry" title="Edit Entry">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-delete-entry" title="Delete Entry">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr class="align-middle">
                                <td colspan="2" class="text-end text-uppercase">Total:</td>
                                <td class="text-end font-monospace text-primary"><?= number_format($total_qty, 2) ?></td>
                                <td></td>
                                <td colspan="2" class="text-end text-uppercase">Sum Realized Total:</td>
                                <td class="text-end font-monospace text-success">Rs. <?= number_format($total_realized, 2) ?></td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<?php include './models/produce_register_modals.php'; ?>

<!-- Script Libraries -->
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
        // Initialize DataTables with CSV, PDF, and Print export capabilities
        var table = $('#produceRegisterTable').DataTable({
            "order": [],
            "dom": '<"d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2"Bf>rt<"d-flex flex-column flex-md-row justify-content-between align-items-md-center mt-3 gap-2"ip>',
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search produce entries..."
            },
            "buttons": [
                { 
                    extend: 'csv', 
                    text: '<i class="bi bi-filetype-csv me-1"></i> CSV', 
                    className: 'btn btn-sm btn-success me-1 rounded shadow-sm',
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] }
                },
                { 
                    extend: 'pdf', 
                    text: '<i class="bi bi-file-earmark-pdf me-1"></i> PDF', 
                    className: 'btn btn-sm btn-danger me-1 rounded shadow-sm', 
                    title: 'Produce Register (Perishables) - Form A.D.30',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] }
                },
                { 
                    extend: 'print', 
                    text: '<i class="bi bi-printer me-1"></i> Print', 
                    className: 'btn btn-sm text-light rounded shadow-sm',
                    style: 'background-color: #370709;',
                    title: 'Produce Register (Perishables) - Form A.D.30',
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] }
                }
            ]
        });

        // 3. FRONTEND AUTOMATION: Auto-calculate Full Sum Realized
        // Logic: (Quantity) * (Price per Unit)
        function calculateRealizedAdd() {
            var qty = parseFloat($('#add_quantity').val()) || 0;
            var price = parseFloat($('#add_price_per_unit').val()) || 0;
            var total = (qty * price);
            $('#add_full_sum_realized').val(total.toFixed(2));
        }

        function calculateRealizedEdit() {
            var qty = parseFloat($('#edit_quantity').val()) || 0;
            var price = parseFloat($('#edit_price_per_unit').val()) || 0;
            var total = (qty * price);
            $('#edit_full_sum_realized').val(total.toFixed(2));
        }

        $(document).on('input keyup change', '.calc-trigger-add', calculateRealizedAdd);
        $(document).on('input keyup change', '.calc-trigger-edit', calculateRealizedEdit);

        // Pre-fill Edit Modal
        $(document).on('click', '.btn-edit-entry', function() {
            var $row = $(this).closest('tr');
            $('#edit_id').val($row.data('id'));
            $('#edit_commodity').val($row.data('commodity'));
            $('#edit_record_date').val($row.data('date'));
            $('#edit_plot_no_crop').val($row.data('plot'));
            $('#edit_quantity').val($row.data('quantity'));
            $('#edit_unit').val($row.data('unit'));
            $('#edit_disposal_method').val($row.data('disposal'));
            $('#edit_price_per_unit').val($row.data('price'));
            $('#edit_full_sum_realized').val($row.data('sum'));
            $('#edit_receipt_no_credit_page').val($row.data('receipt'));
            $('#edit_initials_user').val($row.data('initials'));
            $('#edit_training_center_id').val($row.data('center-id'));

            new bootstrap.Modal(document.getElementById('editProduceModal')).show();
        });

        // AJAX Form Submission for Add Modal with SweetAlert2
        $('#addProduceForm').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $submitBtn = $('#btnSubmitAdd');
            $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                success: function(response) {
                    $submitBtn.prop('disabled', false).html('<i class="bi bi-save me-1"></i> Save Entry');
                    if (response.success) {
                        bootstrap.Modal.getInstance(document.getElementById('addProduceModal')).hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Saved Successfully!',
                            text: response.message,
                            confirmButtonColor: '#370709',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(function() {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: response.message || 'Could not save the produce entry.',
                            confirmButtonColor: '#370709'
                        });
                    }
                },
                error: function() {
                    $submitBtn.prop('disabled', false).html('<i class="bi bi-save me-1"></i> Save Entry');
                    Swal.fire({
                        icon: 'error',
                        title: 'Request Failed',
                        text: 'Failed to communicate with the server processor.',
                        confirmButtonColor: '#370709'
                    });
                }
            });
        });

        // AJAX Form Submission for Edit Modal with SweetAlert2
        $('#editProduceForm').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $submitBtn = $('#btnSubmitEdit');
            $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...');

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                success: function(response) {
                    $submitBtn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Update Entry');
                    if (response.success) {
                        bootstrap.Modal.getInstance(document.getElementById('editProduceModal')).hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated Successfully!',
                            text: response.message,
                            confirmButtonColor: '#1b3d6d',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(function() {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Update Error',
                            text: response.message || 'Could not update the produce entry.',
                            confirmButtonColor: '#1b3d6d'
                        });
                    }
                },
                error: function() {
                    $submitBtn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Update Entry');
                    Swal.fire({
                        icon: 'error',
                        title: 'Request Failed',
                        text: 'Failed to communicate with the server processor.',
                        confirmButtonColor: '#1b3d6d'
                    });
                }
            });
        });

        // Delete with SweetAlert2 Confirmation
        $(document).on('click', '.btn-delete-entry', function() {
            var $row = $(this).closest('tr');
            var entryId = $row.data('id');
            var crop = $row.data('plot') || $row.data('commodity') || 'this record';
            var centerId = $row.data('center-id') || <?= json_encode($current_center_id) ?>;

            Swal.fire({
                icon: 'warning',
                title: 'Delete Produce Entry?',
                html: 'You are about to delete entry for <strong>' + crop + '</strong>.<br>This action cannot be undone.',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-trash me-1"></i> Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then(function(result) {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'processors/produce_register_crud.php',
                        type: 'POST',
                        data: {
                            action: 'delete',
                            id: entryId,
                            training_center_id: centerId,
                            is_ajax: 1
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $row.fadeOut(300, function() {
                                    table.row($row).remove().draw(false);
                                });
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: 'The produce record has been removed.',
                                    confirmButtonColor: '#370709',
                                    timer: 1800,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message || 'Could not delete the record.',
                                    confirmButtonColor: '#370709'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to communicate with DB processor.',
                                confirmButtonColor: '#370709'
                            });
                        }
                    });
                }
            });
        });
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>
