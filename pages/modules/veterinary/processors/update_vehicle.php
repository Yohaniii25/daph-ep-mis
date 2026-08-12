<?php
session_start();
require_once '../../../../config/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $vehicle_type = trim(filter_input(INPUT_POST, 'vehicle_type', FILTER_SANITIZE_SPECIAL_CHARS));
    $vehicle_number = trim(filter_input(INPUT_POST, 'vehicle_number', FILTER_SANITIZE_SPECIAL_CHARS));
    $chassis_number = trim(filter_input(INPUT_POST, 'chassis_number', FILTER_SANITIZE_SPECIAL_CHARS));
    $current_condition = trim(filter_input(INPUT_POST, 'current_condition', FILTER_SANITIZE_SPECIAL_CHARS));
    $other_details = trim(filter_input(INPUT_POST, 'other_details', FILTER_SANITIZE_SPECIAL_CHARS));

    if (!$id || empty($vehicle_type) || empty($vehicle_number)) {
        echo json_encode(['success' => false, 'message' => 'Validation error']);
        exit();
    }

    $stmt = $mysqli->prepare("UPDATE registered_vehicles SET vehicle_type = ?, vehicle_number = ?, chassis_number = ?, current_condition = ?, other_details = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("sssssi", $vehicle_type, $vehicle_number, $chassis_number, $current_condition, $other_details, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Vehicle updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'DB error: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare query statement.']);
    }
}
exit();
