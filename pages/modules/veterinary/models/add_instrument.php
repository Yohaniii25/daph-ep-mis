<div class="modal fade" id="addInstrumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #003ddc;">
                <h5 class="modal-title"><i class="bi bi-scissors me-2"></i>New Instrument Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addInstrumentForm">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Type</label>
                            <input type="text" name="instrument_type" class="form-control" placeholder="e.g., Surgical Kit, Vaginal Speculum, Microscope Set" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Condition</label>
                            <select name="current_condition" class="form-select" required>
                                <option value="Good">Good</option>
                                <option value="Needs Repair">Needs Repair</option>
                                <option value="Unserviceable">Unserviceable</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Available Quantity</label>
                            <input type="number" name="available_quantity" class="form-control" min="1" value="1" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Date of Purchase / Received</label>
                            <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="3" placeholder="Add sterilization notes, specific location storage cabinets or donor info..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light" style="background-color: #003ddc;">Save Instrument</button>
                </div>
            </form>
        </div>
    </div>
</div>