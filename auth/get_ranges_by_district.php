<?php
require_once '../config/db_connect.php';

$district_id = isset($_GET['district_id']) ? intval($_GET['district_id']) : 0;
$ranges = [];

if ($district_id > 0) {
    $stmt = $mysqli->prepare("SELECT id, name FROM `veterinary_ranges` WHERE `district_id` = ? AND `is_active` = 1 ORDER BY name ASC");
    $stmt->bind_param("i", $district_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $ranges[] = [
                'id' => $row['id'],
                'name' => htmlspecialchars($row['name'])
            ];
        }
    }
    $stmt->close();
}

// Return exact JSON footprint for Javascript loop injection
header('Content-Type: application/json');
echo json_encode($ranges);
exit();