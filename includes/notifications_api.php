<?php
/**
 * includes/notifications_api.php
 * AJAX endpoint to fetch notifications and mark as read
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
    echo json_encode(['success' => $ok, 'unread_count' => $unread_count]);
    exit();
}

if ($action === 'fetch') {
    $limit = isset($_GET['limit']) ? max(1, min(50, intval($_GET['limit']))) : 8;
    $notifications = get_user_notifications($mysqli, $user_id, $limit);
    $unread_count = get_unread_notification_count($mysqli, $user_id);
    echo json_encode([
        'success' => true,
        'unread_count' => $unread_count,
        'notifications' => $notifications
    ]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit();
