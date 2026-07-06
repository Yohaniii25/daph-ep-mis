<?php
session_start();
require_once '../../../../config/db_connect.php';

// 1. Authorization Guard Block
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['role']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../production_activities.php?status=error&msg=Unauthorized");
    exit();
}

// 2. Extract Data Values
$range_id              = intval($_POST['range_id']);
$year                  = intval($_POST['year']);
$activity_name         = trim($_POST['activity_name']);
$animal_category       = $_POST['animal_category'] ?? null;
$animal_category_other = isset($_POST['animal_category_other']) ? trim($_POST['animal_category_other']) : null;
$target_quantity       = intval($_POST['target_quantity']);
$achieved_quantity     = intval($_POST['achieved_quantity']);

// 3. Fallback check for missing critical parameters
if (empty($activity_name) || empty($range_id) || empty($year)) {
    $_SESSION['msg'] = "Critical entry fields missing. Please verify activity name elements.";
    $_SESSION['msg_type'] = "danger";
    header("Location: ../production_activities.php?year=" . $year);
    exit();
}

// 4. Safe Database Injection Execution using a prepared statement
$stmt = $mysqli->prepare("INSERT INTO production_activity_targets (year, range_id, activity_name, animal_category, animal_category_other, target_quantity, achieved_quantity) VALUES (?, ?, ?, ?, ?, ?, ?)");

if ($stmt) {
    $stmt->bind_param("iisssii", $year, $range_id, $activity_name, $animal_category, $animal_category_other, $target_quantity, $achieved_quantity);
    
    if ($stmt->execute()) {
        $_SESSION['msg'] = "Production target log logged securely.";
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
header("Location: ../production_activities.php?year=" . $year);
$mysqli->close();
exit();