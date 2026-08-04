<!-- pages/modules/farm/models/chicks_issuing_modals.php -->

<!-- Add Issuing Summary Modal -->
<div class="modal fade" id="addIssuingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form action="processors/chicks_issuing_crud.php" method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header text-white" style="background-color: #6f42c1;">
                    <h5 class="modal-header-title fw-bold m-0"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Log Chicks Issuing Monthly Summary</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12"><h6 class="fw-bold text-dark border-bottom pb-2 mb-0"><i class="bi bi-info-circle me-2"></i>1. Month & Hatchery / Stock Details</h6></div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Reporting Month <span class="text-danger">*</span></label>
                            <input type="month" name="record_month" class="form-control" value="<?= date('Y-m') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Batch No. <span class="text-danger">*</span></label>
                            <input type="text" name="batch_no" class="form-control" placeholder="e.g. BATCH-2026-001" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">No. of Eggs Hatched</label>
                            <input type="number" name="no_of_eggs_hatched" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Starting Balance of Month</label>
                            <input type="number" name="starting_balance_of_month" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-danger">Deaths Before Sexing</label>
                            <input type="number" name="deaths_before_sexing" class="form-control border-danger" min="0" value="0">
                        </div>
                        <div class="col-md-4">
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

                        <div class="col-12 mt-4"><h6 class="fw-bold text-success border-bottom pb-2 mb-0"><i class="bi bi-box-arrow-up-right me-2"></i>4. No. of Live Chicks Issued</h6></div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-success">Issued Cockerels / Pullets</label>
                            <input type="number" name="issue_cockerels_pullets" class="form-control border-success" min="0" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-success">Issued Day-Old Unsexed</label>
                            <input type="number" name="issue_day_old_unsex" class="form-control border-success" min="0" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-success">Issued Day-Old Cockerels</label>
                            <input type="number" name="issue_day_old_cockerel" class="form-control border-success" min="0" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-success">Issued Month-Old Unsexed</label>
                            <input type="number" name="issue_month_old_unsexed" class="form-control border-success" min="0" value="0">
                        </div>

                        <div class="col-12 mt-3">
                            <label class="form-label fw-bold">Remarks / Notes</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. Special notes on hatchability or transport issues..."></textarea>
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
                        <div class="col-12"><h6 class="fw-bold text-dark border-bottom pb-2 mb-0"><i class="bi bi-info-circle me-2"></i>1. Month & Hatchery / Stock Details</h6></div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Reporting Month <span class="text-danger">*</span></label>
                            <input type="month" name="record_month" id="edit_issuing_record_month" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Batch No. <span class="text-danger">*</span></label>
                            <input type="text" name="batch_no" id="edit_issuing_batch_no" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">No. of Eggs Hatched</label>
                            <input type="number" name="no_of_eggs_hatched" id="edit_issuing_eggs_hatched" class="form-control" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Starting Balance of Month</label>
                            <input type="number" name="starting_balance_of_month" id="edit_issuing_starting_bal" class="form-control" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-danger">Deaths Before Sexing</label>
                            <input type="number" name="deaths_before_sexing" id="edit_issuing_deaths_before_sex" class="form-control border-danger" min="0">
                        </div>
                        <div class="col-md-4">
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

                        <div class="col-12 mt-4"><h6 class="fw-bold text-success border-bottom pb-2 mb-0"><i class="bi bi-box-arrow-up-right me-2"></i>4. No. of Live Chicks Issued</h6></div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-success">Issued Cockerels / Pullets</label>
                            <input type="number" name="issue_cockerels_pullets" id="edit_issuing_issue_cock_pullets" class="form-control border-success" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-success">Issued Day-Old Unsexed</label>
                            <input type="number" name="issue_day_old_unsex" id="edit_issuing_issue_day_unsex" class="form-control border-success" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-success">Issued Day-Old Cockerels</label>
                            <input type="number" name="issue_day_old_cockerel" id="edit_issuing_issue_day_cock" class="form-control border-success" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-success">Issued Month-Old Unsexed</label>
                            <input type="number" name="issue_month_old_unsexed" id="edit_issuing_issue_month_unsex" class="form-control border-success" min="0">
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
