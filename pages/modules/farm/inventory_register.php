<?php
// pages/modules/farm/inventory_register.php -> Farm Inventory Register Module
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['role'])) {
    header("Location: ../../../index.php");
    exit();
}


$inventory_items = [];
$res = $mysqli->query("SELECT * FROM farm_inventory ORDER BY item_code ASC");
$total_asset_value = 0;
$total_items_count = 0;
$operational_count = 0;

if ($res) {
    while ($r = $res->fetch_assoc()) {
        $inventory_items[] = $r;
        $total_asset_value += floatval($r['total_value'] ?? ($r['quantity_in_hand'] * $r['unit_cost']));
        $total_items_count += intval($r['quantity_in_hand']);
        if ($r['condition_status'] === 'Operational') {
            $operational_count++;
        }
    }
}
?>

<link rel="stylesheet" href="../../../assets/css/farm.css">

<!-- Header Section -->
<div class="row align-items-center mb-4">
    <div class="col-md-8">
        <h3 class="fw-bold text-dark m-0">
            <i class="bi bi-boxes me-2" style="color: #820100;"></i>Farm Inventory & Asset Register
        </h3>
        <p class="text-muted mb-0 small">Centralized inventory control for machinery, equipment, tools, and farm supplies.</p>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-log-feed fw-bold px-4 text-light shadow-sm" style="background-color: #820100;" onclick="alert('Feature to add new inventory item initiated.')">
            <i class="bi bi-plus-lg me-1"></i>Add Inventory Item
        </button>
    </div>
</div>

<!-- KPI SUMMARY CARDS -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-white card-kpi-opening" style="border-radius: 12px;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold uppercase d-block">Total Asset Valuation</small>
                    <span class="fs-3 fw-bold text-primary">LKR <?= number_format($total_asset_value, 2) ?></span>
                    <small class="text-muted d-block mt-1">Capital Assets Value</small>
                </div>
                <div class="p-3 rounded-circle bg-color-c10-light">
                    <i class="bi bi-calculator-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-white card-kpi-distributed" style="border-radius: 12px;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold uppercase d-block">Total Inventory Items</small>
                    <span class="fs-3 fw-bold text-color-c11"><?= number_format($total_items_count) ?></span>
                    <small class="text-muted d-block mt-1">Units in Stock</small>
                </div>
                <div class="p-3 rounded-circle bg-color-c11-light">
                    <i class="bi bi-box-seam fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-white card-kpi-needed" style="border-radius: 12px;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold uppercase d-block">Equipment Operational Status</small>
                    <span class="fs-3 fw-bold text-success"><?= $operational_count ?> / <?= count($inventory_items) ?></span>
                    <small class="text-muted d-block mt-1">Operational Categories</small>
                </div>
                <div class="p-3 rounded-circle bg-color-c3-light">
                    <i class="bi bi-check-circle-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- INVENTORY TABLE CARD -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="fw-bold text-dark m-0">
            <i class="bi bi-list-stars me-2" style="color: #820100;"></i>Master Equipment & Inventory Catalogue
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle text-center">
                <thead class="table-header-dark">
                    <tr>
                        <th style="width: 10%;">Item Code</th>
                        <th style="width: 25%;">Item Name / Description</th>
                        <th style="width: 14%;">Category</th>
                        <th style="width: 10%;">Quantity</th>
                        <th style="width: 14%;">Unit Cost (LKR)</th>
                        <th style="width: 15%;">Total Value (LKR)</th>
                        <th style="width: 12%;">Condition</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventory_items as $item): ?>
                        <tr>
                            <td class="fw-bold"><span class="badge bg-light text-dark border px-2"><?= htmlspecialchars($item['item_code']) ?></span></td>
                            <td class="text-start fw-bold text-dark"><?= htmlspecialchars($item['item_name']) ?></td>
                            <td><span class="badge bg-primary-subtle text-primary border px-2"><?= htmlspecialchars($item['category']) ?></span></td>
                            <td class="fw-bold fs-6"><?= intval($item['quantity_in_hand']) ?> <?= htmlspecialchars($item['unit']) ?></td>
                            <td>LKR <?= number_format($item['unit_cost'], 2) ?></td>
                            <td class="fw-bold text-primary">LKR <?= number_format($item['total_value'] ?? ($item['quantity_in_hand'] * $item['unit_cost']), 2) ?></td>
                            <td>
                                <?php if ($item['condition_status'] === 'Operational'): ?>
                                    <span class="badge bg-success-subtle text-success border px-2"><i class="bi bi-check-circle me-1"></i>Operational</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning border px-2"><i class="bi bi-tools me-1"></i>Maintenance</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="tfoot-summary fw-bold">
                    <tr>
                        <td colspan="3" class="text-start">TOTAL INVENTORY ASSET VALUE</td>
                        <td class="fs-6"><?= number_format($total_items_count) ?> Units</td>
                        <td>-</td>
                        <td class="fs-6 text-primary">LKR <?= number_format($total_asset_value, 2) ?></td>
                        <td>-</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>
