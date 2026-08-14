<?php
// pages/modules/training/instruments.php -> Scientific & Training Instruments Registry (Training Centre)
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

// Fetch Instrument Assets for current Training Centre
$stmt = $mysqli->prepare("SELECT * FROM instrument_assets WHERE (training_center_id = ? OR (user_id = ? AND user_category = 'training_centers')) ORDER BY id DESC");
$stmt->bind_param("ii", $current_center_id, $user_id);
$stmt->execute();
$instruments_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="bi bi-tools me-2" style="color: #2e7d32;"></i>Instruments &amp; Training Tools Registry
        </h3>
        <p class="text-muted small mb-0">Training demonstration kits, veterinary clinical tools and laboratory instruments</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn text-light shadow-sm fw-bold" style="background-color: #2e7d32;" data-bs-toggle="modal" data-bs-target="#addInstrumentModal">
            <i class="bi bi-plus-circle-fill me-2"></i>Register New Instrument
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
                    confirmButtonColor: '#2e7d32',
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
            <table id="instrumentTable" class="table table-hover align-middle w-100">
                <thead class="table-dark" style="background-color: #370709;">
                    <tr>
                        <th>Instrument / Tool Type</th>
                        <th>Available Quantity</th>
                        <th>Purchase / Issue Date</th>
                        <th>Current Condition</th>
                        <th>Remarks / Specs</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($instruments_list as $inst): ?>
                        <tr>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($inst['instrument_type']) ?></td>
                            <td class="fw-bold fs-6"><span class="badge bg-light text-dark border px-3 py-2 fs-6"><?= intval($inst['available_quantity']) ?></span></td>
                            <td><?= !empty($inst['purchase_date']) ? date('Y-m-d', strtotime($inst['purchase_date'])) : '-' ?></td>
                            <td>
                                <?php
                                    $cond = $inst['current_condition'];
                                    $badge_class = (strpos($cond, 'Good') !== false || strpos($cond, 'Excellent') !== false) ? 'bg-success' : ((strpos($cond, 'Requires') !== false || strpos($cond, 'Needs') !== false) ? 'bg-warning text-dark' : 'bg-danger');
                                ?>
                                <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($cond) ?></span>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars($inst['remarks'] ?: '-') ?></td>
                            <td class="text-center text-nowrap">
                                <button class="btn btn-sm btn-outline-primary me-1 btn-edit-inst"
                                    data-id="<?= $inst['id'] ?>"
                                    data-instrument_type="<?= htmlspecialchars($inst['instrument_type']) ?>"
                                    data-available_quantity="<?= $inst['available_quantity'] ?>"
                                    data-purchase_date="<?= htmlspecialchars($inst['purchase_date'] ?? '') ?>"
                                    data-current_condition="<?= htmlspecialchars($inst['current_condition']) ?>"
                                    data-remarks="<?= htmlspecialchars($inst['remarks'] ?? '') ?>"
                                    data-bs-toggle="modal" data-bs-target="#editInstrumentModal"
                                    title="Edit Instrument">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>
                                <a href="processors/office_assets_crud.php?action=delete_instrument&id=<?= $inst['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete Instrument">
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

