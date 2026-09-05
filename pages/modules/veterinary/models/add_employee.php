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

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Unit <span class="text-danger">*</span></label>
                            <select name="unit" class="form-select" required>
                                <option value="" disabled selected>-- Select Unit --</option>
                                <option value="provincial_director">Provincial Director</option>
                                <option value="additional_provincial_director">Additional Provincial Director</option>
                                <option value="subject_matter_specialist">Subject Matter Specialist</option>
                                <option value="deputy_director_hq_1">Deputy Director - H/Q-1</option>
                                <option value="deputy_director_hq_2">Deputy Director - H/Q-2</option>
                                <option value="deputy_director_district">Deputy Director - District</option>
                                <option value="range_veterinary_officer">Range Veterinary Officer</option>
                                <option value="training_centers">Training Centers</option>
                                <option value="regional_farms">Regional Farms</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Service Number</label>
                            <input type="text" name="service_number" class="form-control" placeholder="e.g. 025" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Officer Name</label>
                            <input type="text" name="officer_name" class="form-control" placeholder="e.g. Mr. A. Perera" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">User Role <span class="text-danger">*</span></label>
                            <select name="user_role" id="add_user_role" class="form-select" required onchange="syncRoleToDesignation(this, 'add_designation')">
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
                            <label class="form-label small fw-bold">Designation <span class="text-danger">*</span></label>
                            <select name="designation" id="add_designation" class="form-select" required>
                                <option value="">Select Designation</option>
                                <option value="Government Veterinary Surgeon (GVS)">Government Veterinary Surgeon (GVS)</option>
                                <option value="Additional Veterinary Surgeon (AVS)">Additional Veterinary Surgeon (AVS)</option>
                                <option value="Livestock Development Officer (or Instructor)">Livestock Development Officer (or Instructor)</option>
                                <option value="Development Officer (DO)">Development Officer (DO)</option>
                                <option value="Driver">Driver</option>
                                <option value="Dispensary Assistant">Dispensary Assistant</option>
                                <option value="Department Laborer">Department Laborer</option>
                                <option value="Night Watcher">Night Watcher</option>
                            </select>
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