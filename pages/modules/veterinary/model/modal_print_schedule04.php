<!-- Modal: Official Schedule - 04 Vehicle Fitness Certificate Print -->
<div class="modal fade" id="modalPrintSchedule04" tabindex="-1" aria-labelledby="modalPrintSchedule04Label" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white d-print-none">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-printer-fill fs-5 text-warning"></i>
                    <h5 class="modal-title fw-bold mb-0" id="modalPrintSchedule04Label">Official Schedule - 04 Vehicle Fitness Certificate Preview</h5>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-warning btn-sm text-dark fw-bold shadow-sm" onclick="printSchedule04();">
                        <i class="bi bi-printer me-1"></i>Print / Save as PDF
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="modal-body p-4 bg-white" id="schedule04PrintArea">
                <div class="p-4 border border-2 border-dark rounded" style="background-color: #ffffff; color: #111827;">
                    <!-- HEADER BLOCK -->
                    <div class="text-center mb-4 border-bottom border-dark pb-3">
                        <h6 class="text-uppercase fw-bold mb-1 letter-spacing-1">Democratic Socialist Republic of Sri Lanka</h6>
                        <h5 class="fw-bold text-uppercase mb-1" style="color: #1e3a8a;">Department of Animal Production and Health</h5>
                        <div class="badge bg-primary text-white px-3 py-1 fs-6 mb-2">SCHEDULE - 04</div>
                        <h4 class="fw-bold text-uppercase text-decoration-underline mb-0">Vehicle Fitness Certificate for Animal Transportation</h4>
                    </div>

                    <!-- CERTIFICATE METADATA ROW -->
                    <div class="row g-3 mb-4 small">
                        <div class="col-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <th class="ps-0" style="width: 170px;">Certificate Serial No:</th>
                                    <td><strong class="font-monospace fs-6 text-primary" id="s04_cert_no">—</strong></td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Date of Inspection:</th>
                                    <td><strong id="s04_date">—</strong></td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Veterinary Range:</th>
                                    <td><span id="s04_range">—</span></td>
                                </tr>
                                <tr>
                                    <th class="ps-0">District:</th>
                                    <td><span id="s04_district">—</span></td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Linked Health Cert No:</th>
                                    <td><span class="font-monospace text-danger fw-bold" id="s04_health_cert">—</span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <th class="ps-0" style="width: 170px;">Applicant / Owner:</th>
                                    <td><strong id="s04_applicant_name">—</strong></td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Farm Registration No:</th>
                                    <td><span class="font-monospace fw-semibold" id="s04_farm_reg_no">—</span></td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Vehicle Registration No:</th>
                                    <td><strong class="font-monospace text-uppercase fs-6" id="s04_vehicle_no">—</strong></td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Vehicle Category:</th>
                                    <td><span id="s04_vehicle_type">—</span></td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Driver Name &amp; License:</th>
                                    <td><span id="s04_driver_info">—</span></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- TRANSPORT ROUTE & LOAD SPECIFICATION -->
                    <div class="row g-2 mb-3 p-2 bg-light border border-secondary rounded small">
                        <div class="col-md-4">
                            <strong>Animals to be Transported:</strong> <span id="s04_load_spec" class="fw-bold text-dark font-monospace">—</span>
                        </div>
                        <div class="col-md-4">
                            <strong>Origin (From):</strong> <span id="s04_from_loc">—</span>
                        </div>
                        <div class="col-md-4">
                            <strong>Destination (To):</strong> <span id="s04_to_loc">—</span>
                        </div>
                    </div>

                    <!-- SCHEDULE - 04 VERIFICATION CHECKLIST (CONDITIONS 02-07) -->
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered border-dark align-middle mb-0 small">
                            <thead class="table-light border-dark text-center fw-bold">
                                <tr>
                                    <th style="width: 70px;">Condition</th>
                                    <th>Statutory Inspection Requirement (Schedule - 04)</th>
                                    <th style="width: 120px;">Verification Result</th>
                                </tr>
                            </thead>
                            <tbody class="text-center">
                                <tr>
                                    <td class="fw-bold font-monospace">02</td>
                                    <td class="text-start">Provision for easy observance of animals during transit without hazard.</td>
                                    <td class="fw-bold" id="s04_cond_02">PASS</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold font-monospace">03</td>
                                    <td class="text-start">Equipped with a sturdy, non-slip ramp of suitable slope for safe loading &amp; unloading.</td>
                                    <td class="fw-bold" id="s04_cond_03">PASS</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold font-monospace">04</td>
                                    <td class="text-start">Floor holes and drainage adequate to maintain hygiene without risk of foot injury.</td>
                                    <td class="fw-bold" id="s04_cond_04">PASS</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold font-monospace">05</td>
                                    <td class="text-start">Adequate covering / canopy to protect livestock from direct sun and heavy rain.</td>
                                    <td class="fw-bold" id="s04_cond_05">PASS</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold font-monospace">06</td>
                                    <td class="text-start">Adequate ventilation apertures ensuring sufficient airflow during transit.</td>
                                    <td class="fw-bold" id="s04_cond_06">PASS</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold font-monospace">07</td>
                                    <td class="text-start">Complies with maximum permissible animal carrying capacity for vehicle dimensions.</td>
                                    <td class="fw-bold" id="s04_cond_07">PASS</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- OFFICIAL APPROVAL ENDORSEMENT -->
                    <div class="p-3 mb-4 rounded border text-center" id="s04_endorsement_card">
                        <h5 class="fw-bold mb-1 text-uppercase" id="s04_approval_text">TRANSPORTATION APPROVAL GRANTED</h5>
                        <p class="mb-0 small text-muted" id="s04_endorsement_sub">
                            The vehicle described above complies with all statutory animal welfare standards under Schedule - 04 regulations.
                        </p>
                    </div>

                    <!-- SIGNATURE & SEAL BLOCK -->
                    <div class="row pt-4 mt-2">
                        <div class="col-6 text-center">
                            <div class="border-top border-dark mx-auto pt-2" style="width: 220px;">
                                <p class="mb-0 fw-bold small">Official Veterinary Seal</p>
                                <small class="text-muted d-block">Department of Animal Production &amp; Health</small>
                            </div>
                        </div>
                        <div class="col-6 text-center">
                            <div class="border-top border-dark mx-auto pt-2" style="width: 240px;">
                                <p class="mb-0 fw-bold small">Government Veterinary Surgeon</p>
                                <p class="mb-0 small" id="s04_surgeon_range">Ampara Veterinary Range</p>
                                <small class="text-muted d-block">Signature &amp; Date</small>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer bg-light border-top d-print-none">
                <button type="button" class="btn btn-secondary px-4 shadow-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn text-white px-4 shadow-sm" style="background-color: #1e3a8a;" onclick="printSchedule04();">
                    <i class="bi bi-printer me-1"></i>Print Certificate
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #schedule04PrintArea, #schedule04PrintArea * {
        visibility: visible;
    }
    #schedule04PrintArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
    }
    .modal-dialog {
        max-width: 100% !important;
        margin: 0 !important;
    }
    .modal-content {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>
