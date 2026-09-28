<div class="modal fade" id="editCropReturnsModal" tabindex="-1" aria-labelledby="editCropReturnsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color:#370709;">
                <h6 class="modal-title fw-bold" id="editCropReturnsLabel"><i class="bi bi-pencil-square me-2"></i>Edit Crop Return Record</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditCropReturn" action="processors/update_crop_return.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Report Year</label>
                            <input type="number" name="report_year" id="edit_report_year" class="form-control form-control-sm" min="2000" max="2099" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Report Month</label>
                            <select name="report_month" id="edit_report_month" class="form-select form-select-sm" required>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>"><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Item Name</label>
                            <input type="text" name="item_name" id="edit_item_name" class="form-control form-control-sm fw-bold bg-light" readonly required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Balance Previous Month</label>
                            <input type="number" id="edit_prev_bal" name="balance_previous_month" class="form-control form-control-sm" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Current Month Received</label>
                            <input type="number" id="edit_received" name="received_current_month" class="form-control form-control-sm" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Current Month Issued</label>
                            <input type="number" id="edit_issued" name="issued_current_month" class="form-control form-control-sm" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Calculated Balance</label>
                            <input type="number" id="edit_current_bal" name="balance_current_month" class="form-control form-control-sm bg-light fw-bold" readonly required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Remarks</label>
                            <textarea name="remark" id="edit_remark" class="form-control form-control-sm" rows="1"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light border-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm text-light fw-bold" style="background-color:#370709;"><i class="bi bi-save me-1"></i>Update Record</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    function recalcEditModal() {
        const prev = parseInt(document.getElementById('edit_prev_bal').value) || 0;
        const rec = parseInt(document.getElementById('edit_received').value) || 0;
        const iss = parseInt(document.getElementById('edit_issued').value) || 0;
        document.getElementById('edit_current_bal').value = prev + rec - iss;
    }
    const prevEl = document.getElementById('edit_prev_bal');
    const recEl = document.getElementById('edit_received');
    const issEl = document.getElementById('edit_issued');
    if (prevEl) prevEl.addEventListener('input', recalcEditModal);
    if (recEl) recEl.addEventListener('input', recalcEditModal);
    if (issEl) issEl.addEventListener('input', recalcEditModal);
});
</script>
