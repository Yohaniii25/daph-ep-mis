<div class="modal fade" id="addProductionModal" tabindex="-1" aria-labelledby="addProductionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="#" method="POST">
                <div class="modal-header bg-dark text-white py-3">
                    <h5 style="color: white;" class="modal-title fw-bold" id="addProductionModalLabel">
                        <i class="bi bi-patch-plus me-2 text-success"></i>Log New Production Output Run
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-close="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-secondary">Production Item Name / Description <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="production_item" placeholder="e.g., Pasteurized Whole Milk, Layer Poultry Feed, Curd Packets" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Yield Output Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control font-monospace" name="amount" placeholder="0.00" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Measurement Metric Unit <span class="text-danger">*</span></label>
                            <select class="form-select" name="amount_unit" required>
                                <option value="" disabled selected>-- Select Measurement Metric --</option>
                                <option value="Ltr">Liters (Ltr)</option>
                                <option value="Kg">Kilograms (Kg)</option>
                                <option value="Units">Units / Packets</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Regional Veterinary Range Station <span class="text-danger">*</span></label>
                            <select class="form-select" name="station_location" required>
                                <option value="" disabled selected>-- Choose Processing Range Venue --</option>
                                <option value="Ampara">Ampara Veterinary Center</option>
                                <option value="Trincomalee">Trincomalee Field Station</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Production Execution Run Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="run_date" required>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light py-3">
                    <button type="button" class="btn btn-secondary px-4" data-bs-close="modal">Cancel</button>
                    <button type="button" class="btn btn-success px-4 fw-bold shadow-sm" data-bs-close="modal">Log Production Run (Demo)</button>
                </div>
            </form>
        </div>
    </div>
</div>