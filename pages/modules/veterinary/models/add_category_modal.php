<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-folder-plus me-2"></i>Add Production Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/save_production_category.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Category Name</label>
                        <input type="text" name="category_name" class="form-control" placeholder="e.g. Milk Production (Formal)" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="0" min="0" required>
                        <p class="text-muted small mt-1 mb-0">Determines display hierarchy order.</p>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>
