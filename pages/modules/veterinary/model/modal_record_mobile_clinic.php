<!-- Modal: Record / Edit Mobile Clinic & Activities (Tab 1) -->
<div class="modal fade" id="modalRecordMobileClinic" tabindex="-1" aria-labelledby="modalRecordMobileClinicLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content border-0 shadow-lg" id="formRecordMobileClinic" autocomplete="off" onsubmit="handleSaveMobileClinic(event)">
            <!-- MODAL HEADER -->
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #370709 0%, #820100 100%);">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-white text-danger d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-truck-front-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="modalRecordMobileClinicLabel">Conduction of Mobile Clinic &amp; Activities</h5>
                        <p class="small text-white-50 mb-0">Record field clinic session, medical team, summary stats, and services conducted</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- MODAL BODY -->
            <div class="modal-body p-4 bg-light">
                <input type="hidden" name="action" value="save_mobile_clinic">
                <input type="hidden" name="id" id="clinic_edit_id" value="">

                <!-- 1. GENERAL INFORMATION & LOCATION -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <span class="badge rounded-pill bg-danger-subtle text-danger px-2 py-1">1</span>
                            General Information &amp; Clinic Schedule
                        </h6>
                        <span class="badge bg-light text-muted border">Mandatory Info</span>
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted">District</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light"><i class="bi bi-geo-alt"></i></span>
                                    <input type="text" class="form-control fw-bold bg-light" id="clinic_district_display" value="<?= htmlspecialchars($district_name) ?>" readonly>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted">Veterinary Range</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light"><i class="bi bi-building"></i></span>
                                    <input type="text" class="form-control fw-bold bg-light" id="clinic_range_display" value="<?= htmlspecialchars($range_name) ?>" readonly>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-danger">Date of Clinic <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar-event text-danger"></i></span>
                                    <input type="date" class="form-control border-start-0 fw-bold" name="clinic_date" id="clinic_date" value="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-dark">Places Visited / Village <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="places_visited" id="clinic_places_visited" placeholder="e.g., Akkaraipattu Div 04, Malwatta" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. MOBILE CLINIC TEAM MEMBERS (DYNAMIC ROW LIST) -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                                <span class="badge rounded-pill bg-danger-subtle text-danger px-2 py-1">2</span>
                                Mobile Veterinary Team Members
                            </h6>
                            <small class="text-muted">Specify clinicians, extension officers, and support staff assigned to this mobile clinic</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="addClinicTeamMemberRow()">
                            <i class="bi bi-person-plus-fill me-1"></i>Add Team Member
                        </button>
                    </div>
                    <div class="card-body p-3">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0" id="tableClinicTeamMembers">
                                <thead class="table-light text-muted small text-uppercase">
                                    <tr>
                                        <th style="width: 45px;" class="text-center">#</th>
                                        <th style="min-width: 200px;">Officer / Staff Member Name <span class="text-danger">*</span></th>
                                        <th style="width: 240px;">Designation <span class="text-danger">*</span></th>
                                        <th style="width: 170px;">Contact / Mobile</th>
                                        <th style="width: 60px;" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="clinicTeamMemberList">
                                    <!-- Dynamic Rows inserted via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 3. CLINIC SUMMARY METRICS -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <span class="badge rounded-pill bg-danger-subtle text-danger px-2 py-1">3</span>
                            Clinic Beneficiaries &amp; Case Attendance Summary
                        </h6>
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3">
                            <div class="col-sm-6 col-md-4">
                                <label class="form-label small fw-bold text-muted">Farmers Benefited</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-primary"><i class="bi bi-people-fill"></i></span>
                                    <input type="number" min="0" class="form-control fw-bold" name="farmers_benefited" id="clinic_farmers_benefited" value="0">
                                </div>
                                <span class="form-text small text-muted">Total livestock owners served</span>
                            </div>

                            <div class="col-sm-6 col-md-4">
                                <label class="form-label small fw-bold text-muted">No. of Cases Reported</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-warning"><i class="bi bi-journal-medical"></i></span>
                                    <input type="number" min="0" class="form-control fw-bold" name="cases_reported" id="clinic_cases_reported" value="0">
                                </div>
                                <span class="form-text small text-muted">Total incidents registered</span>
                            </div>

                            <div class="col-sm-6 col-md-4">
                                <label class="form-label small fw-bold text-muted">No. of Cases Attended</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-success"><i class="bi bi-check2-circle"></i></span>
                                    <input type="number" min="0" class="form-control fw-bold" name="cases_attended" id="clinic_cases_attended" value="0">
                                </div>
                                <span class="form-text small text-muted">Cases examined and treated</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. ACTIVITIES PERFORMED (3 CATEGORY BLOCKS) -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <span class="badge rounded-pill bg-danger-subtle text-danger px-2 py-1">4</span>
                            Activities Performed (Categorized Entry)
                        </h6>
                    </div>
                    <div class="card-body p-3 p-md-4">

                        <!-- BLOCK A: LIVESTOCK BREEDING -->
                        <div class="border rounded-3 p-3 mb-4 bg-white">
                            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                                <i class="bi bi-diagram-3-fill text-danger fs-5"></i>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Category A: Livestock Breeding Activities</h6>
                                    <span class="text-muted small">Artificial Insemination and Pregnancy Diagnosis stats</span>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">No. of Artificial Inseminations Done (A.I)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light"><i class="bi bi-eyedropper text-danger"></i></span>
                                        <input type="number" min="0" class="form-control fw-bold" name="ai_done" id="clinic_ai_done" value="0">
                                    </div>
                                    <span class="form-text small text-muted">Total bovine/caprine inseminations completed</span>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">No. of Pregnancy Diagnosed (P.D)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light"><i class="bi bi-heart-pulse text-primary"></i></span>
                                        <input type="number" min="0" class="form-control fw-bold" name="pd_diagnosed" id="clinic_pd_diagnosed" value="0">
                                    </div>
                                    <span class="form-text small text-muted">Confirmed positive / examined pregnancies</span>
                                </div>
                            </div>
                        </div>

                        <!-- BLOCK B: LIVESTOCK HEALTH & IMMUNIZATION TRACKERS -->
                        <div class="border rounded-3 p-3 mb-4 bg-white">
                            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                                <i class="bi bi-shield-plus text-success fs-5"></i>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Category B: Livestock Health &amp; Immunization Trackers</h6>
                                    <span class="text-muted small">Treatment totals and specific vaccination breakdowns</span>
                                </div>
                            </div>

                            <!-- General Treatments & Vaccinations -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Total Animals Vaccinated (All Species)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light"><i class="bi bi-shield-check text-success"></i></span>
                                        <input type="number" min="0" class="form-control fw-bold" name="animals_vaccinated" id="clinic_animals_vaccinated" value="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Total Treated Animals (Clinical Cases)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light"><i class="bi bi-bandaid text-info"></i></span>
                                        <input type="number" min="0" class="form-control fw-bold" name="animals_treated" id="clinic_animals_treated" value="0">
                                    </div>
                                </div>
                            </div>

                            <!-- Poultry Specific Trackers -->
                            <div class="bg-light p-3 rounded-2 mb-3 border">
                                <label class="fw-bold small text-dark d-block mb-2">
                                    <i class="bi bi-feather text-warning me-1"></i>Poultry Immunization Tracker:
                                </label>
                                <div class="row g-2">
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small text-muted mb-1">Gumboro (Gamboro)</label>
                                        <input type="number" min="0" class="form-control form-control-sm fw-bold" name="poultry_gumboro" id="clinic_poultry_gumboro" value="0">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small text-muted mb-1">Ranikhet 1st</label>
                                        <input type="number" min="0" class="form-control form-control-sm fw-bold" name="poultry_ranikhet_1st" id="clinic_poultry_ranikhet_1st" value="0">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small text-muted mb-1">Ranikhet 2nd</label>
                                        <input type="number" min="0" class="form-control form-control-sm fw-bold" name="poultry_ranikhet_2nd" id="clinic_poultry_ranikhet_2nd" value="0">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small text-muted mb-1">Fowlpox</label>
                                        <input type="number" min="0" class="form-control form-control-sm fw-bold" name="poultry_fowlpox" id="clinic_poultry_fowlpox" value="0">
                                    </div>
                                </div>
                            </div>

                            <!-- Cattle / Goat Specific Trackers -->
                            <div class="bg-light p-3 rounded-2 border">
                                <label class="fw-bold small text-dark d-block mb-2">
                                    <i class="bi bi-virus text-danger me-1"></i>Cattle &amp; Goat Immunization Tracker:
                                </label>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted mb-1">FMD-BQ Vaccination</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white"><i class="bi bi-shield-fill text-danger"></i></span>
                                            <input type="number" min="0" class="form-control fw-bold" name="cattle_goat_fmd_bq" id="clinic_cattle_goat_fmd_bq" value="0">
                                        </div>
                                        <span class="form-text small text-muted">Foot &amp; Mouth / Black Quarter</span>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted mb-1">ARV Vaccination</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white"><i class="bi bi-shield-fill text-warning"></i></span>
                                            <input type="number" min="0" class="form-control fw-bold" name="cattle_goat_arv" id="clinic_cattle_goat_arv" value="0">
                                        </div>
                                        <span class="form-text small text-muted">Anti-Rabies Vaccination</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BLOCK C: EXTENSION ACTIVITIES -->
                        <div class="border rounded-3 p-3 bg-white">
                            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                                <i class="bi bi-easel2-fill text-primary fs-5"></i>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Category C: Extension Activities &amp; Identification</h6>
                                    <span class="text-muted small">Farmer capacity building, training programs, and ear tagging</span>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-dark">Training Programmes Conducted</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light"><i class="bi bi-mortarboard text-primary"></i></span>
                                        <input type="number" min="0" class="form-control fw-bold" name="training_conducted" id="clinic_training_conducted" value="0">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-dark">Type of Programme</label>
                                    <input type="text" class="form-control form-control-sm" name="training_type" id="clinic_training_type" placeholder="e.g., Dairy Management, Fodder, Deworming">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-dark">No. of Participants</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light"><i class="bi bi-person-check"></i></span>
                                        <input type="number" min="0" class="form-control fw-bold" name="training_participants" id="clinic_training_participants" value="0">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-dark">Animals Ear Tagged</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light"><i class="bi bi-tags-fill text-danger"></i></span>
                                        <input type="number" min="0" class="form-control fw-bold" name="animals_ear_tagged" id="clinic_animals_ear_tagged" value="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- 5. REMARKS / FIELD NOTES -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <label class="form-label small fw-bold text-muted mb-1">Remarks &amp; Clinical Observations</label>
                        <textarea class="form-control form-control-sm" name="remarks" id="clinic_remarks" rows="2" placeholder="Record any notable disease prevalence, public cooperation, or special constraints observed..."></textarea>
                    </div>
                </div>

            </div>

            <!-- MODAL FOOTER -->
            <div class="modal-footer bg-white border-top d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                </button>
                <button type="submit" class="btn btn-danger btn-sm px-4 fw-bold shadow-sm" id="btnSubmitMobileClinic">
                    <i class="bi bi-check-circle-fill me-1"></i>Save Clinic Record
                </button>
            </div>
        </form>
    </div>
</div>
