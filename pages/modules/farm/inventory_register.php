<?php
// pages/modules/farm/inventory_register.php -> Monthly Livestock Inventory Summary Matrix
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['role'])) {
    header("Location: ../../../index.php");
    exit();
}

$user_id = intval($_SESSION['user_id'] ?? 1);

// Selected filter month (default to current month YYYY-MM)
$selected_month    = $_GET['month'] ?? date('Y-m');
$first_day         = date('Y-m-01', strtotime($selected_month . '-01'));
$last_day          = date('Y-m-t',  strtotime($selected_month . '-01'));
$first_day_formatted = date('01.m.Y', strtotime($selected_month . '-01'));
$last_day_formatted  = date('t.m.Y',  strtotime($selected_month . '-01'));
$prev_month = date('Y-m', strtotime($selected_month . '-01 -1 month'));

// ---------------------------------------------------------------
// 1. Fetch Dynamic Poultry Batches
// ---------------------------------------------------------------
$poultry_batches = [];
$batch_res = $mysqli->query("SELECT id, batch_number FROM vaccine_batches WHERE user_id = $user_id ORDER BY id ASC");
if ($batch_res && $batch_res->num_rows > 0) {
    while ($row = $batch_res->fetch_assoc()) $poultry_batches[] = $row;
} else {
    $poultry_batches = [
        ['id' => 1, 'batch_number' => 'CPRS-03'],
        ['id' => 2, 'batch_number' => 'Kadaknath-10'],
    ];
}

// ---------------------------------------------------------------
// 2. Define Category / Group Structure
// ---------------------------------------------------------------
$animal_groups = [
    'Cattle' => [
        'cattle_stud_bulls'    => 'Stud Bulls',
        'cattle_cows'          => 'Cows',
        'cattle_heifers'       => 'Heifers',
        'cattle_calves_male'   => 'Calves (Male)',
        'cattle_calves_female' => 'Calves (Female)',
    ],
    'Goat' => [
        'goat_stud_goats'  => 'Stud Goats',
        'goat_he_goats'    => 'He Goats',
        'goat_she_goats'   => 'She Goats',
        'goat_kids_male'   => 'Kids (Male)',
        'goat_kids_female' => 'Kids (Female)',
    ],
    'Buffalo' => [
        'buffalo_stud_bulls'    => 'Stud Bulls',
        'buffalo_cows'          => 'Cows',
        'buffalo_heifers'       => 'Heifers',
        'buffalo_calves_male'   => 'Calves (Male)',
        'buffalo_calves_female' => 'Calves (Female)',
    ],
];

foreach ($poultry_batches as $pb) {
    $clean_bname  = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($pb['batch_number']));
    $group_name   = 'Poultry (' . htmlspecialchars($pb['batch_number']) . ')';
    $animal_groups[$group_name] = [
        'poultry_' . $clean_bname . '_pullets'   => 'Pullets',
        'poultry_' . $clean_bname . '_cockerels' => 'Cockerels',
    ];
}

// Flatten categories
$all_categories = [];
foreach ($animal_groups as $g_name => $cols) {
    foreach ($cols as $col_key => $col_label) {
        $all_categories[$col_key] = ['group' => $g_name, 'label' => $col_label];
    }
}

