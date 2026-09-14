<?php
// pages/modules/sms/processors/office_assets_crud.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../../config/db_connect.php';
require_once '../../../../includes/approval_helper.php';
require_once '../../../../includes/notification_helper.php';

$allowed_roles = ['sms', 'administrator', 'provincial_director', 'district_dd'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access denied.']);
        exit();
    } else {
        die("Access denied");
    }
}

$user_id = intval($_SESSION['user_id'] ?? 12);
$district_id = intval($_SESSION['district_id'] ?? 0);
$user_category = 'subject_matter_specialist';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

function respondJsonOrRedirect($is_ajax, $success, $msg, $redirect_url, $extra = []) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => $success, 'message' => $msg], $extra));
        exit();
    } else {
        if (!empty($extra['staged'])) {
            $_SESSION['staged_msg'] = $msg;
        }
        $_SESSION['msg'] = $msg;
        $_SESSION['msg_type'] = $success ? 'success' : 'danger';
        header("Location: " . $redirect_url);
        exit();
    }
}

$is_ajax = isset($_POST['is_ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strpos(strtolower($_SERVER['HTTP_X_REQUESTED_WITH']), 'xmlhttprequest') !== false);

// -------------------------------------------------------------
// 1. LAND & BUILDINGS CRUD
// -------------------------------------------------------------
if ($action === 'save_land') {
    $property_name    = trim($_POST['property_name'] ?? '');
    $land_extent      = trim($_POST['land_extent'] ?? '');
    $building_area    = trim($_POST['building_area'] ?? '');
    $land_status      = trim($_POST['land_status'] ?? 'State Owned');
    $deed_reference   = trim($_POST['deed_reference'] ?? '');
    $deed_description = trim($_POST['deed_description'] ?? '');

    if (empty($property_name)) {
        respondJsonOrRedirect($is_ajax, false, 'Property Name is required.', '../lands_buildings.php');
    }

    $stmt = $mysqli->prepare("INSERT INTO land_assets (user_id, user_category, district_id, range_id, property_name, land_extent, building_area, land_status, deed_reference, deed_description) VALUES (?, ?, ?, 0, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isissssss", $user_id, $user_category, $district_id, $property_name, $land_extent, $building_area, $land_status, $deed_reference, $deed_description);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Land property registered successfully.', '../lands_buildings.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to save land property: ' . $stmt->error, '../lands_buildings.php');
    }
}

if ($action === 'update_land') {
    $id               = intval($_POST['id'] ?? 0);
    $property_name    = trim($_POST['property_name'] ?? '');
    $land_extent      = trim($_POST['land_extent'] ?? '');
    $building_area    = trim($_POST['building_area'] ?? '');
    $land_status      = trim($_POST['land_status'] ?? 'State Owned');
    $deed_reference   = trim($_POST['deed_reference'] ?? '');
    $deed_description = trim($_POST['deed_description'] ?? '');

    if ($id <= 0 || empty($property_name)) {
        respondJsonOrRedirect($is_ajax, false, 'Invalid property ID or missing name.', '../lands_buildings.php');
    }

    $stmt = $mysqli->prepare("UPDATE land_assets SET property_name = ?, land_extent = ?, building_area = ?, land_status = ?, deed_reference = ?, deed_description = ? WHERE id = ? AND (user_category = 'subject_matter_specialist' OR user_id = ?)");
    $stmt->bind_param("ssssssii", $property_name, $land_extent, $building_area, $land_status, $deed_reference, $deed_description, $id, $user_id);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Land property updated successfully.', '../lands_buildings.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to update land property: ' . $stmt->error, '../lands_buildings.php');
    }
}

if ($action === 'delete_land') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $mysqli->prepare("UPDATE land_assets SET is_active = 0 WHERE id = ? AND (user_category = 'subject_matter_specialist' OR user_id = ?)");
        $stmt->bind_param("ii", $id, $user_id);
        if ($stmt->execute()) {
            respondJsonOrRedirect(false, true, 'Land property deactivated successfully.', '../lands_buildings.php');
        } else {
            respondJsonOrRedirect(false, false, 'Failed to deactivate land property: ' . $stmt->error, '../lands_buildings.php');
        }
    }
}

