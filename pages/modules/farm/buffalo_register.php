<?php
// pages/modules/farm/buffalo_register.php -> Buffalo (Expended/Disposal Register) Module
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['role'])) {
    header("Location: ../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? 1;
$species_type = 'Buffalo';
$current_page_file = 'buffalo_register.php';


// Selected filter month (default to current month YYYY-MM or 'all')
$selected_month = $_GET['month'] ?? date('Y-m');

$where_clause = "WHERE species = ?";
$params = [$species_type];
$types = "s";

if (!empty($selected_month) && $selected_month !== 'all') {
    $first_day = date('Y-m-01', strtotime($selected_month . '-01'));
    $last_day = date('Y-m-t', strtotime($selected_month . '-01'));
    $where_clause .= " AND disposal_date BETWEEN ? AND ?";
    $params[] = $first_day;
    $params[] = $last_day;
    $types .= "ss";
}

$sql = "SELECT * FROM animal_disposal_register $where_clause ORDER BY disposal_date DESC, id DESC";
$stmt = $mysqli->prepare($sql);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = false;
}

$records = [];
$total_disposed_head = 0;
$total_revenue_realized = 0.00;
$total_stud_bulls = 0;
$total_draught_bulls = 0;
$total_cows = 0;
$total_heifer_calves = 0;
$total_bull_calves = 0;
$count_sold = 0;
$count_died = 0;
$count_other = 0;

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $records[] = $row;
        $total_disposed_head += intval($row['total_animals']);
        $total_revenue_realized += floatval($row['amount_realized']);
        $total_stud_bulls += intval($row['stud_bulls']);
        $total_draught_bulls += intval($row['draught_bulls']);
        $total_cows += intval($row['cows']);
        $total_heifer_calves += intval($row['heifer_calves']);
        $total_bull_calves += intval($row['bull_calves']);

        if (strtolower($row['how_disposed_of']) === 'sold') {
            $count_sold += intval($row['total_animals']);
        } elseif (strtolower($row['how_disposed_of']) === 'died') {
            $count_died += intval($row['total_animals']);
        } else {
            $count_other += intval($row['total_animals']);
        }
    }
}
if ($stmt) { $stmt->close(); }
?>

<link rel="stylesheet" href="../../../assets/css/farm.css">

<!-- Header Section -->
<div class="row align-items-center mb-4">
    <div class="col-md-7">
        <h3 class="fw-bold text-dark m-0">
            <i class="bi bi-journal-text me-2" style="color: #820100;"></i>Buffalo Expended / Disposal Register
        </h3>
        <p class="text-muted mb-0 small">Official ledger to log and track the disposal, sale, or expenditure of Buffalo stock.</p>
    </div>
    <div class="col-md-5 d-flex justify-content-end align-items-center gap-2">
        <label class="fw-bold mb-0 text-nowrap"><i class="bi bi-calendar3 me-1"></i>Filter Month:</label>
        <input type="month" id="filter_month" class="form-control form-control-sm w-auto shadow-sm" value="<?= $selected_month === 'all' ? '' : $selected_month ?>">
        <button type="button" id="btn_apply_filter" class="btn btn-sm btn-apply-filter px-3 fw-bold" onclick="applyMonthFilter()">
            <i class="bi bi-funnel me-1"></i>Filter
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary px-3" onclick="window.location.href='buffalo_register.php?month=all'">
            All
        </button>
    </div>
</div>

<!-- SweetAlert Status Notification -->
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

