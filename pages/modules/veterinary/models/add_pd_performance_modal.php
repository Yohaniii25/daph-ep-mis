<div class="modal fade" id="addPDPerfModal" tabindex="-1" aria-labelledby="addPDPerfLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color:#370709;">
                <h6 class="modal-title fw-bold" id="addPDPerfLabel"><i class="bi bi-file-earmark-plus me-2"></i>Add PD Performance Record</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAddPDPerf" action="processors/pd_performance_crud.php" method="POST">
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

                        <div class="col-12"><hr class="my-1 text-muted"></div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">VS / Tech Code</label>
                            <input type="text" name="vs_tech_code" class="form-control form-control-sm" placeholder="e.g. VS/BAL/012" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Cow ID</label>
                            <input type="text" name="cow_id" class="form-control form-control-sm" placeholder="e.g. COW/BAL/4512" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">AI Date (Optional)</label>
                            <input type="date" name="ai_date" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">PD Date</label>
                            <input type="date" name="pd_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Pregnancy Result</label>
                            <select name="result" class="form-select form-select-sm" required>
                                <option value="" disabled selected>-- Select Result --</option>
                                <option value="P">P (Pregnant)</option>
                                <option value="NP">NP (Not Pregnant)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-sm btn-success">Save Record</button>
                </div>
            </form>
        </div>
    </div>
</div>
