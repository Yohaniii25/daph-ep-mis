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
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) || isset($_REQUEST['ajax'])) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
        exit();
    }
    header("Location: ../../../index.php");
    exit();
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id > 0) {
    $u_chk = $mysqli->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
    if ($u_chk) {
        $u_chk->bind_param("i", $user_id);
        $u_chk->execute();
        if (!$u_chk->get_result()->fetch_assoc()) {
            $user_id = null;
        }
        $u_chk->close();
    }
} else {
    $user_id = null;
}

$range_id = (int)($_SESSION['range_id'] ?? 0);
if ($range_id <= 0) {
    $range_id = 1; // Default to Ampara range for testing
}

// Resolve District ID for the range
$district_id = null;
if (!empty($range_id)) {
    $r_query = $mysqli->prepare("SELECT district_id FROM veterinary_ranges WHERE id = ?");
    if ($r_query) {
        $r_query->bind_param("i", $range_id);
        $r_query->execute();
        $r_res = $r_query->get_result();
        if ($r_row = $r_res->fetch_assoc()) {
            $district_id = (int)$r_row['district_id'];
        }
        $r_query->close();
    }
}
if (empty($district_id)) {
    $district_id = 1;
}

$action = trim($_REQUEST['action'] ?? '');

// Helper to send JSON
function sendJson($data) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data);
    exit();
}

