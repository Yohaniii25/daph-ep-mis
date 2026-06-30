<?php
session_start();
require_once '../../../../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized submission access rejected.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'] ?? null;
    
    $land_asset_id      = filter_input(INPUT_POST, 'land_asset_id', FILTER_VALIDATE_INT);
    $inventory_item    = trim(filter_input(INPUT_POST, 'inventory_item', FILTER_SANITIZE_SPECIAL_CHARS));
    $available_quantity = filter_input(INPUT_POST, 'available_quantity', FILTER_VALIDATE_INT);
    $current_condition  = trim(filter_input(INPUT_POST, 'current_condition', FILTER_SANITIZE_SPECIAL_CHARS));
    $specification      = trim(filter_input(INPUT_POST, 'specification', FILTER_SANITIZE_SPECIAL_CHARS));
    $remarks            = trim(filter_input(INPUT_POST, 'remarks', FILTER_SANITIZE_SPECIAL_CHARS));

    if (!$user_id || !$land_asset_id || empty($inventory_item) || !$available_quantity) {
        echo json_encode(['success' => false, 'message' => 'Validation failed. Required values missing.']);
        exit();
    }

    $stmt = $mysqli->prepare("INSERT INTO building_inventories (land_asset_id, user_id, inventory_item, specification, current_condition, available_quantity, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("iisssis", $land_asset_id, $user_id, $inventory_item, $specification, $current_condition, $available_quantity, $remarks);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Inventory item logged successfully under specified property.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database failure: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare transactional database query statement.']);
    }
}