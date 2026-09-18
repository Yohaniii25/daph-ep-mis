<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$allowed_roles = [
    'veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon',
    'district_dd', 'deputy_director_district', 'sms', 'provincial_director', 'administrator'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    header("Location: ../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? '';
$is_supervisory = in_array($user_role, ['district_dd', 'deputy_director_district', 'sms', 'provincial_director', 'administrator']);
$district_id = $_SESSION['district_id'] ?? null;

// Fetch list of accessible ranges for selection
$available_ranges = [];
if ($district_id && !in_array($user_role, ['provincial_director', 'administrator'])) {
    $r_stmt = $mysqli->prepare("SELECT id, name FROM veterinary_ranges WHERE district_id = ? ORDER BY name ASC");
    if ($r_stmt) {
        $r_stmt->bind_param("i", $district_id);
        $r_stmt->execute();
        $r_res = $r_stmt->get_result();
        while ($r_row = $r_res->fetch_assoc()) {
            $available_ranges[] = $r_row;
        }
        $r_stmt->close();
    }
} else {
    $r_res = $mysqli->query("SELECT id, name FROM veterinary_ranges ORDER BY name ASC");
    if ($r_res) {
        while ($r_row = $r_res->fetch_assoc()) {
            $available_ranges[] = $r_row;
        }
    }
}

// Resolve Range ID
$requested_range_id = isset($_GET['range_id']) && is_numeric($_GET['range_id']) ? intval($_GET['range_id']) : null;
$range_id = $requested_range_id ?: ($_SESSION['range_id'] ?? null);

if (empty($range_id) && !empty($available_ranges)) {
    $range_id = intval($available_ranges[0]['id']);
}

$range_name = 'Your Range';
$district_name = 'Your District';
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Fetch Range and District Info using a JOIN query
if (!empty($range_id)) {
    $details_sql = "
        SELECT 
            vr.name AS range_name,
            d.name AS district_name,
            vr.district_id
        FROM veterinary_ranges vr
        LEFT JOIN districts d ON vr.district_id = d.id
        WHERE vr.id = ?
    ";
    $details_query = $mysqli->prepare($details_sql);
    if ($details_query) {
        $details_query->bind_param("i", $range_id);
        $details_query->execute();
        $details_result = $details_query->get_result();
        if ($data = $details_result->fetch_assoc()) {
            $range_name = $data['range_name'] ?? 'Your Assigned Range';
            $district_name = $data['district_name'] ?? 'Your District';
            if (empty($district_id) && !empty($data['district_id'])) {
                $district_id = $data['district_id'];
            }
        }
        $details_query->close();
    }
}

// Fetch Vaccine Balance records matching year filter and range
$records = [];
if (!empty($range_id)) {
    $records_sql = "
        SELECT id, report_year, report_month, vaccine_name, opening_balance, 
               received_doses, used_doses, spoilt_damaged_doses, transferred_doses, 
               closing_balance, batch_no, expiry_date, remarks 
        FROM monthly_vaccine_balances 
        WHERE range_id = ? AND report_year = ? 
        ORDER BY report_month DESC, id DESC
    ";
    $stmt = $mysqli->prepare($records_sql);
    if ($stmt) {
        $stmt->bind_param("ii", $range_id, $selected_year);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $records[] = $row;
        }
        $stmt->close();
    }
}

// Calculate summary metrics
$tot_received = 0;
$tot_used = 0;
$tot_closing = 0;
foreach ($records as $r) {
    $tot_received += intval($r['received_doses']);
    $tot_used += intval($r['used_doses']);
    $tot_closing += intval($r['closing_balance']);
}

$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">

        <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">Vaccine Balance - Monthly Returns</h2>
                <p class="text-muted small mb-0">Manage monthly vaccine stock levels for <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District)</p>
            </div>
            
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <form method="GET" class="d-flex align-items-center gap-2">
                    <?php if (!empty($available_ranges) && ($is_supervisory || count($available_ranges) > 1)): ?>
                        <label class="small fw-bold text-muted mb-0">Range:</label>
                        <select name="range_id" class="form-select form-select-sm border-secondary" onchange="this.form.submit()" style="min-width: 170px;">
                            <?php foreach ($available_ranges as $ar): ?>
                                <option value="<?= $ar['id'] ?>" <?= ($ar['id'] == $range_id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ar['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="hidden" name="range_id" value="<?= htmlspecialchars($range_id) ?>">
                    <?php endif; ?>

                    <label class="small fw-bold text-muted mb-0 ms-2">Year:</label>
                    <select name="year" class="form-select form-select-sm border-secondary" onchange="this.form.submit()" style="width: 100px;">
                        <?php
                        $curr_year = intval(date('Y'));
                        for ($y = $curr_year - 5; $y <= $curr_year + 5; $y++) {
                            $sel = ($y === $selected_year) ? 'selected' : '';
                            echo "<option value=\"$y\" $sel>$y</option>";
                        }
                        ?>
                    </select>
                </form>
                <a href="monthly-annual-reports.php" class="btn btn-sm btn-secondary shadow-sm text-nowrap">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['msg'])): ?>
            <div class="alert alert-<?= htmlspecialchars($_SESSION['msg_type'] ?? 'info') ?> alert-dismissible fade show shadow-sm py-2 px-3 mb-4 small" role="alert">
                <?= $_SESSION['msg'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
        <?php endif; ?>

        <!-- Metric KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #820100 !important;">
                    <div class="card-body p-3">
                        <span class="text-muted small fw-bold text-uppercase">Total Monthly Logs</span>
                        <h4 class="fw-bold mb-0 text-dark"><?= count($records) ?></h4>
                        <div class="text-muted small mt-1">Recorded for Year <?= htmlspecialchars($selected_year) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #10b981 !important;">
                    <div class="card-body p-3">
                        <span class="text-muted small fw-bold text-uppercase">Total Received Doses</span>
                        <h4 class="fw-bold mb-0 text-success">+<?= number_format($tot_received) ?></h4>
                        <div class="text-muted small mt-1">Delivered to <?= htmlspecialchars($range_name) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #3b82f6 !important;">
                    <div class="card-body p-3">
                        <span class="text-muted small fw-bold text-uppercase">Total Administered / Used</span>
                        <h4 class="fw-bold mb-0 text-primary">-<?= number_format($tot_used) ?></h4>
                        <div class="text-muted small mt-1">Vaccinated in field sessions</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #f59e0b !important;">
                    <div class="card-body p-3">
                        <span class="text-muted small fw-bold text-uppercase">Live Cumulative Balance</span>
                        <h4 class="fw-bold mb-0 text-dark"><?= number_format($tot_closing) ?></h4>
                        <div class="text-muted small mt-1">Available across recorded batches</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap-fill me-2 text-danger"></i>Quick Actions & Integrated Batch Tools</h6>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <button class="btn w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #820100; min-height: 105px;" data-bs-toggle="modal" data-bs-target="#addVaccineBalanceModal">
                                    <i class="bi bi-plus-circle fs-3 mb-1 text-warning"></i>
                                    <span class="small fw-bold text-uppercase">Add Vaccine Record</span>
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button type="button" class="btn w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center text-decoration-none" style="background-color: #b08723; min-height: 105px;" data-bs-toggle="modal" data-bs-target="#addVaccineBatchModal">
                                    <i class="bi bi-box-seam fs-3 mb-1 text-light"></i>
                                    <span class="small fw-bold text-uppercase">Register Vaccine Batch</span>
                                </button>
                            </div>
                            <div class="col-md-3">
                                <a href="vaccination_targets.php<?= $range_id ? '?range_id=' . $range_id : '' ?>" class="btn w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center text-decoration-none" style="background-color: #370709; min-height: 105px;">
                                    <i class="bi bi-shield-check fs-3 mb-1 text-warning"></i>
                                    <span class="small fw-bold text-uppercase">Vaccination Programs</span>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="drug_maintenance.php<?= $range_id ? '?range_id=' . $range_id : '' ?>" class="btn w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center text-decoration-none" style="background-color: #2b3a4a; min-height: 105px;">
                                    <i class="bi bi-capsule fs-3 mb-1 text-light"></i>
                                    <span class="small fw-bold text-uppercase">Drug Maintenance</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RECORDS LIST TABLE -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0 fw-bold" style="color: #370709;"><i class="bi bi-file-earmark-medical me-2 text-danger"></i>Vaccine Stock Balances for <?= htmlspecialchars($selected_year) ?></h6>
                <span class="badge" style="background-color: #d4c7b7; color: #370709;"><?= htmlspecialchars($range_name) ?> Range</span>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small" id="vaccineTable">
                        <thead class="bg-light small text-uppercase" style="background-color: #d4c7b7; color: #370709;">
                            <tr>
                                <th>Month</th>
                                <th>Vaccine Name</th>
                                <th>Batch No.</th>
                                <th class="text-end">Opening (Doses)</th>
                                <th class="text-end">Received</th>
                                <th class="text-end">Used</th>
                                <th class="text-end">Spoilt</th>
                                <th class="text-end">Transferred</th>
                                <th class="text-end">Closing</th>
                                <th>Expiry</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($records)): ?>
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-4">No vaccine balance records found for year <?= htmlspecialchars($selected_year) ?>. Click <strong>"Add Vaccine Record"</strong> to begin.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($records as $row): ?>
                                    <tr
                                        data-id="<?= $row['id'] ?>"
                                        data-year="<?= htmlspecialchars($row['report_year']) ?>"
                                        data-month="<?= htmlspecialchars($row['report_month']) ?>"
                                        data-name="<?= htmlspecialchars($row['vaccine_name']) ?>"
                                        data-batch="<?= htmlspecialchars($row['batch_no'] ?? '') ?>"
                                        data-opening="<?= htmlspecialchars($row['opening_balance']) ?>"
                                        data-received="<?= htmlspecialchars($row['received_doses']) ?>"
                                        data-used="<?= htmlspecialchars($row['used_doses']) ?>"
                                        data-spoilt="<?= htmlspecialchars($row['spoilt_damaged_doses']) ?>"
                                        data-transferred="<?= htmlspecialchars($row['transferred_doses']) ?>"
                                        data-closing="<?= htmlspecialchars($row['closing_balance']) ?>"
                                        data-expiry="<?= htmlspecialchars($row['expiry_date'] ?? '') ?>"
                                        data-remarks="<?= htmlspecialchars($row['remarks'] ?? '') ?>">
                                        <td data-order="<?= $row['report_month'] ?>" class="fw-bold"><?= htmlspecialchars($month_names[$row['report_month']] ?? $row['report_month']) ?></td>
                                        <td><strong><?= htmlspecialchars($row['vaccine_name']) ?></strong></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($row['batch_no'] ?? 'N/A') ?></span></td>
                                        <td class="text-end font-monospace text-primary"><?= number_format($row['opening_balance']) ?></td>
                                        <td class="text-end font-monospace text-success">+<?= number_format($row['received_doses']) ?></td>
                                        <td class="text-end font-monospace text-danger">-<?= number_format($row['used_doses']) ?></td>
                                        <td class="text-end font-monospace text-muted">-<?= number_format($row['spoilt_damaged_doses']) ?></td>
                                        <td class="text-end font-monospace text-warning">-<?= number_format($row['transferred_doses']) ?></td>
                                        <td class="text-end font-monospace fw-bold text-dark bg-light"><?= number_format($row['closing_balance']) ?></td>
                                        <td><span class="small font-monospace text-danger"><?= htmlspecialchars($row['expiry_date'] ?? 'N/A') ?></span></td>
                                        <td class="text-end">
                                            <button class="btn btn-xs btn-outline-primary btn-edit-vac" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                            <button class="btn btn-xs btn-outline-danger btn-delete-vac" title="Delete"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- Modals -->
