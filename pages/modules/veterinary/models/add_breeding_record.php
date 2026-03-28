<div class="modal fade" id="addBreedingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background: #198754;">
                <h5 class="modal-title">Record Monthly Breeding Activity</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/save_breeding_activity.php" method="POST">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Officer</label>
                            <select name="officer_id" class="form-select shadow-sm" required>
                                <option value="">-- Select Officer --</option>
                                <?php foreach ($officer_suggestions as $off): ?>
                                    <option value="<?= $off['id'] ?>"><?= htmlspecialchars($off['officer_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Reporting Month</label>
                            <select name="month_number" class="form-select shadow-sm" required>
                                <?php
                                for ($m = 1; $m <= 12; $m++) {
                                    $monthName = date('F', mktime(0, 0, 0, $m, 1));
                                    $selected = ($m == date('n')) ? 'selected' : '';
                                    echo "<option value='$m' $selected>$monthName</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Year</label>
                            <input type="text" class="form-control bg-light" value="<?= date('Y') ?>" readonly name="year">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label text-primary fw-bold">AI (Monthly Total)</label>
                            <input type="number" name="ai_count" class="form-control" value="0" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-info fw-bold">PD (Monthly Total)</label>
                            <input type="number" name="pd_count" class="form-control" value="0" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-success fw-bold">Calvings (Monthly Total)</label>
                            <input type="number" name="calving_count" class="form-control" value="0" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="submit" class="btn btn-success text-white px-5 fw-bold">Save Monthly Data</button>
                </div>
            </form>
        </div>
    </div>
</div>