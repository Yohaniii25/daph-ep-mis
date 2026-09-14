<?php
// pages/modules/farm/processors/office_assets_crud.php
session_start();
require_once '../../../../config/db_connect.php';
require_once '../../../../includes/approval_helper.php';
require_once '../../../../includes/notification_helper.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'farms_dd') {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access denied.']);
        exit();
    } else {
        die("Access denied");
    }
}

$user_id = $_SESSION['user_id'] ?? 1;
$farm_id = $_SESSION['farm_id'] ?? null;
$district_id = intval($_SESSION['district_id'] ?? 0);
$user_category = !empty($_SESSION['user_category']) ? $_SESSION['user_category'] : 'regional_farms';
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

    $stmt = $mysqli->prepare("INSERT INTO land_assets (user_id, farm_id, user_category, district_id, range_id, property_name, land_extent, building_area, land_status, deed_reference, deed_description) VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisissssss", $user_id, $farm_id, $user_category, $district_id, $property_name, $land_extent, $building_area, $land_status, $deed_reference, $deed_description);

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

    $stmt = $mysqli->prepare("UPDATE land_assets SET property_name = ?, land_extent = ?, building_area = ?, land_status = ?, deed_reference = ?, deed_description = ? WHERE id = ? AND (farm_id = ? OR user_id = ?)");
    $stmt->bind_param("ssssssiii", $property_name, $land_extent, $building_area, $land_status, $deed_reference, $deed_description, $id, $farm_id, $user_id);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Land property updated successfully.', '../lands_buildings.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to update land property: ' . $stmt->error, '../lands_buildings.php');
    }
}

if ($action === 'delete_land') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $mysqli->prepare("UPDATE land_assets SET is_active = 0 WHERE id = ? AND (farm_id = ? OR user_id = ?)");
        $stmt->bind_param("iii", $id, $farm_id, $user_id);
        if ($stmt->execute()) {
            respondJsonOrRedirect(false, true, 'Land property removed successfully.', '../lands_buildings.php');
        } else {
            respondJsonOrRedirect(false, false, 'Failed to delete land property: ' . $stmt->error, '../lands_buildings.php');
        }
    }
}

if ($action === 'save_building_inventory') {
    $land_asset_id      = intval($_POST['land_asset_id'] ?? 0);
    $property_name      = trim($_POST['property_name'] ?? '');
    $inventory_item     = trim($_POST['inventory_item'] ?? '');
    $inventory_number   = trim($_POST['inventory_number'] ?? '');
    $inventory_type     = trim($_POST['inventory_type'] ?? 'Equipment');
    $issue_order_no     = trim($_POST['issue_order_no'] ?? '');
    $received_from      = trim($_POST['received_from'] ?? '');
    $receipt_no         = trim($_POST['receipt_no'] ?? '');
    $specification      = trim($_POST['specification'] ?? '');
    $current_condition  = trim($_POST['current_condition'] ?? 'Good');
    $initial_count      = intval($_POST['initial_count'] ?? 0);
    $received_quantity  = intval($_POST['received_quantity'] ?? 0);
    $remarks            = trim($_POST['remarks'] ?? '');

    // Availability Auto-Calculation: initial baseline stock + received amounts
    $available_quantity = $initial_count + $received_quantity;

    // Handle manual property text entry
    if ($land_asset_id <= 0 && !empty($property_name)) {
        $stmt_find = $mysqli->prepare("SELECT id FROM land_assets WHERE property_name = ? AND (farm_id = ? OR user_id = ?) AND is_active = 1 LIMIT 1");
        $stmt_find->bind_param("sii", $property_name, $farm_id, $user_id);
        $stmt_find->execute();
        $res_find = $stmt_find->get_result();
        if ($rf = $res_find->fetch_assoc()) {
            $land_asset_id = intval($rf['id']);
        } else {
            $stmt_new_la = $mysqli->prepare("INSERT INTO land_assets (farm_id, user_id, user_category, property_name, land_status, is_active) VALUES (?, ?, ?, ?, 'State Owned', 1)");
            $stmt_new_la->bind_param("iiss", $farm_id, $user_id, $user_category, $property_name);
            $stmt_new_la->execute();
            $land_asset_id = $stmt_new_la->insert_id;
            $stmt_new_la->close();
        }
        $stmt_find->close();
    }

    if ($land_asset_id <= 0 || empty($inventory_item)) {
        respondJsonOrRedirect($is_ajax, false, 'Property identification and Item Name are required.', '../lands_buildings.php?tab=inventory');
    }

    $stmt = $mysqli->prepare("INSERT INTO building_inventories (land_asset_id, user_id, farm_id, user_category, inventory_item, inventory_number, inventory_type, issue_order_no, received_from, receipt_no, received_quantity, initial_count, available_quantity, specification, current_condition, remarks, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("iiisssssssiiisss", $land_asset_id, $user_id, $farm_id, $user_category, $inventory_item, $inventory_number, $inventory_type, $issue_order_no, $received_from, $receipt_no, $received_quantity, $initial_count, $available_quantity, $specification, $current_condition, $remarks);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Building inventory item logged successfully.', '../lands_buildings.php?tab=inventory');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to log building inventory: ' . $stmt->error, '../lands_buildings.php?tab=inventory');
    }
}

