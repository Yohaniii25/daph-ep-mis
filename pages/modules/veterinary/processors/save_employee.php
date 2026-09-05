<?php

//debug code
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../../../../config/db_connect.php';
require_once '../../../../includes/notification_helper.php';

$vs_roles = ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon', 'provincial_director', 'district_dd', 'deputy_director_district'];
$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_POST['ajax']) && $_POST['ajax'] == 1);

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], $vs_roles)) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
        exit();
    }
    header("Location: ../../../../index.php");
    exit();
}

if (isset($_POST['save_employee']) || $is_ajax) {
    // Collect and sanitize form variables
    $service_number = trim($_POST['service_number'] ?? '');
    $officer_name   = trim($_POST['officer_name'] ?? '');
    $designation    = trim($_POST['designation'] ?? '');
    $user_role      = trim($_POST['user_role'] ?? 'employee');
    $service_cat    = trim($_POST['service_category'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $unit           = trim($_POST['unit'] ?? '');
    
    $dob            = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
    $app_date       = !empty($_POST['appointment_date']) ? $_POST['appointment_date'] : null;
    $app_current    = !empty($_POST['appointment_date_current_position']) ? $_POST['appointment_date_current_position'] : null;

    $district_id    = !empty($_POST['district_id']) ? intval($_POST['district_id']) : null;
    $range_id       = !empty($_POST['range_id']) ? intval($_POST['range_id']) : null;

    // Fallback to session variables if POST hidden fields were empty
    if (empty($district_id) && !empty($_SESSION['district_id'])) {
        $district_id = intval($_SESSION['district_id']);
    }
    if (empty($range_id) && !empty($_SESSION['range_id'])) {
        $range_id = intval($_SESSION['range_id']);
    }

    $district_enum = 'Provincial';
    if ($district_id === 1) {
        $district_enum = 'Amparai';
    } elseif ($district_id === 2) {
        $district_enum = 'Batticaloa';
    } elseif ($district_id === 3) {
        $district_enum = 'Trincomalee';
    }

    // 1. Check if email already exists
    if (!empty($email)) {
        $check_email = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
        if ($check_email) {
            $check_email->bind_param("s", $email);
            $check_email->execute();
            if ($check_email->get_result()->num_rows > 0) {
                $check_email->close();
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => "Error: An officer with email '" . htmlspecialchars($email) . "' already exists."]);
                    exit();
                }
                $_SESSION['msg'] = "Error: An officer with email '" . htmlspecialchars($email) . "' already exists.";
                $_SESSION['msg_type'] = "danger";
                header("Location: ../employee_managment.php");
                exit();
            }
            $check_email->close();
        }
    }

    // 2. Generate unique username from email
    $base_username = !empty($email) ? strtolower(explode('@', $email)[0]) : strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $officer_name));
    if (empty($base_username)) {
        $base_username = 'officer';
    }
    $username = $base_username;
    $counter = 1;

    while (true) {
        $check_user = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
        if ($check_user) {
            $check_user->bind_param("s", $username);
            $check_user->execute();
            $res = $check_user->get_result();
            if ($res->num_rows == 0) {
                $check_user->close();
                break;
            }
            $check_user->close();
            $username = $base_username . $counter;
            $counter++;
        } else {
            break;
        }
    }

    $default_password = password_hash("Daph1234", PASSWORD_BCRYPT);

    $insert_stmt = $mysqli->prepare("
        INSERT INTO users (
            username, password, email, phone, full_name, 
            emp_id, service_number, designation, role, service_category, 
            district_id, range_id, date_of_birth, registered_date, appointment_date, 
            appointment_date_current_position, is_active, district, unit
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, 1, ?, ?)
    ");

    if ($insert_stmt) {
        $insert_stmt->bind_param(
            "ssssssssssiisssss",
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
            $dob,
            $app_date,
            $app_current,
            $district_enum,
            $unit
        );

        if ($insert_stmt->execute()) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Officer record successfully registered.']);
                exit();
            }
            $_SESSION['msg'] = "Officer record successfully created under your Range profile.";
            $_SESSION['msg_type'] = "success";

            // Automated notification trigger
            create_officer_notification($mysqli, 'New Officer Added', $officer_name, $service_number, $range_id, 'pages/modules/veterinary/employee_managment.php');
        } else {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => "Database error: " . $insert_stmt->error]);
                exit();
            }
            $_SESSION['msg'] = "Database error: " . $insert_stmt->error;
            $_SESSION['msg_type'] = "danger";
        }
        $insert_stmt->close();
    } else {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => "Failed to construct application transaction statement: " . $mysqli->error]);
            exit();
        }
        $_SESSION['msg'] = "Failed to construct application transaction statement: " . $mysqli->error;
        $_SESSION['msg_type'] = "danger";
    }

    header("Location: ../employee_managment.php");
    exit();
}
