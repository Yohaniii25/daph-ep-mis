<div class="modal fade" id="addFurnitureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #a07174;">
                <h5 class="modal-title"><i class="bi bi-file-earmark-plus me-2"></i>Log Furniture Asset Log</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addFurnitureForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="unit" value="range_veterinary_officer">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Furniture Classification Type</label>
                            <input type="text" name="furniture_type" class="form-control" placeholder="e.g. Wooden Executive Desk, 4-Drawer Steel Cabinet" required>
                        </div>
                        <!-- Availability Auto-Calculation Block -->
                        <div class="col-md-12">
                            <div class="p-3 bg-light rounded border">
                                <div class="row g-3 align-items-center">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-secondary mb-1">
                                            <i class="bi bi-lock-fill me-1"></i>Initial Baseline Stock <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" name="initial_count" id="add_furniture_initial_count" class="form-control fw-bold" min="0" value="1" required oninput="calcAddFurnitureAvailability()">
                                        <small class="text-muted" style="font-size: 10px;">Manually entered baseline stock</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-success mb-1">
                                            <i class="bi bi-plus-circle-fill me-1"></i>Received Quantity <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" name="received_quantity" id="add_furniture_received_quantity" class="form-control fw-bold border-success" min="0" value="0" required oninput="calcAddFurnitureAvailability()">
                                        <small class="text-muted" style="font-size: 10px;">Newly received amounts logged</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-primary mb-1">
                                            <i class="bi bi-calculator-fill me-1"></i>Current Availability (Auto)
                                        </label>
                                        <input type="number" name="available_quantity" id="add_furniture_available_quantity" class="form-control fw-bold bg-white text-primary border-primary fs-5" readonly value="1">
                                        <small class="text-primary fw-semibold" style="font-size: 10px;"><i class="bi bi-check2-circle me-1"></i>Baseline + Received Amount</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date Received / Purchased</label>
                            <input type="date" name="date_received" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Condition <span class="text-danger">*</span></label>
                            <select name="current_condition" class="form-select" required>
                                <option value="Good" selected>Good</option>
                                <option value="Fair">Fair</option>
                                <option value="Damaged">Damaged</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Issue Order No.</label>
                            <input type="text" name="issue_order_no" class="form-control" placeholder="e.g. IO-2024-001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Received From</label>
                            <input type="text" name="received_from" class="form-control" placeholder="e.g. Central Provincial Stores / Donor">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Receipt No.</label>
                            <input type="text" name="receipt_no" class="form-control" placeholder="e.g. REC-88391">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Specification (Brand / Model)</label>
                            <input type="text" name="specification" class="form-control" placeholder="e.g. Damro Steel, Godrej Model X">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Placement Location / Additional Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. Main Consultation Room, Inventory Ledger Markings..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn text-white" style="background-color: #a07174;">Save Asset Item</button>
                </div>
            </form>
        </div>
    </div>
</div>