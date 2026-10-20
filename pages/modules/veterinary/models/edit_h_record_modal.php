<div class="modal fade" id="editHRecordModal" tabindex="-1" aria-labelledby="editHRecordLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color:#370709;">
                <h6 class="modal-title fw-bold" id="editHRecordLabel"><i class="bi bi-pencil-square me-2"></i>Edit H Record</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditHRecord" action="processors/update_letter_h.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Transaction Date</label>
                            <input type="date" name="transaction_date" id="edit_transaction_date" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Transaction Type</label>
                            <select name="transaction_type" id="edit_transaction_type" class="form-select form-select-sm" required>
                                <option value="Receipt">Receipt</option>
                                <option value="Disbursement">Disbursement</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Reference No.</label>
                            <input type="text" name="reference_no" id="edit_reference_no" class="form-control form-control-sm" placeholder="e.g. Receipt No / Deposit Ref">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Particulars</label>
                            <input type="text" name="particulars" id="edit_particulars" class="form-control form-control-sm" placeholder="e.g. Particulars" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Quantity (Optional)</label>
                            <input type="number" id="edit_qty" name="quantity" class="form-control form-control-sm" placeholder="e.g. 1" min="1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Rate (Optional)</label>
                            <input type="number" id="edit_rate" name="rate" class="form-control form-control-sm" placeholder="e.g. 150.00" step="0.01" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Amount (LKR)</label>
                            <input type="number" id="edit_amount" name="amount" class="form-control form-control-sm" placeholder="e.g. 150.00" step="0.01" min="0" required>
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
    const qtyInput = document.getElementById('edit_qty');
    const rateInput = document.getElementById('edit_rate');
    const amountInput = document.getElementById('edit_amount');

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
