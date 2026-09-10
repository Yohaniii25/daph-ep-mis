<?php
/**
 * pages/modules/veterinary/processors/process_board_of_survey.php
 * Handles formal Board of Survey disposal and removal workflow for inventory items
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$allowed_roles = [
    'veterinary_surgeon', 
    'government_veterinary_surgeon', 
    'additional_veterinary_surgeon', 
    'provincial_director', 
    'district_dd', 
    'deputy_director_district',
    'administrator'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized operation. Only authorized officers can execute Board of Survey removals.']);
    exit();
}

require_once __DIR__ . '/../../../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$user_id            = intval($_SESSION['user_id'] ?? 0);
$id                 = isset($_POST['id']) ? intval($_POST['id']) : (filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0);
$asset_type         = trim($_POST['asset_type'] ?? 'building_inventory');
$removal_status     = trim($_POST['removal_status'] ?? '');
$removal_quantity   = isset($_POST['removal_quantity']) ? intval($_POST['removal_quantity']) : (filter_input(INPUT_POST, 'removal_quantity', FILTER_VALIDATE_INT) ?: 1);
$board_of_survey_ref = trim($_POST['board_of_survey_ref'] ?? '');
$removal_date       = trim($_POST['removal_date'] ?? date('Y-m-d'));
$removal_remarks    = trim($_POST['removal_remarks'] ?? '');

// Validation
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing inventory item identifier.']);
    exit();
}

$valid_statuses = ['Destroyed', 'Repaired', 'Sold'];
if (!in_array($removal_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid removal status. Status must strictly be Destroyed, Repaired, or Sold.']);
    exit();
}

if (empty($board_of_survey_ref)) {
    echo json_encode(['success' => false, 'message' => 'Board of Survey reference documentation is mandatory for formal removal.']);
    exit();
}

if (!$removal_quantity || $removal_quantity < 1) {
    $removal_quantity = 1;
}

// Map asset type to database table
$table_map = [
    'building_inventory' => 'building_inventories',
    'furniture'          => 'furniture_assets',
    'machinery'          => 'machinery_assets',
    'instrument'         => 'instrument_assets',
    'counterfoil'        => 'counterfoil_assets'
];

$table = $table_map[$asset_type] ?? 'building_inventories';

// Fetch current item state
$stmt_curr = $mysqli->prepare("SELECT * FROM `$table` WHERE id = ?");
if (!$stmt_curr) {
    echo json_encode(['success' => false, 'message' => 'Database error preparing item query: ' . $mysqli->error]);
    exit();
}
$stmt_curr->bind_param("i", $id);
$stmt_curr->execute();
$item = $stmt_curr->get_result()->fetch_assoc();
$stmt_curr->close();

if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Inventory record not found in system.']);
    exit();
}

$curr_available = intval($item['available_quantity'] ?? 1);

if ($removal_quantity >= $curr_available) {
    // Complete decommission: remove full quantity from active circulation
    $upd_stmt = $mysqli->prepare("
        UPDATE `$table` 
        SET available_quantity = 0,
            is_active = 0,
            removal_status = ?,
            board_of_survey_ref = ?,
            removal_date = ?,
            removal_remarks = ?,
            removal_authorized_by = ?
        WHERE id = ?
    ");
    $upd_stmt->bind_param("ssssii", $removal_status, $board_of_survey_ref, $removal_date, $removal_remarks, $user_id, $id);
    $exec_ok = $upd_stmt->execute();
    $upd_stmt->close();
} else {
    // Partial decommission: reduce active count and clone decommissioned record for audit
    $remaining_qty = $curr_available - $removal_quantity;
    
    // 1. Update existing row with remaining active count
    $upd_active = $mysqli->prepare("UPDATE `$table` SET available_quantity = ? WHERE id = ?");
    $upd_active->bind_param("ii", $remaining_qty, $id);
    $upd_active->execute();
    $upd_active->close();

    // 2. Clone a decommissioned record capturing the Board of Survey removal
    if ($table === 'building_inventories') {
        $ins_archived = $mysqli->prepare("
            INSERT INTO building_inventories 
            (land_asset_id, user_id, training_center_id, farm_id, user_category, inventory_item, specification, current_condition, available_quantity, initial_count, remarks, is_active, removal_status, board_of_survey_ref, removal_date, removal_remarks, removal_authorized_by, unit)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?)
        ");
        $initial_count_val = intval($item['initial_count'] ?? $curr_available);
        $ins_archived->bind_param(
            "iiiissssiisssssis",
            $item['land_asset_id'],
            $item['user_id'],
            $item['training_center_id'],
            $item['farm_id'],
            $item['user_category'],
            $item['inventory_item'],
            $item['specification'],
            $item['current_condition'],
            $removal_quantity,
            $initial_count_val,
            $item['remarks'],
            $removal_status,
            $board_of_survey_ref,
            $removal_date,
            $removal_remarks,
            $user_id,
            $item['unit']
        );
        $ins_archived->execute();
        $ins_archived->close();
    } else {
        // General asset clone
        $item_type_col = ($table === 'furniture_assets') ? 'furniture_type' : (($table === 'machinery_assets') ? 'machinery_type' : (($table === 'instrument_assets') ? 'instrument_type' : 'counterfoil_type'));
        $item_type_val = $item[$item_type_col] ?? '';
        $ins_arch = $mysqli->prepare("
            INSERT INTO `$table` 
            (user_id, farm_id, user_category, district_id, range_id, `$item_type_col`, current_condition, available_quantity, initial_count, remarks, is_active, removal_status, board_of_survey_ref, removal_date, removal_remarks, removal_authorized_by, unit)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?)
        ");
        $initial_count_val = intval($item['initial_count'] ?? $curr_available);
        $ins_arch->bind_param(
            "iisiissiisssssis",
            $item['user_id'],
            $item['farm_id'],
            $item['user_category'],
            $item['district_id'],
            $item['range_id'],
            $item_type_val,
            $item['current_condition'],
            $removal_quantity,
            $initial_count_val,
            $item['remarks'],
            $removal_status,
            $board_of_survey_ref,
            $removal_date,
            $removal_remarks,
            $user_id,
            $item['unit']
        );
        $ins_arch->execute();
        $ins_arch->close();
    }
    $exec_ok = true;
}

if ($exec_ok) {
    echo json_encode([
        'success' => true, 
        'message' => "Item successfully decommissioned as '{$removal_status}' under Board of Survey document [{$board_of_survey_ref}]."
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $mysqli->error]);
}
