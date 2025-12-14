<?php
session_start();
require_once '../config/db_connect.php';
require_once '../config/constants.php';

// failed 5 login attempts check
if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
if ($_SESSION['login_attempts'] >= 5) {
    $_SESSION['login_error'] = "Too many attempts. Wait 15 minutes.";
    header("Location: ../index.php"); exit();
}

$login_id = trim($_POST['login_id'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($login_id) || empty($password)) {
    $_SESSION['login_error'] = "Enter username/email and password";
    header("Location: ../index.php"); exit();
}

// Detect if input looks like email
$field = filter_var($login_id, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

$stmt = $mysqli->prepare("
    SELECT id, username, email, password, full_name, role, office_id, district, status 
    FROM users 
    WHERE $field = ? AND status = 'active' 
    LIMIT 1
");
$stmt->bind_param("s", $login_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['login_attempts']++;
    $_SESSION['login_error'] = "Invalid login credentials";
    header("Location: ../index.php"); exit();
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password'])) {
    $_SESSION['login_attempts']++;
    $_SESSION['login_error'] = "Invalid login credentials";
    header("Location: ../index.php"); exit();
}

// SUCCESS
$_SESSION['user_id']    = $user['id'];
$_SESSION['username']   = $user['username'];
$_SESSION['email']      = $user['email'];
$_SESSION['full_name']  = $user['full_name'];
$_SESSION['role']       = $user['role'];
$_SESSION['office_id']  = $user['office_id'];
$_SESSION['district']    = $user['district'];
$_SESSION['logged_in']  = true;

// Update last login
$mysqli->query("UPDATE users SET last_login = NOW() WHERE id = {$user['id']}");

unset($_SESSION['login_attempts']);
header("Location: ../dashboard.php");
exit();
?>