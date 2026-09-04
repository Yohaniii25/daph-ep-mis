<!-- Modal: Manage Human Population -->
<div class="modal fade" id="manageHumanPopulationModal" tabindex="-1" aria-labelledby="manageHumanPopulationLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light" style="background: linear-gradient(135deg, #370709 0%, #5a1215 100%);">
                <h5 class="modal-title fw-bold" id="manageHumanPopulationLabel">
                    <i class="bi bi-people-fill me-2"></i>Manage Human Population
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-0">
                <!-- Nav Tabs -->
                <ul class="nav nav-tabs px-3 pt-3 bg-light border-bottom" id="humanPopTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark" id="add-pop-tab" data-bs-toggle="tab" data-bs-target="#tabAddPop" type="button" role="tab" aria-controls="tabAddPop" aria-selected="true">
                            <i class="bi bi-pencil-square me-1 text-primary"></i> <span id="formTabLabel">Add / Update Record</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark" id="list-pop-tab" data-bs-toggle="tab" data-bs-target="#tabListPop" type="button" role="tab" aria-controls="tabListPop" aria-selected="false">
                            <i class="bi bi-table me-1 text-success"></i> Recorded Demographics List
                            <span class="badge bg-secondary ms-1" id="recordsCountBadge">0</span>
                        </button>
                    </li>
                </ul>

                <div class="tab-content p-4" id="humanPopTabsContent">
                    <!-- Tab 1: Add/Update Form -->
                    <div class="tab-pane fade show active" id="tabAddPop" role="tabpanel" aria-labelledby="add-pop-tab">
                        <div id="managePopAlertBox"></div>

                        <form id="manageHumanPopForm" novalidate>
                            <input type="hidden" name="action" value="save">

                            <div class="p-3 mb-3 rounded" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold text-dark">
                                            Census / Survey Year <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white"><i class="bi bi-calendar-event"></i></span>
                                            <input type="number" name="year" id="managePopYear" class="form-control" min="2000" max="2100" value="<?= date('Y') ?>" required>
                                        </div>
                                        <small class="text-muted">Year the population census represents.</small>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold text-dark">
                                            Ethnicity Group <span class="text-danger">*</span>
                                        </label>
                                        <select name="ethnicity" id="managePopEthnicity" class="form-select form-select-sm" required>
                                            <option value="" disabled selected>-- Select Ethnicity --</option>
                                            <option value="Sinhala">Sinhala</option>
                                            <option value="Tamil">Tamil</option>
                                            <option value="Muslim">Muslim</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <h6 class="fw-bold mb-3 text-secondary" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                <i class="bi bi-bar-chart-fill me-1"></i> Population Counts by Demographic Metric
                            </h6>

                            <div class="row g-3 mb-3">
                                <div class="col-12 col-md-4">
                                    <div class="p-3 border rounded h-100 bg-white shadow-xs">
                                        <label class="form-label small fw-bold text-primary mb-1">
                                            <i class="bi bi-gender-male me-1"></i> Male Population
                                        </label>
                                        <input type="number" name="male_count" id="managePopMale" class="form-control form-control-sm pop-counter-input" min="0" value="0" placeholder="0" required>
                                        <small class="text-muted d-block mt-1">Total male persons.</small>
                                    </div>
                                </div>

                                <div class="col-12 col-md-4">
                                    <div class="p-3 border rounded h-100 bg-white shadow-xs">
                                        <label class="form-label small fw-bold text-danger mb-1">
                                            <i class="bi bi-gender-female me-1"></i> Female Population
                                        </label>
                                        <input type="number" name="female_count" id="managePopFemale" class="form-control form-control-sm pop-counter-input" min="0" value="0" placeholder="0" required>
                                        <small class="text-muted d-block mt-1">Total female persons.</small>
                                    </div>
                                </div>

                                <div class="col-12 col-md-4">
                                    <div class="p-3 border rounded h-100 bg-white shadow-xs">
                                        <label class="form-label small fw-bold text-success mb-1">
                                            <i class="bi bi-houses-fill me-1"></i> Households Count
                                        </label>
                                        <input type="number" name="households_count" id="managePopHouseholds" class="form-control form-control-sm" min="0" value="0" placeholder="0" required>
                                        <small class="text-muted d-block mt-1">Total families / households.</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Live Summary Calculation Banner -->
                            <div class="d-flex justify-content-between align-items-center p-3 mb-4 rounded" style="background-color: #f1f5f9; border-left: 4px solid #370709;">
                                <div>
                                    <span class="small fw-bold text-secondary text-uppercase">Calculated Total Population (Male + Female):</span>
                                    <h4 class="mb-0 fw-bold" style="color: #370709;" id="managePopTotalPreview">0</h4>
                                </div>
                                <span class="badge bg-light text-dark border px-2 py-1 small">Automatic Aggregation</span>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" id="btnResetPopForm" class="btn btn-outline-secondary btn-sm px-3">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset / New
                                </button>
                                <button type="submit" id="btnSavePopForm" class="btn btn-dark btn-sm px-4 fw-bold">
                                    <i class="bi bi-check-circle-fill me-1 text-success"></i> Save Demographics
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Tab 2: Existing Records List -->
                    <div class="tab-pane fade" id="tabListPop" role="tabpanel" aria-labelledby="list-pop-tab">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Recorded Population Entries</h6>
                                <small class="text-muted">All active human demographic records for your Veterinary Range.</small>
                            </div>
                            <button type="button" id="btnRefreshPopList" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-arrow-repeat me-1"></i> Refresh List
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table id="recordedDemographicsTable" class="table table-sm table-striped table-bordered align-middle w-100 small m-0">
                                <thead style="background-color: #f1f5f9; color: #370709;">
                                    <tr>
                                        <th>Year</th>
                                        <th>Ethnicity</th>
                                        <th class="text-end">Male</th>
                                        <th class="text-end">Female</th>
                                        <th class="text-end fw-bold">Total Pop</th>
                                        <th class="text-end">Households</th>
                                        <th class="text-center" style="width: 100px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="7" class="text-center py-3 text-muted">Loading records...</td>
                                    </tr>
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
