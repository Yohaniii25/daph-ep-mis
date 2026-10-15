<!-- Modal: Farmer Total Animal Summary -->
<div class="modal fade" id="modalFarmerAnimalSummary" tabindex="-1" aria-labelledby="modalFarmerAnimalSummaryLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #370709 0%, #820100 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white bg-opacity-25 p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="bi bi-clipboard2-data-fill fs-5 text-white"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="modalFarmerAnimalSummaryLabel">Farmer Livestock & Identification Summary</h5>
                        <small class="text-white-50">Master Census vs. Issued Ear Tags & Cattle Vouchers</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-light">
                <!-- Farmer Profile Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-3">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-6 col-lg-4">
                                <span class="text-muted small text-uppercase fw-bold d-block">Farmer / Owner Name</span>
                                <h5 class="fw-bold text-dark mb-0" id="fas_farmer_name">—</h5>
                                <span class="text-muted small" id="fas_address">—</span>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <span class="text-muted small text-uppercase fw-bold d-block">Farm Registration No</span>
                                <span class="badge bg-primary-subtle text-primary font-monospace fs-6 px-3 py-1 border border-primary-subtle" id="fas_farm_reg_no">—</span>
                            </div>
                            <div class="col-md-6 col-lg-2">
                                <span class="text-muted small text-uppercase fw-bold d-block">Farmer NIC</span>
                                <span class="font-monospace fw-bold text-dark" id="fas_nic">—</span>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <span class="text-muted small text-uppercase fw-bold d-block">Veterinary Range / District</span>
                                <span class="fw-semibold text-secondary" id="fas_range_district">—</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 1: Master Livestock Census -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-journal-bookmark-fill me-2 text-danger"></i>1. Master Livestock Registry Census</h6>
                            <small class="text-muted">Recorded animal population under Master Farm Registration (farm_registration_renewals)</small>
                        </div>
                        <span class="badge bg-dark px-3 py-2 fs-6" id="fas_master_total_badge">Total: 0 Animals</span>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-2 text-center">
                            <div class="col-4 col-md-2">
                                <div class="p-2 rounded bg-light border">
                                    <small class="text-muted d-block fw-semibold">Neat Cattle</small>
                                    <span class="fs-5 fw-bold text-primary font-monospace" id="fas_mc_cattle">0</span>
                                </div>
                            </div>
                            <div class="col-4 col-md-2">
                                <div class="p-2 rounded bg-light border">
                                    <small class="text-muted d-block fw-semibold">Buffaloes</small>
                                    <span class="fs-5 fw-bold text-info font-monospace" id="fas_mc_buffalo">0</span>
                                </div>
                            </div>
                            <div class="col-4 col-md-2">
                                <div class="p-2 rounded bg-light border">
                                    <small class="text-muted d-block fw-semibold">Goats</small>
                                    <span class="fs-5 fw-bold text-warning font-monospace" id="fas_mc_goat">0</span>
                                </div>
                            </div>
                            <div class="col-4 col-md-2">
                                <div class="p-2 rounded bg-light border">
                                    <small class="text-muted d-block fw-semibold">Swine</small>
                                    <span class="fs-5 fw-bold text-secondary font-monospace" id="fas_mc_swine">0</span>
                                </div>
                            </div>
                            <div class="col-4 col-md-2">
                                <div class="p-2 rounded bg-light border">
                                    <small class="text-muted d-block fw-semibold">Sheep</small>
                                    <span class="fs-5 fw-bold text-success font-monospace" id="fas_mc_sheep">0</span>
                                </div>
                            </div>
                            <div class="col-4 col-md-2">
                                <div class="p-2 rounded bg-light border">
                                    <small class="text-muted d-block fw-semibold">Poultry</small>
                                    <span class="fs-5 fw-bold text-dark font-monospace" id="fas_mc_poultry">0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Ear Tag & Identification Status Summary -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-tags-fill me-2 text-primary"></i>2. Identified Animals & Voucher Status Breakdown</h6>
                            <small class="text-muted">Lifecycle tracking of individual tags registered to this farm</small>
                        </div>
                        <span class="badge bg-primary px-3 py-2 fs-6" id="fas_ai_total_badge">Identified: 0 Tags</span>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-2 text-center">
                            <div class="col-6 col-md-2">
                                <div class="p-2 rounded border" style="background-color: #f0fdf4; border-color: #86efac !important;">
                                    <small class="text-success fw-bold d-block">Active / Retained</small>
                                    <span class="fs-5 fw-bold text-success font-monospace" id="fas_ai_active">0</span>
                                </div>
                            </div>
                            <div class="col-6 col-md-2">
                                <div class="p-2 rounded border" style="background-color: #eff6ff; border-color: #93c5fd !important;">
                                    <small class="text-primary fw-bold d-block">Sold / Transferred</small>
                                    <span class="fs-5 fw-bold text-primary font-monospace" id="fas_ai_sold">0</span>
                                </div>
                            </div>
                            <div class="col-6 col-md-2">
                                <div class="p-2 rounded border" style="background-color: #fef2f2; border-color: #fca5a5 !important;">
                                    <small class="text-danger fw-bold d-block">Slaughter</small>
                                    <span class="fs-5 fw-bold text-danger font-monospace" id="fas_ai_slaughter">0</span>
                                </div>
                            </div>
                            <div class="col-6 col-md-2">
                                <div class="p-2 rounded border" style="background-color: #fefce8; border-color: #fde047 !important;">
                                    <small class="text-warning-emphasis fw-bold d-block">Died</small>
                                    <span class="fs-5 fw-bold text-dark font-monospace" id="fas_ai_died">0</span>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="p-2 rounded border" style="background-color: #fdf2f8; border-color: #f472b6 !important;">
                                    <div class="d-flex justify-content-between align-items-center px-2">
                                        <div class="text-start">
                                            <small class="text-danger fw-bold d-block"><i class="bi bi-lock-fill me-1"></i>Damaged Tags</small>
                                            <small class="text-muted" style="font-size: 10px;">Permanent Voucher Pairing</small>
                                        </div>
                                        <span class="fs-5 fw-bold text-danger font-monospace" id="fas_ai_damaged">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Detailed Animals Register -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-list-check me-2 text-success"></i>3. Individual Animal Registration Records</h6>
                        <span class="small text-muted" id="fas_animals_table_count">0 records</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 280px;">
                            <table class="table table-hover table-striped align-middle mb-0 small">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="text-center" style="width: 40px;">#</th>
                                        <th>Ear Tag No.</th>
                                        <th>Serial No.</th>
                                        <th>Cattle Voucher</th>
                                        <th>Species</th>
                                        <th>Breed</th>
                                        <th>Gender</th>
                                        <th>Colour</th>
                                        <th>Date</th>
                                        <th class="text-center">Present Status</th>
                                    </tr>
                                </thead>
                                <tbody id="fas_animals_table_body">
                                    <tr>
                                        <td colspan="10" class="text-center py-4 text-muted">Loading animal records...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer bg-white border-top">
                <button type="button" class="btn btn-secondary px-4 shadow-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
