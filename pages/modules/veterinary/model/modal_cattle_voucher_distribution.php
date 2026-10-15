<!-- Modal: Assign Sequential Cattle Voucher Block to Farmer -->
<div class="modal fade" id="modalVoucherDistribution" tabindex="-1" aria-labelledby="modalVoucherDistributionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color: #370709;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-distribute-vertical fs-4 text-warning"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="modalVoucherDistributionLabel">Assign Sequential Cattle Voucher Block</h6>
                        <small class="text-white-50">Assign continuous serial number blocks (e.g. 59 to 100) to specific registered farmers</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="formVoucherDistribution">
                <input type="hidden" name="action" value="create_distribution">
                <input type="hidden" name="range_id" value="<?= htmlspecialchars((string)($range_id ?? 1)) ?>">
                <input type="hidden" name="district_id" value="<?= htmlspecialchars((string)($district_id ?? 1)) ?>">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Farmer Search & Details -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Farm Registration No. <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-card-checklist"></i></span>
                                <input type="text" name="farm_reg_no" id="dist_farm_reg_no" class="form-control" placeholder="e.g. EP/AMP/01/2026" required list="distFarmersList">
                            </div>
                            <datalist id="distFarmersList">
                                <?php foreach ($registered_farmers as $rf): ?>
                                    <option value="<?= htmlspecialchars($rf['farm_reg_no']) ?>"><?= htmlspecialchars($rf['farmer_name']) ?></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Farmer / Owner Name <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                <input type="text" name="farmer_name" id="dist_farmer_name" class="form-control" placeholder="e.g. K. S. Perera" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Farmer NIC</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-person-vcard"></i></span>
                                <input type="text" name="farmer_nic" id="dist_farmer_nic" class="form-control" placeholder="e.g. 198512345678">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Distribution Date <span class="text-danger">*</span></label>
                            <input type="date" name="distribution_date" id="dist_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-12"><hr class="my-1 text-muted"></div>

                        <!-- Sequential Serial Range Configuration -->
                        <div class="col-12">
                            <h6 class="fw-bold text-dark mb-1 small text-uppercase"><i class="bi bi-123 me-1 text-primary"></i>Sequential Serial Range Configuration</h6>
                            <p class="text-muted small mb-2">Enter the starting and ending serial numbers. Prefix is strictly standardized to A-.</p>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Voucher Prefix</label>
                            <input type="text" name="prefix" id="dist_prefix" class="form-control form-control-sm bg-light font-monospace fw-bold text-center" value="A-" readonly>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-primary">Start Serial No. <span class="text-danger">*</span></label>
                            <input type="number" name="start_serial" id="dist_start_serial" class="form-control form-control-sm font-monospace fw-bold dist-calc-trigger" placeholder="e.g. 59" min="1" required>
                            <small class="text-muted" style="font-size: 0.72rem;">e.g. 59 (Formats to A-000059)</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-primary">End Serial No. <span class="text-danger">*</span></label>
                            <input type="number" name="end_serial" id="dist_end_serial" class="form-control form-control-sm font-monospace fw-bold dist-calc-trigger" placeholder="e.g. 100" min="1" required>
                            <small class="text-muted" style="font-size: 0.72rem;">e.g. 100 (Formats to A-000100)</small>
                        </div>

                        <div class="col-md-1 d-flex align-items-center justify-content-center pt-3">
                            <i class="bi bi-arrow-right fs-4 text-muted"></i>
                        </div>

                        <!-- Real-time Preview Banner -->
                        <div class="col-12">
                            <div class="p-3 rounded border bg-light d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="small fw-bold text-muted text-uppercase d-block">Allocated Serial Block Preview</span>
                                    <span class="h5 fw-bold font-monospace text-primary mb-0" id="dist_range_preview">A-000000 – A-000000</span>
                                </div>
                                <div class="text-end">
                                    <span class="small fw-bold text-muted text-uppercase d-block">Total Vouchers</span>
                                    <span class="h4 fw-bold font-monospace text-dark mb-0" id="dist_total_preview">0</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Remarks / Instructions</label>
                            <textarea name="remarks" id="dist_remarks" class="form-control form-control-sm" rows="2" placeholder="Optional notes regarding this block issuance..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success px-4 fw-bold" id="btnSubmitDist">
                        <i class="bi bi-check2-circle me-1"></i> Confirm &amp; Assign Block
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: View Block Leaf Breakdown & Consumption Ledger -->
<div class="modal fade" id="modalBlockLeafLedger" tabindex="-1" aria-labelledby="modalBlockLeafLedgerLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-3" style="background-color: #370709;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-card-list fs-4 text-warning"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="modalBlockLeafLedgerLabel">Sequential Voucher Block Consumption Ledger</h6>
                        <small class="text-white-50" id="blockLedgerSubTitle">Loading block details...</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <!-- KPI Header for Block -->
                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <div class="p-2 border rounded bg-light">
                            <small class="text-muted d-block">Farmer / Owner</small>
                            <span class="fw-bold text-dark" id="blk_farmer_name">—</span>
                            <small class="text-muted d-block font-monospace" id="blk_farm_reg_no">—</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-2 border rounded bg-light">
                            <small class="text-muted d-block">Serial Range</small>
                            <span class="fw-bold font-monospace text-primary" id="blk_range_display">—</span>
                            <small class="text-muted d-block" id="blk_dist_date">—</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="p-2 border rounded bg-light text-center">
                            <small class="text-muted d-block">Total Allocated</small>
                            <span class="h5 fw-bold font-monospace text-dark" id="blk_total_qty">0</span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="p-2 border rounded bg-info-subtle text-center">
                            <small class="text-info-emphasis d-block fw-semibold">Used / Tagged</small>
                            <span class="h5 fw-bold font-monospace text-info" id="blk_used_qty">0</span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="p-2 border rounded bg-success-subtle text-center">
                            <small class="text-success-emphasis d-block fw-semibold">Remaining Available</small>
                            <span class="h5 fw-bold font-monospace text-success" id="blk_rem_qty">0</span>
                        </div>
                    </div>
                </div>

                <!-- Table of individual serial vouchers in the block -->
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-bordered table-hover align-middle mb-0" id="tblBlockLeaves">
                        <thead class="table-dark sticky-top small text-uppercase">
                            <tr>
                                <th style="width: 14%;">Voucher Serial</th>
                                <th style="width: 22%;">Status &amp; Lifecycle</th>
                                <th style="width: 20%;">Paired Ear Tag</th>
                                <th style="width: 26%;">Animal Particulars</th>
                                <th style="width: 18%;">Issue Date</th>
                            </tr>
                        </thead>
                        <tbody id="tblBlockLeavesBody">
                            <tr><td colspan="5" class="text-center py-4 text-muted">Loading serial numbers...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer bg-light border-0 py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
