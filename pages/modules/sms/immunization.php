<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'sms') die("Access denied");
require_once '../../../config/db_connect.php';

// Dynamic metrics extraction using your actual column names
$metric_sql = "SELECT 
    SUM(starter_count_month) as global_starter,
    SUM(during_month_received) as global_received,
    SUM(used_doses_count) as global_used,
    SUM(doses_damaged) as global_damaged
FROM sms_immunization";

$metric_res = $mysqli->query($metric_sql);
$metrics = $metric_res ? $metric_res->fetch_assoc() : [];

$starter_doses  = intval($metrics['global_starter'] ?? 0);
$received_doses = intval($metrics['global_received'] ?? 0);
$doses_used     = intval($metrics['global_used'] ?? 0);
$doses_damaged  = intval($metrics['global_damaged'] ?? 0);

// Formula calculation matching your system metrics
$balance_doses  = ($starter_doses + $received_doses) - ($doses_used + $doses_damaged);

// Count active unique batch options available from your setup configuration table
$batch_count_res = $mysqli->query("SELECT COUNT(id) as total FROM vaccine_batches WHERE is_active = 1");
$total_batches = $batch_count_res ? $batch_count_res->fetch_assoc()['total'] : 0;
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Immunization & Vaccine Logistics Ledger</h2>

        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Active Tracked Batches</h6>
                        <h2 class="text-primary mb-0 fw-bold"><?= number_format($total_batches) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Doses Allocated (Starter)</h6>
                        <h2 class="text-warning mb-0 fw-bold"><?= number_format($starter_doses) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Total Doses Used</h6>
                        <h2 class="text-success mb-0 fw-bold"><?= number_format($doses_used) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Total Live Balance Available</h6>
                        <h2 class="text-info mb-0 fw-bold"><?= number_format($balance_doses) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#addimmunizationModal">
                            <i class="bi bi-plus-circle"></i><br>
                            Add New immunization record
                        </button>
                    </div>
                    <div class="col-md-3">
                        <a href="vaccine_types.php" class="btn btn-primary w-100 py-3">
                            <i class="bi bi-search"></i><br>
                            Vaccine Types
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="batches.php" class="btn btn-warning w-100 py-3">
                            <i class="bi bi-people"></i><br>
                            Batches
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="m-0 fw-bold text-dark"><i class="bi bi-shield-plus me-2 text-success"></i>Monthly Immunization & Vaccine Balance Ledger</h5>
            </div>
            <div class="card-body">
                <table id="immunizationTable" class="table table-bordered table-striped align-middle row-border" style="width:100%">
                    <thead class="table-light text-center align-middle">
                        <tr>
                            <th rowspan="2" style="width: 5%;">ID</th>
                            <th rowspan="2" style="width: 10%;">Date</th>
                            <th rowspan="2" style="width: 15%;">Vaccination Type</th>
                            <th rowspan="2" style="width: 10%;">Starter Count (Month)</th>
                            <th rowspan="2" style="width: 10%;">Received (During Month)</th>
                            <th colspan="2" class="bg-primary-subtle text-primary fw-bold">Doses Used</th>
                            <th rowspan="2" style="width: 10%;" class="text-danger fw-bold">Doses Damaged</th>
                            <th colspan="2" class="bg-success-subtle text-success fw-bold">Balance Doses</th>
                            <th rowspan="2" style="width: 10%;" class="text-end">Actions</th>
                        </tr>
                        <tr>
                            <th class="bg-primary-subtle text-primary-emphasis small py-1">Batch Number</th>
                            <th class="bg-primary-subtle text-primary-emphasis small py-1">No. of Doses</th>
                            <th class="bg-success-subtle text-success-emphasis small py-1">Batch Number</th>
                            <th class="bg-success-subtle text-success-emphasis small py-1">No. of Doses</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        //Query rewritten to use your exact table columns directly without broken JOINs
                        $ledger_sql = "SELECT 
                            id, log_date, vaccination_type, starter_count_month, during_month_received, 
                            used_batch_number, used_doses_count, doses_damaged, balance_batch_number, balance_doses_qty 
                        FROM sms_immunization 
                        ORDER BY log_date DESC, id DESC";

                        $res = $mysqli->query($ledger_sql);
                        if ($res && $res->num_rows > 0):
                            while ($row = $res->fetch_assoc()):
                        ?>
                                <tr>
                                    <td class="text-center fw-bold text-secondary">#<?= $row['id'] ?></td>
                                    <td class="text-center fw-semibold"><?= htmlspecialchars($row['log_date']) ?></td>
                                    <td><span class="fw-bold text-dark"><?= htmlspecialchars($row['vaccination_type']) ?></span></td>
                                    <td class="text-center bg-light text-primary-emphasis fw-bold"><?= number_format($row['starter_count_month']) ?></td>
                                    <td class="text-center bg-light text-success-emphasis fw-bold">+<?= number_format($row['during_month_received']) ?></td>

                                    <td class="text-center bg-primary-subtle fw-semibold text-dark"><i class="bi bi-tag-fill text-primary me-1"></i><?= htmlspecialchars($row['used_batch_number']) ?></td>
                                    <td class="text-center bg-primary-subtle fw-bold text-primary"><?= number_format($row['used_doses_count']) ?></td>

                                    <td class="text-center bg-danger-subtle fw-bold text-danger">-<?= number_format($row['doses_damaged']) ?></td>

                                    <td class="text-center bg-success-subtle fw-semibold text-dark"><i class="bi bi-box-seam-fill text-success me-1"></i><?= htmlspecialchars($row['balance_batch_number']) ?></td>
                                    <td class="text-center bg-success-subtle fw-black <?= $row['balance_doses_qty'] < 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= number_format($row['balance_doses_qty']) ?>
                                    </td>

                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-secondary edit-imm-btn"
                                                data-id="<?= $row['id'] ?>"
                                                data-date="<?= $row['log_date'] ?>"
                                                data-vtype="<?= htmlspecialchars($row['vaccination_type'], ENT_QUOTES) ?>"
                                                data-batch-number="<?= htmlspecialchars($row['used_batch_number'], ENT_QUOTES) ?>"
                                                data-starter="<?= $row['starter_count_month'] ?>"
                                                data-received="<?= $row['during_month_received'] ?>"
                                                data-used="<?= $row['used_doses_count'] ?>"
                                                data-damaged="<?= $row['doses_damaged'] ?>"
                                                data-bs-toggle="modal" data-bs-target="#addimmunizationModal">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="processors/immunization_crud.php?action=delete&id=<?= $row['id'] ?>"
                                                class="btn btn-outline-danger"
                                                onclick="return confirm('Are you sure you want to completely erase this specific ledger entry record?');">
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

