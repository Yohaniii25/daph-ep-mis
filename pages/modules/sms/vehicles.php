<?php
// pages/modules/sms/vehicles.php -> SMS Vehicle Fleet & Mobile Clinic Maintenance Registry
require_once '../../../includes/header.php';
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$allowed_roles = ['sms', 'administrator', 'provincial_director', 'district_dd'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 12;

// Fetch Vehicles for current Subject Matter Specialist
$vehicles_stmt = $mysqli->prepare("SELECT * FROM registered_vehicles WHERE (user_category = 'subject_matter_specialist' OR user_id = ?) AND is_active = 1 ORDER BY id DESC");
$vehicles_stmt->bind_param("i", $user_id);
$vehicles_stmt->execute();
$vehicles_list = $vehicles_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$vehicles_stmt->close();

// Fetch Vehicle Repair Logs for current Subject Matter Specialist
$repairs_stmt = $mysqli->prepare("
    SELECT vr.*, rv.vehicle_number, rv.vehicle_type 
    FROM vehicle_repairs vr 
    LEFT JOIN registered_vehicles rv ON vr.vehicle_id = rv.id 
    WHERE (vr.user_category = 'subject_matter_specialist' OR vr.user_id = ?) 
    ORDER BY vr.id DESC
");
$repairs_stmt->bind_param("i", $user_id);
$repairs_stmt->execute();
$repairs_list = $repairs_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$repairs_stmt->close();

$active_tab = $_GET['tab'] ?? 'vehicles';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="bi bi-car-front-fill me-2" style="color: #b08723;"></i>Vehicles &amp; Mobile Clinic Fleet
        </h3>
        <p class="text-muted small mb-0">Specialist mobile clinic vans, epidemiological surveillance vehicles &amp; maintenance registries</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn text-light shadow-sm fw-bold" style="background-color: #b08723;" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
            <i class="bi bi-plus-circle-fill me-2"></i>Register Vehicle
        </button>
        <button class="btn btn-dark shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addRepairModal">
            <i class="bi bi-tools me-2"></i>Log Service / Repair
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
        <button class="nav-link fw-bold <?= ($active_tab === 'vehicles') ? 'active' : '' ?>" id="vehicles-tab" data-bs-toggle="tab" data-bs-target="#vehicles-content" type="button" role="tab" style="--bs-nav-pills-link-active-bg: #b08723;">
            <i class="bi bi-truck-front-fill me-2"></i>Assigned Fleet (<?= count($vehicles_list) ?>)
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-bold <?= ($active_tab === 'repairs') ? 'active' : '' ?>" id="repairs-tab" data-bs-toggle="tab" data-bs-target="#repairs-content" type="button" role="tab" style="--bs-nav-pills-link-active-bg: #b08723;">
            <i class="bi bi-wrench-adjustable-circle-fill me-2"></i>Maintenance &amp; Repair Logs (<?= count($repairs_list) ?>)
        </button>
    </li>
</ul>

<div class="tab-content" id="vehicleTabsContent">

    <!-- TAB 1: REGISTERED VEHICLES -->
    <div class="tab-pane fade <?= ($active_tab === 'vehicles') ? 'show active' : '' ?>" id="vehicles-content" role="tabpanel">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="vehiclesTable" class="table table-hover align-middle w-100">
                        <thead class="table-dark" style="background-color: #370709;">
                            <tr>
                                <th>Vehicle Number</th>
                                <th>Category / Type</th>
                                <th>Chassis / Engine #</th>
                                <th>Issue Order No.</th>
                                <th>Received From</th>
                                <th>Receipt No.</th>
                                <th>Quantity</th>
                                <th>Current Condition</th>
                                <th>Specification / Remarks</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicles_list as $v): ?>
                                <tr>
                                    <td><span class="fw-bold fs-6 text-primary"><?= htmlspecialchars($v['vehicle_number']) ?></span></td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($v['vehicle_type']) ?></td>
                                    <td class="small text-muted font-monospace"><?= htmlspecialchars($v['chassis_number'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($v['issue_order_no'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($v['received_from'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($v['receipt_no'] ?: '-') ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary fs-6 px-2 py-1"><?= sprintf("%02d", $v['available_quantity'] ?? 1) ?></span>
                                        <br>
                                        <small class="text-muted" style="font-size:10px;" title="Baseline + Received">Base: <?= intval($v['initial_count'] ?? 1) ?> | Recv: <?= intval($v['received_quantity'] ?? 0) ?></small>
                                    </td>
                                    <td>
                                        <?php
                                            $cond = $v['current_condition'];
                                            $badge_class = (strpos($cond, 'Operational') !== false || strpos($cond, 'Good') !== false) ? 'bg-success' : ((strpos($cond, 'Maintenance') !== false || strpos($cond, 'Repair') !== false) ? 'bg-warning text-dark' : 'bg-danger');
                                        ?>
                                        <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($cond) ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($v['specification'])): ?>
                                            <div class="fw-semibold text-dark small mb-1"><?= htmlspecialchars($v['specification']) ?></div>
                                        <?php endif; ?>
                                        <div class="small text-muted"><?= htmlspecialchars($v['remarks'] ?: ($v['other_details'] ?: '-')) ?></div>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary me-1 btn-edit-vehicle"
                                            data-id="<?= $v['id'] ?>"
                                            data-type="<?= htmlspecialchars($v['vehicle_type']) ?>"
                                            data-number="<?= htmlspecialchars($v['vehicle_number']) ?>"
                                            data-chassis="<?= htmlspecialchars($v['chassis_number'] ?? '') ?>"
                                            data-condition="<?= htmlspecialchars($v['current_condition']) ?>"
                                            data-other="<?= htmlspecialchars($v['other_details'] ?? '') ?>"
                                            data-issue_order_no="<?= htmlspecialchars($v['issue_order_no'] ?? '') ?>"
                                            data-received_from="<?= htmlspecialchars($v['received_from'] ?? '') ?>"
                                            data-receipt_no="<?= htmlspecialchars($v['receipt_no'] ?? '') ?>"
                                            data-initial_count="<?= intval($v['initial_count'] ?? 1) ?>"
                                            data-received_quantity="<?= intval($v['received_quantity'] ?? 0) ?>"
                                            data-quantity="<?= intval($v['available_quantity'] ?? 1) ?>"
                                            data-specification="<?= htmlspecialchars($v['specification'] ?? '') ?>"
                                            data-remarks="<?= htmlspecialchars($v['remarks'] ?? '') ?>"
                                            data-bs-toggle="modal" data-bs-target="#editVehicleModal"
                                            title="Edit Vehicle">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <a href="processors/office_assets_crud.php?action=delete_vehicle&id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete Vehicle">
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
                                <th>Vehicle Info</th>
                                <th>Work Performed</th>
                                <th>Service Station / Garage</th>
                                <th>Invoice / Voucher #</th>
                                <th>Total Cost (LKR)</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($repairs_list as $rep): ?>
                                <tr>
                                    <td class="fw-bold text-nowrap"><?= date('Y-m-d', strtotime($rep['repair_date'])) ?></td>
                                    <td>
                                        <span class="badge bg-primary fs-6"><?= htmlspecialchars($rep['vehicle_number'] ?? 'N/A') ?></span>
                                        <div class="small text-muted"><?= htmlspecialchars($rep['vehicle_type'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark"><?= htmlspecialchars($rep['work_done']) ?></span>
                                        <?php if (!empty($rep['description'])): ?>
                                            <div class="small text-muted"><?= htmlspecialchars($rep['description']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($rep['service_station'] ?: '-') ?></td>
                                    <td class="font-monospace small"><?= htmlspecialchars($rep['invoice_ref'] ?: '-') ?></td>
                                    <td class="fw-bold text-success text-nowrap">Rs. <?= number_format($rep['total_cost'], 2) ?></td>
                                    <td class="text-center text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary me-1 btn-edit-repair"
                                            data-id="<?= $rep['id'] ?>"
                                            data-vehicle_id="<?= $rep['vehicle_id'] ?>"
                                            data-date="<?= $rep['repair_date'] ?>"
                                            data-done="<?= htmlspecialchars($rep['work_done']) ?>"
                                            data-desc="<?= htmlspecialchars($rep['description'] ?? '') ?>"
                                            data-place="<?= htmlspecialchars($rep['service_station'] ?? '') ?>"
                                            data-invoice="<?= htmlspecialchars($rep['invoice_ref'] ?? '') ?>"
                                            data-amount="<?= $rep['total_cost'] ?>"
                                            data-bs-toggle="modal" data-bs-target="#editRepairModal"
                                            title="Edit Repair">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <a href="processors/office_assets_crud.php?action=delete_repair&id=<?= $rep['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete Log">
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

</div>

<!-- Modal 1: Add Vehicle -->
<div class="modal fade" id="addVehicleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #b08723;">
                <h5 class="modal-title fw-bold"><i class="bi bi-truck me-2"></i>Register Specialist Fleet Vehicle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="save_vehicle">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Vehicle Registration Number <span class="text-danger">*</span></label>
                            <input type="text" name="vehicle_number" class="form-control" placeholder="e.g. EP-GA-1025 / 62-4521" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Vehicle Category / Role <span class="text-danger">*</span></label>
                            <select name="vehicle_type" class="form-select" required>
                                <option value="Mobile Veterinary Clinical Van" selected>Mobile Veterinary Clinical Van</option>
                                <option value="Epidemiological Surveillance Jeep">Epidemiological Surveillance Jeep</option>
                                <option value="Rapid Response Disease Control Unit">Rapid Response Disease Control Unit</option>
                                <option value="Vaccine Cold Chain Transport Van">Vaccine Cold Chain Transport Van</option>
                                <option value="Field Officer Motorbike">Field Officer Motorbike</option>
                                <option value="Other Departmental Vehicle">Other Departmental Vehicle</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Chassis / Engine Number</label>
                            <input type="text" name="chassis_number" class="form-control" placeholder="e.g. JTF-451298412">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Current Operational Condition</label>
                            <select name="current_condition" id="add_veh_condition" class="form-select" onchange="calcAddVehicle()">
                                <option value="Operational (Good Condition)" selected>Operational (Good Condition)</option>
                                <option value="Operational (Needs Service)">Operational (Needs Service)</option>
                                <option value="Under Repair in Garage">Under Repair in Garage</option>
                                <option value="Condemned / Non-Operational">Condemned / Non-Operational</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Issue Order No.</label>
                            <input type="text" name="issue_order_no" class="form-control" placeholder="e.g. IO-2024-001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Received From</label>
                            <input type="text" name="received_from" class="form-control" placeholder="e.g. Kachcheri / Head Office">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Receipt No.</label>
                            <input type="text" name="receipt_no" class="form-control" placeholder="e.g. REC-1234">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Initial Baseline Stock</label>
                            <input type="number" name="initial_count" id="add_veh_initial_count" class="form-control fw-bold" value="1" min="0" required oninput="calcAddVehicle()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Received Quantity</label>
                            <input type="number" name="received_quantity" id="add_veh_received_quantity" class="form-control fw-bold" value="0" min="0" required oninput="calcAddVehicle()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Current Availability</label>
                            <input type="number" name="available_quantity" id="add_veh_available_quantity" class="form-control fw-bold bg-light" value="1" readonly>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Specification (Brand / Model / Specs)</label>
                            <textarea name="specification" class="form-control" rows="2" placeholder="e.g. Toyota Hilux Double Cab 4x4, 2.4L Diesel..."></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Installed Technical Equipment &amp; Features</label>
                            <textarea name="other_details" class="form-control" rows="2" placeholder="e.g. Mounted 12V vaccine mini-fridge, post-mortem table, examination kit storage"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. Allocated to Field SMS Officer..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: #b08723;">
                        <i class="bi bi-check-circle-fill me-1"></i>Register Vehicle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Edit Vehicle -->
<div class="modal fade" id="editVehicleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #b08723;">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Vehicle Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="update_vehicle">
                <input type="hidden" name="id" id="edit_vehicle_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Vehicle Registration Number <span class="text-danger">*</span></label>
                            <input type="text" name="vehicle_number" id="edit_vehicle_number" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Vehicle Category / Role <span class="text-danger">*</span></label>
                            <input type="text" name="vehicle_type" id="edit_vehicle_type" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Chassis / Engine Number</label>
                            <input type="text" name="chassis_number" id="edit_chassis_number" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Operational Condition</label>
                            <select name="current_condition" id="edit_current_condition" class="form-select" onchange="calcEditVehicle()">
                                <option value="Operational (Good Condition)">Operational (Good Condition)</option>
                                <option value="Operational (Needs Service)">Operational (Needs Service)</option>
                                <option value="Under Repair in Garage">Under Repair in Garage</option>
                                <option value="Condemned / Non-Operational">Condemned / Non-Operational</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Issue Order No.</label>
                            <input type="text" name="issue_order_no" id="edit_vehicle_issue_order_no" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Received From</label>
                            <input type="text" name="received_from" id="edit_vehicle_received_from" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Receipt No.</label>
                            <input type="text" name="receipt_no" id="edit_vehicle_receipt_no" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Initial Baseline Stock</label>
                            <input type="number" name="initial_count" id="edit_veh_initial_count" class="form-control fw-bold" min="0" required oninput="calcEditVehicle()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Received Quantity</label>
                            <input type="number" name="received_quantity" id="edit_veh_received_quantity" class="form-control fw-bold" min="0" required oninput="calcEditVehicle()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Current Availability</label>
                            <input type="number" name="available_quantity" id="edit_vehicle_quantity" class="form-control fw-bold bg-light" readonly>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Specification (Brand / Model / Specs)</label>
                            <textarea name="specification" id="edit_vehicle_specification" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Installed Technical Equipment</label>
                            <textarea name="other_details" id="edit_other_details" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Remarks</label>
                            <textarea name="remarks" id="edit_vehicle_remarks" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: #b08723;">
                        <i class="bi bi-check-circle-fill me-1"></i>Update Vehicle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Add Repair Log -->
<div class="modal fade" id="addRepairModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-wrench me-2"></i>Log Vehicle Maintenance &amp; Repair</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="save_repair">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Select Vehicle <span class="text-danger">*</span></label>
                            <select name="vehicle_id" class="form-select" required>
                                <option value="">-- Choose Registered Fleet Vehicle --</option>
                                <?php foreach ($vehicles_list as $vl): ?>
                                    <option value="<?= $vl['id'] ?>"><?= htmlspecialchars($vl['vehicle_number']) ?> (<?= htmlspecialchars($vl['vehicle_type']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Repair / Service Date</label>
                            <input type="date" name="repair_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Repair Performed / Service Type <span class="text-danger">*</span></label>
                            <input type="text" name="repair_done" class="form-control" placeholder="e.g. Brake pad replacement &amp; 10,000km Engine Service" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Total Cost (LKR)</label>
                            <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Place of Repair / Garage Name</label>
                            <input type="text" name="place_of_repair" class="form-control" placeholder="e.g. Government District Workshop / Auto Service Centre">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Invoice / Bill Reference Number</label>
                            <input type="text" name="invoice_ref" class="form-control" placeholder="e.g. INV-2026-8842">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Additional Scope &amp; Parts Replaced</label>
                            <textarea name="repair_description" class="form-control" rows="2" placeholder="e.g. Oil filter, fuel filter, mobile freezer power cable rewiring"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark fw-bold px-4">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Repair Log
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 4: Edit Repair Log -->
<div class="modal fade" id="editRepairModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Repair Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="update_repair">
                <input type="hidden" name="id" id="edit_repair_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Vehicle</label>
                            <select name="vehicle_id" id="edit_repair_vehicle_id" class="form-select" required>
                                <?php foreach ($vehicles_list as $vl): ?>
                                    <option value="<?= $vl['id'] ?>"><?= htmlspecialchars($vl['vehicle_number']) ?> (<?= htmlspecialchars($vl['vehicle_type']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Service Date</label>
                            <input type="date" name="repair_date" id="edit_repair_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Repair Done <span class="text-danger">*</span></label>
                            <input type="text" name="repair_done" id="edit_repair_done" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Cost (LKR)</label>
                            <input type="number" step="0.01" name="amount" id="edit_repair_amount" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Place of Repair</label>
                            <input type="text" name="place_of_repair" id="edit_repair_place" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Invoice Reference</label>
                            <input type="text" name="invoice_ref" id="edit_repair_invoice" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Description</label>
                            <textarea name="repair_description" id="edit_repair_desc" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark fw-bold px-4">
                        <i class="bi bi-check-circle-fill me-1"></i>Update Log
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function calcAddVehicle() {
    const base = parseInt(document.getElementById('add_veh_initial_count').value) || 0;
    const recv = parseInt(document.getElementById('add_veh_received_quantity').value) || 0;
    const cond = document.getElementById('add_veh_condition').value;
    let qty = base + recv;
    if (cond.includes('Garage') || cond.includes('Condemned') || cond.includes('Non-Operational') || cond.includes('Repair')) {
        qty = Math.max(0, qty - 1);
    }
    document.getElementById('add_veh_available_quantity').value = Math.max(0, qty);
}
function calcEditVehicle() {
    const base = parseInt(document.getElementById('edit_veh_initial_count').value) || 0;
    const recv = parseInt(document.getElementById('edit_veh_received_quantity').value) || 0;
    const cond = document.getElementById('edit_current_condition').value;
    let qty = base + recv;
    if (cond.includes('Garage') || cond.includes('Condemned') || cond.includes('Non-Operational') || cond.includes('Repair')) {
        qty = Math.max(0, qty - 1);
    }
    document.getElementById('edit_vehicle_quantity').value = Math.max(0, qty);
}

document.addEventListener('DOMContentLoaded', function() {
    $(document).on('click', '.btn-edit-vehicle', function() {
        const btn = $(this);
        $('#edit_vehicle_id').val(btn.data('id'));
        $('#edit_vehicle_type').val(btn.data('type'));
        $('#edit_vehicle_number').val(btn.data('number'));
        $('#edit_chassis_number').val(btn.data('chassis'));
        $('#edit_current_condition').val(btn.data('condition'));
        $('#edit_other_details').val(btn.data('other'));
        $('#edit_vehicle_issue_order_no').val(btn.data('issue_order_no'));
        $('#edit_vehicle_received_from').val(btn.data('received_from'));
        $('#edit_vehicle_receipt_no').val(btn.data('receipt_no'));
        $('#edit_veh_initial_count').val(btn.data('initial_count') !== undefined ? btn.data('initial_count') : btn.data('quantity'));
        $('#edit_veh_received_quantity').val(btn.data('received_quantity') !== undefined ? btn.data('received_quantity') : 0);
        $('#edit_vehicle_specification').val(btn.data('specification'));
        $('#edit_vehicle_remarks').val(btn.data('remarks'));
        calcEditVehicle();
    });

    $(document).on('click', '.btn-edit-repair', function() {
        const btn = $(this);
        $('#edit_repair_id').val(btn.data('id'));
        $('#edit_repair_vehicle_id').val(btn.data('vehicle_id'));
        $('#edit_repair_date').val(btn.data('date'));
        $('#edit_repair_done').val(btn.data('done'));
        $('#edit_repair_desc').val(btn.data('desc'));
        $('#edit_repair_place').val(btn.data('place'));
        $('#edit_repair_invoice').val(btn.data('invoice'));
        $('#edit_repair_amount').val(btn.data('amount'));
    });

    if ($.fn.DataTable) {
        $('#vehiclesTable').DataTable({ responsive: true, pageLength: 10 });
        $('#repairsTable').DataTable({ responsive: true, pageLength: 10 });
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
