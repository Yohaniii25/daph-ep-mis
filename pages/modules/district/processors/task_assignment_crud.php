<?php
// pages/modules/district/processors/task_assignment_crud.php
// Backend CRUD and AJAX endpoint for District Deputy Director task assignments.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$allowed_roles = ['district_dd', 'deputy_director_district', 'administrator', 'provincial_director'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

require_once __DIR__ . '/../../../../config/db_connect.php';
require_once __DIR__ . '/../../../../auth/audit_helper.php';
require_once __DIR__ . '/db_migration.php';

ensure_quick_action_assignments_table($mysqli);

// Resolve District context
$district_id = $_SESSION['district_id'] ?? null;
$district_name = $_SESSION['district'] ?? '';

if (empty($district_id) && !empty($district_name)) {
    if (strcasecmp($district_name, 'Amparai') === 0 || strcasecmp($district_name, 'Ampara') === 0) {
        $district_id = 1;
    } elseif (strcasecmp($district_name, 'Batticaloa') === 0) {
        $district_id = 2;
    } elseif (strcasecmp($district_name, 'Trincomalee') === 0) {
        $district_id = 3;
    }
}
if (empty($district_id)) {
    $district_id = 1; // Default fallback for preview/testing
}

// Fetch official district name
$dist_stmt = $mysqli->prepare("SELECT name FROM districts WHERE id = ? LIMIT 1");
if ($dist_stmt) {
    $dist_stmt->bind_param("i", $district_id);
    $dist_stmt->execute();
    $dist_res = $dist_stmt->get_result();
    if ($row = $dist_res->fetch_assoc()) {
        $district_name = $row['name'];
    }
    $dist_stmt->close();
}

$valid_actions = [
    'range_statistics',
    'annual_targets',
    'monthly_annual_reports',
    'regulatory_functions',
    'animal_health',
    'clinical_services',
    'animal_breeding',
    'livestock_production',
    'dairy_hub',
    'projects',
    'monitoring',
    'accounts',
    'clean_sri_lanka',
    'trainings'
];

$tc_district_mapping = [
    'uppuveli'      => 3,
    'uppuweli'      => 3,
    'kallady'       => 2,
    'kanchirankuda' => 1
];

$farm_district_mapping = [
    'uppuveli'     => 3,
    'kantalai'     => 3,
    'morawewa'     => 3,
    'mandoor'      => 2,
    'sathurukonda' => 2,
    'thumpankerny' => 2,
    'thirukkovil'  => 1
];

$action = trim($_REQUEST['action'] ?? '');

// Helper to verify user belongs to this District DD's jurisdiction
function verify_user_district_jurisdiction($mysqli, $target_user_id, $district_id, $district_name, $tc_map, $farm_map) {
    $query = "
        SELECT u.id, u.role, u.district_id, u.district, u.range_id, u.training_center_id, u.farm_id,
               vr.district_id AS range_district_id,
               tc.location AS tc_location,
               rf.location AS farm_location
        FROM users u
        LEFT JOIN veterinary_ranges vr ON u.range_id = vr.id
        LEFT JOIN training_centers tc ON u.training_center_id = tc.id
        LEFT JOIN regional_farms rf ON u.farm_id = rf.id
        WHERE u.id = ? AND u.is_active = 1
        LIMIT 1
    ";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) return false;
    $stmt->bind_param("i", $target_user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) return false;

    // Direct district ID or name match
    if (!empty($user['district_id']) && (int)$user['district_id'] === (int)$district_id) return true;
    if (!empty($user['range_district_id']) && (int)$user['range_district_id'] === (int)$district_id) return true;
    if (!empty($user['district']) && (strcasecmp($user['district'], $district_name) === 0 || strcasecmp($user['district'], $district_name . 'i') === 0)) return true;

    // Training center location match
    if (!empty($user['tc_location'])) {
        $loc = strtolower(trim($user['tc_location']));
        if (isset($tc_map[$loc]) && $tc_map[$loc] === (int)$district_id) return true;
    }

    // Regional farm location match
    if (!empty($user['farm_location'])) {
        $loc = strtolower(trim($user['farm_location']));
        if (isset($farm_map[$loc]) && $farm_map[$loc] === (int)$district_id) return true;
    }

    // For SMS with provincial scope, allow District DD to assign range quick actions if needed
    if ($user['role'] === 'sms') {
        return true;
    }

    return false;
}

