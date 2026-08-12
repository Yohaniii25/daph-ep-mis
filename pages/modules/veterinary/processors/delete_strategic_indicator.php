<?php
session_start();
require_once '../../../../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $range_id = $_SESSION['range_id'] ?? null;

    if (!$id || !$range_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameter input.']);
        exit();
    }

    $stmt = $mysqli->prepare("DELETE FROM strategic_action_indicators WHERE id = ? AND range_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $range_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Strategic indicator record deleted successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Record not found or unauthorized.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare delete statement.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
exit();
