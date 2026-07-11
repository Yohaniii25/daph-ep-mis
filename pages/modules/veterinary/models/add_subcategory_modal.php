<div class="modal fade" id="addSubCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-tag-fill me-2"></i>Add Production Sub Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/save_production_item.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Category Select</label>
                        <select name="category_id" class="form-select" required>
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
                        <label class="form-label fw-bold small">Sub Category / Item Name</label>
                        <input type="text" name="item_name" class="form-control" placeholder="e.g. MILCO, Ice cream, Nestle" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Unit</label>
                        <input type="text" name="unit" class="form-control" placeholder="e.g. L, Nos, Kg" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Sub Category</button>
                </div>
            </form>
        </div>
    </div>
</div>