// ---------------------------------------------------------------
// 3. Row Particulars — is_auto marks auto-fetched rows
// ---------------------------------------------------------------
$particulars = [
    'opening_balance' => [
        'label'       => "Balance on {$first_day_formatted} (Opening Balance)",
        'type'        => 'number',
        'is_calc'     => false,
        'is_auto'     => true,
        'auto_source' => 'Auto-filled from previous month\'s closing balance',
    ],
    'on_hand' => [
        'label'       => 'On hand',
        'type'        => 'number',
        'is_calc'     => false,
        'is_auto'     => false,
        'auto_source' => '',
    ],
    'received' => [
        'label'       => 'Received',
        'type'        => 'number',
        'is_calc'     => false,
        'is_auto'     => false,
        'auto_source' => '',
    ],
    'transfers' => [
        'label'       => 'Transfers',
        'type'        => 'number',
        'is_calc'     => false,
        'is_auto'     => true,
        'auto_source' => 'Auto-fetched from Disposal Registers (Transferred entries)',
    ],
    'births' => [
        'label'       => 'Births',
        'type'        => 'number',
        'is_calc'     => false,
        'is_auto'     => false,
        'auto_source' => '',
    ],
    'action_header' => [
        'label'       => 'Action',
        'type'        => 'header',
        'is_calc'     => false,
        'is_auto'     => false,
        'auto_source' => '',
    ],
    'sold_no' => [
        'label'       => 'Sold: (No.)',
        'type'        => 'number',
        'is_calc'     => false,
        'is_auto'     => true,
        'auto_source' => 'Auto-fetched from Disposal Registers (Sold entries — head count)',
    ],
    'sold_kg' => [
        'label'       => 'Sold: (Amount Kg)',
        'type'        => 'decimal',
        'is_calc'     => false,
        'is_auto'     => false,
        'auto_source' => '',
    ],
    'sold_rs' => [
        'label'       => 'Sold: (Value Rs.)',
        'type'        => 'currency',
        'is_calc'     => false,
        'is_auto'     => true,
        'auto_source' => 'Auto-fetched from Disposal Registers (Amount Realized per species)',
    ],
    'missing' => [
        'label'       => 'Missing',
        'type'        => 'number',
        'is_calc'     => false,
        'is_auto'     => false,
        'auto_source' => '',
    ],
    'deaths' => [
        'label'       => 'Deaths',
        'type'        => 'number',
        'is_calc'     => false,
        'is_auto'     => true,
        'auto_source' => 'Auto-fetched from Disposal Registers (Died entries)',
    ],
    'closing_balance' => [
        'label'       => "Balance on {$last_day_formatted} (Closing Balance)",
        'type'        => 'number',
        'is_calc'     => true,
        'is_auto'     => false,
        'auto_source' => '',
    ],
];

// ---------------------------------------------------------------
// 4. Fetch Stored Matrix (saved/override values)
// ---------------------------------------------------------------
$stored_matrix = [];
$res_curr = $mysqli->query(
    "SELECT particular_key, category_key, value_num
     FROM livestock_monthly_inventory
     WHERE user_id = $user_id AND month_year = '$selected_month'"
);
if ($res_curr) {
    while ($r = $res_curr->fetch_assoc()) {
        $stored_matrix[$r['particular_key']][$r['category_key']] = floatval($r['value_num']);
    }
}

// ---------------------------------------------------------------
// 5. Previous Month's Closing Balance (for opening_balance auto)
// ---------------------------------------------------------------
$prev_closing_balances = [];
$res_prev = $mysqli->query(
    "SELECT category_key, value_num
     FROM livestock_monthly_inventory
     WHERE user_id = $user_id AND month_year = '$prev_month' AND particular_key = 'closing_balance'"
);
if ($res_prev) {
    while ($r = $res_prev->fetch_assoc()) {
        $prev_closing_balances[$r['category_key']] = floatval($r['value_num']);
    }
}

// ---------------------------------------------------------------
// 6. Build Auto Values from live animal_disposal_register data
// ---------------------------------------------------------------
$auto_values = [];

// Opening balance = prev closing
foreach ($all_categories as $col_key => $col_info) {
    $auto_values['opening_balance'][$col_key] = $prev_closing_balances[$col_key] ?? 0;
}

// Aggregate disposals for this month (with user_id filter)
$disp_sql = "SELECT species, how_disposed_of,
                    SUM(stud_bulls)      AS sum_stud,
                    SUM(draught_bulls)   AS sum_draught,
                    SUM(cows)            AS sum_cows,
                    SUM(heifer_calves)   AS sum_heifers,
                    SUM(bull_calves)     AS sum_calves_male,
                    SUM(amount_realized) AS sum_realized
             FROM animal_disposal_register
             WHERE disposal_date BETWEEN '$first_day' AND '$last_day'
               AND user_id = $user_id
             GROUP BY species, how_disposed_of";
$disp_res = $mysqli->query($disp_sql);

