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
                                <th>Condition</th>
                                <th>Details / Remarks</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicles_list as $veh): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($veh['vehicle_type']) ?></td>
                                    <td><span class="badge bg-dark text-light border px-2 fs-6"><?= htmlspecialchars($veh['vehicle_number']) ?></span></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($veh['chassis_number'] ?: '-') ?></span></td>
                                    <td>
                                        <?php
                                            $cond = $veh['current_condition'];
                                            $badge_class = ($cond === 'Good/Running') ? 'bg-success' : (($cond === 'Needs Repair') ? 'bg-warning text-dark' : 'bg-danger');
                                        ?>
                                        <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($cond) ?></span>
                                    </td>
                                    <td class="small text-muted"><?= htmlspecialchars($veh['other_details'] ?: '-') ?></td>
                                    <td class="text-center text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary me-1 btn-edit-vehicle"
                                            data-id="<?= $veh['id'] ?>"
                                            data-vehicle_type="<?= htmlspecialchars($veh['vehicle_type']) ?>"
                                            data-vehicle_number="<?= htmlspecialchars($veh['vehicle_number']) ?>"
                                            data-chassis_number="<?= htmlspecialchars($veh['chassis_number'] ?? '') ?>"
                                            data-current_condition="<?= htmlspecialchars($veh['current_condition']) ?>"
                                            data-other_details="<?= htmlspecialchars($veh['other_details'] ?? '') ?>"
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
                            <?php foreach ($repairs_list as $rep): 
                                $r_nature = !empty($rep['repair_nature']) ? $rep['repair_nature'] : ($rep['repair_done'] ?? '');
                                $r_cost = isset($rep['cost_lkr']) ? $rep['cost_lkr'] : ($rep['amount'] ?? 0);
                                $r_by = !empty($rep['repaired_by']) ? $rep['repaired_by'] : ($rep['place_of_repair'] ?? '');
                                $r_remarks = !empty($rep['remarks']) ? $rep['remarks'] : ($rep['repair_description'] ?? '');
                            ?>
                                <tr>
                                    <td class="fw-bold text-nowrap"><?= date('Y-m-d', strtotime($rep['repair_date'])) ?></td>
                                    <td class="fw-bold text-dark">
                                        <?= htmlspecialchars($rep['vehicle_number'] ?: 'Vehicle #' . $rep['vehicle_id']) ?>
                                        <small class="d-block text-muted"><?= htmlspecialchars($rep['vehicle_type'] ?? '') ?></small>
                                    </td>
                                    <td class="fw-semibold text-primary"><?= htmlspecialchars($r_nature) ?></td>
                                    <td class="fw-bold text-danger fs-6">LKR <?= number_format(floatval($r_cost), 2) ?></td>
                                    <td><?= htmlspecialchars($r_by ?: '-') ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($rep['invoice_ref'] ?: '-') ?></span></td>
                                    <td class="small text-muted"><?= htmlspecialchars($r_remarks ?: '-') ?></td>
                                    <td class="text-center text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary me-1 btn-edit-repair"
                                            data-id="<?= $rep['id'] ?>"
                                            data-vehicle_id="<?= $rep['vehicle_id'] ?>"
                                            data-repair_date="<?= htmlspecialchars($rep['repair_date']) ?>"
                                            data-repair_nature="<?= htmlspecialchars($r_nature) ?>"
                                            data-cost_lkr="<?= $r_cost ?>"
                                            data-repaired_by="<?= htmlspecialchars($r_by) ?>"
                                            data-invoice_ref="<?= htmlspecialchars($rep['invoice_ref'] ?? '') ?>"
                                            data-remarks="<?= htmlspecialchars($r_remarks) ?>"
                                            data-bs-toggle="modal" data-bs-target="#editRepairModal"
                                            title="Edit Repair Log">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <a href="processors/office_assets_crud.php?action=delete_vehicle_repair&id=<?= $rep['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete Log">
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
                            <label class="form-label fw-bold">Reg Number <span class="text-danger">*</span></label>
                            <input type="text" name="vehicle_number" class="form-control fw-bold" placeholder="e.g. WP QA-4821" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Chassis Number</label>
                            <input type="text" name="chassis_number" class="form-control" placeholder="e.g. CH-991823">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Current Condition <span class="text-danger">*</span></label>
                        <select name="current_condition" class="form-select fw-bold" required>
                            <option value="Good/Running">Good/Running</option>
                            <option value="Needs Repair">Needs Repair</option>
                            <option value="Condemned/Unserviceable">Condemned/Unserviceable</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Other Details / Engine Specs</label>
                        <textarea name="other_details" class="form-control" rows="2" placeholder="Model year, capacity, fuel type..."></textarea>
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
                            <input type="text" name="vehicle_number" id="edit_vehicle_number" class="form-control fw-bold" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Chassis Number</label>
                            <input type="text" name="chassis_number" id="edit_chassis_number" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Current Condition <span class="text-danger">*</span></label>
                        <select name="current_condition" id="edit_vehicle_condition" class="form-select fw-bold" required>
                            <option value="Good/Running">Good/Running</option>
                            <option value="Needs Repair">Needs Repair</option>
                            <option value="Condemned/Unserviceable">Condemned/Unserviceable</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Other Details / Engine Specs</label>
                        <textarea name="other_details" id="edit_other_details" class="form-control" rows="2"></textarea>
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

