<?php
/**
 * pages/modules/veterinary/processors/request_transfer.php
 * Handles employee transfer requests initiated by Veterinary Surgeons
 * Dispatches automated notifications to the Provincial Admin Branch
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../../config/db_connect.php';
require_once '../../../../includes/notification_helper.php';

header('Content-Type: application/json');

$vs_roles = ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $vs_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Only Veterinary Surgeons can request transfers.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$employee_id = filter_input(INPUT_POST, 'employee_id', FILTER_VALIDATE_INT);
$target_unit = trim($_POST['target_unit'] ?? '');
$reason      = trim($_POST['reason'] ?? '');

if (!$employee_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing employee identifier.']);
    exit();
}

if (empty($target_unit)) {
    echo json_encode(['success' => false, 'message' => 'Please select a valid target unit or office.']);
    exit();
}

if (empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a justification or reason for the transfer request.']);
    exit();
}

// Security Check: Verify that this employee belongs to the Veterinary Surgeon's assigned district and range
$vs_range_id    = $_SESSION['range_id'] ?? null;
$vs_district_id = $_SESSION['district_id'] ?? null;
$vs_name        = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Veterinary Surgeon';

$stmt_emp = $mysqli->prepare("
    SELECT u.id, u.full_name, u.service_number, u.emp_id, u.designation, u.role, u.range_id, u.district_id, u.unit, u.current_station,
           vr.name AS range_name, d.name AS district_name
    FROM users u
    LEFT JOIN veterinary_ranges vr ON u.range_id = vr.id
    LEFT JOIN districts d ON u.district_id = d.id
    WHERE u.id = ? AND u.district_id = ? AND u.range_id = ? AND u.is_active = 1
    LIMIT 1
");

if (!$stmt_emp) {
    echo json_encode(['success' => false, 'message' => 'Database error preparing verification query.']);
    exit();
}

$stmt_emp->bind_param("iii", $employee_id, $vs_district_id, $vs_range_id);
$stmt_emp->execute();
$emp_res = $stmt_emp->get_result();

if ($emp_res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Employee not found in your assigned veterinary range or you do not have permission to request transfers for this officer.']);
    $stmt_emp->close();
    exit();
}

$employee = $emp_res->fetch_assoc();
$stmt_emp->close();

$emp_name    = $employee['full_name'];
$emp_service = !empty($employee['service_number']) ? $employee['service_number'] : ($employee['emp_id'] ?? '');
$source_range = !empty($employee['range_name']) ? $employee['range_name'] . ' Range' : ($_SESSION['range_name'] ?? 'Assigned Range');

if (!function_exists('parse_transfer_target_unit')) {
    /**
     * Resolve target unit string into structured DB foreign key columns
     */
    function parse_transfer_target_unit($mysqli, $target_unit_str) {
        $result = [
            'target_unit' => $target_unit_str,
            'target_range_id' => null,
            'target_district_id' => null,
            'target_farm_id' => null,
            'target_training_center_id' => null,
            'target_district' => 'Provincial'
        ];

        $trimmed = trim($target_unit_str);

        // 1. Check Range Office
        if (stripos($trimmed, 'Range Office - ') === 0) {
            $raw_name = substr($trimmed, strlen('Range Office - '));
            $clean_name = trim(preg_replace('/\s*\(.*?\)\s*/', '', $raw_name));
            $stmt = $mysqli->prepare("SELECT id, district_id FROM veterinary_ranges WHERE name LIKE ? OR name = ? LIMIT 1");
            if ($stmt) {
                $like_param = "%{$clean_name}%";
                $stmt->bind_param("ss", $like_param, $clean_name);
                $stmt->execute();
                $r = $stmt->get_result()->fetch_assoc();
                if ($r) {
                    $result['target_range_id'] = intval($r['id']);
                    $result['target_district_id'] = intval($r['district_id']);
                    $d_res = $mysqli->query("SELECT name FROM districts WHERE id = " . intval($r['district_id']));
                    if ($d_res && $d_row = $d_res->fetch_assoc()) {
                        $result['target_district'] = $d_row['name'];
                    }
                }
                $stmt->close();
            }
        }
        // 2. Check District Office
        elseif (stripos($trimmed, 'District Office - ') === 0) {
            $dist_name = trim(substr($trimmed, strlen('District Office - ')));
            $stmt = $mysqli->prepare("SELECT id, name FROM districts WHERE name LIKE ? OR name = ? LIMIT 1");
            if ($stmt) {
                $like_param = "%{$dist_name}%";
                $stmt->bind_param("ss", $like_param, $dist_name);
                $stmt->execute();
                $r = $stmt->get_result()->fetch_assoc();
                if ($r) {
                    $result['target_district_id'] = intval($r['id']);
                    $result['target_district'] = $r['name'];
                }
                $stmt->close();
            }
        }
        // 3. Check Regional Farm
        elseif (stripos($trimmed, 'Regional Farm - ') === 0) {
            $farm_name = trim(substr($trimmed, strlen('Regional Farm - ')));
            $stmt = $mysqli->prepare("SELECT id FROM regional_farms WHERE farm_name LIKE ? OR farm_name = ? LIMIT 1");
            if ($stmt) {
                $like_param = "%{$farm_name}%";
                $stmt->bind_param("ss", $like_param, $farm_name);
                $stmt->execute();
                $r = $stmt->get_result()->fetch_assoc();
                if ($r) {
                    $result['target_farm_id'] = intval($r['id']);
                }
                $stmt->close();
            }
        }
        // 4. Check Training Center
        elseif (stripos($trimmed, 'Training Center - ') === 0) {
            $raw_tc = trim(substr($trimmed, strlen('Training Center - ')));
            $clean_tc = trim(preg_replace('/\s*\(.*?\)\s*/', '', $raw_tc));
            $stmt = $mysqli->prepare("SELECT id FROM training_centers WHERE center_name LIKE ? OR center_name = ? LIMIT 1");
            if ($stmt) {
                $like_param = "%{$clean_tc}%";
                $stmt->bind_param("ss", $like_param, $clean_tc);
                $stmt->execute();
                $r = $stmt->get_result()->fetch_assoc();
                if ($r) {
                    $result['target_training_center_id'] = intval($r['id']);
                }
                $stmt->close();
            }
        }

        return $result;
    }
}

