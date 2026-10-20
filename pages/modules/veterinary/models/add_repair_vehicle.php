<div class="modal fade" id="addRepairModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light text-dark">
                <h5 class="modal-title"><i class="bi bi-tools me-2"></i>Log Vehicle Repair Operation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addRepairForm" method="POST" action="processors/save_vehicle_repair.php" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Target Vehicle (Reference Plate &amp; Chassis)</label>
                            <select name="vehicle_id" id="add_repair_vehicle_id" class="form-select" required>
                                <option value="" disabled selected>-- Select target vehicle from fleet reference --</option>
                                <?php foreach($vehicles_cache as $v): ?>
                                    <option value="<?= $v['id'] ?>">Plate: <?= htmlspecialchars($v['vehicle_number']) ?> (Chassis: <?= htmlspecialchars($v['chassis_number'] ?? 'N/A') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Repair Date</label>
                            <input type="date" name="repair_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Repair Operation Done (Brief Header)</label>
                            <input type="text" name="repair_done" class="form-control" placeholder="e.g. Full Clutch Replacement, Brake Servicing" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Place of Repair (Workshop / Station)</label>
                            <input type="text" name="place_of_repair" class="form-control" placeholder="e.g. Saman Motors, Local Junction">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Invoice / Bill Reference No.</label>
                            <input type="text" name="invoice_ref" class="form-control" placeholder="e.g. INV-2026-0982">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Repair Cost / Transaction Amount (LKR)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold text-muted">LKR</span>
                                <input type="number" step="0.01" name="amount" id="add_repair_amount" class="form-control fw-bold text-dark text-end" placeholder="0.00" min="0" required>
                            </div>
                            <input type="hidden" name="transaction_amount" id="add_repair_transaction_amount">
                            <small class="text-muted" style="font-size:11px;">Dedicated transaction cost logged for financial auditing.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Digital Receipt Document Upload</label>
                            <input type="file" name="receipt_file" id="add_repair_receipt" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted" style="font-size:11px;">Accepted formats: PDF, JPG, PNG (Max 5MB).</small>
                        </div>

                        <!-- Tiered Maintenance Approval Routing Live Indicator -->
                        <div class="col-md-12">
                            <div id="add_repair_routing_badge" class="alert alert-light border rounded-3 d-flex align-items-center py-2 px-3 mb-0" style="transition: all 0.3s ease;">
                                <i class="bi bi-shield-check text-secondary fs-4 me-3" id="add_routing_icon"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-bold small" id="add_routing_title">Conditional Approval Workflow Routing</div>
                                    <div class="text-muted small" id="add_routing_desc">
                                        Costs &le; LKR 50,000 route to <strong>District Deputy Director</strong>. Costs &gt; LKR 50,000 route to <strong>Provincial Director</strong>.
                                    </div>
                                </div>
                                <span class="badge bg-secondary rounded-pill px-3 py-2" id="add_routing_pill">Pending Cost</span>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Detailed Description of Repair Action</label>
                            <textarea name="repair_description" class="form-control" rows="3" placeholder="Log broken component analysis details or replacement serial records..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn text-white px-4 shadow-sm" style="background-color: #b08723;">
                        <i class="bi bi-check-circle me-1"></i>Log Repair Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>