if ($action === 'save_inventory') {
    $land_asset_id      = intval($_POST['land_asset_id'] ?? 0);
    $property_name      = trim($_POST['property_name'] ?? '');
    $inventory_item     = trim($_POST['inventory_item'] ?? '');
    $inventory_number   = trim($_POST['inventory_number'] ?? '');
    $inventory_type     = trim($_POST['inventory_type'] ?? 'Equipment');
    $issue_order_no     = trim($_POST['issue_order_no'] ?? '');
    $received_from      = trim($_POST['received_from'] ?? '');
    $receipt_no         = trim($_POST['receipt_no'] ?? '');
    $specification      = trim($_POST['specification'] ?? '');
    $current_condition  = trim($_POST['current_condition'] ?? 'Good Condition');
    $initial_count      = intval($_POST['initial_count'] ?? 0);
    $received_quantity  = intval($_POST['received_quantity'] ?? 0);
    $remarks            = trim($_POST['remarks'] ?? '');

    // Availability Auto-Calculation: initial baseline stock + received amounts
    $available_quantity = $initial_count + $received_quantity;

    // Handle manual property text entry
    if ($land_asset_id <= 0 && !empty($property_name)) {
        $stmt_find = $mysqli->prepare("SELECT id FROM land_assets WHERE property_name = ? AND (user_category = 'subject_matter_specialist' OR user_id = ?) AND is_active = 1 LIMIT 1");
        $stmt_find->bind_param("si", $property_name, $user_id);
        $stmt_find->execute();
        $res_find = $stmt_find->get_result();
        if ($rf = $res_find->fetch_assoc()) {
            $land_asset_id = intval($rf['id']);
        } else {
            $stmt_new_la = $mysqli->prepare("INSERT INTO land_assets (user_id, user_category, property_name, land_status, is_active) VALUES (?, ?, ?, 'State Owned', 1)");
            $stmt_new_la->bind_param("iss", $user_id, $user_category, $property_name);
            $stmt_new_la->execute();
            $land_asset_id = $stmt_new_la->insert_id;
            $stmt_new_la->close();
        }
        $stmt_find->close();
    }

    if (empty($inventory_item)) {
        respondJsonOrRedirect($is_ajax, false, 'Inventory Item name is required.', '../lands_buildings.php?tab=inventory');
    }

    $stmt = $mysqli->prepare("INSERT INTO building_inventories (land_asset_id, user_id, user_category, inventory_item, inventory_number, inventory_type, issue_order_no, received_from, receipt_no, received_quantity, initial_count, available_quantity, specification, current_condition, remarks, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("iisssssssiiisss", $land_asset_id, $user_id, $user_category, $inventory_item, $inventory_number, $inventory_type, $issue_order_no, $received_from, $receipt_no, $received_quantity, $initial_count, $available_quantity, $specification, $current_condition, $remarks);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Building inventory item logged successfully.', '../lands_buildings.php?tab=inventory');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to log inventory item: ' . $stmt->error, '../lands_buildings.php?tab=inventory');
    }
}

if ($action === 'update_inventory') {
    $id                 = intval($_POST['id'] ?? 0);
    $land_asset_id      = intval($_POST['land_asset_id'] ?? 0);
    $property_name      = trim($_POST['property_name'] ?? '');
    $inventory_item     = trim($_POST['inventory_item'] ?? '');
    $inventory_number   = trim($_POST['inventory_number'] ?? '');
    $inventory_type     = trim($_POST['inventory_type'] ?? 'Equipment');
    $issue_order_no     = trim($_POST['issue_order_no'] ?? '');
    $received_from      = trim($_POST['received_from'] ?? '');
    $receipt_no         = trim($_POST['receipt_no'] ?? '');
    $specification      = trim($_POST['specification'] ?? '');
    $current_condition  = trim($_POST['current_condition'] ?? 'Good Condition');
    $initial_count      = intval($_POST['initial_count'] ?? 0);
    $received_quantity  = intval($_POST['received_quantity'] ?? 0);
    $remarks            = trim($_POST['remarks'] ?? '');

    // Availability Auto-Calculation: initial baseline stock + received amounts
    $available_quantity = $initial_count + $received_quantity;

    // Handle manual property text entry
    if ($land_asset_id <= 0 && !empty($property_name)) {
        $stmt_find = $mysqli->prepare("SELECT id FROM land_assets WHERE property_name = ? AND (user_category = 'subject_matter_specialist' OR user_id = ?) AND is_active = 1 LIMIT 1");
        $stmt_find->bind_param("si", $property_name, $user_id);
        $stmt_find->execute();
        $res_find = $stmt_find->get_result();
        if ($rf = $res_find->fetch_assoc()) {
            $land_asset_id = intval($rf['id']);
        } else {
            $stmt_new_la = $mysqli->prepare("INSERT INTO land_assets (user_id, user_category, property_name, land_status, is_active) VALUES (?, ?, ?, 'State Owned', 1)");
            $stmt_new_la->bind_param("iss", $user_id, $user_category, $property_name);
            $stmt_new_la->execute();
            $land_asset_id = $stmt_new_la->insert_id;
            $stmt_new_la->close();
        }
        $stmt_find->close();
    }

    if ($id <= 0 || empty($inventory_item)) {
        respondJsonOrRedirect($is_ajax, false, 'Invalid item ID or missing details.', '../lands_buildings.php?tab=inventory');
    }

    $stmt = $mysqli->prepare("UPDATE building_inventories SET land_asset_id = ?, inventory_item = ?, inventory_number = ?, inventory_type = ?, issue_order_no = ?, received_from = ?, receipt_no = ?, received_quantity = ?, initial_count = ?, available_quantity = ?, specification = ?, current_condition = ?, remarks = ? WHERE id = ? AND (user_category = 'subject_matter_specialist' OR user_id = ?)");
    $stmt->bind_param("issssssiiisssiii", $land_asset_id, $inventory_item, $inventory_number, $inventory_type, $issue_order_no, $received_from, $receipt_no, $received_quantity, $initial_count, $available_quantity, $specification, $current_condition, $remarks, $id, $user_id);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Building inventory updated successfully.', '../lands_buildings.php?tab=inventory');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to update inventory: ' . $stmt->error, '../lands_buildings.php?tab=inventory');
    }
}

if ($action === 'delete_inventory') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM building_inventories WHERE id = ? AND (user_category = 'subject_matter_specialist' OR user_id = ?)");
        $stmt->bind_param("ii", $id, $user_id);
        if ($stmt->execute()) {
            respondJsonOrRedirect(false, true, 'Inventory item deleted successfully.', '../lands_buildings.php?tab=inventory');
        } else {
            respondJsonOrRedirect(false, false, 'Failed to delete inventory item: ' . $stmt->error, '../lands_buildings.php?tab=inventory');
        }
    }
}

