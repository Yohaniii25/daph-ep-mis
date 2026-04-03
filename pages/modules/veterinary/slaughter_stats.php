<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

require_once '../../../config/db_connect.php';

$range_id = $_SESSION['range_id'] ?? null;
$current_year = date('Y');

if (empty($range_id)) {
    die('<div class="alert alert-danger text-center p-5 m-5">Error: Range ID not found.</div>');
}

$query = "
    SELECT 
        species,
        location_type,
        SUM(animal_count) as total_animals,
        SUM(total_weight_kg) as total_weight
    FROM slaughter_statistics 
    WHERE range_id = ? AND report_year = ?
    GROUP BY species, location_type
    ORDER BY species ASC
";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("ii", $range_id, $current_year);
$stmt->execute();
$slaughter_report = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Summary Logic for Top Cards Total by Species regardless of location
$species_summary = [];
foreach ($slaughter_report as $row) {
    $s = $row['species'];
    if (!isset($species_summary[$s])) {
        $species_summary[$s] = ['count' => 0, 'weight' => 0];
    }
    $species_summary[$s]['count'] += $row['total_animals'];
    $species_summary[$s]['weight'] += $row['total_weight'];
}

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 mb-0 fw-bold">SLAUGHTER STATISTICS - <?= $current_year ?></h2>
                <small class="text-muted text-uppercase">Range Monitoring Dashboard</small>
            </div>

        </div>

        <!-- Quick Actions -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#addSlaughterModal">
                            <i class="bi bi-plus-circle fs-3"></i><br>
                            Slaughter Record
                        </button>
                    </div>


                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <?php foreach ($species_summary as $name => $data): ?>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 border-start border-4 border-primary">
                        <div class="card-body">
                            <small class="text-muted fw-bold"><?= strtoupper($name) ?></small>
                            <div class="d-flex justify-content-between align-items-end mt-2">
                                <h3 class="mb-0 fw-bold"><?= number_format($data['count']) ?></h3>
                                <small class="text-primary fw-bold"><?= number_format($data['weight']) ?> kg</small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="slaughterTable" class="table table-hover align-middle">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Livestock Species</th>
                                <th>Location Type</th>
                                <th class="text-center">Animal Count</th>
                                <th class="text-end">Total Weight (kg)</th>
                                <th class="text-center">Avg. Weight/Animal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($slaughter_report as $row):
                                $avg = ($row['total_animals'] > 0) ? ($row['total_weight'] / $row['total_animals']) : 0;
                            ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold text-dark">
                                            <i class="bi bi-check2-circle me-2 text-success"></i><?= $row['species'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill bg-light text-dark border">
                                            <?= $row['location_type'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center fw-bold"><?= number_format($row['total_animals']) ?></td>
                                    <td class="text-end text-primary fw-bold"><?= number_format($row['total_weight'], 2) ?></td>
                                    <td class="text-center text-muted"><?= number_format($avg, 2) ?> kg</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php include 'models/add_slaughter_record_modal.php'; ?>
    </main>
</div>

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
        $('#slaughterTable').DataTable({
            "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rtip',
            "buttons": [{
                    extend: 'csv',
                    className: 'btn btn-sm btn-success',
                    text: '<i class="bi bi-file-spreadsheet"></i> CSV'
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-sm btn-danger',
                    text: '<i class="bi bi-file-pdf"></i> PDF',
                    title: 'Slaughter Statistics - <?= $current_year ?>'
                },
                {
                    extend: 'print',
                    className: 'btn btn-sm btn-dark',
                    text: '<i class="bi bi-printer"></i> Print'
                }
            ],
            "pageLength": 10
        });
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>`