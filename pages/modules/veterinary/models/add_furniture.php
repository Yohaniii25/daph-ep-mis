<div class="modal fade" id="addFurnitureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #a07174;">
                <h5 class="modal-title"><i class="bi bi-file-earmark-plus me-2"></i>Log Furniture Asset Log</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addFurnitureForm">
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
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Furniture Classification Type</label>
                            <input type="text" name="furniture_type" class="form-control" placeholder="e.g. Wooden Executive Desk, 4-Drawer Steel Cabinet" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Available Quantity</label>
                            <input type="number" name="available_quantity" class="form-control" min="1" value="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date Received / Purchased</label>
                            <input type="date" name="date_received" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Current Physical Condition</label>
                            <select name="current_condition" class="form-select" required>
                                <option value="Excellent">Excellent</option>
                                <option value="Good">Good</option>
                                <option value="Fair">Fair</option>
                                <option value="Damaged">Damaged</option>
                                <option value="Unserviceable">Unserviceable</option>
                            </select>
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