<?php
// pages/modules/farm/processors/daily_feed_distribution_crud.php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    $distribution_date = trim($_POST['distribution_date'] ?? date('Y-m-d'));
    $cage_id = !empty($_POST['cage_id']) ? intval($_POST['cage_id']) : NULL;
    $batch_no = trim($_POST['batch_no'] ?? '');
    $feed_type = trim($_POST['feed_type'] ?? 'Layer');
    $no_of_chicks = intval($_POST['no_of_chicks'] ?? 0);
    $amount_needed_kg = floatval($_POST['amount_needed_kg'] ?? 0);
    $amount_distributed_kg = floatval($_POST['amount_distributed_kg'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? '');

    if (empty($distribution_date) || empty($feed_type)) {
        header("Location: ../feed_management.php?tab=daily&status=error&msg=" . urlencode("Date and Feed Type are required."));
        exit();
    }

    if ($action === 'create') {
        $sql = "INSERT INTO daily_feed_distribution (
                    distribution_date, cage_id, batch_no, feed_type,
                    no_of_chicks, amount_needed_kg, amount_distributed_kg, remarks
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param(
            "sissidds",
            $distribution_date, $cage_id, $batch_no, $feed_type,
            $no_of_chicks, $amount_needed_kg, $amount_distributed_kg, $remarks
        );

        if ($stmt->execute()) {
            header("Location: ../feed_management.php?tab=daily&status=success&msg=" . urlencode("Daily feed distribution entry saved successfully."));
        } else {
            header("Location: ../feed_management.php?tab=daily&status=error&msg=" . urlencode("Failed to save entry: " . $stmt->error));
        }
        $stmt->close();
    } elseif ($action === 'update') {
        if ($id <= 0) {
            header("Location: ../feed_management.php?tab=daily&status=error&msg=" . urlencode("Invalid record ID."));
            exit();
        }

        $sql = "UPDATE daily_feed_distribution SET 
                    distribution_date = ?, cage_id = ?, batch_no = ?, feed_type = ?,
                    no_of_chicks = ?, amount_needed_kg = ?, amount_distributed_kg = ?, remarks = ?
                WHERE id = ?";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param(
            "sissiddsi",
            $distribution_date, $cage_id, $batch_no, $feed_type,
            $no_of_chicks, $amount_needed_kg, $amount_distributed_kg, $remarks, $id
        );

        if ($stmt->execute()) {
            header("Location: ../feed_management.php?tab=daily&status=success&msg=" . urlencode("Daily feed distribution entry updated successfully."));
        } else {
            header("Location: ../feed_management.php?tab=daily&status=error&msg=" . urlencode("Failed to update entry: " . $stmt->error));
        }
        $stmt->close();
    }
    $mysqli->close();
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        header("Location: ../feed_management.php?tab=daily&status=error&msg=" . urlencode("Invalid record ID."));
        exit();
    }

    $stmt = $mysqli->prepare("DELETE FROM daily_feed_distribution WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: ../feed_management.php?tab=daily&status=success&msg=" . urlencode("Entry deleted successfully."));
    } else {
        header("Location: ../feed_management.php?tab=daily&status=error&msg=" . urlencode("Failed to delete entry: " . $stmt->error));
    }
    $stmt->close();
    $mysqli->close();
} else {
    header("Location: ../feed_management.php?tab=daily");
    exit();
}
