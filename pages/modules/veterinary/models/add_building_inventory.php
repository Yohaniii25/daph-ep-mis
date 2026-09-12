<div class="modal fade" id="addInventoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-light">
                <h5 class="modal-title"><i class="bi bi-box-seam-fill me-2"></i>Log Building Inventory Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addInventoryForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="unit" value="range_veterinary_officer">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Inventory Number <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-hash text-muted"></i></span>
                                <input type="text" name="inventory_number" id="add_inventory_number" class="form-control" placeholder="e.g. INV-2026-001" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Inventory Type <span class="text-danger">*</span></label>
                            <input type="text" name="inventory_type" id="add_inventory_type" class="form-control" list="inventory_type_list" placeholder="e.g. Equipment, Furniture, Electronics" autocomplete="off" required>
                            <datalist id="inventory_type_list">
                                <?php 
                                if (!isset($all_inventory_type_suggestions)) {
                                    $all_inventory_type_suggestions = ['Equipment', 'Furniture', 'Machinery', 'Office Equipment', 'Medical Equipment', 'Cold Chain Equipment', 'Electronics & IT', 'Clinical & Lab Tools', 'Vehicle / Transport', 'Consumables'];
                                    if (isset($mysqli)) {
                                        $q = $mysqli->query("SELECT DISTINCT inventory_type FROM building_inventories WHERE inventory_type IS NOT NULL AND TRIM(inventory_type) != ''");
                                        if ($q) {
                                            while ($r = $q->fetch_assoc()) {
                                                $t = trim($r['inventory_type']);
                                                if (!empty($t) && !in_array($t, $all_inventory_type_suggestions)) {
                                                    $all_inventory_type_suggestions[] = $t;
                                                }
                                            }
                                        }
                                    }
                                    sort($all_inventory_type_suggestions);
                                }
                                foreach ($all_inventory_type_suggestions as $sug_type): 
                                ?>
                                    <option value="<?= htmlspecialchars($sug_type) ?>">
                                <?php endforeach; ?>
                            </datalist>
                            <small class="text-muted" style="font-size: 11px;"><i class="bi bi-lightbulb me-1"></i>Suggests existing types from database or enter new.</small>
                        </div>
                        <div class="col-md-6 position-relative">
                            <label class="form-label small fw-bold">Item Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-box-seam text-muted"></i></span>
                                <input type="text" 
                                       name="inventory_item" 
                                       id="add_inventory_item" 
                                       class="form-control" 
                                       placeholder="Type item name (e.g. Split Air Conditioner)..." 
                                       autocomplete="off" 
                                       required>
                            </div>
                            <div id="add_inventory_item_suggestions" class="dropdown-menu w-100 shadow border-0 mt-1 py-1" style="display: none; position: absolute; z-index: 1060; max-height: 220px; overflow-y: auto;"></div>
                            <small class="text-muted" style="font-size: 11px;">
                                <i class="bi bi-magic me-1"></i>Auto-suggests from saved inventory items as you type.
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Assigned Location / Property <span class="text-danger">*</span></label>
                            <input type="text" name="location" id="add_inventory_location" class="form-control" list="location_datalist" placeholder="e.g. Office, Quarters, Main Lab" required>
                            <datalist id="location_datalist">
                                <option value="Office">
                                <option value="Quarters">
                            </datalist>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Issue Order No.</label>
                            <input type="text" name="issue_order_no" id="add_issue_order_no" class="form-control" placeholder="e.g. IO-2026-004">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Received From</label>
                            <input type="text" name="received_from" id="add_received_from" class="form-control" placeholder="e.g. Provincial Medical Stores / DAPH HQ">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Receipt No.</label>
                            <input type="text" name="receipt_no" id="add_receipt_no" class="form-control" placeholder="e.g. REC-9821">
                        </div>

                        <!-- Availability Auto-Calculation Block -->
                        <div class="col-md-12">
                            <div class="p-3 bg-light rounded border">
                                <div class="row g-3 align-items-center">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-secondary mb-1">
                                            <i class="bi bi-lock-fill me-1"></i>Initial Baseline Stock <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" name="initial_count" id="add_initial_count" class="form-control fw-bold" min="0" value="1" required oninput="calcAddAvailability()">
                                        <small class="text-muted" style="font-size: 10px;">Manually entered initial baseline stock</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-success mb-1">
                                            <i class="bi bi-plus-circle-fill me-1"></i>Received Quantity <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" name="received_quantity" id="add_received_quantity" class="form-control fw-bold border-success" min="0" value="0" required oninput="calcAddAvailability()">
                                        <small class="text-muted" style="font-size: 10px;">Newly received / logged amount</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-primary mb-1">
                                            <i class="bi bi-calculator-fill me-1"></i>Current Availability (Auto)
                                        </label>
                                        <input type="number" name="available_quantity" id="add_available_quantity" class="form-control fw-bold bg-white text-primary border-primary fs-5" readonly value="1">
                                        <small class="text-primary fw-semibold" style="font-size: 10px;"><i class="bi bi-check2-circle me-1"></i>Baseline + Received Amount</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Condition <span class="text-danger">*</span></label>
                            <select name="current_condition" id="add_current_condition" class="form-select" required>
                                <option value="Good" selected>Good</option>
                                <option value="Fair">Fair</option>
                                <option value="Damaged">Damaged</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Specification / Remarks <span class="text-muted">(Brand, Model, Specs)</span></label>
                            <input type="text" name="specification" class="form-control" placeholder="e.g. Brand: Panasonic, Model: CS-1800, Inverter 18000 BTU">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Additional Notes / Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Any additional internal tracking comments..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-dark">Save Inventory Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function calcAddAvailability() {
    var base = parseInt(document.getElementById('add_initial_count').value) || 0;
    var recv = parseInt(document.getElementById('add_received_quantity').value) || 0;
    document.getElementById('add_available_quantity').value = base + recv;
}
</script>