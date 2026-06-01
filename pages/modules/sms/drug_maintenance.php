<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'sms') die("Access denied");
require_once '../../../config/db_connect.php';

$summary_result = $mysqli->query("SELECT 
    COUNT(DISTINCT vaccine_batch_id) AS total_batches,
    SUM(starter_count_month) AS total_starter,
    SUM(used_doses_count) AS total_used,
    SUM(starter_count_month + during_month_received - used_doses_count - doses_damaged) AS total_balance 
    FROM drug_records");
if (!$summary_result) {
    die("Database Error: " . $mysqli->error);
}
$summary = $summary_result->fetch_assoc();
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Drug Maintenance</h2>

        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Active Tracked Batches</h6>
                        <h2 class="text-primary mb-0 fw-bold"><?= number_format($summary['total_batches'] ?? 0) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Doses Allocated (Starter)</h6>
                        <h2 class="text-warning mb-0 fw-bold"><?= number_format($summary['total_starter'] ?? 0) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Total Doses Used</h6>
                        <h2 class="text-success mb-0 fw-bold"><?= number_format($summary['total_used'] ?? 0) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Total Live Balance Available</h6>
                        <h2 class="text-info mb-0 fw-bold"><?= number_format($summary['total_balance'] ?? 0) ?></h2>
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
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#addDrugRecordModal">
                            <i class="bi bi-plus-circle"></i><br>
                            Add New Drug Record
                        </button>
                    </div>
                    <div class="col-md-3">
                        <a href="drug_types.php" class="btn btn-primary w-100 py-3">
                            <i class="bi bi-search"></i><br>
                            Name of the Drugs
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="batches.php" class="btn btn-warning w-100 py-3 text-dark">
                            <i class="bi bi-box-seam"></i><br>
                            Batches
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="m-0 fw-bold text-dark"><i class="bi bi-shield-plus me-2 text-success"></i>Drugs Balance Report</h5>
            </div>
            <div class="card-body">
                <table id="drugTable" class="table table-bordered table-striped align-middle row-border" style="width:100%">
                    <thead class="table-light text-center align-middle">
                        <tr>
                            <th>Log Date</th>
                            <th>Drug Name</th>
                            <th>Batch No</th>
                            <th>Date of Expiry</th>
                            <th>Opening Balance</th>
                            <th>Mid-Month Receipts</th>
                            <th>Quantity Used</th>
                            <th>Wasted / Damaged</th>
                            <th class="bg-light-success fw-bold">End Balance</th>
                            <th style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $ledger_query = "SELECT r.*, 
                                         COALESCE(t.vaccine_name, 'Unknown Type') AS vaccine_name, 
                                         COALESCE(b.batch_number, 'Unknown Batch') AS batch_number, 
                                         b.expiry_date, 
                                         (r.starter_count_month + r.during_month_received - r.used_doses_count - r.doses_damaged) AS balance_end_month 
                                         FROM `drug_records` r
                                         LEFT JOIN `drug_types` t ON r.drug_type_id = t.id
                                         LEFT JOIN `vaccine_batches` b ON r.vaccine_batch_id = b.id
                                         ORDER BY r.id DESC";
                        $res = $mysqli->query($ledger_query);
                        if (!$res) {
                            die("Database Error: " . $mysqli->error);
                        }
                        if ($res->num_rows > 0):
                            while ($row = $res->fetch_assoc()):
                                $formatted_expiry = !empty($row['expiry_date']) ? date('Y-m-d', strtotime($row['expiry_date'])) : 'N/A';
                        ?>
                                <tr>
                                    <td class="text-center font-monospace small"><?= htmlspecialchars($row['log_date']) ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($row['vaccine_name']) ?></td>
                                    <td class="text-center"><span class="badge bg-dark font-monospace"><?= htmlspecialchars($row['batch_number']) ?></span></td>
                                    <td class="text-center small fw-semibold text-danger"><?= $formatted_expiry ?></td>
                                    <td class="text-center font-monospace"><?= number_format($row['starter_count_month']) ?></td>
                                    <td class="text-center font-monospace text-success">+<?= number_format($row['during_month_received']) ?></td>
                                    <td class="text-center font-monospace text-info">-<?= number_format($row['used_doses_count']) ?></td>
                                    <td class="text-center font-monospace text-danger">-<?= number_format($row['doses_damaged']) ?></td>
                                    <td class="text-center font-monospace fw-bold bg-light text-success"><?= number_format($row['balance_end_month']) ?></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary edit-drug-record-btn"
                                                data-id="<?= $row['id'] ?>"
                                                data-date="<?= $row['log_date'] ?>"
                                                data-drug="<?= htmlspecialchars($row['vaccine_name'], ENT_QUOTES) ?>"
                                                data-batch="<?= $row['vaccine_batch_id'] ?>"
                                                data-expiry="<?= $formatted_expiry ?>"
                                                data-starter="<?= $row['starter_count_month'] ?>"
                                                data-received="<?= $row['during_month_received'] ?>"
                                                data-used="<?= $row['used_doses_count'] ?>"
                                                data-damaged="<?= $row['doses_damaged'] ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="processors/drug_record_crud.php?action=delete&id=<?= $row['id'] ?>" 
                                               class="btn btn-outline-danger" 
                                               onclick="return confirm('Are you sure you want to permanently delete this stock record?');">
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

<?php include './models/drug_record_modal.php'; ?>

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
        // Correct initialization targeting your exact UI element ID
        $('#drugTable').DataTable({
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
                    title: 'Drug Stock Inventory Ledger Balances'
                },
                {
                    extend: 'print',
                    text: '<i class="bi bi-printer"></i> Print',
                    className: 'btn btn-sm btn-warning text-dark rounded shadow-sm'
                }
            ]
        });

        // Event listener bridge script targeting your included modal configuration file
        $('.edit-drug-record-btn').on('click', function() {
            $('#drugAction').val('update');
            $('#drugId').val($(this).data('id'));
            
            // Populates your custom field structure definitions
            $('#logDate').val($(this).data('date'));
            $('#vaccineType').val($(this).data('drug'));
            $('#vaccineBatchId').val($(this).data('batch'));
            $('#qtyStarter').val($(this).data('starter'));
            $('#qtyReceived').val($(this).data('received'));
            $('#qtyUsed').val($(this).data('used'));
            $('#qtyDamaged').val($(this).data('damaged'));
            
            // Sync dynamic display targets inside your modal
            $('#drugExpiryDisplay').text($(this).data('expiry'));
            
            // Trigger calculation execution string
            $('.calc-trigger').first().trigger('input');
            
            // Modify labels and show modal safely
            $('#drugRecordModalTitle').html('<i class="bi bi-pencil-square me-2 text-warning"></i>Modify Drug Stock Entry');
            $('#immSubmitBtn').text('Save Changes');
            $('#addDrugRecordModal').modal('show');
        });

        // Reset elements upon close interaction
        $('#addDrugRecordModal').on('hidden.bs.modal', function() {
            $('#drugRecordForm')[0].reset();
            $('#drugAction').val('create');
            $('#drugId').val('');
            $('#drugExpiryDisplay').text('None selected');
            $('#drugLiveBalanceDisplay').text('0 Doses').removeClass('text-danger text-success').addClass('text-dark');
            $('#drugRecordModalTitle').html('<i class="bi bi-capsule-compartment me-2"></i>Drug Stock Ledger Entry');
            $('#immSubmitBtn').prop('disabled', false).text('Commit Ledger Entry');
        });
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>