<div class="modal fade" id="addDairyModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">New Milk Collection Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/save_dairy_record.php" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Date of Collection</label>
                        <input type="date" name="collection_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Farmer Name / ID</label>
                        <input type="text" name="farmer_reg_no" class="form-control" placeholder="Enter farmer name" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Quantity (Liters)</label>
                            <input type="number" step="0.01" name="milk_quantity" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Price per Liter (Rs)</label>
                            <input type="number" step="0.01" name="price_per_liter" class="form-control" required>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Fat %</label>
                            <input type="number" step="0.01" name="fat_per" class="form-control" placeholder="0.00">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Solid Non-Fat %</label>
                            <input type="number" step="0.01" name="snf_per" class="form-control" placeholder="0.00">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="submit" class="btn btn-primary w-100">Save Collection Data</button>
                </div>
            </form>
        </div>
    </div>
</div>