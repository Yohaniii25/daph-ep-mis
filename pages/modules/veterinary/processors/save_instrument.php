<?php
session_start();
require_once '../../../../config/db_connect.php';
header('Content-Type: application/json');

$allowed_roles = ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon', 'provincial_director', 'district_dd', 'deputy_director_district'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized processing boundary exception.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id     = $_SESSION['user_id'] ?? null;
    $district_id = !empty($_POST['district_id']) ? intval($_POST['district_id']) : ($_SESSION['district_id'] ?? null);
    $range_id    = !empty($_POST['range_id']) ? intval($_POST['range_id']) : ($_SESSION['range_id'] ?? null);

    $instrument_type    = trim(filter_input(INPUT_POST, 'instrument_type', FILTER_SANITIZE_SPECIAL_CHARS));
    $current_condition  = trim(filter_input(INPUT_POST, 'current_condition', FILTER_SANITIZE_SPECIAL_CHARS));
    $available_quantity = filter_input(INPUT_POST, 'available_quantity', FILTER_VALIDATE_INT);
    $purchase_date      = trim(filter_input(INPUT_POST, 'purchase_date', FILTER_SANITIZE_SPECIAL_CHARS));
    $remarks            = trim(filter_input(INPUT_POST, 'remarks', FILTER_SANITIZE_SPECIAL_CHARS));
    $unit               = trim(filter_input(INPUT_POST, 'unit', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

    if (!$user_id || empty($instrument_type) || !$available_quantity) {
        echo json_encode(['success' => false, 'message' => 'Validation error: Missing required field configurations.']);
        exit();
    }

    $stmt = $mysqli->prepare("INSERT INTO instrument_assets (user_id, district_id, range_id, instrument_type, current_condition, available_quantity, purchase_date, remarks, unit) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iiisssiss", $user_id, $district_id, $range_id, $instrument_type, $current_condition, $available_quantity, $purchase_date, $remarks, $unit);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Instrument item saved successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $stmt->error]);
        }
        $stmt->close();
    }
}