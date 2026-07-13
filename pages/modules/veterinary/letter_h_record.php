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

// Fetch Letter H accounts records matching year filter and range
$records = [];
if (!empty($range_id)) {
    $records_sql = "
        SELECT id, transaction_date, transaction_type, reference_no, particulars, quantity, rate, amount 
        FROM letter_h_accounts 
        WHERE range_id = ? AND YEAR(transaction_date) = ? 
        ORDER BY transaction_date DESC, id DESC
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

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">

        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">H Records (Accounts)</h2>
                <p class="text-muted small mb-0">Record and track receipts and disbursements for <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District)</p>
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
                                <button class="btn w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #820100; min-height: 105px;" data-bs-toggle="modal" data-bs-target="#addHRecordModal">
                                    <i class="bi bi-plus-circle fs-3 mb-1"></i>
                                    <span class="small fw-bold text-uppercase">Add H Record</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RECORDS LIST TABLE -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-list-task me-2 text-primary"></i>Letter H Records for <?= htmlspecialchars($selected_year) ?></h6>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="hRecordsTable">
                        <thead class="bg-light small text-uppercase">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Reference No.</th>
                                <th>Particulars</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Rate</th>
                                <th class="text-end">Amount (LKR)</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($records)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No H records found for year <?= htmlspecialchars($selected_year) ?>.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($records as $row): ?>
                                    <tr
                                        data-id="<?= $row['id'] ?>"
                                        data-date="<?= htmlspecialchars($row['transaction_date']) ?>"
                                        data-type="<?= htmlspecialchars($row['transaction_type']) ?>"
                                        data-ref="<?= htmlspecialchars($row['reference_no'] ?? '') ?>"
                                        data-particulars="<?= htmlspecialchars($row['particulars']) ?>"
                                        data-qty="<?= htmlspecialchars($row['quantity'] ?? '') ?>"
                                        data-rate="<?= htmlspecialchars($row['rate'] ?? '') ?>"
                                        data-amount="<?= htmlspecialchars($row['amount']) ?>">
                                        <td><?= date('d M, Y', strtotime($row['transaction_date'])) ?></td>
                                        <td>
                                            <?php if ($row['transaction_type'] === 'Receipt'): ?>
                                                <span class="badge bg-success px-3 py-2" style="font-size: 10px;">Receipt</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary px-3 py-2" style="font-size: 10px;">Disbursement</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['reference_no'] ?? '-') ?></td>
                                        <td><strong><?= htmlspecialchars($row['particulars']) ?></strong></td>
                                        <td class="text-end"><?= $row['quantity'] !== null ? number_format($row['quantity']) : '-' ?></td>
                                        <td class="text-end"><?= $row['rate'] !== null ? number_format($row['rate'], 2) : '-' ?></td>
                                        <td class="text-end fw-bold text-dark"><?= number_format($row['amount'], 2) ?></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary btn-edit-h" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                            <button class="btn btn-sm btn-outline-danger btn-delete-h" title="Delete"><i class="bi bi-trash"></i></button>
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
<?php include 'models/add_h_record_modal.php'; ?>

<!-- Edit Modal -->
<?php include 'models/edit_h_record_modal.php'; ?>

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
    $('#hRecordsTable').DataTable({
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
            title: 'H Record Saved!',
            text: 'Your action was processed successfully.',
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
    $(document).on('click', '.btn-edit-h', function() {
        var $row = $(this).closest('tr');
        $('#edit_id').val($row.data('id'));
        $('#edit_transaction_date').val($row.data('date'));
        $('#edit_transaction_type').val($row.data('type'));
        $('#edit_reference_no').val($row.data('ref'));
        $('#edit_particulars').val($row.data('particulars'));
        $('#edit_qty').val($row.data('qty'));
        $('#edit_rate').val($row.data('rate'));
        $('#edit_amount').val($row.data('amount'));

        new bootstrap.Modal(document.getElementById('editHRecordModal')).show();
    });

    // AJAX Delete Confirmation Click Handler
    $(document).on('click', '.btn-delete-h', function() {
        var $row = $(this).closest('tr');
        var recordId = $row.data('id');
        var name = $row.data('particulars') || 'this record';

        Swal.fire({
            icon: 'warning',
            title: 'Delete H Record?',
            html: 'You are about to delete entry "<strong>' + name + '</strong>".<br>This action cannot be undone.',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'processors/delete_letter_h.php',
                    type: 'POST',
                    data: { id: recordId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'The H record has been deleted.',
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