<!-- Premium Edit Building Inventory Modal -->
<div class="modal fade" id="editInventoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header text-white border-0 py-3 px-4" style="background: linear-gradient(135deg, #820100 0%, #4a0000 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.15); width: 44px; height: 44px;">
                        <i class="bi bi-pencil-square fs-4 text-white"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Edit Building Inventory Item</h5>
                        <small class="text-white-50" style="font-size: 12px;">Audited Asset Tracking &amp; Lifecycle Management</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Form -->
            <form id="editInventoryForm" action="processors/update_building_inventory.php" method="POST">
                <input type="hidden" name="id" id="edit_inventory_id">
                <input type="hidden" name="land_asset_id" id="edit_land_asset_id">
                <input type="hidden" name="unit" id="edit_inventory_unit" value="range_veterinary_officer">

                <div class="modal-body p-4 bg-light-subtle">
                    <!-- SECTION 1: Item Identification -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-body p-3">
                            <h6 class="text-uppercase fw-bold text-muted small mb-3" style="letter-spacing: 0.5px;">
                                <i class="bi bi-tag-fill me-1 text-primary"></i> Asset Identification &amp; Station
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-7 position-relative">
                                    <label class="form-label small fw-bold text-dark">
                                        Inventory Item Name <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-box-seam"></i></span>
                                        <input type="text" name="inventory_item" id="edit_inventory_item" class="form-control border-start-0" placeholder="e.g. Executive Desk, Centrifuge" autocomplete="off" required>
                                    </div>
                                    <div id="edit_inventory_item_suggestions" class="dropdown-menu w-100 shadow border-0 mt-1 py-1" style="display: none; position: absolute; z-index: 1060; max-height: 220px; overflow-y: auto;"></div>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold text-dark">
                                        Assigned Location <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-geo-alt"></i></span>
                                        <select name="location" id="edit_inventory_location" class="form-select border-start-0 shadow-none" required>
                                            <option value="Office">Office</option>
                                            <option value="Quarters">Quarters</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label small fw-bold text-dark">Item Specification</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-card-text"></i></span>
                                        <input type="text" name="specification" id="edit_specification" class="form-control border-start-0" placeholder="Model, dimensions, serial number, material, etc.">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: Fiscal Baseline & Active Count Cards -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-body p-3">
                            <h6 class="text-uppercase fw-bold text-muted small mb-3" style="letter-spacing: 0.5px;">
                                <i class="bi bi-calculator me-1 text-success"></i> Inventory Audit Counts
                            </h6>
                            <div class="row g-3">
                                <!-- Baseline Card -->
                                <div class="col-md-6">
                                    <div class="p-3 rounded-3 border bg-white h-100 position-relative">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label small fw-bold text-secondary mb-0">
                                                <i class="bi bi-lock-fill me-1 text-warning"></i>Fiscal Baseline Count <span class="text-danger">*</span>
                                            </label>
                                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 small">Annual Audit</span>
                                        </div>
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-light text-secondary border-end-0 fw-bold">#</span>
                                            <input type="number" name="initial_count" id="edit_initial_count" class="form-control border-start-0 fw-bold fs-5 text-dark" min="0" required>
                                        </div>
                                        <small class="text-muted d-block mt-2" style="font-size: 11px;">
                                            <i class="bi bi-info-circle me-1"></i>Static baseline count recorded at the start of the fiscal year.
                                        </small>
                                    </div>
                                </div>

                                <!-- Active Available Card -->
                                <div class="col-md-6">
                                    <div class="p-3 rounded-3 border bg-white h-100 position-relative">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label small fw-bold text-dark mb-0">
                                                <i class="bi bi-boxes me-1 text-primary"></i>Available Active Qty <span class="text-danger">*</span>
                                            </label>
                                            <span class="badge bg-primary-subtle text-primary border px-2 py-1 small">Circulating</span>
                                        </div>
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-light text-primary border-end-0 fw-bold">#</span>
                                            <input type="number" name="available_quantity" id="edit_available_quantity" class="form-control border-start-0 fw-bold fs-5 text-primary" min="0" required>
                                        </div>
                                        <small class="text-muted d-block mt-2" style="font-size: 11px;">
                                            <i class="bi bi-check2-circle me-1 text-success"></i>Currently usable units located in this unit facility.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: Condition Status & Dynamic Deduction Alert -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-body p-3">
                            <h6 class="text-uppercase fw-bold text-muted small mb-3" style="letter-spacing: 0.5px;">
                                <i class="bi bi-shield-check me-1 text-danger"></i> Asset Condition
                            </h6>
                            <div class="row g-2">
                                <div class="col-md-12">
                                    <label class="form-label small fw-bold text-dark">
                                        Operational Condition <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-activity"></i></span>
                                        <select name="current_condition" id="edit_current_condition" class="form-select border-start-0 shadow-none fw-semibold" required>
                                            <option value="Good" class="text-success fw-bold">&#9679; Good (Fully Functional)</option>
                                            <option value="Fair" class="text-warning fw-bold">&#9679; Fair (Usable / Minor Wear)</option>
                                            <option value="Damaged" class="text-danger fw-bold">&#9679; Damaged (Automated -1 Deduction)</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Dynamic Damaged Deduction Notice -->
                                <div class="col-md-12">
                                    <div id="edit_condition_damaged_notice" class="alert alert-danger border-0 rounded-3 shadow-sm py-2 px-3 mt-2 d-none" style="display: none; background: #fee2e2;">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-exclamation-triangle-fill text-danger fs-5 flex-shrink-0"></i>
                                            <div class="small text-danger-emphasis">
                                                <strong>Automated Inventory Math:</strong> Condition updated to <em>Damaged</em>. <strong>1 unit</strong> has been automatically deducted from the active available quantity.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 4: Remarks / Audit Notes -->
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body p-3">
                            <label class="form-label small fw-bold text-dark mb-1">
                                <i class="bi bi-chat-left-text me-1 text-muted"></i>Additional Notes / Remarks
                            </label>
                            <textarea name="remarks" id="edit_remarks" class="form-control shadow-none" rows="2" placeholder="Record maintenance history, defects, or specific placement details..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer bg-white border-top py-3 px-4 d-flex justify-content-between align-items-center">
                    <div class="small text-muted d-none d-sm-block">
                        <i class="bi bi-shield-lock me-1"></i>All modifications logged to statutory audit trail
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary px-3 fw-semibold rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn text-white px-4 fw-semibold rounded-pill shadow-sm" style="background: linear-gradient(135deg, #820100 0%, #5e0100 100%);">
                            <i class="bi bi-check2-circle me-1"></i> Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