if ($disp_res) {
    while ($r = $disp_res->fetch_assoc()) {
        $spec = strtolower($r['species']);
        $how  = strtolower($r['how_disposed_of']);

        $prefix = '';
        if ($spec === 'cattle' || $spec === 'white cattle') $prefix = 'cattle_';
        elseif ($spec === 'goat')                           $prefix = 'goat_';
        elseif ($spec === 'buffalo')                        $prefix = 'buffalo_';

        if (!empty($prefix)) {
            $col_map = [
                $prefix . 'stud_bulls'                                           => intval($r['sum_stud']),
                $prefix . ($spec === 'goat' ? 'he_goats'    : 'cows')           => intval($r['sum_cows']),
                $prefix . ($spec === 'goat' ? 'she_goats'   : 'heifers')        => intval($r['sum_heifers']),
                $prefix . ($spec === 'goat' ? 'kids_male'   : 'calves_male')    => intval($r['sum_calves_male']),
                $prefix . ($spec === 'goat' ? 'kids_female' : 'calves_female')  => intval($r['sum_draught']),
            ];

            if ($how === 'sold') {
                foreach ($col_map as $ck => $cv) {
                    $auto_values['sold_no'][$ck] = ($auto_values['sold_no'][$ck] ?? 0) + $cv;
                }
                // Amount realized totalled into first (stud_bulls) column per species
                $rs_col = $prefix . 'stud_bulls';
                $auto_values['sold_rs'][$rs_col] = ($auto_values['sold_rs'][$rs_col] ?? 0) + floatval($r['sum_realized']);
            } elseif ($how === 'died') {
                foreach ($col_map as $ck => $cv) {
                    $auto_values['deaths'][$ck] = ($auto_values['deaths'][$ck] ?? 0) + $cv;
                }
            } elseif ($how === 'transferred') {
                foreach ($col_map as $ck => $cv) {
                    $auto_values['transfers'][$ck] = ($auto_values['transfers'][$ck] ?? 0) + $cv;
                }
            }
        }
    }
}

// Default 0 for all auto categories not found in data
foreach (['opening_balance', 'sold_no', 'sold_rs', 'deaths', 'transfers'] as $auto_key) {
    foreach ($all_categories as $col_key => $col_info) {
        if (!isset($auto_values[$auto_key][$col_key])) {
            $auto_values[$auto_key][$col_key] = 0;
        }
    }
}
?>

<link rel="stylesheet" href="../../../assets/css/farm.css">

<style>
/* ---- Matrix base ---- */
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

/* ---- Auto-fetched cells ---- */
.auto-fetched-cell {
    background-color: #eff6ff !important;
    min-width: 110px;
    padding: 5px 6px !important;
    transition: background-color 0.2s;
}
.auto-fetched-cell.cell-overridden {
    background-color: #fffbeb !important;
}
.auto-mode-display,
.override-mode-display {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
}
.auto-value-badge {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    flex-wrap: nowrap;
}
.auto-num {
    font-size: 0.9rem;
    font-weight: 700;
    color: #1d4ed8;
}
.cell-overridden .auto-num {
    color: #92400e;
}
.badge-auto {
    font-size: 0.58rem;
    font-weight: 800;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    padding: 2px 5px;
    border-radius: 3px;
    letter-spacing: 0.4px;
    white-space: nowrap;
    box-shadow: 0 1px 3px rgba(37,99,235,0.3);
}
.badge-override {
    font-size: 0.58rem;
    font-weight: 800;
    background: linear-gradient(135deg, #d97706, #b45309);
    color: #fff;
    padding: 2px 5px;
    border-radius: 3px;
    letter-spacing: 0.4px;
    white-space: nowrap;
}
.btn-override-toggle {
    font-size: 0.68rem;
    color: #2563eb;
    background: none;
    border: 1px solid #93c5fd;
    border-radius: 4px;
    padding: 1px 6px;
    cursor: pointer;
    width: 100%;
    transition: all 0.15s;
    white-space: nowrap;
}
.btn-override-toggle:hover {
    background-color: #2563eb;
    color: #fff;
    border-color: #2563eb;
}
.btn-reset-auto {
    font-size: 0.68rem;
    color: #dc2626;
    background: none;
    border: 1px solid #fca5a5;
    border-radius: 4px;
    padding: 1px 6px;
    cursor: pointer;
    width: 100%;
    transition: all 0.15s;
    white-space: nowrap;
}
.btn-reset-auto:hover {
    background-color: #dc2626;
    color: #fff;
    border-color: #dc2626;
}
.override-input-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    width: 100%;
}
.override-input-wrap .matrix-input {
    width: 85px;
    border-color: #f59e0b;
    background-color: #fffbeb;
}
.override-input-wrap .matrix-input:focus {
    border-color: #d97706;
    box-shadow: 0 0 0 0.2rem rgba(217, 119, 6, 0.25);
}
/* Auto source tooltip on label */
.auto-row-label {
    display: flex;
    align-items: center;
    gap: 5px;
}
.auto-row-icon {
    color: #2563eb;
    font-size: 0.75rem;
    cursor: help;
    flex-shrink: 0;
}

