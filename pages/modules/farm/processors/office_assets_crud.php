<?php
// pages/modules/farm/processors/office_assets_crud.php
session_start();
require_once '../../../../config/db_connect.php';

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

function respondJsonOrRedirect($is_ajax, $success, $msg, $redirect_url) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $msg]);
        exit();
    } else {
        $status = $success ? 'success' : 'error';
        header("Location: " . $redirect_url . (strpos($redirect_url, '?') !== false ? '&' : '?') . "status=" . $status . "&msg=" . urlencode($msg));
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
    $inventory_item    = trim($_POST['inventory_item'] ?? '');
    $specification      = trim($_POST['specification'] ?? '');
    $current_condition  = trim($_POST['current_condition'] ?? 'Good');
    $available_quantity = intval($_POST['available_quantity'] ?? 1);
    $remarks            = trim($_POST['remarks'] ?? '');

    if ($land_asset_id <= 0 || empty($inventory_item)) {
        respondJsonOrRedirect($is_ajax, false, 'Land Property selection and Item Name are required.', '../lands_buildings.php?tab=inventory');
    }

    $stmt = $mysqli->prepare("INSERT INTO building_inventories (land_asset_id, user_id, farm_id, user_category, inventory_item, specification, current_condition, available_quantity, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiissssis", $land_asset_id, $user_id, $farm_id, $user_category, $inventory_item, $specification, $current_condition, $available_quantity, $remarks);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Building inventory item logged successfully.', '../lands_buildings.php?tab=inventory');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to log building inventory: ' . $stmt->error, '../lands_buildings.php?tab=inventory');
    }
}

if ($action === 'update_building_inventory') {
    $id                 = intval($_POST['id'] ?? 0);
    $land_asset_id      = intval($_POST['land_asset_id'] ?? 0);
    $inventory_item    = trim($_POST['inventory_item'] ?? '');
    $specification      = trim($_POST['specification'] ?? '');
    $current_condition  = trim($_POST['current_condition'] ?? 'Good');
    $available_quantity = intval($_POST['available_quantity'] ?? 1);
    $remarks            = trim($_POST['remarks'] ?? '');

    if ($id <= 0 || empty($inventory_item)) {
        respondJsonOrRedirect($is_ajax, false, 'Invalid item ID or missing item name.', '../lands_buildings.php?tab=inventory');
    }

    $stmt = $mysqli->prepare("UPDATE building_inventories SET land_asset_id = ?, inventory_item = ?, specification = ?, current_condition = ?, available_quantity = ?, remarks = ? WHERE id = ? AND (farm_id = ? OR user_id = ?)");
    $stmt->bind_param("isssisiii", $land_asset_id, $inventory_item, $specification, $current_condition, $available_quantity, $remarks, $id, $farm_id, $user_id);

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
    $vehicle_type     = trim($_POST['vehicle_type'] ?? 'Tractor');
    $vehicle_number   = strtoupper(trim($_POST['vehicle_number'] ?? ''));
    $chassis_number   = strtoupper(trim($_POST['chassis_number'] ?? ''));
    $current_condition = trim($_POST['current_condition'] ?? 'Good/Running');
    $other_details    = trim($_POST['other_details'] ?? '');

    if (empty($vehicle_number)) {
        respondJsonOrRedirect($is_ajax, false, 'Vehicle Registration Number is required.', '../vehicles.php');
    }

    $stmt = $mysqli->prepare("INSERT INTO registered_vehicles (user_id, farm_id, user_category, district_id, range_id, vehicle_type, vehicle_number, chassis_number, current_condition, other_details) VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisisssss", $user_id, $farm_id, $user_category, $district_id, $vehicle_type, $vehicle_number, $chassis_number, $current_condition, $other_details);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Vehicle registered successfully in fleet registry.', '../vehicles.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to register vehicle: ' . $stmt->error, '../vehicles.php');
    }
}

if ($action === 'update_vehicle') {
    $id               = intval($_POST['id'] ?? 0);
    $vehicle_type     = trim($_POST['vehicle_type'] ?? 'Tractor');
    $vehicle_number   = strtoupper(trim($_POST['vehicle_number'] ?? ''));
    $chassis_number   = strtoupper(trim($_POST['chassis_number'] ?? ''));
    $current_condition = trim($_POST['current_condition'] ?? 'Good/Running');
    $other_details    = trim($_POST['other_details'] ?? '');

    if ($id <= 0 || empty($vehicle_number)) {
        respondJsonOrRedirect($is_ajax, false, 'Invalid vehicle ID or missing vehicle number.', '../vehicles.php');
    }

    $stmt = $mysqli->prepare("UPDATE registered_vehicles SET vehicle_type = ?, vehicle_number = ?, chassis_number = ?, current_condition = ?, other_details = ? WHERE id = ? AND (farm_id = ? OR user_id = ?)");
    $stmt->bind_param("sssssiii", $vehicle_type, $vehicle_number, $chassis_number, $current_condition, $other_details, $id, $farm_id, $user_id);

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

    $stmt = $mysqli->prepare("INSERT INTO registered_vehicle_repairs (vehicle_id, user_id, farm_id, user_category, repair_date, repair_nature, cost_lkr, repaired_by, invoice_ref, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
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

    if ($id <= 0 || empty($repair_nature)) {
        respondJsonOrRedirect($is_ajax, false, 'Invalid repair log ID or missing description.', '../vehicles.php?tab=repairs');
    }

    $stmt = $mysqli->prepare("UPDATE registered_vehicle_repairs SET vehicle_id = ?, repair_date = ?, repair_nature = ?, cost_lkr = ?, repaired_by = ?, invoice_ref = ?, remarks = ? WHERE id = ? AND (farm_id = ? OR user_id = ?)");
    $stmt->bind_param("issdsssiii", $vehicle_id, $repair_date, $repair_nature, $cost_lkr, $repaired_by, $invoice_ref, $remarks, $id, $farm_id, $user_id);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Vehicle repair log updated successfully.', '../vehicles.php?tab=repairs');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to update repair log: ' . $stmt->error, '../vehicles.php?tab=repairs');
    }
}

