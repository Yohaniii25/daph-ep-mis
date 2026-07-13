<div class="modal fade" id="addVaccineBalanceModal" tabindex="-1" aria-labelledby="addVaccineBalanceLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color:#370709;">
                <h6 class="modal-title fw-bold" id="addVaccineBalanceLabel"><i class="bi bi-file-earmark-plus me-2"></i>Add Vaccine Balance</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAddVaccineBalance" action="processors/save_vaccine_balance.php" method="POST">
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
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Vaccine Name</label>
                            <input type="text" name="vaccine_name" class="form-control form-control-sm" placeholder="e.g. Ranikhet 1, Fowl Pox, ARV" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Batch No.</label>
                            <input type="text" name="batch_no" class="form-control form-control-sm" placeholder="e.g. B-90382">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Opening Balance (Doses)</label>
                            <input type="number" id="add_opening_balance" name="opening_balance" class="form-control form-control-sm" value="0" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Received Doses</label>
                            <input type="number" id="add_received_doses" name="received_doses" class="form-control form-control-sm" value="0" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Used Doses</label>
                            <input type="number" id="add_used_doses" name="used_doses" class="form-control form-control-sm" value="0" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Spoilt / Damaged Doses</label>
                            <input type="number" id="add_spoilt_doses" name="spoilt_damaged_doses" class="form-control form-control-sm" value="0" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Transferred Doses</label>
                            <input type="number" id="add_transferred_doses" name="transferred_doses" class="form-control form-control-sm" value="0" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Closing Balance (Doses)</label>
                            <input type="number" id="add_closing_balance" name="closing_balance" class="form-control form-control-sm" value="0" min="0" required readonly>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Expiry Date</label>
                            <input type="text" name="expiry_date" class="form-control form-control-sm" placeholder="e.g. August/2026 or YYYY-MM-DD">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Remarks</label>
                            <input type="text" name="remarks" class="form-control form-control-sm" placeholder="e.g. Temporary storage issue">
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
    const obVal = document.getElementById('add_opening_balance');
    const rcVal = document.getElementById('add_received_doses');
    const usVal = document.getElementById('add_used_doses');
    const spVal = document.getElementById('add_spoilt_doses');
    const trVal = document.getElementById('add_transferred_doses');
    const clVal = document.getElementById('add_closing_balance');

    function calculateClosing() {
        const ob = parseInt(obVal.value) || 0;
        const rc = parseInt(rcVal.value) || 0;
        const us = parseInt(usVal.value) || 0;
        const sp = parseInt(spVal.value) || 0;
        const tr = parseInt(trVal.value) || 0;
        
        const closing = ob + rc - us - sp - tr;
        clVal.value = closing >= 0 ? closing : 0;
    }

    [obVal, rcVal, usVal, spVal, trVal].forEach(input => {
        input.addEventListener('input', calculateClosing);
    });
});
</script>
