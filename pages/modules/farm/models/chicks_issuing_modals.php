<!-- pages/modules/farm/models/chicks_issuing_modals.php -->

<!-- Add Issuing Summary Modal -->
<div class="modal fade" id="addIssuingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form action="processors/chicks_issuing_crud.php" method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header text-white" style="background-color: #6f42c1;">
                    <h5 class="modal-header-title fw-bold m-0"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Log Chicks Issuing Summary</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12"><h6 class="fw-bold text-dark border-bottom pb-2 mb-0"><i class="bi bi-info-circle me-2"></i>1. Issue Information & Stock Details</h6></div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Reporting Month <span class="text-danger">*</span></label>
                            <input type="month" name="record_month" class="form-control" value="<?= date('Y-m') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Issue Date</label>
                            <input type="date" name="issue_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Name of Range</label>
                            <input type="text" name="name_of_range" class="form-control" placeholder="e.g. Range A / Northern Range">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Batch No. <span class="text-danger">*</span></label>
                            <input type="text" name="batch_no" class="form-control" placeholder="e.g. BATCH-2026-001" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">No. of Eggs Hatched</label>
                            <input type="number" name="no_of_eggs_hatched" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Starting Balance of Month</label>
                            <input type="number" name="starting_balance_of_month" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-danger">Deaths Before Sexing</label>
                            <input type="number" name="deaths_before_sexing" class="form-control border-danger" min="0" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Received Count</label>
                            <input type="number" name="received" class="form-control" min="0" value="0">
                        </div>

                        <div class="col-12 mt-4"><h6 class="fw-bold text-primary border-bottom pb-2 mb-0"><i class="bi bi-check-circle me-2"></i>2. No. of Live Chicks Count</h6></div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-primary">Live Chicks - Pullets</label>
                            <input type="number" name="live_chicks_pullets" class="form-control border-primary" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-primary">Live Chicks - Cockerels</label>
                            <input type="number" name="live_chicks_cockerels" class="form-control border-primary" min="0" value="0">
                        </div>

                        <div class="col-12 mt-4"><h6 class="fw-bold text-danger border-bottom pb-2 mb-0"><i class="bi bi-x-circle me-2"></i>3. Total Deaths From Sexing To Issue</h6></div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-danger">Deaths - Sexing Pullets</label>
                            <input type="number" name="deaths_sexing_pullets" class="form-control border-danger" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-danger">Deaths - Sexing Cockerels</label>
                            <input type="number" name="deaths_sexing_cockerels" class="form-control border-danger" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-danger">Deaths - Sexing Unsexed</label>
                            <input type="number" name="deaths_sexing_unsexed" class="form-control border-danger" min="0" value="0">
                        </div>

                        <div class="col-12 mt-4"><h6 class="fw-bold text-success border-bottom pb-2 mb-0"><i class="bi bi-box-arrow-up-right me-2"></i>4. No. of Live Chicks Issued (Categories)</h6></div>
                        
                        <!-- Day Old Category -->
                        <div class="col-12"><span class="badge bg-warning text-dark px-3 py-2 fs-6">Day Old (D/O) Category</span></div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-success">D/O Pullets</label>
                            <input type="number" name="do_pullets" id="add_do_pullets" class="form-control border-success add-chick-qty" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-success">D/O Cockerels</label>
                            <input type="number" name="do_cockerels" id="add_do_cockerels" class="form-control border-success add-chick-qty" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-success">D/O Unsexed</label>
                            <input type="number" name="do_unsexed" id="add_do_unsexed" class="form-control border-success add-chick-qty" min="0" value="0">
                        </div>

                        <!-- Week Old Category -->
                        <div class="col-12 mt-3"><span class="badge bg-info text-dark px-3 py-2 fs-6">Week Old Category</span></div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-success">Week Old Pullets</label>
                            <input type="number" name="wo_pullets" id="add_wo_pullets" class="form-control border-success add-chick-qty" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-success">Week Old Cockerels</label>
                            <input type="number" name="wo_cockerels" id="add_wo_cockerels" class="form-control border-success add-chick-qty" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-success">Week Old Unsexed</label>
                            <input type="number" name="wo_unsexed" id="add_wo_unsexed" class="form-control border-success add-chick-qty" min="0" value="0">
                        </div>

                        <!-- Month Old Category -->
                        <div class="col-12 mt-3"><span class="badge bg-secondary text-white px-3 py-2 fs-6">Month Old Category</span></div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-success">Month Old Pullets</label>
                            <input type="number" name="mo_pullets" id="add_mo_pullets" class="form-control border-success add-chick-qty" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-success">Month Old Cockerels</label>
                            <input type="number" name="mo_cockerels" id="add_mo_cockerels" class="form-control border-success add-chick-qty" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-success">Month Old Unsexed</label>
                            <input type="number" name="mo_unsexed" id="add_mo_unsexed" class="form-control border-success add-chick-qty" min="0" value="0">
                        </div>

                        <!-- Financials -->
                        <div class="col-12 mt-4"><h6 class="fw-bold text-dark border-bottom pb-2 mb-0"><i class="bi bi-cash-stack me-2"></i>5. Pricing & Financials</h6></div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Rate (Price per Chick)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rs.</span>
                                <input type="number" step="0.01" name="rate" id="add_rate" class="form-control fw-bold" min="0" value="0.00">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Total Amount (Auto-Calculated)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold">Rs.</span>
                                <input type="number" step="0.01" name="total_amount" id="add_total_amount" class="form-control fw-bold bg-light text-primary" readonly value="0.00">
                            </div>
                            <small class="text-muted">Calculated as: Total Sum of Chicks (9 Categories) &times; Rate</small>
                        </div>

                        <div class="col-12 mt-3">
                            <label class="form-label fw-bold">Remarks / Notes</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. Special notes on hatchability, transport, or range distribution..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-white fw-bold" style="background-color: #6f42c1;">Save Issuing Summary</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Issuing Summary Modal -->
