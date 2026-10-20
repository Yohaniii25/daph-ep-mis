<!-- Modal: View & Print Mobile Clinic Report (Tab 1) -->
<div class="modal fade" id="modalViewMobileClinic" tabindex="-1" aria-labelledby="modalViewMobileClinicLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white d-print-none">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-medical fs-5 text-warning"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="modalViewMobileClinicLabel">Mobile Veterinary Clinic Report</h6>
                        <small class="text-white-50">Official Field Operation &amp; Activity Log</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-light" onclick="printClinicReport()">
                        <i class="bi bi-printer me-1"></i>Print Report
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="modal-body p-4 bg-white" id="printableClinicArea">
                <!-- Printable Official Header -->
                <div class="text-center pb-3 mb-3 border-bottom border-2 border-dark">
                    <h5 class="fw-bold mb-1 text-uppercase text-dark" style="letter-spacing: 0.5px;">Department of Animal Production &amp; Health</h5>
                    <div class="small fw-semibold text-muted text-uppercase mb-1">Eastern Province, Sri Lanka</div>
                    <div class="badge bg-danger text-white px-3 py-1 fs-6 text-uppercase fw-bold mt-1">
                        Mobile Veterinary Clinic &amp; Extension Activity Report
                    </div>
                </div>

                <!-- Report Metadata Bar -->
                <div class="bg-light p-3 rounded-2 border mb-3">
                    <div class="row g-2 small">
                        <div class="col-6 col-sm-3">
                            <span class="text-muted d-block">District:</span>
                            <strong class="text-dark" id="view_clinic_district">-</strong>
                        </div>
                        <div class="col-6 col-sm-3">
                            <span class="text-muted d-block">Veterinary Range:</span>
                            <strong class="text-dark" id="view_clinic_range">-</strong>
                        </div>
                        <div class="col-6 col-sm-3">
                            <span class="text-muted d-block">Clinic Date:</span>
                            <strong class="text-dark font-monospace" id="view_clinic_date">-</strong>
                        </div>
                        <div class="col-6 col-sm-3">
                            <span class="text-muted d-block">Places Visited:</span>
                            <strong class="text-dark" id="view_clinic_places">-</strong>
                        </div>
                    </div>
                </div>

                <!-- 1. Mobile Team Members -->
                <div class="mb-3">
                    <h6 class="fw-bold text-dark border-bottom pb-1 mb-2 small text-uppercase">
                        <i class="bi bi-people-fill text-danger me-1"></i>1. Mobile Veterinary Clinic Team
                    </h6>
                    <table class="table table-sm table-bordered mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;" class="text-center">#</th>
                                <th>Name of Team Member</th>
                                <th>Designation</th>
                                <th>Contact No</th>
                            </tr>
                        </thead>
                        <tbody id="view_clinic_team_tbody">
                            <tr><td colspan="4" class="text-center text-muted">No team members specified.</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- 2. Clinic Attendance & Beneficiaries -->
                <div class="mb-3">
                    <h6 class="fw-bold text-dark border-bottom pb-1 mb-2 small text-uppercase">
                        <i class="bi bi-clipboard2-pulse-fill text-primary me-1"></i>2. Beneficiaries &amp; Attendance Summary
                    </h6>
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="p-2 border rounded-2 bg-light">
                                <span class="d-block small text-muted">Farmers Benefited</span>
                                <h5 class="fw-bold text-primary mb-0 font-monospace" id="view_clinic_farmers">0</h5>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 border rounded-2 bg-light">
                                <span class="d-block small text-muted">Cases Reported</span>
                                <h5 class="fw-bold text-warning mb-0 font-monospace" id="view_clinic_cases_rep">0</h5>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 border rounded-2 bg-light">
                                <span class="d-block small text-muted">Cases Attended</span>
                                <h5 class="fw-bold text-success mb-0 font-monospace" id="view_clinic_cases_att">0</h5>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Activities Performed -->
                <div class="mb-3">
                    <h6 class="fw-bold text-dark border-bottom pb-1 mb-2 small text-uppercase">
                        <i class="bi bi-gear-wide-connected text-success me-1"></i>3. Activities Performed
                    </h6>

                    <!-- Livestock Breeding -->
                    <div class="p-2 border rounded-2 bg-light mb-2">
                        <strong class="d-block small text-dark mb-1"><i class="bi bi-eyedropper text-danger me-1"></i>Livestock Breeding Services:</strong>
                        <div class="row g-2 small">
                            <div class="col-6">
                                <span class="text-muted">Artificial Insemination (A.I) Done:</span>
                                <strong class="ms-1 font-monospace" id="view_clinic_ai">0</strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted">Pregnancy Diagnosed (P.D):</span>
                                <strong class="ms-1 font-monospace" id="view_clinic_pd">0</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Livestock Health & Vaccinations -->
                    <div class="p-2 border rounded-2 bg-light mb-2">
                        <strong class="d-block small text-dark mb-1"><i class="bi bi-shield-plus text-success me-1"></i>Livestock Health &amp; Immunization:</strong>
                        <div class="row g-2 small mb-2">
                            <div class="col-6">
                                <span class="text-muted">Animals Vaccinated (Overall):</span>
                                <strong class="ms-1 font-monospace" id="view_clinic_vaccinated">0</strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted">Treated Animals (Clinical):</span>
                                <strong class="ms-1 font-monospace" id="view_clinic_treated">0</strong>
                            </div>
                        </div>
                        <div class="border-top pt-2">
                            <span class="d-block text-muted small fw-bold mb-1">Specific Immunizations:</span>
                            <div class="row g-1 small">
                                <div class="col-6 col-sm-3">Gumboro: <strong class="font-monospace" id="view_p_gumboro">0</strong></div>
                                <div class="col-6 col-sm-3">Ranikhet 1st: <strong class="font-monospace" id="view_p_ranikhet1">0</strong></div>
                                <div class="col-6 col-sm-3">Ranikhet 2nd: <strong class="font-monospace" id="view_p_ranikhet2">0</strong></div>
                                <div class="col-6 col-sm-3">Fowlpox: <strong class="font-monospace" id="view_p_fowlpox">0</strong></div>
                                <div class="col-6">FMD-BQ Vaccination: <strong class="font-monospace" id="view_cg_fmdbq">0</strong></div>
                                <div class="col-6">ARV Vaccination: <strong class="font-monospace" id="view_cg_arv">0</strong></div>
                            </div>
                        </div>
                    </div>

                    <!-- Extension Activities -->
                    <div class="p-2 border rounded-2 bg-light">
                        <strong class="d-block small text-dark mb-1"><i class="bi bi-mortarboard text-primary me-1"></i>Extension &amp; Identification:</strong>
                        <div class="row g-2 small">
                            <div class="col-6 col-sm-3">
                                <span class="text-muted">Training Programs:</span>
                                <strong class="ms-1 font-monospace" id="view_clinic_training_cnt">0</strong>
                            </div>
                            <div class="col-6 col-sm-3">
                                <span class="text-muted">Type:</span>
                                <strong class="ms-1" id="view_clinic_training_type">-</strong>
                            </div>
                            <div class="col-6 col-sm-3">
                                <span class="text-muted">Participants:</span>
                                <strong class="ms-1 font-monospace" id="view_clinic_participants">0</strong>
                            </div>
                            <div class="col-6 col-sm-3">
                                <span class="text-muted">Animals Ear Tagged:</span>
                                <strong class="ms-1 font-monospace" id="view_clinic_eartagged">0</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Remarks -->
                <div class="mb-4">
                    <h6 class="fw-bold text-dark border-bottom pb-1 mb-1 small text-uppercase">4. Remarks &amp; Observations</h6>
                    <p class="small text-muted mb-0 fst-italic" id="view_clinic_remarks">None provided.</p>
                </div>

                <!-- Official Signature Block -->
                <div class="row mt-4 pt-4 border-top text-center small">
                    <div class="col-6">
                        <div style="border-bottom: 1px dotted #888; width: 80%; margin: 30px auto 5px auto;"></div>
                        <span class="d-block fw-bold">Mobile Clinic Officer In-charge</span>
                        <span class="text-muted">Livestock Development Officer / VS</span>
                    </div>
                    <div class="col-6">
                        <div style="border-bottom: 1px dotted #888; width: 80%; margin: 30px auto 5px auto;"></div>
                        <span class="d-block fw-bold">Government Veterinary Surgeon</span>
                        <span class="text-muted">Veterinary Surgeon's Office</span>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light d-print-none">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger btn-sm" onclick="printClinicReport()">
                    <i class="bi bi-printer me-1"></i>Print
                </button>
            </div>
        </div>
    </div>
</div>
