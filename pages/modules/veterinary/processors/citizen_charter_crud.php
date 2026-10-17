<?php
/**
 * pages/modules/veterinary/processors/citizen_charter_crud.php
 * Backend AJAX Processor for Citizen Charter Non-Clinical Requests Register
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../../config/db_connect.php';
require_once __DIR__ . '/dairy_db_migration.php';

/** @var mysqli $mysqli */
global $mysqli;
ensure_dairy_project_tables($mysqli);

function jsonRes($data) {
    echo json_encode($data);
    exit();
}

// 1. Session and Auth Guard
if (!isset($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    jsonRes(['success' => false, 'message' => 'Unauthorized. Please login to continue.']);
}

$user_id   = intval($_SESSION['user_id'] ?? 0);
$user_role = $_SESSION['role'] ?? '';

$action = trim($_POST['action'] ?? $_GET['action'] ?? '');

// -----------------------------------------------------------------------------
// 2. ACTION: LOOKUP BENEFICIARY (DUAL-TRIGGER NIC / FARM REG NO AUTO-POPULATION)
// -----------------------------------------------------------------------------
if ($action === 'lookup_beneficiary') {
    $nic         = trim($_POST['nic'] ?? $_GET['nic'] ?? '');
    $farm_reg_no = trim($_POST['farm_reg_no'] ?? $_GET['farm_reg_no'] ?? '');
    $query       = trim($_POST['query'] ?? $_GET['query'] ?? '');

    $search_term = !empty($query) ? $query : (!empty($nic) ? $nic : $farm_reg_no);
    if (empty($search_term)) {
        jsonRes(['success' => false, 'message' => 'Please provide an NIC or Farm Registration Number to search.']);
    }

    $found = null;

    // A. Check Dairy Project Beneficiaries
    $stmt1 = $mysqli->prepare("
        SELECT farm_reg_no, beneficiary_name AS name, nic, phone, address, 'Dairy Project Master' AS source 
        FROM dairy_project_beneficiaries 
        WHERE nic = ? OR farm_reg_no = ? 
        ORDER BY id DESC LIMIT 1
    ");
    if ($stmt1) {
        $stmt1->bind_param("ss", $search_term, $search_term);
        $stmt1->execute();
        $r1 = $stmt1->get_result()->fetch_assoc();
        if ($r1) $found = $r1;
        $stmt1->close();
    }

    // B. Check Dairy Farm Applications
    if (!$found) {
        $stmt2 = $mysqli->prepare("
            SELECT farm_reg_no, producer_name AS name, nic_no AS nic, contact_mobile AS phone, address, 'Dairy Application Survey' AS source 
            FROM dairy_farm_applications 
            WHERE nic_no = ? OR farm_reg_no = ? 
            ORDER BY id DESC LIMIT 1
        ");
        if ($stmt2) {
            $stmt2->bind_param("ss", $search_term, $search_term);
            $stmt2->execute();
            $r2 = $stmt2->get_result()->fetch_assoc();
            if ($r2) $found = $r2;
            $stmt2->close();
        }
    }

    // C. Check Clinical OPD Records
    if (!$found) {
        $stmt3 = $mysqli->prepare("
            SELECT farm_reg_no, owner_name AS name, owner_nic AS nic, owner_phone AS phone, owner_address AS address, 'Clinical Services OPD Registry' AS source 
            FROM clinical_opd_records 
            WHERE owner_nic = ? OR farm_reg_no = ? OR registration_no = ? 
            ORDER BY id DESC LIMIT 1
        ");
        if ($stmt3) {
            $stmt3->bind_param("sss", $search_term, $search_term, $search_term);
            $stmt3->execute();
            $r3 = $stmt3->get_result()->fetch_assoc();
            if ($r3) $found = $r3;
            $stmt3->close();
        }
    }

    // D. Check Farmer Registrations Master Table
    if (!$found) {
        $chk_table = $mysqli->query("SHOW TABLES LIKE 'farmer_registrations'");
        if ($chk_table && $chk_table->num_rows > 0) {
            $stmt4 = $mysqli->prepare("
                SELECT farm_registration_no AS farm_reg_no, full_name AS name, nic, contact_number AS phone, permanent_address AS address, 'Master Farmer Registry' AS source 
                FROM farmer_registrations 
                WHERE nic = ? OR farm_registration_no = ? 
                ORDER BY id DESC LIMIT 1
            ");
            if ($stmt4) {
                $stmt4->bind_param("ss", $search_term, $search_term);
                $stmt4->execute();
                $r4 = $stmt4->get_result()->fetch_assoc();
                if ($r4) $found = $r4;
                $stmt4->close();
            }
        }
    }

    if ($found) {
        jsonRes([
            'success' => true,
            'data'    => $found,
            'message' => 'Beneficiary profile matched from ' . $found['source']
        ]);
    } else {
        jsonRes([
            'success' => false,
            'message' => 'No prior registry profile found for query. Please enter beneficiary particulars manually.'
        ]);
    }
}

// -----------------------------------------------------------------------------
// 3. ACTION: SAVE CITIZEN CHARTER REQUEST (INSERT / UPDATE)
// -----------------------------------------------------------------------------
if ($action === 'save_request') {
    $id = intval($_POST['id'] ?? 0);
    $range_id = intval($_POST['range_id'] ?? ($_SESSION['range_id'] ?? 1));
    $district_id = intval($_POST['district_id'] ?? ($_SESSION['district_id'] ?? 1));

    $request_date    = !empty($_POST['request_date']) ? trim($_POST['request_date']) : date('Y-m-d');
    $request_origin  = trim($_POST['request_origin'] ?? 'Direct Visit');
    if (!in_array($request_origin, ['Direct Visit', 'By Call'], true)) {
        $request_origin = 'Direct Visit';
    }

    $beneficiary_name       = trim($_POST['beneficiary_name'] ?? '');
    $nic_no                 = trim($_POST['nic_no'] ?? '');
    $phone_no               = trim($_POST['phone_no'] ?? '');
    $address                = trim($_POST['address'] ?? '');
    $farm_reg_no            = trim($_POST['farm_reg_no'] ?? '');
    $purpose_of_visit       = trim($_POST['purpose_of_visit'] ?? '');
    $immediate_action_taken = trim($_POST['immediate_action_taken'] ?? '');
    $final_remarks          = trim($_POST['final_remarks'] ?? '');
    $status                 = trim($_POST['status'] ?? 'Completed');

    if (!in_array($status, ['Completed', 'Pending', 'In Progress', 'Referred'], true)) {
        $status = 'Completed';
    }

    // Validation
    if (empty($beneficiary_name)) {
        jsonRes(['success' => false, 'message' => 'Beneficiary / Farmer Name is required.']);
    }
    if (empty($purpose_of_visit)) {
        jsonRes(['success' => false, 'message' => 'Purpose of Visit is required.']);
    }
    if (empty($immediate_action_taken)) {
        jsonRes(['success' => false, 'message' => 'Immediate Action Taken is required.']);
    }

    if ($id > 0) {
        // UPDATE existing record
        $stmt = $mysqli->prepare("
            UPDATE citizen_charter_requests SET
                request_date = ?,
                request_origin = ?,
                beneficiary_name = ?,
                nic_no = ?,
                phone_no = ?,
                address = ?,
                farm_reg_no = ?,
                purpose_of_visit = ?,
                immediate_action_taken = ?,
                final_remarks = ?,
                status = ?
            WHERE id = ?
        ");
        if (!$stmt) {
            jsonRes(['success' => false, 'message' => 'Database prepare failed: ' . $mysqli->error]);
        }
        $stmt->bind_param(
            "sssssssssssi",
            $request_date,
            $request_origin,
            $beneficiary_name,
            $nic_no,
            $phone_no,
            $address,
            $farm_reg_no,
            $purpose_of_visit,
            $immediate_action_taken,
            $final_remarks,
            $status,
            $id
        );
        $res = $stmt->execute();
        $stmt->close();

        if ($res) {
            jsonRes([
                'success' => true,
                'id'      => $id,
                'message' => 'Citizen Charter request updated successfully.'
            ]);
        } else {
            jsonRes(['success' => false, 'message' => 'Failed to update request: ' . $mysqli->error]);
        }
    } else {
        // INSERT new record
        // Generate clean Reference No: CCR-YYYY-RANGE-XXXX
        $year_str = date('Y', strtotime($request_date));
        $count_q = $mysqli->query("SELECT COUNT(*) AS cnt FROM citizen_charter_requests WHERE YEAR(request_date) = '{$year_str}'");
        $next_num = 1;
        if ($count_q && $row = $count_q->fetch_assoc()) {
            $next_num = intval($row['cnt']) + 1;
        }
        $reference_no = sprintf("CCR-%s-%02d-%04d", $year_str, $range_id, $next_num);

        $stmt = $mysqli->prepare("
            INSERT INTO citizen_charter_requests (
                range_id,
                district_id,
                request_date,
                reference_no,
                request_origin,
                beneficiary_name,
                nic_no,
                phone_no,
                address,
                farm_reg_no,
                purpose_of_visit,
                immediate_action_taken,
                final_remarks,
                status,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            jsonRes(['success' => false, 'message' => 'Database prepare failed: ' . $mysqli->error]);
        }
        $stmt->bind_param(
            "iissssssssssssi",
            $range_id,
            $district_id,
            $request_date,
            $reference_no,
            $request_origin,
            $beneficiary_name,
            $nic_no,
            $phone_no,
            $address,
            $farm_reg_no,
            $purpose_of_visit,
            $immediate_action_taken,
            $final_remarks,
            $status,
            $user_id
        );
        $res = $stmt->execute();
        $new_id = $stmt->insert_id;
        $stmt->close();

        if ($res) {
            jsonRes([
                'success'      => true,
                'id'           => $new_id,
                'reference_no' => $reference_no,
                'message'      => 'Citizen Charter non-clinical request logged successfully!'
            ]);
        } else {
            jsonRes(['success' => false, 'message' => 'Failed to save request: ' . $mysqli->error]);
        }
    }
}

// -----------------------------------------------------------------------------
// 4. ACTION: GET SINGLE REQUEST DETAILS
// -----------------------------------------------------------------------------
if ($action === 'get_request') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonRes(['success' => false, 'message' => 'Invalid Request ID.']);
    }

    $stmt = $mysqli->prepare("
        SELECT c.*, r.name AS range_name, d.name AS district_name 
        FROM citizen_charter_requests c 
        LEFT JOIN veterinary_ranges r ON c.range_id = r.id 
        LEFT JOIN districts d ON c.district_id = d.id 
        WHERE c.id = ? LIMIT 1
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $rec = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($rec) {
        jsonRes(['success' => true, 'data' => $rec]);
    } else {
        jsonRes(['success' => false, 'message' => 'Record not found.']);
    }
}

// -----------------------------------------------------------------------------
// 5. ACTION: DELETE REQUEST
// -----------------------------------------------------------------------------
if ($action === 'delete_request') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        jsonRes(['success' => false, 'message' => 'Invalid Request ID.']);
    }

    $stmt = $mysqli->prepare("DELETE FROM citizen_charter_requests WHERE id = ?");
    $stmt->bind_param("i", $id);
    $res = $stmt->execute();
    $stmt->close();

    if ($res) {
        jsonRes(['success' => true, 'message' => 'Citizen Charter request removed successfully.']);
    } else {
        jsonRes(['success' => false, 'message' => 'Failed to delete record: ' . $mysqli->error]);
    }
}

jsonRes(['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($action)]);
