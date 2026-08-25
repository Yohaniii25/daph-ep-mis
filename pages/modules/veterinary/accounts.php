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
    $stmt->bind_param("i", $district_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $district_name = $row['name'];
    }
    $stmt->close();
}

if ($range_id) {
    $stmt = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
    $stmt->bind_param("i", $range_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $range_name = $row['name'];
    }
    $stmt->close();
}


require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">



        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 mb-0 fw-bold">ACCOUNTS</h2>
                <small class="text-muted text-uppercase"> Monitoring Dashboard</small>
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
                        <a href="h_records.php" class="btn btn-success w-100 py-3">
                            <i class="bi bi-search fs-3"></i><br>
                             H records
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a style="background-color: #820100; color: white;" href="crop_records.php" class="btn w-100 py-3">
                            <i class="bi bi-search fs-3"></i><br>
                             Crop records
                        </a>
                    </div>


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