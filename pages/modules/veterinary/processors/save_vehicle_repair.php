<?php
session_start();
require_once '../../../../config/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized context termination.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'] ?? null;

    $vehicle_id         = filter_input(INPUT_POST, 'vehicle_id', FILTER_VALIDATE_INT);
    $repair_date        = trim(filter_input(INPUT_POST, 'repair_date', FILTER_SANITIZE_SPECIAL_CHARS));
    $repair_done        = trim(filter_input(INPUT_POST, 'repair_done', FILTER_SANITIZE_SPECIAL_CHARS));
    $place_of_repair    = trim(filter_input(INPUT_POST, 'place_of_repair', FILTER_SANITIZE_SPECIAL_CHARS));
    $amount             = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $repair_description = trim(filter_input(INPUT_POST, 'repair_description', FILTER_SANITIZE_SPECIAL_CHARS));

    if (!$user_id || !$vehicle_id || empty($repair_done) || $amount === false) {
        echo json_encode(['success' => false, 'message' => 'Validation error pipeline execution failed.']);
        exit();
    }

    $ins = $mysqli->prepare("INSERT INTO vehicle_repairs (vehicle_id, user_id, repair_date, repair_done, repair_description, place_of_repair, amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($ins) {
        $ins->bind_param("iissssd", $vehicle_id, $user_id, $repair_date, $repair_done, $repair_description, $place_of_repair, $amount);
        if ($ins->execute()) {
            echo json_encode(['success' => true, 'message' => 'Maintenance financial transaction entry logged successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database failure mapping entry: ' . $ins->error]);
        }
        $ins->close();
    }
}