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

$current_year = date('Y');

$report_stmt = $mysqli->prepare("
    SELECT 
        pc.category_name,
        pi.item_name,
        pi.unit,
        lt.annual_target_value as target,
        COALESCE(SUM(mpr.amount), 0) as current_actual,
        (lt.annual_target_value - COALESCE(SUM(mpr.amount), 0)) as balance
    FROM livestock_targets lt
    JOIN production_items pi ON lt.item_id = pi.id
    JOIN production_categories pc ON pi.category_id = pc.id
    LEFT JOIN monthly_production_records mpr ON pi.id = mpr.item_id 
        AND mpr.range_id = lt.range_id 
        AND YEAR(mpr.report_date) = lt.target_year
    WHERE lt.range_id = ? AND lt.target_year = ?
    GROUP BY pi.id
    ORDER BY pc.category_name ASC
");
$report_stmt->bind_param("ii", $range_id, $current_year);
$report_stmt->execute();
$production_report = $report_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 mb-0 fw-bold">Livestock Production & Targets</h2>
                <small class="text-muted">Target Year: <?= $current_year ?> | Monitoring Progress</small>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <!-- FIXED: Now opens modal instead of navigating -->
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#addProductionModal">
                            <i class="bi bi-plus-circle fs-3"></i><br>
                            Livestock Production
                        </button>
                    </div>
                    <div class="col-md-4">
                        <a href="slaughter_stats.php" class="btn btn-primary w-100 py-3">
                            <i class="bi bi-search fs-3"></i><br>
                            Slaughter Statistics
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="breeding_logs.php" class="btn btn-danger w-100 py-3">
                            <i class="bi bi-search fs-3"></i><br>
                            Breeding and Semen Logs
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <table id="productionTable" class="table table-hover align-middle w-100">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th>Category</th>
                            <th>Product Name</th>
                            <th class="text-end">Target (Year)</th>
                            <th class="text-end">Actual (YTD)</th>
                            <th class="text-end">Balance</th>
                            <th width="150">Achievement %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($production_report as $row): 
                            $percent = ($row['target'] > 0) ? ($row['current_actual'] / $row['target']) * 100 : 0;
                            $bar_color = ($percent >= 100) ? 'bg-success' : ($percent > 50 ? 'bg-info' : 'bg-warning');
                        ?>
                            <tr>
                                <td><span class="badge bg-light text-dark"><?= htmlspecialchars($row['category_name']) ?></span></td>
                                <td><strong><?= htmlspecialchars($row['item_name']) ?></strong></td>
                                <td class="text-end"><?= number_format($row['target']) ?> <?= $row['unit'] ?></td>
                                <td class="text-end fw-bold text-primary"><?= number_format($row['current_actual']) ?></td>
                                <td class="text-end text-muted"><?= number_format($row['balance']) ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                            <div class="progress-bar <?= $bar_color ?>" style="width: <?= min($percent, 100) ?>%"></div>
                                        </div>
                                        <small class="fw-bold"><?= round($percent) ?>%</small>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php include 'models/add_production_record_modal.php'; ?>

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

<script>
$(document).ready(function() {
    // FIXED: Correct table ID
    $('#productionTable').DataTable({
        "order": [[0, "asc"]],
        "dom": '<"d-flex justify-content-between align-items-center mb-3"B>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
        "buttons": [
            {
                extend: 'csv',
                text: '<i class="bi bi-filetype-csv"></i> CSV',
                className: 'btn btn-sm btn-success shadow-sm',
                titleAttr: 'Export Filtered CSV'
            },
            {
                extend: 'pdf',
                text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                className: 'btn btn-sm btn-danger shadow-sm',
                title: 'Livestock Production & Targets - <?= $range_name ?>'
            },
            {
                extend: 'print',
                text: '<i class="bi bi-printer"></i> Print',
                className: 'btn btn-sm btn-warning shadow-sm'
            }
        ]
    });
});
</script>

<?php require_once '../../../includes/footer.php'; ?>