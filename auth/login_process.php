<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db_connect.php';
require_once '../config/constants.php';
require_once './audit_helper.php';

if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
if ($_SESSION['login_attempts'] >= 5) {
    $_SESSION['login_error'] = "Too many attempts. Wait 15 minutes.";
    header("Location: ../index.php");
    exit();
}

$login_id      = trim($_POST['login_id'] ?? '');
$password      = $_POST['password'] ?? '';
$user_category = trim($_POST['user_category'] ?? '');

$district_id        = isset($_POST['district_id']) ? intval($_POST['district_id']) : null;
$range_id           = isset($_POST['range_id']) ? intval($_POST['range_id']) : null;
$training_center_id = isset($_POST['training_center_id']) ? intval($_POST['training_center_id']) : null;
$farm_id            = isset($_POST['farm_id']) ? intval($_POST['farm_id']) : null;

// Validate essential inputs
if (empty($login_id) || empty($password) || empty($user_category)) {
    $_SESSION['login_error'] = "Please fill in all identity credentials and parameters.";
    header("Location: ../index.php");
    exit();
}


$category_to_role_map = [
    'provincial_director'            => 'provincial_director',
    'additional_provincial_director' => 'provincial_director',
    'subject_matter_specialist'      => 'sms',
    'deputy_director_hq_1'           => 'provincial_director',
    'deputy_director_hq_2'           => 'provincial_director',
    'deputy_director_district'       => 'district_dd',
    'range_veterinary_officer'       => 'veterinary_surgeon',
    'training_centers'               => 'training_officer',
    'regional_farms'                 => 'farms_dd'
];

$expected_db_role = $category_to_role_map[$user_category] ?? $user_category;

$field = filter_var($login_id, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

$stmt = $mysqli->prepare("
    SELECT id, username, email, password, full_name, role, range_id, district_id, farm_id 
    FROM users 
    WHERE $field = ? AND is_active = 1 
    LIMIT 1
");

if (!$stmt) {
    die("SQL Structural Error: " . $mysqli->error);
}

$stmt->bind_param("s", $login_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['login_attempts']++;
    $_SESSION['login_error'] = "Invalid verification credentials.";
    header("Location: ../index.php");
    exit();
}

$user = $result->fetch_assoc();

if ($user['role'] !== $expected_db_role) {
    $_SESSION['login_attempts']++;
    $_SESSION['login_error'] = "Authorization Role mismatch for this account footprint.";
    header("Location: ../index.php");
    exit();
}

// Validate matching districts for specialized district fields
if (in_array($user_category, ['deputy_director_district', 'range_veterinary_officer'])) {
    if ($user['district_id'] !== null && intval($user['district_id']) !== $district_id) {
        $_SESSION['login_error'] = "Access denied: Account assigned to a different district region.";
        header("Location: ../index.php");
        exit();
    }
}

// Validate matching range layout metrics for Range Officers
if ($user_category === 'range_veterinary_officer') {
    if ($user['range_id'] !== null && intval($user['range_id']) !== $range_id) {
        $_SESSION['login_error'] = "Access denied: Account structural Range assignment error.";
        header("Location: ../index.php");
        exit();
    }
}


if (password_verify($password, $user['password'])) {

    $_SESSION['user_id']       = $user['id'];
    $_SESSION['username']      = $user['username'];
    $_SESSION['full_name']     = $user['full_name'];
    $_SESSION['role']          = $user['role'];
    $_SESSION['range_id']      = $user['range_id'];
    $_SESSION['district_id']   = $user['district_id'];
    $_SESSION['user_category'] = $user_category;

    if ($training_center_id) $_SESSION['training_center_id'] = $training_center_id;

    // Force farm_id from database if role is farms_dd; otherwise fallback to post input
    if ($user['role'] === 'farms_dd') {
        $_SESSION['farm_id'] = !is_null($user['farm_id']) ? intval($user['farm_id']) : null;
    } elseif ($farm_id) {
        $_SESSION['farm_id'] = $farm_id;
    }

    $_SESSION['logged_in']     = true;

    // Update login timestamp counter
    $mysqli->query("UPDATE users SET last_login = NOW() WHERE id = " . (int)$user['id']);

    // Log tracking operational footprint
    logActivity($mysqli, $user['id'], 'LOGIN', 'users', $user['id'], null, null, "User logged in with context: " . $user_category);

    unset($_SESSION['login_attempts']);
    unset($_SESSION['login_error']);

    header("Location: ../dashboard.php");
    exit();
} else {
    $_SESSION['login_attempts']++;
    $_SESSION['login_error'] = "Invalid verification credentials.";
    header("Location: ../index.php");
    exit();
}
