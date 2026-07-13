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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id           = intval($_POST['id'] ?? 0);
    $report_year  = intval($_POST['report_year'] ?? date('Y'));
    $report_month = intval($_POST['report_month'] ?? date('m'));
    
    $vs_tech_code = trim($_POST['vs_tech_code'] ?? '');
    $ai_date      = trim($_POST['ai_date'] ?? '');
    $cow_id       = trim($_POST['cow_id'] ?? '');
    $pd_date      = trim($_POST['pd_date'] ?? '');
    $result       = trim($_POST['result'] ?? '');

    if (empty($report_year) || empty($report_month) || empty($vs_tech_code) || empty($cow_id) || empty($pd_date) || empty($result)) {
        header("Location: ../pd_performance.php?status=error&msg=Missing+required+fields");
        exit();
    }

    // Treat empty AI Date string as database NULL
    $ai_date_val = !empty($ai_date) ? $ai_date : null;

    if ($action === 'create') {
        if (empty($range_id)) {
            header("Location: ../pd_performance.php?status=error&msg=Invalid+surgeon+range+context");
            exit();
        }

        $stmt = $mysqli->prepare("INSERT INTO `breeding_pd_performance` 
            (range_id, report_year, report_month, vs_tech_code, ai_date, cow_id, pd_date, result, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            header("Location: ../pd_performance.php?status=error&msg=" . urlencode($mysqli->error));
            exit();
        }
        $stmt->bind_param("iiisssssi", $range_id, $report_year, $report_month, $vs_tech_code, $ai_date_val, $cow_id, $pd_date, $result, $user_id);
        
        if ($stmt->execute()) {
            header("Location: ../pd_performance.php?status=success&msg=Record+Created+Successfully");
            exit();
        } else {
            header("Location: ../pd_performance.php?status=error&msg=" . urlencode($stmt->error));
            exit();
        }

    } elseif ($action === 'update' && $id > 0) {
        $stmt = $mysqli->prepare("UPDATE `breeding_pd_performance` SET 
            report_year = ?, 
            report_month = ?, 
            vs_tech_code = ?, 
            ai_date = ?, 
            cow_id = ?, 
            pd_date = ?, 
            result = ? 
            WHERE id = ? AND range_id = ?");
        if (!$stmt) {
            header("Location: ../pd_performance.php?status=error&msg=" . urlencode($mysqli->error));
            exit();
        }
        $stmt->bind_param("iisssssii", $report_year, $report_month, $vs_tech_code, $ai_date_val, $cow_id, $pd_date, $result, $id, $range_id);
        
        if ($stmt->execute()) {
            header("Location: ../pd_performance.php?status=success&msg=Record+Updated+Successfully");
            exit();
        } else {
            header("Location: ../pd_performance.php?status=error&msg=" . urlencode($stmt->error));
            exit();
        }
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        header("Location: ../pd_performance.php?status=error&msg=Invalid+Record+ID");
        exit();
    }

    $stmt = $mysqli->prepare("DELETE FROM `breeding_pd_performance` WHERE id = ? AND range_id = ?");
    if (!$stmt) {
        header("Location: ../pd_performance.php?status=error&msg=" . urlencode($mysqli->error));
        exit();
    }
    $stmt->bind_param("ii", $id, $range_id);
    
    if ($stmt->execute()) {
        header("Location: ../pd_performance.php?status=success&msg=Record+Deleted");
        exit();
    } else {
        header("Location: ../pd_performance.php?status=error&msg=" . urlencode($stmt->error));
        exit();
    }
}

header("Location: ../pd_performance.php");
exit();
?>
