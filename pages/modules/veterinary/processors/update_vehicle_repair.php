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
    $vehicle_id = filter_input(INPUT_POST, 'vehicle_id', FILTER_VALIDATE_INT);
    $repair_date = trim(filter_input(INPUT_POST, 'repair_date', FILTER_SANITIZE_SPECIAL_CHARS));
    $repair_done = trim(filter_input(INPUT_POST, 'repair_done', FILTER_SANITIZE_SPECIAL_CHARS));
    $repair_description = trim(filter_input(INPUT_POST, 'repair_description', FILTER_SANITIZE_SPECIAL_CHARS));
    $place_of_repair = trim(filter_input(INPUT_POST, 'place_of_repair', FILTER_SANITIZE_SPECIAL_CHARS));
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

    if (!$id || !$vehicle_id || empty($repair_done) || empty($repair_date)) {
        echo json_encode(['success' => false, 'message' => 'Validation failed.']);
        exit();
    }

    $stmt = $mysqli->prepare("UPDATE vehicle_repairs SET vehicle_id = ?, repair_date = ?, repair_done = ?, repair_description = ?, place_of_repair = ?, amount = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("issssdi", $vehicle_id, $repair_date, $repair_done, $repair_description, $place_of_repair, $amount, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Vehicle repair record updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'DB error: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement.']);
    }
}
exit();
