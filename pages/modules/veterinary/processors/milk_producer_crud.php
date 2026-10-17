<?php
/**
 * pages/modules/veterinary/processors/milk_producer_crud.php
 * Backend AJAX endpoint for Milk Producers Particulars in Range Details
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../../config/db_connect.php';

header('Content-Type: application/json');

/** @var mysqli $mysqli */
global $mysqli;

if (!isset($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$action  = trim($_REQUEST['action'] ?? '');

function jsonResponse(array $data): void {
    echo json_encode($data);
    exit();
}

function resolveVolumeRange(?float $vol, ?string $raw_range): string {
    if (!empty($raw_range) && in_array($raw_range, [
        'Below 5 liters', '5–10 liters', '10–20 liters', '20–40 liters', '40–100 liters', 'Above 100 liters'
    ], true)) {
        return $raw_range;
    }
    if ($vol === null || $vol <= 0) {
        return 'Below 5 liters';
    }
    if ($vol < 5.0)  return 'Below 5 liters';
    if ($vol <= 10.0) return '5–10 liters';
    if ($vol <= 20.0) return '10–20 liters';
    if ($vol <= 40.0) return '20–40 liters';
    if ($vol <= 100.0) return '40–100 liters';
    return 'Above 100 liters';
}

// =========================================================================
// ACTION: LOOKUP PRODUCER (Auto-Population by Farm Registration Number or NIC)
// =========================================================================
if ($action === 'lookup_producer') {
    $query_str = trim($_REQUEST['query'] ?? ($_REQUEST['farm_reg_no'] ?? ''));
    if (empty($query_str)) {
        jsonResponse(['success' => false, 'found' => false, 'message' => 'Registration number is required.']);
    }

    $found = false;
    $producer = [
        'farm_reg_no'            => $query_str,
        'farmer_name'            => '',
        'nic'                    => '',
        'phone'                  => '',
        'address'                => '',
        'ds_gn_division'         => '',
        'sex'                    => 'Male',
        'ethnicity'              => 'Sinhala',
        'milk_production_volume' => '5–10 liters',
        'daily_milk_liters'      => 0.0,
        'source'                 => ''
    ];

    // 1. Check dairy_farm_applications (Latest comprehensive survey)
    $stmt1 = $mysqli->prepare("
        SELECT farm_reg_no, producer_name, nic_no, contact_mobile, telephone_no, address, 
               ds_gn_division, gender, ethnicity, milk_production_volume, milk_total_liters
        FROM dairy_farm_applications 
        WHERE farm_reg_no = ? OR nic_no = ? 
        ORDER BY id DESC LIMIT 1
    ");
    if ($stmt1) {
        $stmt1->bind_param("ss", $query_str, $query_str);
        $stmt1->execute();
        $row1 = $stmt1->get_result()->fetch_assoc();
        $stmt1->close();
        if ($row1) {
            $found = true;
            $producer['farm_reg_no']            = $row1['farm_reg_no'] ?: $query_str;
            $producer['farmer_name']            = $row1['producer_name'] ?? '';
            $producer['nic']                    = $row1['nic_no'] ?? '';
            $producer['phone']                  = $row1['contact_mobile'] ?: ($row1['telephone_no'] ?? '');
            $producer['address']                = $row1['address'] ?? '';
            $producer['ds_gn_division']         = $row1['ds_gn_division'] ?? '';
            $producer['sex']                    = in_array($row1['gender'], ['Male', 'Female'], true) ? $row1['gender'] : 'Male';
            $producer['ethnicity']              = !empty($row1['ethnicity']) ? $row1['ethnicity'] : 'Sinhala';
            $producer['daily_milk_liters']      = floatval($row1['milk_total_liters'] ?? 0);
            $producer['milk_production_volume'] = resolveVolumeRange($producer['daily_milk_liters'], $row1['milk_production_volume'] ?? '');
            $producer['source']                 = 'Dairy Farm Application Register';
        }
    }

    // 2. If not found, check farm_registration_renewals (Master Registry)
    if (!$found) {
        $stmt2 = $mysqli->prepare("
            SELECT registration_no, farmer_name, nic, telephone_no, farmer_address, farm_address, 
                   ds_division, gn_division, gender, ethnicity, milk_production_volume, daily_milk_production 
            FROM farm_registration_renewals 
            WHERE registration_no = ? OR nic = ? 
            ORDER BY id DESC LIMIT 1
        ");
        if ($stmt2) {
            $stmt2->bind_param("ss", $query_str, $query_str);
            $stmt2->execute();
            $row2 = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
            if ($row2) {
                $found = true;
                $producer['farm_reg_no']            = $row2['registration_no'] ?: $query_str;
                $producer['farmer_name']            = $row2['farmer_name'] ?? '';
                $producer['nic']                    = $row2['nic'] ?? '';
                $producer['phone']                  = $row2['telephone_no'] ?? '';
                $producer['address']                = $row2['farmer_address'] ?: ($row2['farm_address'] ?? '');
                $ds_gn = trim(($row2['ds_division'] ?? '') . ' / ' . ($row2['gn_division'] ?? ''), ' /');
                $producer['ds_gn_division']         = $ds_gn;
                $producer['sex']                    = in_array($row2['gender'], ['Male', 'Female'], true) ? $row2['gender'] : 'Male';
                $producer['ethnicity']              = !empty($row2['ethnicity']) ? $row2['ethnicity'] : 'Sinhala';
                $producer['daily_milk_liters']      = floatval($row2['daily_milk_production'] ?? 0);
                $producer['milk_production_volume'] = resolveVolumeRange($producer['daily_milk_liters'], $row2['milk_production_volume'] ?? '');
                $producer['source']                 = 'Master Farm Registry';
            }
        }
    }

    // 3. Fallback check farmers table
    if (!$found) {
        $stmt3 = $mysqli->prepare("
            SELECT farm_registration_no, full_name, nic_no, contact_no, location_address, gender, ethnicity, milk_production_volume 
            FROM farmers 
            WHERE farm_registration_no = ? OR nic_no = ? 
            ORDER BY id DESC LIMIT 1
        ");
        if ($stmt3) {
            $stmt3->bind_param("ss", $query_str, $query_str);
            $stmt3->execute();
            $row3 = $stmt3->get_result()->fetch_assoc();
            $stmt3->close();
            if ($row3) {
                $found = true;
                $producer['farm_reg_no']            = $row3['farm_registration_no'] ?: $query_str;
                $producer['farmer_name']            = $row3['full_name'] ?? '';
                $producer['nic']                    = $row3['nic_no'] ?? '';
                $producer['phone']                  = $row3['contact_no'] ?? '';
                $producer['address']                = $row3['location_address'] ?? '';
                $producer['sex']                    = in_array($row3['gender'], ['Male', 'Female'], true) ? $row3['gender'] : 'Male';
                $producer['ethnicity']              = !empty($row3['ethnicity']) ? $row3['ethnicity'] : 'Sinhala';
                $producer['milk_production_volume'] = resolveVolumeRange(null, $row3['milk_production_volume'] ?? '');
                $producer['source']                 = 'Farmers Master File';
            }
        }
    }

    if ($found) {
        jsonResponse([
            'success'  => true,
            'found'    => true,
            'producer' => $producer,
            'message'  => "Historical data successfully auto-populated from {$producer['source']}."
        ]);
    } else {
        jsonResponse([
            'success'  => true,
            'found'    => false,
            'producer' => $producer,
            'message'  => 'No prior historical record found for this Farm Registration Number. Ready for new entry.'
        ]);
    }
}

// =========================================================================
// ACTION: SAVE PRODUCER (Update / Store in Master Profiles & Applications)
// =========================================================================
if ($action === 'save_producer') {
    $range_id               = intval($_POST['range_id'] ?? 1);
    $farm_reg_no            = trim($_POST['farm_reg_no'] ?? '');
    $farmer_name            = trim($_POST['farmer_name'] ?? '');
    $nic                    = trim($_POST['nic'] ?? '');
    $phone                  = trim($_POST['phone'] ?? '');
    $address                = trim($_POST['address'] ?? '');
    $ds_gn_division         = trim($_POST['ds_gn_division'] ?? '');
    $sex                    = trim($_POST['sex'] ?? '');
    $ethnicity              = trim($_POST['ethnicity'] ?? '');
    $milk_production_volume = trim($_POST['milk_production_volume'] ?? '');

    // Strict validation
    if (empty($farm_reg_no)) {
        jsonResponse(['success' => false, 'message' => 'Farm Registration Number is mandatory.']);
    }
    if (empty($farmer_name)) {
        jsonResponse(['success' => false, 'message' => "Milk Producer's Name is mandatory."]);
    }
    if (!in_array($sex, ['Male', 'Female'], true)) {
        jsonResponse(['success' => false, 'message' => "Please select a valid Sex option ('Male' or 'Female')."]);
    }
    $allowed_ethnicities = ['Sinhala', 'Tamil', 'Muslim', 'Other'];
    if (!in_array($ethnicity, $allowed_ethnicities, true)) {
        jsonResponse(['success' => false, 'message' => 'Please select a valid Ethnicity from the dropdown.']);
    }
    $allowed_volumes = [
        'Below 5 liters',
        '5–10 liters',
        '10–20 liters',
        '20–40 liters',
        '40–100 liters',
        'Above 100 liters'
    ];
    if (!in_array($milk_production_volume, $allowed_volumes, true)) {
        jsonResponse(['success' => false, 'message' => 'Please select a valid standardized Milk Production Range.']);
    }

    // Numeric approximation for daily_milk_production
    $numeric_approx = match($milk_production_volume) {
        'Below 5 liters'   => 3.0,
        '5–10 liters'      => 7.5,
        '10–20 liters'     => 15.0,
        '20–40 liters'     => 30.0,
        '40–100 liters'    => 70.0,
        'Above 100 liters' => 120.0,
        default            => 5.0
    };

    // 1. Update or Insert into farm_registration_renewals
    $chk_ren = $mysqli->prepare("SELECT id FROM farm_registration_renewals WHERE registration_no = ? ORDER BY id DESC LIMIT 1");
    $chk_ren->bind_param("s", $farm_reg_no);
    $chk_ren->execute();
    $ren_row = $chk_ren->get_result()->fetch_assoc();
    $chk_ren->close();

    if ($ren_row) {
        $ren_id = (int)$ren_row['id'];
        $upd_ren = $mysqli->prepare("
            UPDATE farm_registration_renewals 
            SET farmer_name = ?, nic = ?, telephone_no = ?, farmer_address = ?, 
                gender = ?, ethnicity = ?, milk_production_volume = ?, daily_milk_production = ?
            WHERE id = ?
        ");
        $upd_ren->bind_param("ssssssddi", $farmer_name, $nic, $phone, $address, $sex, $ethnicity, $milk_production_volume, $numeric_approx, $ren_id);
        $upd_ren->execute();
        $upd_ren->close();
    } else {
        $ins_ren = $mysqli->prepare("
            INSERT INTO farm_registration_renewals 
            (range_id, registration_no, farmer_name, nic, telephone_no, farmer_address, gender, ethnicity, milk_production_volume, daily_milk_production, date_of_registration_renewal, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)
        ");
        $ins_ren->bind_param("issssssssdi", $range_id, $farm_reg_no, $farmer_name, $nic, $phone, $address, $sex, $ethnicity, $milk_production_volume, $numeric_approx, $user_id);
        $ins_ren->execute();
        $ins_ren->close();
    }

    // 2. Synchronize with farmers table
    $chk_f = $mysqli->prepare("SELECT id FROM farmers WHERE farm_registration_no = ? ORDER BY id DESC LIMIT 1");
    $chk_f->bind_param("s", $farm_reg_no);
    $chk_f->execute();
    $f_row = $chk_f->get_result()->fetch_assoc();
    $chk_f->close();

    if ($f_row) {
        $fid = (int)$f_row['id'];
        $upd_f = $mysqli->prepare("
            UPDATE farmers 
            SET full_name = ?, nic_no = ?, contact_no = ?, location_address = ?, gender = ?, ethnicity = ?, milk_production_volume = ?
            WHERE id = ?
        ");
        $upd_f->bind_param("sssssssi", $farmer_name, $nic, $phone, $address, $sex, $ethnicity, $milk_production_volume, $fid);
        $upd_f->execute();
        $upd_f->close();
    } else {
        $ins_f = $mysqli->prepare("
            INSERT INTO farmers 
            (range_id, farm_registration_no, full_name, nic_no, contact_no, location_address, gender, ethnicity, milk_production_volume, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $ins_f->bind_param("issssssss", $range_id, $farm_reg_no, $farmer_name, $nic, $phone, $address, $sex, $ethnicity, $milk_production_volume);
        $ins_f->execute();
        $ins_f->close();
    }

    // 3. Synchronize with dairy_farm_applications if present
    $upd_app = $mysqli->prepare("
        UPDATE dairy_farm_applications 
        SET producer_name = ?, nic_no = ?, contact_mobile = ?, address = ?, ds_gn_division = ?, gender = ?, ethnicity = ?, milk_production_volume = ?
        WHERE farm_reg_no = ?
    ");
    if ($upd_app) {
        $upd_app->bind_param("sssssssss", $farmer_name, $nic, $phone, $address, $ds_gn_division, $sex, $ethnicity, $milk_production_volume, $farm_reg_no);
        $upd_app->execute();
        $upd_app->close();
    }

    jsonResponse([
        'success' => true,
        'message' => "Milk Producer '{$farmer_name}' ({$farm_reg_no}) saved with standardized volume '{$milk_production_volume}' and demographic indicators."
    ]);
}

jsonResponse(['success' => false, 'message' => 'Unknown action.']);
