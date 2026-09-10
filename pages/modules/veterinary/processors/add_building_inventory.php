<?php
session_start();
require_once __DIR__ . '/../../../../config/db_connect.php';

header('Content-Type: application/json');

$allowed_roles = ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon', 'provincial_director', 'district_dd', 'deputy_director_district'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized submission access rejected.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'] ?? null;
    
    $location           = trim($_POST['location'] ?? filter_input(INPUT_POST, 'location', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $land_asset_id      = isset($_POST['land_asset_id']) ? intval($_POST['land_asset_id']) : filter_input(INPUT_POST, 'land_asset_id', FILTER_VALIDATE_INT);
    $inventory_item     = trim($_POST['inventory_item'] ?? filter_input(INPUT_POST, 'inventory_item', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $available_quantity = isset($_POST['available_quantity']) ? intval($_POST['available_quantity']) : filter_input(INPUT_POST, 'available_quantity', FILTER_VALIDATE_INT);
    $current_condition  = trim($_POST['current_condition'] ?? filter_input(INPUT_POST, 'current_condition', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $specification      = trim($_POST['specification'] ?? filter_input(INPUT_POST, 'specification', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $remarks            = trim($_POST['remarks'] ?? filter_input(INPUT_POST, 'remarks', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $unit               = trim($_POST['unit'] ?? filter_input(INPUT_POST, 'unit', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

    // Resolve location and link with land_assets
    if (!empty($location) && in_array($location, ['Office', 'Quarters'])) {
        $district_id = !empty($_SESSION['district_id']) ? intval($_SESSION['district_id']) : 0;
        $range_id    = !empty($_SESSION['range_id']) ? intval($_SESSION['range_id']) : 0;

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
            // Automatically provision a land_assets record for this location if not yet created
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

    $initial_count      = isset($_POST['initial_count']) ? intval($_POST['initial_count']) : $available_quantity;
    if ($initial_count <= 0 && $available_quantity > 0) {
        $initial_count = $available_quantity;
    }

    // Strict condition validation
    $valid_conditions = ['Good', 'Fair', 'Damaged'];
    if (!in_array($current_condition, $valid_conditions, true)) {
        echo json_encode(['success' => false, 'message' => 'Validation failed: Condition must strictly be Good, Fair, or Damaged.']);
        exit();
    }

    if (!$user_id || !$land_asset_id || empty($inventory_item) || $available_quantity === null) {
        echo json_encode(['success' => false, 'message' => 'Validation failed. Please select a valid location (Office or Quarters) and fill required values.']);
        exit();
    }

    $stmt = $mysqli->prepare("INSERT INTO building_inventories (land_asset_id, user_id, inventory_item, specification, current_condition, available_quantity, initial_count, remarks, unit, removal_status, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', 1)");
    
    if ($stmt) {
        $stmt->bind_param("iisssiiss", $land_asset_id, $user_id, $inventory_item, $specification, $current_condition, $available_quantity, $initial_count, $remarks, $unit);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Inventory item logged successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database failure: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement.']);
    }
}