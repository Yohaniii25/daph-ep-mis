<!-- 
  pages/modules/pd/models/add_employee.php
  Dedicated Provincial Director Global "Add Employee" Modal
  Replicates Veterinary Surgeon modal styling (#820100 header) with province-wide scope
-->
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #820100;">
                <div>
                    <h5 class="modal-title fw-bold" id="addEmployeeModalLabel">
                        <i class="bi bi-person-plus-fill me-2"></i>Register New Officer (Provincial Directory)
                    </h5>
                    <small class="text-white-50">Global Personnel Onboarding & Role Assignment across Eastern Province</small>
                </div>
                <button type="button" class="btn-close btn-close-light" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/save_employee.php" method="POST" id="pdAddEmployeeForm">
                <div class="modal-body p-4 bg-light">
                    <!-- Notification Banner Info -->
                    <div class="alert alert-info border-0 shadow-sm mb-3 d-flex align-items-center gap-2 py-2">
                        <i class="bi bi-bell-fill text-primary fs-5"></i>
                        <div class="small">
                            <strong>Automated Assignment Notification:</strong> The registered officer will automatically receive an in-app notification stating: 
                            <span class="badge bg-white text-dark border font-monospace" id="pd_add_notif_preview">"You are assigned as the [Role/Designation]"</span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Service / Employee Number -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Service Number / Emp ID <span class="text-danger">*</span></label>
                            <input type="text" name="service_number" class="form-control" placeholder="e.g. EP-DAPH-101" required>
                        </div>

                        <!-- Officer Full Name -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Officer Name <span class="text-danger">*</span></label>
                            <input type="text" name="officer_name" id="pd_add_officer_name" class="form-control" placeholder="e.g. Dr. A. B. Perera" required>
                        </div>

                        <!-- User Role -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">User Role <span class="text-danger">*</span></label>
                            <select name="user_role" id="pd_add_user_role" class="form-select" required onchange="syncPdRoleToDesignation(this)">
                                <option value="">Select Role</option>
                                <optgroup label="Top Provincial Leadership">
                                    <option value="sms">Subject Matter Specialist (SMS)</option>
                                    <option value="deputy_director_hq_1">Deputy Director H/Q (1) - Operations</option>
                                    <option value="deputy_director_hq_2">Deputy Director H/Q (2) - Animal Health & Planning</option>
                                    <option value="provincial_director">Provincial Director</option>
                                    <option value="administrator">System Administrator</option>
                                </optgroup>
                                <optgroup label="District & Clinical Leadership">
                                    <option value="district_dd">District Deputy Director</option>
                                    <option value="veterinary_surgeon">Veterinary Surgeon</option>
                                    <option value="government_veterinary_surgeon">Government Veterinary Surgeon (GVS)</option>
                                    <option value="additional_veterinary_surgeon">Additional Veterinary Surgeon (AVS)</option>
                                </optgroup>
                                <optgroup label="Institutional & Operational Officers">
                                    <option value="farms_dd">Deputy Director (Farms)</option>
                                    <option value="training_officer">Training Officer</option>
                                    <option value="planning_officer">Planning Officer</option>
                                    <option value="finance_admin">Finance Administrator</option>
                                </optgroup>
                                <optgroup label="Range Staff & Technical Support">
                                    <option value="livestock_development_officer">Livestock Development Officer (LDO)</option>
                                    <option value="development_officer">Development Officer (DO)</option>
                                    <option value="driver">Driver</option>
                                    <option value="dispensary_assistant">Dispensary Assistant</option>
                                    <option value="department_laborer">Department Laborer</option>
                                    <option value="night_watcher">Night Watcher</option>
                                    <option value="employee">General Employee</option>
                                </optgroup>
                            </select>
                        </div>

                        <!-- Designation -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Designation <span class="text-danger">*</span></label>
                            <input type="text" name="designation" id="pd_add_designation" class="form-control" placeholder="e.g. Subject Matter Specialist" required oninput="updatePdAddNotifPreview()">
                        </div>

                        <!-- District Scope -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">District Jurisdiction</label>
                            <select name="district_id" id="pd_add_district_id" class="form-select" onchange="filterPdAddRanges(this.value)">
                                <option value="">Provincial / All Districts</option>
                                <option value="1">Amparai</option>
                                <option value="2">Batticaloa</option>
                                <option value="3">Trincomalee</option>
                            </select>
                        </div>

                        <!-- Veterinary Range -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Veterinary Range (If Assigned)</label>
                            <select name="range_id" id="pd_add_range_id" class="form-select">
                                <option value="">Not Applicable / Headquarters</option>
                                <?php if (!empty($ranges)): ?>
                                    <?php foreach ($ranges as $r): ?>
                                        <option value="<?= $r['id'] ?>" data-district="<?= $r['district_id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Regional Farm or Training Center (Optional) -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Regional Farm (Optional)</label>
                            <select name="farm_id" id="pd_add_farm_id" class="form-select">
                                <option value="">None / Not Applicable</option>
                                <?php if (!empty($farms)): ?>
                                    <?php foreach ($farms as $f): ?>
                                        <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['farm_name']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Training Center (Optional)</label>
                            <select name="training_center_id" id="pd_add_training_center_id" class="form-select">
                                <option value="">None / Not Applicable</option>
                                <?php if (!empty($training_centers)): ?>
                                    <?php foreach ($training_centers as $tc): ?>
                                        <option value="<?= $tc['id'] ?>"><?= htmlspecialchars($tc['center_name']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Service Category -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Service Category</label>
                            <input type="text" name="service_category" class="form-control" placeholder="e.g. Veterinary, Animal Health, Administration">
                        </div>

                        <!-- Email Address -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="officer@daph.gov.lk" required>
                            <small class="text-muted" style="font-size: 11px;">Will also be used to send assignment notification</small>
                        </div>

                        <!-- Contact Number -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control" placeholder="07XXXXXXXX">
                        </div>

                        <!-- Date of Birth -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control">
                        </div>

                        <!-- Appointment Date -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Appointment Date</label>
                            <input type="date" name="appointment_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>

                        <!-- Appointment Date to Current Position -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Appointment Date to Current Position</label>
                            <input type="date" name="appointment_date_current_position" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_employee" class="btn text-light px-4" style="color: white; background-color: #820100;">
                        <i class="bi bi-person-check-fill me-1"></i>Save Officer Details
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function syncPdRoleToDesignation(selectElem) {
    var val = selectElem.value;
    var desInput = document.getElementById('pd_add_designation');
    var roleTitleMap = {
        'sms': 'Subject Matter Specialist',
        'deputy_director_hq_1': 'Deputy Director H/Q (1)',
        'deputy_director_hq_2': 'Deputy Director H/Q (2)',
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
    updatePdAddNotifPreview();
}

function updatePdAddNotifPreview() {
    var des = document.getElementById('pd_add_designation').value.trim();
    var roleSelect = document.getElementById('pd_add_user_role');
    var roleText = roleSelect.options[roleSelect.selectedIndex] ? roleSelect.options[roleSelect.selectedIndex].text : '';
    var title = des || roleText || '[Role/Designation]';
    var preview = document.getElementById('pd_add_notif_preview');
    if (preview) {
        preview.textContent = '"You are assigned as the ' + title + '"';
    }
}

function filterPdAddRanges(districtId) {
    var rangeSelect = document.getElementById('pd_add_range_id');
    var options = rangeSelect.getElementsByTagName('option');
    
    for (var i = 1; i < options.length; i++) {
        var opt = options[i];
        var optDistrict = opt.getAttribute('data-district');
        if (!districtId || optDistrict == districtId) {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
        }
    }
}
</script>
