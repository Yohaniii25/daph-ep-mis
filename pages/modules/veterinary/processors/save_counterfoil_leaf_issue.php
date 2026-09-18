<?php
session_start();
require_once __DIR__ . '/../../../../config/db_connect.php';
require_once __DIR__ . '/../../../../includes/counterfoil_module_helper.php';

header('Content-Type: application/json');

$allowed_roles = ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon', 'provincial_director', 'district_dd', 'deputy_director_district'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized clearance profile.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id     = $_SESSION['user_id'] ?? null;
    $district_id = !empty($_POST['district_id']) ? intval($_POST['district_id']) : ($_SESSION['district_id'] ?? null);
    $range_id    = !empty($_POST['range_id']) ? intval($_POST['range_id']) : ($_SESSION['range_id'] ?? null);

    $counterfoil_id       = isset($_POST['counterfoil_id']) ? intval($_POST['counterfoil_id']) : 0;
    $leaf_serial_no       = trim($_POST['leaf_serial_no'] ?? '');
    $farmer_nic           = trim($_POST['farmer_nic'] ?? '');
    $farmer_name          = trim($_POST['farmer_name'] ?? '');
    $farm_registration_no = trim($_POST['farm_registration_no'] ?? '');
    $location_address     = trim($_POST['location_address'] ?? '');
    $animal_counts_summary= trim($_POST['animal_counts_summary'] ?? '');
    $issue_date           = !empty($_POST['issue_date']) ? trim($_POST['issue_date']) : date('Y-m-d');
    $purpose              = trim($_POST['purpose'] ?? '');
    $remarks              = trim($_POST['remarks'] ?? '');

    // Mandatory field validations
    if (empty($farmer_nic)) {
        echo json_encode(['success' => false, 'message' => 'Farmer Identity Card Number (NIC) is mandatory.']);
        exit();
    }
    if (empty($leaf_serial_no)) {
        echo json_encode(['success' => false, 'message' => 'Leaf / Certificate Serial Number is required.']);
        exit();
    }
    if ($counterfoil_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Please select the parent Counterfoil Book.']);
        exit();
    }

    // Retrieve Counterfoil Type & Validate Book Context
    $cf_stmt = $mysqli->prepare("SELECT counterfoil_type, district_id, range_id FROM counterfoil_assets WHERE id = ?");
    $cf_stmt->bind_param("i", $counterfoil_id);
    $cf_stmt->execute();
    $cf_data = $cf_stmt->get_result()->fetch_assoc();
    $cf_stmt->close();

    if (!$cf_data) {
        echo json_encode(['success' => false, 'message' => 'Selected counterfoil book could not be located in registry.']);
        exit();
    }

    $counterfoil_type = $cf_data['counterfoil_type'];
    if (!$district_id) $district_id = $cf_data['district_id'];
    if (!$range_id) $range_id = $cf_data['range_id'];

    // Enforce Farmer Book Restriction: Only 12 authorized book types can be issued to farmers
    if (!isFarmerRelatedBook($counterfoil_type)) {
        echo json_encode([
            'success' => false,
            'message' => "Restricted Book Type: '{$counterfoil_type}' is categorized for internal/operational tracking and cannot be issued as an individual farmer certificate."
        ]);
        exit();
    }

    // Insert into counterfoil_leaf_issues
    $sql = "INSERT INTO counterfoil_leaf_issues 
            (counterfoil_id, district_id, range_id, counterfoil_type, leaf_serial_no, farmer_nic, farmer_name, farm_registration_no, location_address, animal_counts_summary, issue_date, purpose, remarks, issued_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'DB Prepare Error: ' . $mysqli->error]);
        exit();
    }

    $stmt->bind_param("iiissssssssssi", 
        $counterfoil_id, 
        $district_id, 
        $range_id, 
        $counterfoil_type, 
        $leaf_serial_no, 
        $farmer_nic, 
        $farmer_name, 
        $farm_registration_no, 
        $location_address, 
        $animal_counts_summary, 
        $issue_date, 
        $purpose, 
        $remarks, 
        $user_id
    );

    if ($stmt->execute()) {
        // If farmer is newly typed, optionally record or keep in farmers registry
        echo json_encode([
            'success' => true, 
            'message' => "Individual Certificate/Leaf (Serial: {$leaf_serial_no}) successfully logged to {$farmer_name} (NIC: {$farmer_nic})."
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'DB Execute Error: ' . $stmt->error]);
    }
    $stmt->close();
}
exit();
