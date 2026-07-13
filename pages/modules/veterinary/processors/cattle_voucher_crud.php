<?php
session_start();
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['veterinary_surgeon', 'sms'])) {
    die("Access denied: Invalid authentication clearance profile.");
}

require_once '../../../../config/db_connect.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Fetch active coordinator profile context metrics
$user_id = $_SESSION['user_id'] ?? null;
$range_id = $_SESSION['range_id'] ?? null;

// Query to get district_id from veterinary_ranges
$district_id = null;
if (!empty($range_id)) {
    $r_query = $mysqli->prepare("SELECT district_id FROM veterinary_ranges WHERE id = ?");
    if ($r_query) {
        $r_query->bind_param("i", $range_id);
        $r_query->execute();
        $r_result = $r_query->get_result();
        if ($r_row = $r_result->fetch_assoc()) {
            $district_id = $r_row['district_id'];
        }
        $r_query->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id              = intval($_POST['id'] ?? 0);
    $report_year     = intval($_POST['report_year'] ?? date('Y'));
    $report_month    = intval($_POST['report_month'] ?? date('m'));
    $opening_balance = intval($_POST['opening_balance'] ?? 0);
    $received_qty    = intval($_POST['received_qty'] ?? 0);
    $used_qty        = intval($_POST['used_qty'] ?? 0);
    $spoilt_qty      = intval($_POST['spoilt_qty'] ?? 0);
    $transferred_qty = intval($_POST['transferred_qty'] ?? 0);

    // Calculate closing balance: (Opening + Received) - (Used + Spoilt + Transferred)
    $closing_balance = ($opening_balance + $received_qty) - ($used_qty + $spoilt_qty + $transferred_qty);

    if (empty($report_year) || empty($report_month)) {
        header("Location: ../cattle_voucher.php?status=error&msg=Missing+required+fields");
        exit();
    }

    if ($action === 'create') {
        if (empty($district_id) || empty($range_id)) {
            header("Location: ../cattle_voucher.php?status=error&msg=Invalid+surgeon+range+context");
            exit();
        }

        $stmt = $mysqli->prepare("INSERT INTO `cattle_voucher_usage` 
            (district_id, range_id, report_year, report_month, opening_balance, received_qty, used_qty, spoilt_qty, transferred_qty, closing_balance, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            header("Location: ../cattle_voucher.php?status=error&msg=" . urlencode($mysqli->error));
            exit();
        }
        $stmt->bind_param("iiiiiiiiiii", $district_id, $range_id, $report_year, $report_month, $opening_balance, $received_qty, $used_qty, $spoilt_qty, $transferred_qty, $closing_balance, $user_id);
        
        if ($stmt->execute()) {
            header("Location: ../cattle_voucher.php?status=success&msg=Record+Created+Successfully");
            exit();
        } else {
            header("Location: ../cattle_voucher.php?status=error&msg=" . urlencode($stmt->error));
            exit();
        }

    } elseif ($action === 'update' && $id > 0) {
        $stmt = $mysqli->prepare("UPDATE `cattle_voucher_usage` SET 
            report_year = ?, 
            report_month = ?, 
            opening_balance = ?, 
            received_qty = ?, 
            used_qty = ?, 
            spoilt_qty = ?, 
            transferred_qty = ?, 
            closing_balance = ? 
            WHERE id = ? AND range_id = ?");
        if (!$stmt) {
            header("Location: ../cattle_voucher.php?status=error&msg=" . urlencode($mysqli->error));
            exit();
        }
        $stmt->bind_param("iiiiiiiiii", $report_year, $report_month, $opening_balance, $received_qty, $used_qty, $spoilt_qty, $transferred_qty, $closing_balance, $id, $range_id);
        
        if ($stmt->execute()) {
            header("Location: ../cattle_voucher.php?status=success&msg=Record+Updated+Successfully");
            exit();
        } else {
            header("Location: ../cattle_voucher.php?status=error&msg=" . urlencode($stmt->error));
            exit();
        }
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        header("Location: ../cattle_voucher.php?status=error&msg=Invalid+Record+ID");
        exit();
    }

    $stmt = $mysqli->prepare("DELETE FROM `cattle_voucher_usage` WHERE id = ? AND range_id = ?");
    if (!$stmt) {
        header("Location: ../cattle_voucher.php?status=error&msg=" . urlencode($mysqli->error));
        exit();
    }
    $stmt->bind_param("ii", $id, $range_id);
    
    if ($stmt->execute()) {
        header("Location: ../cattle_voucher.php?status=success&msg=Record+Deleted");
        exit();
    } else {
        header("Location: ../cattle_voucher.php?status=error&msg=" . urlencode($stmt->error));
        exit();
    }
}

header("Location: ../cattle_voucher.php");
exit();
