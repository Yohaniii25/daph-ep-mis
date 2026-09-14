<?php
// pages/modules/farm/vehicles.php -> Fleet & Vehicle Asset Registry (Regional Farm)
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

if ($_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;
$farm_id = $_SESSION['farm_id'] ?? null;

// Fetch Fleet Vehicles for current Regional Farm
$veh_stmt = $mysqli->prepare("SELECT * FROM registered_vehicles WHERE (farm_id = ? OR user_id = ?) ORDER BY id DESC");
$veh_stmt->bind_param("ii", $farm_id, $user_id);
$veh_stmt->execute();
$vehicles_list = $veh_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$veh_stmt->close();

// Fetch Vehicle Repairs for current Regional Farm
$rep_stmt = $mysqli->prepare("SELECT vr.*, v.vehicle_number, v.vehicle_type FROM vehicle_repairs vr LEFT JOIN registered_vehicles v ON vr.vehicle_id = v.id WHERE (vr.farm_id = ? OR vr.user_id = ?) ORDER BY vr.id DESC");
$rep_stmt->bind_param("ii", $farm_id, $user_id);
$rep_stmt->execute();
$repairs_list = $rep_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$rep_stmt->close();

$active_tab = $_GET['tab'] ?? 'fleet';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="bi bi-car-front-fill me-2" style="color: #b08723;"></i>Fleet &amp; Vehicle Asset Registry
        </h3>
        <p class="text-muted small mb-0">Regional Farm vehicle fleet, machinery transport, and maintenance logs</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn text-light shadow-sm fw-bold" style="background-color: #b08723;" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
            <i class="bi bi-plus-circle-fill me-2"></i>Register New Vehicle
        </button>
        <button class="btn btn-dark shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addRepairModal">
            <i class="bi bi-wrench-adjustable me-2"></i>Log Repair Work
        </button>
        <a href="office_details.php" class="btn btn-secondary shadow-sm fw-bold">
            <i class="bi bi-arrow-left me-2"></i>Back to Office Details
        </a>
    </div>
</div>

<!-- Notification Status SweetAlert -->
<?php if (isset($_GET['status']) && isset($_GET['msg'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: '<?= ($_GET['status'] === 'success') ? 'success' : 'error' ?>',
                    title: '<?= ($_GET['status'] === 'success') ? 'Success!' : 'Error!' ?>',
                    text: <?= json_encode($_GET['msg'] ?? '') ?>,
                    confirmButtonColor: '#b08723',
                    timer: 3500,
                    timerProgressBar: true
                });
            }
        });
    </script>
<?php endif; ?>

<!-- Navigation Tabs -->
<ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm" id="vehicleTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link fw-bold <?= ($active_tab === 'fleet') ? 'active' : '' ?>" id="fleet-tab" data-bs-toggle="tab" data-bs-target="#fleet-content" type="button" role="tab" style="--bs-nav-pills-link-active-bg: #b08723;">
            <i class="bi bi-truck me-2"></i>Active Vehicle Fleet (<?= count($vehicles_list) ?>)
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-bold <?= ($active_tab === 'repairs') ? 'active' : '' ?>" id="repairs-tab" data-bs-toggle="tab" data-bs-target="#repairs-content" type="button" role="tab" style="--bs-nav-pills-link-active-bg: #b08723;">
            <i class="bi bi-tools me-2"></i>Maintenance &amp; Repair Logs (<?= count($repairs_list) ?>)
        </button>
    </li>
</ul>

