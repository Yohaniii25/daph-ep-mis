<?php
if (!isset($range_id)) {
    $range_id = $_SESSION['range_id'] ?? 0;
}
if (!isset($selected_year)) {
    $selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
}

// Load centralized poultry vaccine list
$poultry_vaccines_cfg = file_exists(__DIR__ . '/../config/poultry_vaccines.php') 
    ? require __DIR__ . '/../config/poultry_vaccines.php' 
    : ['Newcastle Disease (ND / Ranikhet)', 'Infectious Bursal Disease (IBD / Gumboro)', 'Fowl Pox Vaccine', 'Marek\'s Disease Vaccine', 'Infectious Bronchitis (IB)'];

// Livestock species and vaccines
$livestock_species = ['Cow', 'Buffalo', 'Goat', 'Sheep', 'Pig'];
$livestock_vaccines = [
    'FMD (Foot & Mouth Disease)' => 'FMD',
    'HS (Hemorrhagic Septicemia)' => 'HS',
    'BQ (Black Quarter)' => 'BQ',
    'Other Livestock Vaccine' => 'Other'
];

// If $range_id is not set or 0, fallback to current request or default 1
if (empty($range_id) || $range_id <= 0) {
    $range_id = isset($_GET['range_id']) && is_numeric($_GET['range_id']) ? intval($_GET['range_id']) : (intval($_SESSION['range_id'] ?? 1));
}

// Fetch live animal population demographics from Animal Population table (Range Statistics baseline)
if (!isset($animal_pop_data) || !is_array($animal_pop_data)) {
    $animal_pop_data = [];
}

// Ensure database connection is available
if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    require_once __DIR__ . '/../../../../config/db_connect.php';
}

if ($range_id > 0 && isset($mysqli)) {
    $pop_stmt = $mysqli->prepare("SELECT animal_type, SUM(quantity) as quantity FROM animal_populations WHERE range_id = ? AND year = ? GROUP BY animal_type");
    if ($pop_stmt) {
        $pop_stmt->bind_param("ii", $range_id, $selected_year);
        $pop_stmt->execute();
        $p_res = $pop_stmt->get_result();
        while ($p_row = $p_res->fetch_assoc()) {
            $animal_pop_data[$p_row['animal_type']] = intval($p_row['quantity']);
        }
        $pop_stmt->close();
    }
}
?>

