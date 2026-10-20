<div class="modal fade" id="editDailyDiaryModal" tabindex="-1" aria-labelledby="editDailyDiaryLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white py-3" style="background-color:#820100;">
                <h6 class="modal-title fw-bold" id="editDailyDiaryLabel"><i class="bi bi-pencil-square me-2"></i>Edit Daily Diary Entry (Work Done)</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditDailyDiary" action="processors/update_daily_diary.php" method="POST">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="year" id="edit_year" value="<?= htmlspecialchars($selected_year) ?>">
                <input type="hidden" name="month" id="edit_month" value="<?= htmlspecialchars($selected_month) ?>">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" id="edit_date" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Distance <span class="text-danger">*</span></label>
                            <input type="text" name="distance" id="edit_distance" class="form-control form-control-sm" placeholder="e.g. 54KM, 63KM, 45Km" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Duty Time <span class="text-danger">*</span></label>
                            <input type="text" name="duty_time" id="edit_duty_time" class="form-control form-control-sm" placeholder="e.g. 8.35 – 12.11, 12.11 – 7.00" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Work Description <span class="text-danger">*</span></label>
                            <input type="text" name="task" id="edit_task" class="form-control form-control-sm" placeholder="e.g. Office/Field Works, World Milk day, Mobile Clinic" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Work Place / Visiting Place <span class="text-danger">*</span></label>
                            <input type="text" name="place" id="edit_place" class="form-control form-control-sm" placeholder="e.g. GVSO, Kanniya, Illupaikulam, Chinabay Filed, PD Office" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light border-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm text-white fw-bold" style="background-color:#820100;"><i class="bi bi-save me-1"></i>Update Diary Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>
