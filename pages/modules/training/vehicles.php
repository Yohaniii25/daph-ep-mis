<?php
// pages/modules/training/vehicles.php -> Training Centre Fleet Vehicles & Repairs Registry
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

$allowed_roles = ['training_officer', 'administrator', 'provincial_director', 'district_dd'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;
$current_center_id = $_SESSION['training_center_id'] ?? null;
if (empty($current_center_id) && isset($_GET['center_id'])) {
    $current_center_id = intval($_GET['center_id']);
}
if (empty($current_center_id)) {
    $c_res = $mysqli->query("SELECT id FROM training_centers WHERE is_active = 1 LIMIT 1");
    if ($c_res && $row = $c_res->fetch_assoc()) {
        $current_center_id = $row['id'];
    } else {
        $current_center_id = 1;
    }
}

// Fetch Registered Vehicles for current Training Centre
$vehicles_stmt = $mysqli->prepare("SELECT * FROM registered_vehicles WHERE (training_center_id = ? OR (user_id = ? AND user_category = 'training_centers')) AND is_active = 1 ORDER BY id DESC");
$vehicles_stmt->bind_param("ii", $current_center_id, $user_id);
$vehicles_stmt->execute();
$vehicles_list = $vehicles_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$vehicles_stmt->close();

// Fetch Vehicle Repair Logs for current Training Centre
$repairs_stmt = $mysqli->prepare("
    SELECT vr.*, rv.vehicle_number, rv.vehicle_type 
    FROM vehicle_repairs vr 
    LEFT JOIN registered_vehicles rv ON vr.vehicle_id = rv.id 
    WHERE (vr.training_center_id = ? OR (vr.user_id = ? AND vr.user_category = 'training_centers')) 
    ORDER BY vr.id DESC
");
$repairs_stmt->bind_param("ii", $current_center_id, $user_id);
$repairs_stmt->execute();
$repairs_list = $repairs_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$repairs_stmt->close();

$active_tab = $_GET['tab'] ?? 'vehicles';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="bi bi-car-front-fill me-2" style="color: #b08723;"></i>Vehicles &amp; Repairs Management
        </h3>
        <p class="text-muted small mb-0">Official fleet registry, condition monitoring and maintenance logs for Training Centre</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn text-light shadow-sm fw-bold" style="background-color: #b08723;" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
            <i class="bi bi-plus-circle-fill me-2"></i>Register New Vehicle
        </button>
        <button class="btn btn-dark shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addRepairModal">
            <i class="bi bi-tools me-2"></i>Log Maintenance / Repair
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
            <i class="bi bi-car-front-fill me-2"></i>Registered Vehicles (<?= count($vehicles_list) ?>)
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-bold <?= ($active_tab === 'repairs') ? 'active' : '' ?>" id="repairs-tab" data-bs-toggle="tab" data-bs-target="#repairs-content" type="button" role="tab" style="--bs-nav-pills-link-active-bg: #b08723;">
            <i class="bi bi-wrench-adjustable me-2"></i>Repair &amp; Maintenance Logs (<?= count($repairs_list) ?>)
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
                                <th>Vehicle Type</th>
                                <th>Registration Number</th>
                                <th>Chassis Number</th>
                                <th>Current Condition</th>
                                <th>Other Notes</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicles_list as $veh): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($veh['vehicle_type']) ?></td>
                                    <td><span class="badge bg-light text-dark border px-3 py-2 fs-6 font-monospace"><?= htmlspecialchars($veh['vehicle_number']) ?></span></td>
                                    <td class="font-monospace text-secondary"><?= htmlspecialchars($veh['chassis_number'] ?: '-') ?></td>
                                    <td>
                                        <?php
                                            $cond = $veh['current_condition'];
                                            $badge_class = (strpos($cond, 'Good') !== false || strpos($cond, 'Running') !== false) ? 'bg-success' : ((strpos($cond, 'Repair') !== false || strpos($cond, 'Service') !== false) ? 'bg-warning text-dark' : 'bg-danger');
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
                                <th>Vehicle</th>
                                <th>Date of Repair</th>
                                <th>Nature of Repair</th>
                                <th>Garage / Service Center</th>
                                <th>Invoice Ref</th>
                                <th class="text-end">Cost (Rs.)</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($repairs_list as $rep): ?>
                                <tr>
                                    <td>
                                        <strong class="text-dark"><?= htmlspecialchars($rep['vehicle_number'] ?? 'N/A') ?></strong>
                                        <small class="text-muted d-block"><?= htmlspecialchars($rep['vehicle_type'] ?? '') ?></small>
                                    </td>
                                    <td class="font-monospace text-secondary"><?= htmlspecialchars($rep['repair_date']) ?></td>
                                    <td>
                                        <strong class="text-primary"><?= htmlspecialchars($rep['repair_done']) ?></strong>
                                        <?php if (!empty($rep['repair_description'])): ?>
                                            <small class="text-muted d-block"><?= htmlspecialchars($rep['repair_description']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($rep['place_of_repair'] ?: '-') ?></td>
                                    <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($rep['invoice_ref'] ?: '-') ?></span></td>
                                    <td class="text-end font-monospace fw-bold text-danger">Rs. <?= number_format(floatval($rep['amount']), 2) ?></td>
                                    <td class="text-center text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary me-1 btn-edit-repair"
                                            data-id="<?= $rep['id'] ?>"
                                            data-vehicle_id="<?= $rep['vehicle_id'] ?>"
                                            data-repair_date="<?= htmlspecialchars($rep['repair_date']) ?>"
                                            data-repair_done="<?= htmlspecialchars($rep['repair_done']) ?>"
                                            data-repair_description="<?= htmlspecialchars($rep['repair_description'] ?? '') ?>"
                                            data-place_of_repair="<?= htmlspecialchars($rep['place_of_repair'] ?? '') ?>"
                                            data-invoice_ref="<?= htmlspecialchars($rep['invoice_ref'] ?? '') ?>"
                                            data-amount="<?= $rep['amount'] ?>"
                                            data-bs-toggle="modal" data-bs-target="#editRepairModal"
                                            title="Edit Repair">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <a href="processors/office_assets_crud.php?action=delete_repair&id=<?= $rep['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete Repair">
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
    <div class="modal-dialog modal-dialog-centered">
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
                            <option value="Van / Crew Cab">Van / Crew Cab</option>
                            <option value="Double Cab (4WD)">Double Cab (4WD)</option>
                            <option value="Tractor / Trailer">Tractor / Trailer</option>
                            <option value="Motorcycle">Motorcycle</option>
                            <option value="Truck / Lorry">Truck / Lorry</option>
                            <option value="Other Vehicle">Other Vehicle</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Registration Number <span class="text-danger">*</span></label>
                        <input type="text" name="vehicle_number" class="form-control fw-bold font-monospace" placeholder="e.g. WP NA-5821" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Chassis Number</label>
                        <input type="text" name="chassis_number" class="form-control font-monospace" placeholder="e.g. CH-84920489">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Current Condition <span class="text-danger">*</span></label>
                        <select name="current_condition" class="form-select fw-bold" required>
                            <option value="Running Condition (Good)">Running Condition (Good)</option>
                            <option value="Needs Minor Repairs">Needs Minor Repairs</option>
                            <option value="Major Repair Required">Major Repair Required</option>
                            <option value="Condemned / Out of Service">Condemned / Out of Service</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Other Details / Driver</label>
                        <textarea name="other_details" class="form-control" rows="2" placeholder="Driver assignment, fuel specs..."></textarea>
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
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c10, #185dbd);">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Vehicle Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="update_vehicle">
                <input type="hidden" name="id" id="edit_vehicle_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Vehicle Type <span class="text-danger">*</span></label>
                        <select name="vehicle_type" id="edit_vehicle_type" class="form-select fw-bold" required>
                            <option value="Van / Crew Cab">Van / Crew Cab</option>
                            <option value="Double Cab (4WD)">Double Cab (4WD)</option>
                            <option value="Tractor / Trailer">Tractor / Trailer</option>
                            <option value="Motorcycle">Motorcycle</option>
                            <option value="Truck / Lorry">Truck / Lorry</option>
                            <option value="Other Vehicle">Other Vehicle</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Registration Number <span class="text-danger">*</span></label>
                        <input type="text" name="vehicle_number" id="edit_vehicle_number" class="form-control fw-bold font-monospace" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Chassis Number</label>
                        <input type="text" name="chassis_number" id="edit_chassis_number" class="form-control font-monospace">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Current Condition <span class="text-danger">*</span></label>
                        <select name="current_condition" id="edit_vehicle_condition" class="form-select fw-bold" required>
                            <option value="Running Condition (Good)">Running Condition (Good)</option>
                            <option value="Needs Minor Repairs">Needs Minor Repairs</option>
                            <option value="Major Repair Required">Major Repair Required</option>
                            <option value="Condemned / Out of Service">Condemned / Out of Service</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Other Details / Driver</label>
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

<!-- Modal 3: Log Vehicle Repair -->
<div class="modal fade" id="addRepairModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-tools me-2"></i>Log Vehicle Maintenance / Repair</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="save_repair">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Target Vehicle <span class="text-danger">*</span></label>
                        <select name="vehicle_id" class="form-select fw-bold" required>
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
                            <label class="form-label fw-bold">Cost (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" class="form-control fw-bold" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nature of Repair / Service <span class="text-danger">*</span></label>
                        <input type="text" name="repair_done" class="form-control fw-bold" placeholder="e.g. Engine Overhaul, Brake Pad Replacement" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Garage / Service Center</label>
                        <input type="text" name="place_of_repair" class="form-control" placeholder="e.g. Provincial Motor Works">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Invoice / Bill Reference</label>
                        <input type="text" name="invoice_ref" class="form-control" placeholder="e.g. INV-2026-089">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Detailed Description</label>
                        <textarea name="repair_description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark fw-bold px-4">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Repair Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 4: Edit Vehicle Repair -->
<div class="modal fade" id="editRepairModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c10, #185dbd);">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Vehicle Repair</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="update_repair">
                <input type="hidden" name="id" id="edit_repair_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Target Vehicle <span class="text-danger">*</span></label>
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
                            <label class="form-label fw-bold">Cost (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" id="edit_repair_amount" class="form-control fw-bold" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nature of Repair / Service <span class="text-danger">*</span></label>
                        <input type="text" name="repair_done" id="edit_repair_done" class="form-control fw-bold" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Garage / Service Center</label>
                        <input type="text" name="place_of_repair" id="edit_repair_place" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Invoice / Bill Reference</label>
                        <input type="text" name="invoice_ref" id="edit_repair_invoice" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Detailed Description</label>
                        <textarea name="repair_description" id="edit_repair_description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--color-c10, #185dbd);">
                        <i class="bi bi-check-circle-fill me-1"></i>Update Repair
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
        $('#edit_repair_done').val(btn.data('repair_done'));
        $('#edit_repair_description').val(btn.data('repair_description'));
        $('#edit_repair_place').val(btn.data('place_of_repair'));
        $('#edit_repair_invoice').val(btn.data('invoice_ref'));
        $('#edit_repair_amount').val(btn.data('amount'));
    });

    if ($.fn.DataTable) {
        $('#vehiclesTable').DataTable({ responsive: true, pageLength: 10 });
        $('#repairsTable').DataTable({ responsive: true, pageLength: 10 });
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