if ($action === 'update_building_inventory') {
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
    $current_condition  = trim($_POST['current_condition'] ?? 'Good');
    $initial_count      = intval($_POST['initial_count'] ?? 0);
    $received_quantity  = intval($_POST['received_quantity'] ?? 0);
    $remarks            = trim($_POST['remarks'] ?? '');

    // Availability Auto-Calculation: initial baseline stock + received amounts
    $available_quantity = $initial_count + $received_quantity;

    // Handle manual property text entry
    if ($land_asset_id <= 0 && !empty($property_name)) {
        $stmt_find = $mysqli->prepare("SELECT id FROM land_assets WHERE property_name = ? AND (farm_id = ? OR user_id = ?) AND is_active = 1 LIMIT 1");
        $stmt_find->bind_param("sii", $property_name, $farm_id, $user_id);
        $stmt_find->execute();
        $res_find = $stmt_find->get_result();
        if ($rf = $res_find->fetch_assoc()) {
            $land_asset_id = intval($rf['id']);
        } else {
            $stmt_new_la = $mysqli->prepare("INSERT INTO land_assets (farm_id, user_id, user_category, property_name, land_status, is_active) VALUES (?, ?, ?, ?, 'State Owned', 1)");
            $stmt_new_la->bind_param("iiss", $farm_id, $user_id, $user_category, $property_name);
            $stmt_new_la->execute();
            $land_asset_id = $stmt_new_la->insert_id;
            $stmt_new_la->close();
        }
        $stmt_find->close();
    }

    if ($id <= 0 || empty($inventory_item)) {
        respondJsonOrRedirect($is_ajax, false, 'Invalid item ID or missing item name.', '../lands_buildings.php?tab=inventory');
    }

    $stmt = $mysqli->prepare("UPDATE building_inventories SET land_asset_id = ?, inventory_item = ?, inventory_number = ?, inventory_type = ?, issue_order_no = ?, received_from = ?, receipt_no = ?, received_quantity = ?, initial_count = ?, available_quantity = ?, specification = ?, current_condition = ?, remarks = ? WHERE id = ? AND (farm_id = ? OR user_id = ?)");
    $stmt->bind_param("issssssiiisssiii", $land_asset_id, $inventory_item, $inventory_number, $inventory_type, $issue_order_no, $received_from, $receipt_no, $received_quantity, $initial_count, $available_quantity, $specification, $current_condition, $remarks, $id, $farm_id, $user_id);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Building inventory item updated successfully.', '../lands_buildings.php?tab=inventory');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to update inventory: ' . $stmt->error, '../lands_buildings.php?tab=inventory');
    }
}

if ($action === 'delete_building_inventory') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM building_inventories WHERE id = ? AND (farm_id = ? OR user_id = ?)");
        $stmt->bind_param("iii", $id, $farm_id, $user_id);
        if ($stmt->execute()) {
            respondJsonOrRedirect(false, true, 'Building inventory entry deleted successfully.', '../lands_buildings.php?tab=inventory');
        } else {
            respondJsonOrRedirect(false, false, 'Failed to delete inventory: ' . $stmt->error, '../lands_buildings.php?tab=inventory');
        }
    }
}

// -------------------------------------------------------------
// 2. VEHICLES CRUD
// -------------------------------------------------------------
if ($action === 'save_vehicle') {
    $vehicle_type       = trim($_POST['vehicle_type'] ?? 'Tractor');
    $vehicle_number     = strtoupper(trim($_POST['vehicle_number'] ?? ''));
    $chassis_number     = strtoupper(trim($_POST['chassis_number'] ?? ''));
    $current_condition  = trim($_POST['current_condition'] ?? 'Good/Running');
    $other_details      = trim($_POST['other_details'] ?? '');
    $issue_order_no     = trim($_POST['issue_order_no'] ?? '');
    $received_from      = trim($_POST['received_from'] ?? '');
    $receipt_no         = trim($_POST['receipt_no'] ?? '');
    $specification      = trim($_POST['specification'] ?? '');
    $initial_count      = max(0, intval($_POST['initial_count'] ?? 1));
    $received_quantity  = max(0, intval($_POST['received_quantity'] ?? 0));
    $available_quantity = $initial_count + $received_quantity;
    $remarks            = trim($_POST['remarks'] ?? '');
    if (empty($remarks) && !empty($other_details)) {
        $remarks = $other_details;
    }

    if (empty($vehicle_number)) {
        respondJsonOrRedirect($is_ajax, false, 'Vehicle Registration Number is required.', '../vehicles.php');
    }

    $stmt = $mysqli->prepare("INSERT INTO registered_vehicles (user_id, farm_id, user_category, district_id, range_id, vehicle_type, vehicle_number, chassis_number, current_condition, other_details, issue_order_no, received_from, receipt_no, specification, available_quantity, initial_count, received_quantity, remarks) VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisisssssssssiiiss", $user_id, $farm_id, $user_category, $district_id, $vehicle_type, $vehicle_number, $chassis_number, $current_condition, $other_details, $issue_order_no, $received_from, $receipt_no, $specification, $available_quantity, $initial_count, $received_quantity, $remarks);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Vehicle registered successfully in fleet registry.', '../vehicles.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to register vehicle: ' . $stmt->error, '../vehicles.php');
    }
}

