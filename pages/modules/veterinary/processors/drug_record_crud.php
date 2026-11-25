<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['veterinary_surgeon', 'district_dd', 'admin', 'super_admin'])) {
    http_response_code(403);
    die("Access denied: Invalid authentication clearance profile.");
}

require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user_role = $_SESSION['role'] ?? '';
$user_id = intval($_SESSION['user_id'] ?? 0);
$is_supervisory = in_array($user_role, ['district_dd', 'admin', 'super_admin'], true);

$range_id = intval($_SESSION['range_id'] ?? 0);
if ($is_supervisory && !empty($_REQUEST['range_id'])) {
    $range_id = intval($_REQUEST['range_id']);
}
if ($range_id <= 0 && $user_id > 0) {
    $u_stmt = $mysqli->prepare("SELECT range_id FROM users WHERE id = ? LIMIT 1");
    if ($u_stmt) {
        $u_stmt->bind_param("i", $user_id);
        $u_stmt->execute();
        if ($u_row = $u_stmt->get_result()->fetch_assoc()) {
            $range_id = intval($u_row['range_id'] ?? 0);
        }
        $u_stmt->close();
    }
}
if ($range_id <= 0) {
    die("Error: Veterinary Range ID could not be resolved.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id                     = intval($_POST['id'] ?? 0);
    $log_date               = trim($_POST['log_date'] ?? '');
    $drug_type_id           = intval($_POST['drug_type_id'] ?? $_POST['vaccine_type'] ?? 0);
    $vaccine_batch_id       = intval($_POST['vaccine_batch_id'] ?? 0);
    $starter_count_month    = intval($_POST['starter_count_month'] ?? 0);
    $during_month_received  = intval($_POST['during_month_received'] ?? 0);
    $used_doses_count       = intval($_POST['used_doses_count'] ?? 0);
    $doses_damaged          = intval($_POST['doses_damaged'] ?? 0);

    if (empty($log_date) || $drug_type_id <= 0 || $vaccine_batch_id <= 0) {
        die("Data Validation Error: Crucial record metrics are missing.");
    }

    $default_return = '../drug_maintenance.php?view=maintenance&range_id=' . $range_id;
    $return_url = !empty($_POST['return_url']) ? $_POST['return_url'] : (!empty($_GET['return_url']) ? $_GET['return_url'] : $default_return);
    $separator = (strpos($return_url, '?') !== false) ? '&' : '?';

    if ($action === 'create') {
        $stmt = $mysqli->prepare("INSERT INTO `drug_records` 
            (range_id, log_date, drug_type_id, vaccine_batch_id, starter_count_month, during_month_received, used_doses_count, doses_damaged) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            die("Database Error: " . $mysqli->error);
        }
        $stmt->bind_param("isiiiiii", $range_id, $log_date, $drug_type_id, $vaccine_batch_id, $starter_count_month, $during_month_received, $used_doses_count, $doses_damaged);
        
        if ($stmt->execute()) {
            header("Location: {$return_url}{$separator}status=success&msg=Entry+Logged+Successfully");
            exit();
        } else {
            die("Database Error: Failed to append record trace: " . $mysqli->error);
        }

    } elseif ($action === 'update' && $id > 0) {
        $stmt = $mysqli->prepare("UPDATE `drug_records` SET 
            log_date = ?, 
            drug_type_id = ?, 
            vaccine_batch_id = ?, 
            starter_count_month = ?, 
            during_month_received = ?, 
            used_doses_count = ?, 
            doses_damaged = ? 
            WHERE id = ? AND range_id = ?");
        if (!$stmt) {
            die("Database Error: " . $mysqli->error);
        }
        $stmt->bind_param("siiiiiiii", $log_date, $drug_type_id, $vaccine_batch_id, $starter_count_month, $during_month_received, $used_doses_count, $doses_damaged, $id, $range_id);
        
        if ($stmt->execute()) {
            header("Location: {$return_url}{$separator}status=success&msg=Record+Updated+Successfully");
            exit();
        } else {
            die("Database Error: Failed to rewrite modifications: " . $mysqli->error);
        }
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) die("Error: Missing targeting parameters.");

    $default_return = '../drug_maintenance.php?view=maintenance&range_id=' . $range_id;
    $return_url = !empty($_GET['return_url']) ? $_GET['return_url'] : $default_return;
    $separator = (strpos($return_url, '?') !== false) ? '&' : '?';

    $stmt = $mysqli->prepare("DELETE FROM `drug_records` WHERE id = ? AND range_id = ?");
    if (!$stmt) {
        die("Database Error: " . $mysqli->error);
    }
    $stmt->bind_param("ii", $id, $range_id);
    
    if ($stmt->execute()) {
        header("Location: {$return_url}{$separator}status=success&msg=Record+Purged");
        exit();
    } else {
        die("Database Error: Could not clear ledger row: " . $mysqli->error);
    }
}

header("Location: ../drug_maintenance.php?view=maintenance");
exit();