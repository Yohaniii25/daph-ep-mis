<div class="modal fade" id="editAdvancedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="processors/process_update_programme.php" method="POST">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Programme Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="programme_id" id="edit_prog_id">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Year</label>
                            <input type="text" id="edit_year" class="form-control bg-light" readonly>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-bold">Programme Type</label>
                            <input type="text" id="edit_type_name" class="form-control bg-light" readonly>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Location / Place</label>
                            <input type="text" name="place" id="edit_place" class="form-control" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="4"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_programme" class="btn btn-primary px-4">Update Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>