$target_meta = parse_transfer_target_unit($mysqli, $target_unit);

// Stage into pending_approvals table for maker-checker review by the Administrator
$old_data = [
    'unit'             => !empty($employee['unit']) ? $employee['unit'] : $source_range,
    'current_station'  => !empty($employee['current_station']) ? $employee['current_station'] : (!empty($employee['unit']) ? $employee['unit'] : $source_range),
    'range_id'         => $employee['range_id'],
    'district_id'      => $employee['district_id'],
    'full_name'        => $employee['full_name'],
    'emp_id'           => $employee['emp_id'] ?? '',
    'service_number'   => $employee['service_number'] ?? '',
    'designation'      => $employee['designation'] ?? '',
    'current_location' => $source_range
];

$new_data = array_merge([
    'target_unit' => $target_unit,
    'reason'      => $reason
], $target_meta);

$old_json     = json_encode($old_data, JSON_UNESCAPED_UNICODE);
$new_json     = json_encode($new_data, JSON_UNESCAPED_UNICODE);
$module       = 'hr';
$record_type  = 'transfer_request';
$vs_user_id   = intval($_SESSION['user_id'] ?? 0);
$vs_user_role = $_SESSION['role'] ?? 'veterinary_surgeon';
$emp_dist_id  = intval($employee['district_id'] ?? 0);
$emp_range_id = intval($employee['range_id'] ?? 0);

$ins_approval = $mysqli->prepare("
    INSERT INTO pending_approvals 
    (module, record_type, record_id, target_name, requested_by, requester_name, requester_role, district_id, range_id, old_data, new_data, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
");

if ($ins_approval) {
    $ins_approval->bind_param(
        "ssisisiiiss",
        $module,
        $record_type,
        $employee_id,
        $emp_name,
        $vs_user_id,
        $vs_name,
        $vs_user_role,
        $emp_dist_id,
        $emp_range_id,
        $old_json,
        $new_json
    );
    $ins_approval->execute();
    $approval_id = $ins_approval->insert_id;
    $ins_approval->close();
}

// Dispatch automated transfer notification to the Provincial Admin Branch
// (Administrator, Provincial Director, Deputy Director H/Q-1, Deputy Director H/Q-2)
$notify_result = dispatch_transfer_request_notification(
    $mysqli,
    $employee_id,
    $emp_name,
    $emp_service,
    $source_range,
    $target_unit,
    $reason,
    $vs_name
);

if ($notify_result['success'] || !empty($approval_id)) {
    echo json_encode([
        'success' => true,
        'message' => "Transfer request for {$emp_name} to [{$target_unit}] has been successfully routed to the Provincial Admin Branch for official processing.",
        'details' => [
            'employee'           => $emp_name,
            'target_unit'        => $target_unit,
            'approval_id'        => $approval_id ?? null,
            'notifications_sent' => $notify_result['sent_count'] ?? 0
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to dispatch notification to the Provincial Admin Branch. Please contact administration.'
    ]);
}
