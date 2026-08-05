<?php
// pages/modules/farm/inventory_register.php -> Monthly Livestock Inventory Summary Matrix
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['role'])) {
    header("Location: ../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? 1;

// Selected filter month (default to current month YYYY-MM)
$selected_month = $_GET['month'] ?? date('Y-m');
$first_day = date('Y-m-01', strtotime($selected_month . '-01'));
$last_day = date('Y-m-t', strtotime($selected_month . '-01'));
$first_day_formatted = date('01.m.Y', strtotime($selected_month . '-01'));
$last_day_formatted = date('t.m.Y', strtotime($selected_month . '-01'));

$prev_month = date('Y-m', strtotime($selected_month . '-01 -1 month'));

// 1. Fetch Dynamic Poultry Batches
$poultry_batches = [];
$batch_res = $mysqli->query("SELECT id, batch_number FROM vaccine_batches WHERE user_id = $user_id ORDER BY id ASC");
if ($batch_res && $batch_res->num_rows > 0) {
    while ($row = $batch_res->fetch_assoc()) {
        $poultry_batches[] = $row;
    }
} else {
    // Default reference batches if none exist yet
    $poultry_batches = [
        ['id' => 1, 'batch_number' => 'CPRS-03'],
        ['id' => 2, 'batch_number' => 'Kadaknath-10']
    ];
}

// 2. Define Category Structure
$animal_groups = [
    'Cattle' => [
        'cattle_stud_bulls' => 'Stud Bulls',
        'cattle_cows' => 'Cows',
        'cattle_heifers' => 'Heifers',
        'cattle_calves_male' => 'Calves (Male)',
        'cattle_calves_female' => 'Calves (Female)',
    ],
    'Goat' => [
        'goat_stud_goats' => 'Stud Goats',
        'goat_he_goats' => 'He Goats',
        'goat_she_goats' => 'She Goats',
        'goat_kids_male' => 'Kids (Male)',
        'goat_kids_female' => 'Kids (Female)',
    ],
    'Buffalo' => [
        'buffalo_stud_bulls' => 'Stud Bulls',
        'buffalo_cows' => 'Cows',
        'buffalo_heifers' => 'Heifers',
        'buffalo_calves_male' => 'Calves (Male)',
        'buffalo_calves_female' => 'Calves (Female)',
    ],
];

// Add dynamic poultry batches to categories
foreach ($poultry_batches as $pb) {
    $clean_bname = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($pb['batch_number']));
    $group_name = 'Poultry (' . htmlspecialchars($pb['batch_number']) . ')';
    $animal_groups[$group_name] = [
        'poultry_' . $clean_bname . '_pullets' => 'Pullets',
        'poultry_' . $clean_bname . '_cockerels' => 'Cockerels',
    ];
}

// Flatten categories array for easy processing
$all_categories = [];
foreach ($animal_groups as $g_name => $cols) {
    foreach ($cols as $col_key => $col_label) {
        $all_categories[$col_key] = [
            'group' => $g_name,
            'label' => $col_label
        ];
    }
}

// 3. Define Statically Defined Row Particulars
$particulars = [
    'opening_balance' => ['label' => "Balance on {$first_day_formatted} (Opening Balance)", 'type' => 'number', 'is_calc' => false],
    'on_hand'         => ['label' => 'On hand', 'type' => 'number', 'is_calc' => false],
    'received'        => ['label' => 'Received', 'type' => 'number', 'is_calc' => false],
    'transfers'       => ['label' => 'Transfers', 'type' => 'number', 'is_calc' => false],
    'births'          => ['label' => 'Births', 'type' => 'number', 'is_calc' => false],
    'action_header'   => ['label' => 'Action', 'type' => 'header', 'is_calc' => false],
    'sold_no'         => ['label' => 'Sold: (No.)', 'type' => 'number', 'is_calc' => false],
    'sold_kg'         => ['label' => 'Sold: (Amount Kg)', 'type' => 'decimal', 'is_calc' => false],
    'sold_rs'         => ['label' => 'Sold: (Value Rs.)', 'type' => 'currency', 'is_calc' => false],
    'missing'         => ['label' => 'Missing', 'type' => 'number', 'is_calc' => false],
    'deaths'          => ['label' => 'Deaths', 'type' => 'number', 'is_calc' => false],
    'closing_balance' => ['label' => "Balance on {$last_day_formatted} (Closing Balance)", 'type' => 'number', 'is_calc' => true],
];

// 4. Fetch Stored Current Month Matrix Values
$stored_matrix = [];
$res_curr = $mysqli->query("SELECT particular_key, category_key, value_num FROM livestock_monthly_inventory WHERE user_id = $user_id AND month_year = '$selected_month'");
if ($res_curr) {
    while ($r = $res_curr->fetch_assoc()) {
        $stored_matrix[$r['particular_key']][$r['category_key']] = floatval($r['value_num']);
    }
}

// 5. Fetch Previous Month's Closing Balance for Auto Opening Balance
$prev_closing_balances = [];
$res_prev = $mysqli->query("SELECT category_key, value_num FROM livestock_monthly_inventory WHERE user_id = $user_id AND month_year = '$prev_month' AND particular_key = 'closing_balance'");
if ($res_prev) {
    while ($r = $res_prev->fetch_assoc()) {
        $prev_closing_balances[$r['category_key']] = floatval($r['value_num']);
    }
}

// 6. Aggregate Data from animal_disposal_register for the current month
$aggregated_disposals = [];
$disp_sql = "SELECT species, how_disposed_of, 
                    SUM(stud_bulls) as sum_stud, 
                    SUM(draught_bulls) as sum_draught, 
                    SUM(cows) as sum_cows, 
                    SUM(heifer_calves) as sum_heifers, 
                    SUM(bull_calves) as sum_calves_male,
                    SUM(amount_realized) as sum_realized
             FROM animal_disposal_register 
             WHERE disposal_date BETWEEN '$first_day' AND '$last_day'
             GROUP BY species, how_disposed_of";
$disp_res = $mysqli->query($disp_sql);

if ($disp_res) {
    while ($r = $disp_res->fetch_assoc()) {
        $spec = strtolower($r['species']);
        $how  = strtolower($r['how_disposed_of']);

        $prefix = '';
        if ($spec === 'cattle' || $spec === 'white cattle') {
            $prefix = 'cattle_';
        } elseif ($spec === 'goat') {
            $prefix = 'goat_';
        } elseif ($spec === 'buffalo') {
            $prefix = 'buffalo_';
        }

        if (!empty($prefix)) {
            $map = [
                $prefix . 'stud_bulls'      => intval($r['sum_stud']),
                $prefix . ($spec === 'goat' ? 'he_goats' : 'cows') => intval($r['sum_cows']),
                $prefix . ($spec === 'goat' ? 'she_goats' : 'heifers') => intval($r['sum_heifers']),
                $prefix . ($spec === 'goat' ? 'kids_male' : 'calves_male') => intval($r['sum_calves_male']),
                $prefix . ($spec === 'goat' ? 'kids_female' : 'calves_female') => intval($r['sum_draught']),
            ];

            if ($how === 'sold') {
                foreach ($map as $ckey => $cval) {
                    $aggregated_disposals['sold_no'][$ckey] = ($aggregated_disposals['sold_no'][$ckey] ?? 0) + $cval;
                }
                $aggregated_disposals['sold_rs'][$prefix . 'cows'] = ($aggregated_disposals['sold_rs'][$prefix . 'cows'] ?? 0) + floatval($r['sum_realized']);
            } elseif ($how === 'died') {
                foreach ($map as $ckey => $cval) {
                    $aggregated_disposals['deaths'][$ckey] = ($aggregated_disposals['deaths'][$ckey] ?? 0) + $cval;
                }
            } elseif ($how === 'transferred') {
                foreach ($map as $ckey => $cval) {
                    $aggregated_disposals['transfers'][$ckey] = ($aggregated_disposals['transfers'][$ckey] ?? 0) + $cval;
                }
            }
        }
    }
}
?>

<link rel="stylesheet" href="../../../assets/css/farm.css">

<style>
    .matrix-table th, .matrix-table td {
        vertical-align: middle;
        text-align: center;
        padding: 6px 8px;
        font-size: 0.85rem;
    }
    .matrix-header-group {
        background-color: #370709 !important;
        color: #ffffff !important;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #820100 !important;
    }
    .matrix-header-sub {
        background-color: #4a0c0e !important;
        color: #f8f9fa !important;
        font-size: 0.8rem;
    }
    .matrix-particular-row {
        text-align: left !important;
        font-weight: 600;
        background-color: #f8fafc;
        color: #212529;
        min-width: 230px;
        position: sticky;
        left: 0;
        z-index: 2;
        border-right: 2px solid #dee2e6 !important;
    }
    .matrix-input {
        width: 75px;
        text-align: center;
        font-weight: 600;
        padding: 4px 6px;
        font-size: 0.85rem;
        border: 1px solid #ced4da;
        border-radius: 4px;
        transition: all 0.2s ease-in-out;
    }
    .matrix-input:focus {
        border-color: #820100;
        box-shadow: 0 0 0 0.2rem rgba(130, 1, 0, 0.25);
    }
    .matrix-calc-cell {
        background-color: #e9ecef !important;
        font-weight: 700 !important;
        color: #820100 !important;
    }
    .matrix-section-header {
        background-color: #e2e8f0 !important;
        font-weight: 700;
        color: #1e293b;
        text-align: left !important;
    }
</style>

<!-- Header & Month Filter Section -->
<div class="row align-items-center mb-4">
    <div class="col-md-6">
        <h3 class="fw-bold text-dark m-0">
            <i class="bi bi-boxes me-2" style="color: #820100;"></i>Monthly Livestock Inventory Summary
        </h3>
        <p class="text-muted mb-0 small">Official ledger matrix for tracking monthly opening & closing balances across all species.</p>
    </div>
    <div class="col-md-6 text-end d-flex justify-content-end align-items-center gap-2">
        <label class="fw-bold mb-0 text-nowrap small"><i class="bi bi-calendar3 me-1"></i>Month / Year:</label>
        <input type="month" id="matrix_month_filter" class="form-control form-control-sm w-auto shadow-sm" value="<?= $selected_month ?>">
        <button type="button" class="btn btn-sm btn-apply-filter px-3 fw-bold" onclick="applyMatrixMonthFilter()">
            <i class="bi bi-funnel me-1"></i>Load Matrix
        </button>
    </div>
</div>

<!-- SweetAlert Notifications -->
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

<!-- MONTHLY LIVESTOCK INVENTORY SUMMARY MATRIX CARD -->
<form action="processors/save_livestock_monthly_summary.php" method="POST" id="matrixForm">
    <input type="hidden" name="month_year" value="<?= htmlspecialchars($selected_month) ?>">

    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold text-dark m-0">
                    <i class="bi bi-table me-2" style="color: #820100;"></i>Monthly Summary Matrix (<?= date('F Y', strtotime($selected_month . '-01')) ?>)
                </h5>
                <small class="text-muted">Opening/Closing balances & dynamic tracking across Cattle, Goat, Buffalo & Poultry batches.</small>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>Print
                </button>
                <button type="submit" class="btn btn-sm text-light fw-bold px-4 shadow-sm" style="background-color: #820100;">
                    <i class="bi bi-save me-1"></i>Save Monthly Summary
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive" style="overflow-x: auto; max-width: 100%;">
                <table class="table table-bordered align-middle matrix-table mb-0" id="matrixTable">
                    <thead>
                        <!-- TOP ROW: GROUP HEADERS -->
                        <tr>
                            <th rowspan="2" class="matrix-particular-row bg-white text-dark align-middle">Particulars</th>
                            <?php foreach ($animal_groups as $group_name => $cols): ?>
                                <th colspan="<?= count($cols) ?>" class="matrix-header-group">
                                    <?= htmlspecialchars($group_name) ?>
                                </th>
                            <?php endforeach; ?>
                            <th rowspan="2" class="matrix-header-group bg-dark text-white align-middle px-3">Grand Total Head</th>
                        </tr>

                        <!-- SECOND ROW: SUB COLUMNS -->
                        <tr>
                            <?php foreach ($animal_groups as $group_name => $cols): ?>
                                <?php foreach ($cols as $col_key => $col_label): ?>
                                    <th class="matrix-header-sub">
                                        <?= htmlspecialchars($col_label) ?>
                                    </th>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($particulars as $part_key => $part_info): ?>
                            <?php if ($part_info['type'] === 'header'): ?>
                                <!-- SECTION DIVIDER HEADER -->
                                <tr>
                                    <td colspan="<?= count($all_categories) + 2 ?>" class="matrix-section-header py-2 px-3">
                                        <i class="bi bi-gear-fill me-2" style="color: #820100;"></i><?= htmlspecialchars($part_info['label']) ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td class="matrix-particular-row">
                                        <?= htmlspecialchars($part_info['label']) ?>
                                    </td>

                                    <?php foreach ($all_categories as $col_key => $col_info): ?>
                                        <?php
                                        // Determine initial value
                                        $val = 0;
                                        if (isset($stored_matrix[$part_key][$col_key])) {
                                            $val = $stored_matrix[$part_key][$col_key];
                                        } elseif ($part_key === 'opening_balance' && isset($prev_closing_balances[$col_key])) {
                                            $val = $prev_closing_balances[$col_key];
                                        } elseif (isset($aggregated_disposals[$part_key][$col_key])) {
                                            $val = $aggregated_disposals[$part_key][$col_key];
                                        }
                                        ?>

                                        <?php if ($part_info['is_calc']): ?>
                                            <!-- CALCULATED CELL -->
                                            <td class="matrix-calc-cell">
                                                <span id="calc_<?= $part_key ?>_<?= $col_key ?>" class="calc-val">0</span>
                                                <input type="hidden" name="matrix[<?= $part_key ?>][<?= $col_key ?>]" id="input_<?= $part_key ?>_<?= $col_key ?>" value="<?= $val ?>">
                                            </td>
                                        <?php else: ?>
                                            <!-- EDITABLE INPUT CELL -->
                                            <td>
                                                <input type="number" 
                                                       step="<?= ($part_info['type'] === 'decimal' || $part_info['type'] === 'currency') ? '0.01' : '1' ?>" 
                                                       class="matrix-input matrix-cell-input" 
                                                       data-part="<?= $part_key ?>" 
                                                       data-col="<?= $col_key ?>" 
                                                       name="matrix[<?= $part_key ?>][<?= $col_key ?>]" 
                                                       value="<?= $val ?>" 
                                                       onfocus="this.select();">
                                            </td>
                                        <?php endif; ?>
                                    <?php endforeach; ?>

                                    <!-- ROW GRAND TOTAL CELL -->
                                    <td class="bg-light fw-bold text-dark" id="row_total_<?= $part_key ?>">
                                        0
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>Closing Balance Formula: <code>(Opening Balance + Received + Births) - (Transfers + Sold [No.] + Missing + Deaths)</code>.
            </small>
            <button type="submit" class="btn text-light fw-bold px-4 shadow-sm" style="background-color: #820100;">
                <i class="bi bi-check-circle me-1"></i>Save & Commit Matrix
            </button>
        </div>
    </div>
</form>

<script>
function applyMatrixMonthFilter() {
    var monthVal = document.getElementById('matrix_month_filter').value;
    if (monthVal) {
        window.location.href = 'inventory_register.php?month=' + monthVal;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const categories = <?= json_encode(array_keys($all_categories)) ?>;
    const particulars = ['opening_balance', 'on_hand', 'received', 'transfers', 'births', 'sold_no', 'sold_kg', 'sold_rs', 'missing', 'deaths', 'closing_balance'];

    function getCellValue(part, col) {
        if (part === 'closing_balance') {
            const inputHidden = document.getElementById('input_closing_balance_' + col);
            return inputHidden ? parseFloat(inputHidden.value) || 0 : 0;
        } else {
            const input = document.querySelector(`input[data-part="${part}"][data-col="${col}"]`);
            return input ? parseFloat(input.value) || 0 : 0;
        }
    }

    function calculateMatrix() {
        categories.forEach(function(col) {
            const opening  = getCellValue('opening_balance', col);
            const received = getCellValue('received', col);
            const births   = getCellValue('births', col);
            const transfers= getCellValue('transfers', col);
            const soldNo   = getCellValue('sold_no', col);
            const missing  = getCellValue('missing', col);
            const deaths   = getCellValue('deaths', col);

            // Formula: (Opening + Received + Births) - (Transfers + Sold [No.] + Missing + Deaths)
            const closing = (opening + received + births) - (transfers + soldNo + missing + deaths);

            // Update UI & hidden input
            const calcSpan = document.getElementById('calc_closing_balance_' + col);
            const hiddenInput = document.getElementById('input_closing_balance_' + col);

            if (calcSpan) {
                calcSpan.textContent = closing.toLocaleString();
            }
            if (hiddenInput) {
                hiddenInput.value = closing;
            }
        });

        // Update Row Totals
        particulars.forEach(function(part) {
            let rowSum = 0;
            categories.forEach(function(col) {
                rowSum += getCellValue(part, col);
            });

            const rowTotalTd = document.getElementById('row_total_' + part);
            if (rowTotalTd) {
                if (part === 'sold_rs') {
                    rowTotalTd.textContent = 'Rs. ' + rowSum.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                } else if (part === 'sold_kg') {
                    rowTotalTd.textContent = rowSum.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' kg';
                } else {
                    rowTotalTd.textContent = rowSum.toLocaleString();
                }
            }
        });
    }

    // Attach listeners to all inputs
    document.querySelectorAll('.matrix-cell-input').forEach(function(input) {
        input.addEventListener('input', calculateMatrix);
        input.addEventListener('change', calculateMatrix);
    });

    // Initial calculation on page load
    calculateMatrix();
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
