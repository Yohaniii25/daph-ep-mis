<!-- pages/modules/farm/models/month_old_distribution_modals.php -->

<!-- Add Month-Old Distribution Modal -->
<div class="modal fade" id="addMonthOldModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="processors/month_old_distribution_crud.php" method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header text-white" style="background-color: #198754;">
                    <h5 class="modal-header-title fw-bold m-0"><i class="bi bi-truck me-2"></i>Log Month-Old Chicks Distribution</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Record Date <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Source Cage (Data Link)</label>
                            <select name="cage_id" id="add_month_old_cage_id" class="form-select">
                                <option value="">-- Select Source Cage --</option>
                                <?php foreach ($cages as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['cage_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary">No. of Chicks Produced (Surviving Balance)</label>
                            <input type="number" name="no_of_chicks_produced" id="add_month_old_produced" class="form-control" min="0" value="0">
                            <small class="text-muted">Auto-populated from growth log surviving balance when cage is selected.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Sent to Place / Destination <span class="text-danger">*</span></label>
                            <input type="text" name="sent_to_place" class="form-control" placeholder="e.g. Trincomalee, Ampara, Batticaloa" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-success">No. of Chicks Sent <span class="text-danger">*</span></label>
                            <input type="number" name="no_of_chicks_sent" id="add_month_old_sent" class="form-control calc-month-old fw-bold border-success" min="0" value="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Price Per Chick (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price_per_chick" id="add_month_old_price" class="form-control calc-month-old fw-bold" min="0" value="0.00" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold text-success">Total Amount (Rs.)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-success text-white">Rs.</span>
                                <input type="text" id="add_month_old_total" class="form-control bg-light fw-bold text-success fs-5" readonly value="0.00">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold">Save Month-Old Distribution</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Month-Old Distribution Modal -->
<div class="modal fade" id="editMonthOldModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="processors/month_old_distribution_crud.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_month_old_id">
                <div class="modal-header text-white" style="background-color: #198754;">
                    <h5 class="modal-header-title fw-bold m-0"><i class="bi bi-pencil-square me-2"></i>Edit Month-Old Chicks Distribution</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Record Date <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" id="edit_month_old_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Source Cage</label>
                            <select name="cage_id" id="edit_month_old_cage_id" class="form-select">
                                <option value="">-- Select Source Cage --</option>
                                <?php foreach ($cages as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['cage_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">No. of Chicks Produced</label>
                            <input type="number" name="no_of_chicks_produced" id="edit_month_old_produced" class="form-control" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Sent to Place / Destination <span class="text-danger">*</span></label>
                            <input type="text" name="sent_to_place" id="edit_month_old_place" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-success">No. of Chicks Sent <span class="text-danger">*</span></label>
                            <input type="number" name="no_of_chicks_sent" id="edit_month_old_sent" class="form-control edit-calc-month-old fw-bold border-success" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Price Per Chick (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price_per_chick" id="edit_month_old_price" class="form-control edit-calc-month-old fw-bold" min="0" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold text-success">Total Amount (Rs.)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-success text-white">Rs.</span>
                                <input type="text" id="edit_month_old_total" class="form-control bg-light fw-bold text-success fs-5" readonly value="0.00">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold">Update Month-Old Distribution</button>
                </div>
            </form>
        </div>
    </div>
</div>
