<div class="modal fade" id="viewRepairModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="bi bi-tools me-2"></i>Maintenance &amp; Repair Log Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="view_repair_id" value="">
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
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Invoice / Voucher Ref</small>
                        <span class="fw-semibold text-dark font-monospace" id="view_repair_invoice_ref">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Cost / Transaction Amount (LKR)</small>
                        <span class="fw-bold text-success fs-5 font-monospace" id="view_repair_amount">-</span>
                    </div>

                    <!-- Approval Status & Routing Card -->
                    <div class="col-md-12">
                        <div class="card border rounded-3 bg-light p-3">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-4">
                                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Approval Status</small>
                                    <div id="view_repair_status_badge" class="mt-1">-</div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Approval Authority Tier</small>
                                    <span class="fw-bold text-dark small" id="view_repair_authority">-</span>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Approval Decision / Date</small>
                                    <span class="text-secondary small" id="view_repair_decision_info">-</span>
                                </div>
                                <div class="col-12 d-none" id="view_repair_rejection_container">
                                    <div class="alert alert-danger py-2 px-3 mb-0 mt-2 small">
                                        <strong>Rejection Reason:</strong> <span id="view_repair_rejection_reason">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Receipt Attachment -->
                    <div class="col-md-12">
                        <small class="text-muted d-block text-uppercase fw-semibold mb-1" style="font-size:11px;">Digital Receipt Document</small>
                        <div id="view_repair_receipt_container">
                            <span class="text-muted fst-italic small">No receipt document attached.</span>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size:11px;">Detailed Repair Description</small>
                        <span class="text-secondary" id="view_repair_description">-</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <div id="view_repair_approval_actions" class="d-none">
                    <button type="button" class="btn btn-outline-danger me-2" id="btn_reject_repair" onclick="handleRepairAction('reject')">
                        <i class="bi bi-x-circle me-1"></i>Reject Request
                    </button>
                    <button type="button" class="btn btn-success" id="btn_approve_repair" onclick="handleRepairAction('approve')">
                        <i class="bi bi-check-circle-fill me-1"></i>Approve Request
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
