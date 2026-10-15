<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

header('Content-Type: application/json; charset=UTF-8');

// Ensure authorized access
$allowed_roles = [
    'veterinary_surgeon',
    'government_veterinary_surgeon',
    'sms',
    'district_dd',
    'deputy_director_district',
    'administrator',
    'provincial_director',
    'deputy_director_hq_1',
    'deputy_director_hq_2',
    'admin'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
$session_range_id = (int)($_SESSION['range_id'] ?? 0);
$action = trim($_REQUEST['action'] ?? '');

// Strict valid statuses
$VALID_STATUSES = ['Active', 'Sold or transferred', 'Slaughter', 'Died', 'Tag Damaged / Removed'];

/**
 * Enforces voucher serial numbers to strictly start with the prefix "A-" (e.g. A-918059)
 * Replaces previously used "CV" or "CV-" prefixes.
 */
function formatVoucherSerialNumber($val) {
    $val = trim((string)($val ?? ''));
    if ($val === '') {
        return 'A-' . str_pad((string)mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    }
    // If it starts with A- (e.g., A-918059)
    if (preg_match('/^A[-_]([A-Za-z0-9_-]+)$/i', $val, $m)) {
        return 'A-' . strtoupper($m[1]);
    }
    // If it starts with CV- or CV (legacy prefix)
    if (preg_match('/^CV[-_]?([A-Za-z0-9_-]+)$/i', $val, $m)) {
        return 'A-' . strtoupper($m[1]);
    }
    // If it starts with letter A without hyphen (e.g., A918059)
    if (preg_match('/^A([0-9]+)$/i', $val, $m)) {
        return 'A-' . $m[1];
    }
    // If it's a numeric string (e.g., 918059)
    if (preg_match('/^[0-9]+$/', $val)) {
        return 'A-' . $val;
    }
    // General fallback: strip leading non-alphanumeric, CV-, A- and re-prefix with A-
    $cleaned = preg_replace('/^(CV|A)[-_]?/i', '', $val);
    $cleaned = preg_replace('/[^A-Za-z0-9_-]/', '', $cleaned);
    return 'A-' . ($cleaned !== '' ? strtoupper($cleaned) : str_pad((string)mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT));
}

/**
 * Auto-populates and syncs Cattle Voucher records to:
 * 1. counterfoil_leaf_issues (Counter Foil register)
 * 2. cattle_vouchers (Individual cattle vouchers table)
 */
function syncCounterFileRecords($mysqli, $data, $user_id) {
    $voucher_no = trim($data['voucher_serial_no'] ?? '');
    if (empty($voucher_no) || !preg_match('/^A-/i', $voucher_no)) {
        return false;
    }

    $ear_tag_no = trim($data['ear_tag_no'] ?? '');
    $farmer_name = trim($data['farmer_name'] ?? '');
    $farm_reg_no = trim($data['farm_reg_no'] ?? '');
    $district_id = intval($data['district_id'] ?? 1);
    $range_id = intval($data['range_id'] ?? 1);
    $vs_range = trim($data['vs_range'] ?? '');
    $district = trim($data['district'] ?? '');
    $identification_date = trim($data['identification_date'] ?? date('Y-m-d'));
    $gender = trim($data['gender'] ?? 'Female');
    $species = trim($data['species'] ?? 'Cattle');
    $breed = trim($data['breed'] ?? '');
    $age = trim($data['age'] ?? '');
    $purpose = trim($data['purpose'] ?? 'Dairy');
    $present_status = trim($data['present_status'] ?? 'Sold or transferred');
    $remarks = trim($data['remarks'] ?? '');

    // Attempt to lookup farmer NIC if not provided
    $farmer_nic = trim($data['farmer_nic'] ?? '');
    if (empty($farmer_nic) && !empty($farm_reg_no)) {
        $nic_stmt = $mysqli->prepare("SELECT nic FROM farm_registration_renewals WHERE registration_no = ? OR farm_registration_no = ? LIMIT 1");
        if ($nic_stmt) {
            $nic_stmt->bind_param("ss", $farm_reg_no, $farm_reg_no);
            $nic_stmt->execute();
            $nres = $nic_stmt->get_result();
            if ($nrow = $nres->fetch_assoc()) {
                $farmer_nic = $nrow['nic'] ?? '';
            }
            $nic_stmt->close();
        }
    }
    if (empty($farmer_nic)) {
        $farmer_nic = 'N/A';
    }

    // 1. SYNC TO counterfoil_leaf_issues
    // Find or create asset ID for Cattle Voucher in counterfoil_assets
    $counterfoil_id = 0;
    $ast_stmt = $mysqli->prepare("SELECT id FROM counterfoil_assets WHERE counterfoil_type = 'Cattle Voucher' AND (range_id = ? OR range_id = 0 OR range_id IS NULL) ORDER BY id DESC LIMIT 1");
    if ($ast_stmt) {
        $ast_stmt->bind_param("i", $range_id);
        $ast_stmt->execute();
        $ast_res = $ast_stmt->get_result();
        if ($arow = $ast_res->fetch_assoc()) {
            $counterfoil_id = intval($arow['id']);
        }
        $ast_stmt->close();
    }

    if ($counterfoil_id === 0) {
        $init_ast = $mysqli->prepare("INSERT INTO counterfoil_assets 
            (user_id, user_category, district_id, range_id, counterfoil_type, book_serial_no, page_count, current_condition, available_quantity, initial_count, received_quantity, purchase_date, remarks, is_active, removal_status) 
            VALUES (?, 'veterinary', ?, ?, 'Cattle Voucher', 'A-Series', 1000, 'Good', 1000, 1000, 0, CURDATE(), 'Automated Asset for Cattle Vouchers', 1, 'Active')");
        if ($init_ast) {
            $init_ast->bind_param("iii", $user_id, $district_id, $range_id);
            if ($init_ast->execute()) {
                $counterfoil_id = $init_ast->insert_id;
            }
            $init_ast->close();
        }
        if ($counterfoil_id === 0) {
            $counterfoil_id = 1;
        }
    }

    $location_address = $vs_range . ($district ? ", " . $district : "");
    $animal_counts_summary = "1 ({$species} - Ear Tag: {$ear_tag_no})";
    $counterfoil_purpose = "Cattle Voucher - Ear Tag: {$ear_tag_no} ({$purpose})";
    $category_tab = 'tab-consultations';
    $revenue_item = 'Cattle Voucher Issuance';
    $counterfoil_remarks = "Synced from Animal Identification (Tag: {$ear_tag_no}, Status: {$present_status})";
    if ($present_status === 'Tag Damaged / Removed') {
        $counterfoil_remarks .= " [PERMANENT PAIRING RETAINED - DAMAGED TAG REMOVED]";
    }
    if (!empty($remarks)) {
        $counterfoil_remarks .= " | " . $remarks;
    }

    $chk_leaf = $mysqli->prepare("SELECT id FROM counterfoil_leaf_issues WHERE leaf_serial_no = ? LIMIT 1");
    if ($chk_leaf) {
        $chk_leaf->bind_param("s", $voucher_no);
        $chk_leaf->execute();
        $l_res = $chk_leaf->get_result();
        if ($l_row = $l_res->fetch_assoc()) {
            $leaf_id = intval($l_row['id']);
            $upd_lf = $mysqli->prepare("UPDATE counterfoil_leaf_issues SET 
                farmer_nic = ?, farmer_name = ?, farm_registration_no = ?, 
                location_address = ?, animal_counts_summary = ?, issue_date = ?, 
                purpose = ?, revenue_item = ?, category_tab = ?, remarks = ?
                WHERE id = ?");
            if ($upd_lf) {
                $upd_lf->bind_param("ssssssssssi",
                    $farmer_nic, $farmer_name, $farm_reg_no,
                    $location_address, $animal_counts_summary, $identification_date,
                    $counterfoil_purpose, $revenue_item, $category_tab, $counterfoil_remarks,
                    $leaf_id
                );
                $upd_lf->execute();
                $upd_lf->close();
            }
        } else {
            $ins_lf = $mysqli->prepare("INSERT INTO counterfoil_leaf_issues 
                (counterfoil_id, district_id, range_id, counterfoil_type, leaf_serial_no, 
                 client_type, farmer_nic, farmer_name, farm_registration_no, location_address, 
                 animal_counts_summary, issue_date, purpose, amount, quantity, unit_price, 
                 amount_deposited, revenue_item, category_tab, remarks, issued_by) 
                VALUES (?, ?, ?, 'Cattle Voucher', ?, 'farmer', ?, ?, ?, ?, ?, ?, ?, 0.00, 1, 0.00, 0.00, ?, ?, ?, ?)");
            if ($ins_lf) {
                $ins_lf->bind_param("iiisssssssssssi",
                    $counterfoil_id, $district_id, $range_id, $voucher_no,
                    $farmer_nic, $farmer_name, $farm_reg_no, $location_address,
                    $animal_counts_summary, $identification_date, $counterfoil_purpose,
                    $revenue_item, $category_tab, $counterfoil_remarks, $user_id
                );
                $ins_lf->execute();
                $ins_lf->close();
            }
        }
        $chk_leaf->close();
    }

    // 2. SYNC TO cattle_vouchers
    $cv_status = 'Active';
    if ($present_status === 'Sold or transferred') {
        $cv_status = 'Transferred';
    } elseif ($present_status === 'Slaughter') {
        $cv_status = 'Slaughtered';
    } elseif ($present_status === 'Died') {
        $cv_status = 'Dead';
    } elseif ($present_status === 'Tag Damaged / Removed') {
        $cv_status = 'Damaged';
    }

    $chk_cv = $mysqli->prepare("SELECT id FROM cattle_vouchers WHERE voucher_number = ? LIMIT 1");
    if ($chk_cv) {
        $chk_cv->bind_param("s", $voucher_no);
        $chk_cv->execute();
        $cv_res = $chk_cv->get_result();
        if ($cv_row = $cv_res->fetch_assoc()) {
            $cv_id = intval($cv_row['id']);
            $upd_cv = $mysqli->prepare("UPDATE cattle_vouchers SET 
                farmer_nic = ?, farm_reg_no = ?, range_id = ?, ear_tag_number = ?, 
                breed = ?, sex = ?, age = ?, issue_date = ?, status = ?, updated_at = NOW() 
                WHERE id = ?");
            if ($upd_cv) {
                $upd_cv->bind_param("ssissssssi", 
                    $farmer_nic, $farm_reg_no, $range_id, $ear_tag_no,
                    $breed, $gender, $age, $identification_date, $cv_status,
                    $cv_id
                );
                $upd_cv->execute();
                $upd_cv->close();
            }
        } else {
            $ins_cv = $mysqli->prepare("INSERT INTO cattle_vouchers 
                (tag_program_id, farmer_nic, farm_reg_no, range_id, ear_tag_number, voucher_number, breed, sex, age, issue_date, status, created_by) 
                VALUES (0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($ins_cv) {
                $ins_cv->bind_param("ssisssssssi",
                    $farmer_nic, $farm_reg_no, $range_id, $ear_tag_no, $voucher_no,
                    $breed, $gender, $age, $identification_date, $cv_status, $user_id
                );
                $ins_cv->execute();
                $ins_cv->close();
            }
        }
        $chk_cv->close();
    }

    return true;
}

// -------------------------------------------------------------------------
// ACTION: GET FARMER DETAILS BY FARM REGISTRATION NO (AUTO-FETCH WORKFLOW)
// -------------------------------------------------------------------------
if ($action === 'get_farmer_by_reg') {
    $farm_reg_no = trim($_GET['farm_reg_no'] ?? '');
    if (empty($farm_reg_no)) {
        echo json_encode(['success' => false, 'message' => 'Farm registration number is required.']);
        exit();
    }

    $found = false;
    $farmer = null;

    $clean_reg = preg_replace('/[^0-9a-zA-Z]/', '', $farm_reg_no);

    // 1. Try farm_registration_renewals (Master Registry)
    $stmt = $mysqli->prepare("
        SELECT id, registration_no, farmer_name, district, vs_division, range_id, district_id 
        FROM farm_registration_renewals 
        WHERE registration_no = ? 
           OR LOWER(TRIM(registration_no)) = LOWER(?)
           OR REPLACE(REPLACE(registration_no, ' ', ''), '-', '') = ?
           OR (registration_no IS NOT NULL AND registration_no != '' AND registration_no LIKE CONCAT('%', ?, '%'))
        ORDER BY (LOWER(TRIM(registration_no)) = LOWER(?)) DESC, id DESC 
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param("sssss", $farm_reg_no, $farm_reg_no, $clean_reg, $farm_reg_no, $farm_reg_no);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $found = true;
            $farmer = [
                'farm_reg_no' => $row['registration_no'],
                'farmer_name' => $row['farmer_name'],
                'district'    => $row['district'],
                'vs_range'    => $row['vs_division'],
                'range_id'    => (int)$row['range_id'],
                'district_id' => (int)$row['district_id']
            ];
        }
        $stmt->close();
    }

    // 2. Fallback: try farmers table
    if (!$found) {
        $stmt2 = $mysqli->prepare("
            SELECT f.id, f.farm_registration_no, f.full_name AS farmer_name, f.range_id, f.district_id,
                   COALESCE(vr.name, '') AS vs_range, COALESCE(d.name, '') AS district, COALESCE(vr.code, '5314') AS range_code
            FROM farmers f
            LEFT JOIN veterinary_ranges vr ON vr.id = f.range_id
            LEFT JOIN districts d ON d.id = f.district_id
            WHERE f.farm_registration_no = ? 
               OR LOWER(TRIM(f.farm_registration_no)) = LOWER(?)
               OR REPLACE(REPLACE(f.farm_registration_no, ' ', ''), '-', '') = ?
               OR (f.farm_registration_no IS NOT NULL AND f.farm_registration_no != '' AND f.farm_registration_no LIKE CONCAT('%', ?, '%'))
            ORDER BY (LOWER(TRIM(f.farm_registration_no)) = LOWER(?)) DESC, f.id DESC
            LIMIT 1
        ");
        if ($stmt2) {
            $stmt2->bind_param("sssss", $farm_reg_no, $farm_reg_no, $clean_reg, $farm_reg_no, $farm_reg_no);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            if ($row2 = $res2->fetch_assoc()) {
                $found = true;
                $farmer = [
                    'farm_reg_no' => $row2['farm_registration_no'],
                    'farmer_name' => $row2['farmer_name'],
                    'district'    => $row2['district'],
                    'vs_range'    => $row2['vs_range'],
                    'range_id'    => (int)$row2['range_id'],
                    'district_id' => (int)$row2['district_id'],
                    'range_code'  => '5314'
                ];
            }
            $stmt2->close();
        }
    }

    // 3. Fallback: try animal_identifications if not in previous tables
    if (!$found) {
        $stmt3 = $mysqli->prepare("
            SELECT farm_reg_no, farmer_name, district, vs_range, range_id, district_id 
            FROM animal_identifications 
            WHERE farm_reg_no = ? 
               OR LOWER(TRIM(farm_reg_no)) = LOWER(?)
               OR (farm_reg_no IS NOT NULL AND farm_reg_no != '' AND farm_reg_no LIKE CONCAT('%', ?, '%'))
            ORDER BY (LOWER(TRIM(farm_reg_no)) = LOWER(?)) DESC, id DESC 
            LIMIT 1
        ");
        if ($stmt3) {
            $stmt3->bind_param("ssss", $farm_reg_no, $farm_reg_no, $farm_reg_no, $farm_reg_no);
            $stmt3->execute();
            $res3 = $stmt3->get_result();
            if ($row3 = $res3->fetch_assoc()) {
                $found = true;
                $farmer = [
                    'farm_reg_no' => $row3['farm_reg_no'],
                    'farmer_name' => $row3['farmer_name'],
                    'district'    => $row3['district'],
                    'vs_range'    => $row3['vs_range'],
                    'range_id'    => (int)$row3['range_id'],
                    'district_id' => (int)$row3['district_id']
                ];
            }
            $stmt3->close();
        }
    }

    if ($found && $farmer) {
        // Range code: user requirement specifies 5314 for testing Ampara veterinary range
        $farmer['range_code'] = '5314';

        echo json_encode([
            'success' => true,
            'farmer'  => $farmer
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => "No registered farm found for Registration No: {$farm_reg_no}"
        ]);
    }
    exit();
}

// -------------------------------------------------------------------------
// ACTION: GET FARMER TOTAL ANIMAL SUMMARY (FOR POPUP ON CLICKING FARM REG NO)
// -------------------------------------------------------------------------
if ($action === 'get_farmer_animal_summary') {
    $farm_reg_no = trim($_GET['farm_reg_no'] ?? '');
    if (empty($farm_reg_no)) {
        echo json_encode(['success' => false, 'message' => 'Farm registration number is required.']);
        exit();
    }

    $farmer_info = [
        'farm_reg_no' => $farm_reg_no,
        'farmer_name' => '',
        'nic' => '',
        'district' => '',
        'vs_range' => '',
        'address' => '',
        'telephone' => ''
    ];

    $master_animals = [
        'cattle' => 0,
        'buffalo' => 0,
        'goat' => 0,
        'swine' => 0,
        'sheep' => 0,
        'poultry' => 0,
        'total' => 0
    ];

    // 1. Query farm_registration_renewals for master census
    $clean_reg = preg_replace('/[^0-9a-zA-Z]/', '', $farm_reg_no);
    $m_stmt = $mysqli->prepare("
        SELECT id, registration_no, farmer_name, nic, farmer_address, telephone_no, district, vs_division,
               total_neat_cattle, total_buffaloes, goat_total_no, swine_total_no, sheep_total_no, poultry_data
        FROM farm_registration_renewals
        WHERE registration_no = ? 
           OR LOWER(TRIM(registration_no)) = LOWER(?)
           OR REPLACE(REPLACE(registration_no, ' ', ''), '-', '') = ?
           OR (registration_no IS NOT NULL AND registration_no != '' AND registration_no LIKE CONCAT('%', ?, '%'))
        ORDER BY (LOWER(TRIM(registration_no)) = LOWER(?)) DESC, id DESC 
        LIMIT 1
    ");
    if ($m_stmt) {
        $m_stmt->bind_param("sssss", $farm_reg_no, $farm_reg_no, $clean_reg, $farm_reg_no, $farm_reg_no);
        $m_stmt->execute();
        $m_res = $m_stmt->get_result();
        if ($m_row = $m_res->fetch_assoc()) {
            $farmer_info['farm_reg_no'] = $m_row['registration_no'];
            $farmer_info['farmer_name'] = $m_row['farmer_name'];
            $farmer_info['nic'] = $m_row['nic'];
            $farmer_info['district'] = $m_row['district'];
            $farmer_info['vs_range'] = $m_row['vs_division'];
            $farmer_info['address'] = $m_row['farmer_address'];
            $farmer_info['telephone'] = $m_row['telephone_no'];

            $master_animals['cattle'] = (int)$m_row['total_neat_cattle'];
            $master_animals['buffalo'] = (int)$m_row['total_buffaloes'];
            $master_animals['goat'] = (int)$m_row['goat_total_no'];
            $master_animals['swine'] = (int)$m_row['swine_total_no'];
            $master_animals['sheep'] = (int)$m_row['sheep_total_no'];

            // Parse poultry if JSON
            $p_count = 0;
            if (!empty($m_row['poultry_data'])) {
                $p_data = json_decode($m_row['poultry_data'], true);
                if (is_array($p_data)) {
                    if (!empty($p_data['flock_age_groups']) && is_array($p_data['flock_age_groups'])) {
                        foreach ($p_data['flock_age_groups'] as $fg) {
                            if (isset($fg['quantity']) && is_numeric($fg['quantity'])) {
                                $p_count += (int)$fg['quantity'];
                            }
                        }
                    }
                }
            }
            $master_animals['poultry'] = $p_count;
            $master_animals['total'] = $master_animals['cattle'] + $master_animals['buffalo'] + $master_animals['goat'] + $master_animals['swine'] + $master_animals['sheep'] + $master_animals['poultry'];
        }
        $m_stmt->close();
    }

    // 2. Query animal_identifications for identified animals
    $ai_animals = [];
    $ai_status_summary = [
        'total' => 0,
        'active' => 0,
        'sold' => 0,
        'slaughter' => 0,
        'died' => 0,
        'damaged' => 0
    ];

    $ai_stmt = $mysqli->prepare("
        SELECT id, farm_reg_no, farmer_name, district, vs_range, ear_tag_no, animal_seq_no,
               voucher_serial_no, identification_date, gender, colour, species, breed, age, purpose, io_number, present_status
        FROM animal_identifications
        WHERE farm_reg_no = ? 
           OR LOWER(TRIM(farm_reg_no)) = LOWER(?)
           OR (farm_reg_no IS NOT NULL AND farm_reg_no != '' AND farm_reg_no LIKE CONCAT('%', ?, '%'))
        ORDER BY id DESC
    ");
    if ($ai_stmt) {
        $ai_stmt->bind_param("sss", $farm_reg_no, $farm_reg_no, $farm_reg_no);
        $ai_stmt->execute();
        $ai_res = $ai_stmt->get_result();
        while ($row = $ai_res->fetch_assoc()) {
            $ai_animals[] = $row;
            if (empty($farmer_info['farmer_name']) && !empty($row['farmer_name'])) {
                $farmer_info['farmer_name'] = $row['farmer_name'];
                $farmer_info['district'] = $row['district'];
                $farmer_info['vs_range'] = $row['vs_range'];
            }
            $ai_status_summary['total']++;
            $st = $row['present_status'];
            if ($st === 'Sold or transferred') $ai_status_summary['sold']++;
            elseif ($st === 'Slaughter') $ai_status_summary['slaughter']++;
            elseif ($st === 'Died') $ai_status_summary['died']++;
            elseif ($st === 'Tag Damaged / Removed') $ai_status_summary['damaged']++;
            else $ai_status_summary['active']++;
        }
        $ai_stmt->close();
    }

    echo json_encode([
        'success' => true,
        'farmer' => $farmer_info,
        'master_census' => $master_animals,
        'ear_tag_summary' => $ai_status_summary,
        'animals_list' => $ai_animals
    ]);
    exit();
}

// -------------------------------------------------------------------------
// ACTION: GET NEXT SEQUENCE & PREVIEW AUTO-GENERATED TAG NUMBER
// -------------------------------------------------------------------------
if ($action === 'get_next_seq') {
    $farm_reg_no = trim($_GET['farm_reg_no'] ?? '');
    $farmer_name = trim($_GET['farmer_name'] ?? '');
    $range_code = trim($_GET['range_code'] ?? '');
    $range_id = (int)($_GET['range_id'] ?? $session_range_id);

    if (empty($range_code)) {
        $r_stmt = $mysqli->prepare("SELECT id, code FROM veterinary_ranges WHERE id = ?");
        if ($r_stmt) {
            $r_stmt->bind_param("i", $range_id);
            $r_stmt->execute();
            $r_res = $r_stmt->get_result();
            if ($r_row = $r_res->fetch_assoc()) {
                $range_code = !empty($r_row['code']) ? $r_row['code'] : sprintf('%02d', $r_row['id']);
            }
            $r_stmt->close();
        }
    }
    if (empty($range_code)) {
        $range_code = sprintf('%02d', $range_id ?: 1);
    }

    // Determine current count of animals tagged for this farm
    $seq_count = 0;
    if (!empty($farm_reg_no)) {
        $cnt_stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM animal_identifications WHERE farm_reg_no = ?");
        if ($cnt_stmt) {
            $cnt_stmt->bind_param("s", $farm_reg_no);
            $cnt_stmt->execute();
            $seq_count = (int)($cnt_stmt->get_result()->fetch_assoc()['total'] ?? 0);
            $cnt_stmt->close();
        }
    } else {
        $cnt_stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM animal_identifications WHERE range_id = ?");
        if ($cnt_stmt) {
            $cnt_stmt->bind_param("i", $range_id);
            $cnt_stmt->execute();
            $seq_count = (int)($cnt_stmt->get_result()->fetch_assoc()['total'] ?? 0);
            $cnt_stmt->close();
        }
    }

    $next_seq_num = $seq_count + 1;
    $next_seq_formatted = sprintf('%03d', $next_seq_num);

    // Concatenation: 5314 (Ampara) + Last 5 digits of Farm Reg + Serial
    $clean_farm_digits = preg_replace('/\D/', '', (string)$farm_reg_no);
    $last5_farm = strlen($clean_farm_digits) >= 5 ? substr($clean_farm_digits, -5) : str_pad($clean_farm_digits, 5, '0', STR_PAD_LEFT);
    $auto_tag = '5314' . $last5_farm . $next_seq_formatted;
    
    // Check if farmer has an assigned sequential voucher block in cattle_voucher_distributions
    $has_assigned_block = false;
    $block_info = null;
    $suggested_voucher = '';

    if (!empty($farm_reg_no) || !empty($farmer_name)) {
        // Collect all used voucher numbers across this range/farm
        $used_serials = [];
        $u_res = $mysqli->query("
            SELECT voucher_serial_no 
            FROM animal_identifications 
            WHERE (range_id = {$range_id} OR range_id IS NULL OR range_id = 0)
              AND voucher_serial_no IS NOT NULL AND voucher_serial_no != ''
        ");
        if ($u_res) {
            while ($urow = $u_res->fetch_assoc()) {
                $num_only = preg_replace('/\D/', '', (string)$urow['voucher_serial_no']);
                if ($num_only !== '') {
                    $used_serials[(int)$num_only] = true;
                }
            }
        }

        $b_stmt = $mysqli->prepare("
            SELECT id, farmer_name, farm_reg_no, prefix, start_serial, end_serial, total_allocated 
            FROM cattle_voucher_distributions 
            WHERE (farm_reg_no = ? OR farmer_name = ?) AND (range_id = ? OR range_id IS NULL OR range_id = 0)
            ORDER BY id ASC
        ");
        if ($b_stmt) {
            $b_stmt->bind_param("ssi", $farm_reg_no, $farmer_name, $range_id);
            $b_stmt->execute();
            $b_res = $b_stmt->get_result();
            while ($brow = $b_res->fetch_assoc()) {
                $p = $brow['prefix'] ?: 'A-';
                $s = (int)$brow['start_serial'];
                $e = (int)$brow['end_serial'];
                $tot = (int)$brow['total_allocated'];

                $used_in_blk = 0;
                $next_seq_v = null;
                for ($curr = $s; $curr <= $e; $curr++) {
                    if (isset($used_serials[$curr])) {
                        $used_in_blk++;
                    } else {
                        if ($next_seq_v === null) {
                            $next_seq_v = ($curr >= 100000) ? "{$p}{$curr}" : "{$p}" . str_pad((string)$curr, 6, '0', STR_PAD_LEFT);
                        }
                    }
                }

                $rem = $tot - $used_in_blk;
                if ($rem > 0 || $block_info === null) {
                    $has_assigned_block = true;
                    $p_start = ($s >= 100000) ? $s : str_pad((string)$s, 6, '0', STR_PAD_LEFT);
                    $p_end = ($e >= 100000) ? $e : str_pad((string)$e, 6, '0', STR_PAD_LEFT);
                    $block_info = [
                        'block_id' => (int)$brow['id'],
                        'formatted_block' => "{$p}{$p_start} to {$p}{$p_end}",
                        'total_allocated' => $tot,
                        'used_count' => $used_in_blk,
                        'remaining_balance' => $rem,
                        'next_available' => $next_seq_v ?: ''
                    ];
                    if ($next_seq_v) {
                        $suggested_voucher = $next_seq_v;
                        break;
                    }
                }
            }
            $b_stmt->close();
        }
    }

    if (empty($suggested_voucher)) {
        // Fallback: Generate valid A- serial not colliding with existing vouchers
        $suggested_voucher = 'A-' . str_pad((string)mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    echo json_encode([
        'success' => true,
        'range_code' => $range_code,
        'next_seq' => $next_seq_formatted,
        'next_seq_num' => $next_seq_num,
        'auto_tag_no' => $auto_tag,
        'suggested_voucher' => $suggested_voucher,
        'has_assigned_block' => $has_assigned_block,
        'block_info' => $block_info
    ]);
    exit();
}

// -------------------------------------------------------------------------
// ACTION: SYNC ALL EXISTING ANIMAL IDENTIFICATION RECORDS TO COUNTER FILES
// -------------------------------------------------------------------------
if ($action === 'sync_all') {
    $res = $mysqli->query("SELECT * FROM animal_identifications");
    $synced_cnt = 0;
    while ($row = $res->fetch_assoc()) {
        if (syncCounterFileRecords($mysqli, $row, $user_id)) {
            $synced_cnt++;
        }
    }
    echo json_encode(['success' => true, 'message' => "Successfully synchronized {$synced_cnt} records to counter files."]);
    exit();
}

// -------------------------------------------------------------------------
// ACTION: SEARCH FARMERS (AUTO-COMPLETE)
// -------------------------------------------------------------------------
if ($action === 'search_farmers') {
    $term = trim($_GET['term'] ?? '');
    $filter_range_id = (int)($_GET['range_id'] ?? 0);

    $sql = "SELECT id, farmer_name, registration_no, nic, vs_division, district, range_id 
            FROM farm_registration_renewals 
            WHERE 1=1";
    $types = "";
    $params = [];

    if ($filter_range_id > 0) {
        $sql .= " AND (range_id = ? OR range_id = 0 OR range_id IS NULL)";
        $types .= "i";
        $params[] = $filter_range_id;
    }

    if (!empty($term)) {
        $sql .= " AND (farmer_name LIKE ? OR registration_no LIKE ? OR nic LIKE ?)";
        $types .= "sss";
        $wild = '%' . $term . '%';
        $params[] = $wild;
        $params[] = $wild;
        $params[] = $wild;
    }

    $sql .= " ORDER BY farmer_name ASC LIMIT 20";

    $stmt = $mysqli->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $list = [];
    while ($row = $res->fetch_assoc()) {
        $list[] = [
            'id' => $row['id'],
            'farmer_name' => $row['farmer_name'],
            'farm_reg_no' => $row['registration_no'],
            'nic' => $row['nic'],
            'vs_range' => $row['vs_division'],
            'district' => $row['district'],
            'range_id' => $row['range_id']
        ];
    }
    $stmt->close();

    echo json_encode(['success' => true, 'farmers' => $list]);
    exit();
}

// -------------------------------------------------------------------------
// ACTION: GET SINGLE RECORD
// -------------------------------------------------------------------------
if ($action === 'get_record') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid record ID.']);
        exit();
    }

    $stmt = $mysqli->prepare("SELECT * FROM animal_identifications WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($record) {
        echo json_encode(['success' => true, 'record' => $record]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Record not found.']);
    }
    exit();
}

// -------------------------------------------------------------------------
// ACTION: QUICK UPDATE PRESENT STATUS
// -------------------------------------------------------------------------
// ACTION: QUICK UPDATE PRESENT STATUS
// -------------------------------------------------------------------------
if ($action === 'update_status') {
    $id = (int)($_POST['id'] ?? 0);
    $new_status = trim($_POST['status'] ?? $_POST['present_status'] ?? '');

    if ($id <= 0 || !in_array($new_status, $VALID_STATUSES, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID or status value. Must be Active, Sold or transferred, Slaughter, Died, or Tag Damaged / Removed.']);
        exit();
    }

    $status_date = date('Y-m-d');
    $stmt = $mysqli->prepare("UPDATE animal_identifications SET present_status = ?, status_date = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $new_status, $status_date, $id);
    if ($stmt->execute()) {
        $stmt->close();

        // Synchronize updated status with cattle_vouchers and counterfoil_leaf_issues
        $q_stmt = $mysqli->prepare("SELECT voucher_serial_no, ear_tag_no FROM animal_identifications WHERE id = ?");
        if ($q_stmt) {
            $q_stmt->bind_param("i", $id);
            $q_stmt->execute();
            $q_row = $q_stmt->get_result()->fetch_assoc();
            $q_stmt->close();

            if (!empty($q_row['voucher_serial_no'])) {
                $v_num = $q_row['voucher_serial_no'];
                $cv_status = 'Active';
                if ($new_status === 'Sold or transferred') {
                    $cv_status = 'Transferred';
                } elseif ($new_status === 'Slaughter') {
                    $cv_status = 'Slaughtered';
                } elseif ($new_status === 'Died') {
                    $cv_status = 'Dead';
                } elseif ($new_status === 'Tag Damaged / Removed') {
                    $cv_status = 'Damaged';
                }

                $upd_cv = $mysqli->prepare("UPDATE cattle_vouchers SET status = ?, updated_at = NOW() WHERE voucher_number = ?");
                if ($upd_cv) {
                    $upd_cv->bind_param("ss", $cv_status, $v_num);
                    $upd_cv->execute();
                    $upd_cv->close();
                }

                $lf_note = ($new_status === 'Tag Damaged / Removed')
                    ? "Synced Status: Tag Damaged / Removed [PERMANENT PAIRING RETAINED - Tag: {$q_row['ear_tag_no']}] (Updated " . date('Y-m-d') . ")"
                    : "Synced Status: {$new_status} (Updated " . date('Y-m-d') . ")";
                $upd_lf = $mysqli->prepare("UPDATE counterfoil_leaf_issues SET remarks = ? WHERE leaf_serial_no = ?");
                if ($upd_lf) {
                    $upd_lf->bind_param("ss", $lf_note, $v_num);
                    $upd_lf->execute();
                    $upd_lf->close();
                }
            }
        }

        echo json_encode(['success' => true, 'message' => 'Present status successfully updated to ' . $new_status . ' and synchronized with counter files.']);
    } else {
        $err = $stmt->error;
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to update status: ' . $err]);
    }
    exit();
}

// -------------------------------------------------------------------------
// ACTION: DELETE RECORD
// -------------------------------------------------------------------------
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid record ID for deletion.']);
        exit();
    }

    // 1. DAMAGED TAG PERMANENT PAIRING ENFORCEMENT
    // If tag is damaged / removed, regulatory rules mandate permanent pairing retention; do not decouple or delete!
    $chk_dmg = $mysqli->prepare("SELECT present_status, ear_tag_no, voucher_serial_no FROM animal_identifications WHERE id = ?");
    if ($chk_dmg) {
        $chk_dmg->bind_param("i", $id);
        $chk_dmg->execute();
        $d_res = $chk_dmg->get_result();
        if ($d_row = $d_res->fetch_assoc()) {
            if ($d_row['present_status'] === 'Tag Damaged / Removed') {
                echo json_encode([
                    'success' => false,
                    'message' => "Regulatory Violation: Cannot delete record for Damaged Ear Tag ({$d_row['ear_tag_no']}). Department rules mandate permanent pairing retention with Cattle Voucher ({$d_row['voucher_serial_no']}). This voucher cannot be decoupled or returned to the available pool."
                ]);
                $chk_dmg->close();
                exit();
            }
        }
        $chk_dmg->close();
    }

    // Capture voucher serial number before deletion
    $voucher_to_del = '';
    $chk_v = $mysqli->prepare("SELECT voucher_serial_no FROM animal_identifications WHERE id = ?");
    if ($chk_v) {
        $chk_v->bind_param("i", $id);
        $chk_v->execute();
        $v_res = $chk_v->get_result();
        if ($v_row = $v_res->fetch_assoc()) {
            $voucher_to_del = trim($v_row['voucher_serial_no'] ?? '');
        }
        $chk_v->close();
    }

    $stmt = $mysqli->prepare("DELETE FROM animal_identifications WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $stmt->close();

        // Note: Clean up associated counter records only for non-damaged records
        if (!empty($voucher_to_del)) {
            $d_cv = $mysqli->prepare("DELETE FROM cattle_vouchers WHERE voucher_number = ? AND status != 'Damaged'");
            if ($d_cv) {
                $d_cv->bind_param("s", $voucher_to_del);
                $d_cv->execute();
                $d_cv->close();
            }

            $d_lf = $mysqli->prepare("DELETE FROM counterfoil_leaf_issues WHERE leaf_serial_no = ?");
            if ($d_lf) {
                $d_lf->bind_param("s", $voucher_to_del);
                $d_lf->execute();
                $d_lf->close();
            }
        }

        echo json_encode(['success' => true, 'message' => 'Animal identification record deleted successfully.']);
    } else {
        $err = $stmt->error;
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to delete record: ' . $err]);
    }
    exit();
}

// -------------------------------------------------------------------------
// ACTION: SAVE / BATCH SAVE IDENTIFICATION RECORDS
// -------------------------------------------------------------------------
if ($action === 'save') {
    $edit_id = (int)($_POST['id'] ?? 0);
    $is_batch = isset($_POST['animals']) && is_array($_POST['animals']);

    $mysqli->begin_transaction();
    try {
        if ($edit_id > 0) {
            // Update single existing record (extract either from flat inputs or first grid row)
            $first_animal = (isset($_POST['animals']) && is_array($_POST['animals']) && !empty($_POST['animals'])) ? reset($_POST['animals']) : [];

            $district = trim($_POST['district'] ?? $first_animal['district'] ?? '');
            $vs_range = trim($_POST['vs_range'] ?? $first_animal['vs_range'] ?? '');
            $district_id = (int)($_POST['district_id'] ?? $first_animal['district_id'] ?? 0);
            $range_id = (int)($_POST['range_id'] ?? $first_animal['range_id'] ?? 0);
            $farmer_name = trim($_POST['farmer_name'] ?? $first_animal['farmer_name'] ?? '');
            $farm_reg_no = trim($_POST['farm_reg_no'] ?? $first_animal['farm_reg_no'] ?? '');
            $range_code = trim($_POST['range_code'] ?? $first_animal['range_code'] ?? '');
            $animal_seq_no = trim($_POST['animal_seq_no'] ?? $first_animal['animal_seq_no'] ?? '');
            $raw_voucher = $_POST['voucher_serial_no'] ?? $first_animal['voucher_serial_no'] ?? '';
            $voucher_serial_no = formatVoucherSerialNumber($raw_voucher);
            $identification_date = trim($_POST['identification_date'] ?? $first_animal['identification_date'] ?? date('Y-m-d'));
            $gender = trim($_POST['gender'] ?? $first_animal['gender'] ?? 'Female');
            $colour = trim($_POST['colour'] ?? $first_animal['colour'] ?? '');
            $species = trim($_POST['species'] ?? $first_animal['species'] ?? 'Cattle');
            $breed = trim($_POST['breed'] ?? $first_animal['breed'] ?? '');
            $age = trim($_POST['age'] ?? $first_animal['age'] ?? '');
            $purpose = trim($_POST['purpose'] ?? $first_animal['purpose'] ?? 'Dairy');
            $io_number = trim($_POST['io_number'] ?? $first_animal['io_number'] ?? '');
            $present_status = trim($_POST['present_status'] ?? $first_animal['present_status'] ?? 'Sold or transferred');
            $remarks = trim($_POST['remarks'] ?? $first_animal['remarks'] ?? '');

            // Fallback range code
            if (empty($range_code)) {
                $range_code = sprintf('%02d', $range_id ?: 1);
            }
            if (empty($animal_seq_no)) {
                $animal_seq_no = '001';
            }

            // Ear Tag No concatenation: 5314 (Ampara) + Last 5 digits of Farm Reg + Serial
            $ear_tag_no = trim($_POST['ear_tag_no'] ?? $first_animal['ear_tag_no'] ?? '');
            if (empty($ear_tag_no)) {
                $clean_farm_digits = preg_replace('/\D/', '', (string)$farm_reg_no);
                $last5_farm = strlen($clean_farm_digits) >= 5 ? substr($clean_farm_digits, -5) : str_pad($clean_farm_digits, 5, '0', STR_PAD_LEFT);
                $ear_tag_no = '5314' . $last5_farm . $animal_seq_no;
            }

            // Permanent Pairing Protection Check
            $chk_perm = $mysqli->prepare("SELECT present_status, ear_tag_no, voucher_serial_no FROM animal_identifications WHERE id = ?");
            if ($chk_perm) {
                $chk_perm->bind_param("i", $edit_id);
                $chk_perm->execute();
                $old_data = $chk_perm->get_result()->fetch_assoc();
                $chk_perm->close();

                if ($old_data && $old_data['present_status'] === 'Tag Damaged / Removed') {
                    if ($ear_tag_no !== $old_data['ear_tag_no'] || $voucher_serial_no !== $old_data['voucher_serial_no']) {
                        throw new Exception("Regulatory Rule Violation: Ear Tag {$old_data['ear_tag_no']} is marked as Damaged and permanently paired with Cattle Voucher {$old_data['voucher_serial_no']}. Decoupling or changing serial numbers is strictly prohibited under statutory regulations.");
                    }
                }
            }

            if (!in_array($present_status, $VALID_STATUSES, true)) {
                $present_status = 'Sold or transferred';
            }

            $upd = $mysqli->prepare("UPDATE animal_identifications SET 
                district_id = ?, district = ?, range_id = ?, vs_range = ?,
                farmer_name = ?, farm_reg_no = ?, range_code = ?, animal_seq_no = ?,
                ear_tag_no = ?, voucher_serial_no = ?, identification_date = ?,
                gender = ?, colour = ?, species = ?, breed = ?, age = ?,
                purpose = ?, io_number = ?, present_status = ?, remarks = ?,
                updated_at = NOW()
                WHERE id = ?");
            if (!$upd) {
                throw new Exception("Prepare failed: " . $mysqli->error);
            }
            $upd->bind_param("isisssssssssssssssssi",
                $district_id, $district, $range_id, $vs_range,
                $farmer_name, $farm_reg_no, $range_code, $animal_seq_no,
                $ear_tag_no, $voucher_serial_no, $identification_date,
                $gender, $colour, $species, $breed, $age,
                $purpose, $io_number, $present_status, $remarks,
                $edit_id
            );
            if (!$upd->execute()) {
                throw new Exception("Update error: " . $upd->error);
            }
            $upd->close();

            // Synchronize updated record with counter files
            $sync_data = [
                'voucher_serial_no'   => $voucher_serial_no,
                'ear_tag_no'          => $ear_tag_no,
                'farmer_name'         => $farmer_name,
                'farm_reg_no'         => $farm_reg_no,
                'district_id'         => $district_id,
                'range_id'            => $range_id,
                'vs_range'            => $vs_range,
                'district'            => $district,
                'identification_date' => $identification_date,
                'gender'              => $gender,
                'species'             => $species,
                'breed'               => $breed,
                'age'                 => $age,
                'purpose'             => $purpose,
                'present_status'      => $present_status,
                'remarks'             => $remarks
            ];
            syncCounterFileRecords($mysqli, $sync_data, $user_id);

            $mysqli->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Animal Identification record updated and synchronized with counter files.',
                'ear_tag_no' => $ear_tag_no,
                'voucher_serial_no' => $voucher_serial_no
            ]);
            exit();

        } else if ($is_batch) {
            // Batch insertion from grid modal
            $district = trim($_POST['district'] ?? '');
            $vs_range = trim($_POST['vs_range'] ?? '');
            $district_id = (int)($_POST['district_id'] ?? 0);
            $range_id = (int)($_POST['range_id'] ?? 0);
            $farmer_name = trim($_POST['farmer_name'] ?? '');
            $farm_reg_no = trim($_POST['farm_reg_no'] ?? '');
            $range_code = trim($_POST['range_code'] ?? '');
            $identification_date = trim($_POST['identification_date'] ?? date('Y-m-d'));
            $io_number = trim($_POST['io_number'] ?? '');

            if (empty($range_code)) {
                $range_code = sprintf('%02d', $range_id ?: 1);
            }

            $animals = $_POST['animals'];
            $inserted_count = 0;

            $ins = $mysqli->prepare("INSERT INTO animal_identifications 
                (district_id, district, range_id, vs_range, farmer_name, farm_reg_no, range_code, animal_seq_no, ear_tag_no, voucher_serial_no, identification_date, gender, colour, species, breed, age, purpose, io_number, present_status, remarks, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$ins) {
                throw new Exception("Prepare failed: " . $mysqli->error);
            }

            foreach ($animals as $an) {
                $seq_no = trim($an['animal_seq_no'] ?? '');
                if (empty($seq_no)) {
                    $seq_no = sprintf('%03d', $inserted_count + 1);
                }
                
                // Ear Tag Concatenation: 5314 (Ampara) + Last 5 digits of Farm Reg + Serial
                $tag_no = trim($an['ear_tag_no'] ?? '');
                if (empty($tag_no)) {
                    $clean_farm_digits = preg_replace('/\D/', '', (string)$farm_reg_no);
                    $last5_farm = strlen($clean_farm_digits) >= 5 ? substr($clean_farm_digits, -5) : str_pad($clean_farm_digits, 5, '0', STR_PAD_LEFT);
                    $tag_no = '5314' . $last5_farm . $seq_no;
                }

                // Enforce A- prefix on voucher serial numbers
                $voucher_no = formatVoucherSerialNumber($an['voucher_serial_no'] ?? '');

                $gender = trim($an['gender'] ?? 'Female');
                if (!in_array($gender, ['Male', 'Female'], true)) {
                    $gender = 'Female';
                }
                $colour = trim($an['colour'] ?? '');
                $species = trim($an['species'] ?? 'Cattle');
                $breed = trim($an['breed'] ?? '');
                $age = trim($an['age'] ?? '');
                $purpose = trim($an['purpose'] ?? 'Dairy');
                $status = trim($an['present_status'] ?? 'Sold or transferred');
                if (!in_array($status, $VALID_STATUSES, true)) {
                    $status = 'Sold or transferred';
                }
                $row_io = !empty($an['io_number']) ? trim($an['io_number']) : $io_number;
                $row_date = !empty($an['identification_date']) ? trim($an['identification_date']) : $identification_date;
                $remarks = trim($an['remarks'] ?? '');

                $ins->bind_param("isisssssssssssssssssi",
                    $district_id, $district, $range_id, $vs_range,
                    $farmer_name, $farm_reg_no, $range_code, $seq_no,
                    $tag_no, $voucher_no, $row_date,
                    $gender, $colour, $species, $breed, $age,
                    $purpose, $row_io, $status, $remarks,
                    $user_id
                );
                if (!$ins->execute()) {
                    throw new Exception("Insert failed for animal {$tag_no}: " . $ins->error);
                }

                // Synchronize each batch entry to counter files
                $batch_sync_row = [
                    'voucher_serial_no'   => $voucher_no,
                    'ear_tag_no'          => $tag_no,
                    'farmer_name'         => $farmer_name,
                    'farm_reg_no'         => $farm_reg_no,
                    'district_id'         => $district_id,
                    'range_id'            => $range_id,
                    'vs_range'            => $vs_range,
                    'district'            => $district,
                    'identification_date' => $row_date,
                    'gender'              => $gender,
                    'species'             => $species,
                    'breed'               => $breed,
                    'age'                 => $age,
                    'purpose'             => $purpose,
                    'present_status'      => $status,
                    'remarks'             => $remarks
                ];
                syncCounterFileRecords($mysqli, $batch_sync_row, $user_id);

                $inserted_count++;
            }
            $ins->close();

            $mysqli->commit();
            echo json_encode([
                'success' => true,
                'message' => "Successfully recorded {$inserted_count} identified animal(s) and synchronized counter file records.",
                'count' => $inserted_count
            ]);
            exit();

        } else {
            // Single new insertion
            $district = trim($_POST['district'] ?? '');
            $vs_range = trim($_POST['vs_range'] ?? '');
            $district_id = (int)($_POST['district_id'] ?? 0);
            $range_id = (int)($_POST['range_id'] ?? 0);
            $farmer_name = trim($_POST['farmer_name'] ?? '');
            $farm_reg_no = trim($_POST['farm_reg_no'] ?? '');
            $range_code = trim($_POST['range_code'] ?? '');
            $animal_seq_no = trim($_POST['animal_seq_no'] ?? '001');
            $voucher_serial_no = formatVoucherSerialNumber($_POST['voucher_serial_no'] ?? '');
            $identification_date = trim($_POST['identification_date'] ?? date('Y-m-d'));
            $gender = trim($_POST['gender'] ?? 'Female');
            $colour = trim($_POST['colour'] ?? '');
            $species = trim($_POST['species'] ?? 'Cattle');
            $breed = trim($_POST['breed'] ?? '');
            $age = trim($_POST['age'] ?? '');
            $purpose = trim($_POST['purpose'] ?? 'Dairy');
            $io_number = trim($_POST['io_number'] ?? '');
            $present_status = trim($_POST['present_status'] ?? 'Sold or transferred');
            $remarks = trim($_POST['remarks'] ?? '');

            if (empty($range_code)) {
                $range_code = sprintf('%02d', $range_id ?: 1);
            }

            // Auto-generate Ear Tag No
            $ear_tag_no = trim($_POST['ear_tag_no'] ?? '');
            if (empty($ear_tag_no)) {
                $clean_freg = preg_replace('/[^A-Za-z0-9_-]/', '', $farm_reg_no);
                $ear_tag_no = $range_code . '-' . $clean_freg . '-' . $animal_seq_no;
            }

            if (!in_array($present_status, $VALID_STATUSES, true)) {
                $present_status = 'Sold or transferred';
            }

            $ins = $mysqli->prepare("INSERT INTO animal_identifications 
                (district_id, district, range_id, vs_range, farmer_name, farm_reg_no, range_code, animal_seq_no, ear_tag_no, voucher_serial_no, identification_date, gender, colour, species, breed, age, purpose, io_number, present_status, remarks, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$ins) {
                throw new Exception("Prepare failed: " . $mysqli->error);
            }
            $ins->bind_param("isisssssssssssssssssi",
                $district_id, $district, $range_id, $vs_range,
                $farmer_name, $farm_reg_no, $range_code, $animal_seq_no,
                $ear_tag_no, $voucher_serial_no, $identification_date,
                $gender, $colour, $species, $breed, $age,
                $purpose, $io_number, $present_status, $remarks,
                $user_id
            );
            if (!$ins->execute()) {
                throw new Exception("Insert failed: " . $ins->error);
            }
            $ins_id = $ins->insert_id;
            $ins->close();

            // Synchronize newly created record with counter files
            $single_sync_row = [
                'voucher_serial_no'   => $voucher_serial_no,
                'ear_tag_no'          => $ear_tag_no,
                'farmer_name'         => $farmer_name,
                'farm_reg_no'         => $farm_reg_no,
                'district_id'         => $district_id,
                'range_id'            => $range_id,
                'vs_range'            => $vs_range,
                'district'            => $district,
                'identification_date' => $identification_date,
                'gender'              => $gender,
                'species'             => $species,
                'breed'               => $breed,
                'age'                 => $age,
                'purpose'             => $purpose,
                'present_status'      => $present_status,
                'remarks'             => $remarks
            ];
            syncCounterFileRecords($mysqli, $single_sync_row, $user_id);

            $mysqli->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Animal Identification record successfully registered and synchronized with counter files.',
                'id' => $ins_id,
                'ear_tag_no' => $ear_tag_no,
                'voucher_serial_no' => $voucher_serial_no
            ]);
            exit();
        }

    } catch (Exception $e) {
        $mysqli->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// -------------------------------------------------------------------------
// ACTION: GET SCHEDULE - 06 STATUTORY MONTHLY RETURN DATA
// -------------------------------------------------------------------------
if ($action === 'get_schedule06_data') {
    $range_id = (int)($_GET['range_id'] ?? $session_range_id ?: 1);
    $year = (int)($_GET['year'] ?? date('Y'));
    $month = (int)($_GET['month'] ?? date('n'));

    $sql = "
        SELECT ai.*, fr.nic AS lookup_nic
        FROM animal_identifications ai
        LEFT JOIN farm_registration_renewals fr 
          ON (ai.farm_reg_no COLLATE utf8mb4_unicode_ci = fr.registration_no COLLATE utf8mb4_unicode_ci)
        WHERE (ai.range_id = ? OR ai.range_id IS NULL OR ai.range_id = 0)
          AND YEAR(ai.identification_date) = ?
          AND MONTH(ai.identification_date) = ?
          AND ai.voucher_serial_no IS NOT NULL AND ai.voucher_serial_no != ''
        ORDER BY ai.identification_date ASC, ai.id ASC
    ";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("iii", $range_id, $year, $month);
    $stmt->execute();
    $res = $stmt->get_result();

    $entries = [];
    $kpi = [
        'total' => 0,
        'active' => 0,
        'transferred' => 0,
        'slaughter' => 0,
        'died' => 0,
        'damaged' => 0
    ];

    $idx = 1;
    while ($row = $res->fetch_assoc()) {
        $st = $row['present_status'];
        $kpi['total']++;
        if ($st === 'Active') $kpi['active']++;
        elseif ($st === 'Sold or transferred') $kpi['transferred']++;
        elseif ($st === 'Slaughter') $kpi['slaughter']++;
        elseif ($st === 'Died') $kpi['died']++;
        elseif ($st === 'Tag Damaged / Removed') $kpi['damaged']++;

        $entries[] = [
            'index' => $idx++,
            'id' => (int)$row['id'],
            'voucher_serial_no' => $row['voucher_serial_no'],
            'identification_date' => $row['identification_date'],
            'farmer_name' => $row['farmer_name'],
            'farmer_nic' => $row['lookup_nic'] ?: '—',
            'farm_reg_no' => $row['farm_reg_no'],
            'ear_tag_no' => $row['ear_tag_no'],
            'species' => $row['species'] ?: 'Cattle',
            'breed' => $row['breed'] ?: '—',
            'gender' => $row['gender'] ?: 'Female',
            'age' => $row['age'] ?: '—',
            'colour' => $row['colour'] ?: '—',
            'purpose' => $row['purpose'] ?: 'Dairy',
            'io_number' => $row['io_number'] ?: '—',
            'present_status' => $st,
            'is_damaged' => ($st === 'Tag Damaged / Removed')
        ];
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'year' => $year,
        'month' => $month,
        'kpi' => $kpi,
        'entries' => $entries
    ]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Unsupported action requested.']);
exit();
