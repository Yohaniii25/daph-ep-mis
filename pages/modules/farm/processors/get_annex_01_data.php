<?php
// pages/modules/farm/processors/get_annex_01_data.php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;

$month_param = $_GET['month'] ?? date('Y-m');
$date_parts = explode('-', $month_param);
$year = intval($date_parts[0] ?? date('Y'));
$month = intval($date_parts[1] ?? date('m'));

$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);

// 1. Fetch Active Cages
$cages_res = $mysqli->query("SELECT id, cage_name FROM cages ORDER BY cage_name");
$cages = [];
if ($cages_res) {
    while ($c = $cages_res->fetch_assoc()) {
        $cages[] = $c;
    }
}

// 2. Fetch Egg Production Records for Month
$prod_sql = "SELECT dep.*, b.batch_number AS batch_name
            FROM daily_egg_production dep
            JOIN vaccine_batches b ON dep.batch_id = b.id
            WHERE b.user_id = ? AND YEAR(dep.collection_date) = ? AND MONTH(dep.collection_date) = ?";
$stmt = $mysqli->prepare($prod_sql);
$stmt->bind_param("iii", $user_id, $year, $month);
$stmt->execute();
$prod_res = $stmt->get_result();

$prod_data = []; // [date][cage_id]
if ($prod_res) {
    while ($row = $prod_res->fetch_assoc()) {
        $date_str = $row['collection_date'];
        $cage_id = $row['cage_id'];
        $prod_data[$date_str][$cage_id] = $row;
    }
}
$stmt->close();

// 3. Fetch Daily Sales & Returns Records for Month
$sales_sql = "SELECT * FROM daily_egg_sales_returns WHERE YEAR(record_date) = ? AND MONTH(record_date) = ?";
$stmt = $mysqli->prepare($sales_sql);
$stmt->bind_param("ii", $year, $month);
$stmt->execute();
$sales_res = $stmt->get_result();

$sales_data = []; // [date]
if ($sales_res) {
    while ($row = $sales_res->fetch_assoc()) {
        $sales_data[$row['record_date']] = $row;
    }
}
$stmt->close();

$running_bal_no = 0;
$running_bal_kg = 0.00;
$month_label = date('F Y', strtotime("$year-$month-01"));
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold text-dark m-0">
            <i class="bi bi-journal-check me-2 text-primary"></i>Annex 01 - Egg Register (<?= $month_label ?>)
        </h4>
        <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Edit any cell directly in the table. Totals and Running Balances update live and auto-save!</small>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <span id="annexSaveToast" class="badge bg-success p-2 fs-6 d-none"><i class="bi bi-check-circle-fill me-1"></i>Saved</span>
        <a href="processors/export_annex_01_excel.php?month=<?= $month_param ?>" class="btn btn-success fw-bold px-3">
            <i class="bi bi-file-earmark-excel-fill me-1"></i> Export to Excel
        </a>
    </div>
</div>

