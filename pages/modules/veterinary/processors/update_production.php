<?php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon' || !isset($_SESSION['user_id'])) {
    header("Location: ../../../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id     = $_SESSION['user_id'];
    $id          = intval($_POST['id'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $item_id     = intval($_POST['item_id'] ?? 0);
    $amount      = floatval($_POST['amount'] ?? 0);
    $raw_month   = trim($_POST['report_month'] ?? ''); 

    if (empty($id) || empty($category_id) || empty($item_id) || empty($raw_month) || $amount < 0) {
        $_SESSION['msg'] = "Error: Invalid inputs entered.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../section_e.php?status=db_error");
        exit();
    }

    $range_id = $_SESSION['range_id'] ?? null;
    if (empty($range_id)) {
        $user_sql = $mysqli->prepare("SELECT range_id FROM users WHERE id = ?");
        if ($user_sql) {
            $user_sql->bind_param("i", $user_id);
            $user_sql->execute();
            $user_res = $user_sql->get_result()->fetch_assoc();
            if ($user_res) {
                $range_id = $user_res['range_id'];
                $_SESSION['range_id'] = $range_id;
            }
            $user_sql->close();
        }
    }

    if (empty($range_id)) {
        $_SESSION['msg'] = "Error: Surgeon range not found.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../section_e.php?status=db_error");
        exit();
    }

    // Split report month parameter
    $date_parts = explode('-', $raw_month);
    if (count($date_parts) !== 2) {
        $_SESSION['msg'] = "Error: Invalid month format.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../section_e.php?status=db_error");
        exit();
    }
    $report_year  = intval($date_parts[0]);
    $report_month = intval($date_parts[1]);

    // Check duplicate key
    $dup_sql = "
        SELECT id FROM section_e 
        WHERE range_id = ? AND item_id = ? AND report_year = ? AND report_month = ? AND id != ?
    ";
    $dup_stmt = $mysqli->prepare($dup_sql);
    if ($dup_stmt) {
        $dup_stmt->bind_param("iiiii", $range_id, $item_id, $report_year, $report_month, $id);
        $dup_stmt->execute();
        if ($dup_stmt->get_result()->num_rows > 0) {
            $_SESSION['msg'] = "Error: Another record for this item already exists for the selected month.";
            $_SESSION['msg_type'] = "danger";
            $dup_stmt->close();
            header("Location: ../section_e.php?status=db_error");
            exit();
        }
        $dup_stmt->close();
    }

    $update_sql = "
        UPDATE section_e 
        SET report_year = ?, report_month = ?, category_id = ?, 
            item_id = ?, amount = ? 
        WHERE id = ? AND range_id = ?
    ";

    $stmt = $mysqli->prepare($update_sql);
    if ($stmt) {
        $stmt->bind_param(
            "iiiidii", 
            $report_year, $report_month, $category_id, 
            $item_id, $amount, $id, $range_id
        );
        if ($stmt->execute()) {
            $_SESSION['msg'] = "Production record updated successfully.";
            $_SESSION['msg_type'] = "success";
            header("Location: ../section_e.php?status=updated");
        } else {
            $_SESSION['msg'] = "Database error: " . $stmt->error;
            $_SESSION['msg_type'] = "danger";
            header("Location: ../section_e.php?status=db_error");
        }
        $stmt->close();
    } else {
        $_SESSION['msg'] = "Database Statement preparation failed.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../section_e.php?status=db_error");
    }
} else {
    header("Location: ../section_e.php");
}
exit();
?>