// -------------------------------------------------------------
// 2. VEHICLES & FLEET REPAIRS CRUD
// -------------------------------------------------------------
if ($action === 'save_vehicle') {
    $vehicle_type       = trim($_POST['vehicle_type'] ?? '');
    $vehicle_number     = trim($_POST['vehicle_number'] ?? '');
    $chassis_number     = trim($_POST['chassis_number'] ?? '');
    $current_condition  = trim($_POST['current_condition'] ?? 'Operational');
    $other_details      = trim($_POST['other_details'] ?? '');
    $issue_order_no     = trim($_POST['issue_order_no'] ?? '');
    $received_from      = trim($_POST['received_from'] ?? '');
    $receipt_no         = trim($_POST['receipt_no'] ?? '');
    $initial_count      = max(0, intval($_POST['initial_count'] ?? 1));
    $received_quantity  = max(0, intval($_POST['received_quantity'] ?? 0));
    $available_quantity = $initial_count + $received_quantity;
    $specification      = trim($_POST['specification'] ?? '');
    $remarks            = trim($_POST['remarks'] ?? '');
    if (empty($remarks) && !empty($other_details)) {
        $remarks = $other_details;
    }

    if (empty($vehicle_type) || empty($vehicle_number)) {
        respondJsonOrRedirect($is_ajax, false, 'Vehicle Type and Vehicle Number are required.', '../vehicles.php');
    }

    $stmt = $mysqli->prepare("INSERT INTO registered_vehicles (user_id, user_category, district_id, range_id, vehicle_type, vehicle_number, chassis_number, current_condition, other_details, issue_order_no, received_from, receipt_no, available_quantity, initial_count, received_quantity, specification, remarks) VALUES (?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isisssssssssiiiss", $user_id, $user_category, $district_id, $vehicle_type, $vehicle_number, $chassis_number, $current_condition, $other_details, $issue_order_no, $received_from, $receipt_no, $available_quantity, $initial_count, $received_quantity, $specification, $remarks);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'SMS Vehicle registered successfully.', '../vehicles.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to register vehicle: ' . $stmt->error, '../vehicles.php');
    }
}

if ($action === 'update_vehicle') {
    $id                 = intval($_POST['id'] ?? 0);
    $vehicle_type       = trim($_POST['vehicle_type'] ?? '');
    $vehicle_number     = trim($_POST['vehicle_number'] ?? '');
    $chassis_number     = trim($_POST['chassis_number'] ?? '');
    $current_condition  = trim($_POST['current_condition'] ?? 'Operational');
    $other_details      = trim($_POST['other_details'] ?? '');
    $issue_order_no     = trim($_POST['issue_order_no'] ?? '');
    $received_from      = trim($_POST['received_from'] ?? '');
    $receipt_no         = trim($_POST['receipt_no'] ?? '');
    $initial_count      = max(0, intval($_POST['initial_count'] ?? 1));
    $received_quantity  = max(0, intval($_POST['received_quantity'] ?? 0));
    $available_quantity = $initial_count + $received_quantity;
    $specification      = trim($_POST['specification'] ?? '');
    $remarks            = trim($_POST['remarks'] ?? '');
    if (empty($remarks) && !empty($other_details)) {
        $remarks = $other_details;
    }

    if ($id <= 0 || empty($vehicle_type) || empty($vehicle_number)) {
        respondJsonOrRedirect($is_ajax, false, 'Invalid vehicle ID or missing details.', '../vehicles.php');
    }

    $stmt = $mysqli->prepare("UPDATE registered_vehicles SET vehicle_type = ?, vehicle_number = ?, chassis_number = ?, current_condition = ?, other_details = ?, issue_order_no = ?, received_from = ?, receipt_no = ?, available_quantity = ?, initial_count = ?, received_quantity = ?, specification = ?, remarks = ? WHERE id = ? AND (user_category = 'subject_matter_specialist' OR user_id = ?)");
    $stmt->bind_param("ssssssssiiissii", $vehicle_type, $vehicle_number, $chassis_number, $current_condition, $other_details, $issue_order_no, $received_from, $receipt_no, $available_quantity, $initial_count, $received_quantity, $specification, $remarks, $id, $user_id);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Vehicle record updated successfully.', '../vehicles.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to update vehicle: ' . $stmt->error, '../vehicles.php');
    }
}

if ($action === 'delete_vehicle') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM registered_vehicles WHERE id = ? AND (user_category = 'subject_matter_specialist' OR user_id = ?)");
        $stmt->bind_param("ii", $id, $user_id);
        if ($stmt->execute()) {
            respondJsonOrRedirect(false, true, 'Vehicle deleted successfully.', '../vehicles.php');
        } else {
            respondJsonOrRedirect(false, false, 'Failed to delete vehicle: ' . $stmt->error, '../vehicles.php');
        }
    }
}

if ($action === 'save_repair') {
    $vehicle_id         = intval($_POST['vehicle_id'] ?? 0);
    $repair_date        = !empty($_POST['repair_date']) ? $_POST['repair_date'] : date('Y-m-d');
    $repair_done        = trim($_POST['repair_done'] ?? '');
    $repair_description = trim($_POST['repair_description'] ?? '');
    $place_of_repair    = trim($_POST['place_of_repair'] ?? '');
    $invoice_ref        = trim($_POST['invoice_ref'] ?? '');
    $amount             = floatval($_POST['amount'] ?? 0.00);

    if ($vehicle_id <= 0 || empty($repair_done)) {
        respondJsonOrRedirect($is_ajax, false, 'Vehicle selection and Repair Details are required.', '../vehicles.php?tab=repairs');
    }

    $stmt = $mysqli->prepare("INSERT INTO vehicle_repairs (vehicle_id, user_id, user_category, repair_date, repair_done, repair_description, place_of_repair, invoice_ref, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissssssd", $vehicle_id, $user_id, $user_category, $repair_date, $repair_done, $repair_description, $place_of_repair, $invoice_ref, $amount);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Vehicle repair log added successfully.', '../vehicles.php?tab=repairs');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to log repair: ' . $stmt->error, '../vehicles.php?tab=repairs');
    }
}

