<div class="modal fade" id="addVehicleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #b08723;">
                <h5 class="modal-title"><i class="bi bi-truck-flatbed me-2"></i>Register Fleet Asset Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addVehicleForm" action="processors/save_vehicle.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="unit" value="range_veterinary_officer">
                    <div class="row g-3">
                        <div class="col-md-6 position-relative">
                            <label class="form-label small fw-bold">Vehicle Type <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-truck text-muted"></i></span>
                                <input type="text" 
                                       name="vehicle_type" 
                                       id="add_vehicle_type" 
                                       class="form-control" 
                                       placeholder="Type or select type (e.g. Tractor, Trailer, Single Cab)..." 
                                       autocomplete="off" 
                                       required>
                            </div>
                            <div id="add_vehicle_type_suggestions" class="dropdown-menu w-100 shadow border-0 mt-1 py-1" style="display: none; position: absolute; z-index: 1060; max-height: 220px; overflow-y: auto;"></div>
                            <small class="text-muted" style="font-size: 11px;">
                                <i class="bi bi-magic me-1"></i>Auto-suggests from saved fleet types. Custom equipment allowed.
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Vehicle Number (License Plate)</label>
                            <input type="text" name="vehicle_number" class="form-control font-monospace" placeholder="e.g. WP BCX-8452" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Chassis Number / Frame ID</label>
                            <input type="text" name="chassis_number" class="form-control font-monospace" placeholder="e.g. MJD32A10984321X" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Current Mechanical Condition</label>
                            <select name="current_condition" class="form-select" required>
                                <option value="Running">Running (Good)</option>
                                <option value="Needs Repair">Needs Repair</option>
                                <option value="Inactive / Out of Commission">Inactive / Out of Commission</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Other Relevant Details / Assignments</label>
                            <textarea name="other_details" class="form-control" rows="2" placeholder="e.g. Assigned to Field Officer. Heavy load rear leaf springs installed..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn text-white" style="background-color: #b08723;">Save Fleet Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>