<?php
// pages/modules/farm/drug_details.php -> Drugs Details & Drug Register (Annex 5)
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

if ($_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;

// Seed default items if master table is empty
$count_items_res = $mysqli->query("SELECT COUNT(*) AS cnt FROM farm_drug_items");
$count_items = (int)($count_items_res->fetch_assoc()['cnt'] ?? 0);

// Fetch all Drug Items for dropdown & master directory
$drug_items_res = $mysqli->query("SELECT * FROM farm_drug_items ORDER BY item_name ASC");
$drug_items = [];
while ($row = $drug_items_res->fetch_assoc()) {
    $drug_items[] = $row;
}

// Fetch all Master Drug Items with calculated overall stock balances
$master_items_res = $mysqli->query("
    SELECT di.*, 
           COALESCE(rec.rec_sum, 0) - COALESCE(iss.iss_sum, 0) AS calculated_stock
    FROM farm_drug_items di
    LEFT JOIN (SELECT item_id, SUM(received_qty) AS rec_sum FROM farm_drug_register_annex5 GROUP BY item_id) rec ON di.id = rec.item_id
    LEFT JOIN (SELECT item_id, SUM(issued_qty) AS iss_sum FROM farm_drug_register_annex5 GROUP BY item_id) iss ON di.id = iss.item_id
    ORDER BY di.item_name ASC
");
$master_items = [];
if ($master_items_res) {
    while ($m = $master_items_res->fetch_assoc()) {
        $master_items[] = $m;
    }
}

// Determine selected drug item (default to first available)
$selected_item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : ($drug_items[0]['id'] ?? 0);

$selected_item = null;
foreach ($drug_items as $item) {
    if ($item['id'] == $selected_item_id) {
        $selected_item = $item;
        break;
    }
}
if (!$selected_item && !empty($drug_items)) {
    $selected_item = $drug_items[0];
    $selected_item_id = $selected_item['id'];
}

// Fetch Ledger Entries for the selected Drug Item ordered chronologically
$ledger_records = [];
$total_received = 0.00;
$total_issued = 0.00;
$current_balance = 0.00;

if ($selected_item_id > 0) {
    $stmt = $mysqli->prepare("SELECT * FROM farm_drug_register_annex5 WHERE item_id = ? ORDER BY record_date ASC, id ASC");
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

$active_tab = $_GET['tab'] ?? 'ledger';
?>

<!-- Header Section -->
<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h3 class="fw-bold text-dark m-0">
            <i class="bi bi-capsule me-2" style="color: #820100;"></i>Drugs Details & Drug Register
        </h3>
        <p class="text-muted mb-0 small">Running stock ledger and master directory for farm veterinary medicines, vaccines, and supplements.</p>
    </div>
    <div class="col-md-5 text-end">
        <button class="btn btn-sm text-light fw-bold px-3 shadow-sm" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addDrugItemModal">
            <i class="bi bi-plus-circle me-1"></i>Add New Drug Item
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

<!-- Navigation Tabs -->
<ul class="nav nav-tabs nav-tabs-bordered mb-4" id="drugTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold fs-6 <?= ($active_tab === 'ledger') ? 'active text-dark border-secondary border-bottom-0' : 'text-muted' ?>" id="ledger-tab" data-bs-toggle="tab" data-bs-target="#ledger-pane" type="button" role="tab">
            <i class="bi bi-journal-text me-2"></i>Stock Ledger 
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold fs-6 <?= ($active_tab === 'manage') ? 'active text-dark border-secondary border-bottom-0' : 'text-muted' ?>" id="manage-drugs-tab" data-bs-toggle="tab" data-bs-target="#manage-pane" type="button" role="tab">
            <i class="bi bi-gear-fill me-2"></i>Manage Drug Items (<?= count($master_items) ?>)
        </button>
    </li>
</ul>

<div class="tab-content" id="drugTabContent">
    <!-- TAB 1: STOCK LEDGER -->
    <div class="tab-pane fade <?= ($active_tab === 'ledger') ? 'show active' : '' ?>" id="ledger-pane" role="tabpanel">
        
        <!-- Item Selection Card (Crucial Dropdown Filter) -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; background: linear-gradient(135deg, #ffffff 0%, #fdf8f8 100%);">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-dark mb-1">
                            <i class="bi bi-filter-circle me-1 text-farm-secondary"></i>Select Drug / Item:
                        </label>
                        <small class="text-muted d-block mb-2 mb-md-0">Choose medicine to view</small>
                    </div>
                    <div class="col-md-6">
                        <select id="drug_item_selector" class="form-select form-select-lg fw-bold shadow-sm border-2" style="border-color: #820100;" onchange="window.location.href='drug_details.php?item_id=' + this.value + '&tab=ledger';">
                            <?php foreach ($drug_items as $di): ?>
                                <option value="<?= $di['id'] ?>" <?= ($di['id'] == $selected_item_id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($di['item_name']) ?> (<?= htmlspecialchars($di['unit_of_measure']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 text-md-end mt-3 mt-md-0 d-flex flex-column align-items-md-end gap-1">
                        <span class="badge badge-farm-secondary px-3 py-2 fs-6 rounded-pill">
                            <i class="bi bi-box-seam me-1"></i>Selected: <b><?= htmlspecialchars($selected_item['item_name'] ?? 'N/A') ?></b>
                        </span>
                        <?php if (!empty($selected_item['exp_date'])): ?>
                            <?php
                                $today = date('Y-m-d');
                                $item_exp = $selected_item['exp_date'];
                                $days_left = (int)(strtotime($item_exp) - strtotime($today)) / 86400;
                                if ($days_left < 0) {
                                    $exp_badge = '<span class="badge bg-danger text-light px-3 py-1 fs-7 rounded-pill"><i class="bi bi-exclamation-octagon me-1"></i>Expired: ' . date('Y-m-d', strtotime($item_exp)) . '</span>';
                                } elseif ($days_left <= 30) {
                                    $exp_badge = '<span class="badge bg-warning text-dark px-3 py-1 fs-7 rounded-pill"><i class="bi bi-exclamation-triangle me-1"></i>Expiring Soon: ' . date('Y-m-d', strtotime($item_exp)) . '</span>';
                                } else {
                                    $exp_badge = '<span class="badge bg-success text-light px-3 py-1 fs-7 rounded-pill"><i class="bi bi-calendar-check me-1"></i>Exp Date: ' . date('Y-m-d', strtotime($item_exp)) . '</span>';
                                }
                                echo $exp_badge;
                            ?>
                        <?php else: ?>
                            <span class="badge bg-secondary text-light px-3 py-1 fs-7 rounded-pill"><i class="bi bi-calendar-x me-1"></i>Exp Date: Not set</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI Summary Cards for Selected Drug -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 bg-white h-100" style="border-radius: 12px; border-left: 5px solid #185dbd !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Current Stock Balance</small>
                            <span class="fs-3 fw-bold text-primary"><?= number_format($current_balance, 2) ?></span>
                            <small class="text-muted d-block mt-1"><?= htmlspecialchars($selected_item['unit_of_measure'] ?? 'units') ?></small>
                        </div>
                        <div class="p-3 rounded-circle bg-primary-subtle text-primary">
                            <i class="bi bi-wallet2 fs-3"></i>
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
                            <small class="text-muted d-block mt-1"><?= htmlspecialchars($selected_item['unit_of_measure'] ?? 'units') ?> Total In</small>
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
                            <small class="text-muted d-block mt-1"><?= htmlspecialchars($selected_item['unit_of_measure'] ?? 'units') ?> Total Out</small>
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
                            <small class="text-muted d-block mt-1">Transactions</small>
                        </div>
                        <div class="p-3 rounded-circle bg-warning-subtle text-warning">
                            <i class="bi bi-journal-text fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ledger Data Table Card (Annex 5 Format) -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="fw-bold text-dark m-0">
                    <i class="bi bi-journal-bookmark me-2" style="color: #820100;"></i>Stock Ledger for "<?= htmlspecialchars($selected_item['item_name'] ?? '') ?>"
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-success fw-bold px-3 shadow-sm text-light" data-bs-toggle="modal" data-bs-target="#receiveStockOrderModal">
                        <i class="bi bi-box-arrow-in-down me-1"></i>Receive Order
                    </button>
                    <button style="background-color: var(--farm-secondary, #5a1216);" class="btn fw-bold px-3 shadow-sm text-light" data-bs-toggle="modal" data-bs-target="#issueStockOrderModal">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Issue Order
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="drugLedgerTable" class="table table-bordered table-hover align-middle text-center" style="width:100%">
                        <thead class="table-dark" style="background-color: #370709;">
                            <tr>
                                <th style="width: 10%;">Date</th>
                                <th style="width: 12%;">Order No.</th>
                                <th style="width: 14%;">Received From</th>
                                <th style="width: 14%;">Issued To</th>
                                <th style="width: 13%;">Waybill / Ref Doc No.</th>
                                <th style="width: 11%;">Exp Date</th>
                                <th style="width: 8%;" class="text-success">Received</th>
                                <th style="width: 8%;" class="text-danger">Issued</th>
                                <th style="width: 8%;" class="text-primary">Balance</th>
                                <th style="width: 7%;" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ledger_records as $lr): ?>
                                <?php
                                    $rec_from = !empty($lr['received_from']) ? $lr['received_from'] : ($lr['received_qty'] > 0 ? $lr['party_name'] : '-');
                                    $iss_to = !empty($lr['issued_to']) ? $lr['issued_to'] : ($lr['issued_qty'] > 0 ? $lr['party_name'] : '-');
                                    
                                    $row_exp_badge = '-';
                                    if (!empty($lr['exp_date'])) {
                                        $today_ts = strtotime(date('Y-m-d'));
                                        $exp_ts = strtotime($lr['exp_date']);
                                        $diff_days = ($exp_ts - $today_ts) / 86400;
                                        if ($diff_days < 0) {
                                            $row_exp_badge = '<span class="badge bg-danger text-light">' . date('Y-m-d', $exp_ts) . '</span>';
                                        } elseif ($diff_days <= 30) {
                                            $row_exp_badge = '<span class="badge bg-warning text-dark">' . date('Y-m-d', $exp_ts) . '</span>';
                                        } else {
                                            $row_exp_badge = '<span class="badge bg-success text-light">' . date('Y-m-d', $exp_ts) . '</span>';
                                        }
                                    }
                                ?>
                                <tr>
                                    <td class="fw-bold text-nowrap"><?= date('Y-m-d', strtotime($lr['record_date'])) ?></td>
                                    <td>
                                        <span class="badge bg-dark text-light border px-2 py-1">
                                            <i class="bi bi-hash me-1"></i><?= htmlspecialchars($lr['order_no'] ?: '-') ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold text-start text-success"><?= htmlspecialchars($rec_from) ?></td>
                                    <td class="fw-bold text-start text-danger"><?= htmlspecialchars($iss_to) ?></td>
                                    <td><span class="badge bg-light text-dark border px-2"><?= htmlspecialchars($lr['ref_doc_no'] ?: '-') ?></span></td>
                                    <td><?= $row_exp_badge ?></td>
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
                                        <button class="btn btn-sm btn-outline-primary btn-edit-drug-ledger me-1"
                                            data-id="<?= $lr['id'] ?>"
                                            data-order_no="<?= htmlspecialchars($lr['order_no'] ?? '') ?>"
                                            data-record_date="<?= htmlspecialchars($lr['record_date']) ?>"
                                            data-received_from="<?= htmlspecialchars($lr['received_from'] ?? '') ?>"
                                            data-issued_to="<?= htmlspecialchars($lr['issued_to'] ?? '') ?>"
                                            data-party_name="<?= htmlspecialchars($lr['party_name'] ?? '') ?>"
                                            data-ref_doc_no="<?= htmlspecialchars($lr['ref_doc_no'] ?? '') ?>"
                                            data-exp_date="<?= htmlspecialchars($lr['exp_date'] ?? '') ?>"
                                            data-received_qty="<?= $lr['received_qty'] ?>"
                                            data-issued_qty="<?= $lr['issued_qty'] ?>"
                                            data-balance_qty="<?= $lr['calculated_balance'] ?>"
                                            data-remarks="<?= htmlspecialchars($lr['remarks'] ?? '') ?>"
                                            data-bs-toggle="modal" data-bs-target="#editDrugLedgerModal"
                                            title="Edit Entry">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="processors/drug_register_crud.php?action=delete_ledger&id=<?= $lr['id'] ?>&item_id=<?= $selected_item_id ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete Entry">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="fw-bold" style="background-color: #f8f9fa;">
                            <tr>
                                <td colspan="6" class="text-start">TOTAL SUMMARY (<?= count($ledger_records) ?> entries)</td>
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

    <!-- TAB 2: MANAGE DRUG ITEMS DIRECTORY -->
    <div class="tab-pane fade <?= ($active_tab === 'manage') ? 'show active' : '' ?>" id="manage-pane" role="tabpanel">
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark m-0">
                    <i class="bi bi-capsule me-2" style="color: #820100;"></i>Master Drug / Medicine Items Directory
                </h5>
                <button class="btn btn-sm text-light fw-bold px-3 shadow-sm" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addDrugItemModal">
                    <i class="bi bi-plus-circle me-1"></i>Add New Drug Item
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="manageDrugItemsTable" class="table table-bordered table-hover align-middle text-center" style="width:100%">
                        <thead class="table-dark" style="background-color: #370709;">
                            <tr>
                                <th style="width: 25%;">Drug / Item Name</th>
                                <th style="width: 15%;">Unit of Measure</th>
                                <th style="width: 15%;">Current Stock Balance</th>
                                <th style="width: 15%;">Default Exp Date</th>
                                <th style="width: 20%;">Description / Category</th>
                                <th style="width: 10%;" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($master_items as $mi): ?>
                                <tr>
                                    <td class="fw-bold text-start text-dark">
                                        <a href="drug_details.php?item_id=<?= $mi['id'] ?>&tab=ledger" class="text-decoration-none text-dark fw-bold">
                                            <i class="bi bi-capsule me-1 text-farm-secondary"></i><?= htmlspecialchars($mi['item_name']) ?>
                                        </a>
                                    </td>
                                    <td><span class="badge bg-light text-dark border px-2"><?= htmlspecialchars($mi['unit_of_measure']) ?></span></td>
                                    <td class="fw-bold text-primary fs-6 bg-primary-subtle">
                                        <?= number_format($mi['calculated_stock'], 2) ?> <?= htmlspecialchars($mi['unit_of_measure']) ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($mi['exp_date'])): ?>
                                            <span class="badge bg-success text-light"><?= date('Y-m-d', strtotime($mi['exp_date'])) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Not set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-start small text-muted"><?= htmlspecialchars($mi['description'] ?: '-') ?></td>
                                    <td class="text-end text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary btn-edit-master-item me-1"
                                            data-id="<?= $mi['id'] ?>"
                                            data-item_name="<?= htmlspecialchars($mi['item_name']) ?>"
                                            data-unit_of_measure="<?= htmlspecialchars($mi['unit_of_measure']) ?>"
                                            data-exp_date="<?= htmlspecialchars($mi['exp_date'] ?? '') ?>"
                                            data-description="<?= htmlspecialchars($mi['description'] ?? '') ?>"
                                            data-bs-toggle="modal" data-bs-target="#editDrugItemModal"
                                            title="Edit Drug Item">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <a href="processors/drug_register_crud.php?action=delete_item&id=<?= $mi['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete Drug Item">
                                            <i class="bi bi-trash"></i> Delete
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
</div>

<!-- Modals -->
<?php
include './models/drug_register_modals.php';
?>

<!-- Pass current item balance to JS for live calculation -->
<script>
    var currentItemBalance = <?= json_encode($current_balance) ?>;
</script>

<?php require_once '../../../includes/footer.php'; ?>