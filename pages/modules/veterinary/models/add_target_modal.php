<?php
if (!isset($selected_year)) {
    $selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
}
if (!isset($range_id)) {
    $range_id = $_SESSION['range_id'] ?? 0;
}
if (!isset($species_options)) {
    $species_options = [];
}
?>
<div class="modal fade" id="addTargetModal" tabindex="-1" aria-labelledby="addTargetLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-light py-2">
                <h6 class="modal-title" id="addTargetLabel"><i class="bi bi-gear-fill me-2"></i> Configure Annual Targets & Resource Allocations</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/save_vaccination_target.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="year" value="<?= htmlspecialchars($selected_year) ?>">
                    <input type="hidden" name="range_id" value="<?= htmlspecialchars($range_id) ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Animal Species</label>
                            <input type="text" name="animal_type" class="form-control form-control-sm border-secondary" required id="target_animal_type" list="targetSpeciesList" placeholder="Type or select species">
                            <datalist id="targetSpeciesList">
                                <?php
                                $vax_sp_list = isset($species_options) ? $species_options : (isset($species_list) ? $species_list : []);
                                foreach ($vax_sp_list as $sp_opt):
                                ?>
                                    <option value="<?= htmlspecialchars($sp_opt) ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Live Population Count</label>
                            <input type="number" name="quantity" class="form-control form-control-sm border-secondary" min="0" required placeholder="e.g. 5000">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">FMD Vaccination Target</label>
                            <input type="number" name="target_fmd" class="form-control form-control-sm border-secondary" min="0" required value="0">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">BQ Vaccination Target</label>
                            <input type="number" name="target_bq" class="form-control form-control-sm border-secondary" min="0" required value="0">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">HS Vaccination Target</label>
                            <input type="number" name="target_hs" class="form-control form-control-sm border-secondary" min="0" required value="0">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Available LDO Count</label>
                            <input type="number" name="available_ldo_count" class="form-control form-control-sm border-secondary" min="0" required value="0">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Allocated Target for LDO</label>
                            <input type="number" name="allocated_ldo_target" class="form-control form-control-sm border-secondary" min="0" required value="0">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-danger">Assign Casual Vaccinator</label>
                            <select name="assigned_vaccinator_id" class="form-select form-select-sm border-danger" required>
                                <option value="" selected disabled>-- Choose Registered Vaccinator --</option>
                                <?php
                                $vac_query = $mysqli->query("SELECT id, full_name, nic_no FROM casual_vaccinator_deployments ORDER BY full_name ASC");
                                if ($vac_query) {
                                    while ($vac = $vac_query->fetch_assoc()) {
                                        echo '<option value="' . $vac['id'] . '">' . htmlspecialchars($vac['full_name']) . ' (NIC: ' . $vac['nic_no'] . ')</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Allocated Staff Man-Days</label>
                            <input type="number" name="allocated_man_days" class="form-control form-control-sm border-secondary" min="0" required value="0">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Nylon Syringes (10CC)</label>
                            <input type="number" name="syringes_10cc_req" class="form-control form-control-sm border-secondary" min="0" required value="0">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Needle 14G</label>
                            <input type="number" name="needles_14g_dozen_req" class="form-control form-control-sm border-secondary" min="0" required value="0">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Fuel Allocation (L)</label>
                            <input type="number" step="0.01" name="fuel_liters_per_month" class="form-control form-control-sm border-secondary" min="0" required value="0.00">
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 border-top-0 bg-light">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark btn-sm px-4 shadow-sm">Save Metric Limits</button>
                </div>
            </form>
        </div>
    </div>
</div>