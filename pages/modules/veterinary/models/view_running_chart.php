<?php
// pages/modules/veterinary/model/view_running_chart.php
?>
<div class="modal fade" id="viewRunningChartModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-white bg-opacity-25 p-2 me-3 text-white">
                        <i class="bi bi-file-earmark-text-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Running Chart &amp; Fuel Log Dossier</h5>
                        <small class="text-white-50">Audited Vehicle Trip &amp; Consumption Details</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                
                <!-- Vehicle & Trip Header Card -->
                <div class="card border-0 shadow-sm rounded-3 mb-3">
                    <div class="card-body p-3">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <span class="text-muted small text-uppercase">Vehicle Information</span>
                                <h4 class="fw-bold text-dark font-monospace mb-1" id="view_rc_vehicle_number">-</h4>
                                <span class="badge bg-secondary" id="view_rc_vehicle_type">-</span>
                            </div>
                            <div class="col-md-6 text-md-end mt-2 mt-md-0">
                                <span class="text-muted small text-uppercase d-block">Trip Execution Date</span>
                                <span class="fw-bold text-dark fs-5" id="view_rc_trip_date">-</span>
                                <div>
                                    <span class="badge bg-light text-dark border me-1">Time Out: <strong id="view_rc_time_out">-</strong></span>
                                    <span class="badge bg-light text-dark border">Time In: <strong id="view_rc_time_in">-</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Driver & Journey Details -->
                <div class="card border-0 shadow-sm rounded-3 mb-3">
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <span class="text-muted small text-uppercase d-block">Driver Name</span>
                                <span class="fw-bold text-dark fs-6" id="view_rc_driver_name">-</span>
                            </div>
                            <div class="col-md-5">
                                <span class="text-muted small text-uppercase d-block">Driver Initials</span>
                                <span class="badge bg-dark text-warning font-monospace fs-6 px-3 py-1" id="view_rc_driver_initials">-</span>
                            </div>
                            <div class="col-12">
                                <span class="text-muted small text-uppercase d-block">Route &amp; Places Visited</span>
                                <p class="text-dark mb-2 bg-white p-2 rounded border" id="view_rc_route_places_visited">-</p>
                            </div>
                            <div class="col-12">
                                <span class="text-muted small text-uppercase d-block">Purpose of Trip</span>
                                <p class="text-dark mb-0 bg-white p-2 rounded border" id="view_rc_purpose_of_trip">-</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Metric Cards: Mileage & Fuel -->
                <div class="row g-3 mb-3">
                    <!-- Mileage Breakdown -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100">
                            <div class="card-header bg-white py-2 px-3 border-bottom fw-bold text-success small text-uppercase">
                                <i class="bi bi-speedometer2 me-1"></i> Mileage Metrics
                            </div>
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Milometer Out:</span>
                                    <span class="font-monospace fw-semibold" id="view_rc_milometer_out">0.0</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Milometer In:</span>
                                    <span class="font-monospace fw-semibold" id="view_rc_milometer_in">0.0</span>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="fw-bold text-dark">Total Mileage:</span>
                                    <span class="font-monospace fw-bold text-primary fs-6" id="view_rc_total_mileage">0.0 Miles</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="fw-bold text-dark">Miles per Gallon:</span>
                                    <span class="font-monospace fw-bold text-success fs-6" id="view_rc_miles_per_gallon">0.00 MPG</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fuel Breakdown -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100">
                            <div class="card-header bg-white py-2 px-3 border-bottom fw-bold text-danger small text-uppercase">
                                <i class="bi bi-fuel-pump me-1"></i> Fuel &amp; Oil Statistics
                            </div>
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Fuel in Tank:</span>
                                    <span class="font-monospace fw-semibold" id="view_rc_fuel_position">0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Fuel Drawn:</span>
                                    <span class="font-monospace fw-semibold text-success" id="view_rc_fuel_drawn">+0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Fuel Consumed:</span>
                                    <span class="font-monospace fw-semibold text-danger" id="view_rc_fuel_consumed">-0.00</span>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="fw-bold text-dark">Ending Tank Balance:</span>
                                    <span class="font-monospace fw-bold text-dark fs-6" id="view_rc_fuel_balance">0.00</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="fw-bold text-dark">Engine Oil Drawn:</span>
                                    <span class="font-monospace fw-bold text-secondary fs-6" id="view_rc_engine_oil_drawn">0.00 Pts</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Remarks -->
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-3">
                        <span class="text-muted small text-uppercase d-block">Special Remarks</span>
                        <span class="text-dark small" id="view_rc_remarks">None provided.</span>
                    </div>
                </div>

            </div>
            <div class="modal-footer bg-white border-top">
                <button type="button" class="btn btn-dark px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