if ($action === 'update_vehicle') {
    $id                 = intval($_POST['id'] ?? 0);
    $vehicle_type       = trim($_POST['vehicle_type'] ?? 'Tractor');
    $vehicle_number     = strtoupper(trim($_POST['vehicle_number'] ?? ''));
    $chassis_number     = strtoupper(trim($_POST['chassis_number'] ?? ''));
    $current_condition  = trim($_POST['current_condition'] ?? 'Good/Running');
    $other_details      = trim($_POST['other_details'] ?? '');
    $issue_order_no     = trim($_POST['issue_order_no'] ?? '');
    $received_from      = trim($_POST['received_from'] ?? '');
    $receipt_no         = trim($_POST['receipt_no'] ?? '');
    $specification      = trim($_POST['specification'] ?? '');
    $initial_count      = max(0, intval($_POST['initial_count'] ?? 1));
    $received_quantity  = max(0, intval($_POST['received_quantity'] ?? 0));
    $available_quantity = $initial_count + $received_quantity;
    $remarks            = trim($_POST['remarks'] ?? '');
    if (empty($remarks) && !empty($other_details)) {
        $remarks = $other_details;
    }

    if ($id <= 0 || empty($vehicle_number)) {
        respondJsonOrRedirect($is_ajax, false, 'Invalid vehicle ID or missing vehicle number.', '../vehicles.php');
    }

    $stmt = $mysqli->prepare("UPDATE registered_vehicles SET vehicle_type = ?, vehicle_number = ?, chassis_number = ?, current_condition = ?, other_details = ?, issue_order_no = ?, received_from = ?, receipt_no = ?, specification = ?, available_quantity = ?, initial_count = ?, received_quantity = ?, remarks = ? WHERE id = ? AND (farm_id = ? OR user_id = ?)");
    $stmt->bind_param("sssssssssiiisiii", $vehicle_type, $vehicle_number, $chassis_number, $current_condition, $other_details, $issue_order_no, $received_from, $receipt_no, $specification, $available_quantity, $initial_count, $received_quantity, $remarks, $id, $farm_id, $user_id);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Vehicle details updated successfully.', '../vehicles.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to update vehicle: ' . $stmt->error, '../vehicles.php');
    }
}

if ($action === 'delete_vehicle') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM registered_vehicles WHERE id = ? AND (farm_id = ? OR user_id = ?)");
        $stmt->bind_param("iii", $id, $farm_id, $user_id);
        if ($stmt->execute()) {
            respondJsonOrRedirect(false, true, 'Vehicle entry removed from fleet registry.', '../vehicles.php');
        } else {
            respondJsonOrRedirect(false, false, 'Failed to delete vehicle: ' . $stmt->error, '../vehicles.php');
        }
    }
}

if ($action === 'save_vehicle_repair') {
    $vehicle_id    = intval($_POST['vehicle_id'] ?? 0);
    $repair_date   = $_POST['repair_date'] ?? date('Y-m-d');
    $repair_nature = trim($_POST['repair_nature'] ?? '');
    $cost_lkr      = floatval($_POST['cost_lkr'] ?? 0);
    $repaired_by   = trim($_POST['repaired_by'] ?? '');
    $invoice_ref   = trim($_POST['invoice_ref'] ?? '');
    $remarks       = trim($_POST['remarks'] ?? '');

    if ($vehicle_id <= 0 || empty($repair_nature)) {
        respondJsonOrRedirect($is_ajax, false, 'Vehicle selection and Repair Nature are required.', '../vehicles.php?tab=repairs');
    }

    $stmt = $mysqli->prepare("INSERT INTO vehicle_repairs (vehicle_id, user_id, farm_id, user_category, repair_date, repair_done, amount, place_of_repair, invoice_ref, repair_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisssdsss", $vehicle_id, $user_id, $farm_id, $user_category, $repair_date, $repair_nature, $cost_lkr, $repaired_by, $invoice_ref, $remarks);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Vehicle repair log added successfully.', '../vehicles.php?tab=repairs');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to log vehicle repair: ' . $stmt->error, '../vehicles.php?tab=repairs');
    }
}

if ($action === 'update_vehicle_repair') {
    $id            = intval($_POST['id'] ?? 0);
    $vehicle_id    = intval($_POST['vehicle_id'] ?? 0);
    $repair_date   = $_POST['repair_date'] ?? date('Y-m-d');
    $repair_nature = trim($_POST['repair_nature'] ?? '');
    $cost_lkr      = floatval($_POST['cost_lkr'] ?? 0);
    $repaired_by   = trim($_POST['repaired_by'] ?? '');
    $invoice_ref   = trim($_POST['invoice_ref'] ?? '');
    $remarks       = trim($_POST['remarks'] ?? '');

    if ($id <= 0 || $vehicle_id <= 0 || empty($repair_nature)) {
        respondJsonOrRedirect($is_ajax, false, 'Invalid repair log ID or required details missing.', '../vehicles.php?tab=repairs');
    }

    $stmt = $mysqli->prepare("UPDATE vehicle_repairs SET vehicle_id = ?, repair_date = ?, repair_done = ?, amount = ?, place_of_repair = ?, invoice_ref = ?, repair_description = ? WHERE id = ? AND (farm_id = ? OR user_id = ?)");
    $stmt->bind_param("isssdsssii", $vehicle_id, $repair_date, $repair_nature, $cost_lkr, $repaired_by, $invoice_ref, $remarks, $id, $farm_id, $user_id);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Vehicle repair record updated successfully.', '../vehicles.php?tab=repairs');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to update vehicle repair: ' . $stmt->error, '../vehicles.php?tab=repairs');
    }
}

