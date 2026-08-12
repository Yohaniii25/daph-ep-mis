<div class="modal fade" id="editInventoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-light">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Building Inventory Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editInventoryForm" action="processors/update_building_inventory.php" method="POST">
                <input type="hidden" name="id" id="edit_inventory_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Select Target Location Property</label>
                            <select name="land_asset_id" id="edit_land_asset_id" class="form-select" required>
                                <option value="" disabled selected>-- Select Property Site --</option>
                                <?php foreach ($lands_cache as $land): ?>
                                    <option value="<?= $land['id'] ?>"><?= htmlspecialchars($land['property_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Inventory Item Name</label>
                            <input type="text" name="inventory_item" id="edit_inventory_item" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Available Quantity</label>
                            <input type="number" name="available_quantity" id="edit_available_quantity" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Current Condition</label>
                            <select name="current_condition" id="edit_current_condition" class="form-select" required>
                                <option value="Excellent">Excellent</option>
                                <option value="Good">Good</option>
                                <option value="Fair (Needs Service)">Fair (Needs Service)</option>
                                <option value="Critical Failure">Critical Failure</option>
                                <option value="Damaged">Damaged</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Item Specification</label>
                            <input type="text" name="specification" id="edit_specification" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Additional Notes / Remarks</label>
                            <textarea name="remarks" id="edit_remarks" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-dark">Update Inventory Item</button>
                </div>
            </form>
        </div>
    </div>
</div>
