<!-- pages/modules/farm/models/edit_batch_modal.php -->
<div class="modal fade" id="editBatchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" action="processors/save_batch.php" method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="edit_batch_num_id" name="id">

            <div class="modal-header bg-dark text-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Batch</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Batch Number / Code</label>
                    <input type="text" id="edit_batch_number" name="batch_number" class="form-control" placeholder="e.g. V-BATCH-01" required>
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning fw-bold px-4 text-dark">Update Batch</button>
            </div>
        </form>
    </div>
</div>