<div class="modal fade" id="editCashBookSummaryModal" tabindex="-1" aria-labelledby="editCashBookSummaryLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color:#370709;">
                <h6 class="modal-title fw-bold" id="editCashBookSummaryLabel"><i class="bi bi-pencil-square me-2"></i>Edit Cash Book Summary</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditCashBookSummary" action="processors/update_cash_book_summary.php" method="POST">
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
                                <option value="" disabled>-- Select Month --</option>
                                <option value="1">January</option>
                                <option value="2">February</option>
                                <option value="3">March</option>
                                <option value="4">April</option>
                                <option value="5">May</option>
                                <option value="6">June</option>
                                <option value="7">July</option>
                                <option value="8">August</option>
                                <option value="9">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Item Name / Description</label>
                            <input type="text" name="item_name" id="edit_item_name" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Quantity Sold</label>
                            <input type="number" id="edit_qty_sold" name="quantity_sold" class="form-control form-control-sm" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Unit Price (LKR)</label>
                            <input type="number" id="edit_unit_price" name="unit_price" class="form-control form-control-sm" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Total Amount (LKR)</label>
                            <input type="number" id="edit_total_amount" name="total_amount" class="form-control form-control-sm" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Amount Deposited to Bank (LKR)</label>
                            <input type="number" name="amount_deposited" id="edit_amount_deposited" class="form-control form-control-sm" step="0.01" min="0" required>
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
    const qtyInput = document.getElementById('edit_qty_sold');
    const priceInput = document.getElementById('edit_unit_price');
    const totalInput = document.getElementById('edit_total_amount');

    function calculateTotal() {
        const qty = parseInt(qtyInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        totalInput.value = (qty * price).toFixed(2);
    }

    qtyInput.addEventListener('input', calculateTotal);
    priceInput.addEventListener('input', calculateTotal);
});
</script>