if ($action === 'update_repair') {
    $id                 = intval($_POST['id'] ?? 0);
    $vehicle_id         = intval($_POST['vehicle_id'] ?? 0);
    $repair_date        = !empty($_POST['repair_date']) ? $_POST['repair_date'] : date('Y-m-d');
    $repair_done        = trim($_POST['repair_done'] ?? '');
    $repair_description = trim($_POST['repair_description'] ?? '');
    $place_of_repair    = trim($_POST['place_of_repair'] ?? '');
    $invoice_ref        = trim($_POST['invoice_ref'] ?? '');
    $amount             = floatval($_POST['amount'] ?? 0.00);

    if ($id <= 0 || $vehicle_id <= 0 || empty($repair_done)) {
        respondJsonOrRedirect($is_ajax, false, 'Invalid log ID or missing details.', '../vehicles.php?tab=repairs');
    }

    $stmt = $mysqli->prepare("UPDATE vehicle_repairs SET vehicle_id = ?, repair_date = ?, repair_done = ?, repair_description = ?, place_of_repair = ?, invoice_ref = ?, amount = ? WHERE id = ? AND (user_category = 'subject_matter_specialist' OR user_id = ?)");
    $stmt->bind_param("isssssdii", $vehicle_id, $repair_date, $repair_done, $repair_description, $place_of_repair, $invoice_ref, $amount, $id, $user_id);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Vehicle repair log updated successfully.', '../vehicles.php?tab=repairs');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to update repair log: ' . $stmt->error, '../vehicles.php?tab=repairs');
    }
}

if ($action === 'delete_repair') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM vehicle_repairs WHERE id = ? AND (user_category = 'subject_matter_specialist' OR user_id = ?)");
        $stmt->bind_param("ii", $id, $user_id);
        if ($stmt->execute()) {
            respondJsonOrRedirect(false, true, 'Repair log entry deleted successfully.', '../vehicles.php?tab=repairs');
        } else {
            respondJsonOrRedirect(false, false, 'Failed to delete repair log: ' . $stmt->error, '../vehicles.php?tab=repairs');
        }
    }
}

// -------------------------------------------------------------
// 3. FURNITURE ASSETS CRUD
// -------------------------------------------------------------
if ($action === 'save_furniture') {
    $furniture_type     = trim($_POST['furniture_type'] ?? '');
    $initial_count      = max(0, intval($_POST['initial_count'] ?? 1));
    $received_quantity  = max(0, intval($_POST['received_quantity'] ?? 0));
    $available_quantity = $initial_count + $received_quantity;
    $date_received      = !empty($_POST['date_received']) ? $_POST['date_received'] : null;
    $current_condition  = trim($_POST['current_condition'] ?? 'Good Condition');
    $issue_order_no     = trim($_POST['issue_order_no'] ?? '');
    $received_from      = trim($_POST['received_from'] ?? '');
    $receipt_no         = trim($_POST['receipt_no'] ?? '');
    $specification      = trim($_POST['specification'] ?? '');
    $remarks            = trim($_POST['remarks'] ?? '');

    if ($current_condition === 'Damaged') {
        $available_quantity = max(0, $available_quantity - 1);
    }

    if (empty($furniture_type)) {
        respondJsonOrRedirect($is_ajax, false, 'Furniture Type/Category is required.', '../furniture.php');
    }

    $stmt = $mysqli->prepare("INSERT INTO furniture_assets (user_id, user_category, district_id, range_id, furniture_type, available_quantity, initial_count, received_quantity, date_received, current_condition, issue_order_no, received_from, receipt_no, specification, remarks) VALUES (?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isisiiisssssss", $user_id, $user_category, $district_id, $furniture_type, $available_quantity, $initial_count, $received_quantity, $date_received, $current_condition, $issue_order_no, $received_from, $receipt_no, $specification, $remarks);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Furniture asset registered successfully.', '../furniture.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to register furniture: ' . $stmt->error, '../furniture.php');
    }
}

if ($action === 'update_furniture') {
    $id                 = intval($_POST['id'] ?? 0);
    $furniture_type     = trim($_POST['furniture_type'] ?? '');
    $initial_count      = max(0, intval($_POST['initial_count'] ?? 1));
    $received_quantity  = max(0, intval($_POST['received_quantity'] ?? 0));
    $available_quantity = $initial_count + $received_quantity;
    $date_received      = !empty($_POST['date_received']) ? $_POST['date_received'] : null;
    $current_condition  = trim($_POST['current_condition'] ?? 'Good Condition');
    $issue_order_no     = trim($_POST['issue_order_no'] ?? '');
    $received_from      = trim($_POST['received_from'] ?? '');
    $receipt_no         = trim($_POST['receipt_no'] ?? '');
    $specification      = trim($_POST['specification'] ?? '');
    $remarks            = trim($_POST['remarks'] ?? '');

    if ($current_condition === 'Damaged') {
        $available_quantity = max(0, $available_quantity - 1);
    }

    if ($id <= 0 || empty($furniture_type)) {
        respondJsonOrRedirect($is_ajax, false, 'Invalid item ID or missing details.', '../furniture.php');
    }

    $stmt = $mysqli->prepare("UPDATE furniture_assets SET furniture_type = ?, available_quantity = ?, initial_count = ?, received_quantity = ?, date_received = ?, current_condition = ?, issue_order_no = ?, received_from = ?, receipt_no = ?, specification = ?, remarks = ? WHERE id = ? AND (user_category = 'subject_matter_specialist' OR user_id = ?)");
    $stmt->bind_param("siiisssssssii", $furniture_type, $available_quantity, $initial_count, $received_quantity, $date_received, $current_condition, $issue_order_no, $received_from, $receipt_no, $specification, $remarks, $id, $user_id);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Furniture record updated successfully.', '../furniture.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to update furniture: ' . $stmt->error, '../furniture.php');
    }
}

