<!-- pages/modules/farm/models/egg_sales_modals.php -->
<?php
if (!isset($cages)) {
    $cages = [];
    if (isset($mysqli)) {
        $cages_res = $mysqli->query("SELECT id, cage_name FROM cages ORDER BY cage_name");
        if ($cages_res) {
            while ($row = $cages_res->fetch_assoc()) {
                $cages[] = $row;
            }
        }
    }
}

if (!isset($batches)) {
    $batches = [];
    if (isset($mysqli) && isset($user_id)) {
        $batch_stmt = $mysqli->prepare("SELECT id, batch_number AS batch_name, created_at FROM vaccine_batches WHERE user_id = ? ORDER BY id DESC");
        if ($batch_stmt) {
            $batch_stmt->bind_param("i", $user_id);
            $batch_stmt->execute();
            $batch_res = $batch_stmt->get_result();
            if ($batch_res) {
                while ($row = $batch_res->fetch_assoc()) {
                    $batches[] = $row;
                }
            }
            $batch_stmt->close();
        }
    }
}
?>

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
                        
                        <!-- Automatic Collection Data Fetch Banner -->
                        <div class="col-12">
                            <div id="add_autofetch_status" class="alert alert-info py-2 px-3 small d-flex align-items-center justify-content-between mb-1 rounded-3 shadow-sm border-0" style="background-color: #e8f0fa; color: #185dbd;">
                                <div>
                                    <i class="bi bi-magic me-1 fw-bold"></i>
                                    <span id="add_autofetch_msg" class="fw-bold">Select Date, Cage, and Batch to automatically fetch egg collection data.</span>
                                </div>
                                <span id="add_autofetch_spinner" class="spinner-border spinner-border-sm text-primary" style="display: none;" role="status"></span>
                            </div>
                        </div>

                        <!-- General Info -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="sale_date" id="add_sale_date" class="form-control fw-bold" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Cage Name <span class="text-danger">*</span></label>
                            <select name="cage_id" id="add_cage_id" class="form-select fw-bold" required>
                                <option value="" disabled selected>-- Select Active Cage --</option>
                                <?php foreach ($cages ?? [] as $cg): ?>
                                    <option value="<?= $cg['id'] ?>"><?= htmlspecialchars($cg['cage_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Batch <span class="text-danger">*</span></label>
                            <select name="batch_id" id="add_batch_id" class="form-select fw-bold" required>
                                <option value="" disabled selected>-- Select Active Batch --</option>
                                <?php foreach ($batches ?? [] as $bt): ?>
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
                                <?php foreach ($cages ?? [] as $cg): ?>
                                    <option value="<?= $cg['id'] ?>"><?= htmlspecialchars($cg['cage_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Batch <span class="text-danger">*</span></label>
                            <select name="batch_id" id="edit_egg_sale_batch_id" class="form-select" required>
                                <option value="" disabled>-- Select Active Batch --</option>
                                <?php foreach ($batches ?? [] as $bt): ?>
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
