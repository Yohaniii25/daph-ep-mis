<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sms') {
    die("Access denied: Invalid authentication clearance profile.");
}

require_once '../../../../config/db_connect.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id                     = intval($_POST['id'] ?? 0);
    $log_date               = trim($_POST['log_date'] ?? '');
    $drug_type_id           = intval($_POST['drug_type_id'] ?? $_POST['vaccine_type'] ?? 0);
    $vaccine_batch_id       = intval($_POST['vaccine_batch_id'] ?? 0);
    $starter_count_month    = intval($_POST['starter_count_month'] ?? 0);
    $during_month_received  = intval($_POST['during_month_received'] ?? 0);
    $used_doses_count       = intval($_POST['used_doses_count'] ?? 0);
    $doses_damaged          = intval($_POST['doses_damaged'] ?? 0);

    if (empty($log_date) || $drug_type_id <= 0 || $vaccine_batch_id <= 0) {
        die("Data Validation Error: Crucial record metrics are missing.");
    }

    if ($action === 'create') {
        $stmt = $mysqli->prepare("INSERT INTO `drug_records` 
            (log_date, drug_type_id, vaccine_batch_id, starter_count_month, during_month_received, used_doses_count, doses_damaged) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            die("Database Error: " . $mysqli->error);
        }
        $stmt->bind_param("siiiiii", $log_date, $drug_type_id, $vaccine_batch_id, $starter_count_month, $during_month_received, $used_doses_count, $doses_damaged);
        
        if ($stmt->execute()) {
            header("Location: ../drug_maintenance.php?status=success&msg=Entry+Logged+Successfully");
            exit();
        } else {
            die("Database Error: Failed to append record trace: " . $mysqli->error);
        }

    } elseif ($action === 'update' && $id > 0) {
        $stmt = $mysqli->prepare("UPDATE `drug_records` SET 
            log_date = ?, 
            drug_type_id = ?, 
            vaccine_batch_id = ?, 
            starter_count_month = ?, 
            during_month_received = ?, 
            used_doses_count = ?, 
            doses_damaged = ? 
            WHERE id = ?");
        if (!$stmt) {
            die("Database Error: " . $mysqli->error);
        }
        $stmt->bind_param("siiiiiii", $log_date, $drug_type_id, $vaccine_batch_id, $starter_count_month, $during_month_received, $used_doses_count, $doses_damaged, $id);
        
        if ($stmt->execute()) {
            header("Location: ../drug_maintenance.php?status=success&msg=Record+Updated+Successfully");
            exit();
        } else {
            die("Database Error: Failed to rewrite modifications: " . $mysqli->error);
        }
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) die("Error: Missing targeting parameters.");

    $stmt = $mysqli->prepare("DELETE FROM `drug_records` WHERE id = ?");
    if (!$stmt) {
        die("Database Error: " . $mysqli->error);
    }
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: ../drug_maintenance.php?status=success&msg=Record+Purged");
        exit();
    } else {
        die("Database Error: Could not clear ledger row: " . $mysqli->error);
    }
}

header("Location: ../drug_maintenance.php");
exit();