// -------------------------------------------------------------------------
// ACTION: LOOKUP FARMER & ANIMALS (BY NIC OR FARM REG NO)
// -------------------------------------------------------------------------
if ($action === 'lookup_farmer') {
    $query = trim($_REQUEST['query'] ?? '');
    if (empty($query)) {
        sendJson(['success' => false, 'message' => 'Please enter a Farmer NIC or Farm Registration Number.']);
    }

    $clean_query = preg_replace('/[^0-9a-zA-Z]/', '', $query);
    $farmer = null;
    $found = false;

    // 1. Search farm_registration_renewals (Master Registry)
    $stmt1 = $mysqli->prepare("
        SELECT id, registration_no, farmer_name, nic, farmer_address, telephone_no, district, vs_division, range_id, district_id
        FROM farm_registration_renewals
        WHERE registration_no = ? 
           OR LOWER(TRIM(registration_no)) = LOWER(?)
           OR REPLACE(REPLACE(registration_no, ' ', ''), '-', '') = ?
           OR nic = ?
           OR LOWER(TRIM(nic)) = LOWER(?)
           OR REPLACE(REPLACE(nic, ' ', ''), '-', '') = ?
        ORDER BY (LOWER(TRIM(registration_no)) = LOWER(?) OR LOWER(TRIM(nic)) = LOWER(?)) DESC, id DESC
        LIMIT 1
    ");
    if ($stmt1) {
        $stmt1->bind_param("ssssssss", $query, $query, $clean_query, $query, $query, $clean_query, $query, $query);
        $stmt1->execute();
        $res1 = $stmt1->get_result();
        if ($row1 = $res1->fetch_assoc()) {
            $found = true;
            $farmer = [
                'farm_reg_no' => $row1['registration_no'] ?: '',
                'farmer_name' => $row1['farmer_name'] ?: '',
                'farmer_nic'  => $row1['nic'] ?: '',
                'location_address' => $row1['farmer_address'] ?: '',
                'telephone'   => $row1['telephone_no'] ?: '',
                'vs_range'    => $row1['vs_division'] ?: '',
                'district'    => $row1['district'] ?: '',
                'range_id'    => (int)($row1['range_id'] ?: $range_id),
                'district_id' => (int)($row1['district_id'] ?: $district_id)
            ];
        }
        $stmt1->close();
    }

    // 2. Fallback: Search farmers table
    if (!$found) {
        $stmt2 = $mysqli->prepare("
            SELECT f.id, f.nic_no, f.full_name, f.farm_registration_no, f.location_address, f.contact_no, f.range_id, f.district_id,
                   COALESCE(vr.name, '') AS vs_range, COALESCE(d.name, '') AS district
            FROM farmers f
            LEFT JOIN veterinary_ranges vr ON vr.id = f.range_id
            LEFT JOIN districts d ON d.id = f.district_id
            WHERE f.nic_no = ? 
               OR LOWER(TRIM(f.nic_no)) = LOWER(?)
               OR f.farm_registration_no = ?
               OR LOWER(TRIM(f.farm_registration_no)) = LOWER(?)
            ORDER BY id DESC LIMIT 1
        ");
        if ($stmt2) {
            $stmt2->bind_param("ssss", $query, $query, $query, $query);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            if ($row2 = $res2->fetch_assoc()) {
                $found = true;
                $farmer = [
                    'farm_reg_no' => $row2['farm_registration_no'] ?: '',
                    'farmer_name' => $row2['full_name'] ?: '',
                    'farmer_nic'  => $row2['nic_no'] ?: '',
                    'location_address' => $row2['location_address'] ?: '',
                    'telephone'   => $row2['contact_no'] ?: '',
                    'vs_range'    => $row2['vs_range'] ?: '',
                    'district'    => $row2['district'] ?: '',
                    'range_id'    => (int)($row2['range_id'] ?: $range_id),
                    'district_id' => (int)($row2['district_id'] ?: $district_id)
                ];
            }
            $stmt2->close();
        }
    }

    // 3. Fallback: Search animal_identifications table
    if (!$found) {
        $stmt3 = $mysqli->prepare("
            SELECT farm_reg_no, farmer_name, district, vs_range, range_id, district_id
            FROM animal_identifications
            WHERE farm_reg_no = ? OR LOWER(TRIM(farm_reg_no)) = LOWER(?)
            ORDER BY id DESC LIMIT 1
        ");
        if ($stmt3) {
            $stmt3->bind_param("ss", $query, $query);
            $stmt3->execute();
            $res3 = $stmt3->get_result();
            if ($row3 = $res3->fetch_assoc()) {
                $found = true;
                $farmer = [
                    'farm_reg_no' => $row3['farm_reg_no'],
                    'farmer_name' => $row3['farmer_name'],
                    'farmer_nic'  => '',
                    'location_address' => '',
                    'telephone'   => '',
                    'vs_range'    => $row3['vs_range'],
                    'district'    => $row3['district'],
                    'range_id'    => (int)($row3['range_id'] ?: $range_id),
                    'district_id' => (int)($row3['district_id'] ?: $district_id)
                ];
            }
            $stmt3->close();
        }
    }

    // If farmer found, fetch all their identified animals from animal_identifications
    $animals = [];
    if ($found && $farmer) {
        $target_reg = $farmer['farm_reg_no'];
        $target_name = $farmer['farmer_name'];

        $ai_query = "
            SELECT id, ear_tag_no, animal_seq_no, species, breed, age, colour, purpose, voucher_serial_no, present_status
            FROM animal_identifications
            WHERE (farm_reg_no = ? AND farm_reg_no != '')
               OR (farmer_name = ? AND farmer_name != '')
            ORDER BY id ASC
        ";
        $ai_stmt = $mysqli->prepare($ai_query);
        if ($ai_stmt) {
            $ai_stmt->bind_param("ss", $target_reg, $target_name);
            $ai_stmt->execute();
            $ai_res = $ai_stmt->get_result();
            while ($arow = $ai_res->fetch_assoc()) {
                $animals[] = [
                    'id' => (int)$arow['id'],
                    'ear_tag_no' => $arow['ear_tag_no'],
                    'animal_seq_no' => $arow['animal_seq_no'],
                    'species' => $arow['species'] ?: 'Cattle',
                    'breed' => $arow['breed'] ?: 'Standard',
                    'age' => $arow['age'] ?: '—',
                    'colour' => $arow['colour'] ?: '—',
                    'purpose' => $arow['purpose'] ?: 'Transport',
                    'voucher_serial_no' => $arow['voucher_serial_no'] ?: '',
                    'status' => $arow['present_status'] ?: 'Active'
                ];
            }
            $ai_stmt->close();
        }
    }

    sendJson([
        'success' => $found,
        'farmer' => $farmer,
        'animals' => $animals,
        'message' => $found ? 'Farmer and registered animals located successfully.' : 'No registered farmer found for the specified NIC or Farm Registration No.'
    ]);
}

// -------------------------------------------------------------------------
// ACTION: SAVE HEALTH CERTIFICATE (SCHEDULE - 03) WITH ANIMAL SELECTION & COUNTERFOIL
// -------------------------------------------------------------------------
if ($action === 'save_health_certificate') {
    $edit_id = (int)($_POST['id'] ?? 0);
    $report_year = (int)($_POST['report_year'] ?? date('Y'));
    $report_month = (int)($_POST['report_month'] ?? date('m'));
    $date_of_issue = trim($_POST['date_of_issue'] ?? date('Y-m-d'));
    $health_certificate_no = trim($_POST['health_certificate_no'] ?? '');
    $vehicle_fitness_certificate_no = trim($_POST['vehicle_fitness_certificate_no'] ?? '');
    $farmer_nic = trim($_POST['farmer_nic'] ?? '');
    $applicant_name = trim($_POST['applicant_name'] ?? '');
    $applicant_address = trim($_POST['applicant_address'] ?? '');
    $farm_reg_no = trim($_POST['farm_registration_no'] ?? '');
    $purpose = trim($_POST['purpose'] ?? 'Transportation');
    $primary_species = trim($_POST['species'] ?? 'Cattle');

    // Selected Animals Array (Schedule - 03 Form Grid)
    $selected_animals = isset($_POST['selected_animals']) && is_array($_POST['selected_animals']) ? $_POST['selected_animals'] : [];
    $total_animals = count($selected_animals);

    if (empty($health_certificate_no) || empty($date_of_issue) || empty($applicant_name)) {
        sendJson(['success' => false, 'message' => 'Please provide Health Certificate No, Date of Issue, and Applicant Name.']);
    }

    $applicant_combined = $applicant_name;
    if (!empty($applicant_address)) {
        $applicant_combined .= "\n" . $applicant_address;
    }

    $animals_json = json_encode($selected_animals);

    $mysqli->begin_transaction();
    try {
        if ($edit_id > 0) {
            // Update existing Health Certificate
            $u_sql = "
                UPDATE health_certificate_issues SET 
                    report_year = ?,
                    report_month = ?,
                    health_certificate_no = ?,
                    farmer_nic = ?,
                    applicant_name_address = ?,
                    farm_registration_no = ?,
                    date_of_issue = ?,
                    species = ?,
                    total_animals = ?,
                    selected_animals_json = ?,
                    vehicle_fitness_certificate_no = ?,
                    purpose = ?
                WHERE id = ? AND range_id = ?
            ";
            $u_stmt = $mysqli->prepare($u_sql);
            if (!$u_stmt) {
                throw new Exception("Prepare failed: " . $mysqli->error);
            }
            $u_stmt->bind_param(
                "iissssssisssii",
                $report_year,
                $report_month,
                $health_certificate_no,
                $farmer_nic,
                $applicant_combined,
                $farm_reg_no,
                $date_of_issue,
                $primary_species,
                $total_animals,
                $animals_json,
                $vehicle_fitness_certificate_no,
                $purpose,
                $edit_id,
                $range_id
            );
            $u_stmt->execute();
            $u_stmt->close();

            $hc_id = $edit_id;

            // Delete old child records to replace
            $d_stmt = $mysqli->prepare("DELETE FROM health_certificate_animals WHERE health_certificate_id = ?");
            if ($d_stmt) {
                $d_stmt->bind_param("i", $hc_id);
                $d_stmt->execute();
                $d_stmt->close();
            }

        } else {
            // Create new Health Certificate
            $i_sql = "
                INSERT INTO health_certificate_issues 
                    (district_id, range_id, report_year, report_month, health_certificate_no, farmer_nic, applicant_name_address, farm_registration_no, date_of_issue, species, total_animals, selected_animals_json, vehicle_fitness_certificate_no, purpose, created_by)
                VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $i_stmt = $mysqli->prepare($i_sql);
            if (!$i_stmt) {
                throw new Exception("Insert prepare failed: " . $mysqli->error);
            }
            $i_stmt->bind_param(
                "iiiissssssisssi",
                $district_id,
                $range_id,
                $report_year,
                $report_month,
                $health_certificate_no,
                $farmer_nic,
                $applicant_combined,
                $farm_reg_no,
                $date_of_issue,
                $primary_species,
                $total_animals,
                $animals_json,
                $vehicle_fitness_certificate_no,
                $purpose,
                $user_id
            );
            $i_stmt->execute();
            $hc_id = (int)($i_stmt->insert_id ?: $mysqli->insert_id);
            $i_stmt->close();
        }

        // Insert child table rows: Schedule - 03 animals
        if (!empty($selected_animals)) {
            $ins_anim = $mysqli->prepare("
                INSERT INTO health_certificate_animals 
                    (health_certificate_id, animal_identification_id, serial_no, identification_no, species, breed, age, colour, specific_marks)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if ($ins_anim) {
                foreach ($selected_animals as $idx => $anim) {
                    $s_no = $idx + 1;
                    $a_id = !empty($anim['animal_id']) ? (int)$anim['animal_id'] : null;
                    $ident = trim($anim['identification_no'] ?? $anim['ear_tag_no'] ?? '');
                    $sp = trim($anim['species'] ?? 'Cattle');
                    $br = trim($anim['breed'] ?? '');
                    $ag = trim($anim['age'] ?? '');
                    $co = trim($anim['colour'] ?? '');
                    $mk = trim($anim['specific_marks'] ?? '');

                    $ins_anim->bind_param("iiissssss", $hc_id, $a_id, $s_no, $ident, $sp, $br, $ag, $co, $mk);
                    $ins_anim->execute();
                }
                $ins_anim->close();
            }
        }

        // =====================================================================
        // COUNTERFOIL CONNECTION: Record / Sync to counterfoil_leaf_issues
        // =====================================================================
        $c_chk = $mysqli->prepare("SELECT id FROM counterfoil_leaf_issues WHERE leaf_serial_no = ? AND counterfoil_type = 'Health Certificate' LIMIT 1");
        $cf_id = 0;
        if ($c_chk) {
            $c_chk->bind_param("s", $health_certificate_no);
            $c_chk->execute();
            $c_res = $c_chk->get_result();
            if ($c_row = $c_res->fetch_assoc()) {
                $cf_id = (int)$c_row['id'];
            }
            $c_chk->close();
        }

        $animal_summary_str = "Total Animals: {$total_animals} ({$primary_species})";
        if ($cf_id > 0) {
            $c_upd = $mysqli->prepare("
                UPDATE counterfoil_leaf_issues SET 
                    farmer_nic = ?, farmer_name = ?, farm_registration_no = ?, location_address = ?,
                    animal_counts_summary = ?, issue_date = ?, purpose = ?, quantity = ?, issued_by = ?
                WHERE id = ?
            ");
            if ($c_upd) {
                $c_upd->bind_param("sssssssiii", $farmer_nic, $applicant_name, $farm_reg_no, $applicant_address, $animal_summary_str, $date_of_issue, $purpose, $total_animals, $user_id, $cf_id);
                $c_upd->execute();
                $c_upd->close();
            }
        } else {
            $c_ins = $mysqli->prepare("
                INSERT INTO counterfoil_leaf_issues 
                    (counterfoil_id, district_id, range_id, counterfoil_type, leaf_serial_no, client_type, farmer_nic, farmer_name, farm_registration_no, location_address, animal_counts_summary, issue_date, purpose, quantity, category_tab, remarks, issued_by)
                VALUES 
                    (8, ?, ?, 'Health Certificate', ?, 'farmer', ?, ?, ?, ?, ?, ?, ?, ?, 'health_certs', 'Schedule-03 Health Certificate Issued', ?)
            ");
            if ($c_ins) {
                $c_ins->bind_param("iisssssssiii", $district_id, $range_id, $health_certificate_no, $farmer_nic, $applicant_name, $farm_reg_no, $applicant_address, $animal_summary_str, $date_of_issue, $purpose, $total_animals, $user_id);
                $c_ins->execute();
                $c_ins->close();
            }
        }

        $mysqli->commit();

        sendJson([
            'success' => true,
            'id' => $hc_id,
            'certificate_no' => $health_certificate_no,
            'total_animals' => $total_animals,
            'message' => 'Health Certificate (Schedule - 03) saved successfully with counterfoil integration.'
        ]);

    } catch (Throwable $e) {
        $mysqli->rollback();
        sendJson(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// -------------------------------------------------------------------------
// ACTION: GET HEALTH CERTIFICATE FOR PREVIEW / PRINT (SCHEDULE - 03)
// -------------------------------------------------------------------------
if ($action === 'get_health_certificate') {
    $id = (int)($_REQUEST['id'] ?? 0);
    if ($id <= 0) {
        sendJson(['success' => false, 'message' => 'Invalid certificate ID.']);
    }

    $stmt = $mysqli->prepare("
        SELECT hc.*, vr.name AS range_name, d.name AS district_name
        FROM health_certificate_issues hc
        LEFT JOIN veterinary_ranges vr ON vr.id = hc.range_id
        LEFT JOIN districts d ON d.id = hc.district_id
        WHERE hc.id = ?
        LIMIT 1
    ");
    if (!$stmt) {
        sendJson(['success' => false, 'message' => 'Query error.']);
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $record = $res->fetch_assoc();
    $stmt->close();

    if (!$record) {
        sendJson(['success' => false, 'message' => 'Certificate not found.']);
    }

    // Fetch Schedule - 03 Animals
    $animals = [];
    $a_stmt = $mysqli->prepare("
        SELECT serial_no, identification_no, species, breed, age, colour, specific_marks
        FROM health_certificate_animals
        WHERE health_certificate_id = ?
        ORDER BY serial_no ASC
    ");
    if ($a_stmt) {
        $a_stmt->bind_param("i", $id);
        $a_stmt->execute();
        $a_res = $a_stmt->get_result();
        while ($arow = $a_res->fetch_assoc()) {
            $animals[] = $arow;
        }
        $a_stmt->close();
    }

    // Fallback if animals not in child table but in JSON
    if (empty($animals) && !empty($record['selected_animals_json'])) {
        $json_arr = json_decode($record['selected_animals_json'], true);
        if (is_array($json_arr)) {
            foreach ($json_arr as $i => $item) {
                $animals[] = [
                    'serial_no' => $i + 1,
                    'identification_no' => $item['identification_no'] ?? $item['ear_tag_no'] ?? '',
                    'species' => $item['species'] ?? $record['species'],
                    'breed' => $item['breed'] ?? '—',
                    'age' => $item['age'] ?? '—',
                    'colour' => $item['colour'] ?? '—',
                    'specific_marks' => $item['specific_marks'] ?? '—'
                ];
            }
        }
    }

    $record['animals'] = $animals;

    sendJson([
        'success' => true,
        'certificate' => $record
    ]);
}

// -------------------------------------------------------------------------
// ACTION: DELETE HEALTH CERTIFICATE
// -------------------------------------------------------------------------
if ($action === 'delete_health_certificate') {
    $id = (int)($_REQUEST['id'] ?? 0);
    if ($id <= 0) {
        sendJson(['success' => false, 'message' => 'Invalid ID.']);
    }

    // Get certificate number to clean up counterfoil
    $cert_no = '';
    $c_q = $mysqli->prepare("SELECT health_certificate_no FROM health_certificate_issues WHERE id = ? AND range_id = ?");
    if ($c_q) {
        $c_q->bind_param("ii", $id, $range_id);
        $c_q->execute();
        $c_res = $c_q->get_result();
        if ($c_row = $c_res->fetch_assoc()) {
            $cert_no = $c_row['health_certificate_no'];
        }
        $c_q->close();
    }

    $d_hc = $mysqli->prepare("DELETE FROM health_certificate_issues WHERE id = ? AND range_id = ?");
    if ($d_hc) {
        $d_hc->bind_param("ii", $id, $range_id);
        $d_hc->execute();
        $d_hc->close();
    }

    $d_an = $mysqli->prepare("DELETE FROM health_certificate_animals WHERE health_certificate_id = ?");
    if ($d_an) {
        $d_an->bind_param("i", $id);
        $d_an->execute();
        $d_an->close();
    }

    if (!empty($cert_no)) {
        $d_cf = $mysqli->prepare("DELETE FROM counterfoil_leaf_issues WHERE leaf_serial_no = ? AND counterfoil_type = 'Health Certificate'");
        if ($d_cf) {
            $d_cf->bind_param("s", $cert_no);
            $d_cf->execute();
            $d_cf->close();
        }
    }

    sendJson(['success' => true, 'message' => 'Health certificate record and associated counterfoil removed.']);
}

// -------------------------------------------------------------------------
// ACTION: GET HEALTH CERTIFICATE OPTIONS FOR LINKING IN TAB 2
// -------------------------------------------------------------------------
if ($action === 'get_health_cert_options') {
    $options = [];
    $stmt = $mysqli->prepare("
        SELECT id, health_certificate_no, date_of_issue, farmer_nic, applicant_name_address, farm_registration_no, species, total_animals, vehicle_fitness_certificate_no
        FROM health_certificate_issues
        WHERE range_id = ?
        ORDER BY id DESC LIMIT 50
    ");
    if ($stmt) {
        $stmt->bind_param("i", $range_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $lines = explode("\n", $r['applicant_name_address']);
            $name = trim($lines[0] ?? '');
            $options[] = [
                'id' => (int)$r['id'],
                'cert_no' => $r['health_certificate_no'],
                'date' => $r['date_of_issue'],
                'farmer_name' => $name,
                'farmer_nic' => $r['farmer_nic'],
                'farm_reg_no' => $r['farm_registration_no'],
                'species' => $r['species'],
                'total_animals' => (int)$r['total_animals'],
                'vehicle_cert_no' => $r['vehicle_fitness_certificate_no']
            ];
        }
        $stmt->close();
    }
    sendJson(['success' => true, 'options' => $options]);
}

// -------------------------------------------------------------------------
// ACTION: SAVE VEHICLE FITNESS CERTIFICATE (TAB 2, SCHEDULE - 04)
// -------------------------------------------------------------------------
if ($action === 'save_vehicle_fitness') {
    $edit_id = (int)($_POST['id'] ?? 0);
    $report_year = (int)($_POST['report_year'] ?? date('Y'));
    $report_month = (int)($_POST['report_month'] ?? date('m'));
    $certificate_no = trim($_POST['certificate_no'] ?? '');
    $health_cert_id = !empty($_POST['health_certificate_id']) ? (int)$_POST['health_certificate_id'] : null;
    $health_cert_no = trim($_POST['health_certificate_no'] ?? '');
    $farm_reg_no = trim($_POST['farm_registration_no'] ?? '');
    $farmer_nic = trim($_POST['farmer_nic'] ?? '');
    $applicant_name = trim($_POST['applicant_name'] ?? '');
    $applicant_address = trim($_POST['applicant_address'] ?? '');
    $vehicle_reg_no = trim($_POST['vehicle_registration_no'] ?? '');
    $vehicle_type = trim($_POST['vehicle_type'] ?? 'Lorry / Truck');
    $driver_name = trim($_POST['driver_name'] ?? '');
    $driver_license_no = trim($_POST['driver_license_no'] ?? '');
    $animal_species = trim($_POST['animal_species'] ?? 'Cattle');
    $total_animals = (int)($_POST['total_animals'] ?? 0);
    $issue_date = trim($_POST['issue_date'] ?? date('Y-m-d'));
    $valid_until = trim($_POST['valid_until'] ?? date('Y-m-d', strtotime('+3 days')));
    $from_location = trim($_POST['from_location'] ?? '');
    $to_location = trim($_POST['to_location'] ?? '');

    // Verification Conditions 02 through 07 (Schedule - 04 Checklist)
    $cond_02 = (isset($_POST['cond_02_easy_observance']) && $_POST['cond_02_easy_observance'] === 'No') ? 'No' : 'Yes';
    $cond_03 = (isset($_POST['cond_03_ramp']) && $_POST['cond_03_ramp'] === 'No') ? 'No' : 'Yes';
    $cond_04 = (isset($_POST['cond_04_floor_holes']) && $_POST['cond_04_floor_holes'] === 'No') ? 'No' : 'Yes';
    $cond_05 = (isset($_POST['cond_05_adequate_covering']) && $_POST['cond_05_adequate_covering'] === 'No') ? 'No' : 'Yes';
    $cond_06 = (isset($_POST['cond_06_ventilation']) && $_POST['cond_06_ventilation'] === 'No') ? 'No' : 'Yes';
    $cond_07 = (isset($_POST['cond_07_max_animals']) && $_POST['cond_07_max_animals'] === 'No') ? 'No' : 'Yes';

    // Transport approval is granted only if all conditions 02-07 are 'Yes'
    $all_passed = ($cond_02 === 'Yes' && $cond_03 === 'Yes' && $cond_04 === 'Yes' && $cond_05 === 'Yes' && $cond_06 === 'Yes' && $cond_07 === 'Yes');
    $approval_status = $all_passed ? 'Approved' : 'Rejected';
    $remarks = trim($_POST['remarks'] ?? '');

    if (empty($certificate_no) || empty($applicant_name) || empty($vehicle_reg_no)) {
        sendJson(['success' => false, 'message' => 'Please fill in Certificate No, Applicant Name, and Vehicle Registration No.']);
    }

    $mysqli->begin_transaction();
    try {
        if ($edit_id > 0) {
            $u_sql = "
                UPDATE vehicle_fitness_certificates SET 
                    report_year = ?, report_month = ?, certificate_no = ?, health_certificate_id = ?, health_certificate_no = ?,
                    farm_registration_no = ?, farmer_nic = ?, applicant_name = ?, applicant_address = ?,
                    vehicle_registration_no = ?, vehicle_type = ?, driver_name = ?, driver_license_no = ?,
                    animal_species = ?, total_animals = ?, issue_date = ?, valid_until = ?, from_location = ?, to_location = ?,
                    cond_02_easy_observance = ?, cond_03_ramp = ?, cond_04_floor_holes = ?, cond_05_adequate_covering = ?,
                    cond_06_ventilation = ?, cond_07_max_animals = ?, approval_status = ?, remarks = ?
                WHERE id = ? AND range_id = ?
            ";
            $u_stmt = $mysqli->prepare($u_sql);
            if (!$u_stmt) {
                throw new Exception("Update prepare failed: " . $mysqli->error);
            }
            $u_stmt->bind_param(
                "iisissssssssssissssssssssssii",
                $report_year, $report_month, $certificate_no, $health_cert_id, $health_cert_no,
                $farm_reg_no, $farmer_nic, $applicant_name, $applicant_address,
                $vehicle_reg_no, $vehicle_type, $driver_name, $driver_license_no,
                $animal_species, $total_animals, $issue_date, $valid_until, $from_location, $to_location,
                $cond_02, $cond_03, $cond_04, $cond_05,
                $cond_06, $cond_07, $approval_status, $remarks,
                $edit_id, $range_id
            );
            $u_stmt->execute();
            $u_stmt->close();
            $vfc_id = $edit_id;
        } else {
            $i_sql = "
                INSERT INTO vehicle_fitness_certificates 
                    (district_id, range_id, report_year, report_month, certificate_no, health_certificate_id, health_certificate_no,
                     farm_registration_no, farmer_nic, applicant_name, applicant_address,
                     vehicle_registration_no, vehicle_type, driver_name, driver_license_no,
                     animal_species, total_animals, issue_date, valid_until, from_location, to_location,
                     cond_02_easy_observance, cond_03_ramp, cond_04_floor_holes, cond_05_adequate_covering,
                     cond_06_ventilation, cond_07_max_animals, approval_status, remarks, created_by)
                VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $i_stmt = $mysqli->prepare($i_sql);
            if (!$i_stmt) {
                throw new Exception("Insert prepare failed: " . $mysqli->error);
            }
            $i_stmt->bind_param(
                "iiiisissssssssssissssssssssssi",
                $district_id, $range_id, $report_year, $report_month, $certificate_no, $health_cert_id, $health_cert_no,
                $farm_reg_no, $farmer_nic, $applicant_name, $applicant_address,
                $vehicle_reg_no, $vehicle_type, $driver_name, $driver_license_no,
                $animal_species, $total_animals, $issue_date, $valid_until, $from_location, $to_location,
                $cond_02, $cond_03, $cond_04, $cond_05,
                $cond_06, $cond_07, $approval_status, $remarks, $user_id
            );
            $i_stmt->execute();
            $vfc_id = (int)($i_stmt->insert_id ?: $mysqli->insert_id);
            $i_stmt->close();
        }

        // =====================================================================
        // COUNTERFOIL CONNECTION: Record / Sync to counterfoil_leaf_issues
        // =====================================================================
        $c_chk = $mysqli->prepare("SELECT id FROM counterfoil_leaf_issues WHERE leaf_serial_no = ? AND counterfoil_type = 'Animal Transport' LIMIT 1");
        $cf_id = 0;
        if ($c_chk) {
            $c_chk->bind_param("s", $certificate_no);
            $c_chk->execute();
            $c_res = $c_chk->get_result();
            if ($c_row = $c_res->fetch_assoc()) {
                $cf_id = (int)$c_row['id'];
            }
            $c_chk->close();
        }

        $summary_str = "Vehicle: {$vehicle_reg_no} | Animals: {$total_animals} ({$animal_species}) | Status: {$approval_status}";
        if ($cf_id > 0) {
            $c_upd = $mysqli->prepare("
                UPDATE counterfoil_leaf_issues SET 
                    farmer_nic = ?, farmer_name = ?, farm_registration_no = ?, location_address = ?,
                    animal_counts_summary = ?, issue_date = ?, purpose = ?, quantity = ?, issued_by = ?
                WHERE id = ?
            ");
            if ($c_upd) {
                $purpose_v = "Animal Transport Vehicle Fitness: " . $vehicle_reg_no;
                $c_upd->bind_param("sssssssiii", $farmer_nic, $applicant_name, $farm_reg_no, $applicant_address, $summary_str, $issue_date, $purpose_v, $total_animals, $user_id, $cf_id);
                $c_upd->execute();
                $c_upd->close();
            }
        } else {
            $c_ins = $mysqli->prepare("
                INSERT INTO counterfoil_leaf_issues 
                    (counterfoil_id, district_id, range_id, counterfoil_type, leaf_serial_no, client_type, farmer_nic, farmer_name, farm_registration_no, location_address, animal_counts_summary, issue_date, purpose, quantity, category_tab, remarks, issued_by)
                VALUES 
                    (2, ?, ?, 'Animal Transport', ?, 'farmer', ?, ?, ?, ?, ?, ?, ?, ?, 'vehicle_fitness', 'Schedule-04 Vehicle Fitness Issued', ?)
            ");
            if ($c_ins) {
                $purpose_v = "Animal Transport Vehicle Fitness: " . $vehicle_reg_no;
                $c_ins->bind_param("iisssssssiii", $district_id, $range_id, $certificate_no, $farmer_nic, $applicant_name, $farm_reg_no, $applicant_address, $summary_str, $issue_date, $purpose_v, $total_animals, $user_id);
                $c_ins->execute();
                $c_ins->close();
            }
        }

        // If a linked Health Certificate exists, update its vehicle_fitness_certificate_no field
        if (!empty($health_cert_no)) {
            $u_hc = $mysqli->prepare("UPDATE health_certificate_issues SET vehicle_fitness_certificate_no = ? WHERE health_certificate_no = ? AND range_id = ?");
            if ($u_hc) {
                $u_hc->bind_param("ssi", $certificate_no, $health_cert_no, $range_id);
                $u_hc->execute();
                $u_hc->close();
            }
        }

        $mysqli->commit();

        sendJson([
            'success' => true,
            'id' => $vfc_id,
            'certificate_no' => $certificate_no,
            'approval_status' => $approval_status,
            'all_passed' => $all_passed,
            'message' => "Vehicle Fitness Certificate (Schedule - 04) saved successfully with status: {$approval_status}."
        ]);

    } catch (Throwable $e) {
        $mysqli->rollback();
        sendJson(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// -------------------------------------------------------------------------
// ACTION: GET VEHICLE FITNESS CERTIFICATE FOR PREVIEW / PRINT (SCHEDULE - 04)
// -------------------------------------------------------------------------
if ($action === 'get_vehicle_fitness') {
    $id = (int)($_REQUEST['id'] ?? 0);
    if ($id <= 0) {
        sendJson(['success' => false, 'message' => 'Invalid certificate ID.']);
    }

    $stmt = $mysqli->prepare("
        SELECT vfc.*, vr.name AS range_name, d.name AS district_name
        FROM vehicle_fitness_certificates vfc
        LEFT JOIN veterinary_ranges vr ON vr.id = vfc.range_id
        LEFT JOIN districts d ON d.id = vfc.district_id
        WHERE vfc.id = ?
        LIMIT 1
    ");
    if (!$stmt) {
        sendJson(['success' => false, 'message' => 'Query error.']);
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $record = $res->fetch_assoc();
    $stmt->close();

    if (!$record) {
        sendJson(['success' => false, 'message' => 'Vehicle certificate not found.']);
    }

    sendJson(['success' => true, 'certificate' => $record]);
}

// -------------------------------------------------------------------------
// ACTION: DELETE VEHICLE FITNESS CERTIFICATE
// -------------------------------------------------------------------------
if ($action === 'delete_vehicle_fitness') {
    $id = (int)($_REQUEST['id'] ?? 0);
    if ($id <= 0) {
        sendJson(['success' => false, 'message' => 'Invalid ID.']);
    }

    $cert_no = '';
    $c_q = $mysqli->prepare("SELECT certificate_no FROM vehicle_fitness_certificates WHERE id = ? AND range_id = ?");
    if ($c_q) {
        $c_q->bind_param("ii", $id, $range_id);
        $c_q->execute();
        $c_res = $c_q->get_result();
        if ($c_row = $c_res->fetch_assoc()) {
            $cert_no = $c_row['certificate_no'];
        }
        $c_q->close();
    }

    $stmt = $mysqli->prepare("DELETE FROM vehicle_fitness_certificates WHERE id = ? AND range_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $range_id);
        $stmt->execute();
        $stmt->close();
    }

    if (!empty($cert_no)) {
        $d_cf = $mysqli->prepare("DELETE FROM counterfoil_leaf_issues WHERE leaf_serial_no = ? AND counterfoil_type = 'Animal Transport'");
        if ($d_cf) {
            $d_cf->bind_param("s", $cert_no);
            $d_cf->execute();
            $d_cf->close();
        }
    }

    sendJson(['success' => true, 'message' => 'Vehicle fitness certificate record and associated counterfoil removed.']);
}

sendJson(['success' => false, 'message' => 'Unsupported action requested.']);
