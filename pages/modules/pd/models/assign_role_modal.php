<!-- 
  pages/modules/pd/models/assign_role_modal.php
  Dedicated Provincial Director Role & Designation Assignment Modal
  Triggers automated assignment notification directly to the selected officer
-->
<div class="modal fade" id="assignRoleModal" tabindex="-1" aria-labelledby="assignRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #820100 0%, #4a0303 100%);">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="assignRoleModalLabel">
                        <i class="bi bi-person-badge-fill me-2"></i>Officer Role & Designation Assignment
                    </h5>
                    <small class="text-white-50">Updates live system role credentials and dispatches automated direct notification</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="pdAssignRoleForm" action="processors/update_user_role.php" method="POST">
                <input type="hidden" name="user_id" id="modal_user_id">
                <input type="hidden" name="ajax" value="1">

                <div class="modal-body p-4 bg-light">
                    <!-- Officer Profile Quick Info Banner -->
                    <div class="card border-0 shadow-sm p-3 mb-3 bg-white">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="fw-bold mb-1 text-dark" id="modal_officer_name_display">Officer Name</h6>
                                <small class="text-muted" id="modal_officer_email_display">email@daph.gov.lk</small>
                            </div>
                            <span class="badge px-3 py-2" id="modal_current_role_badge" style="background-color: #faebeb; color: #721c24; border: 1px solid #f5c6cb;">Current Role</span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Full Name</label>
                            <input type="text" name="full_name" id="modal_full_name" class="form-control form-control-sm" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-danger">Assign Role *</label>
                            <select name="role" id="modal_role" class="form-select form-select-sm" required>
                                <option value="">-- Select System Role --</option>
                                <optgroup label="Top Provincial Leadership">
                                    <option value="provincial_director">Provincial Director</option>
                                    <option value="sms">Subject Matter Specialist (SMS)</option>
                                    <option value="deputy_director_hq_1">Deputy Director H/Q-1 (Operations)</option>
                                    <option value="deputy_director_hq_2">Deputy Director H/Q-2 (Planning & Health)</option>
                                    <option value="administrator">System Administrator</option>
                                </optgroup>
                                <optgroup label="District & Clinical Leadership">
                                    <option value="district_dd">District Deputy Director</option>
                                    <option value="veterinary_surgeon">Veterinary Surgeon (Range VS)</option>
                                    <option value="government_veterinary_surgeon">Government Veterinary Surgeon (GVS)</option>
                                    <option value="additional_veterinary_surgeon">Additional Veterinary Surgeon (AVS)</option>
                                </optgroup>
                                <optgroup label="Institutional & Operational Officers">
                                    <option value="farms_dd">Deputy Director (Farms Operation)</option>
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
                                    <option value="employee">Employee / Staff</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Official Designation / Position Title *</label>
                            <input type="text" name="designation" id="modal_designation" class="form-control form-control-sm" placeholder="e.g. Subject Matter Specialist" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">District Jurisdiction</label>
                            <select name="district_id" id="modal_district_id" class="form-select form-select-sm" onchange="filterModalRanges(this.value)">
                                <option value="">Provincial / All Districts</option>
                                <?php if (!empty($districts)): ?>
                                    <?php foreach ($districts as $d): ?>
                                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="col-md-6" id="group_range">
                            <label class="form-label small fw-bold text-dark">Veterinary Range (Field Offices)</label>
                            <select name="range_id" id="modal_range_id" class="form-select form-select-sm">
                                <option value="">Not Applicable / Headquarters</option>
                                <?php if (!empty($ranges)): ?>
                                    <?php foreach ($ranges as $r): ?>
                                        <option value="<?= $r['id'] ?>" data-district="<?= $r['district_id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="col-md-6" id="group_farm">
                            <label class="form-label small fw-bold text-dark">Regional Farm</label>
                            <select name="farm_id" id="modal_farm_id" class="form-select form-select-sm">
                                <option value="">Not Applicable</option>
                                <?php if (!empty($farms)): ?>
                                    <?php foreach ($farms as $f): ?>
                                        <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['farm_name']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="col-md-6" id="group_training">
                            <label class="form-label small fw-bold text-dark">Training Center</label>
                            <select name="training_center_id" id="modal_training_center_id" class="form-select form-select-sm">
                                <option value="">Not Applicable</option>
                                <?php if (!empty($training_centers)): ?>
                                    <?php foreach ($training_centers as $tc): ?>
                                        <option value="<?= $tc['id'] ?>"><?= htmlspecialchars($tc['center_name']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Service Number / Employee ID</label>
                            <input type="text" name="service_number" id="modal_service_number" class="form-control form-control-sm" placeholder="e.g. EP-DAPH-104">
                        </div>
                    </div>

                    <!-- Direct Notification Preview Callout -->
                    <div class="alert alert-info border-0 mt-3 mb-0 small d-flex align-items-start gap-2 shadow-sm">
                        <i class="bi bi-bell-fill fs-5 text-primary"></i>
                        <div>
                            <strong>Automated Direct Notification:</strong> Saving will immediately dispatch an in-app notification bell alert stating:
                            <div class="p-2 mt-1 rounded bg-white border font-monospace text-dark fw-bold" id="preview_notification_text">
                                "You are assigned as the ..."
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm px-4 text-white" id="saveAssignmentBtn" style="background-color: #820100;">
                        <i class="bi bi-check2-circle me-1"></i>Confirm & Dispatch Notification
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function filterModalRanges(districtId) {
    var rangeSelect = document.getElementById('modal_range_id');
    var options = rangeSelect.getElementsByTagName('option');
    for (var i = 1; i < options.length; i++) {
        var opt = options[i];
        var optDist = opt.getAttribute('data-district');
        if (!districtId || optDist == districtId) {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
        }
    }
}
</script>
