<!-- Edit Leave Modal -->
<div class="modal fade" id="editLeaveModal" tabindex="-1" aria-labelledby="editLeaveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold" id="editLeaveModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit Leave Request
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editLeaveForm" action="../../../controllers/employee/update_leave.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="leave_id" id="edit_leave_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">From Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="start_date" id="edit_from_date" required onchange="calculateEditDays()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">To Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="resume_date" id="edit_to_date" required onchange="calculateEditDays()">
                        </div>
                        <div class="col-md-12">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="is_half_day" id="edit_is_half_day" value="1" onchange="toggleEditHalfDay()">
                                <label class="form-check-label" for="edit_is_half_day">
                                    Half Day Leave
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Leave Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="leave_type" id="edit_leave_type" required>
                                <option value="">Select Leave Type</option>
                                <option value="Casual">Casual Leave (24 days available)</option>
                                <option value="Sick">Sick Leave (24 days available)</option>
                                <option value="Foreign">Foreign Leave (Unlimited)</option>
                                <option value="Duty">Duty Leave (Unlimited)</option>
                                <option value="Maternity">Maternity Leave (Unlimited)</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">No of Days</label>
                            <input type="text" class="form-control" name="no_of_days" id="edit_no_of_days" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="reason" id="edit_reason" rows="3" placeholder="Enter reason for leave" required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-save me-2"></i>Update Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>