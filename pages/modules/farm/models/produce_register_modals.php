<?php
// pages/modules/farm/models/produce_register_modals.php
?>

<!-- Modal 1: Add New Commodity Item -->
<div class="modal fade" id="addCommodityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c1, #820100);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-tree me-2"></i>Add New Commodity Produce
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/produce_register_crud.php" method="POST">
                <input type="hidden" name="action" value="create_commodity">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Commodity Name <span class="text-danger">*</span></label>
                        <input type="text" name="commodity_name" class="form-control" placeholder="e.g. Cow Milk / Fodder Grass / Fresh Eggs" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Unit of Measure <span class="text-danger">*</span></label>
                        <select name="unit_of_measure" class="form-select">
                            <option value="Liters">Liters</option>
                            <option value="Kg">Kg</option>
                            <option value="Units">Units</option>
                            <option value="Bundles">Bundles</option>
                            <option value="Bags">Bags</option>
                            <option value="Tons">Tons</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description / Notes</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional details on variety or section..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--color-c1, #820100);">
                        <i class="bi bi-plus-circle me-1"></i>Add Commodity
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Log Produce Entry (Annex 6) -->
<div class="modal fade" id="addProduceRegisterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c1, #820100);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-plus-circle me-2"></i>Log Production & Disposal Entry
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/produce_register_crud.php" method="POST" id="addProduceForm">
                <input type="hidden" name="action" value="create_produce">
                <input type="hidden" name="commodity_id" value="<?= $selected_commodity_id ?>">
                
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 shadow-sm py-2 px-3 mb-4 d-flex align-items-center">
                        <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                        <div>
                            Logging Produce Entry for Commodity: <strong class="text-dark"><?= htmlspecialchars($selected_commodity['commodity_name'] ?? '') ?></strong>
                            (Unit: <strong><?= htmlspecialchars($selected_commodity['unit_of_measure'] ?? 'Kg') ?></strong>)
                        </div>
                    </div>

                    <!-- RECEIPT GROUP -->
                    <div class="card border border-primary border-2 mb-4" style="border-radius: 10px;">
                        <div class="card-header bg-primary text-white py-2 fw-bold">
                            <i class="bi bi-box-arrow-in-down me-1"></i>RECEIPT SECTION
                        </div>
                        <div class="card-body bg-light p-3">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                                    <input type="date" name="record_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Plot No</label>
                                    <input type="text" name="plot_no" class="form-control" placeholder="e.g. Plot A-1 / Field 02">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Quantity (<?= htmlspecialchars($selected_commodity['unit_of_measure'] ?? 'Kg') ?>) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0.01" name="quantity" id="add_produce_qty" class="form-control form-control-lg fw-bold text-primary produce-calc" placeholder="0.00" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DISPOSAL GROUP -->
                    <div class="card border border-success border-2 mb-3" style="border-radius: 10px;">
                        <div class="card-header bg-success text-white py-2 fw-bold">
                            <i class="bi bi-box-arrow-up-right me-1"></i>DISPOSAL SECTION
                        </div>
                        <div class="card-body bg-light p-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Method of Disposal <span class="text-danger">*</span></label>
                                    <select name="disposal_method" class="form-select fw-bold" required>
                                        <option value="Cash Sale">Cash Sale</option>
                                        <option value="Credit Sale">Credit Sale</option>
                                        <option value="Hatchery Transfer">Hatchery Transfer</option>
                                        <option value="Farm Internal Use">Farm Internal Use</option>
                                        <option value="Wastage / Spoiled">Wastage / Spoiled</option>
                                        <option value="Free Sample / Demonstration">Free Sample / Demonstration</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Price per Unit if sold for Cash (LKR)</label>
                                    <input type="number" step="0.01" min="0" name="unit_price" id="add_produce_unit_price" class="form-control form-control-lg fw-bold text-success produce-calc" value="0.00">
                                </div>

                                <div class="col-md-6">
                                    <div class="p-3 bg-white rounded border border-success">
                                        <label class="form-label fw-bold text-success">
                                            <i class="bi bi-calculator me-1"></i>Full Sum Realized (Auto) (LKR)
                                        </label>
                                        <input type="number" step="0.01" name="full_sum_realized" id="add_produce_full_sum" class="form-control form-control-lg fw-bold text-success bg-light" value="0.00" readonly>
                                        <small class="text-muted">Calculated as: Quantity × Unit Price</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Cash Receipt No of page of Credit Sale Book</label>
                                    <input type="text" name="receipt_no_or_page" class="form-control" placeholder="e.g. CR-1049 / Page 42">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Initials</label>
                                    <input type="text" name="initials" class="form-control" value="<?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>" placeholder="e.g. K.A.P. / Farm Manager">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Remarks / Notes</label>
                                    <input type="text" name="remarks" class="form-control" placeholder="Additional notes or batch details...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--color-c1, #820100);">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Produce Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Edit Produce Entry -->
