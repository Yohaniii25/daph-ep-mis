<div class="modal fade" id="manageTypesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="processors/process_amendment.php" method="POST">
                <div class="modal-header border-0 bg-dark text-white">
                    <h5 class="modal-title fw-bold">Add Amended Programme</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label fw-bold text-primary">1. Search Original Programme</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" id="search_original" class="form-control border-start-0 ps-0" placeholder="Type programme name to fetch data...">
                        </div>
                        <input type="hidden" name="original_id" id="original_id">
                        <div id="search_help" class="form-text text-info small">Start typing to see suggestions from your Advanced Programmes.</div>
                    </div>

                    <hr>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Year</label>
                            <input type="text" name="programme_year" id="amend_year" class="form-control bg-light" readonly required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">New/Modified Location</label>
                            <input type="text" name="place" id="amend_place" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Modified Description</label>
                            <textarea name="activity_description" id="amend_description" class="form-control" rows="4"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-danger">Amendment Reason</label>
                            <input type="text" name="amendment_reason" class="form-control border-danger" placeholder="Why is this being modified?" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm">Save Amended Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>