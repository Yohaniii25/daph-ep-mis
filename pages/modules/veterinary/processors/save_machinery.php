<?php
session_start();
require_once __DIR__ . '/../../../../config/db_connect.php';
header('Content-Type: application/json');

$allowed_roles = ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon', 'provincial_director', 'district_dd', 'deputy_director_district'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized operation block.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id     = $_SESSION['user_id'] ?? null;
    $district_id = !empty($_POST['district_id']) ? intval($_POST['district_id']) : ($_SESSION['district_id'] ?? null);
    $range_id    = !empty($_POST['range_id']) ? intval($_POST['range_id']) : ($_SESSION['range_id'] ?? null);

    $machinery_type     = trim(htmlspecialchars($_POST['machinery_type'] ?? ''));
    $current_condition  = trim(htmlspecialchars($_POST['current_condition'] ?? ''));
    $available_quantity = isset($_POST['available_quantity']) ? intval($_POST['available_quantity']) : 0;
    $initial_count      = isset($_POST['initial_count']) ? intval($_POST['initial_count']) : $available_quantity;
    $purchase_date      = trim(htmlspecialchars($_POST['purchase_date'] ?? ''));
    $remarks            = trim(htmlspecialchars($_POST['remarks'] ?? ''));
    $unit               = trim(htmlspecialchars($_POST['unit'] ?? ''));

    if (!$user_id || empty($machinery_type) || $available_quantity < 0) {
        echo json_encode(['success' => false, 'message' => 'Validation error: Missing mandatory attributes.']);
        exit();
    }

    $valid_conditions = ['Good', 'Fair', 'Damaged'];
    if (!in_array($current_condition, $valid_conditions, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid condition. Allowed values: Good, Fair, Damaged.']);
        exit();
    }

    $stmt = $mysqli->prepare("INSERT INTO machinery_assets (user_id, district_id, range_id, machinery_type, current_condition, available_quantity, initial_count, purchase_date, remarks, unit, removal_status, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', 1)");
    if ($stmt) {
        $stmt->bind_param("iiisssisss", $user_id, $district_id, $range_id, $machinery_type, $current_condition, $available_quantity, $initial_count, $purchase_date, $remarks, $unit);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Machinery asset added successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Execution Error: ' . $stmt->error]);
        }
        $stmt->close();
    }
}