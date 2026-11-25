<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sms') {
    header("Location: ../../../index.php");
    exit();
}

$current_user = intval($_SESSION['user_id'] ?? 0);

$stats = ['total' => 0, 'upcoming' => 0, 'total_distance' => 0];
$stats_stmt = $mysqli->prepare("
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN date >= CURDATE() THEN 1 ELSE 0 END) AS upcoming,
        COALESCE(SUM(distance), 0) AS total_distance
    FROM advanced_programmes 
    WHERE user_id = ?
");
if ($stats_stmt) {
    $stats_stmt->bind_param("i", $current_user);
    $stats_stmt->execute();
    $stats = $stats_stmt->get_result()->fetch_assoc() ?: $stats;
    $stats_stmt->close();
}

$prog_rows = [];
$prog_stmt = $mysqli->prepare("SELECT id, date, task, place, distance, time_duration FROM advanced_programmes WHERE user_id = ? ORDER BY date DESC, id DESC");
if ($prog_stmt) {
    $prog_stmt->bind_param("i", $current_user);
    $prog_stmt->execute();
    $prog_rows = $prog_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $prog_stmt->close();
}

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold">Advanced Programme Management</h3>
                <p class="text-muted small">Field activity planning, location schedules, and travel distance tracking.</p>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-lightning-charge me-2 text-warning"></i>Administrative Shortcuts</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <button style="background-color: #370709; color: white;" class="btn w-100 py-3 d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#manageTypesModal">
                            <div class="text-center">
                                <i class="bi bi-gear-wide-connected fs-3"></i><br>
                                <span class="fw-bold text-uppercase small">Manage Programme Types</span>
                                <p class="mb-0 x-small" style="font-size: 0.75rem; color: white;">Add/Edit global programme categories</p>
                            </div>
                        </button>
                    </div>

                    <div class="col-md-6">
                        <button style="background-color: #a07174; color: white;" class="btn w-100 py-3 d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#addAdvancedModal">
                            <div class="text-center">
                                <i class="bi bi-calendar-plus fs-3"></i><br>
                                <span class="fw-bold text-uppercase small">New Advanced Programme</span>
                                <p class="mb-0 x-small" style="font-size: 0.75rem; color: white;">Create a new planned activity record</p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-primary text-white">
                    <div class="card-body">
                        <h6 style="color: white;" class="small text-uppercase opacity-75">Total Programmes</h6>
                        <h3 style="color: white;" class="fw-bold mb-0"><?php echo intval($stats['total'] ?? 0); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div style="background-color: #b08723;" class="card border-0 shadow-sm text-white">
                    <div class="card-body">
                        <h6 style="color: white;" class="small text-uppercase opacity-75">Upcoming Activities</h6>
                        <h3 style="color: white;" class="fw-bold mb-0"><?php echo intval($stats['upcoming'] ?? 0); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-success text-white">
                    <div class="card-body">
                        <h6 style="color: white;" class="small text-uppercase opacity-75">Total Planned Distance</h6>
                        <h3 style="color: white;" class="fw-bold mb-0"><?php echo number_format(floatval($stats['total_distance'] ?? 0), 1); ?> km</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-3">
                <table class="table table-hover align-middle" id="advancedProgTable">
                    <thead class="bg-light">
                        <tr>
                            <th>Planned Date</th>
                            <th>Task / Activity</th>
                            <th>Location / Place</th>
                            <th>Distance (km)</th>
                            <th>Time Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prog_rows as $row): ?>
                            <tr>
                                <td class="small fw-semibold"><?php echo htmlspecialchars($row['date']); ?></td>
                                <td><?php echo htmlspecialchars($row['task'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['place'] ?? ''); ?></td>
                                <td><?php echo number_format(floatval($row['distance'] ?? 0), 2); ?></td>
                                <td><?php echo htmlspecialchars($row['time_duration'] ?? '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once '../../../includes/footer.php'; ?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
    $(document).ready(function() {
        $('#advancedProgTable').DataTable({
            "order": [[0, "desc"]],
            "responsive": true,
            "pageLength": 15,
            "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            "buttons": [
                {
                    extend: 'csv',
                    className: 'btn btn-sm btn-success me-2',
                    text: '<i class="bi bi-file-earmark-spreadsheet"></i> CSV'
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-sm btn-danger me-2',
                    text: '<i class="bi bi-file-earmark-pdf"></i> PDF'
                },
                {
                    extend: 'print',
                    className: 'btn btn-sm btn-dark',
                    text: '<i class="bi bi-printer"></i> Print'
                }
            ],
            "language": {
                "search": "Search programmes:",
                "lengthMenu": "Show _MENU_ records"
            }
        });
    });
</script>

<?php
include 'models/add_advanced_programme_modal.php';
include 'models/manage_programme_types_modal.php';
?>
