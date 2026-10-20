<div class="modal fade" id="editPDPerfModal" tabindex="-1" aria-labelledby="editPDPerfLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color:#370709;">
                <h6 class="modal-title fw-bold" id="editPDPerfLabel"><i class="bi bi-pencil-square me-2"></i>Edit PD Performance Record</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditPDPerf" action="processors/pd_performance_crud.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Report Year</label>
                            <input type="number" name="report_year" id="edit_report_year" class="form-control form-control-sm" min="2000" max="2099" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Report Month</label>
                            <select name="report_month" id="edit_report_month" class="form-select form-select-sm" required>
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
                            <input type="text" name="vs_tech_code" id="edit_vs_tech_code" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Cow ID</label>
                            <input type="text" name="cow_id" id="edit_cow_id" class="form-control form-control-sm" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">AI Date (Optional)</label>
                            <input type="date" name="ai_date" id="edit_ai_date" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">PD Date</label>
                            <input type="date" name="pd_date" id="edit_pd_date" class="form-control form-control-sm" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Pregnancy Result</label>
                            <select name="result" id="edit_result" class="form-select form-select-sm" required>
                                <option value="P">P (Pregnant)</option>
                                <option value="NP">NP (Not Pregnant)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
