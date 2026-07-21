<?php
// pages/modules/farm/processors/save_batch.php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batch_number = trim($mysqli->real_escape_string($_POST['batch_number']));
    $user_id = $_SESSION['user_id'] ?? 1;

    if (empty($batch_number)) {
        header("Location: ../parent_stock_operations.php?status=error&msg=Batch number cannot be empty.");
        exit();
    }

    $stmt = $mysqli->prepare("INSERT INTO vaccine_batches (batch_number, user_id) VALUES (?, ?)");
    $stmt->bind_param("si", $batch_number, $user_id);

    if ($stmt->execute()) {
        header("Location: ../parent_stock_operations.php?status=success&msg=Batch added successfully.");
    } else {
        header("Location: ../parent_stock_operations.php?status=error&msg=Failed to add batch: " . $mysqli->error);
    }
    $stmt->close();
    $mysqli->close();
} else {
    header("Location: ../parent_stock_operations.php");
}
