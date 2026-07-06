<?php
session_start();
require_once '../../../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../vaccination_targets.php?status=error&msg=Unauthorized");
    exit();
}

 $vax_target_id = intval($_POST['vaccination_target_id']);
 $full_name     = mysqli_real_escape_string($mysqli, $_POST['full_name']);
 $nic_no        = mysqli_real_escape_string($mysqli, $_POST['nic_no']);
 $year          = intval($_POST['year']);
 $edit_id       = isset($_POST['id']) ? intval($_POST['id']) : 0;

if (empty($vax_target_id) || empty($full_name) || empty($nic_no)) {
    $_SESSION['msg'] = "Please provide both Full Name and NIC Number.";
    $_SESSION['msg_type'] = "danger";
    header("Location: ../vaccination_targets.php?year=" . $year);
    exit();
}

if ($edit_id > 0) {
    // Update existing
    $ust = $mysqli->prepare("UPDATE casual_vaccinator_deployments SET full_name = ?, nic_no = ? WHERE id = ? AND vaccination_target_id = ?");
    if ($ust) {
        $ust->bind_param("ssii", $full_name, $nic_no, $edit_id, $vax_target_id);
        if ($ust->execute()) {
            $_SESSION['msg'] = "Vaccinator updated successfully.";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Database error: " . htmlspecialchars($ust->error);
            $_SESSION['msg_type'] = "danger";
        }
        $ust->close();
    } else {
        $_SESSION['msg'] = "Statement prep error: " . htmlspecialchars($mysqli->error);
        $_SESSION['msg_type'] = "danger";
    }
} else {
    // Insert new
    $stmt = $mysqli->prepare("INSERT INTO casual_vaccinator_deployments (vaccination_target_id, full_name, nic_no) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iss", $vax_target_id, $full_name, $nic_no);
        if ($stmt->execute()) {
            $_SESSION['msg'] = "Vaccinator " . htmlspecialchars($full_name) . " deployed successfully.";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Database error: " . htmlspecialchars($stmt->error);
            $_SESSION['msg_type'] = "danger";
        }
        $stmt->close();
    } else {
        $_SESSION['msg'] = "Statement prep error: " . htmlspecialchars($mysqli->error);
        $_SESSION['msg_type'] = "danger";
    }
}

header("Location: ../vaccination_targets.php?year=" . $year);
$mysqli->close();
exit();
