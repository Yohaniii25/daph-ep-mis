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


// Fetch all Drug Items for dropdown
$drug_items_res = $mysqli->query("SELECT * FROM farm_drug_items ORDER BY item_name ASC");
$drug_items = [];
while ($row = $drug_items_res->fetch_assoc()) {
    $drug_items[] = $row;
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
?>

<!-- Header Section -->
<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h3 class="fw-bold text-dark m-0">
            <i class="bi bi-capsule me-2" style="color: #820100;"></i>Drugs Details & Drug Register
        </h3>
        <p class="text-muted mb-0 small">Running stock ledger for farm veterinary medicines, vaccines, and supplements.</p>
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

<!-- Item Selection Card (Crucial Dropdown Filter) -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; background: linear-gradient(135deg, #ffffff 0%, #fdf8f8 100%);">
    <div class="card-body p-4">
        <div class="row align-items-center">
            <div class="col-md-3">
                <label class="form-label fw-bold text-dark mb-1">
                    <i class="bi bi-filter-circle me-1 text-danger"></i>Select Drug / Item:
                </label>
                <small class="text-muted d-block mb-2 mb-md-0">Choose medicine to view</small>
            </div>
            <div class="col-md-6">
                <select id="drug_item_selector" class="form-select form-select-lg fw-bold shadow-sm border-2" style="border-color: #820100;" onchange="window.location.href='drug_details.php?item_id=' + this.value;">
                    <?php foreach ($drug_items as $di): ?>
                        <option value="<?= $di['id'] ?>" <?= ($di['id'] == $selected_item_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($di['item_name']) ?> (<?= htmlspecialchars($di['unit_of_measure']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 text-md-end mt-3 mt-md-0">
                <span class="badge bg-danger-subtle text-danger border border-danger px-3 py-2 fs-6 rounded-pill">
                    <i class="bi bi-box-seam me-1"></i>Selected: <b><?= htmlspecialchars($selected_item['item_name'] ?? 'N/A') ?></b>
                </span>
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
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold text-dark m-0">
            <i class="bi bi-journal-bookmark me-2" style="color: #820100;"></i>Stock Ledger for "<?= htmlspecialchars($selected_item['item_name'] ?? '') ?>"
        </h5>
        <button class="btn fw-bold px-4 text-light" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addDrugLedgerModal">
            <i class="bi bi-plus-lg me-1"></i>Log Stock Movement
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="drugLedgerTable" class="table table-bordered table-hover align-middle text-center" style="width:100%">
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
                                <button class="btn btn-sm btn-outline-primary btn-edit-drug-ledger me-1"
                                    data-id="<?= $lr['id'] ?>"
                                    data-record_date="<?= htmlspecialchars($lr['record_date']) ?>"
                                    data-party_name="<?= htmlspecialchars($lr['party_name']) ?>"
                                    data-ref_doc_no="<?= htmlspecialchars($lr['ref_doc_no'] ?? '') ?>"
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

<!-- Modals -->
<?php
include './models/drug_register_modals.php';
?>

<!-- Pass current item balance to JS for live calculation -->
<script>
    var currentItemBalance = <?= json_encode($current_balance) ?>;
</script>

<?php require_once '../../../includes/footer.php'; ?>