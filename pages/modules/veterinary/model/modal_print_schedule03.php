<!-- Modal: Official Schedule - 03 Printable Certificate -->
<div class="modal fade" id="modalPrintSchedule03" tabindex="-1" aria-labelledby="modalPrintSchedule03Label" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white d-print-none">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-printer-fill fs-5 text-warning"></i>
                    <h5 class="modal-title fw-bold mb-0" id="modalPrintSchedule03Label">Official Schedule - 03 Certificate Preview</h5>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-warning btn-sm text-dark fw-bold shadow-sm" onclick="printSchedule03();">
                        <i class="bi bi-printer me-1"></i>Print / Save as PDF
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="modal-body p-4 bg-white" id="schedule03PrintArea">
                <!-- PRINTABLE WATERMARK & WRAPPER -->
                <div class="p-4 border border-2 border-dark rounded" style="background-color: #ffffff; color: #111827;">
                    <!-- HEADER BLOCK -->
                    <div class="text-center mb-4 border-bottom border-dark pb-3">
                        <h6 class="text-uppercase fw-bold mb-1 letter-spacing-1">Democratic Socialist Republic of Sri Lanka</h6>
                        <h5 class="fw-bold text-uppercase mb-1" style="color: #370709;">Department of Animal Production and Health</h5>
                        <div class="badge bg-dark text-white px-3 py-1 fs-6 mb-2">SCHEDULE - 03</div>
                        <h4 class="fw-bold text-uppercase text-decoration-underline mb-0">Animal Transportation Health Certificate</h4>
                    </div>

                    <!-- CERTIFICATE METADATA ROW -->
                    <div class="row g-3 mb-4 small">
                        <div class="col-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <th class="ps-0" style="width: 170px;">Certificate Serial No:</th>
                                    <td><strong class="font-monospace fs-6 text-danger" id="s03_cert_no">—</strong></td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Date of Examination:</th>
                                    <td><strong id="s03_date">—</strong></td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Veterinary Range:</th>
                                    <td><span id="s03_range">—</span></td>
                                </tr>
                                <tr>
                                    <th class="ps-0">District:</th>
                                    <td><span id="s03_district">—</span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <th class="ps-0" style="width: 170px;">Applicant / Owner:</th>
                                    <td><strong id="s03_applicant_name">—</strong></td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Farmer NIC:</th>
                                    <td><span class="font-monospace fw-semibold" id="s03_farmer_nic">—</span></td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Farm Registration No:</th>
                                    <td><span class="font-monospace fw-semibold" id="s03_farm_reg_no">—</span></td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Vehicle Fitness Cert No:</th>
                                    <td><span class="font-monospace" id="s03_vehicle_cert">—</span></td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Purpose of Transport:</th>
                                    <td><span id="s03_purpose">—</span></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- SCHEDULE - 03 SPECIFICATION TABLE -->
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered border-dark align-middle mb-0 small text-center">
                            <thead class="table-light border-dark fw-bold">
                                <tr>
                                    <th style="width: 50px;">Serial No</th>
                                    <th style="width: 25%;">Identification No / Brand Marks</th>
                                    <th style="width: 15%;">Animal Species</th>
                                    <th style="width: 15%;">Breed</th>
                                    <th style="width: 12%;">Age (Approx)</th>
                                    <th style="width: 15%;">Colour</th>
                                    <th>Other Specific Marks</th>
                                </tr>
                            </thead>
                            <tbody id="s03_animals_tbody">
                                <tr>
                                    <td colspan="7" class="text-center py-3 text-muted">No animals loaded.</td>
                                </tr>
                            </tbody>
                            <tfoot class="table-light border-dark fw-bold">
                                <tr>
                                    <td colspan="4" class="text-start ps-3 text-uppercase">Total Number of Animals Certified for Transport</td>
                                    <td colspan="3" class="text-center fs-6 text-danger font-monospace" id="s03_total_count">0 Animals</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- STATUTORY CERTIFICATION CLAUSE -->
                    <div class="mb-4 small p-3 rounded border border-secondary bg-light">
                        <p class="mb-2 fw-semibold">
                            I hereby certify that I have thoroughly examined the animal(s) described above and found them to be in good physical health, fit for transport, and free from any clinical symptoms of contagious or infectious diseases (including Foot and Mouth Disease, Anthrax, and Black Quarter) at the time of examination.
                        </p>
                        <p class="mb-0 text-muted fst-italic">
                            This certificate is issued subject to strict compliance with animal transportation welfare guidelines, valid vehicle fitness certification, and statutory movement permits.
                        </p>
                    </div>

                    <!-- SIGNATURE & SEAL BLOCK -->
                    <div class="row pt-4 mt-2">
                        <div class="col-6 text-center">
                            <div class="border-top border-dark mx-auto pt-2" style="width: 220px;">
                                <p class="mb-0 fw-bold small">Official Veterinary Seal</p>
                                <small class="text-muted d-block">Department of Animal Production & Health</small>
                            </div>
                        </div>
                        <div class="col-6 text-center">
                            <div class="border-top border-dark mx-auto pt-2" style="width: 240px;">
                                <p class="mb-0 fw-bold small">Government Veterinary Surgeon</p>
                                <p class="mb-0 small" id="s03_surgeon_range">Ampara Veterinary Range</p>
                                <small class="text-muted d-block">Signature & Date</small>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer bg-light border-top d-print-none">
                <button type="button" class="btn btn-secondary px-4 shadow-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn text-white px-4 shadow-sm" style="background-color: #820100;" onclick="printSchedule03();">
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
    #schedule03PrintArea, #schedule03PrintArea * {
        visibility: visible;
    }
    #schedule03PrintArea {
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
