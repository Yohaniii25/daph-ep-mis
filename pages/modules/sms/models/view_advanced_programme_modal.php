<div class="modal fade" id="viewAdvancedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-info-circle me-2"></i>Programme Summary</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-4">
                    <div class="col-md-8">
                        <label class="text-muted small text-uppercase">Programme Type</label>
                        <h4 id="view_type_name" class="fw-bold text-dark"></h4>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <label class="text-muted small text-uppercase">Year</label>
                        <h4 id="view_year" class="text-secondary"></h4>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="text-muted small text-uppercase">Implementation Site</label>
                    <p id="view_place" class="fs-5 border-start border-4 border-info ps-3 py-1 bg-light"></p>
                </div>

                <div class="mb-4">
                    <label class="text-muted small text-uppercase">Activity Description</label>
                    <div id="view_description" class="p-3 border rounded bg-white shadow-sm" style="min-height: 120px; white-space: pre-wrap;"></div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 border rounded text-center bg-light">
                            <label class="text-muted small d-block mb-1">MID-TERM (6M)</label>
                            <span id="view_mid_status" class="badge rounded-pill px-4"></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 border rounded text-center bg-light">
                            <label class="text-muted small d-block mb-1">ANNUAL (1Y)</label>
                            <span id="view_final_status" class="badge rounded-pill px-4"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>