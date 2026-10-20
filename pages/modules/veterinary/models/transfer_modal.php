<?php
/**
 * pages/modules/veterinary/model/transfer_modal.php
 * Transfer Request Modal for Veterinary Surgeons
 */
?>

<!-- Transfer Request Modal -->
<div class="modal fade" id="transferRequestModal" tabindex="-1" aria-labelledby="transferRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #820100 0%, #500707 100%);">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="transferRequestModalLabel">
                    <i class="bi bi-arrow-left-right"></i> Transfer Request
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="transferRequestForm" method="POST" onsubmit="submitTransferRequest(event)">
                <input type="hidden" name="employee_id" id="transfer_employee_id" value="">

                <div class="modal-body p-4">
                    <!-- Employee Profile Banner -->
                    <div class="card bg-light border-0 rounded-3 p-3 mb-3 border-start border-4 border-danger">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                                <i class="bi bi-person-badge fs-4"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 10px;">Selected Employee</small>
                                <h6 class="fw-bold mb-0 text-dark" id="transfer_employee_name">-</h6>
                                <div class="text-muted small" id="transfer_employee_meta">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- Target Unit Field -->
                    <div class="mb-3">
                        <label for="transfer_target_unit" class="form-label small fw-bold text-dark">
                            Target Unit / Office <span class="text-danger">*</span>
                        </label>
                        <select name="target_unit" id="transfer_target_unit" class="form-select shadow-sm" required>
                            <option value="">-- Select Target Unit / Office --</option>
                            
                            <?php if (!empty($all_ranges_list)): ?>
                            <optgroup label="Veterinary Range Offices">
                                <?php foreach ($all_ranges_list as $vr_item): ?>
                                    <option value="<?= htmlspecialchars($vr_item['name']) ?>">
                                        <?= htmlspecialchars($vr_item['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif; ?>

                            <?php if (!empty($all_districts_list)): ?>
                            <optgroup label="District Secretariats / Offices">
                                <?php foreach ($all_districts_list as $dist_item): ?>
                                    <option value="District Office - <?= htmlspecialchars($dist_item['name']) ?>">
                                        District Office - <?= htmlspecialchars($dist_item['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif; ?>

                            <?php if (!empty($all_farms_list)): ?>
                            <optgroup label="Regional Farms">
                                <?php foreach ($all_farms_list as $rf_item): ?>
                                    <option value="<?= htmlspecialchars($rf_item['name']) ?>">
                                        <?= htmlspecialchars($rf_item['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif; ?>

                            <?php if (!empty($all_training_list)): ?>
                            <optgroup label="Training Centers">
                                <?php foreach ($all_training_list as $tc_item): ?>
                                    <option value="<?= htmlspecialchars($tc_item['name']) ?>">
                                        <?= htmlspecialchars($tc_item['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif; ?>

                            <?php if (!empty($all_master_units_list)): ?>
                            <optgroup label="Core Directorates & Functional Units">
                                <?php foreach ($all_master_units_list as $mu_item): ?>
                                    <option value="<?= htmlspecialchars($mu_item['name']) ?>">
                                        <?= htmlspecialchars($mu_item['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif; ?>
                        </select>
                        <div class="form-text" style="font-size: 11px;">
                            Select the destination office or operational unit where the employee should be relocated.
                        </div>
                    </div>

                    <!-- Reason for Transfer Field -->
                    <div class="mb-3">
                        <label for="transfer_reason" class="form-label small fw-bold text-dark">
                            Reason for Transfer <span class="text-danger">*</span>
                        </label>
                        <textarea name="reason" id="transfer_reason" class="form-control shadow-sm" rows="4" 
                                  placeholder="Provide the justification, grounds, or background for requesting this employee's transfer..." 
                                  required></textarea>
                        <div class="form-text" style="font-size: 11px;">
                            This reason and target unit will be routed to the Provincial Admin Branch for official review and approval.
                        </div>
                    </div>

                    <!-- Informational Notice -->
                    <div class="alert alert-info py-2 px-3 mb-0 small d-flex align-items-start gap-2 rounded-2" style="font-size: 12px;">
                        <i class="bi bi-info-circle-fill text-info mt-1 flex-shrink-0"></i>
                        <div>
                            <strong>Note:</strong> Submitting this request will not remove the officer immediately. An automated transfer notification will be dispatched to the <strong>Provincial Admin Branch</strong> for official processing.
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light px-4 py-3">
                    <button type="button" class="btn btn-secondary btn-sm px-3 rounded-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm px-4 rounded-2 fw-semibold d-inline-flex align-items-center gap-2" id="submitTransferBtn">
                        <i class="bi bi-send-fill"></i>
                        <span>Submit Transfer Request</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
