<!-- pages/modules/farm/models/edit_cage_modal.php -->
<div class="modal fade" id="editCageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" action="processors/save_cage.php" method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="edit_cage_id" name="id">

            <div class="modal-header bg-dark text-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Cage</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Cage Name</label>
                    <input type="text" id="edit_cage_name" name="cage_name" class="form-control" placeholder="e.g. Cage 01" required>
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success fw-bold px-4">Update Cage</button>
            </div>
        </form>
    </div>
</div>