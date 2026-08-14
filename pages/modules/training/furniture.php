<?php
// pages/modules/training/furniture.php -> Furniture & Fittings Inventory Registry (Training Centre)
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

// Fetch Furniture Assets for current Training Centre
$stmt = $mysqli->prepare("SELECT * FROM furniture_assets WHERE (training_center_id = ? OR (user_id = ? AND user_category = 'training_centers')) ORDER BY id DESC");
$stmt->bind_param("ii", $current_center_id, $user_id);
$stmt->execute();
$furniture_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="bi bi-file-earmark-plus me-2" style="color: #a07174;"></i>Furniture &amp; Fittings Inventory
        </h3>
        <p class="text-muted small mb-0">Training Centre desks, lecture benches, conference chairs and fixtures registry</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn text-light shadow-sm fw-bold" style="background-color: #a07174;" data-bs-toggle="modal" data-bs-target="#addFurnitureModal">
            <i class="bi bi-plus-circle-fill me-2"></i>Register New Furniture Asset
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
                        <th>Available Quantity</th>
                        <th>Date Received</th>
                        <th>Current Condition</th>
                        <th>Remarks / Notes</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($furniture_list as $furn): ?>
                        <tr>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($furn['furniture_type']) ?></td>
                            <td class="fw-bold fs-6"><span class="badge bg-light text-dark border px-3 py-2 fs-6"><?= intval($furn['available_quantity']) ?></span></td>
                            <td><?= !empty($furn['date_received']) ? date('Y-m-d', strtotime($furn['date_received'])) : '-' ?></td>
                            <td>
                                <?php
                                    $cond = $furn['current_condition'];
                                    $badge_class = (strpos($cond, 'Good') !== false || strpos($cond, 'Excellent') !== false) ? 'bg-success' : ((strpos($cond, 'Requires') !== false || strpos($cond, 'Needs') !== false) ? 'bg-warning text-dark' : 'bg-danger');
                                ?>
                                <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($cond) ?></span>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars($furn['remarks'] ?: '-') ?></td>
                            <td class="text-center text-nowrap">
                                <button class="btn btn-sm btn-outline-primary me-1 btn-edit-furniture"
                                    data-id="<?= $furn['id'] ?>"
                                    data-furniture_type="<?= htmlspecialchars($furn['furniture_type']) ?>"
                                    data-available_quantity="<?= $furn['available_quantity'] ?>"
                                    data-date_received="<?= htmlspecialchars($furn['date_received'] ?? '') ?>"
                                    data-current_condition="<?= htmlspecialchars($furn['current_condition']) ?>"
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
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #a07174;">
                <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-plus me-2"></i>Register New Furniture Asset</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="save_furniture">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Furniture / Fitting Category <span class="text-danger">*</span></label>
                        <select name="furniture_type" class="form-select fw-bold" required>
                            <option value="Lecture Hall Desks">Lecture Hall Desks</option>
                            <option value="Auditorium Chairs">Auditorium Chairs</option>
                            <option value="Executive Office Desks">Executive Office Desks</option>
                            <option value="Office Chairs">Office Chairs</option>
                            <option value="Filing Cabinets">Filing Cabinets</option>
                            <option value="Conference Tables">Conference Tables</option>
                            <option value="Steel Cupboards">Steel Cupboards</option>
                            <option value="Dining Tables &amp; Benches">Dining Tables &amp; Benches</option>
                            <option value="Hostel Beds / Bunks">Hostel Beds / Bunks</option>
                            <option value="Other Fittings">Other Fittings</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="available_quantity" class="form-control fw-bold" value="1" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date Received</label>
                            <input type="date" name="date_received" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Current Condition <span class="text-danger">*</span></label>
                        <select name="current_condition" class="form-select fw-bold" required>
                            <option value="Excellent / New">Excellent / New</option>
                            <option value="Good Condition">Good Condition</option>
                            <option value="Requires Repair">Requires Repair</option>
                            <option value="Unserviceable">Unserviceable</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks / Notes</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Item specs, room location..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: #a07174;">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Furniture Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Edit Furniture Asset -->
<div class="modal fade" id="editFurnitureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c10, #185dbd);">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Furniture Asset</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="update_furniture">
                <input type="hidden" name="id" id="edit_furniture_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Furniture / Fitting Category <span class="text-danger">*</span></label>
                        <select name="furniture_type" id="edit_furniture_type" class="form-select fw-bold" required>
                            <option value="Lecture Hall Desks">Lecture Hall Desks</option>
                            <option value="Auditorium Chairs">Auditorium Chairs</option>
                            <option value="Executive Office Desks">Executive Office Desks</option>
                            <option value="Office Chairs">Office Chairs</option>
                            <option value="Filing Cabinets">Filing Cabinets</option>
                            <option value="Conference Tables">Conference Tables</option>
                            <option value="Steel Cupboards">Steel Cupboards</option>
                            <option value="Dining Tables &amp; Benches">Dining Tables &amp; Benches</option>
                            <option value="Hostel Beds / Bunks">Hostel Beds / Bunks</option>
                            <option value="Other Fittings">Other Fittings</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="available_quantity" id="edit_furniture_qty" class="form-control fw-bold" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date Received</label>
                            <input type="date" name="date_received" id="edit_furniture_date" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Current Condition <span class="text-danger">*</span></label>
                        <select name="current_condition" id="edit_furniture_condition" class="form-select fw-bold" required>
                            <option value="Excellent / New">Excellent / New</option>
                            <option value="Good Condition">Good Condition</option>
                            <option value="Requires Repair">Requires Repair</option>
                            <option value="Unserviceable">Unserviceable</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks / Notes</label>
                        <textarea name="remarks" id="edit_furniture_remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--color-c10, #185dbd);">
                        <i class="bi bi-check-circle-fill me-1"></i>Update Furniture Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $(document).on('click', '.btn-edit-furniture', function() {
        const btn = $(this);
        $('#edit_furniture_id').val(btn.data('id'));
        $('#edit_furniture_type').val(btn.data('furniture_type'));
        $('#edit_furniture_qty').val(btn.data('available_quantity'));
        $('#edit_furniture_date').val(btn.data('date_received'));
        $('#edit_furniture_condition').val(btn.data('current_condition'));
        $('#edit_furniture_remarks').val(btn.data('remarks'));
    });

    if ($.fn.DataTable) {
        $('#furnitureTable').DataTable({ responsive: true, pageLength: 10 });
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
