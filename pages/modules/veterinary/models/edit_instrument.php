<div class="modal fade" id="editInstrumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #003ddc;">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Instrument Asset Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editInstrumentForm" action="processors/update_instrument.php" method="POST">
                <input type="hidden" name="id" id="edit_instrument_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Instrument Type</label>
                            <input type="text" name="instrument_type" id="edit_instrument_type" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Condition</label>
                            <select name="current_condition" id="edit_instrument_condition" class="form-select" required>
                                <option value="Good">Good</option>
                                <option value="Operational">Operational</option>
                                <option value="Needs Repair">Needs Repair</option>
                                <option value="Unserviceable">Unserviceable</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Available Quantity</label>
                            <input type="number" name="available_quantity" id="edit_instrument_quantity" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Date of Purchase / Received</label>
                            <input type="date" name="purchase_date" id="edit_instrument_purchase_date" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Remarks</label>
                            <textarea name="remarks" id="edit_instrument_remarks" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light" style="background-color: #003ddc;">Update Record</button>
                </div>
            </form>
        </div>
    </div>
</div>
