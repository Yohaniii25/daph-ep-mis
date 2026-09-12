<?php
/**
 * pages/modules/hr/processors/save_employee.php
 * Administrator & Provincial HR Global Employee Onboarding & Unit Assignment
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../../config/db_connect.php';
require_once '../../../../includes/notification_helper.php';

// Authorization: Administrator, Provincial Director, HQ Deputy Directors
$allowed_roles = ['administrator', 'provincial_director', 'deputy_director_hq_1', 'deputy_director_hq_2'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    header("Location: ../../../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_employee'])) {
    $assigned_unit  = trim($_POST['assigned_unit'] ?? '');
    $officer_name   = trim($_POST['officer_name'] ?? '');
    $service_number = trim($_POST['service_number'] ?? ($_POST['emp_id'] ?? ''));
    $emp_id         = $service_number;
    $designation    = trim($_POST['designation'] ?? '');
    $user_role      = trim($_POST['user_role'] ?? 'employee');
    $service_cat    = trim($_POST['service_category'] ?? '');
    $employment_type = trim($_POST['employment_type'] ?? 'permanent');
    if (!in_array($employment_type, ['permanent', 'temporary'])) {
        $employment_type = 'permanent';
    }

    // Capture and standardize Current Station (Required)
    $current_station = trim($_POST['current_station'] ?? '');
    if (empty($current_station) && !empty($assigned_unit)) {
        $current_station = $assigned_unit;
    }

    // Conditional Employment Status & Attachment Reason (Applicable when Employment Type is permanent)
    $employment_status = null;
    $attachment_reason = null;
    if ($employment_type === 'permanent') {
        $employment_status = trim($_POST['employment_status'] ?? 'Permanent');
        if (!in_array($employment_status, ['Permanent', 'Attachment', 'Temporary Attachment'])) {
            $employment_status = 'Permanent';
        }
        if ($employment_status === 'Attachment' || $employment_status === 'Temporary Attachment') {
            $employment_status = 'Attachment';
            $attachment_reason = trim($_POST['attachment_reason'] ?? '');
            if (empty($attachment_reason)) {
                $_SESSION['msg'] = "Error: Reason for Attachment is required when Employment Status is set to Attachment.";
                $_SESSION['msg_type'] = "danger";
                header("Location: ../employee_managment.php");
                exit();
            }
        }
    }

    $email          = trim($_POST['email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');

    $dob            = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
    $reg_date       = !empty($_POST['registered_date']) ? $_POST['registered_date'] : date('Y-m-d');
    $app_date       = !empty($_POST['appointment_date']) ? $_POST['appointment_date'] : date('Y-m-d');
    $app_current    = !empty($_POST['appointment_date_current_position']) ? $_POST['appointment_date_current_position'] : date('Y-m-d');
    $pos_location   = !empty($_POST['position_to_current_location']) ? $_POST['position_to_current_location'] : date('Y-m-d');

    $district_id    = !empty($_POST['district_id']) ? intval($_POST['district_id']) : null;
    $range_id       = !empty($_POST['range_id']) ? intval($_POST['range_id']) : null;
    $farm_id        = !empty($_POST['farm_id']) ? intval($_POST['farm_id']) : null;
    $training_center_id = !empty($_POST['training_center_id']) ? intval($_POST['training_center_id']) : null;
    $unit_type      = trim($_POST['unit_type'] ?? '');
    $unit_type_id   = !empty($_POST['unit_type_id']) ? intval($_POST['unit_type_id']) : null;

    // Fallback resolvers based on assigned_unit text if hidden IDs are missing
    if (empty($range_id) && stripos($assigned_unit, 'Range Office - ') === 0) {
        $r_name = trim(preg_replace('/\s*\(.*?\)\s*/', '', substr($assigned_unit, strlen('Range Office - '))));
        $r_res = $mysqli->query("SELECT id, district_id FROM veterinary_ranges WHERE name LIKE '%" . $mysqli->real_escape_string($r_name) . "%' LIMIT 1");
        if ($r_res && $r_row = $r_res->fetch_assoc()) {
            $range_id = intval($r_row['id']);
            $district_id = intval($r_row['district_id']);
        }
    } elseif (empty($district_id) && stripos($assigned_unit, 'District Office - ') === 0) {
        $d_name = trim(substr($assigned_unit, strlen('District Office - ')));
        $d_res = $mysqli->query("SELECT id, name FROM districts WHERE name LIKE '%" . $mysqli->real_escape_string($d_name) . "%' LIMIT 1");
        if ($d_res && $d_row = $d_res->fetch_assoc()) {
            $district_id = intval($d_row['id']);
        }
    } elseif (empty($farm_id) && stripos($assigned_unit, 'Regional Farm - ') === 0) {
        $f_name = trim(substr($assigned_unit, strlen('Regional Farm - ')));
        $f_res = $mysqli->query("SELECT id FROM regional_farms WHERE farm_name LIKE '%" . $mysqli->real_escape_string($f_name) . "%' LIMIT 1");
        if ($f_res && $f_row = $f_res->fetch_assoc()) {
            $farm_id = intval($f_row['id']);
        }
    } elseif (empty($training_center_id) && stripos($assigned_unit, 'Training Center - ') === 0) {
        $tc_name = trim(preg_replace('/\s*\(.*?\)\s*/', '', substr($assigned_unit, strlen('Training Center - '))));
        $tc_res = $mysqli->query("SELECT id FROM training_centers WHERE center_name LIKE '%" . $mysqli->real_escape_string($tc_name) . "%' LIMIT 1");
        if ($tc_res && $tc_row = $tc_res->fetch_assoc()) {
            $training_center_id = intval($tc_row['id']);
        }
    }

    // Resolve district ENUM value
    $district_enum = 'Provincial';
    if ($district_id === 1) {
        $district_enum = 'Amparai';
    } elseif ($district_id === 2) {
        $district_enum = 'Batticaloa';
    } elseif ($district_id === 3) {
        $district_enum = 'Trincomalee';
    }

    // 1. Validation (Requiring Officer Name, Email, User Role, and Current Station)
    if (empty($officer_name) || empty($email) || empty($user_role) || empty($current_station)) {
        $_SESSION['msg'] = "Error: Officer name, email, user role, and Current Station are mandatory.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../employee_managment.php");
        exit();
    }

    // 2. Email uniqueness check
    $check_email = $mysqli->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    if ($check_email) {
        $check_email->bind_param("s", $email);
        $check_email->execute();
        if ($check_email->get_result()->num_rows > 0) {
            $check_email->close();
            $_SESSION['msg'] = "Error: An account with email '{$email}' is already registered in the system.";
            $_SESSION['msg_type'] = "danger";
            header("Location: ../employee_managment.php");
            exit();
        }
        $check_email->close();
    }

    // 3. Generate unique username
    $base_username = !empty($email) ? strtolower(explode('@', $email)[0]) : strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $officer_name));
    if (empty($base_username)) $base_username = 'officer';
    $username = $base_username;
    $counter = 1;

    while (true) {
        $check_u = $mysqli->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        if ($check_u) {
            $check_u->bind_param("s", $username);
            $check_u->execute();
            $u_res = $check_u->get_result();
            if ($u_res->num_rows == 0) {
                $check_u->close();
                break;
            }
            $check_u->close();
            $username = $base_username . $counter;
            $counter++;
        } else {
            break;
        }
    }

    // 4. Default password hash (Daph1234)
    $default_password = password_hash("Daph1234", PASSWORD_BCRYPT);

    $insert_user = $mysqli->prepare("
        INSERT INTO users (
            username, password, email, phone, full_name, 
            emp_id, service_number, designation, role, service_category, 
            employment_type, employment_status, attachment_reason, district_id, range_id, farm_id, training_center_id, 
            date_of_birth, registered_date, appointment_date, 
            appointment_date_current_position, position_to_current_location, is_active, district, unit, current_station
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)
    ");

    if ($insert_user) {
        $insert_user->bind_param(
            "sssssssssssssiiiissssssss",
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
            $employment_type,
            $employment_status,
            $attachment_reason,
            $district_id,
            $range_id,
            $farm_id,
            $training_center_id,
            $dob,
            $reg_date,
            $app_date,
            $app_current,
            $pos_location,
            $district_enum,
            $assigned_unit,
            $current_station
        );

        if ($insert_user->execute()) {
            $new_user_id = $insert_user->insert_id;

            // Also keep office_details table synced for backward compatibility
            $unit_id = ($unit_type === 'core' && $unit_type_id) ? $unit_type_id : null;
            $ins_od = $mysqli->prepare("
                INSERT INTO office_details 
                (unit_id, range_id, officer_name, designation, emp_id, contact_number, date_of_birth, registered_date, email, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')
            ");
            if ($ins_od) {
                $ins_od->bind_param("iisssssss", $unit_id, $range_id, $officer_name, $designation, $service_number, $contact_number, $dob, $reg_date, $email);
                $ins_od->execute();
                $ins_od->close();
            }

            // Determine Position/Role Title for notification
            $role_title = !empty($designation) ? $designation : ucwords(str_replace('_', ' ', $user_role));

            // Trigger automated direct notification to the new officer
            $notif_res = notify_assigned_officer($mysqli, $new_user_id, $role_title, 'dashboard.php');

            // Dispatch general executive notification
            create_officer_notification(
                $mysqli, 
                'New Officer Added', 
                $officer_name, 
                $service_number, 
                $range_id, 
                'pages/modules/hr/employee_managment.php'
            );

            $_SESSION['msg'] = "Officer '{$officer_name}' was registered and assigned to [{$assigned_unit}] successfully. Notification dispatched: \"" . htmlspecialchars($notif_res['message'] ?? '') . "\".";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Database error inserting officer: " . $insert_user->error;
            $_SESSION['msg_type'] = "danger";
        }
        $insert_user->close();
    } else {
        $_SESSION['msg'] = "Database query preparation error: " . $mysqli->error;
        $_SESSION['msg_type'] = "danger";
    }

    header("Location: ../employee_managment.php");
    exit();
}

header("Location: ../employee_managment.php");
exit();