<?php include 'model/add_vaccine_balance_modal.php'; ?>
<?php include 'model/edit_vaccine_balance_modal.php'; ?>
<?php include 'model/vaccine_batch_modal.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#vaccineTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 10,
        "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
        "buttons": [
            {
                extend: 'csvHtml5',
                text: '<i class="bi bi-file-earmark-spreadsheet"></i> CSV',
                className: 'btn btn-sm btn-success me-2'
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="bi bi-file-pdf"></i> PDF',
                className: 'btn btn-sm btn-danger me-2'
            },
            {
                extend: 'print',
                text: '<i class="bi bi-printer"></i> Print',
                className: 'btn btn-sm btn-dark'
            }
        ]
    });

    // Check status redirects for SweetAlert feedback
    var urlParams = new URLSearchParams(window.location.search);
    var status = urlParams.get('status');

    if (status === 'success') {
        Swal.fire({
            icon: 'success',
            title: 'Record Saved!',
            text: 'Vaccine balance was processed successfully.',
            confirmButtonColor: '#820100'
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    } else if (status === 'db_error') {
        Swal.fire({
            icon: 'error',
            title: 'Operation Failed',
            text: 'Could not process database action. Check inputs and try again.',
            confirmButtonColor: '#820100'
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // Edit Modal Trigger Pre-fill
    $(document).on('click', '.btn-edit-vac', function() {
        var $row = $(this).closest('tr');
        $('#edit_id').val($row.data('id'));
        $('#edit_report_year').val($row.data('year'));
        $('#edit_report_month').val($row.data('month'));
        $('#edit_vaccine_name').val($row.data('name'));
        $('#edit_batch_no').val($row.data('batch'));
        $('#edit_opening_balance').val($row.data('opening'));
        $('#edit_received_doses').val($row.data('received'));
        $('#edit_used_doses').val($row.data('used'));
        $('#edit_spoilt_doses').val($row.data('spoilt'));
        $('#edit_transferred_doses').val($row.data('transferred'));
        $('#edit_closing_balance').val($row.data('closing'));
        $('#edit_expiry_date').val($row.data('expiry'));
        $('#edit_remarks').val($row.data('remarks'));

        new bootstrap.Modal(document.getElementById('editVaccineBalanceModal')).show();
    });

    // AJAX Delete Confirmation Click Handler
    $(document).on('click', '.btn-delete-vac', function() {
        var $row = $(this).closest('tr');
        var recordId = $row.data('id');
        var name = $row.data('name') || 'this record';

        Swal.fire({
            icon: 'warning',
            title: 'Delete Vaccine Stock Entry?',
            html: 'You are about to delete entry for "<strong>' + name + '</strong>".<br>This action cannot be undone.',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'processors/delete_vaccine_balance.php',
                    type: 'POST',
                    data: { id: recordId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'The record has been deleted.',
                                timer: 1500,
                                showConfirmButton: false
                            });
                            $row.fadeOut(400, function() {
                                $row.remove();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed',
                                text: response.message || 'Error occurred during deletion.'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: 'AJAX request execution failed.'
                        });
                    }
                });
            }
        });
    });

    // Handle inline batch registration AJAX submit
    $('#batchForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize() + '&ajax=1';
        $.ajax({
            url: 'processors/vaccine_batch_crud.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.success && res.batch) {
                    var bNum = res.batch.batch_number;
                    var bExp = res.batch.expiry_date || '';
                    var optText = bNum + (bExp ? ' (Exp: ' + bExp + ')' : '');
                    var newOpt = $('<option>', { value: bNum, text: optText, selected: true }).attr('data-expiry', bExp);
                    
                    $('#add_batch_no').append(newOpt).val(bNum);
                    $('#edit_batch_no').append(newOpt.clone());
                    if (bExp) {
                        $('#add_expiry_date').val(bExp);
                    }
                    
                    var modalEl = document.getElementById('addVaccineBatchModal');
                    var modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    Swal.fire({
                        icon: 'success',
                        title: 'Batch Created!',
                        text: 'Batch ' + bNum + ' registered and selected.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: res.message || 'Could not create batch.'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Request Failed',
                    text: 'Unable to communicate with batch processor.'
                });
            }
        });
    });
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