/* Refresh button spin animation */
@keyframes spin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
.spin { display: inline-block; animation: spin 0.9s linear infinite; }

/* Toast notification */
#autoRefreshToast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9999;
    min-width: 280px;
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}
</style>

<!-- Header & Month Filter -->
<div class="row align-items-center mb-4">
    <div class="col-md-6">
        <h3 class="fw-bold text-dark m-0">
            <i class="bi bi-boxes me-2" style="color: #820100;"></i>Monthly Livestock Inventory Summary
        </h3>
        <p class="text-muted mb-0 small">
            Official ledger matrix for tracking monthly opening &amp; closing balances across all species.
            <span class="badge bg-primary-subtle text-primary border border-primary ms-2 small py-1">
                <i class="bi bi-database-fill-check me-1"></i>Auto-fetched rows sync from Disposal Registers
            </span>
        </p>
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
                    title: '<?= ($_GET['status'] === 'success') ? 'Saved!' : 'Error!' ?>',
                    text: <?= json_encode($_GET['msg'] ?? '') ?>,
                    confirmButtonColor: '#820100',
                    timer: 3500,
                    timerProgressBar: true
                });
            }
        });
    </script>
<?php endif; ?>

<!-- Legend row -->
<div class="d-flex gap-3 mb-3 flex-wrap">
    <div class="d-flex align-items-center gap-2">
        <div style="width:14px;height:14px;background:#eff6ff;border:1.5px solid #93c5fd;border-radius:3px;"></div>
        <small class="text-muted fw-semibold"><span class="badge-auto" style="font-size:0.6rem;">AUTO</span> Auto-fetched from Disposal Registers (locked by default)</small>
    </div>
    <div class="d-flex align-items-center gap-2">
        <div style="width:14px;height:14px;background:#fffbeb;border:1.5px solid #fcd34d;border-radius:3px;"></div>
        <small class="text-muted fw-semibold"><span class="badge-override" style="font-size:0.6rem;">EDIT</span> Manual override active — click ↩ Auto to revert</small>
    </div>
    <div class="d-flex align-items-center gap-2">
        <div style="width:14px;height:14px;background:#e9ecef;border:1.5px solid #ced4da;border-radius:3px;"></div>
        <small class="text-muted fw-semibold">Calculated (formula-driven)</small>
    </div>
</div>

