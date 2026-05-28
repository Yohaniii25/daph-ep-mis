<div class="modal fade" id="manageTypesModal" tabindex="-1" aria-labelledby="manageTypesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="processors/process_master_types.php" method="POST">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="manageTypesModalLabel"><i class="bi bi-tags me-2"></i>Programme Categories</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">New Category Name</label>
                        <input type="text" name="programme_name" class="form-control" placeholder="Enter new programme type..." required>
                        <div class="form-text">Example: Cattle farm visit, Disease investigation, etc.</div>
                    </div>
                    
                    <div class="mt-4">
                        <label class="form-label small text-uppercase fw-bold text-muted">Currently Active Types</label>
                        <div class="list-group rounded-3 shadow-sm overflow-auto" style="max-height: 250px;">
                            <?php
                            $types_list = $mysqli->query("SELECT programme_name FROM master_programme_types WHERE is_active = 1 ORDER BY programme_name ASC");
                            if ($types_list->num_rows > 0):
                                while($t = $types_list->fetch_assoc()): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                                        <span class="small"><?php echo htmlspecialchars($t['programme_name']); ?></span>
                                        <span class="badge bg-success rounded-pill"><i class="bi bi-check"></i></span>
                                    </div>
                                <?php endwhile;
                            else: ?>
                                <div class="list-group-item text-center py-3 text-muted">No types defined yet.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_type" class="btn btn-primary px-4">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>