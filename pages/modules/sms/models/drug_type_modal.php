<?php
// Strict mapping matching your MySQL SET column options definition
$allowed_animals = ['Cattle', 'Dairy Cows', 'Buffalo', 'Goats', 'Poultry', 'other'];
?>

<div class="modal fade" id="addDrugTypeModal" tabindex="-1" aria-labelledby="drugModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            
            <div class="modal-header bg-dark text-white py-3" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                <h5 class="modal-title fw-bold" id="drugModalTitle" style="font-size: 1.1rem; color: #ffffff;">
                    <i class="bi bi-patch-plus me-2 text-success"></i>Add New Drug Classification Type
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="drugTypeForm" action="processors/drug_type_crud.php" method="POST">
                <div class="modal-body p-4">
                    
                    <input type="hidden" name="action" id="modalAction" value="create">
                    <input type="hidden" name="id" id="typeId" value="">
                    
                    <div class="mb-4">
                        <label for="drugName" class="form-label fw-semibold text-secondary mb-2">Drug Name / Formulation <span class="text-danger">*</span></label>
                        <div class="input-group shadow-sm rounded">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-capsule"></i></span>
                            <input type="text" class="form-control ps-2 text-dark fw-medium" name="vaccine_name" id="drugName" required placeholder="e.g., Oxytetracycline 20%, Vitamin B-Complex, Albendazole Bolus">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold text-secondary mb-1">Target Animal Classification <span class="text-danger">*</span></label>
                        <div class="text-muted small mb-3">Select all livestock classifications applicable to this therapeutic drug record:</div>
                        
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <?php foreach ($allowed_animals as $animal): ?>
                                <button type="button" 
                                        class="btn btn-outline-secondary animal-toggle-btn py-2 px-3 d-flex align-items-center rounded-pill bg-white shadow-sm" 
                                        data-value="<?= htmlspecialchars($animal) ?>">
                                    <i class="bi bi-tag me-1"></i>
                                    <i class="bi bi-check-circle-fill text-primary check-icon me-1" style="display:none;"></i> 
                                    <?= htmlspecialchars($animal) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <div class="d-none">
                            <input type="text" id="targetAnimalHidden" name="target_animal_string" value="">
                            <select id="targetAnimalArray" name="target_animal[]" multiple></select>
                        </div>

                        <div class="card bg-light border-0 rounded-3">
                            <div class="card-body py-2 px-3 small d-flex align-items-center">
                                <span class="text-muted me-2 font-monospace text-uppercase small" style="font-size: 0.73rem;">Active Targets:</span>
                                <span id="animalSelectedPills" class="fw-semibold text-secondary">No animals selected.</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="description" class="form-label fw-semibold text-secondary mb-2">Description Notes / Clinical Directions</label>
                        <textarea class="form-control text-dark shadow-sm" name="description" id="description" rows="4" placeholder="Enter standard therapeutic dosage instructions, active chemical compounds, withdrawal tracking metrics..."></textarea>
                    </div>

                </div>

                <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-end gap-2" style="border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;">
                    <button type="button" data-bs-dismiss="modal" class="btn btn-secondary px-4 fw-medium shadow-sm">Cancel</button>
                    <button type="submit" id="submitBtn" class="btn btn-success px-4 fw-bold shadow-sm">Save Configuration</button>
                </div>
            </form>

        </div>
    </div>
</div>