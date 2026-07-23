<?php
// pages/modules/farm/processors/export_egg_collections.php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;

// Selected columns from frontend
$selected_cols = $_POST['columns'] ?? [];

// Available columns dictionary (key => header label)
$column_map = [
    'collection_date'         => 'Collection Date',
    'batch_name'              => 'Batch Name',
    'cage_name'               => 'Cage Name',
    'pullets'                 => 'Pullets',
    'cockerels'               => 'Cockerels',
    'hatchable_eggs'          => 'Hatch Eggs',
    'table_eggs'              => 'Table Eggs',
    'cracked_eggs'            => 'Cracked Eggs',
    'total_eggs'              => 'Total Eggs',
    'loading_date'            => 'Loading Date',
    'hatchery_name'           => 'Hatchery Name',
    'eggs_loaded'             => 'Eggs Loaded',
    'hatching_date'           => 'Hatching Date',
    'hatched_eggs'            => 'Hatched Eggs',
    'hatchability_percentage' => 'Hatchability (%)'
];

// Default to all columns if none selected
if (empty($selected_cols)) {
    $selected_cols = array_keys($column_map);
}

// Filter to only valid column keys
$export_keys = array_intersect($selected_cols, array_keys($column_map));

// Fetch User-Scoped Collection Records
$sql = "SELECT dep.*, b.batch_number AS batch_name, c.cage_name 
        FROM daily_egg_production dep
        JOIN vaccine_batches b ON dep.batch_id = b.id
        JOIN cages c ON dep.cage_id = c.id
        WHERE b.user_id = ?
        ORDER BY dep.collection_date DESC, dep.id DESC";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$filename = "daily_egg_collection_report_" . date('Y-m-d_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Build CSV Header row
$header_row = [];
foreach ($export_keys as $key) {
    $header_row[] = $column_map[$key];
}
fputcsv($output, $header_row);

// Build CSV Data rows
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data_row = [];
        foreach ($export_keys as $key) {
            $val = $row[$key] ?? '';
            
            // Format specific values if necessary
            if ($key === 'collection_date' && $val) {
                $val = date('Y-m-d', strtotime($val));
            } elseif ($key === 'loading_date' || $key === 'hatching_date') {
                $val = ($val && $val !== '0000-00-00') ? date('Y-m-d', strtotime($val)) : '';
            } elseif ($key === 'hatchability_percentage') {
                $val = number_format((float)$val, 2) . '%';
            }
            
            $data_row[] = $val;
        }
        fputcsv($output, $data_row);
    }
}

fclose($output);
$stmt->close();
$mysqli->close();
exit();
