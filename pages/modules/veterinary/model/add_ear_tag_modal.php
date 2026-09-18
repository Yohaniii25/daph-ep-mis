<div class="modal fade" id="addEarTagModal" tabindex="-1" aria-labelledby="addEarTagLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color:#370709;">
                <h6 class="modal-title fw-bold" id="addEarTagLabel"><i class="bi bi-file-earmark-plus me-2"></i>Add Ear Tag Returns</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAddEarTag" action="processors/ear_tag_crud.php" method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Report Year</label>
                            <input type="number" name="report_year" class="form-control form-control-sm" value="<?= date('Y') ?>" min="2000" max="2099" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Report Month</label>
                            <select name="report_month" class="form-select form-select-sm" required>
                                <option value="" disabled selected>-- Select Month --</option>
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

                        <div class="col-12"><hr class="my-2 text-muted"></div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Opening Balance (at Beginning of Month)</label>
                            <input type="number" name="opening_balance" id="add_opening_balance" class="form-control form-control-sm ear-calc-trigger" value="0" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-success">Received Qty (During Month)</label>
                            <input type="number" name="received_qty" id="add_received_qty" class="form-control form-control-sm ear-calc-trigger" value="0" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-info">Used Qty</label>
                            <input type="number" name="used_qty" id="add_used_qty" class="form-control form-control-sm ear-calc-trigger" value="0" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-warning">Spoilt/Damaged Qty</label>
                            <input type="number" name="spoilt_qty" id="add_spoilt_qty" class="form-control form-control-sm ear-calc-trigger" value="0" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-danger">Transferred Qty</label>
                            <input type="number" name="transferred_qty" id="add_transferred_qty" class="form-control form-control-sm ear-calc-trigger" value="0" min="0" required>
                        </div>

                        <div class="col-12">
                            <div class="alert alert-secondary d-flex justify-content-between align-items-center mb-0 py-2">
                                <span class="small fw-semibold text-secondary">Formula: (Opening + Received) - (Used + Spoilt + Transferred)</span>
                                <div>
                                    <span class="small fw-semibold text-secondary">Closing Balance: </span>
                                    <strong id="add_closing_balance_display" class="text-dark">0 Units</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="add_submit_btn" class="btn btn-sm btn-success">Save Record</button>
                </div>
            </form>
        </div>
    </div>
</div>