<!-- KPI SUMMARY CARDS -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white card-kpi-distributed" style="border-radius: 12px;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold uppercase d-block">Total Disposed Head</small>
                    <span class="fs-3 fw-bold text-color-c11"><?= number_format($total_disposed_head) ?></span>
                    <small class="text-muted d-block mt-1">Buffalo Animals</small>
                </div>
                <div class="p-3 rounded-circle bg-color-c11-light">
                    <i class="bi bi-box-arrow-up fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white card-kpi-opening" style="border-radius: 12px;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold uppercase d-block">Amount Realized (Sales)</small>
                    <span class="fs-3 fw-bold text-primary">LKR <?= number_format($total_revenue_realized, 2) ?></span>
                    <small class="text-muted d-block mt-1">Total Cash Received</small>
                </div>
                <div class="p-3 rounded-circle bg-color-c10-light">
                    <i class="bi bi-cash-stack fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white card-kpi-needed" style="border-radius: 12px;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold uppercase d-block">Disposal Methods</small>
                    <span class="fs-4 fw-bold text-dark"><?= $count_sold ?> <small class="fs-6 text-success fw-normal">Sold</small> | <?= $count_died ?> <small class="fs-6 text-danger fw-normal">Died</small></span>
                    <small class="text-muted d-block mt-1"><?= $count_other ?> Transferred/Other</small>
                </div>
                <div class="p-3 rounded-circle bg-color-c3-light">
                    <i class="bi bi-pie-chart-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white card-kpi-received" style="border-radius: 12px;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold uppercase d-block">Total Entries</small>
                    <span class="fs-3 fw-bold text-dark"><?= count($records) ?></span>
                    <small class="text-muted d-block mt-1">Ledger Records</small>
                </div>
                <div class="p-3 rounded-circle bg-color-c5-light">
                    <i class="bi bi-journal-check fs-3"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MAIN TABLE CARD -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold text-dark m-0">
            <i class="bi bi-table me-2" style="color: #820100;"></i>Buffalo Expended / Disposal Ledger Entries
        </h5>
        <button class="btn btn-log-feed fw-bold px-4 text-light shadow-sm" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addAnimalDisposalModal">
            <i class="bi bi-plus-circle me-1"></i>Log Buffalo Disposal
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="buffaloDisposalTable" class="table table-bordered table-hover align-middle text-center" style="width:100%">
                <thead class="table-header-dark">
                    <tr>
                        <th rowspan="2" class="align-middle" style="width: 9%;">Date</th>
                        <th rowspan="2" class="align-middle" style="width: 11%;">Voucher No</th>
                        <th rowspan="2" class="align-middle" style="width: 11%;">How Disposed Of</th>
                        <th rowspan="2" class="align-middle" style="width: 12%;">Amount Realized (If Sold)</th>
                        <th rowspan="2" class="align-middle" style="width: 14%;">No. and Date of Cash Receipt</th>
                        <th colspan="5" class="text-center bg-secondary text-white py-2">Animal Categories (Quantity)</th>
                        <th rowspan="2" class="align-middle" style="width: 8%;">Total Animals</th>
                        <th rowspan="2" class="align-middle" style="width: 10%;">Remarks</th>
                        <th rowspan="2" class="align-middle text-end" style="width: 8%;">Actions</th>
                    </tr>
                    <tr class="bg-light text-dark small">
                        <th style="width: 6%;">Stud Bulls</th>
                        <th style="width: 6%;">Draught Bulls</th>
                        <th style="width: 6%;">Cows</th>
                        <th style="width: 6%;">Heifer Calves</th>
                        <th style="width: 6%;">Bull Calves</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $r): ?>
                        <tr>
                            <td class="fw-bold text-nowrap"><?= date('Y-m-d', strtotime($r['disposal_date'])) ?></td>
                            <td><span class="badge bg-light text-dark border px-2"><?= htmlspecialchars($r['voucher_no']) ?></span></td>
                            <td>
                                <?php 
                                $disp = htmlspecialchars($r['how_disposed_of']);
                                if (strtolower($disp) === 'sold') {
                                    echo '<span class="badge bg-success-subtle text-success border px-2"><i class="bi bi-cart-check me-1"></i>Sold</span>';
                                } elseif (strtolower($disp) === 'died') {
                                    echo '<span class="badge bg-danger-subtle text-danger border px-2"><i class="bi bi-x-circle me-1"></i>Died</span>';
                                } elseif (strtolower($disp) === 'transferred') {
                                    echo '<span class="badge bg-info-subtle text-info border px-2"><i class="bi bi-arrow-left-right me-1"></i>Transferred</span>';
                                } else {
                                    echo '<span class="badge bg-secondary-subtle text-dark border px-2">' . $disp . '</span>';
                                }
                                ?>
                            </td>
                            <td class="fw-bold text-primary">
                                <?= ($r['amount_realized'] > 0) ? 'LKR ' . number_format($r['amount_realized'], 2) : '-' ?>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars($r['cash_receipt_info'] ?: '-') ?></td>
                            <td class="fw-bold"><?= intval($r['stud_bulls']) ?></td>
                            <td class="fw-bold"><?= intval($r['draught_bulls']) ?></td>
                            <td class="fw-bold"><?= intval($r['cows']) ?></td>
                            <td class="fw-bold"><?= intval($r['heifer_calves']) ?></td>
                            <td class="fw-bold"><?= intval($r['bull_calves']) ?></td>
                            <td class="fw-bold fs-6 text-danger bg-light"><?= intval($r['total_animals']) ?> Head</td>
                            <td class="small text-start"><?= htmlspecialchars($r['remarks'] ?: '-') ?></td>
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-sm btn-edit-action btn-edit-disposal me-1"
                                    data-id="<?= $r['id'] ?>"
                                    data-date="<?= htmlspecialchars($r['disposal_date']) ?>"
                                    data-voucher="<?= htmlspecialchars($r['voucher_no']) ?>"
                                    data-how="<?= htmlspecialchars($r['how_disposed_of']) ?>"
                                    data-amount="<?= $r['amount_realized'] ?>"
                                    data-receipt="<?= htmlspecialchars($r['cash_receipt_info'] ?? '') ?>"
                                    data-stud="<?= $r['stud_bulls'] ?>"
                                    data-draught="<?= $r['draught_bulls'] ?>"
                                    data-cows="<?= $r['cows'] ?>"
                                    data-heifer="<?= $r['heifer_calves'] ?>"
                                    data-bull="<?= $r['bull_calves'] ?>"
                                    data-remarks="<?= htmlspecialchars($r['remarks'] ?? '') ?>"
                                    data-bs-toggle="modal" data-bs-target="#editAnimalDisposalModal"
                                    title="Edit Entry">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <a href="processors/animal_disposal_crud.php?action=delete&id=<?= $r['id'] ?>&redirect_page=buffalo_register.php" class="btn btn-sm btn-delete-action btn-delete-record" title="Delete Record">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="tfoot-summary fw-bold">
                    <tr>
                        <td colspan="5" class="text-start">TOTAL SUMMARY (<?= count($records) ?> entries)</td>
                        <td><?= number_format($total_stud_bulls) ?></td>
                        <td><?= number_format($total_draught_bulls) ?></td>
                        <td><?= number_format($total_cows) ?></td>
                        <td><?= number_format($total_heifer_calves) ?></td>
                        <td><?= number_format($total_bull_calves) ?></td>
                        <td class="text-danger fs-6"><?= number_format($total_disposed_head) ?> Head</td>
                        <td colspan="2" class="text-primary text-end">LKR <?= number_format($total_revenue_realized, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- MODALS INCLUSION -->
<?php 
include './models/animal_disposal_modals.php'; 
?>

<script>
function applyMonthFilter() {
    var monthVal = document.getElementById('filter_month').value;
    if (monthVal) {
        window.location.href = 'buffalo_register.php?month=' + monthVal;
    } else {
        window.location.href = 'buffalo_register.php?month=all';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Edit Record Handler
    document.querySelectorAll('.btn-edit-disposal').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.getAttribute('data-id');
            document.getElementById('edit_disposal_date').value = this.getAttribute('data-date');
            document.getElementById('edit_voucher_no').value = this.getAttribute('data-voucher');
            
            var howVal = this.getAttribute('data-how');
            var selectHow = document.getElementById('edit_how_disposed_of');
            var otherContainer = document.getElementById('edit_other_disposal_container');
            var otherInput = document.getElementById('edit_how_disposed_other');
            
            var knownOptions = ['Sold', 'Died', 'Transferred', 'Culled'];
            if (knownOptions.indexOf(howVal) !== -1) {
                selectHow.value = howVal;
                otherContainer.style.display = 'none';
                otherInput.value = '';
            } else {
                selectHow.value = 'Other';
                otherContainer.style.display = 'block';
                otherInput.value = howVal;
            }

            document.getElementById('edit_amount_realized').value = this.getAttribute('data-amount');
            document.getElementById('edit_cash_receipt_info').value = this.getAttribute('data-receipt');
            
            document.getElementById('edit_stud_bulls').value = this.getAttribute('data-stud');
            document.getElementById('edit_draught_bulls').value = this.getAttribute('data-draught');
            document.getElementById('edit_cows').value = this.getAttribute('data-cows');
            document.getElementById('edit_heifer_calves').value = this.getAttribute('data-heifer');
            document.getElementById('edit_bull_calves').value = this.getAttribute('data-bull');
            document.getElementById('edit_remarks').value = this.getAttribute('data-remarks');

            var total = (parseInt(this.getAttribute('data-stud'))||0) +
                        (parseInt(this.getAttribute('data-draught'))||0) +
                        (parseInt(this.getAttribute('data-cows'))||0) +
                        (parseInt(this.getAttribute('data-heifer'))||0) +
                        (parseInt(this.getAttribute('data-bull'))||0);
            document.getElementById('edit_total_animals_badge').textContent = 'Total: ' + total + ' Head';
        });
    });

    // Delete Record Handler with SweetAlert
    document.querySelectorAll('.btn-delete-record').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var deleteUrl = this.getAttribute('href');
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete Ledger Entry?',
                    text: "Are you sure you want to delete this Buffalo disposal record?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Delete Record'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = deleteUrl;
                    }
                });
            } else {
                if (confirm("Are you sure you want to delete this Buffalo disposal record?")) {
                    window.location.href = deleteUrl;
                }
            }
        });
    });
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
