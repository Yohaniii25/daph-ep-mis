<?php
session_start();
require_once '../../../../config/db_connect.php';
header('Content-Type: application/json');

$allowed_roles = ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon', 'provincial_director', 'district_dd', 'deputy_director_district'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized submission intercept execution halted.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id     = $_SESSION['user_id'] ?? null;
    $district_id = !empty($_POST['district_id']) ? intval($_POST['district_id']) : ($_SESSION['district_id'] ?? null);
    $range_id    = !empty($_POST['range_id']) ? intval($_POST['range_id']) : ($_SESSION['range_id'] ?? null);

    $furniture_type     = trim(filter_input(INPUT_POST, 'furniture_type', FILTER_SANITIZE_SPECIAL_CHARS));
    $available_quantity = filter_input(INPUT_POST, 'available_quantity', FILTER_VALIDATE_INT);
    $date_received      = trim(filter_input(INPUT_POST, 'date_received', FILTER_SANITIZE_SPECIAL_CHARS));
    $current_condition  = trim(filter_input(INPUT_POST, 'current_condition', FILTER_SANITIZE_SPECIAL_CHARS));
    $remarks            = trim(filter_input(INPUT_POST, 'remarks', FILTER_SANITIZE_SPECIAL_CHARS));
    $unit               = trim(filter_input(INPUT_POST, 'unit', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

    if (!$user_id || empty($furniture_type) || !$available_quantity) {
        echo json_encode(['success' => false, 'message' => 'Validation checklist incomplete. Data execution failure.']);
        exit();
    }

    $stmt = $mysqli->prepare("INSERT INTO furniture_assets (user_id, district_id, range_id, furniture_type, current_condition, available_quantity, date_received, remarks, unit) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iiissssss", $user_id, $district_id, $range_id, $furniture_type, $current_condition, $available_quantity, $date_received, $remarks, $unit);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Furniture item saved successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $stmt->error]);
        }
        $stmt->close();
    }
}