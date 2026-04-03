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

// 1. Fetch data
$report_stmt = $mysqli->prepare("
    SELECT 
        pc.id as category_id,
        pc.category_name,
        pi.item_name,
        pi.unit,
        lt.annual_target_value as target,
        COALESCE(SUM(mpr.amount), 0) as current_actual
    FROM livestock_targets lt
    JOIN production_items pi ON lt.item_id = pi.id
    JOIN production_categories pc ON pi.category_id = pc.id
    LEFT JOIN monthly_production_records mpr ON pi.id = mpr.item_id 
        AND mpr.range_id = lt.range_id 
        AND YEAR(mpr.report_date) = lt.target_year
    WHERE lt.range_id = ? AND lt.target_year = ?
    GROUP BY pi.id
    ORDER BY pc.sort_order ASC, pi.item_name ASC
");
$report_stmt->bind_param("ii", $range_id, $current_year);
$report_stmt->execute();
$production_report = $report_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 2. Group by Category for the Summary Cards
$category_summary = [];
foreach ($production_report as $row) {
    $cat_name = $row['category_name'];
    if (!isset($category_summary[$cat_name])) {
        $category_summary[$cat_name] = [
            'target' => 0,
            'actual' => 0,
            'unit'   => $row['unit'] // Note: Categories usually share units (L or Kg)
        ];
    }
    $category_summary[$cat_name]['target'] += $row['target'];
    $category_summary[$cat_name]['actual'] += $row['current_actual'];
}



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
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#addProductionModal">
                            <i class="bi bi-plus-circle fs-3"></i><br>
                            Livestock Production
                        </button>
                    </div>
                    <div class="col-md-3">
                        <a href="livestock_production_reports.php" class="btn btn-warning w-100 py-3">
                            <i class="bi bi-search fs-3"></i><br>
                            Livestock production reports
                        </a>
                    </div>

                </div>
            </div>
        </div>

        <h5 class="mb-3 fw-bold"><i class="bi bi-grid-3x3-gap me-2"></i>Category-wise Progress</h5>
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-3 mb-4">
            <?php foreach ($category_summary as $name => $stats):
                $cat_percent = ($stats['target'] > 0) ? ($stats['actual'] / $stats['target']) * 100 : 0;
                $cat_color = ($cat_percent >= 100) ? 'bg-success' : ($cat_percent > 50 ? 'bg-primary' : 'bg-warning');
            ?>
                <div class="col">
                    <div class="card h-100 shadow-sm border-0 border-start border-4 <?= str_replace('bg', 'border', $cat_color) ?>">
                        <div class="card-body p-3">
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;"><?= htmlspecialchars($name) ?></small>
                            <div class="d-flex justify-content-between align-items-end mt-1">
                                <h4 class="mb-0 fw-bold"><?= round($cat_percent) ?>%</h4>
                                <small class="text-muted"><?= number_format($stats['actual']) ?> / <?= number_format($stats['target']) ?></small>
                            </div>
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar <?= $cat_color ?>" style="width: <?= min($cat_percent, 100) ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
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
            "order": [
                [0, "asc"]
            ],
            "dom": '<"d-flex justify-content-between align-items-center mb-3"B>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            "buttons": [{
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