if ($action === 'delete_furniture') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM furniture_assets WHERE id = ? AND (user_category = 'subject_matter_specialist' OR user_id = ?)");
        $stmt->bind_param("ii", $id, $user_id);
        if ($stmt->execute()) {
            respondJsonOrRedirect(false, true, 'Furniture record deleted successfully.', '../furniture.php');
        } else {
            respondJsonOrRedirect(false, false, 'Failed to delete furniture record: ' . $stmt->error, '../furniture.php');
        }
    }
}

// -------------------------------------------------------------
// 4. MACHINERY ASSETS CRUD
// -------------------------------------------------------------
if ($action === 'save_machinery') {
    $machinery_type     = trim($_POST['machinery_type'] ?? '');
    $initial_count      = max(0, intval($_POST['initial_count'] ?? 1));
    $received_quantity  = max(0, intval($_POST['received_quantity'] ?? 0));
    $available_quantity = $initial_count + $received_quantity;
    $purchase_date      = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $current_condition  = trim($_POST['current_condition'] ?? 'Operational');
    $issue_order_no     = trim($_POST['issue_order_no'] ?? '');
    $received_from      = trim($_POST['received_from'] ?? '');
    $receipt_no         = trim($_POST['receipt_no'] ?? '');
    $specification      = trim($_POST['specification'] ?? '');
    $remarks            = trim($_POST['remarks'] ?? '');

    if ($current_condition === 'Damaged') {
        $available_quantity = max(0, $available_quantity - 1);
    }

    if (empty($machinery_type)) {
        respondJsonOrRedirect($is_ajax, false, 'Machinery Type/Specification is required.', '../machineries.php');
    }

    $stmt = $mysqli->prepare("INSERT INTO machinery_assets (user_id, user_category, district_id, range_id, machinery_type, available_quantity, initial_count, received_quantity, purchase_date, current_condition, issue_order_no, received_from, receipt_no, specification, remarks) VALUES (?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isisiiisssssss", $user_id, $user_category, $district_id, $machinery_type, $available_quantity, $initial_count, $received_quantity, $purchase_date, $current_condition, $issue_order_no, $received_from, $receipt_no, $specification, $remarks);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Machinery asset registered successfully.', '../machineries.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to register machinery: ' . $stmt->error, '../machineries.php');
    }
}

if ($action === 'update_machinery') {
    $id                 = intval($_POST['id'] ?? 0);
    $machinery_type     = trim($_POST['machinery_type'] ?? '');
    $initial_count      = max(0, intval($_POST['initial_count'] ?? 1));
    $received_quantity  = max(0, intval($_POST['received_quantity'] ?? 0));
    $available_quantity = $initial_count + $received_quantity;
    $purchase_date      = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $current_condition  = trim($_POST['current_condition'] ?? 'Operational');
    $issue_order_no     = trim($_POST['issue_order_no'] ?? '');
    $received_from      = trim($_POST['received_from'] ?? '');
    $receipt_no         = trim($_POST['receipt_no'] ?? '');
    $specification      = trim($_POST['specification'] ?? '');
    $remarks            = trim($_POST['remarks'] ?? '');

    if ($current_condition === 'Damaged') {
        $available_quantity = max(0, $available_quantity - 1);
    }

    if ($id <= 0 || empty($machinery_type)) {
        respondJsonOrRedirect($is_ajax, false, 'Invalid item ID or missing details.', '../machineries.php');
    }

    $stmt = $mysqli->prepare("UPDATE machinery_assets SET machinery_type = ?, available_quantity = ?, initial_count = ?, received_quantity = ?, purchase_date = ?, current_condition = ?, issue_order_no = ?, received_from = ?, receipt_no = ?, specification = ?, remarks = ? WHERE id = ? AND (user_category = 'subject_matter_specialist' OR user_id = ?)");
    $stmt->bind_param("siiisssssssii", $machinery_type, $available_quantity, $initial_count, $received_quantity, $purchase_date, $current_condition, $issue_order_no, $received_from, $receipt_no, $specification, $remarks, $id, $user_id);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Machinery record updated successfully.', '../machineries.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to update machinery: ' . $stmt->error, '../machineries.php');
    }
}

if ($action === 'delete_machinery') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM machinery_assets WHERE id = ? AND (user_category = 'subject_matter_specialist' OR user_id = ?)");
        $stmt->bind_param("ii", $id, $user_id);
        if ($stmt->execute()) {
            respondJsonOrRedirect(false, true, 'Machinery record deleted successfully.', '../machineries.php');
        } else {
            respondJsonOrRedirect(false, false, 'Failed to delete machinery record: ' . $stmt->error, '../machineries.php');
        }
    }
}