<!-- MAIN MATRIX FORM -->
<form action="processors/save_livestock_monthly_summary.php" method="POST" id="matrixForm">
    <input type="hidden" name="month_year" value="<?= htmlspecialchars($selected_month) ?>">

    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="fw-bold text-dark m-0">
                    <i class="bi bi-table me-2" style="color: #820100;"></i>Monthly Summary Matrix (<?= date('F Y', strtotime($selected_month . '-01')) ?>)
                </h5>
                <small class="text-muted">Opening/Closing balances &amp; dynamic tracking across Cattle, Goat, Buffalo &amp; Poultry batches.</small>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" id="btnRefreshAuto" class="btn btn-sm btn-outline-primary fw-bold px-3" onclick="refreshAutoData()">
                    <i class="bi bi-arrow-repeat me-1"></i>Refresh Auto-Data
                </button>
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
                        <!-- TOP ROW: Group Headers -->
                        <tr>
                            <th rowspan="2" class="matrix-particular-row bg-white text-dark align-middle">Particulars</th>
                            <?php foreach ($animal_groups as $group_name => $cols): ?>
                                <th colspan="<?= count($cols) ?>" class="matrix-header-group">
                                    <?= htmlspecialchars($group_name) ?>
                                </th>
                            <?php endforeach; ?>
                            <th rowspan="2" class="matrix-header-group bg-dark text-white align-middle px-3">Grand Total</th>
                        </tr>

                        <!-- SECOND ROW: Sub-column labels -->
                        <tr>
                            <?php foreach ($animal_groups as $group_name => $cols): ?>
                                <?php foreach ($cols as $col_key => $col_label): ?>
                                    <th class="matrix-header-sub"><?= htmlspecialchars($col_label) ?></th>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($particulars as $part_key => $part_info): ?>

                            <?php if ($part_info['type'] === 'header'): ?>
                                <!-- SECTION DIVIDER -->
                                <tr>
                                    <td colspan="<?= count($all_categories) + 2 ?>" class="matrix-section-header py-2 px-3">
                                        <i class="bi bi-gear-fill me-2" style="color: #820100;"></i><?= htmlspecialchars($part_info['label']) ?>
                                    </td>
                                </tr>

                            <?php else: ?>
                                <tr>
                                    <!-- Particulars label column -->
                                    <td class="matrix-particular-row">
                                        <?php if ($part_info['is_auto'] || $part_info['is_calc']): ?>
                                            <div class="auto-row-label">
                                                <?= htmlspecialchars($part_info['label']) ?>
                                                <?php if ($part_info['is_auto']): ?>
                                                    <i class="bi bi-lightning-charge-fill auto-row-icon"
                                                       title="<?= htmlspecialchars($part_info['auto_source']) ?>"></i>
                                                <?php elseif ($part_info['is_calc']): ?>
                                                    <i class="bi bi-calculator auto-row-icon" style="color:#820100;" title="Calculated: (Opening + Received + Births) − (Transfers + Sold + Missing + Deaths)"></i>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <?= htmlspecialchars($part_info['label']) ?>
                                        <?php endif; ?>
                                    </td>

                                    <?php foreach ($all_categories as $col_key => $col_info): ?>
                                        <?php
                                        $step = ($part_info['type'] === 'decimal' || $part_info['type'] === 'currency') ? '0.01' : '1';

                                        if ($part_info['is_calc']):
                                            // ---- CALCULATED CELL ----
                                        ?>
                                            <td class="matrix-calc-cell" id="cell_c_<?= $part_key ?>_<?= $col_key ?>">
                                                <span id="calc_<?= $part_key ?>_<?= $col_key ?>" class="calc-val">0</span>
                                                <input type="hidden"
                                                       name="matrix[<?= $part_key ?>][<?= $col_key ?>]"
                                                       id="input_<?= $part_key ?>_<?= $col_key ?>"
                                                       value="0">
                                            </td>

                                        <?php elseif ($part_info['is_auto']):
                                            // ---- AUTO-FETCHED CELL ----
                                            $auto_val    = floatval($auto_values[$part_key][$col_key] ?? 0);
                                            $has_stored  = isset($stored_matrix[$part_key][$col_key]);
                                            $stored_val  = $has_stored ? floatval($stored_matrix[$part_key][$col_key]) : $auto_val;
                                            // Overridden = stored value exists AND differs from current auto value
                                            $is_overridden = $has_stored && (abs($stored_val - $auto_val) > 0.001);
                                            $submit_val    = $is_overridden ? $stored_val : $auto_val;
                                            $fmt_auto      = ($part_info['type'] === 'currency')
                                                                ? number_format($auto_val, 2)
                                                                : number_format($auto_val);
                                            $fmt_override  = ($part_info['type'] === 'currency')
                                                                ? number_format($stored_val, 2)
                                                                : number_format($stored_val);
                                        ?>
                                            <td class="auto-fetched-cell <?= $is_overridden ? 'cell-overridden' : '' ?>"
                                                id="cell_<?= $part_key ?>_<?= $col_key ?>">

                                                <!-- Single hidden input for form submission -->
                                                <input type="hidden"
                                                       id="submit_<?= $part_key ?>_<?= $col_key ?>"
                                                       name="matrix[<?= $part_key ?>][<?= $col_key ?>]"
                                                       value="<?= htmlspecialchars($submit_val) ?>">

                                                <!-- AUTO MODE display -->
                                                <div class="auto-mode-display <?= $is_overridden ? 'd-none' : '' ?>"
                                                     id="autodisp_<?= $part_key ?>_<?= $col_key ?>">
                                                    <div class="auto-value-badge">
                                                        <span class="auto-num"
                                                              id="autonum_<?= $part_key ?>_<?= $col_key ?>"><?= $fmt_auto ?></span>
                                                        <span class="badge-auto">AUTO</span>
                                                    </div>
                                                    <button type="button"
                                                            class="btn-override-toggle"
                                                            data-part="<?= $part_key ?>"
                                                            data-col="<?= $col_key ?>"
                                                            data-auto="<?= $auto_val ?>"
                                                            onclick="enableOverride(this)"
                                                            title="<?= htmlspecialchars($part_info['auto_source']) ?>">
                                                        <i class="bi bi-pencil-fill"></i> Edit
                                                    </button>
                                                </div>

                                                <!-- OVERRIDE MODE display -->
                                                <div class="override-mode-display <?= $is_overridden ? '' : 'd-none' ?>"
                                                     id="overridedisp_<?= $part_key ?>_<?= $col_key ?>">
                                                    <span class="badge-override" style="font-size:0.58rem;margin-bottom:2px;">EDIT</span>
                                                    <div class="override-input-wrap">
                                                        <input type="number"
                                                               class="matrix-input auto-override-input"
                                                               id="overrideinput_<?= $part_key ?>_<?= $col_key ?>"
                                                               data-part="<?= $part_key ?>"
                                                               data-col="<?= $col_key ?>"
                                                               data-auto="<?= $auto_val ?>"
                                                               step="<?= $step ?>"
                                                               value="<?= htmlspecialchars($submit_val) ?>"
                                                               oninput="syncHidden('<?= $part_key ?>','<?= $col_key ?>',this.value)"
                                                               onfocus="this.select();">
                                                        <button type="button"
                                                                class="btn-reset-auto"
                                                                data-part="<?= $part_key ?>"
                                                                data-col="<?= $col_key ?>"
                                                                data-auto="<?= $auto_val ?>"
                                                                onclick="resetToAuto(this)"
                                                                title="Revert to auto-fetched value">
                                                            <i class="bi bi-arrow-counterclockwise"></i> Auto
                                                        </button>
                                                    </div>
                                                    <small class="text-muted" style="font-size:0.62rem;">
                                                        Auto: <?= $fmt_auto ?> &nbsp;
                                                        <span id="autoref_<?= $part_key ?>_<?= $col_key ?>"></span>
                                                    </small>
                                                </div>
                                            </td>

                                        <?php else:
                                            // ---- MANUAL INPUT CELL ----
                                            $val = $stored_matrix[$part_key][$col_key] ?? 0;
                                        ?>
                                            <td>
                                                <input type="number"
                                                       step="<?= $step ?>"
                                                       class="matrix-input matrix-cell-input"
                                                       data-part="<?= $part_key ?>"
                                                       data-col="<?= $col_key ?>"
                                                       name="matrix[<?= $part_key ?>][<?= $col_key ?>]"
                                                       value="<?= $val ?>"
                                                       onfocus="this.select();">
                                            </td>
                                        <?php endif; ?>

                                    <?php endforeach; /* end categories */ ?>

                                    <!-- ROW GRAND TOTAL -->
                                    <td class="bg-light fw-bold text-dark" id="row_total_<?= $part_key ?>">0</td>
                                </tr>

                            <?php endif; ?>
                        <?php endforeach; /* end particulars */ ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Closing Balance = <code>(Opening + Received + Births) &minus; (Transfers + Sold [No.] + Missing + Deaths)</code>
            </small>
            <button type="submit" class="btn text-light fw-bold px-4 shadow-sm" style="background-color: #820100;">
                <i class="bi bi-check-circle me-1"></i>Save &amp; Commit Matrix
            </button>
        </div>
    </div>
