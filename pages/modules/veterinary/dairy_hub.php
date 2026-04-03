<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

require_once '../../../config/db_connect.php';
$range_id = $_SESSION['range_id'] ?? null;
$current_month = date('m');
$current_year = date('Y');

// Fetch Records for the current month
$query = "SELECT * FROM dairy_hub_records 
          WHERE range_id = ? AND YEAR(collection_date) = ?
          ORDER BY collection_date DESC";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("ii", $range_id, $current_year);
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">


<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 mb-0 fw-bold text-uppercase">Dairy Hub Activity Records</h2>
                <small class="text-muted">Milk Collection & Quality Monitoring</small>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#addDairyModal">
                            <i class="bi bi-plus-circle fs-3"></i><br>
                            Record Collection
                        </button>
                    </div>


                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4">
                <?= $_SESSION['success'];
                unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <table id="dairyTable" class="table table-hover align-middle">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th>Date</th>
                            <th>Month</th>
                            <th>Farmer Reg No</th>
                            <th class="text-center">Qty (L)</th>
                            <th class="text-center">Fat %</th>
                            <th class="text-center">SNF %</th>
                            <th class="text-end">Rate (Rs)</th>
                            <th class="text-end">Total (Rs)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $row):
                            // 2. FIX: Define $mName inside the loop
                            $mName = date('F', strtotime($row['collection_date']));
                        ?>
                            <tr>
                                <td data-order="<?= strtotime($row['collection_date']) ?>">
                                    <?= date('d-M-Y', strtotime($row['collection_date'])) ?>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= $mName ?></span></td>
                                <td class="fw-bold"><?= $row['farmer_reg_no'] ?></td>
                                <td class="text-center"><?= number_format($row['milk_quantity_liters'], 2) ?></td>
                                <td class="text-center"><?= $row['fat_percentage'] ?>%</td>
                                <td class="text-center"><?= $row['snf_percentage'] ?>%</td>
                                <td class="text-end"><?= number_format($row['price_per_liter'], 2) ?></td>
                                <td class="text-end fw-bold text-primary">
                                    <?= number_format($row['total_amount'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php include 'models/add_dairy_record_modal.php'; ?>

<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

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
        $('#dairyTable').DataTable({
            // 'B' is critical here - it stands for Buttons
            "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rtip',
            "buttons": [{
                    extend: 'csv',
                    className: 'btn btn-sm btn-success shadow-sm',
                    text: '<i class="bi bi-file-spreadsheet"></i> CSV'
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-sm btn-danger shadow-sm',
                    text: '<i class="bi bi-file-pdf"></i> PDF',
                    title: 'Dairy Hub Collection Report'
                },
                {
                    extend: 'print',
                    className: 'btn btn-sm btn-dark shadow-sm',
                    text: '<i class="bi bi-printer"></i> Print'
                }
            ],
            "pageLength": 15,
            "order": [
                [0, "desc"]
            ]
        });
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>