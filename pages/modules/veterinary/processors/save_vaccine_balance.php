<?php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon' || !isset($_SESSION['user_id'])) {
    header("Location: ../../../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    $report_year = intval($_POST['report_year'] ?? date('Y'));
    $report_month = intval($_POST['report_month'] ?? 1);
    $vaccine_name = trim($_POST['vaccine_name'] ?? '');
    
    $opening_balance = intval($_POST['opening_balance'] ?? 0);
    $received_doses = intval($_POST['received_doses'] ?? 0);
    $used_doses = intval($_POST['used_doses'] ?? 0);
    $spoilt_damaged_doses = intval($_POST['spoilt_damaged_doses'] ?? 0);
    $transferred_doses = intval($_POST['transferred_doses'] ?? 0);
    $closing_balance = intval($_POST['closing_balance'] ?? 0);
    
    $batch_no = !empty($_POST['batch_no']) ? trim($_POST['batch_no']) : null;
    $expiry_date = !empty($_POST['expiry_date']) ? trim($_POST['expiry_date']) : null;
    $remarks = !empty($_POST['remarks']) ? trim($_POST['remarks']) : null;

    if (empty($vaccine_name) || empty($report_month)) {
        $_SESSION['msg'] = "Vaccine Name and Report Month cannot be empty.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../vaccine_balance.php?status=db_error");
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
        header("Location: ../vaccine_balance.php?status=db_error");
        exit();
    }

    $insert_sql = "
        INSERT INTO monthly_vaccine_balances (
            district_id, range_id, report_year, report_month, vaccine_name,
            opening_balance, received_doses, used_doses, spoilt_damaged_doses, 
            transferred_doses, closing_balance, batch_no, expiry_date, remarks, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $mysqli->prepare($insert_sql);
    if ($stmt) {
        $stmt->bind_param(
            "iiiisiiiiissssi", 
            $district_id, $range_id, $report_year, $report_month, $vaccine_name,
            $opening_balance, $received_doses, $used_doses, $spoilt_damaged_doses,
            $transferred_doses, $closing_balance, $batch_no, $expiry_date, $remarks, $user_id
        );
        if ($stmt->execute()) {
            $_SESSION['msg'] = "Vaccine Balance record saved successfully.";
            $_SESSION['msg_type'] = "success";
            header("Location: ../vaccine_balance.php?status=success");
            exit();
        } else {
            $_SESSION['msg'] = "Database error: " . $stmt->error;
            $_SESSION['msg_type'] = "danger";
            header("Location: ../vaccine_balance.php?status=db_error");
            exit();
        }
        $stmt->close();
    } else {
        $_SESSION['msg'] = "Database statement preparation failed.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../vaccine_balance.php?status=db_error");
        exit();
    }
}

header("Location: ../vaccine_balance.php");
exit();
?>
