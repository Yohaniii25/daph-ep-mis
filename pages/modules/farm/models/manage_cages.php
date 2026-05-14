<div class="modal fade" id="cageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" action="processors/save_cages.php" method="POST">
            <input type="hidden" name="action" value="manage_cages">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">Manage Cage Assignments</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                    <label class="form-label fw-bold">Assigned Cage IDs</label>
                    <input type="text" name="cage_labels" class="form-control" placeholder="e.g., Cage 01, Cage 02, Cage 03" required>
                    <small class="text-muted">Separate multiple cages with commas.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-warning w-100 fw-bold">Update Assignments</button>
            </div>
        </form>
    </div>
</div>