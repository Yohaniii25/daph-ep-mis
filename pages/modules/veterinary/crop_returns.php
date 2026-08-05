<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../index.php");
    exit();
}

// Extract base operational keys from the live user session wrapper
$user_id = $_SESSION['user_id'] ?? null;
$range_id = $_SESSION['range_id'] ?? null;

$range_name = 'Your Range';
$district_name = 'Your District';
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : 2026;

// Step 1: Query the user's data profile if it's missing from the active session context
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

// Step 2: Extract Range Name and District Name using JOIN
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

// Step 3: Fetch Crop Returns records from DB using range_id and filtered report_year
$crop_records = [];
if (!empty($range_id)) {
    $records_sql = "
        SELECT id, report_year, report_month, item_name, 
               balance_previous_month, received_current_month, issued_current_month, 
               balance_current_month, remark 
        FROM crop_returns 
        WHERE range_id = ? AND report_year = ? 
        ORDER BY report_month DESC, item_name ASC
    ";
    $stmt = $mysqli->prepare($records_sql);
    if ($stmt) {
        $stmt->bind_param("ii", $range_id, $selected_year);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $crop_records[] = $row;
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
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">Crop Returns</h2>
                <p class="text-muted small mb-0">Crop Returns metrics captured for <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District)</p>
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
                                <button class="btn w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #820100; min-height: 105px;" data-bs-toggle="modal" data-bs-target="#addCropReturnsModal">
                                    <i class="bi bi-file-earmark-plus fs-3 mb-1"></i>
                                    <span class="small fw-bold text-uppercase">Add Record</span>
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
                <h6 class="mb-0 fw-bold"><i class="bi bi-list-task me-2 text-primary"></i>Crop Returns Records for <?= htmlspecialchars($selected_year) ?></h6>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="cropReturnsTable">
                        <thead class="bg-light small text-uppercase">
                            <tr>
                                <th>Month</th>
                                <th>Item Name</th>
                                <th>Previous Month Balance</th>
                                <th>Received This Month</th>
                                <th>Issued This Month</th>
                                <th>Ending Balance</th>
                                <th>Remarks</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($crop_records)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No records found for year <?= htmlspecialchars($selected_year) ?>.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($crop_records as $rec): ?>
                                    <tr
                                        data-id="<?= $rec['id'] ?>"
                                        data-year="<?= htmlspecialchars($rec['report_year']) ?>"
                                        data-month="<?= htmlspecialchars($rec['report_month']) ?>"
                                        data-item="<?= htmlspecialchars($rec['item_name']) ?>"
                                        data-prev="<?= htmlspecialchars($rec['balance_previous_month']) ?>"
                                        data-received="<?= htmlspecialchars($rec['received_current_month']) ?>"
                                        data-issued="<?= htmlspecialchars($rec['issued_current_month']) ?>"
                                        data-current-bal="<?= htmlspecialchars($rec['balance_current_month']) ?>"
                                        data-remark="<?= htmlspecialchars($rec['remark'] ?? '') ?>">
                                        <td data-order="<?= $rec['report_month'] ?>"><?= htmlspecialchars($month_names[$rec['report_month']] ?? $rec['report_month']) ?></td>
                                        <td><strong><?= htmlspecialchars($rec['item_name']) ?></strong></td>
                                        <td><?= number_format($rec['balance_previous_month']) ?></td>
                                        <td><?= number_format($rec['received_current_month']) ?></td>
                                        <td><?= number_format($rec['issued_current_month']) ?></td>
                                        <td><span class="badge bg-secondary"><?= number_format($rec['balance_current_month']) ?></span></td>
                                        <td><small class="text-muted"><?= htmlspecialchars($rec['remark'] ?? '') ?></small></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary btn-edit-crop" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                            <button class="btn btn-sm btn-outline-danger btn-delete-crop" title="Delete"><i class="bi bi-trash"></i></button>
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

<!-- Add Crop Returns Modal -->
<?php include 'models/add_crop_return.php'; ?>

<!-- Edit Crop Returns Modal -->
<?php include 'models/edit_crop_return.php'; ?>

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
    $('#cropReturnsTable').DataTable({
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
                className: 'btn btn-sm btn-outline-dark'
            }
        ]
    });

    // Check status redirects for SweetAlert feedback
    var urlParams = new URLSearchParams(window.location.search);
    var status = urlParams.get('status');

    if (status === 'added') {
        Swal.fire({
            icon: 'success',
            title: 'Record Saved!',
            text: 'Crop Return entry has been logged successfully.',
            confirmButtonColor: '#370709'
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    } else if (status === 'updated') {
        Swal.fire({
            icon: 'success',
            title: 'Record Updated!',
            text: 'Crop Return entry changes saved successfully.',
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
    $(document).on('click', '.btn-edit-crop', function() {
        var $row = $(this).closest('tr');
        $('#edit_id').val($row.data('id'));
        $('#edit_report_year').val($row.data('year'));
        $('#edit_report_month').val($row.data('month'));
        $('#edit_item_name').val($row.data('item'));
        $('#edit_prev_bal').val($row.data('prev'));
        $('#edit_received').val($row.data('received'));
        $('#edit_issued').val($row.data('issued'));
        $('#edit_current_bal').val($row.data('current-bal'));
        $('#edit_remark').val($row.data('remark'));

        new bootstrap.Modal(document.getElementById('editCropReturnsModal')).show();
    });

    // AJAX Delete Confirmation Click Handler
    $(document).on('click', '.btn-delete-crop', function() {
        var $row = $(this).closest('tr');
        var recordId = $row.data('id');
        var itemName = $row.data('item') || 'this record';

        Swal.fire({
            icon: 'warning',
            title: 'Delete Crop Return?',
            html: 'You are about to delete <strong>' + itemName + '</strong>.<br>This action cannot be undone.',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'processors/delete_crop_return.php',
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