<?php
// pages/modules/farm/processors/save_chicks_death.php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_month = trim($_POST['record_month'] ?? ''); // e.g. 2026-05
    $batch_no = trim($_POST['batch_no'] ?? '');
    $deaths = intval($_POST['deaths'] ?? 0);

    if (empty($raw_month) || empty($batch_no)) {
        header("Location: ../chicks_death_details.php?status=error&msg=" . urlencode("Record month and batch number are required."));
        exit();
    }

    // Convert YYYY-MM to YYYY-MM-01 format
    $record_month = date('Y-m-01', strtotime($raw_month . '-01'));

    if ($action === 'create') {
        $sql = "INSERT INTO chicks_death_details (record_month, batch_no, deaths) VALUES (?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        
        // Exactly 3 parameters: string (s), string (s), int (i) -> "ssi"
        $stmt->bind_param("ssi", $record_month, $batch_no, $deaths);

        if ($stmt->execute()) {
            header("Location: ../chicks_death_details.php?status=success&msg=" . urlencode("Chicks death record saved successfully.") . "&month=" . urlencode($raw_month));
        } else {
            header("Location: ../chicks_death_details.php?status=error&msg=" . urlencode("Failed to save record: " . $stmt->error));
        }
        $stmt->close();
    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            header("Location: ../chicks_death_details.php?status=error&msg=" . urlencode("Invalid record ID."));
            exit();
        }

        $sql = "UPDATE chicks_death_details SET record_month = ?, batch_no = ?, deaths = ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql);

        // Exactly 4 parameters: string (s), string (s), int (i), int (i) -> "ssii"
        $stmt->bind_param("ssii", $record_month, $batch_no, $deaths, $id);

        if ($stmt->execute()) {
            header("Location: ../chicks_death_details.php?status=success&msg=" . urlencode("Chicks death record updated successfully.") . "&month=" . urlencode($raw_month));
        } else {
            header("Location: ../chicks_death_details.php?status=error&msg=" . urlencode("Failed to update record: " . $stmt->error));
        }
        $stmt->close();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    $redirect_month = $_GET['month'] ?? '';

    if ($id <= 0) {
        header("Location: ../chicks_death_details.php?status=error&msg=" . urlencode("Invalid record ID."));
        exit();
    }

    $sql = "DELETE FROM chicks_death_details WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    
    // Exactly 1 parameter: int (i) -> "i"
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: ../chicks_death_details.php?status=success&msg=" . urlencode("Chicks death record deleted successfully.") . "&month=" . urlencode($redirect_month));
    } else {
        header("Location: ../chicks_death_details.php?status=error&msg=" . urlencode("Failed to delete record."));
    }
    $stmt->close();
}

$mysqli->close();
