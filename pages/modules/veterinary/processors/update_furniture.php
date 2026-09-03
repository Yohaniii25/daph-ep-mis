<?php
session_start();
require_once '../../../../config/db_connect.php';
require_once '../../../../includes/approval_helper.php';

header('Content-Type: application/json');

$allowed_roles = ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon', 'provincial_director'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id                 = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $furniture_type     = trim(filter_input(INPUT_POST, 'furniture_type', FILTER_SANITIZE_SPECIAL_CHARS));
    $available_quantity = filter_input(INPUT_POST, 'available_quantity', FILTER_VALIDATE_INT);
    $date_received      = trim(filter_input(INPUT_POST, 'date_received', FILTER_SANITIZE_SPECIAL_CHARS));
    $current_condition  = trim(filter_input(INPUT_POST, 'current_condition', FILTER_SANITIZE_SPECIAL_CHARS));
    $remarks            = trim(filter_input(INPUT_POST, 'remarks', FILTER_SANITIZE_SPECIAL_CHARS));

    if (!$id || empty($furniture_type) || !$available_quantity) {
        echo json_encode(['success' => false, 'message' => 'Validation error']);
        exit();
    }

    // Fetch existing live record snapshot
    $stmt_curr = $mysqli->prepare("SELECT * FROM furniture_assets WHERE id = ?");
    $stmt_curr->bind_param("i", $id);
    $stmt_curr->execute();
    $old_data = $stmt_curr->get_result()->fetch_assoc();
    $stmt_curr->close();

    $new_data = [
        'furniture_type' => $furniture_type,
        'available_quantity' => $available_quantity,
        'date_received' => $date_received,
        'current_condition' => $current_condition,
        'remarks' => $remarks
    ];

    // Staging evaluation
    $district_id = $_SESSION['district_id'] ?? null;
    $range_id    = $_SESSION['range_id'] ?? null;
    $staging_res = stage_or_apply_edit(
        $mysqli, 
        'inventory', 
        'furniture_assets', 
        $id, 
        $furniture_type, 
        $old_data ?: [], 
        $new_data, 
        $district_id, 
        $range_id
    );

    if (!empty($staging_res['is_staged'])) {
        echo json_encode([
            'success' => true,
            'staged'  => true,
            'message' => 'Edit submitted successfully. Changes are pending authorization by the Provincial Director.'
        ]);
        exit();
    }

    // Direct update if pre-authorized
    $stmt = $mysqli->prepare("UPDATE furniture_assets SET furniture_type = ?, available_quantity = ?, date_received = ?, current_condition = ?, remarks = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("sisssi", $furniture_type, $available_quantity, $date_received, $current_condition, $remarks, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Furniture asset updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'DB error: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement.']);
    }
}
exit();
