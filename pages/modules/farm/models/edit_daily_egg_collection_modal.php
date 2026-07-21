<!-- pages/modules/farm/models/edit_daily_egg_collection_modal.php -->
<div class="modal fade" id="editEggModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content border-0 shadow" action="processors/save_daily_egg_collection.php" method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="edit_collection_id" name="id">

            <div class="modal-header bg-dark text-white">
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

                    <!-- Egg Counts Details -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-success">Total No. of Eggs</label>
                        <input type="number" id="edit_egg_count" name="total_eggs" class="form-control" min="0" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Hatchable Eggs</label>
                        <input type="number" id="edit_hatchable_eggs" name="hatchable_eggs" class="form-control" min="0" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Table Eggs</label>
                        <input type="number" id="edit_table_eggs" name="table_eggs" class="form-control" min="0" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Cracked Eggs</label>
                        <input type="number" id="edit_cracked_eggs" name="cracked_eggs" class="form-control" min="0" required>
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