if ($action === 'delete_vehicle_repair') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM registered_vehicle_repairs WHERE id = ? AND (farm_id = ? OR user_id = ?)");
        $stmt->bind_param("iii", $id, $farm_id, $user_id);
        if ($stmt->execute()) {
            respondJsonOrRedirect(false, true, 'Vehicle repair log deleted successfully.', '../vehicles.php?tab=repairs');
        } else {
            respondJsonOrRedirect(false, false, 'Failed to delete repair log: ' . $stmt->error, '../vehicles.php?tab=repairs');
        }
    }
}

// -------------------------------------------------------------
// 3. FURNITURE CRUD
// -------------------------------------------------------------
if ($action === 'save_furniture') {
    $furniture_type     = trim($_POST['furniture_type'] ?? 'Office Chairs');
    $available_quantity = intval($_POST['available_quantity'] ?? 1);
    $date_received      = !empty($_POST['date_received']) ? $_POST['date_received'] : date('Y-m-d');
    $current_condition  = trim($_POST['current_condition'] ?? 'Good Condition');
    $remarks            = trim($_POST['remarks'] ?? '');

    if (empty($furniture_type) || $available_quantity <= 0) {
        respondJsonOrRedirect($is_ajax, false, 'Furniture Type and Quantity are required.', '../furniture.php');
    }

    $stmt = $mysqli->prepare("INSERT INTO furniture_assets (user_id, farm_id, user_category, district_id, range_id, furniture_type, current_condition, available_quantity, date_received, remarks) VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisississ", $user_id, $farm_id, $user_category, $district_id, $furniture_type, $current_condition, $available_quantity, $date_received, $remarks);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Furniture asset registered successfully.', '../furniture.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to register furniture: ' . $stmt->error, '../furniture.php');
    }
}

if ($action === 'update_furniture') {
    $id                 = intval($_POST['id'] ?? 0);
    $furniture_type     = trim($_POST['furniture_type'] ?? 'Office Chairs');
    $available_quantity = intval($_POST['available_quantity'] ?? 1);
    $date_received      = !empty($_POST['date_received']) ? $_POST['date_received'] : date('Y-m-d');
    $current_condition  = trim($_POST['current_condition'] ?? 'Good Condition');
    $remarks            = trim($_POST['remarks'] ?? '');

    if ($id <= 0 || empty($furniture_type)) {
        respondJsonOrRedirect($is_ajax, false, 'Invalid furniture ID or missing type.', '../furniture.php');
    }

    $stmt = $mysqli->prepare("UPDATE furniture_assets SET furniture_type = ?, available_quantity = ?, date_received = ?, current_condition = ?, remarks = ? WHERE id = ? AND (farm_id = ? OR user_id = ?)");
    $stmt->bind_param("sissiiii", $furniture_type, $available_quantity, $date_received, $current_condition, $remarks, $id, $farm_id, $user_id);

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
    $available_quantity = intval($_POST['available_quantity'] ?? 1);
    $purchase_date      = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : date('Y-m-d');
    $current_condition  = trim($_POST['current_condition'] ?? 'Operational / Good');
    $remarks            = trim($_POST['remarks'] ?? '');

    if (empty($machinery_type) || $available_quantity <= 0) {
        respondJsonOrRedirect($is_ajax, false, 'Machinery Type and Quantity are required.', '../machineries.php');
    }

    $stmt = $mysqli->prepare("INSERT INTO machinery_assets (user_id, farm_id, user_category, district_id, range_id, machinery_type, current_condition, available_quantity, purchase_date, remarks) VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisississ", $user_id, $farm_id, $user_category, $district_id, $machinery_type, $current_condition, $available_quantity, $purchase_date, $remarks);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Machinery asset registered successfully.', '../machineries.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to register machinery: ' . $stmt->error, '../machineries.php');
    }
}

