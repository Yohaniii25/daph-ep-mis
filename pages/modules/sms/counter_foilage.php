<?php
// pages/modules/sms/counter_foilage.php -> Official Counter Foil & Certificate Books Registry (SMS Directorate)
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

$allowed_roles = ['sms', 'administrator', 'provincial_director', 'district_dd'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 12;

// Fetch Counter Foil Assets for current Subject Matter Specialist
$stmt = $mysqli->prepare("SELECT * FROM counterfoil_assets WHERE (user_category = 'subject_matter_specialist' OR user_id = ?) ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$counterfoil_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="bi bi-file-earmark-text-fill me-2" style="color: #e65100;"></i>Counter Foil &amp; Certificate Books Registry
        </h3>
        <p class="text-muted small mb-0">Official outbreak notifications, vaccination certificates, quarantine vouchers &amp; receipt books</p>
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
                        <th>Book / Certificate Register Type</th>
                        <th>Book Quantity</th>
                        <th>Issue / Receipt Date</th>
                        <th>Current Status</th>
                        <th>Serial Numbers / Series Range</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($counterfoil_list as $cf): ?>
                        <tr>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($cf['counterfoil_type']) ?></td>
                            <td class="fw-bold fs-6"><span class="badge bg-light text-dark border px-3 py-2 fs-6"><?= intval($cf['available_quantity']) ?> Books</span></td>
                            <td><?= !empty($cf['purchase_date']) ? date('Y-m-d', strtotime($cf['purchase_date'])) : '-' ?></td>
                            <td>
                                <?php
                                    $cond = $cf['current_condition'];
                                    $badge_class = (strpos($cond, 'Good') !== false || strpos($cond, 'Active') !== false || strpos($cond, 'Issued') !== false) ? 'bg-success' : ((strpos($cond, 'Archived') !== false || strpos($cond, 'Full') !== false) ? 'bg-secondary' : 'bg-warning text-dark');
                                ?>
                                <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($cond) ?></span>
                            </td>
                            <td class="small text-muted font-monospace"><?= htmlspecialchars($cf['remarks'] ?: '-') ?></td>
                            <td class="text-center text-nowrap">
                                <button class="btn btn-sm btn-outline-primary me-1 btn-edit-counterfoil"
                                    data-id="<?= $cf['id'] ?>"
                                    data-counterfoil_type="<?= htmlspecialchars($cf['counterfoil_type']) ?>"
                                    data-available_quantity="<?= $cf['available_quantity'] ?>"
                                    data-purchase_date="<?= htmlspecialchars($cf['purchase_date'] ?? '') ?>"
                                    data-current_condition="<?= htmlspecialchars($cf['current_condition']) ?>"
                                    data-remarks="<?= htmlspecialchars($cf['remarks'] ?? '') ?>"
                                    data-bs-toggle="modal" data-bs-target="#editCounterfoilModal"
                                    title="Edit Book Record">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>
                                <a href="processors/office_assets_crud.php?action=delete_counterfoil&id=<?= $cf['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete Book">
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

<!-- Modal 1: Register Counter Foil Asset -->
<div class="modal fade" id="addCounterfoilModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #e65100;">
                <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-text-fill me-2"></i>Register Counter Foil / Certificate Register</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="save_counterfoil">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Book / Register Category <span class="text-danger">*</span></label>
                            <input type="text" name="counterfoil_type" class="form-control" placeholder="e.g. Official Outbreak Notification &amp; Investigation Logbook" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Quantity (Number of Books) <span class="text-danger">*</span></label>
                            <input type="number" name="available_quantity" class="form-control" value="1" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Issue / Receipt Date</label>
                            <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Book Status / Condition</label>
                            <select name="current_condition" class="form-select">
                                <option value="In Active Use (Good Condition)" selected>In Active Use (Good Condition)</option>
                                <option value="Partially Issued">Partially Issued</option>
                                <option value="Fully Completed &amp; Archived">Fully Completed &amp; Archived</option>
                                <option value="Stock / Unopened">Stock / Unopened</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Serial Number Series &amp; Leaf Range</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. Serial #SMS-OB-2026-001 to 2026-100 (100 Leaves per book)"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: #e65100;">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Book
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Edit Counter Foil Asset -->
<div class="modal fade" id="editCounterfoilModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #e65100;">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Counter Foil Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="update_counterfoil">
                <input type="hidden" name="id" id="edit_counterfoil_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Book / Register Category <span class="text-danger">*</span></label>
                            <input type="text" name="counterfoil_type" id="edit_counterfoil_type" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Quantity (Number of Books) <span class="text-danger">*</span></label>
                            <input type="number" name="available_quantity" id="edit_counterfoil_qty" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Issue / Receipt Date</label>
                            <input type="date" name="purchase_date" id="edit_counterfoil_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Book Status / Condition</label>
                            <select name="current_condition" id="edit_counterfoil_cond" class="form-select">
                                <option value="In Active Use (Good Condition)">In Active Use (Good Condition)</option>
                                <option value="Partially Issued">Partially Issued</option>
                                <option value="Fully Completed &amp; Archived">Fully Completed &amp; Archived</option>
                                <option value="Stock / Unopened">Stock / Unopened</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Serial Number Series &amp; Range</label>
                            <textarea name="remarks" id="edit_counterfoil_rem" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: #e65100;">
                        <i class="bi bi-check-circle-fill me-1"></i>Update Book
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
        $('#edit_counterfoil_cond').val(btn.data('current_condition'));
        $('#edit_counterfoil_rem').val(btn.data('remarks'));
    });

    if ($.fn.DataTable) {
        $('#counterfoilTable').DataTable({ responsive: true, pageLength: 10 });
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
