<?php
session_start();
require_once '../../../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $range_id    = $_SESSION['range_id'] ?? null;
    $category_id = $_POST['category_id'] ?? null;
    $item_id     = $_POST['item_id'] ?? null;
    $amount      = $_POST['amount'] ?? 0;
    $raw_month   = $_POST['report_month'] ?? null; 

    if (!$range_id || !$item_id || !$raw_month || $amount <= 0) {
        $_SESSION['error'] = "Invalid data. Please ensure all fields are filled correctly.";
        header("Location: ../livestock_production.php");
        exit();
    }

    $report_date = $raw_month . "-01";

    $check_stmt = $mysqli->prepare("SELECT id FROM monthly_production_records WHERE range_id = ? AND item_id = ? AND report_date = ?");
    $check_stmt->bind_param("iis", $range_id, $item_id, $report_date);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $_SESSION['error'] = "A record for this product already exists for the selected month.";
        header("Location: ../livestock_production.php");
        exit();
    }

    $stmt = $mysqli->prepare("INSERT INTO monthly_production_records (range_id, item_id, amount, report_date, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("iids", $range_id, $item_id, $amount, $report_date);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Production record saved successfully!";
    } else {
        $_SESSION['error'] = "Database Error: " . $mysqli->error;
    }

    header("Location: ../livestock_production.php");
    exit();
}