<?php
// pages/modules/farm/models/animal_disposal_modals.php
// Reusable modal forms for Animal Expended / Disposal Register
$current_species = $species_type ?? 'Cattle';
$current_redirect = $current_page_file ?? 'cattle_register.php';
?>

<!-- ADD ANIMAL DISPOSAL MODAL -->
<div class="modal fade" id="addAnimalDisposalModal" tabindex="-1" aria-labelledby="addAnimalDisposalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header text-light" style="background-color: #370709; border-top-left-radius: 14px; border-top-right-radius: 14px;">
                <h5 class="modal-title fw-bold" id="addAnimalDisposalModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Log <?= htmlspecialchars($current_species) ?> Disposal / Expenditure
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/animal_disposal_crud.php" method="POST" id="addAnimalDisposalForm">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="species" value="<?= htmlspecialchars($current_species) ?>">
                <input type="hidden" name="redirect_page" value="<?= htmlspecialchars($current_redirect) ?>">

                <div class="modal-body p-4">
                    <!-- SECTION 1: GENERAL DETAILS -->
                    <div class="card border-0 bg-light p-3 mb-4" style="border-radius: 10px; border-left: 4px solid #820100 !important;">
                        <h6 class="fw-bold text-dark mb-3">
                            <i class="bi bi-info-circle me-2" style="color: #820100;"></i>1. General Details
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">Date <span class="text-danger">*</span></label>
                                <input type="date" name="disposal_date" class="form-control form-control-sm shadow-sm" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">Voucher No <span class="text-danger">*</span></label>
                                <input type="text" name="voucher_no" class="form-control form-control-sm shadow-sm" placeholder="e.g. VOUCH-2026-001" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">How Disposed Of <span class="text-danger">*</span></label>
                                <select name="how_disposed_of" id="add_how_disposed_of" class="form-select form-select-sm shadow-sm" required onchange="toggleDisposalFields(this, 'add')">
                                    <option value="Sold" selected>Sold</option>
                                    <option value="Died">Died</option>
                                    <option value="Transferred">Transferred</option>
                                    <option value="Culled">Culled</option>
                                    <option value="Other">Other (Specify)</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6" id="add_other_disposal_container" style="display: none;">
                                <label class="form-label fw-bold small text-muted">Specify Other Disposal Method</label>
                                <input type="text" name="how_disposed_other" class="form-control form-control-sm shadow-sm" placeholder="Specify method">
                            </div>

                            <div class="col-md-6" id="add_amount_realized_container">
                                <label class="form-label fw-bold small text-muted">If Sold, Amount Realized (LKR)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white fw-bold">LKR</span>
                                    <input type="number" step="0.01" min="0" name="amount_realized" class="form-control shadow-sm" placeholder="0.00" value="0.00">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">No. and Date of Cash Receipt</label>
                                <input type="text" name="cash_receipt_info" class="form-control form-control-sm shadow-sm" placeholder="e.g. CR-10492 / 2026-08-01">
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: ANIMAL CATEGORIES (QUANTITY INPUTS) -->
                    <div class="card border-0 bg-light p-3 mb-3" style="border-radius: 10px; border-left: 4px solid #185dbd !important;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark m-0">
                                <i class="bi bi-grid-3x3 me-2" style="color: #185dbd;"></i>2. Animal Categories (Quantity Inputs)
                            </h6>
                            <span class="badge bg-primary-subtle text-primary border px-3 py-2 fw-bold" id="add_total_animals_badge">
                                Total: 0 Head
                            </span>
                        </div>
                        <p class="text-muted small mb-3">Record how many of each category were disposed of / expended.</p>
                        
                        <div class="row g-3">
                            <div class="col-md-4 col-6">
                                <label class="form-label fw-bold small text-dark">Stud Bulls</label>
                                <input type="number" min="0" name="stud_bulls" class="form-control form-control-sm shadow-sm calc-qty-add" value="0">
                            </div>
                            <div class="col-md-4 col-6">
                                <label class="form-label fw-bold small text-dark">Draught Bulls</label>
                                <input type="number" min="0" name="draught_bulls" class="form-control form-control-sm shadow-sm calc-qty-add" value="0">
                            </div>
                            <div class="col-md-4 col-6">
                                <label class="form-label fw-bold small text-dark">Cows</label>
                                <input type="number" min="0" name="cows" class="form-control form-control-sm shadow-sm calc-qty-add" value="0">
                            </div>
                            <div class="col-md-6 col-6">
                                <label class="form-label fw-bold small text-dark">Heifer Calves</label>
                                <input type="number" min="0" name="heifer_calves" class="form-control form-control-sm shadow-sm calc-qty-add" value="0">
                            </div>
                            <div class="col-md-6 col-12">
                                <label class="form-label fw-bold small text-dark">Bull Calves</label>
                                <input type="number" min="0" name="bull_calves" class="form-control form-control-sm shadow-sm calc-qty-add" value="0">
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: REMARKS -->
                    <div class="mb-2">
                        <label class="form-label fw-bold small text-muted">Remarks / Details</label>
                        <textarea name="remarks" class="form-control form-control-sm shadow-sm" rows="2" placeholder="Additional notes or references..."></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-3" style="border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;">
                    <button type="button" class="btn btn-secondary btn-sm px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light btn-sm px-4 fw-bold" style="background-color: #370709;">
                        <i class="bi bi-check-circle me-1"></i>Save Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT ANIMAL DISPOSAL MODAL -->
