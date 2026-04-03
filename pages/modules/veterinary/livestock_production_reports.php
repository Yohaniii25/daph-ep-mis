<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

require_once '../../../config/db_connect.php';

// Session & Context Variables
$full_name   = $_SESSION['full_name'] ?? 'Veterinary Surgeon';
$range_id    = $_SESSION['range_id'] ?? null;
$district_id = $_SESSION['district_id'] ?? null;
$current_year = date('Y');

if (empty($range_id)) {
    die('<div class="alert alert-danger text-center p-5 m-5">Error: Your account is not assigned to any Veterinary Range.</div>');
}

// Fetch Names for Display
$district_name = 'Unknown District';
$range_name    = 'Unknown Range';

$meta_stmt = $mysqli->prepare("SELECT (SELECT name FROM districts WHERE id = ?) as d_name, (SELECT name FROM veterinary_ranges WHERE id = ?) as r_name");
$meta_stmt->bind_param("ii", $district_id, $range_id);
$meta_stmt->execute();
$meta_res = $meta_stmt->get_result()->fetch_assoc();
$district_name = $meta_res['d_name'] ?? $district_name;
$range_name = $meta_res['r_name'] ?? $range_name;

// Main Report Query
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
    ORDER BY pc.category_name ASC, pi.item_name ASC
");
$report_stmt->bind_param("ii", $range_id, $current_year);
$report_stmt->execute();
$production_report = $report_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Data for Filter and Summary Cards
$unique_categories = array_unique(array_column($production_report, 'category_name'));
sort($unique_categories);

$cat_summary = [];
foreach ($production_report as $row) {
    $c = $row['category_name'];
    if(!isset($cat_summary[$c])) $cat_summary[$c] = ['t'=>0, 'a'=>0];
    $cat_summary[$c]['t'] += $row['target'];
    $cat_summary[$c]['a'] += $row['current_actual'];
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
                <h2 class="h4 mb-0 fw-bold text-uppercase">Production Monitoring Dashboard</h2>
            </div>
            <div class="text-end">
                <h5 class="mb-0 fw-bold"><?= $current_year ?></h5>
                <small class="text-muted text-uppercase">Target Year</small>
            </div>
        </div>


        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="row g-3 mb-4 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">FILTER BY CATEGORY</label>
                        <div class="input-group shadow-sm">
                            <select id="categoryFilter" class="form-select border-primary">
                                <option value="">View All Categories</option>
                                <?php foreach ($unique_categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-outline-secondary" type="button" id="resetFilter">Reset</button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">SEARCH ITEMS</label>
                        <input type="text" id="tableSearch" class="form-control shadow-sm" placeholder="Type to search product...">
                    </div>
                </div>

                <table id="productionTable" class="table table-hover align-middle w-100">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th>Category</th>
                            <th>Product Name</th>
                            <th class="text-end">Annual Target</th>
                            <th class="text-end">Actual (YTD)</th>
                            <th class="text-end">Balance</th>
                            <th width="180">Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($production_report as $row):
                            $percent = ($row['target'] > 0) ? ($row['current_actual'] / $row['target']) * 100 : 0;
                            $bar_color = ($percent >= 100) ? 'bg-success' : ($percent > 50 ? 'bg-info' : 'bg-warning');
                        ?>
                            <tr>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['category_name']) ?></span></td>
                                <td><strong><?= htmlspecialchars($row['item_name']) ?></strong></td>
                                <td class="text-end"><?= number_format($row['target']) ?> <small><?= $row['unit'] ?></small></td>
                                <td class="text-end fw-bold text-primary"><?= number_format($row['current_actual']) ?></td>
                                <td class="text-end text-muted"><?= number_format($row['balance']) ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2" style="height: 10px;">
                                            <div class="progress-bar progress-bar-striped <?= $bar_color ?>" style="width: <?= min($percent, 100) ?>%"></div>
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
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#productionTable').DataTable({
        "order": [[0, "asc"]],
        "pageLength": 50,
        "dom": '<"d-flex justify-content-end mb-3"B>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
        "buttons": [
            {
                extend: 'csv',
                text: '<i class="bi bi-file-earmark-spreadsheet"></i> CSV',
                className: 'btn btn-sm btn-success',
                exportOptions: { columns: ':visible' }
            },
            {
                extend: 'pdf',
                text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                className: 'btn btn-sm btn-danger',
                orientation: 'landscape',
                title: 'Production Report: <?= $range_name ?> (<?= $current_year ?>)',
                exportOptions: { columns: ':visible' }
            },
            {
                extend: 'print',
                text: '<i class="bi bi-printer"></i> Print',
                className: 'btn btn-sm btn-dark',
                exportOptions: { columns: ':visible' }
            }
        ],
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search products..."
        }
    });

    // 1. External Category Filter
    $('#categoryFilter').on('change', function() {
        var val = $.fn.dataTable.util.escapeRegex($(this).val());
        table.column(0).search(val ? '^' + val + '$' : '', true, false).draw();
    });

    // 2. External Search Bar
    $('#tableSearch').on('keyup', function() {
        table.search(this.value).draw();
    });

    // 3. Reset Button
    $('#resetFilter').on('click', function() {
        $('#categoryFilter').val('');
        $('#tableSearch').val('');
        table.search('').column(0).search('').draw();
    });
});
</script>

<?php require_once '../../../includes/footer.php'; ?>