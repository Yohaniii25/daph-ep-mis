<?php
/**
 * pages/modules/veterinary/models/modal_inventory_transfer.php
 * Inter-Unit Inventory Item Transfer Request Modal
 */

if (!isset($all_ranges_list) && isset($mysqli)) {
    $all_ranges_list = [];
    $res_ranges = $mysqli->query("SELECT vr.id, vr.name, d.name AS district_name FROM veterinary_ranges vr LEFT JOIN districts d ON vr.district_id = d.id ORDER BY d.name ASC, vr.name ASC");
    if ($res_ranges) {
        while ($r = $res_ranges->fetch_assoc()) {
            $dist_suffix = !empty($r['district_name']) ? " ({$r['district_name']})" : "";
            $all_ranges_list[] = [
                'id' => $r['id'],
                'name' => "Range Office - {$r['name']}{$dist_suffix}"
            ];
        }
    }

    $all_districts_list = [];
    $res_districts = $mysqli->query("SELECT id, name FROM districts ORDER BY name ASC");
    if ($res_districts) {
        while ($r = $res_districts->fetch_assoc()) {
            $all_districts_list[] = $r;
        }
    }

    $all_farms_list = [];
    $res_farms = $mysqli->query("SELECT id, farm_name FROM regional_farms ORDER BY farm_name ASC");
    if ($res_farms) {
        while ($r = $res_farms->fetch_assoc()) {
            $all_farms_list[] = [
                'id' => $r['id'],
                'name' => "Regional Farm - {$r['farm_name']}"
            ];
        }
    }

    $all_training_list = [];
    $res_training = $mysqli->query("SELECT id, center_name, location FROM training_centers ORDER BY center_name ASC");
    if ($res_training) {
        while ($r = $res_training->fetch_assoc()) {
            $loc_suffix = !empty($r['location']) ? " ({$r['location']})" : "";
            $all_training_list[] = [
                'id' => $r['id'],
                'name' => "Training Center - {$r['center_name']}{$loc_suffix}"
            ];
        }
    }

    $all_master_units_list = [];
    $res_units = $mysqli->query("SELECT id, unit_name FROM master_units ORDER BY id ASC");
    if ($res_units) {
        while ($r = $res_units->fetch_assoc()) {
            $all_master_units_list[] = [
                'id' => $r['id'],
                'name' => $r['unit_name']
            ];
        }
    }
}
?>

