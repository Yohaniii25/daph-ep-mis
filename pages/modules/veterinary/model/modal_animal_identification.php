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
                <!-- Section 1: Farmer & Operational Jurisdiction -->
                <div class="card border-0 bg-light mb-3 shadow-xs rounded-3">
                    <div class="card-body p-2 p-md-3">
                        <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                            <i class="bi bi-geo-alt-fill text-danger"></i> 1. Location &amp; Farmer Information
                        </h6>
                        <div class="row g-2 g-md-3">
                            <!-- District -->
                            <div class="col-12 col-sm-6 col-md-3">
                                <label class="form-label small fw-bold text-dark mb-1">District <span class="text-danger">*</span></label>
                                <input type="text" name="district" id="ai_district" class="form-control form-control-sm bg-white" value="<?= htmlspecialchars($district_name ?? 'Ampara') ?>" required readonly>
                            </div>

                            <!-- VS Range -->
                            <div class="col-12 col-sm-6 col-md-3">
                                <label class="form-label small fw-bold text-dark mb-1">VS Range <span class="text-danger">*</span></label>
                                <input type="text" name="vs_range" id="ai_vs_range" class="form-control form-control-sm bg-white" value="<?= htmlspecialchars($range_name ?? 'Ampara') ?>" required readonly>
                            </div>

                            <!-- VS Range Number / Code (Element 1 of concatenation) -->
                            <div class="col-12 col-sm-6 col-md-2">
                                <label class="form-label small fw-bold text-primary mb-1">
                                    VS Range No. <span class="badge bg-primary-subtle text-primary py-0 px-1">El. 1</span>
                                </label>
                                <input type="text" name="range_code" id="ai_range_code" class="form-control form-control-sm font-monospace text-uppercase fw-bold" value="<?= htmlspecialchars($range_code ?? '01') ?>" placeholder="e.g. 01" required>
                            </div>

                            <!-- Identification Date -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label small fw-bold text-dark mb-1">Date of Identification <span class="text-danger">*</span></label>
                                <input type="date" name="identification_date" id="ai_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <!-- Farmer Search / Auto-Complete -->
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-bold text-dark mb-1">
                                    Farmer's Name <span class="text-danger">*</span>
                                    <span class="text-muted fw-normal small">(Type to search registered farmers)</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="bi bi-person-fill text-secondary"></i></span>
                                    <input type="text" name="farmer_name" id="ai_farmer_name" class="form-control form-control-sm" placeholder="e.g. M. N. Mohamed Rasheed" required autocomplete="off" list="registeredFarmersList">
                                    <datalist id="registeredFarmersList">
                                        <?php if (!empty($registered_farmers)): ?>
                                            <?php foreach ($registered_farmers as $rfm): ?>
                                                <option value="<?= htmlspecialchars($rfm['farmer_name']) ?>" data-reg="<?= htmlspecialchars($rfm['farm_reg_no'] ?? '') ?>">
                                                    Reg: <?= htmlspecialchars($rfm['farm_reg_no'] ?? '') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </datalist>
                                </div>
                                <small id="farmerLookupHint" class="text-muted d-block mt-1" style="font-size: 0.72rem;">Selecting a farmer auto-populates Farm Registration No.</small>
                            </div>

                            <!-- Farm Registration Number (Element 2 of concatenation) -->
                            <div class="col-12 col-sm-6 col-md-3">
                                <label class="form-label small fw-bold text-primary mb-1">
                                    Farm Reg. No. <span class="badge bg-primary-subtle text-primary py-0 px-1">El. 2</span> <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="farm_reg_no" id="ai_farm_reg_no" class="form-control form-control-sm font-monospace text-uppercase fw-bold" placeholder="e.g. 104829375" required>
                            </div>

                            <!-- Inspecting Officer (IO) Number -->
                            <div class="col-12 col-sm-6 col-md-3">
                                <label class="form-label small fw-bold text-dark mb-1">IO Number <span class="text-muted fw-normal">(Optional)</span></label>
                                <input type="text" name="io_number" id="ai_io_number" class="form-control form-control-sm font-monospace" placeholder="e.g. IO-AMP-2026-001">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Ear Tag Auto-Generation Live Concatenation Engine (Compact Banner) -->
                <div class="card border border-info-subtle bg-info-subtle mb-3 rounded-3 py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-primary text-white text-uppercase px-2 py-1 fw-bold">
                                <i class="bi bi-magic me-1"></i>Concatenation Engine
                            </span>
                            <span class="small text-dark">
                                <code>[VS Range No.]</code> - <code>[Farm Reg No.]</code> - <code>[Seq No.]</code>
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="small text-muted">Live Preview:</span>
                            <div class="tag-preview-badge py-1 px-2" id="liveTagPreview">
                                <i class="bi bi-tag-fill me-1"></i><span id="previewTagText"><?= htmlspecialchars($range_code ?? '01') ?>-FARM-001</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Animal Identification Details Grid (Multi-Row / Batch Enabled) -->
                <div class="card border rounded-3 shadow-xs mb-2">
                    <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="bi bi-card-checklist text-success me-1"></i>2. Animal Identification &amp; Voucher Data Entry
                            </h6>
                            <small class="text-muted d-none d-sm-inline">Enter individual animal particulars. Ear Tag numbers dynamically auto-concatenate.</small>
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

                    <!-- Horizontal Scroll Guidance for Mobile / Tablet Viewports -->
                    <div class="bg-light px-3 py-1 border-bottom d-flex justify-content-between align-items-center small text-muted">
                        <span><i class="bi bi-arrows-expand me-1 text-primary"></i> <span class="d-none d-md-inline">Scroll horizontally within table to review and fill all animal specifications</span><span class="d-inline d-md-none">Scroll table horizontally for all 12 columns</span></span>
                        <span class="badge bg-white text-secondary border">12 Columns</span>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive modal-table-scroll-container" style="overflow-x: auto; width: 100%; -webkit-overflow-scrolling: touch;">
                            <table class="table table-bordered table-hover align-middle mb-0" id="tableBatchGrid" style="min-width: 1220px;">
                                <thead class="table-light small text-secondary sticky-top">
                                    <tr>
                                        <th style="width: 35px;" class="text-center">#</th>
                                        <th style="width: 75px;">Seq No.</th>
                                        <th style="min-width: 170px;">Ear Tag No. <span class="badge bg-info-subtle text-info py-0 px-1">Auto</span></th>
                                        <th style="min-width: 150px;">Cattle Voucher S.No. <span class="text-danger">*</span></th>
                                        <th style="min-width: 105px;">Gender <span class="text-danger">*</span></th>
                                        <th style="min-width: 110px;">Colour</th>
                                        <th style="min-width: 110px;">Species</th>
                                        <th style="min-width: 120px;">Breed</th>
                                        <th style="min-width: 85px;">Age</th>
                                        <th style="min-width: 130px;">Purpose</th>
                                        <!-- Strict Present Status Dropdown -->
                                        <th style="min-width: 165px;" class="table-warning">
                                            Present Status <span class="text-danger">*</span>
                                        </th>
                                        <th style="width: 40px;" class="text-center">Del</th>
                                    </tr>
                                </thead>
                                <tbody id="batchGridBody" class="small">
                                    <!-- Row 1: default -->
                                    <tr class="grid-animal-row" data-row="0">
                                        <td class="text-center text-muted fw-bold row-idx">1</td>
                                        <td>
                                            <input type="text" name="animals[0][animal_seq_no]" class="form-control form-control-sm font-monospace text-center input-seq" value="001" style="max-width: 70px;" required>
                                        </td>
                                        <td>
                                            <input type="text" name="animals[0][ear_tag_no]" class="form-control form-control-sm font-monospace text-uppercase fw-bold text-primary input-tag bg-light" readonly required>
                                        </td>
                                        <td>
                                            <input type="text" name="animals[0][voucher_serial_no]" class="form-control form-control-sm font-monospace text-uppercase input-voucher" placeholder="e.g. A-918059" required>
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
                                            <input type="text" name="animals[0][breed]" class="form-control form-control-sm" placeholder="e.g. Friesian, Jersey" list="breedPresets">
                                        </td>
                                        <td>
                                            <input type="text" name="animals[0][age]" class="form-control form-control-sm" placeholder="e.g. 2 Yrs">
                                        </td>
                                        <td>
                                            <input type="text" name="animals[0][purpose]" class="form-control form-control-sm" placeholder="e.g. Dairy, Draught" value="Dairy Production">
                                        </td>
                                        <!-- Strict Dropdown Menu -->
                                        <td class="table-warning">
                                            <select name="animals[0][present_status]" class="form-select form-select-sm fw-bold">
                                                <option value="Sold or transferred" selected>Sold or transferred</option>
                                                <option value="Slaughter">Slaughter</option>
                                                <option value="Died">Died</option>
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
