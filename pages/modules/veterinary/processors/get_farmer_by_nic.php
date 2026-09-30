<?php
/**
 * pages/modules/veterinary/processors/get_farmer_by_nic.php
 * Real-time AJAX endpoint that queries farmer details by Farmer Identity Card Number (NIC)
 * Master Registry: veterinary/animal_branding.php (`farm_registration_renewals`)
 * Returns Farm Registration Number, Location/Address, Full Name, Contact, and Current Animal Counts.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/db_connect.php';

if (!headers_sent()) {
    header('Content-Type: application/json');
}

// Check authentication: allow any authenticated session in system
if (empty($_SESSION['logged_in']) && empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit();
}

$nic = isset($_REQUEST['nic']) ? trim($_REQUEST['nic']) : (isset($_REQUEST['nic_no']) ? trim($_REQUEST['nic_no']) : '');
if (empty($nic)) {
    echo json_encode(['success' => false, 'message' => 'Farmer NIC number is required.']);
    exit();
}

// Normalize NIC: strip spaces, hyphens
$clean_nic = preg_replace('/[^0-9a-zA-Z]/', '', $nic);

// 1. MASTER REGISTRY: Query `farm_registration_renewals` (Single Source of Truth)
$master_stmt = $mysqli->prepare("
    SELECT id, range_id, district_id, date_of_registration_renewal, province, district, ds_division, vs_division, gn_division,
           farmer_name, farmer_address, registration_no, telephone_no, nic, farm_type,
           total_neat_cattle, total_buffaloes, goat_total_no, swine_total_no, sheep_total_no, poultry_data
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

if ($master_stmt) {
    $master_stmt->bind_param("sssss", $clean_nic, $nic, $nic, $clean_nic, $nic);
    $master_stmt->execute();
    $m_res = $master_stmt->get_result();
    if ($rec = $m_res->fetch_assoc()) {
        $master_stmt->close();

        // Parse poultry count from JSON if available
        $poultry_count = 0;
        if (!empty($rec['poultry_data'])) {
            $pdata = is_string($rec['poultry_data']) ? json_decode($rec['poultry_data'], true) : $rec['poultry_data'];
            if (is_array($pdata)) {
                if (!empty($pdata['flock_age_groups']) && is_array($pdata['flock_age_groups'])) {
                    foreach ($pdata['flock_age_groups'] as $fg) {
                        if (isset($fg['quantity']) && is_numeric($fg['quantity'])) {
                            $poultry_count += intval($fg['quantity']);
                        }
                    }
                }
                if ($poultry_count === 0 && !empty($pdata['housing']) && is_array($pdata['housing'])) {
                    foreach (['deep_litter_pens', 'battery_cages', 'free_range'] as $hKey) {
                        if (!empty($pdata['housing'][$hKey]) && is_numeric($pdata['housing'][$hKey])) {
                            $poultry_count += intval($pdata['housing'][$hKey]);
                        }
                    }
                }
            }
        }

        $c_cattle  = intval($rec['total_neat_cattle'] ?? 0);
        $c_buffalo = intval($rec['total_buffaloes'] ?? 0);
        $c_goat    = intval($rec['goat_total_no'] ?? 0);
        $c_sheep   = intval($rec['sheep_total_no'] ?? 0);
        $c_swine   = intval($rec['swine_total_no'] ?? 0);
        $total_cnt = $c_cattle + $c_buffalo + $c_goat + $c_sheep + $c_swine + $poultry_count;

        $parts = [];
        if ($c_cattle > 0)  $parts[] = "Cattle: " . $c_cattle;
        if ($c_buffalo > 0) $parts[] = "Buffalo: " . $c_buffalo;
        if ($c_goat > 0)    $parts[] = "Goats: " . $c_goat;
        if ($c_sheep > 0)   $parts[] = "Sheep: " . $c_sheep;
        if ($c_swine > 0)   $parts[] = "Swine: " . $c_swine;
        if ($poultry_count > 0) $parts[] = "Poultry: " . $poultry_count;
        $breakdown_text = !empty($parts) ? implode(', ', $parts) : "No livestock recorded";

        echo json_encode([
            'success' => true,
            'found' => true,
            'farmer' => [
                'id' => intval($rec['id']),
                'nic_no' => $rec['nic'],
                'nic' => $rec['nic'],
                'full_name' => $rec['farmer_name'],
                'farmer_name' => $rec['farmer_name'],
                'farm_registration_no' => $rec['registration_no'] ?: '',
                'registration_no' => $rec['registration_no'] ?: '',
                'location_address' => $rec['farmer_address'] ?: '',
                'farmer_address' => $rec['farmer_address'] ?: '',
                'contact_no' => $rec['telephone_no'] ?: '',
                'telephone_no' => $rec['telephone_no'] ?: '',
                'phone' => $rec['telephone_no'] ?: '',
                'province' => $rec['province'] ?: '',
                'district' => $rec['district'] ?: '',
                'ds_division' => $rec['ds_division'] ?: '',
                'vs_division' => $rec['vs_division'] ?: '',
                'gn_division' => $rec['gn_division'] ?: '',
                'farm_type' => $rec['farm_type'] ?: '',
                'range_id' => intval($rec['range_id']),
                'district_id' => intval($rec['district_id']),
                'cattle_count' => $c_cattle,
                'buffalo_count' => $c_buffalo,
                'goat_count' => $c_goat,
                'sheep_count' => $c_sheep,
                'swine_count' => $c_swine,
                'poultry_count' => $poultry_count,
                'animal_counts' => [
                    'cattle' => $c_cattle,
                    'buffalo' => $c_buffalo,
                    'goat' => $c_goat,
                    'sheep' => $c_sheep,
                    'swine' => $c_swine,
                    'poultry' => $poultry_count,
                ],
                'total_animal_count' => $total_cnt,
                'animal_summary' => "Total {$total_cnt} Head ({$breakdown_text})",
                'source' => 'Animal Branding Master Registry (farm_registration_renewals)'
            ]
        ]);
        exit();
    }
    $master_stmt->close();
}

// 2. SECONDARY FALLBACK: Query `farmers` table
$stmt = $mysqli->prepare("
    SELECT id, nic_no, full_name, farm_registration_no, location_address, contact_no,
           cattle_count, buffalo_count, goat_count, swine_count, poultry_count, total_animal_count,
           district_id, range_id
    FROM farmers
    WHERE (
        LOWER(REPLACE(REPLACE(nic_no, ' ', ''), '-', '')) = LOWER(?) 
        OR LOWER(nic_no) = LOWER(?)
        OR LOWER(farm_registration_no) = LOWER(?)
        OR nic_no LIKE CONCAT('%', ?, '%')
    )
    AND (is_active = 1 OR is_active IS NULL)
    ORDER BY (LOWER(nic_no) = LOWER(?)) DESC, id DESC
    LIMIT 1
");

if ($stmt) {
    $stmt->bind_param("sssss", $clean_nic, $nic, $nic, $clean_nic, $nic);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($farmer = $res->fetch_assoc()) {
        $stmt->close();

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
                'nic' => $farmer['nic_no'],
                'full_name' => $farmer['full_name'],
                'farmer_name' => $farmer['full_name'],
                'farm_registration_no' => $farmer['farm_registration_no'] ?: '',
                'registration_no' => $farmer['farm_registration_no'] ?: '',
                'location_address' => $farmer['location_address'] ?: '',
                'farmer_address' => $farmer['location_address'] ?: '',
                'contact_no' => $farmer['contact_no'] ?: '',
                'telephone_no' => $farmer['contact_no'] ?: '',
                'phone' => $farmer['contact_no'] ?: '',
                'province' => '',
                'district' => '',
                'ds_division' => '',
                'vs_division' => '',
                'gn_division' => '',
                'farm_type' => '',
                'range_id' => intval($farmer['range_id'] ?? 0),
                'district_id' => intval($farmer['district_id'] ?? 0),
                'cattle_count' => intval($farmer['cattle_count']),
                'buffalo_count' => intval($farmer['buffalo_count']),
                'goat_count' => intval($farmer['goat_count']),
                'sheep_count' => 0,
                'swine_count' => intval($farmer['swine_count']),
                'poultry_count' => intval($farmer['poultry_count']),
                'animal_counts' => [
                    'cattle' => intval($farmer['cattle_count']),
                    'buffalo' => intval($farmer['buffalo_count']),
                    'goat' => intval($farmer['goat_count']),
                    'sheep' => 0,
                    'swine' => intval($farmer['swine_count']),
                    'poultry' => intval($farmer['poultry_count']),
                ],
                'total_animal_count' => $total_cnt,
                'animal_summary' => "Total {$total_cnt} Head ({$breakdown_text})",
                'source' => 'Farmers Registry'
            ]
        ]);
        exit();
    }
    $stmt->close();
}

// 3. TERTIARY FALLBACK: search in health_certificate_issues
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
        $name_part = explode("\n", $prev['applicant_name_address'])[0] ?? $prev['applicant_name_address'];
        $name_part = explode(',', $name_part)[0] ?? $name_part;

        echo json_encode([
            'success' => true,
            'found' => true,
            'farmer' => [
                'id' => null,
                'nic_no' => $prev['farmer_nic'] ?: $nic,
                'nic' => $prev['farmer_nic'] ?: $nic,
                'full_name' => trim($name_part),
                'farmer_name' => trim($name_part),
                'farm_registration_no' => $prev['farm_registration_no'] ?: '',
                'registration_no' => $prev['farm_registration_no'] ?: '',
                'location_address' => $prev['applicant_name_address'] ?: '',
                'farmer_address' => $prev['applicant_name_address'] ?: '',
                'contact_no' => '',
                'telephone_no' => '',
                'phone' => '',
                'province' => '',
                'district' => '',
                'ds_division' => '',
                'vs_division' => '',
                'gn_division' => '',
                'farm_type' => '',
                'range_id' => 0,
                'district_id' => 0,
                'cattle_count' => (strpos($sp, 'cattle') !== false || strpos($sp, 'cow') !== false) ? $total_cnt : 0,
                'buffalo_count' => (strpos($sp, 'buffalo') !== false) ? $total_cnt : 0,
                'goat_count' => (strpos($sp, 'goat') !== false) ? $total_cnt : 0,
                'sheep_count' => (strpos($sp, 'sheep') !== false) ? $total_cnt : 0,
                'swine_count' => (strpos($sp, 'pig') !== false || strpos($sp, 'swine') !== false) ? $total_cnt : 0,
                'poultry_count' => (strpos($sp, 'poultry') !== false || strpos($sp, 'chicken') !== false) ? $total_cnt : 0,
                'animal_counts' => [
                    'cattle' => (strpos($sp, 'cattle') !== false || strpos($sp, 'cow') !== false) ? $total_cnt : 0,
                    'buffalo' => (strpos($sp, 'buffalo') !== false) ? $total_cnt : 0,
                    'goat' => (strpos($sp, 'goat') !== false) ? $total_cnt : 0,
                    'sheep' => (strpos($sp, 'sheep') !== false) ? $total_cnt : 0,
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

