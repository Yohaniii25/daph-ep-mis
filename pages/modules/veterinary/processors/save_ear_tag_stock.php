<?php
session_start();
require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['veterinary_surgeon', 'sms'])) {
    header("Location: ../../../../index.php");
    exit();
}

$user_id  = $_SESSION['user_id'] ?? null;
$range_id = $_SESSION['range_id'] ?? null;

if (empty($range_id)) {
    header("Location: ../ear_tag_balance.php?tab=summary&status=error&msg=No+range+context");
    exit();
}

// Fetch district_id
$district_id = null;
$dist_stmt = $mysqli->prepare("SELECT district_id FROM veterinary_ranges WHERE id = ?");
if ($dist_stmt) {
    $dist_stmt->bind_param("i", $range_id);
    $dist_stmt->execute();
    $dist_res = $dist_stmt->get_result();
    if ($d_row = $dist_res->fetch_assoc()) {
        $district_id = $d_row['district_id'];
    }
    $dist_stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../ear_tag_balance.php?tab=summary");
    exit();
}

$stock_id        = intval($_POST['stock_id'] ?? 0);
$report_year     = intval($_POST['report_year'] ?? date('Y'));
$report_month    = intval($_POST['report_month'] ?? date('n'));
$opening_balance = intval($_POST['opening_balance'] ?? 0);
$received_qty    = intval($_POST['received_qty'] ?? 0);
$transferred_qty = intval($_POST['transferred_qty'] ?? 0);

// Calculate dynamic real-time tags used and spoiled from ear_tag_programs for this month
$sum_stmt = $mysqli->prepare("
    SELECT COALESCE(SUM(tags_used), 0) AS total_used, 
           COALESCE(SUM(tags_spoiled), 0) AS total_spoiled 
    FROM ear_tag_programs 
    WHERE range_id = ? AND YEAR(program_date) = ? AND MONTH(program_date) = ?
");
$used_qty = 0;
$spoilt_qty = 0;
if ($sum_stmt) {
    $sum_stmt->bind_param("iii", $range_id, $report_year, $report_month);
    $sum_stmt->execute();
    $sum_res = $sum_stmt->get_result();
    if ($s_row = $sum_res->fetch_assoc()) {
        $used_qty   = intval($s_row['total_used']);
        $spoilt_qty = intval($s_row['total_spoiled']);
    }
    $sum_stmt->close();
}

$closing_balance = ($opening_balance + $received_qty) - ($used_qty + $spoilt_qty + $transferred_qty);

// Check if a record already exists for this range, year, and month
$chk = $mysqli->prepare("SELECT id FROM ear_tag_usage WHERE range_id = ? AND report_year = ? AND report_month = ?");
$existing_id = null;
if ($chk) {
    $chk->bind_param("iii", $range_id, $report_year, $report_month);
    $chk->execute();
    $chk_res = $chk->get_result();
    if ($c_row = $chk_res->fetch_assoc()) {
        $existing_id = $c_row['id'];
    }
    $chk->close();
}

if ($existing_id) {
    $upd = $mysqli->prepare("
        UPDATE ear_tag_usage 
        SET opening_balance = ?, received_qty = ?, used_qty = ?, spoilt_qty = ?, transferred_qty = ?, closing_balance = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    if ($upd) {
        $upd->bind_param("iiiiiii", $opening_balance, $received_qty, $used_qty, $spoilt_qty, $transferred_qty, $closing_balance, $existing_id);
        $upd->execute();
        $upd->close();
    }
} else {
    $ins = $mysqli->prepare("
        INSERT INTO ear_tag_usage 
        (district_id, range_id, report_year, report_month, opening_balance, received_qty, used_qty, spoilt_qty, transferred_qty, closing_balance, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if ($ins) {
        $ins->bind_param("iiiiiiiiiii", $district_id, $range_id, $report_year, $report_month, $opening_balance, $received_qty, $used_qty, $spoilt_qty, $transferred_qty, $closing_balance, $user_id);
        $ins->execute();
        $ins->close();
    }
}

header("Location: ../ear_tag_balance.php?tab=summary&year=$report_year&status=success&msg=Monthly+Stock+Updated+Successfully");
exit();
