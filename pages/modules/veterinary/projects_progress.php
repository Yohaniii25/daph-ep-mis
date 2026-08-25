<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

$range_id = $_SESSION['range_id'] ?? null;
if (empty($range_id)) {
    die('<div class="alert alert-danger text-center p-5 m-5">Error: Account not assigned to a Range.</div>');
}

require_once '../../../config/db_connect.php';

$range_name = 'Unknown Range';
$stmt = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
$stmt->bind_param("i", $range_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if ($res) $range_name = $res['name'];
$stmt->close();

$counts = [
    'PSDG' => 0,
    'LMP' => 0,
    'CBG' => 0,
    'Special' => 0,
    'Other' => 0
];

$count_query = "SELECT project_type, COUNT(*) as total 
                FROM projects_progress 
                WHERE range_id = ? 
                GROUP BY project_type";

$c_stmt = $mysqli->prepare($count_query);
$c_stmt->bind_param("i", $range_id);
$c_stmt->execute();
$count_result = $c_stmt->get_result();

while ($row = $count_result->fetch_assoc()) {
    $counts[$row['project_type']] = $row['total'];
}
$c_stmt->close();

$psdg_count    = $counts['PSDG'];
$lmp_count     = $counts['LMP'];
$cbg_count     = $counts['CBG'];
$special_count = $counts['Special'];
$other_count   = $counts['Other'];

$list_stmt = $mysqli->prepare("SELECT * FROM projects_progress WHERE range_id = ? ORDER BY priority DESC, start_date DESC");
$list_stmt->bind_param("i", $range_id);
$list_stmt->execute();
$projects = $list_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">



        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 mb-0 fw-bold text-uppercase">Project Progress & Operations</h2>
                <small class="text-muted"><?= htmlspecialchars($range_name) ?> | Monitoring & Evaluation</small>
            </div>

        </div>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#addProjectModal">
                            <i class="bi bi-plus-circle fs-3"></i><br>
                            Add Project
                        </button>
                    </div>


                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-3 border-start border-primary border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">PSDG Projects</h6>
                    <h3 class="mb-0"><?= $psdg_count ?> </h3>
                </div>
            </div>

            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-3 border-start border-success border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">LMP Projects</h6>
                    <h3 class="mb-0"><?= $lmp_count ?></h3>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-3 border-start border-warning border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">CBG Projects</h6>
                    <h3 class="mb-0"><?= $cbg_count ?> </h3>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-3 border-start border-danger border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">Special Projects</h6>
                    <h3 class="mb-0"><?= $special_count ?> </h3>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-3 border-start border-secondary border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">Other Projects</h6>
                    <h3 class="mb-0"><?= $other_count ?></h3>
                </div>
            </div>

        </div>


        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list-task me-2"></i>Project Progress Records</h5>
                <div id="exportButtons"></div>
            </div>

            <div class="card-body">
                <div class="row g-3 mb-4 bg-light p-3 rounded border">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">Filter by Type</label>
                        <select id="filterType" class="form-select form-select-sm">
                            <option value="">All Types</option>
                            <option value="PSDG">PSDG</option>
                            <option value="LMP">LMP</option>
                            <option value="CBG">CBG</option>
                            <option value="Special">Special Project</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">From Date</label>
                        <input type="date" id="minDate" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">To Date</label>
                        <input type="date" id="maxDate" class="form-control form-control-sm">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="projectsTable">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Project Name & Type</th>
                                <th>Location</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Priority</th>
                                <th style="width: 150px;">Progress</th>
                                <th>Status</th>
                                <th class="text-end no-export">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $proj): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-primary"><?= htmlspecialchars($proj['project_name']) ?></div>
                                        <span class="type-label small text-muted"><?= $proj['project_type'] ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($proj['location']) ?></td>
                                    <td class="date-col"><?= $proj['start_date'] ?></td>
                                    <td><?= $proj['end_date'] ?></td>
                                    <td>
                                        <span class="fw-bold <?= ($proj['priority'] == 'Urgent' || $proj['priority'] == 'High') ? 'text-danger' : '' ?>">
                                            <?= $proj['priority'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                <div class="progress-bar bg-success" style="width: <?= $proj['progress_percent'] ?>%"></div>
                                            </div>
                                            <small><?= $proj['progress_percent'] ?>%</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill bg-<?= $proj['status'] == 'Completed' ? 'success' : 'info' ?>">
                                            <?= $proj['status'] ?>
                                        </span>
                                    </td>
                                    <td class="text-end no-export">
                                        <button class="btn btn-sm btn-light border" title="Edit"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-sm btn-primary border" title="View"><i class="bi bi-eye"></i></button>
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

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<?php include 'models/add_project_modal.php'; ?>

<script>
    $(document).ready(function() {
  
        var table = $('#projectsTable').DataTable({
            dom: 'rtip', // Hide default search, we use custom filters
            pageLength: 15,
            buttons: [{
                    extend: 'print',
                    className: 'btn btn-sm btn-success shadow-sm',
                    exportOptions: {
                        columns: ':not(.no-export)'
                    }
                },
                {
                    extend: 'csv',
                    className: 'btn btn-sm btn-danger shadow-sm',
                    exportOptions: {
                        columns: ':not(.no-export)'
                    }
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-sm btn-warning shadow-sm',
                    exportOptions: {
                        columns: ':not(.no-export)'
                    }
                }
            ]
        });

        // Move Export Buttons to the Card Header
        table.buttons().container().appendTo('#exportButtons');

        // Custom Type Filter Logic
        $('#filterType').on('change', function() {
            table.column(0).search(this.value).draw();
        });

        // Custom Date Range Filter Logic
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                var min = $('#minDate').val();
                var max = $('#maxDate').val();
                var startDate = data[2]; 

                if (min === "" && max === "") return true;
                if (min === "" && startDate <= max) return true;
                if (max === "" && startDate >= min) return true;
                if (startDate >= min && startDate <= max) return true;
                return false;
            }
        );

        $('#minDate, #maxDate').on('change', function() {
            table.draw();
        });
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>