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
    
    $report_year = intval($_POST['report_year'] ?? date('Y'));
    $report_month = intval($_POST['report_month'] ?? 1);
    $vaccine_name = trim($_POST['vaccine_name'] ?? '');
    
    $opening_balance = intval($_POST['opening_balance'] ?? 0);
    $received_doses = intval($_POST['received_doses'] ?? 0);
    $used_doses = intval($_POST['used_doses'] ?? 0);
    $spoilt_damaged_doses = intval($_POST['spoilt_damaged_doses'] ?? 0);
    $transferred_doses = intval($_POST['transferred_doses'] ?? 0);
    
    $batch_no = !empty($_POST['batch_no']) ? trim($_POST['batch_no']) : null;
    $expiry_date = !empty($_POST['expiry_date']) ? trim($_POST['expiry_date']) : null;
    $remarks = !empty($_POST['remarks']) ? trim($_POST['remarks']) : null;

    $post_range_id = intval($_POST['range_id'] ?? 0);
    $session_range_id = intval($_SESSION['range_id'] ?? 0);
    $range_id = ($post_range_id > 0) ? $post_range_id : $session_range_id;

    if (empty($vaccine_name) || empty($report_month)) {
        $_SESSION['msg'] = "Vaccine Name and Report Month cannot be empty.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../vaccine_balance.php?year={$report_year}&range_id={$range_id}&status=db_error");
        exit();
    }

    // Resolve Range and District IDs
    if ($range_id <= 0) {
        $user_stmt = $mysqli->prepare("SELECT range_id, district_id FROM users WHERE id = ?");
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

    if ($range_id <= 0) {
        $_SESSION['msg'] = "Error: Please select a valid Veterinary Range.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../vaccine_balance.php?year={$report_year}&status=db_error");
        exit();
    }

    // Resolve District ID from Range
    $district_id = null;
    $d_stmt = $mysqli->prepare("SELECT district_id FROM veterinary_ranges WHERE id = ?");
    if ($d_stmt) {
        $d_stmt->bind_param("i", $range_id);
        $d_stmt->execute();
        $d_res = $d_stmt->get_result()->fetch_assoc();
        if ($d_res) {
            $district_id = intval($d_res['district_id']);
        }
        $d_stmt->close();
    }
    if (!$district_id) {
        $district_id = intval($_SESSION['district_id'] ?? 1);
    }

    // If batch_no provided and expiry_date empty, auto-populate from vaccine_batches
    if (!empty($batch_no) && empty($expiry_date)) {
        $b_stmt = $mysqli->prepare("SELECT expiry_date FROM vaccine_batches WHERE batch_number = ? LIMIT 1");
        if ($b_stmt) {
            $b_stmt->bind_param("s", $batch_no);
            $b_stmt->execute();
            $b_res = $b_stmt->get_result()->fetch_assoc();
            if ($b_res && !empty($b_res['expiry_date'])) {
                $expiry_date = $b_res['expiry_date'];
            }
            $b_stmt->close();
        }
    }

    // Prior Month Opening Balance Automation:
    // If opening_balance was 0 or not manually altered, check prior month's closing balance
    if ($opening_balance <= 0) {
        $prior_month = ($report_month == 1) ? 12 : ($report_month - 1);
        $prior_year = ($report_month == 1) ? ($report_year - 1) : $report_year;

        $pm_stmt = $mysqli->prepare("SELECT closing_balance, batch_no, expiry_date FROM monthly_vaccine_balances WHERE range_id = ? AND vaccine_name = ? AND report_year = ? AND report_month = ? ORDER BY id DESC LIMIT 1");
        if ($pm_stmt) {
            $pm_stmt->bind_param("isii", $range_id, $vaccine_name, $prior_year, $prior_month);
            $pm_stmt->execute();
            $pm_res = $pm_stmt->get_result()->fetch_assoc();
            if ($pm_res) {
                $opening_balance = intval($pm_res['closing_balance']);
                if (empty($batch_no) && !empty($pm_res['batch_no'])) {
                    $batch_no = $pm_res['batch_no'];
                }
                if (empty($expiry_date) && !empty($pm_res['expiry_date'])) {
                    $expiry_date = $pm_res['expiry_date'];
                }
            }
            $pm_stmt->close();
        }
    }

    // Compute closing balance
    $closing_balance = $opening_balance + $received_doses - $used_doses - $spoilt_damaged_doses - $transferred_doses;
    if ($closing_balance < 0) {
        $closing_balance = 0;
    }

    // Insert record
    $insert_sql = "
        INSERT INTO monthly_vaccine_balances (
            district_id, range_id, report_year, report_month, vaccine_name,
            opening_balance, received_doses, used_doses, spoilt_damaged_doses,
            transferred_doses, closing_balance, batch_no, expiry_date, remarks,
            created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $mysqli->prepare($insert_sql);
    if ($stmt) {
        $stmt->bind_param(
            "iiiisiiiiiisssi",
            $district_id, $range_id, $report_year, $report_month, $vaccine_name,
            $opening_balance, $received_doses, $used_doses, $spoilt_damaged_doses,
            $transferred_doses, $closing_balance, $batch_no, $expiry_date, $remarks,
            $user_id
        );

        if ($stmt->execute()) {
            $_SESSION['msg'] = "Vaccine Balance for " . htmlspecialchars($vaccine_name) . " successfully saved.";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Database insertion error: " . $stmt->error;
            $_SESSION['msg_type'] = "danger";
        }
        $stmt->close();
    } else {
        header("Location: ../vaccine_balance.php?status=db_error");
        exit();
    }
}

header("Location: ../vaccine_balance.php");
exit();
?>
