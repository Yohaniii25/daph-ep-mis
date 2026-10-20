<div class="modal fade" id="addTargetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-black">
                <h5 class="modal-title">Set Annual Designation Targets</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/save_target_template.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Designation (Role)</label>
                        <select name="designation" class="form-select" required>
                            <option value="Veterinary Surgeon">Veterinary Surgeon</option>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-4">
                            <label class="small fw-bold">AI Target</label>
                            <input type="number" name="target_ai" class="form-control" required min="0">
                        </div>
                        <div class="col-4">
                            <label class="small fw-bold">PD Target</label>
                            <input type="number" name="target_pd" class="form-control" required min="0">
                        </div>
                        <div class="col-4">
                            <label class="small fw-bold">Calving Target</label>
                            <input type="number" name="target_calving" class="form-control" required min="0">
                        </div>
                    </div>
                    <input type="hidden" name="year" value="<?= date('Y') ?>">
                </div>
                <div class="modal-footer bg-light">
                    <button type="submit" class="btn btn-primary w-100">Save Category Targets</button>
                </div>
            </form>
        </div>
    </div>
</div>