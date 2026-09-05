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
    $id                = isset($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT) : 0;
    $vehicle_type      = isset($_POST['vehicle_type']) ? trim(htmlspecialchars($_POST['vehicle_type'])) : '';
    $vehicle_number    = isset($_POST['vehicle_number']) ? trim(htmlspecialchars($_POST['vehicle_number'])) : '';
    $chassis_number    = isset($_POST['chassis_number']) ? trim(htmlspecialchars($_POST['chassis_number'])) : '';
    $current_condition = isset($_POST['current_condition']) ? trim(htmlspecialchars($_POST['current_condition'])) : '';
    $other_details     = isset($_POST['other_details']) ? trim(htmlspecialchars($_POST['other_details'])) : '';

    if (!$id || empty($vehicle_type) || empty($vehicle_number)) {
        echo json_encode(['success' => false, 'message' => 'Validation error']);
        exit();
    }

    // Fetch existing live record snapshot
    $stmt_curr = $mysqli->prepare("SELECT * FROM registered_vehicles WHERE id = ?");
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
        'vehicle_type'      => $vehicle_type,
        'vehicle_number'    => $vehicle_number,
        'chassis_number'    => $chassis_number,
        'current_condition' => $current_condition,
        'other_details'     => $other_details
    ];

    // Staging evaluation
    $target_desc = $vehicle_type . ' (' . $vehicle_number . ')';
    $staging_res = stage_or_apply_edit(
        $mysqli, 
        'inventory', 
        'registered_vehicles', 
        $id, 
        $target_desc, 
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
    $stmt = $mysqli->prepare("UPDATE registered_vehicles SET vehicle_type = ?, vehicle_number = ?, chassis_number = ?, current_condition = ?, other_details = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("sssssi", $vehicle_type, $vehicle_number, $chassis_number, $current_condition, $other_details, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Vehicle updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'DB error: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare query statement.']);
    }
}
exit();
