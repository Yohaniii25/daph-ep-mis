<?php
session_start();
require_once '../../../../config/db_connect.php';

$vs_roles = ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], $vs_roles)) {
    header("Location: ../../../../index.php");
    exit();
}

if (isset($_POST['update_employee'])) {
    $id             = intval($_POST['id'] ?? 0);
    $service_number = trim($_POST['service_number'] ?? '');
    $officer_name   = trim($_POST['officer_name'] ?? '');
    $designation    = trim($_POST['designation'] ?? '');
    $user_role      = trim($_POST['user_role'] ?? 'employee');
    $service_cat    = trim($_POST['service_category'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    
    $app_date       = !empty($_POST['appointment_date']) ? $_POST['appointment_date'] : null;
    $app_current    = !empty($_POST['appointment_date_current_position']) ? $_POST['appointment_date_current_position'] : null;

    if ($id <= 0) {
        $_SESSION['msg'] = "Invalid officer record identifier.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../employee_managment.php");
        exit();
    }

    // Check if email belongs to another user
    if (!empty($email)) {
        $check_email = $mysqli->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        if ($check_email) {
            $check_email->bind_param("si", $email, $id);
            $check_email->execute();
            if ($check_email->get_result()->num_rows > 0) {
                $check_email->close();
                $_SESSION['msg'] = "Error: Email '" . htmlspecialchars($email) . "' is already used by another officer.";
                $_SESSION['msg_type'] = "danger";
                header("Location: ../employee_managment.php");
                exit();
            }
            $check_email->close();
        }
    }

    $range_id    = $_SESSION['range_id'] ?? null;
    $district_id = $_SESSION['district_id'] ?? null;

    // Cross-check: ensure VS can only edit employees in their own district/range
    $update_stmt = $mysqli->prepare("
        UPDATE users SET 
            service_number = ?,
            emp_id = ?,
            full_name = ?,
            designation = ?,
            role = ?,
            service_category = ?,
            email = ?,
            phone = ?,
            appointment_date = ?,
            appointment_date_current_position = ?
        WHERE id = ? AND district_id = ? AND range_id = ?
    ");

    if ($update_stmt) {
        $update_stmt->bind_param(
            "ssssssssssiii",
            $service_number,
            $service_number,
            $officer_name,
            $designation,
            $user_role,
            $service_cat,
            $email,
            $contact_number,
            $app_date,
            $app_current,
            $id,
            $district_id,
            $range_id
        );

        if ($update_stmt->execute()) {
            $_SESSION['msg'] = "Officer details successfully updated.";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Database error: " . $update_stmt->error;
            $_SESSION['msg_type'] = "danger";
        }
        $update_stmt->close();
    } else {
        $_SESSION['msg'] = "Failed to prepare update statement: " . $mysqli->error;
        $_SESSION['msg_type'] = "danger";
    }

    header("Location: ../employee_managment.php");
    exit();
}
