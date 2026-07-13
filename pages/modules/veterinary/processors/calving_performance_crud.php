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
    $id              = intval($_POST['id'] ?? 0);
    $report_year     = intval($_POST['report_year'] ?? date('Y'));
    $report_month    = intval($_POST['report_month'] ?? date('m'));
    
    $technician_code = trim($_POST['technician_code'] ?? '');
    $semen_code      = trim($_POST['semen_code'] ?? '');
    
    $ai_date         = trim($_POST['ai_date'] ?? '');
    $calving_date    = trim($_POST['calving_date'] ?? '');
    $cow_id          = trim($_POST['cow_id'] ?? '');
    $calf_id         = trim($_POST['calf_id'] ?? '');
    $calf_sex        = trim($_POST['calf_sex'] ?? '');

    if (empty($report_year) || empty($report_month) || empty($calving_date) || empty($cow_id) || empty($calf_id) || empty($calf_sex)) {
        header("Location: ../calving_performance.php?status=error&msg=Missing+required+fields");
        exit();
    }

    // Treat empty parameters as NULL
    $tech_code_val = !empty($technician_code) ? $technician_code : null;
    $semen_code_val = !empty($semen_code) ? $semen_code : null;
    $ai_date_val = !empty($ai_date) ? $ai_date : null;

    if ($action === 'create') {
        if (empty($range_id)) {
            header("Location: ../calving_performance.php?status=error&msg=Invalid+surgeon+range+context");
            exit();
        }

        $stmt = $mysqli->prepare("INSERT INTO `breeding_calving_performance` 
            (range_id, report_year, report_month, technician_code, ai_date, semen_code, cow_id, calf_id, calving_date, calf_sex, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            header("Location: ../calving_performance.php?status=error&msg=" . urlencode($mysqli->error));
            exit();
        }
        $stmt->bind_param("iiisssssssi", $range_id, $report_year, $report_month, $tech_code_val, $ai_date_val, $semen_code_val, $cow_id, $calf_id, $calving_date, $calf_sex, $user_id);
        
        if ($stmt->execute()) {
            header("Location: ../calving_performance.php?status=success&msg=Record+Created+Successfully");
            exit();
        } else {
            header("Location: ../calving_performance.php?status=error&msg=" . urlencode($stmt->error));
            exit();
        }

    } elseif ($action === 'update' && $id > 0) {
        $stmt = $mysqli->prepare("UPDATE `breeding_calving_performance` SET 
            report_year = ?, 
            report_month = ?, 
            technician_code = ?, 
            ai_date = ?, 
            semen_code = ?, 
            cow_id = ?, 
            calf_id = ?, 
            calving_date = ?, 
            calf_sex = ? 
            WHERE id = ? AND range_id = ?");
        if (!$stmt) {
            header("Location: ../calving_performance.php?status=error&msg=" . urlencode($mysqli->error));
            exit();
        }
        $stmt->bind_param("iisssssssii", $report_year, $report_month, $tech_code_val, $ai_date_val, $semen_code_val, $cow_id, $calf_id, $calving_date, $calf_sex, $id, $range_id);
        
        if ($stmt->execute()) {
            header("Location: ../calving_performance.php?status=success&msg=Record+Updated+Successfully");
            exit();
        } else {
            header("Location: ../calving_performance.php?status=error&msg=" . urlencode($stmt->error));
            exit();
        }
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        header("Location: ../calving_performance.php?status=error&msg=Invalid+Record+ID");
        exit();
    }

    $stmt = $mysqli->prepare("DELETE FROM `breeding_calving_performance` WHERE id = ? AND range_id = ?");
    if (!$stmt) {
        header("Location: ../calving_performance.php?status=error&msg=" . urlencode($mysqli->error));
        exit();
    }
    $stmt->bind_param("ii", $id, $range_id);
    
    if ($stmt->execute()) {
        header("Location: ../calving_performance.php?status=success&msg=Record+Deleted");
        exit();
    } else {
        header("Location: ../calving_performance.php?status=error&msg=" . urlencode($stmt->error));
        exit();
    }
}

header("Location: ../calving_performance.php");
exit();
?>
