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
$spoilt_qty      = intval($_POST['spoilt_qty'] ?? 0);
$damaged_qty     = intval($_POST['damaged_qty'] ?? 0);
$transferred_qty = intval($_POST['transferred_qty'] ?? 0);

// Calculate dynamic real-time tags used from animal_identifications for this range & month
$ai_used = 0;
$ai_stmt = $mysqli->prepare("SELECT COUNT(*) AS total_used FROM animal_identifications WHERE range_id = ? AND YEAR(identification_date) = ? AND MONTH(identification_date) = ?");
if ($ai_stmt) {
    $ai_stmt->bind_param("iii", $range_id, $report_year, $report_month);
    $ai_stmt->execute();
    $ai_res = $ai_stmt->get_result();
    if ($arow = $ai_res->fetch_assoc()) {
        $ai_used = intval($arow['total_used']);
    }
    $ai_stmt->close();
}

// Also check legacy ear_tag_programs if applicable
$prog_used = 0;
$sum_stmt = $mysqli->prepare("
    SELECT COALESCE(SUM(tags_used), 0) AS total_used 
    FROM ear_tag_programs 
    WHERE range_id = ? AND YEAR(program_date) = ? AND MONTH(program_date) = ?
");
if ($sum_stmt) {
    $sum_stmt->bind_param("iii", $range_id, $report_year, $report_month);
    $sum_stmt->execute();
    $sum_res = $sum_stmt->get_result();
    if ($s_row = $sum_res->fetch_assoc()) {
        $prog_used = intval($s_row['total_used']);
    }
    $sum_stmt->close();
}

$used_qty = max($ai_used, $prog_used);
if ($used_qty === 0 && isset($_POST['used_qty']) && intval($_POST['used_qty']) > 0) {
    $used_qty = intval($_POST['used_qty']);
}

// Calculate dynamic closing balance
$closing_balance = ($opening_balance + $received_qty) - ($used_qty + $spoilt_qty + $damaged_qty + $transferred_qty);

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
        SET opening_balance = ?, received_qty = ?, used_qty = ?, spoilt_qty = ?, damaged_qty = ?, transferred_qty = ?, closing_balance = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    if ($upd) {
        $upd->bind_param("iiiiiiii", $opening_balance, $received_qty, $used_qty, $spoilt_qty, $damaged_qty, $transferred_qty, $closing_balance, $existing_id);
        $upd->execute();
        $upd->close();
    }
} else {
    $ins = $mysqli->prepare("
        INSERT INTO ear_tag_usage 
        (district_id, range_id, report_year, report_month, opening_balance, received_qty, used_qty, spoilt_qty, damaged_qty, transferred_qty, closing_balance, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if ($ins) {
        $ins->bind_param("iiiiiiiiiiii", $district_id, $range_id, $report_year, $report_month, $opening_balance, $received_qty, $used_qty, $spoilt_qty, $damaged_qty, $transferred_qty, $closing_balance, $user_id);
        $ins->execute();
        $ins->close();
    }
}

header("Location: ../ear_tag_balance.php?tab=summary&year=$report_year&status=success&msg=Monthly+Stock+Updated+Successfully");
exit();
