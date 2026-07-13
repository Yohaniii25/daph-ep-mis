<div class="modal fade" id="editHealthCertModal" tabindex="-1" aria-labelledby="editHealthCertLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color:#370709;">
                <h6 class="modal-title fw-bold" id="editHealthCertLabel"><i class="bi bi-pencil-square me-2"></i>Edit Issued Health Certificate</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditHealthCert" action="processors/health_certificate_crud.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Report Year</label>
                            <input type="number" name="report_year" id="edit_report_year" class="form-control form-control-sm" min="2000" max="2099" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Report Month</label>
                            <select name="report_month" id="edit_report_month" class="form-select form-select-sm" required>
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
                            <input type="text" name="health_certificate_no" id="edit_health_certificate_no" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date of Issue</label>
                            <input type="date" name="date_of_issue" id="edit_date_of_issue" class="form-control form-control-sm" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Farm Registration No.</label>
                            <input type="text" name="farm_registration_no" id="edit_farm_registration_no" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Species Details</label>
                            <input type="text" name="species" id="edit_species" class="form-control form-control-sm">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Applicant Name & Address</label>
                            <textarea name="applicant_name_address" id="edit_applicant_name_address" class="form-control form-control-sm" rows="2" required></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">No. of Male Animals</label>
                            <input type="number" name="animal_details_male" id="edit_animal_details_male" class="form-control form-control-sm" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">No. of Female Animals</label>
                            <input type="number" name="animal_details_female" id="edit_animal_details_female" class="form-control form-control-sm" min="0">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Vehicle Fitness Certificate No.</label>
                            <input type="text" name="vehicle_fitness_certificate_no" id="edit_vehicle_fitness_certificate_no" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Purpose of Transport/Certificate</label>
                            <input type="text" name="purpose" id="edit_purpose" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
