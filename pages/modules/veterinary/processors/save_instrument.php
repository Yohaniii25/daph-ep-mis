<?php
session_start();
require_once '../../../../config/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized processing boundary exception.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id     = $_SESSION['user_id'] ?? null;
    $district_id = $_SESSION['district_id'] ?? null;
    $range_id    = $_SESSION['range_id'] ?? null;

    $instrument_type    = trim(filter_input(INPUT_POST, 'instrument_type', FILTER_SANITIZE_SPECIAL_CHARS));
    $current_condition  = trim(filter_input(INPUT_POST, 'current_condition', FILTER_SANITIZE_SPECIAL_CHARS));
    $available_quantity = filter_input(INPUT_POST, 'available_quantity', FILTER_VALIDATE_INT);
    $purchase_date      = trim(filter_input(INPUT_POST, 'purchase_date', FILTER_SANITIZE_SPECIAL_CHARS));
    $remarks            = trim(filter_input(INPUT_POST, 'remarks', FILTER_SANITIZE_SPECIAL_CHARS));

    if (!$user_id || empty($instrument_type) || !$available_quantity) {
        echo json_encode(['success' => false, 'message' => 'Validation error: Missing required field configurations.']);
        exit();
    }

    $stmt = $mysqli->prepare("INSERT INTO instrument_assets (user_id, district_id, range_id, instrument_type, current_condition, available_quantity, purchase_date, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iiisssis", $user_id, $district_id, $range_id, $instrument_type, $current_condition, $available_quantity, $purchase_date, $remarks);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Medical/clinical instrument item saved.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database execution insertion trace failure: ' . $stmt->error]);
        }
        $stmt->close();
    }
}