<div class="modal fade" id="editAnimalDisposalModal" tabindex="-1" aria-labelledby="editAnimalDisposalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header text-light" style="background-color: #185dbd; border-top-left-radius: 14px; border-top-right-radius: 14px;">
                <h5 class="modal-title fw-bold" id="editAnimalDisposalModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit <?= htmlspecialchars($current_species) ?> Disposal Record
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/animal_disposal_crud.php" method="POST" id="editAnimalDisposalForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="species" value="<?= htmlspecialchars($current_species) ?>">
                <input type="hidden" name="redirect_page" value="<?= htmlspecialchars($current_redirect) ?>">

                <div class="modal-body p-4">
                    <!-- SECTION 1: GENERAL DETAILS -->
                    <div class="card border-0 bg-light p-3 mb-4" style="border-radius: 10px; border-left: 4px solid #185dbd !important;">
                        <h6 class="fw-bold text-dark mb-3">
                            <i class="bi bi-info-circle me-2" style="color: #185dbd;"></i>1. General Details
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">Date <span class="text-danger">*</span></label>
                                <input type="date" name="disposal_date" id="edit_disposal_date" class="form-control form-control-sm shadow-sm" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">Voucher No <span class="text-danger">*</span></label>
                                <input type="text" name="voucher_no" id="edit_voucher_no" class="form-control form-control-sm shadow-sm" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">How Disposed Of <span class="text-danger">*</span></label>
                                <select name="how_disposed_of" id="edit_how_disposed_of" class="form-select form-select-sm shadow-sm" required onchange="toggleDisposalFields(this, 'edit')">
                                    <option value="Sold">Sold</option>
                                    <option value="Died">Died</option>
                                    <option value="Transferred">Transferred</option>
                                    <option value="Culled">Culled</option>
                                    <option value="Other">Other (Specify)</option>
                                </select>
                            </div>

                            <div class="col-md-6" id="edit_other_disposal_container" style="display: none;">
                                <label class="form-label fw-bold small text-muted">Specify Other Disposal Method</label>
                                <input type="text" name="how_disposed_other" id="edit_how_disposed_other" class="form-control form-control-sm shadow-sm" placeholder="Specify method">
                            </div>

                            <div class="col-md-6" id="edit_amount_realized_container">
                                <label class="form-label fw-bold small text-muted">If Sold, Amount Realized (LKR)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white fw-bold">LKR</span>
                                    <input type="number" step="0.01" min="0" name="amount_realized" id="edit_amount_realized" class="form-control shadow-sm" placeholder="0.00">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">No. and Date of Cash Receipt</label>
                                <input type="text" name="cash_receipt_info" id="edit_cash_receipt_info" class="form-control form-control-sm shadow-sm">
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: ANIMAL CATEGORIES (QUANTITY INPUTS) -->
                    <div class="card border-0 bg-light p-3 mb-3" style="border-radius: 10px; border-left: 4px solid #185dbd !important;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark m-0">
                                <i class="bi bi-grid-3x3 me-2" style="color: #185dbd;"></i>2. Animal Categories (Quantity Inputs)
                            </h6>
                            <span class="badge bg-primary-subtle text-primary border px-3 py-2 fw-bold" id="edit_total_animals_badge">
                                Total: 0 Head
                            </span>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-4 col-6">
                                <label class="form-label fw-bold small text-dark">Stud Bulls</label>
                                <input type="number" min="0" name="stud_bulls" id="edit_stud_bulls" class="form-control form-control-sm shadow-sm calc-qty-edit" value="0">
                            </div>
                            <div class="col-md-4 col-6">
                                <label class="form-label fw-bold small text-dark">Draught Bulls</label>
                                <input type="number" min="0" name="draught_bulls" id="edit_draught_bulls" class="form-control form-control-sm shadow-sm calc-qty-edit" value="0">
                            </div>
                            <div class="col-md-4 col-6">
                                <label class="form-label fw-bold small text-dark">Cows</label>
                                <input type="number" min="0" name="cows" id="edit_cows" class="form-control form-control-sm shadow-sm calc-qty-edit" value="0">
                            </div>
                            <div class="col-md-6 col-6">
                                <label class="form-label fw-bold small text-dark">Heifer Calves</label>
                                <input type="number" min="0" name="heifer_calves" id="edit_heifer_calves" class="form-control form-control-sm shadow-sm calc-qty-edit" value="0">
                            </div>
                            <div class="col-md-6 col-12">
                                <label class="form-label fw-bold small text-dark">Bull Calves</label>
                                <input type="number" min="0" name="bull_calves" id="edit_bull_calves" class="form-control form-control-sm shadow-sm calc-qty-edit" value="0">
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: REMARKS -->
                    <div class="mb-2">
                        <label class="form-label fw-bold small text-muted">Remarks / Details</label>
                        <textarea name="remarks" id="edit_remarks" class="form-control form-control-sm shadow-sm" rows="2"></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-3" style="border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;">
                    <button type="button" class="btn btn-secondary btn-sm px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light btn-sm px-4 fw-bold" style="background-color: #185dbd;">
                        <i class="bi bi-save me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleDisposalFields(selectElem, mode) {
    var val = selectElem.value;
    var otherContainer = document.getElementById(mode + '_other_disposal_container');
    if (val === 'Other') {
        otherContainer.style.display = 'block';
    } else {
        otherContainer.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    function calculateAddTotal() {
        var total = 0;
        document.querySelectorAll('.calc-qty-add').forEach(function(input) {
            total += parseInt(input.value) || 0;
        });
        var badge = document.getElementById('add_total_animals_badge');
        if (badge) {
            badge.textContent = 'Total: ' + total + ' Head';
        }
    }

    function calculateEditTotal() {
        var total = 0;
        document.querySelectorAll('.calc-qty-edit').forEach(function(input) {
            total += parseInt(input.value) || 0;
        });
        var badge = document.getElementById('edit_total_animals_badge');
        if (badge) {
            badge.textContent = 'Total: ' + total + ' Head';
        }
    }

    document.querySelectorAll('.calc-qty-add').forEach(function(input) {
        input.addEventListener('input', calculateAddTotal);
    });

    document.querySelectorAll('.calc-qty-edit').forEach(function(input) {
        input.addEventListener('input', calculateEditTotal);
    });
});
</script>
