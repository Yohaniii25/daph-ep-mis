<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

// 1. Authorization Guard Block
$allowed_roles = [
    'veterinary_surgeon',
    'government_veterinary_surgeon',
    'additional_veterinary_surgeon',
    'deputy_director_hq_1',
    'district_dd',
    'deputy_director_district',
    'provincial_director',
    'admin',
    'super_admin'
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles, true)) {
    header("Location: ../production_activities.php?status=error&msg=Unauthorized");
    exit();
}

// 2. Extract Data Values
$range_id              = intval($_POST['range_id'] ?? 0);
$year                  = intval($_POST['year'] ?? 0);
$activity_name         = trim($_POST['activity_name'] ?? '');
$funding_source        = trim($_POST['funding_source'] ?? '');
$animal_category       = $_POST['animal_category'] ?? null;
$animal_category_other = isset($_POST['animal_category_other']) ? trim($_POST['animal_category_other']) : null;
$target_quantity       = intval($_POST['target_quantity'] ?? 0);
$achieved_quantity     = intval($_POST['achieved_quantity'] ?? 0);

// If funding_source is "Other" and specific other name provided
if ($funding_source === 'Other' && !empty($_POST['funding_source_other'])) {
    $funding_source = trim($_POST['funding_source_other']);
}

// 3. Validation for mandatory fields
if (empty($activity_name) || empty($range_id) || empty($year)) {
    $_SESSION['msg'] = "Critical entry fields missing. Please verify activity name elements.";
    $_SESSION['msg_type'] = "danger";
    header("Location: ../production_activities.php?year=" . $year . "&range_id=" . $range_id);
    exit();
}

if (empty($funding_source)) {
    $_SESSION['msg'] = "Validation Error: Funding Source is a mandatory field (e.g., PSDG, Line Ministry, NGOs).";
    $_SESSION['msg_type'] = "danger";
    header("Location: ../production_activities.php?year=" . $year . "&range_id=" . $range_id);
    exit();
}

// 4. Safe Database Injection Execution using a prepared statement
$stmt = $mysqli->prepare("
    INSERT INTO production_activity_targets 
    (year, range_id, activity_name, funding_source, animal_category, animal_category_other, target_quantity, achieved_quantity) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

if ($stmt) {
    $stmt->bind_param(
        "iissssii",
        $year,
        $range_id,
        $activity_name,
        $funding_source,
        $animal_category,
        $animal_category_other,
        $target_quantity,
        $achieved_quantity
    );
    
    if ($stmt->execute()) {
        $_SESSION['msg'] = "Production target activity registered successfully.";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['msg'] = "Database insertion error: " . htmlspecialchars($stmt->error);
        $_SESSION['msg_type'] = "danger";
    }
    $stmt->close();
} else {
    $_SESSION['msg'] = "Database engine initialization constraint error: " . htmlspecialchars($mysqli->error);
    $_SESSION['msg_type'] = "danger";
}

// 5. Clean Return Redirect
header("Location: ../production_activities.php?year=" . $year . "&range_id=" . $range_id);
$mysqli->close();
exit();