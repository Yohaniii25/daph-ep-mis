<?php
session_start();
require_once '../../../../config/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized submission intercept execution halted.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id     = $_SESSION['user_id'] ?? null;
    $district_id = $_SESSION['district_id'] ?? null;
    $range_id    = $_SESSION['range_id'] ?? null;

    $furniture_type     = trim(filter_input(INPUT_POST, 'furniture_type', FILTER_SANITIZE_SPECIAL_CHARS));
    $available_quantity = filter_input(INPUT_POST, 'available_quantity', FILTER_VALIDATE_INT);
    $date_received      = trim(filter_input(INPUT_POST, 'date_received', FILTER_SANITIZE_SPECIAL_CHARS));
    $current_condition  = trim(filter_input(INPUT_POST, 'current_condition', FILTER_SANITIZE_SPECIAL_CHARS));
    $remarks            = trim(filter_input(INPUT_POST, 'remarks', FILTER_SANITIZE_SPECIAL_CHARS));

    if (!$user_id || empty($furniture_type) || !$available_quantity) {
        echo json_encode(['success' => false, 'message' => 'Validation checklist incomplete. Data execution failure.']);
        exit();
    }

    $stmt = $mysqli->prepare("INSERT INTO furniture_assets (user_id, district_id, range_id, furniture_type, current_condition, available_quantity, date_received, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iiisssss", $user_id, $district_id, $range_id, $furniture_type, $current_condition, $available_quantity, $date_received, $remarks);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Furniture item logged inside systemic operational directory structural databases.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'SQL structural insertion failure occurred: ' . $stmt->error]);
        }
        $stmt->close();
    }
}