// -------------------------------------------------------------
// ACTION: fetch_officers
// -------------------------------------------------------------
if ($action === 'fetch_officers') {
    $category = trim($_GET['category'] ?? '');
    $sub_role = trim($_GET['sub_role'] ?? '');

    $officers = [];

    if ($category === 'range_veterinary_officer') {
        $role_filter_sql = "";
        if ($sub_role === 'government_veterinary_surgeon') {
            $role_filter_sql = "u.role IN ('government_veterinary_surgeon', 'veterinary_surgeon')";
        } elseif (!empty($sub_role)) {
            $safe_role = $mysqli->real_escape_string($sub_role);
            if ($safe_role === 'department_laborer') {
                $role_filter_sql = "u.role IN ('department_laborer', 'employee')";
            } else {
                $role_filter_sql = "u.role = '$safe_role'";
            }
        } else {
            $role_filter_sql = "u.role IN ('government_veterinary_surgeon', 'veterinary_surgeon', 'additional_veterinary_surgeon', 'livestock_development_officer', 'development_officer', 'driver', 'dispensary_assistant', 'department_laborer', 'night_watcher', 'employee')";
        }

        $sql = "
            SELECT u.id, u.username, u.full_name, u.role, u.designation,
                   vr.name AS workstation_name,
                   (SELECT COUNT(*) FROM user_quick_action_assignments a WHERE a.user_id = u.id) AS assigned_count
            FROM users u
            LEFT JOIN veterinary_ranges vr ON u.range_id = vr.id
            WHERE u.is_active = 1
              AND ($role_filter_sql)
              AND (
                  vr.district_id = $district_id 
                  OR u.district_id = $district_id 
                  OR u.district = '$district_name' 
                  OR u.district = '{$district_name}i'
              )
            ORDER BY vr.name ASC, u.full_name ASC
        ";

        $res = $mysqli->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $officers[] = [
                    'id' => (int)$row['id'],
                    'username' => $row['username'],
                    'full_name' => $row['full_name'],
                    'role' => $row['role'],
                    'designation' => $row['designation'] ?: ucwords(str_replace('_', ' ', $row['role'])),
                    'workstation' => $row['workstation_name'] ? "Range: " . $row['workstation_name'] : "District Office",
                    'assigned_count' => (int)$row['assigned_count']
                ];
            }
        }
    } elseif ($category === 'subject_matter_specialist') {
        $sql = "
            SELECT u.id, u.username, u.full_name, u.role, u.designation,
                   (SELECT COUNT(*) FROM user_quick_action_assignments a WHERE a.user_id = u.id) AS assigned_count
            FROM users u
            WHERE u.is_active = 1 AND u.role = 'sms'
              AND (
                  u.district_id = $district_id 
                  OR u.district = '$district_name' 
                  OR u.district = '{$district_name}i' 
                  OR u.district = 'Provincial' 
                  OR u.district_id IS NULL
              )
            ORDER BY u.full_name ASC
        ";
        $res = $mysqli->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $officers[] = [
                    'id' => (int)$row['id'],
                    'username' => $row['username'],
                    'full_name' => $row['full_name'],
                    'role' => $row['role'],
                    'designation' => $row['designation'] ?: 'Subject Matter Specialist',
                    'workstation' => 'District SMS Unit (' . $district_name . ')',
                    'assigned_count' => (int)$row['assigned_count']
                ];
            }
        }
    } elseif ($category === 'training_centers') {
        $sql = "
            SELECT u.id, u.username, u.full_name, u.role, u.designation,
                   tc.center_name, tc.location,
                   (SELECT COUNT(*) FROM user_quick_action_assignments a WHERE a.user_id = u.id) AS assigned_count
            FROM users u
            LEFT JOIN training_centers tc ON u.training_center_id = tc.id
            WHERE u.is_active = 1 AND u.role = 'training_officer'
            ORDER BY tc.center_name ASC, u.full_name ASC
        ";
        $res = $mysqli->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $loc = strtolower(trim($row['location'] ?? ''));
                $tc_dist = $tc_district_mapping[$loc] ?? 0;
                if ($tc_dist === (int)$district_id || $row['location'] === $district_name) {
                    $officers[] = [
                        'id' => (int)$row['id'],
                        'username' => $row['username'],
                        'full_name' => $row['full_name'],
                        'role' => $row['role'],
                        'designation' => $row['designation'] ?: 'Training Officer',
                        'workstation' => ($row['center_name'] ?: 'Training Center') . ($row['location'] ? " ({$row['location']})" : ""),
                        'assigned_count' => (int)$row['assigned_count']
                    ];
                }
            }
        }
    } elseif ($category === 'regional_farms') {
        $sql = "
            SELECT u.id, u.username, u.full_name, u.role, u.designation,
                   rf.farm_name, rf.location,
                   (SELECT COUNT(*) FROM user_quick_action_assignments a WHERE a.user_id = u.id) AS assigned_count
            FROM users u
            LEFT JOIN regional_farms rf ON u.farm_id = rf.id
            WHERE u.is_active = 1 AND u.role = 'farms_dd'
            ORDER BY rf.farm_name ASC, u.full_name ASC
        ";
        $res = $mysqli->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $loc = strtolower(trim($row['location'] ?? ''));
                $f_dist = $farm_district_mapping[$loc] ?? 0;
                if ($f_dist === (int)$district_id) {
                    $officers[] = [
                        'id' => (int)$row['id'],
                        'username' => $row['username'],
                        'full_name' => $row['full_name'],
                        'role' => $row['role'],
                        'designation' => $row['designation'] ?: 'Deputy Director (Farms Operation)',
                        'workstation' => ($row['farm_name'] ?: 'Regional Farm') . ($row['location'] ? " ({$row['location']})" : ""),
                        'assigned_count' => (int)$row['assigned_count']
                    ];
                }
            }
        }
    }

    echo json_encode([
        'status' => 'success',
        'district_id' => $district_id,
        'district_name' => $district_name,
        'officers' => $officers
    ]);
    exit();
}

