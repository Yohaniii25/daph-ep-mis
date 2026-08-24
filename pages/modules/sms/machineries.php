<?php
// pages/modules/sms/machineries.php -> Cold Chain & Technical Machinery Registry (SMS Directorate)
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

$allowed_roles = ['sms', 'administrator', 'provincial_director', 'district_dd'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 12;

// Fetch Machinery Assets for current Subject Matter Specialist
$stmt = $mysqli->prepare("SELECT * FROM machinery_assets WHERE (user_category = 'subject_matter_specialist' OR user_id = ?) ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$machinery_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="bi bi-gear-fill me-2" style="color: #1e3c72;"></i>Machineries &amp; Cold Chain Equipment
        </h3>
        <p class="text-muted small mb-0">Vaccine cold storage freezers, solar ice-lined refrigerators, field generators &amp; laboratory autoclaves</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn text-light shadow-sm fw-bold" style="background-color: #1e3c72;" data-bs-toggle="modal" data-bs-target="#addMachineryModal">
            <i class="bi bi-plus-circle-fill me-2"></i>Register Machinery Asset
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
                    confirmButtonColor: '#1e3c72',
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
                        <th>Machinery / Equipment Specification</th>
                        <th>Available Qty</th>
                        <th>Commission Date</th>
                        <th>Current Condition</th>
                        <th>Operational Notes / Temperature Scope</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($machinery_list as $mac): ?>
                        <tr>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($mac['machinery_type']) ?></td>
                            <td class="fw-bold fs-6"><span class="badge bg-light text-dark border px-3 py-2 fs-6"><?= intval($mac['available_quantity']) ?></span></td>
                            <td><?= !empty($mac['purchase_date']) ? date('Y-m-d', strtotime($mac['purchase_date'])) : '-' ?></td>
                            <td>
                                <?php
                                    $cond = $mac['current_condition'];
                                    $badge_class = (strpos($cond, 'Operational') !== false || strpos($cond, 'Good') !== false) ? 'bg-success' : ((strpos($cond, 'Maintenance') !== false || strpos($cond, 'Service') !== false) ? 'bg-warning text-dark' : 'bg-danger');
                                ?>
                                <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($cond) ?></span>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars($mac['remarks'] ?: '-') ?></td>
                            <td class="text-center text-nowrap">
                                <button class="btn btn-sm btn-outline-primary me-1 btn-edit-machinery"
                                    data-id="<?= $mac['id'] ?>"
                                    data-machinery_type="<?= htmlspecialchars($mac['machinery_type']) ?>"
                                    data-available_quantity="<?= $mac['available_quantity'] ?>"
                                    data-purchase_date="<?= htmlspecialchars($mac['purchase_date'] ?? '') ?>"
                                    data-current_condition="<?= htmlspecialchars($mac['current_condition']) ?>"
                                    data-remarks="<?= htmlspecialchars($mac['remarks'] ?? '') ?>"
                                    data-bs-toggle="modal" data-bs-target="#editMachineryModal"
                                    title="Edit Machinery">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>
                                <a href="processors/office_assets_crud.php?action=delete_machinery&id=<?= $mac['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete Record">
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
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #1e3c72;">
                <h5 class="modal-title fw-bold"><i class="bi bi-gear-fill me-2"></i>Register Specialist Cold Chain &amp; Machinery</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="save_machinery">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Machinery Type / Name <span class="text-danger">*</span></label>
                            <input type="text" name="machinery_type" class="form-control" placeholder="e.g. Solar Ice-Lined Vaccine Refrigerator (ILR)" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Quantity Available <span class="text-danger">*</span></label>
                            <input type="number" name="available_quantity" class="form-control" value="1" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Commission / Purchase Date</label>
                            <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Operational Condition</label>
                            <select name="current_condition" class="form-select">
                                <option value="Operational (Optimal)" selected>Operational (Optimal)</option>
                                <option value="Operational (Needs Calibration)">Operational (Needs Calibration)</option>
                                <option value="Requires Service / Repair">Requires Service / Repair</option>
                                <option value="Decommissioned / Non-Functional">Decommissioned / Non-Functional</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Technical Specifications &amp; Temperature Profile</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. 2°C - 8°C continuous logging, backup battery system, located in cold store depot"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: #1e3c72;">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Machinery
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Edit Machinery Asset -->
<div class="modal fade" id="editMachineryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #1e3c72;">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Machinery Asset</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="update_machinery">
                <input type="hidden" name="id" id="edit_machinery_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Machinery Type / Name <span class="text-danger">*</span></label>
                            <input type="text" name="machinery_type" id="edit_machinery_type" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Quantity Available <span class="text-danger">*</span></label>
                            <input type="number" name="available_quantity" id="edit_machinery_qty" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Commission / Purchase Date</label>
                            <input type="date" name="purchase_date" id="edit_machinery_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Operational Condition</label>
                            <select name="current_condition" id="edit_machinery_cond" class="form-select">
                                <option value="Operational (Optimal)">Operational (Optimal)</option>
                                <option value="Operational (Needs Calibration)">Operational (Needs Calibration)</option>
                                <option value="Requires Service / Repair">Requires Service / Repair</option>
                                <option value="Decommissioned / Non-Functional">Decommissioned / Non-Functional</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Technical Specifications &amp; Notes</label>
                            <textarea name="remarks" id="edit_machinery_rem" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: #1e3c72;">
                        <i class="bi bi-check-circle-fill me-1"></i>Update Machinery
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
        $('#edit_machinery_cond').val(btn.data('current_condition'));
        $('#edit_machinery_rem').val(btn.data('remarks'));
    });

    if ($.fn.DataTable) {
        $('#machineryTable').DataTable({ responsive: true, pageLength: 10 });
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
