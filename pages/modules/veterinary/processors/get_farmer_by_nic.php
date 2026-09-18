<?php
/**
 * pages/modules/veterinary/processors/get_farmer_by_nic.php
 * Real-time AJAX endpoint that queries farmer details by Farmer Identity Card Number (NIC)
 * Returns Farm Registration Number, Location/Address, Full Name, and Current Animal Counts.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/db_connect.php';

if (!headers_sent()) {
    header('Content-Type: application/json');
}

$allowed_roles = [
    'veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon', 
    'district_dd', 'provincial_director', 'deputy_director_district', 'administrator', 'sms'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$nic = isset($_GET['nic']) ? trim($_GET['nic']) : '';
if (empty($nic)) {
    echo json_encode(['success' => false, 'message' => 'Farmer NIC number is required.']);
    exit();
}

// Normalize NIC: strip spaces, hyphens
$clean_nic = preg_replace('/[^0-9a-zA-Z]/', '', $nic);

// Query `farmers` table
$stmt = $mysqli->prepare("
    SELECT id, nic_no, full_name, farm_registration_no, location_address, contact_no,
           cattle_count, buffalo_count, goat_count, swine_count, poultry_count, total_animal_count
    FROM farmers
    WHERE (REPLACE(REPLACE(nic_no, ' ', ''), '-', '') = ? OR nic_no = ?) 
      AND is_active = 1
    LIMIT 1
");

if ($stmt) {
    $stmt->bind_param("ss", $clean_nic, $nic);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($farmer = $res->fetch_assoc()) {
        $stmt->close();

        // Construct friendly animal inventory summary
        $parts = [];
        if (!empty($farmer['cattle_count'])) $parts[] = "Cattle: " . intval($farmer['cattle_count']);
        if (!empty($farmer['buffalo_count'])) $parts[] = "Buffalo: " . intval($farmer['buffalo_count']);
        if (!empty($farmer['goat_count'])) $parts[] = "Goats: " . intval($farmer['goat_count']);
        if (!empty($farmer['swine_count'])) $parts[] = "Swine: " . intval($farmer['swine_count']);
        if (!empty($farmer['poultry_count'])) $parts[] = "Poultry: " . intval($farmer['poultry_count']);

        $breakdown_text = !empty($parts) ? implode(', ', $parts) : "No livestock recorded";
        $total_cnt = intval($farmer['total_animal_count']);
        if ($total_cnt === 0 && !empty($parts)) {
            $total_cnt = intval($farmer['cattle_count']) + intval($farmer['buffalo_count']) + intval($farmer['goat_count']) + intval($farmer['swine_count']) + intval($farmer['poultry_count']);
        }

        echo json_encode([
            'success' => true,
            'found' => true,
            'farmer' => [
                'id' => intval($farmer['id']),
                'nic_no' => $farmer['nic_no'],
                'full_name' => $farmer['full_name'],
                'farm_registration_no' => $farmer['farm_registration_no'],
                'location_address' => $farmer['location_address'],
                'contact_no' => $farmer['contact_no'],
                'cattle_count' => intval($farmer['cattle_count']),
                'buffalo_count' => intval($farmer['buffalo_count']),
                'goat_count' => intval($farmer['goat_count']),
                'swine_count' => intval($farmer['swine_count']),
                'poultry_count' => intval($farmer['poultry_count']),
                'animal_counts' => [
                    'cattle' => intval($farmer['cattle_count']),
                    'buffalo' => intval($farmer['buffalo_count']),
                    'goat' => intval($farmer['goat_count']),
                    'swine' => intval($farmer['swine_count']),
                    'poultry' => intval($farmer['poultry_count']),
                ],
                'total_animal_count' => $total_cnt,
                'animal_summary' => "Total {$total_cnt} Head ({$breakdown_text})",
                'source' => 'Animal Health Farm Registration'
            ]
        ]);
        exit();
    }
    $stmt->close();
}

// Fallback search in health_certificate_issues if not in farmers table
$f_stmt = $mysqli->prepare("
    SELECT farmer_nic, applicant_name_address, farm_registration_no, species, 
           animal_details_male, animal_details_female
    FROM health_certificate_issues
    WHERE (REPLACE(REPLACE(farmer_nic, ' ', ''), '-', '') = ? OR farmer_nic = ?)
    ORDER BY id DESC LIMIT 1
");
if ($f_stmt) {
    $f_stmt->bind_param("ss", $clean_nic, $nic);
    $f_stmt->execute();
    $f_res = $f_stmt->get_result();
    if ($prev = $f_res->fetch_assoc()) {
        $f_stmt->close();
        $total_cnt = intval($prev['animal_details_male']) + intval($prev['animal_details_female']);
        $sp = strtolower($prev['species'] ?? '');
        echo json_encode([
            'success' => true,
            'found' => true,
            'farmer' => [
                'id' => null,
                'nic_no' => $prev['farmer_nic'] ?: $nic,
                'full_name' => explode(',', $prev['applicant_name_address'])[0] ?? $prev['applicant_name_address'],
                'farm_registration_no' => $prev['farm_registration_no'],
                'location_address' => $prev['applicant_name_address'],
                'contact_no' => '',
                'cattle_count' => (strpos($sp, 'cattle') !== false || strpos($sp, 'cow') !== false) ? $total_cnt : 0,
                'buffalo_count' => (strpos($sp, 'buffalo') !== false) ? $total_cnt : 0,
                'goat_count' => (strpos($sp, 'goat') !== false) ? $total_cnt : 0,
                'swine_count' => (strpos($sp, 'pig') !== false || strpos($sp, 'swine') !== false) ? $total_cnt : 0,
                'poultry_count' => (strpos($sp, 'poultry') !== false || strpos($sp, 'chicken') !== false) ? $total_cnt : 0,
                'animal_counts' => [
                    'cattle' => (strpos($sp, 'cattle') !== false || strpos($sp, 'cow') !== false) ? $total_cnt : 0,
                    'buffalo' => (strpos($sp, 'buffalo') !== false) ? $total_cnt : 0,
                    'goat' => (strpos($sp, 'goat') !== false) ? $total_cnt : 0,
                    'swine' => (strpos($sp, 'pig') !== false || strpos($sp, 'swine') !== false) ? $total_cnt : 0,
                    'poultry' => (strpos($sp, 'poultry') !== false || strpos($sp, 'chicken') !== false) ? $total_cnt : 0,
                ],
                'total_animal_count' => $total_cnt,
                'animal_summary' => "Past Cert Total: {$total_cnt} ({$prev['species']})",
                'source' => 'Animal Health Certificate Log'
            ]
        ]);
        exit();
    }
    $f_stmt->close();
}

echo json_encode([
    'success' => true,
    'found' => false,
    'message' => 'No prior farmer record located for NIC: ' . htmlspecialchars($nic)
]);
exit();
