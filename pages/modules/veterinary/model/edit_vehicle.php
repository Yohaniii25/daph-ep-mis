<div class="modal fade" id="editVehicleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #b08723;">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Registered Vehicle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editVehicleForm" action="processors/update_vehicle.php" method="POST">
                <input type="hidden" name="id" id="edit_vehicle_id">
                <div class="modal-body p-4">
                    <input type="hidden" name="unit" id="edit_vehicle_unit" value="range_veterinary_officer">
                    <div class="row g-3">
                        <div class="col-md-6 position-relative">
                            <label class="form-label small fw-bold">Vehicle Type <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-truck text-muted"></i></span>
                                <input type="text" name="vehicle_type" id="edit_vehicle_type" class="form-control" placeholder="Type or select type..." autocomplete="off" required>
                            </div>
                            <div id="edit_vehicle_type_suggestions" class="dropdown-menu w-100 shadow border-0 mt-1 py-1" style="display: none; position: absolute; z-index: 1060; max-height: 220px; overflow-y: auto;"></div>
                            <small class="text-muted" style="font-size: 11px;">
                                <i class="bi bi-magic me-1"></i>Auto-suggests from saved fleet types. Custom equipment allowed.
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Vehicle Registration Number</label>
                            <input type="text" name="vehicle_number" id="edit_vehicle_number" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Chassis Number</label>
                            <input type="text" name="chassis_number" id="edit_chassis_number" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Current Physical Condition</label>
                            <select name="current_condition" id="edit_current_condition" class="form-select" required>
                                <option value="Running">Running</option>
                                <option value="Needs Repair">Needs Repair</option>
                                <option value="Under Repair">Under Repair</option>
                                <option value="Unserviceable">Unserviceable</option>
                            </select>
                        </div>
                        <!-- Availability Auto-Calculation Block -->
                        <div class="col-md-12">
                            <div class="p-3 bg-light rounded border">
                                <div class="row g-3 align-items-center">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-secondary mb-1">
                                            <i class="bi bi-lock-fill me-1"></i>Initial Baseline Stock <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" name="initial_count" id="edit_vehicle_initial_count" class="form-control fw-bold" min="0" value="1" required oninput="calcEditVehicleAvailability()">
                                        <small class="text-muted" style="font-size: 10px;">Manually entered baseline fleet</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-success mb-1">
                                            <i class="bi bi-plus-circle-fill me-1"></i>Received Quantity <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" name="received_quantity" id="edit_vehicle_received_quantity" class="form-control fw-bold border-success" min="0" value="0" required oninput="calcEditVehicleAvailability()">
                                        <small class="text-muted" style="font-size: 10px;">Newly received / allocated fleet</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-primary mb-1">
                                            <i class="bi bi-calculator-fill me-1"></i>Current Availability (Auto)
                                        </label>
                                        <input type="number" name="available_quantity" id="edit_vehicle_available_quantity" class="form-control fw-bold bg-white text-primary border-primary fs-5" readonly value="1">
                                        <small class="text-primary fw-semibold" style="font-size: 10px;"><i class="bi bi-check2-circle me-1"></i>Baseline + Received Amount</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Specification (Make / Model / Capacity)</label>
                            <input type="text" name="specification" id="edit_vehicle_specification" class="form-control" placeholder="e.g. Toyota Hilux 2.4L Diesel 4WD">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Issue Order No.</label>
                            <input type="text" name="issue_order_no" id="edit_vehicle_issue_order_no" class="form-control" placeholder="e.g. IO-2024-001">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Received From</label>
                            <input type="text" name="received_from" id="edit_vehicle_received_from" class="form-control" placeholder="e.g. Ministry Pool / Provincial Council">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Receipt No.</label>
                            <input type="text" name="receipt_no" id="edit_vehicle_receipt_no" class="form-control" placeholder="e.g. REC-77401">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Specification / Remarks / Assignments</label>
                            <textarea name="remarks" id="edit_vehicle_remarks" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn text-white" style="background-color: #b08723;">Update Vehicle Asset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function calcEditVehicleAvailability() {
    var base = parseInt(document.getElementById('edit_vehicle_initial_count').value) || 0;
    var recv = parseInt(document.getElementById('edit_vehicle_received_quantity').value) || 0;
    document.getElementById('edit_vehicle_available_quantity').value = base + recv;
}
</script>
