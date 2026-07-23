<!-- pages/modules/farm/models/add_daily_egg_collection.php -->
<div class="modal fade" id="eggModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content border-0 shadow" action="processors/save_daily_egg_collection.php" method="POST">
            <input type="hidden" name="action" value="create">

            <div class="modal-header text-light" style="background-color: #370709 !important;">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-egg-fill me-2"></i>Add Daily Egg Collection
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">
                    <!-- Batch Select -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Select Batch</label>
                        <select name="batch_id" class="form-select" required>
                            <option value="">Choose a batch...</option>
                            <?php
                            // Restricted to batches created by their logged-in user context
                            $avail_batch_stmt = $mysqli->prepare("SELECT id, batch_number AS batch_name FROM vaccine_batches WHERE user_id = ? ORDER BY batch_number");
                            $avail_batch_stmt->bind_param("i", $user_id);
                            $avail_batch_stmt->execute();
                            $avail_batch_res = $avail_batch_stmt->get_result();
                            while ($ab = $avail_batch_res->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($ab['id']) . "'>" . htmlspecialchars($ab['batch_name']) . "</option>";
                            }
                            $avail_batch_stmt->close();
                            ?>
                        </select>
                        <small class="text-muted">You only see batches created by yourself.</small>
                    </div>

                    <!-- Cage Select -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Select Cage Name</label>
                        <select name="cage_id" class="form-select" required>
                            <option value="">Choose a cage...</option>
                            <?php
                            $avail_cages = $mysqli->query("SELECT id, cage_name FROM cages ORDER BY cage_name");
                            while ($ac = $avail_cages->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($ac['id']) . "'>" . htmlspecialchars($ac['cage_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Date -->
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Collection Date</label>
                        <input type="date" name="collection_date" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                    </div>

                    <!-- Bird Counts -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 p-3">
                            <h6 class="fw-bold mb-3"><i class="bi bi-chevron-right me-1 text-primary"></i>Live Birds (Pullets)</h6>
                            <input type="number" name="pullets" class="form-control" placeholder="No. of Pullets" min="0" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card bg-light border-0 p-3">
                            <h6 class="fw-bold mb-3"><i class="bi bi-chevron-right me-1 text-primary"></i>Live Birds (Cockerels)</h6>
                            <input type="number" name="cockerels" class="form-control" placeholder="No. of Cockerels" min="0" required>
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
                            <input type="number" id="add_hatchable_eggs" name="hatchable_eggs" class="form-control egg-calc" placeholder="0" min="0" value="0" required>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text small">Kg</span>
                            <input type="number" id="add_hatchable_eggs_kg" name="hatchable_eggs_kg" class="form-control egg-kg-calc" placeholder="0.00" step="0.01" min="0" value="0.00" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Table Eggs (NO & Kg)</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text small">NO</span>
                            <input type="number" id="add_table_eggs" name="table_eggs" class="form-control egg-calc" placeholder="0" min="0" value="0" required>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text small">Kg</span>
                            <input type="number" id="add_table_eggs_kg" name="table_eggs_kg" class="form-control egg-kg-calc" placeholder="0.00" step="0.01" min="0" value="0.00" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Cracked Eggs (NO & Kg)</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text small">NO</span>
                            <input type="number" id="add_cracked_eggs" name="cracked_eggs" class="form-control egg-calc" placeholder="0" min="0" value="0" required>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text small">Kg</span>
                            <input type="number" id="add_cracked_eggs_kg" name="cracked_eggs_kg" class="form-control egg-kg-calc" placeholder="0.00" step="0.01" min="0" value="0.00" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-success">Total Production (NO & Kg)</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text small">NO</span>
                            <input type="number" id="add_total_eggs" name="total_eggs" class="form-control bg-light fw-bold text-success" placeholder="0" min="0" value="0" readonly required>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text small">Kg</span>
                            <input type="number" id="add_total_eggs_kg" name="total_eggs_kg" class="form-control bg-light fw-bold text-success" placeholder="0.00" step="0.01" min="0" value="0.00" readonly required>
                        </div>
                    </div>

                    <!-- Hatchery Operations Section -->
                    <div class="col-12">
                        <hr class="my-2">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-building me-1 text-warning"></i>Hatchery Operations</h6>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Loading Date</label>
                        <input type="date" id="add_loading_date" name="loading_date" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Hatchery Name</label>
                        <input type="text" id="add_hatchery_name" name="hatchery_name" class="form-control" placeholder="Hatchery Name">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Eggs Loaded</label>
                        <input type="number" id="add_eggs_loaded" name="eggs_loaded" class="form-control hatch-calc" placeholder="No. of eggs loaded" min="0" value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Hatching Date</label>
                        <input type="date" id="add_hatching_date" name="hatching_date" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Hatched Eggs</label>
                        <input type="number" id="add_hatched_eggs" name="hatched_eggs" class="form-control hatch-calc" placeholder="No. of hatched eggs" min="0" value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-primary">Hatchability %</label>
                        <div class="input-group">
                            <input type="number" id="add_hatchability_percentage" name="hatchability_percentage" class="form-control bg-light fw-bold text-primary" placeholder="0.00" step="0.01" min="0" max="100" value="0.00" readonly>
                            <span class="input-group-text fw-bold">%</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold px-4" style="background-color: #370709 !important; border-color: #370709 !important;">Save Collection</button>
            </div>
        </form>
    </div>
</div>