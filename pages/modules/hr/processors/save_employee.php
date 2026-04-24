<?php
session_start();
require_once '../../../../config/db_connect.php';

if (isset($_POST['save_employee'])) {

    $unit_id        = !empty($_POST['unit_id']) ? intval($_POST['unit_id']) : null;
    $range_id       = !empty($_POST['range_id']) ? intval($_POST['range_id']) : null;
    $officer_name   = $mysqli->real_escape_string($_POST['officer_name']);
    $designation    = $mysqli->real_escape_string($_POST['designation']);
    $emp_id         = $mysqli->real_escape_string($_POST['emp_id']);
    $contact_number = $mysqli->real_escape_string($_POST['contact_number']);
    $registered_date= !empty($_POST['registered_date']) ? $_POST['registered_date'] : date('Y-m-d');
    $email          = $mysqli->real_escape_string($_POST['email']);
    $status         = 'Active';


    $sql = "INSERT INTO office_details 
            (unit_id, range_id, officer_name, designation, emp_id, contact_number, registered_date, email, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $mysqli->prepare($sql);
    
    if ($stmt) {

        $stmt->bind_param("iisssssss", 
            $unit_id, 
            $range_id, 
            $officer_name, 
            $designation, 
            $emp_id, 
            $contact_number, 
            $registered_date, 
            $email, 
            $status
        );

        if ($stmt->execute()) {
            $_SESSION['msg'] = "Officer registered successfully under the selected unit!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Database Error: " . $stmt->error;
            $_SESSION['msg_type'] = "danger";
        }
        $stmt->close();
    } else {
        $_SESSION['msg'] = "System Error: Could not prepare the statement.";
        $_SESSION['msg_type'] = "danger";
    }

    header("Location: ../employee_managment.php");
    exit();
}