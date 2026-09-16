<?php
session_start();
require_once __DIR__ . '/../../../../config/db_connect.php';
header('Content-Type: application/json');

$allowed_roles = ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon', 'provincial_director', 'district_dd', 'deputy_director_district'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized action block security breach.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id     = $_SESSION['user_id'] ?? null;
    $district_id = !empty($_POST['district_id']) ? intval($_POST['district_id']) : ($_SESSION['district_id'] ?? null);
    $range_id    = !empty($_POST['range_id']) ? intval($_POST['range_id']) : ($_SESSION['range_id'] ?? null);

    $counterfoil_type   = trim(htmlspecialchars($_POST['counterfoil_type'] ?? ''));
    $current_condition  = trim(htmlspecialchars($_POST['current_condition'] ?? ''));
    $initial_count      = isset($_POST['initial_count']) ? max(0, intval($_POST['initial_count'])) : 0;
    $received_quantity  = isset($_POST['received_quantity']) ? max(0, intval($_POST['received_quantity'])) : 0;
    $available_quantity = $initial_count + $received_quantity;
    $purchase_date      = trim(htmlspecialchars($_POST['purchase_date'] ?? ''));
    $remarks            = trim(htmlspecialchars($_POST['remarks'] ?? ''));
    $unit               = trim(htmlspecialchars($_POST['unit'] ?? ''));

    $issue_order_no     = trim(htmlspecialchars($_POST['issue_order_no'] ?? ''));
    $received_from      = trim(htmlspecialchars($_POST['received_from'] ?? ''));
    $receipt_no         = trim(htmlspecialchars($_POST['receipt_no'] ?? ''));
    $specification      = trim(htmlspecialchars($_POST['specification'] ?? ''));

    $book_serial_no     = trim(htmlspecialchars($_POST['book_serial_no'] ?? ''));
    $page_count         = trim(htmlspecialchars($_POST['page_count'] ?? ''));
    $issued_to          = null; // Field deprecated & removed as per individual leaf tracking requirement
    $date_of_issue      = null;
    $date_of_return     = null;

    if (!$user_id || empty($counterfoil_type) || $available_quantity < 0) {
        echo json_encode(['success' => false, 'message' => 'Validation error: All key indicators must be specified.']);
        exit();
    }

    $valid_conditions = ['Good', 'Fair', 'Damaged'];
    if (!in_array($current_condition, $valid_conditions, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid condition. Allowed values: Good, Fair, Damaged.']);
        exit();
    }

    if ($current_condition === 'Damaged') {
        $available_quantity = max(0, $available_quantity - 1);
    }

    $stmt = $mysqli->prepare("INSERT INTO counterfoil_assets (user_id, district_id, range_id, counterfoil_type, book_serial_no, page_count, current_condition, available_quantity, initial_count, received_quantity, purchase_date, remarks, unit, issue_order_no, received_from, receipt_no, specification, issued_to, date_of_issue, date_of_return, removal_status, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', 1)");
    if ($stmt) {
        $stmt->bind_param("iiissssiiissssssssss", $user_id, $district_id, $range_id, $counterfoil_type, $book_serial_no, $page_count, $current_condition, $available_quantity, $initial_count, $received_quantity, $purchase_date, $remarks, $unit, $issue_order_no, $received_from, $receipt_no, $specification, $issued_to, $date_of_issue, $date_of_return);
        if ($stmt->execute()) {
            // Persist new counterfoil type into master types catalog for future auto-suggestions
            $m_stmt = $mysqli->prepare("INSERT IGNORE INTO master_counterfoil_types (type_name, is_active) VALUES (?, 1)");
            if ($m_stmt) {
                $m_stmt->bind_param("s", $counterfoil_type);
                $m_stmt->execute();
                $m_stmt->close();
            }
            echo json_encode(['success' => true, 'message' => 'Counterfoil registration successful.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $stmt->error]);
        }
        $stmt->close();
    }
}