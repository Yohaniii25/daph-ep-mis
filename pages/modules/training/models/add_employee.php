<!-- Modal 1: Register New Staff Officer for Training Centre -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #370709;">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2"></i>Register New Staff Officer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="save_employee">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Service Number <span class="text-danger">*</span></label>
                            <input type="text" name="service_number" class="form-control" placeholder="e.g. TC-SRV-001" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Officer Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="officer_name" class="form-control" placeholder="e.g. Mr. K. Silva" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Designation <span class="text-danger">*</span></label>
                            <input type="text" name="designation" class="form-control" placeholder="e.g. Training Officer / Instructor" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">User Role <span class="text-danger">*</span></label>
                            <select name="user_role" class="form-select fw-bold" required>
                                <option value="training_officer" selected>Training Officer</option>
                                <option value="employee">Staff Employee</option>
                                <option value="sms">Subject Matter Specialist</option>
                                <option value="planning_officer">Planning Officer</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Employment Type <span class="text-danger">*</span></label>
                            <select name="employment_type" id="training_add_employment_type" class="form-select fw-bold" required onchange="toggleTrainingAddEmpEmploymentStatus(this.value)">
                                <option value="permanent" selected>Permanent</option>
                                <option value="temporary">Temporary</option>
                            </select>
                        </div>

                        <!-- Conditional Employment Status (Renders only when Employee Type is Permanent) -->
                        <div class="col-md-6" id="training_add_emp_employment_status_col" style="display: block;">
                            <label class="form-label small fw-bold text-primary">
                                <i class="bi bi-briefcase-fill me-1"></i>Employment Status <span class="text-danger">*</span>
                            </label>
                            <select name="employment_status" id="training_add_emp_employment_status" class="form-select border-primary" onchange="toggleTrainingAddEmpAttachmentReason(this.value)">
                                <option value="Permanent" selected>Permanent</option>
                                <option value="Attachment">Attachment</option>
                            </select>
                            <small class="text-muted" style="font-size: 11px;">Select status for permanent cadre personnel</small>
                        </div>

                        <!-- Secondary Conditional Trigger: Reason for Attachment (Renders only when Employment Status is Attachment) -->
                        <div class="col-md-12" id="training_add_emp_attachment_reason_col" style="display: none;">
                            <label class="form-label small fw-bold text-danger">
                                <i class="bi bi-pin-angle-fill me-1 text-danger"></i>Reason for Attachment <span class="text-danger">*</span>
                            </label>
                            <textarea name="attachment_reason" id="training_add_emp_attachment_reason" class="form-control border-danger" rows="2" placeholder="Specify the reason for attachment (e.g., medical reasons, maternity leave, urgent operational cover)..." disabled></textarea>
                            <small class="text-muted" style="font-size: 11px;">Mandatory justification for assigning an officer under temporary attachment to this station.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Service Category</label>
                            <input type="text" name="service_category" class="form-control" placeholder="e.g. Vocational Training / Extension">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="officer@daph.gov.lk" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control" placeholder="07XXXXXXXX">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Appointment Date</label>
                            <input type="date" name="appointment_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Appointment Date to Current Position</label>
                            <input type="date" name="appointment_date_current_position" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Position to Current Location</label>
                            <input type="date" name="position_to_current_location" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: #370709;">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Officer Details
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleTrainingAddEmpEmploymentStatus(empTypeValue) {
    var col = document.getElementById('training_add_emp_employment_status_col');
    var statusSelect = document.getElementById('training_add_emp_employment_status');
    if (!col || !statusSelect) return;

    if (empTypeValue && empTypeValue.toLowerCase() === 'permanent') {
        col.style.display = 'block';
        statusSelect.disabled = false;
        if (!statusSelect.value) {
            statusSelect.value = 'Permanent';
        }
        toggleTrainingAddEmpAttachmentReason(statusSelect.value);
    } else {
        col.style.display = 'none';
        statusSelect.disabled = true;
        statusSelect.value = '';
        toggleTrainingAddEmpAttachmentReason('');
    }
}

function toggleTrainingAddEmpAttachmentReason(statusVal) {
    var reasonCol = document.getElementById('training_add_emp_attachment_reason_col');
    var reasonInput = document.getElementById('training_add_emp_attachment_reason');
    if (!reasonCol || !reasonInput) return;

    if (statusVal === 'Attachment') {
        reasonCol.style.display = 'block';
        reasonInput.disabled = false;
        reasonInput.required = true;
    } else {
        reasonCol.style.display = 'none';
        reasonInput.disabled = true;
        reasonInput.required = false;
        reasonInput.value = '';
    }
}
</script>
