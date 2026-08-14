<?php
// pages/modules/training/counter_foilage.php -> Counter Foil Books & Receipt Vouchers Registry (Training Centre)
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

// Fetch Counter Foil Assets for current Training Centre
$stmt = $mysqli->prepare("SELECT * FROM counterfoil_assets WHERE (training_center_id = ? OR (user_id = ? AND user_category = 'training_centers')) ORDER BY id DESC");
$stmt->bind_param("ii", $current_center_id, $user_id);
$stmt->execute();
$counterfoils_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="bi bi-file-earmark-text-fill me-2" style="color: #e65100;"></i>Counter Foil &amp; Receipt Books Registry
        </h3>
        <p class="text-muted small mb-0">Official receipt books, revenue vouchers and Form A.D.30 / General 172 counter foil management</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn text-light shadow-sm fw-bold" style="background-color: #e65100;" data-bs-toggle="modal" data-bs-target="#addCounterfoilModal">
            <i class="bi bi-plus-circle-fill me-2"></i>Register Counter Foil Book
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
                    confirmButtonColor: '#e65100',
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
            <table id="counterfoilTable" class="table table-hover align-middle w-100">
                <thead class="table-dark" style="background-color: #370709;">
                    <tr>
                        <th>Counter Foil / Book Type</th>
                        <th>Available Books</th>
                        <th>Date Received / Opened</th>
                        <th>Current Status</th>
                        <th>Book Numbers / Serial Range</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($counterfoils_list as $cf): ?>
                        <tr>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($cf['counterfoil_type']) ?></td>
                            <td class="fw-bold fs-6"><span class="badge bg-light text-dark border px-3 py-2 fs-6"><?= intval($cf['available_quantity']) ?></span></td>
                            <td><?= !empty($cf['purchase_date']) ? date('Y-m-d', strtotime($cf['purchase_date'])) : '-' ?></td>
                            <td>
                                <?php
                                    $cond = $cf['current_condition'];
                                    $badge_class = (strpos($cond, 'Active') !== false || strpos($cond, 'In Use') !== false) ? 'bg-success' : ((strpos($cond, 'Completed') !== false || strpos($cond, 'Closed') !== false) ? 'bg-secondary' : 'bg-warning text-dark');
                                ?>
                                <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($cond) ?></span>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars($cf['remarks'] ?: '-') ?></td>
                            <td class="text-center text-nowrap">
                                <button class="btn btn-sm btn-outline-primary me-1 btn-edit-cf"
                                    data-id="<?= $cf['id'] ?>"
                                    data-counterfoil_type="<?= htmlspecialchars($cf['counterfoil_type']) ?>"
                                    data-available_quantity="<?= $cf['available_quantity'] ?>"
                                    data-purchase_date="<?= htmlspecialchars($cf['purchase_date'] ?? '') ?>"
                                    data-current_condition="<?= htmlspecialchars($cf['current_condition']) ?>"
                                    data-remarks="<?= htmlspecialchars($cf['remarks'] ?? '') ?>"
                                    data-bs-toggle="modal" data-bs-target="#editCounterfoilModal"
                                    title="Edit Counter Foil">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>
                                <a href="processors/office_assets_crud.php?action=delete_counterfoil&id=<?= $cf['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete Counter Foil">
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

