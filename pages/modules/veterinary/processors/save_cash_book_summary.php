<?php
session_start();
require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon' || !isset($_SESSION['user_id'])) {
    header("Location: ../../../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    $report_year = intval($_POST['report_year'] ?? date('Y'));
    $report_month = intval($_POST['report_month'] ?? 1);
    $item_name = trim($_POST['item_name'] ?? '');
    $quantity_sold = intval($_POST['quantity_sold'] ?? 0);
    $unit_price = floatval($_POST['unit_price'] ?? 0.00);
    $total_amount = floatval($_POST['total_amount'] ?? 0.00);
    $amount_deposited = floatval($_POST['amount_deposited'] ?? 0.00);

    if (empty($item_name) || empty($report_month)) {
        $_SESSION['msg'] = "Item Name and Report Month cannot be empty.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../cash_book_summary.php?status=db_error");
        exit();
    }

    // Double-check range and district IDs from session or user account
    $district_id = $_SESSION['district_id'] ?? null;
    $range_id = $_SESSION['range_id'] ?? null;

    if (empty($district_id) || empty($range_id)) {
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
    }

    if (empty($district_id) || empty($range_id)) {
        $_SESSION['msg'] = "Error: Your user account is not fully configured with a Range/District.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../cash_book_summary.php?status=db_error");
        exit();
    }

    $insert_sql = "
        INSERT INTO cash_book_summaries (
            district_id, range_id, report_year, report_month, item_name, 
            quantity_sold, unit_price, total_amount, amount_deposited, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $mysqli->prepare($insert_sql);
    if ($stmt) {
        $stmt->bind_param(
            "iiiisidddi", 
            $district_id, $range_id, $report_year, $report_month, $item_name,
            $quantity_sold, $unit_price, $total_amount, $amount_deposited, $user_id
        );
        $from_month = intval($_POST['from_month'] ?? 1);
        $to_month = intval($_POST['to_month'] ?? 12);
        $active_tab = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['active_tab'] ?? '');
        $redirect_query = "?status=success&year=" . urlencode($report_year) . "&from_month=" . urlencode($from_month) . "&to_month=" . urlencode($to_month);
        if (!empty($active_tab)) {
            $redirect_query .= "&tab=" . urlencode($active_tab);
        }

        if ($stmt->execute()) {
            $_SESSION['msg'] = "Cash Book record saved successfully.";
            $_SESSION['msg_type'] = "success";
            header("Location: ../cash_book_summary.php" . $redirect_query);
            exit();
        } else {
            $_SESSION['msg'] = "Database error: " . $stmt->error;
            $_SESSION['msg_type'] = "danger";
            header("Location: ../cash_book_summary.php?status=db_error");
            exit();
        }
        $stmt->close();
    } else {
        $_SESSION['msg'] = "Database statement preparation failed.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../cash_book_summary.php?status=db_error");
        exit();
    }
}

header("Location: ../cash_book_summary.php");
exit();
?>
