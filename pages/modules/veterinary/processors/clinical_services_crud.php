<?php
/**
 * pages/modules/veterinary/processors/clinical_services_crud.php
 * Backend AJAX & Form processor for Clinical Services OPD Registers
 * Handles dual-workflow intake (Small Animal vs. Large Animal),
 * clinical diagnostic records, and automated counter file & cash book synchronization.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/db_connect.php';
require_once __DIR__ . '/clinical_db_migration.php';
require_once __DIR__ . '/../../../../includes/counterfoil_module_helper.php';

/** @var mysqli $mysqli */
global $mysqli;
ensure_clinical_opd_tables($mysqli);

header('Content-Type: application/json; charset=utf-8');

// Role Clearance Guard
$allowed_roles = [
    'veterinary_surgeon',
    'government_veterinary_surgeon',
    'additional_veterinary_surgeon',
    'deputy_director_hq_1',
    'district_dd',
    'deputy_director_district',
    'provincial_director',
    'admin',
    'super_admin',
    'administrator',
    'sms',
    'planning_officer'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized clearance profile.']);
    exit();
}

$action = trim($_REQUEST['action'] ?? '');
$raw_user_id = intval($_SESSION['user_id'] ?? 0);
$user_id = null;
if ($raw_user_id > 0) {
    $u_chk = $mysqli->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
    if ($u_chk) {
        $u_chk->bind_param("i", $raw_user_id);
        $u_chk->execute();
        if ($u_chk->get_result()->fetch_assoc()) {
            $user_id = $raw_user_id;
        }
        $u_chk->close();
    }
}

function sendJsonResponse(array $payload) {
    echo json_encode($payload);
    exit();
}

// -----------------------------------------------------------------------------
// 1. ACTION: LOOKUP FARMER (Auto-Population for Large Animals)
// -----------------------------------------------------------------------------
if ($action === 'lookup_farmer') {
    $query = trim($_REQUEST['farm_reg_no'] ?? $_REQUEST['query'] ?? '');
    if (empty($query)) {
        sendJsonResponse(['success' => false, 'message' => 'Farm Registration Number or NIC is required.']);
    }

    $clean_query = preg_replace('/[^0-9a-zA-Z]/', '', $query);
    $found = false;
    $farmer = null;

    // Search farm_registration_renewals (Master Registry)
    $stmt = $mysqli->prepare("
        SELECT id, range_id, district_id, farmer_name, farmer_address, registration_no, telephone_no, nic,
               total_neat_cattle, total_buffaloes
        FROM farm_registration_renewals
        WHERE (
            LOWER(TRIM(registration_no)) = LOWER(?)
            OR LOWER(REPLACE(REPLACE(COALESCE(registration_no, ''), ' ', ''), '-', '')) = LOWER(?)
            OR LOWER(TRIM(nic)) = LOWER(?)
            OR LOWER(REPLACE(REPLACE(COALESCE(nic, ''), ' ', ''), '-', '')) = LOWER(?)
            OR (registration_no IS NOT NULL AND registration_no != '' AND registration_no LIKE CONCAT('%', ?, '%'))
        )
        ORDER BY (LOWER(TRIM(registration_no)) = LOWER(?)) DESC, id DESC
        LIMIT 1
    ");

    if ($stmt) {
        $stmt->bind_param("ssssss", $query, $clean_query, $query, $clean_query, $clean_query, $query);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($r = $res->fetch_assoc()) {
            $found = true;
            $neat = intval($r['total_neat_cattle'] ?? 0);
            $buff = intval($r['total_buffaloes'] ?? 0);
            $farmer = [
                'farm_reg_no'   => $r['registration_no'] ?: $query,
                'farmer_name'   => $r['farmer_name'] ?: '',
                'address'       => $r['farmer_address'] ?: '',
                'phone'         => $r['telephone_no'] ?: '',
                'nic'           => $r['nic'] ?: '',
                'range_id'      => intval($r['range_id'] ?? 0),
                'district_id'   => intval($r['district_id'] ?? 0),
                'herd_population' => max(1, $neat + $buff),
                'source'        => 'Master Registry (farm_registration_renewals)'
            ];
        }
        $stmt->close();
    }

    // Fallback: search farmers table
    if (!$found) {
        $stmt2 = $mysqli->prepare("
            SELECT id, range_id, district_id, full_name, address, farm_reg_no, phone, nic
            FROM farmers
            WHERE (
                LOWER(TRIM(farm_reg_no)) = LOWER(?)
                OR LOWER(REPLACE(REPLACE(COALESCE(farm_reg_no, ''), ' ', ''), '-', '')) = LOWER(?)
                OR LOWER(TRIM(nic)) = LOWER(?)
                OR LOWER(REPLACE(REPLACE(COALESCE(nic, ''), ' ', ''), '-', '')) = LOWER(?)
                OR (farm_reg_no IS NOT NULL AND farm_reg_no != '' AND farm_reg_no LIKE CONCAT('%', ?, '%'))
            )
            ORDER BY (LOWER(TRIM(farm_reg_no)) = LOWER(?)) DESC, id DESC
            LIMIT 1
        ");

        if ($stmt2) {
            $stmt2->bind_param("ssssss", $query, $clean_query, $query, $clean_query, $clean_query, $query);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            if ($r2 = $res2->fetch_assoc()) {
                $found = true;
                $farmer = [
                    'farm_reg_no'   => $r2['farm_reg_no'] ?: $query,
                    'farmer_name'   => $r2['full_name'] ?: '',
                    'address'       => $r2['address'] ?: '',
                    'phone'         => $r2['phone'] ?: '',
                    'nic'           => $r2['nic'] ?: '',
                    'range_id'      => intval($r2['range_id'] ?? 0),
                    'district_id'   => intval($r2['district_id'] ?? 0),
                    'herd_population' => 1,
                    'source'        => 'Farmers Master Table'
                ];
            }
            $stmt2->close();
        }
    }

    if ($found && $farmer) {
        sendJsonResponse([
            'success' => true,
            'message' => 'Farmer record auto-fetched from registry successfully.',
            'data'    => $farmer
        ]);
    } else {
        sendJsonResponse([
            'success' => false,
            'message' => "No registration found matching '{$query}'. You can enter details manually."
        ]);
    }
}

// -----------------------------------------------------------------------------
// 2. ACTION: GET SINGLE OPD RECORD (for View/Edit)
// -----------------------------------------------------------------------------
if ($action === 'get_opd_record') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid record ID.']);
    }

    $stmt = $mysqli->prepare("SELECT * FROM clinical_opd_records WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($data) {
        sendJsonResponse(['success' => true, 'data' => $data]);
    } else {
        sendJsonResponse(['success' => false, 'message' => 'Record not found.']);
    }
}

