<?php
/**
 * pages/modules/pd/processors/process_approval.php
 * AJAX endpoint for Provincial Director to Approve or Reject pending edits
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once '../../../../config/db_connect.php';
require_once '../../../../includes/approval_helper.php';

$user_role = $_SESSION['role'] ?? '';
$user_id   = intval($_SESSION['user_id'] ?? 0);

if (!isset($_SESSION['user_id']) || !in_array($user_role, ['provincial_director', 'deputy_director_hq_1', 'deputy_director_hq_2'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Only Provincial Director can authorize staged changes.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = trim($_POST['action'] ?? '');
    $approval_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $reason      = trim($_POST['reason'] ?? '');

    if (!$approval_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid approval request identifier.']);
        exit();
    }

    if ($action === 'approve') {
        $result = approve_pending_edit($mysqli, $approval_id, $user_id);
        $result['pending_count'] = get_pending_approvals_count($mysqli);
        echo json_encode($result);
        exit();
    } elseif ($action === 'reject') {
        $result = reject_pending_edit($mysqli, $approval_id, $user_id, $reason);
        $result['pending_count'] = get_pending_approvals_count($mysqli);
        echo json_encode($result);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid approval action specified.']);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}
