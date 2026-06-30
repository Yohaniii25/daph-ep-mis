<?php
session_start();
require_once '../../../../config/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $mysqli->prepare("UPDATE registered_vehicles SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Vehicle row dropped safely from fleet records matrix.']);
        } else { echo json_encode(['success' => false, 'message' => 'Failed to drop execution row.']); }
        $stmt->close();
    }
}