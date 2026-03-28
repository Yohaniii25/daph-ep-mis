<?php
session_start();

// 1. SESSION & SECURITY CHECK
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

$range_id    = $_SESSION['range_id'] ?? null;
$district_id = $_SESSION['district_id'] ?? null;
$current_year = date('Y');

if (empty($range_id)) {
    die('<div class="alert alert-danger text-center p-5 m-5">Error: Account not assigned to a Veterinary Range.</div>');
}

require_once '../../../config/db_connect.php';

// 2. FETCH OFFICE & RANGE NAMES
$range_name = 'Unknown Range';
if ($range_id) {
    $stmt = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
    $stmt->bind_param("i", $range_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) { $range_name = $row['name']; }
    $stmt->close();
}

// 3. FETCH MONTHLY DATA (OFFICER-WISE)
// Logic: Joins Breeding Progress with Targets and Officer Details
$stmt = $mysqli->prepare("
    SELECT 
        od.officer_name, 
        od.designation,
        bp.month_number,
        CEIL(bp.month_number / 3) AS quarter,
        (tt.target_ai / 12) as m_target_ai,
        (tt.target_pd / 12) as m_target_pd,
        (tt.target_calving / 12) as m_target_calving,
        bp.ai_count, 
        bp.pd_count, 
        bp.calving_count
    FROM office_details od
    INNER JOIN breeding_progress bp ON od.id = bp.officer_id
    LEFT JOIN breeding_target_templates tt 
        ON od.designation = tt.designation 
        AND od.range_id = tt.range_id 
        AND tt.year = bp.year
    WHERE od.range_id = ? AND bp.year = ? AND od.status = 'Active'
    ORDER BY quarter DESC, bp.month_number DESC, od.officer_name ASC
");
$stmt->bind_param("ii", $range_id, $current_year);
$stmt->execute();
$report_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 mb-0 fw-bold">Breeding Performance Report - <?= $current_year ?></h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item text-primary"><?= htmlspecialchars($range_name) ?></li>
                        <li class="breadcrumb-item active">Quarterly & Monthly Breakdown</li>
                    </ol>
                </nav>
            </div>
            <div class="btn-group shadow-sm">
                <button type="button" class="btn btn-sm btn-outline-primary toggle-vis" data-column="3,4">AI View</button>
                <button type="button" class="btn btn-sm btn-outline-info toggle-vis" data-column="5,6">PD View</button>
                <button type="button" class="btn btn-sm btn-outline-success toggle-vis" data-column="7,8">Calving View</button>
                <button type="button" class="btn btn-sm btn-secondary toggle-vis" data-column="all">Reset</button>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="masterBreedingTable" class="table table-hover align-middle w-100">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th style="width: 120px;">Period</th>
                                <th>Officer Details</th>
                                <th>Post</th>
                                <th class="text-primary border-start">AI Target</th>
                                <th class="text-primary">AI Actual</th>
                                <th class="text-info border-start">PD Target</th>
                                <th class="text-info">PD Actual</th>
                                <th class="text-success border-start">Calving Target</th>
                                <th class="text-success">Calving Actual</th>
                                <th class="text-end">Achievement</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): 
                                $monthName = date("F", mktime(0, 0, 0, $row['month_number'], 10));
                                $total_target = $row['m_target_ai'] + $row['m_target_pd'] + $row['m_target_calving'];
                                $total_actual = $row['ai_count'] + $row['pd_count'] + $row['calving_count'];
                                $percent = ($total_target > 0) ? round(($total_actual / $total_target) * 100) : 0;
                            ?>
                            <tr>
                                <td>
                                    <span class="badge bg-dark rounded-pill mb-1">Q<?= $row['quarter'] ?></span><br>
                                    <span class="fw-bold"><?= $monthName ?></span>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($row['officer_name']) ?></div>
                                </td>
                                <td><span class="text-muted small"><?= $row['designation'] ?></span></td>
                                
                                <td class="text-center bg-light"><?= number_format($row['m_target_ai'], 1) ?></td>
                                <td class="text-center fw-bold text-primary"><?= $row['ai_count'] ?></td>
                                
                                <td class="text-center bg-light"><?= number_format($row['m_target_pd'], 1) ?></td>
                                <td class="text-center fw-bold text-info"><?= $row['pd_count'] ?></td>
                                
                                <td class="text-center bg-light"><?= number_format($row['m_target_calving'], 1) ?></td>
                                <td class="text-center fw-bold text-success"><?= $row['calving_count'] ?></td>
                                
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end">
                                        <div class="me-2 fw-bold text-<?= $percent >= 100 ? 'success' : 'primary' ?>"><?= $percent ?>%</div>
                                        <div class="progress shadow-sm" style="width: 50px; height: 6px;">
                                            <div class="progress-bar bg-<?= $percent >= 100 ? 'success' : 'primary' ?>" 
                                                 style="width: <?= min($percent, 100) ?>%"></div>
                                        </div>
                                    </div>
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
    var table = $('#masterBreedingTable').DataTable({
        "dom": '<"d-flex justify-content-between align-items-center mb-3"B>rtip',
        "buttons": [
            { extend: 'csv', className: 'btn btn-sm btn-success shadow-sm', text: '<i class="bi bi-file-csv"></i> CSV' },
            { 
                extend: 'pdf', 
                className: 'btn btn-sm btn-danger shadow-sm', 
                text: '<i class="bi bi-file-pdf"></i> PDF',
                orientation: 'landscape',
                pageSize: 'A4',
                title: 'Breeding Report - <?= $range_name ?>'
            },
            { extend: 'print', className: 'btn btn-sm btn-dark shadow-sm', text: '<i class="bi bi-printer"></i> Print' }
        ],
        "pageLength": 15,
        "order": [[0, "desc"]], // Sorts by Quarter and Month first
        "language": { "search": "Quick Search:" }
    });

    // Toggle Visibility Logic
    $('.toggle-vis').on('click', function(e) {
        e.preventDefault();
        var cols = $(this).attr('data-column');

        if (cols === 'all') {
            table.columns().visible(true);
        } else {
            // Index map: AI(3,4), PD(5,6), Calving(7,8)
            table.columns([3, 4, 5, 6, 7, 8]).visible(false);
            var targetCols = cols.split(',');
            targetCols.forEach(function(index) {
                table.column(parseInt(index)).visible(true);
            });
        }
    });
});
</script>

<?php require_once '../../../includes/footer.php'; ?>