<!-- Modal: Issue / Edit Health Certificate (Schedule - 03) -->
<div class="modal fade" id="modalIssueHealthCert" tabindex="-1" aria-labelledby="modalIssueHealthCertLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-fullscreen-lg-down modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content shadow-lg border-0" id="formIssueHealthCert" onsubmit="submitHealthCertForm(event)">
            <input type="hidden" name="id" id="hc_edit_id" value="0">
            <input type="hidden" name="report_year" id="hc_report_year" value="<?= $selected_year ?>">
            <input type="hidden" name="report_month" id="hc_report_month" value="<?= (int)date('m') ?>">
            <input type="hidden" name="action" value="save_health_certificate">

            <div class="modal-header text-white" style="background: linear-gradient(135deg, #370709 0%, #820100 100%);">
                <div class="d-flex align-items-center gap-2 gap-md-3">
                    <div class="rounded-circle bg-white bg-opacity-25 p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
                        <i class="bi bi-file-earmark-medical-fill fs-5 text-white"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 fs-6 fs-md-5" id="modalIssueHealthCertLabel">Issue Animal Health Certificate (Schedule - 03)</h5>
                        <small class="text-white-50 d-block" style="font-size: 12px;">Transportation Health Certification &amp; Counterfoil Integration</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-3 p-md-4 bg-light">
                    <!-- SECTION 1: APPLICANT LOOKUP & AUTO-POPULATION -->
                    <div class="card border-0 shadow-sm mb-3 mb-md-4">
                        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-person-bounding-box me-2 text-danger"></i>1. Applicant &amp; Farm Auto-Population Lookup</h6>
                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 text-wrap" id="hcLookupBadge">
                                <i class="bi bi-search me-1"></i>Enter NIC or Farm Reg No.
                            </span>
                        </div>
                        <div class="card-body pt-0">
                            <div class="row g-3">
                                <div class="col-12 col-xl-5">
                                    <div class="p-3 rounded border bg-white h-100">
                                        <label class="form-label small fw-bold text-secondary mb-1">Farmer NIC / Farm Registration No. <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control font-monospace fw-bold" id="hc_lookup_input" placeholder="e.g. 198654567890 or 530112345" autocomplete="off">
                                            <button class="btn btn-danger px-3 fw-semibold" type="button" id="btnHcLookup">
                                                <i class="bi bi-search me-1"></i>Lookup
                                            </button>
                                        </div>
                                        <div id="hc_lookup_status" class="small mt-2 text-muted">
                                            <i class="bi bi-info-circle me-1"></i>Type NIC or Farm Reg to auto-fill details and load registered animals.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-xl-7">
                                    <div class="p-3 rounded border bg-white h-100">
                                        <div class="row g-2">
                                            <div class="col-12 col-sm-6">
                                                <label class="form-label small text-muted mb-1">Applicant / Farmer Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-sm fw-bold text-dark" name="applicant_name" id="hc_applicant_name" placeholder="Auto-populated or enter name" required>
                                            </div>
                                            <div class="col-12 col-sm-6">
                                                <label class="form-label small text-muted mb-1">Farmer NIC</label>
                                                <input type="text" class="form-control form-control-sm font-monospace" name="farmer_nic" id="hc_farmer_nic" placeholder="e.g. 198214502391">
                                            </div>
                                            <div class="col-12 col-sm-6">
                                                <label class="form-label small text-muted mb-1">Farm Registration No</label>
                                                <input type="text" class="form-control form-control-sm font-monospace" name="farm_registration_no" id="hc_farm_reg_no" placeholder="e.g. 530112345">
                                            </div>
                                            <div class="col-12 col-sm-6">
                                                <label class="form-label small text-muted mb-1">Location / Address</label>
                                                <input type="text" class="form-control form-control-sm" name="applicant_address" id="hc_applicant_address" placeholder="Farm location address">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: MANUAL INPUTS (CERTIFICATE & TRANSPORT DETAILS) -->
                    <div class="card border-0 shadow-sm mb-3 mb-md-4">
                        <div class="card-header bg-white py-3 border-0">
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-card-text me-2 text-primary"></i>2. Certificate &amp; Transport Metadata (Manual Inputs)</h6>
                        </div>
                        <div class="card-body pt-0">
                            <div class="row g-3">
                                <div class="col-12 col-sm-6 col-lg-3">
                                    <label class="form-label small fw-bold text-secondary mb-1">Date of Issue <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control form-control-sm fw-bold" name="date_of_issue" id="hc_date_of_issue" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-12 col-sm-6 col-lg-3">
                                    <label class="form-label small fw-bold text-secondary mb-1">
                                        Health Certificate No. <span class="text-danger">*</span>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 10px;">Counterfoil Sync</span>
                                    </label>
                                    <input type="text" class="form-control form-control-sm font-monospace fw-bold text-uppercase text-danger" name="health_certificate_no" id="hc_health_certificate_no" placeholder="e.g. HC-004521" required>
                                </div>
                                <div class="col-12 col-sm-6 col-lg-3">
                                    <label class="form-label small fw-bold text-secondary mb-1">Vehicle Fitness Cert No.</label>
                                    <input type="text" class="form-control form-control-sm font-monospace fw-semibold text-uppercase" name="vehicle_fitness_certificate_no" id="hc_vehicle_fitness_certificate_no" placeholder="e.g. VFC-2026-089">
                                </div>
                                <div class="col-12 col-sm-6 col-lg-3">
                                    <label class="form-label small fw-bold text-secondary mb-1">Primary Animal Species</label>
                                    <select class="form-select form-select-sm fw-semibold" name="species" id="hc_species">
                                        <option value="Cattle" selected>Cattle</option>
                                        <option value="Buffalo">Buffalo</option>
                                        <option value="Goat">Goat</option>
                                        <option value="Sheep">Sheep</option>
                                        <option value="Swine">Swine</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-secondary mb-1">Purpose of Transport / Certificate <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm" name="purpose" id="hc_purpose" placeholder="e.g. Breeding and Dairy Production / Relocation / Slaughter / Sale" value="Dairy Production &amp; Relocation" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: ANIMAL SELECTION INTERFACE (GROUP PICKER) -->
                    <div class="card border-0 shadow-sm mb-3 mb-md-4">
                        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-check2-square me-2 text-success"></i>3. Animal Selection Interface</h6>
                                <small class="text-muted d-block">Select a specific group of animals (e.g. 5, 10, or 20 animals) belonging to this farmer</small>
                            </div>
                            <div class="d-flex align-items-center gap-1 gap-sm-2 flex-wrap">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnHcSelectAll">
                                    <i class="bi bi-check-all me-1"></i>Select All
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnHcUnselectAll">
                                    <i class="bi bi-dash-circle me-1"></i>Unselect All
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success" id="btnHcAddManualRow">
                                    <i class="bi bi-plus-circle me-1"></i>Add Custom Row
                                </button>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <!-- Farmer's registered animals picker -->
                            <div class="border rounded p-2 bg-white mb-2" style="max-height: 220px; overflow-y: auto;" id="hc_animal_picker_container">
                                <div class="text-center text-muted py-3 small" id="hc_animal_picker_placeholder">
                                    <i class="bi bi-info-circle me-1"></i>Lookup a farmer above to display their registered animals for quick selection, or use "Add Custom Row" below.
                                </div>
                                <div class="row g-2 d-none" id="hc_animal_picker_grid">
                                    <!-- Dynamic animal cards/checkboxes populated by JS -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 4: SCHEDULE - 03 DETAILS GRID -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-table me-2 text-danger"></i>4. Schedule - 03 Form Details Grid</h6>
                                <small class="text-muted d-block">Official Schedule - 03 schedule table. Note: Male/Female column removed per statutory standard.</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-danger fs-6 px-3 py-2 text-wrap" id="hc_total_selected_badge">
                                    Total Animals Selected: <span id="hc_selected_count_text" class="fw-bold">0</span>
                                </span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-hover table-striped align-middle mb-0 small" id="hcSchedule03Table" style="min-width: 820px;">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th class="text-center" style="width: 50px;">#</th>
                                            <th style="min-width: 170px;">Identification No / Brand Marks <span class="text-danger">*</span></th>
                                            <th style="min-width: 120px;">Animal Species</th>
                                            <th style="min-width: 130px;">Breed</th>
                                            <th style="min-width: 100px;">Age (Approx)</th>
                                            <th style="min-width: 120px;">Colour</th>
                                            <th style="min-width: 160px;">Other Specific Marks</th>
                                            <th class="text-center" style="width: 50px;">Remove</th>
                                        </tr>
                                    </thead>
                                    <tbody id="hcSchedule03Body">
                                        <tr id="hcNoAnimalsRow">
                                            <td colspan="8" class="text-center py-4 text-muted">
                                                <i class="bi bi-inbox fs-4 d-block mb-1 opacity-50"></i>
                                                No animals selected yet. Select animals above or click "Add Custom Row".
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer bg-white border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="text-muted small">
                        <i class="bi bi-shield-check text-success me-1"></i>Serial Number automatically synchronizes with Counterfoil Records.
                    </div>
                    <div class="d-flex gap-2 w-100 w-sm-auto justify-content-end">
                        <button type="button" class="btn btn-secondary px-3 px-sm-4 shadow-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn text-white px-3 px-sm-4 shadow-sm" style="background-color: #820100;" id="btnSubmitHealthCert">
                            <i class="bi bi-check2-circle me-1"></i>Save Health Certificate
                        </button>
                    </div>
                </div>
        </form>
    </div>
</div>
