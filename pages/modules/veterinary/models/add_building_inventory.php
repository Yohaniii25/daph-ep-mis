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
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Assigned Location <span class="text-danger">*</span></label>
                            <select name="location" id="add_inventory_location" class="form-select" required>
                                <option value="" disabled selected>-- Select Location (Office / Quarters) --</option>
                                <option value="Office">Office</option>
                                <option value="Quarters">Quarters</option>
                            </select>
                        </div>
                        <div class="col-md-8 position-relative">
                            <label class="form-label small fw-bold">Inventory Item Name <span class="text-danger">*</span></label>
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
                                <i class="bi bi-magic me-1"></i>Auto-suggests from previously saved inventory items as you type.
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Total Initial Count (Fiscal Baseline) <span class="text-danger">*</span></label>
                            <input type="number" name="initial_count" id="add_initial_count" class="form-control" min="0" value="1" required>
                            <small class="text-muted" style="font-size: 11px;">Baseline count at start of fiscal year.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Current Available Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="available_quantity" id="add_available_quantity" class="form-control" min="0" value="1" required>
                            <small class="text-muted" style="font-size: 11px;">Active circulation count in facility.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Condition <span class="text-danger">*</span></label>
                            <select name="current_condition" id="add_current_condition" class="form-select" required>
                                <option value="" disabled selected>-- Select Condition --</option>
                                <option value="Good">Good</option>
                                <option value="Fair">Fair</option>
                                <option value="Damaged">Damaged</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Item Specification</label>
                            <input type="text" name="specification" class="form-control" placeholder="e.g. Panasonic Inverter, 12000 BTU">
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