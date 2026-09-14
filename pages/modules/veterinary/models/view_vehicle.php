<div class="modal fade" id="viewVehicleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #b08723;">
                <h5 class="modal-title"><i class="bi bi-truck me-2"></i>Registered Vehicle Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Vehicle Type</small>
                        <span class="fw-bold fs-6 text-dark" id="view_vehicle_type">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Vehicle Number</small>
                        <span class="fw-bold text-dark font-monospace" id="view_vehicle_number">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Chassis Number</small>
                        <span class="fw-semibold text-secondary font-monospace" id="view_chassis_number">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Current Condition</small>
                        <span class="fw-semibold text-dark" id="view_current_condition">-</span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Initial Baseline</small>
                        <span class="fw-bold text-dark" id="view_vehicle_initial_count">1</span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Received Quantity</small>
                        <span class="fw-bold text-success" id="view_vehicle_received_quantity">0</span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Available Quantity</small>
                        <span class="fw-bold text-primary" id="view_vehicle_quantity">1</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Specification (Make / Model)</small>
                        <span class="fw-semibold text-dark" id="view_vehicle_specification">-</span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Issue Order No.</small>
                        <span class="fw-semibold text-dark" id="view_vehicle_issue_order_no">-</span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Received From</small>
                        <span class="fw-semibold text-dark" id="view_vehicle_received_from">-</span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Receipt No.</small>
                        <span class="fw-semibold text-dark" id="view_vehicle_receipt_no">-</span>
                    </div>
                    <div class="col-md-12">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Specification / Remarks / Other Details</small>
                        <span class="text-secondary" id="view_other_details">-</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
