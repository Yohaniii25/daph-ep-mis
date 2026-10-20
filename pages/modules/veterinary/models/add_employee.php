<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #820100;">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Register New Officer (<?= htmlspecialchars($range_name) ?>)</h5>
                <button type="button" class="btn-close btn-close-light" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/save_employee.php" method="POST">
                <input type="hidden" name="district_id" value="<?= htmlspecialchars($_SESSION['district_id'] ?? $district_id ?? '') ?>">
                <input type="hidden" name="range_id" value="<?= htmlspecialchars($_SESSION['range_id'] ?? $range_id ?? '') ?>">
                <input type="hidden" name="unit" value="range_veterinary_officer">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Service Number</label>
                            <input type="text" name="service_number" class="form-control" placeholder="e.g. 025" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Officer Name</label>
                            <input type="text" name="officer_name" class="form-control" placeholder="e.g. Mr. A. Perera" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Designation <span class="text-danger">*</span></label>
                            <select name="user_role" id="add_user_role" class="form-select" required>
                                <option value="">Select Role</option>
                                <option value="government_veterinary_surgeon">Government Veterinary Surgeon</option>
                                <option value="additional_veterinary_surgeon">Additional Veterinary Surgeon</option>
                                <option value="livestock_development_officer">Livestock Development Officer (or Instructor)</option>
                                <option value="development_officer">Development Officer</option>
                                <option value="driver">Driver</option>
                                <option value="dispensary_assistant">Dispensary Assistant</option>
                                <option value="department_laborer">Department Laborer</option>
                                <option value="night_watcher">Night Watcher</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Employment Type <span class="text-danger">*</span></label>
                            <select name="employment_type" id="vet_add_employment_type" class="form-select" required onchange="toggleVetAddEmpEmploymentStatus(this.value)">
                                <option value="permanent" selected>Permanent</option>
                                <option value="temporary">Temporary</option>
                            </select>
                        </div>

                        <!-- Conditional Employment Status (Renders only when Employee Type is Permanent) -->
                        <div class="col-md-6" id="vet_add_emp_employment_status_col" style="display: block;">
                            <label class="form-label small fw-bold text-primary">
                                <i class="bi bi-briefcase-fill me-1"></i>Employment Status <span class="text-danger">*</span>
                            </label>
                            <select name="employment_status" id="vet_add_emp_employment_status" class="form-select border-primary" onchange="toggleVetAddEmpAttachmentReason(this.value)">
                                <option value="Permanent" selected>Permanent</option>
                                <option value="Attachment">Attachment</option>
                            </select>
                            <small class="text-muted" style="font-size: 11px;">Select status for permanent cadre personnel</small>
                        </div>

                        <!-- Secondary Conditional Trigger: Reason for Attachment (Renders only when Employment Status is Attachment) -->
                        <div class="col-md-12" id="vet_add_emp_attachment_reason_col" style="display: none;">
                            <label class="form-label small fw-bold text-danger">
                                <i class="bi bi-pin-angle-fill me-1 text-danger"></i>Reason for Attachment <span class="text-danger">*</span>
                            </label>
                            <textarea name="attachment_reason" id="vet_add_emp_attachment_reason" class="form-control border-danger" rows="2" placeholder="Specify the reason for attachment (e.g., medical reasons, maternity leave, urgent operational cover)..." disabled></textarea>
                            <small class="text-muted" style="font-size: 11px;">Mandatory justification for assigning an officer under temporary attachment to this station.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Service Category</label>
                            <input type="text" name="service_category" class="form-control" placeholder="e.g. Veterinary">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Email Address</label>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_employee" class="btn text-light px-4" style="color: white; background-color: #820100;">Save Officer Details</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleVetAddEmpEmploymentStatus(empTypeValue) {
    var col = document.getElementById('vet_add_emp_employment_status_col');
    var statusSelect = document.getElementById('vet_add_emp_employment_status');
    if (!col || !statusSelect) return;

    if (empTypeValue && empTypeValue.toLowerCase() === 'permanent') {
        col.style.display = 'block';
        statusSelect.disabled = false;
        if (!statusSelect.value) {
            statusSelect.value = 'Permanent';
        }
        toggleVetAddEmpAttachmentReason(statusSelect.value);
    } else {
        col.style.display = 'none';
        statusSelect.disabled = true;
        statusSelect.value = '';
        toggleVetAddEmpAttachmentReason('');
    }
}

function toggleVetAddEmpAttachmentReason(statusVal) {
    var reasonCol = document.getElementById('vet_add_emp_attachment_reason_col');
    var reasonInput = document.getElementById('vet_add_emp_attachment_reason');
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