<!-- Modal 1: Register Instrument Asset -->
<div class="modal fade" id="addInstrumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #2e7d32;">
                <h5 class="modal-title fw-bold"><i class="bi bi-tools me-2"></i>Register New Instrument</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="save_instrument">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Instrument / Equipment Type <span class="text-danger">*</span></label>
                        <select name="instrument_type" class="form-select fw-bold" required>
                            <option value="AI Training Gun &amp; Sheaths Kit">AI Training Gun &amp; Sheaths Kit</option>
                            <option value="Liquid Nitrogen Cryo Container (LN2)">Liquid Nitrogen Cryo Container (LN2)</option>
                            <option value="Microscope (Laboratory / Demo)">Microscope (Laboratory / Demo)</option>
                            <option value="Autoclave / Sterilizer Unit">Autoclave / Sterilizer Unit</option>
                            <option value="Clinical Surgical Demonstration Kit">Clinical Surgical Demonstration Kit</option>
                            <option value="Digital Electronic Weighing Scale">Digital Electronic Weighing Scale</option>
                            <option value="Refractometer / Lactometer Kit">Refractometer / Lactometer Kit</option>
                            <option value="Post-Mortem Demonstration Set">Post-Mortem Demonstration Set</option>
                            <option value="Vaccination Injector Guns">Vaccination Injector Guns</option>
                            <option value="Other Training Tools">Other Training Tools</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="available_quantity" class="form-control fw-bold" value="1" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Purchase / Issue Date</label>
                            <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Current Condition <span class="text-danger">*</span></label>
                        <select name="current_condition" class="form-select fw-bold" required>
                            <option value="Excellent / Operational">Excellent / Operational</option>
                            <option value="Good Condition">Good Condition</option>
                            <option value="Requires Calibration / Repair">Requires Calibration / Repair</option>
                            <option value="Damaged / Unusable">Damaged / Unusable</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks / Specifications</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Brand, serial number, storage location..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: #2e7d32;">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Instrument Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Edit Instrument Asset -->
<div class="modal fade" id="editInstrumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c10, #185dbd);">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Instrument Asset</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="update_instrument">
                <input type="hidden" name="id" id="edit_inst_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Instrument / Equipment Type <span class="text-danger">*</span></label>
                        <select name="instrument_type" id="edit_inst_type" class="form-select fw-bold" required>
                            <option value="AI Training Gun &amp; Sheaths Kit">AI Training Gun &amp; Sheaths Kit</option>
                            <option value="Liquid Nitrogen Cryo Container (LN2)">Liquid Nitrogen Cryo Container (LN2)</option>
                            <option value="Microscope (Laboratory / Demo)">Microscope (Laboratory / Demo)</option>
                            <option value="Autoclave / Sterilizer Unit">Autoclave / Sterilizer Unit</option>
                            <option value="Clinical Surgical Demonstration Kit">Clinical Surgical Demonstration Kit</option>
                            <option value="Digital Electronic Weighing Scale">Digital Electronic Weighing Scale</option>
                            <option value="Refractometer / Lactometer Kit">Refractometer / Lactometer Kit</option>
                            <option value="Post-Mortem Demonstration Set">Post-Mortem Demonstration Set</option>
                            <option value="Vaccination Injector Guns">Vaccination Injector Guns</option>
                            <option value="Other Training Tools">Other Training Tools</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="available_quantity" id="edit_inst_qty" class="form-control fw-bold" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Purchase / Issue Date</label>
                            <input type="date" name="purchase_date" id="edit_inst_date" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Current Condition <span class="text-danger">*</span></label>
                        <select name="current_condition" id="edit_inst_condition" class="form-select fw-bold" required>
                            <option value="Excellent / Operational">Excellent / Operational</option>
                            <option value="Good Condition">Good Condition</option>
                            <option value="Requires Calibration / Repair">Requires Calibration / Repair</option>
                            <option value="Damaged / Unusable">Damaged / Unusable</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks / Specifications</label>
                        <textarea name="remarks" id="edit_inst_remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--color-c10, #185dbd);">
                        <i class="bi bi-check-circle-fill me-1"></i>Update Instrument
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $(document).on('click', '.btn-edit-inst', function() {
        const btn = $(this);
        $('#edit_inst_id').val(btn.data('id'));
        $('#edit_inst_type').val(btn.data('instrument_type'));
        $('#edit_inst_qty').val(btn.data('available_quantity'));
        $('#edit_inst_date').val(btn.data('purchase_date'));
        $('#edit_inst_condition').val(btn.data('current_condition'));
        $('#edit_inst_remarks').val(btn.data('remarks'));
    });

    if ($.fn.DataTable) {
        $('#instrumentTable').DataTable({ responsive: true, pageLength: 10 });
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
