<?php
// pages/modules/farm/machineries.php -> Machinery & Equipment Inventory Registry (Regional Farm)
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

if ($_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;
$farm_id = $_SESSION['farm_id'] ?? null;

// Fetch Machinery Assets for current Regional Farm
$stmt = $mysqli->prepare("SELECT * FROM machinery_assets WHERE (farm_id = ? OR user_id = ?) ORDER BY id DESC");
$stmt->bind_param("ii", $farm_id, $user_id);
$stmt->execute();
$machinery_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="bi bi-gear-fill me-2" style="color: #689ccf;"></i>Machineries &amp; Farm Equipment Registry
        </h3>
        <p class="text-muted small mb-0">Regional Farm machinery assets, chaff cutters, milking machines &amp; pumps</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn text-light shadow-sm fw-bold" style="background-color: #689ccf;" data-bs-toggle="modal" data-bs-target="#addMachineryModal">
            <i class="bi bi-plus-circle-fill me-2"></i>Register New Machinery Asset
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
                    confirmButtonColor: '#689ccf',
                    timer: 3500,
                    timerProgressBar: true
                });
            }
        });
    </script>
<?php endif; ?>

<div class="card shadow-sm border-0" style="border-radius: 12px;">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table id="machineryTable" class="table table-hover align-middle w-100">
                <thead class="table-dark" style="background-color: #370709;">
                    <tr>
                        <th>Machinery Asset Category</th>
                        <th>Available Quantity</th>
                        <th>Purchase / Reg Date</th>
                        <th>Current Condition</th>
                        <th>Remarks / Notes</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($machinery_list as $mach): ?>
                        <tr>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($mach['machinery_type']) ?></td>
                            <td class="fw-bold fs-6"><span class="badge bg-light text-dark border px-3 py-2 fs-6"><?= intval($mach['available_quantity']) ?></span></td>
                            <td><?= !empty($mach['purchase_date']) ? date('Y-m-d', strtotime($mach['purchase_date'])) : '-' ?></td>
                            <td>
                                <?php
                                    $cond = $mach['current_condition'];
                                    $badge_class = (strpos($cond, 'Operational') !== false || strpos($cond, 'Good') !== false) ? 'bg-success' : ((strpos($cond, 'Minor') !== false || strpos($cond, 'Repair') !== false) ? 'bg-warning text-dark' : 'bg-danger');
                                ?>
                                <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($cond) ?></span>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars($mach['remarks'] ?: '-') ?></td>
                            <td class="text-center text-nowrap">
                                <button class="btn btn-sm btn-outline-primary me-1 btn-edit-machinery"
                                    data-id="<?= $mach['id'] ?>"
                                    data-machinery_type="<?= htmlspecialchars($mach['machinery_type']) ?>"
                                    data-available_quantity="<?= $mach['available_quantity'] ?>"
                                    data-purchase_date="<?= htmlspecialchars($mach['purchase_date'] ?? '') ?>"
                                    data-current_condition="<?= htmlspecialchars($mach['current_condition']) ?>"
                                    data-remarks="<?= htmlspecialchars($mach['remarks'] ?? '') ?>"
                                    data-bs-toggle="modal" data-bs-target="#editMachineryModal"
                                    title="Edit Machinery">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>
                                <a href="processors/office_assets_crud.php?action=delete_machinery&id=<?= $mach['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete Machinery">
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

<!-- Modal 1: Register Machinery Asset -->
<div class="modal fade" id="addMachineryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #689ccf;">
                <h5 class="modal-title fw-bold"><i class="bi bi-gear-fill me-2"></i>Register New Machinery Asset</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="save_machinery">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Machinery Category <span class="text-danger">*</span></label>
                        <select name="machinery_type" class="form-select fw-bold" required>
                            <option value="Grass Cutter / Lawn Mower">Grass Cutter / Lawn Mower</option>
                            <option value="Water Pump">Water Pump</option>
                            <option value="Milking Machine">Milking Machine</option>
                            <option value="Chaff Cutter">Chaff Cutter</option>
                            <option value="Generator">Generator</option>
                            <option value="Incubator">Incubator</option>
                            <option value="Feed Mixer">Feed Mixer</option>
                            <option value="Spray Pump">Spray Pump</option>
                            <option value="Other Machinery">Other Machinery</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="available_quantity" class="form-control fw-bold" value="1" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Purchase Date</label>
                            <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Current Condition <span class="text-danger">*</span></label>
                        <select name="current_condition" class="form-select fw-bold" required>
                            <option value="Operational / Good">Operational / Good</option>
                            <option value="Needs Minor Repair">Needs Minor Repair</option>
                            <option value="Out of Service">Out of Service</option>
                            <option value="Condemned">Condemned</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks / Notes</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Model, capacity, engine serial #..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: #689ccf;">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Machinery Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Edit Machinery Asset -->
<div class="modal fade" id="editMachineryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c10, #185dbd);">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Machinery Asset</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="update_machinery">
                <input type="hidden" name="id" id="edit_machinery_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Machinery Category <span class="text-danger">*</span></label>
                        <select name="machinery_type" id="edit_machinery_type" class="form-select fw-bold" required>
                            <option value="Grass Cutter / Lawn Mower">Grass Cutter / Lawn Mower</option>
                            <option value="Water Pump">Water Pump</option>
                            <option value="Milking Machine">Milking Machine</option>
                            <option value="Chaff Cutter">Chaff Cutter</option>
                            <option value="Generator">Generator</option>
                            <option value="Incubator">Incubator</option>
                            <option value="Feed Mixer">Feed Mixer</option>
                            <option value="Spray Pump">Spray Pump</option>
                            <option value="Other Machinery">Other Machinery</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="available_quantity" id="edit_machinery_qty" class="form-control fw-bold" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Purchase Date</label>
                            <input type="date" name="purchase_date" id="edit_machinery_date" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Current Condition <span class="text-danger">*</span></label>
                        <select name="current_condition" id="edit_machinery_condition" class="form-select fw-bold" required>
                            <option value="Operational / Good">Operational / Good</option>
                            <option value="Needs Minor Repair">Needs Minor Repair</option>
                            <option value="Out of Service">Out of Service</option>
                            <option value="Condemned">Condemned</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks / Notes</label>
                        <textarea name="remarks" id="edit_machinery_remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--color-c10, #185dbd);">
                        <i class="bi bi-check-circle-fill me-1"></i>Update Machinery Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $(document).on('click', '.btn-edit-machinery', function() {
        const btn = $(this);
        $('#edit_machinery_id').val(btn.data('id'));
        $('#edit_machinery_type').val(btn.data('machinery_type'));
        $('#edit_machinery_qty').val(btn.data('available_quantity'));
        $('#edit_machinery_date').val(btn.data('purchase_date'));
        $('#edit_machinery_condition').val(btn.data('current_condition'));
        $('#edit_machinery_remarks').val(btn.data('remarks'));
    });
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
