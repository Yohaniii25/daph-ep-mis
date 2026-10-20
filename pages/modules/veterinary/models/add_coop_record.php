<div class="modal fade" id="addSocModal" tabindex="-1" aria-labelledby="addSocModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #370709; color: white;">
                    <h5 class="modal-title" id="addSocModalLabel"><i class="bi bi-plus-circle me-2"></i>Add Livestock Society</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Report Year</label>
                            <input type="number" name="report_year" class="form-control" value="<?= date('Y') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Society Name</label>
                            <input type="text" name="society_name" class="form-control" placeholder="e.g. Balapitiya Diary Cooperative" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">President Details (Name, Tel, Addr)</label>
                            <textarea name="president_details" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Secretary Details (Name, Tel, Addr)</label>
                            <textarea name="secretary_details" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Treasurer Details (Name, Tel, Addr)</label>
                            <textarea name="treasurer_details" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Active Members Count</label>
                            <input type="number" name="active_members_count" class="form-control" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Shares Value (Rs.)</label>
                            <input type="number" step="0.01" name="shares_value_rs" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Current Savings Balance (Rs.)</label>
                            <input type="number" step="0.01" name="current_savings_balance_rs" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Outstanding Loan Balance (Rs.)</label>
                            <input type="number" step="0.01" name="outstanding_loan_balance_rs" class="form-control" value="0.00">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Society</button>
                </div>
            </div>
        </form>
    </div>
</div>