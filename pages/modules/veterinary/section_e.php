<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

if (!isset($_SESSION['full_name'])) {
    $_SESSION['full_name'] = $_SESSION['username'] ?? 'Veterinary Surgeon';
}

$full_name   = $_SESSION['full_name'];
$range_id    = $_SESSION['range_id'] ?? null;
$district_id = $_SESSION['district_id'] ?? null;

if (empty($range_id)) {
    die('<div class="alert alert-danger text-center p-5 m-5">Error: Your account is not assigned to any Veterinary Range.</div>');
}

require_once '../../../config/db_connect.php';

$district_name = 'Unknown District';
$range_name    = 'Unknown Range';

// Fetch District and Range Names
if ($district_id) {
    $stmt = $mysqli->prepare("SELECT name FROM districts WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $district_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $district_name = $row['name'];
        }
        $stmt->close();
    }
}

if ($range_id) {
    $stmt = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $range_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $range_name = $row['name'];
        }
        $stmt->close();
    }
}

$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// 1. Fetch data
$report_stmt = $mysqli->prepare("
    SELECT 
        se.id,
        se.report_year, 
        se.report_month, 
        se.amount, 
        pc.category_name, 
        pi.item_name, 
        pi.unit,
        se.category_id,
        se.item_id
    FROM section_e se
    JOIN production_categories pc ON se.category_id = pc.id
    JOIN production_items pi ON se.item_id = pi.id
    WHERE se.range_id = ? AND se.report_year = ?
    ORDER BY se.report_month DESC, pc.sort_order ASC, pi.item_name ASC
");

$production_report = [];
if ($report_stmt) {
    $report_stmt->bind_param("ii", $range_id, $selected_year);
    $report_stmt->execute();
    $production_report = $report_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $report_stmt->close();
}

$unique_categories = [];
foreach ($production_report as $row) {
    if (!in_array($row['category_name'], $unique_categories)) {
        $unique_categories[] = $row['category_name'];
    }
}

// Month names helper
$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

require_once '../../../includes/header.php';
?>

<!-- Add dependencies styles -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">



        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">Section E: Production</h2>
                <p class="text-muted small mb-0">Production metrics captured for <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District)</p>
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
                        <h6 class="mb-0 fw-bold class-header"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Quick Actions</h6>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <button class="btn w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #370709; min-height: 105px;" data-bs-toggle="modal" data-bs-target="#addProductionModal">
                                    <i class="bi bi-file-earmark-plus fs-3 mb-1"></i>
                                    <span class="small fw-bold text-uppercase">Add Production Record</span>
                                </button>
                            </div>
                            <div class="col-md-4">
                                <button class="btn w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #58181b; min-height: 105px;" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                    <i class="bi bi-folder-plus fs-3 mb-1"></i>
                                    <span class="small fw-bold text-uppercase">Add Category</span>
                                </button>
                            </div>
                            <div class="col-md-4">
                                <button class="btn w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #820100; min-height: 105px;" data-bs-toggle="modal" data-bs-target="#addSubCategoryModal">
                                    <i class="bi bi-tag-fill fs-3 mb-1"></i>
                                    <span class="small fw-bold text-uppercase">Add Sub Category</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 mb-0 fw-bold text-uppercase">Production Monitoring Dashboard</h2>
            </div>
            <div class="text-end">
                <h5 class="mb-0 fw-bold" style="color: #370709;"><?= $selected_year ?></h5>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="row g-3 mb-4 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">FILTER BY CATEGORY</label>
                        <div class="input-group shadow-sm">
                            <select id="categoryFilter" class="form-select border-secondary">
                                <option value="">View All Categories</option>
                                <?php foreach ($unique_categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-outline-secondary" type="button" id="resetFilter">Reset</button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">SEARCH ITEMS</label>
                        <input type="text" id="tableSearch" class="form-control shadow-sm" placeholder="Type to search product...">
                    </div>
                </div>

                <table id="productionTable" class="table table-hover align-middle w-100">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th>Month</th>
                            <th>Category</th>
                            <th>Product Name</th>
                            <th>Unit</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($production_report as $rec): 
                            $month_name = $month_names[$rec['report_month']] ?? $rec['report_month'];
                        ?>
                            <tr data-id="<?= $rec['id'] ?>"
                                data-year="<?= $rec['report_year'] ?>"
                                data-month="<?= $rec['report_month'] ?>"
                                data-category-id="<?= $rec['category_id'] ?>"
                                data-item-id="<?= $rec['item_id'] ?>"
                                data-amount="<?= $rec['amount'] ?>"
                                data-unit="<?= htmlspecialchars($rec['unit']) ?>"
                                data-item="<?= htmlspecialchars($rec['item_name']) ?>">
                                <td data-order="<?= $rec['report_month'] ?>"><?= htmlspecialchars($month_name) ?></td>
                                <td><?= htmlspecialchars($rec['category_name']) ?></td>
                                <td><strong><?= htmlspecialchars($rec['item_name']) ?></strong></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($rec['unit']) ?></span></td>
                                <td><?= htmlspecialchars(number_format($rec['amount'], 2)) ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary btn-edit-prod" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-delete-prod" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php include 'models/add_production_record_modal.php'; ?>
        <?php include 'models/edit_production_record_modal.php'; ?>
        <?php include 'models/add_category_modal.php'; ?>
        <?php include 'models/add_subcategory_modal.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
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
    var table = $('#productionTable').DataTable({
        "order": [[0, "desc"]], // sort by Month desc by default
        "pageLength": 50,
        "dom": '<"d-flex justify-content-end mb-3"B>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
        "buttons": [
            {
                extend: 'csv',
                text: '<i class="bi bi-file-earmark-spreadsheet"></i> CSV',
                className: 'btn btn-sm btn-success',
                exportOptions: { columns: [0, 1, 2, 3, 4] }
            },
            {
                extend: 'pdf',
                text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                className: 'btn btn-sm btn-danger',
                orientation: 'landscape',
                title: 'Production Report: <?= $range_name ?> (<?= $selected_year ?>)',
                exportOptions: { columns: [0, 1, 2, 3, 4] }
            },
            {
                extend: 'print',
                text: '<i class="bi bi-printer"></i> Print',
                className: 'btn btn-sm btn-dark',
                exportOptions: { columns: [0, 1, 2, 3, 4] }
            }
        ],
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search production..."
        }
    });

    // Category Filter (now column 1 is Category)
    $('#categoryFilter').on('change', function() {
        var val = $.fn.dataTable.util.escapeRegex($(this).val());
        table.column(1).search(val ? '^' + val + '$' : '', true, false).draw();
    });

    // Search Bar
    $('#tableSearch').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Reset Filter
    $('#resetFilter').on('click', function() {
        $('#categoryFilter').val('');
        $('#tableSearch').val('');
        table.search('').column(1).search('').draw();
    });

    // Edit Handler
    $(document).on('click', '.btn-edit-prod', function() {
        var $row = $(this).closest('tr');
        var id = $row.data('id');
        var year = $row.data('year');
        var month = $row.data('month');
        var catId = $row.data('category-id');
        var itemId = $row.data('item-id');
        var amount = $row.data('amount');

        window.initEditProductionModal(id, year, month, catId, itemId, amount);
        new bootstrap.Modal(document.getElementById('editProductionModal')).show();
    });

    // Delete AJAX SweetAlert Confirmation
    $(document).on('click', '.btn-delete-prod', function() {
        var $row = $(this).closest('tr');
        var recordId = $row.data('id');
        var itemName = $row.data('item') || 'this record';

        Swal.fire({
            icon: 'warning',
            title: 'Delete Production Record?',
            html: 'You are about to delete record of <strong>' + itemName + '</strong>.<br>This action cannot be undone.',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'processors/delete_production.php',
                    type: 'POST',
                    data: { id: recordId },
                    dataType: 'json',
                    success: function(resp) {
                        if (resp.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'The production record has been successfully deleted.',
                                confirmButtonColor: '#370709',
                                timer: 2000
                            }).then(function() {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: resp.message || 'Failed to delete record from database.',
                                confirmButtonColor: '#370709'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while connecting to the delete handler.',
                            confirmButtonColor: '#370709'
                        });
                    }
                });
            }
        });
    });
});
</script>

<?php if (isset($_SESSION['msg'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '<?= $_SESSION['msg_type'] === 'success' ? 'success' : 'error' ?>',
                title: '<?= htmlspecialchars($_SESSION['msg_type'] === 'success' ? 'Success' : 'Error') ?>',
                text: '<?= htmlspecialchars($_SESSION['msg']) ?>',
                confirmButtonColor: '#370709'
            });
        });
    </script>
    <?php 
    unset($_SESSION['msg']);
    unset($_SESSION['msg_type']);
    endif; 
?>

<?php require_once '../../../includes/footer.php'; ?>