<?php
// pages/modules/farm/models/produce_register_modals.php
?>

<!-- Modal 1: Add New Master Commodity Item -->
<div class="modal fade" id="addCommodityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--farm-secondary, #5a1216);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-box-seam me-2"></i>Add New Master Commodity Produce
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
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--farm-secondary, #5a1216);">
                        <i class="bi bi-plus-circle me-1"></i>Add Commodity
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Edit Master Commodity Item -->
<div class="modal fade" id="editCommodityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c10, #185dbd);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-pencil-square me-2"></i>Edit Commodity Item
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/produce_register_crud.php" method="POST">
                <input type="hidden" name="action" value="update_commodity">
                <input type="hidden" name="id" id="edit_commodity_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Commodity Name <span class="text-danger">*</span></label>
                        <input type="text" name="commodity_name" id="edit_commodity_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Unit of Measure <span class="text-danger">*</span></label>
                        <select name="unit_of_measure" id="edit_commodity_unit" class="form-select">
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
                        <textarea name="description" id="edit_commodity_desc" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--color-c10, #185dbd);">
                        <i class="bi bi-check-circle-fill me-1"></i>Update Commodity
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Distinct Receive Produce (Harvest Intake) Modal -->
<div class="modal fade" id="receiveProduceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light bg-success">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-box-arrow-in-down me-2"></i>Receive Produce / Harvest Intake
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/produce_register_crud.php" method="POST">
                <input type="hidden" name="action" value="create_receive_produce">
                <input type="hidden" name="commodity_id" value="<?= $selected_commodity_id ?>">
                
                <div class="modal-body p-4">
                    <div class="alert alert-success border-0 shadow-sm py-2 px-3 mb-3 d-flex align-items-center">
                        <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                        <div>
                            Receiving produce for: <strong class="text-dark"><?= htmlspecialchars($selected_commodity['commodity_name'] ?? '') ?></strong>
                            (Opening Stock: <strong><?= number_format($current_balance, 2) ?> <?= htmlspecialchars($selected_commodity['unit_of_measure'] ?? 'Kg') ?></strong>)
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" class="form-control fw-bold" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Plot / Field Section No</label>
                            <input type="text" name="plot_no" class="form-control" placeholder="e.g. Plot A-1 / Field 02">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-success">Received From (Source) <span class="text-danger">*</span></label>
                            <input type="text" name="received_from" class="form-control border-success fw-bold" placeholder="e.g. Plot Harvest / Milking Parlor / Green House" required>
                        </div>

                        <!-- Quantity Section -->
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border border-success border-2">
                                <label class="form-label fw-bold text-success">
                                    <i class="bi bi-plus-circle me-1"></i>Received / Harvested Qty (+) <span class="text-danger">*</span>
                                </label>
                                <input type="number" step="0.01" min="0.01" name="received_qty" id="receive_produce_qty" class="form-control form-control-lg fw-bold text-success" value="10.00" required>
                                <small class="text-muted">In <?= htmlspecialchars($selected_commodity['unit_of_measure'] ?? 'Kg') ?></small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 bg-white rounded border border-primary border-2">
                                <label class="form-label fw-bold text-primary">
                                    <i class="bi bi-calculator me-1"></i>Closing Stock (Auto)
                                </label>
                                <input type="number" step="0.01" id="receive_produce_calc_balance" class="form-control form-control-lg fw-bold text-primary bg-light" value="<?= number_format($current_balance + 10, 2, '.', '') ?>" readonly>
                                <small class="text-muted">Updated total stock balance</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Initials</label>
                            <input type="text" name="initials" class="form-control" value="<?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Remarks / Notes</label>
                            <input type="text" name="remarks" class="form-control" placeholder="Harvest notes or quality grade...">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold px-4 text-light">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Receive Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 4: Distinct Issue Produce (Sales & Disposal) Modal -->
