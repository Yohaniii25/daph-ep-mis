<?php
session_start();
require_once '../../../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../vaccination_targets.php?status=error&msg=Unauthorized");
    exit();
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');

if ($id <= 0) {
    $_SESSION['msg'] = "Invalid staff id.";
    $_SESSION['msg_type'] = "danger";
    header("Location: ../vaccination_targets.php?year=" . $year);
    exit();
}

$del = $mysqli->prepare("DELETE FROM casual_vaccinator_deployments WHERE id = ?");
if ($del) {
    $del->bind_param("i", $id);
    if ($del->execute()) {
        $_SESSION['msg'] = "Vaccinator record deleted.";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['msg'] = "Database error: " . htmlspecialchars($del->error);
        $_SESSION['msg_type'] = "danger";
    }
    $del->close();
} else {
    $_SESSION['msg'] = "Statement prep error: " . htmlspecialchars($mysqli->error);
    $_SESSION['msg_type'] = "danger";
}

header("Location: ../vaccination_targets.php?year=" . $year);
$mysqli->close();
exit();
