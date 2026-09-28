<!-- Modal: View Cattle Vouchers & Animal Details for Program -->
<div class="modal fade" id="modalViewVouchers" tabindex="-1" aria-labelledby="modalViewVouchersLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color: #370709;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-card-checklist fs-5 text-warning"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="modalViewVouchersLabel">Cattle Vouchers & Animal Identity Cards</h6>
                        <small class="text-white-50" id="viewProgSubtitle">Program Details</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4">
                <!-- Farmer & Program Info Card -->
                <div class="card border-0 bg-light p-3 mb-3">
                    <div class="row g-2 small">
                        <div class="col-md-4">
                            <span class="text-muted d-block">Program Date:</span>
                            <strong id="viewProgDate" class="text-dark">--</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted d-block">Farmer Name:</span>
                            <strong id="viewProgFarmer" class="text-dark">--</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted d-block">Farmer IC / NIC:</span>
                            <strong id="viewProgNIC" class="text-dark">--</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted d-block">Farm Registration No:</span>
                            <strong id="viewProgRegNo" class="text-dark">--</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted d-block">Staff / Vaccinators:</span>
                            <strong id="viewProgStaff" class="text-dark">--</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted d-block">Tags Used / Spoiled:</span>
                            <strong id="viewProgTags" class="text-dark">--</strong>
                        </div>
                    </div>
                </div>

                <!-- Animal Vouchers Table -->
                <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                    <i class="bi bi-tag-fill text-success"></i> Registered Animals & Cattle Vouchers
                </h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle border mb-0" id="tableProgramVouchers">
                        <thead class="table-light small text-secondary">
                            <tr>
                                <th style="width: 5%;" class="text-center">#</th>
                                <th style="width: 25%;">Ear Tag Number</th>
                                <th style="width: 25%;">Cattle Voucher No.</th>
                                <th style="width: 20%;">Breed</th>
                                <th style="width: 12%;">Sex</th>
                                <th style="width: 13%;">Age</th>
                            </tr>
                        </thead>
                        <tbody id="viewVouchersBody" class="small">
                            <!-- Injected dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer bg-light border-0 py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
