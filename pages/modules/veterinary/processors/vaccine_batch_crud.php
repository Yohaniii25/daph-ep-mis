<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$allowed_roles = [
    'veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon',
    'sms', 'district_dd', 'deputy_director_district', 'provincial_director', 'administrator'
];

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles, true)) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    die("Access denied");
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || !empty($_POST['ajax']);
$return_url = $_POST['return_url'] ?? $_GET['return_url'] ?? '../batches.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id           = intval($_POST['id'] ?? 0);
    $batch_number = trim($_POST['batch_number'] ?? '');
    $is_active    = intval($_POST['is_active'] ?? 1);
    $remarks      = trim($_POST['remarks'] ?? '');
    $expiry_date  = !empty($_POST['expiry_date']) ? trim($_POST['expiry_date']) : null;
    if ($expiry_date && !strtotime($expiry_date)) {
        $expiry_date = null;
    }

    if (empty($batch_number)) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Batch number cannot be empty']);
            exit;
        }
        header("Location: {$return_url}?status=error&msg=Empty+Batch+Number");
        exit;
    }

    if ($action === 'create') {
        $stmt = $mysqli->prepare("INSERT INTO `vaccine_batches` (batch_number, is_active, remarks, expiry_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siss", $batch_number, $is_active, $remarks, $expiry_date);
        
        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            $stmt->close();
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Batch Registered Successfully',
                    'batch' => [
                        'id' => $new_id,
                        'batch_number' => $batch_number,
                        'expiry_date' => $expiry_date
                    ]
                ]);
                exit;
            }
            header("Location: {$return_url}?status=success&msg=Batch+Registered");
        } else {
            $err = $stmt->error;
            $stmt->close();
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Write Failure: ' . $err]);
                exit;
            }
            header("Location: {$return_url}?status=error&msg=Write+Failure");
        }
        exit;
    } 
    
    elseif ($action === 'update' && $id > 0) {
        $stmt = $mysqli->prepare("UPDATE `vaccine_batches` SET batch_number = ?, is_active = ?, remarks = ?, expiry_date = ? WHERE id = ?");
        $stmt->bind_param("sissi", $batch_number, $is_active, $remarks, $expiry_date, $id);
        
        if ($stmt->execute()) {
            $stmt->close();
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Changes Saved']);
                exit;
            }
            header("Location: {$return_url}?status=success&msg=Changes+Saved");
        } else {
            $stmt->close();
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Update Failure']);
                exit;
            }
            header("Location: {$return_url}?status=error&msg=Update+Failure");
        }
        exit;
    }
} 

// Handle deletions via URL flags
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM `vaccine_batches` WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: {$return_url}?status=success&msg=Entry+Dropped");
        } else {
            $stmt->close();
            header("Location: {$return_url}?status=error&msg=Delete+Failure");
        }
        exit;
    }
}