// -------------------------------------------------------------
// ACTION: fetch_officer_assignments
// -------------------------------------------------------------
if ($action === 'fetch_officer_assignments') {
    $target_user_id = intval($_GET['user_id'] ?? 0);
    if ($target_user_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid officer selected.']);
        exit();
    }

    if (!verify_user_district_jurisdiction($mysqli, $target_user_id, $district_id, $district_name, $tc_district_mapping, $farm_district_mapping)) {
        echo json_encode(['status' => 'error', 'message' => 'Officer does not belong to your district jurisdiction.']);
        exit();
    }

    $assigned_actions = [];
    $stmt = $mysqli->prepare("SELECT action_id FROM user_quick_action_assignments WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $target_user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $assigned_actions[] = $row['action_id'];
        }
        $stmt->close();
    }

    echo json_encode([
        'status' => 'success',
        'user_id' => $target_user_id,
        'assignments' => $assigned_actions
    ]);
    exit();
}

// -------------------------------------------------------------
// ACTION: save_assignments
// -------------------------------------------------------------
if ($action === 'save_assignments') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
        exit();
    }

    $target_user_id = intval($_POST['user_id'] ?? 0);
    $selected_actions = $_POST['action_ids'] ?? [];

    if ($target_user_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Please select a valid officer.']);
        exit();
    }

    if (!is_array($selected_actions)) {
        $selected_actions = [];
    }

    // Filter to only valid quick actions
    $filtered_actions = array_values(array_intersect($selected_actions, $valid_actions));

    if (!verify_user_district_jurisdiction($mysqli, $target_user_id, $district_id, $district_name, $tc_district_mapping, $farm_district_mapping)) {
        echo json_encode(['status' => 'error', 'message' => 'Officer does not belong to your district jurisdiction.']);
        exit();
    }

    $assigned_by = (int)$_SESSION['user_id'];

    // Retrieve previous assignments for audit logging
    $old_assignments = [];
    $old_stmt = $mysqli->prepare("SELECT action_id FROM user_quick_action_assignments WHERE user_id = ?");
    if ($old_stmt) {
        $old_stmt->bind_param("i", $target_user_id);
        $old_stmt->execute();
        $old_res = $old_stmt->get_result();
        while ($r = $old_res->fetch_assoc()) {
            $old_assignments[] = $r['action_id'];
        }
        $old_stmt->close();
    }

    $mysqli->begin_transaction();
    try {
        // Delete current assignments
        $del_stmt = $mysqli->prepare("DELETE FROM user_quick_action_assignments WHERE user_id = ?");
        $del_stmt->bind_param("i", $target_user_id);
        $del_stmt->execute();
        $del_stmt->close();

        // Insert new assignments
        if (!empty($filtered_actions)) {
            $ins_stmt = $mysqli->prepare("INSERT INTO user_quick_action_assignments (user_id, action_id, assigned_by) VALUES (?, ?, ?)");
            foreach ($filtered_actions as $act_id) {
                $ins_stmt->bind_param("isi", $target_user_id, $act_id, $assigned_by);
                $ins_stmt->execute();
            }
            $ins_stmt->close();
        }

        $mysqli->commit();

        // Record audit activity
        if (function_exists('logActivity')) {
            logActivity(
                $mysqli,
                $assigned_by,
                'UPDATE_QUICK_ACTION_ASSIGNMENTS',
                'user_quick_action_assignments',
                $target_user_id,
                $old_assignments,
                $filtered_actions,
                "District DD updated task assignments for Officer ID $target_user_id (" . count($filtered_actions) . " actions assigned)"
            );
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Task assignments updated successfully for the selected officer.',
            'assigned_count' => count($filtered_actions),
            'action_ids' => $filtered_actions
        ]);
        exit();
    } catch (Exception $e) {
        $mysqli->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
}

