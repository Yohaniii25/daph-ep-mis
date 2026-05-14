<?php
session_start();
require_once '../../../../config/db_connect.php';

// Access Control
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    die("Unauthorized access");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_eggs') {
    
    // Sanitize inputs
    $flock_id = intval($_POST['flock_id']);
    $egg_count = intval($_POST['egg_count']);
    $collection_date = $_POST['collection_date'];

    if (empty($flock_id) || $egg_count < 0 || empty($collection_date)) {
        header("Location: ../parent_stock_operations.php?status=error&msg=Invalid data provided");
        exit();
    }

    $sql = "INSERT INTO daily_egg_production (flock_id, collection_date, egg_count) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE egg_count = VALUES(egg_count), created_at = CURRENT_TIMESTAMP";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("isi", $flock_id, $collection_date, $egg_count);

    if ($stmt->execute()) {
        header("Location: ../parent_stock_operations.php?status=success&msg=Production data saved successfully");
    } else {
        header("Location: ../parent_stock_operations.php?status=error&msg=Database error: " . $mysqli->error);
    }

    $stmt->close();
    $mysqli->close();
} else {
    header("Location: ../parent_stock_operations.php");
    exit();
}