<!-- pages/modules/farm/models/export_columns_modal.php -->
<div class="modal fade" id="exportColumnsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" action="processors/export_egg_collections.php" method="POST">
            <div class="modal-header bg-dark text-light">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-file-earmark-spreadsheet-fill me-2 text-success"></i>Export Custom CSV Report
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <p class="text-muted small mb-3">Select the columns you want to include in your exported report:</p>
                
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectAllCols">Select All</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDeselectAllCols">Deselect All</button>
                </div>

                <div class="row g-2" id="exportColumnsList">
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input export-col-chk" type="checkbox" name="columns[]" value="collection_date" id="col_date" checked>
                            <label class="form-check-label fw-medium" for="col_date">Collection Date</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input export-col-chk" type="checkbox" name="columns[]" value="batch_name" id="col_batch" checked>
                            <label class="form-check-label fw-medium" for="col_batch">Batch Name</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input export-col-chk" type="checkbox" name="columns[]" value="cage_name" id="col_cage" checked>
                            <label class="form-check-label fw-medium" for="col_cage">Cage Name</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input export-col-chk" type="checkbox" name="columns[]" value="pullets" id="col_pullets" checked>
                            <label class="form-check-label fw-medium" for="col_pullets">Pullets</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input export-col-chk" type="checkbox" name="columns[]" value="cockerels" id="col_cockerels" checked>
                            <label class="form-check-label fw-medium" for="col_cockerels">Cockerels</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input export-col-chk" type="checkbox" name="columns[]" value="hatchable_eggs" id="col_hatchable" checked>
                            <label class="form-check-label fw-medium" for="col_hatchable">Hatch Eggs</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input export-col-chk" type="checkbox" name="columns[]" value="table_eggs" id="col_table" checked>
                            <label class="form-check-label fw-medium" for="col_table">Table Eggs</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input export-col-chk" type="checkbox" name="columns[]" value="cracked_eggs" id="col_cracked" checked>
                            <label class="form-check-label fw-medium" for="col_cracked">Cracked Eggs</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input export-col-chk" type="checkbox" name="columns[]" value="total_eggs" id="col_total" checked>
                            <label class="form-check-label fw-medium text-success" for="col_total">Total Eggs</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input export-col-chk" type="checkbox" name="columns[]" value="loading_date" id="col_loading_date" checked>
                            <label class="form-check-label fw-medium" for="col_loading_date">Loading Date</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input export-col-chk" type="checkbox" name="columns[]" value="hatchery_name" id="col_hatchery_name" checked>
                            <label class="form-check-label fw-medium" for="col_hatchery_name">Hatchery Name</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input export-col-chk" type="checkbox" name="columns[]" value="eggs_loaded" id="col_eggs_loaded" checked>
                            <label class="form-check-label fw-medium" for="col_eggs_loaded">Eggs Loaded</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input export-col-chk" type="checkbox" name="columns[]" value="hatching_date" id="col_hatching_date" checked>
                            <label class="form-check-label fw-medium" for="col_hatching_date">Hatching Date</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input export-col-chk" type="checkbox" name="columns[]" value="hatched_eggs" id="col_hatched_eggs" checked>
                            <label class="form-check-label fw-medium" for="col_hatched_eggs">Hatched Eggs</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input class="form-check-input export-col-chk" type="checkbox" name="columns[]" value="hatchability_percentage" id="col_hatchability" checked>
                            <label class="form-check-label fw-medium text-primary" for="col_hatchability">Hatchability %</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success fw-bold px-4">
                    <i class="bi bi-download me-1"></i>Export CSV
                </button>
            </div>
        </form>
    </div>
</div>
