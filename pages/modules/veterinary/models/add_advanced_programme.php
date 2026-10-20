<div class="modal fade" id="addAdvancedModal" tabindex="-1" aria-labelledby="addAdvancedLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color:#370709;">
                <h6 class="modal-title fw-bold" id="addAdvancedLabel"><i class="bi bi-calendar-plus me-2"></i>Add Advance Program Schedule</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAddProg" action="processors/save_advanced_programme.php" method="POST">
                <input type="hidden" name="year" value="<?= htmlspecialchars($selected_year) ?>">
                <input type="hidden" name="month" value="<?= htmlspecialchars($selected_month) ?>">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control form-control-sm" required value="<?= ($selected_month > 0 ? sprintf('%04d-%02d-01', $selected_year, $selected_month) : date('Y-m-d')) ?>">
                            <div class="form-text small text-muted">Formatted on schedule as "01 Tue", "02 Wed", etc.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Duty Time <span class="text-danger">*</span></label>
                            <input type="text" name="duty_time" class="form-control form-control-sm" placeholder="e.g. 8.30 – 15.30 or 8.30 – 12.30" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Work Description <span class="text-danger">*</span></label>
                            <input type="text" name="task" class="form-control form-control-sm" placeholder="e.g. Ear tagging Program, Office Filed Work, Mobile Clinic" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Work Place / Visiting Place <span class="text-danger">*</span></label>
                            <input type="text" name="place" class="form-control form-control-sm" placeholder="e.g. GVSO, Velveri GVSO, Kilikunchumalai" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light border-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm text-light fw-bold" style="background-color:#370709;"><i class="bi bi-save me-1"></i>Save Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>