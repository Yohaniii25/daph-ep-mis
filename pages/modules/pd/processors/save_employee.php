<?php
/**
 * pages/modules/pd/processors/save_employee.php
 * Provincial Director Global Employee Registration & Automated Direct Notification
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../../config/db_connect.php';
require_once '../../../../includes/notification_helper.php';

// Authorization: Provincial Director, Administrator, HQ Deputy Directors
$allowed_roles = ['provincial_director', 'deputy_director_hq_1', 'deputy_director_hq_2', 'administrator'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    header("Location: ../../../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_number = trim($_POST['service_number'] ?? '');
    $officer_name   = trim($_POST['officer_name'] ?? '');
    $designation    = trim($_POST['designation'] ?? '');
    $user_role      = trim($_POST['user_role'] ?? 'employee');
    $service_cat    = trim($_POST['service_category'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');

    $dob            = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
    $app_date       = !empty($_POST['appointment_date']) ? $_POST['appointment_date'] : null;
    $app_current    = !empty($_POST['appointment_date_current_position']) ? $_POST['appointment_date_current_position'] : null;

    $district_id    = !empty($_POST['district_id']) ? intval($_POST['district_id']) : null;
    $range_id       = !empty($_POST['range_id']) ? intval($_POST['range_id']) : null;
    $farm_id        = !empty($_POST['farm_id']) ? intval($_POST['farm_id']) : null;
    $training_center_id = !empty($_POST['training_center_id']) ? intval($_POST['training_center_id']) : null;

    // Resolve district ENUM value
    $district_enum = 'Provincial';
    if ($district_id === 1) {
        $district_enum = 'Amparai';
    } elseif ($district_id === 2) {
        $district_enum = 'Batticaloa';
    } elseif ($district_id === 3) {
        $district_enum = 'Trincomalee';
    }

    // 1. Validate required fields
    if (empty($officer_name) || empty($email) || empty($user_role)) {
        $_SESSION['msg'] = "Error: Officer name, user role, and email are strictly required.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../employee_managment.php");
        exit();
    }

    // 2. Check if email already exists
    $check_email = $mysqli->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    if ($check_email) {
        $check_email->bind_param("s", $email);
        $check_email->execute();
        if ($check_email->get_result()->num_rows > 0) {
            $check_email->close();
            $_SESSION['msg'] = "Error: An officer account with email '" . htmlspecialchars($email) . "' already exists.";
            $_SESSION['msg_type'] = "danger";
            header("Location: ../employee_managment.php");
            exit();
        }
        $check_email->close();
    }

    // 3. Generate unique username
    $base_username = !empty($email) ? strtolower(explode('@', $email)[0]) : strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $officer_name));
    if (empty($base_username)) {
        $base_username = 'officer';
    }
    $username = $base_username;
    $counter = 1;

    while (true) {
        $check_user = $mysqli->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
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

    // 4. Default password hash (Daph1234)
    $default_password = password_hash("Daph1234", PASSWORD_BCRYPT);

    $insert_stmt = $mysqli->prepare("
        INSERT INTO users (
            username, password, email, phone, full_name, 
            emp_id, service_number, designation, role, service_category, 
            district_id, range_id, farm_id, training_center_id, 
            date_of_birth, registered_date, appointment_date, 
            appointment_date_current_position, is_active, district
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, 1, ?)
    ");

    if ($insert_stmt) {
        $insert_stmt->bind_param(
            "ssssssssssiiiissss",
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
            $farm_id,
            $training_center_id,
            $dob,
            $app_date,
            $app_current,
            $district_enum
        );

        if ($insert_stmt->execute()) {
            $new_user_id = $insert_stmt->insert_id;

            // Determine Position/Role Title for notification
            $role_title = !empty($designation) ? $designation : $user_role;

            // Trigger Requirement 4: Automated Direct Notification to the assigned user
            // Message strictly format: "You are assigned as the [Insert Position/Role Name]"
            $notif_res = notify_assigned_officer($mysqli, $new_user_id, $role_title, 'dashboard.php');

            // Also dispatch oversight notification to executives
            create_officer_notification(
                $mysqli, 
                'New Officer Added', 
                $officer_name, 
                $service_number, 
                $range_id, 
                'pages/modules/pd/employee_managment.php'
            );

            $_SESSION['msg'] = "Officer '{$officer_name}' was registered successfully. Automated notification dispatched: \"" . htmlspecialchars($notif_res['message']) . "\".";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Database error inserting officer: " . $insert_stmt->error;
            $_SESSION['msg_type'] = "danger";
        }
        $insert_stmt->close();
    } else {
        $_SESSION['msg'] = "Database preparation error: " . $mysqli->error;
        $_SESSION['msg_type'] = "danger";
    }

    header("Location: ../employee_managment.php");
    exit();
}
