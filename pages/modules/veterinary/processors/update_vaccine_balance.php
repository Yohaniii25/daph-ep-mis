<?php
session_start();
require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$allowed_roles = [
    'veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon',
    'district_dd', 'deputy_director_district', 'sms', 'provincial_director', 'administrator'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true) || !isset($_SESSION['user_id'])) {
    header("Location: ../../../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $id = intval($_POST['id'] ?? 0);
    
    $report_year = intval($_POST['report_year'] ?? date('Y'));
    $report_month = intval($_POST['report_month'] ?? 1);
    $vaccine_name = trim($_POST['vaccine_name'] ?? '');
    
    $opening_balance = intval($_POST['opening_balance'] ?? 0);
    $received_doses = intval($_POST['received_doses'] ?? 0);
    $used_doses = intval($_POST['used_doses'] ?? 0);
    $spoilt_damaged_doses = intval($_POST['spoilt_damaged_doses'] ?? 0);
    $transferred_doses = intval($_POST['transferred_doses'] ?? 0);
    $closing_balance = $opening_balance + $received_doses - $used_doses - $spoilt_damaged_doses - $transferred_doses;
    if ($closing_balance < 0) $closing_balance = 0;
    
    $batch_no = !empty($_POST['batch_no']) ? trim($_POST['batch_no']) : null;
    $expiry_date = !empty($_POST['expiry_date']) ? trim($_POST['expiry_date']) : null;
    $remarks = !empty($_POST['remarks']) ? trim($_POST['remarks']) : null;

    $post_range_id = intval($_POST['range_id'] ?? 0);
    $session_range_id = intval($_SESSION['range_id'] ?? 0);
    $range_id = ($post_range_id > 0) ? $post_range_id : $session_range_id;

    if (empty($id) || empty($vaccine_name) || empty($report_month)) {
        $_SESSION['msg'] = "Error: Invalid inputs entered.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../vaccine_balance.php?status=db_error");
        exit();
    }

    if (empty($range_id)) {
        $user_stmt = $mysqli->prepare("SELECT range_id FROM users WHERE id = ?");
        if ($user_stmt) {
            $user_stmt->bind_param("i", $user_id);
            $user_stmt->execute();
            $user_res = $user_stmt->get_result()->fetch_assoc();
            if ($user_res && !empty($user_res['range_id'])) {
                $range_id = intval($user_res['range_id']);
            }
            $user_stmt->close();
        }
    }

    if (empty($range_id)) {
        $_SESSION['msg'] = "Error: You are not assigned to a range.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../vaccine_balance.php?status=db_error");
        exit();
    }

    $update_sql = "
        UPDATE monthly_vaccine_balances 
        SET report_year = ?, report_month = ?, vaccine_name = ?, 
            opening_balance = ?, received_doses = ?, used_doses = ?, 
            spoilt_damaged_doses = ?, transferred_doses = ?, closing_balance = ?, 
            batch_no = ?, expiry_date = ?, remarks = ?
        WHERE id = ? AND range_id = ?
    ";

    $stmt = $mysqli->prepare($update_sql);
    if ($stmt) {
        $stmt->bind_param(
            "iisiiiiisssii",
            $report_year, $report_month, $vaccine_name,
            $opening_balance, $received_doses, $used_doses,
            $spoilt_damaged_doses, $transferred_doses, $closing_balance,
            $batch_no, $expiry_date, $remarks,
            $id, $range_id
        );
        if ($stmt->execute()) {
            $_SESSION['msg'] = "Vaccine Balance record updated successfully.";
            $_SESSION['msg_type'] = "success";
            header("Location: ../vaccine_balance.php?status=success");
        } else {
            $_SESSION['msg'] = "Database error: " . $stmt->error;
            $_SESSION['msg_type'] = "danger";
            header("Location: ../vaccine_balance.php?status=db_error");
        }
        $stmt->close();
    } else {
        $_SESSION['msg'] = "Database statement preparation failed.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../vaccine_balance.php?status=db_error");
    }
} else {
    header("Location: ../vaccine_balance.php");
}
exit();
?>
