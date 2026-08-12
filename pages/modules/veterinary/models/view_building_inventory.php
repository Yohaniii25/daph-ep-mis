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
                    <div class="col-md-8">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Inventory Item</small>
                        <span class="fw-bold fs-6 text-dark" id="view_inventory_item">-</span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Available Quantity</small>
                        <span class="fw-bold text-primary" id="view_available_quantity">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Located Property</small>
                        <span class="fw-semibold text-dark" id="view_inventory_property">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Current Condition</small>
                        <span class="fw-semibold text-dark" id="view_inventory_condition">-</span>
                    </div>
                    <div class="col-md-12">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Item Specification</small>
                        <span class="text-secondary" id="view_inventory_specification">-</span>
                    </div>
                    <div class="col-md-12">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Remarks / Internal Notes</small>
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
