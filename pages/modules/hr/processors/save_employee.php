<?php
session_start();
require_once '../../../../config/db_connect.php';

if (isset($_POST['save_employee'])) {

    $range_id       = $_POST['range_id'];
    $officer_name   = $mysqli->real_escape_string($_POST['officer_name']);
    $designation    = $mysqli->real_escape_string($_POST['designation']);
    $emp_id         = $mysqli->real_escape_string($_POST['emp_id']);
    $contact_number = $mysqli->real_escape_string($_POST['contact_number']);
    $registered_date= $_POST['registered_date'];
    $email          = $mysqli->real_escape_string($_POST['email']);
    $status         = 'Active';

    $stmt = $mysqli->prepare("INSERT INTO office_details (range_id, officer_name, designation, emp_id, contact_number, registered_date, email, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("isssssss", $range_id, $officer_name, $designation, $emp_id, $contact_number, $registered_date, $email, $status);

    if ($stmt->execute()) {
        $_SESSION['msg'] = "Officer registered successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['msg'] = "Error: " . $stmt->error;
        $_SESSION['msg_type'] = "danger";
    }

    $stmt->close();
    header("Location: ../employee_managment.php");
    exit();
}