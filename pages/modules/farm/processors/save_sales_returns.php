<?php
// pages/modules/farm/processors/save_sales_returns.php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $record_date = $mysqli->real_escape_string($_POST['record_date']);
    $hatchery_return_no = intval($_POST['hatchery_return_no'] ?? 0);
    $hatchery_return_kg = floatval($_POST['hatchery_return_kg'] ?? 0.00);
    $total_sales_no = intval($_POST['total_sales_no'] ?? 0);
    $total_sales_kg = floatval($_POST['total_sales_kg'] ?? 0.00);

    if (empty($record_date)) {
        header("Location: ../parent_stock_operations.php?status=error&msg=Record date is required.");
        exit();
    }

    // Upsert (INSERT ... ON DUPLICATE KEY UPDATE)
    $sql = "INSERT INTO daily_egg_sales_returns 
            (record_date, hatchery_return_no, hatchery_return_kg, total_sales_no, total_sales_kg) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            hatchery_return_no = VALUES(hatchery_return_no),
            hatchery_return_kg = VALUES(hatchery_return_kg),
            total_sales_no = VALUES(total_sales_no),
            total_sales_kg = VALUES(total_sales_kg)";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("siidi", $record_date, $hatchery_return_no, $hatchery_return_kg, $total_sales_no, $total_sales_kg);

    if ($stmt->execute()) {
        header("Location: ../parent_stock_operations.php?status=success&msg=Daily farm sales and returns saved for " . $record_date);
    } else {
        header("Location: ../parent_stock_operations.php?status=error&msg=Failed to save sales & returns: " . urlencode($stmt->error));
    }
    $stmt->close();
    $mysqli->close();
} else {
    header("Location: ../parent_stock_operations.php");
}