if ($action === 'delete_vehicle_repair') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM vehicle_repairs WHERE id = ? AND (farm_id = ? OR user_id = ?)");
        $stmt->bind_param("iii", $id, $farm_id, $user_id);
        if ($stmt->execute()) {
            respondJsonOrRedirect(false, true, 'Vehicle repair log deleted successfully.', '../vehicles.php?tab=repairs');
        } else {
            respondJsonOrRedirect(false, false, 'Failed to delete vehicle repair log: ' . $stmt->error, '../vehicles.php?tab=repairs');
        }
    }
}

// -------------------------------------------------------------
// 3. FURNITURE CRUD
// -------------------------------------------------------------
if ($action === 'save_furniture') {
    $furniture_type     = trim($_POST['furniture_type'] ?? 'Office Chairs');
    $initial_count      = max(0, intval($_POST['initial_count'] ?? 1));
    $received_quantity  = max(0, intval($_POST['received_quantity'] ?? 0));
    $available_quantity = $initial_count + $received_quantity;
    $date_received      = !empty($_POST['date_received']) ? $_POST['date_received'] : date('Y-m-d');
    $current_condition  = trim($_POST['current_condition'] ?? 'Good Condition');
    $remarks            = trim($_POST['remarks'] ?? '');
    $issue_order_no     = trim($_POST['issue_order_no'] ?? '');
    $received_from      = trim($_POST['received_from'] ?? '');
    $receipt_no         = trim($_POST['receipt_no'] ?? '');
    $specification      = trim($_POST['specification'] ?? '');

    if ($current_condition === 'Damaged') {
        $available_quantity = max(0, $available_quantity - 1);
    }

    if (empty($furniture_type)) {
        respondJsonOrRedirect($is_ajax, false, 'Furniture Type is required.', '../furniture.php');
    }

    $stmt = $mysqli->prepare("INSERT INTO furniture_assets (user_id, farm_id, user_category, district_id, range_id, furniture_type, available_quantity, initial_count, received_quantity, date_received, current_condition, remarks, issue_order_no, received_from, receipt_no, specification) VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisisiiisssssss", $user_id, $farm_id, $user_category, $district_id, $furniture_type, $available_quantity, $initial_count, $received_quantity, $date_received, $current_condition, $remarks, $issue_order_no, $received_from, $receipt_no, $specification);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Furniture asset registered successfully.', '../furniture.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to register furniture: ' . $stmt->error, '../furniture.php');
    }
}

if ($action === 'update_furniture') {
    $id                 = intval($_POST['id'] ?? 0);
    $furniture_type     = trim($_POST['furniture_type'] ?? 'Office Chairs');
    $initial_count      = max(0, intval($_POST['initial_count'] ?? 1));
    $received_quantity  = max(0, intval($_POST['received_quantity'] ?? 0));
    $available_quantity = $initial_count + $received_quantity;
    $date_received      = !empty($_POST['date_received']) ? $_POST['date_received'] : date('Y-m-d');
    $current_condition  = trim($_POST['current_condition'] ?? 'Good Condition');
    $remarks            = trim($_POST['remarks'] ?? '');
    $issue_order_no     = trim($_POST['issue_order_no'] ?? '');
    $received_from      = trim($_POST['received_from'] ?? '');
    $receipt_no         = trim($_POST['receipt_no'] ?? '');
    $specification      = trim($_POST['specification'] ?? '');

    if ($current_condition === 'Damaged') {
        $available_quantity = max(0, $available_quantity - 1);
    }

    if ($id <= 0 || empty($furniture_type)) {
        respondJsonOrRedirect($is_ajax, false, 'Invalid furniture ID or missing type.', '../furniture.php');
    }

    $stmt = $mysqli->prepare("UPDATE furniture_assets SET furniture_type = ?, available_quantity = ?, initial_count = ?, received_quantity = ?, date_received = ?, current_condition = ?, remarks = ?, issue_order_no = ?, received_from = ?, receipt_no = ?, specification = ? WHERE id = ? AND (farm_id = ? OR user_id = ?)");
    $stmt->bind_param("siiisssssssiii", $furniture_type, $available_quantity, $initial_count, $received_quantity, $date_received, $current_condition, $remarks, $issue_order_no, $received_from, $receipt_no, $specification, $id, $farm_id, $user_id);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Furniture details updated successfully.', '../furniture.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to update furniture: ' . $stmt->error, '../furniture.php');
    }
}

if ($action === 'delete_furniture') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM furniture_assets WHERE id = ? AND (farm_id = ? OR user_id = ?)");
        $stmt->bind_param("iii", $id, $farm_id, $user_id);
        if ($stmt->execute()) {
            respondJsonOrRedirect(false, true, 'Furniture entry deleted successfully.', '../furniture.php');
        } else {
            respondJsonOrRedirect(false, false, 'Failed to delete furniture: ' . $stmt->error, '../furniture.php');
        }
    }
}