<!-- Modal 3: Log Repair Work -->
<div class="modal fade" id="addRepairModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light bg-dark">
                <h5 class="modal-title fw-bold"><i class="bi bi-wrench-adjustable me-2"></i>Log Vehicle Repair Work</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="save_vehicle_repair">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Vehicle <span class="text-danger">*</span></label>
                        <select name="vehicle_id" class="form-select fw-bold" required>
                            <?php foreach ($vehicles_list as $v): ?>
                                <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['vehicle_number']) ?> (<?= htmlspecialchars($v['vehicle_type']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="repair_date" class="form-control fw-bold" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Cost (LKR)</label>
                            <input type="number" step="0.01" name="cost_lkr" class="form-control fw-bold text-danger" placeholder="0.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nature of Repair <span class="text-danger">*</span></label>
                        <input type="text" name="repair_nature" class="form-control" placeholder="e.g. Engine Overhaul / Tire Replacement / Brake Service" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Repaired By (Garage/Mechanic)</label>
                            <input type="text" name="repaired_by" class="form-control" placeholder="e.g. DAPH Central Workshop">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Invoice / Voucher Ref</label>
                            <input type="text" name="invoice_ref" class="form-control" placeholder="e.g. INV-9912">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks / Notes</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Additional repair notes..."></textarea>
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

<!-- Modal 4: Edit Vehicle Repair -->
<div class="modal fade" id="editRepairModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c10, #185dbd);">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Vehicle Repair Log</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="update_vehicle_repair">
                <input type="hidden" name="id" id="edit_repair_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Vehicle <span class="text-danger">*</span></label>
                        <select name="vehicle_id" id="edit_repair_vehicle_id" class="form-select fw-bold" required>
                            <?php foreach ($vehicles_list as $v): ?>
                                <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['vehicle_number']) ?> (<?= htmlspecialchars($v['vehicle_type']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="repair_date" id="edit_repair_date" class="form-control fw-bold" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Cost (LKR)</label>
                            <input type="number" step="0.01" name="cost_lkr" id="edit_cost_lkr" class="form-control fw-bold text-danger">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nature of Repair <span class="text-danger">*</span></label>
                        <input type="text" name="repair_nature" id="edit_repair_nature" class="form-control" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Repaired By</label>
                            <input type="text" name="repaired_by" id="edit_repaired_by" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Invoice / Voucher Ref</label>
                            <input type="text" name="invoice_ref" id="edit_invoice_ref" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks / Notes</label>
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
document.addEventListener('DOMContentLoaded', function() {
    $(document).on('click', '.btn-edit-vehicle', function() {
        const btn = $(this);
        $('#edit_vehicle_id').val(btn.data('id'));
        $('#edit_vehicle_type').val(btn.data('vehicle_type'));
        $('#edit_vehicle_number').val(btn.data('vehicle_number'));
        $('#edit_chassis_number').val(btn.data('chassis_number'));
        $('#edit_vehicle_condition').val(btn.data('current_condition'));
        $('#edit_other_details').val(btn.data('other_details'));
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
