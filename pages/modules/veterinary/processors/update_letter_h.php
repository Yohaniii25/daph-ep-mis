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
    
    $transaction_date = trim($_POST['transaction_date'] ?? '');
    $transaction_type = trim($_POST['transaction_type'] ?? 'Receipt');
    $reference_no = !empty($_POST['reference_no']) ? trim($_POST['reference_no']) : null;
    $particulars = trim($_POST['particulars'] ?? '');
    $quantity = !empty($_POST['quantity']) ? intval($_POST['quantity']) : null;
    $rate = !empty($_POST['rate']) ? floatval($_POST['rate']) : null;
    $amount = floatval($_POST['amount'] ?? 0.00);

    if (empty($id) || empty($transaction_date) || empty($particulars)) {
        $_SESSION['msg'] = "Error: Invalid inputs entered.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../letter_h_record.php");
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
        header("Location: ../letter_h_record.php");
        exit();
    }

    $update_sql = "
        UPDATE letter_h_accounts 
        SET transaction_date = ?, transaction_type = ?, reference_no = ?, 
            particulars = ?, quantity = ?, rate = ?, amount = ? 
        WHERE id = ? AND range_id = ?
    ";

    $stmt = $mysqli->prepare($update_sql);
    if ($stmt) {
        $stmt->bind_param(
            "ssssiddii",
            $transaction_date, $transaction_type, $reference_no,
            $particulars, $quantity, $rate, $amount,
            $id, $range_id
        );
        if ($stmt->execute()) {
            $_SESSION['msg'] = "Letter H Record updated successfully.";
            $_SESSION['msg_type'] = "success";
            header("Location: ../letter_h_record.php?status=success");
        } else {
            $_SESSION['msg'] = "Database error: " . $stmt->error;
            $_SESSION['msg_type'] = "danger";
            header("Location: ../letter_h_record.php?status=db_error");
        }
        $stmt->close();
    } else {
        $_SESSION['msg'] = "Database statement preparation failed.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../letter_h_record.php?status=db_error");
    }
} else {
    header("Location: ../letter_h_record.php");
}
exit();
?>
