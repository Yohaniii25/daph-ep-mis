<?php
session_start();
require_once __DIR__ . '/../../../../config/db_connect.php';
require_once __DIR__ . '/../../../../includes/approval_helper.php';

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
    $unit               = isset($_POST['unit']) ? trim(htmlspecialchars($_POST['unit'])) : '';

    $initial_count      = isset($_POST['initial_count']) ? filter_var($_POST['initial_count'], FILTER_VALIDATE_INT) : null;

    if (!$id || empty($furniture_type) || $available_quantity === false || $available_quantity < 0) {
        echo json_encode(['success' => false, 'message' => 'Validation error: required fields missing or invalid.']);
        exit();
    }

    $valid_conditions = ['Good', 'Fair', 'Damaged'];
    if (!in_array($current_condition, $valid_conditions, true)) {
        echo json_encode(['success' => false, 'message' => 'Validation failed: Condition must strictly be Good, Fair, or Damaged.']);
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

    if ($initial_count === null || $initial_count === false) {
        $initial_count = isset($old_data['initial_count']) ? intval($old_data['initial_count']) : $available_quantity;
    }

    // Resolve unit fallback if not passed
    if ($unit === '' && isset($old_data['unit'])) {
        $unit = $old_data['unit'];
    }

    // Detect Inter-Departmental Transfer & Notify Provincial Director
    check_and_notify_unit_transfer(
        $mysqli, 
        $furniture_type, 
        $old_data['unit'] ?? '', 
        $unit, 
        'pages/modules/pd/pending_approvals.php'
    );

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
        'furniture_type'     => $furniture_type,
        'available_quantity' => $available_quantity,
        'initial_count'      => $initial_count,
        'date_received'      => $date_received,
        'current_condition'  => $current_condition,
        'remarks'            => $remarks,
        'unit'               => $unit
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
    $stmt = $mysqli->prepare("UPDATE furniture_assets SET furniture_type = ?, available_quantity = ?, initial_count = ?, date_received = ?, current_condition = ?, remarks = ?, unit = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("siissssi", $furniture_type, $available_quantity, $initial_count, $date_received, $current_condition, $remarks, $unit, $id);
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
