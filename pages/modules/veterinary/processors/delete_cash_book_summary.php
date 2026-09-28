<?php
session_start();
require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

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

    $source = trim($_POST['source'] ?? '');
    $leaf_id = intval($_POST['leaf_id'] ?? 0);

    if ($source === 'leaf' || $source === 'counterfoil_leaf' || $leaf_id > 0) {
        $target_leaf_id = $leaf_id > 0 ? $leaf_id : $id;
        // Delete from counterfoil_leaf_issues
        $d_stmt = $mysqli->prepare("DELETE FROM counterfoil_leaf_issues WHERE id = ? AND range_id = ?");
        if ($d_stmt) {
            $d_stmt->bind_param("ii", $target_leaf_id, $range_id);
            $d_stmt->execute();
            $d_stmt->close();
        }
        // Also delete any linked row in cash_book_summaries
        $cb_d = $mysqli->prepare("DELETE FROM cash_book_summaries WHERE (leaf_issue_id = ? OR id = ?) AND range_id = ?");
        if ($cb_d) {
            $cb_d->bind_param("iii", $target_leaf_id, $id, $range_id);
            $cb_d->execute();
            $cb_d->close();
        }
        echo json_encode(['success' => true]);
        exit();
    }

    // Normal summary id deletion: check if it links to leaf_issue_id
    $chk_stmt = $mysqli->prepare("SELECT leaf_issue_id FROM cash_book_summaries WHERE id = ? AND range_id = ?");
    $linked_leaf = 0;
    if ($chk_stmt) {
        $chk_stmt->bind_param("ii", $id, $range_id);
        $chk_stmt->execute();
        $chk_res = $chk_stmt->get_result()->fetch_assoc();
        if ($chk_res && !empty($chk_res['leaf_issue_id'])) {
            $linked_leaf = intval($chk_res['leaf_issue_id']);
        }
        $chk_stmt->close();
    }

    $stmt = $mysqli->prepare("DELETE FROM cash_book_summaries WHERE id = ? AND range_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $range_id);
        if ($stmt->execute()) {
            if ($linked_leaf > 0) {
                $del_lf = $mysqli->prepare("DELETE FROM counterfoil_leaf_issues WHERE id = ? AND range_id = ?");
                if ($del_lf) {
                    $del_lf->bind_param("ii", $linked_leaf, $range_id);
                    $del_lf->execute();
                    $del_lf->close();
                }
            }
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
