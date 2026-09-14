<div class="modal fade" id="addCounterfoilModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #e67e22;">
                <h5 class="modal-title"><i class="bi bi-book me-2"></i>New Counterfoil Book Entry</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addCounterfoilForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="unit" value="range_veterinary_officer">
                    <div class="row g-3">
                        <div class="col-md-12 position-relative">
                            <label class="form-label small fw-bold">Counterfoil Book Type <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-journal-bookmark text-muted"></i></span>
                                <input type="text" 
                                       name="counterfoil_type" 
                                       id="add_counterfoil_type" 
                                       class="form-control" 
                                       placeholder="Type or select type (e.g. AI Register, Cash Receipt Book)..." 
                                       autocomplete="off" 
                                       required>
                            </div>
                            <div id="add_counterfoil_type_suggestions" class="dropdown-menu w-100 shadow border-0 mt-1 py-1" style="display: none; position: absolute; z-index: 1060; max-height: 220px; overflow-y: auto;"></div>
                            <small class="text-muted" style="font-size: 11px;">
                                <i class="bi bi-magic me-1 text-primary"></i>Auto-suggests from saved &amp; baseline book categories. Custom types allowed.
                            </small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Condition <span class="text-danger">*</span></label>
                            <select name="current_condition" class="form-select" required>
                                <option value="Good" selected>Good</option>
                                <option value="Fair">Fair</option>
                                <option value="Damaged">Damaged</option>
                            </select>
                        </div>
                        <!-- Availability Auto-Calculation Block -->
                        <div class="col-md-12">
                            <div class="p-3 bg-light rounded border">
                                <div class="row g-3 align-items-center">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-secondary mb-1">
                                            <i class="bi bi-lock-fill me-1"></i>Initial Baseline Stock <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" name="initial_count" id="add_counterfoil_initial_count" class="form-control fw-bold" min="0" value="1" required oninput="calcAddCounterfoilAvailability()">
                                        <small class="text-muted" style="font-size: 10px;">Manually entered baseline stock</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-success mb-1">
                                            <i class="bi bi-plus-circle-fill me-1"></i>Received Quantity <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" name="received_quantity" id="add_counterfoil_received_quantity" class="form-control fw-bold border-success" min="0" value="0" required oninput="calcAddCounterfoilAvailability()">
                                        <small class="text-muted" style="font-size: 10px;">Newly received amounts logged</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-primary mb-1">
                                            <i class="bi bi-calculator-fill me-1"></i>Current Availability (Auto)
                                        </label>
                                        <input type="number" name="available_quantity" id="add_counterfoil_available_quantity" class="form-control fw-bold bg-white text-primary border-primary fs-5" readonly value="1">
                                        <small class="text-primary fw-semibold" style="font-size: 10px;"><i class="bi bi-check2-circle me-1"></i>Baseline + Received Amount</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Book / Serial No. Range (e.g. 1-5)</label>
                            <input type="text" name="book_serial_no" id="add_counterfoil_book_serial_no" class="form-control font-monospace" placeholder="e.g. 1-5">
                            <small class="text-muted" style="font-size: 10px;">Number of book - serial numbers (e.g. 1-5)</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Page Counts (Leaves / Pages per Book)</label>
                            <input type="text" name="page_count" id="add_counterfoil_page_count" class="form-control" placeholder="e.g. 50 Pages / 100 Folios">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date of Purchase / Received</label>
                            <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Specification (Brand / Model / Form Ref)</label>
                            <input type="text" name="specification" class="form-control" placeholder="e.g. Form 172, 100 Pages Duplicate">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Issue Order No.</label>
                            <input type="text" name="issue_order_no" class="form-control" placeholder="e.g. IO-2024-001">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Received From</label>
                            <input type="text" name="received_from" class="form-control" placeholder="e.g. Govt Press / District Office">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Receipt No.</label>
                            <input type="text" name="receipt_no" class="form-control" placeholder="e.g. REC-99201">
                        </div>
                        <!-- Custody / Issue & Return Tracking Block -->
                        <div class="col-md-12">
                            <div class="p-3 bg-light rounded border">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-person-badge-fill text-primary me-2"></i>
                                    <span class="fw-bold small text-dark text-uppercase" style="letter-spacing: 0.5px;">Custody / Issue &amp; Return Tracking</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">To Whom Issued</label>
                                        <input type="text" name="issued_to" id="add_counterfoil_issued_to" class="form-control" placeholder="e.g. Dr. K. Perera / Range Veterinary Officer">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Date of Issue</label>
                                        <input type="date" name="date_of_issue" id="add_counterfoil_date_of_issue" class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Date of Return</label>
                                        <input type="date" name="date_of_return" id="add_counterfoil_date_of_return" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Enter book serial number ranges (e.g., Serial No. 45001 - 45100) or dispatch log tracking metadata..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-white" style="background-color: #e67e22;">Save Counterfoil</button>
                </div>
            </form>
        </div>
    </div>
</div>