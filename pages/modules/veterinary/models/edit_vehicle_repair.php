<div class="modal fade" id="editRepairModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Maintenance &amp; Repair Log</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editRepairForm" action="processors/update_vehicle_repair.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit_repair_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Select Registered Vehicle</label>
                            <select name="vehicle_id" id="edit_repair_vehicle_id" class="form-select" required>
                                <option value="" disabled selected>-- Select Vehicle --</option>
                                <?php foreach ($vehicles_cache as $veh): ?>
                                    <option value="<?= $veh['id'] ?>"><?= htmlspecialchars($veh['vehicle_number']) ?> (<?= htmlspecialchars($veh['vehicle_type'] ?? '') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Repair Date</label>
                            <input type="date" name="repair_date" id="edit_repair_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Repair Work Executed</label>
                            <input type="text" name="repair_done" id="edit_repair_done" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Place / Garage of Repair</label>
                            <input type="text" name="place_of_repair" id="edit_place_of_repair" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Invoice / Bill Reference No.</label>
                            <input type="text" name="invoice_ref" id="edit_invoice_ref" class="form-control" placeholder="e.g. INV-2026-0982">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Repair Cost / Transaction Amount (LKR)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold text-muted">LKR</span>
                                <input type="number" step="0.01" name="amount" id="edit_repair_amount" class="form-control fw-bold text-dark text-end" required>
                            </div>
                            <input type="hidden" name="transaction_amount" id="edit_repair_transaction_amount">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Digital Receipt Document</label>
                            <input type="file" name="receipt_file" id="edit_repair_receipt" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            <div id="edit_receipt_preview_container" class="mt-1 small"></div>
                            <small class="text-muted" style="font-size:11px;">Leave empty to keep the currently attached receipt file.</small>
                        </div>

                        <!-- Tiered Maintenance Approval Routing Live Indicator -->
                        <div class="col-md-12">
                            <div id="edit_repair_routing_badge" class="alert alert-light border rounded-3 d-flex align-items-center py-2 px-3 mb-0" style="transition: all 0.3s ease;">
                                <i class="bi bi-shield-check text-secondary fs-4 me-3" id="edit_routing_icon"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-bold small" id="edit_routing_title">Approval Routing Tier</div>
                                    <div class="text-muted small" id="edit_routing_desc">
                                        Costs &le; LKR 50,000 route to <strong>District Deputy Director</strong>. Costs &gt; LKR 50,000 route to <strong>Provincial Director</strong>.
                                    </div>
                                </div>
                                <span class="badge bg-secondary rounded-pill px-3 py-2" id="edit_routing_pill">-</span>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Detailed Repair Description</label>
                            <textarea name="repair_description" id="edit_repair_description" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-dark px-4 shadow-sm">
                        <i class="bi bi-check-circle me-1"></i>Update Repair Log
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
