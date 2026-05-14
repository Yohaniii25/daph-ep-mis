<?php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $batch_date = $mysqli->real_escape_string($_POST['batch_date']);
    $hatchable  = intval($_POST['hatchable_count']);
    $cracked    = intval($_POST['cracked_count']);
    $table      = intval($_POST['table_count']);
    $chicks     = ($_POST['chicks_hatched'] !== '') ? intval($_POST['chicks_hatched']) : null;

    if ($action === 'create') {
        // Create Operation
        $stmt = $mysqli->prepare("INSERT INTO hatchery_batches (user_id, batch_date, hatchable_count, cracked_count, table_count, chicks_hatched) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isiiii", $user_id, $batch_date, $hatchable, $cracked, $table, $chicks);
        
        if ($stmt->execute()) {
            header("Location: ../hatchery_operations.php?status=success&msg=Batch logged successfully.");
        } else {
            header("Location: ../hatchery_operations.php?status=error&msg=Failed to save entry.");
        }
        $stmt->close();
    } 
    
    elseif ($action === 'update') {
        // Update Operation
        $id = intval($_POST['id']);
        
        $stmt = $mysqli->prepare("UPDATE hatchery_batches SET batch_date = ?, hatchable_count = ?, cracked_count = ?, table_count = ?, chicks_hatched = ? WHERE id = ?");
        $stmt->bind_param("siiiii", $batch_date, $hatchable, $cracked, $table, $chicks, $id);
        
        if ($stmt->execute()) {
            header("Location: ../hatchery_operations.php?status=success&msg=Batch updated successfully.");
        } else {
            header("Location: ../hatchery_operations.php?status=error&msg=Failed to update batch data.");
        }
        $stmt->close();
    }
} 

elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    // Delete Operation
    $id = intval($_GET['id']);
    
    $stmt = $mysqli->prepare("DELETE FROM hatchery_batches WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: ../hatchery_operations.php?status=success&msg=Batch row removed successfully.");
    } else {
        header("Location: ../hatchery_operations.php?status=error&msg=Failed to delete row.");
    }
    $stmt->close();
}

$mysqli->close();
?>