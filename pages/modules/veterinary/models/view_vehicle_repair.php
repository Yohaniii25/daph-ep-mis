<div class="modal fade" id="viewRepairModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="bi bi-tools me-2"></i>Maintenance & Repair Log Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Vehicle Registration</small>
                        <span class="fw-bold fs-6 text-dark font-monospace" id="view_repair_vehicle_number">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Repair Date</small>
                        <span class="fw-semibold text-secondary" id="view_repair_date">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Repair Done</small>
                        <span class="fw-bold text-dark" id="view_repair_done">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Place of Repair</small>
                        <span class="fw-semibold text-dark" id="view_place_of_repair">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Cost / Amount (LKR)</small>
                        <span class="fw-bold text-success fs-6" id="view_repair_amount">-</span>
                    </div>
                    <div class="col-md-12">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Detailed Repair Description</small>
                        <span class="text-secondary" id="view_repair_description">-</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
