<!-- View Inventory Modal -->
<div class="modal fade" id="viewInventoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-light">
                <h5 class="modal-title"><i class="bi bi-box-seam-fill me-2"></i>Building Inventory Item Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Inventory Number</small>
                        <span class="fw-bold fs-6 text-dark" id="view_inventory_number">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Inventory Type</small>
                        <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 fs-6" id="view_inventory_type">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Item Name</small>
                        <span class="fw-bold fs-6 text-dark" id="view_inventory_item">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Located Property / Station</small>
                        <span class="fw-semibold text-dark" id="view_inventory_property">-</span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Issue Order No.</small>
                        <span class="fw-semibold text-dark" id="view_issue_order_no">-</span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Received From</small>
                        <span class="fw-semibold text-dark" id="view_received_from">-</span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Receipt No.</small>
                        <span class="fw-semibold text-dark" id="view_receipt_no">-</span>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 border rounded bg-light text-center">
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:10px;">Initial Baseline</small>
                            <span class="fw-bold fs-5 text-secondary" id="view_initial_count">0</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 border rounded bg-light text-center">
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:10px;">Received Quantity</small>
                            <span class="fw-bold fs-5 text-success" id="view_received_quantity">0</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 border border-primary rounded bg-primary-subtle text-center">
                            <small class="text-primary d-block text-uppercase fw-semibold" style="font-size:10px;">Current Availability</small>
                            <span class="fw-bold fs-5 text-primary" id="view_available_quantity">0</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Current Condition</small>
                        <span class="fw-semibold text-dark" id="view_inventory_condition">-</span>
                    </div>
                    <div class="col-md-12">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Specification / Remarks (Brand, Model, Specs)</small>
                        <span class="text-secondary" id="view_inventory_specification">-</span>
                    </div>
                    <div class="col-md-12">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Additional Notes / Remarks</small>
                        <span class="text-secondary" id="view_inventory_remarks">-</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
