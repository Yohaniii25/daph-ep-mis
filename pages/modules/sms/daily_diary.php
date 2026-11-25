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

$stats = ['total' => 0, 'upcoming' => 0, 'past' => 0, 'total_distance' => 0];
$stats_stmt = $mysqli->prepare("
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN task_date >= CURDATE() THEN 1 ELSE 0 END) AS upcoming,
        SUM(CASE WHEN task_date < CURDATE() THEN 1 ELSE 0 END) AS past,
        COALESCE(SUM(CAST(distance AS DECIMAL(10,2))), 0) AS total_distance
    FROM diary_tasks 
    WHERE user_id = ?
");
if ($stats_stmt) {
    $stats_stmt->bind_param("i", $current_user);
    $stats_stmt->execute();
    $stats = $stats_stmt->get_result()->fetch_assoc() ?: $stats;
    $stats_stmt->close();
}

$total          = intval($stats['total'] ?? 0);
$upcoming       = intval($stats['upcoming'] ?? 0);
$past           = intval($stats['past'] ?? 0);
$total_distance = floatval($stats['total_distance'] ?? 0);

$tasks_rows = [];
$t_stmt = $mysqli->prepare("SELECT id, task_date, place, distance, activity, time_duration FROM diary_tasks WHERE user_id = ? ORDER BY task_date DESC, id DESC");
if ($t_stmt) {
    $t_stmt->bind_param("i", $current_user);
    $t_stmt->execute();
    $tasks_rows = $t_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $t_stmt->close();
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
                <h3 class="fw-bold mb-1">Daily Diary / Tasks</h3>
                <p class="text-muted small mb-0">Manage your daily tasks and activities</p>
            </div>
            <button class="btn btn-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                <i class="bi bi-plus-circle me-2"></i>Add New Task
            </button>
        </div>

        <!-- TOP STATISTICS -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body text-center">
                        <h5 class="text-muted small">TOTAL TASKS</h5>
                        <h2 class="fw-bold text-primary mb-0"><?php echo $total; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body text-center">
                        <h5 class="text-muted small">UPCOMING / TODAY</h5>
                        <h2 class="fw-bold text-warning mb-0"><?php echo $upcoming; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body text-center">
                        <h5 class="text-muted small">LOGGED (PAST)</h5>
                        <h2 class="fw-bold text-info mb-0"><?php echo $past; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body text-center">
                        <h5 class="text-muted small">TOTAL DISTANCE</h5>
                        <h2 class="fw-bold text-success mb-0"><?php echo number_format($total_distance, 1); ?> km</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- DATA TABLE -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-list-task me-2 text-primary"></i>My Tasks</h6>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="tasksTable">
                        <thead class="bg-light small text-uppercase">
                            <tr>
                                <th>Date</th>
                                <th>Place</th>
                                <th>Activity</th>
                                <th>Distance (km)</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks_rows as $task): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($task['task_date']); ?></td>
                                    <td><?php echo htmlspecialchars($task['place'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($task['activity'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($task['distance'] ?? '0'); ?></td>
                                    <td><?php echo htmlspecialchars($task['time_duration'] ?? '—'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Add Task Modal -->
<?php include 'models/add_daily_diary.php'; ?>

<!-- DataTables Scripts -->
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
    $('#tasksTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 15,
        "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
        "buttons": [
            {
                extend: 'csvHtml5',
                text: '<i class="bi bi-file-earmark-spreadsheet"></i> CSV',
                className: 'btn btn-sm btn-success me-2'
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="bi bi-file-pdf"></i> PDF',
                className: 'btn btn-sm btn-danger me-2'
            },
            {
                extend: 'print',
                text: '<i class="bi bi-printer"></i> Print',
                className: 'btn btn-sm btn-outline-dark'
            }
        ]
    });
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
