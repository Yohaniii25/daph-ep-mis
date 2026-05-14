<?php
require_once '../../../includes/header.php';

require_once '../../../config/db_connect.php';

if ($_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

require_once '../../../includes/sidebar.php';


$query = "SELECT id, flock_code, region, assigned_cages, current_count, 
          COALESCE(
              (SELECT SUM(egg_count) FROM daily_egg_production 
               WHERE flock_id = parent_stock_flocks.id AND collection_date = CURDATE()),
              (SELECT SUM(egg_count) FROM daily_egg_production 
               WHERE flock_id = parent_stock_flocks.id AND collection_date = (
                   SELECT MAX(collection_date) FROM daily_egg_production 
                   WHERE flock_id = parent_stock_flocks.id
               ))
          ) AS today_eggs,
          (SELECT MAX(collection_date) FROM daily_egg_production 
           WHERE flock_id = parent_stock_flocks.id) AS egg_collection_date
          FROM parent_stock_flocks";

$result = $mysqli->query($query);
$flocks = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $flocks[] = [
            'id'               => $row['id'],
            'code'             => $row['flock_code'],
            'region'           => $row['region'],
            'cages'            => $row['assigned_cages'] ?? 'N/A',
            'current'          => $row['current_count'], // This is your live balance
            'eggs'             => $row['today_eggs'] ?? 0,
            'collection_date'  => $row['egg_collection_date']
        ];
    }
}
?>


<!-- DataTables CSS & Buttons -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">

        <h2 class="fw-bold mb-4">Parent Stock Operations</h2>

        <!-- Status Messages -->
        <?php if (isset($_GET['status'])): ?>
            <div class="alert alert-<?= ($_GET['status'] === 'success') ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                <strong><?= ($_GET['status'] === 'success') ? 'Success!' : 'Error!' ?></strong>
                <?= htmlspecialchars($_GET['msg'] ?? '') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100 py-4" data-bs-toggle="modal" data-bs-target="#stockModal">
                            <i class="bi bi-file-earmark-plus"></i><br>
                            Update Stock Balance
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-4" data-bs-toggle="modal" data-bs-target="#eggModal">
                            <i class="bi bi-droplet"></i><br>
                            Add Daily Egg Collection
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-info w-100 py-4" data-bs-toggle="modal" data-bs-target="#cageModal">
                            <i class="bi bi-graph-up"></i><br>
                            Manage Cages
                        </button>
                    </div>


                </div>
            </div>
        </div>

        <!-- SECTION: DATA TABLE -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-primary">Flock Performance Report</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">Egg counts show today's collection; if today is empty, the latest available collection is displayed.</p>
                <table id="parentStockTable" class="table table-striped align-middle" style="width:100%">
                    <thead>
                        <tr>
                            <th>Flock Code</th>
                            <th>Region</th>
                            <th>Cages</th>
                            <th>Current Balance</th>
                            <th>Daily Eggs</th>
                            <th>Egg Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($flocks as $f):
                           
                        ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($f['code']) ?></td>
                                <td><?= htmlspecialchars($f['region']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($f['cages']) ?></span></td>
                                <td class="fw-bold text-primary"><?= number_format($f['current']) ?></td>
                                <td class="text-success fw-bold"><?= number_format($f['eggs']) ?></td>
                                <td>
                                    <?= $f['collection_date'] ? date('d-M-Y', strtotime($f['collection_date'])) : '-' ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php include './models/add_daily_egg_collection.php'; ?>
<?php include './models/update_stock_balance.php'; ?>
<?php include './models/manage_cages.php'; ?>

<!-- REQUIRED SCRIPTS -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- Buttons for Export -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
    $(document).ready(function() {
        $('#parentStockTable').DataTable({
            dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            buttons: [{
                    extend: 'csv',
                    className: 'btn btn-sm btn-success'
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-sm btn-warning'
                },
                {
                    extend: 'print',
                    className: 'btn btn-sm btn-danger'
                }
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search records..."
            }
        });
    });
</script>

<style>
    .hover-shadow:hover {
        transform: translateY(-5px);
        box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15) !important;
    }

    .transition {
        transition: all 0.3s ease;
    }

    /* DataTables Button Styling */
    .dt-buttons .btn {
        margin-right: 5px;
        border-radius: 5px;
    }
</style>