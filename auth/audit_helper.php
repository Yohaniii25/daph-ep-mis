<?php
function logActivity($mysqli, $userId, $action, $table, $recordId, $oldValues = null, $newValues = null, $remarks = "") {
    $username = $_SESSION['username'] ?? 'System';
    $role     = $_SESSION['role'] ?? 'Unknown';
    $ip       = $_SERVER['REMOTE_ADDR'];
    $device   = $_SERVER['HTTP_USER_AGENT'];

    $stmt = $mysqli->prepare("
        INSERT INTO audit_logs 
        (user_id, username, role, action_type, table_name, record_id, old_values, new_values, ip_address, device_info, remarks) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    // Convert arrays/objects to JSON strings for the TEXT columns
    $oldJson = $oldValues ? json_encode($oldValues) : null;
    $newJson = $newValues ? json_encode($newValues) : null;

    $stmt->bind_param("isssissssss", $userId, $username, $role, $action, $table, $recordId, $oldJson, $newJson, $ip, $device, $remarks);
    return $stmt->execute();
}