<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'sms') die("Access denied");
require_once '../../../config/db_connect.php';

// Fetch the absolute count of active distinct batches registered in the warehouse system
$total_batches_query = "SELECT COUNT(id) AS active_batches_count FROM `vaccine_batches` WHERE `is_active` = 1";
$total_batches_res = $mysqli->query($total_batches_query);
$active_batches_count = 0;
if ($total_batches_res) {
    $row = $total_batches_res->fetch_assoc();
    $active_batches_count = $row['active_batches_count'] ?? 0;
}
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<style>
    .metric-card-custom {
        border-radius: 16px !important;
        background-color: #ffffff;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04) !important;
        transition: all 0.25s ease-in-out;
    }

    .metric-card-custom:hover {
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08) !important;
    }
</style>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Immunization - Vaccine Batches Register</h2>

        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-primary border-4 metric-card-custom">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold mb-2">Active Tracked Batches</h6>
                        <h2 class="text-primary mb-0 fw-bold"><?= number_format($active_batches_count) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4 border-0">
            <div class="card-header bg-light border-0 py-3">
                <h5 class="m-0 fw-bold text-dark">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addVaccineBatchModal">
                            <i class="bi bi-box-seam fs-5"></i><br>
                            Add New Batch
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3 mb-5">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="m-0 fw-bold text-dark"><i class="bi bi-bookmark-star me-2 text-success"></i>Registered Vaccine Stock Batches</h5>
            </div>
            <div class="card-body">
                <table id="batchTable" class="table table-striped align-middle row-border" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 8%;">ID</th>
                            <th style="width: 30%;">Batch Identity Code</th>
                            <th style="width: 15%;">Status</th>
                            <th style="width: 27%;">Remarks / Log Notes</th>
                            <th style="width: 20%;">Date Registered</th>
                            <th style="width: 10%;" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $batch_sql = "SELECT id, batch_number, is_active, remarks, created_at FROM `vaccine_batches` ORDER BY id DESC";
                        $res = $mysqli->query($batch_sql);
                        if ($res && $res->num_rows > 0):
                            while ($row = $res->fetch_assoc()):
                        ?>
                                <tr>
                                    <td class="fw-bold text-secondary">#<?= $row['id'] ?></td>
                                    <td>
                                        <div class="fw-bold text-dark">
                                            <i class="bi bi-qr-code-scan me-2 text-muted"></i><?= htmlspecialchars($row['batch_number']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($row['is_active'] == 1): ?>
                                            <span class="badge bg-success-subtle text-success px-2.5 py-1.5 rounded-pill fw-semibold">
                                                <i class="bi bi-check-circle-fill me-1"></i>Active Stock
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger px-2.5 py-1.5 rounded-pill fw-semibold">
                                                <i class="bi bi-x-circle-fill me-1"></i>Archived
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted text-wrap d-block text-break">
                                            <?= htmlspecialchars($row['remarks'] ?: 'No operational remarks added.') ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="small text-dark fw-semibold">
                                            <i class="bi bi-calendar3 me-1.5 text-muted"></i><?= date('Y-m-d g:i A', strtotime($row['created_at'])) ?>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-secondary edit-batch-btn"
                                                data-id="<?= $row['id'] ?>"
                                                data-batch="<?= htmlspecialchars($row['batch_number'], ENT_QUOTES) ?>"
                                                data-status="<?= $row['is_active'] ?>"
                                                data-remarks="<?= htmlspecialchars($row['remarks'], ENT_QUOTES) ?>"
                                                data-bs-toggle="modal" data-bs-target="#addVaccineBatchModal">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="processors/vaccine_batch_crud.php?action=delete&id=<?= $row['id'] ?>"
                                                class="btn btn-outline-danger"
                                                onclick="return confirm('Are you sure you want to drop this unique vaccine batch configuration? This might orphan downstream dose entries.');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                        <?php
                            endwhile;
                        endif;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php include './models/vaccine_batch_modal.php'; ?>

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

<script>
    $(document).ready(function() {
        // Intercept inline dynamic click event rules for modification modal mappings
        $(document).on('click', '.edit-batch-btn', function() {
            $('#modalAction').val('update');
            $('#batchId').val($(this).data('id'));
            $('#batchNumber').val($(this).data('batch'));
            $('#is_active').val($(this).data('status'));
            $('#remarks').val($(this).data('remarks'));

            $('#modalTitle').html('<i class="bi bi-pencil-square me-2 text-warning"></i>Modify Master Batch Details');
            $('#submitBtn').removeClass('btn-success').addClass('btn-warning').text('Save Changes');
        });

        // Completely clear layout structures when form dismiss occurs
        $('#addVaccineBatchModal').on('hidden.bs.modal', function() {
            $('#modalAction').val('create');
            $('#batchId').val('');
            $('#batchForm')[0].reset();

            $('#modalTitle').html('<i class="bi bi-box-seam me-2"></i>Register New Vaccine Stock Batch');
            $('#submitBtn').removeClass('btn-warning').addClass('btn-success').text('Save Configuration');
        });

        $('#batchTable').DataTable({
            "order": [[0, "desc"]],
            "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search batches register..."
            },
            "buttons": [
                {
                    extend: 'csv',
                    text: '<i class="bi bi-filetype-csv"></i> CSV',
                    className: 'btn btn-sm btn-success shadow-sm me-1 rounded',
                    titleAttr: 'Export Filtered CSV'
                },
                {
                    extend: 'pdf',
                    text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                    className: 'btn btn-sm btn-danger shadow-sm me-1 rounded',
                    title: 'Registered Master Vaccine Batches Ledger'
                },
                {
                    extend: 'print',
                    text: '<i class="bi bi-printer"></i> Print',
                    className: 'btn btn-sm btn-warning shadow-sm rounded text-dark'
                }
            ]
        });
    });
</script>

<?php
require_once '../../../includes/footer.php';
?>