<!-- pages/modules/farm/models/egg_sales_modals.php -->

<!-- Add Sales of Eggs Modal -->
<div class="modal fade" id="addEggSalesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c1, #820100);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-egg-fried me-2"></i>Log Sales of Eggs Entry
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/egg_sales_crud.php" method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        
                        <!-- Import / Auto-fill from Parent Stock Daily Collection -->
                        <div class="col-12">
                            <div class="card border-primary-subtle bg-primary-subtle p-3 rounded-3 mb-2">
                                <label class="form-label fw-bold text-primary mb-1">
                                    <i class="bi bi-box-arrow-in-down me-1"></i>Import Eggs from Parent Stock Collection (Daily Egg Register)
                                </label>
                                <select id="add_select_collection" class="form-select border-primary fw-bold shadow-sm">
                                    <option value="" selected>-- Select Daily Collection Entry (Optional Auto-Fill) --</option>
                                    <?php if (!empty($collections_data)): ?>
                                        <?php foreach ($collections_data as $col): ?>
                                            <option value="<?= $col['id'] ?>"
                                                    data-date="<?= htmlspecialchars($col['collection_date']) ?>"
                                                    data-cage="<?= $col['cage_id'] ?>"
                                                    data-batch="<?= $col['batch_id'] ?>"
                                                    data-table-no="<?= $col['table_eggs'] ?>"
                                                    data-table-kg="<?= $col['table_eggs_kg'] ?>"
                                                    data-cracked-no="<?= $col['cracked_eggs'] ?>"
                                                    data-cracked-kg="<?= $col['cracked_eggs_kg'] ?>">
                                                [<?= date('d-M-Y', strtotime($col['collection_date'])) ?>] Batch: <?= htmlspecialchars($col['batch_name']) ?> | Cage: <?= htmlspecialchars($col['cage_name']) ?> &mdash; Table Eggs: <?= number_format($col['table_eggs']) ?> NO (<?= number_format($col['table_eggs_kg'], 2) ?> Kg), Cracked: <?= number_format($col['cracked_eggs']) ?> NO (<?= number_format($col['cracked_eggs_kg'], 2) ?> Kg)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <small class="text-muted mt-1 d-block"><i class="bi bi-info-circle me-1"></i>Selecting an entry populates the date, cage, batch, table eggs count & weight, and cracked eggs count & weight from Parent Stock Operations.</small>
                            </div>
                        </div>

                        <!-- General Info -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="sale_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Cage Name <span class="text-danger">*</span></label>
                            <select name="cage_id" class="form-select" required>
                                <option value="" disabled selected>-- Select Active Cage --</option>
                                <?php foreach ($cages as $cg): ?>
                                    <option value="<?= $cg['id'] ?>"><?= htmlspecialchars($cg['cage_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Batch <span class="text-danger">*</span></label>
                            <select name="batch_id" class="form-select" required>
                                <option value="" disabled selected>-- Select Active Batch --</option>
                                <?php foreach ($batches as $bt): ?>
                                    <option value="<?= $bt['id'] ?>"><?= htmlspecialchars($bt['batch_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Table Eggs Section -->
                        <div class="col-12"><hr class="my-2"></div>
                        <div class="col-12">
                            <h6 class="fw-bold text-primary"><i class="bi bi-egg me-2"></i>Table Eggs Section</h6>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">No. of Table Eggs</label>
                            <input type="number" name="table_eggs_no" id="add_table_eggs_no" class="form-control calc-table-egg" min="0" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Weight (Kg)</label>
                            <input type="number" step="0.01" name="table_eggs_kg" id="add_table_eggs_kg" class="form-control calc-table-egg" min="0" value="0.00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Unit Price (LKR)</label>
                            <input type="number" step="0.01" name="table_eggs_unit_price" id="add_table_eggs_unit_price" class="form-control calc-table-egg" min="0" value="0.00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-success">Total Sales (LKR)</label>
                            <input type="number" step="0.01" name="table_eggs_total_sales" id="add_table_eggs_total_sales" class="form-control fw-bold bg-light text-success" value="0.00" readonly>
                        </div>

                        <!-- Cracked Eggs Section -->
                        <div class="col-12"><hr class="my-2"></div>
                        <div class="col-12">
                            <h6 class="fw-bold text-warning"><i class="bi bi-egg-fill me-2"></i>Cracked Eggs Section</h6>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">No. of Cracked Eggs</label>
                            <input type="number" name="cracked_eggs_no" id="add_cracked_eggs_no" class="form-control calc-cracked-egg" min="0" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Weight (Kg)</label>
                            <input type="number" step="0.01" name="cracked_eggs_kg" id="add_cracked_eggs_kg" class="form-control calc-cracked-egg" min="0" value="0.00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Unit Price (LKR)</label>
                            <input type="number" step="0.01" name="cracked_eggs_unit_price" id="add_cracked_eggs_unit_price" class="form-control calc-cracked-egg" min="0" value="0.00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-warning">Total Sale (LKR)</label>
                            <input type="number" step="0.01" name="cracked_eggs_total_sales" id="add_cracked_eggs_total_sales" class="form-control fw-bold bg-light text-warning" value="0.00" readonly>
                        </div>

                        <!-- Remarks -->
                        <div class="col-12 mt-3">
                            <label class="form-label fw-bold">Remarks / Notes</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                        </div>

                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--color-c1, #820100);">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Egg Sales Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Sales of Eggs Modal -->
<div class="modal fade" id="editEggSalesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c10, #185dbd);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-pencil-square me-2"></i>Edit Egg Sales Entry
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/egg_sales_crud.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_egg_sale_id">
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        
                        <!-- General Info -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="sale_date" id="edit_egg_sale_date" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Cage Name <span class="text-danger">*</span></label>
                            <select name="cage_id" id="edit_egg_sale_cage_id" class="form-select" required>
                                <option value="" disabled>-- Select Active Cage --</option>
                                <?php foreach ($cages as $cg): ?>
                                    <option value="<?= $cg['id'] ?>"><?= htmlspecialchars($cg['cage_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Batch <span class="text-danger">*</span></label>
                            <select name="batch_id" id="edit_egg_sale_batch_id" class="form-select" required>
                                <option value="" disabled>-- Select Active Batch --</option>
                                <?php foreach ($batches as $bt): ?>
                                    <option value="<?= $bt['id'] ?>"><?= htmlspecialchars($bt['batch_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Table Eggs Section -->
                        <div class="col-12"><hr class="my-2"></div>
                        <div class="col-12">
                            <h6 class="fw-bold text-primary"><i class="bi bi-egg me-2"></i>Table Eggs Section</h6>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">No. of Table Eggs</label>
                            <input type="number" name="table_eggs_no" id="edit_table_eggs_no" class="form-control edit-calc-table-egg" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Weight (Kg)</label>
                            <input type="number" step="0.01" name="table_eggs_kg" id="edit_table_eggs_kg" class="form-control edit-calc-table-egg" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Unit Price (LKR)</label>
                            <input type="number" step="0.01" name="table_eggs_unit_price" id="edit_table_eggs_unit_price" class="form-control edit-calc-table-egg" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-success">Total Sales (LKR)</label>
                            <input type="number" step="0.01" name="table_eggs_total_sales" id="edit_table_eggs_total_sales" class="form-control fw-bold bg-light text-success" readonly>
                        </div>

                        <!-- Cracked Eggs Section -->
                        <div class="col-12"><hr class="my-2"></div>
                        <div class="col-12">
                            <h6 class="fw-bold text-warning"><i class="bi bi-egg-fill me-2"></i>Cracked Eggs Section</h6>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">No. of Cracked Eggs</label>
                            <input type="number" name="cracked_eggs_no" id="edit_cracked_eggs_no" class="form-control edit-calc-cracked-egg" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Weight (Kg)</label>
                            <input type="number" step="0.01" name="cracked_eggs_kg" id="edit_cracked_eggs_kg" class="form-control edit-calc-cracked-egg" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Unit Price (LKR)</label>
                            <input type="number" step="0.01" name="cracked_eggs_unit_price" id="edit_cracked_eggs_unit_price" class="form-control edit-calc-cracked-egg" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-warning">Total Sale (LKR)</label>
                            <input type="number" step="0.01" name="cracked_eggs_total_sales" id="edit_cracked_eggs_total_sales" class="form-control fw-bold bg-light text-warning" readonly>
                        </div>

                        <!-- Remarks -->
                        <div class="col-12 mt-3">
                            <label class="form-label fw-bold">Remarks / Notes</label>
                            <textarea name="remarks" id="edit_egg_sale_remarks" class="form-control" rows="2"></textarea>
                        </div>

                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--color-c10, #185dbd);">
                        <i class="bi bi-check-circle-fill me-1"></i>Update Egg Sales Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
