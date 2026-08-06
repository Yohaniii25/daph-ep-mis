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

// Fetch all Master Commodity Items with calculated overall stock balances
$master_commodities_res = $mysqli->query("
    SELECT c.*, 
           COALESCE(rec.rec_sum, 0) - COALESCE(iss.iss_sum, 0) AS calculated_stock
    FROM farm_commodities c
    LEFT JOIN (SELECT commodity_id, SUM(received_qty) AS rec_sum FROM farm_produce_register_annex6 GROUP BY commodity_id) rec ON c.id = rec.commodity_id
    LEFT JOIN (SELECT commodity_id, SUM(issued_qty) AS iss_sum FROM farm_produce_register_annex6 GROUP BY commodity_id) iss ON c.id = iss.commodity_id
    ORDER BY c.commodity_name ASC
");
$master_commodities = [];
if ($master_commodities_res) {
    while ($m = $master_commodities_res->fetch_assoc()) {
        $master_commodities[] = $m;
    }
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

// Fetch Produce Register Entries for the selected Commodity ordered chronologically
$produce_records = [];
$total_received = 0.00;
$total_issued = 0.00;
$total_realized_sum = 0.00;
$current_balance = 0.00;

if ($selected_commodity_id > 0) {
    $stmt = $mysqli->prepare("SELECT * FROM farm_produce_register_annex6 WHERE commodity_id = ? ORDER BY record_date ASC, id ASC");
    $stmt->bind_param("i", $selected_commodity_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $running_calc = 0.00;
    while ($row = $res->fetch_assoc()) {
        $rec = floatval($row['received_qty']);
        $iss = floatval($row['issued_qty']);
        
        // Backward compatibility fallback for legacy 'quantity' entries
        if ($rec == 0 && $iss == 0 && floatval($row['quantity']) > 0) {
            $rec = floatval($row['quantity']);
        }

        $row['opening_stock'] = $running_calc;
        $running_calc = round($running_calc + $rec - $iss, 2);
        $row['closing_stock'] = $running_calc;

        $produce_records[] = $row;
        $total_received += $rec;
        $total_issued += $iss;
        $total_realized_sum += floatval($row['full_sum_realized']);
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
            <i class="bi bi-box-seam me-2" style="color: #820100;"></i>Production Details & Produce Register
        </h3>
        <p class="text-muted mb-0 small">Produce Register for Perishables: Track receipts, plot harvests, and disposal sales.</p>
    </div>
    <div class="col-md-5 text-end">
        <button class="btn btn-sm text-light fw-bold px-3 shadow-sm" style="background-color: var(--farm-secondary, #5a1216);" data-bs-toggle="modal" data-bs-target="#addCommodityModal">
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

<!-- Navigation Tabs -->
<ul class="nav nav-tabs nav-tabs-bordered mb-4" id="produceTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold fs-6 <?= ($active_tab === 'ledger') ? 'active text-dark border-secondary border-bottom-0' : 'text-muted' ?>" id="ledger-tab" data-bs-toggle="tab" data-bs-target="#ledger-pane" type="button" role="tab">
            <i class="bi bi-journal-text me-2"></i>Stock & Produce Ledger
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold fs-6 <?= ($active_tab === 'manage') ? 'active text-dark border-secondary border-bottom-0' : 'text-muted' ?>" id="manage-commodities-tab" data-bs-toggle="tab" data-bs-target="#manage-pane" type="button" role="tab">
            <i class="bi bi-gear-fill me-2"></i>Manage Commodities (<?= count($master_commodities) ?>)
        </button>
    </li>
</ul>

<div class="tab-content" id="produceTabContent">
    <!-- TAB 1: STOCK & PRODUCE LEDGER -->
    <div class="tab-pane fade <?= ($active_tab === 'ledger') ? 'show active' : '' ?>" id="ledger-pane" role="tabpanel">
        
        <!-- Commodity Selection Card (Filter Dropdown) -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; background: linear-gradient(135deg, #ffffff 0%, #f4f8fb 100%);">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-dark mb-1">
                            <i class="bi bi-funnel me-1 text-farm-secondary"></i>Select Commodity:
                        </label>
                        <small class="text-muted d-block mb-2 mb-md-0">Choose produce item to view Annex 6 ledger</small>
                    </div>
                    <div class="col-md-6">
                        <select id="commodity_selector" class="form-select form-select-lg fw-bold shadow-sm border-2" style="border-color: #185dbd;" onchange="window.location.href='production_details.php?commodity_id=' + this.value + '&tab=ledger';">
                            <?php foreach ($commodities as $cmd): ?>
                                <option value="<?= $cmd['id'] ?>" <?= ($cmd['id'] == $selected_commodity_id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cmd['commodity_name']) ?> (<?= htmlspecialchars($cmd['unit_of_measure']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 text-md-end mt-3 mt-md-0">
                        <span class="badge badge-farm-secondary px-3 py-2 fs-6 rounded-pill">
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
                            <small class="text-muted fw-bold uppercase d-block">Current Stock Balance</small>
                            <span class="fs-3 fw-bold text-primary"><?= number_format($current_balance, 2) ?></span>
                            <small class="text-muted d-block mt-1"><?= htmlspecialchars($selected_commodity['unit_of_measure'] ?? 'Kg') ?></small>
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
                            <small class="text-muted fw-bold uppercase d-block">Total Received / Harvested</small>
                            <span class="fs-3 fw-bold text-success"><?= number_format($total_received, 2) ?></span>
                            <small class="text-muted d-block mt-1"><?= htmlspecialchars($selected_commodity['unit_of_measure'] ?? 'Kg') ?> Total In</small>
                        </div>
                        <div class="p-3 rounded-circle bg-success-subtle text-success">
                            <i class="bi bi-arrow-down-left-circle fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 bg-white h-100" style="border-radius: 12px; border-left: 5px solid #5a1216 !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Total Issued / Disposed</small>
                            <span class="fs-3 fw-bold text-farm-secondary"><?= number_format($total_issued, 2) ?></span>
                            <small class="text-muted d-block mt-1"><?= htmlspecialchars($selected_commodity['unit_of_measure'] ?? 'Kg') ?> Total Out</small>
                        </div>
                        <div class="p-3 rounded-circle bg-farm-secondary" style="background-color: rgba(90,18,22,0.1) !important; color: #5a1216 !important;">
                            <i class="bi bi-arrow-up-right-circle fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 bg-white h-100" style="border-radius: 12px; border-left: 5px solid #ffc107 !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold uppercase d-block">Total Revenue Realized</small>
                            <span class="fs-3 fw-bold text-dark">LKR <?= number_format($total_realized_sum, 2) ?></span>
                            <small class="text-muted d-block mt-1">Disposal Cash Revenue</small>
                        </div>
                        <div class="p-3 rounded-circle bg-warning-subtle text-warning">
                            <i class="bi bi-cash-stack fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Produce Register Data Table Card (Annex 6 Format) -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="fw-bold text-dark m-0">
                    <i class="bi bi-journal-text me-2 text-primary"></i>Annex 6: Stock & Produce Register for "<?= htmlspecialchars($selected_commodity['commodity_name'] ?? '') ?>"
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-success fw-bold px-3 shadow-sm text-light" data-bs-toggle="modal" data-bs-target="#receiveProduceModal">
                        <i class="bi bi-box-arrow-in-down me-1"></i>Receive Produce
                    </button>
                    <button style="background-color: var(--farm-secondary, #5a1216);" class="btn fw-bold px-3 shadow-sm text-light" data-bs-toggle="modal" data-bs-target="#issueProduceModal">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Issue Produce
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="produceRegisterTable" class="table table-bordered table-hover align-middle text-center" style="width:100%">
                        <thead class="table-dark" style="background-color: #370709;">
                            <tr>
                                <th style="width: 9%;">Date</th>
                                <th style="width: 13%;">Received From / Plot</th>
                                <th style="width: 13%;">Issued To / Buyer</th>
                                <th style="width: 12%;">Disposal Method</th>
                                <th style="width: 9%;">Opening Stock</th>
                                <th style="width: 8%;" class="text-success">Received (+)</th>
                                <th style="width: 8%;" class="text-farm-secondary">Issued (-)</th>
                                <th style="width: 9%;" class="text-primary">Closing Stock</th>
                                <th style="width: 11%;">Sum Realized</th>
                                <th style="width: 8%;" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produce_records as $pr): ?>
                                <?php
                                    $rec_from = !empty($pr['received_from']) ? $pr['received_from'] : (!empty($pr['plot_no']) ? 'Plot: ' . $pr['plot_no'] : '-');
                                    $iss_to = !empty($pr['issued_to']) ? $pr['issued_to'] : '-';
                                ?>
                                <tr>
                                    <td class="fw-bold text-nowrap"><?= date('Y-m-d', strtotime($pr['record_date'])) ?></td>
                                    <td class="fw-bold text-start text-success"><?= htmlspecialchars($rec_from) ?></td>
                                    <td class="fw-bold text-start text-farm-secondary"><?= htmlspecialchars($iss_to) ?></td>
                                    <td><span class="badge bg-light text-dark border px-2"><?= htmlspecialchars($pr['disposal_method'] ?: 'Harvest Intake') ?></span></td>
                                    <td class="fw-bold text-muted"><?= number_format($pr['opening_stock'], 2) ?></td>
                                    <td class="fw-bold text-success fs-6">
                                        <?= ($pr['received_qty'] > 0) ? '+' . number_format($pr['received_qty'], 2) : '-' ?>
                                    </td>
                                    <td class="fw-bold text-farm-secondary fs-6">
                                        <?= ($pr['issued_qty'] > 0) ? '-' . number_format($pr['issued_qty'], 2) : '-' ?>
                                    </td>
                                    <td class="fw-bold text-primary fs-6 bg-primary-subtle">
                                        <?= number_format($pr['closing_stock'], 2) ?>
                                    </td>
                                    <td class="fw-bold text-success">
                                        <?= ($pr['full_sum_realized'] > 0) ? 'LKR ' . number_format($pr['full_sum_realized'], 2) : '-' ?>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary btn-edit-produce me-1"
                                            data-id="<?= $pr['id'] ?>"
                                            data-record_date="<?= htmlspecialchars($pr['record_date']) ?>"
                                            data-received_from="<?= htmlspecialchars($pr['received_from'] ?? '') ?>"
                                            data-issued_to="<?= htmlspecialchars($pr['issued_to'] ?? '') ?>"
                                            data-plot_no="<?= htmlspecialchars($pr['plot_no'] ?? '') ?>"
                                            data-received_qty="<?= $pr['received_qty'] ?>"
                                            data-issued_qty="<?= $pr['issued_qty'] ?>"
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
                                <td colspan="5" class="text-start">TOTAL SUMMARY (<?= count($produce_records) ?> entries)</td>
                                <td class="text-success">+<?= number_format($total_received, 2) ?></td>
                                <td class="text-farm-secondary">-<?= number_format($total_issued, 2) ?></td>
                                <td class="text-primary fs-6 bg-primary-subtle"><?= number_format($current_balance, 2) ?></td>
                                <td class="text-success">LKR <?= number_format($total_realized_sum, 2) ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: MANAGE COMMODITIES DIRECTORY -->
    <div class="tab-pane fade <?= ($active_tab === 'manage') ? 'show active' : '' ?>" id="manage-pane" role="tabpanel">
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark m-0">
                    <i class="bi bi-box-seam me-2" style="color: var(--farm-secondary, #5a1216);"></i>Master Commodity Produce Directory
                </h5>
                <button class="btn btn-sm text-light fw-bold px-3 shadow-sm" style="background-color: var(--farm-secondary, #5a1216);" data-bs-toggle="modal" data-bs-target="#addCommodityModal">
                    <i class="bi bi-plus-circle me-1"></i>Add New Commodity
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="manageCommoditiesTable" class="table table-bordered table-hover align-middle text-center" style="width:100%">
                        <thead class="table-dark" style="background-color: #370709;">
                            <tr>
                                <th style="width: 30%;">Commodity Name</th>
                                <th style="width: 20%;">Unit of Measure</th>
                                <th style="width: 20%;">Current Stock Balance</th>
                                <th style="width: 20%;">Description / Notes</th>
                                <th style="width: 10%;" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($master_commodities as $mc): ?>
                                <tr>
                                    <td class="fw-bold text-start text-dark">
                                        <a href="production_details.php?commodity_id=<?= $mc['id'] ?>&tab=ledger" class="text-decoration-none text-dark fw-bold">
                                            <i class="bi bi-box-seam me-1 text-farm-secondary"></i><?= htmlspecialchars($mc['commodity_name']) ?>
                                        </a>
                                    </td>
                                    <td><span class="badge bg-light text-dark border px-2"><?= htmlspecialchars($mc['unit_of_measure']) ?></span></td>
                                    <td class="fw-bold text-primary fs-6 bg-primary-subtle">
                                        <?= number_format($mc['calculated_stock'], 2) ?> <?= htmlspecialchars($mc['unit_of_measure']) ?>
                                    </td>
                                    <td class="text-start small text-muted"><?= htmlspecialchars($mc['description'] ?: '-') ?></td>
                                    <td class="text-end text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary btn-edit-master-commodity me-1"
                                            data-id="<?= $mc['id'] ?>"
                                            data-commodity_name="<?= htmlspecialchars($mc['commodity_name']) ?>"
                                            data-unit_of_measure="<?= htmlspecialchars($mc['unit_of_measure']) ?>"
                                            data-description="<?= htmlspecialchars($mc['description'] ?? '') ?>"
                                            data-bs-toggle="modal" data-bs-target="#editCommodityModal"
                                            title="Edit Commodity">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <a href="processors/produce_register_crud.php?action=delete_commodity&id=<?= $mc['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete Commodity">
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
include './models/produce_register_modals.php';
?>

<!-- Pass current commodity balance to JS for live calculation -->
<script>
    var currentCommodityBalance = <?= json_encode($current_balance) ?>;
</script>

<?php require_once '../../../includes/footer.php'; ?>
