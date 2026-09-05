<?php
session_start();
require_once '../../../../config/db_connect.php';
require_once '../../../../includes/approval_helper.php';

$vs_roles = ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon', 'provincial_director', 'district_dd', 'deputy_director_district'];
$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_POST['ajax']) && $_POST['ajax'] == 1);

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], $vs_roles)) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    header("Location: ../../../../index.php");
    exit();
}

if (isset($_POST['update_employee']) || $is_ajax) {
    $id             = intval($_POST['id'] ?? 0);
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

    if ($id <= 0) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Invalid officer record identifier.']);
            exit();
        }
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
                if ($is_ajax) {
                    echo json_encode(['success' => false, 'message' => "Error: Email '" . htmlspecialchars($email) . "' is already used by another officer."]);
                    exit();
                }
                $_SESSION['msg'] = "Error: Email '" . htmlspecialchars($email) . "' is already used by another officer.";
                $_SESSION['msg_type'] = "danger";
                header("Location: ../employee_managment.php");
                exit();
            }
            $check_email->close();
        }
    }

    // Fetch existing live record snapshot
    $stmt_curr = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
    $stmt_curr->bind_param("i", $id);
    $stmt_curr->execute();
    $old_user = $stmt_curr->get_result()->fetch_assoc();
    $stmt_curr->close();

    if (!$old_user) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Record not found']);
            exit();
        }
        $_SESSION['msg'] = "Record not found";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../employee_managment.php");
        exit();
    }

    // Resolve unit fallback if not passed
    if ($unit === '' && isset($old_user['unit'])) {
        $unit = $old_user['unit'];
    }

    // Detect Inter-Departmental Transfer & Notify Provincial Director
    check_and_notify_unit_transfer(
        $mysqli, 
        $officer_name, 
        $old_user['unit'] ?? '', 
        $unit, 
        'pages/modules/pd/pending_approvals.php'
    );

    // Resolve district and range
    $district_id = !empty($old_user['district_id']) ? intval($old_user['district_id']) : intval($_SESSION['district_id'] ?? 0);
    $range_id    = !empty($old_user['range_id']) ? intval($old_user['range_id']) : ($_SESSION['range_id'] ?? null);

    // Jurisdiction check for District DD
    if (in_array($_SESSION['role'], ['district_dd', 'deputy_director_district'])) {
        $user_dist = intval($_SESSION['district_id'] ?? 0);
        if ($user_dist > 0 && $district_id > 0 && $district_id !== $user_dist) {
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized: Officer does not belong to your assigned district.']);
                exit();
            }
            $_SESSION['msg'] = "Unauthorized: Officer does not belong to your assigned district.";
            $_SESSION['msg_type'] = "danger";
            header("Location: ../employee_managment.php");
            exit();
        }
    }

    $new_user_data = [
        'service_number' => $service_number,
        'emp_id' => $service_number,
        'full_name' => $officer_name,
        'designation' => $designation,
        'role' => $user_role,
        'service_category' => $service_cat,
        'email' => $email,
        'phone' => $contact_number,
        'date_of_birth' => $dob,
        'appointment_date' => $app_date,
        'appointment_date_current_position' => $app_current,
        'unit' => $unit
    ];

    // Staging evaluation
    $staging_res = stage_or_apply_edit(
        $mysqli, 
        'hr', 
        'users', 
        $id, 
        $officer_name, 
        $old_user ?: [], 
        $new_user_data, 
        $district_id, 
        $range_id
    );

    if (!empty($staging_res['is_staged'])) {
        if ($is_ajax) {
            echo json_encode([
                'success' => true,
                'staged'  => true,
                'message' => 'Changes submitted successfully. Awaiting final approval from the Provincial Director.'
            ]);
            exit();
        }
        $_SESSION['staged_msg'] = "Changes submitted successfully. Awaiting final approval from the Provincial Director.";
        $_SESSION['msg'] = "Changes submitted successfully. Awaiting final approval from the Provincial Director.";
        $_SESSION['msg_type'] = "info";
        header("Location: ../employee_managment.php");
        exit();
    }

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
            date_of_birth = ?,
            appointment_date = ?,
            appointment_date_current_position = ?,
            unit = ?
        WHERE id = ? AND district_id = ?
    ");

    if ($update_stmt) {
        $update_stmt->bind_param(
            "ssssssssssssii",
            $service_number,
            $service_number,
            $officer_name,
            $designation,
            $user_role,
            $service_cat,
            $email,
            $contact_number,
            $dob,
            $app_date,
            $app_current,
            $unit,
            $id,
            $district_id
        );

        if ($update_stmt->execute()) {
            if ($is_ajax) {
                echo json_encode(['success' => true, 'message' => 'Officer details successfully updated.']);
                exit();
            }
            $_SESSION['msg'] = "Officer details successfully updated.";
            $_SESSION['msg_type'] = "success";
        } else {
            if ($is_ajax) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $update_stmt->error]);
                exit();
            }
            $_SESSION['msg'] = "Database error: " . $update_stmt->error;
            $_SESSION['msg_type'] = "danger";
        }
        $update_stmt->close();
    } else {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Failed to prepare update statement: ' . $mysqli->error]);
            exit();
        }
        $_SESSION['msg'] = "Failed to prepare update statement: " . $mysqli->error;
        $_SESSION['msg_type'] = "danger";
    }

    header("Location: ../employee_managment.php");
    exit();
}
