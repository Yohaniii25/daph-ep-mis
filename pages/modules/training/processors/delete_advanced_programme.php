<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../../config/db_connect.php';

header('Content-Type: application/json');

$allowed_roles = ['training_officer', 'administrator', 'provincial_director', 'district_dd'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $training_center_id = intval($_POST['training_center_id'] ?? $_SESSION['training_center_id'] ?? 0);
    $id = intval($_POST['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID specified.']);
        exit();
    }

    if ($training_center_id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM training_advanced_programmes WHERE id = ? AND training_center_id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $id, $training_center_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database execution failed.']);
            }
            $stmt->close();
        }
    } else {
        $stmt = $mysqli->prepare("DELETE FROM training_advanced_programmes WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database execution failed.']);
            }
            $stmt->close();
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
exit();