if ($action === 'update_machinery') {
    $id                 = intval($_POST['id'] ?? 0);
    $machinery_type     = trim($_POST['machinery_type'] ?? 'Generator');
    $available_quantity = intval($_POST['available_quantity'] ?? 1);
    $purchase_date      = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : date('Y-m-d');
    $current_condition  = trim($_POST['current_condition'] ?? 'Operational / Good');
    $remarks            = trim($_POST['remarks'] ?? '');

    if ($id <= 0 || empty($machinery_type)) {
        respondJsonOrRedirect($is_ajax, false, 'Invalid machinery ID or missing type.', '../machineries.php');
    }

    $stmt = $mysqli->prepare("UPDATE machinery_assets SET machinery_type = ?, available_quantity = ?, purchase_date = ?, current_condition = ?, remarks = ? WHERE id = ? AND (farm_id = ? OR user_id = ?)");
    $stmt->bind_param("sissiiii", $machinery_type, $available_quantity, $purchase_date, $current_condition, $remarks, $id, $farm_id, $user_id);

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
    $available_quantity = intval($_POST['available_quantity'] ?? 1);
    $purchase_date      = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : date('Y-m-d');
    $current_condition  = trim($_POST['current_condition'] ?? 'Working / Calibrated');
    $remarks            = trim($_POST['remarks'] ?? '');

    if (empty($instrument_type) || $available_quantity <= 0) {
        respondJsonOrRedirect($is_ajax, false, 'Instrument Type and Quantity are required.', '../instruments.php');
    }

    $stmt = $mysqli->prepare("INSERT INTO instrument_assets (user_id, farm_id, user_category, district_id, range_id, instrument_type, current_condition, available_quantity, purchase_date, remarks) VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisississ", $user_id, $farm_id, $user_category, $district_id, $instrument_type, $current_condition, $available_quantity, $purchase_date, $remarks);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Instrument asset registered successfully.', '../instruments.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to register instrument: ' . $stmt->error, '../instruments.php');
    }
}

if ($action === 'update_instrument') {
    $id                 = intval($_POST['id'] ?? 0);
    $instrument_type    = trim($_POST['instrument_type'] ?? 'AI Equipment');
    $available_quantity = intval($_POST['available_quantity'] ?? 1);
    $purchase_date      = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : date('Y-m-d');
    $current_condition  = trim($_POST['current_condition'] ?? 'Working / Calibrated');
    $remarks            = trim($_POST['remarks'] ?? '');

    if ($id <= 0 || empty($instrument_type)) {
        respondJsonOrRedirect($is_ajax, false, 'Invalid instrument ID or missing type.', '../instruments.php');
    }

    $stmt = $mysqli->prepare("UPDATE instrument_assets SET instrument_type = ?, available_quantity = ?, purchase_date = ?, current_condition = ?, remarks = ? WHERE id = ? AND (farm_id = ? OR user_id = ?)");
    $stmt->bind_param("sissiiii", $instrument_type, $available_quantity, $purchase_date, $current_condition, $remarks, $id, $farm_id, $user_id);

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
    $available_quantity = intval($_POST['available_quantity'] ?? 1);
    $purchase_date      = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : date('Y-m-d');
    $current_condition  = trim($_POST['current_condition'] ?? 'Active / In Use');
    $remarks            = trim($_POST['remarks'] ?? '');

    if (empty($counterfoil_type) || $available_quantity <= 0) {
        respondJsonOrRedirect($is_ajax, false, 'Counterfoil Type and Quantity are required.', '../counter_foilage.php');
    }

    $stmt = $mysqli->prepare("INSERT INTO counterfoil_assets (user_id, farm_id, user_category, district_id, range_id, counterfoil_type, current_condition, available_quantity, purchase_date, remarks) VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisississ", $user_id, $farm_id, $user_category, $district_id, $counterfoil_type, $current_condition, $available_quantity, $purchase_date, $remarks);

    if ($stmt->execute()) {
        respondJsonOrRedirect($is_ajax, true, 'Counterfoil registered successfully.', '../counter_foilage.php');
    } else {
        respondJsonOrRedirect($is_ajax, false, 'Failed to register counterfoil: ' . $stmt->error, '../counter_foilage.php');
    }
}

if ($action === 'update_counterfoil') {
    $id                 = intval($_POST['id'] ?? 0);
    $counterfoil_type   = trim($_POST['counterfoil_type'] ?? 'General Receipt Book');
    $available_quantity = intval($_POST['available_quantity'] ?? 1);
    $purchase_date      = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : date('Y-m-d');
    $current_condition  = trim($_POST['current_condition'] ?? 'Active / In Use');
    $remarks            = trim($_POST['remarks'] ?? '');

    if ($id <= 0 || empty($counterfoil_type)) {
        respondJsonOrRedirect($is_ajax, false, 'Invalid counterfoil ID or missing type.', '../counter_foilage.php');
    }

    $stmt = $mysqli->prepare("UPDATE counterfoil_assets SET counterfoil_type = ?, available_quantity = ?, purchase_date = ?, current_condition = ?, remarks = ? WHERE id = ? AND (farm_id = ? OR user_id = ?)");
    $stmt->bind_param("sissiiii", $counterfoil_type, $available_quantity, $purchase_date, $current_condition, $remarks, $id, $farm_id, $user_id);

    if ($stmt->execute()) {
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

$mysqli->close();
?>
