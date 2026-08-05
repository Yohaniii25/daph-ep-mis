<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

require_once '../../../config/db_connect.php';
$range_id = $_SESSION['range_id'] ?? null;
$current_year = date('Y');

// Fetch Semen Logs
$query = "SELECT *, 
          (opening_balance + received_qty - used_qty - issued_qty - spoiled_qty) as closing_balance 
          FROM semen_logs 
          WHERE range_id = ? AND report_year = ?
          ORDER BY report_month DESC, species ASC";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("ii", $range_id, $current_year);
$stmt->execute();
$semen_report = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">


        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>

                <h2 class="h4 mb-0 fw-bold text-uppercase">Semen Inventory & Logs - <?= $current_year ?></h2>
                <small class="text-muted">Tracking monthly stock movements for Range: <?= $_SESSION['range_name'] ?? 'DAPH' ?></small>
            </div>
        </div>
        
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#addSemenModal">
                            <i class="bi bi-plus-circle fs-3"></i><br>
                            Semen Logs
                        </button>
                    </div>


                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="semenTable" class="table table-hover align-middle border">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Month</th>
                                <th>Species</th>
                                <th class="text-center">Opening</th>
                                <th class="text-center text-success">Received</th>
                                <th class="text-center text-danger">Used</th>
                                <th class="text-center text-danger">Issued</th>
                                <th class="text-center text-warning">Spoiled</th>
                                <th class="text-center fw-bold bg-light">Closing Balance</th>
                                <th class="text-end">Paid (Rs.)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($semen_report as $row): 
                                $monthName = date("F", mktime(0, 0, 0, $row['report_month'], 10));
                            ?>
                                <tr>
                                    <td data-order="<?= $row['report_month'] ?>"><?= $monthName ?></td>
                                    <td><span class="badge bg-danger opacity-75"><?= $row['species'] ?></span></td>
                                    <td class="text-center"><?= number_format($row['opening_balance']) ?></td>
                                    <td class="text-center text-success fw-bold">+ <?= number_format($row['received_qty']) ?></td>
                                    <td class="text-center text-danger">- <?= number_format($row['used_qty']) ?></td>
                                    <td class="text-center text-danger">- <?= number_format($row['issued_qty']) ?></td>
                                    <td class="text-center text-warning">- <?= number_format($row['spoiled_qty']) ?></td>
                                    <td class="text-center bg-light fw-bold text-primary">
                                        <?= number_format($row['closing_balance']) ?>
                                    </td>
                                    <td class="text-end fw-bold">
                                        <?= number_format($row['paid_amount'], 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include 'models/add_semen_log_modal.php'; ?>

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
        $('#semenTable').DataTable({
            "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rtip',
            "buttons": [
                {
                    extend: 'csv',
                    className: 'btn btn-sm btn-success',
                    text: '<i class="bi bi-file-spreadsheet"></i> CSV'
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-sm btn-danger',
                    text: '<i class="bi bi-file-pdf"></i> PDF',
                    orientation: 'landscape',
                    title: 'Semen Inventory Report - <?= $current_year ?>'
                },
                {
                    extend: 'print',
                    className: 'btn btn-sm btn-dark',
                    text: '<i class="bi bi-printer"></i> Print'
                }
            ],
            "order": [[0, "desc"]], 
            "pageLength": 12,
            "language": {
                "searchPlaceholder": "Filter by month or species...",
                "search": ""
            }
        });
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>