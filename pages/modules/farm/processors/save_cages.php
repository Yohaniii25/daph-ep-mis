<?php
session_start();
require_once '../../../../config/db_connect.php';

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    die("Unauthorized access");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'manage_cages') {
    
    // Sanitize inputs
    $flock_id = intval($_POST['flock_id']);
    $cage_labels = trim($mysqli->real_escape_string($_POST['cage_labels']));

    if (empty($flock_id) || empty($cage_labels)) {
        header("Location: ../parent_stock_operations.php?status=error&msg=All fields are required");
        exit();
    }

    // Update the parent_stock_flocks table
    $stmt = $mysqli->prepare("UPDATE parent_stock_flocks SET assigned_cages = ? WHERE id = ?");
    $stmt->bind_param("si", $cage_labels, $flock_id);

    if ($stmt->execute()) {
        header("Location: ../parent_stock_operations.php?status=success&msg=Cage assignments updated successfully");
    } else {
        header("Location: ../parent_stock_operations.php?status=error&msg=Database error: " . $mysqli->error);
    }

    $stmt->close();
    $mysqli->close();
} else {
    header("Location: ../parent_stock_operations.php");
    exit();
}