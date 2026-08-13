<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../../config/db_connect.php';

// Allow authorized roles
$allowed_roles = ['training_officer', 'administrator', 'provincial_director', 'district_dd'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied");
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$redirect_url = '../monthly_income_summary.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $training_center_id = intval($_POST['training_center_id'] ?? 0);
        $receipt_date       = trim($_POST['receipt_date'] ?? '');
        $receipt_no         = trim($_POST['receipt_no'] ?? '');
        $category           = trim($_POST['category'] ?? '');
        $amount             = floatval($_POST['amount'] ?? 0);
        $payer_name         = trim($_POST['payer_name'] ?? '');
        $remarks            = trim($_POST['remarks'] ?? '');
        $created_by         = intval($_SESSION['user_id'] ?? 0);

        if ($training_center_id <= 0 || empty($receipt_date) || empty($receipt_no) || empty($category) || $amount <= 0) {
            $_SESSION['error_message'] = "Please fill in all required receipt fields with valid amounts.";
            header("Location: $redirect_url");
            exit();
        }

        $stmt = $mysqli->prepare("INSERT INTO training_income_receipts (training_center_id, receipt_date, receipt_no, category, amount, payer_name, remarks, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isssdssi", $training_center_id, $receipt_date, $receipt_no, $category, $amount, $payer_name, $remarks, $created_by);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Receipt recorded successfully.";
            } else {
                $_SESSION['error_message'] = "Database error: " . $stmt->error;
            }
            $stmt->close();
        }
    } elseif ($action === 'update') {
        $id                 = intval($_POST['id'] ?? 0);
        $receipt_date       = trim($_POST['receipt_date'] ?? '');
        $receipt_no         = trim($_POST['receipt_no'] ?? '');
        $category           = trim($_POST['category'] ?? '');
        $amount             = floatval($_POST['amount'] ?? 0);
        $payer_name         = trim($_POST['payer_name'] ?? '');
        $remarks            = trim($_POST['remarks'] ?? '');

        if ($id <= 0 || empty($receipt_date) || empty($receipt_no) || empty($category) || $amount <= 0) {
            $_SESSION['error_message'] = "Please fill in all required receipt fields with valid amounts.";
            header("Location: $redirect_url");
            exit();
        }

        $stmt = $mysqli->prepare("UPDATE training_income_receipts SET receipt_date = ?, receipt_no = ?, category = ?, amount = ?, payer_name = ?, remarks = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("sssdssi", $receipt_date, $receipt_no, $category, $amount, $payer_name, $remarks, $id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Receipt updated successfully.";
            } else {
                $_SESSION['error_message'] = "Failed to update receipt: " . $stmt->error;
            }
            $stmt->close();
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM training_income_receipts WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Receipt deleted successfully.";
            } else {
                $_SESSION['error_message'] = "Failed to delete receipt.";
            }
            $stmt->close();
        }
    }
}

header("Location: $redirect_url");
exit();
