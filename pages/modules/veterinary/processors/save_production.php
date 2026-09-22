<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon' || !isset($_SESSION['user_id'])) {
    header("Location: ../../../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $range_id    = $_SESSION['range_id'] ?? null;
    $category_id = intval($_POST['category_id'] ?? 0);
    $item_id     = intval($_POST['item_id'] ?? 0);
    $amount      = floatval($_POST['amount'] ?? 0);
    $raw_month   = trim($_POST['report_month'] ?? ''); // expects 'YYYY-MM'

    if (empty($range_id)) {
        // Query user's range if session is not configured
        $user_id = $_SESSION['user_id'];
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

    if (empty($range_id) || empty($category_id) || empty($item_id) || empty($raw_month) || $amount < 0) {
        $_SESSION['msg'] = "Error: Invalid inputs entered.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../section_e.php?status=db_error");
        exit();
    }

    // Split year and month
    $date_parts = explode('-', $raw_month);
    if (count($date_parts) !== 2) {
        $_SESSION['msg'] = "Error: Invalid month selection format.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../section_e.php?status=db_error");
        exit();
    }
    $report_year  = intval($date_parts[0]);
    $report_month = intval($date_parts[1]);

    // Query district_id linked to the active vet range
    $district_id = $_SESSION['district_id'] ?? null;
    if (empty($district_id)) {
        $dist_sql = $mysqli->prepare("SELECT district_id FROM veterinary_ranges WHERE id = ?");
        if ($dist_sql) {
            $dist_sql->bind_param("i", $range_id);
            $dist_sql->execute();
            $dist_res = $dist_sql->get_result()->fetch_assoc();
            if ($dist_res) {
                $district_id = $dist_res['district_id'];
                $_SESSION['district_id'] = $district_id;
            }
            $dist_sql->close();
        }
    }

    if (empty($district_id)) {
        $_SESSION['msg'] = "Error: District configuration not found for your range.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../section_e.php?status=db_error");
        exit();
    }

    // Duplicate check for range_id, item_id, report_year, report_month
    $dup_sql = "
        SELECT id FROM section_e 
        WHERE range_id = ? AND item_id = ? AND report_year = ? AND report_month = ?
    ";
    $dup_stmt = $mysqli->prepare($dup_sql);
    if ($dup_stmt) {
        $dup_stmt->bind_param("iiii", $range_id, $item_id, $report_year, $report_month);
        $dup_stmt->execute();
        if ($dup_stmt->get_result()->num_rows > 0) {
            $_SESSION['msg'] = "Error: A record for this item already exists for the selected month.";
            $_SESSION['msg_type'] = "danger";
            $dup_stmt->close();
            header("Location: ../section_e.php?status=db_error");
            exit();
        }
        $dup_stmt->close();
    }

    $insert_sql = "
        INSERT INTO section_e (
            district_id, range_id, report_year, report_month, 
            category_id, item_id, amount
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ";
    
    $stmt = $mysqli->prepare($insert_sql);
    if ($stmt) {
        $stmt->bind_param(
            "iiiiiid", 
            $district_id, $range_id, $report_year, $report_month, 
            $category_id, $item_id, $amount
        );
        if ($stmt->execute()) {
            $_SESSION['msg'] = "Production record matching Section E added successfully.";
            $_SESSION['msg_type'] = "success";
            header("Location: ../section_e.php?status=added");
        } else {
            $_SESSION['msg'] = "Database error: " . $stmt->error;
            $_SESSION['msg_type'] = "danger";
            header("Location: ../section_e.php?status=db_error");
        }
        $stmt->close();
    } else {
        $_SESSION['msg'] = "Database update execution setup failed.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../section_e.php?status=db_error");
    }
} else {
    header("Location: ../section_e.php");
}
exit();
?>