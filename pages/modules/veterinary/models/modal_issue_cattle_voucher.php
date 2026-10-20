<!-- Modal: Digitize Cattle Voucher & Animal Birth Certificate -->
<div class="modal fade" id="modalIssueCattleVoucher" tabindex="-1" aria-labelledby="modalIssueCattleVoucherLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <!-- Modal Header -->
            <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, #370709 0%, #5a1215 100%); border-bottom: 3px solid #c59b27;">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-warning text-dark p-2 d-flex align-items-center justify-content-center shadow-xs" style="width: 44px; height: 44px;">
                        <i class="bi bi-file-earmark-medical-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h5 class="modal-title fw-bold mb-0 text-white" id="modalIssueCattleVoucherLabel">
                                Cattle Voucher / Animal Birth Certificate
                            </h5>
                            <span class="badge bg-warning text-dark fw-bold px-2 py-1 font-monospace">STATUTORY FORM</span>
                        </div>
                        <small class="text-white-50">Department of Animal Production &amp; Health — Official Animal Identification &amp; Birth Certificate Registry</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Form -->
            <form id="formCattleVoucher" autocomplete="off">
                <input type="hidden" name="action" value="save_voucher">
                <input type="hidden" name="voucher_id" id="s06_voucher_id" value="0">
                <input type="hidden" name="range_id" id="s06_range_id" value="<?= htmlspecialchars($range_id ?? 1) ?>">

                <div class="modal-body p-4 bg-light">
                    <!-- Alert notification container inside modal -->
                    <div id="s06ModalAlert" class="alert d-none shadow-xs mb-3 py-2 px-3 small rounded-3" role="alert"></div>

                    <!-- HEADER SECTION: VOUCHER SERIAL NUMBER -->
                    <div class="card border-0 rounded-3 shadow-xs mb-3 bg-white">
                        <div class="card-body p-3">
                            <div class="row align-items-center g-3">
                                <div class="col-md-7">
                                    <label class="form-label small fw-bold text-dark mb-1">
                                        <i class="bi bi-hash text-danger me-1"></i>Schedule - 06 Voucher Serial Number (Unique Identifier) <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light fw-bold text-muted font-monospace"><i class="bi bi-upc-scan"></i></span>
                                        <input type="text" name="voucher_number" id="s06_voucher_number" class="form-control font-monospace fw-bold text-danger fs-6" placeholder="e.g. APH/CV/2026/0001 or A-339222" required>
                                        <button type="button" class="btn btn-outline-secondary" id="btnAutoGenerateSerial" title="Auto Generate Next Sequential Serial Number">
                                            <i class="bi bi-magic me-1"></i>Auto Generate
                                        </button>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.74rem;">Prescribed statutory serial numbering under Section 4 of Animals Act.</small>
                                </div>
                                <div class="col-md-5 text-md-end">
                                    <div class="d-inline-flex flex-column align-items-md-end">
                                        <span class="small text-muted mb-1">Certificate Purpose</span>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fw-semibold">
                                            <i class="bi bi-patch-check-fill me-1"></i>Official Animal Birth Certificate &amp; Title
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 01. DETAILS OF THE OWNER -->
                    <div class="card border-0 rounded-3 shadow-xs mb-3 bg-white">
                        <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge rounded-pill bg-danger text-white px-2 py-1">01</span>
                                <h6 class="fw-bold mb-0 text-dark">Details of the Owner</h6>
                            </div>
                            <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Entering NIC will auto-fill owner &amp; farm details</small>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">
                                        National Identity Card No (NIC) <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light"><i class="bi bi-card-heading text-primary"></i></span>
                                        <input type="text" name="farmer_nic" id="s06_farmer_nic" class="form-control font-monospace fw-semibold" placeholder="e.g. 198654567890" list="s06_nic_datalist" required>
                                        <button type="button" class="btn btn-primary" id="btnLookupNic" title="Auto-fetch owner & holding details">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.72rem;">Type, click, or select to auto-populate profile</small>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label small fw-bold text-dark">Title Prefix</label>
                                    <select name="owner_title" id="s06_owner_title" class="form-select form-select-sm">
                                        <option value="Mr.">Mr.</option>
                                        <option value="Mrs.">Mrs.</option>
                                        <option value="Miss">Miss</option>
                                        <option value="Rev.">Rev.</option>
                                        <option value="Dr.">Dr.</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">
                                        Name of the Owner <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="farmer_name" id="s06_farmer_name" class="form-control form-control-sm" placeholder="Full name of livestock owner" required>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold text-dark">Address of the Owner</label>
                                    <input type="text" name="farmer_address" id="s06_farmer_address" class="form-control form-control-sm" placeholder="Permanent residential address / street / postal code">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 02. DETAILS OF THE ANIMAL / FARM HOLDING -->
                    <div class="card border-0 rounded-3 shadow-xs mb-3 bg-white">
                        <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge rounded-pill bg-danger text-white px-2 py-1">02</span>
                                <h6 class="fw-bold mb-0 text-dark">Details of the Animal / Farm Holding</h6>
                            </div>
                            <small class="text-muted"><i class="bi bi-geo-alt me-1"></i>Entering Farm Reg No will also sync holding location</small>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">
                                        Registration Number of the Farm
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light"><i class="bi bi-house-door text-warning"></i></span>
                                        <input type="text" name="farm_reg_no" id="s06_farm_reg_no" class="form-control font-monospace fw-semibold" placeholder="e.g. 530112345" list="s06_farm_reg_datalist">
                                        <button type="button" class="btn btn-outline-secondary" id="btnLookupFarmReg" title="Auto-fetch farm holding location">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.72rem;">DAPH Livestock Farm Registration Serial</small>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Province</label>
                                    <input type="text" name="province" id="s06_province" class="form-control form-control-sm" value="Eastern Province" placeholder="e.g. Eastern Province">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">District</label>
                                    <input type="text" name="district" id="s06_district" class="form-control form-control-sm" value="<?= htmlspecialchars($district_name ?? 'Ampara') ?>" placeholder="e.g. Ampara">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Divisional Secretariat Area (DS Area)</label>
                                    <input type="text" name="ds_division" id="s06_ds_division" class="form-control form-control-sm" placeholder="e.g. Ampara DS Division">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Government Veterinary Surgeon's Area (Town)</label>
                                    <input type="text" name="vs_division" id="s06_vs_division" class="form-control form-control-sm" value="<?= htmlspecialchars($range_name ?? 'Ampara') ?>" placeholder="e.g. Ampara VS Range">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">G.N. Division &amp; No.</label>
                                    <input type="text" name="gn_division" id="s06_gn_division" class="form-control form-control-sm" placeholder="e.g. GN_01 / Grama Niladhari Area">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 03. DESCRIPTION OF THE ANIMAL (BIRTH CERTIFICATE) -->
                    <div class="card border-0 rounded-3 shadow-xs mb-3 bg-white">
                        <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge rounded-pill bg-danger text-white px-2 py-1">03</span>
                                <h6 class="fw-bold mb-0 text-dark">Description of the Animal (Animal Birth Particulars)</h6>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace">
                                <i class="bi bi-tag-fill me-1"></i>Auto-Synced with Tag Registry
                            </span>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">
                                        Animal Identification Number (Ear Tag) <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light"><i class="bi bi-tag text-success"></i></span>
                                        <input type="text" name="ear_tag_number" id="s06_ear_tag_number" class="form-control font-monospace fw-bold text-success" placeholder="e.g. 531412345001" list="s06_ear_tag_datalist" required>
                                        <button type="button" class="btn btn-success" id="btnLookupAnimal" title="Auto-fill animal particulars from registry">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.72rem;">Type, pick, or press Enter to auto-fill Breed, Sex, DOB &amp; Marks</small>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Date of Identification</label>
                                    <input type="date" name="identification_date" id="s06_identification_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Animal Species</label>
                                    <select name="species" id="s06_species" class="form-select form-select-sm">
                                        <option value="Cattle" selected>Neat Cattle</option>
                                        <option value="Buffalo">Buffalo</option>
                                        <option value="Goat">Goat</option>
                                        <option value="Sheep">Sheep</option>
                                        <option value="Swine">Swine / Pig</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Breed <span class="text-danger">*</span></label>
                                    <input type="text" name="breed" id="s06_breed" class="form-control form-control-sm" placeholder="e.g. Jersey Cross, Friesian, Sahiwal" required>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label small fw-bold text-dark">Sex <span class="text-danger">*</span></label>
                                    <select name="sex" id="s06_sex" class="form-select form-select-sm" required>
                                        <option value="Female" selected>Female</option>
                                        <option value="Male">Male</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-dark">Date of Birth (Animal Birth Date)</label>
                                    <input type="date" name="date_of_birth" id="s06_date_of_birth" class="form-control form-control-sm" max="<?= date('Y-m-d') ?>">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-dark">Age (Years / Months)</label>
                                    <input type="text" name="age" id="s06_age" class="form-control form-control-sm" placeholder="e.g. 2 Yrs or 18 Mo">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Colour / Coat Pattern</label>
                                    <input type="text" name="colour" id="s06_colour" class="form-control form-control-sm" placeholder="e.g. Brown, Black & White, Fawn">
                                </div>

                                <div class="col-md-8">
                                    <label class="form-label small fw-bold text-dark">Other Specific Marks &amp; Genetic Characteristics (if any)</label>
                                    <input type="text" name="specific_marks" id="s06_specific_marks" class="form-control form-control-sm" placeholder="e.g. White patch on forehead, horn structure, branded markings, dewlap">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 04. SIGNATURES & AUTHORIZATIONS -->
                    <div class="card border-0 rounded-3 shadow-xs bg-white">
                        <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge rounded-pill bg-danger text-white px-2 py-1">04</span>
                                <h6 class="fw-bold mb-0 text-dark">Signatures &amp; Authorizations</h6>
                            </div>
                            <span class="badge bg-light text-dark border">Veterinary Surgeon Legal Attestation</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Issue Date <span class="text-danger">*</span></label>
                                    <input type="date" name="issue_date" id="s06_issue_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Government Veterinary Surgeon Name</label>
                                    <input type="text" name="vs_name" id="s06_vs_name" class="form-control form-control-sm" value="<?= htmlspecialchars($_SESSION['name'] ?? 'Government Veterinary Surgeon') ?>" placeholder="Name of Certifying VS">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-dark">Certificate Status</label>
                                    <select name="status" id="s06_status" class="form-select form-select-sm">
                                        <option value="Active" selected>Active (Valid Title &amp; Registry)</option>
                                        <option value="Transferred">Transferred (Sold / Ownership Changed)</option>
                                        <option value="Dead">Dead (Deceased)</option>
                                        <option value="Slaughtered">Slaughtered</option>
                                        <option value="Damaged">Damaged (Withdrawn / Lost Tag)</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold text-dark">Remarks / Special Conditions</label>
                                    <input type="text" name="remarks" id="s06_remarks" class="form-control form-control-sm" placeholder="Optional remarks, registry reference, or special conditions">
                                </div>

                                <div class="col-12">
                                    <div class="p-3 bg-light border rounded-3 text-muted small d-flex align-items-center gap-3">
                                        <i class="bi bi-shield-check text-success fs-3 flex-shrink-0"></i>
                                        <div>
                                            <strong class="text-dark d-block">Official Schedule-06 Statutory Certification</strong>
                                            Upon saving, this system record will officially serve as the animal's birth certificate and legal identity title under the Animals Act. Both the <strong>Printable Cattle Voucher</strong> and the <strong>Counterfoil (Receipt/Stub)</strong> can be generated and printed immediately.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="modal-footer bg-light py-2 px-4 justify-content-between flex-wrap gap-2 border-top">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm px-3" id="btnSaveAndPrintVoucher">
                            <i class="bi bi-printer me-1"></i>Save &amp; Print Immediately
                        </button>
                        <button type="submit" class="btn btn-danger btn-sm px-4 fw-bold shadow-sm" id="btnSubmitCattleVoucher">
                            <i class="bi bi-check-circle-fill me-1"></i>Issue Cattle Voucher
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Dynamic Suggestions Datalists for Auto-Fetching -->
<datalist id="s06_nic_datalist">
    <?php if (!empty($registered_farmers)): ?>
        <?php foreach ($registered_farmers as $rf): if (empty($rf['nic'])) continue; ?>
            <option value="<?= htmlspecialchars($rf['nic']) ?>"><?= htmlspecialchars($rf['farmer_name'] ?? '') ?><?= !empty($rf['registration_no']) ? ' (' . htmlspecialchars($rf['registration_no']) . ')' : '' ?></option>
        <?php endforeach; ?>
    <?php endif; ?>
</datalist>

<datalist id="s06_farm_reg_datalist">
    <?php if (!empty($registered_farmers)): ?>
        <?php foreach ($registered_farmers as $rf): if (empty($rf['registration_no'])) continue; ?>
            <option value="<?= htmlspecialchars($rf['registration_no']) ?>"><?= htmlspecialchars($rf['farmer_name'] ?? '') ?><?= !empty($rf['nic']) ? ' (NIC: ' . htmlspecialchars($rf['nic']) . ')' : '' ?></option>
        <?php endforeach; ?>
    <?php endif; ?>
</datalist>

<datalist id="s06_ear_tag_datalist">
    <?php if (!empty($registered_tags)): ?>
        <?php foreach ($registered_tags as $rt): if (empty($rt['ear_tag_no'])) continue; ?>
            <option value="<?= htmlspecialchars($rt['ear_tag_no']) ?>"><?= htmlspecialchars($rt['species'] ?? 'Cattle') ?> - <?= htmlspecialchars($rt['breed'] ?? '') ?><?= !empty($rt['farmer_name']) ? ' (' . htmlspecialchars($rt['farmer_name']) . ')' : '' ?></option>
        <?php endforeach; ?>
    <?php endif; ?>
</datalist>
