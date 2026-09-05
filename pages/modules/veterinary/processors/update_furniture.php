<?php
session_start();
require_once '../../../../config/db_connect.php';
require_once '../../../../includes/approval_helper.php';

header('Content-Type: application/json');

$allowed_roles = ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon', 'provincial_director', 'district_dd', 'deputy_director_district'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id                 = isset($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT) : 0;
    $furniture_type     = isset($_POST['furniture_type']) ? trim(htmlspecialchars($_POST['furniture_type'])) : '';
    $available_quantity = isset($_POST['available_quantity']) ? filter_var($_POST['available_quantity'], FILTER_VALIDATE_INT) : 0;
    $date_received      = isset($_POST['date_received']) ? trim(htmlspecialchars($_POST['date_received'])) : '';
    $current_condition  = isset($_POST['current_condition']) ? trim(htmlspecialchars($_POST['current_condition'])) : '';
    $remarks            = isset($_POST['remarks']) ? trim(htmlspecialchars($_POST['remarks'])) : '';

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

    if (!$old_data) {
        echo json_encode(['success' => false, 'message' => 'Record not found']);
        exit();
    }

    // Resolve district and range
    $district_id = !empty($old_data['district_id']) ? intval($old_data['district_id']) : intval($_SESSION['district_id'] ?? 0);
    $range_id    = !empty($old_data['range_id']) ? intval($old_data['range_id']) : ($_SESSION['range_id'] ?? null);

    // Jurisdiction check for District DD
    if (in_array($_SESSION['role'], ['district_dd', 'deputy_director_district'])) {
        $user_dist = intval($_SESSION['district_id'] ?? 0);
        if ($user_dist > 0 && $district_id > 0 && $district_id !== $user_dist) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Record does not belong to your assigned district.']);
            exit();
        }
    }

    $new_data = [
        'furniture_type' => $furniture_type,
        'available_quantity' => $available_quantity,
        'date_received' => $date_received,
        'current_condition' => $current_condition,
        'remarks' => $remarks
    ];

    // Staging evaluation
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
            'message' => 'Changes submitted successfully. Awaiting final approval from the Provincial Director.'
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
