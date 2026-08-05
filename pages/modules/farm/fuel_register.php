<?php
// pages/modules/farm/fuel_register.php -> Daily Fuel Register & Monthly Fuel Details Summary
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

if ($_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;

// Active tab detection
$active_tab = $_GET['tab'] ?? 'daily'; // 'daily' or 'summary'

// Selected month for filter (default current month YYYY-MM)
$selected_month = $_GET['month'] ?? date('Y-m');
$first_day_of_month = date('Y-m-01', strtotime($selected_month . '-01'));
$last_day_of_month = date('Y-m-t', strtotime($selected_month . '-01'));
$month_label = date('F Y', strtotime($first_day_of_month));


// Seed default items if master table is empty
$count_items_res = $mysqli->query("SELECT COUNT(*) AS cnt FROM farm_fuel_items");
$count_items = (int)($count_items_res->fetch_assoc()['cnt'] ?? 0);



// Fetch all Fuel Items for dropdown
$fuel_items_res = $mysqli->query("SELECT * FROM farm_fuel_items ORDER BY item_name ASC");
$fuel_items = [];
while ($row = $fuel_items_res->fetch_assoc()) {
    $fuel_items[] = $row;
}

// Determine selected fuel item for Daily Tab
$selected_item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : ($fuel_items[0]['id'] ?? 0);

$selected_item = null;
foreach ($fuel_items as $item) {
    if ($item['id'] == $selected_item_id) {
        $selected_item = $item;
        break;
    }
}
if (!$selected_item && !empty($fuel_items)) {
    $selected_item = $fuel_items[0];
    $selected_item_id = $selected_item['id'];
}

// Fetch Ledger Entries for Daily Tab ordered chronologically
$ledger_records = [];
$total_received = 0.00;
$total_issued = 0.00;
$current_balance = 0.00;

if ($selected_item_id > 0) {
    $stmt = $mysqli->prepare("SELECT * FROM farm_fuel_register WHERE item_id = ? ORDER BY record_date ASC, id ASC");
    $stmt->bind_param("i", $selected_item_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $running_calc = 0.00;
    while ($row = $res->fetch_assoc()) {
        $rec = floatval($row['received_qty']);
        $iss = floatval($row['issued_qty']);
        $running_calc = round($running_calc + $rec - $iss, 2);
        $row['calculated_balance'] = $running_calc;
        
        $ledger_records[] = $row;
        $total_received += $rec;
        $total_issued += $iss;
    }
    $stmt->close();
    
    $current_balance = $running_calc;
}

// ================= TAB 2: MONTHLY SUMMARY REPORT LOGIC =================
// Pre-defined required rows: Petrol, Diesel, Kerosene, Oil, Coolant
$predefined_fuels = ['Petrol', 'Diesel', 'Kerosene', 'Oil', 'Coolant'];

foreach ($predefined_fuels as $ftype) {
    $stmt_chk = $mysqli->prepare("SELECT id FROM monthly_fuel_summary WHERE record_month = ? AND fuel_type = ?");
    $stmt_chk->bind_param("ss", $first_day_of_month, $ftype);
    $stmt_chk->execute();
    $chk_res = $stmt_chk->get_result();
    if ($chk_res->num_rows === 0) {
        $stmt_ins = $mysqli->prepare("INSERT INTO monthly_fuel_summary (record_month, fuel_type, opening_stock, purchased, consumption, balance) VALUES (?, ?, 0.00, 0.00, 0.00, 0.00)");
        $stmt_ins->bind_param("ss", $first_day_of_month, $ftype);
        $stmt_ins->execute();
        $stmt_ins->close();
    }
    $stmt_chk->close();
}

// Fetch Monthly Summary records for selected month & auto-calculate Purchased (Received) and Consumption (Issued) from daily Fuel Register
$sql_summary = "SELECT * FROM monthly_fuel_summary WHERE record_month = ? ORDER BY FIELD(fuel_type, 'Petrol', 'Diesel', 'Kerosene', 'Oil', 'Coolant')";
$stmt_summary = $mysqli->prepare($sql_summary);
$stmt_summary->bind_param("s", $first_day_of_month);
$stmt_summary->execute();
$res_summary = $stmt_summary->get_result();

$fuel_summary_records = [];
while ($row = $res_summary->fetch_assoc()) {
    $row_id = $row['id'];
    $ftype = $row['fuel_type'];
    $opening = floatval($row['opening_stock']);
    
    // Auto-calculate Purchased & Consumption for this fuel type from daily Fuel Register
    $search_pattern = '%' . $ftype . '%';
    $stmt_calc = $mysqli->prepare("
        SELECT 
            COALESCE(SUM(fr.received_qty), 0) AS total_purchased,
            COALESCE(SUM(fr.issued_qty), 0) AS total_consumed
        FROM farm_fuel_register fr
        JOIN farm_fuel_items fi ON fr.item_id = fi.id
        WHERE (fi.item_name LIKE ? OR ? LIKE CONCAT('%', fi.item_name, '%'))
          AND fr.record_date BETWEEN ? AND ?
    ");
    $stmt_calc->bind_param("ssss", $search_pattern, $ftype, $first_day_of_month, $last_day_of_month);
    $stmt_calc->execute();
    $calc_res = $stmt_calc->get_result()->fetch_assoc();
    $purchased = floatval($calc_res['total_purchased'] ?? 0);
    $consumption = floatval($calc_res['total_consumed'] ?? 0);
    $stmt_calc->close();

    // Balance Formula: (Opening stock + Purchased) - Consumption
    $calculated_balance = ($opening + $purchased) - $consumption;

    // Auto-sync calculated values back to DB table
    $stmt_upd = $mysqli->prepare("UPDATE monthly_fuel_summary SET purchased = ?, consumption = ?, balance = ? WHERE id = ?");
    $stmt_upd->bind_param("dddi", $purchased, $consumption, $calculated_balance, $row_id);
    $stmt_upd->execute();
    $stmt_upd->close();

    $row['purchased'] = $purchased;
    $row['consumption'] = $consumption;
    $row['balance'] = $calculated_balance;
    $fuel_summary_records[] = $row;
}
$stmt_summary->close();
?>

<!-- Header Section -->
<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h3 class="fw-bold text-dark m-0">
            <i class="bi bi-fuel-pump me-2" style="color: #820100;"></i>Fuel Register & Monthly Details Summary
        </h3>
        <p class="text-muted mb-0 small">Daily fuel stock movement ledger (Annex 7) and monthly fuel summary report.</p>
    </div>
    <div class="col-md-5 d-flex justify-content-end align-items-center gap-2">
        <label class="fw-bold mb-0 text-nowrap"><i class="bi bi-calendar3 me-1"></i>Select Month:</label>
        <input type="month" id="filter_month" class="form-control form-control-sm w-auto shadow-sm" value="<?= $selected_month ?>">
        <button type="button" id="btn_apply_filter" class="btn btn-sm px-3 fw-bold" style="background-color: #370709; color: #ffffff;">
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

<!-- Sub-Navigation Tabs Component -->
<ul class="nav nav-tabs mb-4 px-3 border-bottom-0" id="fuelModuleTabs" role="tablist">
    <li class="nav-item shadow-sm" role="presentation" style="margin-right: 4px;">
        <button class="nav-link fw-bold <?= ($active_tab === 'daily') ? 'active text-light' : 'text-dark bg-white' ?> border-0 py-3 px-4" 
                id="daily-tab" data-bs-toggle="tab" data-bs-target="#daily-pane" type="button" role="tab"
                style="<?= ($active_tab === 'daily') ? 'background-color: #820100; color: #ffffff; border-radius: 8px 8px 0 0;' : 'border-radius: 8px 8px 0 0;' ?>">
            <i class="bi bi-fuel-pump-diesel me-2"></i>1. Daily Fuel Register (Annex 7)
        </button>
    </li>
    <li class="nav-item shadow-sm" role="presentation">
        <button class="nav-link fw-bold <?= ($active_tab === 'summary') ? 'active text-light' : 'text-dark bg-white' ?> border-0 py-3 px-4" 
                id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary-pane" type="button" role="tab"
                style="<?= ($active_tab === 'summary') ? 'background-color: #185dbd; color: #ffffff; border-radius: 8px 8px 0 0;' : 'border-radius: 8px 8px 0 0;' ?>">
            <i class="bi bi-file-earmark-spreadsheet me-2"></i>2. Fuel Details (Monthly Summary Report)
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- ================= TAB 1: DAILY FUEL REGISTER ================= -->
    <div class="tab-pane fade <?= ($active_tab === 'daily') ? 'show active' : '' ?>" id="daily-pane" role="tabpanel">
        
        <!-- Item Selection Card (Crucial Dropdown Filter) -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; background: linear-gradient(135deg, #ffffff 0%, #fffbf5 100%);">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-dark mb-1">
                            <i class="bi bi-fuel-pump-diesel me-1 text-warning"></i>Select Fuel / Item:
                        </label>
                        <small class="text-muted d-block mb-2 mb-md-0">Choose fuel type to view stock ledger</small>
                    </div>
                    <div class="col-md-6">
                        <select id="fuel_item_selector" class="form-select form-select-lg fw-bold shadow-sm border-2" style="border-color: #b08723;" onchange="window.location.href='fuel_register.php?tab=daily&item_id=' + this.value;">
                            <?php foreach ($fuel_items as $fi): ?>
                                <option value="<?= $fi['id'] ?>" <?= ($fi['id'] == $selected_item_id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($fi['item_name']) ?> (<?= htmlspecialchars($fi['unit_of_measure']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 text-md-end mt-3 mt-md-0">
                        <button class="btn btn-sm text-light fw-bold px-3 shadow-sm" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addFuelItemModal">
                            <i class="bi bi-plus-circle me-1"></i>Add Fuel Item
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI Summary Cards for Selected Fuel -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 bg-white h-100" style="border-radius: 12px; border-left: 5px solid #185dbd !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Current Fuel Balance</small>
                            <span class="fs-3 fw-bold text-primary"><?= number_format($current_balance, 2) ?></span>
                            <small class="text-muted d-block mt-1"><?= htmlspecialchars($selected_item['unit_of_measure'] ?? 'Liters') ?></small>
                        </div>
                        <div class="p-3 rounded-circle bg-primary-subtle text-primary">
                            <i class="bi bi-speedometer2 fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 bg-white h-100" style="border-radius: 12px; border-left: 5px solid #198754 !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Total Received</small>
                            <span class="fs-3 fw-bold text-success"><?= number_format($total_received, 2) ?></span>
                            <small class="text-muted d-block mt-1"><?= htmlspecialchars($selected_item['unit_of_measure'] ?? 'Liters') ?> Total In</small>
                        </div>
                        <div class="p-3 rounded-circle bg-success-subtle text-success">
                            <i class="bi bi-arrow-down-left-circle fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 bg-white h-100" style="border-radius: 12px; border-left: 5px solid #dc3545 !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Total Issued</small>
                            <span class="fs-3 fw-bold text-danger"><?= number_format($total_issued, 2) ?></span>
                            <small class="text-muted d-block mt-1"><?= htmlspecialchars($selected_item['unit_of_measure'] ?? 'Liters') ?> Consumed</small>
                        </div>
                        <div class="p-3 rounded-circle bg-danger-subtle text-danger">
                            <i class="bi bi-arrow-up-right-circle fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 bg-white h-100" style="border-radius: 12px; border-left: 5px solid #ffc107 !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Total Ledger Entries</small>
                            <span class="fs-3 fw-bold text-dark"><?= count($ledger_records) ?></span>
                            <small class="text-muted d-block mt-1">Fuel Transactions</small>
                        </div>
                        <div class="p-3 rounded-circle bg-warning-subtle text-warning">
                            <i class="bi bi-journal-text fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ledger Data Table Card -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark m-0">
                    <i class="bi bi-journal-bookmark me-2" style="color: #820100;"></i>Stock Ledger for "<?= htmlspecialchars($selected_item['item_name'] ?? '') ?>"
                </h5>
                <button class="btn fw-bold px-4 text-light" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addFuelLedgerModal">
                    <i class="bi bi-plus-lg me-1"></i>Log Fuel Movement
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="fuelLedgerTable" class="table table-bordered table-hover align-middle text-center" style="width:100%">
                        <thead class="table-dark" style="background-color: #370709;">
                            <tr>
                                <th style="width: 12%;">Date</th>
                                <th style="width: 25%;">Received from, or Issued to</th>
                                <th style="width: 20%;">No. of Way-bill, Issue Note, &c.</th>
                                <th style="width: 12%;" class="text-success">Received</th>
                                <th style="width: 12%;" class="text-danger">Issued</th>
                                <th style="width: 12%;" class="text-primary">Balance</th>
                                <th style="width: 7%;" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ledger_records as $lr): ?>
                                <tr>
                                    <td class="fw-bold text-nowrap"><?= date('Y-m-d', strtotime($lr['record_date'])) ?></td>
                                    <td class="fw-bold text-start text-dark"><?= htmlspecialchars($lr['party_name']) ?></td>
                                    <td><span class="badge bg-light text-dark border px-2"><?= htmlspecialchars($lr['ref_doc_no'] ?: '-') ?></span></td>
                                    <td class="fw-bold text-success fs-6">
                                        <?= ($lr['received_qty'] > 0) ? '+' . number_format($lr['received_qty'], 2) : '-' ?>
                                    </td>
                                    <td class="fw-bold text-danger fs-6">
                                        <?= ($lr['issued_qty'] > 0) ? '-' . number_format($lr['issued_qty'], 2) : '-' ?>
                                    </td>
                                    <td class="fw-bold text-primary fs-6 bg-primary-subtle">
                                        <?= number_format($lr['calculated_balance'], 2) ?>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary btn-edit-fuel-ledger me-1"
                                            data-id="<?= $lr['id'] ?>"
                                            data-record_date="<?= htmlspecialchars($lr['record_date']) ?>"
                                            data-party_name="<?= htmlspecialchars($lr['party_name']) ?>"
                                            data-ref_doc_no="<?= htmlspecialchars($lr['ref_doc_no'] ?? '') ?>"
                                            data-received_qty="<?= $lr['received_qty'] ?>"
                                            data-issued_qty="<?= $lr['issued_qty'] ?>"
                                            data-balance_qty="<?= $lr['calculated_balance'] ?>"
                                            data-remarks="<?= htmlspecialchars($lr['remarks'] ?? '') ?>"
                                            data-bs-toggle="modal" data-bs-target="#editFuelLedgerModal"
                                            title="Edit Entry">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="processors/fuel_register_crud.php?action=delete_ledger&id=<?= $lr['id'] ?>&item_id=<?= $selected_item_id ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete Entry">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="fw-bold" style="background-color: #f8f9fa;">
                            <tr>
                                <td colspan="3" class="text-start">TOTAL SUMMARY (<?= count($ledger_records) ?> entries)</td>
                                <td class="text-success">+<?= number_format($total_received, 2) ?></td>
                                <td class="text-danger">-<?= number_format($total_issued, 2) ?></td>
                                <td class="text-primary fs-6 bg-primary-subtle"><?= number_format($current_balance, 2) ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= TAB 2: FUEL DETAILS (MONTHLY SUMMARY REPORT) ================= -->
    <div class="tab-pane fade <?= ($active_tab === 'summary') ? 'show active' : '' ?>" id="summary-pane" role="tabpanel">
        
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark m-0">
                    <i class="bi bi-file-earmark-spreadsheet me-2" style="color: #185dbd;"></i>Fuel Details Monthly Summary Inventory Register (<?= $month_label ?>)
                </h5>
                <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Purchased & Consumption auto-populate from daily Fuel Register</small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="fuelSummaryTable" class="table table-bordered table-hover align-middle text-center" style="width:100%">
                        <thead class="table-dark" style="background-color: #185dbd;">
                            <tr>
                                <th style="width: 20%;">Type of Fuel</th>
                                <th style="width: 15%;">Opening stock</th>
                                <th style="width: 15%;" class="text-success">Purchased</th>
                                <th style="width: 15%;" class="text-danger">Consumption</th>
                                <th style="width: 15%;" class="text-primary">Balance</th>
                                <th style="width: 12%;">Remarks</th>
                                <th style="width: 8%;" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fuel_summary_records as $fs): ?>
                                <tr>
                                    <td class="fw-bold text-start text-dark">
                                        <span class="badge bg-secondary-subtle text-dark border px-3 py-2 fs-6">
                                            <i class="bi bi-droplet me-1 text-warning"></i><?= htmlspecialchars($fs['fuel_type']) ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold text-dark fs-6"><?= number_format($fs['opening_stock'], 2) ?></td>
                                    <td class="fw-bold text-success fs-6">+<?= number_format($fs['purchased'], 2) ?></td>
                                    <td class="fw-bold text-danger fs-6">-<?= number_format($fs['consumption'], 2) ?></td>
                                    <td class="fw-bold text-primary fs-6 bg-primary-subtle"><?= number_format($fs['balance'], 2) ?></td>
                                    <td class="small"><?= htmlspecialchars($fs['remarks'] ?: '-') ?></td>
                                    <td class="text-end text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary btn-edit-fuel-summary me-1"
                                            data-id="<?= $fs['id'] ?>"
                                            data-fuel_type="<?= htmlspecialchars($fs['fuel_type']) ?>"
                                            data-opening_stock="<?= $fs['opening_stock'] ?>"
                                            data-purchased="<?= $fs['purchased'] ?>"
                                            data-consumption="<?= $fs['consumption'] ?>"
                                            data-balance="<?= $fs['balance'] ?>"
                                            data-remarks="<?= htmlspecialchars($fs['remarks'] ?? '') ?>"
                                            data-bs-toggle="modal" data-bs-target="#editMonthlyFuelModal"
                                            title="Edit Opening Stock & Remarks">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modals -->
<?php
include './models/fuel_register_modals.php';
?>

<!-- Pass current item balance to JS for live calculation -->
<script>
    var currentFuelBalance = <?= json_encode($current_balance) ?>;
</script>

<?php require_once '../../../includes/footer.php'; ?>
