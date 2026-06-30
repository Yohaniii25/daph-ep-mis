<div class="modal fade" id="addRepairModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light text-dark">
                <h5 class="modal-title"><i class="bi bi-tools me-2"></i>Log Vehicle Repair Operation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addRepairForm" method="POST" action="processors/save_vehicle_repair.php">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Target Vehicle (Reference Plate &amp; Chassis)</label>
                            <select name="vehicle_id" class="form-select" required>
                                <option value="" disabled selected>-- Select target vehicle from fleet reference --</option>
                                <?php foreach($vehicles_cache as $v): ?>
                                    <option value="<?= $v['id'] ?>">Plate: <?= htmlspecialchars($v['vehicle_number']) ?> (Chassis: <?= htmlspecialchars($v['chassis_number']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Repair Date</label>
                            <input type="date" name="repair_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Repair Operation Done (Brief Header)</label>
                            <input type="text" name="repair_done" class="form-control" placeholder="e.g. Full Clutch Replacement" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Place of Repair (Workshop / Station)</label>
                            <input type="text" name="place_of_repair" class="form-control" placeholder="e.g. Saman Motors, Local Junction" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Amount Paid Total Cost (LKR)</label>
                            <input type="number" step="0.01" name="amount" class="form-control text-end" placeholder="0.00" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Detailed Description of Repair Action</label>
                            <textarea name="repair_description" class="form-control" rows="3" placeholder="Log broken component analysis details or replacement serial records..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-dark">Log Repair Record</button>
                </div>
            </form>
        </div>
    </div>
</div>