<div class="modal fade" id="issueProduceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--farm-secondary, #5a1216);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-box-arrow-up-right me-2"></i>Issue Produce / Sales & Disposal
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/produce_register_crud.php" method="POST">
                <input type="hidden" name="action" value="create_issue_produce">
                <input type="hidden" name="commodity_id" value="<?= $selected_commodity_id ?>">
                
                <div class="modal-body p-4">
                    <div class="alert border-0 shadow-sm py-2 px-3 mb-3 d-flex align-items-center" style="background-color: rgba(90, 18, 22, 0.1); color: var(--farm-secondary, #5a1216);">
                        <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                        <div>
                            Issuing produce for: <strong class="text-dark"><?= htmlspecialchars($selected_commodity['commodity_name'] ?? '') ?></strong>
                            (Opening Stock Available: <strong><?= number_format($current_balance, 2) ?> <?= htmlspecialchars($selected_commodity['unit_of_measure'] ?? 'Kg') ?></strong>)
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" class="form-control fw-bold" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-farm-secondary">Issued To (Recipient / Buyer) <span class="text-danger">*</span></label>
                            <input type="text" name="issued_to" class="form-control border-farm-secondary" placeholder="e.g. Milk Society / Buyer Name / Livestock Unit" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Method of Disposal <span class="text-danger">*</span></label>
                            <select name="disposal_method" class="form-select fw-bold" required>
                                <option value="Cash Sale">Cash Sale</option>
                                <option value="Credit Sale">Credit Sale</option>
                                <option value="Farm Internal Use">Farm Internal Use</option>
                                <option value="Hatchery Transfer">Hatchery Transfer</option>
                                <option value="Wastage / Spoiled">Wastage / Spoiled</option>
                                <option value="Free Sample / Demonstration">Free Sample / Demonstration</option>
                            </select>
                        </div>

                        <!-- Quantity Section -->
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border border-farm-secondary border-2">
                                <label class="form-label fw-bold text-farm-secondary">
                                    <i class="bi bi-arrow-up-right-circle me-1"></i>Issued Qty (-) <span class="text-danger">*</span>
                                </label>
                                <input type="number" step="0.01" min="0.01" name="issued_qty" id="issue_produce_qty" class="form-control form-control-lg fw-bold text-farm-secondary produce-issue-calc" value="5.00" required>
                                <small class="text-muted">In <?= htmlspecialchars($selected_commodity['unit_of_measure'] ?? 'Kg') ?></small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 bg-white rounded border border-primary border-2">
                                <label class="form-label fw-bold text-primary">
                                    <i class="bi bi-calculator me-1"></i>Closing Stock (Auto)
                                </label>
                                <input type="number" step="0.01" id="issue_produce_calc_balance" class="form-control form-control-lg fw-bold text-primary bg-light" value="<?= number_format(max(0, $current_balance - 5), 2, '.', '') ?>" readonly>
                                <small class="text-muted">Updated remaining stock balance</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Price per Unit (LKR)</label>
                            <input type="number" step="0.01" min="0" name="unit_price" id="issue_produce_unit_price" class="form-control form-control-lg fw-bold text-success produce-issue-calc" value="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-success">Full Sum Realized (LKR)</label>
                            <input type="number" step="0.01" name="full_sum_realized" id="issue_produce_full_sum" class="form-control form-control-lg fw-bold text-success bg-light" value="0.00" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Cash Receipt / Credit Page No.</label>
                            <input type="text" name="receipt_no_or_page" class="form-control" placeholder="e.g. CR-1049 / Page 42">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Initials</label>
                            <input type="text" name="initials" class="form-control" value="<?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Remarks / Notes</label>
                            <input type="text" name="remarks" class="form-control" placeholder="Sales receipt details or notes...">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--farm-secondary, #5a1216);">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Issue Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 5: Edit Produce Register Entry -->
<div class="modal fade" id="editProduceRegisterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c10, #185dbd);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-pencil-square me-2"></i>Edit Production & Disposal Entry
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/produce_register_crud.php" method="POST">
                <input type="hidden" name="action" value="update_produce">
                <input type="hidden" name="id" id="edit_produce_id">
                <input type="hidden" name="commodity_id" value="<?= $selected_commodity_id ?>">
                
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" id="edit_record_date" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Received From</label>
                            <input type="text" name="received_from" id="edit_received_from" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Issued To</label>
                            <input type="text" name="issued_to" id="edit_issued_to" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Plot No</label>
                            <input type="text" name="plot_no" id="edit_plot_no" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-success">Received Qty (+)</label>
                            <input type="number" step="0.01" min="0" name="received_qty" id="edit_received_qty" class="form-control fw-bold text-success edit-produce-calc">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-farm-secondary">Issued Qty (-)</label>
                            <input type="number" step="0.01" min="0" name="issued_qty" id="edit_issued_qty" class="form-control fw-bold text-farm-secondary edit-produce-calc">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Method of Disposal <span class="text-danger">*</span></label>
                            <select name="disposal_method" id="edit_disposal_method" class="form-select fw-bold" required>
                                <option value="Harvest Intake">Harvest Intake</option>
                                <option value="Cash Sale">Cash Sale</option>
                                <option value="Credit Sale">Credit Sale</option>
                                <option value="Farm Internal Use">Farm Internal Use</option>
                                <option value="Hatchery Transfer">Hatchery Transfer</option>
                                <option value="Wastage / Spoiled">Wastage / Spoiled</option>
                                <option value="Free Sample / Demonstration">Free Sample / Demonstration</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Price per Unit (LKR)</label>
                            <input type="number" step="0.01" min="0" name="unit_price" id="edit_produce_unit_price" class="form-control fw-bold text-success edit-produce-calc">
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 bg-white rounded border border-success">
                                <label class="form-label fw-bold text-success">Full Sum Realized (LKR)</label>
                                <input type="number" step="0.01" name="full_sum_realized" id="edit_produce_full_sum" class="form-control fw-bold text-success bg-light" readonly>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Cash Receipt No / Page</label>
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
