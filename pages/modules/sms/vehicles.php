<?php
// pages/modules/sms/vehicles.php -> SMS Vehicle Fleet & Mobile Clinic Maintenance Registry
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

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
                                <th>Current Condition</th>
                                <th>Equipment / Role Details</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicles_list as $v): ?>
                                <tr>
                                    <td><span class="fw-bold fs-6 text-primary"><?= htmlspecialchars($v['vehicle_number']) ?></span></td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($v['vehicle_type']) ?></td>
                                    <td class="small text-muted font-monospace"><?= htmlspecialchars($v['chassis_number'] ?: '-') ?></td>
                                    <td>
                                        <?php
                                            $cond = $v['current_condition'];
                                            $badge_class = (strpos($cond, 'Operational') !== false || strpos($cond, 'Good') !== false) ? 'bg-success' : ((strpos($cond, 'Maintenance') !== false || strpos($cond, 'Repair') !== false) ? 'bg-warning text-dark' : 'bg-danger');
                                        ?>
                                        <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($cond) ?></span>
                                    </td>
                                    <td class="small text-muted"><?= htmlspecialchars($v['other_details'] ?: '-') ?></td>
                                    <td class="text-center text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary me-1 btn-edit-vehicle"
                                            data-id="<?= $v['id'] ?>"
                                            data-type="<?= htmlspecialchars($v['vehicle_type']) ?>"
                                            data-number="<?= htmlspecialchars($v['vehicle_number']) ?>"
                                            data-chassis="<?= htmlspecialchars($v['chassis_number'] ?? '') ?>"
                                            data-condition="<?= htmlspecialchars($v['current_condition']) ?>"
                                            data-other="<?= htmlspecialchars($v['other_details'] ?? '') ?>"
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
                                    <td class="small text-nowrap"><?= date('Y-m-d', strtotime($rep['repair_date'])) ?></td>
                                    <td>
                                        <span class="fw-bold text-dark"><?= htmlspecialchars($rep['vehicle_number'] ?: 'N/A') ?></span>
                                        <small class="d-block text-muted"><?= htmlspecialchars($rep['vehicle_type'] ?: '') ?></small>
                                    </td>
                                    <td>
                                        <strong class="text-dark"><?= htmlspecialchars($rep['repair_done']) ?></strong>
                                        <?php if (!empty($rep['repair_description'])): ?>
                                            <small class="d-block text-muted"><?= htmlspecialchars($rep['repair_description']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small"><?= htmlspecialchars($rep['place_of_repair'] ?: '-') ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($rep['invoice_ref'] ?: '-') ?></span></td>
                                    <td class="fw-bold text-success text-nowrap">Rs. <?= number_format(floatval($rep['amount']), 2) ?></td>
                                    <td class="text-center text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary me-1 btn-edit-repair"
                                            data-id="<?= $rep['id'] ?>"
                                            data-vehicle_id="<?= $rep['vehicle_id'] ?>"
                                            data-date="<?= $rep['repair_date'] ?>"
                                            data-done="<?= htmlspecialchars($rep['repair_done']) ?>"
                                            data-desc="<?= htmlspecialchars($rep['repair_description'] ?? '') ?>"
                                            data-place="<?= htmlspecialchars($rep['place_of_repair'] ?? '') ?>"
                                            data-invoice="<?= htmlspecialchars($rep['invoice_ref'] ?? '') ?>"
                                            data-amount="<?= $rep['amount'] ?>"
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
                            <select name="current_condition" class="form-select">
                                <option value="Operational (Good Condition)" selected>Operational (Good Condition)</option>
                                <option value="Operational (Needs Service)">Operational (Needs Service)</option>
                                <option value="Under Repair in Garage">Under Repair in Garage</option>
                                <option value="Condemned / Non-Operational">Condemned / Non-Operational</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Installed Technical Equipment &amp; Features</label>
                            <textarea name="other_details" class="form-control" rows="2" placeholder="e.g. Mounted 12V vaccine mini-fridge, post-mortem table, examination kit storage"></textarea>
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
                            <select name="current_condition" id="edit_current_condition" class="form-select">
                                <option value="Operational (Good Condition)">Operational (Good Condition)</option>
                                <option value="Operational (Needs Service)">Operational (Needs Service)</option>
                                <option value="Under Repair in Garage">Under Repair in Garage</option>
                                <option value="Condemned / Non-Operational">Condemned / Non-Operational</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Installed Technical Equipment</label>
                            <textarea name="other_details" id="edit_other_details" class="form-control" rows="2"></textarea>
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
document.addEventListener('DOMContentLoaded', function() {
    $(document).on('click', '.btn-edit-vehicle', function() {
        const btn = $(this);
        $('#edit_vehicle_id').val(btn.data('id'));
        $('#edit_vehicle_type').val(btn.data('type'));
        $('#edit_vehicle_number').val(btn.data('number'));
        $('#edit_chassis_number').val(btn.data('chassis'));
        $('#edit_current_condition').val(btn.data('condition'));
        $('#edit_other_details').val(btn.data('other'));
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
