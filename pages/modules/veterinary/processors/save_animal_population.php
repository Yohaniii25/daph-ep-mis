<?php
session_start();
require_once '../../../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../vaccination_targets.php?status=error&msg=Unauthorized");
    exit();
}

$range_id    = intval($_SESSION['range_id']);
$year        = intval($_POST['year']);
$animal_type = mysqli_real_escape_string($mysqli, $_POST['animal_type']);
$quantity    = intval($_POST['quantity'] ?? 0);

if (empty($animal_type)) {
    $_SESSION['msg'] = "Please select a valid animal type.";
    $_SESSION['msg_type'] = "danger";
    header("Location: ../vaccination_targets.php?year=" . $year);
    exit();
}

// Check if record exists, insert or duplicate key update
$stmt = $mysqli->prepare("
    INSERT INTO animal_populations (range_id, year, animal_type, quantity) 
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)
");

if ($stmt) {
    $stmt->bind_param("iisi", $range_id, $year, $animal_type, $quantity);
    if ($stmt->execute()) {
        $_SESSION['msg'] = "Population count for " . htmlspecialchars($animal_type) . " updated successfully to " . number_format($quantity) . ".";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['msg'] = "Database error: " . htmlspecialchars($mysqli->error);
        $_SESSION['msg_type'] = "danger";
    }
    $stmt->close();
} else {
    $_SESSION['msg'] = "Statement prep error: " . htmlspecialchars($mysqli->error);
    $_SESSION['msg_type'] = "danger";
}

header("Location: ../vaccination_targets.php?year=" . $year);
$mysqli->close();
exit();
