<div class="modal fade" id="addTrainingModal" tabindex="-1" aria-labelledby="addTrainingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="#" method="POST">
                <input type="hidden" name="action" value="save_training_program">
                
                <div class="modal-header bg-light text-white py-3">
                    <h5 class="modal-title fw-bold" id="addTrainingModalLabel">
                        <i class="bi bi-plus-circle me-2 text-success"></i>Create New Training Program
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-close="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-secondary">Target Group Allocation Profile <span class="text-danger">*</span></label>
                            <select class="form-select border-2" name="target_group_id" required>
                                <option value="" disabled selected>-- Choose Target Profile Group Reference --</option>
                                <option value="1">Ampara Dairy Operators (TG-041)</option>
                                <option value="2">Trincomalee Poultry Holders (TG-098)</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-secondary">Course Details / Topic Allocation Description <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="allocation_details" placeholder="e.g., Modern Milking Hygiene & Quality Parameters" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Training Hall Venue Location <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="location" placeholder="e.g., Hall A, Main Center" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Duration (Days) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" min="1" class="form-control" name="duration_days" value="1" required>
                                <span class="input-group-text bg-light text-muted small">Days</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Confirmed Farmer Count <span class="text-danger">*</span></label>
                            <input type="number" min="0" class="form-control" name="participants_count" placeholder="e.g., 25" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Initial System Status Block <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="Ongoing" selected>Ongoing Session</option>
                                <option value="Completed">Completed Allocation</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light py-3">
                    <button type="button" class="btn btn-secondary px-4" data-bs-close="modal">Cancel</button>
                    <button type="button" class="btn btn-success px-4 fw-bold shadow-sm" data-bs-close="modal">Save Allocation (Demo)</button>
                </div>
            </form>
        </div>
    </div>
</div>