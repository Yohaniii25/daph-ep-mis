<div class="modal fade" id="addVaccineBalanceModal" tabindex="-1" aria-labelledby="addVaccineBalanceLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background: linear-gradient(135deg, #370709 0%, #820100 100%);">
                <h6 class="modal-title fw-bold" id="addVaccineBalanceLabel"><i class="bi bi-file-earmark-plus me-2"></i>Add Monthly Vaccine Balance</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAddVaccineBalance" action="processors/save_vaccine_balance.php" method="POST">
                <input type="hidden" name="range_id" id="add_modal_range_id" value="<?= htmlspecialchars($range_id ?? '') ?>">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Report Year <span class="text-danger">*</span></label>
                            <input type="number" id="add_report_year" name="report_year" class="form-control form-control-sm border-secondary fw-bold" value="<?= htmlspecialchars($selected_year ?? date('Y')) ?>" min="2000" max="2099" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Report Month <span class="text-danger">*</span></label>
                            <select name="report_month" id="add_report_month" class="form-select form-select-sm border-secondary fw-bold" required>
                                <option value="" disabled selected>-- Select Month --</option>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= (date('n') == $m) ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Vaccine Formulation / Name <span class="text-danger">*</span></label>
                            <select name="vaccine_name" id="add_vaccine_name" class="form-select form-select-sm fw-bold text-dark border-secondary" required>
                                <option value="" disabled selected>-- Select Vaccine --</option>
                                <optgroup label="Standard Immunizations">
                                    <option value="FMD">FMD (Foot & Mouth Disease)</option>
                                    <option value="HS">HS (Hemorrhagic Septicemia)</option>
                                    <option value="BQ">BQ (Black Quarter)</option>
                                    <option value="Newcastle Disease (ND / Ranikhet)">Newcastle Disease (ND / Ranikhet)</option>
                                    <option value="Infectious Bursal Disease (IBD / Gumboro)">Infectious Bursal Disease (IBD / Gumboro)</option>
                                    <option value="Fowl Pox Vaccine">Fowl Pox Vaccine</option>
                                </optgroup>
                                <optgroup label="Registered Drug Formulations">
                                    <?php
                                    $type_opts = $mysqli->query("SELECT id, vaccine_name, expiry_date, brand_name, chemical_composition FROM drug_types ORDER BY vaccine_name ASC");
                                    if ($type_opts) {
                                        while ($t_opt = $type_opts->fetch_assoc()):
                                            $disp_name = !empty($t_opt['brand_name']) && !empty($t_opt['chemical_composition'])
                                                ? htmlspecialchars($t_opt['brand_name'] . ' (' . $t_opt['chemical_composition'] . ')')
                                                : htmlspecialchars($t_opt['vaccine_name']);
                                            $val_name = htmlspecialchars($t_opt['vaccine_name'], ENT_QUOTES);
                                            $expiry = !empty($t_opt['expiry_date']) ? date('Y-m-d', strtotime($t_opt['expiry_date'])) : '';
                                    ?>
                                        <option value="<?= $val_name ?>" data-expiry="<?= $expiry ?>">
                                            <?= $disp_name ?>
                                        </option>
                                    <?php 
                                        endwhile; 
                                    }
                                    ?>
                                </optgroup>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold text-dark mb-0">Vaccine Batch No.</label>
                                <button type="button" class="btn btn-link p-0 text-decoration-none small text-danger fw-bold" data-bs-toggle="modal" data-bs-target="#addVaccineBatchModal">
                                    <i class="bi bi-plus-circle-fill me-1"></i>+ New Batch
                                </button>
                            </div>
                            <select name="batch_no" id="add_batch_no" class="form-select form-select-sm fw-bold text-dark border-secondary">
                                <option value="" selected>-- Select Registered Batch or None --</option>
                                <?php
                                $batch_opts = $mysqli->query("SELECT batch_number, expiry_date FROM vaccine_batches WHERE is_active = 1 ORDER BY id DESC");
                                if ($batch_opts) {
                                    while ($opt = $batch_opts->fetch_assoc()):
                                        $b_expiry = !empty($opt['expiry_date']) ? date('Y-m-d', strtotime($opt['expiry_date'])) : '';
                                ?>
                                    <option value="<?= htmlspecialchars($opt['batch_number'], ENT_QUOTES) ?>" data-expiry="<?= $b_expiry ?>">
                                        <?= htmlspecialchars($opt['batch_number']) ?><?= $b_expiry ? ' (Exp: ' . $b_expiry . ')' : '' ?>
                                    </option>
                                <?php 
                                    endwhile;
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <div id="priorBalanceAlert" class="alert alert-info py-2 px-3 small d-none mb-0">
                                <i class="bi bi-info-circle-fill me-1"></i> <span id="priorBalanceAlertText">Opening balance automatically retrieved from prior month's closing balance.</span>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Opening Balance (Doses) <span class="text-danger">*</span></label>
                            <input type="number" id="add_opening_balance" name="opening_balance" class="form-control form-control-sm border-secondary fw-bold text-primary" value="0" min="0" required>
                            <small class="text-muted" id="openingBalHelp">Auto-populated from prior closing balance.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Received Doses</label>
                            <input type="number" id="add_received_doses" name="received_doses" class="form-control form-control-sm border-secondary fw-bold text-success" value="0" min="0" required>
                            <small class="text-muted">Stock received in month.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Used Doses</label>
                            <input type="number" id="add_used_doses" name="used_doses" class="form-control form-control-sm border-secondary fw-bold text-info" value="0" min="0" required>
                            <small class="text-muted">Administered in field.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Spoilt / Damaged Doses</label>
                            <input type="number" id="add_spoilt_doses" name="spoilt_damaged_doses" class="form-control form-control-sm border-secondary fw-bold text-danger" value="0" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Transferred Doses</label>
                            <input type="number" id="add_transferred_doses" name="transferred_doses" class="form-control form-control-sm border-secondary fw-bold" value="0" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Closing Balance (Doses)</label>
                            <input type="number" id="add_closing_balance" name="closing_balance" class="form-control form-control-sm border-secondary fw-bold bg-light text-dark" value="0" min="0" required readonly>
                            <small class="text-muted">Calculated available stock.</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Expiry Date</label>
                            <input type="date" name="expiry_date" id="add_expiry_date" class="form-control form-control-sm border-secondary">
                            <small class="text-muted">Auto-populates when batch or formulation is chosen.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Remarks</label>
                            <input type="text" name="remarks" class="form-control form-control-sm border-secondary" placeholder="e.g. Cold chain, supplier notes, field allocations">
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light border-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm text-light fw-bold px-4" style="background-color:#820100;"><i class="bi bi-save me-1"></i>Save Vaccine Balance</button>
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
    const vaxNameSelect = document.getElementById('add_vaccine_name');
    const batchSelect = document.getElementById('add_batch_no');
    const expiryInput = document.getElementById('add_expiry_date');
    const yearInput = document.getElementById('add_report_year');
    const monthSelect = document.getElementById('add_report_month');
    const rangeIdInput = document.getElementById('add_modal_range_id');
    const alertBox = document.getElementById('priorBalanceAlert');
    const alertText = document.getElementById('priorBalanceAlertText');

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
        if (input) input.addEventListener('input', calculateClosing);
    });

    // Auto-populate expiry when batch changes
    if (batchSelect) {
        batchSelect.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            if (opt && opt.dataset && opt.dataset.expiry) {
                expiryInput.value = opt.dataset.expiry;
            }
        });
    }

    // Auto-populate expiry and pull prior balance when vaccine or month changes
    function fetchPriorBalance() {
        const vax = vaxNameSelect ? vaxNameSelect.value : '';
        const yr = yearInput ? yearInput.value : '';
        const mo = monthSelect ? monthSelect.value : '';
        const rid = rangeIdInput ? rangeIdInput.value : '';

        if (!vax || !yr || !mo) return;

        // Auto populate expiry from vaccine if available and expiry field empty
        if (vaxNameSelect) {
            const selectedOpt = vaxNameSelect.options[vaxNameSelect.selectedIndex];
            if (selectedOpt && selectedOpt.dataset && selectedOpt.dataset.expiry && !expiryInput.value) {
                expiryInput.value = selectedOpt.dataset.expiry;
            }
        }

        const url = 'processors/get_prior_vaccine_balance.php?range_id=' + encodeURIComponent(rid) +
                    '&vaccine_name=' + encodeURIComponent(vax) +
                    '&report_year=' + encodeURIComponent(yr) +
                    '&report_month=' + encodeURIComponent(mo);

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data && data.success) {
                    if (data.found_prior) {
                        obVal.value = data.prior_closing_balance;
                        if (alertBox && alertText) {
                            alertText.textContent = 'Auto-pulled opening balance: ' + Number(data.prior_closing_balance).toLocaleString() + ' doses from prior month closing balance.';
                            alertBox.classList.remove('d-none');
                        }
                    } else {
                        if (alertBox) alertBox.classList.add('d-none');
                    }
                    if (data.expiry_date && (!expiryInput.value || expiryInput.value === 'N/A')) {
                        expiryInput.value = data.expiry_date;
                    }
                    if (data.batch_no && batchSelect && !batchSelect.value) {
                        for (let i = 0; i < batchSelect.options.length; i++) {
                            if (batchSelect.options[i].value === data.batch_no) {
                                batchSelect.selectedIndex = i;
                                break;
                            }
                        }
                    }
                    calculateClosing();
                }
            })
            .catch(err => console.error('Error fetching prior balance:', err));
    }

    if (vaxNameSelect) vaxNameSelect.addEventListener('change', fetchPriorBalance);
    if (yearInput) yearInput.addEventListener('change', fetchPriorBalance);
    if (monthSelect) monthSelect.addEventListener('change', fetchPriorBalance);
});
</script>
