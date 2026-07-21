<?php
require_once '../../../includes/header.php';

require_once '../../../config/db_connect.php';

if ($_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1; // Fallback to 1 if session key differs

$where = " WHERE sales_date = CURDATE()";
if ($_SESSION['role'] === 'farms_dd' && !empty($_SESSION['farm_id'])) {
    $where .= " AND farm_id = " . (int)$_SESSION['farm_id'];
}
$kpi_query = "SELECT 
    IFNULL(SUM(total_revenue), 0) AS total_sales,
    IFNULL(SUM(CASE WHEN egg_category = 'Table' THEN quantity_sold ELSE 0 END), 0) AS table_qty,
    IFNULL(SUM(CASE WHEN egg_category = 'Cracked' THEN quantity_sold ELSE 0 END), 0) AS cracked_qty
FROM hatchery_sales" . $where;

$kpi_result = $mysqli->query($kpi_query);
$kpi_res = $kpi_result ? $kpi_result->fetch_assoc() : ['total_sales' => 0, 'table_qty' => 0, 'cracked_qty' => 0];

require_once '../../../includes/sidebar.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">
        <h2 class="fw-bold mb-4 text-dark">Hatchery Sales Outlet</h2>

        <?php if (isset($_GET['status'])): ?>
            <div class="alert alert-<?= ($_GET['status'] === 'success') ? 'success' : 'danger' ?> alert-dismissible fade show shadow-sm" role="alert">
                <strong><?= ($_GET['status'] === 'success') ? 'Success!' : 'Error!' ?></strong> <?= htmlspecialchars($_GET['msg'] ?? '') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-4">
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Today's Total Revenue</h6>
                        <h2 class="text-info mb-0 fw-bold">LKR <?= number_format($kpi_res['total_sales'], 2) ?></h2>
                        <small class="text-muted">Gross Earnings Today</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Table Eggs Disposed</h6>
                        <h2 class="text-primary mb-0 fw-bold"><?= number_format($kpi_res['table_qty']) ?> <span class="fs-6 fw-normal text-muted">Eggs</span></h2>
                        <small class="text-muted">Premium Grade Sales Today</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Cracked Eggs Disposed</h6>
                        <h2 class="text-danger mb-0 fw-bold"><?= number_format($kpi_res['cracked_qty']) ?> <span class="fs-6 fw-normal text-muted">Eggs</span></h2>
                        <small class="text-muted">Commercial Grade Sales Today</small>
                    </div>
                </div>
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
                        <button style="background-color: #370709; border-color: #370709;" class="btn btn-primary w-100 py-3" data-bs-toggle="modal" data-bs-target="#salesModal">
                            <i class="bi bi-file-earmark-plus"></i><br>
                            Log Grading & Collection
                        </button>
                    </div>


                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="m-0 fw-bold text-dark">Sales Outflow & Revenue Ledger</h5>
            </div>
            <div class="card-body">
                <table id="salesTable" class="table table-striped align-middle row-border" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>Invoice ID</th>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Quantity Sold</th>
                            <th>Actual Rate</th>
                            <th>Hope Rate</th>
                            <th>Total Revenue</th>
                            <th>Target Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $where_ledger = "";
                        if ($_SESSION['role'] === 'farms_dd' && !empty($_SESSION['farm_id'])) {
                            $where_ledger = " WHERE farm_id = " . (int)$_SESSION['farm_id'];
                        }
                        $sales_sql = "SELECT * FROM hatchery_sales" . $where_ledger . " ORDER BY sales_date DESC, id DESC";
                        $res = $mysqli->query($sales_sql);
                        if ($res):
                            while ($row = $res->fetch_assoc()):
                                $target_met = ($row['actual_rate'] >= $row['hope_rate']);
                        ?>
                                <tr>
                                    <td class="fw-bold">#<?= $row['id'] ?></td>
                                    <td><?= $row['sales_date'] ?></td>
                                    <td>
                                        <span class="badge <?= ($row['egg_category'] === 'Table') ? 'bg-primary' : 'bg-danger' ?>">
                                            <?= $row['egg_category'] ?> Egg
                                        </span>
                                    </td>
                                    <td class="fw-bold"><?= number_format($row['quantity_sold']) ?></td>
                                    <td>LKR <?= number_format($row['actual_rate'], 2) ?></td>
                                    <td class="text-muted">LKR <?= number_format($row['hope_rate'], 2) ?></td>
                                    <td class="fw-bold text-dark">LKR <?= number_format($row['total_revenue'], 2) ?></td>
                                    <td>
                                        <?php if ($target_met): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2">Met Target</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2">Below Hope</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-secondary edit-sales-btn"
                                                data-id="<?= $row['id'] ?>"
                                                data-date="<?= $row['sales_date'] ?>"
                                                data-category="<?= $row['egg_category'] ?>"
                                                data-qty="<?= $row['quantity_sold'] ?>"
                                                data-actual="<?= $row['actual_rate'] ?>"
                                                data-hope="<?= $row['hope_rate'] ?>"
                                                data-bs-toggle="modal" data-bs-target="#salesModal">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="processors/sales_crud.php?action=delete&id=<?= $row['id'] ?>"
                                                class="btn btn-outline-danger"
                                                onclick="return confirm('Are you sure you want to permanently delete this sales row?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                        <?php endwhile;
                        endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php
include './models/sales_modal.php';
require_once '../../../includes/footer.php';
?>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
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
        $('#salesTable').DataTable({
            dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            buttons: [{
                    extend: 'csv',
                    className: 'btn btn-sm btn-success px-3 me-1 rounded'
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-sm btn-warning px-3 me-1 rounded text-dark'
                },
                {
                    extend: 'print',
                    className: 'btn btn-sm btn-danger px-3 rounded'
                }
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search sales rows..."
            }
        });

        // Populate dynamic adjustments into fields on trigger
        $('.edit-sales-btn').on('click', function() {
            $('#modalAction').val('update');
            $('#saleId').val($(this).data('id'));
            $('#salesDate').val($(this).data('date'));
            $('#eggCategory').val($(this).data('category'));
            $('#qtySold').val($(this).data('qty'));
            $('#rateActual').val($(this).data('actual'));
            $('#rateHope').val($(this).data('hope'));
            $('#modalTitle').html('<i class="bi bi-pencil-square me-2"></i>Edit Sales Invoice');
        });

        // Reset layout elements smoothly upon cancellation
        $('#salesModal').on('hidden.bs.modal', function() {
            $('#modalAction').val('create');
            $('#saleId').val('');
            $('#salesForm')[0].reset();
            $('#modalTitle').html('<i class="bi bi-cart-plus me-2"></i>Log New Sales Invoice');
        });
    });
</script>