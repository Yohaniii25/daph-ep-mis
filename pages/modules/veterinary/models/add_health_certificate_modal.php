<div class="modal fade" id="addHealthCertModal" tabindex="-1" aria-labelledby="addHealthCertLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color:#370709;">
                <h6 class="modal-title fw-bold" id="addHealthCertLabel"><i class="bi bi-file-earmark-plus me-2"></i>Issue Health Certificate</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAddHealthCert" action="processors/health_certificate_crud.php" method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Report Year</label>
                            <input type="number" name="report_year" class="form-control form-control-sm" value="<?= date('Y') ?>" min="2000" max="2099" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Report Month</label>
                            <select name="report_month" class="form-select form-select-sm" required>
                                <option value="" disabled selected>-- Select Month --</option>
                                <option value="1">January</option>
                                <option value="2">February</option>
                                <option value="3">March</option>
                                <option value="4">April</option>
                                <option value="5">May</option>
                                <option value="6">June</option>
                                <option value="7">July</option>
                                <option value="8">August</option>
                                <option value="9">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                        </div>

                        <div class="col-12"><hr class="my-1 text-muted"></div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Health Certificate No.</label>
                            <input type="text" name="health_certificate_no" class="form-control form-control-sm" placeholder="e.g. HC/2026/1029" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date of Issue</label>
                            <input type="date" name="date_of_issue" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Farm Registration No.</label>
                            <input type="text" name="farm_registration_no" class="form-control form-control-sm" placeholder="e.g. FRN/BAL/89">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Species Details</label>
                            <input type="text" name="species" class="form-control form-control-sm" placeholder="e.g. Cattle, Poultry, Canine">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Applicant Name & Address</label>
                            <textarea name="applicant_name_address" class="form-control form-control-sm" rows="2" placeholder="Enter Full Name and Registered Address" required></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">No. of Male Animals</label>
                            <input type="number" name="animal_details_male" class="form-control form-control-sm" value="0" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">No. of Female Animals</label>
                            <input type="number" name="animal_details_female" class="form-control form-control-sm" value="0" min="0">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Vehicle Fitness Certificate No.</label>
                            <input type="text" name="vehicle_fitness_certificate_no" class="form-control form-control-sm" placeholder="e.g. VF/4580">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Purpose of Transport/Certificate</label>
                            <input type="text" name="purpose" class="form-control form-control-sm" placeholder="e.g. Breeding, Relocation, Slaughter">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-sm btn-success">Save Record</button>
                </div>
            </form>
        </div>
    </div>
</div>
