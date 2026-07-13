<?php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon' || !isset($_SESSION['user_id'])) {
    header("Location: ../../../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $id = intval($_POST['id'] ?? 0);
    
    $report_year = intval($_POST['report_year'] ?? date('Y'));
    $report_month = intval($_POST['report_month'] ?? 1);
    $item_name = trim($_POST['item_name'] ?? '');
    $quantity_sold = intval($_POST['quantity_sold'] ?? 0);
    $unit_price = floatval($_POST['unit_price'] ?? 0.00);
    $total_amount = floatval($_POST['total_amount'] ?? 0.00);
    $amount_deposited = floatval($_POST['amount_deposited'] ?? 0.00);

    if (empty($id) || empty($item_name) || empty($report_month)) {
        $_SESSION['msg'] = "Error: Invalid inputs entered.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../cash_book_summary.php?status=db_error");
        exit();
    }

    // Secure range verify
    $range_id = $_SESSION['range_id'] ?? null;
    if (empty($range_id)) {
        $user_stmt = $mysqli->prepare("SELECT range_id FROM users WHERE id = ?");
        if ($user_stmt) {
            $user_stmt->bind_param("i", $user_id);
            $user_stmt->execute();
            $user_res = $user_stmt->get_result()->fetch_assoc();
            if ($user_res) {
                $range_id = $user_res['range_id'];
            }
            $user_stmt->close();
        }
    }

    if (empty($range_id)) {
        $_SESSION['msg'] = "Error: You are not assigned to a range.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../cash_book_summary.php?status=db_error");
        exit();
    }

    $update_sql = "
        UPDATE cash_book_summaries 
        SET report_year = ?, report_month = ?, item_name = ?, 
            quantity_sold = ?, unit_price = ?, total_amount = ?, amount_deposited = ? 
        WHERE id = ? AND range_id = ?
    ";

    $stmt = $mysqli->prepare($update_sql);
    if ($stmt) {
        $stmt->bind_param(
            "iisidddii",
            $report_year, $report_month, $item_name,
            $quantity_sold, $unit_price, $total_amount, $amount_deposited,
            $id, $range_id
        );
        if ($stmt->execute()) {
            $_SESSION['msg'] = "Cash Book record updated successfully.";
            $_SESSION['msg_type'] = "success";
            header("Location: ../cash_book_summary.php?status=success");
        } else {
            $_SESSION['msg'] = "Database error: " . $stmt->error;
            $_SESSION['msg_type'] = "danger";
            header("Location: ../cash_book_summary.php?status=db_error");
        }
        $stmt->close();
    } else {
        $_SESSION['msg'] = "Database statement preparation failed.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../cash_book_summary.php?status=db_error");
    }
} else {
    header("Location: ../cash_book_summary.php");
}
exit();
?>
