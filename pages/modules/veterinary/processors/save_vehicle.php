<?php
session_start();
require_once '../../../../config/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized entry context execution terminated.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id     = $_SESSION['user_id'] ?? null;
    $district_id = $_SESSION['district_id'] ?? null;
    $range_id    = $_SESSION['range_id'] ?? null;

    $vehicle_type      = trim(filter_input(INPUT_POST, 'vehicle_type', FILTER_SANITIZE_SPECIAL_CHARS));
    $vehicle_number    = strtoupper(trim(filter_input(INPUT_POST, 'vehicle_number', FILTER_SANITIZE_SPECIAL_CHARS)));
    $chassis_number    = strtoupper(trim(filter_input(INPUT_POST, 'chassis_number', FILTER_SANITIZE_SPECIAL_CHARS)));
    $current_condition  = trim(filter_input(INPUT_POST, 'current_condition', FILTER_SANITIZE_SPECIAL_CHARS));
    $other_details     = trim(filter_input(INPUT_POST, 'other_details', FILTER_SANITIZE_SPECIAL_CHARS));

    if (!$user_id || empty($vehicle_number) || empty($chassis_number)) {
        echo json_encode(['success' => false, 'message' => 'Critical data variables extraction exception validation failed.']);
        exit();
    }

    $ins = $mysqli->prepare("INSERT INTO registered_vehicles (user_id, district_id, range_id, vehicle_type, vehicle_number, chassis_number, current_condition, other_details) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($ins) {
        $ins->bind_param("iiisssss", $user_id, $district_id, $range_id, $vehicle_type, $vehicle_number, $chassis_number, $current_condition, $other_details);
        if ($ins->execute()) {
            echo json_encode(['success' => true, 'message' => 'New asset entity profile cleanly saved into master registry index.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'SQL Error execution crash context trace: ' . $ins->error]);
        }
        $ins->close();
    }
}