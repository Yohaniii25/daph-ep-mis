<!-- Modal: Log / Update Monthly Ear Tag Stock Balances (Received, Spoiled, Damaged, Transferred, Opening) -->
<div class="modal fade" id="modalStockAdjustment" tabindex="-1" aria-labelledby="modalStockAdjustmentLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color: #370709;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-tag-fill fs-5 text-warning"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="modalStockAdjustmentLabel">Ear Tag Monthly Stock Adjustment &amp; Spoilage Tracking</h6>
                        <small class="text-white-50">Log receipts, transfers, and track spoiled and damaged ear tag inventory</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="formStockAdjustment" action="processors/save_ear_tag_stock.php" method="POST">
                <input type="hidden" name="stock_id" id="stock_id" value="0">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Report Year</label>
                            <input type="number" name="report_year" id="stock_year" class="form-control form-control-sm" value="<?= date('Y') ?>" min="2000" max="2099" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Report Month</label>
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

                        <!-- Opening Balance -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Opening Balance</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-box-seam"></i></span>
                                <input type="number" name="opening_balance" id="stock_opening_balance" class="form-control stock-calc-trigger" value="0" min="0" required>
                            </div>
                            <small class="text-muted" style="font-size: 0.72rem;">Baseline stock available at start of month</small>
                        </div>

                        <!-- Received Quantity -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-success">Received Quantity (+)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-success-subtle text-success"><i class="bi bi-plus-lg"></i></span>
                                <input type="number" name="received_qty" id="stock_received_qty" class="form-control stock-calc-trigger text-success fw-bold" value="0" min="0" required>
                            </div>
                            <small class="text-muted" style="font-size: 0.72rem;">New ear tag shipments received from HQ/District</small>
                        </div>

                        <!-- Spoiled Units (During Tagging) -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-danger">Spoiled Ear Tags (-)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-danger-subtle text-danger"><i class="bi bi-exclamation-triangle-fill"></i></span>
                                <input type="number" name="spoilt_qty" id="stock_spoilt_qty" class="form-control stock-calc-trigger text-danger fw-bold" value="0" min="0" required>
                            </div>
                            <small class="text-muted" style="font-size: 0.72rem;">Tags spoiled/misapplied during tagging operations</small>
                        </div>

                        <!-- Damaged Units (Defective / In Storage) -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-danger">Damaged Ear Tags (-)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-danger-subtle text-danger"><i class="bi bi-x-octagon-fill"></i></span>
                                <input type="number" name="damaged_qty" id="stock_damaged_qty" class="form-control stock-calc-trigger text-danger fw-bold" value="0" min="0" required>
                            </div>
                            <small class="text-muted" style="font-size: 0.72rem;">Defective pins, damaged in transit or broken in storage</small>
                        </div>

                        <!-- Transferred Quantity -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-warning">Transferred Quantity (-)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-warning-subtle text-warning"><i class="bi bi-arrow-right"></i></span>
                                <input type="number" name="transferred_qty" id="stock_transferred_qty" class="form-control stock-calc-trigger text-warning fw-bold" value="0" min="0" required>
                            </div>
                            <small class="text-muted" style="font-size: 0.72rem;">Stock transferred to neighboring veterinary ranges</small>
                        </div>

                        <!-- Auto-Calculated Used Indicator -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-info">Used Ear Tags (System Synced)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-info-subtle text-info"><i class="bi bi-check-circle-fill"></i></span>
                                <input type="number" name="used_qty" id="stock_used_qty" class="form-control bg-light text-info fw-bold" value="0" readonly>
                            </div>
                            <small class="text-muted" style="font-size: 0.72rem;">Auto-pulled real-time from Animal Identification grid</small>
                        </div>

                        <!-- Calculated Projected Ending Balance Card -->
                        <div class="col-12 mt-3">
                            <div class="p-3 rounded border bg-light d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="small fw-bold text-muted text-uppercase d-block">Projected Month Ending Balance</span>
                                    <small class="text-muted">Formula: (Opening + Received) - (Used + Spoiled + Damaged + Transferred)</small>
                                </div>
                                <div class="text-end">
                                    <span class="h4 fw-bold font-monospace text-dark mb-0" id="stock_calc_ending_preview">0</span>
                                    <small class="text-muted d-block">Units</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="alert alert-secondary py-2 px-3 mb-0 small">
                                <i class="bi bi-info-circle-fill text-primary me-1"></i>
                                <span><strong>Used Quantity</strong> is pulled directly and automatically from the Animal Identification system registry. Spoiled and Damaged counts are recorded separately for accurate audit compliance.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-sm btn-success px-4 fw-bold">
                        <i class="bi bi-check2-circle me-1"></i> Save Stock Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
