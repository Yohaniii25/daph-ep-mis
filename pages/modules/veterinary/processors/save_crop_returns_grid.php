<?php
session_start();
require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon' || !isset($_SESSION['range_id'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    header('Location: ../../../../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $range_id     = $_SESSION['range_id'];
    $district_id  = $_SESSION['district_id'] ?? null;
    $report_year  = intval($_POST['report_year'] ?? date('Y'));
    $report_month = intval($_POST['report_month'] ?? date('n'));
    $items        = $_POST['items'] ?? [];

    if (empty($range_id) || empty($report_year) || empty($report_month)) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
            exit();
        }
        header("Location: ../crop_returns.php?year=$report_year&month=$report_month&status=db_error");
        exit();
    }

    // Lookup district_id if missing
    if (empty($district_id)) {
        $stmt_d = $mysqli->prepare("SELECT district_id FROM veterinary_ranges WHERE id = ?");
        if ($stmt_d) {
            $stmt_d->bind_param("i", $range_id);
            $stmt_d->execute();
            $stmt_d->bind_result($district_id);
            $stmt_d->fetch();
            $stmt_d->close();
        }
    }

    $stmt = $mysqli->prepare("
        INSERT INTO crop_returns (
            district_id, range_id, report_year, report_month, item_name, 
            balance_previous_month, received_current_month, issued_current_month, 
            balance_current_month, remark
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            balance_previous_month = VALUES(balance_previous_month),
            received_current_month = VALUES(received_current_month),
            issued_current_month   = VALUES(issued_current_month),
            balance_current_month  = VALUES(balance_current_month),
            remark                 = VALUES(remark),
            updated_at             = NOW()
    ");

    $success_count = 0;
    foreach ($items as $item) {
        $item_name = trim($item['item_name'] ?? '');
        if (empty($item_name)) continue;

        $prev_bal  = intval($item['balance_previous_month'] ?? 0);
        $received  = intval($item['received_current_month'] ?? 0);
        $issued    = intval($item['issued_current_month'] ?? 0);
        $curr_bal  = $prev_bal + $received - $issued;
        $remark    = trim($item['remark'] ?? '');

        if ($stmt) {
            $stmt->bind_param(
                "iiiisiiiis", 
                $district_id, $range_id, $report_year, $report_month, $item_name,
                $prev_bal, $received, $issued, $curr_bal, $remark
            );
            if ($stmt->execute()) {
                $success_count++;
            }
        }
    }
    if ($stmt) $stmt->close();

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['success' => true, 'updated' => $success_count]);
        exit();
    }

    header("Location: ../crop_returns.php?year=$report_year&month=$report_month&status=saved");
    exit();
}

header("Location: ../crop_returns.php");
exit();
