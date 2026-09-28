<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['veterinary_surgeon', 'sms'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$range_id = $_SESSION['range_id'] ?? null;
$id       = intval($_GET['id'] ?? 0);

if ($id <= 0 || empty($range_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID or Range context']);
    exit();
}

// Fetch program (Strictly NO Program Title!)
$stmt = $mysqli->prepare("
    SELECT id, range_id, district_id, program_date, farmer_id, farmer_name, nic_no, 
           farm_reg_no, address, tags_used, tags_spoiled, staff_involved, remarks 
    FROM ear_tag_programs 
    WHERE id = ? AND range_id = ?
");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Query error: ' . $mysqli->error]);
    exit();
}

$stmt->bind_param("ii", $id, $range_id);
$stmt->execute();
$res = $stmt->get_result();
$program = $res->fetch_assoc();
$stmt->close();

if (!$program) {
    echo json_encode(['success' => false, 'message' => 'Program record not found']);
    exit();
}

// Fetch linked cattle vouchers
$vouchers = [];
$v_stmt = $mysqli->prepare("
    SELECT id, ear_tag_number, voucher_number, breed, sex, age, issue_date, status 
    FROM cattle_vouchers 
    WHERE tag_program_id = ? 
    ORDER BY id ASC
");
if ($v_stmt) {
    $v_stmt->bind_param("i", $id);
    $v_stmt->execute();
    $v_res = $v_stmt->get_result();
    while ($row = $v_res->fetch_assoc()) {
        $vouchers[] = $row;
    }
    $v_stmt->close();
}

echo json_encode([
    'success' => true,
    'program' => $program,
    'vouchers' => $vouchers
]);
