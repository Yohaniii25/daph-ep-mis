<?php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['veterinary_surgeon', 'sms'])) {
    die("Access denied");
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id           = intval($_POST['id'] ?? 0);
    $batch_number = trim($mysqli->real_escape_string($_POST['batch_number']));
    $is_active    = intval($_POST['is_active'] ?? 1);
    $remarks      = trim($mysqli->real_escape_string($_POST['remarks']));

    if ($action === 'create') {
        $stmt = $mysqli->prepare("INSERT INTO `vaccine_batches` (batch_number, is_active, remarks) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $batch_number, $is_active, $remarks);
        
        if ($stmt->execute()) {
            header("Location: ../batches.php?status=success&msg=Batch+Registered");
        } else {
            header("Location: ../batches.php?status=error&msg=Write+Failure");
        }
        $stmt->close();
        exit;
    } 
    
    elseif ($action === 'update' && $id > 0) {
        $stmt = $mysqli->prepare("UPDATE `vaccine_batches` SET batch_number = ?, is_active = ?, remarks = ? WHERE id = ?");
        $stmt->bind_param("sisi", $batch_number, $is_active, $remarks, $id);
        
        if ($stmt->execute()) {
            header("Location: ../batches.php?status=success&msg=Changes+Saved");
        } else {
            header("Location: ../batches.php?status=error&msg=Update+Failure");
        }
        $stmt->close();
        exit;
    }
} 

// Handle deletions via URL flags
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM `vaccine_batches` WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header("Location: ../batches.php?status=success&msg=Entry+Dropped");
        } else {
            header("Location: ../batches.php?status=error&msg=Delete+Failure");
        }
        $stmt->close();
        exit;
    }
}