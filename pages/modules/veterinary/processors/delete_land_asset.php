<?php
session_start();
require_once '../../../../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized operation.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $district_id = $_SESSION['district_id'] ?? null;
    $range_id = $_SESSION['range_id'] ?? null;

    if (!$id || !$district_id || !$range_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
        exit();
    }

    $stmt = $mysqli->prepare("UPDATE land_assets SET is_active = 0 WHERE id = ? AND district_id = ? AND range_id = ?");
    if ($stmt) {
        $stmt->bind_param("iii", $id, $district_id, $range_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Property asset record deactivated successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Record not found or unauthorized access.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare delete query.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
exit();
