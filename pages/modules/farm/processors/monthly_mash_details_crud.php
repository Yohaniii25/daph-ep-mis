<?php
// pages/modules/farm/processors/monthly_mash_details_crud.php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    $opening_stock_kg = floatval($_POST['opening_stock_kg'] ?? 0);
    $received_kg = floatval($_POST['received_kg'] ?? 0);
    $issued_other_farm_kg = floatval($_POST['issued_other_farm_kg'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? '');

    if ($id <= 0) {
        header("Location: ../feed_management.php?tab=annex4&status=error&msg=" . urlencode("Invalid record ID."));
        exit();
    }

    // First fetch current consumption_kg for this record
    $stmt_get = $mysqli->prepare("SELECT record_month, feed_type, consumption_kg FROM monthly_mash_details WHERE id = ?");
    $stmt_get->bind_param("i", $id);
    $stmt_get->execute();
    $res = $stmt_get->get_result();
    $current = $res->fetch_assoc();
    $stmt_get->close();

    if (!$current) {
        header("Location: ../feed_management.php?tab=annex4&status=error&msg=" . urlencode("Record not found."));
        exit();
    }

    // Auto-calculate consumption from daily_feed_distribution to ensure it's up to date
    $first_day = $current['record_month'];
    $last_day = date('Y-m-t', strtotime($first_day));
    $feed_type = $current['feed_type'];

    $stmt_sum = $mysqli->prepare("SELECT COALESCE(SUM(amount_distributed_kg), 0) AS total_consumed FROM daily_feed_distribution WHERE feed_type = ? AND distribution_date BETWEEN ? AND ?");
    $stmt_sum->bind_param("sss", $feed_type, $first_day, $last_day);
    $stmt_sum->execute();
    $sum_res = $stmt_sum->get_result()->fetch_assoc();
    $consumption_kg = floatval($sum_res['total_consumed'] ?? 0);
    $stmt_sum->close();

    // Auto-calculate balance formula: (opening_stock_kg + received_kg) - (consumption_kg + issued_other_farm_kg)
    $balance_stock_kg = ($opening_stock_kg + $received_kg) - ($consumption_kg + $issued_other_farm_kg);

    $sql = "UPDATE monthly_mash_details SET 
                opening_stock_kg = ?, received_kg = ?, consumption_kg = ?,
                issued_other_farm_kg = ?, balance_stock_kg = ?, remarks = ?
            WHERE id = ?";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param(
        "dddddsi",
        $opening_stock_kg, $received_kg, $consumption_kg,
        $issued_other_farm_kg, $balance_stock_kg, $remarks, $id
    );

    if ($stmt->execute()) {
        header("Location: ../feed_management.php?tab=annex4&status=success&msg=" . urlencode("Annex 4 Mash stock details updated successfully."));
    } else {
        header("Location: ../feed_management.php?tab=annex4&status=error&msg=" . urlencode("Failed to update record: " . $stmt->error));
    }
    $stmt->close();
    $mysqli->close();
} else {
    header("Location: ../feed_management.php?tab=annex4");
    exit();
}
