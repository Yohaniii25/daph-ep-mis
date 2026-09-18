<?php
// Fetch all items for dynamic binding
$edit_items = [];
$edit_items_query = $mysqli->query("SELECT id, category_id, item_name, unit FROM production_items");
if ($edit_items_query) {
    while($row = $edit_items_query->fetch_assoc()) {
        $edit_items[] = $row;
    }
}
?>

<div class="modal fade" id="editProductionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Production Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/update_production.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Report Month & Year</label>
                        <input type="month" name="report_month" id="edit_report_month" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Production Category</label>
                        <select id="editCategorySelect" name="category_id" class="form-select" required>
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
                        <select id="editItemSelect" name="item_id" class="form-select" required>
                            <option value="">-- Select Category First --</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Amount Produced</label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="amount" id="edit_amount" class="form-control" placeholder="0.00" required>
                            <span class="input-group-text" id="editUnitDisplay">-</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    const productsEdit = <?= json_encode($edit_items) ?>;

    // Prefill child dropdown when category changes
    function rebuildEditItems(selectedCatId, preselectedItemId = null) {
        const itemSelect = document.getElementById('editItemSelect');
        const unitDisplay = document.getElementById('editUnitDisplay');

        itemSelect.innerHTML = '<option value="">-- Select Item --</option>';
        unitDisplay.textContent = '-';

        if (selectedCatId) {
            const filtered = productsEdit.filter(p => p.category_id == selectedCatId);
            
            filtered.forEach(item => {
                let opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.item_name;
                opt.setAttribute('data-unit', item.unit);
                if (preselectedItemId && item.id == preselectedItemId) {
                    opt.selected = true;
                    unitDisplay.textContent = item.unit;
                }
                itemSelect.appendChild(opt);
            });
        }
    }

    document.addEventListener('change', function(e) {
        if (e.target && e.target.id === 'editCategorySelect') {
            rebuildEditItems(e.target.value);
        }

        if (e.target && e.target.id === 'editItemSelect') {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const unit = selectedOption.getAttribute('data-unit');
            document.getElementById('editUnitDisplay').textContent = unit ? unit : '-';
        }
    });

    // Handle incoming triggers to populate Edit Modal
    window.initEditProductionModal = function(id, year, month, catId, itemId, amount) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_amount').value = amount;
        
        // Format report month input (YYYY-MM)
        const paddedMonth = String(month).padStart(2, '0');
        document.getElementById('edit_report_month').value = `${year}-${paddedMonth}`;
        
        // Set category select and rebuild items
        document.getElementById('editCategorySelect').value = catId;
        rebuildEditItems(catId, itemId);
    };
})();
</script>
