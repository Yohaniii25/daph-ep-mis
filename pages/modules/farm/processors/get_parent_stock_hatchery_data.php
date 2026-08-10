<?php
// pages/modules/farm/processors/get_parent_stock_hatchery_data.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once __DIR__ . '/../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$batch_id    = intval($_GET['batch_id']    ?? 0);
$cage_id     = intval($_GET['cage_id']     ?? 0);
$record_date = trim($_GET['record_date']   ?? '');

if ($batch_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid batch ID']);
    exit();
}

$eggs_loaded    = 0;
$hatched_eggs   = 0;

$expr = "IF(SUM(COALESCE(eggs_loaded, 0)) > 0, SUM(COALESCE(eggs_loaded, 0)), IF(SUM(COALESCE(hatchable_eggs, 0)) > 0, SUM(COALESCE(hatchable_eggs, 0)), SUM(COALESCE(total_eggs, 0))))";

// -------------------------------------------------------
// Priority 1: exact match — date + batch + cage
// -------------------------------------------------------
if (!empty($record_date) && $cage_id > 0) {
    $stmt = $mysqli->prepare(
        "SELECT {$expr} AS total_loaded,
                COALESCE(SUM(hatched_eggs), 0) AS total_hatched
         FROM daily_egg_production
         WHERE batch_id = ? AND cage_id = ? AND (collection_date = ? OR loading_date = ?)"
    );
    $stmt->bind_param("iiss", $batch_id, $cage_id, $record_date, $record_date);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $eggs_loaded  = intval($row['total_loaded']);
        $hatched_eggs = intval($row['total_hatched']);
    }
    $stmt->close();
}

// -------------------------------------------------------
// Priority 2: date + batch (ignore cage)
// -------------------------------------------------------
if ($eggs_loaded == 0 && !empty($record_date)) {
    $stmt = $mysqli->prepare(
        "SELECT {$expr} AS total_loaded,
                COALESCE(SUM(hatched_eggs), 0) AS total_hatched
         FROM daily_egg_production
         WHERE batch_id = ? AND (collection_date = ? OR loading_date = ?)"
    );
    $stmt->bind_param("iss", $batch_id, $record_date, $record_date);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $eggs_loaded  = intval($row['total_loaded']);
        $hatched_eggs = intval($row['total_hatched']);
    }
    $stmt->close();
}

// -------------------------------------------------------
// Priority 3: batch + cage (any date)
// -------------------------------------------------------
if ($eggs_loaded == 0 && $cage_id > 0) {
    $stmt = $mysqli->prepare(
        "SELECT {$expr} AS total_loaded,
                COALESCE(SUM(hatched_eggs), 0) AS total_hatched
         FROM daily_egg_production
         WHERE batch_id = ? AND cage_id = ?"
    );
    $stmt->bind_param("ii", $batch_id, $cage_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $eggs_loaded  = intval($row['total_loaded']);
        $hatched_eggs = intval($row['total_hatched']);
    }
    $stmt->close();
}

// -------------------------------------------------------
// Priority 4: batch only
// -------------------------------------------------------
if ($eggs_loaded == 0) {
    $stmt = $mysqli->prepare(
        "SELECT {$expr} AS total_loaded,
                COALESCE(SUM(hatched_eggs), 0) AS total_hatched
         FROM daily_egg_production
         WHERE batch_id = ?"
    );
    $stmt->bind_param("i", $batch_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $eggs_loaded  = intval($row['total_loaded']);
        $hatched_eggs = intval($row['total_hatched']);
    }
    $stmt->close();
}

echo json_encode([
    'success'      => true,
    'batch_id'     => $batch_id,
    'cage_id'      => $cage_id,
    'record_date'  => $record_date,
    'eggs_loaded'  => $eggs_loaded,
    'hatched_eggs' => $hatched_eggs
]);
$mysqli->close();
?>
