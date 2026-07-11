<?php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon' || !isset($_SESSION['user_id'])) {
    header("Location: ../../../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $report_year = intval($_POST['report_year'] ?? 2026);
    $report_month = intval($_POST['report_month'] ?? 1);
    $item_name = trim($_POST['item_name'] ?? '');
    $balance_prev = intval($_POST['balance_previous_month'] ?? 0);
    $received = intval($_POST['received_current_month'] ?? 0);
    $issued = intval($_POST['issued_current_month'] ?? 0);
    $remark = trim($_POST['remark'] ?? '');

    // Retrieve manually entered current balance
    $balance_curr = intval($_POST['balance_current_month'] ?? 0);

    if (empty($item_name)) {
        $_SESSION['msg'] = "Item Name cannot be empty.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../crop_returns.php");
        exit();
    }

    // Extract district_id and range_id from users
    $district_id = null;
    $range_id = null;

    $user_stmt = $mysqli->prepare("SELECT district_id, range_id FROM users WHERE id = ?");
    if ($user_stmt) {
        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        $user_res = $user_stmt->get_result()->fetch_assoc();
        if ($user_res) {
            $district_id = $user_res['district_id'];
            $range_id = $user_res['range_id'];
        }
        $user_stmt->close();
    }

    if (empty($district_id) || empty($range_id)) {
        $_SESSION['msg'] = "Error: Your user account is not fully configured with a Range/District.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../crop_returns.php");
        exit();
    }

    $insert_sql = "
        INSERT INTO crop_returns (
            district_id, range_id, report_year, report_month, item_name, 
            balance_previous_month, received_current_month, issued_current_month, 
            balance_current_month, remark
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $mysqli->prepare($insert_sql);
    if ($stmt) {
        $stmt->bind_param(
            "iiiisiiiis", 
            $district_id, $range_id, $report_year, $report_month, $item_name,
            $balance_prev, $received, $issued, $balance_curr, $remark
        );
        if ($stmt->execute()) {
            $_SESSION['msg'] = "Crop Return record added successfully.";
            $_SESSION['msg_type'] = "success";
            header("Location: ../crop_returns.php?status=added");
            exit();
        } else {
            $_SESSION['msg'] = "Database error: " . $stmt->error;
            $_SESSION['msg_type'] = "danger";
            header("Location: ../crop_returns.php?status=db_error");
            exit();
        }
        $stmt->close();
    } else {
        $_SESSION['msg'] = "Database statement preparation failed.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../crop_returns.php?status=db_error");
        exit();
    }
}

header("Location: ../crop_returns.php");
exit();
?>
