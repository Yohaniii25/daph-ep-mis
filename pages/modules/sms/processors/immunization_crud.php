<?php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sms') {
    die("Access denied");
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id                    = intval($_POST['id'] ?? 0);
    $user_id               = intval($_SESSION['user_id'] ?? 1); // Fallback to 1 if session id isn't set
    $log_date              = $mysqli->real_escape_string($_POST['log_date']);
    
    // Read your dropdown structural fields as strings to match your table columns
    $vaccination_type      = trim($mysqli->real_escape_string($_POST['vaccine_type_id'] ?? $_POST['vaccine_type'] ?? ''));
    $batch_number          = trim($mysqli->real_escape_string($_POST['vaccine_batch_id'] ?? $_POST['batch_number'] ?? ''));
    
    $starter_count_month   = intval($_POST['starter_count_month']);
    $during_month_received = intval($_POST['during_month_received']);
    $used_doses_count      = intval($_POST['used_doses_count']);
    $doses_damaged         = intval($_POST['doses_damaged']);

    // Math calculation for total balance doses based on your formula
    $balance_doses_qty     = ($starter_count_month + $during_month_received) - ($used_doses_count + $doses_damaged);

    // Create new isolated ledger record mapping
    if ($action === 'create') {
        // MATCHED: Exactly maps to your exact 12 columns (excluding auto-timestamp)
        $stmt = $mysqli->prepare("INSERT INTO `sms_immunization` 
            (user_id, log_date, vaccination_type, starter_count_month, during_month_received, used_batch_number, used_doses_count, doses_damaged, balance_batch_number, balance_doses_qty) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
        if (!$stmt) {
            die("Database Error: " . $mysqli->error);
        }
        
        // Bind parameters matching: i (user_id), s (log_date), s (vaccination_type), i (starter), i (received), s (used_batch), i (used_count), i (damaged), s (balance_batch), i (balance_qty)
        $stmt->bind_param("issiiisisi", 
            $user_id, 
            $log_date, 
            $vaccination_type, 
            $starter_count_month, 
            $during_month_received, 
            $batch_number, // acts as used_batch_number
            $used_doses_count, 
            $doses_damaged, 
            $batch_number, // acts as balance_batch_number
            $balance_doses_qty
        );
        
        if ($stmt->execute()) {
            header("Location: ../immunization.php?status=success&msg=Ledger+Entry+Recorded");
        } else {
            header("Location: ../immunization.php?status=error&msg=Database+Write+Failure");
        }
        $stmt->close();
        exit;
    } 
    
    // Save alterations for existing ledger entry row mapping
    elseif ($action === 'update' && $id > 0) {
        $stmt = $mysqli->prepare("UPDATE `sms_immunization` SET 
            log_date = ?, 
            vaccination_type = ?, 
            starter_count_month = ?, 
            during_month_received = ?, 
            used_batch_number = ?, 
            used_doses_count = ?, 
            doses_damaged = ?, 
            balance_batch_number = ?, 
            balance_doses_qty = ? 
            WHERE id = ?");
            
        if (!$stmt) {
            die("Database Error: " . $mysqli->error);
        }
        
        // Bind types matching update statement
        $stmt->bind_param("ssiiisisii", 
            $log_date, 
            $vaccination_type, 
            $starter_count_month, 
            $during_month_received, 
            $batch_number, 
            $used_doses_count, 
            $doses_damaged, 
            $batch_number, 
            $balance_doses_qty, 
            $id
        );
        
        if ($stmt->execute()) {
            header("Location: ../immunization.php?status=success&msg=Ledger+Changes+Saved");
        } else {
            header("Location: ../immunization.php?status=error&msg=Database+Update+Failure");
        }
        $stmt->close();
        exit;
    }
} 

// Remove transaction mapping parameters completely via URL triggers
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM `sms_immunization` WHERE id = ?");
        if (!$stmt) {
            die("Database Error: " . $mysqli->error);
        }
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            header("Location: ../immunization.php?status=success&msg=Ledger+Entry+Removed");
        } else {
            header("Location: ../immunization.php?status=error&msg=Delete+Failure");
        }
        $stmt->close();
        exit;
    }
}