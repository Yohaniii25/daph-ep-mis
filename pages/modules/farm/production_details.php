<?php
// pages/modules/farm/production_details.php -> Production Details & Produce Register (Perishables) - Annex 6
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

if ($_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;


// Seed default commodities if empty
$count_com_res = $mysqli->query("SELECT COUNT(*) AS cnt FROM farm_commodities");
$count_com = (int)($count_com_res->fetch_assoc()['cnt'] ?? 0);

// Fetch all Commodities for dropdown
$commodities_res = $mysqli->query("SELECT * FROM farm_commodities ORDER BY commodity_name ASC");
$commodities = [];
while ($row = $commodities_res->fetch_assoc()) {
    $commodities[] = $row;
}

// Determine selected commodity (default to first available)
$selected_commodity_id = isset($_GET['commodity_id']) ? intval($_GET['commodity_id']) : ($commodities[0]['id'] ?? 0);

$selected_commodity = null;
foreach ($commodities as $com) {
    if ($com['id'] == $selected_commodity_id) {
        $selected_commodity = $com;
        break;
    }
}
if (!$selected_commodity && !empty($commodities)) {
    $selected_commodity = $commodities[0];
    $selected_commodity_id = $selected_commodity['id'];
}

// Fetch Produce Register Entries for the selected Commodity
$produce_records = [];
$total_qty = 0.00;
$total_realized_sum = 0.00;
$cash_sales_count = 0;

if ($selected_commodity_id > 0) {
    $stmt = $mysqli->prepare("SELECT * FROM farm_produce_register_annex6 WHERE commodity_id = ? ORDER BY record_date DESC, id DESC");
    $stmt->bind_param("i", $selected_commodity_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $produce_records[] = $row;
        $total_qty += floatval($row['quantity']);
        $total_realized_sum += floatval($row['full_sum_realized']);
        if ($row['unit_price'] > 0) {
            $cash_sales_count++;
        }
    }
    $stmt->close();
}
?>

<!-- Header Section -->
<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h3 class="fw-bold text-dark m-0">
            <i class="bi bi-box-seam me-2" style="color: #820100;"></i>Production Details & Produce Register
        </h3>
        <p class="text-muted mb-0 small">Produce Register for Perishables: Track receipts, plot harvests, and disposal sales.</p>
    </div>
    <div class="col-md-5 text-end">
        <button class="btn btn-sm text-light fw-bold px-3 shadow-sm" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addCommodityModal">
            <i class="bi bi-plus-circle me-1"></i>Add New Commodity
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

<!-- Commodity Selection Card (Crucial Dropdown Filter) -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; background: linear-gradient(135deg, #ffffff 0%, #f4f8fb 100%);">
    <div class="card-body p-4">
        <div class="row align-items-center">
            <div class="col-md-3">
                <label class="form-label fw-bold text-dark mb-1">
                    <i class="bi bi-funnel me-1 text-primary"></i>Select Commodity:
                </label>
                <small class="text-muted d-block mb-2 mb-md-0">Choose produce item to view Annex 6 ledger</small>
            </div>
            <div class="col-md-6">
                <select id="commodity_selector" class="form-select form-select-lg fw-bold shadow-sm border-2" style="border-color: #185dbd;" onchange="window.location.href='production_details.php?commodity_id=' + this.value;">
                    <?php foreach ($commodities as $cmd): ?>
                        <option value="<?= $cmd['id'] ?>" <?= ($cmd['id'] == $selected_commodity_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cmd['commodity_name']) ?> (<?= htmlspecialchars($cmd['unit_of_measure']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 text-md-end mt-3 mt-md-0">
                <span class="badge bg-primary-subtle text-primary border border-primary px-3 py-2 fs-6 rounded-pill">
                    <i class="bi bi-tag me-1"></i>Active: <b><?= htmlspecialchars($selected_commodity['commodity_name'] ?? 'N/A') ?></b>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- KPI Summary Cards for Selected Commodity -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white h-100" style="border-radius: 12px; border-left: 5px solid #185dbd !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold uppercase d-block">Total Quantity Produced</small>
                    <span class="fs-3 fw-bold text-primary"><?= number_format($total_qty, 2) ?></span>
                    <small class="text-muted d-block mt-1"><?= htmlspecialchars($selected_commodity['unit_of_measure'] ?? 'Kg') ?></small>
                </div>
                <div class="p-3 rounded-circle bg-primary-subtle text-primary">
                    <i class="bi bi-box-seam fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white h-100" style="border-radius: 12px; border-left: 5px solid #198754 !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold uppercase d-block">Total Sum Realized</small>
                    <span class="fs-3 fw-bold text-success">LKR <?= number_format($total_realized_sum, 2) ?></span>
                    <small class="text-muted d-block mt-1">Disposal Cash Revenue</small>
                </div>
                <div class="p-3 rounded-circle bg-success-subtle text-success">
                    <i class="bi bi-cash-stack fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white h-100" style="border-radius: 12px; border-left: 5px solid #ffc107 !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold uppercase d-block">Avg Unit Price</small>
                    <span class="fs-3 fw-bold text-dark">
                        LKR <?= ($total_qty > 0 && $total_realized_sum > 0) ? number_format($total_realized_sum / $total_qty, 2) : '0.00' ?>
                    </span>
                    <small class="text-muted d-block mt-1">Per <?= htmlspecialchars($selected_commodity['unit_of_measure'] ?? 'Kg') ?></small>
                </div>
                <div class="p-3 rounded-circle bg-warning-subtle text-warning">
                    <i class="bi bi-tags fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white h-100" style="border-radius: 12px; border-left: 5px solid #6f42c1 !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold uppercase d-block">Total Transactions</small>
                    <span class="fs-3 fw-bold text-purple" style="color: #6f42c1;"><?= count($produce_records) ?></span>
                    <small class="text-muted d-block mt-1">Annex 6 Records</small>
                </div>
                <div class="p-3 rounded-circle bg-purple-subtle" style="background-color: #f3ebf9; color: #6f42c1;">
                    <i class="bi bi-journal-check fs-3"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Produce Register Data Table Card (Annex 6 Format with Grouped Headers) -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold text-dark m-0">
            <i class="bi bi-journal-text me-2 text-primary"></i>Annex 6: Produce Register (Perishables) for "<?= htmlspecialchars($selected_commodity['commodity_name'] ?? '') ?>"
        </h5>
        <button class="btn fw-bold px-4 text-light" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addProduceRegisterModal">
            <i class="bi bi-plus-lg me-1"></i>Log Production & Disposal
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="produceRegisterTable" class="table table-bordered table-hover align-middle text-center" style="width:100%">
                <thead class="table-dark" style="background-color: #370709;">
                    <tr>
                        <th colspan="3" class="bg-primary text-white text-center py-2 fw-bold">RECEIPT GROUP</th>
                        <th colspan="5" class="bg-success text-white text-center py-2 fw-bold">DISPOSAL GROUP</th>
                        <th rowspan="2" class="align-middle text-center">Remarks</th>
                        <th rowspan="2" class="align-middle text-end">Actions</th>
                    </tr>
                    <tr class="table-secondary text-dark small fw-bold">
                        <th>Date</th>
                        <th>Plot No</th>
                        <th>Quantity (<?= htmlspecialchars($selected_commodity['unit_of_measure'] ?? 'Kg') ?>)</th>
                        <th>Method of Disposal</th>
                        <th>Price per Unit (LKR)</th>
                        <th>Full Sum Realized (LKR)</th>
                        <th>Cash Receipt No / Page</th>
                        <th>Initials</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produce_records as $pr): ?>
                        <tr>
                            <td class="fw-bold text-nowrap"><?= date('Y-m-d', strtotime($pr['record_date'])) ?></td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($pr['plot_no'] ?: '-') ?></td>
                            <td class="fw-bold text-primary"><?= number_format($pr['quantity'], 2) ?></td>
                            <td><span class="badge bg-light text-dark border px-2"><?= htmlspecialchars($pr['disposal_method']) ?></span></td>
                            <td class="text-success">LKR <?= number_format($pr['unit_price'], 2) ?></td>
                            <td class="fw-bold text-success fs-6">LKR <?= number_format($pr['full_sum_realized'], 2) ?></td>
                            <td class="small"><?= htmlspecialchars($pr['receipt_no_or_page'] ?: '-') ?></td>
                            <td class="fw-bold text-muted"><?= htmlspecialchars($pr['initials'] ?: '-') ?></td>
                            <td class="small"><?= htmlspecialchars($pr['remarks'] ?: '-') ?></td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-outline-primary btn-edit-produce me-1"
                                    data-id="<?= $pr['id'] ?>"
                                    data-record_date="<?= htmlspecialchars($pr['record_date']) ?>"
                                    data-plot_no="<?= htmlspecialchars($pr['plot_no'] ?? '') ?>"
                                    data-quantity="<?= $pr['quantity'] ?>"
                                    data-disposal_method="<?= htmlspecialchars($pr['disposal_method']) ?>"
                                    data-unit_price="<?= $pr['unit_price'] ?>"
                                    data-full_sum_realized="<?= $pr['full_sum_realized'] ?>"
                                    data-receipt_no_or_page="<?= htmlspecialchars($pr['receipt_no_or_page'] ?? '') ?>"
                                    data-initials="<?= htmlspecialchars($pr['initials'] ?? '') ?>"
                                    data-remarks="<?= htmlspecialchars($pr['remarks'] ?? '') ?>"
                                    data-bs-toggle="modal" data-bs-target="#editProduceRegisterModal"
                                    title="Edit Record">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <a href="processors/produce_register_crud.php?action=delete_produce&id=<?= $pr['id'] ?>&commodity_id=<?= $selected_commodity_id ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete Record">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="fw-bold" style="background-color: #f8f9fa;">
                    <tr>
                        <td colspan="2" class="text-start">TOTAL SUMMARY (<?= count($produce_records) ?> entries)</td>
                        <td class="text-primary"><?= number_format($total_qty, 2) ?> <?= htmlspecialchars($selected_commodity['unit_of_measure'] ?? 'Kg') ?></td>
                        <td colspan="2" class="text-end">Total Realized Revenue:</td>
                        <td class="text-success fs-6">LKR <?= number_format($total_realized_sum, 2) ?></td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Modals -->
<?php
include './models/produce_register_modals.php';
?>

<?php require_once '../../../includes/footer.php'; ?>
