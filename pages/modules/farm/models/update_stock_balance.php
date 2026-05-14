<div class="modal fade" id="stockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" action="processors/update_stock_balance.php" method="POST">
            <input type="hidden" name="action" value="update_stock">
            <div class="modal-header bg-primary">
                <h5 style="color: white;" class="modal-title">Update Stock Balance</h5>
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
                        <label class="form-label fw-bold">Current Count</label>
                        <input type="number" id="currentCount" class="form-control" value="" readonly>
                    </div>
                    <div class="row">
                        <div class="col mb-3">
                            <label class="form-label fw-bold">Number of newly added</label>
                            <input type="number" name="newly_added" class="form-control" placeholder="0" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col mb-3">
                            <label class="form-label fw-bold">Culling Count</label>
                            <input type="number" name="culling" class="form-control" placeholder="0" required>
                        </div>
                    </div>
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        var flockSelect = document.getElementById('flockSelect');
                        var currentCount = document.getElementById('currentCount');
                        if (flockSelect) {
                            flockSelect.addEventListener('change', function() {
                                var selected = flockSelect.options[flockSelect.selectedIndex];
                                currentCount.value = selected.getAttribute('data-current') || '';
                            });
                        }
                    });
                    </script>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary w-100">Update Balance</button>
            </div>
        </form>
    </div>
</div>