<div class="table-responsive shadow-sm rounded border">
    <table id="annexMatrixTable" class="table table-bordered align-middle text-center small mb-0 table-hover bg-white" style="min-width: 1300px;">
        <thead class="table-dark align-middle">
            <tr>
                <th rowspan="2" class="align-middle px-3">DATE</th>
                <th rowspan="2" class="align-middle px-2">TYPE</th>
                <?php foreach ($cages as $cg): ?>
                    <th colspan="2" class="border-start border-end"><?= htmlspecialchars($cg['cage_name']) ?></th>
                <?php endforeach; ?>
                <th colspan="2" class="table-primary text-dark">TOTAL PRODUCTION</th>
                <th colspan="2" class="table-warning text-dark">HATCHERY RETURN</th>
                <th colspan="2" class="table-info text-dark">TOTAL SALES</th>
                <th colspan="2" class="table-success text-dark">BALANCE</th>
            </tr>
            <tr>
                <?php foreach ($cages as $cg): ?>
                    <th class="fw-normal">NO</th>
                    <th class="fw-normal">Kg</th>
                <?php endforeach; ?>
                <th class="table-primary text-dark fw-bold">NO</th>
                <th class="table-primary text-dark fw-bold">Kg</th>
                <th class="table-warning text-dark fw-bold">NO</th>
                <th class="table-warning text-dark fw-bold">Kg</th>
                <th class="table-info text-dark fw-bold">NO</th>
                <th class="table-info text-dark fw-bold">Kg</th>
                <th class="table-success text-dark fw-bold">NO</th>
                <th class="table-success text-dark fw-bold">Kg</th>
            </tr>
        </thead>
        <tbody>
            <?php for ($day = 1; $day <= $days_in_month; $day++): 
                $date_str = sprintf("%04d-%02d-%02d", $year, $month, $day);
                $formatted_date = date('d-M-Y', strtotime($date_str));
                
                $sales_row = $sales_data[$date_str] ?? null;
                $ret_no = intval($sales_row['hatchery_return_no'] ?? 0);
                $ret_kg = floatval($sales_row['hatchery_return_kg'] ?? 0.00);
                $sale_no = intval($sales_row['total_sales_no'] ?? 0);
                $sale_kg = floatval($sales_row['total_sales_kg'] ?? 0.00);

                $types = [
                    'Hat'   => ['label' => 'Hat', 'field_no' => 'hatchable_eggs', 'field_kg' => 'hatchable_eggs_kg', 'bg' => 'bg-white'],
                    'T/E'   => ['label' => 'T/E', 'field_no' => 'table_eggs', 'field_kg' => 'table_eggs_kg', 'bg' => 'bg-light'],
                    'C/E'   => ['label' => 'C/E', 'field_no' => 'cracked_eggs', 'field_kg' => 'cracked_eggs_kg', 'bg' => 'bg-white'],
                    'Total' => ['label' => 'Total', 'field_no' => 'total_eggs', 'field_kg' => 'total_eggs_kg', 'bg' => 'table-active fw-bold']
                ];

                // Calculate Day Total Production
                $day_tot_prod_no = 0;
                $day_tot_prod_kg = 0.00;
                foreach ($cages as $cg) {
                    $record = $prod_data[$date_str][$cg['id']] ?? null;
                    if ($record) {
                        $day_tot_prod_no += intval($record['total_eggs'] ?? 0);
                        $day_tot_prod_kg += floatval($record['total_eggs_kg'] ?? 0);
                    }
                }

                // Update Running Balance for Total row
                $running_bal_no = $running_bal_no + $day_tot_prod_no + $ret_no - $sale_no;
                $running_bal_kg = round($running_bal_kg + $day_tot_prod_kg + $ret_kg - $sale_kg, 2);
            ?>
                <?php foreach ($types as $t_key => $t_info): 
                    $row_tot_no = 0;
                    $row_tot_kg = 0.00;
                ?>
                    <tr class="<?= $t_info['bg'] ?>" data-date="<?= $date_str ?>" data-row-type="<?= $t_key ?>">
                        <?php if ($t_key === 'Hat'): ?>
                            <td rowspan="4" class="align-middle fw-bold text-dark bg-white border-end date-col"><?= $formatted_date ?></td>
                        <?php endif; ?>
                        
                        <td class="fw-bold <?= ($t_key === 'Total') ? 'text-success' : 'text-muted' ?>"><?= $t_info['label'] ?></td>

                        <!-- Cage Columns (Editable for Hat, T/E, C/E) -->
                        <?php foreach ($cages as $cg): 
                            $c_rec = $prod_data[$date_str][$cg['id']] ?? null;
                            $c_no = intval($c_rec[$t_info['field_no']] ?? 0);
                            $c_kg = floatval($c_rec[$t_info['field_kg']] ?? 0.00);
                            $row_tot_no += $c_no;
                            $row_tot_kg += $c_kg;
                        ?>
                            <?php if ($t_key === 'Total'): ?>
                                <td id="cage_tot_no_<?= $date_str ?>_<?= $cg['id'] ?>" class="fw-bold text-primary"><?= number_format($c_no) ?></td>
                                <td id="cage_tot_kg_<?= $date_str ?>_<?= $cg['id'] ?>" class="fw-bold text-primary"><?= number_format($c_kg, 2) ?></td>
                            <?php else: ?>
                                <td class="p-0">
                                    <input type="number" 
                                           class="form-control form-control-sm text-center border-0 bg-transparent annex-cell-edit" 
                                           data-date="<?= $date_str ?>" 
                                           data-cage-id="<?= $cg['id'] ?>" 
                                           data-row-type="<?= $t_key ?>" 
                                           data-field-type="no" 
                                           value="<?= $c_no ?>" 
                                           min="0"
                                           style="width: 65px; margin: 0 auto;">
                                </td>
                                <td class="p-0">
                                    <input type="number" 
                                           class="form-control form-control-sm text-center border-0 bg-transparent annex-cell-edit" 
                                           data-date="<?= $date_str ?>" 
                                           data-cage-id="<?= $cg['id'] ?>" 
                                           data-row-type="<?= $t_key ?>" 
                                           data-field-type="kg" 
                                           value="<?= number_format($c_kg, 2, '.', '') ?>" 
                                           step="0.01" min="0"
                                           style="width: 75px; margin: 0 auto;">
                                </td>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <!-- Total Production Across Cages -->
                        <td id="tot_prod_no_<?= $date_str ?>_<?= $t_key ?>" class="table-primary text-dark fw-bold tot-prod-no-cell"><?= number_format($row_tot_no) ?></td>
                        <td id="tot_prod_kg_<?= $date_str ?>_<?= $t_key ?>" class="table-primary text-dark fw-bold tot-prod-kg-cell"><?= number_format($row_tot_kg, 2) ?></td>

                        <!-- Hatchery Returns (Editable on Total Row) -->
                        <?php if ($t_key === 'Total'): ?>
                            <td class="table-warning text-dark p-0">
                                <input type="number" 
                                       class="form-control form-control-sm text-center border-0 bg-transparent fw-bold annex-sales-edit" 
                                       data-date="<?= $date_str ?>" 
                                       data-field-name="hatchery_return_no" 
                                       value="<?= $ret_no ?>" 
                                       min="0"
                                       style="width: 65px; margin: 0 auto;">
                            </td>
                            <td class="table-warning text-dark p-0">
                                <input type="number" 
                                       class="form-control form-control-sm text-center border-0 bg-transparent fw-bold annex-sales-edit" 
                                       data-date="<?= $date_str ?>" 
                                       data-field-name="hatchery_return_kg" 
                                       value="<?= number_format($ret_kg, 2, '.', '') ?>" 
                                       step="0.01" min="0"
                                       style="width: 75px; margin: 0 auto;">
                            </td>

                            <!-- Total Sales (Editable on Total Row) -->
                            <td class="table-info text-dark p-0">
                                <input type="number" 
                                       class="form-control form-control-sm text-center border-0 bg-transparent fw-bold annex-sales-edit" 
                                       data-date="<?= $date_str ?>" 
                                       data-field-name="total_sales_no" 
                                       value="<?= $sale_no ?>" 
                                       min="0"
                                       style="width: 65px; margin: 0 auto;">
                            </td>
                            <td class="table-info text-dark p-0">
                                <input type="number" 
                                       class="form-control form-control-sm text-center border-0 bg-transparent fw-bold annex-sales-edit" 
                                       data-date="<?= $date_str ?>" 
                                       data-field-name="total_sales_kg" 
                                       value="<?= number_format($sale_kg, 2, '.', '') ?>" 
                                       step="0.01" min="0"
                                       style="width: 75px; margin: 0 auto;">
                            </td>

                            <!-- Running Balance (Dynamic) -->
                            <td id="bal_no_<?= $date_str ?>" class="table-success text-dark fw-bold bal-no-cell" data-date="<?= $date_str ?>"><?= number_format($running_bal_no) ?></td>
                            <td id="bal_kg_<?= $date_str ?>" class="table-success text-dark fw-bold bal-kg-cell" data-date="<?= $date_str ?>"><?= number_format($running_bal_kg, 2) ?></td>
                        <?php else: ?>
                            <td class="table-warning text-dark">-</td>
                            <td class="table-warning text-dark">-</td>
                            <td class="table-info text-dark">-</td>
                            <td class="table-info text-dark">-</td>
                            <td class="table-success text-dark">-</td>
                            <td class="table-success text-dark">-</td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endfor; ?>
        </tbody>
    </table>
