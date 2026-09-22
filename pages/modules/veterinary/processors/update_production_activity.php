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

$user_role      = $_SESSION['role'] ?? '';
$is_supervisory = in_array($user_role, ['deputy_director_hq_1', 'district_dd', 'deputy_director_district', 'provincial_director', 'admin', 'super_admin'], true);

// 2. Extract Data Values
$id                    = intval($_POST['id'] ?? 0);
$range_id              = intval($_POST['range_id'] ?? 0);
$year                  = intval($_POST['year'] ?? 2026);
$activity_name         = trim($_POST['activity_name'] ?? '');
$funding_source        = trim($_POST['funding_source'] ?? '');
$animal_category       = $_POST['animal_category'] ?? null;
$animal_category_other = isset($_POST['animal_category_other']) ? trim($_POST['animal_category_other']) : null;
$target_quantity       = max(0, intval($_POST['target_quantity'] ?? 0));
$achieved_quantity     = max(0, intval($_POST['achieved_quantity'] ?? 0));

// If funding_source is "Other" and specific other name provided
if ($funding_source === 'Other' && !empty($_POST['funding_source_other'])) {
    $funding_source = trim($_POST['funding_source_other']);
}

// Fallback to session range if non-supervisory
if (!$is_supervisory && !empty($_SESSION['range_id'])) {
    $range_id = intval($_SESSION['range_id']);
}

// 3. Validation for mandatory fields
if ($id <= 0 || empty($activity_name)) {
    $_SESSION['msg'] = "Critical entry fields missing. Please verify activity name.";
    $_SESSION['msg_type'] = "danger";
    header("Location: ../production_activities.php?year=" . $year . "&range_id=" . $range_id);
    exit();
}

if (empty($funding_source)) {
    $_SESSION['msg'] = "Validation Error: Funding Source is a mandatory field.";
    $_SESSION['msg_type'] = "danger";
    header("Location: ../production_activities.php?year=" . $year . "&range_id=" . $range_id);
    exit();
}

// Check animal_category 'Other'
if ($animal_category !== 'Other') {
    $animal_category_other = null;
}

// 4. Safe Database Update Execution using a prepared statement
if (!$is_supervisory && !empty($_SESSION['range_id'])) {
    // Restrict update to user's assigned range
    $stmt = $mysqli->prepare("
        UPDATE production_activity_targets 
        SET activity_name = ?, funding_source = ?, animal_category = ?, animal_category_other = ?, target_quantity = ?, achieved_quantity = ? 
        WHERE id = ? AND range_id = ?
    ");
    if ($stmt) {
        $stmt->bind_param(
            "ssssiiii",
            $activity_name,
            $funding_source,
            $animal_category,
            $animal_category_other,
            $target_quantity,
            $achieved_quantity,
            $id,
            $range_id
        );
    }
} else {
    // Supervisory / Admin role update
    $stmt = $mysqli->prepare("
        UPDATE production_activity_targets 
        SET activity_name = ?, funding_source = ?, animal_category = ?, animal_category_other = ?, target_quantity = ?, achieved_quantity = ?, range_id = ?
        WHERE id = ?
    ");
    if ($stmt) {
        $stmt->bind_param(
            "ssssiiii",
            $activity_name,
            $funding_source,
            $animal_category,
            $animal_category_other,
            $target_quantity,
            $achieved_quantity,
            $range_id,
            $id
        );
    }
}

if ($stmt) {
    if ($stmt->execute()) {
        $_SESSION['msg'] = "Production activity target updated successfully.";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['msg'] = "Database update error: " . htmlspecialchars($stmt->error);
        $_SESSION['msg_type'] = "danger";
    }
    $stmt->close();
} else {
    $_SESSION['msg'] = "Database engine initialization constraint error: " . htmlspecialchars($mysqli->error);
    $_SESSION['msg_type'] = "danger";
}

// 5. Clean Return Redirect
$redirect_url = "../production_activities.php?year=" . $year;
if ($range_id > 0) {
    $redirect_url .= "&range_id=" . $range_id;
}
header("Location: " . $redirect_url);
$mysqli->close();
exit();
