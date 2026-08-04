<!-- pages/modules/farm/models/day_old_distribution_modals.php -->

<!-- Add Day-Old Distribution Modal -->
<div class="modal fade" id="addDayOldModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="processors/day_old_distribution_crud.php" method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header text-white" style="background-color: #0d6efd;">
                    <h5 class="modal-header-title fw-bold m-0"><i class="bi bi-box-arrow-up-right me-2"></i>Log Day-Old Chicks Distribution</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Record Date <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">No. of Chicks Produced</label>
                            <input type="number" name="no_of_chicks_produced" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Sent to Place / Destination <span class="text-danger">*</span></label>
                            <input type="text" name="sent_to_place" class="form-control" placeholder="e.g. Trincomalee, Colombo, Farm X" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-primary">No. of Chicks Sent <span class="text-danger">*</span></label>
                            <input type="number" name="no_of_chicks_sent" id="add_day_old_sent" class="form-control calc-day-old fw-bold border-primary" min="0" value="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Price Per Chick (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price_per_chick" id="add_day_old_price" class="form-control calc-day-old fw-bold" min="0" value="0.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-success">Total Amount (Rs.)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-success text-white">Rs.</span>
                                <input type="text" id="add_day_old_total" class="form-control bg-light fw-bold text-success" readonly value="0.00">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Save Day-Old Distribution</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Day-Old Distribution Modal -->
<div class="modal fade" id="editDayOldModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="processors/day_old_distribution_crud.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_day_old_id">
                <div class="modal-header text-white" style="background-color: #0d6efd;">
                    <h5 class="modal-header-title fw-bold m-0"><i class="bi bi-pencil-square me-2"></i>Edit Day-Old Chicks Distribution</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Record Date <span class="text-danger">*</span></label>
                            <input type="date" name="record_date" id="edit_day_old_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">No. of Chicks Produced</label>
                            <input type="number" name="no_of_chicks_produced" id="edit_day_old_produced" class="form-control" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Sent to Place / Destination <span class="text-danger">*</span></label>
                            <input type="text" name="sent_to_place" id="edit_day_old_place" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-primary">No. of Chicks Sent <span class="text-danger">*</span></label>
                            <input type="number" name="no_of_chicks_sent" id="edit_day_old_sent" class="form-control edit-calc-day-old fw-bold border-primary" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Price Per Chick (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price_per_chick" id="edit_day_old_price" class="form-control edit-calc-day-old fw-bold" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-success">Total Amount (Rs.)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-success text-white">Rs.</span>
                                <input type="text" id="edit_day_old_total" class="form-control bg-light fw-bold text-success" readonly value="0.00">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Update Day-Old Distribution</button>
                </div>
            </form>
        </div>
    </div>
</div>