</form>

<!-- Toast notification -->
<div id="autoRefreshToast" class="toast align-items-center text-white border-0 d-none" role="alert">
    <div class="d-flex">
        <div class="toast-body fw-semibold" id="toastMsg">Auto-data refreshed.</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="hideToast()"></button>
    </div>
</div>

<script>
/* ================================================================
   Embedded data from PHP (used for Refresh button initial state)
================================================================ */
const phpAutoValues  = <?= json_encode($auto_values) ?>;
const phpParticulars = <?= json_encode(array_keys($particulars)) ?>;
const allCategoryKeys = <?= json_encode(array_keys($all_categories)) ?>;
const selectedMonth  = '<?= $selected_month ?>';

/* ================================================================
   Navigation
================================================================ */
function applyMatrixMonthFilter() {
    const monthVal = document.getElementById('matrix_month_filter').value;
    if (monthVal) window.location.href = 'inventory_register.php?month=' + monthVal;
}

/* ================================================================
   Auto-cell: Enable Override (show input, hide badge)
================================================================ */
function enableOverride(btn) {
    const part = btn.dataset.part;
    const col  = btn.dataset.col;
    const cell = document.getElementById('cell_' + part + '_' + col);

    document.getElementById('autodisp_'     + part + '_' + col).classList.add('d-none');
    document.getElementById('overridedisp_' + part + '_' + col).classList.remove('d-none');
    cell.classList.add('cell-overridden');

    const input = document.getElementById('overrideinput_' + part + '_' + col);
    if (input) { input.focus(); input.select(); }
}