// -------------------------------------------------------------
// 5. INSTRUMENT ASSETS CRUD
// -------------------------------------------------------------
if ($action === 'save_instrument') {
    $instrument_type    = trim($_POST['instrument_type'] ?? '');
    $initial_count      = max(0, intval($_POST['initial_count'] ?? 1));
    $received_quantity  = max(0, intval($_POST['received_quantity'] ?? 0));
    $available_quantity = $initial_count + $received_quantity;
    $purchase_date      = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $current_condition  = trim($_POST['current_condition'] ?? 'Operational');
    $issue_order_no     = trim($_POST['issue_order_no'] ?? '');
    $received_from      = trim($_POST['received_from'] ?? '');
    $receipt_no         = trim($_POST['receipt_no'] ?? '');
    $specification      = trim($_POST['specification'] ?? '');
    $remarks            = trim($_POST['remarks'] ?? '');

    if ($current_condition === 'Damaged') {
        $available_quantity = max(0, $available_quantity - 1);
    }

    if (empty($instrument_type)) {
        respondJsonOrRedirect($is_ajax, false, 'Instrument Type/Category is required.', '../instruments.php');
    }

    $stmt = $mysqli->prepare("INSERT INTO instrument_assets (user_id, user_category, district_id, range_id, instrument_type, available_quantity, initial_count, received_quantity, purchase_date, current_condition, issue_order_no, received_from, receipt_no, specification, remarks) VALUES (?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isisiiisssssss", $user_id, $user_category, $district_id, $instrument_type, $available_quantity, $initial_count, $received_quantity, $purchase_date, $current_condition, $issue_order_no, $received_from, $receipt_no, $specification, $remarks);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Instrument asset registered successfully.', '../instruments.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to register instrument: ' . $stmt->error, '../instruments.php');
    }
}

if ($action === 'update_instrument') {
    $id                 = intval($_POST['id'] ?? 0);
    $instrument_type    = trim($_POST['instrument_type'] ?? '');
    $initial_count      = max(0, intval($_POST['initial_count'] ?? 1));
    $received_quantity  = max(0, intval($_POST['received_quantity'] ?? 0));
    $available_quantity = $initial_count + $received_quantity;
    $purchase_date      = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $current_condition  = trim($_POST['current_condition'] ?? 'Operational');
    $issue_order_no     = trim($_POST['issue_order_no'] ?? '');
    $received_from      = trim($_POST['received_from'] ?? '');
    $receipt_no         = trim($_POST['receipt_no'] ?? '');
    $specification      = trim($_POST['specification'] ?? '');
    $remarks            = trim($_POST['remarks'] ?? '');

    if ($current_condition === 'Damaged') {
        $available_quantity = max(0, $available_quantity - 1);
    }

    if ($id <= 0 || empty($instrument_type)) {
        respondJsonOrRedirect($is_ajax, false, 'Invalid item ID or missing details.', '../instruments.php');
    }

    $stmt = $mysqli->prepare("UPDATE instrument_assets SET instrument_type = ?, available_quantity = ?, initial_count = ?, received_quantity = ?, purchase_date = ?, current_condition = ?, issue_order_no = ?, received_from = ?, receipt_no = ?, specification = ?, remarks = ? WHERE id = ? AND (user_category = 'subject_matter_specialist' OR user_id = ?)");
    $stmt->bind_param("siiisssssssii", $instrument_type, $available_quantity, $initial_count, $received_quantity, $purchase_date, $current_condition, $issue_order_no, $received_from, $receipt_no, $specification, $remarks, $id, $user_id);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Instrument record updated successfully.', '../instruments.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to update instrument: ' . $stmt->error, '../instruments.php');
    }
}

if ($action === 'delete_instrument') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM instrument_assets WHERE id = ? AND (user_category = 'subject_matter_specialist' OR user_id = ?)");
        $stmt->bind_param("ii", $id, $user_id);
        if ($stmt->execute()) {
            respondJsonOrRedirect(false, true, 'Instrument record deleted successfully.', '../instruments.php');
        } else {
            respondJsonOrRedirect(false, false, 'Failed to delete instrument record: ' . $stmt->error, '../instruments.php');
        }
    }
}

// -------------------------------------------------------------
// 6. COUNTER FOIL ASSETS CRUD
// -------------------------------------------------------------
if ($action === 'save_counterfoil') {
    $counterfoil_type   = trim($_POST['counterfoil_type'] ?? '');
    $book_serial_no     = trim($_POST['book_serial_no'] ?? '');
    $page_count         = trim($_POST['page_count'] ?? '');
    $issued_to          = trim($_POST['issued_to'] ?? '');
    $date_of_issue      = !empty($_POST['date_of_issue']) ? $_POST['date_of_issue'] : null;
    $date_of_return     = !empty($_POST['date_of_return']) ? $_POST['date_of_return'] : null;
    $initial_count      = max(0, intval($_POST['initial_count'] ?? 1));
    $received_quantity  = max(0, intval($_POST['received_quantity'] ?? 0));
    $available_quantity = $initial_count + $received_quantity;
    $purchase_date      = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $current_condition  = trim($_POST['current_condition'] ?? 'Good Condition');
    $issue_order_no     = trim($_POST['issue_order_no'] ?? '');
    $received_from      = trim($_POST['received_from'] ?? '');
    $receipt_no         = trim($_POST['receipt_no'] ?? '');
    $specification      = trim($_POST['specification'] ?? '');
    $remarks            = trim($_POST['remarks'] ?? '');

    if ($current_condition === 'Damaged') {
        $available_quantity = max(0, $available_quantity - 1);
    }

    if (empty($counterfoil_type)) {
        respondJsonOrRedirect($is_ajax, false, 'Counter Foil Book Type is required.', '../counter_foilage.php');
    }

    $stmt = $mysqli->prepare("INSERT INTO counterfoil_assets (user_id, user_category, district_id, range_id, counterfoil_type, book_serial_no, page_count, available_quantity, initial_count, received_quantity, purchase_date, current_condition, issue_order_no, received_from, receipt_no, specification, remarks, issued_to, date_of_issue, date_of_return) VALUES (?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isisssiiissssssssss", $user_id, $user_category, $district_id, $counterfoil_type, $book_serial_no, $page_count, $available_quantity, $initial_count, $received_quantity, $purchase_date, $current_condition, $issue_order_no, $received_from, $receipt_no, $specification, $remarks, $issued_to, $date_of_issue, $date_of_return);

    if ($stmt->execute()) {
        if (!empty($counterfoil_type)) {
            $m_stmt = $mysqli->prepare("INSERT IGNORE INTO master_counterfoil_types (type_name, is_active) VALUES (?, 1)");
            if ($m_stmt) {
                $m_stmt->bind_param("s", $counterfoil_type);
                $m_stmt->execute();
                $m_stmt->close();
            }
        }
        respondJsonOrRedirect($is_ajax, true, 'Counter foil record registered successfully.', '../counter_foilage.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to register counter foil: ' . $stmt->error, '../counter_foilage.php');
    }
}

