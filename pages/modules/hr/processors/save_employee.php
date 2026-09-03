<?php
session_start();
require_once '../../../../config/db_connect.php';
require_once '../../../../includes/notification_helper.php';

if (isset($_POST['save_employee'])) {

    $unit_id        = !empty($_POST['unit_id']) ? intval($_POST['unit_id']) : null;
    $range_id       = !empty($_POST['range_id']) ? intval($_POST['range_id']) : null;
    $officer_name   = trim($_POST['officer_name'] ?? '');
    $designation    = trim($_POST['designation'] ?? '');
    $emp_id         = trim($_POST['emp_id'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $date_of_birth  = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
    $registered_date= !empty($_POST['registered_date']) ? $_POST['registered_date'] : date('Y-m-d');
    $email          = trim($_POST['email'] ?? '');
    $status         = 'Active';

    $sql = "INSERT INTO office_details 
            (unit_id, range_id, officer_name, designation, emp_id, contact_number, date_of_birth, registered_date, email, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $mysqli->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("iissssssss", 
            $unit_id, 
            $range_id, 
            $officer_name, 
            $designation, 
            $emp_id, 
            $contact_number, 
            $date_of_birth,
            $registered_date, 
            $email, 
            $status
        );

        if ($stmt->execute()) {
            $_SESSION['msg'] = "Officer registered successfully under the selected unit!";
            $_SESSION['msg_type'] = "success";

            // Trigger automated notification with dynamic jurisdiction routing
            create_officer_notification($mysqli, 'New Officer Added', $officer_name, $emp_id, $range_id, 'pages/modules/hr/employee_managment.php');
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