<?php
// pages/modules/farm/models/drug_register_modals.php
?>

<!-- Modal 1: Add New Drug Master Item -->
<div class="modal fade" id="addDrugItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c1, #820100);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-capsule me-2"></i>Add New Drug / Medicine Item
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/drug_register_crud.php" method="POST">
                <input type="hidden" name="action" value="create_item">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Drug / Item Name <span class="text-danger">*</span></label>
                        <input type="text" name="item_name" class="form-control" placeholder="e.g. Amoxicillin 50% Powder" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Unit of Measure <span class="text-danger">*</span></label>
                            <select name="unit_of_measure" class="form-select">
                                <option value="Bottles">Bottles</option>
                                <option value="Vials">Vials</option>
                                <option value="Packets">Packets</option>
                                <option value="Tablets">Tablets</option>
                                <option value="ml">ml</option>
                                <option value="Liters">Liters</option>
                                <option value="g">g</option>
                                <option value="Kg">Kg</option>
                                <option value="Boxes">Boxes</option>
                                <option value="Units">Units</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Expiration Date</label>
                            <input type="date" name="exp_date" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description / Category</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional notes on usage or strength..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--color-c1, #820100);">
                        <i class="bi bi-plus-circle me-1"></i>Add Drug Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Distinct Receive Stock Order Modal -->
<div class="modal fade" id="receiveStockOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light bg-success">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-box-arrow-in-down me-2"></i>Receive Stock Order - Drug Register
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/drug_register_crud.php" method="POST">
                <input type="hidden" name="action" value="create_receive_order">
                <input type="hidden" name="item_id" value="<?= $selected_item_id ?>">
                
                <div class="modal-body p-4">
                    <div class="alert alert-success border-0 shadow-sm py-2 px-3 mb-3 d-flex align-items-center">
                        <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                        <div>
                            Receiving stock for: <strong class="text-dark"><?= htmlspecialchars($selected_item['item_name'] ?? '') ?></strong>
                            (Current Balance: <strong><?= number_format($current_balance, 2) ?> <?= htmlspecialchars($selected_item['unit_of_measure'] ?? 'units') ?></strong>)
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" class="form-control fw-bold" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Order Number (Audit)</label>
                            <input type="text" name="order_no" class="form-control fw-bold" value="RO-<?= date('Ymd') ?>-<?= rand(100, 999) ?>" placeholder="e.g. RO-20260806-101">
                            <small class="text-muted">Auto-generated / Editable</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Waybill / Delivery Note No.</label>
                            <input type="text" name="ref_doc_no" class="form-control" placeholder="e.g. WB-2026-0042">
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-bold text-success">Received From (Supplier / Central Store) <span class="text-danger">*</span></label>
                            <input type="text" name="received_from" class="form-control border-success" placeholder="e.g. Veterinary Central Store / SPC Colombo" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Expiration Date (Exp Date)</label>
                            <input type="date" name="exp_date" class="form-control">
                        </div>

                        <!-- Quantity Section -->
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border border-success border-2">
                                <label class="form-label fw-bold text-success">
                                    <i class="bi bi-arrow-down-left-circle me-1"></i>Received Qty (+) <span class="text-danger">*</span>
                                </label>
                                <input type="number" step="0.01" min="0.01" name="received_qty" id="receive_order_qty" class="form-control form-control-lg fw-bold text-success" value="1.00" required>
                                <small class="text-muted">Quantity added to inventory</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 bg-white rounded border border-primary border-2">
                                <label class="form-label fw-bold text-primary">
                                    <i class="bi bi-calculator me-1"></i>New Balance (Auto)
                                </label>
                                <input type="number" step="0.01" id="receive_order_calc_balance" class="form-control form-control-lg fw-bold text-primary bg-light" value="<?= number_format($current_balance + 1, 2, '.', '') ?>" readonly>
                                <small class="text-muted">Updated running total</small>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Remarks / Notes</label>
                            <input type="text" name="remarks" class="form-control" placeholder="Batch No, storage condition, or supplier invoice notes...">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold px-4 text-light">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Receive Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Distinct Issue Stock Order Modal -->
