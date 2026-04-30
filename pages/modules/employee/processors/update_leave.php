<?php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $leave_id = $_POST['leave_id'];
    $user_id = $_SESSION['user_id'];
    $start_date = $_POST['start_date'];
    $resume_date = $_POST['resume_date'];
    $leave_type = $_POST['leave_type'];
    $reason = $_POST['reason'];
    $no_of_days = $_POST['no_of_days'];
    $is_half_day = isset($_POST['is_half_day']) ? 1 : 0;
    
    // Update query
    $query = "UPDATE leave_requests SET 
              start_date = '$start_date',
              resume_date = '$resume_date',
              leave_type = '$leave_type',
              reason = '$reason',
              no_of_days = '$no_of_days',
              is_half_day = '$is_half_day'
              WHERE id = '$leave_id' AND user_id = '$user_id' AND status = 'Pending'";
    
    if ($mysqli->query($query)) {
        $_SESSION['success'] = "Leave request updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update leave request: " . $mysqli->error;
    }
    
    header("Location: ../leave_requests.php?success=updated");
    exit();
}
?>