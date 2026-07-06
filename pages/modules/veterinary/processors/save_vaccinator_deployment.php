<?php
session_start();
require_once '../../../../config/db_connect.php';

// 1. Session Validation Guard
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['role']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../vaccination_targets.php?status=error&msg=Unauthorized");
    exit();
}

// 2. Safely Extract Inputs
$full_name = trim($_POST['full_name'] ?? '');
$nic_no    = trim($_POST['nic_no'] ?? '');
$year      = isset($_POST['year']) ? intval($_POST['year']) : 2026;
$range_id  = intval($_SESSION['range_id']);
$edit_id   = isset($_POST['id']) ? intval($_POST['id']) : 0;

// 3. Clean Validation Rules (Checking ONLY what your table requires)
if (empty($full_name) || empty($nic_no)) {
    $_SESSION['msg'] = "Please provide both Full Name and NIC Number.";
    $_SESSION['msg_type'] = "danger";
    header("Location: ../vaccination_targets.php?year=" . $year);
    exit();
}

// 4. Persistence Query Logic
if ($edit_id > 0) {
    // Update existing record using exactly 3 inputs bound cleanly
    $ust = $mysqli->prepare("UPDATE casual_vaccinator_deployments SET full_name = ?, nic_no = ? WHERE id = ?");
    if ($ust) {
        $ust->bind_param("ssi", $full_name, $nic_no, $edit_id);
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
    // Insert a new record into your exact 3-column layout structure plus range/year
    $stmt = $mysqli->prepare("INSERT INTO casual_vaccinator_deployments (full_name, nic_no, range_id, year) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssii", $full_name, $nic_no, $range_id, $year);
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

// 5. Clean Redirect keeping the view context intact
header("Location: ../vaccination_targets.php?year=" . $year);
$mysqli->close();
exit();
