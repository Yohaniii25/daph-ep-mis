<?php
session_start();
require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon' || !isset($_SESSION['user_id'])) {
    header("Location: ../../../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    $report_year = intval($_POST['report_year'] ?? date('Y'));
    $report_month = intval($_POST['report_month'] ?? 1);
    $item_name = trim($_POST['item_name'] ?? '');
    $quantity_sold = intval($_POST['quantity_sold'] ?? 0);
    $unit_price = floatval($_POST['unit_price'] ?? 0.00);
    $total_amount = floatval($_POST['total_amount'] ?? 0.00);
    $amount_deposited = floatval($_POST['amount_deposited'] ?? 0.00);

    if (empty($item_name) || empty($report_month)) {
        $_SESSION['msg'] = "Item Name and Report Month cannot be empty.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../cash_book_summary.php?status=db_error");
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
        header("Location: ../cash_book_summary.php?status=db_error");
        exit();
    }

    require_once __DIR__ . '/../../../../includes/counterfoil_module_helper.php';

    $receipt_date = !empty($_POST['receipt_date']) ? $_POST['receipt_date'] : sprintf('%04d-%02d-%02d', $report_year, $report_month, min(intval(date('d')), 28));
    $receipt_no = !empty($_POST['receipt_no']) ? trim($_POST['receipt_no']) : ('CR-' . date('y') . '/' . sprintf('%02d', $report_month) . '/' . rand(1000, 9999));
    $client_name = !empty($_POST['client_name']) ? trim($_POST['client_name']) : 'General Client';
    $client_nic = trim($_POST['client_nic'] ?? '');

    list($mapped_tab, $canonical_item) = map_to_canonical_item($item_name);

    // Locate parent counterfoil book ID if available
    $parent_cf_id = 0;
    $cf_b_stmt = $mysqli->prepare("SELECT id FROM counterfoil_assets WHERE range_id = ? AND (counterfoil_type LIKE '%Cash Receipt%' OR counterfoil_type LIKE '%Receipt%') AND is_active = 1 LIMIT 1");
    if ($cf_b_stmt) {
        $cf_b_stmt->bind_param("i", $range_id);
        $cf_b_stmt->execute();
        $cf_b_res = $cf_b_stmt->get_result();
        if ($cf_b_row = $cf_b_res->fetch_assoc()) {
            $parent_cf_id = intval($cf_b_row['id']);
        }
        $cf_b_stmt->close();
    }

    // Persist to counterfoil_leaf_issues
    $leaf_ins = $mysqli->prepare("
        INSERT INTO counterfoil_leaf_issues 
        (counterfoil_id, district_id, range_id, counterfoil_type, leaf_serial_no, farmer_nic, farmer_name, issue_date, purpose, amount, quantity, unit_price, amount_deposited, revenue_item, category_tab, remarks, issued_by)
        VALUES (?, ?, ?, 'Cash Receipt Book', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Logged via Cash Book Summary', ?)
    ");
    $inserted_leaf_id = null;
    if ($leaf_ins) {
        $leaf_ins->bind_param(
            "iiisssssdiddssi",
            $parent_cf_id,
            $district_id,
            $range_id,
            $receipt_no,
            $client_nic,
            $client_name,
            $receipt_date,
            $item_name,
            $total_amount,
            $quantity_sold,
            $unit_price,
            $amount_deposited,
            $canonical_item,
            $mapped_tab,
            $user_id
        );
        if ($leaf_ins->execute()) {
            $inserted_leaf_id = $leaf_ins->insert_id;
        }
        $leaf_ins->close();
    }

    $insert_sql = "
        INSERT INTO cash_book_summaries (
            district_id, range_id, report_year, report_month, receipt_no, receipt_date, client_name, item_name, 
            quantity_sold, unit_price, total_amount, amount_deposited, leaf_issue_id, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $mysqli->prepare($insert_sql);
    if ($stmt) {
        $stmt->bind_param(
            "iiiissssidddii", 
            $district_id, $range_id, $report_year, $report_month, $receipt_no, $receipt_date, $client_name, $canonical_item,
            $quantity_sold, $unit_price, $total_amount, $amount_deposited, $inserted_leaf_id, $user_id
        );
        $from_month = intval($_POST['from_month'] ?? 1);
        $to_month = intval($_POST['to_month'] ?? 12);
        $active_tab = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['active_tab'] ?? '');
        $redirect_query = "?status=success&year=" . urlencode($report_year) . "&from_month=" . urlencode($from_month) . "&to_month=" . urlencode($to_month);
        if (!empty($active_tab)) {
            $redirect_query .= "&tab=" . urlencode($active_tab);
        }

        if ($stmt->execute()) {
            $_SESSION['msg'] = "Receipt and Cash Book record saved successfully.";
            $_SESSION['msg_type'] = "success";
            header("Location: ../cash_book_summary.php" . $redirect_query);
            exit();
        } else {
            $_SESSION['msg'] = "Database error: " . $stmt->error;
            $_SESSION['msg_type'] = "danger";
            header("Location: ../cash_book_summary.php?status=db_error");
            exit();
        }
        $stmt->close();
    } else {
        $_SESSION['msg'] = "Database statement preparation failed.";
        $_SESSION['msg_type'] = "danger";
        header("Location: ../cash_book_summary.php?status=db_error");
        exit();
    }
}

header("Location: ../cash_book_summary.php");
exit();
?>
