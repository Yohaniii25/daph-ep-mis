<?php
// pages/modules/sms/instruments.php -> Technical Diagnostic & Field Instruments Registry (SMS Directorate)
require_once '../../../includes/header.php';
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$allowed_roles = ['sms', 'administrator', 'provincial_director', 'district_dd'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 12;

// Fetch Instrument Assets for current Subject Matter Specialist
$stmt = $mysqli->prepare("SELECT * FROM instrument_assets WHERE (user_category = 'subject_matter_specialist' OR user_id = ?) ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$instrument_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="bi bi-tools me-2" style="color: #2e7d32;"></i>Diagnostic &amp; Surveillance Instruments
        </h3>
        <p class="text-muted small mb-0">Epidemiological sampling kits, dart guns, microchip scanners, automatic vaccinators &amp; cold boxes</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn text-light shadow-sm fw-bold" style="background-color: #2e7d32;" data-bs-toggle="modal" data-bs-target="#addInstrumentModal">
            <i class="bi bi-plus-circle-fill me-2"></i>Register Instrument
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
                        <th>Instrument Type / Tool Kit</th>
                        <th>Issue Order No.</th>
                        <th>Received From</th>
                        <th>Receipt No.</th>
                        <th>Quantity</th>
                        <th>Acquisition Date</th>
                        <th>Current Condition</th>
                        <th>Specification / Remarks</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($instrument_list as $inst): ?>
                        <tr>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($inst['instrument_type']) ?></td>
                            <td><?= htmlspecialchars($inst['issue_order_no'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($inst['received_from'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($inst['receipt_no'] ?: '-') ?></td>
                            <td class="text-center">
                                <span class="badge bg-primary fs-6 px-2 py-1"><?= sprintf("%02d", $inst['available_quantity'] ?? 1) ?></span>
                                <br>
                                <small class="text-muted" style="font-size:10px;" title="Baseline + Received">Base: <?= intval($inst['initial_count'] ?? 1) ?> | Recv: <?= intval($inst['received_quantity'] ?? 0) ?></small>
                            </td>
                            <td><?= !empty($inst['purchase_date']) ? date('Y-m-d', strtotime($inst['purchase_date'])) : '-' ?></td>
                            <td>
                                <?php
                                    $cond = $inst['current_condition'];
                                    $badge_class = (strpos($cond, 'Good') !== false || strpos($cond, 'Operational') !== false || strpos($cond, 'Sterilized') !== false) ? 'bg-success' : ((strpos($cond, 'Calibration') !== false || strpos($cond, 'Maintenance') !== false) ? 'bg-warning text-dark' : 'bg-danger');
                                ?>
                                <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($cond) ?></span>
                            </td>
                            <td>
                                <?php if (!empty($inst['specification'])): ?>
                                    <div class="fw-semibold text-dark small mb-1"><?= htmlspecialchars($inst['specification']) ?></div>
                                <?php endif; ?>
                                <div class="small text-muted"><?= htmlspecialchars($inst['remarks'] ?: '-') ?></div>
                            </td>
                            <td class="text-center text-nowrap">
                                <button class="btn btn-sm btn-outline-primary me-1 btn-edit-instrument"
                                    data-id="<?= $inst['id'] ?>"
                                    data-instrument_type="<?= htmlspecialchars($inst['instrument_type']) ?>"
                                    data-issue_order_no="<?= htmlspecialchars($inst['issue_order_no'] ?? '') ?>"
                                    data-received_from="<?= htmlspecialchars($inst['received_from'] ?? '') ?>"
                                    data-receipt_no="<?= htmlspecialchars($inst['receipt_no'] ?? '') ?>"
                                    data-initial_count="<?= intval($inst['initial_count'] ?? 1) ?>"
                                    data-received_quantity="<?= intval($inst['received_quantity'] ?? 0) ?>"
                                    data-available_quantity="<?= $inst['available_quantity'] ?>"
                                    data-purchase_date="<?= htmlspecialchars($inst['purchase_date'] ?? '') ?>"
                                    data-current_condition="<?= htmlspecialchars($inst['current_condition']) ?>"
                                    data-specification="<?= htmlspecialchars($inst['specification'] ?? '') ?>"
                                    data-remarks="<?= htmlspecialchars($inst['remarks'] ?? '') ?>"
                                    data-bs-toggle="modal" data-bs-target="#editInstrumentModal"
                                    title="Edit Instrument">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>
                                <a href="processors/office_assets_crud.php?action=delete_instrument&id=<?= $inst['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete Record">
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
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #2e7d32;">
                <h5 class="modal-title fw-bold"><i class="bi bi-tools me-2"></i>Register Field &amp; Diagnostic Instrument</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="save_instrument">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Instrument Name / Kit Category <span class="text-danger">*</span></label>
                            <input type="text" name="instrument_type" class="form-control" placeholder="e.g. Field Disease Post-Mortem Diagnostic Kit" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Issue Order No.</label>
                            <input type="text" name="issue_order_no" class="form-control" placeholder="e.g. IO-2024-001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Received From</label>
                            <input type="text" name="received_from" class="form-control" placeholder="e.g. Central Store / Supplier">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Receipt No.</label>
                            <input type="text" name="receipt_no" class="form-control" placeholder="e.g. REC-1234">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Acquisition / Calibration Date</label>
                            <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Initial Baseline Stock</label>
                            <input type="number" name="initial_count" id="add_ins_initial_count" class="form-control fw-bold" value="1" min="0" required oninput="calcAddInstrument()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Received Quantity</label>
                            <input type="number" name="received_quantity" id="add_ins_received_quantity" class="form-control fw-bold" value="0" min="0" required oninput="calcAddInstrument()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Current Availability</label>
                            <input type="number" name="available_quantity" id="add_ins_available_quantity" class="form-control fw-bold bg-light" value="1" readonly>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Operational Condition</label>
                            <select name="current_condition" id="add_ins_condition" class="form-select" onchange="calcAddInstrument()">
                                <option value="Operational (Sterilized / Calibrated)" selected>Operational (Sterilized / Calibrated)</option>
                                <option value="Good Condition">Good Condition</option>
                                <option value="Requires Recalibration">Requires Recalibration</option>
                                <option value="Damaged / Needs Replacement">Damaged / Needs Replacement</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Specification (Brand / Model / Specs)</label>
                            <textarea name="specification" class="form-control" rows="2" placeholder="e.g. Surgical grade stainless steel, CE certified..."></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Technical Specifications &amp; Allocation</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. Assigned to Mobile Clinic Unit #1, Includes stainless dissection tools & transport vials"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: #2e7d32;">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Instrument
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Edit Instrument Asset -->
<div class="modal fade" id="editInstrumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #2e7d32;">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Instrument Asset</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="update_instrument">
                <input type="hidden" name="id" id="edit_instrument_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Instrument Name / Kit Category <span class="text-danger">*</span></label>
                            <input type="text" name="instrument_type" id="edit_instrument_type" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Issue Order No.</label>
                            <input type="text" name="issue_order_no" id="edit_instrument_issue_order_no" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Received From</label>
                            <input type="text" name="received_from" id="edit_instrument_received_from" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Receipt No.</label>
                            <input type="text" name="receipt_no" id="edit_instrument_receipt_no" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Acquisition / Calibration Date</label>
                            <input type="date" name="purchase_date" id="edit_instrument_date" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Initial Baseline Stock</label>
                            <input type="number" name="initial_count" id="edit_ins_initial_count" class="form-control fw-bold" min="0" required oninput="calcEditInstrument()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Received Quantity</label>
                            <input type="number" name="received_quantity" id="edit_ins_received_quantity" class="form-control fw-bold" min="0" required oninput="calcEditInstrument()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Current Availability</label>
                            <input type="number" name="available_quantity" id="edit_instrument_qty" class="form-control fw-bold bg-light" readonly>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Operational Condition</label>
                            <select name="current_condition" id="edit_instrument_cond" class="form-select" onchange="calcEditInstrument()">
                                <option value="Operational (Sterilized / Calibrated)">Operational (Sterilized / Calibrated)</option>
                                <option value="Good Condition">Good Condition</option>
                                <option value="Requires Recalibration">Requires Recalibration</option>
                                <option value="Damaged / Needs Replacement">Damaged / Needs Replacement</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Specification (Brand / Model / Specs)</label>
                            <textarea name="specification" id="edit_instrument_specification" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Technical Specifications &amp; Notes</label>
                            <textarea name="remarks" id="edit_instrument_rem" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: #2e7d32;">
                        <i class="bi bi-check-circle-fill me-1"></i>Update Instrument
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function calcAddInstrument() {
    const base = parseInt(document.getElementById('add_ins_initial_count').value) || 0;
    const recv = parseInt(document.getElementById('add_ins_received_quantity').value) || 0;
    const cond = document.getElementById('add_ins_condition').value;
    let qty = base + recv;
    if (cond.includes('Damaged') || cond.includes('Condemned') || cond.includes('Needs Replacement') || cond.includes('Requires Recalibration')) {
        qty = Math.max(0, qty - 1);
    }
    document.getElementById('add_ins_available_quantity').value = Math.max(0, qty);
}
function calcEditInstrument() {
    const base = parseInt(document.getElementById('edit_ins_initial_count').value) || 0;
    const recv = parseInt(document.getElementById('edit_ins_received_quantity').value) || 0;
    const cond = document.getElementById('edit_instrument_cond').value;
    let qty = base + recv;
    if (cond.includes('Damaged') || cond.includes('Condemned') || cond.includes('Needs Replacement') || cond.includes('Requires Recalibration')) {
        qty = Math.max(0, qty - 1);
    }
    document.getElementById('edit_instrument_qty').value = Math.max(0, qty);
}

document.addEventListener('DOMContentLoaded', function() {
    $(document).on('click', '.btn-edit-instrument', function() {
        const btn = $(this);
        $('#edit_instrument_id').val(btn.data('id'));
        $('#edit_instrument_type').val(btn.data('instrument_type'));
        $('#edit_instrument_issue_order_no').val(btn.data('issue_order_no'));
        $('#edit_instrument_received_from').val(btn.data('received_from'));
        $('#edit_instrument_receipt_no').val(btn.data('receipt_no'));
        $('#edit_ins_initial_count').val(btn.data('initial_count') !== undefined ? btn.data('initial_count') : btn.data('available_quantity'));
        $('#edit_ins_received_quantity').val(btn.data('received_quantity') !== undefined ? btn.data('received_quantity') : 0);
        $('#edit_instrument_date').val(btn.data('purchase_date'));
        $('#edit_instrument_cond').val(btn.data('current_condition'));
        $('#edit_instrument_specification').val(btn.data('specification'));
        $('#edit_instrument_rem').val(btn.data('remarks'));
        calcEditInstrument();
    });

    if ($.fn.DataTable) {
        $('#instrumentTable').DataTable({ responsive: true, pageLength: 10 });
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
