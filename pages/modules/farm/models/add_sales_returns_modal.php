<!-- pages/modules/farm/models/add_sales_returns_modal.php -->
<div class="modal fade" id="salesReturnsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content border-0 shadow" action="processors/save_sales_returns.php" method="POST">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-cart-check-fill me-2"></i>Log Daily Farm Sales & Returns
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Select Record Date</label>
                        <input type="date" id="sales_record_date" name="record_date" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                        <small class="text-muted">Sales and Hatchery Returns apply to the whole farm for this specific date.</small>
                    </div>

                    <!-- Hatchery Returns Section -->
                    <div class="col-12"><hr class="my-2"></div>
                    <div class="col-md-12">
                        <h6 class="fw-bold text-primary"><i class="bi bi-arrow-return-left me-1"></i>Hatchery Returns</h6>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Hatchery Return Quantity (NO)</label>
                        <input type="number" id="hatchery_return_no" name="hatchery_return_no" class="form-control" placeholder="0" min="0" value="0" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Hatchery Return Weight (Kg)</label>
                        <input type="number" id="hatchery_return_kg" name="hatchery_return_kg" class="form-control" placeholder="0.00" step="0.01" min="0" value="0.00" required>
                    </div>

                    <!-- Total Sales Section -->
                    <div class="col-12"><hr class="my-2"></div>
                    <div class="col-md-12">
                        <h6 class="fw-bold text-success"><i class="bi bi-cash-stack me-1"></i>Total Farm Sales</h6>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Total Sales Quantity (NO)</label>
                        <input type="number" id="total_sales_no" name="total_sales_no" class="form-control" placeholder="0" min="0" value="0" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Total Sales Weight (Kg)</label>
                        <input type="number" id="total_sales_kg" name="total_sales_kg" class="form-control" placeholder="0.00" step="0.01" min="0" value="0.00" required>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success fw-bold px-4">
                    <i class="bi bi-check-circle-fill me-1"></i>Save Sales & Returns
                </button>
            </div>
        </form>
    </div>
</div>
