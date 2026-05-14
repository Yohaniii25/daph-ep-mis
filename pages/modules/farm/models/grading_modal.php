<div class="modal fade" id="gradingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="gradingForm" class="modal-content border-0 shadow" action="processors/hatchery_crud.php" method="POST">
            <input type="hidden" name="action" id="modalAction" value="create">
            <input type="hidden" name="id" id="batchId" value="">
            
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="modalTitle"><i class="bi bi-egg-fried me-2"></i>Log Grading & Collection</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Collection & Setting Date</label>
                    <input type="date" name="batch_date" id="batchDate" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="card bg-light border-0 p-3 mb-3">
                    <h6 class="fw-bold text-secondary mb-3">Egg Distribution Metrics</h6>
                    <div class="row g-2">
                        <div class="col-4">
                            <label class="form-label small fw-bold text-success">Hatchable</label>
                            <input type="number" name="hatchable_count" id="qtyHatchable" class="form-control" value="0" min="0" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold text-danger">Cracked</label>
                            <input type="number" name="cracked_count" id="qtyCracked" class="form-control" value="0" min="0" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-bold text-primary">Table</label>
                            <input type="number" name="table_count" id="qtyTable" class="form-control" value="0" min="0" required>
                        </div>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-bold text-dark">Chicks Hatched (Output Counter)</label>
                    <input type="number" name="chicks_hatched" id="qtyChicks" class="form-control" placeholder="Leave empty if still incubating" min="0">
                    <div class="form-text text-muted">Update this field once incubation is complete to calculate your success rate.</div>
                </div>
            </div>
            
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success fw-bold px-4">Save Entry</button>
            </div>
        </form>
    </div>
</div>