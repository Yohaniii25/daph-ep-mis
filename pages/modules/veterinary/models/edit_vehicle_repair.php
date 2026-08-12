<div class="modal fade" id="editRepairModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Maintenance & Repair Log</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editRepairForm" action="processors/update_vehicle_repair.php" method="POST">
                <input type="hidden" name="id" id="edit_repair_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Select Registered Vehicle</label>
                            <select name="vehicle_id" id="edit_repair_vehicle_id" class="form-select" required>
                                <option value="" disabled selected>-- Select Vehicle --</option>
                                <?php foreach ($vehicles_cache as $veh): ?>
                                    <option value="<?= $veh['id'] ?>"><?= htmlspecialchars($veh['vehicle_number']) ?> (<?= htmlspecialchars($veh['vehicle_type']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Repair Date</label>
                            <input type="date" name="repair_date" id="edit_repair_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Repair Work Executed</label>
                            <input type="text" name="repair_done" id="edit_repair_done" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Place / Garage of Repair</label>
                            <input type="text" name="place_of_repair" id="edit_place_of_repair" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Total Cost Amount (LKR)</label>
                            <input type="number" step="0.01" name="amount" id="edit_repair_amount" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Detailed Repair Description</label>
                            <textarea name="repair_description" id="edit_repair_description" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-dark">Update Repair Log</button>
                </div>
            </form>
        </div>
    </div>
</div>