<div class="modal fade" id="issueStockOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--farm-secondary, #5a1216);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-box-arrow-up-right me-2"></i>Issue Stock Order - Drug Register
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/drug_register_crud.php" method="POST">
                <input type="hidden" name="action" value="create_issue_order">
                <input type="hidden" name="item_id" value="<?= $selected_item_id ?>">
                
                <div class="modal-body p-4">
                    <div class="alert border-0 shadow-sm py-2 px-3 mb-3 d-flex align-items-center" style="background-color: rgba(90, 18, 22, 0.1); color: var(--farm-secondary, #5a1216);">
                        <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                        <div>
                            Issuing stock for: <strong class="text-dark"><?= htmlspecialchars($selected_item['item_name'] ?? '') ?></strong>
                            (Current Stock Available: <strong><?= number_format($current_balance, 2) ?> <?= htmlspecialchars($selected_item['unit_of_measure'] ?? 'units') ?></strong>)
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" class="form-control fw-bold" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Order Number (Audit)</label>
                            <input type="text" name="order_no" class="form-control fw-bold" value="IO-<?= date('Ymd') ?>-<?= rand(100, 999) ?>" placeholder="e.g. IO-20260806-202">
                            <small class="text-muted">Auto-generated / Editable</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Requisition / Issue Note No.</label>
                            <input type="text" name="ref_doc_no" class="form-control" placeholder="e.g. IN-8821">
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-bold text-farm-secondary">Issued To (Farm Section / Unit) <span class="text-danger">*</span></label>
                            <input type="text" name="issued_to" class="form-control border-farm-secondary" placeholder="e.g. Brooder Section / Cage A / Layer Unit" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Expiration Date (Exp Date)</label>
                            <input type="date" name="exp_date" class="form-control" value="<?= htmlspecialchars($selected_item['exp_date'] ?? '') ?>">
                        </div>

                        <!-- Quantity Section -->
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border border-farm-secondary border-2">
                                <label class="form-label fw-bold text-farm-secondary">
                                    <i class="bi bi-arrow-up-right-circle me-1"></i>Issued Qty (-) <span class="text-danger">*</span>
                                </label>
                                <input type="number" step="0.01" min="0.01" name="issued_qty" id="issue_order_qty" class="form-control form-control-lg fw-bold text-farm-secondary" value="1.00" required>
                                <small class="text-muted">Quantity deducted from stock</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 bg-white rounded border border-primary border-2">
                                <label class="form-label fw-bold text-primary">
                                    <i class="bi bi-calculator me-1"></i>New Balance (Auto)
                                </label>
                                <input type="number" step="0.01" id="issue_order_calc_balance" class="form-control form-control-lg fw-bold text-primary bg-light" value="<?= number_format(max(0, $current_balance - 1), 2, '.', '') ?>" readonly>
                                <small class="text-muted">Updated running total</small>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Remarks / Purpose</label>
                            <input type="text" name="remarks" class="form-control" placeholder="e.g. Routine vaccination / Disease treatment / Cage B Flock...">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--farm-secondary, #5a1216);">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Issue Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 4: Edit Drug Stock Entry Modal -->
<div class="modal fade" id="editDrugLedgerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c10, #185dbd);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-pencil-square me-2"></i>Edit Stock Entry
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/drug_register_crud.php" method="POST">
                <input type="hidden" name="action" value="update_ledger">
                <input type="hidden" name="id" id="edit_drug_ledger_id">
                <input type="hidden" name="item_id" value="<?= $selected_item_id ?>">
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Order Number (Audit)</label>
                            <input type="text" name="order_no" id="edit_order_no" class="form-control fw-bold">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" id="edit_record_date" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Expiration Date (Exp Date)</label>
                            <input type="date" name="exp_date" id="edit_exp_date" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-success">Received From</label>
                            <input type="text" name="received_from" id="edit_received_from" class="form-control border-success" placeholder="Supplier / Store">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-danger">Issued To</label>
                            <input type="text" name="issued_to" id="edit_issued_to" class="form-control border-danger" placeholder="Farm Unit / Section">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">No. of Way-bill, Issue Note, &c.</label>
                            <input type="text" name="ref_doc_no" id="edit_ref_doc_no" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded border border-success border-2">
                                <label class="form-label fw-bold text-success">Received Qty (+)</label>
                                <input type="number" step="0.01" min="0" name="received_qty" id="edit_received_qty" class="form-control form-control-lg fw-bold text-success" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded border border-danger border-2">
                                <label class="form-label fw-bold text-danger">Issued Qty (-)</label>
                                <input type="number" step="0.01" min="0" name="issued_qty" id="edit_issued_qty" class="form-control form-control-lg fw-bold text-danger" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 bg-white rounded border border-primary border-2">
                                <label class="form-label fw-bold text-primary">Recorded Balance</label>
                                <input type="number" step="0.01" id="edit_balance_qty" class="form-control form-control-lg fw-bold text-primary bg-light" readonly>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Remarks / Notes</label>
                            <input type="text" name="remarks" id="edit_remarks" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--color-c10, #185dbd);">
                        <i class="bi bi-check-circle-fill me-1"></i>Update Stock Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 5: Edit Master Drug Item Modal -->
<div class="modal fade" id="editDrugItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c10, #185dbd);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-pencil-square me-2"></i>Edit Drug / Medicine Item
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/drug_register_crud.php" method="POST">
                <input type="hidden" name="action" value="update_item">
                <input type="hidden" name="id" id="edit_item_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Drug / Item Name <span class="text-danger">*</span></label>
                        <input type="text" name="item_name" id="edit_item_name" class="form-control" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Unit of Measure <span class="text-danger">*</span></label>
                            <select name="unit_of_measure" id="edit_item_unit" class="form-select">
                                <option value="Bottles">Bottles</option>
                                <option value="Vials">Vials</option>
                                <option value="Packets">Packets</option>
                                <option value="Tablets">Tablets</option>
                                <option value="ml">ml</option>
                                <option value="Liters">Liters</option>
                                <option value="g">g</option>
                                <option value="Kg">Kg</option>
                                <option value="Boxes">Boxes</option>
                                <option value="Units">Units</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Expiration Date</label>
                            <input type="date" name="exp_date" id="edit_item_exp_date" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description / Category</label>
                        <textarea name="description" id="edit_item_desc" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--color-c10, #185dbd);">
                        <i class="bi bi-check-circle-fill me-1"></i>Update Drug Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

