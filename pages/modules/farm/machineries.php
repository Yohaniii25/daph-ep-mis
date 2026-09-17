<?php
// pages/modules/farm/machineries.php -> Machinery & Equipment Inventory Registry (Regional Farm)
require_once '../../../includes/header.php';
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

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
                        <th>Issue Order No.</th>
                        <th>Received From</th>
                        <th>Receipt No.</th>
                        <th>Quantity</th>
                        <th>Purchase / Reg Date</th>
                        <th>Current Condition</th>
                        <th>Specification / Remarks</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($machinery_list as $mach): ?>
                        <tr>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($mach['machinery_type']) ?></td>
                            <td><?= htmlspecialchars($mach['issue_order_no'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($mach['received_from'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($mach['receipt_no'] ?: '-') ?></td>
                            <td class="text-center">
                                <span class="badge bg-primary fs-6 px-2 py-1"><?= sprintf("%02d", $mach['available_quantity'] ?? 1) ?></span>
                                <br>
                                <small class="text-muted" style="font-size:10px;" title="Baseline + Received">Base: <?= intval($mach['initial_count'] ?? 1) ?> | Recv: <?= intval($mach['received_quantity'] ?? 0) ?></small>
                            </td>
                            <td><?= !empty($mach['purchase_date']) ? date('Y-m-d', strtotime($mach['purchase_date'])) : '-' ?></td>
                            <td>
                                <?php
                                    $cond = $mach['current_condition'];
                                    $badge_class = (strpos($cond, 'Operational') !== false || strpos($cond, 'Good') !== false) ? 'bg-success' : ((strpos($cond, 'Minor') !== false || strpos($cond, 'Repair') !== false) ? 'bg-warning text-dark' : 'bg-danger');
                                ?>
                                <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($cond) ?></span>
                            </td>
                            <td>
                                <?php if (!empty($mach['specification'])): ?>
                                    <div class="fw-semibold text-dark small mb-1"><?= htmlspecialchars($mach['specification']) ?></div>
                                <?php endif; ?>
                                <div class="small text-muted"><?= htmlspecialchars($mach['remarks'] ?: '-') ?></div>
                            </td>
                            <td class="text-center text-nowrap">
                                <button class="btn btn-sm btn-outline-primary me-1 btn-edit-machinery"
                                    data-id="<?= $mach['id'] ?>"
                                    data-machinery_type="<?= htmlspecialchars($mach['machinery_type']) ?>"
                                    data-issue_order_no="<?= htmlspecialchars($mach['issue_order_no'] ?? '') ?>"
                                    data-received_from="<?= htmlspecialchars($mach['received_from'] ?? '') ?>"
                                    data-receipt_no="<?= htmlspecialchars($mach['receipt_no'] ?? '') ?>"
                                    data-initial_count="<?= intval($mach['initial_count'] ?? 1) ?>"
                                    data-received_quantity="<?= intval($mach['received_quantity'] ?? 0) ?>"
                                    data-available_quantity="<?= $mach['available_quantity'] ?>"
                                    data-purchase_date="<?= htmlspecialchars($mach['purchase_date'] ?? '') ?>"
                                    data-current_condition="<?= htmlspecialchars($mach['current_condition']) ?>"
                                    data-specification="<?= htmlspecialchars($mach['specification'] ?? '') ?>"
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
                            <label class="form-label fw-bold">Issue Order No.</label>
                            <input type="text" name="issue_order_no" class="form-control" placeholder="e.g. IO-2024-001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Received From</label>
                            <input type="text" name="received_from" class="form-control" placeholder="e.g. Head Office / Supplier">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Receipt No.</label>
                            <input type="text" name="receipt_no" class="form-control" placeholder="e.g. REC-1234">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Purchase Date</label>
                            <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Initial Baseline Stock</label>
                            <input type="number" name="initial_count" id="add_machinery_initial_count" class="form-control fw-bold" value="1" min="0" required oninput="calcAddMachinery()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Received Quantity</label>
                            <input type="number" name="received_quantity" id="add_machinery_received_quantity" class="form-control fw-bold" value="0" min="0" required oninput="calcAddMachinery()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Current Availability</label>
                            <input type="number" name="available_quantity" id="add_machinery_available_quantity" class="form-control fw-bold bg-light" value="1" readonly>
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
                        <label class="form-label fw-bold">Specification (Brand / Model / Specs)</label>
                        <textarea name="specification" class="form-control" rows="2" placeholder="e.g. Brand, Engine Capacity, Model, Power..."></textarea>
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
                            <label class="form-label fw-bold">Issue Order No.</label>
                            <input type="text" name="issue_order_no" id="edit_machinery_issue_order_no" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Received From</label>
                            <input type="text" name="received_from" id="edit_machinery_received_from" class="form-control">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Receipt No.</label>
                            <input type="text" name="receipt_no" id="edit_machinery_receipt_no" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Purchase Date</label>
                            <input type="date" name="purchase_date" id="edit_machinery_date" class="form-control">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Initial Baseline Stock</label>
                            <input type="number" name="initial_count" id="edit_machinery_initial_count" class="form-control fw-bold" min="0" required oninput="calcEditMachinery()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Received Quantity</label>
                            <input type="number" name="received_quantity" id="edit_machinery_received_quantity" class="form-control fw-bold" min="0" required oninput="calcEditMachinery()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Current Availability</label>
                            <input type="number" name="available_quantity" id="edit_machinery_qty" class="form-control fw-bold bg-light" readonly>
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
                        <label class="form-label fw-bold">Specification (Brand / Model / Specs)</label>
                        <textarea name="specification" id="edit_machinery_specification" class="form-control" rows="2"></textarea>
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
function calcAddMachinery() {
    const base = parseInt(document.getElementById('add_machinery_initial_count').value) || 0;
    const recv = parseInt(document.getElementById('add_machinery_received_quantity').value) || 0;
    document.getElementById('add_machinery_available_quantity').value = Math.max(0, base + recv);
}
function calcEditMachinery() {
    const base = parseInt(document.getElementById('edit_machinery_initial_count').value) || 0;
    const recv = parseInt(document.getElementById('edit_machinery_received_quantity').value) || 0;
    document.getElementById('edit_machinery_qty').value = Math.max(0, base + recv);
}

document.addEventListener('DOMContentLoaded', function() {
    $(document).on('click', '.btn-edit-machinery', function() {
        const btn = $(this);
        $('#edit_machinery_id').val(btn.data('id'));
        $('#edit_machinery_type').val(btn.data('machinery_type'));
        $('#edit_machinery_issue_order_no').val(btn.data('issue_order_no'));
        $('#edit_machinery_received_from').val(btn.data('received_from'));
        $('#edit_machinery_receipt_no').val(btn.data('receipt_no'));
        $('#edit_machinery_initial_count').val(btn.data('initial_count') !== undefined ? btn.data('initial_count') : btn.data('available_quantity'));
        $('#edit_machinery_received_quantity').val(btn.data('received_quantity') !== undefined ? btn.data('received_quantity') : 0);
        $('#edit_machinery_date').val(btn.data('purchase_date'));
        $('#edit_machinery_condition').val(btn.data('current_condition'));
        $('#edit_machinery_specification').val(btn.data('specification'));
        $('#edit_machinery_remarks').val(btn.data('remarks'));
        calcEditMachinery();
    });
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
