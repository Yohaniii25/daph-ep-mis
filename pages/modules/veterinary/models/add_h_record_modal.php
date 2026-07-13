<div class="modal fade" id="addHRecordModal" tabindex="-1" aria-labelledby="addHRecordLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color:#370709;">
                <h6 class="modal-title fw-bold" id="addHRecordLabel"><i class="bi bi-file-earmark-plus me-2"></i>Add H Record</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAddHRecord" action="processors/save_letter_h.php" method="POST">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Transaction Date</label>
                            <input type="date" name="transaction_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Transaction Type</label>
                            <select name="transaction_type" class="form-select form-select-sm" required>
                                <option value="Receipt">Receipt</option>
                                <option value="Disbursement">Disbursement</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Reference No.</label>
                            <input type="text" name="reference_no" class="form-control form-control-sm" placeholder="e.g. Receipt No (1030832) / Deposit Ref">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Particulars</label>
                            <input type="text" name="particulars" class="form-control form-control-sm" placeholder="e.g. Consultation, Ranikhet vaccine, Deposit to Bank" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Quantity (Optional)</label>
                            <input type="number" id="add_qty" name="quantity" class="form-control form-control-sm" placeholder="e.g. 1" min="1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Rate (Optional)</label>
                            <input type="number" id="add_rate" name="rate" class="form-control form-control-sm" placeholder="e.g. 150.00" step="0.01" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Amount (LKR)</label>
                            <input type="number" id="add_amount" name="amount" class="form-control form-control-sm" placeholder="e.g. 150.00" step="0.01" min="0" required>
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
    const qtyInput = document.getElementById('add_qty');
    const rateInput = document.getElementById('add_rate');
    const amountInput = document.getElementById('add_amount');

    function calculateAmount() {
        const qty = parseFloat(qtyInput.value) || 0;
        const rate = parseFloat(rateInput.value) || 0;
        if (qty > 0 && rate > 0) {
            amountInput.value = (qty * rate).toFixed(2);
        }
    }

    qtyInput.addEventListener('input', calculateAmount);
    rateInput.addEventListener('input', calculateAmount);
});
</script>
