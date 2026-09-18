<div class="modal fade" id="addPopulationModal" tabindex="-1" aria-labelledby="addPopulationLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-light py-2">
                <h6 class="modal-title" id="addPopulationLabel"><i class="bi bi-plus-circle-fill me-2"></i> Add / Update Species Population</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/save_animal_population.php" method="POST">
                <div class="modal-body p-3">
                    <input type="hidden" name="year" value="<?= htmlspecialchars($selected_year) ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Animal Species</label>
                        <select name="animal_type" class="form-select form-select-sm border-secondary">
                            <option value="" selected disabled>-- Select Species --</option>
                            <?php foreach ($species_options as $sp_opt): ?>
                                <option value="<?= htmlspecialchars($sp_opt) ?>"><?= htmlspecialchars($sp_opt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Live Population Count</label>
                        <input type="number" name="quantity" class="form-control form-control-sm border-secondary" min="0" placeholder="e.g. 4500">
                    </div>
                </div>
                <div class="modal-footer py-2 border-top-0">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark btn-sm px-4 shadow-sm">Save Population</button>
                </div>
            </form>
        </div>
    </div>
</div>