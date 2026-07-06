<?php
session_start();
require_once '../../../config/db_connect.php';

// Session and Role Guard
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("HTTP/1.1 403 Forbidden");
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

$range_id = $_SESSION['range_id'] ?? null;
if (empty($range_id)) {
    echo json_encode([]);
    exit();
}

$selected_year = isset($_GET['year']) ? intval($_GET['year']) : 2026;
$animal_type = isset($_GET['animal_category']) ? $_GET['animal_category'] : null;

if ($animal_type && $animal_type !== 'All') {
    $query = "SELECT activity_name, animal_category, target_quantity, achieved_quantity FROM production_activity_targets WHERE range_id = ? AND year = ? AND animal_category = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("iis", $range_id, $selected_year, $animal_type);
} else {
    $query = "SELECT activity_name, animal_category, target_quantity, achieved_quantity FROM production_activity_targets WHERE range_id = ? AND year = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ii", $range_id, $selected_year);
}

$stmt->execute();
$result = $stmt->get_result();
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        "activity_name" => $row['activity_name'],
        "animal_category" => $row['animal_category'] ?? "General",
        "target_quantity" => intval($row['target_quantity']),
        "achieved_quantity" => intval($row['achieved_quantity'])
    ];
}
$stmt->close();
$mysqli->close();

header('Content-Type: application/json');
echo json_encode($data);
