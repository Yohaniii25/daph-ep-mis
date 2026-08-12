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
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Vehicle Type</label>
                            <input type="text" name="vehicle_type" id="edit_vehicle_type" class="form-control" required>
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
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Other Relevant Details / Specifications</label>
                            <textarea name="other_details" id="edit_other_details" class="form-control" rows="2"></textarea>
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
