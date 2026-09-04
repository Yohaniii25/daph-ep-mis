<?php
/**
 * pages/modules/pd/processors/update_user_role.php
 * Provincial Director Module: Update Officer Role/Designation & Dispatch Automated Assignment Notification
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../../config/db_connect.php';
require_once '../../../../includes/notification_helper.php';

$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['ajax']);

// Authorization: Provincial Director, Administrator, HQ Deputy Directors
$allowed_roles = ['provincial_director', 'deputy_director_hq_1', 'deputy_director_hq_2', 'administrator'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
        exit();
    }
    header("Location: ../../../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id        = !empty($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $role           = trim($_POST['role'] ?? '');
    $designation    = trim($_POST['designation'] ?? '');
    $full_name      = trim($_POST['full_name'] ?? '');
    $service_number = trim($_POST['service_number'] ?? '');

    $district_id    = !empty($_POST['district_id']) ? intval($_POST['district_id']) : null;
    $range_id       = !empty($_POST['range_id']) ? intval($_POST['range_id']) : null;
    $farm_id        = !empty($_POST['farm_id']) ? intval($_POST['farm_id']) : null;
    $training_center_id = !empty($_POST['training_center_id']) ? intval($_POST['training_center_id']) : null;

    if ($user_id <= 0 || empty($role)) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid user ID or empty role specified.']);
            exit();
        }
        $_SESSION['msg'] = "Error: Invalid user or role specified.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../employee_managment.php");
        exit();
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

    // Check existing officer
    $check_stmt = $mysqli->prepare("SELECT id, full_name, username, role, designation FROM users WHERE id = ? LIMIT 1");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $existing = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    if (!$existing) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Officer record not found in system database.']);
            exit();
        }
        $_SESSION['msg'] = "Error: Officer not found.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../employee_managment.php");
        exit();
    }

    // Update users record
    $update_stmt = $mysqli->prepare("
        UPDATE users 
        SET 
            role = ?,
            designation = ?,
            full_name = ?,
            service_number = ?,
            district_id = ?,
            range_id = ?,
            farm_id = ?,
            training_center_id = ?,
            district = ?,
            appointment_date_current_position = CURDATE()
        WHERE id = ?
    ");

    if ($update_stmt) {
        $update_stmt->bind_param(
            "ssssiiiisi",
            $role,
            $designation,
            $full_name,
            $service_number,
            $district_id,
            $range_id,
            $farm_id,
            $training_center_id,
            $district_enum,
            $user_id
        );

        if ($update_stmt->execute()) {
            // Determine Position/Role Title for notification
            $role_title = !empty($designation) ? $designation : $role;

            // Trigger Requirement 4: Automated Direct Notification to the assigned user
            // Message format strictly: "You are assigned as the [Insert Position/Role Name]"
            $notif_res = notify_assigned_officer($mysqli, $user_id, $role_title, 'dashboard.php');

            // Also dispatch oversight notification to executives
            create_officer_notification(
                $mysqli, 
                'Role Updated', 
                $full_name, 
                $service_number, 
                $range_id, 
                'pages/modules/pd/employee_managment.php'
            );

            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Officer role successfully assigned and updated in live system.',
                    'notification_msg' => $notif_res['message'] ?? "You are assigned as the {$role_title}",
                    'email_sent' => $notif_res['email_sent'] ?? false
                ]);
                exit();
            }

            $_SESSION['msg'] = "Officer credentials updated. Notification sent: \"" . htmlspecialchars($notif_res['message']) . "\".";
            $_SESSION['msg_type'] = "success";
        } else {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Database update error: ' . $update_stmt->error]);
                exit();
            }
            $_SESSION['msg'] = "Database error: " . $update_stmt->error;
            $_SESSION['msg_type'] = "danger";
        }
        $update_stmt->close();
    } else {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Statement preparation error: ' . $mysqli->error]);
            exit();
        }
        $_SESSION['msg'] = "Database error: " . $mysqli->error;
        $_SESSION['msg_type'] = "danger";
    }

    header("Location: ../employee_managment.php");
    exit();
}
