<div class="modal fade" id="addVaccineBatchModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <form id="batchForm" class="modal-content border-0 shadow-lg" action="processors/vaccine_batch_crud.php" method="POST">
            <input type="hidden" name="action" id="modalAction" value="create">
            <input type="hidden" name="id" id="batchId" value="">
            
            <div class="modal-header bg-white text-dark border-bottom-0">
                <h5 class="modal-title fw-bold" id="modalTitle"><i class="bi bi-box-seam me-2"></i>Register New Vaccine Stock Batch</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body">
                <div class="mb-3" id="vaccineTypeContainer">
                    <label class="form-label fw-bold text-dark">Linked Vaccine Nomenclature Profile</label>
                    <select name="vaccine_type_id" id="vaccineTypeId" class="form-select" required>
                        <option value="" disabled selected>-- Select Registered Vaccine Classification --</option>
                        <?php
                        $types_query = "SELECT id, vaccine_name FROM vaccine_types ORDER BY vaccine_name ASC";
                        $types_res = $mysqli->query($types_query);
                        while($t = $types_res->fetch_assoc()) {
                            echo "<option value='{$t['id']}'>" . htmlspecialchars($t['vaccine_name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-dark">Batch Identification Reference String</label>
                    <input type="text" name="batch_number" id="batchNumber" class="form-control" placeholder="e.g. BATCH-2026-FMD-09" required>
                </div>

                <div id="initialAllocationWrapper" class="mb-3">
                    <label class="form-label fw-bold text-dark">Initial Allocated Doses (Opening Balance)</label>
                    <input type="number" name="initial_allocated_doses" id="initialAllocatedDoses" class="form-control" min="1" placeholder="e.g. 1500" required>
                </div>

                <div id="ledgerAdjustmentsWrapper" class="d-none border rounded p-3 bg-light mb-3">
                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3"><i class="bi bi-arrow-left-right me-1"></i>Log Inventory Adjustments</h6>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-success">Add Mid-Month Supply</label>
                            <input type="number" name="mid_month_arrival" id="midMonthArrival" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-danger">Log New Damaged Doses</label>
                            <input type="number" name="new_damaged" id="newDamaged" class="form-control" min="0" value="0">
                        </div>
                    </div>
                    <div class="mt-2 text-muted" style="font-size: 0.82rem;">
                        Leave at 0 if you are only modifying baseline parameters like expiration data elements.
                    </div>
                </div>

                <div class="row g-3 mb-2">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark">Manufacturer Label</label>
                        <input type="text" name="manufacturer" id="manufacturer" class="form-control" placeholder="e.g. State Bio-Labs">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark">Product Expiration Date</label>
                        <input type="date" name="expiry_date" id="expiryDate" class="form-control" required>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" id="submitBtn" class="btn btn-success fw-bold px-4">Save Configuration</button>
            </div>
        </form>
    </div>
</div>