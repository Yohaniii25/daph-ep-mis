<div class="modal fade" id="addCropReturnsModal" tabindex="-1" aria-labelledby="addCropReturnsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color:#370709;">
                <h6 class="modal-title fw-bold" id="addCropReturnsLabel"><i class="bi bi-file-earmark-plus me-2"></i>Add Crop Return Item Record</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAddCropReturn" action="processors/save_crop_return.php" method="POST">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Report Year</label>
                            <input type="number" name="report_year" class="form-control form-control-sm" value="<?= htmlspecialchars($selected_year) ?>" min="2000" max="2099" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Report Month</label>
                            <select name="report_month" class="form-select form-select-sm" required>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= ($selected_month == $m) ? 'selected' : '' ?>>
                                        <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Standard Item Name <span class="text-danger">*</span></label>
                            <select name="item_name" class="form-select form-select-sm" required>
                                <option value="" disabled selected>-- Select from 20 Standard Items --</option>
                                <?php foreach ($standard_items as $std_item): ?>
                                    <option value="<?= htmlspecialchars($std_item) ?>"><?= htmlspecialchars($std_item) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Balance Previous Month</label>
                            <input type="number" id="add_prev_bal" name="balance_previous_month" class="form-control form-control-sm" value="0" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Current Month Received</label>
                            <input type="number" id="add_received" name="received_current_month" class="form-control form-control-sm" value="0" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Current Month Issued</label>
                            <input type="number" id="add_issued" name="issued_current_month" class="form-control form-control-sm" value="0" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Calculated Balance</label>
                            <input type="number" id="add_current_bal" name="balance_current_month" class="form-control form-control-sm bg-light fw-bold" value="0" readonly required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Remark</label>
                            <input type="text" name="remark" class="form-control form-control-sm" placeholder="Optional notes or supplier details">
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light border-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm text-light fw-bold" style="background-color:#370709;"><i class="bi bi-save me-1"></i>Save Record</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    function recalcAddModal() {
        const prev = parseInt(document.getElementById('add_prev_bal').value) || 0;
        const rec = parseInt(document.getElementById('add_received').value) || 0;
        const iss = parseInt(document.getElementById('add_issued').value) || 0;
        document.getElementById('add_current_bal').value = prev + rec - iss;
    }
    const prevEl = document.getElementById('add_prev_bal');
    const recEl = document.getElementById('add_received');
    const issEl = document.getElementById('add_issued');
    if (prevEl) prevEl.addEventListener('input', recalcAddModal);
    if (recEl) recEl.addEventListener('input', recalcAddModal);
    if (issEl) issEl.addEventListener('input', recalcAddModal);
});
</script>
