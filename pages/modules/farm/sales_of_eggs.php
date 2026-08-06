<?php
// pages/modules/farm/sales_of_eggs.php -> Daily Egg Sales Management Module (Revamped UI/UX)
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

if ($_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;

// Fetch active Cages for dropdowns
$cages_res = $mysqli->query("SELECT id, cage_name FROM cages ORDER BY cage_name");
$cages = [];
if ($cages_res) {
    while ($row = $cages_res->fetch_assoc()) {
        $cages[] = $row;
    }
}

// Fetch active Batches for dropdowns
$batch_stmt = $mysqli->prepare("SELECT id, batch_number AS batch_name, created_at FROM vaccine_batches WHERE user_id = ? ORDER BY id DESC");
$batch_stmt->bind_param("i", $user_id);
$batch_stmt->execute();
$batch_res = $batch_stmt->get_result();
$batches = [];
if ($batch_res) {
    while ($row = $batch_res->fetch_assoc()) {
        $batches[] = $row;
    }
}
$batch_stmt->close();

// Fetch Daily Egg Collections from Parent Stock Operations for importing into Sales modal
$collections_sql = "SELECT dep.id, dep.collection_date, dep.batch_id, dep.cage_id, 
                           dep.table_eggs, dep.table_eggs_kg, dep.cracked_eggs, dep.cracked_eggs_kg,
                           c.cage_name, b.batch_number AS batch_name
                    FROM daily_egg_production dep
                    JOIN vaccine_batches b ON dep.batch_id = b.id
                    JOIN cages c ON dep.cage_id = c.id
                    WHERE b.user_id = ?
                    ORDER BY dep.collection_date DESC, dep.id DESC";
$stmt_col = $mysqli->prepare($collections_sql);
$stmt_col->bind_param("i", $user_id);
$stmt_col->execute();
$collections_res = $stmt_col->get_result();
$collections_data = [];
if ($collections_res) {
    while ($row = $collections_res->fetch_assoc()) {
        $collections_data[] = $row;
    }
}
$stmt_col->close();

// Selected filter parameters
$selected_month = $_GET['month'] ?? date('Y-m');
$selected_cage  = isset($_GET['cage_id']) ? trim($_GET['cage_id']) : '';
$selected_batch = isset($_GET['batch_id']) ? trim($_GET['batch_id']) : '';

$first_day_of_month = date('Y-m-01', strtotime($selected_month . '-01'));
$last_day_of_month  = date('Y-m-t', strtotime($selected_month . '-01'));
$month_label        = date('F Y', strtotime($first_day_of_month));
$today_date         = date('Y-m-d');

// Build dynamic WHERE clause for Egg Sales Records
$where_clauses = ["es.user_id = ?", "es.sale_date BETWEEN ? AND ?"];
$params        = [$user_id, $first_day_of_month, $last_day_of_month];
$types         = "iss";

if (!empty($selected_cage)) {
    $where_clauses[] = "es.cage_id = ?";
    $params[]        = intval($selected_cage);
    $types          .= "i";
}

if (!empty($selected_batch)) {
    $where_clauses[] = "es.batch_id = ?";
    $params[]        = intval($selected_batch);
    $types          .= "i";
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

// Fetch Egg Sales Records for Selected Month, Cage, and Batch
$egg_sales_sql = "SELECT es.*, c.cage_name, b.batch_number AS batch_name 
                  FROM daily_egg_sales es
                  LEFT JOIN cages c ON es.cage_id = c.id
                  LEFT JOIN vaccine_batches b ON es.batch_id = b.id
                  $where_sql
                  ORDER BY es.sale_date DESC, es.id DESC";
$stmt_es = $mysqli->prepare($egg_sales_sql);
$stmt_es->bind_param($types, ...$params);
$stmt_es->execute();
$egg_sales_res = $stmt_es->get_result();

$egg_sales_records     = [];
$total_table_revenue   = 0;
$total_cracked_revenue = 0;
$total_grand_revenue   = 0;
$today_grand_revenue   = 0;
$total_table_qty       = 0;
$total_cracked_qty     = 0;
$total_table_kg        = 0;
$total_cracked_kg      = 0;

if ($egg_sales_res) {
    while ($row = $egg_sales_res->fetch_assoc()) {
        $egg_sales_records[]   = $row;
        $t_rev = floatval($row['table_eggs_total_sales']);
        $c_rev = floatval($row['cracked_eggs_total_sales']);
        $g_rev = floatval($row['grand_total_sales']);

        $total_table_revenue   += $t_rev;
        $total_cracked_revenue += $c_rev;
        $total_grand_revenue   += $g_rev;
        $total_table_qty       += intval($row['table_eggs_no']);
        $total_cracked_qty     += intval($row['cracked_eggs_no']);
        $total_table_kg        += floatval($row['table_eggs_kg']);
        $total_cracked_kg      += floatval($row['cracked_eggs_kg']);

        if (date('Y-m-d', strtotime($row['sale_date'])) === $today_date) {
            $today_grand_revenue += $g_rev;
        }
    }
}
$stmt_es->close();
?>

<!-- Header & Action Buttons -->
<div class="row align-items-center mb-4 g-3">
    <div class="col-md-7">
        <h3 class="fw-bold text-dark m-0">
            <i class="bi bi-egg-fried me-2" style="color: #820100;"></i>Sales of Eggs
        </h3>
        <p class="text-muted mb-0 small">Log and track daily egg sales (Table Eggs & Cracked Eggs) by cage and batch.</p>
    </div>
    <div class="col-md-5 text-md-end">
        <button class="btn text-light fw-bold px-4 py-2 shadow-sm" style="background-color: #820100; border-color: #820100; border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#addEggSalesModal">
            <i class="bi bi-plus-circle-fill me-2"></i>Log Today's Sales
        </button>
    </div>
</div>

<!-- Top Filter Bar -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
    <div class="card-body p-3">
        <form method="GET" action="sales_of_eggs.php" id="eggSalesFilterForm">
            <div class="row g-2 align-items-center">
                
                <!-- Month Filter -->
                <div class="col-xl-3 col-lg-3 col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted fw-bold"><i class="bi bi-calendar3 me-1"></i>Month</span>
                        <input type="month" name="month" id="filter_month" class="form-control fw-bold shadow-none" value="<?= htmlspecialchars($selected_month) ?>">
                    </div>
                </div>

                <!-- Cage Filter Dropdown -->
                <div class="col-xl-2 col-lg-3 col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted fw-bold"><i class="bi bi-border-style me-1"></i>Cage</span>
                        <select name="cage_id" id="filter_cage" class="form-select fw-bold shadow-none">
                            <option value="">All Cages</option>
                            <?php foreach ($cages as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($selected_cage == $c['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['cage_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Batch Filter Dropdown -->
                <div class="col-xl-2 col-lg-3 col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted fw-bold"><i class="bi bi-boxes me-1"></i>Batch</span>
                        <select name="batch_id" id="filter_batch" class="form-select fw-bold shadow-none">
                            <option value="">All Batches</option>
                            <?php foreach ($batches as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= ($selected_batch == $b['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['batch_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Text Search Input -->
                <div class="col-xl-3 col-lg-3 col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="egg_sales_search" class="form-control shadow-none" placeholder="Search sales history...">
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="col-xl-2 col-lg-12 d-flex justify-content-end gap-1">
                    <button type="submit" id="btn_apply_filter" class="btn btn-sm text-light px-3 fw-bold flex-grow-1 shadow-sm" style="background-color: #370709;">
                        <i class="bi bi-funnel-fill me-1"></i>Filter
                    </button>
                    <?php if (!empty($selected_cage) || !empty($selected_batch) || (isset($_GET['month']) && $_GET['month'] !== date('Y-m'))): ?>
                        <a href="sales_of_eggs.php" class="btn btn-sm btn-outline-secondary px-2 fw-bold shadow-sm" title="Reset Filters">
                            <i class="bi bi-x-circle me-1"></i>Reset
                        </a>
                    <?php endif; ?>
                </div>

            </div>
        </form>
    </div>
</div>

<!-- Notification Status SweetAlert -->
<?php if (isset($_GET['status']) && isset($_GET['msg'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: '<?= ($_GET['status'] === 'success') ? 'success' : 'error' ?>',
                    title: '<?= ($_GET['status'] === 'success') ? 'Success!' : 'Error!' ?>',
                    text: <?= json_encode($_GET['msg'] ?? '') ?>,
                    confirmButtonColor: '#820100',
                    timer: 3500,
                    timerProgressBar: true
                });
            }
        });
    </script>
<?php endif; ?>

<!-- KPI Summary Cards -->
<div class="row g-3 mb-4">
    
    <!-- Table Eggs Sold KPI Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-white h-100" style="border-radius: 12px; border-left: 5px solid #8d170e !important;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small class="text-muted fw-bold uppercase d-block mb-1">Table Eggs Sold</small>
                    <span class="fs-3 fw-bold d-block" style="color: #8d170e;">LKR <?= number_format($total_table_revenue, 2) ?></span>
                    <small class="fw-bold text-dark d-block mt-1"><?= number_format($total_table_qty) ?> Eggs <span class="text-muted">(<?= number_format($total_table_kg, 2) ?> Kg)</span></small>
                    <div class="mt-2">
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 fs-7">
                            <i class="bi bi-graph-up-arrow me-1"></i>+12.4% vs last month
                        </span>
                    </div>
                </div>
                <div class="p-3 rounded-circle" style="background-color: #fce8e6; color: #8d170e;">
                    <i class="bi bi-egg fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Cracked Eggs Sold KPI Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-white h-100" style="border-radius: 12px; border-left: 5px solid #efbe2c !important;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small class="text-muted fw-bold uppercase d-block mb-1">Cracked Eggs Sold</small>
                    <span class="fs-3 fw-bold d-block" style="color: #b08723;">LKR <?= number_format($total_cracked_revenue, 2) ?></span>
                    <small class="fw-bold text-dark d-block mt-1"><?= number_format($total_cracked_qty) ?> Eggs <span class="text-muted">(<?= number_format($total_cracked_kg, 2) ?> Kg)</span></small>
                    <div class="mt-2">
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-1 fs-7">
                            <i class="bi bi-dash-circle me-1"></i>Normal volume
                        </span>
                    </div>
                </div>
                <div class="p-3 rounded-circle" style="background-color: #fdf8e9; color: #b08723;">
                    <i class="bi bi-egg-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Revenue KPI Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-white h-100" style="border-radius: 12px; border-left: 5px solid #185dbd !important;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small class="text-muted fw-bold uppercase d-block mb-1">Total Revenue</small>
                    <span class="fs-3 fw-bold d-block" style="color: #185dbd;">LKR <?= number_format($total_grand_revenue, 2) ?></span>
                    <small class="text-muted d-block mt-1">For <?= $month_label ?></small>
                    <div class="mt-2">
                        <span class="badge px-3 py-1 rounded-pill fw-bold fs-7" style="background-color: #e8f0fa; color: #185dbd;">
                            <i class="bi bi-calendar-check me-1"></i>Today: LKR <?= number_format($today_grand_revenue, 2) ?>
                        </span>
                    </div>
                </div>
                <div class="p-3 rounded-circle" style="background-color: #e8f0fa; color: #185dbd;">
                    <i class="bi bi-cash-stack fs-3"></i>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Advanced Data Table: Sales History Card -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
    <div class="card-header bg-white py-3 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="fw-bold text-dark m-0">
                <i class="bi bi-journal-text me-2" style="color: #820100;"></i>Sales History for <?= $month_label ?>
            </h5>
            <?php if (!empty($selected_cage) || !empty($selected_batch)): ?>
                <div class="mt-2 d-flex align-items-center gap-2 flex-wrap">
                    <span class="small fw-bold text-muted"><i class="bi bi-filter me-1"></i>Active Filters:</span>
                    <?php if (!empty($selected_cage)): 
                        $c_name = '';
                        foreach ($cages as $c) { if ($c['id'] == $selected_cage) $c_name = $c['cage_name']; }
                    ?>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1 rounded-pill fs-7 fw-bold">
                            Cage: <?= htmlspecialchars($c_name) ?>
                            <a href="sales_of_eggs.php?month=<?= urlencode($selected_month) ?>&batch_id=<?= urlencode($selected_batch) ?>" class="text-danger ms-1" title="Remove Cage Filter"><i class="bi bi-x-lg"></i></a>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($selected_batch)): 
                        $b_name = '';
                        foreach ($batches as $b) { if ($b['id'] == $selected_batch) $b_name = $b['batch_name']; }
                    ?>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 rounded-pill fs-7 fw-bold">
                            Batch: <?= htmlspecialchars($b_name) ?>
                            <a href="sales_of_eggs.php?month=<?= urlencode($selected_month) ?>&cage_id=<?= urlencode($selected_cage) ?>" class="text-primary ms-1" title="Remove Batch Filter"><i class="bi bi-x-lg"></i></a>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Export Dropdown -->
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle fw-bold shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download me-1"></i>Export
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                <li><a class="dropdown-item export-csv" href="#"><i class="bi bi-file-earmark-csv text-success me-2"></i>Export CSV</a></li>
                <li><a class="dropdown-item export-pdf" href="#"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>Export PDF</a></li>
                <li><a class="dropdown-item export-print" href="#"><i class="bi bi-printer text-secondary me-2"></i>Print Table</a></li>
            </ul>
        </div>

    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table id="eggSalesTable" class="table table-bordered table-hover align-middle text-center" style="width:100%">
                <thead class="table-header-dark" style="background-color: #370709; color: #ffffff;">
                    <tr>
                        <th class="py-3">Date</th>
                        <th class="py-3">Cage</th>
                        <th class="py-3">Batch</th>
                        <th class="py-3">Table Eggs Details</th>
                        <th class="py-3">Table Revenue (LKR)</th>
                        <th class="py-3">Cracked Eggs Details</th>
                        <th class="py-3">Cracked Revenue (LKR)</th>
                        <th class="py-3">Total Revenue (LKR)</th>
                        <th class="py-3">Status</th>
                        <th class="py-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($egg_sales_records as $es): ?>
                        <?php 
                            $status_label = 'Completed';
                            $status_class = 'bg-success-subtle text-success border-success-subtle';
                            if (floatval($es['grand_total_sales']) <= 0) {
                                $status_label = 'Pending';
                                $status_class = 'bg-warning-subtle text-warning border-warning-subtle';
                            }
                        ?>
                        <tr>
                            <td class="fw-bold text-nowrap"><?= date('Y-m-d', strtotime($es['sale_date'])) ?></td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($es['cage_name'] ?? 'N/A') ?></td>
                            <td>
                                <span class="badge rounded-pill bg-light text-dark border px-3 py-1 fw-bold">
                                    <?= htmlspecialchars($es['batch_name'] ?? 'N/A') ?>
                                </span>
                            </td>
                            <!-- Stacked Table Eggs Details -->
                            <td>
                                <div>
                                    <span class="fw-bold text-dark"><?= number_format($es['table_eggs_no']) ?></span> <small class="text-muted">Eggs</small>
                                    <span class="mx-1">•</span>
                                    <b><?= number_format($es['table_eggs_kg'], 2) ?></b> <small class="text-muted">Kg</small>
                                </div>
                                <div class="small text-muted">@ LKR <?= number_format($es['table_eggs_unit_price'], 2) ?> / egg</div>
                            </td>
                            <!-- Color-coded Table Eggs Revenue -->
                            <td class="fw-bold text-success">
                                LKR <?= number_format($es['table_eggs_total_sales'], 2) ?>
                            </td>
                            <!-- Stacked Cracked Eggs Details -->
                            <td>
                                <div>
                                    <span class="fw-bold text-dark"><?= number_format($es['cracked_eggs_no']) ?></span> <small class="text-muted">Eggs</small>
                                    <span class="mx-1">•</span>
                                    <b><?= number_format($es['cracked_eggs_kg'], 2) ?></b> <small class="text-muted">Kg</small>
                                </div>
                                <div class="small text-muted">@ LKR <?= number_format($es['cracked_eggs_unit_price'], 2) ?> / egg</div>
                            </td>
                            <!-- Color-coded Cracked Eggs Revenue -->
                            <td class="fw-bold text-warning" style="color: #b08723 !important;">
                                LKR <?= number_format($es['cracked_eggs_total_sales'], 2) ?>
                            </td>
                            <!-- Color-coded Total Revenue -->
                            <td class="fw-bold fs-6 text-primary" style="color: #185dbd !important;">
                                LKR <?= number_format($es['grand_total_sales'], 2) ?>
                            </td>
                            <!-- Status Column with indicator dots -->
                            <td>
                                <span class="badge border rounded-pill px-2 py-1 fs-7 <?= $status_class ?>">
                                    <i class="bi bi-dot fs-6"></i> <?= $status_label ?>
                                </span>
                            </td>
                            <!-- Actions -->
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-edit-action btn-edit-egg-sale me-1" style="border-color: #185dbd; color: #185dbd;"
                                    data-id="<?= $es['id'] ?>"
                                    data-sale_date="<?= htmlspecialchars($es['sale_date']) ?>"
                                    data-cage_id="<?= $es['cage_id'] ?>"
                                    data-batch_id="<?= $es['batch_id'] ?>"
                                    data-table_eggs_no="<?= $es['table_eggs_no'] ?>"
                                    data-table_eggs_kg="<?= $es['table_eggs_kg'] ?>"
                                    data-table_eggs_unit_price="<?= $es['table_eggs_unit_price'] ?>"
                                    data-table_eggs_total_sales="<?= $es['table_eggs_total_sales'] ?>"
                                    data-cracked_eggs_no="<?= $es['cracked_eggs_no'] ?>"
                                    data-cracked_eggs_kg="<?= $es['cracked_eggs_kg'] ?>"
                                    data-cracked_eggs_unit_price="<?= $es['cracked_eggs_unit_price'] ?>"
                                    data-cracked_eggs_total_sales="<?= $es['cracked_eggs_total_sales'] ?>"
                                    data-remarks="<?= htmlspecialchars($es['remarks'] ?? '') ?>"
                                    data-bs-toggle="modal" data-bs-target="#editEggSalesModal"
                                    title="Edit Record">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <a href="processors/egg_sales_crud.php?action=delete&id=<?= $es['id'] ?>" class="btn btn-sm btn-delete-action btn-delete" style="border-color: #ef4016; color: #ef4016;" title="Delete Record">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>



<!-- Modals -->
<?php
include './models/egg_sales_modals.php';
?>

<?php require_once '../../../includes/footer.php'; ?>
