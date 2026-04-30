<?php
session_start();
require_once '../../../../config/db_connect.php';

if (isset($_POST['submit_leave'])) {
    $user_id = $mysqli->real_escape_string($_POST['user_id']);
    $leave_type = $mysqli->real_escape_string($_POST['leave_type']);
    $start_date = $mysqli->real_escape_string($_POST['start_date']);
    $resume_date = $mysqli->real_escape_string($_POST['resume_date']);
    $no_of_days = $mysqli->real_escape_string($_POST['no_of_days']);
    $is_half_day = isset($_POST['is_half_day']) ? 1 : 0;
    $reason = $mysqli->real_escape_string($_POST['reason']);
    $acting_user_id = !empty($_POST['acting_user_id']) ? "'" . $mysqli->real_escape_string($_POST['acting_user_id']) . "'" : "NULL";
    $request_date = date('Y-m-d');

    $query = "INSERT INTO leave_requests (user_id, leave_type, request_date, start_date, resume_date, no_of_days, is_half_day, reason, acting_user_id, status) 
              VALUES ('$user_id', '$leave_type', '$request_date', '$start_date', '$resume_date', '$no_of_days', $is_half_day, '$reason', $acting_user_id, 'Pending')";

    if ($mysqli->query($query)) {
        $_SESSION['success'] = "Leave request submitted successfully.";
        header("Location: ../leave_requests.php?success=1");
    } else {
        $_SESSION['error'] = "Error: " . $mysqli->error;
        header("Location: ../leave_requests.php?status=error");
    }
    exit();
} else {
    header("Location: ../leave_requests.php");
    exit();
}
?>
