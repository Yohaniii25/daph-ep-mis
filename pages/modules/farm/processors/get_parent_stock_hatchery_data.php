<?php
// pages/modules/farm/processors/get_parent_stock_hatchery_data.php
session_start();
header('Content-Type: application/json');
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$batch_id = intval($_GET['batch_id'] ?? $_POST['batch_id'] ?? 0);
$record_date = trim($_GET['record_date'] ?? $_POST['record_date'] ?? '');

if ($batch_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid batch ID']);
    exit();
}

$eggs_loaded = 0;
$hatched_eggs = 0;
$hatchable_eggs = 0;

// First attempt: search daily_egg_production matching batch_id and specific record_date
if (!empty($record_date)) {
    $stmt = $mysqli->prepare("SELECT 
                                SUM(COALESCE(eggs_loaded, 0)) AS total_loaded, 
                                SUM(COALESCE(hatched_eggs, 0)) AS total_hatched,
                                SUM(COALESCE(hatchable_eggs, 0)) AS total_hatchable
                              FROM daily_egg_production 
                              WHERE batch_id = ? AND (collection_date = ? OR loading_date = ? OR hatching_date = ?)");
    $stmt->bind_param("isss", $batch_id, $record_date, $record_date, $record_date);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $eggs_loaded = intval($row['total_loaded']);
        $hatched_eggs = intval($row['total_hatched']);
        $hatchable_eggs = intval($row['total_hatchable']);
    }
    $stmt->close();
}

// Fallback: If no date specific data found or no date passed, query by batch_id overall/latest
if ($eggs_loaded == 0 && $hatched_eggs == 0 && $hatchable_eggs == 0) {
    $stmt = $mysqli->prepare("SELECT 
                                SUM(COALESCE(eggs_loaded, 0)) AS total_loaded, 
                                SUM(COALESCE(hatched_eggs, 0)) AS total_hatched,
                                SUM(COALESCE(hatchable_eggs, 0)) AS total_hatchable
                              FROM daily_egg_production 
                              WHERE batch_id = ?");
    $stmt->bind_param("i", $batch_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $eggs_loaded = intval($row['total_loaded']);
        $hatched_eggs = intval($row['total_hatched']);
        $hatchable_eggs = intval($row['total_hatchable']);
    }
    $stmt->close();
}

// If eggs_loaded is 0, use hatchable_eggs as fallback for loaded eggs count
$final_eggs_loaded = ($eggs_loaded > 0) ? $eggs_loaded : $hatchable_eggs;

echo json_encode([
    'success' => true,
    'batch_id' => $batch_id,
    'record_date' => $record_date,
    'eggs_loaded' => $final_eggs_loaded,
    'hatched_eggs' => $hatched_eggs,
    'hatchable_eggs' => $hatchable_eggs
]);
$mysqli->close();
?>
