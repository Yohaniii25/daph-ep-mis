<?php
// pages/modules/farm/models/fuel_register_modals.php
?>

<!-- Modal 1: Add New Fuel Master Item -->
<div class="modal fade" id="addFuelItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c1, #820100);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-fuel-pump me-2"></i>Add New Fuel Item
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/fuel_register_crud.php" method="POST">
                <input type="hidden" name="action" value="create_item">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Fuel / Item Name <span class="text-danger">*</span></label>
                        <input type="text" name="item_name" class="form-control" placeholder="e.g. Auto Diesel / Petrol Octane 92 / Kerosene" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Unit of Measure <span class="text-danger">*</span></label>
                        <select name="unit_of_measure" class="form-select">
                            <option value="Liters">Liters</option>
                            <option value="Gallons">Gallons</option>
                            <option value="Drums">Drums</option>
                            <option value="Canisters">Canisters</option>
                            <option value="Units">Units</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description / Equipment Category</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional notes on usage (e.g. Tractors, Generators, Vehicles)..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--color-c1, #820100);">
                        <i class="bi bi-plus-circle me-1"></i>Add Fuel Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Log Fuel Stock Entry -->
<div class="modal fade" id="addFuelLedgerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c1, #820100);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-file-earmark-plus me-2"></i>Log Stock Entry - Fuel Register
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/fuel_register_crud.php" method="POST" id="addFuelLedgerForm">
                <input type="hidden" name="action" value="create_ledger">
                <input type="hidden" name="item_id" value="<?= $selected_item_id ?>">
                
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 shadow-sm py-2 px-3 mb-3 d-flex align-items-center">
                        <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                        <div>
                            Logging fuel entry for: <strong class="text-dark"><?= htmlspecialchars($selected_item['item_name'] ?? '') ?></strong>
                            (Current Fuel Balance: <strong><?= number_format($current_balance, 2) ?> <?= htmlspecialchars($selected_item['unit_of_measure'] ?? 'Liters') ?></strong>)
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Received from, or Issued to <span class="text-danger">*</span></label>
                            <input type="text" name="party_name" class="form-control" placeholder="Supplier / Ceypetco / Tractor No / Generator 01..." required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">No. of Way-bill, Issue Note, &c.</label>
                            <input type="text" name="ref_doc_no" class="form-control" placeholder="e.g. WB-FUEL-1029 / IN-4011">
                        </div>

                        <!-- Quantity Section -->
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded border border-success border-2">
                                <label class="form-label fw-bold text-success">
                                    <i class="bi bi-arrow-down-left-circle me-1"></i>Received (+)
                                </label>
                                <input type="number" step="0.01" min="0" name="received_qty" id="add_fuel_received_qty" class="form-control form-control-lg fw-bold text-success" value="0.00">
                                <small class="text-muted">Fuel added to stock</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded border border-danger border-2">
                                <label class="form-label fw-bold text-danger">
                                    <i class="bi bi-arrow-up-right-circle me-1"></i>Issued (-)
                                </label>
                                <input type="number" step="0.01" min="0" name="issued_qty" id="add_fuel_issued_qty" class="form-control form-control-lg fw-bold text-danger" value="0.00">
                                <small class="text-muted">Fuel consumed / issued</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 bg-white rounded border border-primary border-2">
                                <label class="form-label fw-bold text-primary">
                                    <i class="bi bi-calculator me-1"></i>Balance (Auto)
                                </label>
                                <input type="number" step="0.01" id="add_fuel_calculated_balance" class="form-control form-control-lg fw-bold text-primary bg-light" value="<?= number_format($current_balance, 2, '.', '') ?>" readonly>
                                <small class="text-muted">Calculated balance</small>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Remarks / Notes</label>
                            <input type="text" name="remarks" class="form-control" placeholder="Vehicle meter reading, purpose, or voucher details...">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--color-c1, #820100);">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Fuel Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Edit Fuel Stock Entry -->
<div class="modal fade" id="editFuelLedgerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c10, #185dbd);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-pencil-square me-2"></i>Edit Fuel Stock Entry
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/fuel_register_crud.php" method="POST">
                <input type="hidden" name="action" value="update_ledger">
                <input type="hidden" name="id" id="edit_fuel_ledger_id">
                <input type="hidden" name="item_id" value="<?= $selected_item_id ?>">
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" id="edit_fuel_record_date" class="form-control" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Received from, or Issued to <span class="text-danger">*</span></label>
                            <input type="text" name="party_name" id="edit_fuel_party_name" class="form-control" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">No. of Way-bill, Issue Note, &c.</label>
                            <input type="text" name="ref_doc_no" id="edit_fuel_ref_doc_no" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded border border-success border-2">
                                <label class="form-label fw-bold text-success">Received (+)</label>
                                <input type="number" step="0.01" min="0" name="received_qty" id="edit_fuel_received_qty" class="form-control form-control-lg fw-bold text-success" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded border border-danger border-2">
                                <label class="form-label fw-bold text-danger">Issued (-)</label>
                                <input type="number" step="0.01" min="0" name="issued_qty" id="edit_fuel_issued_qty" class="form-control form-control-lg fw-bold text-danger" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 bg-white rounded border border-primary border-2">
                                <label class="form-label fw-bold text-primary">Recorded Balance</label>
                                <input type="number" step="0.01" id="edit_fuel_balance_qty" class="form-control form-control-lg fw-bold text-primary bg-light" readonly>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Remarks / Notes</label>
                            <input type="text" name="remarks" id="edit_fuel_remarks" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--color-c10, #185dbd);">
                        <i class="bi bi-check-circle-fill me-1"></i>Update Fuel Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 4: Edit Fuel Details Monthly Summary Record -->
<div class="modal fade" id="editMonthlyFuelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c10, #185dbd);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-pencil-square me-2"></i>Edit Monthly Fuel Summary
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/monthly_fuel_summary_crud.php" method="POST">
                <input type="hidden" name="id" id="edit_fuel_summary_id">
                
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Type of Fuel</label>
                        <input type="text" id="edit_fuel_type_display" class="form-control fw-bold bg-light" readonly>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Opening Stock <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="opening_stock" id="edit_fuel_opening_stock" class="form-control form-control-lg fw-bold fuel-summary-calc" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-success">Purchased (Auto from Fuel Register)</label>
                            <input type="number" step="0.01" id="edit_fuel_purchased" class="form-control form-control-lg fw-bold text-success bg-light fuel-summary-calc" readonly>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-danger">Consumption (Auto from Fuel Register)</label>
                            <input type="number" step="0.01" id="edit_fuel_consumption" class="form-control form-control-lg fw-bold text-danger bg-light fuel-summary-calc" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-primary">Balance (Auto Calculated)</label>
                            <input type="number" step="0.01" id="edit_fuel_summary_balance" class="form-control form-control-lg fw-bold text-primary bg-light" readonly>
                            <small class="text-muted">(Opening + Purchased) - Consumption</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks</label>
                        <input type="text" name="remarks" id="edit_fuel_summary_remarks" class="form-control" placeholder="Optional notes for this fuel type...">
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--color-c10, #185dbd);">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Monthly Summary
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
