<div class="modal fade" id="addCounterfoilModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #e67e22;">
                <h5 class="modal-title"><i class="bi bi-book me-2"></i>New Counterfoil Book Entry</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addCounterfoilForm">
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
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Type</label>
                            <input type="text" name="counterfoil_type" class="form-control" placeholder="e.g., General Receipt Books (General 172), Animal Transport Permit Books" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Condition</label>
                            <select name="current_condition" class="form-select" required>
                                <option value="New">New / Unused</option>
                                <option value="Half-Used">Partially Used</option>
                                <option value="Exhausted">Exhausted</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Available Quantity</label>
                            <input type="number" name="available_quantity" class="form-control" min="1" value="1" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Date of Purchase / Received</label>
                            <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="3" placeholder="Enter book serial number ranges (e.g., Serial No. 45001 - 45100) or dispatch log tracking metadata..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-white" style="background-color: #e67e22;">Save Counterfoil</button>
                </div>
            </form>
        </div>
    </div>
</div>