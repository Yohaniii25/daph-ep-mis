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

                        <!-- Official Designation -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Official Designation <span class="text-danger">*</span></label>
                            <input type="text" name="designation" id="add_emp_designation" class="form-control form-control-sm" placeholder="e.g. Government Veterinary Surgeon" required oninput="updateAddEmpNotifPreview()">
                        </div>

                        <!-- Employment Type -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Employment Type <span class="text-danger">*</span></label>
                            <select name="employment_type" id="add_emp_employment_type" class="form-select form-select-sm" required>
                                <option value="permanent" selected>Permanent</option>
                                <option value="temporary">Temporary</option>
                            </select>
                        </div>

                        <!-- Service Category -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Service Category</label>
                            <input type="text" name="service_category" class="form-control form-control-sm" placeholder="e.g. Animal Health, Clinical, Administration">
                        </div>

                        <!-- Email Address -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control form-control-sm" placeholder="officer@daph.gov.lk" required>
                        </div>

                        <!-- Contact Number -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control form-control-sm" placeholder="07XXXXXXXX">
                        </div>

                        <!-- Date of Birth -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control form-control-sm">
                        </div>

                        <!-- Appointment Date -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Appointment Date</label>
                            <input type="date" name="appointment_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                        </div>

                        <!-- Current Position Date -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Current Position Date</label>
                            <input type="date" name="appointment_date_current_position" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
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

    document.getElementById('add_emp_unit_type').value = type;
    document.getElementById('add_emp_unit_type_id').value = id;
    document.getElementById('add_emp_range_id').value = (type === 'range') ? id : '';
    document.getElementById('add_emp_district_id').value = (type === 'district') ? id : ((type === 'range') ? dist : '');
    document.getElementById('add_emp_farm_id').value = (type === 'farm') ? id : '';
    document.getElementById('add_emp_training_center_id').value = (type === 'training') ? id : '';
}

function syncAddEmpRoleToDesignation(selectElem) {
    var val = selectElem.value;
    var desInput = document.getElementById('add_emp_designation');
    var roleTitleMap = {
        'sms': 'Subject Matter Specialist',
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
        'livestock_development_officer': 'Livestock Development Officer',
        'development_officer': 'Development Officer',
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
</script>