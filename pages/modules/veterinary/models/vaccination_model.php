<div class="modal fade" id="recordVaccinationModal" tabindex="-1" aria-labelledby="recordVaccinationLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white py-2">
                <h6 class="modal-title" id="recordVaccinationLabel"><i class="bi bi-shield-plus me-2"></i> Record Vaccination Log</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/save_vaccination_record.php" method="POST">
                <div class="modal-body p-3">
                    <input type="hidden" name="range_id" value="<?= htmlspecialchars($range_id) ?>">

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date of Vaccination</label>
                            <input type="date" name="date" class="form-control form-control-sm border-secondary" value="<?= date('Y-m-d') ?>">
                        </div>

                        <hr class="my-1">

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Animal Type / Species</label>
                            <select name="animal_type" class="form-select form-select-sm border-secondary">
                                <option value="" selected disabled>-- Select Species --</option>
                                <?php
                                // Prefer live population species for selection
                                if (!empty($animal_pop_data) && is_array($animal_pop_data)) {
                                    foreach ($animal_pop_data as $atype => $qty) {
                                        $safe = htmlspecialchars($atype);
                                        echo '<option value="' . $safe . '">' . $safe . ' (' . number_format($qty) . ')</option>';
                                    }
                                } elseif (!empty($species_options)) {
                                    foreach ($species_options as $sp) {
                                        $safe = htmlspecialchars($sp);
                                        echo '<option value="' . $safe . '">' . $safe . '</option>';
                                    }
                                } else {
                                    // Fallback hardcoded list
                                    $defaults = ['Cattle','Buffalo','Goat','Sheep','Swine','Poultry','Ornamental Birds','Other'];
                                    foreach ($defaults as $d) {
                                        $safe = htmlspecialchars($d);
                                        echo '<option value="' . $safe . '">' . $safe . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Vaccine Given</label>
                            <select name="vaccine_name" class="form-select form-select-sm border-secondary">
                                <option value="" selected disabled>-- Select Vaccine --</option>
                                <option value="FMD">FMD (Foot and Mouth)</option>
                                <option value="BQ">BQ (Black Quarter)</option>
                                <option value="HS">HS (Hemorrhagic Septicemia)</option>
                                <option value="Fowl pox">Fowl pox</option>
                                <option value="Gumboro">Gumboro</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Disease Name</label>
                            <input type="text" name="disease_name" class="form-control form-control-sm border-secondary" placeholder="e.g. Mouth disease">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Vaccinated Count</label>
                            <input type="number" name="occurrence_count" class="form-control form-control-sm border-secondary" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Number of Doses</label>
                            <input type="number" name="doses" class="form-control form-control-sm border-secondary" min="0">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Treatment & Remarks</label>
                            <textarea name="treatment_details" class="form-control form-control-sm border-secondary" rows="2" placeholder="Batch number, vaccination notes, dosage details..."></textarea>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label class="form-label small fw-bold">Vaccinator (Select from Deployed Casual Staff)</label>
                            <?php if (!empty($vax_targets['id'])): ?>
                                <select name="vaccinator_id" class="form-select form-select-sm border-secondary">
                                    <option value="" selected>-- Select Vaccinator --</option>
                                    <?php
                                    $sv = $mysqli->prepare("SELECT id, full_name, nic_no FROM casual_vaccinator_deployments WHERE vaccination_target_id = ? ORDER BY id ASC");
                                    if ($sv) {
                                        $sv->bind_param("i", $vax_targets['id']);
                                        $sv->execute();
                                        $rs = $sv->get_result();
                                        while ($r = $rs->fetch_assoc()) {
                                            $name = htmlspecialchars($r['full_name']);
                                            $nic = htmlspecialchars($r['nic_no']);
                                            echo '<option value="' . intval($r['id']) . '">' . $name . ' (NIC: ' . $nic . ')</option>';
                                        }
                                        $sv->close();
                                    }
                                    ?>
                                </select>
                                <small class="text-muted d-block">If vaccinator is not listed, enter name below.</small>
                                <input type="text" name="vaccinator_manual" class="form-control form-control-sm border-secondary mt-1" placeholder="Manual vaccinator name (optional)">
                            <?php else: ?>
                                <input type="text" name="vaccinator_manual" class="form-control form-control-sm border-secondary" placeholder="Vaccinator name (no deployed staff yet)">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 border-top-0">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm px-4 shadow-sm">Save Vaccination</button>
                </div>
            </form>
        </div>
    </div>
</div>