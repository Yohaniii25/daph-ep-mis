<?php
session_start();
require_once '../../../../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized entry request execution rejected.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize parameters
    $user_id       = $_SESSION['user_id'] ?? null;
    $district_id   = $_SESSION['district_id'] ?? null;
    $range_id      = $_SESSION['range_id'] ?? null;
    
    $property_name    = trim(filter_input(INPUT_POST, 'property_name', FILTER_SANITIZE_SPECIAL_CHARS));
    $land_extent      = trim(filter_input(INPUT_POST, 'land_extent', FILTER_SANITIZE_SPECIAL_CHARS));
    $building_area    = trim(filter_input(INPUT_POST, 'building_area', FILTER_SANITIZE_SPECIAL_CHARS));
    $land_status      = trim(filter_input(INPUT_POST, 'land_status', FILTER_SANITIZE_SPECIAL_CHARS));
    $deed_reference   = trim(filter_input(INPUT_POST, 'deed_reference', FILTER_SANITIZE_SPECIAL_CHARS));
    $deed_description = trim(filter_input(INPUT_POST, 'deed_description', FILTER_SANITIZE_SPECIAL_CHARS));

    if (!$user_id || !$district_id || !$range_id || empty($property_name)) {
        echo json_encode(['success' => false, 'message' => 'Validation failed. Critical region parameters missing.']);
        exit();
    }

    $stmt = $mysqli->prepare("INSERT INTO land_assets (user_id, district_id, range_id, property_name, land_extent, building_area, land_status, deed_reference, deed_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("iiissssss", $user_id, $district_id, $range_id, $property_name, $land_extent, $building_area, $land_status, $deed_reference, $deed_description);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Property entry logged into regional registry system database.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database execution failed: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to build transaction query pipeline statement statement.']);
    }
}