<?php
/**
 * pages/modules/veterinary/processors/approve_vehicle_repair.php
 * Handles approval/rejection workflows for vehicle maintenance requests
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/db_connect.php';

if (!headers_sent()) {
    header('Content-Type: application/json');
}

$allowed_roles = ['provincial_director', 'district_dd', 'administrator'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: You do not have permission to review repair requests.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $repair_id = filter_input(INPUT_POST, 'repair_id', FILTER_VALIDATE_INT);
    $action = trim(filter_input(INPUT_POST, 'action', FILTER_SANITIZE_SPECIAL_CHARS)); // 'approve' or 'reject'
    $rejection_reason = trim(filter_input(INPUT_POST, 'rejection_reason', FILTER_SANITIZE_SPECIAL_CHARS));
    $user_id = $_SESSION['user_id'] ?? null;
    $user_role = $_SESSION['role'] ?? '';

    if (!$repair_id || !in_array($action, ['approve', 'reject'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters provided for approval workflow.']);
        exit();
    }

    if ($action === 'reject' && empty($rejection_reason)) {
        echo json_encode(['success' => false, 'message' => 'Please provide a justification reason for rejection.']);
        exit();
    }

    // Fetch repair details
    $stmt = $mysqli->prepare("
        SELECT vr.*, v.registration_no, v.vehicle_name, v.district_id 
        FROM vehicle_repairs vr
        LEFT JOIN registered_vehicles v ON vr.vehicle_id = v.id
        WHERE vr.id = ? AND vr.is_active = 1
        LIMIT 1
    ");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database query preparation failed.']);
        exit();
    }
    $stmt->bind_param("i", $repair_id);
    $stmt->execute();
    $repair = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$repair) {
        echo json_encode(['success' => false, 'message' => 'Repair record not found.']);
        exit();
    }

    $cost = floatval($repair['transaction_amount'] > 0 ? $repair['transaction_amount'] : $repair['amount']);
    $veh_name = !empty($repair['registration_no']) ? $repair['registration_no'] : ($repair['vehicle_name'] ?? 'Vehicle #' . $repair['vehicle_id']);

    // Check tier authorization
    if ($user_role === 'district_dd' && $cost > 50000) {
        echo json_encode([
            'success' => false, 
            'message' => 'Expenditure exceeds LKR 50,000.00. This repair request requires Provincial Director approval.'
        ]);
        exit();
    }

    // Check district jurisdiction if district_dd
    if ($user_role === 'district_dd' && !empty($_SESSION['district_id']) && !empty($repair['district_id'])) {
        if (intval($_SESSION['district_id']) !== intval($repair['district_id'])) {
            echo json_encode([
                'success' => false, 
                'message' => 'Unauthorized: This vehicle belongs to a different district jurisdiction.'
            ]);
            exit();
        }
    }

    $new_status = ($action === 'approve') ? 'Approved' : 'Rejected';
    $reason_val = ($action === 'reject') ? $rejection_reason : null;

    $upd = $mysqli->prepare("
        UPDATE vehicle_repairs 
        SET approval_status = ?, 
            approved_by = ?, 
            approved_at = NOW(), 
            rejection_reason = ? 
        WHERE id = ?
    ");

    if ($upd) {
        $upd->bind_param("sisi", $new_status, $user_id, $reason_val, $repair_id);
        if ($upd->execute()) {
            $upd->close();

            // Fetch approver's display name
            $approver_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Authority';

            // Send notification to the requester
            $requester_id = intval($repair['user_id']);
            if ($requester_id > 0) {
                $notif_title = ($action === 'approve') 
                    ? "Vehicle Repair Request Approved: {$veh_name}" 
                    : "Vehicle Repair Request Rejected: {$veh_name}";
                
                $notif_msg = ($action === 'approve')
                    ? "Your repair request for {$veh_name} (LKR " . number_format($cost, 2) . ") was approved by {$approver_name}."
                    : "Your repair request for {$veh_name} (LKR " . number_format($cost, 2) . ") was rejected by {$approver_name}. Reason: {$rejection_reason}";

                $link = "pages/modules/veterinary/vehicles.php";
                $notif_stmt = $mysqli->prepare("
                    INSERT INTO notifications (user_id, title, message, type, link, is_read, created_at)
                    VALUES (?, ?, ?, 'repair_approval', ?, 0, NOW())
                ");
                if ($notif_stmt) {
                    $notif_stmt->bind_param("isss", $requester_id, $notif_title, $notif_msg, $link);
                    $notif_stmt->execute();
                    $notif_stmt->close();
                }
            }

            echo json_encode([
                'success' => true,
                'message' => "Repair request successfully marked as {$new_status}.",
                'status' => $new_status
            ]);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update repair record: ' . $upd->error]);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Statement preparation error: ' . $mysqli->error]);
        exit();
    }
}
