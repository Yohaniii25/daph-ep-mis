<div class="modal fade" id="addDailyDiaryModal" tabindex="-1" aria-labelledby="addDailyDiaryLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color:#370709;">
                <h6 class="modal-title fw-bold" id="addDailyDiaryLabel"><i class="bi bi-calendar-plus me-2"></i>Add Daily Diary Entry</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAddProg" action="processors/save_daily_diary.php" method="POST">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date</label>
                            <input type="date" name="started_date" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Year</label>
                            <input type="number" name="year" class="form-control form-control-sm" value="<?= isset($selected_year) ? htmlspecialchars($selected_year) : 2026 ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Activity Description</label>
                            <input type="text" name="programme_type" class="form-control form-control-sm" placeholder="e.g. Clinical treatment or farm visit" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Location / Place</label>
                            <input type="text" name="location" class="form-control form-control-sm" placeholder="e.g. Village center or range clinic" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Duration(s)</label>
                            <div id="duration-container-add">
                                <div class="input-group mb-1 duration-entry">
                                    <input type="text" name="duration[]" class="form-control form-control-sm" placeholder="e.g. 2 Hours or 09:00 AM - 11:00 AM" required>
                                    <button type="button" class="btn btn-sm btn-outline-success add-duration-btn"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light border-0">
                    <button type="button" class="btn pointer-cursor btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm text-light fw-bold" style="background-color:#370709;"><i class="bi bi-save me-1"></i>Save Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>
