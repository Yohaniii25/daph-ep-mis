<div class="modal fade" id="deployPersonnelModal" tabindex="-1" aria-labelledby="deployPersonnelLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title" id="deployPersonnelLabel"><i class="bi bi-person-plus-fill me-2"></i> Deploy Casual Staff</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deployStaffForm" action="processors/save_vaccinator_deployment.php" method="POST">
                <div class="modal-body p-3">
                    <input type="hidden" name="vaccination_target_id" value="<?= htmlspecialchars($vax_targets['id']) ?>">
                    <input type="hidden" name="year" value="<?= htmlspecialchars($selected_year) ?>">
                    <input type="hidden" name="id" id="deploy_staff_id" value="">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Name</label>
                        <input type="text" name="full_name" class="form-control form-control-sm border-secondary" placeholder="e.g. A.B. Perera">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">NIC Number</label>
                        <input type="text" name="nic_no" class="form-control form-control-sm border-secondary" placeholder="e.g. 199212345678">
                    </div>
                </div>
                <div class="modal-footer py-2 border-top-0">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">Save Staff</button>
                </div>
            </form>
        </div>
    </div>
</div>