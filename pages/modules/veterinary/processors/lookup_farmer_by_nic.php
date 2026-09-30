<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) && empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../../../config/db_connect.php';
/** @var mysqli $mysqli */
global $mysqli;

$nic_no = trim($_REQUEST['nic_no'] ?? $_REQUEST['nic'] ?? '');

if (empty($nic_no)) {
    echo json_encode(['success' => false, 'message' => 'NIC Number required']);
    exit;
}

$clean_nic = preg_replace('/[^0-9a-zA-Z]/', '', $nic_no);

// 1. Check farm_registration_renewals as Master Registry
$stmt = $mysqli->prepare("
    SELECT id, nic, farmer_name, registration_no, farmer_address, telephone_no, range_id, district_id 
    FROM farm_registration_renewals 
    WHERE (
        LOWER(REPLACE(REPLACE(COALESCE(nic, ''), ' ', ''), '-', '')) = LOWER(?) 
        OR LOWER(COALESCE(nic, '')) = LOWER(?)
        OR LOWER(COALESCE(registration_no, '')) = LOWER(?)
        OR (nic IS NOT NULL AND nic != '' AND nic LIKE CONCAT('%', ?, '%'))
    )
    ORDER BY (LOWER(COALESCE(nic, '')) = LOWER(?)) DESC, id DESC
    LIMIT 1
");

if ($stmt) {
    $stmt->bind_param("sssss", $clean_nic, $nic_no, $nic_no, $clean_nic, $nic_no);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $stmt->close();
        echo json_encode([
            'success' => true,
            'found' => true,
            'farmer' => [
                'id' => $row['id'],
                'nic_no' => $row['nic'],
                'full_name' => $row['farmer_name'],
                'farm_registration_no' => $row['registration_no'] ?: '',
                'location_address' => $row['farmer_address'] ?: '',
                'contact_no' => $row['telephone_no'] ?: '',
                'range_id' => $row['range_id'],
                'district_id' => $row['district_id']
            ]
        ]);
        exit;
    }
    $stmt->close();
}

// 2. Fallback to farmers table
$stmt2 = $mysqli->prepare("
    SELECT id, nic_no, full_name, farm_registration_no, location_address, contact_no, range_id, district_id 
    FROM farmers 
    WHERE (
        LOWER(REPLACE(REPLACE(COALESCE(nic_no, ''), ' ', ''), '-', '')) = LOWER(?) 
        OR LOWER(COALESCE(nic_no, '')) = LOWER(?)
        OR LOWER(COALESCE(farm_registration_no, '')) = LOWER(?)
    )
    LIMIT 1
");

if ($stmt2) {
    $stmt2->bind_param("sss", $clean_nic, $nic_no, $nic_no);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    if ($row2 = $res2->fetch_assoc()) {
        $stmt2->close();
        echo json_encode([
            'success' => true,
            'found' => true,
            'farmer' => [
                'id' => $row2['id'],
                'nic_no' => $row2['nic_no'],
                'full_name' => $row2['full_name'],
                'farm_registration_no' => $row2['farm_registration_no'] ?: '',
                'location_address' => $row2['location_address'] ?: '',
                'contact_no' => $row2['contact_no'] ?: '',
                'range_id' => $row2['range_id'],
                'district_id' => $row2['district_id']
            ]
        ]);
        exit;
    }
    $stmt2->close();
}

echo json_encode([
    'success' => true,
    'found' => false,
    'message' => 'No farmer found with this IC Number in registry'
]);
exit;
