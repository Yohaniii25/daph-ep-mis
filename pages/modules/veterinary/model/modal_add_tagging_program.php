<!-- Modal: Tagging Program Data Entry with Cattle Voucher Linkage -->
<div class="modal fade" id="modalTaggingProgram" tabindex="-1" aria-labelledby="modalTaggingProgramLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color: #370709;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-tag-fill fs-5 text-warning"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="modalTaggingProgramLabel">Log Ear Tagging Program</h6>
                        <small class="text-white-50">Record tagging event, farmer registration, and cattle identity vouchers</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="formTaggingProgram" action="processors/save_ear_tag_program.php" method="POST">
                <input type="hidden" name="program_id" id="prog_id" value="0">
                <input type="hidden" name="is_ajax" value="0">

                <div class="modal-body p-4">
                    <!-- SECTION 1: PROGRAM & FARMER INFORMATION (STRICTLY NO PROGRAM TITLE!) -->
                    <div class="card border-0 bg-light mb-4 shadow-sm">
                        <div class="card-body p-3">
                            <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                                <i class="bi bi-person-vcard text-primary"></i> Program & Farmer Information
                            </h6>
                            <div class="row g-3">
                                <!-- Date -->
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-dark">Date <span class="text-danger">*</span></label>
                                    <input type="date" name="program_date" id="prog_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                                </div>

                                <!-- IC Number with Auto-lookup -->
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-dark">
                                        Farmer IC Number <span class="text-danger">*</span>
                                        <span id="icLookupSpinner" class="spinner-border spinner-border-sm text-primary d-none ms-1" role="status"></span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white"><i class="bi bi-credit-card-2-front"></i></span>
                                        <input type="text" name="nic_no" id="prog_nic_no" class="form-control form-control-sm text-uppercase" placeholder="e.g. 198512345678 or 851234567V" required autocomplete="off">
                                    </div>
                                    <div id="icLookupFeedback" class="small mt-1 text-muted" style="font-size: 0.75rem;">Type IC Number to auto-populate</div>
                                </div>

                                <!-- Farmer's Name -->
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-dark">Farmer's Name <span class="text-danger">*</span></label>
                                    <input type="text" name="farmer_name" id="prog_farmer_name" class="form-control form-control-sm" placeholder="Full name of farmer" required>
                                </div>

                                <!-- Farm Registration Number (Auto-populated by IC Number) -->
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-dark">
                                        Farm Registration Number
                                        <span class="badge bg-secondary-subtle text-secondary py-0 px-1" style="font-size: 0.68rem;">Auto-populated</span>
                                    </label>
                                    <input type="text" name="farm_reg_no" id="prog_farm_reg_no" class="form-control form-control-sm" placeholder="e.g. FR-KD-2024-001">
                                </div>

                                <!-- Farmer Address (Auto-populated by IC Number) -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">
                                        Farm / Residence Address
                                        <span class="badge bg-secondary-subtle text-secondary py-0 px-1" style="font-size: 0.68rem;">Auto-populated</span>
                                    </label>
                                    <input type="text" name="address" id="prog_address" class="form-control form-control-sm" placeholder="Farmer village / road address">
                                </div>

                                <!-- Staff/Vaccinators involved -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Staff / Vaccinators Involved <span class="text-danger">*</span></label>
                                    <input type="text" name="staff_involved" id="prog_staff_involved" class="form-control form-control-sm" placeholder="Surgeon, LDI, Livestock Officer, or field assistants" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: TAG QUANTITIES -->
                    <div class="card border-0 bg-light mb-4 shadow-sm">
                        <div class="card-body p-3">
                            <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                                <i class="bi bi-123 text-info"></i> Ear Tag Utilization Metrics
                            </h6>
                            <div class="row g-3 align-items-center">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-info">Number of Tags Used <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="tags_used" id="prog_tags_used" class="form-control form-control-sm font-monospace fw-bold" value="0" min="0" required>
                                        <span class="input-group-text bg-white small text-muted">Tags</span>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.72rem;">Automatically synchronized with voucher entries</small>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-warning">Number of Spoiled / Damaged Tags <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="tags_spoiled" id="prog_tags_spoiled" class="form-control form-control-sm font-monospace fw-bold" value="0" min="0" required>
                                        <span class="input-group-text bg-white small text-muted">Tags</span>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.72rem;">Tags bent, broken, or unusable during tagging</small>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-secondary">General Remarks / Notes</label>
                                    <input type="text" name="remarks" id="prog_remarks" class="form-control form-control-sm" placeholder="Optional tagging notes or condition">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: ANIMAL DETAILS & CATTLE VOUCHER LINKAGE (IDENTITY CARDS) -->
                    <div class="card border shadow-sm">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                            <div>
                                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                                    <i class="bi bi-card-checklist text-success"></i>
                                    Animal Details & Cattle Voucher Linkage (Identity Cards)
                                </h6>
                                <p class="text-muted small mb-0">Record individual animal attributes (Breed, Sex, Age) interconnected with official cattle voucher numbers</p>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary-subtle text-primary px-3 py-2 fw-semibold" id="voucherCountBadge">
                                    <i class="bi bi-link-45deg me-1"></i>0 Animals Linked
                                </span>
                                <button type="button" class="btn btn-sm btn-outline-success" id="btnAddAnimalRow">
                                    <i class="bi bi-plus-lg me-1"></i>Add Animal
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                                <table class="table table-hover align-middle mb-0" id="tableAnimalVouchers">
                                    <thead class="table-light text-secondary small uppercase sticky-top">
                                        <tr>
                                            <th style="width: 5%;" class="text-center">#</th>
                                            <th style="width: 25%;">Ear Tag Number <span class="text-danger">*</span></th>
                                            <th style="width: 25%;">Cattle Voucher No. (ID Card) <span class="text-danger">*</span></th>
                                            <th style="width: 20%;">Breed <span class="text-danger">*</span></th>
                                            <th style="width: 12%;">Sex <span class="text-danger">*</span></th>
                                            <th style="width: 13%;">Age <span class="text-danger">*</span></th>
                                            <th style="width: 5%;" class="text-center">Remove</th>
                                        </tr>
                                    </thead>
                                    <tbody id="animalVouchersBody" class="small">
                                        <!-- Dynamically inserted animal rows -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-light py-2 text-center border-top">
                            <button type="button" class="btn btn-sm btn-link text-decoration-none text-success fw-bold" id="btnAddAnimalRowBottom">
                                <i class="bi bi-plus-circle me-1"></i>Add Another Animal Record
                            </button>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnSubmitProgram" class="btn btn-sm text-white px-4 fw-bold" style="background-color: #820100;">
                        <i class="bi bi-check-circle me-1"></i>Save Tagging Program
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
