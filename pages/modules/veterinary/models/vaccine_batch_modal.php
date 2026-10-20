<div class="modal fade" id="addVaccineBatchModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background: linear-gradient(135deg, #370709 0%, #820100 100%);">
                <h5 class="modal-title fw-bold" id="modalTitle">
                    <i class="bi bi-box-seam me-2"></i>Register New Vaccine Stock Batch
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="batchForm" action="processors/vaccine_batch_crud.php" method="POST">
                <input type="hidden" id="modalAction" name="action" value="create">
                <input type="hidden" id="batchId" name="id" value="">
                <input type="hidden" id="batchReturnUrl" name="return_url" value="">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="batchNumber" class="form-label fw-semibold text-dark">Batch Identity / Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control fw-bold border-secondary" id="batchNumber" name="batch_number" placeholder="e.g. FMD-2026-B1" required>
                        </div>

                        <div class="col-12">
                            <label for="batchExpiryDate" class="form-label fw-semibold text-dark">Expiration Date</label>
                            <input type="date" class="form-control border-secondary" id="batchExpiryDate" name="expiry_date">
                            <small class="text-muted">Batch expiry date for auto-filling inventory forms.</small>
                        </div>

                        <div class="col-12">
                            <label for="is_active" class="form-label fw-semibold text-dark">Operational Availability Status</label>
                            <select class="form-select border-secondary" id="is_active" name="is_active">
                                <option value="1">Active / Usable in Stock Forms</option>
                                <option value="0">Archived / Disabled</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="remarks" class="form-label fw-semibold text-dark">Remarks / Manufacturer Notes</label>
                            <textarea class="form-control border-secondary" id="remarks" name="remarks" rows="2" placeholder="Manufacturer name, supplier, or delivery notes..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="submitBtn" class="btn text-light fw-bold px-4" style="background-color: #820100;">Save Batch</button>
                </div>
            </form>
        </div>
    </div>
</div>