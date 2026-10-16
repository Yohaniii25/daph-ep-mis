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
    'admin',
    'super_admin'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
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
    $range_id = 1; // Default to Ampara range
}

$district_id = (int)($_SESSION['district_id'] ?? 0);
if ($district_id <= 0) {
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

function sendJson($data) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data);
    exit();
}

// -------------------------------------------------------------------------
// ACTION: GET DRUG OPTIONS & CURRENT INVENTORY STOCKS
// -------------------------------------------------------------------------
if ($action === 'get_drug_options') {
    $drugs = [];
    $res = $mysqli->query("
        SELECT dt.id, dt.vaccine_name AS drug_name, dt.brand_name, dt.chemical_composition, dt.target_animal,
               IFNULL((
                   SELECT (dr.starter_count_month + dr.during_month_received - dr.used_doses_count - dr.doses_damaged)
                   FROM drug_records dr
                   WHERE dr.drug_type_id = dt.id
                   ORDER BY dr.log_date DESC, dr.id DESC
                   LIMIT 1
               ), 100) AS current_stock
        FROM drug_types dt
        ORDER BY dt.vaccine_name ASC
    ");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $drugs[] = [
                'id' => (int)$row['id'],
                'drug_name' => $row['drug_name'],
                'brand_name' => $row['brand_name'] ?? '',
                'composition' => $row['chemical_composition'] ?? '',
                'stock' => (float)$row['current_stock']
            ];
        }
    }
    sendJson(['success' => true, 'drugs' => $drugs]);
}

