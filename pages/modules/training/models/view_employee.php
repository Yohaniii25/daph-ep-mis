<!-- Modal 3: View Staff Officer Details -->
<div class="modal fade" id="viewEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #370709;">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-badge-fill me-2"></i>Staff Officer Profile Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="card bg-light border-0 p-3 mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle p-3 bg-white shadow-sm text-primary">
                            <i class="bi bi-person-bounding-box fs-2"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1 text-dark" id="view_full_name">-</h5>
                            <span class="badge bg-primary px-3 py-1" id="view_designation">-</span>
                            <span class="badge bg-secondary ms-1" id="view_role">-</span>
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block fw-bold text-uppercase">Service Number</small>
                        <span class="fw-bold text-dark fs-6" id="view_service_number">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block fw-bold text-uppercase">Service Category</small>
                        <span class="fw-semibold text-dark" id="view_service_category">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block fw-bold text-uppercase">Email Address</small>
                        <span class="text-dark" id="view_email">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block fw-bold text-uppercase">Phone Contact</small>
                        <span class="text-dark" id="view_phone">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block fw-bold text-uppercase">Appointment Date</small>
                        <span class="text-dark" id="view_appointment_date">-</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block fw-bold text-uppercase">Current Position Date</small>
                        <span class="text-dark" id="view_appointment_date_current">-</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Close Profile</button>
            </div>
        </div>
    </div>
</div>
