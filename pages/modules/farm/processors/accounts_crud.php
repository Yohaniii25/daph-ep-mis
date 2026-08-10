<?php
// pages/modules/farm/processors/accounts_crud.php -> CRUD processor for Direct Farm Accounts Ledger Entries
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['role'])) {
    header("Location: ../../../index.php");
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add') {
    $transaction_date = trim($_POST['transaction_date'] ?? date('Y-m-d'));
    $voucher_no = trim($_POST['voucher_no'] ?? '');
    $account_category = trim($_POST['account_category'] ?? 'General');
    $transaction_type = trim($_POST['transaction_type'] ?? 'Income');
    $description = trim($_POST['description'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $cash_book_ref = trim($_POST['cash_book_ref'] ?? '');

    if (empty($voucher_no) || empty($description) || $amount <= 0) {
        header("Location: ../accounts_register.php?status=error&msg=" . urlencode("Please fill all required fields with a valid positive amount."));
        exit();
    }

    $stmt = $mysqli->prepare("INSERT INTO farm_accounts (transaction_date, voucher_no, account_category, transaction_type, description, amount, cash_book_ref) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sssssds", $transaction_date, $voucher_no, $account_category, $transaction_type, $description, $amount, $cash_book_ref);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: ../accounts_register.php?status=success&msg=" . urlencode("Financial entry created successfully."));
            exit();
        } else {
            $err = $stmt->error;
            $stmt->close();
            header("Location: ../accounts_register.php?status=error&msg=" . urlencode("Failed to insert record: " . $err));
            exit();
        }
    } else {
        header("Location: ../accounts_register.php?status=error&msg=" . urlencode("Database error: " . $mysqli->error));
        exit();
    }

} elseif ($action === 'edit') {
    $id = intval($_POST['id'] ?? 0);
    $transaction_date = trim($_POST['transaction_date'] ?? date('Y-m-d'));
    $voucher_no = trim($_POST['voucher_no'] ?? '');
    $account_category = trim($_POST['account_category'] ?? 'General');
    $transaction_type = trim($_POST['transaction_type'] ?? 'Income');
    $description = trim($_POST['description'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $cash_book_ref = trim($_POST['cash_book_ref'] ?? '');

    if ($id <= 0 || empty($voucher_no) || empty($description) || $amount <= 0) {
        header("Location: ../accounts_register.php?status=error&msg=" . urlencode("Invalid data submitted for update."));
        exit();
    }

    $stmt = $mysqli->prepare("UPDATE farm_accounts SET transaction_date = ?, voucher_no = ?, account_category = ?, transaction_type = ?, description = ?, amount = ?, cash_book_ref = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("sssssdsi", $transaction_date, $voucher_no, $account_category, $transaction_type, $description, $amount, $cash_book_ref, $id);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: ../accounts_register.php?status=success&msg=" . urlencode("Financial entry updated successfully."));
            exit();
        } else {
            $err = $stmt->error;
            $stmt->close();
            header("Location: ../accounts_register.php?status=error&msg=" . urlencode("Failed to update record: " . $err));
            exit();
        }
    } else {
        header("Location: ../accounts_register.php?status=error&msg=" . urlencode("Database error: " . $mysqli->error));
        exit();
    }

} elseif ($action === 'delete') {
    $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) {
        header("Location: ../accounts_register.php?status=error&msg=" . urlencode("Invalid ID for deletion."));
        exit();
    }

    $stmt = $mysqli->prepare("DELETE FROM farm_accounts WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: ../accounts_register.php?status=success&msg=" . urlencode("Financial entry deleted successfully."));
            exit();
        } else {
            $err = $stmt->error;
            $stmt->close();
            header("Location: ../accounts_register.php?status=error&msg=" . urlencode("Failed to delete record: " . $err));
            exit();
        }
    } else {
        header("Location: ../accounts_register.php?status=error&msg=" . urlencode("Database error: " . $mysqli->error));
        exit();
    }
} else {
    header("Location: ../accounts_register.php");
    exit();
}
