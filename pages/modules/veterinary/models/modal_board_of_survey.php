<!-- Board of Survey Formal Removal Modal -->
<div class="modal fade" id="boardOfSurveyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #4a0000 0%, #820100 100%);">
                <div>
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="bi bi-shield-exclamation me-2"></i>Board of Survey - Formal Item Removal
                    </h5>
                    <small class="text-white-50">Audited Inventory Decommissioning Procedure</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="boardOfSurveyForm" action="processors/process_board_of_survey.php" method="POST">
                <input type="hidden" name="id" id="bos_item_id">
                <input type="hidden" name="asset_type" id="bos_asset_type" value="building_inventory">

                <div class="modal-body p-4">
                    <!-- Item Information Banner -->
                    <div class="alert alert-light border shadow-sm py-2 px-3 mb-3 rounded-3">
                        <div class="row align-items-center small">
                            <div class="col-md-5">
                                <span class="text-muted d-block text-uppercase fw-semibold" style="font-size: 10px;">Item for Decommission</span>
                                <strong class="fs-6 text-dark" id="bos_item_name">-</strong>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted d-block text-uppercase fw-semibold" style="font-size: 10px;">Assigned Location</span>
                                <span class="fw-semibold text-secondary" id="bos_item_location">-</span>
                            </div>
                            <div class="col-md-3 text-md-end">
                                <span class="text-muted d-block text-uppercase fw-semibold" style="font-size: 10px;">Active In Circulation</span>
                                <span class="badge bg-primary fs-6 px-2 py-1" id="bos_item_available_qty">0</span>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning py-2 px-3 small border-0 d-flex align-items-center gap-2 mb-3" style="background: #fff8e6; border-left: 4px solid #f59e0b !important;">
                        <i class="bi bi-info-circle-fill text-warning fs-5"></i>
                        <div>
                            <strong>Formal Removal Notice:</strong> Direct deletions are permanently disabled. Items decommissioned through this workflow are officially recorded under statutory Board of Survey documentation for auditing.
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Removal Status -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">
                                Authorized Removal Status <span class="text-danger">*</span>
                            </label>
                            <select name="removal_status" id="bos_removal_status" class="form-select" required>
                                <option value="" disabled selected>-- Select Authorized Status --</option>
                                <option value="Destroyed">Destroyed (Unserviceable / Condemned)</option>
                                <option value="Repaired">Repaired (Sent Out of Circulation)</option>
                                <option value="Sold">Sold / Auctioned Off</option>
                            </select>
                            <small class="text-muted" style="font-size: 11px;">Select the statutory disposal decision.</small>
                        </div>

                        <!-- Removal Quantity -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">
                                Quantity to Remove / Decommission <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="removal_quantity" id="bos_removal_quantity" class="form-control" min="1" value="1" required>
                            <small class="text-muted" style="font-size: 11px;">Quantity deducted from active circulation.</small>
                        </div>

                        <!-- Board of Survey Reference Documentation -->
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-dark">
                                Board of Survey Reference Documentation <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-file-earmark-text text-danger"></i></span>
                                <input type="text" name="board_of_survey_ref" id="bos_ref" class="form-control" placeholder="e.g. BOS/EP/2026/042 or Audit Minute No. 3.4" required>
                            </div>
                            <small class="text-muted" style="font-size: 11px;">Mandatory file code or authorization minute number.</small>
                        </div>

                        <!-- Effective Date -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">
                                Authorization Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="removal_date" id="bos_removal_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <!-- Remarks / Disposal Notes -->
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-dark">
                                Board of Survey Findings &amp; Disposal Remarks
                            </label>
                            <textarea name="removal_remarks" id="bos_remarks" class="form-control" rows="2" placeholder="Record inspection committee findings, salvage details, or auction receipt details..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-white px-4 fw-semibold" style="background-color: #820100;">
                        <i class="bi bi-check2-circle me-1"></i>Authorize Removal (Board of Survey)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
