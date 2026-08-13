<!-- Add New Income Receipt Modal -->
<div class="modal fade" id="addIncomeReceiptModal" tabindex="-1" aria-labelledby="addIncomeReceiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom py-3 px-4" style="background-color: #370709; color: white;">
                <h5 class="modal-title fw-bold" id="addIncomeReceiptModalLabel">
                    <i class="bi bi-receipt-cutoff me-2"></i>Record New Training Income Receipt
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="./processors/income_receipt_crud.php" method="POST">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="training_center_id" value="<?= htmlspecialchars($current_center_id ?? '') ?>">

                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 px-3 small border-0 shadow-sm mb-3">
                        <i class="bi bi-info-circle-fill me-1"></i> Recording a receipt automatically updates the <strong>Monthly Income Summary</strong> matrix for 
                        <strong><?= htmlspecialchars($current_training_center['center_name'] ?? 'Training Centre') ?></strong>.
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Receipt Date <span class="text-danger">*</span></label>
                            <input type="date" name="receipt_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Receipt / Reference No. <span class="text-danger">*</span></label>
                            <input type="text" name="receipt_no" class="form-control" placeholder="e.g. CR-2026/0891" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Income Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <option value="" disabled selected>-- Select Income Category --</option>
                                <optgroup label="Facilitation Services">
                                    <option value="accommodation">Accommodation</option>
                                    <option value="hall_charge">Hall Charge</option>
                                    <option value="usage_multimedia">Usage of Multimedia</option>
                                    <option value="usage_sound_system">Usage of Sound System</option>
                                </optgroup>
                                <optgroup label="Sales Categories">
                                    <option value="sales_grass">Sales: Grass</option>
                                    <option value="sales_banana">Sales: Banana</option>
                                    <option value="sales_vegetable">Sales: Vegetable</option>
                                    <option value="sales_coconut">Sales: Coconut</option>
                                    <option value="sales_bag">Sales: Bag</option>
                                    <option value="sales_tamarind">Sales: Tamarind</option>
                                    <option value="sales_pasture_cuttings">Sales: Pasture Cuttings</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Amount (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control font-monospace" placeholder="0.00" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Received From / Payer Name</label>
                            <input type="text" name="payer_name" class="form-control" placeholder="e.g. Department / Client / Trainee Name">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Remarks / Particulars</label>
                            <input type="text" name="remarks" class="form-control" placeholder="Optional notes or details...">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-top py-3 px-4">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light rounded-3 px-4 fw-semibold" style="background-color: #370709;">
                        <i class="bi bi-check-circle me-1"></i> Save Receipt
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Receipt Modal -->
<div class="modal fade" id="editIncomeReceiptModal" tabindex="-1" aria-labelledby="editIncomeReceiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom py-3 px-4 bg-dark text-light">
                <h5 class="modal-title fw-bold" id="editIncomeReceiptModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit Training Income Receipt
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="./processors/income_receipt_crud.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_receipt_id">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Receipt Date <span class="text-danger">*</span></label>
                            <input type="date" name="receipt_date" id="edit_receipt_date" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Receipt / Reference No. <span class="text-danger">*</span></label>
                            <input type="text" name="receipt_no" id="edit_receipt_no" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Income Category <span class="text-danger">*</span></label>
                            <select name="category" id="edit_category" class="form-select" required>
                                <option value="" disabled>-- Select Income Category --</option>
                                <optgroup label="Facilitation Services">
                                    <option value="accommodation">Accommodation</option>
                                    <option value="hall_charge">Hall Charge</option>
                                    <option value="usage_multimedia">Usage of Multimedia</option>
                                    <option value="usage_sound_system">Usage of Sound System</option>
                                </optgroup>
                                <optgroup label="Sales Categories">
                                    <option value="sales_grass">Sales: Grass</option>
                                    <option value="sales_banana">Sales: Banana</option>
                                    <option value="sales_vegetable">Sales: Vegetable</option>
                                    <option value="sales_coconut">Sales: Coconut</option>
                                    <option value="sales_bag">Sales: Bag</option>
                                    <option value="sales_tamarind">Sales: Tamarind</option>
                                    <option value="sales_pasture_cuttings">Sales: Pasture Cuttings</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Amount (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" id="edit_amount" class="form-control font-monospace" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Received From / Payer Name</label>
                            <input type="text" name="payer_name" id="edit_payer_name" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Remarks / Particulars</label>
                            <input type="text" name="remarks" id="edit_remarks" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-top py-3 px-4">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark rounded-3 px-4 fw-semibold">
                        <i class="bi bi-check-circle me-1"></i> Update Receipt
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
