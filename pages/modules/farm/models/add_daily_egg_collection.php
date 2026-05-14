<div class="modal fade" id="eggModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" action="processors/save_daily_egg_collection.php" method="POST">
            <input type="hidden" name="action" value="add_eggs">
            
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-egg-fill me-2"></i>Daily Egg Collection
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Select Flock</label>
                    <select name="flock_id" class="form-select" required>
                        <option value="">Choose a region/flock</option>
                        <?php
                        $flock_query = "SELECT id, flock_code, region FROM parent_stock_flocks ORDER BY region, flock_code";
                        $flock_result = $mysqli->query($flock_query);
                        if ($flock_result && $flock_result->num_rows > 0) {
                            while ($flock_row = $flock_result->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($flock_row['id']) . "'>" .
                                    htmlspecialchars($flock_row['region']) . " - " . htmlspecialchars($flock_row['flock_code']) .
                                    "</option>";
                            }
                        } else {
                            echo "<option disabled>No flocks available. Please add flocks first.</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Total Eggs Collected</label>
                    <input type="number" name="egg_count" class="form-control" placeholder="Enter total eggs" min="0" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Collection Date</label>
                    <input type="date" name="collection_date" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                    <small class="text-muted">You cannot select a future date.</small>
                </div>
            </div>
            
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success fw-bold px-4">Save Production Data</button>
            </div>
        </form>
    </div>
</div>