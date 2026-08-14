<?php
$default_user_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Training Officer';
?>

<!-- MODAL 1: ADD PRODUCE REGISTER ENTRY -->
<div class="modal fade" id="addProduceModal" tabindex="-1" aria-labelledby="addProduceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header text-light px-4 py-3" style="background: linear-gradient(135deg, #370709 0%, #5d0c10 100%);">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-white bg-opacity-10 rounded-3">
                        <i class="bi bi-journal-plus fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="addProduceModalLabel">New Produce Entry (Perishables)</h5>
                        <small class="text-light-50">Form A.D.30 - Digital Ledger Register</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="addProduceForm" action="processors/produce_register_crud.php" method="POST">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="training_center_id" value="<?= htmlspecialchars($current_center_id ?? '') ?>">
                <input type="hidden" name="is_ajax" value="1">

                <div class="modal-body p-4 bg-light">
                    <!-- Top Section: Commodity -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-body p-3">
                            <label class="form-label fw-bold text-dark mb-1">
                                <i class="bi bi-tag-fill me-1" style="color: #370709;"></i> Commodity <span style="color: #370709;">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-secondary"><i class="bi bi-box-seam"></i></span>
                                <input type="text" list="commodityListOptions" name="commodity" id="add_commodity" class="form-control fw-semibold" placeholder="Select or type commodity (e.g. Red Napier, Grass, Coconut, Mango)" value="<?= !empty($selected_commodity) && $selected_commodity !== 'all' ? htmlspecialchars($selected_commodity) : '' ?>" required>
                                <datalist id="commodityListOptions">
                                    <?php foreach ($available_commodities as $c_item): ?>
                                        <option value="<?= htmlspecialchars($c_item) ?>"></option>
                                    <?php endforeach; ?>
                                    <option value="Red Napier"></option>
                                    <option value="Grass (Fodder)"></option>
                                    <option value="Coconut"></option>
                                    <option value="Mango"></option>
                                    <option value="Banana"></option>
                                    <option value="Vegetable"></option>
                                    <option value="Fresh Cow Milk"></option>
                                    <option value="Fresh Eggs"></option>
                                    <option value="Pasture Cuttings"></option>
                                    <option value="Tamarind"></option>
                                </datalist>
                            </div>
                            <small class="text-muted">Specify the perishable commodity registered under this ledger.</small>
                        </div>
                    </div>

                    <!-- Two Form Sections Matching Physical Document Structure -->
                    <div class="row g-3">
                        <!-- GROUP 1: RECEIPT (ලැබීම) -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm rounded-3 h-100 border-top border-3 border-success">
                                <div class="card-header bg-white py-2 px-3 border-0">
                                    <h6 class="fw-bold text-success mb-0 d-flex align-items-center">
                                        <i class="bi bi-box-arrow-in-down me-2 fs-5"></i> 1. RECEIPT
                                    </h6>
                                </div>
                                <div class="card-body p-3 pt-0">
                                    <!-- Date -->
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Date <span style="color: #370709;">*</span></label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                                            <input type="date" name="record_date" id="add_record_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                                        </div>
                                    </div>

                                    <!-- Plot No. / Crop -->
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Plot No. / Crop</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                            <input type="text" name="plot_no_crop" id="add_plot_no_crop" class="form-control form-control-sm" placeholder="e.g. Plot 02 - Red Napier / Mango Orchard">
                                        </div>
                                    </div>

                                    <!-- Quantity & Unit -->
                                    <div class="row g-2">
                                        <div class="col-7">
                                            <label class="form-label small fw-semibold text-secondary mb-1">Quantity <span style="color: #370709;">*</span></label>
                                            <input type="number" step="0.01" min="0.01" name="quantity" id="add_quantity" class="form-control form-control-sm fw-bold text-end calc-trigger-add" placeholder="0.00" required>
                                        </div>
                                        <div class="col-5">
                                            <label class="form-label small fw-semibold text-secondary mb-1">Unit <span style="color: #370709;">*</span></label>
                                            <select name="unit" id="add_unit" class="form-select form-select-sm">
                                                <option value="kg" selected>kg </option>
                                                <option value="nos">nos </option>
                                                <option value="liters">liters </option>
                                                <option value="bundles">bundles </option>
                                                <option value="packets">packets </option>
                                                <option value="tons">tons </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- GROUP 2: DISPOSAL (බෙදා හැරීම) -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm rounded-3 h-100 border-top border-3" style="border-top-color: #370709 !important;">
                                <div class="card-header bg-white py-2 px-3 border-0">
                                    <h6 class="fw-bold mb-0 d-flex align-items-center" style="color: #370709;">
                                        <i class="bi bi-box-arrow-up-right me-2 fs-5"></i> 2. DISPOSAL
                                    </h6>
                                </div>
                                <div class="card-body p-3 pt-0">
                                    <!-- Method of Disposal -->
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Method of Disposal </label>
                                        <select name="disposal_method" id="add_disposal_method" class="form-select form-select-sm">
                                            <option value="Sold" selected>Sold </option>
                                            <option value="Issued">Issued </option>
                                            <option value="Discarded">Discarded / Spoilage </option>
                                            <option value="Transferred">Transferred </option>
                                            <option value="Self-consumption">Training / Farm Usage</option>
                                        </select>
                                    </div>

                                    <!-- Price per Unit & Full Sum Realized -->
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold text-secondary mb-1">Price per Unit (LKR)</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Rs.</span>
                                                <input type="number" step="0.01" min="0" name="price_per_unit" id="add_price_per_unit" class="form-control form-control-sm text-end calc-trigger-add" placeholder="0.00">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold text-secondary mb-1">Full Sum Realized (LKR)</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text text-success"><i class="bi bi-calculator"></i></span>
                                                <input type="number" step="0.01" min="0" name="full_sum_realized" id="add_full_sum_realized" class="form-control form-control-sm text-end fw-bold bg-success bg-opacity-10 text-success" placeholder="0.00">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Cash Receipt No. / Page of Credit Sale Book -->
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Cash Receipt No. / Credit Sale Page</label>
                                        <input type="text" name="receipt_no_credit_page" id="add_receipt_no_credit_page" class="form-control form-control-sm" placeholder="e.g. CR-88902 / Page 45">
                                    </div>

                                    <!-- Initials / User -->
                                    <div>
                                        <label class="form-label small fw-semibold text-secondary mb-1">Initials / User</label>
                                        <input type="text" name="initials_user" id="add_initials_user" class="form-control form-control-sm" value="<?= htmlspecialchars($default_user_name) ?>" placeholder="e.g. T.O. / initials">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-white px-4 py-3 border-0">
                    <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnSubmitAdd" class="btn text-light px-4 shadow-sm fw-semibold" style="background-color: #370709;">
                        <i class="bi bi-save me-1"></i> Save Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 2: EDIT PRODUCE REGISTER ENTRY -->