<div class="tab-content" id="vehicleTabsContent">

    <!-- TAB 1: FLEET VEHICLES -->
    <div class="tab-pane fade <?= ($active_tab === 'fleet') ? 'show active' : '' ?>" id="fleet-content" role="tabpanel">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="vehiclesTable" class="table table-hover align-middle w-100">
                        <thead class="table-dark" style="background-color: #370709;">
                            <tr>
                                <th>Vehicle Type</th>
                                <th>Reg Number</th>
                                <th>Chassis Number</th>
                                <th>Issue Order No.</th>
                                <th>Received From</th>
                                <th>Receipt No.</th>
                                <th>Quantity</th>
                                <th>Condition</th>
                                <th>Specification / Remarks</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicles_list as $veh): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($veh['vehicle_type']) ?></td>
                                    <td><span class="badge bg-dark text-light border px-2 fs-6"><?= htmlspecialchars($veh['vehicle_number']) ?></span></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($veh['chassis_number'] ?: '-') ?></span></td>
                                    <td><?= htmlspecialchars($veh['issue_order_no'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($veh['received_from'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($veh['receipt_no'] ?: '-') ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary fs-6 px-2 py-1"><?= sprintf("%02d", $veh['available_quantity'] ?? 1) ?></span>
                                        <br>
                                        <small class="text-muted" style="font-size:10px;" title="Baseline + Received">Base: <?= intval($veh['initial_count'] ?? 1) ?> | Recv: <?= intval($veh['received_quantity'] ?? 0) ?></small>
                                    </td>
                                    <td>
                                        <?php
                                            $cond = $veh['current_condition'];
                                            $badge_class = ($cond === 'Good/Running' || strpos($cond, 'Good') !== false) ? 'bg-success' : (($cond === 'Needs Repair') ? 'bg-warning text-dark' : 'bg-danger');
                                        ?>
                                        <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($cond) ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($veh['specification'])): ?>
                                            <div class="fw-semibold text-dark small mb-1"><?= htmlspecialchars($veh['specification']) ?></div>
                                        <?php endif; ?>
                                        <div class="small text-muted"><?= htmlspecialchars($veh['remarks'] ?: ($veh['other_details'] ?: '-')) ?></div>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary me-1 btn-edit-vehicle"
                                            data-id="<?= $veh['id'] ?>"
                                            data-vehicle_type="<?= htmlspecialchars($veh['vehicle_type']) ?>"
                                            data-vehicle_number="<?= htmlspecialchars($veh['vehicle_number']) ?>"
                                            data-chassis_number="<?= htmlspecialchars($veh['chassis_number'] ?? '') ?>"
                                            data-issue_order_no="<?= htmlspecialchars($veh['issue_order_no'] ?? '') ?>"
                                            data-received_from="<?= htmlspecialchars($veh['received_from'] ?? '') ?>"
                                            data-receipt_no="<?= htmlspecialchars($veh['receipt_no'] ?? '') ?>"
                                            data-initial_count="<?= intval($veh['initial_count'] ?? 1) ?>"
                                            data-received_quantity="<?= intval($veh['received_quantity'] ?? 0) ?>"
                                            data-available_quantity="<?= intval($veh['available_quantity'] ?? 1) ?>"
                                            data-current_condition="<?= htmlspecialchars($veh['current_condition']) ?>"
                                            data-specification="<?= htmlspecialchars($veh['specification'] ?? '') ?>"
                                            data-remarks="<?= htmlspecialchars($veh['remarks'] ?: ($veh['other_details'] ?? '')) ?>"
                                            data-bs-toggle="modal" data-bs-target="#editVehicleModal"
                                            title="Edit Vehicle">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <a href="processors/office_assets_crud.php?action=delete_vehicle&id=<?= $veh['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete Vehicle">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: REPAIR LOGS -->
    <div class="tab-pane fade <?= ($active_tab === 'repairs') ? 'show active' : '' ?>" id="repairs-content" role="tabpanel">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="repairsTable" class="table table-hover align-middle w-100">
                        <thead class="table-dark" style="background-color: #370709;">
                            <tr>
                                <th>Date</th>
                                <th>Vehicle</th>
                                <th>Nature of Repair</th>
                                <th>Cost (LKR)</th>
                                <th>Repaired By</th>
                                <th>Invoice / Ref</th>
                                <th>Remarks</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($repairs_list as $rep): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($rep['repair_date']) ?></td>
                                    <td><span class="badge bg-dark text-light border"><?= htmlspecialchars($rep['vehicle_number'] ?? 'Vehicle #' . $rep['vehicle_id']) ?></span></td>
                                    <td><?= htmlspecialchars($rep['repair_nature']) ?></td>
                                    <td class="fw-bold text-danger">Rs. <?= number_format(floatval($rep['cost_lkr']), 2) ?></td>
                                    <td><?= htmlspecialchars($rep['repaired_by'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($rep['invoice_ref'] ?: '-') ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars($rep['remarks'] ?: '-') ?></td>
                                    <td class="text-center text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary me-1 btn-edit-repair"
                                            data-id="<?= $rep['id'] ?>"
                                            data-vehicle_id="<?= $rep['vehicle_id'] ?>"
                                            data-repair_date="<?= htmlspecialchars($rep['repair_date']) ?>"
                                            data-repair_nature="<?= htmlspecialchars($rep['repair_nature']) ?>"
                                            data-cost_lkr="<?= $rep['cost_lkr'] ?>"
                                            data-repaired_by="<?= htmlspecialchars($rep['repaired_by'] ?? '') ?>"
                                            data-invoice_ref="<?= htmlspecialchars($rep['invoice_ref'] ?? '') ?>"
                                            data-remarks="<?= htmlspecialchars($rep['remarks'] ?? '') ?>"
                                            data-bs-toggle="modal" data-bs-target="#editRepairModal"
                                            title="Edit Repair">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="processors/office_assets_crud.php?action=delete_repair&id=<?= $rep['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete Repair Log">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: Register Vehicle -->
<div class="modal fade" id="addVehicleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #b08723;">
                <h5 class="modal-title fw-bold"><i class="bi bi-car-front-fill me-2"></i>Register New Vehicle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="save_vehicle">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Vehicle Type <span class="text-danger">*</span></label>
                        <select name="vehicle_type" class="form-select fw-bold" required>
                            <option value="Tractor">Tractor</option>
                            <option value="Cab / Pickup">Cab / Pickup</option>
                            <option value="Lorry / Truck">Lorry / Truck</option>
                            <option value="Car">Car</option>
                            <option value="Motorcycle">Motorcycle</option>
                            <option value="Three Wheeler">Three Wheeler</option>
                            <option value="Van / Bus">Van / Bus</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Registration Number <span class="text-danger">*</span></label>
                            <input type="text" name="vehicle_number" class="form-control fw-bold font-monospace" placeholder="e.g. EP DA-1234" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Chassis Number</label>
                            <input type="text" name="chassis_number" class="form-control" placeholder="e.g. CH-991823">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Issue Order No.</label>
                            <input type="text" name="issue_order_no" class="form-control" placeholder="e.g. IO-2024-001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Received From</label>
                            <input type="text" name="received_from" class="form-control" placeholder="e.g. Line Ministry / Head Office">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Receipt No.</label>
                            <input type="text" name="receipt_no" class="form-control" placeholder="e.g. REC-1234">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Initial Baseline Stock</label>
                            <input type="number" name="initial_count" id="add_vehicle_initial_count" class="form-control fw-bold" value="1" min="0" required oninput="calcAddVehicle()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Received Quantity</label>
                            <input type="number" name="received_quantity" id="add_vehicle_received_quantity" class="form-control fw-bold" value="0" min="0" required oninput="calcAddVehicle()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Current Availability</label>
                            <input type="number" name="available_quantity" id="add_vehicle_available_quantity" class="form-control fw-bold bg-light" value="1" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Current Condition <span class="text-danger">*</span></label>
                        <select name="current_condition" class="form-select fw-bold" required onchange="calcAddVehicle()">
                            <option value="Good/Running">Good/Running</option>
                            <option value="Needs Repair">Needs Repair</option>
                            <option value="Condemned/Unserviceable">Condemned/Unserviceable</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Specification (Brand / Model / Engine / Specs)</label>
                        <textarea name="specification" class="form-control" rows="2" placeholder="e.g. Massey Ferguson, 45HP Diesel, Model 240..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks / Other Details / Driver</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Model year, capacity, fuel type, driver notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: #b08723;">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Vehicle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Edit Vehicle -->
<div class="modal fade" id="editVehicleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c10, #185dbd);">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Vehicle Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="update_vehicle">
                <input type="hidden" name="id" id="edit_vehicle_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Vehicle Type <span class="text-danger">*</span></label>
                        <select name="vehicle_type" id="edit_vehicle_type" class="form-select fw-bold" required>
                            <option value="Tractor">Tractor</option>
                            <option value="Cab / Pickup">Cab / Pickup</option>
                            <option value="Lorry / Truck">Lorry / Truck</option>
                            <option value="Car">Car</option>
                            <option value="Motorcycle">Motorcycle</option>
                            <option value="Three Wheeler">Three Wheeler</option>
                            <option value="Van / Bus">Van / Bus</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Reg Number <span class="text-danger">*</span></label>
                            <input type="text" name="vehicle_number" id="edit_vehicle_number" class="form-control fw-bold font-monospace" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Chassis Number</label>
                            <input type="text" name="chassis_number" id="edit_chassis_number" class="form-control font-monospace">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Issue Order No.</label>
                            <input type="text" name="issue_order_no" id="edit_vehicle_issue_order_no" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Received From</label>
                            <input type="text" name="received_from" id="edit_vehicle_received_from" class="form-control">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Receipt No.</label>
                            <input type="text" name="receipt_no" id="edit_vehicle_receipt_no" class="form-control">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Initial Baseline Stock</label>
                            <input type="number" name="initial_count" id="edit_vehicle_initial_count" class="form-control fw-bold" min="0" required oninput="calcEditVehicle()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Received Quantity</label>
                            <input type="number" name="received_quantity" id="edit_vehicle_received_quantity" class="form-control fw-bold" min="0" required oninput="calcEditVehicle()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Current Availability</label>
                            <input type="number" name="available_quantity" id="edit_vehicle_qty" class="form-control fw-bold bg-light" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Current Condition <span class="text-danger">*</span></label>
                        <select name="current_condition" id="edit_vehicle_condition" class="form-select fw-bold" required onchange="calcEditVehicle()">
                            <option value="Good/Running">Good/Running</option>
                            <option value="Needs Repair">Needs Repair</option>
                            <option value="Condemned/Unserviceable">Condemned/Unserviceable</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Specification (Brand / Model / Engine / Specs)</label>
                        <textarea name="specification" id="edit_vehicle_specification" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks / Other Details / Driver</label>
                        <textarea name="remarks" id="edit_vehicle_remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--color-c10, #185dbd);">
                        <i class="bi bi-check-circle-fill me-1"></i>Update Vehicle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Log Vehicle Repair -->
<div class="modal fade" id="addRepairModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #370709;">
                <h5 class="modal-title fw-bold"><i class="bi bi-tools me-2"></i>Log Vehicle Repair &amp; Maintenance</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="save_vehicle_repair">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Vehicle <span class="text-danger">*</span></label>
                        <select name="vehicle_id" class="form-select fw-bold" required>
                            <option value="" disabled selected>-- Select Vehicle --</option>
                            <?php foreach ($vehicles_list as $v): ?>
                                <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['vehicle_number']) ?> (<?= htmlspecialchars($v['vehicle_type']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Repair Date <span class="text-danger">*</span></label>
                            <input type="date" name="repair_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Cost (LKR) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="cost_lkr" class="form-control fw-bold" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nature / Description of Repair <span class="text-danger">*</span></label>
                        <input type="text" name="repair_nature" class="form-control" placeholder="e.g. Engine overhaul, Tire replacement..." required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Repaired By / Garage</label>
                            <input type="text" name="repaired_by" class="form-control" placeholder="Garage / Technician name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Invoice / Bill Ref</label>
                            <input type="text" name="invoice_ref" class="form-control" placeholder="e.g. INV-9912">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Warranty, additional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: #370709;">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Repair Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 4: Edit Vehicle Repair -->
<div class="modal fade" id="editRepairModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c10, #185dbd);">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Repair Log</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="update_vehicle_repair">
                <input type="hidden" name="id" id="edit_repair_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Vehicle <span class="text-danger">*</span></label>
                        <select name="vehicle_id" id="edit_repair_vehicle_id" class="form-select fw-bold" required>
                            <?php foreach ($vehicles_list as $v): ?>
                                <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['vehicle_number']) ?> (<?= htmlspecialchars($v['vehicle_type']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Repair Date <span class="text-danger">*</span></label>
                            <input type="date" name="repair_date" id="edit_repair_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Cost (LKR) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="cost_lkr" id="edit_cost_lkr" class="form-control fw-bold" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nature / Description of Repair <span class="text-danger">*</span></label>
                        <input type="text" name="repair_nature" id="edit_repair_nature" class="form-control" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Repaired By / Garage</label>
                            <input type="text" name="repaired_by" id="edit_repaired_by" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Invoice / Bill Ref</label>
                            <input type="text" name="invoice_ref" id="edit_invoice_ref" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks</label>
                        <textarea name="remarks" id="edit_repair_remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--color-c10, #185dbd);">
                        <i class="bi bi-check-circle-fill me-1"></i>Update Repair Log
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function calcAddVehicle() {
    const base = parseInt(document.getElementById('add_vehicle_initial_count').value) || 0;
    const recv = parseInt(document.getElementById('add_vehicle_received_quantity').value) || 0;
    const condEl = document.querySelector('#addVehicleModal select[name="current_condition"]');
    const cond = condEl ? condEl.value : '';
    let total = base + recv;
    if (cond.includes('Condemned') || cond.includes('Unserviceable') || cond.includes('Damaged')) {
        total = Math.max(0, total - 1);
    }
    document.getElementById('add_vehicle_available_quantity').value = total;
}
function calcEditVehicle() {
    const base = parseInt(document.getElementById('edit_vehicle_initial_count').value) || 0;
    const recv = parseInt(document.getElementById('edit_vehicle_received_quantity').value) || 0;
    const condEl = document.getElementById('edit_vehicle_condition');
    const cond = condEl ? condEl.value : '';
    let total = base + recv;
    if (cond.includes('Condemned') || cond.includes('Unserviceable') || cond.includes('Damaged')) {
        total = Math.max(0, total - 1);
    }
    document.getElementById('edit_vehicle_qty').value = total;
}

document.addEventListener('DOMContentLoaded', function() {
    $(document).on('click', '.btn-edit-vehicle', function() {
        const btn = $(this);
        $('#edit_vehicle_id').val(btn.data('id'));
        $('#edit_vehicle_type').val(btn.data('vehicle_type'));
        $('#edit_vehicle_number').val(btn.data('vehicle_number'));
        $('#edit_chassis_number').val(btn.data('chassis_number'));
        $('#edit_vehicle_issue_order_no').val(btn.data('issue_order_no'));
        $('#edit_vehicle_received_from').val(btn.data('received_from'));
        $('#edit_vehicle_receipt_no').val(btn.data('receipt_no'));
        $('#edit_vehicle_initial_count').val(btn.data('initial_count') !== undefined ? btn.data('initial_count') : btn.data('available_quantity'));
        $('#edit_vehicle_received_quantity').val(btn.data('received_quantity') !== undefined ? btn.data('received_quantity') : 0);
        $('#edit_vehicle_condition').val(btn.data('current_condition'));
        $('#edit_vehicle_specification').val(btn.data('specification'));
        $('#edit_vehicle_remarks').val(btn.data('remarks'));
        calcEditVehicle();
    });

    $(document).on('click', '.btn-edit-repair', function() {
        const btn = $(this);
        $('#edit_repair_id').val(btn.data('id'));
        $('#edit_repair_vehicle_id').val(btn.data('vehicle_id'));
        $('#edit_repair_date').val(btn.data('repair_date'));
        $('#edit_repair_nature').val(btn.data('repair_nature'));
        $('#edit_cost_lkr').val(btn.data('cost_lkr'));
        $('#edit_repaired_by').val(btn.data('repaired_by'));
        $('#edit_invoice_ref').val(btn.data('invoice_ref'));
        $('#edit_repair_remarks').val(btn.data('remarks'));
    });
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
