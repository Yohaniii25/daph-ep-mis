<!-- Premium Edit Counterfoil Book Asset Modal -->
<div class="modal fade" id="editCounterfoilModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header text-white border-0 py-3 px-4" style="background: linear-gradient(135deg, #820100 0%, #4a0000 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.15); width: 44px; height: 44px;">
                        <i class="bi bi-book-half fs-4 text-white"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Edit Counterfoil Book Record</h5>
                        <small class="text-white-50" style="font-size: 12px;">Audited Statutory Receipt &amp; Register Book Tracking</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Form -->
            <form id="editCounterfoilForm" action="processors/update_counterfoil.php" method="POST">
                <input type="hidden" name="id" id="edit_counterfoil_id">
                <input type="hidden" name="unit" id="edit_counterfoil_unit" value="range_veterinary_officer">

                <div class="modal-body p-4 bg-light-subtle">
                    <!-- SECTION 1: Item Identification & Date -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-body p-3">
                            <h6 class="text-uppercase fw-bold text-muted small mb-3" style="letter-spacing: 0.5px;">
                                <i class="bi bi-tag-fill me-1 text-primary"></i> Counterfoil Book Specification
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-7 position-relative">
                                    <label class="form-label small fw-bold text-dark">
                                        Counterfoil Book Type <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-journal-bookmark"></i></span>
                                        <input type="text" 
                                               name="counterfoil_type" 
                                               id="edit_counterfoil_type" 
                                               class="form-control border-start-0" 
                                               placeholder="Type or select type (e.g. AI Register, Cash Receipt Book)..." 
                                               autocomplete="off" 
                                               required>
                                    </div>
                                    <div id="edit_counterfoil_type_suggestions" class="dropdown-menu w-100 shadow border-0 mt-1 py-1" style="display: none; position: absolute; z-index: 1060; max-height: 220px; overflow-y: auto;"></div>
                                    <small class="text-muted" style="font-size: 11px;">
                                        <i class="bi bi-magic me-1 text-primary"></i>Auto-suggests from saved &amp; baseline book categories. Custom types allowed.
                                    </small>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold text-dark">
                                        Date Received / Issued <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-calendar-event"></i></span>
                                        <input type="date" name="purchase_date" id="edit_counterfoil_purchase_date" class="form-control border-start-0" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">
                                        Specific Serial Number Range <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="book_serial_no" id="edit_counterfoil_book_serial_no" class="form-control font-monospace fw-semibold" placeholder="e.g. 001001 - 001050" required>
                                    <small class="text-muted" style="font-size: 10px;">Specific start and end serial numbers (e.g. 001001 - 001050)</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">
                                        Total Page Counts <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" name="page_count" id="edit_counterfoil_page_count" class="form-control font-monospace fw-bold" placeholder="50" min="1" required>
                                        <span class="input-group-text bg-light text-muted small">Pages / Leaves</span>
                                    </div>
                                    <small class="text-muted" style="font-size: 10px;">Total leaves or folios per book</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: Fiscal Baseline & Active Count Cards -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-body p-3">
                            <h6 class="text-uppercase fw-bold text-muted small mb-3" style="letter-spacing: 0.5px;">
                                <i class="bi bi-calculator me-1 text-success"></i> Inventory Stock &amp; Availability
                            </h6>
                            <div class="row g-3">
                                <!-- Baseline Card -->
                                <div class="col-md-4">
                                    <div class="p-3 rounded-3 border bg-white h-100 position-relative">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label small fw-bold text-secondary mb-0">
                                                <i class="bi bi-lock-fill me-1 text-warning"></i>Baseline Stock <span class="text-danger">*</span>
                                            </label>
                                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 small">Audit</span>
                                        </div>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-secondary border-end-0 fw-bold">#</span>
                                            <input type="number" name="initial_count" id="edit_counterfoil_initial_count" class="form-control border-start-0 fw-bold fs-5 text-dark" min="0" required oninput="calcEditCounterfoilAvailability()">
                                        </div>
                                        <small class="text-muted d-block mt-2" style="font-size: 11px;">
                                            Initial baseline count.
                                        </small>
                                    </div>
                                </div>

                                <!-- Received Quantity Card -->
                                <div class="col-md-4">
                                    <div class="p-3 rounded-3 border bg-white h-100 position-relative">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label small fw-bold text-success mb-0">
                                                <i class="bi bi-plus-circle-fill me-1 text-success"></i>Received Qty <span class="text-danger">*</span>
                                            </label>
                                        </div>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-success border-end-0 fw-bold">#</span>
                                            <input type="number" name="received_quantity" id="edit_counterfoil_received_quantity" class="form-control border-start-0 fw-bold fs-5 text-success" min="0" value="0" required oninput="calcEditCounterfoilAvailability()">
                                        </div>
                                        <small class="text-muted d-block mt-2" style="font-size: 11px;">
                                            Newly logged received amount.
                                        </small>
                                    </div>
                                </div>

                                <!-- Active Available Card -->
                                <div class="col-md-4">
                                    <div class="p-3 rounded-3 border bg-white h-100 position-relative">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label small fw-bold text-dark mb-0">
                                                <i class="bi bi-boxes me-1 text-primary"></i>Current Availability
                                            </label>
                                            <span class="badge bg-primary-subtle text-primary border px-2 py-1 small">Auto</span>
                                        </div>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-primary border-end-0 fw-bold">#</span>
                                            <input type="number" name="available_quantity" id="edit_counterfoil_quantity" class="form-control border-start-0 fw-bold fs-5 text-primary bg-light" readonly min="0" required>
                                        </div>
                                        <small class="text-primary d-block mt-2 fw-semibold" style="font-size: 11px;">
                                            <i class="bi bi-check2-circle me-1 text-success"></i>Baseline + Received Qty
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: Condition Status & Dynamic Deduction Alert -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-body p-3">
                            <h6 class="text-uppercase fw-bold text-muted small mb-3" style="letter-spacing: 0.5px;">
                                <i class="bi bi-shield-check me-1 text-danger"></i> Register Condition
                            </h6>
                            <div class="row g-2">
                                <div class="col-md-12">
                                    <label class="form-label small fw-bold text-dark">
                                        Condition Status <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-activity"></i></span>
                                        <select name="current_condition" id="edit_counterfoil_condition" class="form-select border-start-0 shadow-none fw-semibold" required>
                                            <option value="Good" class="text-success fw-bold">&#9679; Good (Intact &amp; In-Service)</option>
                                            <option value="Fair" class="text-warning fw-bold">&#9679; Fair (Usable / Minor Wear)</option>
                                            <option value="Damaged" class="text-danger fw-bold">&#9679; Damaged (Automated -1 Deduction)</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Dynamic Damaged Deduction Notice -->
                                <div class="col-md-12">
                                    <div id="edit_counterfoil_damaged_notice" class="alert alert-danger border-0 rounded-3 shadow-sm py-2 px-3 mt-2 d-none" style="display: none; background: #fee2e2;">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-exclamation-triangle-fill text-danger fs-5 flex-shrink-0"></i>
                                            <div class="small text-danger-emphasis">
                                                <strong>Automated Inventory Math:</strong> Condition updated to <em>Damaged</em>. <strong>1 unit</strong> has been automatically deducted from the active available quantity.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 4: Procurement & Tracking Details -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-body p-3">
                            <h6 class="text-uppercase fw-bold text-muted small mb-3" style="letter-spacing: 0.5px;">
                                <i class="bi bi-file-earmark-text me-1 text-info"></i> Procurement &amp; Tracking Details
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Issue Order No.</label>
                                    <input type="text" name="issue_order_no" id="edit_counterfoil_issue_order_no" class="form-control" placeholder="e.g. IO-2024-001">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Received From</label>
                                    <input type="text" name="received_from" id="edit_counterfoil_received_from" class="form-control" placeholder="e.g. Govt Press / District Office">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Receipt No.</label>
                                    <input type="text" name="receipt_no" id="edit_counterfoil_receipt_no" class="form-control" placeholder="e.g. REC-99201">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Specification (Brand / Model / Form Ref)</label>
                                    <input type="text" name="specification" id="edit_counterfoil_specification" class="form-control" placeholder="e.g. Form 172, 100 Pages Duplicate">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 5: Remarks / Notes -->
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body p-3">
                            <label class="form-label small fw-bold text-dark mb-1">
                                <i class="bi bi-chat-left-text me-1 text-muted"></i>Additional Remarks &amp; Stock Notes
                            </label>
                            <textarea name="remarks" id="edit_counterfoil_remarks" class="form-control shadow-none" rows="2" placeholder="e.g. Counterfoil book physical verification notes..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer bg-white border-top py-3 px-4 d-flex justify-content-between align-items-center">
                    <div class="small text-muted d-none d-sm-block">
                        <i class="bi bi-shield-lock me-1"></i>All modifications logged to statutory audit trail
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary px-3 fw-semibold rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn text-white px-4 fw-semibold rounded-pill shadow-sm" style="background: linear-gradient(135deg, #820100 0%, #5e0100 100%);">
                            <i class="bi bi-check2-circle me-1"></i> Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function calcEditCounterfoilAvailability() {
    var base = parseInt(document.getElementById('edit_counterfoil_initial_count').value) || 0;
    var recv = parseInt(document.getElementById('edit_counterfoil_received_quantity').value) || 0;
    var avail = base + recv;
    var cond = document.getElementById('edit_counterfoil_condition').value;
    if (cond === 'Damaged') {
        avail = Math.max(0, avail - 1);
    }
    document.getElementById('edit_counterfoil_quantity').value = avail;
}
</script>