// -------------------------------------------------------------------------
// ACTION: GET SPECIFIC DRUG STOCK
// -------------------------------------------------------------------------
if ($action === 'get_drug_stock') {
    $drug_id = (int)($_GET['drug_id'] ?? 0);
    $drug_name = trim($_GET['drug_name'] ?? '');
    $stock = 0.00;

    if ($drug_id > 0) {
        $stmt = $mysqli->prepare("
            SELECT (starter_count_month + during_month_received - used_doses_count - doses_damaged) AS bal
            FROM drug_records
            WHERE drug_type_id = ?
            ORDER BY log_date DESC, id DESC
            LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param("i", $drug_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($r = $res->fetch_assoc()) {
                $stock = max(0, (float)$r['bal']);
            }
            $stmt->close();
        }
    }
    // Also check if previously distributed stock balance exists
    if ($stock <= 0 && !empty($drug_name)) {
        $stmt2 = $mysqli->prepare("
            SELECT balance_in_hand 
            FROM mobile_clinic_drug_distributions 
            WHERE drug_name = ? AND range_id = ? 
            ORDER BY distribution_date DESC, id DESC 
            LIMIT 1
        ");
        if ($stmt2) {
            $stmt2->bind_param("si", $drug_name, $range_id);
            $stmt2->execute();
            if ($r2 = $stmt2->get_result()->fetch_assoc()) {
                $stock = max(0, (float)$r2['balance_in_hand']);
            }
            $stmt2->close();
        }
    }

    sendJson(['success' => true, 'stock' => $stock]);
}

// -------------------------------------------------------------------------
// ACTION: QUICK FARMER LOOKUP FOR TREATMENT GRID
// -------------------------------------------------------------------------
if ($action === 'lookup_farmer') {
    $query = trim($_REQUEST['query'] ?? '');
    if (empty($query)) {
        sendJson(['success' => false, 'message' => 'Query is empty.']);
    }

    $clean = preg_replace('/[^0-9a-zA-Z]/', '', $query);
    $stmt = $mysqli->prepare("
        SELECT farmer_name, nic, farmer_address, registration_no
        FROM farm_registration_renewals
        WHERE registration_no = ? OR nic = ? OR REPLACE(REPLACE(registration_no, ' ', ''), '-', '') = ? OR REPLACE(REPLACE(nic, ' ', ''), '-', '') = ?
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param("ssss", $query, $query, $clean, $clean);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($f = $res->fetch_assoc()) {
            $stmt->close();
            sendJson([
                'success' => true,
                'farmer' => [
                    'farmer_name' => $f['farmer_name'],
                    'farmer_nic' => $f['nic'],
                    'farmer_address' => $f['farmer_address'],
                    'farm_reg_no' => $f['registration_no']
                ]
            ]);
        }
        $stmt->close();
    }

    // Secondary search in farmers table
    $stmt2 = $mysqli->prepare("SELECT name, nic, address FROM farmers WHERE nic = ? OR name LIKE ? LIMIT 1");
    if ($stmt2) {
        $like = "%{$query}%";
        $stmt2->bind_param("ss", $query, $like);
        $stmt2->execute();
        if ($f2 = $stmt2->get_result()->fetch_assoc()) {
            $stmt2->close();
            sendJson([
                'success' => true,
                'farmer' => [
                    'farmer_name' => $f2['name'],
                    'farmer_nic' => $f2['nic'],
                    'farmer_address' => $f2['address'],
                    'farm_reg_no' => ''
                ]
            ]);
        }
        $stmt2->close();
    }

    sendJson(['success' => false, 'message' => 'No farmer found.']);
}

// -------------------------------------------------------------------------
// ACTION: SAVE MOBILE CLINIC & ACTIVITIES (TAB 1)
// -------------------------------------------------------------------------
if ($action === 'save_mobile_clinic') {
    $edit_id = (int)($_POST['id'] ?? 0);
    $clinic_date = trim($_POST['clinic_date'] ?? date('Y-m-d'));
    $places_visited = trim($_POST['places_visited'] ?? '');
    $farmers_benefited = (int)($_POST['farmers_benefited'] ?? 0);
    $cases_reported = (int)($_POST['cases_reported'] ?? 0);
    $cases_attended = (int)($_POST['cases_attended'] ?? 0);

    // Livestock Breeding
    $ai_done = (int)($_POST['ai_done'] ?? 0);
    $pd_diagnosed = (int)($_POST['pd_diagnosed'] ?? 0);

    // Livestock Health
    $animals_vaccinated = (int)($_POST['animals_vaccinated'] ?? 0);
    $animals_treated = (int)($_POST['animals_treated'] ?? 0);
    $poultry_gumboro = (int)($_POST['poultry_gumboro'] ?? 0);
    $poultry_ranikhet_1st = (int)($_POST['poultry_ranikhet_1st'] ?? 0);
    $poultry_ranikhet_2nd = (int)($_POST['poultry_ranikhet_2nd'] ?? 0);
    $poultry_fowlpox = (int)($_POST['poultry_fowlpox'] ?? 0);
    $cattle_goat_fmd_bq = (int)($_POST['cattle_goat_fmd_bq'] ?? 0);
    $cattle_goat_arv = (int)($_POST['cattle_goat_arv'] ?? 0);

    // Extension Activities
    $training_conducted = (int)($_POST['training_conducted'] ?? 0);
    $training_type = trim($_POST['training_type'] ?? '');
    $training_participants = (int)($_POST['training_participants'] ?? 0);
    $animals_ear_tagged = (int)($_POST['animals_ear_tagged'] ?? 0);

    $remarks = trim($_POST['remarks'] ?? '');

    // Team Members
    $team_members = [];
    if (isset($_POST['team_members']) && is_array($_POST['team_members'])) {
        foreach ($_POST['team_members'] as $tm) {
            $tname = trim($tm['name'] ?? '');
            $tdesig = trim($tm['designation'] ?? '');
            if (!empty($tname)) {
                $team_members[] = [
                    'name' => $tname,
                    'designation' => $tdesig,
                    'contact' => trim($tm['contact'] ?? '')
                ];
            }
        }
    }
    $team_json = json_encode($team_members, JSON_UNESCAPED_UNICODE);

    $report_year = (int)date('Y', strtotime($clinic_date));
    $report_month = (int)date('m', strtotime($clinic_date));

    $mysqli->begin_transaction();
    try {
        if ($edit_id > 0) {
            $stmt = $mysqli->prepare("
                UPDATE mobile_clinics SET 
                    clinic_date = ?, places_visited = ?, team_members_json = ?,
                    farmers_benefited = ?, cases_reported = ?, cases_attended = ?,
                    ai_done = ?, pd_diagnosed = ?, animals_vaccinated = ?, animals_treated = ?,
                    poultry_gumboro = ?, poultry_ranikhet_1st = ?, poultry_ranikhet_2nd = ?, poultry_fowlpox = ?,
                    cattle_goat_fmd_bq = ?, cattle_goat_arv = ?,
                    training_conducted = ?, training_type = ?, training_participants = ?, animals_ear_tagged = ?,
                    remarks = ?, report_year = ?, report_month = ?
                WHERE id = ? AND range_id = ?
            ");
            if (!$stmt) {
                throw new Exception("Update prepare failed: " . $mysqli->error);
            }
            $stmt->bind_param(
                "sssiiiiiiiiiiiiiisiiisiii",
                $clinic_date, $places_visited, $team_json,
                $farmers_benefited, $cases_reported, $cases_attended,
                $ai_done, $pd_diagnosed, $animals_vaccinated, $animals_treated,
                $poultry_gumboro, $poultry_ranikhet_1st, $poultry_ranikhet_2nd, $poultry_fowlpox,
                $cattle_goat_fmd_bq, $cattle_goat_arv,
                $training_conducted, $training_type, $training_participants, $animals_ear_tagged,
                $remarks, $report_year, $report_month,
                $edit_id, $range_id
            );
            if (!$stmt->execute()) {
                throw new Exception("Update failed: " . $stmt->error);
            }
            $stmt->close();
            $clinic_id = $edit_id;
        } else {
            $stmt = $mysqli->prepare("
                INSERT INTO mobile_clinics 
                    (district_id, range_id, report_year, report_month, clinic_date, places_visited, team_members_json,
                     farmers_benefited, cases_reported, cases_attended, ai_done, pd_diagnosed,
                     animals_vaccinated, animals_treated, poultry_gumboro, poultry_ranikhet_1st, poultry_ranikhet_2nd, poultry_fowlpox,
                     cattle_goat_fmd_bq, cattle_goat_arv, training_conducted, training_type, training_participants,
                     animals_ear_tagged, remarks, created_by)
                VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$stmt) {
                throw new Exception("Insert prepare failed: " . $mysqli->error);
            }
            $stmt->bind_param(
                "iiiisssiiiiiiiiiiiiiisiisi",
                $district_id, $range_id, $report_year, $report_month, $clinic_date, $places_visited, $team_json,
                $farmers_benefited, $cases_reported, $cases_attended, $ai_done, $pd_diagnosed,
                $animals_vaccinated, $animals_treated, $poultry_gumboro, $poultry_ranikhet_1st, $poultry_ranikhet_2nd, $poultry_fowlpox,
                $cattle_goat_fmd_bq, $cattle_goat_arv, $training_conducted, $training_type, $training_participants,
                $animals_ear_tagged, $remarks, $user_id
            );
            if (!$stmt->execute()) {
                throw new Exception("Insert failed: " . $stmt->error);
            }
            $clinic_id = (int)($stmt->insert_id ?: $mysqli->insert_id);
            $stmt->close();
        }

        $mysqli->commit();
        sendJson([
            'success' => true,
            'id' => $clinic_id,
            'message' => 'Mobile Clinic & Activities recorded successfully!'
        ]);
    } catch (Throwable $e) {
        $mysqli->rollback();
        sendJson(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// -------------------------------------------------------------------------
// ACTION: GET MOBILE CLINIC (TAB 1)
// -------------------------------------------------------------------------
if ($action === 'get_mobile_clinic') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        sendJson(['success' => false, 'message' => 'Invalid clinic ID.']);
    }

    $stmt = $mysqli->prepare("
        SELECT mc.*, vr.name AS range_name, d.name AS district_name
        FROM mobile_clinics mc
        LEFT JOIN veterinary_ranges vr ON vr.id = mc.range_id
        LEFT JOIN districts d ON d.id = mc.district_id
        WHERE mc.id = ?
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

    if ($record) {
        $record['team_members'] = json_decode($record['team_members_json'] ?? '[]', true) ?: [];
        sendJson(['success' => true, 'clinic' => $record]);
    } else {
        sendJson(['success' => false, 'message' => 'Clinic record not found.']);
    }
}

// -------------------------------------------------------------------------
// ACTION: DELETE MOBILE CLINIC (TAB 1)
// -------------------------------------------------------------------------
if ($action === 'delete_mobile_clinic') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        sendJson(['success' => false, 'message' => 'Invalid clinic ID.']);
    }

    $stmt = $mysqli->prepare("DELETE FROM mobile_clinics WHERE id = ? AND range_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $range_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        sendJson([
            'success' => ($affected > 0),
            'message' => ($affected > 0) ? 'Mobile clinic record deleted successfully.' : 'Record could not be deleted or does not exist.'
        ]);
    } else {
        sendJson(['success' => false, 'message' => 'Query error.']);
    }
}

// -------------------------------------------------------------------------
// ACTION: SAVE DRUG DISTRIBUTION & TREATMENT GRID (TAB 2)
// -------------------------------------------------------------------------
if ($action === 'save_drug_distribution') {
    $edit_id = (int)($_POST['id'] ?? 0);
    $distribution_date = trim($_POST['distribution_date'] ?? date('Y-m-d'));
    $drug_name = trim($_POST['drug_name'] ?? '');
    $drug_type_id = !empty($_POST['drug_type_id']) ? (int)$_POST['drug_type_id'] : null;
    $initial_stock = (float)($_POST['initial_stock'] ?? 0);
    $unit_of_measure = trim($_POST['unit_of_measure'] ?? 'Doses');
    $remarks = trim($_POST['remarks'] ?? '');

    $treatments = isset($_POST['treatments']) && is_array($_POST['treatments']) ? $_POST['treatments'] : [];

    if (empty($drug_name) || empty($distribution_date)) {
        sendJson(['success' => false, 'message' => 'Please provide Drug Name and Distribution Date.']);
    }

    // Calculate total issued from treatment grid
    $calculated_total_issued = 0.00;
    foreach ($treatments as $t) {
        $dose = (float)($t['total_dose_issued'] ?? 0);
        if ($dose > 0) {
            $calculated_total_issued += $dose;
        }
    }

    $balance_in_hand = max(0, $initial_stock - $calculated_total_issued);
    $report_year = (int)date('Y', strtotime($distribution_date));
    $report_month = (int)date('m', strtotime($distribution_date));

    $mysqli->begin_transaction();
    try {
        if ($edit_id > 0) {
            $stmt = $mysqli->prepare("
                UPDATE mobile_clinic_drug_distributions SET 
                    distribution_date = ?, drug_name = ?, drug_type_id = ?,
                    initial_stock = ?, total_issued = ?, balance_in_hand = ?,
                    unit_of_measure = ?, remarks = ?, report_year = ?, report_month = ?
                WHERE id = ? AND range_id = ?
            ");
            if (!$stmt) {
                throw new Exception("Update prepare failed: " . $mysqli->error);
            }
            $stmt->bind_param(
                "ssidddssiiii",
                $distribution_date, $drug_name, $drug_type_id,
                $initial_stock, $calculated_total_issued, $balance_in_hand,
                $unit_of_measure, $remarks, $report_year, $report_month,
                $edit_id, $range_id
            );
            if (!$stmt->execute()) {
                throw new Exception("Update failed: " . $stmt->error);
            }
            $stmt->close();
            $dist_id = $edit_id;

            // Delete old treatments
            $d_stmt = $mysqli->prepare("DELETE FROM mobile_clinic_treatments WHERE distribution_id = ?");
            if ($d_stmt) {
                $d_stmt->bind_param("i", $dist_id);
                $d_stmt->execute();
                $d_stmt->close();
            }
        } else {
            $stmt = $mysqli->prepare("
                INSERT INTO mobile_clinic_drug_distributions 
                    (district_id, range_id, report_year, report_month, distribution_date, drug_name, drug_type_id,
                     initial_stock, total_issued, balance_in_hand, unit_of_measure, remarks, created_by)
                VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$stmt) {
                throw new Exception("Insert prepare failed: " . $mysqli->error);
            }
            $stmt->bind_param(
                "iiiissidddssi",
                $district_id, $range_id, $report_year, $report_month, $distribution_date, $drug_name, $drug_type_id,
                $initial_stock, $calculated_total_issued, $balance_in_hand, $unit_of_measure, $remarks, $user_id
            );
            if (!$stmt->execute()) {
                throw new Exception("Insert failed: " . $stmt->error);
            }
            $dist_id = (int)($stmt->insert_id ?: $mysqli->insert_id);
            $stmt->close();
        }

        // Insert Treatments Grid Rows
        if (!empty($treatments)) {
            $t_ins = $mysqli->prepare("
                INSERT INTO mobile_clinic_treatments 
                    (distribution_id, serial_no, farmer_name, farmer_address, farmer_nic, species_affected, disease_diagnosed, animals_treated, total_dose_issued)
                VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if ($t_ins) {
                foreach ($treatments as $idx => $t) {
                    $s_no = $idx + 1;
                    $fname = trim($t['farmer_name'] ?? '');
                    if (empty($fname)) continue;
                    $faddr = trim($t['farmer_address'] ?? '');
                    $fnic = trim($t['farmer_nic'] ?? '');
                    $species = trim($t['species_affected'] ?? 'Cattle');
                    $disease = trim($t['disease_diagnosed'] ?? 'Clinical Condition');
                    $anim_cnt = max(1, (int)($t['animals_treated'] ?? 1));
                    $dose = (float)($t['total_dose_issued'] ?? 0);

                    $t_ins->bind_param("iisssssid", $dist_id, $s_no, $fname, $faddr, $fnic, $species, $disease, $anim_cnt, $dose);
                    $t_ins->execute();
                }
                $t_ins->close();
            }
        }

        // =====================================================================
        // INVENTORY AUTOMATION: Sync deduction with drug_records
        // =====================================================================
        if ($drug_type_id && $drug_type_id > 0 && $calculated_total_issued > 0) {
            // Check if record exists for this month and drug
            $dr_chk = $mysqli->prepare("
                SELECT id, used_doses_count, balance_end_month, starter_count_month, during_month_received, doses_damaged 
                FROM drug_records 
                WHERE drug_type_id = ? AND MONTH(log_date) = ? AND YEAR(log_date) = ? 
                LIMIT 1
            ");
            if ($dr_chk) {
                $dr_chk->bind_param("iii", $drug_type_id, $report_month, $report_year);
                $dr_chk->execute();
                $dr_res = $dr_chk->get_result();
                if ($dr_row = $dr_res->fetch_assoc()) {
                    $rec_id = (int)$dr_row['id'];
                    $new_used = (int)$dr_row['used_doses_count'] + (int)$calculated_total_issued;
                    $new_bal = (int)$dr_row['starter_count_month'] + (int)$dr_row['during_month_received'] - $new_used - (int)$dr_row['doses_damaged'];
                    $u_dr = $mysqli->prepare("UPDATE drug_records SET used_doses_count = ?, balance_end_month = ? WHERE id = ?");
                    if ($u_dr) {
                        $u_dr->bind_param("iii", $new_used, $new_bal, $rec_id);
                        $u_dr->execute();
                        $u_dr->close();
                    }
                }
                $dr_chk->close();
            }
        }

        $mysqli->commit();
        sendJson([
            'success' => true,
            'id' => $dist_id,
            'total_issued' => $calculated_total_issued,
            'balance_in_hand' => $balance_in_hand,
            'message' => "Drug Distribution recorded! Total Issued: {$calculated_total_issued} {$unit_of_measure}. Balance in Hand: {$balance_in_hand}."
        ]);

    } catch (Throwable $e) {
        $mysqli->rollback();
        sendJson(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// -------------------------------------------------------------------------
// ACTION: GET DRUG DISTRIBUTION & TREATMENTS (TAB 2)
// -------------------------------------------------------------------------
if ($action === 'get_drug_distribution') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        sendJson(['success' => false, 'message' => 'Invalid distribution ID.']);
    }

    $stmt = $mysqli->prepare("
        SELECT dd.*, vr.name AS range_name, d.name AS district_name
        FROM mobile_clinic_drug_distributions dd
        LEFT JOIN veterinary_ranges vr ON vr.id = dd.range_id
        LEFT JOIN districts d ON d.id = dd.district_id
        WHERE dd.id = ?
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

    if ($record) {
        $treatments = [];
        $t_stmt = $mysqli->prepare("
            SELECT * FROM mobile_clinic_treatments 
            WHERE distribution_id = ? 
            ORDER BY serial_no ASC, id ASC
        ");
        if ($t_stmt) {
            $t_stmt->bind_param("i", $id);
            $t_stmt->execute();
            $t_res = $t_stmt->get_result();
            while ($t_row = $t_res->fetch_assoc()) {
                $treatments[] = $t_row;
            }
            $t_stmt->close();
        }
        $record['treatments'] = $treatments;
        sendJson(['success' => true, 'distribution' => $record]);
    } else {
        sendJson(['success' => false, 'message' => 'Distribution record not found.']);
    }
}

// -------------------------------------------------------------------------
// ACTION: DELETE DRUG DISTRIBUTION (TAB 2)
// -------------------------------------------------------------------------
if ($action === 'delete_drug_distribution') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        sendJson(['success' => false, 'message' => 'Invalid distribution ID.']);
    }

    $mysqli->begin_transaction();
    try {
        $d_t = $mysqli->prepare("DELETE FROM mobile_clinic_treatments WHERE distribution_id = ?");
        if ($d_t) {
            $d_t->bind_param("i", $id);
            $d_t->execute();
            $d_t->close();
        }

        $d_main = $mysqli->prepare("DELETE FROM mobile_clinic_drug_distributions WHERE id = ? AND range_id = ?");
        if ($d_main) {
            $d_main->bind_param("ii", $id, $range_id);
            $d_main->execute();
            $d_main->close();
        }

        $mysqli->commit();
        sendJson(['success' => true, 'message' => 'Drug distribution and linked treatments deleted successfully.']);
    } catch (Throwable $e) {
        $mysqli->rollback();
        sendJson(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

sendJson(['success' => false, 'message' => 'Unsupported action requested.']);
