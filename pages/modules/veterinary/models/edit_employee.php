<!-- Edit Employee Modal -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #820100;">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Officer Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/update_employee.php" method="POST">
                <input type="hidden" name="id" id="edit_id">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Service Number</label>
                            <input type="text" name="service_number" id="edit_service_number" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Officer Name</label>
                            <input type="text" name="officer_name" id="edit_officer_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">User Role <span class="text-danger">*</span></label>
                            <select name="user_role" id="edit_user_role" class="form-select" required onchange="syncRoleToDesignation(this, 'edit_designation')">
                                <option value="">Select Role</option>
                                <option value="government_veterinary_surgeon">Government Veterinary Surgeon</option>
                                <option value="additional_veterinary_surgeon">Additional Veterinary Surgeon</option>
                                <option value="livestock_development_officer">Livestock Development Officer (or Instructor)</option>
                                <option value="development_officer">Development Officer</option>
                                <option value="driver">Driver</option>
                                <option value="dispensary_assistant">Dispensary Assistant</option>
                                <option value="department_laborer">Department Laborer</option>
                                <option value="night_watcher">Night Watcher</option>
                                <option value="veterinary_surgeon">Veterinary Surgeon (Legacy)</option>
                                <option value="employee">Employee (Legacy)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Designation <span class="text-danger">*</span></label>
                            <select name="designation" id="edit_designation" class="form-select" required>
                                <option value="">Select Designation</option>
                                <option value="Government Veterinary Surgeon (GVS)">Government Veterinary Surgeon (GVS)</option>
                                <option value="Additional Veterinary Surgeon (AVS)">Additional Veterinary Surgeon (AVS)</option>
                                <option value="Livestock Development Officer (or Instructor)">Livestock Development Officer (or Instructor)</option>
                                <option value="Development Officer (DO)">Development Officer (DO)</option>
                                <option value="Driver">Driver</option>
                                <option value="Dispensary Assistant">Dispensary Assistant</option>
                                <option value="Department Laborer">Department Laborer</option>
                                <option value="Night Watcher">Night Watcher</option>
                                <option value="GVS">GVS</option>
                                <option value="LDO">LDO</option>
                                <option value="LDI">LDI</option>
                                <option value="PDO">PDO</option>
                                <option value="CDO">CDO</option>
                                <option value="Watcher">Watcher</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Service Category</label>
                            <input type="text" name="service_category" id="edit_service_category" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Email Address</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Contact Number</label>
                            <input type="text" name="contact_number" id="edit_contact_number" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date of Birth</label>
                            <input type="date" name="date_of_birth" id="edit_date_of_birth" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Appointment Date</label>
                            <input type="date" name="appointment_date" id="edit_appointment_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Appointment Date to Current Position</label>
                            <input type="date" name="appointment_date_current_position" id="edit_appointment_date_current_position" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_employee" class="btn text-light px-4" style="color: white; background-color: #820100;">Update Officer Details</button>
                </div>
            </form>
        </div>
    </div>
</div>
