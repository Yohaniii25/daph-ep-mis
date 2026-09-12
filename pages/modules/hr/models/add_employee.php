<?php
/**
 * pages/modules/hr/models/add_employee.php
 * Administrator Global Add Employee Modal with Comprehensive Unit / Office Selection
 */
?>
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #500707 0%, #721c24 100%);">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="addEmployeeModalLabel">
                        <i class="bi bi-person-plus-fill me-2"></i>Register New Officer & Assign Unit
                    </h5>
                    <small class="text-white-50">Global Personnel Onboarding & Direct Workstation Assignment across Eastern Province</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="processors/save_employee.php" method="POST" id="adminAddEmployeeForm">
                <!-- Hidden Foreign Key Routing Inputs -->
                <input type="hidden" name="unit_type" id="add_emp_unit_type" value="">
                <input type="hidden" name="unit_type_id" id="add_emp_unit_type_id" value="">
                <input type="hidden" name="range_id" id="add_emp_range_id" value="">
                <input type="hidden" name="district_id" id="add_emp_district_id" value="">
                <input type="hidden" name="farm_id" id="add_emp_farm_id" value="">
                <input type="hidden" name="training_center_id" id="add_emp_training_center_id" value="">

                <div class="modal-body p-4 bg-light">
                    <!-- Automated Notification Banner -->
                    <div class="alert alert-info border-0 shadow-sm mb-3 d-flex align-items-center gap-2 py-2">
                        <i class="bi bi-bell-fill text-primary fs-5"></i>
                        <div class="small">
                            <strong>Automated Assignment Notification:</strong> The registered officer will automatically receive an in-app alert stating: 
                            <span class="badge bg-white text-dark border font-monospace" id="add_emp_notif_preview">"You are assigned as the [Designation/Role]"</span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Primary Target Unit Selection Dropdown -->
                        <div class="col-12">
                            <div class="card border-0 shadow-sm p-3 bg-white border-start border-4 border-danger">
                                <label class="form-label small fw-bold text-danger mb-1">
                                    <i class="bi bi-geo-alt-fill me-1"></i>Assigned Unit / Office Jurisdiction <span class="text-danger">*</span>
                                </label>
                                <select name="assigned_unit" id="add_emp_assigned_unit" class="form-select form-select-md border-danger shadow-sm" required onchange="syncAddEmployeeUnit(this)">
                                    <option value="">-- Choose Target Unit / Office to Assign Employee --</option>

                                    <!-- 1. Range Offices -->
                                    <?php if (!empty($ranges)): ?>
                                    <optgroup label="Veterinary Range Offices (Field Jurisdictions)">
                                        <?php foreach ($ranges as $r): ?>
                                            <option value="Range Office - <?= htmlspecialchars($r['name']) ?>"
                                                    data-type="range"
                                                    data-id="<?= $r['id'] ?>"
                                                    data-district="<?= $r['district_id'] ?>">
                                                Range Office - <?= htmlspecialchars($r['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <?php endif; ?>

                                    <!-- 2. District Offices -->
                                    <?php if (!empty($districts)): ?>
                                    <optgroup label="District Secretariats / District Offices">
                                        <?php foreach ($districts as $d): ?>
                                            <option value="District Office - <?= htmlspecialchars($d['name']) ?>"
                                                    data-type="district"
                                                    data-id="<?= $d['id'] ?>">
                                                District Office - <?= htmlspecialchars($d['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <?php endif; ?>

                                    <!-- 3. Regional Farms -->
                                    <?php if (!empty($farms)): ?>
                                    <optgroup label="Regional Livestock Farms">
                                        <?php foreach ($farms as $f): ?>
                                            <option value="Regional Farm - <?= htmlspecialchars($f['farm_name']) ?>"
                                                    data-type="farm"
                                                    data-id="<?= $f['id'] ?>">
                                                Regional Farm - <?= htmlspecialchars($f['farm_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <?php endif; ?>

                                    <!-- 4. Training Centers -->
                                    <?php if (!empty($training_centers)): ?>
                                    <optgroup label="Provincial Training Centers">
                                        <?php foreach ($training_centers as $tc): ?>
                                            <option value="Training Center - <?= htmlspecialchars($tc['center_name']) ?>"
                                                    data-type="training"
                                                    data-id="<?= $tc['id'] ?>">
                                                Training Center - <?= htmlspecialchars($tc['center_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <?php endif; ?>

                                    <!-- 5. Core Operational Units -->
                                    <?php if (!empty($master_units)): ?>
                                    <optgroup label="Core Directorates & Operational Units">
                                        <?php foreach ($master_units as $mu): ?>
                                            <option value="Unit - <?= htmlspecialchars($mu['unit_name']) ?>"
                                                    data-type="core"
                                                    data-id="<?= $mu['id'] ?>">
                                                Unit - <?= htmlspecialchars($mu['unit_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <?php endif; ?>
                                </select>
                                <small class="text-muted mt-1" style="font-size: 11px;">
                                    Directly binds the officer's registry footprint to this office. State change immediately populates them into the corresponding unit registry.
                                </small>
                            </div>
                        </div>

                        <!-- Officer Full Name -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Officer Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="officer_name" id="add_emp_officer_name" class="form-control form-control-sm" placeholder="e.g. Dr. K. S. Fernando" required>
                        </div>

                        <!-- Service Number / Employee ID -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Service No / Emp ID <span class="text-danger">*</span></label>
                            <input type="text" name="service_number" class="form-control form-control-sm" placeholder="e.g. EP-DAPH-108" required>
                        </div>

                        <!-- User Role -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-danger">Assign Role <span class="text-danger">*</span></label>
                            <select name="user_role" id="add_emp_user_role" class="form-select form-select-sm" required onchange="syncAddEmpRoleToDesignation(this)">
                                <option value="">-- Select System Role --</option>
                                <optgroup label="Top Provincial Leadership">
                                    <option value="provincial_director">Provincial Director</option>
                                    <option value="sms">Subject Matter Specialist (SMS)</option>
                                    <option value="deputy_director_hq_1">Deputy Director H/Q (1) - Operations</option>
                                    <option value="deputy_director_hq_2">Deputy Director H/Q (2) - Planning</option>
                                    <option value="administrator">System Administrator</option>
                                </optgroup>
                                <optgroup label="District & Clinical Leadership">
                                    <option value="district_dd">District Deputy Director</option>
                                    <option value="veterinary_surgeon">Veterinary Surgeon (Range VS)</option>
                                    <option value="government_veterinary_surgeon">Government Veterinary Surgeon (GVS)</option>
                                    <option value="additional_veterinary_surgeon">Additional Veterinary Surgeon (AVS)</option>
                                </optgroup>
                                <optgroup label="Institutional & Operational Officers">
                                    <option value="farms_dd">Deputy Director (Farms)</option>
                                    <option value="training_officer">Training Officer</option>
                                    <option value="planning_officer">Planning Officer</option>
                                    <option value="finance_admin">Finance Administrator</option>
                                </optgroup>
                                <optgroup label="Range Staff & Field Support">
                                    <option value="livestock_development_officer">Livestock Development Officer (LDO)</option>
                                    <option value="development_officer">Development Officer (DO)</option>
                                    <option value="driver">Driver</option>
                                    <option value="dispensary_assistant">Dispensary Assistant</option>
                                    <option value="department_laborer">Department Laborer</option>
                                    <option value="night_watcher">Night Watcher</option>
                                    <option value="employee">Staff Employee</option>
                                </optgroup>
                            </select>
                        </div>

                        <!-- Official Designation (Standardized with DAPH Titles Datalist) -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Official Designation <span class="text-danger">*</span></label>
                            <input type="text" name="designation" id="add_emp_designation" list="daph_standard_designations" class="form-control form-control-sm" placeholder="Select or type official designation" required oninput="updateAddEmpNotifPreview()">
                            <datalist id="daph_standard_designations">
                                <option value="Provincial Director"></option>
                                <option value="Deputy Director H/Q (1) - Operations"></option>
                                <option value="Deputy Director H/Q (2) - Planning"></option>
                                <option value="Subject Matter Specialist (SMS)"></option>
                                <option value="District Deputy Director"></option>
                                <option value="Government Veterinary Surgeon (GVS)"></option>
                                <option value="Additional Veterinary Surgeon (AVS)"></option>
                                <option value="Veterinary Surgeon"></option>
                                <option value="Deputy Director (Farms)"></option>
                                <option value="Training Officer"></option>
                                <option value="Planning Officer"></option>
                                <option value="Finance Administrator"></option>
                                <option value="Livestock Development Officer (LDO)"></option>
                                <option value="Development Officer (DO)"></option>
                                <option value="Driver"></option>
                                <option value="Dispensary Assistant"></option>
                                <option value="Department Laborer"></option>
                                <option value="Night Watcher"></option>
                                <option value="Staff Officer"></option>
                            </datalist>
                        </div>

                        <!-- Current Station (Required Input Field) -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-danger">
                                <i class="bi bi-geo-alt-fill me-1"></i>Current Station <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="current_station" id="add_emp_current_station" class="form-control form-control-sm border-danger" placeholder="e.g. Range Office - Kalmunai / Central HQ" required>
                            <small class="text-muted" style="font-size: 11px;">Active office location / official workstation</small>
                        </div>

                        <!-- Primary Employee Type -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Employee Type <span class="text-danger">*</span></label>
                            <select name="employment_type" id="add_emp_employment_type" class="form-select form-select-sm" required onchange="toggleAddEmpEmploymentStatus(this.value)">
                                <option value="permanent" selected>Permanent</option>
                                <option value="temporary">Temporary</option>
                            </select>
                        </div>

                        <!-- Conditional Employment Status (Renders only when Employee Type is Permanent) -->
                        <div class="col-md-6" id="add_emp_employment_status_col" style="display: block;">
                            <label class="form-label small fw-bold text-primary">
                                <i class="bi bi-briefcase-fill me-1"></i>Employment Status <span class="text-danger">*</span>
                            </label>
                            <select name="employment_status" id="add_emp_employment_status" class="form-select form-select-sm border-primary" onchange="toggleAddEmpAttachmentReason(this.value)">
                                <option value="Permanent" selected>Permanent</option>
                                <option value="Attachment">Attachment</option>
                            </select>
                            <small class="text-muted" style="font-size: 11px;">Select status for permanent cadre personnel</small>
                        </div>

                        <!-- Secondary Conditional Trigger: Reason for Attachment (Renders only when Employment Status is Attachment) -->
                        <div class="col-md-12" id="add_emp_attachment_reason_col" style="display: none;">
                            <label class="form-label small fw-bold text-danger">
                                <i class="bi bi-pin-angle-fill me-1 text-danger"></i>Reason for Attachment <span class="text-danger">*</span>
                            </label>
                            <textarea name="attachment_reason" id="add_emp_attachment_reason" class="form-control form-control-sm border-danger" rows="2" placeholder="Specify the reason for attachment (e.g., medical reasons, maternity leave, urgent operational cover)..." disabled></textarea>
                            <small class="text-muted" style="font-size: 11px;">Mandatory justification for assigning an officer under temporary attachment to this station.</small>
                        </div>

                        <!-- Service Category (Standardized with DAPH Categories Datalist) -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Service Category</label>
                            <input type="text" name="service_category" id="add_emp_service_category" list="daph_standard_service_categories" class="form-control form-control-sm" placeholder="Select or type service category">
                            <datalist id="daph_standard_service_categories">
                                <option value="Animal Health & Disease Control"></option>
                                <option value="Clinical & Field Veterinary Services"></option>
                                <option value="Animal Breeding & Genetics"></option>
                                <option value="Livestock Development & Production"></option>
                                <option value="Veterinary Public Health & Epidemiology"></option>
                                <option value="Extension, Education & Training"></option>
                                <option value="Administration & Human Resources"></option>
                                <option value="Finance, Accounts & Procurement"></option>
                                <option value="Technical Field Support"></option>
                                <option value="General & Transport Services"></option>
                            </datalist>
                        </div>

                        <!-- Email Address (Standardized Contact Details) -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Official Email Address <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control" placeholder="officer@daph.ep.gov.lk" required>
                            </div>
                        </div>

                        <!-- Contact Number (Standardized Contact Details) -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Contact Number (Mobile / Office)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
                                <input type="tel" name="contact_number" class="form-control" placeholder="07XXXXXXXX" pattern="0[0-9]{9}" maxlength="10" title="10-digit Sri Lankan phone number starting with 0">
                            </div>
                            <small class="text-muted" style="font-size: 11px;">Format: 10 digits starting with 0 (e.g. 0771234567)</small>
                        </div>

                        <!-- Date of Birth -->
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control form-control-sm">
                        </div>

                        <!-- Appointment Date -->
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Appointment Date</label>
                            <input type="date" name="appointment_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                        </div>

                        <!-- Current Position Date -->
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Current Position Date</label>
                            <input type="date" name="appointment_date_current_position" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                        </div>

                        <!-- Position to Current Location -->
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Position to Current Location</label>
                            <input type="date" name="position_to_current_location" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_employee" class="btn btn-danger btn-sm px-4" style="background-color: #500707; border-color: #500707;">
                        <i class="bi bi-check2-circle me-1"></i>Save & Assign Officer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function syncAddEmployeeUnit(selectElem) {
    var opt = selectElem.options[selectElem.selectedIndex];
    if (!opt) return;

    var type = opt.getAttribute('data-type') || '';
    var id   = opt.getAttribute('data-id') || '';
    var dist = opt.getAttribute('data-district') || '';
    var unitVal = selectElem.value || '';

    document.getElementById('add_emp_unit_type').value = type;
    document.getElementById('add_emp_unit_type_id').value = id;
    document.getElementById('add_emp_range_id').value = (type === 'range') ? id : '';
    document.getElementById('add_emp_district_id').value = (type === 'district') ? id : ((type === 'range') ? dist : '');
    document.getElementById('add_emp_farm_id').value = (type === 'farm') ? id : '';
    document.getElementById('add_emp_training_center_id').value = (type === 'training') ? id : '';

    // Auto-sync Current Station with the selected Assigned Unit
    var currentStationInput = document.getElementById('add_emp_current_station');
    if (currentStationInput && unitVal) {
        currentStationInput.value = unitVal;
    }
}

// Conditional UI Logic: Render Employment Status only when Employee Type is 'permanent'
function toggleAddEmpEmploymentStatus(empTypeValue) {
    var col = document.getElementById('add_emp_employment_status_col');
    var statusSelect = document.getElementById('add_emp_employment_status');
    if (!col || !statusSelect) return;

    if (empTypeValue && empTypeValue.toLowerCase() === 'permanent') {
        col.style.display = 'block';
        statusSelect.disabled = false;
        if (!statusSelect.value) {
            statusSelect.value = 'Permanent';
        }
        toggleAddEmpAttachmentReason(statusSelect.value);
    } else {
        col.style.display = 'none';
        statusSelect.disabled = true;
        statusSelect.value = '';
        toggleAddEmpAttachmentReason('');
    }
}

// Secondary Conditional Trigger: Render Reason field only when Employment Status is 'Attachment'
function toggleAddEmpAttachmentReason(statusVal) {
    var reasonCol = document.getElementById('add_emp_attachment_reason_col');
    var reasonInput = document.getElementById('add_emp_attachment_reason');
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

function syncAddEmpRoleToDesignation(selectElem) {
    var val = selectElem.value;
    var desInput = document.getElementById('add_emp_designation');
    var roleTitleMap = {
        'sms': 'Subject Matter Specialist (SMS)',
        'deputy_director_hq_1': 'Deputy Director H/Q (1) - Operations',
        'deputy_director_hq_2': 'Deputy Director H/Q (2) - Planning',
        'provincial_director': 'Provincial Director',
        'administrator': 'System Administrator',
        'district_dd': 'District Deputy Director',
        'veterinary_surgeon': 'Veterinary Surgeon',
        'government_veterinary_surgeon': 'Government Veterinary Surgeon (GVS)',
        'additional_veterinary_surgeon': 'Additional Veterinary Surgeon (AVS)',
        'farms_dd': 'Deputy Director (Farms)',
        'training_officer': 'Training Officer',
        'planning_officer': 'Planning Officer',
        'finance_admin': 'Finance Administrator',
        'livestock_development_officer': 'Livestock Development Officer (LDO)',
        'development_officer': 'Development Officer (DO)',
        'driver': 'Driver',
        'dispensary_assistant': 'Dispensary Assistant',
        'department_laborer': 'Department Laborer',
        'night_watcher': 'Night Watcher',
        'employee': 'Staff Officer'
    };

    if (roleTitleMap[val]) {
        desInput.value = roleTitleMap[val];
    }
    updateAddEmpNotifPreview();
}

function updateAddEmpNotifPreview() {
    var des = document.getElementById('add_emp_designation').value.trim();
    var roleElem = document.getElementById('add_emp_user_role');
    var roleTxt = roleElem ? roleElem.options[roleElem.selectedIndex]?.text : '';
    var title = des ? des : (roleTxt ? roleTxt : '[Role/Designation]');
    var prevElem = document.getElementById('add_emp_notif_preview');
    if (prevElem) {
        prevElem.innerText = '"You are assigned as the ' + title + '"';
    }
}

// Initialize conditional state on DOM load
document.addEventListener('DOMContentLoaded', function() {
    var typeElem = document.getElementById('add_emp_employment_type');
    if (typeElem) {
        toggleAddEmpEmploymentStatus(typeElem.value);
    }
});
</script>