<!-- Inventory Transfer Modal -->
<div class="modal fade" id="inventoryTransferModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);">
                <div>
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="bi bi-arrow-left-right me-2"></i>Initiate Inter-Unit Inventory Transfer
                    </h5>
                    <small class="text-white-50">Official Asset Relocation & Staged Authorization Request</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="inventoryTransferForm" action="processors/process_inventory_transfer.php" method="POST">
                <input type="hidden" name="id" id="trans_item_id">
                <input type="hidden" name="asset_type" id="trans_asset_type" value="building_inventory">
                <input type="hidden" name="item_name" id="trans_item_name_input">
                <input type="hidden" name="from_unit" id="trans_from_unit">

                <div class="modal-body p-4">
                    <!-- Item Metadata Banner -->
                    <div class="alert alert-light border shadow-sm py-2 px-3 mb-3 rounded-3">
                        <div class="row align-items-center small">
                            <div class="col-md-4">
                                <span class="text-muted d-block text-uppercase fw-semibold" style="font-size: 10px;">Item for Transfer</span>
                                <strong class="fs-6 text-dark" id="trans_item_name">-</strong>
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted d-block text-uppercase fw-semibold" style="font-size: 10px;">Current Location / Station</span>
                                <span class="fw-semibold text-secondary" id="trans_item_location">-</span>
                            </div>
                            <div class="col-md-2 text-center">
                                <span class="text-muted d-block text-uppercase fw-semibold" style="font-size: 10px;">Condition</span>
                                <span class="badge bg-secondary px-2 py-1" id="trans_item_condition">Good</span>
                            </div>
                            <div class="col-md-3 text-md-end">
                                <span class="text-muted d-block text-uppercase fw-semibold" style="font-size: 10px;">Active In Circulation</span>
                                <span class="badge bg-success fs-6 px-2 py-1" id="trans_item_available_qty">0</span>
                            </div>
                        </div>
                    </div>

                    <!-- CRITICAL INVARIANT POLICY NOTICE -->
                    <div class="alert alert-info py-2 px-3 small border-0 d-flex align-items-center gap-2 mb-3" style="background: #e0f2fe; border-left: 4px solid #0284c7 !important;">
                        <i class="bi bi-shield-check text-primary fs-4"></i>
                        <div>
                            <strong class="text-primary">Transfer Workflow Invariant:</strong>
                            Initiating this transfer request will log the relocation into the tracking registry and route it for administrative authorization. 
                            <strong>The item will NOT be deducted from your current active inventory count</strong>; the quantity remains completely intact while pending.
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Transfer Quantity -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">
                                Quantity to Transfer <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-123 text-muted"></i></span>
                                <input type="number" name="transfer_quantity" id="trans_transfer_quantity" class="form-control" min="1" value="1" required>
                            </div>
                            <small class="text-muted" style="font-size: 11px;">Must not exceed currently available count.</small>
                        </div>

                        <!-- Target Unit / Destination -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">
                                Destination Unit / Office <span class="text-danger">*</span>
                            </label>
                            <select name="target_unit" id="trans_target_unit" class="form-select shadow-sm" required>
                                <option value="" disabled selected>-- Select Destination Unit --</option>
                                
                                <?php if (!empty($all_ranges_list)): ?>
                                <optgroup label="Veterinary Range Offices">
                                    <?php foreach ($all_ranges_list as $vr_item): ?>
                                        <option value="<?= htmlspecialchars($vr_item['name']) ?>">
                                            <?= htmlspecialchars($vr_item['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <?php endif; ?>

                                <?php if (!empty($all_districts_list)): ?>
                                <optgroup label="District Secretariats / Offices">
                                    <?php foreach ($all_districts_list as $dist_item): ?>
                                        <option value="District Office - <?= htmlspecialchars($dist_item['name']) ?>">
                                            District Office - <?= htmlspecialchars($dist_item['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <?php endif; ?>

                                <?php if (!empty($all_farms_list)): ?>
                                <optgroup label="Regional Farms">
                                    <?php foreach ($all_farms_list as $rf_item): ?>
                                        <option value="<?= htmlspecialchars($rf_item['name']) ?>">
                                            <?= htmlspecialchars($rf_item['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <?php endif; ?>

                                <?php if (!empty($all_training_list)): ?>
                                <optgroup label="Training Centers">
                                    <?php foreach ($all_training_list as $tc_item): ?>
                                        <option value="<?= htmlspecialchars($tc_item['name']) ?>">
                                            <?= htmlspecialchars($tc_item['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <?php endif; ?>

                                <?php if (!empty($all_master_units_list)): ?>
                                <optgroup label="Core Directorates & Functional Units">
                                    <?php foreach ($all_master_units_list as $mu_item): ?>
                                        <option value="<?= htmlspecialchars($mu_item['name']) ?>">
                                            <?= htmlspecialchars($mu_item['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <?php endif; ?>
                            </select>
                            <small class="text-muted" style="font-size: 11px;">Receiving operational station or facility.</small>
                        </div>

                        <!-- Dispatch Reference Code -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">
                                Dispatch Reference Number
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-file-earmark-text text-muted"></i></span>
                                <input type="text" name="dispatch_reference" id="trans_dispatch_reference" class="form-control" placeholder="e.g. TR-2026/01">
                            </div>
                            <small class="text-muted" style="font-size: 11px;">Waybill or dispatch note reference code.</small>
                        </div>

                        <!-- Transfer Reason / Remarks -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">
                                Reason for Transfer <span class="text-danger">*</span>
                            </label>
                            <textarea name="transfer_reason" id="trans_transfer_reason" class="form-control" rows="2" placeholder="Administrative reallocation, facility support, etc." required></textarea>
                            <small class="text-muted" style="font-size: 11px;">Operational justification for transfer.</small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="submitInventoryTransferBtn" class="btn btn-primary px-4 fw-semibold">
                        <i class="bi bi-send me-1"></i> Initiate Transfer Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
