<div class="modal fade" id="addCounterfoilModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #e67e22;">
                <h5 class="modal-title"><i class="bi bi-journal-bookmark me-2"></i>New Counterfoil Book Entry</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addCounterfoilForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="unit" value="range_veterinary_officer">
                    <div class="row g-3">
                        <!-- Book Type Selection -->
                        <div class="col-md-12 position-relative">
                            <label class="form-label small fw-bold">Counterfoil Book Type <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-journal-bookmark text-muted"></i></span>
                                <input type="text" 
                                       name="counterfoil_type" 
                                       id="add_counterfoil_type" 
                                       class="form-control" 
                                       placeholder="Select or enter book type (e.g. Animal Health Certificate Book, AI Register, Cash Receipt Book)..." 
                                       autocomplete="off" 
                                       required>
                            </div>
                            <div id="add_counterfoil_type_suggestions" class="dropdown-menu w-100 shadow border-0 mt-1 py-1" style="display: none; position: absolute; z-index: 1060; max-height: 220px; overflow-y: auto;"></div>
                            <small class="text-muted" style="font-size: 11px;">
                                <i class="bi bi-magic me-1 text-primary"></i>Official security book catalog: Animal Health Certificates, Cattle Movement Permits, General 172 Cash Receipts, AI Registers, etc.
                            </small>
                        </div>

                        <!-- Specific Serial Number Range -->
                        <div class="col-md-7">
                            <label class="form-label small fw-bold">Specific Serial Number Range <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light small fw-semibold">Start Serial</span>
                                <input type="text" id="add_cf_serial_start" class="form-control font-monospace" placeholder="e.g. 001001" oninput="updateAddSerialRange()">
                                <span class="input-group-text bg-light small fw-semibold">End Serial</span>
                                <input type="text" id="add_cf_serial_end" class="form-control font-monospace" placeholder="e.g. 001050" oninput="updateAddSerialRange()">
                            </div>
                            <input type="hidden" name="book_serial_no" id="add_counterfoil_book_serial_no" required>
                            <small class="text-muted" style="font-size: 11px;">
                                Range: <strong id="add_serial_range_preview" class="text-dark font-monospace">-</strong>
                            </small>
                        </div>

                        <!-- Total Page Counts -->
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">Total Page Counts <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="page_count" id="add_counterfoil_page_count" class="form-control font-monospace fw-bold" placeholder="50" min="1" required>
                                <span class="input-group-text bg-light text-muted small">Pages / Leaves</span>
                            </div>
                            <small class="text-muted" style="font-size: 11px;">Total leaves or folios in received book</small>
                        </div>

                        <!-- Condition -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Condition / Physical Status <span class="text-danger">*</span></label>
                            <select name="current_condition" class="form-select" required>
                                <option value="Good" selected>Good (Intact)</option>
                                <option value="Fair">Fair (Slight wear)</option>
                                <option value="Damaged">Damaged (Defective / Spoilt)</option>
                            </select>
                        </div>

                        <!-- Purchase / Received Date -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date Received / Logged <span class="text-danger">*</span></label>
                            <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <!-- Quantity Tracking Block -->
                        <div class="col-md-12">
                            <div class="p-3 bg-light rounded border">
                                <div class="row g-3 align-items-center">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-secondary mb-1">
                                            <i class="bi bi-lock-fill me-1"></i>Initial Baseline Books <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" name="initial_count" id="add_counterfoil_initial_count" class="form-control fw-bold" min="0" value="1" required oninput="calcAddCounterfoilAvailability()">
                                        <small class="text-muted" style="font-size: 10px;">Pre-existing inventory stock</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-success mb-1">
                                            <i class="bi bi-plus-circle-fill me-1"></i>Received Books <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" name="received_quantity" id="add_counterfoil_received_quantity" class="form-control fw-bold border-success" min="0" value="0" required oninput="calcAddCounterfoilAvailability()">
                                        <small class="text-muted" style="font-size: 10px;">Newly received batch count</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-primary mb-1">
                                            <i class="bi bi-calculator-fill me-1"></i>Current Available Books
                                        </label>
                                        <input type="number" name="available_quantity" id="add_counterfoil_available_quantity" class="form-control fw-bold bg-white text-primary border-primary fs-5" readonly value="1">
                                        <small class="text-primary fw-semibold" style="font-size: 10px;"><i class="bi bi-check2-circle me-1"></i>Total Active Books in Range</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Specification -->
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Specification (Brand / Model / Form Ref)</label>
                            <input type="text" name="specification" class="form-control" placeholder="e.g. Form 172, Duplicate Carbonized, Govt Press Reference">
                        </div>

                        <!-- Vouchers & Receipts -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Issue Order No.</label>
                            <input type="text" name="issue_order_no" class="form-control" placeholder="e.g. IO-2026-001">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Received From</label>
                            <input type="text" name="received_from" class="form-control" placeholder="e.g. Govt Press / District Office">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Receipt No.</label>
                            <input type="text" name="receipt_no" class="form-control" placeholder="e.g. REC-99201">
                        </div>

                        <!-- Additional Remarks -->
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Remarks &amp; Inventory Notes</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Enter procurement details, security seals, or batch identifiers..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-white px-4 shadow-sm" style="background-color: #e67e22;">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Counterfoil Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateAddSerialRange() {
    var s = document.getElementById('add_cf_serial_start').value.trim();
    var e = document.getElementById('add_cf_serial_end').value.trim();
    var range = '';
    if (s && e) {
        range = s + ' - ' + e;
    } else if (s) {
        range = s;
    } else if (e) {
        range = e;
    }
    document.getElementById('add_counterfoil_book_serial_no').value = range;
    document.getElementById('add_serial_range_preview').textContent = range || '-';

    // Auto-calculate page counts if start and end are integer numbers
    var numStart = parseInt(s, 10);
    var numEnd = parseInt(e, 10);
    if (!isNaN(numStart) && !isNaN(numEnd) && numEnd >= numStart) {
        var pageInput = document.getElementById('add_counterfoil_page_count');
        if (!pageInput.value || pageInput.value === '0') {
            pageInput.value = (numEnd - numStart + 1);
        }
    }
}
</script>