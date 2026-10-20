<!-- Modal: Manage Animal Population -->
<div class="modal fade" id="manageAnimalPopulationModal" tabindex="-1" aria-labelledby="manageAnimalPopulationLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light" style="background: linear-gradient(135deg, #370709 0%, #5a1215 100%);">
                <h5 class="modal-title fw-bold" id="manageAnimalPopulationLabel">
                    <i class="bi bi-shield-shaded me-2"></i>Manage Animal Population
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-0">
                <!-- Nav Tabs -->
                <ul class="nav nav-tabs px-3 pt-3 bg-light border-bottom" id="animalPopTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark" id="add-animal-pop-tab" data-bs-toggle="tab" data-bs-target="#tabAddAnimalPop" type="button" role="tab" aria-controls="tabAddAnimalPop" aria-selected="true">
                            <i class="bi bi-pencil-square me-1 text-primary"></i> <span id="formAnimalTabLabel">Add / Update Record</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark" id="list-animal-pop-tab" data-bs-toggle="tab" data-bs-target="#tabListAnimalPop" type="button" role="tab" aria-controls="tabListAnimalPop" aria-selected="false">
                            <i class="bi bi-table me-1 text-success"></i> Recorded Livestock List
                            <span class="badge bg-secondary ms-1" id="animalRecordsCountBadge">0</span>
                        </button>
                    </li>
                </ul>

                <div class="tab-content p-4" id="animalPopTabsContent">
                    <!-- Tab 1: Add/Update Form -->
                    <div class="tab-pane fade show active" id="tabAddAnimalPop" role="tabpanel" aria-labelledby="add-animal-pop-tab">
                        <div id="manageAnimalPopAlertBox"></div>

                        <form id="manageAnimalPopForm" novalidate>
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="range_id" id="manageAnimalPopRangeId" value="<?= htmlspecialchars($range_id ?? '') ?>">

                            <div class="p-3 mb-3 rounded" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                                <div class="row g-3 align-items-center">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold text-dark">
                                            Census / Survey Year <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white"><i class="bi bi-calendar-event"></i></span>
                                            <input type="number" name="year" id="manageAnimalPopYear" class="form-control" min="2000" max="2100" value="<?= date('Y') ?>" required>
                                        </div>
                                        <small class="text-muted">Year the livestock population census represents.</small>
                                    </div>
                                    <div class="col-12 col-md-6 text-md-end">
                                        <button type="button" id="btnLoadYearAnimalData" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-cloud-arrow-down me-1"></i> Load Data for Selected Year
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <h6 class="fw-bold mb-3 text-secondary" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                <i class="bi bi-tag-fill me-1"></i> Population Counts by Livestock Category
                            </h6>

                            <div class="row g-3 mb-3">
                                <!-- Cow / Cattle -->
                                <div class="col-12 col-sm-6 col-md-4">
                                    <div class="p-3 border rounded h-100 bg-white shadow-xs">
                                        <label class="form-label small fw-bold mb-1" style="color: #370709;">
                                            <i class="bi bi-award-fill me-1"></i> Cow / Cattle
                                        </label>
                                        <input type="number" name="counts[Cow]" id="animal_count_Cow" class="form-control form-control-sm animal-counter-input" min="0" value="0" placeholder="0" required>
                                        <small class="text-muted d-block mt-1">Total cattle count.</small>
                                    </div>
                                </div>

                                <!-- Buffalo -->
                                <div class="col-12 col-sm-6 col-md-4">
                                    <div class="p-3 border rounded h-100 bg-white shadow-xs">
                                        <label class="form-label small fw-bold text-primary mb-1">
                                            <i class="bi bi-award-fill me-1"></i> Buffalo
                                        </label>
                                        <input type="number" name="counts[Buffalo]" id="animal_count_Buffalo" class="form-control form-control-sm animal-counter-input" min="0" value="0" placeholder="0" required>
                                        <small class="text-muted d-block mt-1">Total buffalo count.</small>
                                    </div>
                                </div>

                                <!-- Goat -->
                                <div class="col-12 col-sm-6 col-md-4">
                                    <div class="p-3 border rounded h-100 bg-white shadow-xs">
                                        <label class="form-label small fw-bold text-warning mb-1">
                                            <i class="bi bi-award-fill me-1"></i> Goat
                                        </label>
                                        <input type="number" name="counts[Goat]" id="animal_count_Goat" class="form-control form-control-sm animal-counter-input" min="0" value="0" placeholder="0" required>
                                        <small class="text-muted d-block mt-1">Total goat count.</small>
                                    </div>
                                </div>

                                <!-- Sheep -->
                                <div class="col-12 col-sm-6 col-md-4">
                                    <div class="p-3 border rounded h-100 bg-white shadow-xs">
                                        <label class="form-label small fw-bold mb-1" style="color: #6f42c1;">
                                            <i class="bi bi-award-fill me-1"></i> Sheep
                                        </label>
                                        <input type="number" name="counts[Sheep]" id="animal_count_Sheep" class="form-control form-control-sm animal-counter-input" min="0" value="0" placeholder="0" required>
                                        <small class="text-muted d-block mt-1">Total sheep count.</small>
                                    </div>
                                </div>

                                <!-- Chicken / Poultry -->
                                <div class="col-12 col-sm-6 col-md-4">
                                    <div class="p-3 border rounded h-100 bg-white shadow-xs">
                                        <label class="form-label small fw-bold text-success mb-1">
                                            <i class="bi bi-award-fill me-1"></i> Poultry
                                        </label>
                                        <input type="number" name="counts[Chicken]" id="animal_count_Chicken" class="form-control form-control-sm animal-counter-input" min="0" value="0" placeholder="0" required>
                                        <small class="text-muted d-block mt-1">Total poultry birds count.</small>
                                    </div>
                                </div>

                                <!-- Pig / Swine -->
                                <div class="col-12 col-sm-6 col-md-4">
                                    <div class="p-3 border rounded h-100 bg-white shadow-xs">
                                        <label class="form-label small fw-bold text-danger mb-1">
                                            <i class="bi bi-award-fill me-1"></i> Pig / Swine
                                        </label>
                                        <input type="number" name="counts[Pig]" id="animal_count_Pig" class="form-control form-control-sm animal-counter-input" min="0" value="0" placeholder="0" required>
                                        <small class="text-muted d-block mt-1">Total swine count.</small>
                                    </div>
                                </div>

                                <!-- Others -->
                                <div class="col-12 col-sm-6 col-md-4">
                                    <div class="p-3 border rounded h-100 bg-white shadow-xs">
                                        <label class="form-label small fw-bold text-secondary mb-1">
                                            <i class="bi bi-award-fill me-1"></i> Others
                                        </label>
                                        <input type="number" name="counts[Others]" id="animal_count_Others" class="form-control form-control-sm animal-counter-input" min="0" value="0" placeholder="0" required>
                                        <small class="text-muted d-block mt-1">Other livestock count.</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Live Summary Calculation Banner -->
                            <div class="d-flex justify-content-between align-items-center p-3 mb-4 rounded" style="background-color: #f1f5f9; border-left: 4px solid #370709;">
                                <div>
                                    <span class="small fw-bold text-secondary text-uppercase">Calculated Total Livestock Population:</span>
                                    <h4 class="mb-0 fw-bold" style="color: #370709;" id="manageAnimalPopTotalPreview">0</h4>
                                </div>
                                <span class="badge bg-light text-dark border px-2 py-1 small">Automatic Aggregation</span>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" id="btnResetAnimalPopForm" class="btn btn-outline-secondary btn-sm px-3">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset / New
                                </button>
                                <button type="submit" id="btnSaveAnimalPopForm" class="btn btn-dark btn-sm px-4 fw-bold">
                                    <i class="bi bi-check-circle-fill me-1 text-success"></i> Save Animal Population
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Tab 2: Existing Records List -->
                    <div class="tab-pane fade" id="tabListAnimalPop" role="tabpanel" aria-labelledby="list-animal-pop-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Recorded Animal Population Entries</h6>
                                <small class="text-muted">All active livestock demographic records for your Veterinary Range.</small>
                            </div>
                            <button type="button" id="btnRefreshAnimalPopList" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-arrow-repeat me-1"></i> Refresh List
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table id="recordedAnimalDemographicsTable" class="table table-sm table-striped table-bordered align-middle w-100 small m-0">
                                <thead style="background-color: #f1f5f9; color: #370709;">
                                    <tr>
                                        <th>Year</th>
                                        <th class="text-end">Cow</th>
                                        <th class="text-end">Buffalo</th>
                                        <th class="text-end">Goat</th>
                                        <th class="text-end">Sheep</th>
                                        <th class="text-end">Poultry</th>
                                        <th class="text-end">Pig</th>
                                        <th class="text-end">Others</th>
                                        <th class="text-end fw-bold" style="color: #370709;">Total</th>
                                        <th class="text-center" style="width: 85px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
