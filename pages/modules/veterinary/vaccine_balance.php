<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$range_id = $_SESSION['range_id'] ?? null;

$range_name = 'Your Range';
$district_name = 'Your District';
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Resolve Range ID from User Profiler if empty in session
if (empty($range_id) && !empty($user_id)) {
    $user_query = $mysqli->prepare("SELECT range_id FROM users WHERE id = ?");
    if ($user_query) {
        $user_query->bind_param("i", $user_id);
        $user_query->execute();
        $user_result = $user_query->get_result();
        if ($row = $user_result->fetch_assoc()) {
            $_SESSION['range_id'] = $row['range_id'];
            $range_id = $row['range_id'];
        }
        $user_query->close();
    }
}

// Fetch Range and District Info using a JOIN query
if (!empty($range_id)) {
    $details_sql = "
        SELECT 
            vr.name AS range_name,
            d.name AS district_name
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

$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">



        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">Vaccine Balance - Monthly Returns</h2>
                <p class="text-muted small mb-0">Manage monthly vaccine stock levels for <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District)</p>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <form method="GET" class="d-flex align-items-center gap-2">
                    <label class="small fw-bold text-muted mb-0">Year:</label>
                    <select name="year" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 100px;">
                        <?php
                        $curr_year = intval(date('Y'));
                        for ($y = $curr_year - 5; $y <= $curr_year + 5; $y++) {
                            $sel = ($y === $selected_year) ? 'selected' : '';
                            echo "<option value=\"$y\" $sel>$y</option>";
                        }
                        ?>
                    </select>
                </form>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Quick Actions</h6>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <button class="btn w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #820100; min-height: 105px;" data-bs-toggle="modal" data-bs-target="#addVaccineBalanceModal">
                                    <i class="bi bi-plus-circle fs-3 mb-1"></i>
                                    <span class="small fw-bold text-uppercase">Add Vaccine Record</span>
                                </button>
                            </div>
                            <div class="col-md-3">
                                <a href="batches.php" class="btn w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center text-decoration-none" style="background-color: #b08723; min-height: 105px;">
                                    <i class="bi bi-box-seam fs-3 mb-1"></i>
                                    <span class="small fw-bold text-uppercase">Vaccine Batches</span>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="drug_types.php" class="btn w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center text-decoration-none" style="background-color: #370709; min-height: 105px;">
                                    <i class="bi bi-capsule fs-3 mb-1"></i>
                                    <span class="small fw-bold text-uppercase">Drug Types Config</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RECORDS LIST TABLE -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-file-earmark-medical me-2 text-primary"></i>Vaccine Stock Balances for <?= htmlspecialchars($selected_year) ?></h6>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="vaccineTable">
                        <thead class="bg-light small text-uppercase">
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
                                    <td colspan="11" class="text-center text-muted py-4">No vaccine balance records found for year <?= htmlspecialchars($selected_year) ?>.</td>
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
                                        <td data-order="<?= $row['report_month'] ?>"><?= htmlspecialchars($month_names[$row['report_month']] ?? $row['report_month']) ?></td>
                                        <td><strong><?= htmlspecialchars($row['vaccine_name']) ?></strong></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($row['batch_no'] ?? 'N/A') ?></span></td>
                                        <td class="text-end"><?= number_format($row['opening_balance']) ?></td>
                                        <td class="text-end text-success">+<?= number_format($row['received_doses']) ?></td>
                                        <td class="text-end text-danger">-<?= number_format($row['used_doses']) ?></td>
                                        <td class="text-end text-muted">-<?= number_format($row['spoilt_damaged_doses']) ?></td>
                                        <td class="text-end text-warning">-<?= number_format($row['transferred_doses']) ?></td>
                                        <td class="text-end fw-bold text-dark"><?= number_format($row['closing_balance']) ?></td>
                                        <td><span class="small font-monospace"><?= htmlspecialchars($row['expiry_date'] ?? 'N/A') ?></span></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary btn-edit-vac" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                            <button class="btn btn-sm btn-outline-danger btn-delete-vac" title="Delete"><i class="bi bi-trash"></i></button>
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

<!-- Add Modal -->
<?php include 'models/add_vaccine_balance_modal.php'; ?>

<!-- Edit Modal -->
<?php include 'models/edit_vaccine_balance_modal.php'; ?>

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
            confirmButtonColor: '#370709'
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    } else if (status === 'db_error') {
        Swal.fire({
            icon: 'error',
            title: 'Operation Failed',
            text: 'Could not process database action. Check inputs and try again.',
            confirmButtonColor: '#370709'
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
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
