<div class="modal fade" id="addVaccineTypeModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form id="typeForm" class="modal-content border-0 shadow-lg" action="processors/vaccine_type_crud.php" method="POST">
            <input type="hidden" name="action" id="modalAction" value="create">
            <input type="hidden" name="id" id="typeId" value="">

            <div class="modal-header bg-light text-white">
                <h5 class="modal-title fw-bold" id="modalTitle"><i class="bi bi-patch-plus me-2"></i>Add New Vaccine Variant Type</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark">Vaccine Common Name / Nomenclature</label>
                    <input type="text" name="vaccine_name" id="vaccineName" class="form-control" placeholder="e.g. Foot and Mouth Disease (FMD)" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-dark">Target Animal Classification Group</label>
                    <p class="text-muted small mb-2">Click to select one or more animals</p>

                    <div id="animalToggleGrid" style="display:flex; flex-wrap:wrap; gap:8px;">
                        <?php
                        $animals = ['Cattle', 'Buffalo', 'Goat', 'Sheep', 'Swine', 'Poultry', 'Other'];
                        foreach ($animals as $a): ?>
                            <button type="button" class="animal-toggle-btn btn btn-outline-secondary btn-sm px-3"
                                data-value="<?= $a ?>">
                                <i class="bi bi-tag me-1"></i>
                                <i class="bi bi-check-lg me-1 d-none check-icon"></i>
                                <?= $a === 'Other' ? 'Other Livestock Species' : $a ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div id="animalSelectedPills" class="mt-2 text-muted small">No animals selected.</div>

                    <!-- Hidden field that actually submits the CSV string value -->
                    <input type="hidden" name="target_animal" id="targetAnimalHidden">
                    <!-- Hidden array field to guarantee POST contains the selected animals -->
                    <select name="target_animal[]" id="targetAnimalArray" multiple hidden></select>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-bold text-dark">Administrative Scope Notes / Description</label>
                    <textarea name="description" id="description" class="form-control" rows="3" placeholder="Optional notes regarding dosage sizes, recommended age intervals..."></textarea>
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" id="submitBtn" class="btn btn-success fw-bold px-4">Save Configuration</button>
            </div>
        </form>
    </div>
</div>