// -----------------------------------------------------------------------------
// 3. ACTION: SAVE OPD RECORD (Create / Update + Counterfoil Sync)
// -----------------------------------------------------------------------------
if ($action === 'save_opd_record') {
    $id = intval($_POST['id'] ?? 0);
    $clinic_type = trim($_POST['clinic_type'] ?? 'large_animal');
    if (!in_array($clinic_type, ['large_animal', 'small_animal'], true)) {
        $clinic_type = 'large_animal';
    }

    $entry_date   = !empty($_POST['entry_date']) ? trim($_POST['entry_date']) : date('Y-m-d');
    $range_id     = !empty($_POST['range_id']) ? intval($_POST['range_id']) : ($_SESSION['range_id'] ?? 1);
    $district_id  = !empty($_POST['district_id']) ? intval($_POST['district_id']) : ($_SESSION['district_id'] ?? 1);

    // Registration Identifiers
    $registration_no = trim($_POST['registration_no'] ?? '');
    $farm_reg_no     = trim($_POST['farm_reg_no'] ?? '');
    if ($clinic_type === 'large_animal' && empty($farm_reg_no)) {
        $farm_reg_no = $registration_no;
    }
    if ($clinic_type === 'large_animal' && empty($registration_no)) {
        $registration_no = $farm_reg_no;
    }

    $owner_name    = trim($_POST['owner_name'] ?? '');
    $owner_address = trim($_POST['owner_address'] ?? '');
    $owner_phone   = trim($_POST['owner_phone'] ?? '');
    $owner_nic     = trim($_POST['owner_nic'] ?? '');

    // Clinical Columns from Physical Register
    $herd_total_population    = intval($_POST['herd_total_population'] ?? 1);
    if ($herd_total_population < 1) $herd_total_population = 1;

    $species                   = trim($_POST['species'] ?? '');
    $breed                     = trim($_POST['breed'] ?? '');
    $age_years                 = trim($_POST['age_years'] ?? '');
    $sex                       = trim($_POST['sex'] ?? 'Female');
    if (!in_array($sex, ['Male', 'Female', 'Unknown'], true)) {
        $sex = 'Female';
    }
    $coat_colour               = trim($_POST['coat_colour'] ?? '');
    $special_marks             = trim($_POST['special_marks'] ?? '');
    $clinically_affected_count = intval($_POST['clinically_affected_count'] ?? 1);
    if ($clinically_affected_count < 1) $clinically_affected_count = 1;

    $death_count               = intval($_POST['death_count'] ?? 0);
    if ($death_count < 0) $death_count = 0;

    $tentative_diagnosis       = trim($_POST['tentative_diagnosis'] ?? '');
    $action_taken              = trim($_POST['action_taken'] ?? '');

    // Revenue Collection Breakdown (Rs.)
    $revenue_consultation      = floatval($_POST['revenue_consultation'] ?? 0.00);
    $revenue_drugs             = floatval($_POST['revenue_drugs'] ?? 0.00);
    $revenue_others            = floatval($_POST['revenue_others'] ?? 0.00);
    $revenue_total             = round($revenue_consultation + $revenue_drugs + $revenue_others, 2);

    // Receipt Tracking (Mandatory per specification)
    $cash_receipt_no           = trim($_POST['cash_receipt_no'] ?? '');
    $remarks                   = trim($_POST['remarks'] ?? '');

    // Validations
    if (empty($registration_no)) {
        $reg_label = ($clinic_type === 'large_animal') ? 'Farm Registration Number' : 'Animal Registration Number';
        sendJsonResponse(['success' => false, 'message' => "The {$reg_label} is mandatory."]);
    }

    if (empty($owner_name)) {
        $owner_label = ($clinic_type === 'large_animal') ? 'Name of Farmer' : 'Name of Pet Owner';
        sendJsonResponse(['success' => false, 'message' => "The {$owner_label} is required."]);
    }

    if (empty($species)) {
        sendJsonResponse(['success' => false, 'message' => 'Animal Species is required.']);
    }

    if (empty($tentative_diagnosis)) {
        sendJsonResponse(['success' => false, 'message' => 'Tentative Diagnosis is required.']);
    }

    if (empty($action_taken)) {
        sendJsonResponse(['success' => false, 'message' => 'Action Taken is required.']);
    }

    if (empty($cash_receipt_no)) {
        sendJsonResponse(['success' => false, 'message' => 'Cash Receipt No. is mandatory for register record tracking and counterfoil integration.']);
    }

    // Begin Database Transaction for OPD Record + Counter File Linkage
    $mysqli->begin_transaction();

    try {
        $leaf_issue_id = null;

        // ---------------------------------------------------------------------
        // STEP A: Counterfoil Book & Leaf Issue Backend Integration
        // ---------------------------------------------------------------------
        // 1. Resolve or Ensure parent counterfoil book in counterfoil_assets
        $cf_book_id = 0;
        $cf_book_stmt = $mysqli->prepare("
            SELECT id FROM counterfoil_assets 
            WHERE counterfoil_type = 'Cash Receipt Book' 
              AND (range_id = ? OR range_id IS NULL OR range_id = 0)
              AND is_active = 1
            ORDER BY (range_id = ?) DESC, id DESC 
            LIMIT 1
        ");
        if ($cf_book_stmt) {
            $cf_book_stmt->bind_param("ii", $range_id, $range_id);
            $cf_book_stmt->execute();
            $cf_res = $cf_book_stmt->get_result();
            if ($c_row = $cf_res->fetch_assoc()) {
                $cf_book_id = intval($c_row['id']);
            }
            $cf_book_stmt->close();
        }

        // If no active Cash Receipt Book exists in counterfoil_assets for this range, create one
        if ($cf_book_id === 0) {
            $default_book_serial = 'CRB-R' . $range_id . '-' . date('Y');
            $auto_book_stmt = $mysqli->prepare("
                INSERT INTO counterfoil_assets 
                (district_id, range_id, counterfoil_type, book_serial_no, page_count, available_quantity, initial_count, is_active, remarks) 
                VALUES (?, ?, 'Cash Receipt Book', ?, '100', 100, 100, 1, 'Auto-provisioned for Clinical Services OPD Revenue Receipts')
            ");
            if ($auto_book_stmt) {
                $auto_book_stmt->bind_param("iis", $district_id, $range_id, $default_book_serial);
                $auto_book_stmt->execute();
                $cf_book_id = $auto_book_stmt->insert_id;
                $auto_book_stmt->close();
            }
        }

        // 2. Check if a leaf issue already exists for this cash receipt number
        $leaf_chk_stmt = $mysqli->prepare("
            SELECT id FROM counterfoil_leaf_issues 
            WHERE leaf_serial_no = ? AND counterfoil_type = 'Cash Receipt Book'
            LIMIT 1
        ");
        $existing_leaf_id = 0;
        if ($leaf_chk_stmt) {
            $leaf_chk_stmt->bind_param("s", $cash_receipt_no);
            $leaf_chk_stmt->execute();
            $leaf_res = $leaf_chk_stmt->get_result();
            if ($l_row = $leaf_res->fetch_assoc()) {
                $existing_leaf_id = intval($l_row['id']);
            }
            $leaf_chk_stmt->close();
        }

        $client_type = ($clinic_type === 'large_animal') ? 'farmer' : 'pet_owner';
        $animal_summary_str = "Species: {$species}" . ($breed ? " ({$breed})" : "") . " | Affected: {$clinically_affected_count}";
        $cf_purpose = "Clinical OPD ({$clinic_type}): {$tentative_diagnosis} [Consult: Rs. " . number_format($revenue_consultation, 2) . ", Drugs: Rs. " . number_format($revenue_drugs, 2) . ", Others: Rs. " . number_format($revenue_others, 2) . "]";
        $revenue_item = "Consultation fee for cross breed Pet's"; // canonical mapping item
        $category_tab = "tab-consultations";
        $cf_remarks = "Synced from Clinical Services OPD Register. Receipt #{$cash_receipt_no}. " . $remarks;

        if ($existing_leaf_id > 0) {
            // Update existing leaf
            $upd_leaf = $mysqli->prepare("
                UPDATE counterfoil_leaf_issues SET 
                    district_id = ?, range_id = ?, client_type = ?, farmer_nic = ?, farmer_name = ?,
                    farm_registration_no = ?, location_address = ?, animal_counts_summary = ?,
                    issue_date = ?, purpose = ?, amount = ?, unit_price = ?, amount_deposited = ?,
                    revenue_item = ?, category_tab = ?, remarks = ?, issued_by = ?
                WHERE id = ?
            ");
            if ($upd_leaf) {
                $upd_leaf->bind_param(
                    "iissssssssdddsssii",
                    $district_id, $range_id, $client_type, $owner_nic, $owner_name,
                    $registration_no, $owner_address, $animal_summary_str,
                    $entry_date, $cf_purpose, $revenue_total, $revenue_total, $revenue_total,
                    $revenue_item, $category_tab, $cf_remarks, $user_id,
                    $existing_leaf_id
                );
                $upd_leaf->execute();
                $upd_leaf->close();
            }
            $leaf_issue_id = $existing_leaf_id;
        } else {
            // Insert new counterfoil leaf issue
            $ins_leaf = $mysqli->prepare("
                INSERT INTO counterfoil_leaf_issues 
                (counterfoil_id, district_id, range_id, counterfoil_type, leaf_serial_no, client_type, farmer_nic, farmer_name, farm_registration_no, location_address, animal_counts_summary, issue_date, purpose, amount, quantity, unit_price, amount_deposited, revenue_item, category_tab, remarks, issued_by) 
                VALUES (?, ?, ?, 'Cash Receipt Book', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?)
            ");
            if ($ins_leaf) {
                $ins_leaf->bind_param(
                    "iiisssssssssdddsssi",
                    $cf_book_id, $district_id, $range_id, $cash_receipt_no, $client_type,
                    $owner_nic, $owner_name, $registration_no, $owner_address,
                    $animal_summary_str, $entry_date, $cf_purpose,
                    $revenue_total, $revenue_total, $revenue_total,
                    $revenue_item, $category_tab, $cf_remarks, $user_id
                );
                $ins_leaf->execute();
                $leaf_issue_id = $ins_leaf->insert_id;
                $ins_leaf->close();
            }
        }

        // 3. Update or Insert into cash_book_summaries (Overarching Cash Book)
        $report_year  = intval(date('Y', strtotime($entry_date)));
        $report_month = intval(date('n', strtotime($entry_date)));
        $cb_item_title = "OPD Clinical (" . ($clinic_type === 'large_animal' ? 'Large Animal' : 'Small Animal') . ") - " . $tentative_diagnosis;

        $cb_chk = $mysqli->prepare("SELECT id FROM cash_book_summaries WHERE receipt_no = ? AND range_id = ? LIMIT 1");
        $existing_cb_id = 0;
        if ($cb_chk) {
            $cb_chk->bind_param("si", $cash_receipt_no, $range_id);
            $cb_chk->execute();
            $cb_res = $cb_chk->get_result();
            if ($cb_row = $cb_res->fetch_assoc()) {
                $existing_cb_id = intval($cb_row['id']);
            }
            $cb_chk->close();
        }

        if ($existing_cb_id > 0) {
            $upd_cb = $mysqli->prepare("
                UPDATE cash_book_summaries SET 
                    report_year = ?, report_month = ?, receipt_date = ?, client_name = ?, item_name = ?,
                    quantity_sold = 1, unit_price = ?, total_amount = ?, amount_deposited = ?,
                    leaf_issue_id = ?, created_by = ?
                WHERE id = ?
            ");
            if ($upd_cb) {
                $upd_cb->bind_param("iisssdddiii", $report_year, $report_month, $entry_date, $owner_name, $cb_item_title, $revenue_total, $revenue_total, $revenue_total, $leaf_issue_id, $user_id, $existing_cb_id);
                $upd_cb->execute();
                $upd_cb->close();
            }
        } else {
            $ins_cb = $mysqli->prepare("
                INSERT INTO cash_book_summaries 
                (district_id, range_id, report_year, report_month, receipt_no, receipt_date, client_name, item_name, quantity_sold, unit_price, total_amount, amount_deposited, leaf_issue_id, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?)
            ");
            if ($ins_cb) {
                $ins_cb->bind_param("iiiissssdddii", $district_id, $range_id, $report_year, $report_month, $cash_receipt_no, $entry_date, $owner_name, $cb_item_title, $revenue_total, $revenue_total, $revenue_total, $leaf_issue_id, $user_id);
                $ins_cb->execute();
                $ins_cb->close();
            }
        }

        // ---------------------------------------------------------------------
        // STEP B: Insert or Update clinical_opd_records table
        // ---------------------------------------------------------------------
        if ($id > 0) {
            $stmt = $mysqli->prepare("
                UPDATE clinical_opd_records SET 
                    clinic_type = ?, entry_date = ?, range_id = ?, district_id = ?,
                    registration_no = ?, farm_reg_no = ?, owner_name = ?, owner_address = ?,
                    owner_phone = ?, owner_nic = ?, herd_total_population = ?, species = ?,
                    breed = ?, age_years = ?, sex = ?, coat_colour = ?, special_marks = ?,
                    clinically_affected_count = ?, death_count = ?, tentative_diagnosis = ?,
                    action_taken = ?, revenue_consultation = ?, revenue_drugs = ?,
                    revenue_others = ?, revenue_total = ?, cash_receipt_no = ?,
                    leaf_issue_id = ?, remarks = ?
                WHERE id = ?
            ");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $mysqli->error);
            }
            $stmt->bind_param(
                "ssiissssssissssssiissddddsisi",
                $clinic_type, $entry_date, $range_id, $district_id,
                $registration_no, $farm_reg_no, $owner_name, $owner_address,
                $owner_phone, $owner_nic, $herd_total_population, $species,
                $breed, $age_years, $sex, $coat_colour, $special_marks,
                $clinically_affected_count, $death_count, $tentative_diagnosis,
                $action_taken, $revenue_consultation, $revenue_drugs,
                $revenue_others, $revenue_total, $cash_receipt_no,
                $leaf_issue_id, $remarks, $id
            );
            $stmt->execute();
            $stmt->close();
            $record_id = $id;
            $msg = "Clinical OPD Record #{$record_id} updated and synchronized with Cash Receipt #{$cash_receipt_no}.";
        } else {
            $stmt = $mysqli->prepare("
                INSERT INTO clinical_opd_records (
                    clinic_type, entry_date, range_id, district_id,
                    registration_no, farm_reg_no, owner_name, owner_address,
                    owner_phone, owner_nic, herd_total_population, species,
                    breed, age_years, sex, coat_colour, special_marks,
                    clinically_affected_count, death_count, tentative_diagnosis,
                    action_taken, revenue_consultation, revenue_drugs,
                    revenue_others, revenue_total, cash_receipt_no,
                    leaf_issue_id, remarks, created_by
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?
                )
            ");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $mysqli->error);
            }
            $stmt->bind_param(
                "ssiissssssissssssiissddddsisi",
                $clinic_type, $entry_date, $range_id, $district_id,
                $registration_no, $farm_reg_no, $owner_name, $owner_address,
                $owner_phone, $owner_nic, $herd_total_population, $species,
                $breed, $age_years, $sex, $coat_colour, $special_marks,
                $clinically_affected_count, $death_count, $tentative_diagnosis,
                $action_taken, $revenue_consultation, $revenue_drugs,
                $revenue_others, $revenue_total, $cash_receipt_no,
                $leaf_issue_id, $remarks, $user_id
            );
            $stmt->execute();
            $record_id = $stmt->insert_id;
            $stmt->close();
            $msg = "New Clinical OPD Record #{$record_id} saved and linked to Counterfoil Leaf & Cash Book (#{$cash_receipt_no}).";
        }

        $mysqli->commit();

        sendJsonResponse([
            'success'   => true,
            'message'   => $msg,
            'id'        => $record_id,
            'receipt_no'=> $cash_receipt_no,
            'leaf_id'   => $leaf_issue_id,
            'total_revenue' => $revenue_total
        ]);

    } catch (Exception $e) {
        $mysqli->rollback();
        sendJsonResponse([
            'success' => false,
            'message' => 'Failed to save OPD record: ' . $e->getMessage()
        ]);
    }
}

// -----------------------------------------------------------------------------
// 4. ACTION: DELETE OPD RECORD
// -----------------------------------------------------------------------------
if ($action === 'delete_opd_record') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid record ID.']);
    }

    $mysqli->begin_transaction();
    try {
        // Fetch receipt and leaf details before deletion
        $stmt_f = $mysqli->prepare("SELECT cash_receipt_no, leaf_issue_id, range_id FROM clinical_opd_records WHERE id = ?");
        $stmt_f->bind_param("i", $id);
        $stmt_f->execute();
        $record = $stmt_f->get_result()->fetch_assoc();
        $stmt_f->close();

        if (!$record) {
            throw new Exception("Record not found.");
        }

        $receipt_no = $record['cash_receipt_no'];
        $leaf_id    = intval($record['leaf_issue_id'] ?? 0);
        $rng_id     = intval($record['range_id'] ?? 0);

        // Delete from clinical_opd_records
        $del_stmt = $mysqli->prepare("DELETE FROM clinical_opd_records WHERE id = ?");
        $del_stmt->bind_param("i", $id);
        $del_stmt->execute();
        $del_stmt->close();

        // Clean up linked counterfoil leaf and cash book summary
        if ($leaf_id > 0) {
            $del_leaf = $mysqli->prepare("DELETE FROM counterfoil_leaf_issues WHERE id = ?");
            if ($del_leaf) {
                $del_leaf->bind_param("i", $leaf_id);
                $del_leaf->execute();
                $del_leaf->close();
            }

            $del_cb = $mysqli->prepare("DELETE FROM cash_book_summaries WHERE leaf_issue_id = ?");
            if ($del_cb) {
                $del_cb->bind_param("i", $leaf_id);
                $del_cb->execute();
                $del_cb->close();
            }
        } elseif (!empty($receipt_no)) {
            $del_cb2 = $mysqli->prepare("DELETE FROM cash_book_summaries WHERE receipt_no = ? AND range_id = ?");
            if ($del_cb2) {
                $del_cb2->bind_param("si", $receipt_no, $rng_id);
                $del_cb2->execute();
                $del_cb2->close();
            }
        }

        $mysqli->commit();
        sendJsonResponse([
            'success' => true,
            'message' => "Clinical OPD Record #{$id} and associated counterfoil entries removed successfully."
        ]);

    } catch (Exception $e) {
        $mysqli->rollback();
        sendJsonResponse(['success' => false, 'message' => 'Deletion error: ' . $e->getMessage()]);
    }
}

// Fallback unknown action
sendJsonResponse(['success' => false, 'message' => 'Unknown action requested.']);
