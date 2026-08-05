<?php
// pages/modules/farm/sales_of_eggs.php -> Daily Egg Sales Management Module
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

if ($_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;

// Selected filter month (default to current month YYYY-MM)
$selected_month = $_GET['month'] ?? date('Y-m');
$first_day_of_month = date('Y-m-01', strtotime($selected_month . '-01'));
$last_day_of_month = date('Y-m-t', strtotime($selected_month . '-01'));
$month_label = date('F Y', strtotime($first_day_of_month));

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

// Fetch Daily Egg Collections from Parent Stock Operations for importing into Sales
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

// Fetch Egg Sales Records for Selected Month
$egg_sales_sql = "SELECT es.*, c.cage_name, b.batch_number AS batch_name 
                  FROM daily_egg_sales es
                  LEFT JOIN cages c ON es.cage_id = c.id
                  LEFT JOIN vaccine_batches b ON es.batch_id = b.id
                  WHERE es.user_id = ? AND es.sale_date BETWEEN ? AND ?
                  ORDER BY es.sale_date DESC, es.id DESC";
$stmt_es = $mysqli->prepare($egg_sales_sql);
$stmt_es->bind_param("iss", $user_id, $first_day_of_month, $last_day_of_month);
$stmt_es->execute();
$egg_sales_res = $stmt_es->get_result();

$egg_sales_records = [];
$total_table_revenue = 0;
$total_cracked_revenue = 0;
$total_grand_revenue = 0;
$total_table_qty = 0;
$total_cracked_qty = 0;

if ($egg_sales_res) {
    while ($row = $egg_sales_res->fetch_assoc()) {
        $egg_sales_records[] = $row;
        $total_table_revenue += floatval($row['table_eggs_total_sales']);
        $total_cracked_revenue += floatval($row['cracked_eggs_total_sales']);
        $total_grand_revenue += floatval($row['grand_total_sales']);
        $total_table_qty += intval($row['table_eggs_no']);
        $total_cracked_qty += intval($row['cracked_eggs_no']);
    }
}
$stmt_es->close();
?>

<!-- Header -->
<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h3 class="fw-bold text-dark m-0">
            <i class="bi bi-egg-fried me-2" style="color: #820100;"></i>Sales of Eggs
        </h3>
        <p class="text-muted mb-0 small">Log and track daily egg sales (Table Eggs & Cracked Eggs) by cage and batch.</p>
    </div>
    <div class="col-md-5 d-flex justify-content-end align-items-center gap-2">
        <label class="fw-bold mb-0 text-nowrap"><i class="bi bi-calendar3 me-1"></i>Select Month:</label>
        <input type="month" id="filter_month" class="form-control form-control-sm w-auto shadow-sm" value="<?= $selected_month ?>">
        <button type="button" id="btn_apply_filter" class="btn btn-sm btn-apply-filter px-3 fw-bold" style="background-color: #370709; color: #ffffff;">
            <i class="bi bi-funnel me-1"></i>Filter
        </button>
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
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-white card-kpi-distributed" style="border-radius: 12px; border-left: 5px solid #8d170e !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold uppercase d-block">Total Table Eggs Sales</small>
                    <span class="fs-3 fw-bold text-color-c11" style="color: #8d170e;">LKR <?= number_format($total_table_revenue, 2) ?></span>
                    <small class="text-muted d-block mt-1"><?= number_format($total_table_qty) ?> Table Eggs Sold</small>
                </div>
                <div class="p-3 rounded-circle bg-color-c11-light" style="background-color: #fce8e6; color: #8d170e;">
                    <i class="bi bi-egg me-1 fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-white card-kpi-needed" style="border-radius: 12px; border-left: 5px solid #efbe2c !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold uppercase d-block">Total Cracked Eggs Sales</small>
                    <span class="fs-3 fw-bold text-color-c8" style="color: #b08723;">LKR <?= number_format($total_cracked_revenue, 2) ?></span>
                    <small class="text-muted d-block mt-1"><?= number_format($total_cracked_qty) ?> Cracked Eggs Sold</small>
                </div>
                <div class="p-3 rounded-circle bg-color-c3-light" style="background-color: #fdf8e9; color: #b08723;">
                    <i class="bi bi-egg-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-white card-kpi-opening" style="border-radius: 12px; border-left: 5px solid #185dbd !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold uppercase d-block">Grand Total Sales Revenue</small>
                    <span class="fs-3 fw-bold text-color-c10" style="color: #185dbd;">LKR <?= number_format($total_grand_revenue, 2) ?></span>
                    <small class="text-muted d-block mt-1">For <?= $month_label ?></small>
                </div>
                <div class="p-3 rounded-circle bg-color-c10-light" style="background-color: #e8f0fa; color: #185dbd;">
                    <i class="bi bi-cash-stack fs-3"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Table Card -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold text-dark m-0">
            <i class="bi bi-list-check me-2 text-color-c11" style="color: #8d170e;"></i>Egg Sales Transactions for <?= $month_label ?>
        </h5>
        <button class="btn btn-log-feed fw-bold px-4 text-light" style="background-color: #820100; color: #ffffff;" data-bs-toggle="modal" data-bs-target="#addEggSalesModal">
            <i class="bi bi-plus-circle me-1"></i>Log Sales of Eggs
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="eggSalesTable" class="table table-bordered table-hover align-middle text-center" style="width:100%">
                <thead class="table-header-dark" style="background-color: #370709; color: #ffffff;">
                    <tr>
                        <th>Date</th>
                        <th>Cage Name</th>
                        <th>Batch</th>
                        <th>Table Eggs (No / Kg / Unit Price)</th>
                        <th>Table Eggs Total (LKR)</th>
                        <th>Cracked Eggs (No / Kg / Unit Price)</th>
                        <th>Cracked Eggs Total (LKR)</th>
                        <th>Grand Total (LKR)</th>
                        <th>Remarks</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($egg_sales_records as $es): ?>
                        <tr>
                            <td class="fw-bold text-nowrap"><?= date('Y-m-d', strtotime($es['sale_date'])) ?></td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($es['cage_name'] ?? 'N/A') ?></td>
                            <td><span class="badge bg-light text-dark border px-2"><?= htmlspecialchars($es['batch_name'] ?? 'N/A') ?></span></td>
                            <td>
                                <div><b><?= number_format($es['table_eggs_no']) ?></b> NO / <b><?= number_format($es['table_eggs_kg'], 2) ?></b> Kg</div>
                                <small class="text-muted">@ LKR <?= number_format($es['table_eggs_unit_price'], 2) ?></small>
                            </td>
                            <td class="fw-bold text-success">LKR <?= number_format($es['table_eggs_total_sales'], 2) ?></td>
                            <td>
                                <div><b><?= number_format($es['cracked_eggs_no']) ?></b> NO / <b><?= number_format($es['cracked_eggs_kg'], 2) ?></b> Kg</div>
                                <small class="text-muted">@ LKR <?= number_format($es['cracked_eggs_unit_price'], 2) ?></small>
                            </td>
                            <td class="fw-bold text-warning">LKR <?= number_format($es['cracked_eggs_total_sales'], 2) ?></td>
                            <td class="fw-bold text-danger fs-6">LKR <?= number_format($es['grand_total_sales'], 2) ?></td>
                            <td class="small"><?= htmlspecialchars($es['remarks'] ?? '-') ?></td>
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
                <tfoot class="tfoot-summary fw-bold" style="background-color: #d4c7b7; color: #370709;">
                    <tr>
                        <td colspan="4" class="text-start">MONTHLY TOTAL SUMMARY</td>
                        <td class="text-success">LKR <?= number_format($total_table_revenue, 2) ?></td>
                        <td></td>
                        <td class="text-warning">LKR <?= number_format($total_cracked_revenue, 2) ?></td>
                        <td class="text-danger fs-6">LKR <?= number_format($total_grand_revenue, 2) ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Modals -->
<?php
include './models/egg_sales_modals.php';
?>

<?php require_once '../../../includes/footer.php'; ?>
