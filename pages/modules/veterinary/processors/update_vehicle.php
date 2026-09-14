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
    $unit              = isset($_POST['unit']) ? trim(htmlspecialchars($_POST['unit'])) : '';

    $issue_order_no     = isset($_POST['issue_order_no']) ? trim(htmlspecialchars($_POST['issue_order_no'])) : '';
    $received_from      = isset($_POST['received_from']) ? trim(htmlspecialchars($_POST['received_from'])) : '';
    $receipt_no         = isset($_POST['receipt_no']) ? trim(htmlspecialchars($_POST['receipt_no'])) : '';
    $specification      = isset($_POST['specification']) ? trim(htmlspecialchars($_POST['specification'])) : '';
    $initial_count      = isset($_POST['initial_count']) ? filter_var($_POST['initial_count'], FILTER_VALIDATE_INT) : null;
    $received_quantity  = isset($_POST['received_quantity']) ? filter_var($_POST['received_quantity'], FILTER_VALIDATE_INT) : null;
    $remarks            = isset($_POST['remarks']) ? trim(htmlspecialchars($_POST['remarks'])) : '';

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

    if ($initial_count === null || $initial_count === false) {
        $initial_count = isset($old_data['initial_count']) ? intval($old_data['initial_count']) : intval($old_data['available_quantity'] ?? 1);
    }
    if ($received_quantity === null || $received_quantity === false) {
        $received_quantity = isset($old_data['received_quantity']) ? intval($old_data['received_quantity']) : 0;
    }

    // Availability Auto-Calculation: initial baseline stock + received amounts
    $available_quantity = $initial_count + $received_quantity;

    // Resolve unit fallback if not passed
    if ($unit === '' && isset($old_data['unit'])) {
        $unit = $old_data['unit'];
    }

    $target_desc = $vehicle_type . ' (' . $vehicle_number . ')';

    // Detect Inter-Departmental Transfer & Notify Provincial Director
    check_and_notify_unit_transfer(
        $mysqli, 
        $target_desc, 
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
        'vehicle_type'       => $vehicle_type,
        'vehicle_number'     => $vehicle_number,
        'chassis_number'     => $chassis_number,
        'current_condition'  => $current_condition,
        'other_details'      => $other_details,
        'unit'               => $unit,
        'issue_order_no'     => $issue_order_no,
        'received_from'      => $received_from,
        'receipt_no'         => $receipt_no,
        'specification'      => $specification,
        'available_quantity' => $available_quantity,
        'initial_count'      => $initial_count,
        'received_quantity'  => $received_quantity,
        'remarks'            => $remarks
    ];

    // Staging evaluation
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
    $stmt = $mysqli->prepare("UPDATE registered_vehicles SET vehicle_type = ?, vehicle_number = ?, chassis_number = ?, current_condition = ?, other_details = ?, unit = ?, issue_order_no = ?, received_from = ?, receipt_no = ?, specification = ?, available_quantity = ?, initial_count = ?, received_quantity = ?, remarks = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ssssssssssiiisi", $vehicle_type, $vehicle_number, $chassis_number, $current_condition, $other_details, $unit, $issue_order_no, $received_from, $receipt_no, $specification, $available_quantity, $initial_count, $received_quantity, $remarks, $id);
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
