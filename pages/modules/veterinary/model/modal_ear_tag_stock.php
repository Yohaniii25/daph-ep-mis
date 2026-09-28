<!-- Modal: Log / Update Monthly Stock Balances (Received, Transferred, Opening) -->
<div class="modal fade" id="modalStockAdjustment" tabindex="-1" aria-labelledby="modalStockAdjustmentLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color: #370709;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-box-seam-fill fs-5 text-warning"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="modalStockAdjustmentLabel">Log Stock Movement / Balance</h6>
                        <small class="text-white-50">Manage stock receipts, transfers, and opening inventory</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="formStockAdjustment" action="processors/save_ear_tag_stock.php" method="POST">
                <input type="hidden" name="stock_id" id="stock_id" value="0">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Year</label>
                            <input type="number" name="report_year" id="stock_year" class="form-control form-control-sm" value="<?= date('Y') ?>" min="2000" max="2099" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Month</label>
                            <select name="report_month" id="stock_month" class="form-select form-select-sm" required>
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

                        <div class="col-12"><hr class="my-1 text-muted"></div>

                        <div class="col-12">
                            <label class="form-label small fw-bold text-dark">Opening Balance</label>
                            <input type="number" name="opening_balance" id="stock_opening_balance" class="form-control form-control-sm stock-calc-trigger" value="0" min="0" required>
                            <small class="text-muted" style="font-size: 0.72rem;">Baseline balance at the start of this month</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-success">Received Quantity (+)</label>
                            <input type="number" name="received_qty" id="stock_received_qty" class="form-control form-control-sm stock-calc-trigger" value="0" min="0" required>
                            <small class="text-muted" style="font-size: 0.72rem;">Stock received from District/HQ</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-warning">Transferred Quantity (-)</label>
                            <input type="number" name="transferred_qty" id="stock_transferred_qty" class="form-control form-control-sm stock-calc-trigger" value="0" min="0" required>
                            <small class="text-muted" style="font-size: 0.72rem;">Stock transferred to other ranges</small>
                        </div>

                        <div class="col-12">
                            <div class="alert alert-secondary py-2 px-3 mb-0 small">
                                <i class="bi bi-info-circle-fill text-primary me-1"></i>
                                <span><strong>Used</strong> and <strong>Spoiled</strong> quantities are automatically and dynamically aggregated from individual Tagging Programs.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-sm btn-success px-3 fw-bold">Save Stock Record</button>
                </div>
            </form>
        </div>
    </div>
</div>
