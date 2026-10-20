<!-- Modal: View & Print Drug Distribution Register (Tab 2) -->
<div class="modal fade" id="modalViewDrugDistribution" tabindex="-1" aria-labelledby="modalViewDrugDistributionLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white d-print-none">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-capsule fs-5 text-info"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="modalViewDrugDistributionLabel">Drug &amp; Consumable Distribution Register</h6>
                        <small class="text-white-50">Official Treatment Log &amp; Stock Reconciliation Sheet</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-light" onclick="printDrugReport()">
                        <i class="bi bi-printer me-1"></i>Print Register
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="modal-body p-4 bg-white" id="printableDrugArea">
                <!-- Printable Official Header -->
                <div class="text-center pb-3 mb-3 border-bottom border-2 border-dark">
                    <h5 class="fw-bold mb-1 text-uppercase text-dark" style="letter-spacing: 0.5px;">Department of Animal Production &amp; Health</h5>
                    <div class="small fw-semibold text-muted text-uppercase mb-1">Eastern Province, Sri Lanka</div>
                    <div class="badge bg-primary text-white px-3 py-1 fs-6 text-uppercase fw-bold mt-1">
                        Mobile Clinic Drugs &amp; Consumable Distribution Register
                    </div>
                </div>

                <!-- Inventory Stock Header Summary -->
                <div class="card border mb-3">
                    <div class="card-header bg-light py-2">
                        <strong class="small text-uppercase text-dark"><i class="bi bi-box-seam me-1 text-primary"></i>Distribution &amp; Stock Reconciliation Header</strong>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2 small">
                            <div class="col-md-3">
                                <span class="text-muted d-block">Drug / Consumable Name:</span>
                                <strong class="text-dark fs-6" id="view_drug_name">-</strong>
                            </div>
                            <div class="col-6 col-md-2">
                                <span class="text-muted d-block">Distribution Date:</span>
                                <strong class="text-dark font-monospace" id="view_dist_date">-</strong>
                            </div>
                            <div class="col-6 col-md-2">
                                <span class="text-muted d-block">Opening Stock:</span>
                                <strong class="text-secondary font-monospace" id="view_initial_stock">0.00</strong>
                            </div>
                            <div class="col-6 col-md-2">
                                <span class="text-muted d-block">Total Issued:</span>
                                <strong class="text-danger font-monospace" id="view_total_issued">0.00</strong>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-muted d-block">Balance in Hand:</span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle fs-6 font-monospace" id="view_balance_hand">
                                    0.00
                                </span>
                            </div>
                        </div>
                        <div class="row g-2 small mt-2 pt-2 border-top">
                            <div class="col-6">
                                <span class="text-muted">Range:</span>
                                <strong class="ms-1" id="view_dist_range">-</strong>
                            </div>
                            <div class="col-6 text-end">
                                <span class="text-muted">District:</span>
                                <strong class="ms-1" id="view_dist_district">-</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Treatment Grid Data Table -->
                <div class="mb-3">
                    <h6 class="fw-bold text-dark border-bottom pb-1 mb-2 small text-uppercase">
                        <i class="bi bi-people-fill text-primary me-1"></i>Individual Beneficiary Treatments &amp; Dispensing Record
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0 small">
                            <thead class="table-light text-uppercase">
                                <tr>
                                    <th style="width: 40px;" class="text-center">S.No</th>
                                    <th>Name of the Farmer</th>
                                    <th>Address</th>
                                    <th>N.I.C. No</th>
                                    <th>Species Affected</th>
                                    <th>Disease Diagnosed / Tentative Diagnosis</th>
                                    <th class="text-center" style="width: 75px;">Animals</th>
                                    <th class="text-end" style="width: 95px;">Dose Issued</th>
                                </tr>
                            </thead>
                            <tbody id="view_treatments_tbody">
                                <tr><td colspan="8" class="text-center text-muted">No treatments recorded.</td></tr>
                            </tbody>
                            <tfoot class="table-light fw-bold font-monospace">
                                <tr>
                                    <td colspan="6" class="text-end">Total Doses Distributed:</td>
                                    <td class="text-center" id="view_sum_animals">0</td>
                                    <td class="text-end text-danger" id="view_sum_doses">0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Remarks -->
                <div class="mb-4">
                    <h6 class="fw-bold text-dark border-bottom pb-1 mb-1 small text-uppercase">Remarks &amp; Storage Conditions</h6>
                    <p class="small text-muted mb-0 fst-italic" id="view_dist_remarks">None provided.</p>
                </div>

                <!-- Signatures -->
                <div class="row mt-4 pt-4 border-top text-center small">
                    <div class="col-6">
                        <div style="border-bottom: 1px dotted #888; width: 80%; margin: 30px auto 5px auto;"></div>
                        <span class="d-block fw-bold">Dispensing Officer / Veterinary Surgeon</span>
                        <span class="text-muted">Signature &amp; Official Stamp</span>
                    </div>
                    <div class="col-6">
                        <div style="border-bottom: 1px dotted #888; width: 80%; margin: 30px auto 5px auto;"></div>
                        <span class="d-block fw-bold">Stock Verification Officer</span>
                        <span class="text-muted">Inventory In-charge / Range Office</span>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light d-print-none">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="printDrugReport()">
                    <i class="bi bi-printer me-1"></i>Print Register
                </button>
            </div>
        </div>
    </div>
</div>
