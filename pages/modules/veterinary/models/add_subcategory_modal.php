<?php
/** @var mysqli $mysqli */
global $mysqli;

// Ensure database connection is available
if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    require_once __DIR__ . '/../../../../config/db_connect.php';
}
?>
<div class="modal fade" id="addSubCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background-color: #820100;">
                <h6 class="modal-title fw-bold"><i class="bi bi-tag-fill me-2"></i>Add Production Sub Category / Product</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/save_production_item.php" method="POST">
                <input type="hidden" name="year" value="<?= htmlspecialchars($selected_year ?? date('Y')) ?>">
                <input type="hidden" name="month" value="<?= htmlspecialchars($active_month ?? date('n')) ?>">
                <input type="hidden" name="active_tab" id="add_subcat_active_tab" value="">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Main Category Header <span class="text-danger">*</span></label>
                        <select name="category_id" id="add_subcat_category_id" class="form-select form-select-sm" required>
                            <option value="">-- Select Main Category --</option>
                            <?php
                            if (isset($mysqli) && $mysqli instanceof mysqli) {
                                $cat_res = $mysqli->query("SELECT * FROM production_categories ORDER BY sort_order ASC, id ASC");
                                if ($cat_res) {
                                    while ($cat = $cat_res->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($cat['id']) . "'>" . htmlspecialchars($cat['category_name']) . "</option>";
                                    }
                                }
                            }
                            ?>
                        </select>
                        <div class="form-text small text-muted">Select which of the 11 main headers this subcategory belongs to.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Subcategory / Item / Company Name <span class="text-danger">*</span></label>
                        <input type="text" name="item_name" class="form-control form-control-sm" placeholder="e.g. Fonterra Milk, Richlife, Organic Compost" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Unit of Measurement <span class="text-danger">*</span></label>
                        <input type="text" name="unit" class="form-control form-control-sm" placeholder="e.g. L, Kg, Nos, Acres, Bags" required>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm text-white fw-bold" style="background-color: #820100;">
                        <i class="bi bi-save me-1"></i>Save Subcategory
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
