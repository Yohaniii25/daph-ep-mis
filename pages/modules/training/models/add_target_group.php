<div class="modal fade" id="addTargetGroupModal" tabindex="-1" aria-labelledby="addTargetGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="#" method="POST">
                <input type="hidden" name="action" value="save_target_group">
                
                <div class="modal-header text-white py-3" style="background-color: #370709;">
                    <h5 style="color: #fff;" class="modal-title" id="addTargetGroupModalLabel">
                        <i class="bi bi-people me-2"></i>Add Target Group Profile
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-close="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-secondary">Unique Group Tracking Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control font-monospace" name="group_code" placeholder="e.g., TG-041" style="text-transform: uppercase;" required>
                            <div class="form-text text-muted" style="font-size: 11px;">Must be a unique identification index for range groupings.</div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-secondary">Target Group Profile Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="group_name" placeholder="e.g., Ampara Dairy Operators" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-secondary">Description / Scope Context</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Describe the operational background context of this range farming sector..."></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light py-3">
                    <button type="button" class="btn btn-secondary px-4" data-bs-close="modal">Discard</button>
                    <button type="button" class="btn px-4 fw-bold shadow-sm" style="background-color: #370709; color: white;" data-bs-close="modal">Register Group (Demo)</button>
                </div>
            </form>
        </div>
    </div>
</div>