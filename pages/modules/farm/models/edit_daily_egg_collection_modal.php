<!-- pages/modules/farm/models/edit_daily_egg_collection_modal.php -->
<div class="modal fade" id="editEggModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content border-0 shadow" action="processors/save_daily_egg_collection.php" method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="edit_collection_id" name="id">

            <div class="modal-header bg-dark text-light">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-pencil-square me-2"></i>Edit Daily Egg Collection
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">
                    <!-- Batch Select -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Select Batch</label>
                        <select id="edit_batch_id" name="batch_id" class="form-select" required>
                            <option value="">Choose a batch...</option>
                            <?php
                            $edit_batch_stmt = $mysqli->prepare("SELECT id, batch_number AS batch_name FROM vaccine_batches WHERE user_id = ? ORDER BY batch_number");
                            $edit_batch_stmt->bind_param("i", $user_id);
                            $edit_batch_stmt->execute();
                            $edit_batch_res = $edit_batch_stmt->get_result();
                            while ($ab = $edit_batch_res->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($ab['id']) . "'>" . htmlspecialchars($ab['batch_name']) . "</option>";
                            }
                            $edit_batch_stmt->close();
                            ?>
                        </select>
                        <small class="text-muted">You only see batches created by yourself.</small>
                    </div>

                    <!-- Cage Select -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Select Cage Name</label>
                        <select id="edit_cage_id" name="cage_id" class="form-select" required>
                            <option value="">Choose a cage...</option>
                            <?php
                            $edit_cages = $mysqli->query("SELECT id, cage_name FROM cages ORDER BY cage_name");
                            while ($ac = $edit_cages->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($ac['id']) . "'>" . htmlspecialchars($ac['cage_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Date -->
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Collection Date</label>
                        <input type="date" id="edit_collection_date" name="collection_date" class="form-control" max="<?= date('Y-m-d') ?>" required>
                    </div>

                    <!-- Bird Counts -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 p-3">
                            <h6 class="fw-bold mb-3"><i class="bi bi-chevron-right me-1 text-primary"></i>Live Birds (Pullets)</h6>
                            <input type="number" id="edit_pullets" name="pullets" class="form-control" min="0" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card bg-light border-0 p-3">
                            <h6 class="fw-bold mb-3"><i class="bi bi-chevron-right me-1 text-primary"></i>Live Birds (Cockerels)</h6>
                            <input type="number" id="edit_cockerels" name="cockerels" class="form-control" min="0" required>
                        </div>
                    </div>

                    <!-- Egg Details (CPRS-21 (B)) -->
                    <div class="col-12">
                        <hr class="my-2">
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-egg-fried me-1"></i>Egg Details (CPRS-21 (B)) - Quantities & Weights</h6>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Hatch Eggs (NO & Kg)</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text small">NO</span>
                            <input type="number" id="edit_hatchable_eggs" name="hatchable_eggs" class="form-control edit-egg-calc" placeholder="0" min="0" required>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text small">Kg</span>
                            <input type="number" id="edit_hatchable_eggs_kg" name="hatchable_eggs_kg" class="form-control edit-egg-kg-calc" placeholder="0.00" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Table Eggs (NO & Kg)</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text small">NO</span>
                            <input type="number" id="edit_table_eggs" name="table_eggs" class="form-control edit-egg-calc" placeholder="0" min="0" required>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text small">Kg</span>
                            <input type="number" id="edit_table_eggs_kg" name="table_eggs_kg" class="form-control edit-egg-kg-calc" placeholder="0.00" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Cracked Eggs (NO & Kg)</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text small">NO</span>
                            <input type="number" id="edit_cracked_eggs" name="cracked_eggs" class="form-control edit-egg-calc" placeholder="0" min="0" required>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text small">Kg</span>
                            <input type="number" id="edit_cracked_eggs_kg" name="cracked_eggs_kg" class="form-control edit-egg-kg-calc" placeholder="0.00" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-success">Total Production (NO & Kg)</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text small">NO</span>
                            <input type="number" id="edit_egg_count" name="total_eggs" class="form-control bg-light fw-bold text-success" placeholder="0" min="0" readonly required>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text small">Kg</span>
                            <input type="number" id="edit_total_eggs_kg" name="total_eggs_kg" class="form-control bg-light fw-bold text-success" placeholder="0.00" step="0.01" min="0" readonly required>
                        </div>
                    </div>

                    <!-- Hatchery Operations Section -->
                    <div class="col-12">
                        <hr class="my-2">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-building me-1 text-warning"></i>Hatchery Operations</h6>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Loading Date</label>
                        <input type="date" id="edit_loading_date" name="loading_date" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Hatchery Name</label>
                        <input type="text" id="edit_hatchery_name" name="hatchery_name" class="form-control" placeholder="Hatchery Name">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Eggs Loaded</label>
                        <input type="number" id="edit_eggs_loaded" name="eggs_loaded" class="form-control edit-hatch-calc" placeholder="No. of eggs loaded" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Hatching Date</label>
                        <input type="date" id="edit_hatching_date" name="hatching_date" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Hatched Eggs</label>
                        <input type="number" id="edit_hatched_eggs" name="hatched_eggs" class="form-control edit-hatch-calc" placeholder="No. of hatched eggs" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-primary">Hatchability %</label>
                        <div class="input-group">
                            <input type="number" id="edit_hatchability_percentage" name="hatchability_percentage" class="form-control bg-light fw-bold text-primary" placeholder="0.00" step="0.01" min="0" max="100" readonly>
                            <span class="input-group-text fw-bold">%</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success fw-bold px-4">Update Collection</button>
            </div>
        </form>
    </div>
</div>