<?php
if (!isset($selected_year)) {
    $selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
}
if (!isset($range_id)) {
    $range_id = $_SESSION['range_id'] ?? 0;
}
if (!isset($animal_pop_data)) {
    $animal_pop_data = [];
}
?>
<div class="modal fade" id="addTargetModal" tabindex="-1" aria-labelledby="addTargetLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header py-2 text-light" style="background: linear-gradient(135deg, #370709 0%, #820100 100%);">
                <h6 class="modal-title fw-bold" id="addTargetLabel">
                    <i class="bi bi-gear-fill me-2"></i> Configure Annual Targets & Resource Allocations
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/save_vaccination_target.php" method="POST" id="formVaxTarget">
                <div class="modal-body p-4">
                    <input type="hidden" name="year" value="<?= htmlspecialchars($selected_year) ?>">
                    <input type="hidden" name="range_id" value="<?= htmlspecialchars($range_id) ?>">

                    <div class="row g-3">
                        <!-- Species Selection with Optgroups matching Log Session -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Animal Species <span class="text-danger">*</span></label>
                            <select name="animal_type" class="form-select form-select-sm border-secondary" required id="target_animal_type">
                                <option value="" disabled selected>-- Select Species --</option>
                                <optgroup label="Livestock Species" id="targetOptgroupLivestock">
                                    <option value="Cow">Cow / Cattle</option>
                                    <option value="Buffalo">Buffalo</option>
                                    <option value="Goat">Goat</option>
                                    <option value="Sheep">Sheep</option>
                                    <option value="Pig">Pig / Swine</option>
                                </optgroup>
                                <optgroup label="Poultry Species" id="targetOptgroupPoultry">
                                    <option value="Chicken">Poultry Birds</option>
                                </optgroup>
                            </select>
                        </div>

                        <!-- Synchronized Population Baseline -->
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold text-dark mb-0">Live Population Count</label>
                                <span class="badge bg-success text-white py-1 px-2" style="font-size: 0.7rem;">
                                    <i class="bi bi-link-45deg me-1"></i>Range Statistics Baseline
                                </span>
                            </div>
                            <input type="number" id="target_species_pop_display" name="quantity" class="form-control form-control-sm border-secondary bg-light fw-bold" readonly placeholder="0">
                            <small class="text-muted">Synchronized automatically with Range Statistics Census.</small>
                        </div>

                        <!-- Livestock & Poultry Annual Targets -->
                        <div class="col-12" id="livestockTargetsGroup">
                            <div class="p-3 rounded mb-1" style="background-color: #fdf8f6; border: 1px solid #fed7aa;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="small fw-bold text-dark">
                                        <i class="bi bi-shield-fill text-danger me-1"></i> Livestock & Poultry Vaccination Targets (Annual)
                                    </div>
                                    <span class="badge bg-light text-muted border small">Annual Disease Plan</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-3" id="col_target_fmd">
                                        <label class="form-label small fw-semibold text-dark">FMD Target</label>
                                        <input type="number" name="target_fmd" id="target_fmd_input" class="form-control form-control-sm border-secondary" min="0" value="0">
                                    </div>
                                    <div class="col-md-3" id="col_target_bq">
                                        <label class="form-label small fw-semibold text-dark">BQ Target</label>
                                        <input type="number" name="target_bq" id="target_bq_input" class="form-control form-control-sm border-secondary" min="0" value="0">
                                    </div>
                                    <div class="col-md-3" id="col_target_hs">
                                        <label class="form-label small fw-semibold text-dark">HS Target</label>
                                        <input type="number" name="target_hs" id="target_hs_input" class="form-control form-control-sm border-secondary" min="0" value="0">
                                    </div>
                                    <div class="col-md-3" id="col_target_poultry">
                                        <label class="form-label small fw-semibold text-dark">
                                            <i class="bi bi-egg-fill text-warning me-1"></i>Poultry Target Doses
                                        </label>
                                        <input type="number" name="target_poultry_doses" id="target_poultry_input" class="form-control form-control-sm border-secondary" min="0" value="0">
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-2">Annual vaccine dose targets planned for the selected species in this range.</small>
                            </div>
                        </div>

                        <!-- Staffing & Personnel Allocations -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Available LDO Count</label>
                            <input type="number" name="available_ldo_count" id="target_ldo_count" class="form-control form-control-sm border-secondary" min="0" value="0">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Allocated Target for LDO</label>
                            <input type="number" name="allocated_ldo_target" id="target_ldo_target" class="form-control form-control-sm border-secondary" min="0" value="0">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-danger">Assign Casual Vaccinator</label>
                            <select name="assigned_vaccinator_id" id="target_assigned_vaccinator" class="form-select form-select-sm border-danger">
                                <option value="" selected>-- Choose Registered Vaccinator (Optional) --</option>
                                <?php
                                $vac_query = $mysqli->query("SELECT id, full_name, nic_no FROM casual_vaccinator_deployments WHERE range_id = " . intval($range_id) . " AND year = " . intval($selected_year) . " ORDER BY full_name ASC");
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
                            <input type="number" name="allocated_man_days" id="target_man_days" class="form-control form-control-sm border-secondary" min="0" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 border-top-0 bg-light">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm px-4 shadow-sm text-light fw-bold" style="background-color: #820100;">
                        <i class="bi bi-check-circle me-1"></i> Save Vaccination Targets
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Live population lookup data from Range Statistics
const rangeAnimalPopulationMap = <?= json_encode($animal_pop_data) ?>;

document.addEventListener('DOMContentLoaded', function() {
    const spSelect = document.getElementById('target_animal_type');
    const popDisplay = document.getElementById('target_species_pop_display');
    const fmdInput = document.getElementById('target_fmd_input');
    const bqInput = document.getElementById('target_bq_input');
    const hsInput = document.getElementById('target_hs_input');
    const poultryInput = document.getElementById('target_poultry_input');

    if (spSelect) {
        spSelect.addEventListener('change', function() {
            const sp = this.value;
            // Lookup population count
            let count = 0;
            if (rangeAnimalPopulationMap && rangeAnimalPopulationMap[sp] !== undefined) {
                count = rangeAnimalPopulationMap[sp];
            }
            if (popDisplay) {
                popDisplay.value = count;
            }

            // Visual styling emphasis depending on category
            const isPoultry = (sp === 'Chicken');
            if (poultryInput) {
                poultryInput.closest('.col-md-3').classList.toggle('opacity-50', !isPoultry);
            }
            if (fmdInput) fmdInput.closest('.col-md-3').classList.toggle('opacity-50', isPoultry);
            if (bqInput) bqInput.closest('.col-md-3').classList.toggle('opacity-50', isPoultry);
            if (hsInput) hsInput.closest('.col-md-3').classList.toggle('opacity-50', isPoultry);
        });
    }
});
</script>