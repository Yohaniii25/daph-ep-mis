<div class="modal fade" id="addAssetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #820100;">
                <h5 class="modal-title"><i class="bi bi-building-fill-add me-2"></i>Register Property / Land Asset</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addAssetForm" action="processors/add_land_asset.php" method="POST">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Property Identification Name</label>
                            <input type="text" name="property_name" class="form-control" placeholder="e.g. Sub-Office Complex Block B" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Land Extent</label>
                            <input type="text" name="land_extent" class="form-control" placeholder="e.g. 1 Acre, 2 Roods" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Building Area</label>
                            <input type="text" name="building_area" class="form-control" placeholder="e.g. 2,500 sq. ft." required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Land Ownership Status</label>
                            <select name="land_status" class="form-select" required>
                                <option value="State Owned">State Owned</option>
                                <option value="Leased">Leased</option>
                                <option value="Vested">Vested</option>
                                <option value="Private">Private</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Deed Reference Code Number</label>
                            <input type="text" name="deed_reference" class="form-control" placeholder="e.g. Deed No: G-5421" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Deed Registry Office Description</label>
                            <textarea name="deed_description" class="form-control" rows="2" placeholder="Provide tracking descriptions..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn text-light" style="background-color: #820100;">Save Asset Record</button>
                </div>
            </form>
        </div>
    </div>
</div>