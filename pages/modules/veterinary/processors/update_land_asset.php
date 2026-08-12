<?php
session_start();
require_once '../../../../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized entry request.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id               = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $district_id      = $_SESSION['district_id'] ?? null;
    $range_id         = $_SESSION['range_id'] ?? null;

    $property_name    = trim(filter_input(INPUT_POST, 'property_name', FILTER_SANITIZE_SPECIAL_CHARS));
    $land_extent      = trim(filter_input(INPUT_POST, 'land_extent', FILTER_SANITIZE_SPECIAL_CHARS));
    $building_area    = trim(filter_input(INPUT_POST, 'building_area', FILTER_SANITIZE_SPECIAL_CHARS));
    $land_status      = trim(filter_input(INPUT_POST, 'land_status', FILTER_SANITIZE_SPECIAL_CHARS));
    $deed_reference   = trim(filter_input(INPUT_POST, 'deed_reference', FILTER_SANITIZE_SPECIAL_CHARS));
    $deed_description = trim(filter_input(INPUT_POST, 'deed_description', FILTER_SANITIZE_SPECIAL_CHARS));

    if (!$id || !$district_id || !$range_id || empty($property_name)) {
        echo json_encode(['success' => false, 'message' => 'Validation failed. Required fields missing.']);
        exit();
    }

    $stmt = $mysqli->prepare("
        UPDATE land_assets SET 
            property_name = ?,
            land_extent = ?,
            building_area = ?,
            land_status = ?,
            deed_reference = ?,
            deed_description = ?
        WHERE id = ? AND district_id = ? AND range_id = ?
    ");

    if ($stmt) {
        $stmt->bind_param("ssssssiii", $property_name, $land_extent, $building_area, $land_status, $deed_reference, $deed_description, $id, $district_id, $range_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Property asset record updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database execution failed: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare update query statement.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
exit();