/* ================================================================
   Auto-cell: Reset to Auto value
================================================================ */
function resetToAuto(btn) {
    const part    = btn.dataset.part;
    const col     = btn.dataset.col;
    const autoVal = parseFloat(btn.dataset.auto) || 0;
    const cell    = document.getElementById('cell_' + part + '_' + col);

    // Restore hidden submit input to auto value
    const hidden = document.getElementById('submit_' + part + '_' + col);
    if (hidden) hidden.value = autoVal;

    // Restore displayed auto number
    const autoNum = document.getElementById('autonum_' + part + '_' + col);
    if (autoNum) autoNum.textContent = autoVal.toLocaleString(undefined, {maximumFractionDigits: 2});

    // Switch display modes
    document.getElementById('autodisp_'     + part + '_' + col).classList.remove('d-none');
    document.getElementById('overridedisp_' + part + '_' + col).classList.add('d-none');
    cell.classList.remove('cell-overridden');

    calculateMatrix();
}

/* ================================================================
   Auto-cell: Sync hidden submit input when override input changes
================================================================ */
function syncHidden(part, col, value) {
    const hidden = document.getElementById('submit_' + part + '_' + col);
    if (hidden) hidden.value = value;
    calculateMatrix();
}

/* ================================================================
   Matrix Calculations (closing balance + row totals)
================================================================ */
function getCellValue(part, col) {
    // Closing balance uses its own hidden input
    if (part === 'closing_balance') {
        const h = document.getElementById('input_' + part + '_' + col);
        return h ? parseFloat(h.value) || 0 : 0;
    }
    // Auto-fetched cells use submit_ hidden inputs
    const submitH = document.getElementById('submit_' + part + '_' + col);
    if (submitH) return parseFloat(submitH.value) || 0;
    // Manual cells use data-part / data-col inputs
    const inp = document.querySelector(`input[data-part="${part}"][data-col="${col}"]`);
    return inp ? parseFloat(inp.value) || 0 : 0;
}

function calculateMatrix() {
    const categories  = allCategoryKeys;
    const particulars = [
        'opening_balance', 'on_hand', 'received', 'transfers', 'births',
        'sold_no', 'sold_kg', 'sold_rs', 'missing', 'deaths', 'closing_balance'
    ];

    // Calculate closing balance per column
    categories.forEach(function(col) {
        const opening   = getCellValue('opening_balance', col);
        const received  = getCellValue('received',        col);
        const births    = getCellValue('births',          col);
        const transfers = getCellValue('transfers',       col);
        const soldNo    = getCellValue('sold_no',         col);
        const missing   = getCellValue('missing',         col);
        const deaths    = getCellValue('deaths',          col);

        const closing = (opening + received + births) - (transfers + soldNo + missing + deaths);

        const calcSpan   = document.getElementById('calc_closing_balance_' + col);
        const hiddenInput= document.getElementById('input_closing_balance_' + col);
        if (calcSpan)    calcSpan.textContent = closing.toLocaleString();
        if (hiddenInput) hiddenInput.value    = closing;
    });

    // Update row grand totals
    particulars.forEach(function(part) {
        let rowSum = 0;
        categories.forEach(function(col) { rowSum += getCellValue(part, col); });

        const rowTotalTd = document.getElementById('row_total_' + part);
        if (rowTotalTd) {
            if (part === 'sold_rs') {
                rowTotalTd.textContent = 'Rs. ' + rowSum.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
            } else if (part === 'sold_kg') {
                rowTotalTd.textContent = rowSum.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) + ' kg';
            } else {
                rowTotalTd.textContent = rowSum.toLocaleString();
            }
        }
    });
}

