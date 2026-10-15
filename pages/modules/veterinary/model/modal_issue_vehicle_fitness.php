<!-- Modal: Issue / Edit Vehicle Fitness Certificate (Tab 2, Schedule - 04) -->
<div class="modal fade" id="modalIssueVehicleFitness" tabindex="-1" aria-labelledby="modalIssueVehicleFitnessLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-fullscreen-lg-down modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content shadow-lg border-0" id="formIssueVehicleFitness" onsubmit="submitVehicleFitnessForm(event)">
            <input type="hidden" name="id" id="vfc_edit_id" value="0">
            <input type="hidden" name="report_year" id="vfc_report_year" value="<?= $selected_year ?>">
            <input type="hidden" name="report_month" id="vfc_report_month" value="<?= (int)date('m') ?>">
            <input type="hidden" name="action" value="save_vehicle_fitness">

            <div class="modal-header text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white bg-opacity-25 p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="bi bi-truck-front-fill fs-5 text-white"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="modalIssueVehicleFitnessLabel">Issue Vehicle Fitness Certificate (Schedule - 04)</h5>
                        <small class="text-white-50">Transport Vehicle Inspection & Statutory Welfare Verification</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-light">
                    <!-- SECTION 1: AUTO-POPULATION (LINKED HEALTH CERTIFICATE OR FARM REG) -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-link-45deg me-2 text-primary"></i>1. Auto-Population (Link Health Certificate or Farm Registration)</h6>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                <i class="bi bi-lightning-charge-fill me-1"></i>Auto-Fill Enabled
                            </span>
                        </div>
                        <div class="card-body pt-0">
                            <div class="row g-3">
                                <div class="col-md-6 col-lg-5">
                                    <label class="form-label small fw-bold text-secondary mb-1">Select Linked Health Certificate</label>
                                    <select class="form-select form-select-sm fw-semibold" id="vfc_linked_hc_select">
                                        <option value="">-- Choose Issued Health Certificate --</option>
                                        <!-- Dynamically populated options -->
                                    </select>
                                    <small class="text-muted d-block mt-1">Selecting a certificate auto-fills Applicant Name, Species, & Animal Count.</small>
                                </div>
                                <div class="col-md-6 col-lg-7">
                                    <label class="form-label small fw-bold text-secondary mb-1">Or Direct Farmer Lookup (NIC / Farm Reg No)</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control form-control-sm font-monospace" id="vfc_lookup_input" placeholder="Enter Farmer NIC or Farm Reg No to search">
                                        <button class="btn btn-outline-primary btn-sm px-3" type="button" id="btnVfcLookup">
                                            <i class="bi bi-search me-1"></i>Fetch
                                        </button>
                                    </div>
                                    <small class="text-muted d-block mt-1" id="vfc_lookup_status">Lookup farmer to pull master registration details.</small>
                                </div>
                            </div>

                            <hr class="my-3 opacity-25">

                            <!-- AUTO-POPULATED APPLICANT & DETAILS -->
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label small text-muted mb-0">Applicant / Owner Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm fw-bold text-dark" name="applicant_name" id="vfc_applicant_name" placeholder="Applicant Name" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted mb-0">Farm Registration No</label>
                                    <input type="text" class="form-control form-control-sm font-monospace" name="farm_registration_no" id="vfc_farm_reg_no" placeholder="e.g. 530112345">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted mb-0">Farmer NIC</label>
                                    <input type="text" class="form-control form-control-sm font-monospace" name="farmer_nic" id="vfc_farmer_nic" placeholder="NIC Number">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted mb-0">Linked Health Certificate No</label>
                                    <input type="text" class="form-control form-control-sm font-monospace text-danger fw-bold" name="health_certificate_no" id="vfc_health_cert_no" placeholder="e.g. HC-004521">
                                    <input type="hidden" name="health_certificate_id" id="vfc_health_cert_id" value="">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted mb-0">Animal Species</label>
                                    <input type="text" class="form-control form-control-sm fw-semibold" name="animal_species" id="vfc_animal_species" value="Cattle">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted mb-0">Total Animals Transported</label>
                                    <input type="number" class="form-control form-control-sm font-monospace fw-bold" name="total_animals" id="vfc_total_animals" value="1" min="1">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: VEHICLE & TRANSPORT LOGISTICS -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3 border-0">
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-speedometer2 me-2 text-info"></i>2. Vehicle & Transport Logistics</h6>
                        </div>
                        <div class="card-body pt-0">
                            <div class="row g-3">
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label small fw-bold text-secondary mb-1">
                                        Vehicle Fitness Cert No. <span class="text-danger">*</span>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 10px;">Counterfoil Sync</span>
                                    </label>
                                    <input type="text" class="form-control form-control-sm font-monospace fw-bold text-uppercase text-primary" name="certificate_no" id="vfc_certificate_no" placeholder="e.g. VFC-2026-089" required>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label small fw-bold text-secondary mb-1">Vehicle Registration No. <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm font-monospace fw-bold text-uppercase" name="vehicle_registration_no" id="vfc_vehicle_reg_no" placeholder="e.g. WP NA-5421" required>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label small fw-bold text-secondary mb-1">Vehicle Type / Category</label>
                                    <select class="form-select form-select-sm" name="vehicle_type" id="vfc_vehicle_type">
                                        <option value="Lorry / Truck" selected>Lorry / Heavy Truck</option>
                                        <option value="Light Truck / Pickup">Light Truck / Pickup</option>
                                        <option value="Tractor Trailer">Tractor Trailer</option>
                                        <option value="Container Truck">Ventilated Container Truck</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label small fw-bold text-secondary mb-1">Date of Issue <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control form-control-sm fw-bold" name="issue_date" id="vfc_issue_date" value="<?= date('Y-m-d') ?>" required>
                                </div>

                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label small text-muted mb-1">Driver's Full Name</label>
                                    <input type="text" class="form-control form-control-sm" name="driver_name" id="vfc_driver_name" placeholder="Driver Name">
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label small text-muted mb-1">Driver's License No</label>
                                    <input type="text" class="form-control form-control-sm font-monospace text-uppercase" name="driver_license_no" id="vfc_driver_license_no" placeholder="e.g. B-1294821">
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label small text-muted mb-1">Route: From (Origin)</label>
                                    <input type="text" class="form-control form-control-sm" name="from_location" id="vfc_from_location" placeholder="e.g. Ampara Farm">
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <label class="form-label small text-muted mb-1">Route: To (Destination)</label>
                                    <input type="text" class="form-control form-control-sm" name="to_location" id="vfc_to_location" placeholder="e.g. Colombo / Kandy">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: SCHEDULE - 04 VERIFICATION CHECKLIST (CONDITIONS 02 THROUGH 07) -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-shield-check me-2 text-warning"></i>3. Schedule - 04 Inspection Checklist (Conditions 02 Through 07)</h6>
                                <small class="text-muted">Statutory verification conditions for granting transportation approval (Manual "Yes" or "No" required)</small>
                            </div>
                            <div id="vfc_approval_live_badge">
                                <span class="badge bg-success fs-6 px-3 py-2"><i class="bi bi-check-circle-fill me-1"></i>Transport Approval Granted</span>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0 small">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center" style="width: 70px;">Condition</th>
                                            <th>Statutory Inspection Requirement (Schedule - 04)</th>
                                            <th class="text-center" style="width: 140px;">Verification Input</th>
                                            <th class="text-center" style="width: 110px;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- CONDITION 02 -->
                                        <tr>
                                            <td class="text-center fw-bold font-monospace">02</td>
                                            <td>
                                                <strong class="text-dark d-block">Easy Observance of Animals</strong>
                                                <small class="text-muted">Provision for continuous, unobstructed observation and inspection of animals during transport without risking safety.</small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm w-100" role="group">
                                                    <input type="radio" class="btn-check vfc-cond-radio" name="cond_02_easy_observance" id="c02_yes" value="Yes" checked>
                                                    <label class="btn btn-outline-success" for="c02_yes">Yes</label>
                                                    <input type="radio" class="btn-check vfc-cond-radio" name="cond_02_easy_observance" id="c02_no" value="No">
                                                    <label class="btn btn-outline-danger" for="c02_no">No</label>
                                                </div>
                                            </td>
                                            <td class="text-center" id="status_pill_02">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">Pass</span>
                                            </td>
                                        </tr>

                                        <!-- CONDITION 03 -->
                                        <tr>
                                            <td class="text-center fw-bold font-monospace">03</td>
                                            <td>
                                                <strong class="text-dark d-block">Ramp for Loading &amp; Unloading</strong>
                                                <small class="text-muted">Equipped with a sturdy, non-slip ramp of suitable slope ensuring safe, injury-free boarding and disembarkation.</small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm w-100" role="group">
                                                    <input type="radio" class="btn-check vfc-cond-radio" name="cond_03_ramp" id="c03_yes" value="Yes" checked>
                                                    <label class="btn btn-outline-success" for="c03_yes">Yes</label>
                                                    <input type="radio" class="btn-check vfc-cond-radio" name="cond_03_ramp" id="c03_no" value="No">
                                                    <label class="btn btn-outline-danger" for="c03_no">No</label>
                                                </div>
                                            </td>
                                            <td class="text-center" id="status_pill_03">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">Pass</span>
                                            </td>
                                        </tr>

                                        <!-- CONDITION 04 -->
                                        <tr>
                                            <td class="text-center fw-bold font-monospace">04</td>
                                            <td>
                                                <strong class="text-dark d-block">Floor Holes &amp; Drainage</strong>
                                                <small class="text-muted">Adequate floor drainage apertures to prevent waste build-up while strictly preventing animal foot slippage or limb entrapment.</small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm w-100" role="group">
                                                    <input type="radio" class="btn-check vfc-cond-radio" name="cond_04_floor_holes" id="c04_yes" value="Yes" checked>
                                                    <label class="btn btn-outline-success" for="c04_yes">Yes</label>
                                                    <input type="radio" class="btn-check vfc-cond-radio" name="cond_04_floor_holes" id="c04_no" value="No">
                                                    <label class="btn btn-outline-danger" for="c04_no">No</label>
                                                </div>
                                            </td>
                                            <td class="text-center" id="status_pill_04">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">Pass</span>
                                            </td>
                                        </tr>

                                        <!-- CONDITION 05 -->
                                        <tr>
                                            <td class="text-center fw-bold font-monospace">05</td>
                                            <td>
                                                <strong class="text-dark d-block">Adequate Weather Covering / Roof</strong>
                                                <small class="text-muted">Secured top tarpaulin / canopy providing comprehensive protection against intense direct sunlight and rain.</small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm w-100" role="group">
                                                    <input type="radio" class="btn-check vfc-cond-radio" name="cond_05_adequate_covering" id="c05_yes" value="Yes" checked>
                                                    <label class="btn btn-outline-success" for="c05_yes">Yes</label>
                                                    <input type="radio" class="btn-check vfc-cond-radio" name="cond_05_adequate_covering" id="c05_no" value="No">
                                                    <label class="btn btn-outline-danger" for="c05_no">No</label>
                                                </div>
                                            </td>
                                            <td class="text-center" id="status_pill_05">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">Pass</span>
                                            </td>
                                        </tr>

                                        <!-- CONDITION 06 -->
                                        <tr>
                                            <td class="text-center fw-bold font-monospace">06</td>
                                            <td>
                                                <strong class="text-dark d-block">Adequate Ventilation During Transit</strong>
                                                <small class="text-muted">Vehicle structure provides adequate airflow to prevent suffocation, thermal stress, or toxic gas accumulation.</small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm w-100" role="group">
                                                    <input type="radio" class="btn-check vfc-cond-radio" name="cond_06_ventilation" id="c06_yes" value="Yes" checked>
                                                    <label class="btn btn-outline-success" for="c06_yes">Yes</label>
                                                    <input type="radio" class="btn-check vfc-cond-radio" name="cond_06_ventilation" id="c06_no" value="No">
                                                    <label class="btn btn-outline-danger" for="c06_no">No</label>
                                                </div>
                                            </td>
                                            <td class="text-center" id="status_pill_06">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">Pass</span>
                                            </td>
                                        </tr>

                                        <!-- CONDITION 07 -->
                                        <tr>
                                            <td class="text-center fw-bold font-monospace">07</td>
                                            <td>
                                                <strong class="text-dark d-block">Maximum Permissible Number of Animals</strong>
                                                <small class="text-muted">Total number loaded does not exceed maximum allowable density for vehicle dimensions (preventing overcrowding).</small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm w-100" role="group">
                                                    <input type="radio" class="btn-check vfc-cond-radio" name="cond_07_max_animals" id="c07_yes" value="Yes" checked>
                                                    <label class="btn btn-outline-success" for="c07_yes">Yes</label>
                                                    <input type="radio" class="btn-check vfc-cond-radio" name="cond_07_max_animals" id="c07_no" value="No">
                                                    <label class="btn btn-outline-danger" for="c07_no">No</label>
                                                </div>
                                            </td>
                                            <td class="text-center" id="status_pill_07">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">Pass</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- REMARKS -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-3">
                            <label class="form-label small fw-bold text-secondary mb-1">Surgeon Endorsements / Special Transit Instructions</label>
                            <input type="text" class="form-control form-control-sm" name="remarks" id="vfc_remarks" placeholder="e.g. Approved for daytime transit only. Water stop mandatory after 4 hours.">
                        </div>
                    </div>

                </div>

                <div class="modal-footer bg-white border-top d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        <i class="bi bi-shield-check text-success me-1"></i>Connected to Counterfoil System (Type: Animal Transport) and Master Registry.
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary px-4 shadow-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn text-white px-4 shadow-sm" style="background-color: #2563eb;" id="btnSubmitVehicleFitness">
                            <i class="bi bi-check2-circle me-1"></i>Save Vehicle Certificate
                        </button>
                    </div>
                </div>
        </form>
    </div>
</div>
