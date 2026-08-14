<!-- Edit Training Advance Programme Modal -->
<div class="modal fade" id="editAdvancedModal" tabindex="-1" aria-labelledby="editAdvancedLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color:#370709;">
                <h6 class="modal-title fw-bold" id="editAdvancedLabel"><i class="bi bi-pencil-square me-2"></i>Edit Training Advance Programme</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditProg" action="processors/update_advanced_programme.php" method="POST">
                <input type="hidden" name="training_center_id" value="<?= htmlspecialchars($current_center_id ?? '') ?>">
                <input type="hidden" name="id" id="edit_id">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="started_date" id="edit_date" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Year <span class="text-danger">*</span></label>
                            <input type="number" name="year" id="edit_year" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Programme Type / Topic <span class="text-danger">*</span></label>
                            <input type="text" name="programme_type" id="edit_type" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Location / Venue <span class="text-danger">*</span></label>
                            <input type="text" name="location" id="edit_location" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Duration(s) <span class="text-danger">*</span></label>
                            <div id="duration-container-edit">
                                <!-- Will be dynamically generated from record time_duration -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light border-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm text-light fw-bold" style="background-color:#370709;"><i class="bi bi-save me-1"></i>Update Programme</button>
                </div>
            </form>
        </div>
    </div>
</div>
