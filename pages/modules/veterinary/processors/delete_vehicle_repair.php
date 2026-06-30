<?php
session_start();
require_once '../../../../config/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $mysqli->prepare("UPDATE vehicle_repairs SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Maintenance log item hidden successfully from ledger tables.']);
        } else { echo json_encode(['success' => false, 'message' => 'Mutation structural error.']); }
        $stmt->close();
    }
}