<div class="modal fade" id="editIssuingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form action="processors/chicks_issuing_crud.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_issuing_id">
                <div class="modal-header text-white" style="background-color: #6f42c1;">
                    <h5 class="modal-header-title fw-bold m-0"><i class="bi bi-pencil-square me-2"></i>Edit Chicks Issuing Summary Record</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12"><h6 class="fw-bold text-dark border-bottom pb-2 mb-0"><i class="bi bi-info-circle me-2"></i>1. Issue Information & Stock Details</h6></div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Reporting Month <span class="text-danger">*</span></label>
                            <input type="month" name="record_month" id="edit_issuing_record_month" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Issue Date</label>
                            <input type="date" name="issue_date" id="edit_issuing_issue_date" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Name of Range</label>
                            <input type="text" name="name_of_range" id="edit_issuing_name_of_range" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Batch No. <span class="text-danger">*</span></label>
                            <input type="text" name="batch_no" id="edit_issuing_batch_no" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">No. of Eggs Hatched</label>
                            <input type="number" name="no_of_eggs_hatched" id="edit_issuing_eggs_hatched" class="form-control" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Starting Balance of Month</label>
                            <input type="number" name="starting_balance_of_month" id="edit_issuing_starting_bal" class="form-control" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-danger">Deaths Before Sexing</label>
                            <input type="number" name="deaths_before_sexing" id="edit_issuing_deaths_before_sex" class="form-control border-danger" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Received Count</label>
                            <input type="number" name="received" id="edit_issuing_received" class="form-control" min="0">
                        </div>

                        <div class="col-12 mt-4"><h6 class="fw-bold text-primary border-bottom pb-2 mb-0"><i class="bi bi-check-circle me-2"></i>2. No. of Live Chicks Count</h6></div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-primary">Live Chicks - Pullets</label>
                            <input type="number" name="live_chicks_pullets" id="edit_issuing_live_pullets" class="form-control border-primary" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-primary">Live Chicks - Cockerels</label>
                            <input type="number" name="live_chicks_cockerels" id="edit_issuing_live_cockerels" class="form-control border-primary" min="0">
                        </div>

                        <div class="col-12 mt-4"><h6 class="fw-bold text-danger border-bottom pb-2 mb-0"><i class="bi bi-x-circle me-2"></i>3. Total Deaths From Sexing To Issue</h6></div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-danger">Deaths - Sexing Pullets</label>
                            <input type="number" name="deaths_sexing_pullets" id="edit_issuing_deaths_pullets" class="form-control border-danger" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-danger">Deaths - Sexing Cockerels</label>
                            <input type="number" name="deaths_sexing_cockerels" id="edit_issuing_deaths_cockerels" class="form-control border-danger" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-danger">Deaths - Sexing Unsexed</label>
                            <input type="number" name="deaths_sexing_unsexed" id="edit_issuing_deaths_unsexed" class="form-control border-danger" min="0">
                        </div>

                        <div class="col-12 mt-4"><h6 class="fw-bold text-success border-bottom pb-2 mb-0"><i class="bi bi-box-arrow-up-right me-2"></i>4. No. of Live Chicks Issued (Categories)</h6></div>
                        
                        <!-- Day Old Category -->
                        <div class="col-12"><span class="badge bg-warning text-dark px-3 py-2 fs-6">Day Old (D/O) Category</span></div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-success">D/O Pullets</label>
                            <input type="number" name="do_pullets" id="edit_do_pullets" class="form-control border-success edit-chick-qty" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-success">D/O Cockerels</label>
                            <input type="number" name="do_cockerels" id="edit_do_cockerels" class="form-control border-success edit-chick-qty" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-success">D/O Unsexed</label>
                            <input type="number" name="do_unsexed" id="edit_do_unsexed" class="form-control border-success edit-chick-qty" min="0">
                        </div>

                        <!-- Week Old Category -->
                        <div class="col-12 mt-3"><span class="badge bg-info text-dark px-3 py-2 fs-6">Week Old Category</span></div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-success">Week Old Pullets</label>
                            <input type="number" name="wo_pullets" id="edit_wo_pullets" class="form-control border-success edit-chick-qty" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-success">Week Old Cockerels</label>
                            <input type="number" name="wo_cockerels" id="edit_wo_cockerels" class="form-control border-success edit-chick-qty" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-success">Week Old Unsexed</label>
                            <input type="number" name="wo_unsexed" id="edit_wo_unsexed" class="form-control border-success edit-chick-qty" min="0">
                        </div>

                        <!-- Month Old Category -->
                        <div class="col-12 mt-3"><span class="badge bg-secondary text-white px-3 py-2 fs-6">Month Old Category</span></div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-success">Month Old Pullets</label>
                            <input type="number" name="mo_pullets" id="edit_mo_pullets" class="form-control border-success edit-chick-qty" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-success">Month Old Cockerels</label>
                            <input type="number" name="mo_cockerels" id="edit_mo_cockerels" class="form-control border-success edit-chick-qty" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-success">Month Old Unsexed</label>
                            <input type="number" name="mo_unsexed" id="edit_mo_unsexed" class="form-control border-success edit-chick-qty" min="0">
                        </div>

                        <!-- Financials -->
                        <div class="col-12 mt-4"><h6 class="fw-bold text-dark border-bottom pb-2 mb-0"><i class="bi bi-cash-stack me-2"></i>5. Pricing & Financials</h6></div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Rate (Price per Chick)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rs.</span>
                                <input type="number" step="0.01" name="rate" id="edit_rate" class="form-control fw-bold" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Total Amount (Auto-Calculated)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold">Rs.</span>
                                <input type="number" step="0.01" name="total_amount" id="edit_total_amount" class="form-control fw-bold bg-light text-primary" readonly>
                            </div>
                        </div>

                        <div class="col-12 mt-3">
                            <label class="form-label fw-bold">Remarks / Notes</label>
                            <textarea name="remarks" id="edit_issuing_remarks" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-white fw-bold" style="background-color: #6f42c1;">Update Issuing Summary</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function setupAutoCalculate(modalId, prefix) {
        var modal = document.getElementById(modalId);
        if (!modal) return;

        var chickFields = [
            prefix + 'do_pullets', prefix + 'do_cockerels', prefix + 'do_unsexed',
            prefix + 'wo_pullets', prefix + 'wo_cockerels', prefix + 'wo_unsexed',
            prefix + 'mo_pullets', prefix + 'mo_cockerels', prefix + 'mo_unsexed'
        ];

        function recalc() {
            var sumChicks = 0;
            chickFields.forEach(function(id) {
                var input = document.getElementById(id);
                if (input) {
                    sumChicks += (parseFloat(input.value) || 0);
                }
            });

            var rateInput = document.getElementById(prefix + 'rate');
            var rate = rateInput ? (parseFloat(rateInput.value) || 0) : 0;

            var totalInput = document.getElementById(prefix + 'total_amount');
            if (totalInput) {
                totalInput.value = (sumChicks * rate).toFixed(2);
            }
        }

        chickFields.forEach(function(id) {
            var input = document.getElementById(id);
            if (input) {
                input.addEventListener('input', recalc);
            }
        });

        var rateInput = document.getElementById(prefix + 'rate');
        if (rateInput) {
            rateInput.addEventListener('input', recalc);
        }

        // Trigger initial calculation on modal open if needed
        modal.addEventListener('shown.bs.modal', recalc);
    }

    setupAutoCalculate('addIssuingModal', 'add_');
    setupAutoCalculate('editIssuingModal', 'edit_');
});
</script>
