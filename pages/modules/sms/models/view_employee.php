<!-- Modal 3: View Technical Officer Details for SMS Unit -->
<div class="modal fade" id="viewEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-badge me-2"></i>Officer Profile Summary</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-3">
                    <div class="bg-light p-3 rounded-circle d-inline-block border shadow-sm">
                        <i class="bi bi-person-circle fs-1 text-primary"></i>
                    </div>
                    <h5 class="fw-bold text-dark mt-2 mb-0" id="view_full_name">-</h5>
                    <span class="badge bg-primary mt-1" id="view_designation">-</span>
                </div>
                <ul class="list-group list-group-flush border-top border-bottom mb-3">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted small">Service / Employee ID:</span>
                        <strong class="text-dark" id="view_service_number">-</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted small">Assigned Role:</span>
                        <strong class="text-info" id="view_role">-</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted small">Service Category:</span>
                        <span class="text-dark fw-medium" id="view_service_category">-</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted small">Official Email:</span>
                        <span class="text-dark" id="view_email">-</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted small">Contact Number:</span>
                        <span class="text-dark" id="view_phone">-</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted small">Appointment Date:</span>
                        <span class="text-dark" id="view_appointment_date">-</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted small">Current Position Since:</span>
                        <span class="text-dark" id="view_appointment_date_current">-</span>
                    </li>
                </ul>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
