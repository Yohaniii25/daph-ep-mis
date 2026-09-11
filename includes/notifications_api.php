<?php
/**
 * includes/notifications_api.php
 * Comprehensive AJAX endpoint to fetch notifications, filter by type/status, and mark as read
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/notification_helper.php';

$user_id = intval($_SESSION['user_id']);
$action = $_POST['action'] ?? $_GET['action'] ?? 'fetch';

if ($action === 'mark_read') {
    $notification_id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : null);
    $ok = mark_notification_as_read($mysqli, $user_id, $notification_id);
    $unread_count = get_unread_notification_count($mysqli, $user_id);
    $counts = get_notification_type_counts($mysqli, $user_id);
    echo json_encode([
        'success' => $ok,
        'is_read' => 1,
        'unread_count' => $unread_count,
        'counts' => $counts
    ]);
    exit();
}

if ($action === 'mark_unread') {
    $notification_id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : null);
    $ok = mark_notification_as_unread($mysqli, $user_id, $notification_id);
    $unread_count = get_unread_notification_count($mysqli, $user_id);
    $counts = get_notification_type_counts($mysqli, $user_id);
    echo json_encode([
        'success' => $ok,
        'is_read' => 0,
        'unread_count' => $unread_count,
        'counts' => $counts
    ]);
    exit();
}

if ($action === 'toggle_read') {
    $notification_id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : null);
    $res = toggle_notification_read_status($mysqli, $user_id, $notification_id);
    $unread_count = get_unread_notification_count($mysqli, $user_id);
    $counts = get_notification_type_counts($mysqli, $user_id);
    echo json_encode([
        'success' => $res['success'] ?? false,
        'is_read' => $res['is_read'] ?? 0,
        'unread_count' => $unread_count,
        'counts' => $counts
    ]);
    exit();
}

if ($action === 'delete') {
    $notification_id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : null);
    $ok = delete_notification($mysqli, $user_id, $notification_id);
    $unread_count = get_unread_notification_count($mysqli, $user_id);
    $counts = get_notification_type_counts($mysqli, $user_id);
    echo json_encode([
        'success' => $ok,
        'unread_count' => $unread_count,
        'counts' => $counts
    ]);
    exit();
}

if ($action === 'delete_all_read') {
    $ok = delete_all_read_notifications($mysqli, $user_id);
    $unread_count = get_unread_notification_count($mysqli, $user_id);
    $counts = get_notification_type_counts($mysqli, $user_id);
    echo json_encode([
        'success' => $ok,
        'unread_count' => $unread_count,
        'counts' => $counts
    ]);
    exit();
}

if ($action === 'fetch') {
    $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 50;
    $offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;
    $filter_type = trim($_GET['type'] ?? 'all');
    $unread_only = !empty($_GET['unread_only']);

    $notifications = get_filtered_notifications($mysqli, $user_id, $filter_type, $unread_only, $limit, $offset);
    $unread_count = get_unread_notification_count($mysqli, $user_id);
    $counts = get_notification_type_counts($mysqli, $user_id);

    echo json_encode([
        'success' => true,
        'unread_count' => $unread_count,
        'counts' => $counts,
        'notifications' => $notifications
    ]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit();
