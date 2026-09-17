<?php
// pages/modules/sms/furniture.php -> Furniture & Workstation Registry (SMS Directorate)
require_once '../../../includes/header.php';
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$allowed_roles = ['sms', 'administrator', 'provincial_director', 'district_dd'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 12;

// Fetch Furniture Assets for current Subject Matter Specialist
$stmt = $mysqli->prepare("SELECT * FROM furniture_assets WHERE (user_category = 'subject_matter_specialist' OR user_id = ?) ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$furniture_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="bi bi-file-earmark-plus me-2" style="color: #a07174;"></i>Furniture &amp; Technical Fittings
        </h3>
        <p class="text-muted small mb-0">Subject Matter Specialist workstations, laboratory desks, cold-depot fittings &amp; specimen cabinets</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn text-light shadow-sm fw-bold" style="background-color: #a07174;" data-bs-toggle="modal" data-bs-target="#addFurnitureModal">
            <i class="bi bi-plus-circle-fill me-2"></i>Register Furniture Item
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
                    confirmButtonColor: '#a07174',
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
            <table id="furnitureTable" class="table table-hover align-middle w-100">
                <thead class="table-dark" style="background-color: #370709;">
                    <tr>
                        <th>Furniture Asset Category</th>
                        <th>Issue Order No.</th>
                        <th>Received From</th>
                        <th>Receipt No.</th>
                        <th>Quantity</th>
                        <th>Date Received</th>
                        <th>Current Condition</th>
                        <th>Specification / Remarks</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($furniture_list as $furn): ?>
                        <tr>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($furn['furniture_type']) ?></td>
                            <td><?= htmlspecialchars($furn['issue_order_no'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($furn['received_from'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($furn['receipt_no'] ?: '-') ?></td>
                            <td class="text-center">
                                <span class="badge bg-primary fs-6 px-2 py-1"><?= sprintf("%02d", $furn['available_quantity'] ?? 1) ?></span>
                                <br>
                                <small class="text-muted" style="font-size:10px;" title="Baseline + Received">Base: <?= intval($furn['initial_count'] ?? 1) ?> | Recv: <?= intval($furn['received_quantity'] ?? 0) ?></small>
                            </td>
                            <td><?= !empty($furn['date_received']) ? date('Y-m-d', strtotime($furn['date_received'])) : '-' ?></td>
                            <td>
                                <?php
                                    $cond = $furn['current_condition'];
                                    $badge_class = (strpos($cond, 'Good') !== false || strpos($cond, 'Excellent') !== false) ? 'bg-success' : ((strpos($cond, 'Requires') !== false || strpos($cond, 'Needs') !== false) ? 'bg-warning text-dark' : 'bg-danger');
                                ?>
                                <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($cond) ?></span>
                            </td>
                            <td>
                                <?php if (!empty($furn['specification'])): ?>
                                    <div class="fw-semibold text-dark small mb-1"><?= htmlspecialchars($furn['specification']) ?></div>
                                <?php endif; ?>
                                <div class="small text-muted"><?= htmlspecialchars($furn['remarks'] ?: '-') ?></div>
                            </td>
                            <td class="text-center text-nowrap">
                                <button class="btn btn-sm btn-outline-primary me-1 btn-edit-furniture"
                                    data-id="<?= $furn['id'] ?>"
                                    data-furniture_type="<?= htmlspecialchars($furn['furniture_type']) ?>"
                                    data-issue_order_no="<?= htmlspecialchars($furn['issue_order_no'] ?? '') ?>"
                                    data-received_from="<?= htmlspecialchars($furn['received_from'] ?? '') ?>"
                                    data-receipt_no="<?= htmlspecialchars($furn['receipt_no'] ?? '') ?>"
                                    data-initial_count="<?= intval($furn['initial_count'] ?? 1) ?>"
                                    data-received_quantity="<?= intval($furn['received_quantity'] ?? 0) ?>"
                                    data-available_quantity="<?= $furn['available_quantity'] ?>"
                                    data-date_received="<?= htmlspecialchars($furn['date_received'] ?? '') ?>"
                                    data-current_condition="<?= htmlspecialchars($furn['current_condition']) ?>"
                                    data-specification="<?= htmlspecialchars($furn['specification'] ?? '') ?>"
                                    data-remarks="<?= htmlspecialchars($furn['remarks'] ?? '') ?>"
                                    data-bs-toggle="modal" data-bs-target="#editFurnitureModal"
                                    title="Edit Furniture">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>
                                <a href="processors/office_assets_crud.php?action=delete_furniture&id=<?= $furn['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete Furniture">
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

<!-- Modal 1: Register Furniture Asset -->
<div class="modal fade" id="addFurnitureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #a07174;">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle-fill me-2"></i>Register Specialist Furniture Asset</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="save_furniture">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Furniture Item / Category <span class="text-danger">*</span></label>
                            <input type="text" name="furniture_type" class="form-control" placeholder="e.g. Epidemiology Analysis Workstation / Steel Specimen Cabinet" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Issue Order No.</label>
                            <input type="text" name="issue_order_no" class="form-control" placeholder="e.g. IO-2024-001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Received From</label>
                            <input type="text" name="received_from" class="form-control" placeholder="e.g. Provincial Store / Supplier">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Receipt No.</label>
                            <input type="text" name="receipt_no" class="form-control" placeholder="e.g. REC-1234">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date Received / Assigned</label>
                            <input type="date" name="date_received" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Initial Baseline Stock</label>
                            <input type="number" name="initial_count" id="add_furn_initial_count" class="form-control fw-bold" value="1" min="0" required oninput="calcAddFurniture()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Received Quantity</label>
                            <input type="number" name="received_quantity" id="add_furn_received_quantity" class="form-control fw-bold" value="0" min="0" required oninput="calcAddFurniture()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Current Availability</label>
                            <input type="number" name="available_quantity" id="add_furn_available_quantity" class="form-control fw-bold bg-light" value="1" readonly>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Current Physical Condition</label>
                            <select name="current_condition" class="form-select">
                                <option value="Excellent Condition">Excellent Condition</option>
                                <option value="Good Condition" selected>Good Condition</option>
                                <option value="Needs Minor Maintenance">Needs Minor Maintenance</option>
                                <option value="Requires Replacement / Condemned">Requires Replacement / Condemned</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Specification (Brand / Model / Specs)</label>
                            <textarea name="specification" class="form-control" rows="2" placeholder="e.g. Damro Steel Cabinet, 4 Shelves..."></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Remarks / Location Placement</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. Main SMS Analysis Office Room #2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: #a07174;">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Furniture
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Edit Furniture Asset -->
<div class="modal fade" id="editFurnitureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #a07174;">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Furniture Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="update_furniture">
                <input type="hidden" name="id" id="edit_furniture_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Furniture Item / Category <span class="text-danger">*</span></label>
                            <input type="text" name="furniture_type" id="edit_furniture_type" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Issue Order No.</label>
                            <input type="text" name="issue_order_no" id="edit_furniture_issue_order_no" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Received From</label>
                            <input type="text" name="received_from" id="edit_furniture_received_from" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Receipt No.</label>
                            <input type="text" name="receipt_no" id="edit_furniture_receipt_no" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date Received</label>
                            <input type="date" name="date_received" id="edit_date_received" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Initial Baseline Stock</label>
                            <input type="number" name="initial_count" id="edit_furn_initial_count" class="form-control fw-bold" min="0" required oninput="calcEditFurniture()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Received Quantity</label>
                            <input type="number" name="received_quantity" id="edit_furn_received_quantity" class="form-control fw-bold" min="0" required oninput="calcEditFurniture()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Current Availability</label>
                            <input type="number" name="available_quantity" id="edit_available_quantity" class="form-control fw-bold bg-light" readonly>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Physical Condition</label>
                            <select name="current_condition" id="edit_current_condition" class="form-select">
                                <option value="Excellent Condition">Excellent Condition</option>
                                <option value="Good Condition">Good Condition</option>
                                <option value="Needs Minor Maintenance">Needs Minor Maintenance</option>
                                <option value="Requires Replacement / Condemned">Requires Replacement / Condemned</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Specification (Brand / Model / Specs)</label>
                            <textarea name="specification" id="edit_furniture_specification" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Remarks / Location Placement</label>
                            <textarea name="remarks" id="edit_remarks" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: #a07174;">
                        <i class="bi bi-check-circle-fill me-1"></i>Update Furniture
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function calcAddFurniture() {
    const base = parseInt(document.getElementById('add_furn_initial_count').value) || 0;
    const recv = parseInt(document.getElementById('add_furn_received_quantity').value) || 0;
    document.getElementById('add_furn_available_quantity').value = Math.max(0, base + recv);
}
function calcEditFurniture() {
    const base = parseInt(document.getElementById('edit_furn_initial_count').value) || 0;
    const recv = parseInt(document.getElementById('edit_furn_received_quantity').value) || 0;
    document.getElementById('edit_available_quantity').value = Math.max(0, base + recv);
}

document.addEventListener('DOMContentLoaded', function() {
    $(document).on('click', '.btn-edit-furniture', function() {
        const btn = $(this);
        $('#edit_furniture_id').val(btn.data('id'));
        $('#edit_furniture_type').val(btn.data('furniture_type'));
        $('#edit_furniture_issue_order_no').val(btn.data('issue_order_no'));
        $('#edit_furniture_received_from').val(btn.data('received_from'));
        $('#edit_furniture_receipt_no').val(btn.data('receipt_no'));
        $('#edit_furn_initial_count').val(btn.data('initial_count') !== undefined ? btn.data('initial_count') : btn.data('available_quantity'));
        $('#edit_furn_received_quantity').val(btn.data('received_quantity') !== undefined ? btn.data('received_quantity') : 0);
        $('#edit_date_received').val(btn.data('date_received'));
        $('#edit_current_condition').val(btn.data('current_condition'));
        $('#edit_furniture_specification').val(btn.data('specification'));
        $('#edit_remarks').val(btn.data('remarks'));
        calcEditFurniture();
    });

    if ($.fn.DataTable) {
        $('#furnitureTable').DataTable({ responsive: true, pageLength: 10 });
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
