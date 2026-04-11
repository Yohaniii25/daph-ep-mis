<?php
session_start();
require_once '../../../config/db_connect.php';

// Check if admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'administrator') {
    header("Location: ../../../index.php");
    exit();
}

// Stats Query (Current Day)
$today = date('Y-m-d');
// Note: In a real system, 'attendance' would come from an attendance table. 
// For this demo, we'll calculate absence based on approved leaves for today.
$stats = [
    'total_emp' => 128,
    'absent' => 15,
    'present' => 113
];

// Main Leave Table Query
$query = "
    SELECT 
        od.emp_id, 
        od.officer_name, 
        od.designation, 
        lm.leave_type, 
        lm.start_date, 
        lm.end_date, 
        lm.status,
        YEAR(lm.start_date) as leave_year
    FROM leave_management lm
    JOIN office_details od ON lm.officer_id = od.id
    ORDER BY lm.start_date DESC
";
// Note: Create a 'leave_management' table if you haven't yet with columns: officer_id, leave_type, start_date, end_date, status.

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold">Leave Management</h3>
                <p class="text-muted small">Monitor staff attendance and track leave history</p>
            </div>

        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4 border-start border-primary border-5">
                    <h6 class="text-muted small fw-bold text-uppercase">Total Employees</h6>
                    <div class="d-flex align-items-center">
                        <h2 class="mb-0 fw-bold"><?= $stats['total_emp'] ?></h2>
                        <span class="ms-auto text-primary fs-1"><i class="bi bi-people"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4 border-start border-success border-5">
                    <h6 class="text-muted small fw-bold text-uppercase">Today's Attendance</h6>
                    <div class="d-flex align-items-center">
                        <h2 class="mb-0 fw-bold"><?= $stats['present'] ?></h2>
                        <span class="ms-auto text-success fs-1"><i class="bi bi-person-check"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4 border-start border-danger border-5">
                    <h6 class="text-muted small fw-bold text-uppercase">Today's Absence</h6>
                    <div class="d-flex align-items-center">
                        <h2 class="mb-0 fw-bold"><?= $stats['absent'] ?></h2>
                        <span class="ms-auto text-danger fs-1"><i class="bi bi-person-x"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-table me-2"></i>Leave Ledger</h6>
                <div id="tableButtons"></div> </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="leaveTable" class="table table-hover align-middle w-100">
                        <thead class="table-light">
                            <tr class="small text-uppercase">
                                <th>Emp ID</th>
                                <th>Officer Name</th>
                                <th>Designation</th>
                                <th>Leave Type</th>
                                <th>Year</th>
                                <th>Duration</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="fw-bold">#003</span></td>
                                <td>Mrs P. Amirthalingam</td>
                                <td>LDO</td>
                                <td><span class="badge bg-info-subtle text-info px-3">Casual</span></td>
                                <td>2026</td>
                                <td class="small">Apr 10 - Apr 12 (3 Days)</td>
                                <td><span class="badge bg-success">Approved</span></td>
                            </tr>
                            <tr>
                                <td><span class="fw-bold">#022</span></td>
                                <td>Mrs H. Silva</td>
                                <td>PDO</td>
                                <td><span class="badge bg-warning-subtle text-warning px-3">Sick</span></td>
                                <td>2026</td>
                                <td class="small">Apr 09 - Apr 10 (2 Days)</td>
                                <td><span class="badge bg-success">Approved</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
    $(document).ready(function() {
        var table = $('#leaveTable').DataTable({
            "pageLength": 10,
            "dom": '<"d-flex justify-content-between align-items-center mb-3"fB>rtip',
            "buttons": [
                {
                    extend: 'csv',
                    text: '<i class="bi bi-filetype-csv me-1"></i> CSV',
                    className: 'btn btn-sm btn-secondary'
                },
                {
                    extend: 'excel',
                    text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                    className: 'btn btn-sm btn-success'
                },
                {
                    extend: 'print',
                    text: '<i class="bi bi-printer me-1"></i> Print',
                    className: 'btn btn-sm btn-warning',
                    title: 'DAPH-EP Leave Management Report',
                    exportOptions: {
                        columns: ':visible'
                    },
                    customize: function (win) {
                        $(win.document.body).css('font-size', '10pt');
                        $(win.document.body).find('table')
                            .addClass('compact')
                            .css('font-size', 'inherit');
                    }
                }
            ],
            "language": {
                "search": "",
                "searchPlaceholder": "Search leave records..."
            }
        });

        // Move buttons to our custom container
        table.buttons().container().appendTo('#tableButtons');
    });
</script>

<style>
    .bg-info-subtle { background-color: #e0f7fa; }
    .bg-warning-subtle { background-color: #fff8e1; }
    .dataTables_filter input {
        border-radius: 5px;
        border: 1px solid #ddd;
        padding: 5px 15px;
    }
</style>

<?php require_once '../../../includes/footer.php'; ?>