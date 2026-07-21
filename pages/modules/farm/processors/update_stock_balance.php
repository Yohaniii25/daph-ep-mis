<?php
session_start();
require_once '../../../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_stock') {

    // Validate POST data
    if (!isset($_POST['flock_id']) || !isset($_POST['newly_added']) || !isset($_POST['culling'])) {
        header("Location: ../parent_stock_operations.php?status=error&msg=Missing required fields.");
        exit();
    }

    $flock_id = intval($_POST['flock_id']);
    $newly_added = intval($_POST['newly_added']);
    $culling = intval($_POST['culling']);

    // Validate values
    if ($flock_id <= 0) {
        header("Location: ../parent_stock_operations.php?status=error&msg=Invalid flock ID.");
        exit();
    }

    // DEBUG: Check if flock exists and belongs to the user's farm
    $farm_where = "";
    if ($_SESSION['role'] === 'farms_dd' && !empty($_SESSION['farm_id'])) {
        $farm_where = " AND farm_id = " . (int)$_SESSION['farm_id'];
    }
    $check_flock = $mysqli->query("SELECT id FROM parent_stock_flocks WHERE id = " . $flock_id . $farm_where);
    if (!$check_flock || $check_flock->num_rows == 0) {
        error_log("Flock ID " . $flock_id . " does not exist or unauthorized");
        header("Location: ../parent_stock_operations.php?status=error&msg=Selected flock does not exist or access denied.");
        exit();
    }

    // Start transaction to ensure data consistency
    $mysqli->begin_transaction();

    try {
        // 1. Insert into Audit Log
        $log_query = "INSERT INTO stock_balance_logs (flock_id, newly_added, culling) VALUES (?, ?, ?)";
        $log_stmt = $mysqli->prepare($log_query);

        if (!$log_stmt) {
            throw new Exception("Prepare failed: " . $mysqli->error);
        }

        $log_stmt->bind_param("iii", $flock_id, $newly_added, $culling);

        if (!$log_stmt->execute()) {
            throw new Exception("Insert failed: " . $log_stmt->error);
        }
        $log_stmt->close();

        // 2. Update current_count by adding newly_added
        $add_query = "UPDATE parent_stock_flocks SET current_count = current_count + ? WHERE id = ?";
        $add_stmt = $mysqli->prepare($add_query);
        if (!$add_stmt) {
            throw new Exception("Prepare failed: " . $mysqli->error);
        }
        $add_stmt->bind_param("ii", $newly_added, $flock_id);
        if (!$add_stmt->execute()) {
            throw new Exception("Add update failed: " . $add_stmt->error);
        }
        $add_stmt->close();

        // 3. Update current_count by subtracting culling
        $subtract_query = "UPDATE parent_stock_flocks SET current_count = current_count - ? WHERE id = ?";
        $subtract_stmt = $mysqli->prepare($subtract_query);
        if (!$subtract_stmt) {
            throw new Exception("Prepare failed: " . $mysqli->error);
        }
        $subtract_stmt->bind_param("ii", $culling, $flock_id);
        if (!$subtract_stmt->execute()) {
            throw new Exception("Subtract update failed: " . $subtract_stmt->error);
        }
        if ($subtract_stmt->affected_rows === 0) {
            throw new Exception("Flock not found with ID: " . $flock_id);
        }
        $subtract_stmt->close();

        // Commit changes
        $mysqli->commit();

        header("Location: ../parent_stock_operations.php?status=success&msg=Stock balance updated successfully.");
        exit();
    } catch (Exception $e) {
        // Rollback if anything fails
        $mysqli->rollback();
        error_log("Stock balance update error: " . $e->getMessage());
        header("Location: ../parent_stock_operations.php?status=error&msg=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    die("Invalid request.");
}
