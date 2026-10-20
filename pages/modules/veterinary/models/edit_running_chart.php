<?php
// pages/modules/veterinary/models/edit_running_chart.php
?>
<div class="modal fade" id="editRunningChartModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #212529 0%, #343a40 100%);">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-white bg-opacity-25 p-2 me-3 text-white">
                        <i class="bi bi-pencil-square fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Edit Running Chart &amp; Fuel Log Entry</h5>
                        <small class="text-white-50">Modify recorded driver journey, milometer readings and fuel metrics</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editRunningChartForm" action="processors/update_running_chart.php" method="POST">
                <input type="hidden" name="id" id="edit_rc_id">
                <div class="modal-body p-4 bg-light">
                    
                    <!-- Section 1: Vehicle & Trip Metadata -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-header bg-white py-2 px-3 border-bottom">
                            <span class="fw-bold text-dark small text-uppercase"><i class="bi bi-truck text-warning me-2"></i>1. Vehicle &amp; Journey Schedule</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold">Select Vehicle Asset <span class="text-danger">*</span></label>
                                    <select name="vehicle_id" id="edit_rc_vehicle_id" class="form-select" required>
                                        <option value="" disabled>-- Choose Registered Vehicle --</option>
                                        <?php
                                        if (isset($mysqli) && !empty($district_id)) {
                                            $v_stmt = $mysqli->prepare("SELECT id, vehicle_number, vehicle_type, current_condition FROM registered_vehicles WHERE district_id = ? AND range_id = ? AND is_active = 1 ORDER BY vehicle_number ASC");
                                            $v_stmt->bind_param("ii", $district_id, $range_id);
                                            $v_stmt->execute();
                                            $v_res = $v_stmt->get_result();
                                            while ($v = $v_res->fetch_assoc()) {
                                                echo "<option value='{$v['id']}'>" . htmlspecialchars($v['vehicle_number']) . " — " . htmlspecialchars($v['vehicle_type']) . " (" . htmlspecialchars($v['current_condition']) . ")</option>";
                                            }
                                            $v_stmt->close();
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Date of Trip <span class="text-danger">*</span></label>
                                    <input type="date" name="trip_date" id="edit_rc_trip_date" class="form-control" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold">Time Out <span class="text-danger">*</span></label>
                                    <input type="time" name="time_out" id="edit_rc_time_out" class="form-control" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold">Time In <span class="text-danger">*</span></label>
                                    <input type="time" name="time_in" id="edit_rc_time_in" class="form-control" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Driver Details & Route Information -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-header bg-white py-2 px-3 border-bottom">
                            <span class="fw-bold text-dark small text-uppercase"><i class="bi bi-person-badge text-primary me-2"></i>2. Driver Details &amp; Mission Route</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label small fw-bold">Name of Driver <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-person text-muted"></i></span>
                                        <input type="text" name="driver_name" id="edit_rc_driver_name" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Driver Initials <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-pen text-muted"></i></span>
                                        <input type="text" name="driver_initials" id="edit_rc_driver_initials" class="form-control font-monospace text-uppercase" maxlength="25" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Route &amp; Places Visited <span class="text-danger">*</span></label>
                                    <textarea name="route_places_visited" id="edit_rc_route_places_visited" class="form-control" rows="2" required></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Purpose of Trip <span class="text-danger">*</span></label>
                                    <textarea name="purpose_of_trip" id="edit_rc_purpose_of_trip" class="form-control" rows="2" required></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Mileage Tracking & Fuel / Oil Metrics -->
                    <div class="row g-3 mb-2">
                        
                        <!-- Left: Mileage Tracking -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm rounded-3 h-100">
                                <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-dark small text-uppercase"><i class="bi bi-speedometer text-success me-2"></i>3. Mileage Tracking</span>
                                    <span class="badge bg-light text-secondary border font-monospace">Milometer (Miles / Km)</span>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Milometer Reading - Out <span class="text-danger">*</span></label>
                                            <input type="number" step="0.1" name="milometer_out" id="edit_rc_milometer_out" class="form-control font-monospace edit-rc-calc-trigger" min="0" required>
                                            <small class="text-muted" style="font-size: 11px;">Odometer reading at departure.</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Milometer Reading - In <span class="text-danger">*</span></label>
                                            <input type="number" step="0.1" name="milometer_in" id="edit_rc_milometer_in" class="form-control font-monospace edit-rc-calc-trigger" min="0" required>
                                            <small class="text-muted" style="font-size: 11px;">Odometer reading at return.</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Total Trip Mileage (Auto-Calculated)</label>
                                            <div class="input-group">
                                                <input type="number" step="0.1" name="total_mileage" id="edit_rc_total_mileage" class="form-control font-monospace bg-light fw-bold text-primary" readonly>
                                                <span class="input-group-text bg-light text-muted small">Miles</span>
                                            </div>
                                            <small class="text-success fw-semibold" style="font-size: 11px;"><i class="bi bi-check-circle me-1"></i>Auto: Reading In - Reading Out</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Miles per Gallon (MPG)</label>
                                            <div class="input-group">
                                                <input type="number" step="0.01" name="miles_per_gallon" id="edit_rc_miles_per_gallon" class="form-control font-monospace bg-light fw-bold text-success" readonly>
                                                <span class="input-group-text bg-light text-muted small">MPG</span>
                                            </div>
                                            <small class="text-muted" style="font-size: 11px;">Efficiency: Mileage / Fuel Consumed</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Fuel & Oil Metrics -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm rounded-3 h-100">
                                <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-dark small text-uppercase"><i class="bi bi-fuel-pump text-danger me-2"></i>4. Fuel &amp; Oil Metrics</span>
                                    <span class="badge bg-light text-secondary border font-monospace">Gallons / Litres</span>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Fuel Position in Tank</label>
                                            <input type="number" step="0.01" name="fuel_position_in_tank" id="edit_rc_fuel_position_in_tank" class="form-control font-monospace edit-rc-calc-trigger" min="0" required>
                                            <small class="text-muted" style="font-size: 11px;">Total fuel available.</small>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Fuel Drawn (Purchased)</label>
                                            <input type="number" step="0.01" name="fuel_drawn" id="edit_rc_fuel_drawn" class="form-control font-monospace edit-rc-calc-trigger" min="0" required>
                                            <small class="text-muted" style="font-size: 11px;">Newly added fuel.</small>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Fuel Consumed</label>
                                            <input type="number" step="0.01" name="fuel_consumed" id="edit_rc_fuel_consumed" class="form-control font-monospace edit-rc-calc-trigger" min="0" required>
                                            <small class="text-muted" style="font-size: 11px;">Burnt during trip.</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Tank Ending Balance</label>
                                            <div class="input-group">
                                                <input type="number" step="0.01" name="fuel_balance" id="edit_rc_fuel_balance" class="form-control font-monospace bg-light fw-bold text-dark" readonly>
                                                <span class="input-group-text bg-light text-muted small">Vol</span>
                                            </div>
                                            <small class="text-muted" style="font-size: 11px;">Calculated: Fuel Position - Consumed</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Engine Oil Drawn</label>
                                            <div class="input-group">
                                                <input type="number" step="0.01" name="engine_oil_drawn" id="edit_rc_engine_oil_drawn" class="form-control font-monospace" min="0" required>
                                                <span class="input-group-text bg-white text-muted small">Pts/L</span>
                                            </div>
                                            <small class="text-muted" style="font-size: 11px;">Lubricant / top-up drawn.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-2">
                        <label class="form-label small fw-bold">Special Remarks / Notes</label>
                        <input type="text" name="remarks" id="edit_rc_remarks" class="form-control" placeholder="Optional journey notes, terrain conditions, fuel voucher serials, etc.">
                    </div>

                </div>
                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark px-4 shadow-sm">
                        <i class="bi bi-check-circle-fill me-2"></i>Update Running Chart Log
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
