<?php
session_start();
require_once '../../../../config/db_connect.php';
require_once '../../../../includes/notification_helper.php';

header('Content-Type: application/json');

$vs_roles = ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], $vs_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized system access request.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid or missing target employee identifier.']);
        exit();
    }

    // Explicit cross-check validation: Ensure this VS can only deactivate users inside their own assigned district and range
    $range_id = $_SESSION['range_id'] ?? null;
    $district_id = $_SESSION['district_id'] ?? null;

    // Fetch officer details before deactivation for notification
    $stmt_find = $mysqli->prepare("SELECT id, full_name, service_number, emp_id, range_id FROM users WHERE id = ? AND district_id = ? AND range_id = ?");
    $officer = null;
    if ($stmt_find) {
        $stmt_find->bind_param("iii", $user_id, $district_id, $range_id);
        $stmt_find->execute();
        $officer = $stmt_find->get_result()->fetch_assoc();
        $stmt_find->close();
    }

    $stmt = $mysqli->prepare("UPDATE users SET is_active = 0 WHERE id = ? AND district_id = ? AND range_id = ?");
    
    if ($stmt) {
        $stmt->bind_param("iii", $user_id, $district_id, $range_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                // Trigger automated notification
                if ($officer) {
                    $svc_no = !empty($officer['service_number']) ? $officer['service_number'] : ($officer['emp_id'] ?? '');
                    create_officer_notification(
                        $mysqli, 
                        'Officer Removed', 
                        $officer['full_name'], 
                        $svc_no, 
                        $range_id, 
                        'pages/modules/veterinary/employee_managment.php'
                    );
                }

                echo json_encode(['success' => true, 'message' => 'Officer record successfully deactivated.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Record not found or you do not have permission to modify it.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Database execution failed.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare the database operation statement.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method context encountered.']);
}