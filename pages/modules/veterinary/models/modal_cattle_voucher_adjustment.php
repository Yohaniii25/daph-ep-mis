<!-- Modal: Cattle Voucher Monthly Return (Automated Entry - Received Qty Only) -->
<div class="modal fade" id="modalCattleVoucherAdjustment" tabindex="-1" aria-labelledby="modalCattleVoucherAdjustmentLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color: #370709;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-receipt-cutoff fs-4 text-warning"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="modalCattleVoucherAdjustmentLabel">Cattle Voucher Monthly Return Entry</h6>
                        <small class="text-white-50">Manual entry is strictly restricted to Received Quantities; all other metrics are auto-computed from live records</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="formCattleVoucherAdjustment" action="processors/cattle_voucher_crud.php" method="POST">
                <input type="hidden" name="action" id="cv_action" value="create">
                <input type="hidden" name="id" id="cv_id" value="0">
                <input type="hidden" name="range_id" value="<?= htmlspecialchars((string)($range_id ?? 1)) ?>">
                <input type="hidden" name="redirect_to" value="ear_tag_balance.php?tab=vouchers&year=<?= $selected_year ?>">

                <div class="modal-body p-4">
                    <!-- Informative Banner -->
                    <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center gap-2 small">
                        <i class="bi bi-shield-check fs-5 text-primary"></i>
                        <div>
                            <strong>Statutory Monthly Return Automation:</strong> Per department guidelines, manual data entry is restricted <strong>strictly to Received Quantities</strong>. Opening balances, distributed serial blocks, used vouchers, and ending balances are fetched automatically from live farm registrations.
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Report Year</label>
                            <input type="number" name="report_year" id="cv_year" class="form-control form-control-sm bg-light" value="<?= $selected_year ?>" min="2000" max="2099" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Report Month</label>
                            <select name="report_month" id="cv_month" class="form-select form-select-sm" required>
                                <option value="1">January</option>
                                <option value="2">February</option>
                                <option value="3">March</option>
                                <option value="4">April</option>
                                <option value="5">May</option>
                                <option value="6">June</option>
                                <option value="7">July</option>
                                <option value="8">August</option>
                                <option value="9">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                        </div>

                        <div class="col-12"><hr class="my-1 text-muted"></div>

                        <!-- 1. Opening Balance (Automated Readonly) -->
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold text-dark mb-0">Opening Balance</label>
                                <span class="badge bg-secondary-subtle text-secondary border px-1" style="font-size: 0.65rem;">Auto-Computed</span>
                            </div>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-box-seam"></i></span>
                                <input type="number" name="opening_balance" id="cv_opening_balance" class="form-control bg-light font-monospace fw-bold" value="0" readonly tabindex="-1">
                            </div>
                            <small class="text-muted" style="font-size: 0.72rem;">Derived from prior month ending closing balance</small>
                        </div>

                        <!-- 2. Received Quantity (THE ONLY EDITABLE FIELD) -->
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold text-success mb-0">Received Quantity (+)</label>
                                <span class="badge bg-success text-white px-2" style="font-size: 0.68rem;"><i class="bi bi-pencil-fill me-1"></i>Manual Entry Allowed</span>
                            </div>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-success text-white"><i class="bi bi-plus-lg"></i></span>
                                <input type="number" name="received_qty" id="cv_received_qty" class="form-control cv-calc-trigger text-success fw-bold font-monospace border-success border-2" value="0" min="0" required placeholder="Enter quantity received" autofocus>
                            </div>
                            <small class="text-success fw-semibold" style="font-size: 0.72rem;">Voucher booklets physically received by office from HQ/District</small>
                        </div>

                        <!-- 3. Used Quantity (Live System Synced Readonly) -->
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold text-info mb-0">Used Vouchers (-)</label>
                                <span class="badge bg-info-subtle text-info border px-1" style="font-size: 0.65rem;">Live Registration Count</span>
                            </div>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-info"><i class="bi bi-check-circle-fill"></i></span>
                                <input type="number" name="used_qty" id="cv_used_qty" class="form-control bg-light text-info fw-bold font-monospace" value="0" readonly tabindex="-1">
                            </div>
                            <small class="text-muted" style="font-size: 0.72rem;">Calculated directly from live Animal Identification entries</small>
                        </div>

                        <!-- 4. Damaged Tags / Vouchers (Live System Synced Readonly) -->
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold text-danger mb-0">Damaged Tags / Vouchers (-)</label>
                                <span class="badge bg-danger-subtle text-danger border px-1" style="font-size: 0.65rem;">Permanent Pairing Retained</span>
                            </div>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-danger"><i class="bi bi-x-octagon-fill"></i></span>
                                <input type="number" name="damaged_qty" id="cv_damaged_qty" class="form-control bg-light text-danger fw-bold font-monospace" value="0" readonly tabindex="-1">
                            </div>
                            <small class="text-muted" style="font-size: 0.72rem;">Damaged tags removed from animals; voucher permanently retained</small>
                        </div>

                        <!-- Distributed Serial Numbers in Selected Month (Live Fetch) -->
                        <div class="col-12">
                            <div class="card border bg-light">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small fw-bold text-dark"><i class="bi bi-distribute-vertical me-1 text-primary"></i>Distributed Sequential Serial Blocks in this Month:</span>
                                        <span class="badge bg-primary-subtle text-primary border" id="cv_dist_count_badge">0 Blocks</span>
                                    </div>
                                    <div id="cv_distributed_blocks_container" class="small text-muted font-monospace py-1">
                                        <span class="fst-italic text-secondary">None allocated in this specific month, or loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Projected Month Ending Balance -->
                        <div class="col-12 mt-2">
                            <div class="p-3 rounded border bg-light d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="small fw-bold text-muted text-uppercase d-block">Automated Ending Balance</span>
                                    <small class="text-muted">Formula: (Opening + Received) - (Used + Damaged)</small>
                                </div>
                                <div class="text-end">
                                    <span class="h4 fw-bold font-monospace text-dark mb-0" id="cv_calc_ending_preview">0</span>
                                    <small class="text-muted d-block">Vouchers Available</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-sm btn-success px-4 fw-bold" id="btnSaveCvReturn">
                        <i class="bi bi-check2-circle me-1"></i> Save Monthly Return
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>