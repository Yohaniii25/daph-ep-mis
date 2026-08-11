<?php
// pages/modules/farm/processors/get_auto_inventory_data.php
// AJAX endpoint: returns auto-aggregated inventory values from animal_disposal_register
session_start();
require_once '../../../../config/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['role'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = intval($_SESSION['user_id'] ?? 1);
$month   = preg_match('/^\d{4}-\d{2}$/', $_GET['month'] ?? '') ? $_GET['month'] : date('Y-m');

$first_day  = date('Y-m-01', strtotime($month . '-01'));
$last_day   = date('Y-m-t',  strtotime($month . '-01'));
$prev_month = date('Y-m',    strtotime($month . '-01 -1 month'));

// ---------------------------------------------------------------
// 1. Fetch previous month closing balances (for opening_balance)
// ---------------------------------------------------------------
$prev_closing = [];
$stmt_prev = $mysqli->prepare(
    "SELECT category_key, value_num
     FROM livestock_monthly_inventory
     WHERE user_id = ? AND month_year = ? AND particular_key = 'closing_balance'"
);
if ($stmt_prev) {
    $stmt_prev->bind_param('is', $user_id, $prev_month);
    $stmt_prev->execute();
    $res_prev = $stmt_prev->get_result();
    while ($r = $res_prev->fetch_assoc()) {
        $prev_closing[$r['category_key']] = floatval($r['value_num']);
    }
    $stmt_prev->close();
}

// ---------------------------------------------------------------
// 2. Aggregate disposals for the current month
// ---------------------------------------------------------------
$auto_values = [
    'opening_balance' => $prev_closing,
    'sold_no'         => [],
    'sold_rs'         => [],
    'deaths'          => [],
    'transfers'       => [],
];

$stmt_disp = $mysqli->prepare(
    "SELECT species, how_disposed_of,
            SUM(stud_bulls)      AS sum_stud,
            SUM(draught_bulls)   AS sum_draught,
            SUM(cows)            AS sum_cows,
            SUM(heifer_calves)   AS sum_heifers,
            SUM(bull_calves)     AS sum_calves_male,
            SUM(amount_realized) AS sum_realized
     FROM animal_disposal_register
     WHERE disposal_date BETWEEN ? AND ?
       AND user_id = ?
     GROUP BY species, how_disposed_of"
);

if ($stmt_disp) {
    $stmt_disp->bind_param('ssi', $first_day, $last_day, $user_id);
    $stmt_disp->execute();
    $res_disp = $stmt_disp->get_result();

    while ($r = $res_disp->fetch_assoc()) {
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
                // Total amount_realized mapped to first (stud_bulls) column per species
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
    $stmt_disp->close();
}

echo json_encode([
    'success'     => true,
    'month'       => $month,
    'auto_values' => $auto_values,
]);
