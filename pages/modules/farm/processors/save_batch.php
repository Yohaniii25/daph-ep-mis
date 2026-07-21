<?php
// pages/modules/farm/processors/save_batch.php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;
$action = $_POST['action'] ?? $_GET['action'] ?? 'create';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batch_number = trim($mysqli->real_escape_string($_POST['batch_number']));

    if (empty($batch_number)) {
        header("Location: ../parent_stock_operations.php?status=error&msg=Batch number cannot be empty.");
        exit();
    }

    if ($action === 'create') {
        $stmt = $mysqli->prepare("INSERT INTO vaccine_batches (batch_number, user_id) VALUES (?, ?)");
        $stmt->bind_param("si", $batch_number, $user_id);

        if ($stmt->execute()) {
            header("Location: ../parent_stock_operations.php?status=success&msg=Batch added successfully.");
        } else {
            header("Location: ../parent_stock_operations.php?status=error&msg=Failed to add batch: " . $mysqli->error);
        }
        $stmt->close();
    } elseif ($action === 'update') {
        $id = intval($_POST['id']);

        // Verify ownership
        $chk = $mysqli->prepare("SELECT id FROM vaccine_batches WHERE id = ? AND user_id = ?");
        $chk->bind_param("ii", $id, $user_id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows === 0) {
            $chk->close();
            header("Location: ../parent_stock_operations.php?status=error&msg=Access denied to update this batch.");
            exit();
        }
        $chk->close();

        $stmt = $mysqli->prepare("UPDATE vaccine_batches SET batch_number = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sii", $batch_number, $id, $user_id);

        if ($stmt->execute()) {
            header("Location: ../parent_stock_operations.php?status=success&msg=Batch updated successfully.");
        } else {
            header("Location: ../parent_stock_operations.php?status=error&msg=Failed to update batch: " . $mysqli->error);
        }
        $stmt->close();
    }
    $mysqli->close();
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = intval($_GET['id']);

    // Verify ownership
    $chk = $mysqli->prepare("SELECT id FROM vaccine_batches WHERE id = ? AND user_id = ?");
    $chk->bind_param("ii", $id, $user_id);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows === 0) {
        $chk->close();
        header("Location: ../parent_stock_operations.php?status=error&msg=Access denied to delete this batch.");
        exit();
    }
    $chk->close();

    $stmt = $mysqli->prepare("DELETE FROM vaccine_batches WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);

    if ($stmt->execute()) {
        header("Location: ../parent_stock_operations.php?status=success&msg=Batch deleted successfully.");
    } else {
        header("Location: ../parent_stock_operations.php?status=error&msg=Failed to delete batch: " . $mysqli->error);
    }
    $stmt->close();
    $mysqli->close();
} else {
    header("Location: ../parent_stock_operations.php");
}
