<!-- pages/modules/farm/models/add_batch_modal.php -->
<div class="modal fade" id="addBatchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" action="processors/save_batch.php" method="POST">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-tags-fill me-2"></i>Add Batch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Batch Number / Code</label>
                    <input type="text" name="batch_number" class="form-control" placeholder="e.g. V-BATCH-01" required>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning fw-bold px-4 text-dark">Save Batch</button>
            </div>
        </form>
    </div>
</div>