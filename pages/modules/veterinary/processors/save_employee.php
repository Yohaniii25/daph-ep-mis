<?php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

if (isset($_POST['save_employee'])) {
    // Collect and sanitize form variables
    $service_number = trim($_POST['service_number']);
    $officer_name   = trim($_POST['officer_name']);
    $designation    = trim($_POST['designation']);
    $user_role      = trim($_POST['user_role']);
    $service_cat    = trim($_POST['service_category']);
    $email          = trim($_POST['email']);
    $contact_number = trim($_POST['contact_number']);
    $app_date       = $_POST['appointment_date'];
    $app_current    = $_POST['appointment_date_current_position'];

    $district_id    = intval($_POST['district_id']);
    $range_id       = intval($_POST['range_id']);

    $username = strtolower(explode('@', $email)[0]);
    $default_password = password_hash("Daph1234", PASSWORD_BCRYPT); 

    $insert_stmt = $mysqli->prepare("
        INSERT INTO users (
            username, password, email, phone, full_name, 
            emp_id, service_number, designation, role, service_category, 
            district_id, range_id, registered_date, appointment_date, 
            appointment_date_current_position, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, 1)
    ");

    if ($insert_stmt) {
        // emp_id field defaults initially to match service_number structure
        $insert_stmt->bind_param(
            "ssssssssssiiss",
            $username,
            $default_password,
            $email,
            $contact_number,
            $officer_name,
            $service_number,
            $service_number,
            $designation,
            $user_role,
            $service_cat,
            $district_id,
            $range_id,
            $app_date,
            $app_current
        );

        if ($insert_stmt->execute()) {
            $_SESSION['msg_success'] = "Officer record successfully created under your Range profile.";
        } else {
            $_SESSION['msg_error'] = "Database execution error: Unable to create account.";
        }
        $insert_stmt->close();
    } else {
        $_SESSION['msg_error'] = "Failed to construct application transaction statement.";
    }

    header("Location: ../employee_managment.php");
    exit();
}