// -------------------------------------------------------------
// ACTION: revoke_assignments
// -------------------------------------------------------------
if ($action === 'revoke_assignments') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
        exit();
    }

    $target_user_id = intval($_POST['user_id'] ?? 0);
    if ($target_user_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid officer specified.']);
        exit();
    }

    if (!verify_user_district_jurisdiction($mysqli, $target_user_id, $district_id, $district_name, $tc_district_mapping, $farm_district_mapping)) {
        echo json_encode(['status' => 'error', 'message' => 'Officer does not belong to your district jurisdiction.']);
        exit();
    }

    $del_stmt = $mysqli->prepare("DELETE FROM user_quick_action_assignments WHERE user_id = ?");
    $del_stmt->bind_param("i", $target_user_id);
    $success = $del_stmt->execute();
    $del_stmt->close();

    if ($success) {
        if (function_exists('logActivity')) {
            logActivity(
                $mysqli,
                $_SESSION['user_id'],
                'REVOKE_QUICK_ACTION_ASSIGNMENTS',
                'user_quick_action_assignments',
                $target_user_id,
                null,
                null,
                "District DD revoked all task assignments for Officer ID $target_user_id"
            );
        }
        echo json_encode(['status' => 'success', 'message' => 'All task assignments have been successfully revoked.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to revoke assignments.']);
    }
    exit();
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
exit();
