<?php
session_start();
require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
           (isset($_POST['is_ajax']) && $_POST['is_ajax'] == '1');

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['veterinary_surgeon', 'sms'])) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
        exit();
    }
    header("Location: ../../../../index.php");
    exit();
}

$range_id = $_SESSION['range_id'] ?? null;
$user_id  = $_SESSION['user_id'] ?? null;
$id       = intval($_POST['id'] ?? $_GET['id'] ?? 0);

if ($id <= 0 || empty($range_id)) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid program ID or range context.']);
        exit();
    }
    header("Location: ../ear_tag_balance.php?tab=programs&status=error&msg=Invalid+request");
    exit();
}

$mysqli->begin_transaction();

try {
    // 1. Fetch program info before deletion
    $stmt = $mysqli->prepare("SELECT program_date, district_id FROM ear_tag_programs WHERE id = ? AND range_id = ?");
    if (!$stmt) {
        throw new Exception("Error preparing query: " . $mysqli->error);
    }
    $stmt->bind_param("ii", $id, $range_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prog = $res->fetch_assoc();
    $stmt->close();

    if (!$prog) {
        throw new Exception("Program record not found or access denied.");
    }

    $program_date = $prog['program_date'];
    $district_id  = $prog['district_id'];

    // 2. Delete linked cattle vouchers
    $del_v = $mysqli->prepare("DELETE FROM cattle_vouchers WHERE tag_program_id = ?");
    if ($del_v) {
        $del_v->bind_param("i", $id);
        $del_v->execute();
        $del_v->close();
    }

    // 3. Delete tagging program record
    $del_p = $mysqli->prepare("DELETE FROM ear_tag_programs WHERE id = ? AND range_id = ?");
    if (!$del_p) {
        throw new Exception("Error preparing delete: " . $mysqli->error);
    }
    $del_p->bind_param("ii", $id, $range_id);
    if (!$del_p->execute()) {
        throw new Exception("Error deleting program: " . $del_p->error);
    }
    $del_p->close();

    // 4. Synchronize month's usage in ear_tag_usage
    $ts = strtotime($program_date);
    $report_year  = intval(date('Y', $ts));
    $report_month = intval(date('n', $ts));

    $sum_stmt = $mysqli->prepare("
        SELECT COALESCE(SUM(tags_used), 0) AS total_used, 
               COALESCE(SUM(tags_spoiled), 0) AS total_spoiled 
        FROM ear_tag_programs 
        WHERE range_id = ? AND YEAR(program_date) = ? AND MONTH(program_date) = ?
    ");
    $total_used = 0;
    $total_spoiled = 0;
    if ($sum_stmt) {
        $sum_stmt->bind_param("iii", $range_id, $report_year, $report_month);
        $sum_stmt->execute();
        $sum_res = $sum_stmt->get_result();
        if ($s_row = $sum_res->fetch_assoc()) {
            $total_used = intval($s_row['total_used']);
            $total_spoiled = intval($s_row['total_spoiled']);
        }
        $sum_stmt->close();
    }

    // Update monthly usage row
    $upd_u = $mysqli->prepare("
        UPDATE ear_tag_usage 
        SET used_qty = ?, spoilt_qty = ?, 
            closing_balance = (opening_balance + received_qty) - (? + ? + transferred_qty), 
            updated_at = NOW() 
        WHERE range_id = ? AND report_year = ? AND report_month = ?
    ");
    if ($upd_u) {
        $upd_u->bind_param("iiiiiii", $total_used, $total_spoiled, $total_used, $total_spoiled, $range_id, $report_year, $report_month);
        $upd_u->execute();
        $upd_u->close();
    }

    $mysqli->commit();

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Tagging program deleted successfully.']);
        exit();
    }

    header("Location: ../ear_tag_balance.php?tab=programs&status=success&msg=Program+Deleted+Successfully");
    exit();

} catch (Exception $e) {
    $mysqli->rollback();
    $err = $e->getMessage();
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $err]);
        exit();
    }
    header("Location: ../ear_tag_balance.php?tab=programs&status=error&msg=" . urlencode($err));
    exit();
}
