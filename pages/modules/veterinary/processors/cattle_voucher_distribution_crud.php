<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

header('Content-Type: application/json; charset=UTF-8');

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

/**
 * Format sequential voucher serial with prefix and zero-padding
 */
function formatSeqVoucher($prefix, $num) {
    $clean_prefix = rtrim(trim($prefix), '-') . '-';
    if ($num >= 100000) {
        return $clean_prefix . $num;
    }
    return $clean_prefix . str_pad((string)$num, 6, '0', STR_PAD_LEFT);
}

/**
 * Extract integer serial from voucher string (e.g. A-918059 -> 918059, A-000059 -> 59)
 */
function extractVoucherNumber($str) {
    $num = preg_replace('/\D/', '', (string)$str);
    return $num !== '' ? (int)$num : null;
}

// -------------------------------------------------------------------------
// ACTION: CREATE / ASSIGN SEQUENTIAL BLOCK TO FARMER
// -------------------------------------------------------------------------
if ($action === 'create_distribution') {
    $farmer_name = trim($_POST['farmer_name'] ?? '');
    $farm_reg_no = trim($_POST['farm_reg_no'] ?? '');
    $farmer_nic = trim($_POST['farmer_nic'] ?? '');
    $range_id = (int)($_POST['range_id'] ?? $session_range_id ?: 1);
    $district_id = (int)($_POST['district_id'] ?? 1);
    $prefix = trim($_POST['prefix'] ?? 'A-');
    if (empty($prefix)) {
        $prefix = 'A-';
    }
    if (substr($prefix, -1) !== '-') {
        $prefix .= '-';
    }

    $start_serial = (int)($_POST['start_serial'] ?? 0);
    $end_serial = (int)($_POST['end_serial'] ?? 0);
    $distribution_date = trim($_POST['distribution_date'] ?? date('Y-m-d'));
    $remarks = trim($_POST['remarks'] ?? '');

    if (empty($farmer_name) || empty($farm_reg_no)) {
        echo json_encode(['success' => false, 'message' => 'Farmer Name and Farm Registration Number are required.']);
        exit();
    }

    if ($start_serial <= 0 || $end_serial < $start_serial) {
        echo json_encode(['success' => false, 'message' => 'Invalid serial range. End serial must be greater than or equal to start serial (both > 0).']);
        exit();
    }

    $total_allocated = ($end_serial - $start_serial) + 1;

    // Check for overlap within the same Range & Prefix
    $chk_stmt = $mysqli->prepare("
        SELECT id, farmer_name, farm_reg_no, start_serial, end_serial 
        FROM cattle_voucher_distributions 
        WHERE range_id = ? AND prefix = ? 
          AND NOT (end_serial < ? OR start_serial > ?)
        LIMIT 1
    ");
    if ($chk_stmt) {
        $chk_stmt->bind_param("isii", $range_id, $prefix, $start_serial, $end_serial);
        $chk_stmt->execute();
        $ov_res = $chk_stmt->get_result();
        if ($ov = $ov_res->fetch_assoc()) {
            $ov_start = formatSeqVoucher($prefix, $ov['start_serial']);
            $ov_end = formatSeqVoucher($prefix, $ov['end_serial']);
            echo json_encode([
                'success' => false,
                'message' => "Serial range overlaps with an existing allocation: {$ov['farmer_name']} ({$ov['farm_reg_no']}) has serials {$ov_start} to {$ov_end}."
            ]);
            $chk_stmt->close();
            exit();
        }
        $chk_stmt->close();
    }

    // Insert new distribution
    $ins_stmt = $mysqli->prepare("
        INSERT INTO cattle_voucher_distributions 
        (district_id, range_id, farmer_name, farm_reg_no, farmer_nic, prefix, start_serial, end_serial, total_allocated, distribution_date, remarks, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$ins_stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $mysqli->error]);
        exit();
    }
    $ins_stmt->bind_param("iissssiiissi", 
        $district_id, $range_id, $farmer_name, $farm_reg_no, $farmer_nic,
        $prefix, $start_serial, $end_serial, $total_allocated, $distribution_date, $remarks, $user_id
    );

    if ($ins_stmt->execute()) {
        $new_id = $ins_stmt->insert_id;
        $ins_stmt->close();
        $formatted_range = formatSeqVoucher($prefix, $start_serial) . " to " . formatSeqVoucher($prefix, $end_serial);
        echo json_encode([
            'success' => true,
            'message' => "Successfully allocated {$total_allocated} vouchers ({$formatted_range}) to {$farmer_name}.",
            'id' => $new_id
        ]);
    } else {
        $err = $ins_stmt->error;
        $ins_stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to save distribution: ' . $err]);
    }
    exit();
}

