<?php
// Fetch items from the database
$all_items = [];
$items_query = $mysqli->query("SELECT id, category_id, item_name, unit FROM production_items");
if ($items_query) {
    while($row = $items_query->fetch_assoc()) {
        $all_items[] = $row;
    }
}
?>

<div class="modal fade" id="addProductionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Add Monthly Production Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/save_production.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Report Month & Year</label>
                        <input type="month" name="report_month" class="form-control" required value="<?= date('Y-m') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Production Category</label>
                        <select id="categorySelect" name="category_id" class="form-select" required>
                            <option value="">-- Select Category --</option>
                            <?php
                            $cat_res = $mysqli->query("SELECT * FROM production_categories ORDER BY sort_order ASC");
                            while ($cat = $cat_res->fetch_assoc()) {
                                echo "<option value='{$cat['id']}'>{$cat['category_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Specific Product / Item</label>
                        <select id="itemSelect" name="item_id" class="form-select" required>
                            <option value="">-- Select Category First --</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Amount Produced</label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
                            <span class="input-group-text" id="unitDisplay">-</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Use a self-invoking function to avoid conflicts
(function() {
    const products = <?= json_encode($all_items) ?>;

    // Use standard DOM events instead of jQuery to ensure it runs regardless of load order
    document.addEventListener('change', function(e) {
        
        // Handle Category Change
        if (e.target && e.target.id === 'categorySelect') {
            const selectedCatId = e.target.value;
            const itemSelect = document.getElementById('itemSelect');
            const unitDisplay = document.getElementById('unitDisplay');

            // Reset Item Select
            itemSelect.innerHTML = '<option value="">-- Select Item --</option>';
            unitDisplay.textContent = '-';

            if (selectedCatId) {
                const filtered = products.filter(p => p.category_id == selectedCatId);
                
                if (filtered.length > 0) {
                    filtered.forEach(item => {
                        let opt = document.createElement('option');
                        opt.value = item.id;
                        opt.textContent = item.item_name;
                        opt.setAttribute('data-unit', item.unit);
                        itemSelect.appendChild(opt);
                    });
                } else {
                    itemSelect.innerHTML = '<option value="">No products found</option>';
                }
            }
        }

        // Handle Item Change (to update unit)
        if (e.target && e.target.id === 'itemSelect') {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const unit = selectedOption.getAttribute('data-unit');
            document.getElementById('unitDisplay').textContent = unit ? unit : '-';
        }
    });
})();
</script>