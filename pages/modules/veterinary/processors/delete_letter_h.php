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

    // Secure range verify
    $range_id = $_SESSION['range_id'] ?? null;
    if (empty($range_id)) {
        $user_stmt = $mysqli->prepare("SELECT range_id FROM users WHERE id = ?");
        if ($user_stmt) {
            $user_stmt->bind_param("i", $user_id);
            $user_stmt->execute();
            $user_res = $user_stmt->get_result()->fetch_assoc();
            if ($user_res) {
                $range_id = $user_res['range_id'];
            }
            $user_stmt->close();
        }
    }

    if (empty($range_id)) {
        echo json_encode(['success' => false, 'message' => 'Surgeon range configuration not found.']);
        exit();
    }

    $stmt = $mysqli->prepare("DELETE FROM letter_h_accounts WHERE id = ? AND range_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $range_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database deletion execution failed.']);
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
