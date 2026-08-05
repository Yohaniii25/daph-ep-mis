<?php
// pages/modules/farm/processors/monthly_fuel_summary_crud.php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $opening_stock = floatval($_POST['opening_stock'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? '');

    if ($id <= 0) {
        header("Location: ../fuel_register.php?tab=summary&status=error&msg=" . urlencode("Invalid record ID."));
        exit();
    }

    // Fetch record info
    $stmt_get = $mysqli->prepare("SELECT record_month, fuel_type FROM monthly_fuel_summary WHERE id = ?");
    $stmt_get->bind_param("i", $id);
    $stmt_get->execute();
    $current = $stmt_get->get_result()->fetch_assoc();
    $stmt_get->close();

    if (!$current) {
        header("Location: ../fuel_register.php?tab=summary&status=error&msg=" . urlencode("Record not found."));
        exit();
    }

    $first_day = $current['record_month'];
    $last_day = date('Y-m-t', strtotime($first_day));
    $fuel_type = $current['fuel_type'];
    $month_str = date('Y-m', strtotime($first_day));

    // Auto-calculate Purchased (Received) and Consumption (Issued) from daily Fuel Register
    $purchased = 0.00;
    $consumption = 0.00;

    $search_pattern = '%' . $fuel_type . '%';
    $stmt_calc = $mysqli->prepare("
        SELECT 
            COALESCE(SUM(fr.received_qty), 0) AS total_purchased,
            COALESCE(SUM(fr.issued_qty), 0) AS total_consumed
        FROM farm_fuel_register fr
        JOIN farm_fuel_items fi ON fr.item_id = fi.id
        WHERE (fi.item_name LIKE ? OR ? LIKE CONCAT('%', fi.item_name, '%'))
          AND fr.record_date BETWEEN ? AND ?
    ");
    $stmt_calc->bind_param("ssss", $search_pattern, $fuel_type, $first_day, $last_day);
    $stmt_calc->execute();
    $calc_res = $stmt_calc->get_result()->fetch_assoc();
    if ($calc_res) {
        $purchased = floatval($calc_res['total_purchased']);
        $consumption = floatval($calc_res['total_consumed']);
    }
    $stmt_calc->close();

    // Balance Formula: (Opening stock + Purchased) - Consumption
    $balance = ($opening_stock + $purchased) - $consumption;

    $stmt_upd = $mysqli->prepare("UPDATE monthly_fuel_summary SET opening_stock = ?, purchased = ?, consumption = ?, balance = ?, remarks = ? WHERE id = ?");
    $stmt_upd->bind_param("ddddsi", $opening_stock, $purchased, $consumption, $balance, $remarks, $id);

    if ($stmt_upd->execute()) {
        header("Location: ../fuel_register.php?tab=summary&month=" . $month_str . "&status=success&msg=" . urlencode("Monthly fuel summary updated successfully."));
    } else {
        header("Location: ../fuel_register.php?tab=summary&month=" . $month_str . "&status=error&msg=" . urlencode("Failed to update fuel summary: " . $stmt_upd->error));
    }
    $stmt_upd->close();
    $mysqli->close();
} else {
    header("Location: ../fuel_register.php?tab=summary");
    exit();
}
?>