// -------------------------------------------------------------
// 4. MACHINERIES CRUD
// -------------------------------------------------------------
if ($action === 'save_machinery') {
    $machinery_type     = trim($_POST['machinery_type'] ?? 'Generator');
    $initial_count      = max(0, intval($_POST['initial_count'] ?? 1));
    $received_quantity  = max(0, intval($_POST['received_quantity'] ?? 0));
    $available_quantity = $initial_count + $received_quantity;
    $purchase_date      = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : date('Y-m-d');
    $current_condition  = trim($_POST['current_condition'] ?? 'Operational / Good');
    $remarks            = trim($_POST['remarks'] ?? '');
    $issue_order_no     = trim($_POST['issue_order_no'] ?? '');
    $received_from      = trim($_POST['received_from'] ?? '');
    $receipt_no         = trim($_POST['receipt_no'] ?? '');
    $specification      = trim($_POST['specification'] ?? '');

    if ($current_condition === 'Damaged') {
        $available_quantity = max(0, $available_quantity - 1);
    }

    if (empty($machinery_type)) {
        respondJsonOrRedirect($is_ajax, false, 'Machinery Type is required.', '../machineries.php');
    }

    $stmt = $mysqli->prepare("INSERT INTO machinery_assets (user_id, farm_id, user_category, district_id, range_id, machinery_type, available_quantity, initial_count, received_quantity, purchase_date, current_condition, remarks, issue_order_no, received_from, receipt_no, specification) VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisisiiisssssss", $user_id, $farm_id, $user_category, $district_id, $machinery_type, $available_quantity, $initial_count, $received_quantity, $purchase_date, $current_condition, $remarks, $issue_order_no, $received_from, $receipt_no, $specification);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Machinery asset registered successfully.', '../machineries.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to register machinery: ' . $stmt->error, '../machineries.php');
    }
}

if ($action === 'update_machinery') {
    $id                 = intval($_POST['id'] ?? 0);
    $machinery_type     = trim($_POST['machinery_type'] ?? 'Generator');
    $initial_count      = max(0, intval($_POST['initial_count'] ?? 1));
    $received_quantity  = max(0, intval($_POST['received_quantity'] ?? 0));
    $available_quantity = $initial_count + $received_quantity;
    $purchase_date      = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : date('Y-m-d');
    $current_condition  = trim($_POST['current_condition'] ?? 'Operational / Good');
    $remarks            = trim($_POST['remarks'] ?? '');
    $issue_order_no     = trim($_POST['issue_order_no'] ?? '');
    $received_from      = trim($_POST['received_from'] ?? '');
    $receipt_no         = trim($_POST['receipt_no'] ?? '');
    $specification      = trim($_POST['specification'] ?? '');

    if ($current_condition === 'Damaged') {
        $available_quantity = max(0, $available_quantity - 1);
    }

    if ($id <= 0 || empty($machinery_type)) {
        respondJsonOrRedirect($is_ajax, false, 'Invalid machinery ID or missing type.', '../machineries.php');
    }

    $stmt = $mysqli->prepare("UPDATE machinery_assets SET machinery_type = ?, available_quantity = ?, initial_count = ?, received_quantity = ?, purchase_date = ?, current_condition = ?, remarks = ?, issue_order_no = ?, received_from = ?, receipt_no = ?, specification = ? WHERE id = ? AND (farm_id = ? OR user_id = ?)");
    $stmt->bind_param("siiisssssssiii", $machinery_type, $available_quantity, $initial_count, $received_quantity, $purchase_date, $current_condition, $remarks, $issue_order_no, $received_from, $receipt_no, $specification, $id, $farm_id, $user_id);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Machinery details updated successfully.', '../machineries.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to update machinery: ' . $stmt->error, '../machineries.php');
    }
}

if ($action === 'delete_machinery') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM machinery_assets WHERE id = ? AND (farm_id = ? OR user_id = ?)");
        $stmt->bind_param("iii", $id, $farm_id, $user_id);
        if ($stmt->execute()) {
            respondJsonOrRedirect(false, true, 'Machinery entry deleted successfully.', '../machineries.php');
        } else {
            respondJsonOrRedirect(false, false, 'Failed to delete machinery: ' . $stmt->error, '../machineries.php');
        }
    }
}

// -------------------------------------------------------------
// 5. INSTRUMENTS CRUD
// -------------------------------------------------------------
if ($action === 'save_instrument') {
    $instrument_type    = trim($_POST['instrument_type'] ?? 'AI Equipment');
    $initial_count      = max(0, intval($_POST['initial_count'] ?? 1));
    $received_quantity  = max(0, intval($_POST['received_quantity'] ?? 0));
    $available_quantity = $initial_count + $received_quantity;
    $purchase_date      = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : date('Y-m-d');
    $current_condition  = trim($_POST['current_condition'] ?? 'Working / Calibrated');
    $remarks            = trim($_POST['remarks'] ?? '');
    $issue_order_no     = trim($_POST['issue_order_no'] ?? '');
    $received_from      = trim($_POST['received_from'] ?? '');
    $receipt_no         = trim($_POST['receipt_no'] ?? '');
    $specification      = trim($_POST['specification'] ?? '');

    if ($current_condition === 'Damaged') {
        $available_quantity = max(0, $available_quantity - 1);
    }

    if (empty($instrument_type)) {
        respondJsonOrRedirect($is_ajax, false, 'Instrument Type is required.', '../instruments.php');
    }

    $stmt = $mysqli->prepare("INSERT INTO instrument_assets (user_id, farm_id, user_category, district_id, range_id, instrument_type, available_quantity, initial_count, received_quantity, purchase_date, current_condition, remarks, issue_order_no, received_from, receipt_no, specification) VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisisiiisssssss", $user_id, $farm_id, $user_category, $district_id, $instrument_type, $available_quantity, $initial_count, $received_quantity, $purchase_date, $current_condition, $remarks, $issue_order_no, $received_from, $receipt_no, $specification);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Instrument asset registered successfully.', '../instruments.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to register instrument: ' . $stmt->error, '../instruments.php');
    }
}

