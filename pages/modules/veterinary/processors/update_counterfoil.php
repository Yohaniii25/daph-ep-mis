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
    $counterfoil_type = trim(filter_input(INPUT_POST, 'counterfoil_type', FILTER_SANITIZE_SPECIAL_CHARS));
    $current_condition = trim(filter_input(INPUT_POST, 'current_condition', FILTER_SANITIZE_SPECIAL_CHARS));
    $available_quantity = filter_input(INPUT_POST, 'available_quantity', FILTER_VALIDATE_INT);
    $purchase_date = trim(filter_input(INPUT_POST, 'purchase_date', FILTER_SANITIZE_SPECIAL_CHARS));
    $remarks = trim(filter_input(INPUT_POST, 'remarks', FILTER_SANITIZE_SPECIAL_CHARS));

    if (!$id || empty($counterfoil_type) || !$available_quantity) {
        echo json_encode(['success' => false, 'message' => 'Validation failed']);
        exit();
    }

    $stmt = $mysqli->prepare("UPDATE counterfoil_assets SET counterfoil_type = ?, current_condition = ?, available_quantity = ?, purchase_date = ?, remarks = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ssissi", $counterfoil_type, $current_condition, $available_quantity, $purchase_date, $remarks, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Counterfoil asset record updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'DB error: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement.']);
    }
}
exit();
