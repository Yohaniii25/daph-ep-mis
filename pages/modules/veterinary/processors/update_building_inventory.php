<?php
session_start();
require_once '../../../../config/db_connect.php';
require_once '../../../../includes/approval_helper.php';

header('Content-Type: application/json');

$allowed_roles = ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon', 'provincial_director', 'district_dd', 'deputy_director_district'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized entry request.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id                 = isset($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT) : 0;
    $location           = isset($_POST['location']) ? trim(htmlspecialchars($_POST['location'])) : '';
    $land_asset_id      = isset($_POST['land_asset_id']) ? filter_var($_POST['land_asset_id'], FILTER_VALIDATE_INT) : 0;
    $inventory_item     = isset($_POST['inventory_item']) ? trim(htmlspecialchars($_POST['inventory_item'])) : '';
    $available_quantity = isset($_POST['available_quantity']) ? filter_var($_POST['available_quantity'], FILTER_VALIDATE_INT) : 0;
    $current_condition  = isset($_POST['current_condition']) ? trim(htmlspecialchars($_POST['current_condition'])) : '';
    $specification      = isset($_POST['specification']) ? trim(htmlspecialchars($_POST['specification'])) : '';
    $remarks            = isset($_POST['remarks']) ? trim(htmlspecialchars($_POST['remarks'])) : '';
    $unit               = isset($_POST['unit']) ? trim(htmlspecialchars($_POST['unit'])) : '';

    $initial_count      = isset($_POST['initial_count']) ? filter_var($_POST['initial_count'], FILTER_VALIDATE_INT) : null;

    if (!empty($location) && in_array($location, ['Office', 'Quarters'])) {
        $district_id = !empty($_SESSION['district_id']) ? intval($_SESSION['district_id']) : 0;
        $range_id    = !empty($_SESSION['range_id']) ? intval($_SESSION['range_id']) : 0;
        $user_id     = $_SESSION['user_id'] ?? 0;

        $stmt_la = $mysqli->prepare("
            SELECT id FROM land_assets 
            WHERE property_name = ? 
              AND (range_id = ? OR (range_id = 0 AND district_id = ?)) 
              AND is_active = 1 
            ORDER BY id DESC LIMIT 1
        ");
        $stmt_la->bind_param("sii", $location, $range_id, $district_id);
        $stmt_la->execute();
        $la_res = $stmt_la->get_result();
        if ($la_row = $la_res->fetch_assoc()) {
            $land_asset_id = intval($la_row['id']);
        } else {
            $stmt_ins = $mysqli->prepare("
                INSERT INTO land_assets 
                (user_id, district_id, range_id, property_name, land_extent, building_area, land_status, deed_reference, deed_description, unit, is_active)
                VALUES (?, ?, ?, ?, 'Standard Facility', '', 'State Owned', 'Standard Registry', 'Designated facility', 'range_veterinary_officer', 1)
            ");
            $stmt_ins->bind_param("iiis", $user_id, $district_id, $range_id, $location);
            $stmt_ins->execute();
            $land_asset_id = $stmt_ins->insert_id;
            $stmt_ins->close();
        }
        $stmt_la->close();
    }

    if (!$id || !$land_asset_id || empty($inventory_item) || $available_quantity === false || $available_quantity < 0) {
        echo json_encode(['success' => false, 'message' => 'Validation failed. Required values missing or invalid.']);
        exit();
    }

    // Strict condition validation
    $valid_conditions = ['Good', 'Fair', 'Damaged'];
    if (!in_array($current_condition, $valid_conditions, true)) {
        echo json_encode(['success' => false, 'message' => 'Validation failed: Condition must strictly be Good, Fair, or Damaged.']);
        exit();
    }

    // Fetch existing live record snapshot
    $stmt_curr = $mysqli->prepare("SELECT * FROM building_inventories WHERE id = ?");
    $stmt_curr->bind_param("i", $id);
    $stmt_curr->execute();
    $old_data = $stmt_curr->get_result()->fetch_assoc();
    $stmt_curr->close();

    if (!$old_data) {
        echo json_encode(['success' => false, 'message' => 'Record not found']);
        exit();
    }

    // Resolve unit fallback if not passed
    if ($unit === '' && isset($old_data['unit'])) {
        $unit = $old_data['unit'];
    }

    // Detect Inter-Departmental Transfer & Notify Provincial Director
    check_and_notify_unit_transfer(
        $mysqli, 
        $inventory_item, 
        $old_data['unit'] ?? '', 
        $unit, 
        'pages/modules/pd/pending_approvals.php'
    );

    // Resolve district and range from land asset
    $land_info = null;
    $stmt_land = $mysqli->prepare("SELECT district_id, range_id FROM land_assets WHERE id = ?");
    $stmt_land->bind_param("i", $land_asset_id);
    $stmt_land->execute();
    $land_info = $stmt_land->get_result()->fetch_assoc();
    $stmt_land->close();

    $district_id = !empty($land_info['district_id']) ? intval($land_info['district_id']) : intval($_SESSION['district_id'] ?? 0);
    $range_id    = !empty($land_info['range_id']) ? intval($land_info['range_id']) : ($_SESSION['range_id'] ?? null);

    // Jurisdiction check for District DD
    if (in_array($_SESSION['role'], ['district_dd', 'deputy_director_district'])) {
        $user_dist = intval($_SESSION['district_id'] ?? 0);
        if ($user_dist > 0 && $district_id > 0 && $district_id !== $user_dist) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Record does not belong to your assigned district.']);
            exit();
        }
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

    $new_data = [
        'land_asset_id'      => $land_asset_id,
        'inventory_item'     => $inventory_item,
        'specification'      => $specification,
        'current_condition'  => $current_condition,
        'available_quantity' => $available_quantity,
        'initial_count'      => $initial_count,
        'remarks'            => $remarks,
        'unit'               => $unit
    ];

    // Staging evaluation
    $staging_res = stage_or_apply_edit(
        $mysqli, 
        'inventory', 
        'building_inventories', 
        $id, 
        $inventory_item, 
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
    $stmt = $mysqli->prepare("
        UPDATE building_inventories SET 
            land_asset_id = ?,
            inventory_item = ?,
            specification = ?,
            current_condition = ?,
            available_quantity = ?,
            initial_count = ?,
            remarks = ?,
            unit = ?
        WHERE id = ?
    ");

    if ($stmt) {
        $stmt->bind_param("isssiissi", $land_asset_id, $inventory_item, $specification, $current_condition, $available_quantity, $initial_count, $remarks, $unit, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Inventory item updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database failure: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare update query statement.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
exit();
