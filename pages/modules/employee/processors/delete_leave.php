<?php
session_start();
require_once '../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $leave_id = $_POST['leave_id'];
    $user_id = $_SESSION['user_id'];
    
    // Delete query (only pending requests)
    $query = "DELETE FROM leave_requests WHERE id = '$leave_id' AND user_id = '$user_id' AND status = 'Pending'";
    
    if ($mysqli->query($query)) {
        $_SESSION['success'] = "Leave request deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete leave request: " . $mysqli->error;
    }
    
    header("Location: ../../../views/employee/leave_request.php");
    exit();
}
?>