<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #820100;">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Register New Officer (<?= htmlspecialchars($range_name) ?>)</h5>
                <button type="button" class="btn-close btn-close-light" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/save_employee.php" method="POST">
                <input type="hidden" name="district_id" value="<?= htmlspecialchars($_SESSION['district_id']) ?>">
                <input type="hidden" name="range_id" value="<?= htmlspecialchars($range_id) ?>">

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
                            <label class="form-label small fw-bold">Designation</label>
                            <input type="text" name="designation" class="form-control" placeholder="e.g. LDO / AMN" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">User Role</label>
                            <select name="user_role" class="form-select" required>
                                <option value="">Select Role</option>
                                <option value="employee">Employee</option>
                                <option value="training_officer">Training Officer</option>
                                <option value="sms">Subject Matter Specialist</option>
                                <option value="farms_dd">Farms DD</option>
                                <option value="finance_admin">Finance Admin</option>
                                <option value="planning_officer">Planning Officer</option>
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