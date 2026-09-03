<?php
session_start();
require_once '../../../../config/db_connect.php';
require_once '../../../../includes/approval_helper.php';

header('Content-Type: application/json');

$allowed_roles = ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon', 'provincial_director'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized entry request.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id                 = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $land_asset_id      = filter_input(INPUT_POST, 'land_asset_id', FILTER_VALIDATE_INT);
    $inventory_item     = trim(filter_input(INPUT_POST, 'inventory_item', FILTER_SANITIZE_SPECIAL_CHARS));
    $available_quantity = filter_input(INPUT_POST, 'available_quantity', FILTER_VALIDATE_INT);
    $current_condition  = trim(filter_input(INPUT_POST, 'current_condition', FILTER_SANITIZE_SPECIAL_CHARS));
    $specification      = trim(filter_input(INPUT_POST, 'specification', FILTER_SANITIZE_SPECIAL_CHARS));
    $remarks            = trim(filter_input(INPUT_POST, 'remarks', FILTER_SANITIZE_SPECIAL_CHARS));

    if (!$id || !$land_asset_id || empty($inventory_item) || !$available_quantity) {
        echo json_encode(['success' => false, 'message' => 'Validation failed. Required values missing.']);
        exit();
    }

    // Fetch existing live record snapshot
    $stmt_curr = $mysqli->prepare("SELECT * FROM building_inventories WHERE id = ?");
    $stmt_curr->bind_param("i", $id);
    $stmt_curr->execute();
    $old_data = $stmt_curr->get_result()->fetch_assoc();
    $stmt_curr->close();

    $new_data = [
        'land_asset_id'      => $land_asset_id,
        'inventory_item'     => $inventory_item,
        'specification'      => $specification,
        'current_condition'  => $current_condition,
        'available_quantity' => $available_quantity,
        'remarks'            => $remarks
    ];

    // Staging evaluation
    $district_id = $_SESSION['district_id'] ?? null;
    $range_id    = $_SESSION['range_id'] ?? null;
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
            'message' => 'Edit submitted successfully. Changes are pending authorization by the Provincial Director.'
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
            remarks = ?
        WHERE id = ?
    ");

    if ($stmt) {
        $stmt->bind_param("isssisi", $land_asset_id, $inventory_item, $specification, $current_condition, $available_quantity, $remarks, $id);
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
