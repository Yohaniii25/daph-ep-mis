<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Register New Officer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/save_employee.php" method="POST">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Officer Name</label>
                            <input type="text" name="officer_name" class="form-control" placeholder="e.g. Mr. A. Perera" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Employee ID (Emp No)</label>
                            <input type="text" name="emp_id" class="form-control" placeholder="e.g. 025" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Designation</label>
                            <select name="designation" class="form-select" required>
                                <option value="">Select Designation</option>
                                <option value="GVS">GVS (Government Veterinary Surgeon)</option>
                                <option value="LDO">LDO (Livestock Development Officer)</option>
                                <option value="LDI">LDI (Livestock Development Instructor)</option>
                                <option value="PDO">PDO (Project Development Officer)</option>
                                <option value="Clerk">Clerk</option>
                                <option value="Driver">Driver</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="officer@daph.gov.lk">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">District</label>
                            <select id="modal_district" class="form-select" required>
                                <option value="">Select District</option>
                                <?php
                                $dist_res = $mysqli->query("SELECT * FROM districts ORDER BY name");
                                while($d = $dist_res->fetch_assoc()) {
                                    echo "<option value='{$d['id']}'>{$d['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Veterinary Range Office</label>
                            <select name="range_id" id="modal_range" class="form-select" required>
                                <option value="">Select Range Office</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control" placeholder="07XXXXXXXX">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Registration Date</label>
                            <input type="date" name="registered_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_employee" class="btn btn-success px-4 text-white">Save Officer Details</button>
                </div>
            </form>
        </div>
    </div>
</div>