if ($action === 'update_instrument') {
    $id                 = intval($_POST['id'] ?? 0);
    $instrument_type    = trim($_POST['instrument_type'] ?? 'AI Equipment');
    $initial_count      = max(0, intval($_POST['initial_count'] ?? 1));
    $received_quantity  = max(0, intval($_POST['received_quantity'] ?? 0));
    $available_quantity = $initial_count + $received_quantity;
    $purchase_date      = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : date('Y-m-d');
    $current_condition  = trim($_POST['current_condition'] ?? 'Working / Calibrated');
    $remarks            = trim($_POST['remarks'] ?? '');
    $issue_order_no     = trim($_POST['issue_order_no'] ?? '');
    $received_from      = trim($_POST['received_from'] ?? '');
    $receipt_no         = trim($_POST['receipt_no'] ?? '');
    $specification      = trim($_POST['specification'] ?? '');

    if ($current_condition === 'Damaged') {
        $available_quantity = max(0, $available_quantity - 1);
    }

    if ($id <= 0 || empty($instrument_type)) {
        respondJsonOrRedirect($is_ajax, false, 'Invalid instrument ID or missing type.', '../instruments.php');
    }

    $stmt = $mysqli->prepare("UPDATE instrument_assets SET instrument_type = ?, available_quantity = ?, initial_count = ?, received_quantity = ?, purchase_date = ?, current_condition = ?, remarks = ?, issue_order_no = ?, received_from = ?, receipt_no = ?, specification = ? WHERE id = ? AND (farm_id = ? OR user_id = ?)");
    $stmt->bind_param("siiisssssssiii", $instrument_type, $available_quantity, $initial_count, $received_quantity, $purchase_date, $current_condition, $remarks, $issue_order_no, $received_from, $receipt_no, $specification, $id, $farm_id, $user_id);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Instrument details updated successfully.', '../instruments.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to update instrument: ' . $stmt->error, '../instruments.php');
    }
}

if ($action === 'delete_instrument') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM instrument_assets WHERE id = ? AND (farm_id = ? OR user_id = ?)");
        $stmt->bind_param("iii", $id, $farm_id, $user_id);
        if ($stmt->execute()) {
            respondJsonOrRedirect(false, true, 'Instrument entry deleted successfully.', '../instruments.php');
        } else {
            respondJsonOrRedirect(false, false, 'Failed to delete instrument: ' . $stmt->error, '../instruments.php');
        }
    }
}

// -------------------------------------------------------------
// 6. COUNTER FOIL CRUD
// -------------------------------------------------------------
if ($action === 'save_counterfoil') {
    $counterfoil_type   = trim($_POST['counterfoil_type'] ?? 'General Receipt Book');
    $book_serial_no     = trim($_POST['book_serial_no'] ?? '');
    $page_count         = trim($_POST['page_count'] ?? '');
    $issued_to          = trim($_POST['issued_to'] ?? '');
    $date_of_issue      = !empty($_POST['date_of_issue']) ? $_POST['date_of_issue'] : null;
    $date_of_return     = !empty($_POST['date_of_return']) ? $_POST['date_of_return'] : null;
    $initial_count      = max(0, intval($_POST['initial_count'] ?? 1));
    $received_quantity  = max(0, intval($_POST['received_quantity'] ?? 0));
    $available_quantity = $initial_count + $received_quantity;
    $purchase_date      = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : date('Y-m-d');
    $current_condition  = trim($_POST['current_condition'] ?? 'Active / In Use');
    $remarks            = trim($_POST['remarks'] ?? '');
    $issue_order_no     = trim($_POST['issue_order_no'] ?? '');
    $received_from      = trim($_POST['received_from'] ?? '');
    $receipt_no         = trim($_POST['receipt_no'] ?? '');
    $specification      = trim($_POST['specification'] ?? '');

    if ($current_condition === 'Damaged') {
        $available_quantity = max(0, $available_quantity - 1);
    }

    if (empty($counterfoil_type)) {
        respondJsonOrRedirect($is_ajax, false, 'Counterfoil Type is required.', '../counter_foilage.php');
    }

    $stmt = $mysqli->prepare("INSERT INTO counterfoil_assets (user_id, farm_id, user_category, district_id, range_id, counterfoil_type, book_serial_no, page_count, available_quantity, initial_count, received_quantity, purchase_date, current_condition, remarks, issue_order_no, received_from, receipt_no, specification, issued_to, date_of_issue, date_of_return) VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisisssiiissssssssss", $user_id, $farm_id, $user_category, $district_id, $counterfoil_type, $book_serial_no, $page_count, $available_quantity, $initial_count, $received_quantity, $purchase_date, $current_condition, $remarks, $issue_order_no, $received_from, $receipt_no, $specification, $issued_to, $date_of_issue, $date_of_return);

    if ($stmt->execute()) {
        if (!empty($counterfoil_type)) {
            $m_stmt = $mysqli->prepare("INSERT IGNORE INTO master_counterfoil_types (type_name, is_active) VALUES (?, 1)");
            if ($m_stmt) {
                $m_stmt->bind_param("s", $counterfoil_type);
                $m_stmt->execute();
                $m_stmt->close();
            }
        }
        respondJsonOrRedirect($is_ajax, true, 'Counterfoil registered successfully.', '../counter_foilage.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to register counterfoil: ' . $stmt->error, '../counter_foilage.php');
    }
}

