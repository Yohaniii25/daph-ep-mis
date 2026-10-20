<?php
/**
 * Modal: Add Cash Book Summary Record
 * Categorized items mapped to tabs: Consultations & Certificates, Poultry & Semen, Vaccines & Surgeries, Post Mortems
 */
?>
<div class="modal fade" id="addCashBookSummaryModal" tabindex="-1" aria-labelledby="addCashBookSummaryLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color: #820100;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-plus-fill fs-5"></i>
                    <h6 class="modal-title fw-bold mb-0" id="addCashBookSummaryLabel">Record Cash Book Revenue Entry</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAddCashBookSummary" action="processors/save_cash_book_summary.php" method="POST">
                <!-- Keep filter & tab state upon return -->
                <input type="hidden" name="from_month" id="add_modal_from_month" value="<?= htmlspecialchars($from_month ?? 1) ?>">
                <input type="hidden" name="to_month" id="add_modal_to_month" value="<?= htmlspecialchars($to_month ?? 12) ?>">
                <input type="hidden" name="active_tab" id="add_modal_active_tab" value="<?= htmlspecialchars($active_tab ?? 'tab-consultations') ?>">

                <div class="modal-body p-4">
                    <div class="alert border py-2 px-3 small mb-3 d-flex align-items-center justify-content-between" style="background-color: #fffdf0; border-color: #fcd34d !important;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-receipt-cutoff text-warning fs-5"></i>
                            <div>
                                <span class="fw-bold text-dark">Digital Counterfoil Receipt Voucher (General Form 172)</span>
                                <span class="d-block text-muted" style="font-size: 11px;">Captures client receipt details and automatically aggregates into Cashbook Summary categories.</span>
                            </div>
                        </div>
                        <span class="badge bg-warning text-dark border border-warning px-2 py-1 fw-bold">Audited Receipt</span>
                    </div>

                    <div class="row g-3">
                        <!-- Receipt Serial No and Date -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Receipt / Leaf Serial No. <span class="text-danger">*</span></label>
                            <input type="text" name="receipt_no" id="add_receipt_no" class="form-control form-control-sm font-monospace fw-bold" placeholder="e.g. CR-26/03/4821" value="CR-<?= date('y/m/') . rand(1000, 9999) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Receipt Date <span class="text-danger">*</span></label>
                            <input type="date" name="receipt_date" id="add_receipt_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <!-- Owner Name and Client NIC -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Owner Name (Client / Farmer) <span class="text-danger">*</span></label>
                            <input type="text" name="client_name" id="add_client_name" class="form-control form-control-sm" placeholder="e.g. K. M. Fareed or Dr. Silva" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Client NIC / Contact (Optional)</label>
                            <input type="text" name="client_nic" id="add_client_nic" class="form-control form-control-sm font-monospace" placeholder="e.g. 198512304561 or 0771234567">
                        </div>

                        <!-- Reporting Year & Month -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Cashbook Year <span class="text-danger">*</span></label>
                            <select name="report_year" id="add_report_year" class="form-select form-select-sm" required>
                                <?php
                                $c_yr = intval(date('Y'));
                                for ($y = $c_yr - 3; $y <= $c_yr + 3; $y++): ?>
                                    <option value="<?= $y ?>" <?= ($y === ($selected_year ?? $c_yr)) ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Cashbook Month <span class="text-danger">*</span></label>
                            <select name="report_month" id="add_report_month" class="form-select form-select-sm" required>
                                <option value="" disabled>-- Select Month --</option>
                                <?php
                                $m_names = [
                                    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                ];
                                $cur_m = isset($from_month) && $from_month >= 1 && $from_month <= 12 ? $from_month : intval(date('n'));
                                foreach ($m_names as $m_num => $m_lbl): ?>
                                    <option value="<?= $m_num ?>" <?= ($m_num === $cur_m) ? 'selected' : '' ?>><?= str_pad($m_num, 2, '0', STR_PAD_LEFT) ?> - <?= $m_lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Item Category Selector -->
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-dark">Revenue Item / Stream <span class="text-danger">*</span></label>
                            <select id="add_item_selector" class="form-select form-select-sm" required>
                                <option value="" disabled selected>-- Select Categorized Item --</option>
                                <optgroup label="1. Consultations & Certificates">
                                    <option value="Consultation fee for cross breed Pet's" data-price="100.00">Consultation fee for cross breed Pet's (Rs. 100.00)</option>
                                    <option value="Consultation fee for pure breed dog" data-price="150.00">Consultation fee for pure breed dog (Rs. 150.00)</option>
                                    <option value="Health certificate" data-price="250.00">Health certificate (Rs. 250.00)</option>
                                </optgroup>
                                <optgroup label="2. Poultry Sales & Semen">
                                    <option value="Day old unsexed backyard chicks" data-price="120.00">Day old unsexed backyard chicks</option>
                                    <option value="Semen straws for AI services" data-price="200.00">Semen straws for AI services</option>
                                    <option value="Day Old Cockerels" data-price="80.00">Day Old Cockerels</option>
                                </optgroup>
                                <optgroup label="3. Vaccines, Surgeries & Treatments">
                                    <option value="Wound ress (Wound dress)" data-price="150.00">Wound ress (Wound dress)</option>
                                    <option value="Ranikhet 1st dose & 2nd Dose" data-price="50.00">Ranikhet 1st dose & 2nd Dose</option>
                                    <option value="OHE - Dog" data-price="1500.00">OHE - Dog (Rs. 1,500.00)</option>
                                    <option value="OHE -Cat" data-price="1000.00">OHE -Cat (Rs. 1,000.00)</option>
                                </optgroup>
                                <optgroup label="4. Post Mortems">
                                    <option value="Poultry -Bird post mortems" data-price="100.00">Poultry -Bird post mortems</option>
                                    <option value="Post moturm Rabbit" data-price="150.00">Post moturm Rabbit</option>
                                </optgroup>
                                <optgroup label="Other Revenue Streams">
                                    <option value="__custom__" data-price="0.00">+ Enter Custom Item Name...</option>
                                </optgroup>
                            </select>

                            <!-- Actual submitted item name input -->
                            <input type="hidden" name="item_name" id="add_final_item_name" value="">

                            <!-- Custom item name text field, shown if __custom__ selected -->
                            <div id="add_custom_item_wrapper" class="mt-2" style="display: none;">
                                <input type="text" id="add_custom_item_name" class="form-control form-control-sm" placeholder="Type custom revenue item name here...">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Quantity Sold / Services <span class="text-danger">*</span></label>
                            <input type="number" id="add_qty_sold" name="quantity_sold" class="form-control form-control-sm text-end font-monospace fw-bold" value="1" min="1" step="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Unit Price (Rs. / Cts.) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted">Rs.</span>
                                <input type="number" id="add_unit_price" name="unit_price" class="form-control form-control-sm text-end font-monospace" value="0.00" step="0.01" min="0" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Total Amount (Rs. / Cts.) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted">Rs.</span>
                                <input type="number" id="add_total_amount" name="total_amount" class="form-control form-control-sm bg-light fw-bold text-dark text-end font-monospace fs-6" value="0.00" step="0.01" min="0" required>
                            </div>
                            <span class="text-muted small" style="font-size: 0.75rem;">Calculated automatically: Quantity &times; Unit Price</span>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold text-dark mb-0">Amount Deposited (Rs. / Cts.) <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-link p-0 text-decoration-none small text-success fw-bold" id="btnDepositFullAdd" style="font-size: 0.75rem;">
                                    <i class="bi bi-arrow-down-circle me-1"></i>Deposit in Full
                                </button>
                            </div>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted">Rs.</span>
                                <input type="number" id="add_amount_deposited" name="amount_deposited" class="form-control form-control-sm fw-bold text-success text-end font-monospace fs-6" value="0.00" step="0.01" min="0" required>
                            </div>
                            <span class="text-muted small" style="font-size: 0.75rem;">Amount deposited to Government Bank account</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light border-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm text-light fw-bold shadow-sm" style="background-color: #820100;">
                        <i class="bi bi-receipt me-1"></i>Issue &amp; Save Receipt
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const itemSelect = document.getElementById('add_item_selector');
    const customItemWrap = document.getElementById('add_custom_item_wrapper');
    const customItemInput = document.getElementById('add_custom_item_name');
    const finalItemInput = document.getElementById('add_final_item_name');
    const qtyInput = document.getElementById('add_qty_sold');
    const priceInput = document.getElementById('add_unit_price');
    const totalInput = document.getElementById('add_total_amount');
    const depositInput = document.getElementById('add_amount_deposited');
    const btnDepositFull = document.getElementById('btnDepositFullAdd');

    function syncItemName() {
        if (!itemSelect) return;
        if (itemSelect.value === '__custom__') {
            customItemWrap.style.display = 'block';
            finalItemInput.value = customItemInput.value.trim();
        } else {
            customItemWrap.style.display = 'none';
            finalItemInput.value = itemSelect.value;
        }
    }

    if (itemSelect) {
        itemSelect.addEventListener('change', syncItemName);
    }
    if (customItemInput) {
        customItemInput.addEventListener('input', function() {
            if (itemSelect.value === '__custom__') {
                finalItemInput.value = this.value.trim();
            }
        });
    }

    function calculateTotal() {
        if (!qtyInput || !priceInput || !totalInput) return;
        const qty = parseFloat(qtyInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        const tot = (qty * price).toFixed(2);
        totalInput.value = tot;
    }

    if (qtyInput) qtyInput.addEventListener('input', calculateTotal);
    if (priceInput) priceInput.addEventListener('input', calculateTotal);

    if (btnDepositFull) {
        btnDepositFull.addEventListener('click', function() {
            calculateTotal();
            if (depositInput && totalInput) {
                depositInput.value = totalInput.value;
            }
        });
    }

    const form = document.getElementById('formAddCashBookSummary');
    if (form) {
        form.addEventListener('submit', function(e) {
            syncItemName();
            if (!finalItemInput.value) {
                e.preventDefault();
                alert('Please select or specify a valid revenue item name.');
                if (itemSelect.value === '__custom__') {
                    customItemInput.focus();
                } else {
                    itemSelect.focus();
                }
            }
        });
    }
});
</script>