if ($action === 'update_counterfoil') {
    $id                 = intval($_POST['id'] ?? 0);
    $counterfoil_type   = trim($_POST['counterfoil_type'] ?? '');
    $book_serial_no     = trim($_POST['book_serial_no'] ?? '');
    $page_count         = trim($_POST['page_count'] ?? '');
    $issued_to          = trim($_POST['issued_to'] ?? '');
    $date_of_issue      = !empty($_POST['date_of_issue']) ? $_POST['date_of_issue'] : null;
    $date_of_return     = !empty($_POST['date_of_return']) ? $_POST['date_of_return'] : null;
    $initial_count      = max(0, intval($_POST['initial_count'] ?? 1));
    $received_quantity  = max(0, intval($_POST['received_quantity'] ?? 0));
    $available_quantity = $initial_count + $received_quantity;
    $purchase_date      = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $current_condition  = trim($_POST['current_condition'] ?? 'Good Condition');
    $issue_order_no     = trim($_POST['issue_order_no'] ?? '');
    $received_from      = trim($_POST['received_from'] ?? '');
    $receipt_no         = trim($_POST['receipt_no'] ?? '');
    $specification      = trim($_POST['specification'] ?? '');
    $remarks            = trim($_POST['remarks'] ?? '');

    if ($current_condition === 'Damaged') {
        $available_quantity = max(0, $available_quantity - 1);
    }

    if ($id <= 0 || empty($counterfoil_type)) {
        respondJsonOrRedirect($is_ajax, false, 'Invalid item ID or missing details.', '../counter_foilage.php');
    }

    $stmt = $mysqli->prepare("UPDATE counterfoil_assets SET counterfoil_type = ?, book_serial_no = ?, page_count = ?, available_quantity = ?, initial_count = ?, received_quantity = ?, purchase_date = ?, current_condition = ?, issue_order_no = ?, received_from = ?, receipt_no = ?, specification = ?, remarks = ?, issued_to = ?, date_of_issue = ?, date_of_return = ? WHERE id = ? AND (user_category = 'subject_matter_specialist' OR user_id = ?)");
    $stmt->bind_param("sssiiissssssssssii", $counterfoil_type, $book_serial_no, $page_count, $available_quantity, $initial_count, $received_quantity, $purchase_date, $current_condition, $issue_order_no, $received_from, $receipt_no, $specification, $remarks, $issued_to, $date_of_issue, $date_of_return, $id, $user_id);

    if ($stmt->execute()) {
        if (!empty($counterfoil_type)) {
            $m_stmt = $mysqli->prepare("INSERT IGNORE INTO master_counterfoil_types (type_name, is_active) VALUES (?, 1)");
            if ($m_stmt) {
                $m_stmt->bind_param("s", $counterfoil_type);
                $m_stmt->execute();
                $m_stmt->close();
            }
        }
        respondJsonOrRedirect($is_ajax, true, 'Counter foil record updated successfully.', '../counter_foilage.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to update counter foil: ' . $stmt->error, '../counter_foilage.php');
    }
}

if ($action === 'delete_counterfoil') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM counterfoil_assets WHERE id = ? AND (user_category = 'subject_matter_specialist' OR user_id = ?)");
        $stmt->bind_param("ii", $id, $user_id);
        if ($stmt->execute()) {
            respondJsonOrRedirect(false, true, 'Counter foil record deleted successfully.', '../counter_foilage.php');
        } else {
            respondJsonOrRedirect(false, false, 'Failed to delete counter foil: ' . $stmt->error, '../counter_foilage.php');
        }
    }
}

