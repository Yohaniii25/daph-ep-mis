<div class="modal fade" id="addCropReturnsModal" tabindex="-1" aria-labelledby="addCropReturnsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color:#370709;">
                <h6 class="modal-title fw-bold" id="addCropReturnsLabel"><i class="bi bi-file-earmark-plus me-2"></i>Add Crop Return Record</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAddCropReturn" action="processors/save_crop_return.php" method="POST">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Report Year</label>
                            <input type="number" name="report_year" class="form-control form-control-sm" value="2026" min="2000" max="2099" required>
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
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Item Name</label>
                            <input type="text" name="item_name" class="form-control form-control-sm" placeholder="e.g. Maize, Paddy, Sorghum" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Balance from Previous Month</label>
                            <input type="number" id="add_prev_bal" name="balance_previous_month" class="form-control form-control-sm" value="0" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Received During Current Month</label>
                            <input type="number" id="add_received" name="received_current_month" class="form-control form-control-sm" value="0" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Issued During Current Month</label>
                            <input type="number" id="add_issued" name="issued_current_month" class="form-control form-control-sm" value="0" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Balance at End of Month (Manual Entry)</label>
                            <input type="number" id="add_current_bal" name="balance_current_month" class="form-control form-control-sm" value="0" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Remarks</label>
                            <textarea name="remark" class="form-control form-control-sm" rows="1" placeholder="Optional notes"></textarea>
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
