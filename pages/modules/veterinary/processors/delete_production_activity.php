<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

header('Content-Type: application/json');

// 1. Authorization Guard Block
$allowed_roles = [
    'veterinary_surgeon',
    'government_veterinary_surgeon',
    'additional_veterinary_surgeon',
    'deputy_director_hq_1',
    'district_dd',
    'deputy_director_district',
    'provincial_director',
    'admin',
    'super_admin'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid activity identifier.']);
    exit();
}

$user_role      = $_SESSION['role'] ?? '';
$is_supervisory = in_array($user_role, ['deputy_director_hq_1', 'district_dd', 'deputy_director_district', 'provincial_director', 'admin', 'super_admin'], true);
$session_range  = $_SESSION['range_id'] ?? null;

// 2. Safety Check: Verify if activity has enrolled beneficiaries
$check_stmt = $mysqli->prepare("SELECT COUNT(*) AS total_ben FROM activity_beneficiaries WHERE activity_id = ?");
if ($check_stmt) {
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $res = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();
    
    $beneficiary_count = intval($res['total_ben'] ?? 0);
    if ($beneficiary_count > 0) {
        echo json_encode([
            'success' => false, 
            'message' => "Cannot delete this activity because {$beneficiary_count} beneficiary record(s) are linked to it. Please remove the beneficiaries first to prevent data loss."
        ]);
        exit();
    }
}

// 3. Execution of Safe Deletion
if (!$is_supervisory && !empty($session_range)) {
    $del_stmt = $mysqli->prepare("DELETE FROM production_activity_targets WHERE id = ? AND range_id = ?");
    if ($del_stmt) {
        $del_stmt->bind_param("ii", $id, $session_range);
    }
} else {
    $del_stmt = $mysqli->prepare("DELETE FROM production_activity_targets WHERE id = ?");
    if ($del_stmt) {
        $del_stmt->bind_param("i", $id);
    }
}

if ($del_stmt) {
    if ($del_stmt->execute()) {
        if ($del_stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Production activity deleted successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Record not found or already deleted.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $del_stmt->error]);
    }
    $del_stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to initialize delete operation.']);
}

$mysqli->close();
exit();
