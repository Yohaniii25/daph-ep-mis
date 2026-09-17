<?php
// pages/modules/sms/counter_foilage.php -> Official Counter Foil & Certificate Books Registry (SMS Directorate)
require_once '../../../includes/header.php';
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

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
                        <th>Book / Serial Range</th>
                        <th>Issue Order No.</th>
                        <th>Received From</th>
                        <th>Receipt No.</th>
                        <th>Quantity</th>
                        <th>To Whom Issued</th>
                        <th>Issue / Return Date</th>
                        <th>Issue / Receipt Date</th>
                        <th>Current Status</th>
                        <th>Specification / Remarks</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($counterfoil_list as $cf): ?>
                        <tr>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($cf['counterfoil_type']) ?></td>
                            <td>
                                <span class="font-monospace fw-bold text-dark"><?= !empty($cf['book_serial_no']) ? htmlspecialchars($cf['book_serial_no']) : '-' ?></span>
                                <?php if(!empty($cf['page_count'])): ?>
                                    <br><small class="text-muted"><i class="bi bi-file-earmark-break me-1"></i><?= htmlspecialchars($cf['page_count']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($cf['issue_order_no'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($cf['received_from'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($cf['receipt_no'] ?: '-') ?></td>
                            <td class="text-center">
                                <span class="badge bg-primary fs-6 px-2 py-1"><?= sprintf("%02d", $cf['available_quantity'] ?? 1) ?></span>
                                <br>
                                <small class="text-muted" style="font-size:10px;" title="Baseline + Received">Base: <?= intval($cf['initial_count'] ?? 1) ?> | Recv: <?= intval($cf['received_quantity'] ?? 0) ?></small>
                            </td>
                            <td><small class="fw-semibold text-dark"><?= !empty($cf['issued_to']) ? htmlspecialchars($cf['issued_to']) : '-' ?></small></td>
                            <td>
                                <small class="text-muted">
                                    Issued: <span class="text-dark fw-medium"><?= !empty($cf['date_of_issue']) ? htmlspecialchars($cf['date_of_issue']) : '-' ?></span><br>
                                    Return: <span class="text-dark fw-medium"><?= !empty($cf['date_of_return']) ? htmlspecialchars($cf['date_of_return']) : '-' ?></span>
                                </small>
                            </td>
                            <td><?= !empty($cf['purchase_date']) ? date('Y-m-d', strtotime($cf['purchase_date'])) : '-' ?></td>
                            <td>
                                <?php
                                    $cond = $cf['current_condition'];
                                    $badge_class = (strpos($cond, 'Good') !== false || strpos($cond, 'Active') !== false || strpos($cond, 'Issued') !== false) ? 'bg-success' : ((strpos($cond, 'Archived') !== false || strpos($cond, 'Full') !== false) ? 'bg-secondary' : 'bg-warning text-dark');
                                ?>
                                <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($cond) ?></span>
                            </td>
                            <td>
                                <?php if (!empty($cf['specification'])): ?>
                                    <div class="fw-semibold text-dark small mb-1"><?= htmlspecialchars($cf['specification']) ?></div>
                                <?php endif; ?>
                                <div class="small text-muted font-monospace"><?= htmlspecialchars($cf['remarks'] ?: '-') ?></div>
                            </td>
                            <td class="text-center text-nowrap">
                                <button class="btn btn-sm btn-outline-primary me-1 btn-edit-counterfoil"
                                    data-id="<?= $cf['id'] ?>"
                                    data-counterfoil_type="<?= htmlspecialchars($cf['counterfoil_type']) ?>"
                                    data-book_serial_no="<?= htmlspecialchars($cf['book_serial_no'] ?? '') ?>"
                                    data-page_count="<?= htmlspecialchars($cf['page_count'] ?? '') ?>"
                                    data-issued_to="<?= htmlspecialchars($cf['issued_to'] ?? '') ?>"
                                    data-date_of_issue="<?= htmlspecialchars($cf['date_of_issue'] ?? '') ?>"
                                    data-date_of_return="<?= htmlspecialchars($cf['date_of_return'] ?? '') ?>"
                                    data-issue_order_no="<?= htmlspecialchars($cf['issue_order_no'] ?? '') ?>"
                                    data-received_from="<?= htmlspecialchars($cf['received_from'] ?? '') ?>"
                                    data-receipt_no="<?= htmlspecialchars($cf['receipt_no'] ?? '') ?>"
                                    data-initial_count="<?= intval($cf['initial_count'] ?? 1) ?>"
                                    data-received_quantity="<?= intval($cf['received_quantity'] ?? 0) ?>"
                                    data-available_quantity="<?= $cf['available_quantity'] ?>"
                                    data-purchase_date="<?= htmlspecialchars($cf['purchase_date'] ?? '') ?>"
                                    data-current_condition="<?= htmlspecialchars($cf['current_condition']) ?>"
                                    data-specification="<?= htmlspecialchars($cf['specification'] ?? '') ?>"
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
                        <div class="col-md-12 position-relative">
                            <label class="form-label small fw-bold">Book / Register Category <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-journal-bookmark text-muted"></i></span>
                                <input type="text" 
                                       name="counterfoil_type" 
                                       id="add_sms_counterfoil_type" 
                                       class="form-control" 
                                       placeholder="Type or select type (e.g. AI Register, Cash Receipt Book)..." 
                                       autocomplete="off" 
                                       required>
                            </div>
                            <div id="add_sms_counterfoil_type_suggestions" class="dropdown-menu w-100 shadow border-0 mt-1 py-1" style="display: none; position: absolute; z-index: 1060; max-height: 220px; overflow-y: auto;"></div>
                            <small class="text-muted" style="font-size: 11px;">
                                <i class="bi bi-magic me-1 text-primary"></i>Auto-suggests from saved &amp; baseline book categories. Custom types allowed.
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Number of Book - Serial Numbers <small class="text-muted fw-normal">(e.g. 1-5)</small></label>
                            <input type="text" name="book_serial_no" id="add_sms_book_serial_no" class="form-control font-monospace" placeholder="e.g. 1-5">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Page Counts <small class="text-muted fw-normal">(per book)</small></label>
                            <input type="text" name="page_count" id="add_sms_page_count" class="form-control" placeholder="e.g. 50 Pages or 100 Folios">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Issue Order No.</label>
                            <input type="text" name="issue_order_no" class="form-control" placeholder="e.g. IO-2024-001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Received From</label>
                            <input type="text" name="received_from" class="form-control" placeholder="e.g. Kachcheri / Head Office">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Receipt No.</label>
                            <input type="text" name="receipt_no" class="form-control" placeholder="e.g. REC-1234">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Issue / Receipt Date</label>
                            <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Initial Baseline Stock</label>
                            <input type="number" name="initial_count" id="add_cf_initial_count" class="form-control fw-bold" value="1" min="0" required oninput="calcAddCounterfoil()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Received Quantity</label>
                            <input type="number" name="received_quantity" id="add_cf_received_quantity" class="form-control fw-bold" value="0" min="0" required oninput="calcAddCounterfoil()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Current Availability</label>
                            <input type="number" name="available_quantity" id="add_cf_available_quantity" class="form-control fw-bold bg-light" value="1" readonly>
                        </div>
                        <div class="col-md-12">
                            <div class="p-3 rounded border bg-light">
                                <div class="fw-bold text-dark mb-2 small text-uppercase"><i class="bi bi-person-badge me-1 text-primary"></i>Issuance &amp; Custody Details</div>
                                <div class="mb-2">
                                    <label class="form-label fw-semibold small">To Whom Issued</label>
                                    <input type="text" name="issued_to" id="add_sms_issued_to" class="form-control form-control-sm" placeholder="e.g. Name / Designation of Officer">
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Date of Issue</label>
                                        <input type="date" name="date_of_issue" id="add_sms_date_of_issue" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Date of Return</label>
                                        <input type="date" name="date_of_return" id="add_sms_date_of_return" class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Book Status / Condition</label>
                            <select name="current_condition" class="form-select">
                                <option value="In Active Use (Good Condition)" selected>In Active Use (Good Condition)</option>
                                <option value="Partially Issued">Partially Issued</option>
                                <option value="Fully Completed &amp; Archived">Fully Completed &amp; Archived</option>
                                <option value="Stock / Unopened">Stock / Unopened</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Specification (Brand / Model / Specs)</label>
                            <textarea name="specification" class="form-control" rows="2" placeholder="e.g. Official Gov Print, 100 Duplicate Leaves..."></textarea>
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
                        <div class="col-md-12 position-relative">
                            <label class="form-label small fw-bold">Book / Register Category <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-journal-bookmark text-muted"></i></span>
                                <input type="text" 
                                       name="counterfoil_type" 
                                       id="edit_counterfoil_type" 
                                       class="form-control" 
                                       placeholder="Type or select type (e.g. AI Register, Cash Receipt Book)..." 
                                       autocomplete="off" 
                                       required>
                            </div>
                            <div id="edit_counterfoil_type_suggestions" class="dropdown-menu w-100 shadow border-0 mt-1 py-1" style="display: none; position: absolute; z-index: 1060; max-height: 220px; overflow-y: auto;"></div>
                            <small class="text-muted" style="font-size: 11px;">
                                <i class="bi bi-magic me-1 text-primary"></i>Auto-suggests from saved &amp; baseline book categories. Custom types allowed.
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Number of Book - Serial Numbers <small class="text-muted fw-normal">(e.g. 1-5)</small></label>
                            <input type="text" name="book_serial_no" id="edit_counterfoil_book_serial_no" class="form-control font-monospace" placeholder="e.g. 1-5">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Page Counts <small class="text-muted fw-normal">(per book)</small></label>
                            <input type="text" name="page_count" id="edit_counterfoil_page_count" class="form-control" placeholder="e.g. 50 Pages or 100 Folios">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Issue Order No.</label>
                            <input type="text" name="issue_order_no" id="edit_counterfoil_issue_order_no" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Received From</label>
                            <input type="text" name="received_from" id="edit_counterfoil_received_from" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Receipt No.</label>
                            <input type="text" name="receipt_no" id="edit_counterfoil_receipt_no" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Issue / Receipt Date</label>
                            <input type="date" name="purchase_date" id="edit_counterfoil_date" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Initial Baseline Stock</label>
                            <input type="number" name="initial_count" id="edit_counterfoil_initial_count" class="form-control fw-bold" min="0" required oninput="calcEditCounterfoil()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Received Quantity</label>
                            <input type="number" name="received_quantity" id="edit_counterfoil_received_quantity" class="form-control fw-bold" min="0" required oninput="calcEditCounterfoil()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Current Availability</label>
                            <input type="number" name="available_quantity" id="edit_counterfoil_qty" class="form-control fw-bold bg-light" readonly>
                        </div>
                        <div class="col-md-12">
                            <div class="p-3 rounded border bg-light">
                                <div class="fw-bold text-dark mb-2 small text-uppercase"><i class="bi bi-person-badge me-1 text-primary"></i>Issuance &amp; Custody Details</div>
                                <div class="mb-2">
                                    <label class="form-label fw-semibold small">To Whom Issued</label>
                                    <input type="text" name="issued_to" id="edit_counterfoil_issued_to" class="form-control form-control-sm" placeholder="e.g. Name / Designation of Officer">
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Date of Issue</label>
                                        <input type="date" name="date_of_issue" id="edit_counterfoil_date_of_issue" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Date of Return</label>
                                        <input type="date" name="date_of_return" id="edit_counterfoil_date_of_return" class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Book Status / Condition</label>
                            <select name="current_condition" id="edit_counterfoil_cond" class="form-select">
                                <option value="In Active Use (Good Condition)">In Active Use (Good Condition)</option>
                                <option value="Partially Issued">Partially Issued</option>
                                <option value="Fully Completed &amp; Archived">Fully Completed &amp; Archived</option>
                                <option value="Stock / Unopened">Stock / Unopened</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Specification (Brand / Model / Specs)</label>
                            <textarea name="specification" id="edit_counterfoil_specification" class="form-control" rows="2"></textarea>
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
function calcAddCounterfoil() {
    const base = parseInt(document.getElementById('add_cf_initial_count').value) || 0;
    const recv = parseInt(document.getElementById('add_cf_received_quantity').value) || 0;
    document.getElementById('add_cf_available_quantity').value = Math.max(0, base + recv);
}
function calcEditCounterfoil() {
    const base = parseInt(document.getElementById('edit_counterfoil_initial_count').value) || 0;
    const recv = parseInt(document.getElementById('edit_counterfoil_received_quantity').value) || 0;
    document.getElementById('edit_counterfoil_qty').value = Math.max(0, base + recv);
}

document.addEventListener('DOMContentLoaded', function() {
    $(document).on('click', '.btn-edit-counterfoil', function() {
        const btn = $(this);
        $('#edit_counterfoil_id').val(btn.data('id'));
        $('#edit_counterfoil_type').val(btn.data('counterfoil_type'));
        $('#edit_counterfoil_book_serial_no').val(btn.data('book_serial_no') || '');
        $('#edit_counterfoil_page_count').val(btn.data('page_count') || '');
        $('#edit_counterfoil_issued_to').val(btn.data('issued_to') || '');
        $('#edit_counterfoil_date_of_issue').val(btn.data('date_of_issue') || '');
        $('#edit_counterfoil_date_of_return').val(btn.data('date_of_return') || '');
        $('#edit_counterfoil_issue_order_no').val(btn.data('issue_order_no'));
        $('#edit_counterfoil_received_from').val(btn.data('received_from'));
        $('#edit_counterfoil_receipt_no').val(btn.data('receipt_no'));
        $('#edit_counterfoil_initial_count').val(btn.data('initial_count') !== undefined ? btn.data('initial_count') : btn.data('available_quantity'));
        $('#edit_counterfoil_received_quantity').val(btn.data('received_quantity') !== undefined ? btn.data('received_quantity') : 0);
        $('#edit_counterfoil_date').val(btn.data('purchase_date'));
        $('#edit_counterfoil_cond').val(btn.data('current_condition'));
        $('#edit_counterfoil_specification').val(btn.data('specification'));
        $('#edit_counterfoil_rem').val(btn.data('remarks'));
        calcEditCounterfoil();
    });

    if ($.fn.DataTable) {
        $('#counterfoilTable').DataTable({ responsive: true, pageLength: 10 });
    }

    // Auto-suggest implementation for Counterfoil Book Type
    function setupCounterfoilTypeAutocomplete(inputSelector, dropdownSelector, apiUrl) {
        var timer = null;
        apiUrl = apiUrl || 'processors/get_counterfoil_types.php';

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
        function escapeRegex(str) {
            return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        $(inputSelector).on('input focus', function() {
            var term = $(this).val().trim();
            var $dropdown = $(dropdownSelector);

            clearTimeout(timer);
            timer = setTimeout(function() {
                $.ajax({
                    url: apiUrl,
                    type: 'GET',
                    data: { q: term },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success && res.suggestions && res.suggestions.length > 0) {
                            var html = '';
                            res.suggestions.forEach(function(item) {
                                var highlighted = escapeHtml(item);
                                if (term.length > 0) {
                                    var re = new RegExp('(' + escapeRegex(term) + ')', 'gi');
                                    highlighted = highlighted.replace(re, '<strong class="text-primary">$1</strong>');
                                }
                                html += '<a class="dropdown-item py-2 px-3 d-flex align-items-center suggestion-item" href="javascript:void(0)" data-value="' + escapeHtml(item) + '">' +
                                        '<i class="bi bi-journal-text me-2 text-muted" style="font-size: 13px;"></i>' +
                                        '<span>' + highlighted + '</span>' +
                                        '</a>';
                            });
                            $dropdown.html(html).show();
                        } else if (term.length > 0) {
                            $dropdown.html('<div class="dropdown-header text-muted py-2 px-3 small"><i class="bi bi-pencil me-1"></i>New book type: "' + escapeHtml(term) + '" (will be auto-saved)</div>').show();
                        } else {
                            $dropdown.hide();
                        }
                    }
                });
            }, 180);
        });

        $(dropdownSelector).on('click', '.suggestion-item', function(e) {
            e.preventDefault();
            var val = $(this).data('value');
            $(inputSelector).val(val);
            $(dropdownSelector).hide();
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest(inputSelector + ', ' + dropdownSelector).length) {
                $(dropdownSelector).hide();
            }
        });
    }

    setupCounterfoilTypeAutocomplete('#add_sms_counterfoil_type', '#add_sms_counterfoil_type_suggestions', 'processors/get_counterfoil_types.php');
    setupCounterfoilTypeAutocomplete('#edit_counterfoil_type', '#edit_counterfoil_type_suggestions', 'processors/get_counterfoil_types.php');
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
