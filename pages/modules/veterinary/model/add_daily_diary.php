<div class="modal fade" id="addDailyDiaryModal" tabindex="-1" aria-labelledby="addDailyDiaryLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color:#370709;">
                <h6 class="modal-title fw-bold" id="addDailyDiaryLabel"><i class="bi bi-calendar-plus me-2"></i>Add Daily Diary Entry (Work Done)</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAddDailyDiary" action="processors/save_daily_diary.php" method="POST">
                <input type="hidden" name="year" value="<?= htmlspecialchars($selected_year) ?>">
                <input type="hidden" name="month" value="<?= htmlspecialchars($selected_month) ?>">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control form-control-sm" required value="<?= ($selected_month > 0 ? sprintf('%04d-%02d-01', $selected_year, $selected_month) : date('Y-m-d')) ?>">
                            <div class="form-text small text-muted">Formatted as "01 Tue", etc.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Distance <span class="text-danger">*</span></label>
                            <input type="text" name="distance" class="form-control form-control-sm" placeholder="e.g. 54KM, 63KM, 45Km" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Duty Time <span class="text-danger">*</span></label>
                            <input type="text" name="duty_time" class="form-control form-control-sm" placeholder="e.g. 8.35 – 12.11, 12.11 – 7.00" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Work Description <span class="text-danger">*</span></label>
                            <input type="text" name="task" class="form-control form-control-sm" placeholder="e.g. Office/Field Works, World Milk day, Mobile Clinic" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Work Place / Visiting Place <span class="text-danger">*</span></label>
                            <input type="text" name="place" class="form-control form-control-sm" placeholder="e.g. GVSO, Kanniya, Illupaikulam, Chinabay Filed, PD Office" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light border-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm text-light fw-bold" style="background-color:#370709;"><i class="bi bi-save me-1"></i>Save Diary Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>
