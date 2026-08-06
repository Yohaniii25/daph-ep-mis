<?php
// pages/modules/farm/counter_foilage.php -> Counter Foil & Receipt Book Registry (Regional Farm)
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

if ($_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;
$farm_id = $_SESSION['farm_id'] ?? null;

// Fetch Counterfoil Assets for current Regional Farm
$stmt = $mysqli->prepare("SELECT * FROM counterfoil_assets WHERE (farm_id = ? OR user_id = ?) ORDER BY id DESC");
$stmt->bind_param("ii", $farm_id, $user_id);
$stmt->execute();
$counterfoil_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="bi bi-file-earmark-text-fill me-2" style="color: #e65100;"></i>Counter Foil &amp; Receipt Book Registry
        </h3>
        <p class="text-muted small mb-0">Regional Farm official receipt books, vouchers, store notes, and permits</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn text-light shadow-sm fw-bold" style="background-color: #e65100;" data-bs-toggle="modal" data-bs-target="#addCounterfoilModal">
            <i class="bi bi-plus-circle-fill me-2"></i>Register New Counter Foil
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
                        <th>Available Books / Quantity</th>
                        <th>Issued / Reg Date</th>
                        <th>Status / Condition</th>
                        <th>Remarks / Serial Ranges</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($counterfoil_list as $cf): ?>
                        <tr>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($cf['counterfoil_type']) ?></td>
                            <td class="fw-bold fs-6"><span class="badge bg-light text-dark border px-3 py-2 fs-6"><?= intval($cf['available_quantity']) ?></span></td>
                            <td><?= !empty($cf['purchase_date']) ? date('Y-m-d', strtotime($cf['purchase_date'])) : '-' ?></td>
                            <td>
                                <?php
                                    $cond = $cf['current_condition'];
                                    $badge_class = (strpos($cond, 'Active') !== false || strpos($cond, 'In Use') !== false) ? 'bg-success' : ((strpos($cond, 'Archived') !== false || strpos($cond, 'Completed') !== false) ? 'bg-info text-white' : 'bg-danger');
                                ?>
                                <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($cond) ?></span>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars($cf['remarks'] ?: '-') ?></td>
                            <td class="text-center text-nowrap">
                                <button class="btn btn-sm btn-outline-primary me-1 btn-edit-counterfoil"
                                    data-id="<?= $cf['id'] ?>"
                                    data-counterfoil_type="<?= htmlspecialchars($cf['counterfoil_type']) ?>"
                                    data-available_quantity="<?= $cf['available_quantity'] ?>"
                                    data-purchase_date="<?= htmlspecialchars($cf['purchase_date'] ?? '') ?>"
                                    data-current_condition="<?= htmlspecialchars($cf['current_condition']) ?>"
                                    data-remarks="<?= htmlspecialchars($cf['remarks'] ?? '') ?>"
                                    data-bs-toggle="modal" data-bs-target="#editCounterfoilModal"
                                    title="Edit Counterfoil">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>
                                <a href="processors/office_assets_crud.php?action=delete_counterfoil&id=<?= $cf['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete Counterfoil">
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

<!-- Modal 1: Register Counter Foil -->
<div class="modal fade" id="addCounterfoilModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #e65100;">
                <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-text-fill me-2"></i>Register New Counter Foil</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="save_counterfoil">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Counter Foil / Book Category <span class="text-danger">*</span></label>
                        <select name="counterfoil_type" class="form-select fw-bold" required>
                            <option value="General Receipt Book">General Receipt Book</option>
                            <option value="Credit Sale Book">Credit Sale Book</option>
                            <option value="Voucher Book">Voucher Book</option>
                            <option value="Store Issue Note">Store Issue Note</option>
                            <option value="Gate Pass Book">Gate Pass Book</option>
                            <option value="Permit Book">Permit Book</option>
                            <option value="Other Books">Other Books</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Quantity (Books) <span class="text-danger">*</span></label>
                            <input type="number" name="available_quantity" class="form-control fw-bold" value="1" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date Received / Issued</label>
                            <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status / Condition <span class="text-danger">*</span></label>
                        <select name="current_condition" class="form-select fw-bold" required>
                            <option value="Active / In Use">Active / In Use</option>
                            <option value="Completed / Archived">Completed / Archived</option>
                            <option value="Damaged / Cancelled">Damaged / Cancelled</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks / Serial Number Range</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. Serial # 001001 to 001100"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: #e65100;">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Counter Foil
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Edit Counter Foil -->
<div class="modal fade" id="editCounterfoilModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c10, #185dbd);">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Counter Foil Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="update_counterfoil">
                <input type="hidden" name="id" id="edit_counterfoil_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Counter Foil / Book Category <span class="text-danger">*</span></label>
                        <select name="counterfoil_type" id="edit_counterfoil_type" class="form-select fw-bold" required>
                            <option value="General Receipt Book">General Receipt Book</option>
                            <option value="Credit Sale Book">Credit Sale Book</option>
                            <option value="Voucher Book">Voucher Book</option>
                            <option value="Store Issue Note">Store Issue Note</option>
                            <option value="Gate Pass Book">Gate Pass Book</option>
                            <option value="Permit Book">Permit Book</option>
                            <option value="Other Books">Other Books</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Quantity (Books) <span class="text-danger">*</span></label>
                            <input type="number" name="available_quantity" id="edit_counterfoil_qty" class="form-control fw-bold" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date Received / Issued</label>
                            <input type="date" name="purchase_date" id="edit_counterfoil_date" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status / Condition <span class="text-danger">*</span></label>
                        <select name="current_condition" id="edit_counterfoil_condition" class="form-select fw-bold" required>
                            <option value="Active / In Use">Active / In Use</option>
                            <option value="Completed / Archived">Completed / Archived</option>
                            <option value="Damaged / Cancelled">Damaged / Cancelled</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks / Serial Number Range</label>
                        <textarea name="remarks" id="edit_counterfoil_remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--color-c10, #185dbd);">
                        <i class="bi bi-check-circle-fill me-1"></i>Update Counter Foil
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $(document).on('click', '.btn-edit-counterfoil', function() {
        const btn = $(this);
        $('#edit_counterfoil_id').val(btn.data('id'));
        $('#edit_counterfoil_type').val(btn.data('counterfoil_type'));
        $('#edit_counterfoil_qty').val(btn.data('available_quantity'));
        $('#edit_counterfoil_date').val(btn.data('purchase_date'));
        $('#edit_counterfoil_condition').val(btn.data('current_condition'));
        $('#edit_counterfoil_remarks').val(btn.data('remarks'));
    });
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
