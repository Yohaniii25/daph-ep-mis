<?php
/**
 * pages/modules/hr/processors/process_transfer.php
 * AJAX endpoint for Administrator & Provincial Executives to Approve or Reject employee transfer requests
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once '../../../../config/db_connect.php';
require_once '../../../../includes/approval_helper.php';

$user_role = $_SESSION['role'] ?? '';
$user_id   = intval($_SESSION['user_id'] ?? 0);

$allowed_roles = ['administrator', 'provincial_director', 'deputy_director_hq_1', 'deputy_director_hq_2'];
if (!isset($_SESSION['user_id']) || !in_array($user_role, $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Only Provincial Administration can authorize transfers.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = trim($_POST['action'] ?? '');
    $approval_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $reason      = trim($_POST['reason'] ?? '');

    if (!$approval_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid transfer request identifier.']);
        exit();
    }

    if ($action === 'approve') {
        $result = approve_pending_edit($mysqli, $approval_id, $user_id);
        $result['pending_transfers_count'] = get_pending_transfers_count($mysqli);
        $result['pending_all_count']       = get_pending_approvals_count($mysqli);
        echo json_encode($result);
        exit();
    } elseif ($action === 'reject') {
        $result = reject_pending_edit($mysqli, $approval_id, $user_id, $reason);
        $result['pending_transfers_count'] = get_pending_transfers_count($mysqli);
        $result['pending_all_count']       = get_pending_approvals_count($mysqli);
        echo json_encode($result);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}
