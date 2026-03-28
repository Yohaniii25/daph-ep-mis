<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/constants.php';
require_once './audit_helper.php';

// Failed 5 login attempts check
if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
if ($_SESSION['login_attempts'] >= 5) {
    $_SESSION['login_error'] = "Too many attempts. Wait 15 minutes.";
    header("Location: ../index.php");
    exit();
}

$login_id = trim($_POST['login_id'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($login_id) || empty($password)) {
    $_SESSION['login_error'] = "Enter username/email and password";
    header("Location: ../index.php");
    exit();
}

// Detect email or username
$field = filter_var($login_id, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

$stmt = $mysqli->prepare("
    SELECT id, username, email, password, full_name, role, range_id, district_id 
    FROM users 
    WHERE $field = ? AND is_active = 1 
    LIMIT 1
");

if (!$stmt) {
    die("SQL Error: " . $mysqli->error);
}

$stmt->bind_param("s", $login_id);
$stmt->execute();
$result = $stmt->get_result();    

if ($result->num_rows === 0) {
    $_SESSION['login_attempts']++;
    $_SESSION['login_error'] = "Invalid login credentials";
    header("Location: ../index.php");
    exit();
}

$user = $result->fetch_assoc();

// ==================== PASSWORD CHECK ====================
if (password_verify($password, $user['password'])) {
    // ==================== SUCCESS ====================
    $_SESSION['user_id']     = $user['id'];
    $_SESSION['username']    = $user['username'];
    $_SESSION['full_name']   = $user['full_name'];
    $_SESSION['role']        = $user['role'];
    $_SESSION['range_id']    = $user['range_id'];
    $_SESSION['district_id'] = $user['district_id'];
    $_SESSION['logged_in']   = true;

    // Update last login time
    $mysqli->query("UPDATE users SET last_login = NOW() WHERE id = " . (int)$user['id']);

    // Audit Log
    logActivity($mysqli, $user['id'], 'LOGIN', 'users', $user['id'], null, null, "User logged in via Web");

    unset($_SESSION['login_attempts']);
    unset($_SESSION['login_error']);

    header("Location: ../dashboard.php");
    exit();

} else {
    // Wrong password
    $_SESSION['login_attempts']++;
    $_SESSION['login_error'] = "Invalid login credentials";
    header("Location: ../index.php");
    exit();
}
?>