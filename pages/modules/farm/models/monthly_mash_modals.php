<!-- pages/modules/farm/models/monthly_mash_modals.php -->

<!-- Edit Monthly Mash Stock Modal -->
<div class="modal fade" id="editMashModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="processors/monthly_mash_details_crud.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_mash_id">
                <div class="modal-header text-light" style="background-color: var(--color-c10, #185dbd);">
                    <h5 class="modal-header-title fw-bold m-0"><i class="bi bi-pencil-square me-2"></i>Edit Mash Stock Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Feed Type</label>
                            <input type="text" id="edit_mash_feed_type" class="form-control bg-light fw-bold" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Monthly Consumption (Auto-Summed from Daily Logs)</label>
                            <input type="number" step="0.01" id="edit_mash_consumption_kg" class="form-control bg-light fw-bold text-color-c6" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Opening Stock (kg)</label>
                            <input type="number" step="0.01" name="opening_stock_kg" id="edit_mash_opening_stock_kg" class="form-control mash-calc" min="0" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-color-c10">Received Stock (kg)</label>
                            <input type="number" step="0.01" name="received_kg" id="edit_mash_received_kg" class="form-control mash-calc" style="border-color: var(--color-c10);" min="0" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-color-c8">Issued to Other Farm (kg)</label>
                            <input type="number" step="0.01" name="issued_other_farm_kg" id="edit_mash_issued_other_farm_kg" class="form-control mash-calc" style="border-color: var(--color-c8);" min="0" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-color-c10">Balance Stock (Auto-Calculated kg)</label>
                            <input type="number" step="0.01" id="edit_mash_balance_stock_kg" class="form-control bg-light fw-bold text-color-c10" style="border-color: var(--color-c10);" readonly value="0.00">
                            <small class="text-muted">(Opening + Received) - (Consumption + Issued Other)</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Remarks / Notes</label>
                            <textarea name="remarks" id="edit_mash_remarks" class="form-control" rows="2" placeholder="e.g. Stock transfer notes..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold" style="background-color: var(--color-c10, #185dbd);">Update Stock Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function calcMashBalance() {
        const opening = parseFloat(document.getElementById('edit_mash_opening_stock_kg').value) || 0;
        const received = parseFloat(document.getElementById('edit_mash_received_kg').value) || 0;
        const consumption = parseFloat(document.getElementById('edit_mash_consumption_kg').value) || 0;
        const issuedOther = parseFloat(document.getElementById('edit_mash_issued_other_farm_kg').value) || 0;

        const balance = (opening + received) - (consumption + issuedOther);
        document.getElementById('edit_mash_balance_stock_kg').value = balance.toFixed(2);
    }

    const calcInputs = document.querySelectorAll('.mash-calc');
    calcInputs.forEach(function(input) {
        input.addEventListener('input', calcMashBalance);
    });

    const mashModal = document.getElementById('editMashModal');
    if (mashModal) {
        mashModal.addEventListener('shown.bs.modal', calcMashBalance);
    }
});
</script>
