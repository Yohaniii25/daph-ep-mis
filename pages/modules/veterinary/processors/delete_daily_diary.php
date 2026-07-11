<?php
session_start();
require_once '../../../../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon' || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $id = intval($_POST['id'] ?? 0);

    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID specified.']);
        exit();
    }

    $stmt = $mysqli->prepare("DELETE FROM diary_tasks WHERE id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $user_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database execution failed.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Database statement preparation failed.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
exit();
?>