</div>

<script>
(function() {
    var cages = <?= json_encode($cages) ?>;
    var daysInMonth = <?= $days_in_month ?>;
    var year = <?= $year ?>;
    var month = <?= $month ?>;

    // Recalculates Totals & Chronological Balances live
    function recalcAnnexGrid() {
        var runningBalNo = 0;
        var runningBalKg = 0.00;

        for (var day = 1; day <= daysInMonth; day++) {
            var dayPad = String(day).padStart(2, '0');
            var monthPad = String(month).padStart(2, '0');
            var dateStr = year + '-' + monthPad + '-' + dayPad;

            // Recalculate each row type (Hat, T/E, C/E)
            var rowTypes = ['Hat', 'T/E', 'C/E'];
            var dayTotProdNo = 0;
            var dayTotProdKg = 0.00;

            // Track per-cage sum for the Total row
            var cageTotalsNo = {};
            var cageTotalsKg = {};
            cages.forEach(function(cg) {
                cageTotalsNo[cg.id] = 0;
                cageTotalsKg[cg.id] = 0.00;
            });

            rowTypes.forEach(function(rType) {
                var rowNoSum = 0;
                var rowKgSum = 0.00;

                cages.forEach(function(cg) {
                    var $noInput = $('.annex-cell-edit[data-date="' + dateStr + '"][data-cage-id="' + cg.id + '"][data-row-type="' + rType + '"][data-field-type="no"]');
                    var $kgInput = $('.annex-cell-edit[data-date="' + dateStr + '"][data-cage-id="' + cg.id + '"][data-row-type="' + rType + '"][data-field-type="kg"]');

                    var valNo = parseInt($noInput.val()) || 0;
                    var valKg = parseFloat($kgInput.val()) || 0.00;

                    rowNoSum += valNo;
                    rowKgSum += valKg;

                    cageTotalsNo[cg.id] += valNo;
                    cageTotalsKg[cg.id] += valKg;
                });

                // Update Total Production for row
                $('#tot_prod_no_' + dateStr + '_' + rType).text(rowNoSum.toLocaleString());
                $('#tot_prod_kg_' + dateStr + '_' + rType).text(rowKgSum.toFixed(2));

                dayTotProdNo += rowNoSum;
                dayTotProdKg += rowKgSum;
            });

            // Update Total row per cage
            cages.forEach(function(cg) {
                $('#cage_tot_no_' + dateStr + '_' + cg.id).text(cageTotalsNo[cg.id].toLocaleString());
                $('#cage_tot_kg_' + dateStr + '_' + cg.id).text(cageTotalsKg[cg.id].toFixed(2));
            });

            // Update Day Total Production Total Row
            $('#tot_prod_no_' + dateStr + '_Total').text(dayTotProdNo.toLocaleString());
            $('#tot_prod_kg_' + dateStr + '_Total').text(dayTotProdKg.toFixed(2));

            // Get Return and Sales for Day
            var retNo = parseInt($('.annex-sales-edit[data-date="' + dateStr + '"][data-field-name="hatchery_return_no"]').val()) || 0;
            var retKg = parseFloat($('.annex-sales-edit[data-date="' + dateStr + '"][data-field-name="hatchery_return_kg"]').val()) || 0.00;
            var saleNo = parseInt($('.annex-sales-edit[data-date="' + dateStr + '"][data-field-name="total_sales_no"]').val()) || 0;
            var saleKg = parseFloat($('.annex-sales-edit[data-date="' + dateStr + '"][data-field-name="total_sales_kg"]').val()) || 0.00;

            // Accumulate Running Balance
            runningBalNo = runningBalNo + dayTotProdNo + retNo - saleNo;
            runningBalKg = Math.round((runningBalKg + dayTotProdKg + retKg - saleKg) * 100) / 100;

            // Update Balance DOM cells
            $('#bal_no_' + dateStr).text(runningBalNo.toLocaleString());
            $('#bal_kg_' + dateStr).text(runningBalKg.toFixed(2));
        }
    }

    function showToast() {
        $('#annexSaveToast').removeClass('d-none').fadeIn();
        setTimeout(function() {
            $('#annexSaveToast').fadeOut(function() {
                $(this).addClass('d-none');
            });
        }, 2000);
    }

    // Live calculation on input
    $(document).off('input', '.annex-cell-edit, .annex-sales-edit').on('input', '.annex-cell-edit, .annex-sales-edit', function() {
        recalcAnnexGrid();
    });

    // Auto-save cage cell on change
    $(document).off('change', '.annex-cell-edit').on('change', '.annex-cell-edit', function() {
        var $input = $(this);
        var date = $input.data('date');
        var cageId = $input.data('cage-id');
        var rowType = $input.data('row-type');
        var fieldType = $input.data('field-type');
        var val = $input.val();

        $input.addClass('bg-warning-subtle');

        $.ajax({
            url: 'processors/update_annex_cell.php',
            type: 'POST',
            data: {
                target_type: 'cage_entry',
                date: date,
                cage_id: cageId,
                row_type: rowType,
                field_type: fieldType,
                val: val
            },
            dataType: 'json',
            success: function(res) {
                $input.removeClass('bg-warning-subtle');
                if (res.success) {
                    $input.addClass('bg-success-subtle');
                    setTimeout(function() { $input.removeClass('bg-success-subtle'); }, 1000);
                    showToast();
                } else {
                    Swal.fire('Update Failed', res.message, 'error');
                }
            },
            error: function() {
                $input.removeClass('bg-warning-subtle');
                Swal.fire('Error', 'Failed to save changes. Please try again.', 'error');
            }
        });
    });

    // Auto-save sales & returns cell on change
    $(document).off('change', '.annex-sales-edit').on('change', '.annex-sales-edit', function() {
        var $input = $(this);
        var date = $input.data('date');
        var fieldName = $input.data('field-name');
        var val = $input.val();

        $input.addClass('bg-warning-subtle');

        $.ajax({
            url: 'processors/update_annex_cell.php',
            type: 'POST',
            data: {
                target_type: 'sales_returns',
                date: date,
                field_name: fieldName,
                val: val
            },
            dataType: 'json',
            success: function(res) {
                $input.removeClass('bg-warning-subtle');
                if (res.success) {
                    $input.addClass('bg-success-subtle');
                    setTimeout(function() { $input.removeClass('bg-success-subtle'); }, 1000);
                    showToast();
                } else {
                    Swal.fire('Update Failed', res.message, 'error');
                }
            },
            error: function() {
                $input.removeClass('bg-warning-subtle');
                Swal.fire('Error', 'Failed to save changes. Please try again.', 'error');
            }
        });
    });

})();
</script>