// -------------------------------------------------------------------------
// ACTION: LIST DISTRIBUTIONS WITH DYNAMIC BALANCE CALCULATIONS
// -------------------------------------------------------------------------
if ($action === 'list_distributions') {
    $filter_range_id = (int)($_GET['range_id'] ?? $session_range_id ?: 1);

    // Fetch all animal identification records for this range to calculate live usage
    $used_vouchers = [];
    $damaged_vouchers = [];
    $ai_res = $mysqli->query("
        SELECT voucher_serial_no, ear_tag_no, present_status, farm_reg_no, identification_date 
        FROM animal_identifications 
        WHERE (range_id = {$filter_range_id} OR range_id IS NULL OR range_id = 0)
          AND voucher_serial_no IS NOT NULL AND voucher_serial_no != ''
    ");
    if ($ai_res) {
        while ($row = $ai_res->fetch_assoc()) {
            $num = extractVoucherNumber($row['voucher_serial_no']);
            if ($num !== null) {
                $used_vouchers[$num] = $row;
                if ($row['present_status'] === 'Tag Damaged / Removed') {
                    $damaged_vouchers[$num] = $row;
                }
            }
        }
    }

    $sql = "
        SELECT d.*, vr.name AS range_name, dis.name AS district_name 
        FROM cattle_voucher_distributions d
        LEFT JOIN veterinary_ranges vr ON d.range_id = vr.id
        LEFT JOIN districts dis ON d.district_id = dis.id
        WHERE d.range_id = ?
        ORDER BY d.distribution_date DESC, d.id DESC
    ";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $filter_range_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $distributions = [];
    while ($row = $res->fetch_assoc()) {
        $s = (int)$row['start_serial'];
        $e = (int)$row['end_serial'];
        $prefix = $row['prefix'];
        $total = (int)$row['total_allocated'];

        $used_in_block = 0;
        $damaged_in_block = 0;
        $next_available = null;

        for ($curr = $s; $curr <= $e; $curr++) {
            if (isset($used_vouchers[$curr])) {
                $used_in_block++;
                if (isset($damaged_vouchers[$curr])) {
                    $damaged_in_block++;
                }
            } else {
                if ($next_available === null) {
                    $next_available = formatSeqVoucher($prefix, $curr);
                }
            }
        }

        $remaining = $total - $used_in_block;
        $pct_used = $total > 0 ? round(($used_in_block / $total) * 100, 1) : 0;

        $distributions[] = [
            'id' => (int)$row['id'],
            'farmer_name' => $row['farmer_name'],
            'farm_reg_no' => $row['farm_reg_no'],
            'farmer_nic' => $row['farmer_nic'] ?: '—',
            'prefix' => $prefix,
            'start_serial' => $s,
            'end_serial' => $e,
            'total_allocated' => $total,
            'formatted_range' => formatSeqVoucher($prefix, $s) . " – " . formatSeqVoucher($prefix, $e),
            'used_count' => $used_in_block,
            'damaged_count' => $damaged_in_block,
            'remaining_balance' => $remaining,
            'next_available' => $next_available ?: 'Depleted (0 left)',
            'percent_used' => $pct_used,
            'distribution_date' => $row['distribution_date'],
            'remarks' => $row['remarks'] ?: ''
        ];
    }
    $stmt->close();

    echo json_encode(['success' => true, 'distributions' => $distributions]);
    exit();
}

// -------------------------------------------------------------------------
// ACTION: GET ACTIVE BLOCK FOR SPECIFIC FARMER (FOR ANIMAL ENTRY MODAL)
// -------------------------------------------------------------------------
if ($action === 'get_farmer_block') {
    $farm_reg_no = trim($_GET['farm_reg_no'] ?? '');
    $farmer_name = trim($_GET['farmer_name'] ?? '');
    $range_id = (int)($_GET['range_id'] ?? $session_range_id ?: 1);

    if (empty($farm_reg_no) && empty($farmer_name)) {
        echo json_encode(['success' => false, 'has_block' => false]);
        exit();
    }

    // Pull used vouchers
    $used_vouchers = [];
    $ai_res = $mysqli->query("
        SELECT voucher_serial_no, present_status 
        FROM animal_identifications 
        WHERE (range_id = {$range_id} OR range_id IS NULL OR range_id = 0)
          AND voucher_serial_no IS NOT NULL AND voucher_serial_no != ''
    ");
    if ($ai_res) {
        while ($row = $ai_res->fetch_assoc()) {
            $num = extractVoucherNumber($row['voucher_serial_no']);
            if ($num !== null) {
                $used_vouchers[$num] = true;
            }
        }
    }

    $stmt = $mysqli->prepare("
        SELECT * FROM cattle_voucher_distributions 
        WHERE (farm_reg_no = ? OR farmer_name = ?) AND range_id = ? 
        ORDER BY id ASC
    ");
    $stmt->bind_param("ssi", $farm_reg_no, $farmer_name, $range_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $found_block = null;
    while ($row = $res->fetch_assoc()) {
        $s = (int)$row['start_serial'];
        $e = (int)$row['end_serial'];
        $prefix = $row['prefix'];
        $total = (int)$row['total_allocated'];

        $used_in_block = 0;
        $next_available = null;

        for ($curr = $s; $curr <= $e; $curr++) {
            if (isset($used_vouchers[$curr])) {
                $used_in_block++;
            } else {
                if ($next_available === null) {
                    $next_available = formatSeqVoucher($prefix, $curr);
                }
            }
        }

        $remaining = $total - $used_in_block;
        if ($remaining > 0 || $found_block === null) {
            $found_block = [
                'has_block' => true,
                'block_id' => (int)$row['id'],
                'farmer_name' => $row['farmer_name'],
                'farm_reg_no' => $row['farm_reg_no'],
                'prefix' => $prefix,
                'start_serial' => $s,
                'end_serial' => $e,
                'total_allocated' => $total,
                'used_count' => $used_in_block,
                'remaining_balance' => $remaining,
                'next_available' => $next_available ?: '',
                'formatted_block' => formatSeqVoucher($prefix, $s) . " – " . formatSeqVoucher($prefix, $e)
            ];
            if ($remaining > 0) {
                break; // Found active available block
            }
        }
    }
    $stmt->close();

    if ($found_block) {
        $resp = array_merge(['success' => true], $found_block);
        $resp['block'] = [
            'total_allocated' => $found_block['total_allocated'],
            'remaining_qty' => $found_block['remaining_balance'],
            'next_voucher_serial' => $found_block['next_available'],
            'formatted_range' => $found_block['formatted_block']
        ];
        echo json_encode($resp);
    } else {
        echo json_encode(['success' => true, 'has_block' => false, 'block' => null]);
    }
    exit();
}

// -------------------------------------------------------------------------
// ACTION: DELETE DISTRIBUTION BLOCK (ONLY IF UNCONSUMED)
// -------------------------------------------------------------------------
if ($action === 'delete_distribution') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid distribution block ID.']);
        exit();
    }

    $stmt = $mysqli->prepare("SELECT * FROM cattle_voucher_distributions WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $block = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$block) {
        echo json_encode(['success' => false, 'message' => 'Distribution block not found.']);
        exit();
    }

    $s = (int)$block['start_serial'];
    $e = (int)$block['end_serial'];
    $prefix = $block['prefix'];

    // Check if any vouchers in this block have been consumed
    $used_count = 0;
    $ai_res = $mysqli->query("
        SELECT voucher_serial_no FROM animal_identifications 
        WHERE voucher_serial_no IS NOT NULL AND voucher_serial_no != ''
    ");
    if ($ai_res) {
        while ($row = $ai_res->fetch_assoc()) {
            $num = extractVoucherNumber($row['voucher_serial_no']);
            if ($num !== null && $num >= $s && $num <= $e) {
                $used_count++;
            }
        }
    }

    if ($used_count > 0) {
        echo json_encode([
            'success' => false,
            'message' => "Cannot delete allocation block: {$used_count} voucher(s) have already been registered/consumed or marked damaged from this block."
        ]);
        exit();
    }

    $del = $mysqli->prepare("DELETE FROM cattle_voucher_distributions WHERE id = ?");
    $del->bind_param("i", $id);
    if ($del->execute()) {
        $del->close();
        echo json_encode(['success' => true, 'message' => 'Sequential distribution block deleted successfully.']);
    } else {
        $err = $del->error;
        $del->close();
        echo json_encode(['success' => false, 'message' => 'Failed to delete block: ' . $err]);
    }
    exit();
}

// -------------------------------------------------------------------------
// ACTION: GET DETAILED LEAF BREAKDOWN OF A BLOCK
// -------------------------------------------------------------------------
if ($action === 'get_block_details') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        exit();
    }

    $stmt = $mysqli->prepare("SELECT * FROM cattle_voucher_distributions WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $block = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$block) {
        echo json_encode(['success' => false, 'message' => 'Block not found.']);
        exit();
    }

    $s = (int)$block['start_serial'];
    $e = (int)$block['end_serial'];
    $prefix = $block['prefix'];

    // Map consumed vouchers
    $records_map = [];
    $ai_res = $mysqli->query("
        SELECT id, voucher_serial_no, ear_tag_no, identification_date, species, breed, gender, present_status, remarks 
        FROM animal_identifications 
        WHERE voucher_serial_no IS NOT NULL AND voucher_serial_no != ''
    ");
    if ($ai_res) {
        while ($row = $ai_res->fetch_assoc()) {
            $num = extractVoucherNumber($row['voucher_serial_no']);
            if ($num !== null && $num >= $s && $num <= $e) {
                $records_map[$num] = $row;
            }
        }
    }

    $leaf_items = [];
    for ($curr = $s; $curr <= $e; $curr++) {
        $formatted = formatSeqVoucher($prefix, $curr);
        if (isset($records_map[$curr])) {
            $r = $records_map[$curr];
            $is_damaged = ($r['present_status'] === 'Tag Damaged / Removed');
            $leaf_items[] = [
                'serial_num' => $curr,
                'voucher_no' => $formatted,
                'status' => $is_damaged ? 'Damaged Tag (Permanently Retained)' : 'Issued / Used',
                'status_type' => $is_damaged ? 'damaged' : 'used',
                'ear_tag_no' => $r['ear_tag_no'],
                'animal_desc' => "{$r['gender']} {$r['species']} ({$r['breed']})",
                'issue_date' => $r['identification_date'],
                'record_id' => $r['id']
            ];
        } else {
            $leaf_items[] = [
                'serial_num' => $curr,
                'voucher_no' => $formatted,
                'status' => 'Available',
                'status_type' => 'available',
                'ear_tag_no' => '—',
                'animal_desc' => '—',
                'issue_date' => '—',
                'record_id' => 0
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'block' => [
            'id' => $block['id'],
            'farmer_name' => $block['farmer_name'],
            'farm_reg_no' => $block['farm_reg_no'],
            'farmer_nic' => $block['farmer_nic'] ?: '—',
            'formatted_range' => formatSeqVoucher($prefix, $s) . " – " . formatSeqVoucher($prefix, $e),
            'total_allocated' => (int)$block['total_allocated'],
            'distribution_date' => $block['distribution_date']
        ],
        'leaf_items' => $leaf_items
    ]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
exit();
