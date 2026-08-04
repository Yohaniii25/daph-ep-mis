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
                    <div class="mb-3">
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

<!-- Modal 2: Log Drug Stock Entry (Annex 5) -->
<div class="modal fade" id="addDrugLedgerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c1, #820100);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-file-earmark-plus me-2"></i>Log Stock Entry - Drug Register
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/drug_register_crud.php" method="POST" id="addDrugLedgerForm">
                <input type="hidden" name="action" value="create_ledger">
                <input type="hidden" name="item_id" value="<?= $selected_item_id ?>">
                
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 shadow-sm py-2 px-3 mb-3 d-flex align-items-center">
                        <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                        <div>
                            Logging stock entry for: <strong class="text-dark"><?= htmlspecialchars($selected_item['item_name'] ?? '') ?></strong>
                            (Current Stock Balance: <strong><?= number_format($current_balance, 2) ?> <?= htmlspecialchars($selected_item['unit_of_measure'] ?? 'units') ?></strong>)
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Received from, or Issued to <span class="text-danger">*</span></label>
                            <input type="text" name="party_name" class="form-control" placeholder="Supplier name / Farm Section / VSL Office..." required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">No. of Way-bill, Issue Note, &c.</label>
                            <input type="text" name="ref_doc_no" class="form-control" placeholder="e.g. WB-2026-0042 / IN-8821">
                        </div>

                        <!-- Quantity Section -->
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded border border-success border-2">
                                <label class="form-label fw-bold text-success">
                                    <i class="bi bi-arrow-down-left-circle me-1"></i>Received Qty (+)
                                </label>
                                <input type="number" step="0.01" min="0" name="received_qty" id="add_received_qty" class="form-control form-control-lg fw-bold text-success" value="0.00">
                                <small class="text-muted">Stock coming in</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded border border-danger border-2">
                                <label class="form-label fw-bold text-danger">
                                    <i class="bi bi-arrow-up-right-circle me-1"></i>Issued Qty (-)
                                </label>
                                <input type="number" step="0.01" min="0" name="issued_qty" id="add_issued_qty" class="form-control form-control-lg fw-bold text-danger" value="0.00">
                                <small class="text-muted">Stock going out</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 bg-white rounded border border-primary border-2">
                                <label class="form-label fw-bold text-primary">
                                    <i class="bi bi-calculator me-1"></i>New Balance (Auto)
                                </label>
                                <input type="number" step="0.01" id="add_calculated_balance" class="form-control form-control-lg fw-bold text-primary bg-light" value="<?= number_format($current_balance, 2, '.', '') ?>" readonly>
                                <small class="text-muted">Running balance</small>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Remarks / Notes</label>
                            <input type="text" name="remarks" class="form-control" placeholder="Batch No, Expiry date, or reason for issue...">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--color-c1, #820100);">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Stock Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Edit Drug Stock Entry -->
<div class="modal fade" id="editDrugLedgerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c10, #185dbd);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-pencil-square me-2"></i>Edit Stock Entry (Annex 5)
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
                            <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" id="edit_record_date" class="form-control" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Received from, or Issued to <span class="text-danger">*</span></label>
                            <input type="text" name="party_name" id="edit_party_name" class="form-control" required>
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
