<?php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon' || !isset($_SESSION['user_id'])) {
    header("Location: ../../../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    $transaction_date = trim($_POST['transaction_date'] ?? '');
    $transaction_type = trim($_POST['transaction_type'] ?? 'Receipt');
    $reference_no = !empty($_POST['reference_no']) ? trim($_POST['reference_no']) : null;
    $particulars = trim($_POST['particulars'] ?? '');
    $quantity = !empty($_POST['quantity']) ? intval($_POST['quantity']) : null;
    $rate = !empty($_POST['rate']) ? floatval($_POST['rate']) : null;
    $amount = floatval($_POST['amount'] ?? 0.00);

    if (empty($transaction_date) || empty($particulars)) {
        $_SESSION['msg'] = "Transaction Date and Particulars cannot be empty.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../letter_h_record.php");
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
        header("Location: ../letter_h_record.php");
        exit();
    }

    $insert_sql = "
        INSERT INTO letter_h_accounts (
            district_id, range_id, transaction_date, transaction_type, 
            reference_no, particulars, quantity, rate, amount, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $mysqli->prepare($insert_sql);
    if ($stmt) {
        $stmt->bind_param(
            "iissssiddi", 
            $district_id, $range_id, $transaction_date, $transaction_type,
            $reference_no, $particulars, $quantity, $rate, $amount, $user_id
        );
        if ($stmt->execute()) {
            $_SESSION['msg'] = "Letter H Record added successfully.";
            $_SESSION['msg_type'] = "success";
            header("Location: ../letter_h_record.php?status=success");
            exit();
        } else {
            $_SESSION['msg'] = "Database error: " . $stmt->error;
            $_SESSION['msg_type'] = "danger";
            header("Location: ../letter_h_record.php?status=db_error");
            exit();
        }
        $stmt->close();
    } else {
        $_SESSION['msg'] = "Database statement preparation failed.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../letter_h_record.php?status=db_error");
        exit();
    }
}

header("Location: ../letter_h_record.php");
exit();
?>
