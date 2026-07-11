<div class="modal fade" id="editDailyDiaryModal" tabindex="-1" aria-labelledby="editDailyDiaryLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white py-3" style="background-color:#a07174;">
                <h6 class="modal-title fw-bold" id="editDailyDiaryLabel"><i class="bi bi-pencil-square me-2"></i>Edit Daily Diary Entry</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditProg" action="processors/update_daily_diary.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date</label>
                            <input type="date" name="started_date" id="edit_date" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Year</label>
                            <input type="number" name="year" id="edit_year" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Activity Description</label>
                            <input type="text" name="programme_type" id="edit_type" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Location / Place</label>
                            <input type="text" name="location" id="edit_location" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Duration(s)</label>
                            <div id="duration-container-edit">
                                <!-- Will be dynamically generated from record time_duration CSV -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light border-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm text-white fw-bold" style="background-color:#a07174;"><i class="bi bi-save me-1"></i>Update Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>
