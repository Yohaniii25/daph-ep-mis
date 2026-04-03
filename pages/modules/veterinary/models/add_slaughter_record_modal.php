<div class="modal fade" id="addSlaughterModal" tabindex="-1" aria-labelledby="addSlaughterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="addSlaughterModalLabel">
                    <i class="bi bi-clipboard-plus me-2"></i>Record Slaughter Statistics
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/save_slaughter_stats.php" method="POST" id="slaughterForm">
                <div class="modal-body p-4">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Report Month</label>
                            <select name="report_month" class="form-select" required>
                                <?php for($m=1; $m<=12; $m++): ?>
                                    <option value="<?= $m ?>" <?= $m == date('n') ? 'selected' : '' ?>>
                                        <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Report Year</label>
                            <input type="number" name="report_year" class="form-control" value="<?= date('Y') ?>" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Livestock Species</label>
                        <select name="species" class="form-select" required>
                            <option value="" selected disabled>Select Species</option>
                            <option value="Cattle">Cattle</option>
                            <option value="Goat">Goat</option>
                            <option value="Poultry">Poultry</option>
                            <option value="Pig">Pig</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Slaughter Location</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="location_type" id="loc1" value="Slaughter House" checked>
                                <label class="form-check-label" for="loc1">Slaughter House</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="location_type" id="loc2" value="In-Farm">
                                <label class="form-check-label" for="loc2">In-Farm</label>
                            </div>
                        </div>
                    </div>

                    <hr class="text-muted">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Animal Count (Heads)</label>
                            <input type="number" name="animal_count" class="form-control" placeholder="0" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Total Weight (kg)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="total_weight_kg" class="form-control" placeholder="0.00" required>
                                <span class="input-group-text small">kg</span>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm">
                        <i class="bi bi-save me-2"></i>Save Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Basic validation to ensure weight isn't suspiciously low for the count
document.getElementById('slaughterForm').onsubmit = function() {
    const count = parseFloat(this.animal_count.value);
    const weight = parseFloat(this.total_weight_kg.value);
    
    if (weight / count < 1) {
        return confirm("The average weight per animal seems very low. Are you sure you want to save this?");
    }
    return true;
};
</script>