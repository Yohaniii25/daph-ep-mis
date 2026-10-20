<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

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
    if (isset($_REQUEST['ajax'])) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access clearance profile.']);
        exit();
    }
    die("Access denied: Invalid authentication clearance profile.");
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
$session_range_id = (int)($_SESSION['range_id'] ?? 0);
$action = trim($_REQUEST['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '');
if (!function_exists('cleanText')) {
    function cleanText($val) {
        return trim((string)($val ?? ''));
    }
}

$range_id = (int)($_REQUEST['range_id'] ?? $session_range_id ?: 1);

// Query to get district_id from veterinary_ranges
$district_id = 1;
if (!empty($range_id)) {
    $r_query = $mysqli->prepare("SELECT district_id FROM veterinary_ranges WHERE id = ?");
    if ($r_query) {
        $r_query->bind_param("i", $range_id);
        $r_query->execute();
        $r_result = $r_query->get_result();
        if ($r_row = $r_result->fetch_assoc()) {
            $district_id = (int)$r_row['district_id'];
        }
        $r_query->close();
    }
}

/**
 * Helper to compute automated monthly metrics from live registrations
 */
function computeMonthlyMetrics($mysqli, $range_id, $report_year, $report_month) {
    // 1. Opening Balance
    $opening_balance = 0;
    if ($report_month === 1) {
        $prev_y = $report_year - 1;
        $seed_q = $mysqli->prepare("SELECT closing_balance FROM cattle_voucher_usage WHERE range_id = ? AND report_year = ? AND report_month = 12 LIMIT 1");
        if ($seed_q) {
            $seed_q->bind_param("ii", $range_id, $prev_y);
            $seed_q->execute();
            $s_res = $seed_q->get_result();
            if ($sr = $s_res->fetch_assoc()) {
                $opening_balance = (int)$sr['closing_balance'];
            }
            $seed_q->close();
        }
    } else {
        $prev_m = $report_month - 1;
        $prev_q = $mysqli->prepare("SELECT closing_balance FROM cattle_voucher_usage WHERE range_id = ? AND report_year = ? AND report_month = ? LIMIT 1");
        if ($prev_q) {
            $prev_q->bind_param("iii", $range_id, $report_year, $prev_m);
            $prev_q->execute();
            $p_res = $prev_q->get_result();
            if ($pr = $p_res->fetch_assoc()) {
                $opening_balance = (int)$pr['closing_balance'];
            }
            $prev_q->close();
        }
    }

    // 2. Used Vouchers Count (Live from animal_identifications)
    $used_qty = 0;
    $u_stmt = $mysqli->prepare("
        SELECT COUNT(*) AS total_used 
        FROM animal_identifications 
        WHERE (range_id = ? OR range_id IS NULL OR range_id = 0)
          AND YEAR(identification_date) = ? AND MONTH(identification_date) = ? 
          AND voucher_serial_no IS NOT NULL AND voucher_serial_no != ''
    ");
    if ($u_stmt) {
        $u_stmt->bind_param("iii", $range_id, $report_year, $report_month);
        $u_stmt->execute();
        $used_qty = (int)($u_stmt->get_result()->fetch_assoc()['total_used'] ?? 0);
        $u_stmt->close();
    }

    // 3. Damaged Tags Count (Live from animal_identifications)
    $damaged_qty = 0;
    $d_stmt = $mysqli->prepare("
        SELECT COUNT(*) AS total_damaged 
        FROM animal_identifications 
        WHERE (range_id = ? OR range_id IS NULL OR range_id = 0)
          AND YEAR(identification_date) = ? AND MONTH(identification_date) = ? 
          AND present_status = 'Tag Damaged / Removed'
    ");
    if ($d_stmt) {
        $d_stmt->bind_param("iii", $range_id, $report_year, $report_month);
        $d_stmt->execute();
        $damaged_qty = (int)($d_stmt->get_result()->fetch_assoc()['total_damaged'] ?? 0);
        $d_stmt->close();
    }

    // 4. Distributed serial blocks allocated in this month
    $distributed_blocks = [];
    $dist_stmt = $mysqli->prepare("
        SELECT farmer_name, farm_reg_no, prefix, start_serial, end_serial, total_allocated, distribution_date 
        FROM cattle_voucher_distributions 
        WHERE range_id = ? AND YEAR(distribution_date) = ? AND MONTH(distribution_date) = ?
        ORDER BY id ASC
    ");
    if ($dist_stmt) {
        $dist_stmt->bind_param("iii", $range_id, $report_year, $report_month);
        $dist_stmt->execute();
        $dist_res = $dist_stmt->get_result();
        while ($drow = $dist_res->fetch_assoc()) {
            $prefix = $drow['prefix'] ?: 'A-';
            $p_start = $drow['start_serial'] >= 100000 ? $drow['start_serial'] : str_pad((string)$drow['start_serial'], 6, '0', STR_PAD_LEFT);
            $p_end = $drow['end_serial'] >= 100000 ? $drow['end_serial'] : str_pad((string)$drow['end_serial'], 6, '0', STR_PAD_LEFT);
            $distributed_blocks[] = [
                'farmer_name' => $drow['farmer_name'],
                'farm_reg_no' => $drow['farm_reg_no'],
                'range_label' => "{$prefix}{$p_start} to {$prefix}{$p_end}",
                'total' => (int)$drow['total_allocated'],
                'date' => $drow['distribution_date']
            ];
        }
        $dist_stmt->close();
    }

    return [
        'opening_balance' => $opening_balance,
        'used_qty' => $used_qty,
        'damaged_qty' => $damaged_qty,
        'distributed_blocks' => $distributed_blocks
    ];
}

// ACTION: GET NEXT SERIAL NUMBER (e.g., APH/CV/2026/0001)
// -----------------------------------------------------------------------------
if ($action === 'get_next_serial') {
    $year = (int)($_GET['year'] ?? date('Y'));
    $prefix = "APH/CV/{$year}/";
    
    // Find highest serial matching pattern APH/CV/YYYY/XXXX or any CV
    $stmt = $mysqli->prepare("
        SELECT voucher_number 
        FROM cattle_vouchers 
        WHERE voucher_number LIKE CONCAT(?, '%') 
        ORDER BY id DESC 
        LIMIT 50
    ");
    $next_num = 1;
    if ($stmt) {
        $stmt->bind_param("s", $prefix);
        $stmt->execute();
        $res = $stmt->get_result();
        $max_found = 0;
        while ($row = $res->fetch_assoc()) {
            $vn = $row['voucher_number'];
            if (preg_match('/APH\/CV\/\d{4}\/(\d+)/', $vn, $matches)) {
                $num = (int)$matches[1];
                if ($num > $max_found) $max_found = $num;
            }
        }
        $stmt->close();
        if ($max_found > 0) {
            $next_num = $max_found + 1;
        } else {
            // Also count total vouchers to give sensible start
            $c_res = $mysqli->query("SELECT COUNT(*) FROM cattle_vouchers");
            $total_existing = $c_res ? (int)$c_res->fetch_row()[0] : 0;
            $next_num = $total_existing + 1;
        }
    }
    
    $suggested_serial = $prefix . str_pad((string)$next_num, 4, '0', STR_PAD_LEFT);
    echo json_encode([
        'success' => true,
        'serial' => $suggested_serial,
        'year' => $year,
        'sequence' => $next_num
    ]);
    exit();
}

// -----------------------------------------------------------------------------
// ACTION: LOOKUP OWNER & HOLDING BY NIC OR FARM REG NO
// -----------------------------------------------------------------------------
if ($action === 'lookup_owner') {
    $nic = cleanText($_REQUEST['nic'] ?? '');
    $farm_reg_no = cleanText($_REQUEST['farm_reg_no'] ?? '');

    if (empty($nic) && empty($farm_reg_no)) {
        echo json_encode(['success' => false, 'message' => 'Please provide an NIC or Farm Registration Number.']);
        exit();
    }

    $found = false;
    $owner = null;

    // 1. Search in farm_registration_renewals (Master Registry)
    if (!empty($nic)) {
        $clean_nic = preg_replace('/[^0-9a-zA-Z]/', '', $nic);
        $stmt = $mysqli->prepare("
            SELECT id, farmer_name, nic, registration_no, farmer_address, farm_address,
                   province, district, ds_division, vs_division, gn_division, range_id, district_id
            FROM farm_registration_renewals
            WHERE nic = ? OR LOWER(TRIM(nic)) = LOWER(?) OR REPLACE(nic, ' ', '') = ?
            ORDER BY id DESC LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param("sss", $nic, $nic, $clean_nic);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($r = $res->fetch_assoc()) {
                $found = true;
                $owner = [
                    'owner_title'     => 'Mr.',
                    'farmer_name'     => $r['farmer_name'],
                    'farmer_nic'      => $r['nic'],
                    'farmer_address'  => $r['farmer_address'] ?: $r['farm_address'] ?: '',
                    'farm_reg_no'     => $r['registration_no'] ?: '',
                    'province'        => $r['province'] ?: 'Eastern Province',
                    'district'        => $r['district'] ?: '',
                    'ds_division'     => $r['ds_division'] ?: '',
                    'vs_division'     => $r['vs_division'] ?: '',
                    'gn_division'     => $r['gn_division'] ?: '',
                    'range_id'        => (int)($r['range_id'] ?? 0),
                    'district_id'     => (int)($r['district_id'] ?? 0),
                    'source'          => 'farm_registration_renewals'
                ];
            }
            $stmt->close();
        }
    }

    if (!$found && !empty($farm_reg_no)) {
        $clean_reg = preg_replace('/[^0-9a-zA-Z]/', '', $farm_reg_no);
        $stmt = $mysqli->prepare("
            SELECT id, farmer_name, nic, registration_no, farmer_address, farm_address,
                   province, district, ds_division, vs_division, gn_division, range_id, district_id
            FROM farm_registration_renewals
            WHERE registration_no = ? 
               OR LOWER(TRIM(registration_no)) = LOWER(?)
               OR REPLACE(REPLACE(registration_no, ' ', ''), '-', '') = ?
            ORDER BY id DESC LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param("sss", $farm_reg_no, $farm_reg_no, $clean_reg);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($r = $res->fetch_assoc()) {
                $found = true;
                $owner = [
                    'owner_title'     => 'Mr.',
                    'farmer_name'     => $r['farmer_name'],
                    'farmer_nic'      => $r['nic'] ?: '',
                    'farmer_address'  => $r['farmer_address'] ?: $r['farm_address'] ?: '',
                    'farm_reg_no'     => $r['registration_no'] ?: '',
                    'province'        => $r['province'] ?: 'Eastern Province',
                    'district'        => $r['district'] ?: '',
                    'ds_division'     => $r['ds_division'] ?: '',
                    'vs_division'     => $r['vs_division'] ?: '',
                    'gn_division'     => $r['gn_division'] ?: '',
                    'range_id'        => (int)($r['range_id'] ?? 0),
                    'district_id'     => (int)($r['district_id'] ?? 0),
                    'source'          => 'farm_registration_renewals'
                ];
            }
            $stmt->close();
        }
    }

    // 2. Search in farmers table fallback
    if (!$found && !empty($nic)) {
        $stmt2 = $mysqli->prepare("
            SELECT f.*, COALESCE(vr.name, '') AS vs_range, COALESCE(d.name, '') AS district_name
            FROM farmers f
            LEFT JOIN veterinary_ranges vr ON vr.id = f.range_id
            LEFT JOIN districts d ON d.id = f.district_id
            WHERE f.nic_no = ? OR LOWER(TRIM(f.nic_no)) = LOWER(?)
            ORDER BY f.id DESC LIMIT 1
        ");
        if ($stmt2) {
            $stmt2->bind_param("ss", $nic, $nic);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            if ($r2 = $res2->fetch_assoc()) {
                $found = true;
                $owner = [
                    'owner_title'     => 'Mr.',
                    'farmer_name'     => $r2['full_name'],
                    'farmer_nic'      => $r2['nic_no'],
                    'farmer_address'  => $r2['location_address'] ?: '',
                    'farm_reg_no'     => $r2['farm_registration_no'] ?: '',
                    'province'        => 'Eastern Province',
                    'district'        => $r2['district_name'] ?: '',
                    'ds_division'     => '',
                    'vs_division'     => $r2['vs_range'] ?: '',
                    'gn_division'     => '',
                    'range_id'        => (int)($r2['range_id'] ?? 0),
                    'district_id'     => (int)($r2['district_id'] ?? 0),
                    'source'          => 'farmers'
                ];
            }
            $stmt2->close();
        }
    }

    // 3. Search in existing cattle_vouchers or animal_identifications fallback
    if (!$found) {
        $search_term = !empty($nic) ? $nic : $farm_reg_no;
        $stmt3 = $mysqli->prepare("
            SELECT farmer_name, farmer_nic, farm_reg_no, province, district, ds_division, vs_division, gn_division, farmer_address, range_id
            FROM cattle_vouchers
            WHERE (farmer_nic = ? AND farmer_nic != '' AND farmer_nic != 'N/A')
               OR (farm_reg_no = ? AND farm_reg_no != '')
            ORDER BY id DESC LIMIT 1
        ");
        if ($stmt3) {
            $stmt3->bind_param("ss", $search_term, $search_term);
            $stmt3->execute();
            $res3 = $stmt3->get_result();
            if ($r3 = $res3->fetch_assoc()) {
                $found = true;
                $owner = [
                    'owner_title'     => 'Mr.',
                    'farmer_name'     => $r3['farmer_name'],
                    'farmer_nic'      => $r3['farmer_nic'],
                    'farmer_address'  => $r3['farmer_address'] ?: '',
                    'farm_reg_no'     => $r3['farm_reg_no'] ?: '',
                    'province'        => $r3['province'] ?: 'Eastern Province',
                    'district'        => $r3['district'] ?: '',
                    'ds_division'     => $r3['ds_division'] ?: '',
                    'vs_division'     => $r3['vs_division'] ?: '',
                    'gn_division'     => $r3['gn_division'] ?: '',
                    'range_id'        => (int)($r3['range_id'] ?? 0),
                    'district_id'     => 1,
                    'source'          => 'cattle_vouchers'
                ];
            }
            $stmt3->close();
        }
    }

    if ($found && $owner) {
        echo json_encode([
            'success' => true,
            'owner'   => $owner
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No matching owner or farm holding found for provided identifier.'
        ]);
    }
    exit();
}

// -----------------------------------------------------------------------------
// ACTION: LOOKUP ANIMAL BY IDENTIFICATION NUMBER (EAR TAG NO)
// -----------------------------------------------------------------------------
if ($action === 'lookup_animal') {
    $ear_tag_no = cleanText($_REQUEST['ear_tag_no'] ?? '');

    if (empty($ear_tag_no)) {
        echo json_encode(['success' => false, 'message' => 'Animal Identification / Ear Tag Number is required.']);
        exit();
    }

    $found = false;
    $animal = null;

    // Search animal_identifications
    $stmt = $mysqli->prepare("
        SELECT id, ear_tag_no, voucher_serial_no, identification_date, gender, colour,
               species, breed, age, purpose, io_number, remarks, present_status,
               farmer_name, farm_reg_no, vs_range, district, range_id, district_id
        FROM animal_identifications
        WHERE ear_tag_no = ? 
           OR LOWER(TRIM(ear_tag_no)) = LOWER(?)
           OR REPLACE(ear_tag_no, ' ', '') = ?
        ORDER BY id DESC LIMIT 1
    ");
    if ($stmt) {
        $clean_tag = preg_replace('/[^0-9a-zA-Z]/', '', $ear_tag_no);
        $stmt->bind_param("sss", $ear_tag_no, $ear_tag_no, $clean_tag);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($r = $res->fetch_assoc()) {
            $found = true;
            
            // Calculate approximate DOB if age is given (e.g. "5 Yrs", "4", "24 Months")
            $dob = '';
            $age_str = trim($r['age'] ?? '');
            if (preg_match('/(\d+)\s*(yr|year)/i', $age_str, $m)) {
                $years_ago = (int)$m[1];
                $dob = date('Y-m-d', strtotime("-{$years_ago} years"));
            } elseif (preg_match('/^(\d+)$/', $age_str, $m)) {
                $years_ago = (int)$m[1];
                $dob = date('Y-m-d', strtotime("-{$years_ago} years"));
            } elseif (preg_match('/(\d+)\s*(mo|month)/i', $age_str, $m)) {
                $months_ago = (int)$m[1];
                $dob = date('Y-m-d', strtotime("-{$months_ago} months"));
            }

            $animal = [
                'ear_tag_no'          => $r['ear_tag_no'],
                'voucher_serial_no'   => $r['voucher_serial_no'] ?: '',
                'identification_date' => $r['identification_date'] ?: date('Y-m-d'),
                'species'             => $r['species'] ?: 'Cattle',
                'breed'               => $r['breed'] ?: 'Local / Cross',
                'gender'              => $r['gender'] ?: 'Female',
                'colour'              => $r['colour'] ?: '',
                'age'                 => $r['age'] ?: '',
                'date_of_birth'       => $dob,
                'specific_marks'      => $r['remarks'] ?: '',
                'present_status'      => $r['present_status'] ?: 'Active',
                'farmer_name'         => $r['farmer_name'] ?: '',
                'farm_reg_no'         => $r['farm_reg_no'] ?: '',
                'vs_range'            => $r['vs_range'] ?: '',
                'district'            => $r['district'] ?: '',
                'range_id'            => (int)($r['range_id'] ?? 0)
            ];
        }
        $stmt->close();
    }

    // Fallback: check cattle_vouchers if existing
    if (!$found) {
        $stmt2 = $mysqli->prepare("
            SELECT * FROM cattle_vouchers
            WHERE ear_tag_number = ? OR LOWER(TRIM(ear_tag_number)) = LOWER(?)
            ORDER BY id DESC LIMIT 1
        ");
        if ($stmt2) {
            $stmt2->bind_param("ss", $ear_tag_no, $ear_tag_no);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            if ($r2 = $res2->fetch_assoc()) {
                $found = true;
                $animal = [
                    'ear_tag_no'          => $r2['ear_tag_number'],
                    'voucher_serial_no'   => $r2['voucher_number'] ?: '',
                    'identification_date' => $r2['identification_date'] ?: $r2['issue_date'],
                    'species'             => $r2['species'] ?: 'Cattle',
                    'breed'               => $r2['breed'] ?: '',
                    'gender'              => $r2['sex'] ?: 'Female',
                    'colour'              => $r2['colour'] ?: '',
                    'age'                 => $r2['age'] ?: '',
                    'date_of_birth'       => $r2['date_of_birth'] ?: '',
                    'specific_marks'      => $r2['specific_marks'] ?: '',
                    'present_status'      => $r2['status'] ?: 'Active',
                    'farmer_name'         => $r2['farmer_name'] ?: '',
                    'farm_reg_no'         => $r2['farm_reg_no'] ?: '',
                    'vs_range'            => $r2['vs_division'] ?: '',
                    'district'            => $r2['district'] ?: '',
                    'range_id'            => (int)($r2['range_id'] ?? 0)
                ];
            }
            $stmt2->close();
        }
    }

    if ($found && $animal) {
        echo json_encode([
            'success' => true,
            'animal'  => $animal
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Animal Identification Number not found in registry. You may enter animal details manually.'
        ]);
    }
    exit();
}

// -----------------------------------------------------------------------------
// ACTION: SAVE / ISSUE CATTLE VOUCHER & BIRTH CERTIFICATE (CREATE OR UPDATE)
// -----------------------------------------------------------------------------
if ($action === 'save_voucher') {
    $id = (int)($_POST['voucher_id'] ?? 0);
    $voucher_number = cleanText($_POST['voucher_number'] ?? '');
    
    // Header & Section 01: Owner Details
    $owner_title = cleanText($_POST['owner_title'] ?? 'Mr.');
    $farmer_name = cleanText($_POST['farmer_name'] ?? '');
    $farmer_address = cleanText($_POST['farmer_address'] ?? '');
    $farmer_nic = cleanText($_POST['farmer_nic'] ?? '');
    
    // Section 02: Holding Details
    $province = cleanText($_POST['province'] ?? 'Eastern Province');
    $district = cleanText($_POST['district'] ?? '');
    $ds_division = cleanText($_POST['ds_division'] ?? '');
    $vs_division = cleanText($_POST['vs_division'] ?? '');
    $farm_reg_no = cleanText($_POST['farm_reg_no'] ?? '');
    $gn_division = cleanText($_POST['gn_division'] ?? '');
    
    // Section 03: Animal Description (Birth Certificate)
    $ear_tag_number = cleanText($_POST['ear_tag_number'] ?? '');
    $identification_date = cleanText($_POST['identification_date'] ?? date('Y-m-d'));
    $species = cleanText($_POST['species'] ?? 'Cattle');
    $breed = cleanText($_POST['breed'] ?? '');
    $sex = cleanText($_POST['sex'] ?? 'Female');
    $date_of_birth = cleanText($_POST['date_of_birth'] ?? '');
    if (empty($date_of_birth)) $date_of_birth = null;
    $age = cleanText($_POST['age'] ?? '');
    $colour = cleanText($_POST['colour'] ?? '');
    $specific_marks = cleanText($_POST['specific_marks'] ?? '');
    
    // Section 04: Authorizations & Signatures
    $issue_date = cleanText($_POST['issue_date'] ?? date('Y-m-d'));
    $vs_name = cleanText($_POST['vs_name'] ?? '');
    $status = cleanText($_POST['status'] ?? 'Active');
    $remarks = cleanText($_POST['remarks'] ?? '');
    $range_id = (int)($_POST['range_id'] ?? $session_range_id ?: 1);

    // Validation
    if (empty($voucher_number)) {
        echo json_encode(['success' => false, 'message' => 'Voucher Serial Number is required.']);
        exit();
    }
    if (empty($farmer_name)) {
        echo json_encode(['success' => false, 'message' => 'Owner Name is required.']);
        exit();
    }
    if (empty($ear_tag_number)) {
        echo json_encode(['success' => false, 'message' => 'Animal Identification / Ear Tag Number is required.']);
        exit();
    }

    // Check duplicate voucher number
    $chk_stmt = $mysqli->prepare("SELECT id FROM cattle_vouchers WHERE voucher_number = ? AND id != ? LIMIT 1");
    if ($chk_stmt) {
        $chk_stmt->bind_param("si", $voucher_number, $id);
        $chk_stmt->execute();
        $chk_res = $chk_stmt->get_result();
        if ($chk_res->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => "Voucher Serial No '{$voucher_number}' is already assigned to another record."]);
            $chk_stmt->close();
            exit();
        }
        $chk_stmt->close();
    }

    // Auto-calculate age if DOB is provided and age is blank
    if (!empty($date_of_birth) && empty($age)) {
        $dob_dt = new DateTime($date_of_birth);
        $now_dt = new DateTime($issue_date);
        $diff = $now_dt->diff($dob_dt);
        if ($diff->y > 0) {
            $age = $diff->y . ' Yrs ' . ($diff->m > 0 ? $diff->m . ' Mo' : '');
        } else {
            $age = max(1, $diff->m) . ' Months';
        }
    }

    if ($id > 0) {
        // UPDATE
        $sql = "UPDATE cattle_vouchers SET 
            voucher_number = ?, owner_title = ?, farmer_name = ?, farmer_address = ?, farmer_nic = ?,
            farm_reg_no = ?, province = ?, district = ?, ds_division = ?, vs_division = ?, gn_division = ?,
            range_id = ?, ear_tag_number = ?, identification_date = ?, species = ?, breed = ?, colour = ?,
            sex = ?, date_of_birth = ?, age = ?, specific_marks = ?, issue_date = ?, status = ?,
            vs_name = ?, remarks = ?, updated_at = NOW()
            WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'DB error: ' . $mysqli->error]);
            exit();
        }
        $stmt->bind_param("sssssssssssisssssssssssssi",
            $voucher_number, $owner_title, $farmer_name, $farmer_address, $farmer_nic,
            $farm_reg_no, $province, $district, $ds_division, $vs_division, $gn_division,
            $range_id, $ear_tag_number, $identification_date, $species, $breed, $colour,
            $sex, $date_of_birth, $age, $specific_marks, $issue_date, $status,
            $vs_name, $remarks, $id
        );
        $stmt->execute();
        $stmt->close();
        $saved_id = $id;
    } else {
        // INSERT
        $sql = "INSERT INTO cattle_vouchers 
            (tag_program_id, farmer_id, owner_title, farmer_name, farmer_address, farmer_nic,
             farm_reg_no, province, district, ds_division, vs_division, gn_division,
             range_id, ear_tag_number, identification_date, species, voucher_number, breed, colour,
             sex, date_of_birth, age, specific_marks, issue_date, status, vs_name, remarks, created_by)
            VALUES (0, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'DB error: ' . $mysqli->error]);
            exit();
        }
        $stmt->bind_param("ssssssssssissssssssssssssi",
            $owner_title, $farmer_name, $farmer_address, $farmer_nic,
            $farm_reg_no, $province, $district, $ds_division, $vs_division, $gn_division,
            $range_id, $ear_tag_number, $identification_date, $species, $voucher_number, $breed, $colour,
            $sex, $date_of_birth, $age, $specific_marks, $issue_date, $status, $vs_name, $remarks, $user_id
        );
        $stmt->execute();
        $saved_id = $stmt->insert_id;
        $stmt->close();
    }

    // Synchronize to animal_identifications if matching ear tag exists
    $ai_status = 'Active';
    if ($status === 'Transferred') $ai_status = 'Sold or transferred';
    elseif ($status === 'Dead') $ai_status = 'Died';
    elseif ($status === 'Slaughtered') $ai_status = 'Slaughter';
    elseif ($status === 'Damaged') $ai_status = 'Tag Damaged / Removed';

    $upd_ai = $mysqli->prepare("
        UPDATE animal_identifications SET 
            voucher_serial_no = ?, farmer_name = ?, farm_reg_no = ?, breed = ?,
            gender = ?, colour = ?, age = ?, species = ?, remarks = ?,
            present_status = ?, updated_at = NOW()
        WHERE ear_tag_no = ?
    ");
    if ($upd_ai) {
        $upd_ai->bind_param("sssssssssss",
            $voucher_number, $farmer_name, $farm_reg_no, $breed,
            $sex, $colour, $age, $species, $specific_marks,
            $ai_status, $ear_tag_number
        );
        $upd_ai->execute();
        $upd_ai->close();
    }

    echo json_encode([
        'success'        => true,
        'message'        => 'Schedule-06 Cattle Voucher successfully issued and synchronized!',
        'voucher_id'     => $saved_id,
        'voucher_number' => $voucher_number
    ]);
    exit();
}

// -----------------------------------------------------------------------------
// ACTION: GET FULL VOUCHER DETAILS FOR PRINTING / EDITING
// -----------------------------------------------------------------------------
if ($action === 'get_voucher') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $mysqli->prepare("SELECT * FROM cattle_vouchers WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            echo json_encode(['success' => true, 'voucher' => $row]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Voucher record not found.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Query error.']);
    }
    exit();
}

// -----------------------------------------------------------------------------
// ACTION: UPDATE STATUS (ACTIVE, TRANSFERRED, DEAD, SLAUGHTERED, DAMAGED)
// -----------------------------------------------------------------------------
if ($action === 'update_status') {
    $id = (int)($_POST['id'] ?? 0);
    $new_status = cleanText($_POST['status'] ?? 'Active');

    $valid_statuses = ['Active', 'Transferred', 'Dead', 'Slaughtered', 'Damaged'];
    if (!in_array($new_status, $valid_statuses, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status selected.']);
        exit();
    }

    $stmt = $mysqli->prepare("UPDATE cattle_vouchers SET status = ?, updated_at = NOW() WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $new_status, $id);
        $stmt->execute();
        $stmt->close();

        // Also sync to animal_identifications
        $ai_map = [
            'Active'      => 'Active',
            'Transferred' => 'Sold or transferred',
            'Dead'        => 'Died',
            'Slaughtered' => 'Slaughter',
            'Damaged'     => 'Tag Damaged / Removed'
        ];
        $ai_stat = $ai_map[$new_status] ?? 'Active';
        $v_res = $mysqli->query("SELECT ear_tag_number, voucher_number FROM cattle_vouchers WHERE id = {$id} LIMIT 1");
        if ($v_res && $vr = $v_res->fetch_assoc()) {
            $upd_ai = $mysqli->prepare("UPDATE animal_identifications SET present_status = ?, updated_at = NOW() WHERE ear_tag_no = ? OR voucher_serial_no = ?");
            if ($upd_ai) {
                $upd_ai->bind_param("sss", $ai_stat, $vr['ear_tag_number'], $vr['voucher_number']);
                $upd_ai->execute();
                $upd_ai->close();
            }
        }

        echo json_encode(['success' => true, 'message' => "Voucher status updated to '{$new_status}'."]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error updating status.']);
    }
    exit();
}

// -----------------------------------------------------------------------------
// ACTION: DELETE VOUCHER
// -----------------------------------------------------------------------------
if ($action === 'delete_voucher') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid voucher ID.']);
        exit();
    }

    $stmt = $mysqli->prepare("DELETE FROM cattle_vouchers WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Voucher record deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting voucher record.']);
    }
    exit();
}


// -------------------------------------------------------------------------
// ACTION: GET MONTH AUTO METRICS (AJAX)
// -------------------------------------------------------------------------
if ($action === 'get_month_auto_metrics') {
    header('Content-Type: application/json; charset=UTF-8');
    $report_year  = (int)($_GET['year'] ?? date('Y'));
    $report_month = (int)($_GET['month'] ?? date('n'));

    // Check if record already exists to get current received_qty
    $existing_received = 0;
    $existing_id = 0;
    $ex_q = $mysqli->prepare("SELECT id, received_qty, opening_balance FROM cattle_voucher_usage WHERE range_id = ? AND report_year = ? AND report_month = ? LIMIT 1");
    if ($ex_q) {
        $ex_q->bind_param("iii", $range_id, $report_year, $report_month);
        $ex_q->execute();
        $ex_res = $ex_q->get_result();
        if ($ex_row = $ex_res->fetch_assoc()) {
            $existing_id = (int)$ex_row['id'];
            $existing_received = (int)$ex_row['received_qty'];
        }
        $ex_q->close();
    }

    $auto = computeMonthlyMetrics($mysqli, $range_id, $report_year, $report_month);
    $ending = ($auto['opening_balance'] + $existing_received) - ($auto['used_qty'] + $auto['damaged_qty']);

    echo json_encode([
        'success' => true,
        'id' => $existing_id,
        'opening_balance' => $auto['opening_balance'],
        'received_qty' => $existing_received,
        'used_qty' => $auto['used_qty'],
        'damaged_qty' => $auto['damaged_qty'],
        'spoilt_qty' => 0,
        'transferred_qty' => 0,
        'ending_balance' => $ending,
        'distributed_blocks' => $auto['distributed_blocks']
    ]);
    exit();
}

$redirect_to = trim($_POST['redirect_to'] ?? $_GET['redirect_to'] ?? '../cattle_voucher.php');
if (empty($redirect_to)) {
    $redirect_to = '../cattle_voucher.php';
}

// -------------------------------------------------------------------------
// POST: SAVE / UPDATE CATTLE VOUCHER MONTHLY RETURN
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id              = (int)($_POST['id'] ?? 0);
    $report_year     = (int)($_POST['report_year'] ?? date('Y'));
    $report_month    = (int)($_POST['report_month'] ?? date('n'));
    
    // Strict requirement: User data entry is RESTRICTED to received_qty!
    $received_qty    = (int)($_POST['received_qty'] ?? 0);
    if ($received_qty < 0) {
        $received_qty = 0;
    }

    // Auto-compute all other metrics from live database records
    $auto = computeMonthlyMetrics($mysqli, $range_id, $report_year, $report_month);
    $opening_balance = $auto['opening_balance'];
    $used_qty        = $auto['used_qty'];
    $damaged_qty     = $auto['damaged_qty'];
    $spoilt_qty      = 0;
    $transferred_qty = 0;

    // Projected Ending Balance
    $closing_balance = ($opening_balance + $received_qty) - ($used_qty + $spoilt_qty + $damaged_qty + $transferred_qty);

    $is_ajax = isset($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    $separator = (strpos($redirect_to, '?') !== false) ? '&' : '?';

    if (empty($report_year) || empty($report_month)) {
        if ($is_ajax) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => false, 'message' => 'Missing report year or month.']);
            exit();
        }
        header("Location: {$redirect_to}{$separator}status=error&msg=Missing+required+fields");
        exit();
    }

    // Check if a record already exists for this range/year/month
    $check_stmt = $mysqli->prepare("SELECT id FROM `cattle_voucher_usage` WHERE range_id = ? AND report_year = ? AND report_month = ? LIMIT 1");
    if ($check_stmt) {
        $check_stmt->bind_param("iii", $range_id, $report_year, $report_month);
        $check_stmt->execute();
        $c_res = $check_stmt->get_result();
        if ($c_row = $c_res->fetch_assoc()) {
            $id = (int)$c_row['id'];
            $action = 'update';
        }
        $check_stmt->close();
    }

    if ($action === 'create' || $id === 0) {
        $stmt = $mysqli->prepare("INSERT INTO `cattle_voucher_usage` 
            (district_id, range_id, report_year, report_month, opening_balance, received_qty, used_qty, spoilt_qty, damaged_qty, transferred_qty, closing_balance, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            $err = $mysqli->error;
            if ($is_ajax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['success' => false, 'message' => $err]);
                exit();
            }
            header("Location: {$redirect_to}{$separator}status=error&msg=" . urlencode($err));
            exit();
        }
        $stmt->bind_param("iiiiiiiiiiii", 
            $district_id, $range_id, $report_year, $report_month, 
            $opening_balance, $received_qty, $used_qty, $spoilt_qty, $damaged_qty, $transferred_qty, 
            $closing_balance, $user_id
        );
        
        if ($stmt->execute()) {
            $stmt->close();
            if ($is_ajax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['success' => true, 'message' => 'Cattle Voucher Monthly Return successfully logged.']);
                exit();
            }
            header("Location: {$redirect_to}{$separator}status=success&msg=Voucher+Stock+Record+Created+Successfully");
            exit();
        } else {
            $err = $stmt->error;
            $stmt->close();
            if ($is_ajax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['success' => false, 'message' => $err]);
                exit();
            }
            header("Location: {$redirect_to}{$separator}status=error&msg=" . urlencode($err));
            exit();
        }

    } else {
        // UPDATE
        $stmt = $mysqli->prepare("UPDATE `cattle_voucher_usage` SET 
            opening_balance = ?, 
            received_qty = ?, 
            used_qty = ?, 
            spoilt_qty = ?, 
            damaged_qty = ?, 
            transferred_qty = ?, 
            closing_balance = ?,
            updated_at = NOW() 
            WHERE id = ? AND range_id = ?");
        if (!$stmt) {
            $err = $mysqli->error;
            if ($is_ajax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['success' => false, 'message' => $err]);
                exit();
            }
            header("Location: {$redirect_to}{$separator}status=error&msg=" . urlencode($err));
            exit();
        }
        $stmt->bind_param("iiiiiiiii", 
            $opening_balance, $received_qty, $used_qty, $spoilt_qty, $damaged_qty, $transferred_qty, 
            $closing_balance, $id, $range_id
        );
        
        if ($stmt->execute()) {
            $stmt->close();
            if ($is_ajax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['success' => true, 'message' => 'Cattle Voucher Monthly Return updated successfully.']);
                exit();
            }
            header("Location: {$redirect_to}{$separator}status=success&msg=Voucher+Stock+Record+Updated+Successfully");
            exit();
        } else {
            $err = $stmt->error;
            $stmt->close();
            if ($is_ajax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['success' => false, 'message' => $err]);
                exit();
            }
            header("Location: {$redirect_to}{$separator}status=error&msg=" . urlencode($err));
            exit();
        }
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    $separator = (strpos($redirect_to, '?') !== false) ? '&' : '?';

    if ($id <= 0) {
        header("Location: {$redirect_to}{$separator}status=error&msg=Invalid+Record+ID");
        exit();
    }

    $stmt = $mysqli->prepare("DELETE FROM `cattle_voucher_usage` WHERE id = ? AND range_id = ?");
    if (!$stmt) {
        header("Location: {$redirect_to}{$separator}status=error&msg=" . urlencode($mysqli->error));
        exit();
    }
    $stmt->bind_param("ii", $id, $range_id);
    
    if ($stmt->execute()) {
        header("Location: {$redirect_to}{$separator}status=success&msg=Voucher+Stock+Record+Deleted");
        exit();
    } else {
        header("Location: {$redirect_to}{$separator}status=error&msg=" . urlencode($stmt->error));
        exit();
    }
}

header("Location: " . $redirect_to);
exit();
