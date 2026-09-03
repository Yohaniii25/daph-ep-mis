<?php
session_start();
require_once '../../../../config/db_connect.php';
require_once '../../../../includes/notification_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'administrator') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid officer identifier.']);
        exit();
    }

    // 1. Fetch officer details before removal to get range_id and identifiers
    $stmt_find = $mysqli->prepare("SELECT id, officer_name, emp_id, range_id FROM office_details WHERE id = ?");
    $stmt_find->bind_param("i", $id);
    $stmt_find->execute();
    $officer = $stmt_find->get_result()->fetch_assoc();
    $stmt_find->close();

    if (!$officer) {
        echo json_encode(['success' => false, 'message' => 'Officer record not found.']);
        exit();
    }

    // 2. Update status to Inactive (or delete)
    $stmt_del = $mysqli->prepare("UPDATE office_details SET status = 'Inactive' WHERE id = ?");
    $stmt_del->bind_param("i", $id);

    if ($stmt_del->execute()) {
        $stmt_del->close();

        // 3. Trigger automated notification
        create_officer_notification(
            $mysqli, 
            'Officer Removed', 
            $officer['officer_name'], 
            $officer['emp_id'], 
            $officer['range_id'], 
            'pages/modules/hr/employee_managment.php'
        );

        echo json_encode([
            'success' => true, 
            'message' => 'Officer record successfully deactivated.'
        ]);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt_del->error]);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}
