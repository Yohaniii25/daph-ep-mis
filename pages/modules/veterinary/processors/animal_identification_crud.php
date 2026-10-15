<?php
session_start();
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
$VALID_STATUSES = ['Sold or transferred', 'Slaughter', 'Died'];

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

// -------------------------------------------------------------------------
// ACTION: GET NEXT SEQUENCE & PREVIEW AUTO-GENERATED TAG NUMBER
// -------------------------------------------------------------------------
if ($action === 'get_next_seq') {
    $farm_reg_no = trim($_GET['farm_reg_no'] ?? '');
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

    // Auto-generation concatenation: [Range]-[FarmReg]-[Sequence]
    $clean_farm_reg = !empty($farm_reg_no) ? preg_replace('/[^A-Za-z0-9_-]/', '', $farm_reg_no) : 'FARM';
    $auto_tag = $range_code . '-' . $clean_farm_reg . '-' . $next_seq_formatted;
    $suggested_voucher = 'A-' . str_pad((string)mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);

    echo json_encode([
        'success' => true,
        'range_code' => $range_code,
        'next_seq' => $next_seq_formatted,
        'next_seq_num' => $next_seq_num,
        'auto_tag_no' => $auto_tag,
        'suggested_voucher' => $suggested_voucher
    ]);
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
if ($action === 'update_status') {
    $id = (int)($_POST['id'] ?? 0);
    $new_status = trim($_POST['status'] ?? $_POST['present_status'] ?? '');

    if ($id <= 0 || !in_array($new_status, $VALID_STATUSES, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID or status value. Must be Sold or transferred, Slaughter, or Died.']);
        exit();
    }

    $status_date = date('Y-m-d');
    $stmt = $mysqli->prepare("UPDATE animal_identifications SET present_status = ?, status_date = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $new_status, $status_date, $id);
    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Present status successfully updated to ' . $new_status . '.']);
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

    $stmt = $mysqli->prepare("DELETE FROM animal_identifications WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $stmt->close();
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

            // Auto-generate Ear Tag No concatenation
            $ear_tag_no = trim($_POST['ear_tag_no'] ?? $first_animal['ear_tag_no'] ?? '');
            if (empty($ear_tag_no)) {
                $ear_tag_no = $range_code . '-' . preg_replace('/[^A-Za-z0-9_-]/', '', $farm_reg_no) . '-' . $animal_seq_no;
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

            $mysqli->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Animal Identification record updated successfully.',
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
                
                // Ear Tag Auto-Generation
                $tag_no = trim($an['ear_tag_no'] ?? '');
                if (empty($tag_no)) {
                    $clean_freg = preg_replace('/[^A-Za-z0-9_-]/', '', $farm_reg_no);
                    $tag_no = $range_code . '-' . $clean_freg . '-' . $seq_no;
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
                $inserted_count++;
            }
            $ins->close();

            $mysqli->commit();
            echo json_encode([
                'success' => true,
                'message' => "Successfully recorded {$inserted_count} identified animal(s) into the program registry.",
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

            $mysqli->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Animal Identification record successfully registered.',
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

echo json_encode(['success' => false, 'message' => 'Unsupported action requested.']);
exit();
