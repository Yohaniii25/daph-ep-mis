<!-- Leave Request Modal -->
<div class="modal fade" id="leaveRequestModal" tabindex="-1" aria-labelledby="leaveRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="leaveRequestModalLabel">
                    <i class="bi bi-calendar-plus me-2"></i>Request New Leave
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="leaveRequestForm" action="processors/save_leave.php" method="POST">
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3" style="font-size: 0.85rem;">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Your Working Hours:</strong><br>
                        Mon - Fri: <?= $work_hours[$work_group]['mon_fri'] ?> | Sat: <?= $work_hours[$work_group]['sat'] ?>
                    </div>
                    
                    <div class="row g-3">
                        <input type="hidden" name="user_id" value="<?= $user_id ?>">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">From Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="start_date" id="from_date" required onchange="calculateDays()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">To Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="resume_date" id="to_date" required onchange="calculateDays()">
                        </div>
                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_half_day" name="is_half_day" value="1" onchange="toggleHalfDay()">
                                <label class="form-check-label fw-bold" for="is_half_day">Apply for Half Day</label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Leave Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="leave_type" id="leave_type" required>
                                <option value="">Select Leave Type</option>
                                <option value="Casual">Casual Leave</option>
                                <option value="Sick">Sick Leave</option>
                                <option value="Foreign">Foreign Leave</option>
                                <option value="Duty">Duty Leave</option>
                                <option value="Maternity">Maternity Leave</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">No of Days</label>
                            <input type="text" class="form-control" name="no_of_days" id="no_of_days" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Acting Officer</label>
                            <select class="form-select" name="acting_user_id">
                                <option value="">Select Acting Officer</option>
                                <?php while ($acting = $acting_result->fetch_assoc()): ?>
                                    <option value="<?= $acting['id'] ?>"><?= htmlspecialchars($acting['full_name']) ?> (<?= htmlspecialchars($acting['designation']) ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="reason" rows="2" placeholder="Enter reason for leave" required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="submit_leave" class="btn btn-success">
                        <i class="bi bi-send me-2"></i>Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>