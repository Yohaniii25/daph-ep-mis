<!-- pages/modules/farm/models/add_cage_modal.php -->
<div class="modal fade" id="addCageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" action="processors/save_cage.php" method="POST">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-grid-3x3 me-2"></i>Add Cage</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Cage Name</label>
                    <input type="text" name="cage_name" class="form-control" placeholder="e.g. Cage 01" required>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success fw-bold px-4">Save Cage</button>
            </div>
        </form>
    </div>
</div>