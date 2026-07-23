<!-- pages/modules/farm/models/view_daily_egg_collection_modal.php -->
<div class="modal fade" id="viewEggModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-eye-fill me-2"></i>Daily Egg Collection Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block uppercase fw-bold">Collection Date</small>
                            <span id="view_collection_date" class="fs-6 fw-bold text-dark">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block uppercase fw-bold">Batch Name</small>
                            <span id="view_batch_name" class="fs-6 fw-bold text-primary">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block uppercase fw-bold">Cage Name</small>
                            <span id="view_cage_name" class="fs-6 fw-bold text-dark">-</span>
                        </div>
                    </div>

                    <!-- Bird Counts -->
                    <div class="col-12"><hr class="my-2"></div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border-start border-4 border-primary">
                            <small class="text-muted d-block fw-bold">Pullets (Live Birds)</small>
                            <span id="view_pullets" class="fs-5 fw-bold text-dark">0</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border-start border-4 border-primary">
                            <small class="text-muted d-block fw-bold">Cockerels (Live Birds)</small>
                            <span id="view_cockerels" class="fs-5 fw-bold text-dark">0</span>
                        </div>
                    </div>

                    <!-- Egg Details CPRS-21(B) -->
                    <div class="col-12">
                        <hr class="my-2">
                        <h6 class="fw-bold text-primary"><i class="bi bi-egg-fried me-1"></i>Egg Details (CPRS-21 (B)) - Quantities & Weights</h6>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block fw-bold">Hatch Eggs</small>
                            <span id="view_hatchable_eggs" class="fs-6 fw-bold text-dark">0 NO</span>
                            <small id="view_hatchable_eggs_kg" class="d-block text-muted">0.00 Kg</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block fw-bold">Table Eggs</small>
                            <span id="view_table_eggs" class="fs-6 fw-bold text-dark">0 NO</span>
                            <small id="view_table_eggs_kg" class="d-block text-muted">0.00 Kg</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block fw-bold">Cracked Eggs</small>
                            <span id="view_cracked_eggs" class="fs-6 fw-bold text-dark">0 NO</span>
                            <small id="view_cracked_eggs_kg" class="d-block text-muted">0.00 Kg</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-success-subtle rounded border border-success">
                            <small class="text-success d-block fw-bold">Total Production</small>
                            <span id="view_total_eggs" class="fs-5 fw-bold text-success">0 NO</span>
                            <small id="view_total_eggs_kg" class="d-block fw-bold text-success">0.00 Kg</small>
                        </div>
                    </div>

                    <!-- Hatchery Operations Section -->
                    <div class="col-12">
                        <hr class="my-2">
                        <h6 class="fw-bold text-dark"><i class="bi bi-building me-1 text-warning"></i>Hatchery Operations</h6>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block fw-bold">Loading Date</small>
                            <span id="view_loading_date" class="fs-6 fw-medium text-dark">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block fw-bold">Hatchery Name</small>
                            <span id="view_hatchery_name" class="fs-6 fw-medium text-dark">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block fw-bold">Eggs Loaded</small>
                            <span id="view_eggs_loaded" class="fs-6 fw-medium text-dark">0</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block fw-bold">Hatching Date</small>
                            <span id="view_hatching_date" class="fs-6 fw-medium text-dark">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block fw-bold">Hatched Eggs</small>
                            <span id="view_hatched_eggs" class="fs-6 fw-medium text-dark">0</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-primary-subtle rounded border border-primary">
                            <small class="text-primary d-block fw-bold">Hatchability %</small>
                            <span id="view_hatchability_percentage" class="fs-5 fw-bold text-primary">0.00%</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