<!-- Modal: Log / Edit Vaccination Session -->
<div class="modal fade" id="modalVaccinationSession" tabindex="-1" aria-labelledby="modalVaccinationSessionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-2" style="background: linear-gradient(135deg, #370709 0%, #820100 100%);">
                <h6 class="modal-title fw-bold" id="modalVaccinationSessionLabel">
                    <i class="bi bi-shield-plus me-2"></i> <span id="vaxSessionModalTitle">Log Manual Vaccination Session</span>
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/vaccination_session_crud.php" method="POST" id="vaxSessionForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" id="vax_session_action" value="add">
                    <input type="hidden" name="id" id="vax_session_id" value="">
                    <input type="hidden" name="range_id" value="<?= htmlspecialchars($range_id) ?>">

                    <div class="row g-3">
                        <!-- Date of Session -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">
                                <i class="bi bi-calendar-check me-1 text-danger"></i> Session Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="session_date" id="vax_session_date" class="form-control form-control-sm border-secondary" value="<?= date('Y-m-d') ?>" required>
                            <small class="text-muted">Exact calendar date the vaccination activity occurred.</small>
                        </div>

                        <!-- Target Category Selector -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">
                                <i class="bi bi-tag-fill me-1 text-danger"></i> Tracking Category <span class="text-danger">*</span>
                            </label>
                            <div class="d-flex gap-3 pt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="category" id="vaxCatLivestock" value="Livestock" checked>
                                    <label class="form-check-label small fw-bold text-dark" for="vaxCatLivestock">
                                        <i class="bi bi-shield-fill text-primary me-1"></i> Livestock (Cattle/Buffalo/Sheep/Goat/Pig)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="category" id="vaxCatPoultry" value="Poultry">
                                    <label class="form-check-label small fw-bold text-dark" for="vaxCatPoultry">
                                        <i class="bi bi-egg-fill text-warning me-1"></i> Poultry
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Animal Species Selection -->
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold text-dark mb-0">
                                    Animal Species <span class="text-danger">*</span>
                                </label>
                                <span class="badge bg-light text-dark border small" id="speciesLivePopBadge" style="font-size: 0.72rem;">
                                    <i class="bi bi-bar-chart-line text-primary me-1"></i>Range Population: <strong id="speciesLivePopVal">--</strong>
                                </span>
                            </div>
                            <select name="animal_type" id="vax_session_animal_type" class="form-select form-select-sm border-secondary" required>
                                <option value="" disabled selected>-- Select Species --</option>
                                <optgroup label="Livestock Species" id="optgroupLivestock">
                                    <option value="Cow" data-pop="<?= intval($animal_pop_data['Cow'] ?? 0) ?>">
                                        Cow / Cattle (Pop: <?= number_format($animal_pop_data['Cow'] ?? 0) ?>)
                                    </option>
                                    <option value="Buffalo" data-pop="<?= intval($animal_pop_data['Buffalo'] ?? 0) ?>">
                                        Buffalo (Pop: <?= number_format($animal_pop_data['Buffalo'] ?? 0) ?>)
                                    </option>
                                    <option value="Goat" data-pop="<?= intval($animal_pop_data['Goat'] ?? 0) ?>">
                                        Goat (Pop: <?= number_format($animal_pop_data['Goat'] ?? 0) ?>)
                                    </option>
                                    <option value="Sheep" data-pop="<?= intval($animal_pop_data['Sheep'] ?? 0) ?>">
                                        Sheep (Pop: <?= number_format($animal_pop_data['Sheep'] ?? 0) ?>)
                                    </option>
                                    <option value="Pig" data-pop="<?= intval($animal_pop_data['Pig'] ?? 0) ?>">
                                        Pig / Swine (Pop: <?= number_format($animal_pop_data['Pig'] ?? 0) ?>)
                                    </option>
                                </optgroup>
                                <optgroup label="Poultry Species" id="optgroupPoultry">
                                    <option value="Chicken" data-pop="<?= intval($animal_pop_data['Chicken'] ?? 0) ?>">
                                        Poultry Birds (Pop: <?= number_format($animal_pop_data['Chicken'] ?? 0) ?>)
                                    </option>

                                </optgroup>
                            </select>
                        </div>

                        <!-- Vaccine Type Selection -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">
                                Vaccine Type Administered <span class="text-danger">*</span>
                            </label>
                            <select name="vaccine_name" id="vax_session_vaccine_name" class="form-select form-select-sm border-secondary" required>
                                <option value="" disabled selected>-- Select Vaccine --</option>
                                <optgroup label="Livestock Vaccines" id="vaxGroupLivestock">
                                    <option value="FMD">FMD (Foot and Mouth Disease)</option>
                                    <option value="HS">HS (Hemorrhagic Septicemia)</option>
                                    <option value="BQ">BQ (Black Quarter)</option>
                                    <option value="Other">Other Livestock Vaccine...</option>
                                </optgroup>
                                <optgroup label="Poultry Vaccines (Configurable)" id="vaxGroupPoultry">
                                    <?php foreach ($poultry_vaccines_cfg as $pv): ?>
                                        <option value="<?= htmlspecialchars($pv) ?>"><?= htmlspecialchars($pv) ?></option>
                                    <?php endforeach; ?>
                                    <option value="Other">Other Poultry Vaccine...</option>
                                </optgroup>
                            </select>
                            <input type="text" name="custom_vaccine_name" id="vax_session_custom_vaccine" class="form-control form-control-sm border-secondary mt-1 d-none" placeholder="Enter custom vaccine name">
                        </div>

                        <!-- Assigned Personnel / Vaccinator -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">
                                <i class="bi bi-person-badge-fill me-1 text-danger"></i> Assigned Personnel / Vaccinator <span class="text-danger">*</span>
                            </label>
                            <select name="vaccinator_id" id="vax_session_vaccinator_id" class="form-select form-select-sm border-secondary">
                                <option value="" selected>-- Select Registered Vaccinator or Manual Entry --</option>
                                <?php
                                if (!empty($deployed_staff_records)) {
                                    foreach ($deployed_staff_records as $st_rec) {
                                        echo '<option value="' . intval($st_rec['id']) . '" data-name="' . htmlspecialchars($st_rec['full_name']) . '">' . htmlspecialchars($st_rec['full_name']) . ' (NIC: ' . htmlspecialchars($st_rec['nic_no']) . ')</option>';
                                    }
                                }
                                ?>
                                <option value="0">Manual / Other Vaccinator...</option>
                            </select>
                            <input type="text" name="vaccinator_name" id="vax_session_vaccinator_manual" class="form-control form-control-sm border-secondary mt-1" placeholder="Type personnel / vaccinator name" required>
                        </div>

                        <!-- Exact Number of Animals Vaccinated -->
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-dark">
                                Animals Vaccinated <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="vaccinated_count" id="vax_session_count" class="form-control form-control-sm border-secondary fw-bold" min="1" required placeholder="e.g. 150">
                            <small class="text-muted">Exact animal head count.</small>
                        </div>

                        <!-- Doses Administered -->
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-dark">
                                Doses Administered
                            </label>
                            <input type="number" name="doses_administered" id="vax_session_doses" class="form-control form-control-sm border-secondary" min="0" placeholder="e.g. 150">
                            <small class="text-muted">Defaults to animal count.</small>
                        </div>

                        <!-- Batch No -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Vaccine Batch No. (Optional)</label>
                            <input type="text" name="batch_no" id="vax_session_batch_no" class="form-control form-control-sm border-secondary" placeholder="e.g. BATCH-2026-FMD-04">
                        </div>

                        <!-- Location / GN Division -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Session Location / Village / GN</label>
                            <input type="text" name="location_name" id="vax_session_location" class="form-control form-control-sm border-secondary" placeholder="e.g. Farm cluster / GN division">
                        </div>

                        <!-- Remarks -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-dark">Session Remarks / Operational Notes</label>
                            <textarea name="remarks" id="vax_session_remarks" class="form-control form-control-sm border-secondary" rows="2" placeholder="Cold chain observations, booster status, field notes..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer py-2 border-top-0 bg-light">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm px-4 shadow-sm text-light fw-bold" style="background-color: #820100;" id="btnSubmitVaxSession">
                        <i class="bi bi-check-circle me-1"></i> Save Vaccination Session
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const radioLivestock = document.getElementById('vaxCatLivestock');
    const radioPoultry = document.getElementById('vaxCatPoultry');
    const groupLivestockVax = document.getElementById('vaxGroupLivestock');
    const groupPoultryVax = document.getElementById('vaxGroupPoultry');
    const optgroupLivestock = document.getElementById('optgroupLivestock');
    const optgroupPoultry = document.getElementById('optgroupPoultry');
    const speciesSelect = document.getElementById('vax_session_animal_type');
    const vaxSelect = document.getElementById('vax_session_vaccine_name');
    const customVaxInput = document.getElementById('vax_session_custom_vaccine');
    const vaccinatorSelect = document.getElementById('vax_session_vaccinator_id');
    const vaccinatorManualInput = document.getElementById('vax_session_vaccinator_manual');
    const countInput = document.getElementById('vax_session_count');
    const dosesInput = document.getElementById('vax_session_doses');

    const livePopVal = document.getElementById('speciesLivePopVal');

    function updateLivePopBadge() {
        if (!speciesSelect || !livePopVal) return;
        const selectedOpt = speciesSelect.options[speciesSelect.selectedIndex];
        if (selectedOpt && selectedOpt.dataset && selectedOpt.dataset.pop !== undefined) {
            const count = parseInt(selectedOpt.dataset.pop, 10);
            livePopVal.textContent = isNaN(count) ? '0' : Number(count).toLocaleString() + ' animals';
        } else {
            livePopVal.textContent = '--';
        }
    }

    function syncCategoryState() {
        const isPoultry = radioPoultry.checked;
        if (isPoultry) {
            optgroupLivestock.disabled = true;
            optgroupPoultry.disabled = false;
            groupLivestockVax.disabled = true;
            groupPoultryVax.disabled = false;
            if (speciesSelect.value && !['Chicken','Broiler','Layer','Backyard Poultry','Duck'].includes(speciesSelect.value)) {
                speciesSelect.value = 'Chicken';
            }
        } else {
            optgroupLivestock.disabled = false;
            optgroupPoultry.disabled = true;
            groupLivestockVax.disabled = false;
            groupPoultryVax.disabled = true;
            if (speciesSelect.value && ['Chicken','Broiler','Layer','Backyard Poultry','Duck'].includes(speciesSelect.value)) {
                speciesSelect.value = 'Cow';
            }
        }
        updateLivePopBadge();
    }

    if (speciesSelect) {
        speciesSelect.addEventListener('change', updateLivePopBadge);
        updateLivePopBadge();
    }

    if (radioLivestock && radioPoultry) {
        radioLivestock.addEventListener('change', syncCategoryState);
        radioPoultry.addEventListener('change', syncCategoryState);
    }

    if (vaxSelect) {
        vaxSelect.addEventListener('change', function() {
            if (this.value === 'Other' || this.value.startsWith('Other')) {
                customVaxInput.classList.remove('d-none');
                customVaxInput.focus();
            } else {
                customVaxInput.classList.add('d-none');
                customVaxInput.value = '';
            }
        });
    }

    if (vaccinatorSelect) {
        vaccinatorSelect.addEventListener('change', function() {
            const selectedOpt = this.options[this.selectedIndex];
            if (selectedOpt && selectedOpt.dataset && selectedOpt.dataset.name) {
                vaccinatorManualInput.value = selectedOpt.dataset.name;
            } else if (this.value === '0') {
                vaccinatorManualInput.value = '';
                vaccinatorManualInput.focus();
            }
        });
    }

    if (countInput && dosesInput) {
        countInput.addEventListener('input', function() {
            if (!dosesInput.value || parseInt(dosesInput.value) === 0) {
                dosesInput.value = this.value;
            }
        });
    }
});
</script>
