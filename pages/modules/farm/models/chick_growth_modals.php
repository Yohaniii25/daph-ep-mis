<?php
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
<!-- pages/modules/farm/models/chick_growth_modals.php -->

<!-- Add Growth Modal -->
<div class="modal fade" id="addGrowthModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="processors/chick_growth_log_crud.php" method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header text-light" style="background-color: #370709;">
                    <h5 class="modal-header-title fw-bold m-0"><i class="bi bi-activity me-2"></i>Log Daily Growth & Mortality</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Record Date <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" id="add_growth_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Select Cage <span class="text-danger">*</span></label>
                            <select name="cage_id" id="add_growth_cage_id" class="form-select" required>
                                <option value="">-- Select Cage --</option>
                                <?php foreach ($cages as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['cage_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-primary">Opening Chicks Count <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="opening_chicks_count" id="add_growth_opening" class="form-control fw-bold border-primary" min="0" value="0" required>
                                <button type="button" class="btn btn-outline-primary" id="btn_auto_calc_opening" title="Auto-fetch balance from previous log/hatchery">
                                    <i class="bi bi-arrow-repeat"></i> Auto-Fetch
                                </button>
                            </div>
                            <small class="text-muted">Calculated as Previous Day Opening - Deaths (or Hatchery Register if 1st entry).</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-danger">No. of Deaths <span class="text-danger">*</span></label>
                            <input type="number" name="no_of_deaths" id="add_growth_deaths" class="form-control border-danger fw-bold" min="0" value="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Feed Type</label>
                            <input type="text" name="feed_type" class="form-control" placeholder="e.g. Starter Mesh, Grower Feed">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Feed To Be Given (kg)</label>
                            <input type="number" step="0.01" name="feed_amount_to_be_given" class="form-control" min="0" value="0.00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Feed Given (kg)</label>
                            <input type="number" step="0.01" name="feed_amount_given" class="form-control" min="0" value="0.00">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Vaccination / Treatment Details</label>
                            <textarea name="vaccination_treatment" class="form-control" rows="2" placeholder="e.g. ND Vaccine, Vitamins..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold" style="background-color: #370709; border-color: #370709;">Save Growth Log</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Growth Modal -->
<div class="modal fade" id="editGrowthModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="processors/chick_growth_log_crud.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_growth_id">
                <div class="modal-header text-white" style="background-color: #370709;">
                    <h5 class="modal-header-title fw-bold m-0"><i class="bi bi-pencil-square me-2"></i>Edit Growth Log Entry</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Record Date <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" id="edit_growth_record_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Select Cage <span class="text-danger">*</span></label>
                            <select name="cage_id" id="edit_growth_cage_id" class="form-select" required>
                                <option value="">-- Select Cage --</option>
                                <?php foreach ($cages as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['cage_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-primary">Opening Chicks Count <span class="text-danger">*</span></label>
                            <input type="number" name="opening_chicks_count" id="edit_growth_opening" class="form-control fw-bold border-primary" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-danger">No. of Deaths <span class="text-danger">*</span></label>
                            <input type="number" name="no_of_deaths" id="edit_growth_deaths" class="form-control border-danger fw-bold" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Feed Type</label>
                            <input type="text" name="feed_type" id="edit_growth_feed_type" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Feed To Be Given (kg)</label>
                            <input type="number" step="0.01" name="feed_amount_to_be_given" id="edit_growth_feed_to_be_given" class="form-control" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Feed Given (kg)</label>
                            <input type="number" step="0.01" name="feed_amount_given" id="edit_growth_feed_given" class="form-control" min="0">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Vaccination / Treatment Details</label>
                            <textarea name="vaccination_treatment" id="edit_growth_vaccination" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold" style="background-color: #370709; border-color: #370709;">Update Growth Log</button>
                </div>
            </form>
        </div>
    </div>
</div>