/* ================================================================
   Refresh Auto-Data — AJAX re-fetch from server
================================================================ */
function refreshAutoData() {
    const btn = document.getElementById('btnRefreshAuto');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin me-1"></i>Refreshing...';

    fetch('processors/get_auto_inventory_data.php?month=' + selectedMonth)
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                applyAutoValues(data.auto_values);
                showToast('Auto-data refreshed from Disposal Registers!', 'success');
            } else {
                showToast('Failed to fetch auto-data. Try reloading the page.', 'danger');
            }
        })
        .catch(function() {
            showToast('Network error during refresh. Try again.', 'danger');
        })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Refresh Auto-Data';
        });
}

/* Apply freshly fetched auto values to cells in auto mode */
function applyAutoValues(autoValues) {
    Object.keys(autoValues).forEach(function(partKey) {
        const partData = autoValues[partKey];
        if (!partData || typeof partData !== 'object') return;

        Object.keys(partData).forEach(function(colKey) {
            const newVal = parseFloat(partData[colKey]) || 0;
            const autoDispId = 'autodisp_' + partKey + '_' + colKey;
            const autoDisp   = document.getElementById(autoDispId);
            if (!autoDisp) return; // not an auto cell

            // Update data-auto on toggle buttons
            const cell = document.getElementById('cell_' + partKey + '_' + colKey);
            if (cell) {
                cell.querySelectorAll('[data-part]').forEach(function(el) {
                    if (el.dataset.part === partKey && el.dataset.col === colKey) {
                        el.dataset.auto = newVal;
                    }
                });
                // Update override-input's data-auto
                const oInput = document.getElementById('overrideinput_' + partKey + '_' + colKey);
                if (oInput) oInput.dataset.auto = newVal;
            }

            const isInAutoMode = !autoDisp.classList.contains('d-none');
            if (isInAutoMode) {
                // Update display number
                const autoNum = document.getElementById('autonum_' + partKey + '_' + colKey);
                if (autoNum) autoNum.textContent = newVal.toLocaleString(undefined, {maximumFractionDigits:2});
                // Update hidden submit value
                const hidden = document.getElementById('submit_' + partKey + '_' + colKey);
                if (hidden) hidden.value = newVal;
            }

            // Always update the "Auto: X" reference shown under override input
            const autoRef = document.getElementById('autoref_' + partKey + '_' + colKey);
            // (the small reference span is empty, but we update the static text via the button data)
        });
    });
    calculateMatrix();
}

/* ================================================================
   Toast helper
================================================================ */
let _toastTimer;
function showToast(msg, type) {
    const toast = document.getElementById('autoRefreshToast');
    const toastMsg = document.getElementById('toastMsg');
    const colors = { success: '#198754', danger: '#dc3545', info: '#0dcaf0' };
    toast.style.backgroundColor = colors[type] || '#6c757d';
    toastMsg.textContent = msg;
    toast.classList.remove('d-none');
    clearTimeout(_toastTimer);
    _toastTimer = setTimeout(hideToast, 4000);
}
function hideToast() {
    document.getElementById('autoRefreshToast').classList.add('d-none');
}

/* ================================================================
   Attach change listeners to manual inputs & run initial calc
================================================================ */
document.addEventListener('DOMContentLoaded', function() {
    // Manual cell inputs
    document.querySelectorAll('.matrix-cell-input').forEach(function(input) {
        input.addEventListener('input',  calculateMatrix);
        input.addEventListener('change', calculateMatrix);
    });
    // Override inputs
    document.querySelectorAll('.auto-override-input').forEach(function(input) {
        input.addEventListener('input',  calculateMatrix);
        input.addEventListener('change', calculateMatrix);
    });
    // Initial calculation
    calculateMatrix();
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
