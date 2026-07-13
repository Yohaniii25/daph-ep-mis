<div class="modal fade" id="editVaccineBalanceModal" tabindex="-1" aria-labelledby="editVaccineBalanceLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color:#370709;">
                <h6 class="modal-title fw-bold" id="editVaccineBalanceLabel"><i class="bi bi-pencil-square me-2"></i>Edit Vaccine Balance</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditVaccineBalance" action="processors/update_vaccine_balance.php" method="POST">
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
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Vaccine Name</label>
                            <select name="vaccine_name" id="edit_vaccine_name" class="form-select form-select-sm fw-bold text-dark" required>
                                <option value="" disabled>-- Select Vaccine --</option>
                                <?php
                                $type_opts = $mysqli->query("SELECT id, vaccine_name, expiry_date FROM drug_types ORDER BY vaccine_name ASC");
                                while ($t_opt = $type_opts->fetch_assoc()):
                                    $expiry = !empty($t_opt['expiry_date']) ? date('Y-m-d', strtotime($t_opt['expiry_date'])) : 'N/A';
                                ?>
                                    <option value="<?= htmlspecialchars($t_opt['vaccine_name'], ENT_QUOTES) ?>" data-expiry="<?= $expiry ?>">
                                        <?= htmlspecialchars($t_opt['vaccine_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Batch No.</label>
                            <select name="batch_no" id="edit_batch_no" class="form-select form-select-sm fw-bold text-dark">
                                <option value="" selected disabled>-- Select Batch --</option>
                                <?php
                                // Fetch all batches in edit modal to prevent legacy active state orphans
                                $batch_opts = $mysqli->query("SELECT DISTINCT batch_number FROM vaccine_batches ORDER BY id DESC");
                                while ($opt = $batch_opts->fetch_assoc()):
                                ?>
                                    <option value="<?= htmlspecialchars($opt['batch_number'], ENT_QUOTES) ?>">
                                        <?= htmlspecialchars($opt['batch_number']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Opening Balance (Doses)</label>
                            <input type="number" id="edit_opening_balance" name="opening_balance" class="form-control form-control-sm" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Received Doses</label>
                            <input type="number" id="edit_received_doses" name="received_doses" class="form-control form-control-sm" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Used Doses</label>
                            <input type="number" id="edit_used_doses" name="used_doses" class="form-control form-control-sm" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Spoilt / Damaged Doses</label>
                            <input type="number" id="edit_spoilt_doses" name="spoilt_damaged_doses" class="form-control form-control-sm" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Transferred Doses</label>
                            <input type="number" id="edit_transferred_doses" name="transferred_doses" class="form-control form-control-sm" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Closing Balance (Doses)</label>
                            <input type="number" id="edit_closing_balance" name="closing_balance" class="form-control form-control-sm" min="0" required readonly>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Expiry Date</label>
                            <input type="text" name="expiry_date" id="edit_expiry_date" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Remarks</label>
                            <input type="text" name="remarks" id="edit_remarks" class="form-control form-control-sm">
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
    const obVal = document.getElementById('edit_opening_balance');
    const rcVal = document.getElementById('edit_received_doses');
    const usVal = document.getElementById('edit_used_doses');
    const spVal = document.getElementById('edit_spoilt_doses');
    const trVal = document.getElementById('edit_transferred_doses');
    const clVal = document.getElementById('edit_closing_balance');

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

    // Auto-fill expiry date on vaccine name change
    document.getElementById('edit_vaccine_name').addEventListener('change', function() {
        const selectedExpiry = this.options[this.selectedIndex].getAttribute('data-expiry');
        document.getElementById('edit_expiry_date').value = (selectedExpiry && selectedExpiry !== 'N/A') ? selectedExpiry : '';
    });
});
</script>
