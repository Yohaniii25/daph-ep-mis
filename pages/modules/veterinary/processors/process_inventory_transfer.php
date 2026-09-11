<?php
/**
 * pages/modules/veterinary/processors/process_inventory_transfer.php
 * Handles initiating inter-unit inventory transfer requests without deducting active quantity while pending
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/../../../../config/db_connect.php';
require_once __DIR__ . '/../../../../includes/approval_helper.php';

$allowed_roles = [
    'veterinary_surgeon', 
    'government_veterinary_surgeon', 
    'additional_veterinary_surgeon', 
    'provincial_director', 
    'district_dd', 
    'deputy_director_district',
    'administrator',
    'subject_matter_specialist',
    'farms_dd',
    'training_officer'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized operation. Please log in with authorized credentials.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$user_id            = intval($_SESSION['user_id'] ?? 0);
$user_role          = $_SESSION['role'] ?? '';
$user_name          = $_SESSION['full_name'] ?? $_SESSION['username'] ?? "Officer #{$user_id}";

$id                 = isset($_POST['id']) ? intval($_POST['id']) : (filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0);
$asset_type         = trim($_POST['asset_type'] ?? 'building_inventory');
$transfer_qty       = isset($_POST['transfer_quantity']) ? intval($_POST['transfer_quantity']) : (filter_input(INPUT_POST, 'transfer_quantity', FILTER_VALIDATE_INT) ?: 1);
$target_unit        = trim($_POST['target_unit'] ?? '');
$dispatch_reference = trim($_POST['dispatch_reference'] ?? '');
$transfer_reason    = trim($_POST['transfer_reason'] ?? '');
$from_unit_input    = trim($_POST['from_unit'] ?? '');

// Validation
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing inventory item identifier.']);
    exit();
}

if (empty($target_unit)) {
    echo json_encode(['success' => false, 'message' => 'Target destination unit / office is required.']);
    exit();
}

if ($transfer_qty < 1) {
    echo json_encode(['success' => false, 'message' => 'Transfer quantity must be at least 1 unit.']);
    exit();
}

if (empty($transfer_reason)) {
    echo json_encode(['success' => false, 'message' => 'Please provide an operational reason or justification for this transfer.']);
    exit();
}

// Table mapping
$table_map = [
    'building_inventory' => 'building_inventories',
    'furniture'          => 'furniture_assets',
    'machinery'          => 'machinery_assets',
    'instrument'         => 'instrument_assets',
    'counterfoil'        => 'counterfoil_assets'
];

$table = $table_map[$asset_type] ?? 'building_inventories';

// Fetch current asset record
$stmt = $mysqli->prepare("SELECT * FROM `$table` WHERE id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error preparing item query: ' . $mysqli->error]);
    exit();
}
$stmt->bind_param("i", $id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Inventory record not found in system.']);
    exit();
}

$available_qty = intval($item['available_quantity'] ?? 0);
if ($transfer_qty > $available_qty) {
    echo json_encode([
        'success' => false, 
        'message' => "Requested transfer quantity ({$transfer_qty}) exceeds currently available active circulation ({$available_qty})."
    ]);
    exit();
}

// Determine item name
if ($table === 'building_inventories') {
    $item_name = $item['inventory_item'] ?? 'Building Inventory Item';
} elseif ($table === 'furniture_assets') {
    $item_name = $item['furniture_type'] ?? 'Furniture Asset';
} elseif ($table === 'machinery_assets') {
    $item_name = $item['machinery_type'] ?? 'Machinery Asset';
} elseif ($table === 'instrument_assets') {
    $item_name = $item['instrument_type'] ?? 'Instrument Asset';
} else {
    $item_name = $item['counterfoil_type'] ?? 'Counterfoil Asset';
}

// Determine source location metadata
$from_district_id = !empty($item['district_id']) ? intval($item['district_id']) : intval($_SESSION['district_id'] ?? 0);
$from_range_id    = !empty($item['range_id']) ? intval($item['range_id']) : intval($_SESSION['range_id'] ?? 0);
$from_farm_id     = !empty($item['farm_id']) ? intval($item['farm_id']) : intval($_SESSION['farm_id'] ?? 0);
$from_tc_id       = !empty($item['training_center_id']) ? intval($item['training_center_id']) : intval($_SESSION['training_center_id'] ?? 0);

$from_unit = $from_unit_input;
if (empty($from_unit)) {
    if (!empty($item['unit'])) {
        $from_unit = $item['unit'];
    } elseif ($from_range_id > 0) {
        $q_r = $mysqli->query("SELECT name FROM veterinary_ranges WHERE id = {$from_range_id}");
        if ($q_r && $r_row = $q_r->fetch_assoc()) {
            $from_unit = "Range Office - {$r_row['name']}";
        }
    }
}
if (empty($from_unit)) {
    $from_unit = "Current Workstation";
}

// Resolve destination location metadata
$target_info = parse_transfer_target_unit($mysqli, $target_unit);
$to_district_id = $target_info['target_district_id'];
$to_range_id    = $target_info['target_range_id'];
$to_farm_id     = $target_info['target_farm_id'];
$to_tc_id       = $target_info['target_training_center_id'];

// Default dispatch reference if omitted
if (empty($dispatch_reference)) {
    $dispatch_reference = "TR-" . date('Y') . "-" . sprintf("%04d", mt_rand(100, 9999));
}

// 1. Log transfer record in inventory_transfers table
$ins_trans = $mysqli->prepare("
    INSERT INTO inventory_transfers 
    (asset_type, asset_id, item_name, transfer_qty, from_unit, from_district_id, from_range_id, from_farm_id, from_training_center_id, to_unit, to_district_id, to_range_id, to_farm_id, to_training_center_id, status, dispatch_reference, transfer_reason, initiated_by, initiated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?, NOW())
");

if (!$ins_trans) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare transfer record: ' . $mysqli->error]);
    exit();
}

$ins_trans->bind_param(
    "sisisiisisiissssi",
    $asset_type,
    $id,
    $item_name,
    $transfer_qty,
    $from_unit,
    $from_district_id,
    $from_range_id,
    $from_farm_id,
    $from_tc_id,
    $target_unit,
    $to_district_id,
    $to_range_id,
    $to_farm_id,
    $to_tc_id,
    $dispatch_reference,
    $transfer_reason,
    $user_id
);

$trans_exec = $ins_trans->execute();
$transfer_id = $mysqli->insert_id;
$ins_trans->close();

if (!$trans_exec || !$transfer_id) {
    echo json_encode(['success' => false, 'message' => 'Failed to log inventory transfer record: ' . $mysqli->error]);
    exit();
}

// CRITICAL INVARIANT: The active available_quantity on `$table` is NOT deducted here!
// It remains completely intact while the transfer is logged and pending approval.

// 2. Stage approval record into pending_approvals
$old_data = [
    'asset_type'             => $asset_type,
    'asset_id'               => $id,
    'item_name'              => $item_name,
    'from_unit'              => $from_unit,
    'available_quantity'     => $available_qty,
    'current_condition'      => $item['current_condition'] ?? 'Good',
    'initial_count'          => $item['initial_count'] ?? $available_qty
];

$new_data = [
    'asset_type'                => $asset_type,
    'asset_id'                  => $id,
    'transfer_id'               => $transfer_id,
    'target_unit'               => $target_unit,
    'transfer_qty'              => $transfer_qty,
    'dispatch_reference'        => $dispatch_reference,
    'reason'                    => $transfer_reason,
    'target_range_id'           => $to_range_id,
    'target_district_id'        => $to_district_id,
    'target_farm_id'            => $to_farm_id,
    'target_training_center_id' => $to_tc_id
];

$old_json = json_encode($old_data, JSON_UNESCAPED_UNICODE);
$new_json = json_encode($new_data, JSON_UNESCAPED_UNICODE);

$stmt_app = $mysqli->prepare("
    INSERT INTO pending_approvals 
    (module, record_type, record_id, target_name, requested_by, requester_name, requester_role, district_id, range_id, old_data, new_data, status, created_at)
    VALUES ('inventory', 'inventory_transfer', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
");

if ($stmt_app) {
    $stmt_app->bind_param(
        "isisiiiss",
        $transfer_id,
        $item_name,
        $user_id,
        $user_name,
        $user_role,
        $from_district_id,
        $from_range_id,
        $old_json,
        $new_json
    );
    $stmt_app->execute();
    $approval_id = $mysqli->insert_id;
    $stmt_app->close();

    // 3. Dispatch automated notification to Provincial Director & Administrators
    $notif_title = "Inventory Transfer Request: {$item_name}";
    $notif_msg = "Officer {$user_name} ({$user_role}) initiated transfer of {$transfer_qty} unit(s) of '{$item_name}' from [{$from_unit}] to [{$target_unit}]. Active quantity intact pending authorization.";
    $notif_link = "pages/modules/pd/pending_approvals.php?filter=inventory";

    $adm_res = $mysqli->query("SELECT id FROM users WHERE role IN ('provincial_director', 'administrator') AND is_active = 1");
    if ($adm_res) {
        $ins_n = $mysqli->prepare("INSERT INTO notifications (user_id, title, message, type, link, is_read, created_at) VALUES (?, ?, ?, 'transfer_alert', ?, 0, NOW())");
        if ($ins_n) {
            while ($adm_user = $adm_res->fetch_assoc()) {
                $adm_uid = intval($adm_user['id']);
                $ins_n->bind_param("isss", $adm_uid, $notif_title, $notif_msg, $notif_link);
                $ins_n->execute();
            }
            $ins_n->close();
        }
    }
}

echo json_encode([
    'success' => true,
    'message' => "Transfer request for {$transfer_qty} unit(s) of '{$item_name}' initiated successfully under reference [{$dispatch_reference}]. Active count remains intact ({$available_qty} available) pending authorization.",
    'transfer_id' => $transfer_id,
    'available_quantity' => $available_qty
]);
exit();
