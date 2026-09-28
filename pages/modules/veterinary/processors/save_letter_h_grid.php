<?php
session_start();
require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon' || !isset($_SESSION['range_id'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
        exit();
    }
    header('Location: ../../../../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $range_id     = intval($_SESSION['range_id']);
    $district_id  = $_SESSION['district_id'] ?? null;
    $report_year  = intval($_POST['report_year'] ?? date('Y'));
    $report_month = intval($_POST['report_month'] ?? date('n'));
    $items        = $_POST['items'] ?? [];

    if (empty($range_id) || empty($report_year) || empty($report_month)) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters provided.']);
            exit();
        }
        header("Location: ../letter_h_record.php?year=$report_year&month=$report_month&status=db_error");
        exit();
    }

    // Lookup district_id if empty
    if (empty($district_id)) {
        $stmt_d = $mysqli->prepare("SELECT district_id FROM veterinary_ranges WHERE id = ?");
        if ($stmt_d) {
            $stmt_d->bind_param("i", $range_id);
            $stmt_d->execute();
            $stmt_d->bind_result($district_id);
            $stmt_d->fetch();
            $stmt_d->close();
        }
    }

    $stmt = $mysqli->prepare("
        INSERT INTO letter_h_entries (
            district_id, range_id, report_year, report_month, category_id, item_id, amount
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            category_id = VALUES(category_id),
            amount      = VALUES(amount),
            updated_at  = NOW()
    ");

    $saved_count = 0;
    foreach ($items as $item) {
        $item_id     = intval($item['item_id'] ?? 0);
        $category_id = intval($item['category_id'] ?? 0);
        $amount      = floatval($item['amount'] ?? 0);

        if ($item_id <= 0 || $category_id <= 0) continue;

        if ($stmt) {
            $stmt->bind_param(
                "iiiiidd",
                $district_id, $range_id, $report_year, $report_month, $category_id, $item_id, $amount
            );
            if ($stmt->execute()) {
                $saved_count++;
            }
        }
    }
    if ($stmt) $stmt->close();

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['success' => true, 'saved' => $saved_count]);
        exit();
    }

    header("Location: ../letter_h_record.php?year=$report_year&month=$report_month&status=saved");
    exit();
}

header("Location: ../letter_h_record.php");
exit();
