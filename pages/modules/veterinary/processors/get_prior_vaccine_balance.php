<?php
session_start();
require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

header('Content-Type: application/json');

$allowed_roles = [
    'veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon',
    'sms', 'district_dd', 'deputy_director_district', 'provincial_director', 'administrator'
];

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles, true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$range_id = isset($_REQUEST['range_id']) ? intval($_REQUEST['range_id']) : intval($_SESSION['range_id'] ?? 0);
$vaccine_name = trim($_REQUEST['vaccine_name'] ?? '');
$report_year = isset($_REQUEST['report_year']) ? intval($_REQUEST['report_year']) : intval(date('Y'));
$report_month = isset($_REQUEST['report_month']) ? intval($_REQUEST['report_month']) : intval(date('n'));
$batch_no = trim($_REQUEST['batch_no'] ?? '');

$response = [
    'success' => true,
    'prior_closing_balance' => 0,
    'found_prior' => false,
    'batch_no' => $batch_no,
    'expiry_date' => ''
];

// If batch_no was specified, fetch its expiry date
if (!empty($batch_no)) {
    $b_stmt = $mysqli->prepare("SELECT expiry_date FROM vaccine_batches WHERE batch_number = ? LIMIT 1");
    if ($b_stmt) {
        $b_stmt->bind_param("s", $batch_no);
        $b_stmt->execute();
        $b_res = $b_stmt->get_result()->fetch_assoc();
        if ($b_res && !empty($b_res['expiry_date'])) {
            $response['expiry_date'] = $b_res['expiry_date'];
        }
        $b_stmt->close();
    }
}

if (!empty($vaccine_name) && $range_id > 0) {
    // 1. Check exact prior month
    $prior_month = ($report_month == 1) ? 12 : ($report_month - 1);
    $prior_year = ($report_month == 1) ? ($report_year - 1) : $report_year;

    $stmt = $mysqli->prepare("SELECT closing_balance, batch_no, expiry_date FROM monthly_vaccine_balances WHERE range_id = ? AND vaccine_name = ? AND report_year = ? AND report_month = ? ORDER BY id DESC LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("isii", $range_id, $vaccine_name, $prior_year, $prior_month);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $response['prior_closing_balance'] = intval($row['closing_balance']);
            $response['found_prior'] = true;
            if (empty($response['batch_no']) && !empty($row['batch_no'])) {
                $response['batch_no'] = $row['batch_no'];
            }
            if (empty($response['expiry_date']) && !empty($row['expiry_date'])) {
                $response['expiry_date'] = $row['expiry_date'];
            }
        }
        $stmt->close();
    }

    // 2. Fallback: Check latest historical balance prior to this period
    if (!$response['found_prior']) {
        $stmt2 = $mysqli->prepare("SELECT closing_balance, batch_no, expiry_date FROM monthly_vaccine_balances WHERE range_id = ? AND vaccine_name = ? AND (report_year < ? OR (report_year = ? AND report_month < ?)) ORDER BY report_year DESC, report_month DESC, id DESC LIMIT 1");
        if ($stmt2) {
            $stmt2->bind_param("isiii", $range_id, $vaccine_name, $report_year, $report_year, $report_month);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            if ($row2 = $res2->fetch_assoc()) {
                $response['prior_closing_balance'] = intval($row2['closing_balance']);
                $response['found_prior'] = true;
                if (empty($response['batch_no']) && !empty($row2['batch_no'])) {
                    $response['batch_no'] = $row2['batch_no'];
                }
                if (empty($response['expiry_date']) && !empty($row2['expiry_date'])) {
                    $response['expiry_date'] = $row2['expiry_date'];
                }
            }
            $stmt2->close();
        }
    }

    // If batch_no was resolved from prior balance and expiry is empty, lookup batch table
    if (!empty($response['batch_no']) && empty($response['expiry_date'])) {
        $b_stmt2 = $mysqli->prepare("SELECT expiry_date FROM vaccine_batches WHERE batch_number = ? LIMIT 1");
        if ($b_stmt2) {
            $b_stmt2->bind_param("s", $response['batch_no']);
            $b_stmt2->execute();
            $b_res2 = $b_stmt2->get_result()->fetch_assoc();
            if ($b_res2 && !empty($b_res2['expiry_date'])) {
                $response['expiry_date'] = $b_res2['expiry_date'];
            }
            $b_stmt2->close();
        }
    }
}

echo json_encode($response);