<?php include './models/immunization_modal.php'; ?>

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
        $('#immunizationTable').DataTable({
            "order": [
                [0, "desc"]
            ],
            "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search ledger rows..."
            },
            "buttons": [{
                    extend: 'csv',
                    text: '<i class="bi bi-filetype-csv"></i> CSV',
                    className: 'btn btn-sm btn-success me-1 rounded shadow-sm'
                },
                {
                    extend: 'pdf',
                    text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                    className: 'btn btn-sm btn-danger me-1 rounded shadow-sm',
                    title: 'Monthly Immunization Stock Ledger Summary'
                },
                {
                    extend: 'print',
                    text: '<i class="bi bi-printer"></i> Print',
                    className: 'btn btn-sm btn-warning text-dark rounded shadow-sm'
                }
            ]
        });

        // Trigger Interceptor for loading Row items into modifications fields
        $(document).on('click', '.edit-imm-btn', function() {
            $('#immAction').val('update');
            $('#immId').val($(this).data('id'));
            $('#logDate').val($(this).data('date'));
            
            //Select values target element IDs to map names/batch text selections correctly
            $('#vaccineType').val($(this).data('vtype')).trigger('change');
            $('#vaccineBatchId').val($(this).data('batch-number')).trigger('change');
            
            $('#qtyStarter').val($(this).data('starter'));
            $('#qtyReceived').val($(this).data('received'));
            $('#qtyUsed').val($(this).data('used'));
            $('#qtyDamaged').val($(this).data('damaged'));

            $('.calc-trigger').first().trigger('input');

            $('#immModalTitle').html('<i class="bi bi-pencil-square me-2 text-warning"></i>Modify Historical Ledger Entry Row');
            $('#immSubmitBtn').removeClass('btn-success').addClass('btn-warning').text('Save Changes');
        });

        $('#addimmunizationModal').on('hidden.bs.modal', function() {
            $('#immAction').val('create');
            $('#immId').val('');
            $('#immunizationForm')[0].reset();
            $('#liveBalanceDisplay').text('0 Doses').removeClass('text-danger text-success').addClass('text-dark');

            $('#immModalTitle').html('<i class="bi bi-plus-circle-fill me-2"></i>Log Immunization Stock Entry');
            $('#immSubmitBtn').removeClass('btn-warning').addClass('btn-success').text('Commit Ledger Entry').prop('disabled', false);
        });
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>