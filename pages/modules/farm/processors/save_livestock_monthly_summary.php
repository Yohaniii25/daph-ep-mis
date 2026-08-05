<?php
// pages/modules/farm/processors/save_livestock_monthly_summary.php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role'])) {
    header("Location: ../../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $month_year = trim($_POST['month_year'] ?? date('Y-m'));
    $matrix_data = $_POST['matrix'] ?? [];

    $stmt = $mysqli->prepare("INSERT INTO livestock_monthly_inventory (user_id, month_year, particular_key, category_key, value_num)
                              VALUES (?, ?, ?, ?, ?)
                              ON DUPLICATE KEY UPDATE value_num = VALUES(value_num)");

    if ($stmt) {
        foreach ($matrix_data as $particular_key => $categories) {
            foreach ($categories as $category_key => $val) {
                $num_val = floatval($val);
                $stmt->bind_param("isssd", $user_id, $month_year, $particular_key, $category_key, $num_val);
                $stmt->execute();
            }
        }
        $stmt->close();
        header("Location: ../inventory_register.php?month=" . urlencode($month_year) . "&status=success&msg=" . urlencode("Monthly Livestock Inventory Summary saved successfully."));
        exit();
    } else {
        header("Location: ../inventory_register.php?month=" . urlencode($month_year) . "&status=error&msg=" . urlencode("Failed to prepare database query."));
        exit();
    }
} else {
    header("Location: ../inventory_register.php");
    exit();
}
