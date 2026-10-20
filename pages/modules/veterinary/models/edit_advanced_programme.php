<div class="modal fade" id="editAdvancedModal" tabindex="-1" aria-labelledby="editAdvancedLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white py-3" style="background-color:#820100;">
                <h6 class="modal-title fw-bold" id="editAdvancedLabel"><i class="bi bi-pencil-square me-2"></i>Edit Advance Program Schedule</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditProg" action="processors/update_advanced_programme.php" method="POST">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="year" id="edit_year" value="<?= htmlspecialchars($selected_year) ?>">
                <input type="hidden" name="month" id="edit_month" value="<?= htmlspecialchars($selected_month) ?>">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" id="edit_date" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Duty Time <span class="text-danger">*</span></label>
                            <input type="text" name="duty_time" id="edit_duty_time" class="form-control form-control-sm" placeholder="e.g. 8.30 – 15.30 or 8.30 – 12.30" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Work Description <span class="text-danger">*</span></label>
                            <input type="text" name="task" id="edit_task" class="form-control form-control-sm" placeholder="e.g. Ear tagging Program, Office Filed Work, Mobile Clinic" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Work Place / Visiting Place <span class="text-danger">*</span></label>
                            <input type="text" name="place" id="edit_place" class="form-control form-control-sm" placeholder="e.g. GVSO, Velveri GVSO, Kilikunchumalai" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light border-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm text-white fw-bold" style="background-color:#820100;"><i class="bi bi-save me-1"></i>Update Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>