// -------------------------------------------------------------
// 7. HR / EMPLOYEE MANAGEMENT CRUD
// -------------------------------------------------------------
if ($action === 'save_employee') {
    $service_number     = trim($_POST['service_number'] ?? '');
    $officer_name       = trim($_POST['officer_name'] ?? '');
    $designation        = trim($_POST['designation'] ?? 'SMS Field Assistant');
    $user_role          = trim($_POST['user_role'] ?? 'employee');
    $employment_type    = trim($_POST['employment_type'] ?? 'permanent');
    if (!in_array($employment_type, ['permanent', 'temporary'])) {
        $employment_type = 'permanent';
    }

    // Conditional Employment Status & Attachment Reason
    $employment_status = null;
    $attachment_reason = null;
    if ($employment_type === 'permanent') {
        $employment_status = trim($_POST['employment_status'] ?? 'Permanent');
        if (!in_array($employment_status, ['Permanent', 'Attachment', 'Temporary Attachment'])) {
            $employment_status = 'Permanent';
        }
        if ($employment_status === 'Attachment' || $employment_status === 'Temporary Attachment') {
            $employment_status = 'Attachment';
            $attachment_reason = trim($_POST['attachment_reason'] ?? '');
            if (empty($attachment_reason)) {
                respondJsonOrRedirect($is_ajax, false, 'Reason for Attachment is required when Employment Status is Attachment.', '../employee_managment.php');
            }
        }
    }

    $service_category   = trim($_POST['service_category'] ?? 'Technical Support');
    $email              = trim($_POST['email'] ?? '');
    $contact_number     = trim($_POST['contact_number'] ?? '');
    $date_of_birth      = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
    $appointment_date   = !empty($_POST['appointment_date']) ? $_POST['appointment_date'] : null;
    $appointment_date_current_position = !empty($_POST['appointment_date_current_position']) ? $_POST['appointment_date_current_position'] : null;
    $position_to_current_location = !empty($_POST['position_to_current_location']) ? $_POST['position_to_current_location'] : null;
    $username           = !empty($email) ? strtolower(explode('@', $email)[0]) : 'sms_user_' . rand(1000, 9999);
    $default_password   = password_hash('Pass1234!', PASSWORD_DEFAULT);

    if (empty($officer_name) || empty($service_number)) {
        respondJsonOrRedirect($is_ajax, false, 'Officer Name and Service Number are required.', '../employee_managment.php');
    }

    $stmt = $mysqli->prepare("INSERT INTO users (username, password, full_name, email, phone, designation, role, employment_type, employment_status, attachment_reason, service_category, service_number, emp_id, district_id, date_of_birth, appointment_date, appointment_date_current_position, position_to_current_location, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("sssssssssssssissss", $username, $default_password, $officer_name, $email, $contact_number, $designation, $user_role, $employment_type, $employment_status, $attachment_reason, $service_category, $service_number, $service_number, $district_id, $date_of_birth, $appointment_date, $appointment_date_current_position, $position_to_current_location);

    if ($stmt->execute()) {
        $new_user_id = $stmt->insert_id;
        $role_title = !empty($designation) ? $designation : $user_role;
        if (!empty($new_user_id)) {
            notify_assigned_officer($mysqli, $new_user_id, $role_title, 'dashboard.php');
        }
        create_officer_notification($mysqli, 'New Officer Added', $officer_name, $service_number, null, 'pages/modules/sms/employee_managment.php');
        respondJsonOrRedirect($is_ajax, true, 'New technical staff officer registered successfully.', '../employee_managment.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to register officer: ' . $stmt->error, '../employee_managment.php');
    }
}

if ($action === 'update_employee') {
    $id                 = intval($_POST['id'] ?? 0);
    $service_number     = trim($_POST['service_number'] ?? '');
    $officer_name       = trim($_POST['officer_name'] ?? '');
    $designation        = trim($_POST['designation'] ?? '');
    $user_role          = trim($_POST['user_role'] ?? 'employee');
    $employment_type    = trim($_POST['employment_type'] ?? 'permanent');
    if (!in_array($employment_type, ['permanent', 'temporary'])) {
        $employment_type = 'permanent';
    }
    $service_category   = trim($_POST['service_category'] ?? '');
    $email              = trim($_POST['email'] ?? '');
    $contact_number     = trim($_POST['contact_number'] ?? '');
    $date_of_birth      = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
    $appointment_date   = !empty($_POST['appointment_date']) ? $_POST['appointment_date'] : null;
    $appointment_date_current_position = !empty($_POST['appointment_date_current_position']) ? $_POST['appointment_date_current_position'] : null;
    $position_to_current_location = !empty($_POST['position_to_current_location']) ? $_POST['position_to_current_location'] : null;

    if ($id <= 0 || empty($officer_name)) {
        respondJsonOrRedirect($is_ajax, false, 'Invalid officer ID or missing details.', '../employee_managment.php');
    }

    // Fetch existing live record snapshot
    $stmt_curr = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
    $stmt_curr->bind_param("i", $id);
    $stmt_curr->execute();
    $old_user = $stmt_curr->get_result()->fetch_assoc();
    $stmt_curr->close();

    $new_user_data = [
        'full_name' => $officer_name,
        'email' => $email,
        'phone' => $contact_number,
        'designation' => $designation,
        'role' => $user_role,
        'employment_type' => $employment_type,
        'service_category' => $service_category,
        'service_number' => $service_number,
        'date_of_birth' => $date_of_birth,
        'appointment_date' => $appointment_date,
        'appointment_date_current_position' => $appointment_date_current_position,
        'position_to_current_location' => $position_to_current_location
    ];

    $staging_res = stage_or_apply_edit($mysqli, 'hr', 'users', $id, $officer_name, $old_user ?: [], $new_user_data, $district_id);
    if (!empty($staging_res['is_staged'])) {
        respondJsonOrRedirect($is_ajax, true, 'Edit submitted successfully. Changes are pending authorization by the Provincial Director.', '../employee_managment.php', ['staged' => true]);
    }

    $stmt = $mysqli->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, designation = ?, role = ?, employment_type = ?, service_category = ?, service_number = ?, date_of_birth = ?, appointment_date = ?, appointment_date_current_position = ?, position_to_current_location = ? WHERE id = ?");
    $stmt->bind_param("ssssssssssssi", $officer_name, $email, $contact_number, $designation, $user_role, $employment_type, $service_category, $service_number, $date_of_birth, $appointment_date, $appointment_date_current_position, $position_to_current_location, $id);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Officer details updated successfully.', '../employee_managment.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to update officer details: ' . $stmt->error, '../employee_managment.php');
    }
}

if ($action === 'delete_employee') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $mysqli->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            respondJsonOrRedirect(false, true, 'Officer profile deactivated successfully.', '../employee_managment.php');
        } else {
            respondJsonOrRedirect(false, false, 'Failed to deactivate officer: ' . $stmt->error, '../employee_managment.php');
        }
    }
}

respondJsonOrRedirect($is_ajax, false, 'Invalid request action.', '../office_details.php');
