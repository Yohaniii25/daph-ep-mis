<div class="modal fade" id="addVaccineBatchModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light text-white py-3">
                <h5 class="modal-title" id="modalTitle">
                    <i class="bi bi-box-seam me-2"></i>Register New Vaccine Stock Batch
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="batchForm" action="processors/vaccine_batch_crud.php" method="POST">
                <input type="hidden" id="modalAction" name="action" value="create">
                <input type="hidden" id="batchId" name="id" value="">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="batchNumber" class="form-label fw-semibold text-secondary">Batch Identity / Number</label>
                            <input type="text" class="form-control fw-bold" id="batchNumber" name="batch_number" placeholder="e.g. FMD-2026-B1" required>
                        </div>

                        <div class="col-12">
                            <label for="is_active" class="form-label fw-semibold text-secondary">Operational Availability Status</label>
                            <select class="form-select" id="is_active" name="is_active">
                                <option value="1">Active / Usable in Stock Forms</option>
                                <option value="0">Archived / Disabled</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="remarks" class="form-label fw-semibold text-secondary">Remarks / Structural Descriptions</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="3" placeholder="Add details like manufacturer name or delivery notes here..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="submitBtn" class="btn btn-success fw-bold px-4">Save Configuration</button>
                </div>
            </form>
        </div>
    </div>
</div>