if ($action === 'update_counterfoil') {
    $id                 = intval($_POST['id'] ?? 0);
    $counterfoil_type   = trim($_POST['counterfoil_type'] ?? 'General Receipt Book');
    $book_serial_no     = trim($_POST['book_serial_no'] ?? '');
    $page_count         = trim($_POST['page_count'] ?? '');
    $issued_to          = trim($_POST['issued_to'] ?? '');
    $date_of_issue      = !empty($_POST['date_of_issue']) ? $_POST['date_of_issue'] : null;
    $date_of_return     = !empty($_POST['date_of_return']) ? $_POST['date_of_return'] : null;
    $initial_count      = max(0, intval($_POST['initial_count'] ?? 1));
    $received_quantity  = max(0, intval($_POST['received_quantity'] ?? 0));
    $available_quantity = $initial_count + $received_quantity;
    $purchase_date      = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : date('Y-m-d');
    $current_condition  = trim($_POST['current_condition'] ?? 'Active / In Use');
    $remarks            = trim($_POST['remarks'] ?? '');
    $issue_order_no     = trim($_POST['issue_order_no'] ?? '');
    $received_from      = trim($_POST['received_from'] ?? '');
    $receipt_no         = trim($_POST['receipt_no'] ?? '');
    $specification      = trim($_POST['specification'] ?? '');

    if ($current_condition === 'Damaged') {
        $available_quantity = max(0, $available_quantity - 1);
    }

    if ($id <= 0 || empty($counterfoil_type)) {
        respondJsonOrRedirect($is_ajax, false, 'Invalid counterfoil ID or missing type.', '../counter_foilage.php');
    }

    $stmt = $mysqli->prepare("UPDATE counterfoil_assets SET counterfoil_type = ?, book_serial_no = ?, page_count = ?, available_quantity = ?, initial_count = ?, received_quantity = ?, purchase_date = ?, current_condition = ?, remarks = ?, issue_order_no = ?, received_from = ?, receipt_no = ?, specification = ?, issued_to = ?, date_of_issue = ?, date_of_return = ? WHERE id = ? AND (farm_id = ? OR user_id = ?)");
    $stmt->bind_param("sssiiissssssssssiii", $counterfoil_type, $book_serial_no, $page_count, $available_quantity, $initial_count, $received_quantity, $purchase_date, $current_condition, $remarks, $issue_order_no, $received_from, $receipt_no, $specification, $issued_to, $date_of_issue, $date_of_return, $id, $farm_id, $user_id);

    if ($stmt->execute()) {
        if (!empty($counterfoil_type)) {
            $m_stmt = $mysqli->prepare("INSERT IGNORE INTO master_counterfoil_types (type_name, is_active) VALUES (?, 1)");
            if ($m_stmt) {
                $m_stmt->bind_param("s", $counterfoil_type);
                $m_stmt->execute();
                $m_stmt->close();
            }
        }
        respondJsonOrRedirect($is_ajax, true, 'Counterfoil details updated successfully.', '../counter_foilage.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to update counterfoil: ' . $stmt->error, '../counter_foilage.php');
    }
}

if ($action === 'delete_counterfoil') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM counterfoil_assets WHERE id = ? AND (farm_id = ? OR user_id = ?)");
        $stmt->bind_param("iii", $id, $farm_id, $user_id);
        if ($stmt->execute()) {
            respondJsonOrRedirect(false, true, 'Counterfoil entry deleted successfully.', '../counter_foilage.php');
        } else {
            respondJsonOrRedirect(false, false, 'Failed to delete counterfoil: ' . $stmt->error, '../counter_foilage.php');
        }
    }
}

