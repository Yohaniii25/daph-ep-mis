<div class="modal fade" id="addAssetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div id="modalHeaderColor" class="modal-header bg-dark text-white py-2">
                <h6 class="modal-title" id="assetModalTitle"><i class="bi bi-plus-square me-2"></i>Register New Asset</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/process_assets.php" method="POST">
                <div class="modal-body p-3">
                    
                    <div class="mb-3 p-2 bg-light rounded border">
                        <label class="form-label small fw-bold text-muted mb-1">Asset Category</label>
                        <select name="asset_type" id="assetTypeSelector" class="form-select form-select-sm border-primary" required>
                            <option value="" selected disabled>-- Select Category --</option>
                            <option value="immovable">Immovable (Land / Buildings)</option>
                            <option value="movable">Movable (Vehicles / Equipment)</option>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold">Asset / Item Name</label>
                        <input type="text" name="display_name" class="form-control form-control-sm" required placeholder="e.g. Range Office Main Hall">
                    </div>

                    <div id="dynamicFields" class="mt-2 pt-2 border-top">
                        
                        <div id="immovableFields" style="display: none;">
                            <div class="row g-2">
                                <div class="col-7">
                                    <label class="form-label small fw-normal">Location / Address</label>
                                    <input type="text" name="location" class="form-control form-control-sm immovable-input" placeholder="Address">
                                </div>
                                <div class="col-5">
                                    <label class="form-label small fw-normal">Extent (Size)</label>
                                    <input type="text" name="extent" class="form-control form-control-sm immovable-input" placeholder="e.g. 10P">
                                </div>
                            </div>
                        </div>

                        <div id="movableFields" style="display: none;">
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small fw-normal">Category</label>
                                    <select name="category" class="form-select form-select-sm movable-input">
                                        <option value="Equipment">Equipment</option>
                                        <option value="Vehicle">Vehicle</option>
                                        <option value="Furniture">Furniture</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-normal">Condition</label>
                                    <select name="condition" class="form-select form-select-sm movable-input">
                                        <option value="Good">Good</option>
                                        <option value="Fair">Fair</option>
                                        <option value="Needs Repair">Needs Repair</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-normal">Serial / Tag No</label>
                                <input type="text" name="serial_no" class="form-control form-control-sm movable-input" placeholder="ID or Serial Number">
                            </div>
                        </div>

                        <div class="mt-2">
                            <label class="form-label small fw-normal">Remarks</label>
                            <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Short notes..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 border-top-0">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="submitBtn" class="btn btn-primary btn-sm px-4 shadow-sm" disabled>Save Asset Record</button>
                </div>
            </form>
        </div>
    </div>
</div>