<?php
// Defensive initialization of $cages if undefined
if (!isset($cages) || !is_array($cages)) {
    $cages = [];
    if (isset($mysqli)) {
        $cages_res = $mysqli->query("SELECT id, cage_name FROM cages ORDER BY cage_name");
        if ($cages_res) {
            while ($row = $cages_res->fetch_assoc()) {
                $cages[] = $row;
            }
        }
    }
}
?>
<!-- pages/modules/farm/models/daily_feed_modals.php -->

<!-- Add Daily Feed Distribution Modal -->
<div class="modal fade" id="addDailyFeedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="processors/daily_feed_distribution_crud.php" method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header text-light" style="background-color: var(--color-c1, #820100);">
                    <h5 class="modal-header-title fw-bold m-0"><i class="bi bi-basket me-2"></i>Log Daily Feed Distribution</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Distribution Date <span class="text-danger">*</span></label>
                            <input type="date" name="distribution_date" id="add_feed_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Select Feed Type <span class="text-danger">*</span></label>
                            <select name="feed_type" id="add_feed_type" class="form-select" required>
                                <option value="Layer">Layer</option>
                                <option value="Starter">Starter</option>
                                <option value="Grower">Grower</option>
                                <option value="Cattle Feed">Cattle Feed</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Select Cage</label>
                            <select name="cage_id" id="add_feed_cage_id" class="form-select">
                                <option value="">-- Select Cage (Optional) --</option>
                                <?php foreach ($cages as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['cage_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Batch No.</label>
                            <input type="text" name="batch_no" id="add_feed_batch_no" class="form-control" placeholder="e.g. BATCH-2026-001">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">No. of Chicks / Stock</label>
                            <input type="number" name="no_of_chicks" id="add_feed_no_of_chicks" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Amount Needed (kg)</label>
                            <input type="number" step="0.01" name="amount_needed_kg" id="add_feed_amount_needed" class="form-control" min="0" value="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-color-c11">Amount Distributed (kg) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount_distributed_kg" id="add_feed_amount_distributed" class="form-control fw-bold" style="border-color: var(--color-c11);" min="0" value="0.00" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Remarks / Notes</label>
                            <textarea name="remarks" id="add_feed_remarks" class="form-control" rows="2" placeholder="e.g. Feed quality, special ration..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold" style="background-color: var(--color-c1, #820100);">Save Daily Feed Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Daily Feed Distribution Modal -->
<div class="modal fade" id="editDailyFeedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="processors/daily_feed_distribution_crud.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_feed_id">
                <div class="modal-header text-light" style="background-color: var(--color-c10, #185dbd);">
                    <h5 class="modal-header-title fw-bold m-0"><i class="bi bi-pencil-square me-2"></i>Edit Daily Feed Entry</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Distribution Date <span class="text-danger">*</span></label>
                            <input type="date" name="distribution_date" id="edit_feed_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Select Feed Type <span class="text-danger">*</span></label>
                            <select name="feed_type" id="edit_feed_type" class="form-select" required>
                                <option value="Layer">Layer</option>
                                <option value="Starter">Starter</option>
                                <option value="Grower">Grower</option>
                                <option value="Cattle Feed">Cattle Feed</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Select Cage</label>
                            <select name="cage_id" id="edit_feed_cage_id" class="form-select">
                                <option value="">-- Select Cage (Optional) --</option>
                                <?php foreach ($cages as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['cage_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Batch No.</label>
                            <input type="text" name="batch_no" id="edit_feed_batch_no" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">No. of Chicks / Stock</label>
                            <input type="number" name="no_of_chicks" id="edit_feed_no_of_chicks" class="form-control" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Amount Needed (kg)</label>
                            <input type="number" step="0.01" name="amount_needed_kg" id="edit_feed_amount_needed" class="form-control" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-color-c11">Amount Distributed (kg) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount_distributed_kg" id="edit_feed_amount_distributed" class="form-control fw-bold" style="border-color: var(--color-c11);" min="0" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Remarks / Notes</label>
                            <textarea name="remarks" id="edit_feed_remarks" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold" style="background-color: var(--color-c10, #185dbd);">Update Feed Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>
