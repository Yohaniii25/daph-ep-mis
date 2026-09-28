<?php
/**
 * Modal: Edit Cash Book Summary Record
 * Categorized items mapped to tabs: Consultations & Certificates, Poultry & Semen, Vaccines & Surgeries, Post Mortems
 */
?>
<div class="modal fade" id="editCashBookSummaryModal" tabindex="-1" aria-labelledby="editCashBookSummaryLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color: #370709;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-pencil-square fs-5"></i>
                    <h6 class="modal-title fw-bold mb-0" id="editCashBookSummaryLabel">Edit Cash Book Revenue Entry</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditCashBookSummary" action="processors/update_cash_book_summary.php" method="POST">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="from_month" id="edit_modal_from_month" value="<?= htmlspecialchars($from_month ?? 1) ?>">
                <input type="hidden" name="to_month" id="edit_modal_to_month" value="<?= htmlspecialchars($to_month ?? 12) ?>">
                <input type="hidden" name="active_tab" id="edit_modal_active_tab" value="<?= htmlspecialchars($active_tab ?? 'tab-consultations') ?>">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Reporting Year <span class="text-danger">*</span></label>
                            <select name="report_year" id="edit_report_year" class="form-select form-select-sm" required>
                                <?php
                                $c_yr = intval(date('Y'));
                                for ($y = $c_yr - 3; $y <= $c_yr + 3; $y++): ?>
                                    <option value="<?= $y ?>"><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Reporting Month <span class="text-danger">*</span></label>
                            <select name="report_month" id="edit_report_month" class="form-select form-select-sm" required>
                                <option value="" disabled>-- Select Month --</option>
                                <?php
                                $m_names = [
                                    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                ];
                                foreach ($m_names as $m_num => $m_lbl): ?>
                                    <option value="<?= $m_num ?>"><?= str_pad($m_num, 2, '0', STR_PAD_LEFT) ?> - <?= $m_lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Item Category Selector -->
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-dark">Revenue Item / Stream <span class="text-danger">*</span></label>
                            <select id="edit_item_selector" class="form-select form-select-sm" required>
                                <option value="" disabled>-- Select Categorized Item --</option>
                                <optgroup label="1. Consultations & Certificates">
                                    <option value="Consultation fee for cross breed Pet's">Consultation fee for cross breed Pet's</option>
                                    <option value="Consultation fee for pure breed dog">Consultation fee for pure breed dog</option>
                                    <option value="Health certificate">Health certificate</option>
                                </optgroup>
                                <optgroup label="2. Poultry Sales & Semen">
                                    <option value="Day old unsexed backyard chicks">Day old unsexed backyard chicks</option>
                                    <option value="Semen straws for AI services">Semen straws for AI services</option>
                                    <option value="Day Old Cockerels">Day Old Cockerels</option>
                                </optgroup>
                                <optgroup label="3. Vaccines, Surgeries & Treatments">
                                    <option value="Wound ress (Wound dress)">Wound ress (Wound dress)</option>
                                    <option value="Ranikhet 1st dose & 2nd Dose">Ranikhet 1st dose & 2nd Dose</option>
                                    <option value="OHE - Dog">OHE - Dog</option>
                                    <option value="OHE -Cat">OHE -Cat</option>
                                </optgroup>
                                <optgroup label="4. Post Mortems">
                                    <option value="Poultry -Bird post mortems">Poultry -Bird post mortems</option>
                                    <option value="Post moturm Rabbit">Post moturm Rabbit</option>
                                </optgroup>
                                <optgroup label="Other Revenue Streams">
                                    <option value="__custom__">+ Enter Custom Item Name...</option>
                                </optgroup>
                            </select>

                            <!-- Actual submitted item name input -->
                            <input type="hidden" name="item_name" id="edit_final_item_name" value="">

                            <!-- Custom item name text field, shown if __custom__ selected -->
                            <div id="edit_custom_item_wrapper" class="mt-2" style="display: none;">
                                <input type="text" id="edit_custom_item_name" class="form-control form-control-sm" placeholder="Type custom revenue item name here...">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Quantity Sold <span class="text-danger">*</span></label>
                            <input type="number" id="edit_qty_sold" name="quantity_sold" class="form-control form-control-sm" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Unit Price (Rs. / Cts.) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted">Rs.</span>
                                <input type="number" id="edit_unit_price" name="unit_price" class="form-control form-control-sm" step="0.01" min="0" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Total Amount (Rs. / Cts.) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted">Rs.</span>
                                <input type="number" id="edit_total_amount" name="total_amount" class="form-control form-control-sm bg-light fw-bold text-dark" step="0.01" min="0" required>
                            </div>
                            <span class="text-muted small" style="font-size: 0.75rem;">Calculated automatically: Quantity &times; Unit Price</span>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold text-dark mb-0">Amount Deposited (Rs. / Cts.) <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-link p-0 text-decoration-none small text-primary" id="btnDepositFullEdit" style="font-size: 0.75rem;">
                                    <i class="bi bi-arrow-down-circle me-1"></i>Deposit in Full
                                </button>
                            </div>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted">Rs.</span>
                                <input type="number" id="edit_amount_deposited" name="amount_deposited" class="form-control form-control-sm fw-bold text-success" step="0.01" min="0" required>
                            </div>
                            <span class="text-muted small" style="font-size: 0.75rem;">Amount deposited to Government Bank account</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light border-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm text-light fw-bold shadow-sm" style="background-color: #370709;">
                        <i class="bi bi-save me-1"></i>Update Cash Book Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editItemSelect = document.getElementById('edit_item_selector');
    const editCustomWrap = document.getElementById('edit_custom_item_wrapper');
    const editCustomInput = document.getElementById('edit_custom_item_name');
    const editFinalInput = document.getElementById('edit_final_item_name');
    const editQtyInput = document.getElementById('edit_qty_sold');
    const editPriceInput = document.getElementById('edit_unit_price');
    const editTotalInput = document.getElementById('edit_total_amount');
    const editDepositInput = document.getElementById('edit_amount_deposited');
    const editBtnDepositFull = document.getElementById('btnDepositFullEdit');

    function syncEditItemName() {
        if (!editItemSelect) return;
        if (editItemSelect.value === '__custom__') {
            editCustomWrap.style.display = 'block';
            editFinalInput.value = editCustomInput.value.trim();
        } else {
            editCustomWrap.style.display = 'none';
            editFinalInput.value = editItemSelect.value;
        }
    }

    if (editItemSelect) {
        editItemSelect.addEventListener('change', syncEditItemName);
    }
    if (editCustomInput) {
        editCustomInput.addEventListener('input', function() {
            if (editItemSelect.value === '__custom__') {
                editFinalInput.value = this.value.trim();
            }
        });
    }

    function calculateEditTotal() {
        if (!editQtyInput || !editPriceInput || !editTotalInput) return;
        const qty = parseFloat(editQtyInput.value) || 0;
        const price = parseFloat(editPriceInput.value) || 0;
        editTotalInput.value = (qty * price).toFixed(2);
    }

    if (editQtyInput) editQtyInput.addEventListener('input', calculateEditTotal);
    if (editPriceInput) editPriceInput.addEventListener('input', calculateEditTotal);

    if (editBtnDepositFull) {
        editBtnDepositFull.addEventListener('click', function() {
            calculateEditTotal();
            if (editDepositInput && editTotalInput) {
                editDepositInput.value = editTotalInput.value;
            }
        });
    }

    const editForm = document.getElementById('formEditCashBookSummary');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            syncEditItemName();
            if (!editFinalInput.value) {
                e.preventDefault();
                alert('Please select or specify a valid revenue item name.');
                if (editItemSelect.value === '__custom__') {
                    editCustomInput.focus();
                } else {
                    editItemSelect.focus();
                }
            }
        });
    }
});
</script>
