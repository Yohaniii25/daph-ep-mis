<?php
session_start();
require_once '../../../config/db_connect.php';


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sms') {
    header("Location: ../../../index.php");
    exit();
}

$current_user = $_SESSION['user_id'];


$stats = $mysqli->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status IN ('Not Started', 'Ongoing') THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN task_date < CURDATE() AND status != 'Completed' THEN 1 ELSE 0 END) as overdue
    FROM diary_tasks 
    WHERE user_id = '$current_user'
")->fetch_assoc();

$total      = $stats['total'] ?? 0;
$pending    = $stats['pending'] ?? 0;
$completed  = $stats['completed'] ?? 0;
$overdue    = $stats['overdue'] ?? 0;
$rate       = $total > 0 ? round(($completed / $total) * 100, 1) : 0;


$query = "SELECT * FROM diary_tasks WHERE user_id = '$current_user' ORDER BY task_date DESC";
$result = $mysqli->query($query);

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold">Daily Diary / Tasks</h3>
            <p class="text-muted small">Manage your daily tasks and activities</p>
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
                        <h5 class="text-muted small">PENDING</h5>
                        <h2 class="fw-bold text-warning mb-0"><?php echo $pending; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body text-center">
                        <h5 class="text-muted small">OVERDUE</h5>
                        <h2 class="fw-bold text-danger mb-0"><?php echo $overdue; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body text-center">
                        <h5 class="text-muted small">COMPLETION RATE</h5>
                        <h2 class="fw-bold text-success mb-0"><?php echo $rate; ?>%</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- DATA TABLE -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-list-task me-2 text-primary"></i>My Tasks</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="tasksTable">
                        <thead class="bg-light small text-uppercase">
                            <tr>
                                <th>Date</th>
                                <th>Place</th>
                                <th>Activity</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($task = $result->fetch_assoc()): 
                                $badge = ($task['status'] == 'Completed') ? 'success' : 
                                        (($task['status'] == 'Ongoing') ? 'info' : 'secondary');
                            ?>
                                <tr>
                                    <td><?php echo $task['task_date']; ?></td>
                                    <td><?php echo htmlspecialchars($task['place']); ?></td>
                                    <td><?php echo htmlspecialchars($task['activity']); ?></td>
                                    <td><span class="badge bg-light text-dark"><?php echo $task['task_type']; ?></span></td>
                                    <td><span class="badge bg-<?php echo $badge; ?>"><?php echo $task['status']; ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
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
        ],
        "columnDefs": [
            { "orderable": false, "targets": [3, 4] }
        ]
    });
});
</script>

<?php require_once '../../../includes/footer.php'; ?>