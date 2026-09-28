<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../../../config/db_connect.php';
/** @var mysqli $mysqli */
global $mysqli;

$nic_no = trim($_GET['nic_no'] ?? $_POST['nic_no'] ?? '');

if (empty($nic_no)) {
    echo json_encode(['success' => false, 'message' => 'NIC Number required']);
    exit;
}

$stmt = $mysqli->prepare("
    SELECT id, nic_no, full_name, farm_registration_no, location_address, contact_no, range_id, district_id 
    FROM farmers 
    WHERE nic_no = ? 
    LIMIT 1
");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database query preparation failed']);
    exit;
}

$stmt->bind_param("s", $nic_no);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'found' => true,
        'farmer' => [
            'id' => $row['id'],
            'nic_no' => $row['nic_no'],
            'full_name' => $row['full_name'],
            'farm_registration_no' => $row['farm_registration_no'] ?: '',
            'location_address' => $row['location_address'] ?: '',
            'contact_no' => $row['contact_no'] ?: ''
        ]
    ]);
} else {
    echo json_encode([
        'success' => true,
        'found' => false,
        'message' => 'No farmer found with this IC Number in registry'
    ]);
}
$stmt->close();