<!-- Modal 1: Register Counter Foil Book -->
<div class="modal fade" id="addCounterfoilModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #e65100;">
                <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-text-fill me-2"></i>Register Counter Foil Book</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="save_counterfoil">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Counter Foil Book Category <span class="text-danger">*</span></label>
                        <select name="counterfoil_type" class="form-select fw-bold" required>
                            <option value="General 172 (Receipt Book)">General 172 (Receipt Book)</option>
                            <option value="Form A.D.30 (Produce Register / Perishables)">Form A.D.30 (Produce Register / Perishables)</option>
                            <option value="General 35 (Cheque / Payment Voucher)">General 35 (Cheque / Payment Voucher)</option>
                            <option value="Cash Receipt Voucher (CR-Book)">Cash Receipt Voucher (CR-Book)</option>
                            <option value="Training Course Fee Receipt Book">Training Course Fee Receipt Book</option>
                            <option value="Hostel &amp; Accommodation Receipt Book">Hostel &amp; Accommodation Receipt Book</option>
                            <option value="Issue Order Book (Store / Seed / Animals)">Issue Order Book (Store / Seed / Animals)</option>
                            <option value="Other Counter Foil Books">Other Counter Foil Books</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Number of Books <span class="text-danger">*</span></label>
                            <input type="number" name="available_quantity" class="form-control fw-bold" value="1" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date Received / Opened</label>
                            <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Current Status <span class="text-danger">*</span></label>
                        <select name="current_condition" class="form-select fw-bold" required>
                            <option value="In Use / Active">In Use / Active</option>
                            <option value="New / Unopened in Safe">New / Unopened in Safe</option>
                            <option value="Completed / Archived">Completed / Archived</option>
                            <option value="Returned to Treasury">Returned to Treasury</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Serial Numbers / Page Range</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. Book No. 104, Leaf 001 to 100..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: #e65100;">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Book Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Edit Counter Foil Book -->
<div class="modal fade" id="editCounterfoilModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c10, #185dbd);">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Counter Foil Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="update_counterfoil">
                <input type="hidden" name="id" id="edit_cf_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Counter Foil Book Category <span class="text-danger">*</span></label>
                        <select name="counterfoil_type" id="edit_cf_type" class="form-select fw-bold" required>
                            <option value="General 172 (Receipt Book)">General 172 (Receipt Book)</option>
                            <option value="Form A.D.30 (Produce Register / Perishables)">Form A.D.30 (Produce Register / Perishables)</option>
                            <option value="General 35 (Cheque / Payment Voucher)">General 35 (Cheque / Payment Voucher)</option>
                            <option value="Cash Receipt Voucher (CR-Book)">Cash Receipt Voucher (CR-Book)</option>
                            <option value="Training Course Fee Receipt Book">Training Course Fee Receipt Book</option>
                            <option value="Hostel &amp; Accommodation Receipt Book">Hostel &amp; Accommodation Receipt Book</option>
                            <option value="Issue Order Book (Store / Seed / Animals)">Issue Order Book (Store / Seed / Animals)</option>
                            <option value="Other Counter Foil Books">Other Counter Foil Books</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Number of Books <span class="text-danger">*</span></label>
                            <input type="number" name="available_quantity" id="edit_cf_qty" class="form-control fw-bold" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date Received / Opened</label>
                            <input type="date" name="purchase_date" id="edit_cf_date" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Current Status <span class="text-danger">*</span></label>
                        <select name="current_condition" id="edit_cf_condition" class="form-select fw-bold" required>
                            <option value="In Use / Active">In Use / Active</option>
                            <option value="New / Unopened in Safe">New / Unopened in Safe</option>
                            <option value="Completed / Archived">Completed / Archived</option>
                            <option value="Returned to Treasury">Returned to Treasury</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Serial Numbers / Page Range</label>
                        <textarea name="remarks" id="edit_cf_remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--color-c10, #185dbd);">
                        <i class="bi bi-check-circle-fill me-1"></i>Update Book Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $(document).on('click', '.btn-edit-cf', function() {
        const btn = $(this);
        $('#edit_cf_id').val(btn.data('id'));
        $('#edit_cf_type').val(btn.data('counterfoil_type'));
        $('#edit_cf_qty').val(btn.data('available_quantity'));
        $('#edit_cf_date').val(btn.data('purchase_date'));
        $('#edit_cf_condition').val(btn.data('current_condition'));
        $('#edit_cf_remarks').val(btn.data('remarks'));
    });

    if ($.fn.DataTable) {
        $('#counterfoilTable').DataTable({ responsive: true, pageLength: 10 });
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
