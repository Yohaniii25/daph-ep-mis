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
    $counterfoil_type   = isset($_POST['counterfoil_type']) ? trim(htmlspecialchars($_POST['counterfoil_type'])) : '';
    $current_condition  = isset($_POST['current_condition']) ? trim(htmlspecialchars($_POST['current_condition'])) : '';
    $available_quantity = isset($_POST['available_quantity']) ? filter_var($_POST['available_quantity'], FILTER_VALIDATE_INT) : 0;
    $purchase_date      = isset($_POST['purchase_date']) ? trim(htmlspecialchars($_POST['purchase_date'])) : '';
    $remarks            = isset($_POST['remarks']) ? trim(htmlspecialchars($_POST['remarks'])) : '';
    $unit               = isset($_POST['unit']) ? trim(htmlspecialchars($_POST['unit'])) : '';

    $initial_count      = isset($_POST['initial_count']) ? filter_var($_POST['initial_count'], FILTER_VALIDATE_INT) : null;

    if (!$id || empty($counterfoil_type) || $available_quantity === false || $available_quantity < 0) {
        echo json_encode(['success' => false, 'message' => 'Validation error: required fields missing or invalid.']);
        exit();
    }

    $valid_conditions = ['Good', 'Fair', 'Damaged'];
    if (!in_array($current_condition, $valid_conditions, true)) {
        echo json_encode(['success' => false, 'message' => 'Validation failed: Condition must strictly be Good, Fair, or Damaged.']);
        exit();
    }

    // Fetch existing live record snapshot
    $stmt_curr = $mysqli->prepare("SELECT * FROM counterfoil_assets WHERE id = ?");
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

    // Automated Quantity Deduction: If condition updated to "Damaged", automatically deduct 1 from active circulating quantity
    if ($current_condition === 'Damaged' && ($old_data['current_condition'] ?? '') !== 'Damaged') {
        $old_available = intval($old_data['available_quantity'] ?? 0);
        if ($available_quantity >= $old_available) {
            $available_quantity = max(0, $old_available - 1);
        } else {
            $available_quantity = max(0, $available_quantity);
        }
    }

    // Resolve unit fallback if not passed
    if ($unit === '' && isset($old_data['unit'])) {
        $unit = $old_data['unit'];
    }

    // Detect Inter-Departmental Transfer & Notify Provincial Director
    check_and_notify_unit_transfer(
        $mysqli, 
        $counterfoil_type, 
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
        'counterfoil_type'   => $counterfoil_type,
        'current_condition'  => $current_condition,
        'available_quantity' => $available_quantity,
        'initial_count'      => $initial_count,
        'purchase_date'      => $purchase_date,
        'remarks'            => $remarks,
        'unit'               => $unit
    ];

    // Staging evaluation
    $staging_res = stage_or_apply_edit(
        $mysqli, 
        'inventory', 
        'counterfoil_assets', 
        $id, 
        $counterfoil_type, 
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
    $stmt = $mysqli->prepare("UPDATE counterfoil_assets SET counterfoil_type = ?, current_condition = ?, available_quantity = ?, initial_count = ?, purchase_date = ?, remarks = ?, unit = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ssiisssi", $counterfoil_type, $current_condition, $available_quantity, $initial_count, $purchase_date, $remarks, $unit, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Counterfoil asset record updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'DB error: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement.']);
    }
}
exit();
