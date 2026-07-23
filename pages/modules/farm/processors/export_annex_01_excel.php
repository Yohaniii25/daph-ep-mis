<?php
// pages/modules/farm/processors/export_annex_01_excel.php
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

// 1. Active Cages
$cages_res = $mysqli->query("SELECT id, cage_name FROM cages ORDER BY cage_name");
$cages = [];
if ($cages_res) {
    while ($c = $cages_res->fetch_assoc()) {
        $cages[] = $c;
    }
}

// 2. Production Records
$prod_sql = "SELECT dep.*, b.batch_number AS batch_name
            FROM daily_egg_production dep
            JOIN vaccine_batches b ON dep.batch_id = b.id
            WHERE b.user_id = ? AND YEAR(dep.collection_date) = ? AND MONTH(dep.collection_date) = ?";
$stmt = $mysqli->prepare($prod_sql);
$stmt->bind_param("iii", $user_id, $year, $month);
$stmt->execute();
$prod_res = $stmt->get_result();

$prod_data = [];
if ($prod_res) {
    while ($row = $prod_res->fetch_assoc()) {
        $prod_data[$row['collection_date']][$row['cage_id']] = $row;
    }
}
$stmt->close();

// 3. Sales & Returns
$sales_sql = "SELECT * FROM daily_egg_sales_returns WHERE YEAR(record_date) = ? AND MONTH(record_date) = ?";
$stmt = $mysqli->prepare($sales_sql);
$stmt->bind_param("ii", $year, $month);
$stmt->execute();
$sales_res = $stmt->get_result();

$sales_data = [];
if ($sales_res) {
    while ($row = $sales_res->fetch_assoc()) {
        $sales_data[$row['record_date']] = $row;
    }
}
$stmt->close();

$running_bal_no = 0;
$running_bal_kg = 0.00;
$month_label = date('F_Y', strtotime("$year-$month-01"));

$filename = "Annex_01_Egg_Register_" . $month_label . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");

echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head><meta charset="utf-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Annex 01 Register</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head>';
echo '<body>';
echo '<h2>Annex 01 - Egg Register (' . date('F Y', strtotime("$year-$month-01")) . ')</h2>';
echo '<table border="1" style="border-collapse:collapse; text-align:center;">';
echo '<thead style="background-color:#212529; color:#ffffff;">';
echo '<tr>';
echo '<th rowspan="2">DATE</th>';
echo '<th rowspan="2">TYPE</th>';
foreach ($cages as $cg) {
    echo '<th colspan="2">' . htmlspecialchars($cg['cage_name']) . '</th>';
}
echo '<th colspan="2" style="background-color:#0d6efd; color:#fff;">TOTAL PRODUCTION</th>';
echo '<th colspan="2" style="background-color:#ffc107; color:#000;">HATCHERY RETURN</th>';
echo '<th colspan="2" style="background-color:#0dcaf0; color:#000;">TOTAL SALES</th>';
echo '<th colspan="2" style="background-color:#198754; color:#fff;">BALANCE</th>';
echo '</tr>';
echo '<tr>';
foreach ($cages as $cg) {
    echo '<th>NO</th><th>Kg</th>';
}
echo '<th>NO</th><th>Kg</th>';
echo '<th>NO</th><th>Kg</th>';
echo '<th>NO</th><th>Kg</th>';
echo '<th>NO</th><th>Kg</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

for ($day = 1; $day <= $days_in_month; $day++) {
    $date_str = sprintf("%04d-%02d-%02d", $year, $month, $day);
    $formatted_date = date('d-M-Y', strtotime($date_str));
    
    $sales_row = $sales_data[$date_str] ?? null;
    $ret_no = intval($sales_row['hatchery_return_no'] ?? 0);
    $ret_kg = floatval($sales_row['hatchery_return_kg'] ?? 0.00);
    $sale_no = intval($sales_row['total_sales_no'] ?? 0);
    $sale_kg = floatval($sales_row['total_sales_kg'] ?? 0.00);

    $types = [
        'Hat'   => ['label' => 'Hat', 'field_no' => 'hatchable_eggs', 'field_kg' => 'hatchable_eggs_kg'],
        'T/E'   => ['label' => 'T/E', 'field_no' => 'table_eggs', 'field_kg' => 'table_eggs_kg'],
        'C/E'   => ['label' => 'C/E', 'field_no' => 'cracked_eggs', 'field_kg' => 'cracked_eggs_kg'],
        'Total' => ['label' => 'Total', 'field_no' => 'total_eggs', 'field_kg' => 'total_eggs_kg']
    ];

    $day_tot_prod_no = 0;
    $day_tot_prod_kg = 0.00;
    foreach ($cages as $cg) {
        $record = $prod_data[$date_str][$cg['id']] ?? null;
        if ($record) {
            $day_tot_prod_no += intval($record['total_eggs'] ?? 0);
            $day_tot_prod_kg += floatval($record['total_eggs_kg'] ?? 0);
        }
    }

    $running_bal_no = $running_bal_no + $day_tot_prod_no + $ret_no - $sale_no;
    $running_bal_kg = round($running_bal_kg + $day_tot_prod_kg + $ret_kg - $sale_kg, 2);

    foreach ($types as $t_key => $t_info) {
        $row_tot_no = 0;
        $row_tot_kg = 0.00;
        $style = ($t_key === 'Total') ? 'style="font-weight:bold; background-color:#e9ecef;"' : '';
        echo "<tr $style>";
        if ($t_key === 'Hat') {
            echo '<td rowspan="4" style="vertical-align:middle; font-weight:bold;">' . $formatted_date . '</td>';
        }
        echo '<td style="font-weight:bold;">' . $t_info['label'] . '</td>';

        foreach ($cages as $cg) {
            $c_rec = $prod_data[$date_str][$cg['id']] ?? null;
            $c_no = intval($c_rec[$t_info['field_no']] ?? 0);
            $c_kg = floatval($c_rec[$t_info['field_kg']] ?? 0.00);
            $row_tot_no += $c_no;
            $row_tot_kg += $c_kg;
            echo '<td>' . ($c_no > 0 ? $c_no : 0) . '</td>';
            echo '<td>' . ($c_kg > 0 ? number_format($c_kg, 2) : '0.00') . '</td>';
        }

        echo '<td>' . ($row_tot_no > 0 ? $row_tot_no : 0) . '</td>';
        echo '<td>' . ($row_tot_kg > 0 ? number_format($row_tot_kg, 2) : '0.00') . '</td>';

        echo '<td>' . (($t_key === 'Total' && $ret_no > 0) ? $ret_no : 0) . '</td>';
        echo '<td>' . (($t_key === 'Total' && $ret_kg > 0) ? number_format($ret_kg, 2) : '0.00') . '</td>';

        echo '<td>' . (($t_key === 'Total' && $sale_no > 0) ? $sale_no : 0) . '</td>';
        echo '<td>' . (($t_key === 'Total' && $sale_kg > 0) ? number_format($sale_kg, 2) : '0.00') . '</td>';

        echo '<td>' . ($t_key === 'Total' ? $running_bal_no : '-') . '</td>';
        echo '<td>' . ($t_key === 'Total' ? number_format($running_bal_kg, 2) : '-') . '</td>';
        echo '</tr>';
    }
}

echo '</tbody>';
echo '</table>';
echo '</body></html>';
$mysqli->close();
exit();