<div class="modal fade" id="editProduceRegisterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c10, #185dbd);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-pencil-square me-2"></i>Edit Production & Disposal Entry (Annex 6)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/produce_register_crud.php" method="POST">
                <input type="hidden" name="action" value="update_produce">
                <input type="hidden" name="id" id="edit_produce_id">
                <input type="hidden" name="commodity_id" value="<?= $selected_commodity_id ?>">
                
                <div class="modal-body p-4">
                    <!-- RECEIPT GROUP -->
                    <div class="card border border-primary border-2 mb-4" style="border-radius: 10px;">
                        <div class="card-header bg-primary text-white py-2 fw-bold">
                            <i class="bi bi-box-arrow-in-down me-1"></i>RECEIPT SECTION
                        </div>
                        <div class="card-body bg-light p-3">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                                    <input type="date" name="record_date" id="edit_record_date" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Plot No</label>
                                    <input type="text" name="plot_no" id="edit_plot_no" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0.01" name="quantity" id="edit_produce_qty" class="form-control form-control-lg fw-bold text-primary edit-produce-calc" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DISPOSAL GROUP -->
                    <div class="card border border-success border-2 mb-3" style="border-radius: 10px;">
                        <div class="card-header bg-success text-white py-2 fw-bold">
                            <i class="bi bi-box-arrow-up-right me-1"></i>DISPOSAL SECTION
                        </div>
                        <div class="card-body bg-light p-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Method of Disposal <span class="text-danger">*</span></label>
                                    <select name="disposal_method" id="edit_disposal_method" class="form-select fw-bold" required>
                                        <option value="Cash Sale">Cash Sale</option>
                                        <option value="Credit Sale">Credit Sale</option>
                                        <option value="Hatchery Transfer">Hatchery Transfer</option>
                                        <option value="Farm Internal Use">Farm Internal Use</option>
                                        <option value="Wastage / Spoiled">Wastage / Spoiled</option>
                                        <option value="Free Sample / Demonstration">Free Sample / Demonstration</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Price per Unit if sold for Cash (LKR)</label>
                                    <input type="number" step="0.01" min="0" name="unit_price" id="edit_produce_unit_price" class="form-control form-control-lg fw-bold text-success edit-produce-calc">
                                </div>

                                <div class="col-md-6">
                                    <div class="p-3 bg-white rounded border border-success">
                                        <label class="form-label fw-bold text-success">Full Sum Realized (LKR)</label>
                                        <input type="number" step="0.01" name="full_sum_realized" id="edit_produce_full_sum" class="form-control form-control-lg fw-bold text-success bg-light" readonly>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Cash Receipt No of page of Credit Sale Book</label>
                                    <input type="text" name="receipt_no_or_page" id="edit_receipt_no_or_page" class="form-control">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Initials</label>
                                    <input type="text" name="initials" id="edit_initials" class="form-control">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Remarks / Notes</label>
                                    <input type="text" name="remarks" id="edit_remarks" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--color-c10, #185dbd);">
                        <i class="bi bi-check-circle-fill me-1"></i>Update Produce Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
