<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db_connect.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
        exit();
    }
    header("Location: ../index.php");
    exit();
}

$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
           (isset($_POST['ajax']) && $_POST['ajax'] === '1');

$user_id          = $_SESSION['user_id'];
$current_password = $_POST['current_password'] ?? '';
$new_password     = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validation
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    $msg = "Please complete all password fields.";
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit();
    }
    $_SESSION['error'] = $msg;
    header("Location: ../pages/profile.php");
    exit();
}

if ($new_password !== $confirm_password) {
    $msg = "New password and confirmation password do not match.";
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit();
    }
    $_SESSION['error'] = $msg;
    header("Location: ../pages/profile.php");
    exit();
}

if (strlen($new_password) < 6) {
    $msg = "New password must be at least 6 characters long.";
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit();
    }
    $_SESSION['error'] = $msg;
    header("Location: ../pages/profile.php");
    exit();
}

// Fetch user's current password hash
$stmt = $mysqli->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
if (!$stmt) {
    $msg = "Database query error.";
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit();
    }
    $_SESSION['error'] = $msg;
    header("Location: ../pages/profile.php");
    exit();
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    $msg = "User record not found.";
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit();
    }
    $_SESSION['error'] = $msg;
    header("Location: ../pages/profile.php");
    exit();
}

$user = $res->fetch_assoc();

// Verify current password
if (!password_verify($current_password, $user['password'])) {
    $msg = "Current password is incorrect.";
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit();
    }
    $_SESSION['error'] = $msg;
    header("Location: ../pages/profile.php");
    exit();
}

// Check if new password is same as current password
if (password_verify($new_password, $user['password'])) {
    $msg = "New password cannot be the same as your current password.";
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit();
    }
    $_SESSION['error'] = $msg;
    header("Location: ../pages/profile.php");
    exit();
}

// Hash new password and update database
$new_hash = password_hash($new_password, PASSWORD_DEFAULT);
$update_stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
if (!$update_stmt) {
    $msg = "Failed to prepare update query.";
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit();
    }
    $_SESSION['error'] = $msg;
    header("Location: ../pages/profile.php");
    exit();
}

$update_stmt->bind_param("si", $new_hash, $user_id);

if ($update_stmt->execute()) {
    // Log activity if audit helper is available
    if (file_exists(__DIR__ . '/audit_helper.php')) {
        require_once __DIR__ . '/audit_helper.php';
        if (function_exists('logActivity')) {
            logActivity($mysqli, $user_id, 'CHANGE_PASSWORD', 'users', $user_id, null, null, "User updated account password");
        }
    }
    
    $success_msg = "Your password has been changed successfully!";
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => $success_msg]);
        exit();
    }
    $_SESSION['success'] = $success_msg;
    header("Location: ../pages/profile.php");
    exit();
} else {
    $msg = "Failed to update password: " . $mysqli->error;
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit();
    }
    $_SESSION['error'] = $msg;
    header("Location: ../pages/profile.php");
    exit();
}
