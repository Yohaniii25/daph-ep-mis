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
                <!-- STEP 1 (TRIGGER): FARM REGISTRATION NO.                   -->
                <!-- ========================================================= -->
                <div class="card border border-primary-subtle shadow-xs rounded-3 mb-3 bg-white">
                    <div class="card-header bg-primary-subtle border-primary-subtle py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="fw-bold mb-0 text-primary d-flex align-items-center gap-2">
                            <span class="badge bg-primary text-white rounded-pill px-2 py-1">Step 1</span>
                            <span>Farm Registration No. <span class="badge bg-warning text-dark ms-1">Trigger</span></span>
                        </h6>
                        <small class="text-muted d-none d-sm-inline"><i class="bi bi-info-circle me-1"></i>Primary key for automatic farmer &amp; jurisdiction retrieval</small>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-center">
                            <div class="col-12 col-md-7">
                                <label class="form-label small fw-bold text-dark mb-1">
                                    Enter Farm Registration No. <span class="text-danger">*</span>
                                    <span class="text-muted fw-normal small">(Type or pick from registered farms)</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-primary"><i class="bi bi-search"></i></span>
                                    <input type="text" name="farm_reg_no" id="ai_farm_reg_no" class="form-control form-control-sm font-monospace text-uppercase fw-bold" placeholder="e.g. 104829375" required autocomplete="off" list="registeredFarmsList">
                                    <button type="button" class="btn btn-primary btn-sm px-3 fw-semibold" id="btnFetchFarmByReg">
                                        <i class="bi bi-arrow-repeat me-1"></i>Fetch Farm
                                    </button>
                                </div>
                                <datalist id="registeredFarmsList">
                                    <?php if (!empty($registered_farms)): ?>
                                        <?php foreach ($registered_farms as $rfm): ?>
                                            <option value="<?= htmlspecialchars($rfm['farm_reg_no']) ?>" label="<?= htmlspecialchars($rfm['farmer_name'] . ' (' . ($rfm['vs_range'] ?? '') . ')') ?>">
                                                <?= htmlspecialchars($rfm['farmer_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </datalist>
                            </div>
                            <div class="col-12 col-md-5">
                                <div id="farmFetchStatus" class="p-2 rounded bg-light border text-muted small d-flex align-items-center gap-2">
                                    <i class="bi bi-info-circle text-primary flex-shrink-0" id="farmFetchStatusIcon"></i>
                                    <span id="farmFetchStatusText">Enter Farm Registration No. to auto-fetch farmer and location details.</span>
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
                            <div class="col-12 col-md-5">
                                <label class="form-label small fw-bold text-dark mb-1">
                                    Farmer's Name <span class="badge bg-secondary-subtle text-secondary py-0 px-1"><i class="bi bi-lock-fill"></i> Read-Only</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="bi bi-person-fill text-secondary"></i></span>
                                    <input type="text" name="farmer_name" id="ai_farmer_name" class="form-control form-control-sm bg-white fw-bold text-dark" placeholder="Auto-populated upon entering Farm Reg No." required readonly>
                                </div>
                            </div>

                            <!-- District (Read-Only) -->
                            <div class="col-12 col-sm-6 col-md-3">
                                <label class="form-label small fw-bold text-dark mb-1">
                                    District <span class="badge bg-secondary-subtle text-secondary py-0 px-1"><i class="bi bi-lock-fill"></i> Read-Only</span>
                                </label>
                                <input type="text" name="district" id="ai_district" class="form-control form-control-sm bg-white" value="<?= htmlspecialchars($district_name ?? 'Ampara') ?>" required readonly>
                            </div>

                            <!-- VS Range (Read-Only) -->
                            <div class="col-12 col-sm-6 col-md-3">
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