<div class="modal fade" id="salesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="salesForm" class="modal-content border-0 shadow" action="processors/sales_crud.php" method="POST">
            <input type="hidden" name="action" id="modalAction" value="create">
            <input type="hidden" name="id" id="saleId" value="">
            
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold" id="modalTitle"><i class="bi bi-cart-plus me-2"></i>Log New Sales Invoice</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Sales Dispatched Date</label>
                    <input type="date" name="sales_date" id="salesDate" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Egg Classification Category</label>
                    <select name="egg_category" id="eggCategory" class="form-select" required>
                        <option value="Table">Table Eggs (Premium Commercial)</option>
                        <option value="Cracked">Cracked Eggs (Discount Asset)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Quantity Sold (Units)</label>
                    <input type="number" name="quantity_sold" id="qtySold" class="form-control" placeholder="Enter quantity sold" min="1" required>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label fw-bold text-success">Actual Selling Rate (LKR)</label>
                        <input type="number" step="0.01" name="actual_rate" id="rateActual" class="form-control" placeholder="0.00" min="0.01" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold text-secondary">Hope/Target Rate (LKR)</label>
                        <input type="number" step="0.01" name="hope_rate" id="rateHope" class="form-control" placeholder="0.00" min="0.01" required>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-info text-white fw-bold px-4">Save Invoice</button>
            </div>
        </form>
    </div>
</div>