// -------------------------------------------------------------
// 7. EMPLOYEE / HR CRUD (DATA ISOLATED BY FARM_ID)
// -------------------------------------------------------------
if ($action === 'save_employee') {
    $service_number = trim($_POST['service_number'] ?? '');
    $officer_name   = trim($_POST['officer_name'] ?? '');
    $designation    = trim($_POST['designation'] ?? '');
    $user_role      = trim($_POST['user_role'] ?? 'employee');
    $service_cat    = trim($_POST['service_category'] ?? '');
    $employment_type = trim($_POST['employment_type'] ?? 'permanent');
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

    $email          = trim($_POST['email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $dob            = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
    $app_date       = !empty($_POST['appointment_date']) ? $_POST['appointment_date'] : date('Y-m-d');
    $app_current    = !empty($_POST['appointment_date_current_position']) ? $_POST['appointment_date_current_position'] : date('Y-m-d');
    $pos_location   = !empty($_POST['position_to_current_location']) ? $_POST['position_to_current_location'] : date('Y-m-d');

    if (empty($officer_name) || empty($service_number) || empty($email)) {
        respondJsonOrRedirect($is_ajax, false, 'Officer Name, Service Number and Email are required.', '../employee_managment.php');
    }

    $username = strtolower(explode('@', $email)[0]);
    $default_password = password_hash("Daph1234", PASSWORD_BCRYPT);

    $stmt = $mysqli->prepare("
        INSERT INTO users (
            username, password, email, phone, full_name, 
            emp_id, service_number, designation, role, service_category, 
            employment_type, employment_status, attachment_reason, district_id, range_id, farm_id, date_of_birth, registered_date, appointment_date, 
            appointment_date_current_position, position_to_current_location, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, CURDATE(), ?, ?, ?, 1)
    ");

    if ($stmt) {
        $stmt->bind_param(
            "sssssssssssssiissss",
            $username,
            $default_password,
            $email,
            $contact_number,
            $officer_name,
            $service_number,
            $service_number,
            $designation,
            $user_role,
            $service_cat,
            $employment_type,
            $employment_status,
            $attachment_reason,
            $district_id,
            $farm_id,
            $dob,
            $app_date,
            $app_current,
            $pos_location
        );

        if ($stmt->execute()) {
            $new_user_id = $stmt->insert_id;
            $role_title = !empty($designation) ? $designation : $user_role;
            if (!empty($new_user_id)) {
                notify_assigned_officer($mysqli, $new_user_id, $role_title, 'dashboard.php');
            }
            create_officer_notification($mysqli, 'New Officer Added', $officer_name, $service_number, null, 'pages/modules/farm/employee_managment.php');
            respondJsonOrRedirect($is_ajax, true, 'Officer record successfully created under your farm profile.', '../employee_managment.php');
        } else {
            respondJsonOrRedirect($is_ajax, false, 'Database error creating officer account: ' . $stmt->error, '../employee_managment.php');
        }
        $stmt->close();
    }
}

if ($action === 'update_employee') {
    $id             = intval($_POST['id'] ?? 0);
    $service_number = trim($_POST['service_number'] ?? '');
    $officer_name   = trim($_POST['officer_name'] ?? '');
    $designation    = trim($_POST['designation'] ?? '');
    $user_role      = trim($_POST['user_role'] ?? 'employee');
    $service_cat    = trim($_POST['service_category'] ?? '');
    $employment_type = trim($_POST['employment_type'] ?? 'permanent');
    if (!in_array($employment_type, ['permanent', 'temporary'])) {
        $employment_type = 'permanent';
    }
    $email          = trim($_POST['email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $dob            = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
    $app_date       = !empty($_POST['appointment_date']) ? $_POST['appointment_date'] : date('Y-m-d');
    $app_current    = !empty($_POST['appointment_date_current_position']) ? $_POST['appointment_date_current_position'] : date('Y-m-d');
    $pos_location   = !empty($_POST['position_to_current_location']) ? $_POST['position_to_current_location'] : date('Y-m-d');

    if ($id <= 0 || empty($officer_name) || empty($email)) {
        respondJsonOrRedirect($is_ajax, false, 'Invalid officer ID or missing mandatory attributes.', '../employee_managment.php');
    }

    // Fetch existing live record snapshot
    $stmt_curr = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
    $stmt_curr->bind_param("i", $id);
    $stmt_curr->execute();
    $old_user = $stmt_curr->get_result()->fetch_assoc();
    $stmt_curr->close();

    $new_user_data = [
        'service_number'   => $service_number,
        'full_name'        => $officer_name,
        'designation'      => $designation,
        'role'             => $user_role,
        'service_category' => $service_cat,
        'employment_type'  => $employment_type,
        'email'            => $email,
        'phone'            => $contact_number,
        'date_of_birth'    => $dob,
        'appointment_date' => $app_date,
        'appointment_date_current_position' => $app_current,
        'position_to_current_location' => $pos_location
    ];

    $staging_res = stage_or_apply_edit($mysqli, 'hr', 'users', $id, $officer_name, $old_user ?: [], $new_user_data, $district_id);
    if (!empty($staging_res['is_staged'])) {
        respondJsonOrRedirect($is_ajax, true, 'Edit submitted successfully. Changes are pending authorization by the Provincial Director.', '../employee_managment.php', ['staged' => true]);
    }

    $stmt = $mysqli->prepare("
        UPDATE users SET 
            service_number = ?, full_name = ?, designation = ?, role = ?, 
            service_category = ?, employment_type = ?, email = ?, phone = ?, date_of_birth = ?, appointment_date = ?, 
            appointment_date_current_position = ?, position_to_current_location = ?
        WHERE id = ? AND (farm_id = ? OR id = ?)
    ");
    $stmt->bind_param("ssssssssssssiii", $service_number, $officer_name, $designation, $user_role, $service_cat, $employment_type, $email, $contact_number, $dob, $app_date, $app_current, $pos_location, $id, $farm_id, $user_id);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Officer details updated successfully.', '../employee_managment.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to update officer details: ' . $stmt->error, '../employee_managment.php');
    }
}

if ($action === 'delete_employee') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $mysqli->prepare("UPDATE users SET is_active = 0 WHERE id = ? AND (farm_id = ? OR id = ?)");
        $stmt->bind_param("iii", $id, $farm_id, $user_id);
        if ($stmt->execute()) {
            respondJsonOrRedirect(false, true, 'Officer record removed successfully.', '../employee_managment.php');
        } else {
            respondJsonOrRedirect(false, false, 'Failed to delete officer record: ' . $stmt->error, '../employee_managment.php');
        }
    }
}

$mysqli->close();
?>
