<!-- Modal: Record / Edit Drug & Consumable Distribution (Tab 2) -->
<div class="modal fade" id="modalRecordDrugDistribution" tabindex="-1" aria-labelledby="modalRecordDrugDistributionLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-fullscreen-lg-down modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 95vw;">
        <form class="modal-content border-0 shadow-lg" id="formRecordDrugDistribution" autocomplete="off" onsubmit="handleSaveDrugDistribution(event)">
            <!-- MODAL HEADER -->
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-capsule-pill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="modalRecordDrugDistributionLabel">Drug &amp; Consumable Distribution Entry</h5>
                        <p class="small text-white-50 mb-0">Record individual field treatments, auto-calculate total issued doses, and synchronize inventory balance</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- MODAL BODY -->
            <div class="modal-body p-3 p-md-4 bg-light">
                <input type="hidden" name="action" value="save_drug_distribution">
                <input type="hidden" name="id" id="dist_edit_id" value="">

                <!-- 1. INVENTORY HEADER & STOCK STATUS -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <span class="badge rounded-pill bg-primary-subtle text-primary px-2 py-1">1</span>
                            Drug &amp; Consumable Inventory Header
                        </h6>
                        <span class="badge bg-primary text-white shadow-sm px-3 py-1 font-monospace">
                            <i class="bi bi-arrow-repeat me-1"></i>Live Inventory Sync
                        </span>
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3 align-items-end">
                            <!-- Drug Selection -->
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-dark">
                                    Drug / Item Name <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <select class="form-select fw-bold" name="drug_select" id="dist_drug_select" onchange="handleDrugSelectChange(this)">
                                        <option value="">-- Choose Master Inventory Drug --</option>
                                        <!-- Populated via AJAX / PHP -->
                                    </select>
                                    <button class="btn btn-outline-secondary" type="button" title="Type custom drug" onclick="toggleCustomDrugInput()">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                </div>
                                <input type="hidden" name="drug_type_id" id="dist_drug_type_id" value="">
                                <div id="customDrugWrapper" class="mt-2 d-none">
                                    <input type="text" class="form-control form-control-sm border-primary" name="custom_drug_name" id="dist_custom_drug_name" placeholder="Enter custom drug / consumable name..." oninput="syncDrugName()">
                                    <small class="text-muted">Type manual item if not found in catalog</small>
                                </div>
                                <input type="hidden" name="drug_name" id="dist_drug_name" value="" required>
                            </div>

                            <!-- Distribution Date -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-dark">
                                    Date <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="bi bi-calendar text-primary"></i></span>
                                    <input type="date" class="form-control fw-bold" name="distribution_date" id="dist_distribution_date" value="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>

                            <!-- Unit of Measure -->
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted">Unit of Measure</label>
                                <select class="form-select form-select-sm fw-bold font-monospace" name="unit_of_measure" id="dist_unit_of_measure" onchange="updateUnitLabels(this.value)">
                                    <option value="Doses" selected>Doses</option>
                                    <option value="Vials">Vials</option>
                                    <option value="ml">ml (Milliliters)</option>
                                    <option value="Tablets">Tablets</option>
                                    <option value="Bolus">Bolus</option>
                                    <option value="Bottles">Bottles</option>
                                    <option value="Ampoules">Ampoules</option>
                                    <option value="Tubes">Tubes</option>
                                </select>
                            </div>

                            <!-- Initial Stock (Opening Balance) -->
                            <div class="col-6 col-md-2">
                                <label class="form-label small fw-bold text-dark">
                                    Initial Stock <span class="text-muted fw-normal">(Opening)</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light"><i class="bi bi-box-seam text-secondary"></i></span>
                                    <input type="number" step="any" min="0" class="form-control fw-bold text-end font-monospace" name="initial_stock" id="dist_initial_stock" value="0.00" oninput="recalcInventoryBalances()" required>
                                </div>
                                <span class="form-text small text-muted">Stock at start of clinic</span>
                            </div>

                            <!-- Live Calculated Total Issued -->
                            <div class="col-6 col-md-2">
                                <label class="form-label small fw-bold text-danger">
                                    Total Issued <span class="text-muted fw-normal">(Auto Sum)</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-danger-subtle text-danger"><i class="bi bi-dash-circle-fill"></i></span>
                                    <input type="text" class="form-control fw-bold text-end font-monospace text-danger bg-light" id="dist_total_issued_display" value="0.00" readonly>
                                </div>
                                <span class="form-text small text-danger fw-semibold">Sum of treatment grid</span>
                            </div>
                        </div>

                        <!-- LIVE STOCK BALANCE STRIP -->
                        <div class="row g-2 mt-3 pt-3 border-top align-items-center">
                            <div class="col-md-7">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-info-circle text-primary me-1"></i>Stock Formula:</span>
                                    <code class="small text-dark font-monospace">[Initial Stock] - [Total Issued from Grid] = [Balance in Hand]</code>
                                </div>
                            </div>
                            <div class="col-md-5 text-md-end">
                                <div class="d-inline-flex align-items-center gap-2 p-2 rounded-2 border bg-white shadow-sm">
                                    <span class="small fw-bold text-muted text-uppercase">Balance in Hand:</span>
                                    <span class="badge bg-success fs-6 font-monospace px-3 py-1" id="badge_balance_in_hand">
                                        0.00 Doses
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. DISTRIBUTION GRID (INDIVIDUAL PATIENT / FARM TREATMENTS) -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                                <span class="badge rounded-pill bg-primary-subtle text-primary px-2 py-1">2</span>
                                Treatment &amp; Dispensing Grid
                            </h6>
                            <small class="text-muted">Enter individual farmer treatments. Enter NIC to automatically retrieve farmer identity</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addSampleTreatmentRow()" title="Quick fill sample">
                                <i class="bi bi-magic me-1"></i>Sample Row
                            </button>
                            <button type="button" class="btn btn-sm btn-primary shadow-sm" onclick="addTreatmentRow()">
                                <i class="bi bi-plus-circle-fill me-1"></i>Add Treatment Row
                            </button>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 480px;">
                            <table class="table table-sm table-hover align-middle mb-0" id="tableDrugTreatments">
                                <thead class="table-light sticky-top text-muted small text-uppercase" style="z-index: 10;">
                                    <tr>
                                        <th style="width: 45px;" class="text-center">S.No</th>
                                        <th style="width: 175px;">N.I.C. No <span class="text-primary"><i class="bi bi-search"></i></span></th>
                                        <th style="min-width: 180px;">Name of the Farmer <span class="text-danger">*</span></th>
                                        <th style="min-width: 180px;">Address</th>
                                        <th style="width: 130px;">Species</th>
                                        <th style="min-width: 170px;">Disease / Tentative Diagnosis</th>
                                        <th style="width: 95px;" class="text-center">Animals</th>
                                        <th style="width: 120px;" class="text-end">Dose Issued <span class="unit-label-span text-primary">(Doses)</span></th>
                                        <th style="width: 50px;" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="treatmentGridRows">
                                    <!-- Dynamic Treatment Rows -->
                                </tbody>
                            </table>
                        </div>

                        <!-- EMPTY STATE FOR GRID -->
                        <div id="emptyTreatmentState" class="p-5 text-center text-muted">
                            <i class="bi bi-table fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            <h6 class="fw-bold mb-1">No Treatment Rows Added Yet</h6>
                            <p class="small text-muted mb-3">Click below to start recording farmer diagnosis and dose dispensing.</p>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addTreatmentRow()">
                                <i class="bi bi-plus-lg me-1"></i>Add First Treatment
                            </button>
                        </div>
                    </div>

                    <!-- GRID SUMMARY FOOTER -->
                    <div class="card-footer bg-white border-top py-2 px-3">
                        <div class="row align-items-center g-2 text-muted small">
                            <div class="col-sm-6 d-flex align-items-center gap-3">
                                <span><i class="bi bi-people me-1"></i>Beneficiaries: <strong class="text-dark font-monospace" id="grid_total_farmers">0</strong></span>
                                <span><i class="bi bi-shield me-1"></i>Animals: <strong class="text-dark font-monospace" id="grid_total_animals">0</strong></span>
                            </div>
                            <div class="col-sm-6 text-sm-end">
                                <span class="me-2">Grid Total Issued:</span>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-6 font-monospace px-2 py-1" id="grid_sum_doses">
                                    0.00
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. REMARKS -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <label class="form-label small fw-bold text-muted mb-1">Session Remarks &amp; Dispensing Notes</label>
                        <textarea class="form-control form-control-sm" name="remarks" id="dist_remarks" rows="2" placeholder="Record batch numbers, storage conditions, or clinical response observations..."></textarea>
                    </div>
                </div>

            </div>

            <!-- MODAL FOOTER -->
            <div class="modal-footer bg-white border-top d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                </button>
                <div class="d-flex align-items-center gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" id="btnSubmitDrugDistribution">
                        <i class="bi bi-check2-circle me-1"></i>Save Distribution &amp; Sync Inventory
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