<div class="modal fade" id="editProduceModal" tabindex="-1" aria-labelledby="editProduceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header text-light px-4 py-3" style="background: linear-gradient(135deg, #1b3d6d 0%, #0d2346 100%);">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-white bg-opacity-10 rounded-3">
                        <i class="bi bi-pencil-square fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="editProduceModalLabel">Edit Produce Entry</h5>
                        <small class="text-light-50">Form A.D.30 - Digital Ledger Register</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="editProduceForm" action="processors/produce_register_crud.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="training_center_id" id="edit_training_center_id" value="<?= htmlspecialchars($current_center_id ?? '') ?>">
                <input type="hidden" name="is_ajax" value="1">

                <div class="modal-body p-4 bg-light">
                    <!-- Top Section: Commodity -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-body p-3">
                            <label class="form-label fw-bold text-dark mb-1">
                                <i class="bi bi-tag-fill me-1" style="color: #370709;"></i> Commodity <span style="color: #370709;">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-secondary"><i class="bi bi-box-seam"></i></span>
                                <input type="text" list="commodityListOptionsEdit" name="commodity" id="edit_commodity" class="form-control fw-semibold" placeholder="Commodity name" required>
                                <datalist id="commodityListOptionsEdit">
                                    <?php foreach ($available_commodities as $c_item): ?>
                                        <option value="<?= htmlspecialchars($c_item) ?>"></option>
                                    <?php endforeach; ?>
                                    <option value="Red Napier"></option>
                                    <option value="Grass (Fodder)"></option>
                                    <option value="Coconut"></option>
                                    <option value="Mango"></option>
                                    <option value="Banana"></option>
                                    <option value="Vegetable"></option>
                                    <option value="Fresh Cow Milk"></option>
                                    <option value="Fresh Eggs"></option>
                                    <option value="Pasture Cuttings"></option>
                                    <option value="Tamarind"></option>
                                </datalist>
                            </div>
                        </div>
                    </div>

                    <!-- Two Form Sections Matching Physical Document Structure -->
                    <div class="row g-3">
                        <!-- GROUP 1: RECEIPT (ලැබීම) -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm rounded-3 h-100 border-top border-3 border-success">
                                <div class="card-header bg-white py-2 px-3 border-0">
                                    <h6 class="fw-bold text-success mb-0 d-flex align-items-center">
                                        <i class="bi bi-box-arrow-in-down me-2 fs-5"></i> 1. RECEIPT
                                    </h6>
                                </div>
                                <div class="card-body p-3 pt-0">
                                    <!-- Date -->
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Date <span style="color: #370709;">*</span></label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                                            <input type="date" name="record_date" id="edit_record_date" class="form-control form-control-sm" required>
                                        </div>
                                    </div>

                                    <!-- Plot No. / Crop -->
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Plot No. / Crop</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                            <input type="text" name="plot_no_crop" id="edit_plot_no_crop" class="form-control form-control-sm" placeholder="e.g. Plot 02 - Red Napier / Mango Orchard">
                                        </div>
                                    </div>

                                    <!-- Quantity & Unit -->
                                    <div class="row g-2">
                                        <div class="col-7">
                                            <label class="form-label small fw-semibold text-secondary mb-1">Quantity <span style="color: #370709;">*</span></label>
                                            <input type="number" step="0.01" min="0.01" name="quantity" id="edit_quantity" class="form-control form-control-sm fw-bold text-end calc-trigger-edit" required>
                                        </div>
                                        <div class="col-5">
                                            <label class="form-label small fw-semibold text-secondary mb-1">Unit <span style="color: #370709;">*</span></label>
                                            <select name="unit" id="edit_unit" class="form-select form-select-sm">
                                                <option value="kg">kg</option>
                                                <option value="nos">nos</option>
                                                <option value="liters">liters</option>
                                                <option value="bundles">bundles</option>
                                                <option value="packets">packets</option>
                                                <option value="tons">tons</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- GROUP 2: DISPOSAL (බෙදා හැරීම) -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm rounded-3 h-100 border-top border-3 border-primary">
                                <div class="card-header bg-white py-2 px-3 border-0">
                                    <h6 class="fw-bold text-primary mb-0 d-flex align-items-center">
                                        <i class="bi bi-box-arrow-up-right me-2 fs-5"></i> 2. DISPOSAL 
                                    </h6>
                                </div>
                                <div class="card-body p-3 pt-0">
                                    <!-- Method of Disposal -->
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Method of Disposal</label>
                                        <select name="disposal_method" id="edit_disposal_method" class="form-select form-select-sm">
                                            <option value="Sold">Sold</option>
                                            <option value="Issued">Issued</option>
                                            <option value="Discarded">Discarded / Spoilage</option>
                                            <option value="Transferred">Transferred</option>
                                            <option value="Self-consumption">Training / Farm Usage</option>
                                        </select>
                                    </div>

                                    <!-- Price per Unit & Full Sum Realized -->
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold text-secondary mb-1">Price per Unit (LKR)</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Rs.</span>
                                                <input type="number" step="0.01" min="0" name="price_per_unit" id="edit_price_per_unit" class="form-control form-control-sm text-end calc-trigger-edit">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold text-secondary mb-1">Full Sum Realized (LKR)</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text text-primary"><i class="bi bi-calculator"></i></span>
                                                <input type="number" step="0.01" min="0" name="full_sum_realized" id="edit_full_sum_realized" class="form-control form-control-sm text-end fw-bold bg-primary bg-opacity-10 text-primary">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Cash Receipt No. / Page of Credit Sale Book -->
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Cash Receipt No. / Credit Sale Page</label>
                                        <input type="text" name="receipt_no_credit_page" id="edit_receipt_no_credit_page" class="form-control form-control-sm">
                                    </div>

                                    <!-- Initials / User -->
                                    <div>
                                        <label class="form-label small fw-semibold text-secondary mb-1">Initials / User</label>
                                        <input type="text" name="initials_user" id="edit_initials_user" class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-white px-4 py-3 border-0">
                    <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="btnSubmitEdit" class="btn text-light px-4 shadow-sm fw-semibold" style="background-color: #1b3d6d;">
                        <i class="bi bi-check-circle me-1"></i> Update Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
