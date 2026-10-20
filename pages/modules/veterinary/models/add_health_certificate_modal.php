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

                        <!-- Farmer NIC Integration & Auto-Pull Section -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">
                                Farmer Identity Card Number (NIC) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white text-muted"><i class="bi bi-person-badge-fill text-primary"></i></span>
                                <input type="text" 
                                       name="farmer_nic" 
                                       id="add_hc_farmer_nic" 
                                       class="form-control form-control-sm font-monospace fw-bold" 
                                       placeholder="e.g. 198214502391 or 765421980V" 
                                       autocomplete="off" 
                                       required>
                                <button class="btn btn-outline-secondary btn-sm" type="button" id="btn_lookup_farmer_nic" title="Lookup Farmer">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <small class="text-muted" style="font-size: 11px;">
                                    Auto-fills Farm Reg No., Address &amp; Animal Counts
                                </small>
                                <span id="add_hc_nic_status" class="badge bg-secondary-subtle text-secondary small" style="display: none;"></span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Health Certificate No. <span class="text-danger">*</span></label>
                            <input type="text" name="health_certificate_no" class="form-control form-control-sm" placeholder="e.g. HC/2026/1029" required>
                        </div>

                        <!-- Live Farmer Details & Animal Counts Auto-Pull Card (Collapsible / Dynamic) -->
                        <div class="col-12" id="add_hc_farmer_info_card" style="display: none;">
                            <div class="p-3 rounded-3 border bg-light-subtle shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold">
                                        <i class="bi bi-check-circle-fill me-1"></i>Farmer Profile Linked
                                    </span>
                                    <small class="text-muted" id="add_hc_farmer_reg_display"></small>
                                </div>
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-12">
                                        <small class="text-muted d-block fw-semibold" style="font-size: 11px;">CURRENT REGISTERED ANIMAL POPULATION</small>
                                        <div class="d-flex flex-wrap gap-2 mt-1" id="add_hc_animal_counts_badges">
                                            <!-- Dynamically populated -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Farm Registration No.</label>
                            <input type="text" name="farm_registration_no" id="add_hc_farm_registration_no" class="form-control form-control-sm font-monospace" placeholder="e.g. FRN/EP/KAN/001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date of Issue <span class="text-danger">*</span></label>
                            <input type="date" name="date_of_issue" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Species Details</label>
                            <input type="text" name="species" id="add_hc_species" class="form-control form-control-sm" placeholder="e.g. Cattle, Buffalo, Goat, Poultry">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Vehicle Fitness Certificate No.</label>
                            <input type="text" name="vehicle_fitness_certificate_no" class="form-control form-control-sm" placeholder="e.g. VF/4580">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Applicant Name &amp; Address <span class="text-danger">*</span></label>
                            <textarea name="applicant_name_address" id="add_hc_applicant_name_address" class="form-control form-control-sm" rows="2" placeholder="Enter Full Name and Registered Address" required></textarea>
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
