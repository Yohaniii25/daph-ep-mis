<!-- ============================================================== -->
<!-- MODAL: ADD / REGISTER ANIMAL IDENTIFICATION (SINGLE & BATCH)    -->
<!-- ============================================================== -->
<div class="modal fade" id="modalAddAnimalIdentification" tabindex="-1" aria-labelledby="modalAddAnimalIdentificationLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-fullscreen-lg-down modal-dialog-centered modal-dialog-scrollable">
        <form id="formAddAnimalIdentification" method="POST" action="processors/animal_identification_crud.php" class="modal-content border-0 shadow-lg">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="ai_edit_id" value="0">
            <input type="hidden" name="district_id" id="ai_district_id" value="<?= $district_id ?? 1 ?>">
            <input type="hidden" name="range_id" id="ai_range_id" value="<?= $range_id ?? 1 ?>">

            <div class="modal-header text-white py-2 py-md-3 px-3 px-md-4" style="background-color: #370709;">
                <div class="d-flex align-items-center gap-2 flex-grow-1 overflow-hidden">
                    <i class="bi bi-tag-fill fs-5 text-warning flex-shrink-0"></i>
                    <div class="text-truncate">
                        <h5 class="modal-title fw-bold mb-0 fs-6 fs-md-5 text-truncate" id="modalAddAnimalIdentificationLabel">Animal Identification Entry (Ear Tagging &amp; Cattle Voucher)</h5>
                        <small class="text-white-50 d-none d-sm-block">Auto-generate Ear Tag Numbers from VS Range, Farm Registration, and Animal Sequence</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-3 p-md-4" style="overflow-y: auto;">
                <!-- ========================================================= -->
                <!-- STEP 1 (TRIGGER): FARM REGISTRATION NO. & NIC AUTO-FETCH   -->
                <!-- ========================================================= -->
                <div class="card border border-primary-subtle shadow-xs rounded-3 mb-3 bg-white">
                    <div class="card-header bg-primary-subtle border-primary-subtle py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="fw-bold mb-0 text-primary d-flex align-items-center gap-2">
                            <span class="badge bg-primary text-white rounded-pill px-2 py-1">Step 1</span>
                            <span>Farmer Identification &amp; Live Population Metrics <span class="badge bg-warning text-dark ms-1">Trigger</span></span>
                        </h6>
                        <small class="text-muted d-none d-sm-inline"><i class="bi bi-lightning-charge-fill text-warning me-1"></i>Enter or click NIC number to auto-calculate live animal counts</small>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-end">
                            <!-- Farmer NIC Field (Interactive Auto-Fetch Trigger) -->
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-dark mb-1">
                                    Farmer NIC Number <span class="badge bg-warning text-dark py-0 px-1"><i class="bi bi-lightning-charge-fill"></i> Live Calculator Trigger</span>
                                    <span class="text-muted fw-normal small">(Click / Enter to Calculate)</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-primary"><i class="bi bi-person-vcard"></i></span>
                                    <input type="text" name="farmer_nic" id="ai_farmer_nic" class="form-control form-control-sm font-monospace text-uppercase fw-bold" placeholder="e.g. 198654567890 or 865456789V" autocomplete="off" list="registeredFarmerNicsList">
                                    <button type="button" class="btn btn-warning btn-sm px-3 fw-semibold text-dark" id="btnFetchFarmerByNic" title="Fetch live farmer animal calculations based on NIC">
                                        <i class="bi bi-calculator-fill me-1"></i>Calculate
                                    </button>
                                </div>
                                <datalist id="registeredFarmerNicsList">
                                    <?php if (!empty($registered_farms)): ?>
                                        <?php foreach ($registered_farms as $rfm): ?>
                                            <?php if (!empty($rfm['nic'])): ?>
                                                <option value="<?= htmlspecialchars($rfm['nic']) ?>" label="<?= htmlspecialchars($rfm['farmer_name'] . ' (' . ($rfm['farm_reg_no'] ?? '') . ')') ?>">
                                                    <?= htmlspecialchars($rfm['farmer_name']) ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </datalist>
                            </div>

                            <!-- Farm Registration No. -->
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-dark mb-1">
                                    Farm Registration No. <span class="text-danger">*</span>
                                    <span class="text-muted fw-normal small">(Type or select farm)</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-primary"><i class="bi bi-search"></i></span>
                                    <input type="text" name="farm_reg_no" id="ai_farm_reg_no" class="form-control form-control-sm font-monospace text-uppercase fw-bold" placeholder="e.g. 530112345" required autocomplete="off" list="registeredFarmsList">
                                    <button type="button" class="btn btn-primary btn-sm px-3 fw-semibold" id="btnFetchFarmByReg">
                                        <i class="bi bi-arrow-repeat me-1"></i>Fetch Farm
                                    </button>
                                </div>
                                <datalist id="registeredFarmsList">
                                    <?php if (!empty($registered_farms)): ?>
                                        <?php foreach ($registered_farms as $rfm): ?>
                                            <option value="<?= htmlspecialchars($rfm['farm_reg_no']) ?>" label="<?= htmlspecialchars($rfm['farmer_name'] . (!empty($rfm['nic']) ? ' • NIC: ' . $rfm['nic'] : '') . ' (' . ($rfm['vs_range'] ?? '') . ')') ?>">
                                                <?= htmlspecialchars($rfm['farmer_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </datalist>
                            </div>
                        </div>

                        <!-- Status Feedback Bar -->
                        <div class="mt-2">
                            <div id="farmFetchStatus" class="p-2 rounded bg-light border text-muted small d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-info-circle text-primary flex-shrink-0" id="farmFetchStatusIcon"></i>
                                    <span id="farmFetchStatusText">Enter or click the NIC number (or Farm Reg No.) to auto-load live animal calculations.</span>
                                </div>
                                <span class="badge bg-secondary-subtle text-secondary font-monospace" id="statFarmerRegSyncBadge">Awaiting Input</span>
                            </div>
                        </div>

                        <!-- ========================================================= -->
                        <!-- LIVE DATA CALCULATIONS CONTAINER: 3 METRICS               -->
                        <!-- ========================================================= -->
                        <div class="mt-3 p-3 rounded-3 border bg-white shadow-xs" id="nicLiveMetricsContainer" style="background: linear-gradient(180deg, #fbfcfe 0%, #ffffff 100%);">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-dark text-white text-uppercase px-2 py-1" style="font-size: 0.72rem;">
                                        <i class="bi bi-cpu-fill text-warning me-1"></i>Live NIC Calculations
                                    </span>
                                    <span class="small fw-bold text-dark" id="statFarmerTitleDisplay">Farmer Population &amp; Tagging Balance</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="small text-muted">Tagging Completion:</span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace fw-bold" id="statPercentageBadge">0.0%</span>
                                </div>
                            </div>

                            <div class="row g-2 text-center">
                                <!-- Metric 1: Total Population -->
                                <div class="col-12 col-sm-4">
                                    <div class="p-2 p-md-3 rounded-3 border border-primary-subtle bg-primary-subtle text-start position-relative overflow-hidden h-100">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="text-uppercase fw-bold text-primary" style="font-size: 0.72rem; letter-spacing: 0.04em;">Total Population</div>
                                                <h3 class="fw-bold mb-0 text-primary font-monospace mt-1" id="stat_total_population">0</h3>
                                            </div>
                                            <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                                <i class="bi bi-collection-fill fs-6"></i>
                                            </div>
                                        </div>
                                        <div class="small text-muted mt-2" style="font-size: 0.73rem;">
                                            <i class="bi bi-check-circle me-1 text-primary"></i>Total registered farm animals
                                        </div>
                                    </div>
                                </div>

                                <!-- Metric 2: Tagged Count -->
                                <div class="col-12 col-sm-4">
                                    <div class="p-2 p-md-3 rounded-3 border border-success-subtle bg-success-subtle text-start position-relative overflow-hidden h-100">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="text-uppercase fw-bold text-success" style="font-size: 0.72rem; letter-spacing: 0.04em;">Tagged Count</div>
                                                <h3 class="fw-bold mb-0 text-success font-monospace mt-1" id="stat_tagged_count">0</h3>
                                            </div>
                                            <div class="bg-success text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                                <i class="bi bi-tag-fill fs-6"></i>
                                            </div>
                                        </div>
                                        <div class="small text-muted mt-2" style="font-size: 0.73rem;">
                                            <i class="bi bi-shield-check me-1 text-success"></i>Ear tagged &amp; voucher issued
                                        </div>
                                    </div>
                                </div>

                                <!-- Metric 3: Pending Count -->
                                <div class="col-12 col-sm-4">
                                    <div class="p-2 p-md-3 rounded-3 border border-danger-subtle bg-danger-subtle text-start position-relative overflow-hidden h-100">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="text-uppercase fw-bold text-danger" style="font-size: 0.72rem; letter-spacing: 0.04em;">Pending Count</div>
                                                <h3 class="fw-bold mb-0 text-danger font-monospace mt-1" id="stat_pending_count">0</h3>
                                            </div>
                                            <div class="bg-danger text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                                <i class="bi bi-hourglass-split fs-6"></i>
                                            </div>
                                        </div>
                                        <div class="small text-muted mt-2" style="font-size: 0.73rem;">
                                            <i class="bi bi-exclamation-circle me-1 text-danger"></i>Balance needing registration
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Progress Bar and Species Sub-Totals -->
                            <div class="mt-3">
                                <div class="d-flex justify-content-between align-items-center small text-muted mb-1" style="font-size: 0.72rem;">
                                    <span>Ear Tagging Progress (Tagged vs Pending)</span>
                                    <span id="statProgressBarLabel">0 / 0 Animals</span>
                                </div>
                                <div class="progress" style="height: 8px; border-radius: 4px; background-color: #e9ecef;">
                                    <div class="progress-bar bg-success" id="statProgressBarTagged" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                    <div class="progress-bar bg-warning" id="statProgressBarPending" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="d-flex flex-wrap gap-1 mt-2 align-items-center" style="font-size: 0.7rem;">
                                    <span class="text-secondary fw-semibold me-1"><i class="bi bi-pie-chart me-1"></i>Census:</span>
                                    <span class="badge bg-light text-dark border">Cattle: <strong id="spec_cattle_val" class="text-primary">0</strong></span>
                                    <span class="badge bg-light text-dark border">Buffalo: <strong id="spec_buffalo_val" class="text-primary">0</strong></span>
                                    <span class="badge bg-light text-dark border">Goats: <strong id="spec_goat_val" class="text-primary">0</strong></span>
                                    <span class="badge bg-light text-dark border">Sheep: <strong id="spec_sheep_val" class="text-primary">0</strong></span>
                                    <span class="badge bg-light text-dark border">Swine: <strong id="spec_swine_val" class="text-primary">0</strong></span>
                                    <span class="badge bg-light text-dark border">Poultry: <strong id="spec_poultry_val" class="text-primary">0</strong></span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- STEP 2 (AUTO-FETCH): READ-ONLY LOCATION & FARMER DETAILS   -->
                <!-- ========================================================= -->
                <div class="card border-0 bg-light rounded-3 mb-3 shadow-xs">
                    <div class="card-header bg-light border-bottom py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <span class="badge bg-secondary text-white rounded-pill px-2 py-1">Step 2</span>
                            <span>Location &amp; Farmer Details</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle ms-1"><i class="bi bi-lock-fill me-1"></i>Auto-Fetched (Read-Only)</span>
                        </h6>
                        <small class="text-muted d-none d-sm-inline">Populated automatically from farm registry</small>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2 g-md-3">
                            <!-- Farmer's Name (Read-Only) -->
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-bold text-dark mb-1">
                                    Farmer's Name <span class="badge bg-secondary-subtle text-secondary py-0 px-1"><i class="bi bi-lock-fill"></i> Read-Only</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="bi bi-person-fill text-secondary"></i></span>
                                    <input type="text" name="farmer_name" id="ai_farmer_name" class="form-control form-control-sm bg-white fw-bold text-dark" placeholder="Auto-populated upon entering NIC / Reg No." required readonly>
                                </div>
                            </div>

                            <!-- Farmer NIC Display (Read-Only) -->
                            <div class="col-12 col-sm-6 col-md-3">
                                <label class="form-label small fw-bold text-dark mb-1">
                                    Farmer NIC <span class="badge bg-secondary-subtle text-secondary py-0 px-1"><i class="bi bi-lock-fill"></i> Read-Only</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="bi bi-fingerprint text-secondary"></i></span>
                                    <input type="text" name="farmer_nic_display" id="ai_farmer_nic_display" class="form-control form-control-sm bg-white font-monospace fw-bold" placeholder="Auto-populated" readonly>
                                </div>
                            </div>

                            <!-- District (Read-Only) -->
                            <div class="col-12 col-sm-6 col-md-2">
                                <label class="form-label small fw-bold text-dark mb-1">
                                    District <span class="badge bg-secondary-subtle text-secondary py-0 px-1"><i class="bi bi-lock-fill"></i> Read-Only</span>
                                </label>
                                <input type="text" name="district" id="ai_district" class="form-control form-control-sm bg-white" value="<?= htmlspecialchars($district_name ?? 'Ampara') ?>" required readonly>
                            </div>

                            <!-- VS Range (Read-Only) -->
                            <div class="col-12 col-sm-6 col-md-2">
                                <label class="form-label small fw-bold text-dark mb-1">
                                    VS Range <span class="badge bg-secondary-subtle text-secondary py-0 px-1"><i class="bi bi-lock-fill"></i> Read-Only</span>
                                </label>
                                <input type="text" name="vs_range" id="ai_vs_range" class="form-control form-control-sm bg-white" value="<?= htmlspecialchars($range_name ?? 'Ampara') ?>" required readonly>
                            </div>

                            <!-- VS Range Code (Element 1 of concatenation) -->
                            <div class="col-12 col-sm-6 col-md-1">
                                <label class="form-label small fw-bold text-primary mb-1">
                                    Range No.
                                </label>
                                <input type="text" name="range_code" id="ai_range_code" class="form-control form-control-sm font-monospace text-uppercase fw-bold bg-white" value="5314" readonly required>
                            </div>
                        </div>

                        <!-- Assigned Sequential Voucher Block Indicator -->
                        <div class="card border border-primary-subtle bg-primary-subtle mt-2 rounded-3 py-2 px-3 d-none" id="farmerBlockCard">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary text-white text-uppercase px-2 py-1 fw-bold">
                                        <i class="bi bi-link-45deg me-1"></i>Assigned Sequential Block
                                    </span>
                                    <span class="small text-dark">
                                        Allocated Range: <strong id="fBlockRangeText" class="font-monospace text-primary">—</strong>
                                    </span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="small text-dark">Remaining in Block: <strong id="fBlockRemText" class="badge bg-success font-monospace fs-6">0</strong></span>
                                    <span class="small text-dark">Next Serial: <strong id="fBlockNextText" class="badge bg-dark font-monospace fs-6">—</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- STEP 3 (GLOBAL MANUAL INPUTS): DATE & IO NUMBER           -->
                <!-- ========================================================= -->
                <div class="card border-0 bg-light rounded-3 mb-3 shadow-xs">
                    <div class="card-header bg-light border-bottom py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <span class="badge bg-primary text-white rounded-pill px-2 py-1">Step 3</span>
                            <span>Global Identification Parameters</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1">Manual Input</span>
                        </h6>
                        <small class="text-muted d-none d-sm-inline">Applies to all animals in this registration batch</small>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2 g-md-3">
                            <!-- Date of Identification -->
                            <div class="col-12 col-sm-6 col-md-6">
                                <label class="form-label small fw-bold text-dark mb-1">
                                    Date of Identification <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="bi bi-calendar-event text-secondary"></i></span>
                                    <input type="date" name="identification_date" id="ai_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>

                            <!-- Inspecting Officer (IO) Number -->
                            <div class="col-12 col-sm-6 col-md-6">
                                <label class="form-label small fw-bold text-dark mb-1">
                                    IO Number <span class="text-muted fw-normal">(Inspecting Officer Reference - Optional)</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="bi bi-person-badge text-secondary"></i></span>
                                    <input type="text" name="io_number" id="ai_io_number" class="form-control form-control-sm font-monospace" placeholder="e.g. IO-AMP-2026-001">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- STEP 4 (ANIMAL DETAILS): DYNAMIC GRID ROW SYSTEM          -->
                <!-- ========================================================= -->
                <div class="card border rounded-3 shadow-xs mb-2">
                    <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                                <span class="badge bg-primary text-white rounded-pill px-2 py-1">Step 4</span>
                                <span>Animal Specific Details</span>
                            </h6>
                            <small class="text-muted d-none d-sm-inline">Enter individual animal particulars. Ear Tag numbers dynamically concatenate.</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" id="animalsCountBadge">
                                1 Animal Entry
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-success fw-bold" id="btnAddGridRow">
                                <i class="bi bi-plus-lg me-1"></i>Add Animal
                            </button>
                        </div>
                    </div>

                    <!-- Concatenation Engine preview banner -->
                    <div class="bg-info-subtle border-bottom px-3 py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-primary text-white text-uppercase px-2 py-1 fw-bold">
                                <i class="bi bi-magic me-1"></i>Ear Tag Concatenation Formula
                            </span>
                            <span class="small text-dark font-monospace">
                                <code>[Range No. (5314)]</code> + <code>[Last 5 Digits of Farm Reg No.]</code> + <code>[Ear Tag Serial No.]</code>
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="small text-muted fw-bold">Live Preview:</span>
                            <div class="tag-preview-badge py-1 px-2" id="liveTagPreview">
                                <i class="bi bi-tag-fill me-1"></i><span id="previewTagText">531456789999</span>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive modal-table-scroll-container" style="overflow-x: auto; width: 100%; -webkit-overflow-scrolling: touch;">
                            <table class="table table-bordered table-hover align-middle mb-0" id="tableBatchGrid" style="min-width: 1250px;">
                                <thead class="table-light small text-secondary sticky-top">
                                    <tr>
                                        <th style="width: 35px;" class="text-center">#</th>
                                        <th style="min-width: 100px;">Ear Tag Serial Number <span class="text-danger">*</span></th>
                                        <th style="min-width: 170px;">Ear Tag No. <span class="badge bg-info-subtle text-info py-0 px-1">Auto</span></th>
                                        <th style="min-width: 155px;">Cattle Voucher S.No. <span class="text-danger">*</span></th>
                                        <th style="min-width: 110px;">Gender <span class="text-danger">*</span></th>
                                        <th style="min-width: 110px;">Colour</th>
                                        <th style="min-width: 110px;">Species</th>
                                        <th style="min-width: 130px;">Breed</th>
                                        <th style="min-width: 85px;">Age</th>
                                        <th style="min-width: 130px;">Purpose</th>
                                        <th style="min-width: 165px;" class="table-warning">Present Status <span class="text-danger">*</span></th>
                                        <th style="width: 40px;" class="text-center">Del</th>
                                    </tr>
                                </thead>
                                <tbody id="batchGridBody" class="small">
                                    <!-- Row 1: default -->
                                    <tr class="grid-animal-row" data-row="0">
                                        <td class="text-center text-muted fw-bold row-idx">1</td>
                                        <td>
                                            <input type="text" name="animals[0][animal_seq_no]" class="form-control form-control-sm font-monospace text-center input-seq fw-bold" value="" placeholder="e.g. 999" required>
                                        </td>
                                        <td>
                                            <input type="text" name="animals[0][ear_tag_no]" class="form-control form-control-sm font-monospace text-uppercase fw-bold text-primary input-tag bg-light" placeholder="5314..." readonly required>
                                        </td>
                                        <td>
                                            <input type="text" name="animals[0][voucher_serial_no]" class="form-control form-control-sm font-monospace text-uppercase input-voucher fw-bold" placeholder="e.g. A-918059" required>
                                        </td>
                                        <td>
                                            <select name="animals[0][gender]" class="form-select form-select-sm">
                                                <option value="Female" selected>Female</option>
                                                <option value="Male">Male</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="animals[0][colour]" class="form-control form-control-sm" placeholder="e.g. Black/White">
                                        </td>
                                        <td>
                                            <select name="animals[0][species]" class="form-select form-select-sm">
                                                <option value="Cattle" selected>Cattle</option>
                                                <option value="Buffalo">Buffalo</option>
                                                <option value="Goat">Goat</option>
                                                <option value="Sheep">Sheep</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="animals[0][breed]" class="form-control form-control-sm" placeholder="e.g. Friesian Cross" list="breedPresets">
                                        </td>
                                        <td>
                                            <input type="text" name="animals[0][age]" class="form-control form-control-sm" placeholder="e.g. 2 Yrs">
                                        </td>
                                        <td>
                                            <input type="text" name="animals[0][purpose]" class="form-control form-control-sm" placeholder="e.g. Dairy, Draught" value="Dairy Production">
                                        </td>
                                        <td class="table-warning">
                                            <select name="animals[0][present_status]" class="form-select form-select-sm fw-bold">
                                                <option value="Sold or transferred" selected>Sold or transferred</option>
                                                <option value="Slaughter">Slaughter</option>
                                                <option value="Died">Died</option>
                                                <option value="Tag Damaged / Removed" class="text-danger fw-bold">Tag Damaged / Removed</option>
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1 btn-del-row" title="Remove" disabled>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer bg-light p-2 text-end">
                        <button type="button" class="btn btn-sm btn-outline-success fw-bold" id="btnAddGridRowBottom">
                            <i class="bi bi-plus-circle me-1"></i>Add Another Animal Entry
                        </button>
                    </div>
                </div>

                <datalist id="breedPresets">
                    <option value="Friesian Cross"></option>
                    <option value="Jersey Cross"></option>
                    <option value="Sahiwal"></option>
                    <option value="Red Sindhi"></option>
                    <option value="Murrah Cross"></option>
                    <option value="Surti"></option>
                    <option value="Local (Indigenous)"></option>
                </datalist>

            </div>

            <div class="modal-footer bg-light py-2 px-3 px-md-4 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-maroon fw-bold px-3" id="btnSubmitIdentification">
                    <i class="bi bi-check-circle-fill me-1"></i>Save Animal Identification(s)
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: PRINTABLE ANIMAL IDENTIFICATION CARD (CATTLE VOUCHER)   -->
<!-- ============================================================== -->
<div class="modal fade" id="modalPrintCard" tabindex="-1" aria-labelledby="modalPrintCardLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-md">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white py-3" style="background-color: #370709;">
                <h6 class="modal-title fw-bold mb-0" id="modalPrintCardLabel">
                    <i class="bi bi-card-heading text-warning me-2"></i>Official Animal Identification Certificate / Voucher
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="printableCardContent">
                <div class="border border-2 border-dark rounded-3 p-3 bg-white" style="border-style: double !important;">
                    <div class="text-center mb-3 pb-2 border-bottom">
                        <div class="fw-bold small text-uppercase" style="letter-spacing: 0.05em; color: #370709;">
                            Department of Animal Production &amp; Health
                        </div>
                        <div class="small fw-bold text-secondary">Eastern Province, Sri Lanka</div>
                        <h6 class="fw-bold text-dark my-1">OFFICIAL CATTLE IDENTIFICATION VOUCHER</h6>
                        <span class="badge bg-danger text-white font-monospace" id="cardEarTag">—</span>
                    </div>

                    <div class="row g-2 small">
                        <div class="col-6">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Voucher Serial No:</span>
                            <strong id="cardVoucherNo" class="font-monospace text-dark">—</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Date of Identification:</span>
                            <strong id="cardDate" class="text-dark">—</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Farmer's Name:</span>
                            <strong id="cardFarmer">—</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Farm Reg. No:</span>
                            <strong id="cardFarmReg" class="font-monospace text-dark">—</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">VS Range / District:</span>
                            <strong id="cardRange">—</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Species &amp; Breed:</span>
                            <strong id="cardBreed">—</strong>
                        </div>
                        <div class="col-4">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Gender:</span>
                            <strong id="cardGender">—</strong>
                        </div>
                        <div class="col-4">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Colour:</span>
                            <strong id="cardColour">—</strong>
                        </div>
                        <div class="col-4">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Age:</span>
                            <strong id="cardAge">—</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Purpose (Ownership):</span>
                            <strong id="cardPurpose">—</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">IO Number:</span>
                            <strong id="cardIO" class="font-monospace">—</strong>
                        </div>
                        <div class="col-12 mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted d-block" style="font-size: 0.72rem;">Present Status:</span>
                                <span id="cardStatus" class="badge bg-primary text-white">—</span>
                            </div>
                            <div class="text-end" style="font-size: 0.7rem; color: #666;">
                                Authorized Veterinary Office Signature
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3 border-top">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="window.print();">
                    <i class="bi bi-printer-fill me-1"></i>Print Voucher Card
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- SCRIPT: NIC AUTO-FETCH & LIVE ANIMAL POPULATION CALCULATIONS  -->
<!-- ============================================================== -->
<script>
(function($) {
    if (!$) return;

    // Helper: update the 3 live metrics in the modal
    function renderLiveAnimalMetrics(resp) {
        if (!resp) return;
        const m = resp.metrics || {};
        const f = resp.farmer || {};
        const sp = resp.species_breakdown || {};

        const totalPop = parseInt(m.total_population || 0, 10);
        const taggedCount = parseInt(m.tagged_count || 0, 10);
        const pendingCount = parseInt(m.pending_count || Math.max(0, totalPop - taggedCount), 10);
        const pct = totalPop > 0 ? (taggedCount / totalPop * 100).toFixed(1) : (taggedCount > 0 ? '100.0' : '0.0');

        // 1. Total Population
        $("#stat_total_population").text(totalPop.toLocaleString());
        // 2. Tagged Count
        $("#stat_tagged_count").text(taggedCount.toLocaleString());
        // 3. Pending Count
        $("#stat_pending_count").text(pendingCount.toLocaleString());

        // Tagging completion percentage
        $("#statPercentageBadge").text(pct + '%');

        // Progress bar visual
        const taggedPct = totalPop > 0 ? Math.min(100, (taggedCount / totalPop * 100)) : (taggedCount > 0 ? 100 : 0);
        const pendingPct = totalPop > 0 ? Math.min(100 - taggedPct, (pendingCount / totalPop * 100)) : 0;
        $("#statProgressBarTagged").css("width", taggedPct + '%').attr("title", taggedCount + " Tagged (" + taggedPct.toFixed(1) + "%)");
        $("#statProgressBarPending").css("width", pendingPct + '%').attr("title", pendingCount + " Pending (" + pendingPct.toFixed(1) + "%)");
        $("#statProgressBarLabel").text(`${taggedCount} Tagged / ${totalPop} Total (${pendingCount} Pending)`);

        // Species Breakdown Counters
        $("#spec_cattle_val").text(sp.cattle || 0);
        $("#spec_buffalo_val").text(sp.buffalo || 0);
        $("#spec_goat_val").text(sp.goat || 0);
        $("#spec_sheep_val").text(sp.sheep || 0);
        $("#spec_swine_val").text(sp.swine || 0);
        $("#spec_poultry_val").text(sp.poultry || 0);

        if (f.farmer_name) {
            $("#statFarmerTitleDisplay").html(`<strong class="text-dark">${f.farmer_name}</strong> <span class="text-muted">(${f.vs_range || 'Range'})</span>`);
            $("#statFarmerRegSyncBadge")
                .text(f.farm_reg_no ? "Reg: " + f.farm_reg_no : "NIC Verified")
                .attr("class", "badge bg-success-subtle text-success font-monospace");
        }
    }
    window.renderLiveAnimalMetrics = renderLiveAnimalMetrics;

    // Core function: fetch & calculate farmer metrics by NIC
    let activeNicRequest = null;
    function fetchAndCalculateByNIC(nicNumber, showFeedback) {
        const nic = (nicNumber || '').trim();
        if (!nic) {
            $("#farmFetchStatusText").html("Enter or click the NIC number (or Farm Reg No.) to auto-load live animal calculations.");
            $("#farmFetchStatusIcon").attr("class", "bi bi-info-circle text-primary flex-shrink-0");
            return;
        }

        $("#farmFetchStatusText").html(`<span class="spinner-border spinner-border-sm me-1 text-primary"></span> Calculating live animal population & ear tagging balance for NIC: <strong>${nic}</strong>...`);
        $("#farmFetchStatusIcon").attr("class", "bi bi-arrow-repeat text-primary flex-shrink-0");
        $("#btnFetchFarmerByNic").prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1"></span>Calculating');

        if (activeNicRequest) {
            activeNicRequest.abort();
        }

        activeNicRequest = $.ajax({
            url: "processors/animal_identification_crud.php?action=get_farmer_by_nic",
            type: "GET",
            data: { nic: nic },
            dataType: "json",
            success: function(resp) {
                activeNicRequest = null;
                $("#btnFetchFarmerByNic").prop("disabled", false).html('<i class="bi bi-calculator-fill me-1"></i>Calculate');

                if (resp && resp.success && resp.farmer) {
                    const f = resp.farmer;
                    // Auto-populate Step 1 and Step 2 fields
                    if (f.farm_reg_no && !$("#ai_farm_reg_no").val()) {
                        $("#ai_farm_reg_no").val(f.farm_reg_no);
                    }
                    $("#ai_farmer_name").val(f.farmer_name);
                    $("#ai_farmer_nic_display").val(f.nic || nic);
                    if (f.district) $("#ai_district").val(f.district);
                    if (f.vs_range) $("#ai_vs_range").val(f.vs_range);
                    $("#ai_range_code").val("5314");
                    if (f.range_id) $("#ai_range_id").val(f.range_id);
                    if (f.district_id) $("#ai_district_id").val(f.district_id);

                    // Display live 3 metrics
                    renderLiveAnimalMetrics(resp);

                    $("#farmFetchStatusText").html(`<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>NIC Verified:</span> ${f.farmer_name} • ${resp.metrics.total_population} Animals registered (${resp.metrics.tagged_count} Tagged, ${resp.metrics.pending_count} Pending)`);
                    $("#farmFetchStatusIcon").attr("class", "bi bi-check-circle-fill text-success flex-shrink-0");

                    // Trigger sequence lookup and concatenation refresh if available
                    if (typeof window.fetchNextSequenceForFarm === 'function' && f.farm_reg_no) {
                        window.fetchNextSequenceForFarm(f.farm_reg_no);
                    }
                    if (typeof window.refreshGridConcatenations === 'function') {
                        window.refreshGridConcatenations();
                    }
                } else {
                    $("#farmFetchStatusText").html(`<span class="text-danger fw-semibold"><i class="bi bi-exclamation-triangle-fill me-1"></i>Unregistered NIC.</span> No active census records found for NIC: ${nic}`);
                    $("#farmFetchStatusIcon").attr("class", "bi bi-exclamation-circle text-danger flex-shrink-0");
                    $("#statFarmerRegSyncBadge").text("NIC Not Found").attr("class", "badge bg-danger-subtle text-danger font-monospace");

                    if (showFeedback && typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: "info",
                            title: "Farmer NIC Not Found",
                            text: resp.message || "No registered livestock farm census found for the entered NIC.",
                            confirmButtonColor: "#820100"
                        });
                    }
                }
            },
            error: function(xhr, status) {
                if (status === "abort") return;
                activeNicRequest = null;
                $("#btnFetchFarmerByNic").prop("disabled", false).html('<i class="bi bi-calculator-fill me-1"></i>Calculate');
                $("#farmFetchStatusText").html(`<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Error connecting to server.</span>`);
                $("#farmFetchStatusIcon").attr("class", "bi bi-x-circle text-danger flex-shrink-0");
            }
        });
    }
    window.fetchAndCalculateByNIC = fetchAndCalculateByNIC;

    // -------------------------------------------------------------
    // TRIGGER EVENT LISTENER ON NIC NUMBER FIELD
    // -------------------------------------------------------------
    // 1. Click & Focus: Trigger calculation if NIC is clicked or focused
    $(document).on("click focus", "#ai_farmer_nic", function() {
        const val = $(this).val().trim();
        if (val && val.length >= 4) {
            fetchAndCalculateByNIC(val, false);
        }
    });

    // 2. Input: Debounced live auto-calculation as user types
    let nicInputTimer = null;
    $(document).on("input", "#ai_farmer_nic", function() {
        const val = $(this).val().trim();
        clearTimeout(nicInputTimer);
        if (val.length >= 4) {
            nicInputTimer = setTimeout(function() {
                fetchAndCalculateByNIC(val, false);
            }, 300);
        }
    });

    // 3. Change & Blur: Immediate calculation when leaving or picking from datalist
    $(document).on("change blur", "#ai_farmer_nic", function() {
        const val = $(this).val().trim();
        if (val && val.length >= 4) {
            fetchAndCalculateByNIC(val, false);
        }
    });

    // 4. Calculate Button click trigger
    $(document).on("click", "#btnFetchFarmerByNic", function() {
        const val = $("#ai_farmer_nic").val().trim();
        if (!val) {
            if (typeof Swal !== 'undefined') {
                Swal.fire("Enter NIC Number", "Please enter a Farmer National Identity Card (NIC) number.", "warning");
            } else {
                alert("Please enter a Farmer National Identity Card (NIC) number.");
            }
            return;
        }
        fetchAndCalculateByNIC(val, true);
    });

})(jQuery);
</script>