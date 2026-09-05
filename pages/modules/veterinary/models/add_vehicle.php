<div class="modal fade" id="addVehicleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #b08723;">
                <h5 class="modal-title"><i class="bi bi-truck-flatbed me-2"></i>Register Fleet Asset Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addVehicleForm" action="processors/save_vehicle.php" method="POST">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Unit <span class="text-danger">*</span></label>
                            <select name="unit" class="form-select" required>
                                <option value="">-- Select Unit --</option>
                                <option value="provincial_director">Provincial Director</option>
                                <option value="additional_provincial_director">Additional Provincial Director</option>
                                <option value="subject_matter_specialist">Subject Matter Specialist</option>
                                <option value="deputy_director_hq_1">Deputy Director - H/Q-1</option>
                                <option value="deputy_director_hq_2">Deputy Director - H/Q-2</option>
                                <option value="deputy_director_district">Deputy Director - District</option>
                                <option value="range_veterinary_officer" selected>Range Veterinary Officer</option>
                                <option value="training_centers">Training Centers</option>
                                <option value="regional_farms">Regional Farms</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Vehicle Type</label>
                            <select name="vehicle_type" class="form-select" required>
                                <option value="Motorbike">Motorbike</option>
                                <option value="Single Cab (4x4)">Single Cab (4x4)</option>
                                <option value="Double Cab">Double Cab</option>
                                <option value="Truck Logistics">Truck Logistics</option>
                                <option value="Van / Emergency Utility">Van / Emergency Utility</option>
                            </select>
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