<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$allowed_roles = [
    'veterinary_surgeon',
    'government_veterinary_surgeon',
    'additional_veterinary_surgeon',
    'deputy_director_hq_1',
    'district_dd',
    'provincial_director',
    'admin',
    'super_admin',
    'planning_officer'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$action = $_REQUEST['action'] ?? '';

// Helper for JSON output
function sendJson($data) {
    echo json_encode($data);
    exit();
}

// -------------------------------------------------------------------------
// 1. ACTION: GET 23 PSDG PROJECT CATALOG ITEMS
// -------------------------------------------------------------------------
if ($action === 'get_psdg_categories') {
    $res = $mysqli->query("SELECT id, category_name, sub_sector, default_subsidy_pct FROM `psdg_project_catalog` WHERE is_active = 1 ORDER BY category_name ASC");
    $categories = [];
    if ($res) {
        $categories = $res->fetch_all(MYSQLI_ASSOC);
    }
    sendJson(['success' => true, 'categories' => $categories]);
}

// -------------------------------------------------------------------------
// 2. ACTION: SAVE BENEFICIARY (ADD OR EDIT)
// -------------------------------------------------------------------------
if ($action === 'save_beneficiary') {
    $id                              = intval($_POST['beneficiary_id'] ?? 0);
    $project_id                      = intval($_POST['project_id'] ?? 0);
    $range_id                        = !empty($_POST['range_id']) && is_numeric($_POST['range_id']) ? intval($_POST['range_id']) : intval($_SESSION['range_id'] ?? 1);
    $beneficiary_name                = trim($_POST['beneficiary_name'] ?? '');
    $nic_no                          = trim($_POST['nic_no'] ?? '');
    $address                         = trim($_POST['address'] ?? '');
    $district                        = trim($_POST['district'] ?? '');
    $range_name                      = trim($_POST['range_name'] ?? '');
    $gn_division                     = trim($_POST['gn_division'] ?? '');
    $phone_no                        = trim($_POST['phone_no'] ?? '');
    $gender                          = trim($_POST['gender'] ?? 'Male');
    
    // Financial & Dual Contribution Tracking
    $dept_contribution_amount        = floatval($_POST['dept_contribution_amount'] ?? 0.00);
    $dept_contribution_activity      = trim($_POST['dept_contribution_activity'] ?? '');
    $beneficiary_contribution_amount = floatval($_POST['beneficiary_contribution_amount'] ?? 0.00);
    $beneficiary_contribution_activity = trim($_POST['beneficiary_contribution_activity'] ?? '');
    
    // Individual Project Parameters / Milestones
    $start_date                      = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $agreement_date                  = !empty($_POST['agreement_date']) ? $_POST['agreement_date'] : null;
    $completion_date                 = !empty($_POST['completion_date']) ? $_POST['completion_date'] : null;
    $doc_submission_date             = !empty($_POST['doc_submission_date']) ? $_POST['doc_submission_date'] : null;
    $final_amount                    = floatval($_POST['final_amount'] ?? 0.00);
    $check_received_date             = !empty($_POST['check_received_date']) ? $_POST['check_received_date'] : null;
    $check_no                        = trim($_POST['check_no'] ?? '');
    $status                          = trim($_POST['status'] ?? 'In Progress');

    if (empty($beneficiary_name) || empty($nic_no) || $project_id <= 0) {
        sendJson(['success' => false, 'message' => 'Beneficiary Name, NIC Number, and valid Project ID are required.']);
    }

    if ($id > 0) {
        // Update existing beneficiary
        $stmt = $mysqli->prepare("
            UPDATE `project_individual_beneficiaries` SET
                `beneficiary_name` = ?,
                `nic_no` = ?,
                `address` = ?,
                `district` = ?,
                `range_name` = ?,
                `gn_division` = ?,
                `phone_no` = ?,
                `gender` = ?,
                `dept_contribution_amount` = ?,
                `dept_contribution_activity` = ?,
                `beneficiary_contribution_amount` = ?,
                `beneficiary_contribution_activity` = ?,
                `start_date` = ?,
                `agreement_date` = ?,
                `completion_date` = ?,
                `doc_submission_date` = ?,
                `final_amount` = ?,
                `check_received_date` = ?,
                `check_no` = ?,
                `status` = ?
            WHERE id = ? AND project_id = ?
        ");
        $stmt->bind_param(
            "ssssssssdsdsssssdsssii",
            $beneficiary_name,
            $nic_no,
            $address,
            $district,
            $range_name,
            $gn_division,
            $phone_no,
            $gender,
            $dept_contribution_amount,
            $dept_contribution_activity,
            $beneficiary_contribution_amount,
            $beneficiary_contribution_activity,
            $start_date,
            $agreement_date,
            $completion_date,
            $doc_submission_date,
            $final_amount,
            $check_received_date,
            $check_no,
            $status,
            $id,
            $project_id
        );
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            sendJson(['success' => true, 'message' => "Beneficiary '{$beneficiary_name}' updated successfully!"]);
        } else {
            sendJson(['success' => false, 'message' => 'Database error updating beneficiary: ' . $mysqli->error]);
        }
    } else {
        // Insert new beneficiary
        $stmt = $mysqli->prepare("
            INSERT INTO `project_individual_beneficiaries` (
                `project_id`, `range_id`, `beneficiary_name`, `nic_no`, `address`,
                `district`, `range_name`, `gn_division`, `phone_no`, `gender`,
                `dept_contribution_amount`, `dept_contribution_activity`,
                `beneficiary_contribution_amount`, `beneficiary_contribution_activity`,
                `start_date`, `agreement_date`, `completion_date`, `doc_submission_date`,
                `final_amount`, `check_received_date`, `check_no`, `status`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iissssssssdsdsssssdsss",
            $project_id,
            $range_id,
            $beneficiary_name,
            $nic_no,
            $address,
            $district,
            $range_name,
            $gn_division,
            $phone_no,
            $gender,
            $dept_contribution_amount,
            $dept_contribution_activity,
            $beneficiary_contribution_amount,
            $beneficiary_contribution_activity,
            $start_date,
            $agreement_date,
            $completion_date,
            $doc_submission_date,
            $final_amount,
            $check_received_date,
            $check_no,
            $status
        );
        $ok = $stmt->execute();
        $new_id = $mysqli->insert_id;
        $stmt->close();

        if ($ok) {
            sendJson(['success' => true, 'message' => "Beneficiary '{$beneficiary_name}' registered successfully!", 'id' => $new_id]);
        } else {
            sendJson(['success' => false, 'message' => 'Database error registering beneficiary: ' . $mysqli->error]);
        }
    }
}

// -------------------------------------------------------------------------
// 3. ACTION: GET SINGLE BENEFICIARY
// -------------------------------------------------------------------------
if ($action === 'get_beneficiary') {
    $id = intval($_GET['id'] ?? 0);
    $stmt = $mysqli->prepare("SELECT * FROM `project_individual_beneficiaries` WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $ben = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($ben) {
        sendJson(['success' => true, 'beneficiary' => $ben]);
    } else {
        sendJson(['success' => false, 'message' => 'Beneficiary profile not found.']);
    }
}

// -------------------------------------------------------------------------
// 4. ACTION: DELETE BENEFICIARY
// -------------------------------------------------------------------------
if ($action === 'delete_beneficiary') {
    $id = intval($_POST['id'] ?? 0);
    $stmt = $mysqli->prepare("DELETE FROM `project_individual_beneficiaries` WHERE id = ?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) {
        sendJson(['success' => true, 'message' => 'Beneficiary removed successfully.']);
    } else {
        sendJson(['success' => false, 'message' => 'Could not remove beneficiary.']);
    }
}

// -------------------------------------------------------------------------
// 5. ACTION: RECORD SCHEDULED MONITORING SITE VISIT (PROJECT OR DAIRY HUB)
// -------------------------------------------------------------------------
if ($action === 'save_monitoring_visit') {
    $source_module           = trim($_POST['source_module'] ?? 'Project');
    $project_id              = !empty($_POST['project_id']) ? intval($_POST['project_id']) : null;
    $beneficiary_id          = !empty($_POST['beneficiary_id']) ? intval($_POST['beneficiary_id']) : null;
    $dairy_hub_id            = !empty($_POST['dairy_hub_id']) ? intval($_POST['dairy_hub_id']) : null;
    $farm_reg_no             = trim($_POST['farm_reg_no'] ?? '');
    $farmer_name             = trim($_POST['farmer_name'] ?? '');
    $range_id                = !empty($_POST['range_id']) && is_numeric($_POST['range_id']) ? intval($_POST['range_id']) : intval($_SESSION['range_id'] ?? 1);
    $range_name              = trim($_POST['range_name'] ?? 'Range Office');
    $veterinary_surgeon_name = trim($_POST['veterinary_surgeon_name'] ?? ($_SESSION['full_name'] ?? 'Veterinary Surgeon'));
    
    $visit_date              = !empty($_POST['visit_date']) ? $_POST['visit_date'] : date('Y-m-d');
    $visit_interval          = trim($_POST['visit_interval'] ?? '1 Month');
    $farm_conditions         = trim($_POST['farm_conditions'] ?? '');
    $animal_illnesses        = trim($_POST['animal_illnesses'] ?? '');
    $deaths_count            = intval($_POST['deaths_count'] ?? 0);
    $deaths_details          = trim($_POST['deaths_details'] ?? '');
    $natural_disasters       = trim($_POST['natural_disasters'] ?? '');
    $recommendations         = trim($_POST['recommendations'] ?? '');

    if (empty($farm_conditions) || empty($visit_date)) {
        sendJson(['success' => false, 'message' => 'Visit Date and Farm Conditions evaluation are mandatory.']);
    }

    $stmt = $mysqli->prepare("
        INSERT INTO `project_monitoring_visits` (
            `source_module`, `project_id`, `beneficiary_id`, `dairy_hub_id`,
            `farm_reg_no`, `farmer_name`, `range_id`, `range_name`,
            `veterinary_surgeon_name`, `visit_date`, `visit_interval`,
            `farm_conditions`, `animal_illnesses`, `deaths_count`,
            `deaths_details`, `natural_disasters`, `recommendations`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "siiississssssisss",
        $source_module,
        $project_id,
        $beneficiary_id,
        $dairy_hub_id,
        $farm_reg_no,
        $farmer_name,
        $range_id,
        $range_name,
        $veterinary_surgeon_name,
        $visit_date,
        $visit_interval,
        $farm_conditions,
        $animal_illnesses,
        $deaths_count,
        $deaths_details,
        $natural_disasters,
        $recommendations
    );
    $ok = $stmt->execute();
    $visit_id = $mysqli->insert_id;
    $stmt->close();

    if ($ok) {
        sendJson([
            'success' => true, 
            'message' => "Monitoring site visit ({$visit_interval}) recorded successfully by {$veterinary_surgeon_name}!",
            'visit_id' => $visit_id
        ]);
    } else {
        sendJson(['success' => false, 'message' => 'Error recording monitoring visit: ' . $mysqli->error]);
    }
}

// -------------------------------------------------------------------------
// 6. ACTION: GET MONITORING VISITS LIST (FILTERABLE)
// -------------------------------------------------------------------------
if ($action === 'get_monitoring_visits') {
    $source_module = trim($_GET['source_module'] ?? '');
    $project_id    = intval($_GET['project_id'] ?? 0);
    $beneficiary_id= intval($_GET['beneficiary_id'] ?? 0);
    $dairy_hub_id  = intval($_GET['dairy_hub_id'] ?? 0);
    $range_id      = !empty($_POST['range_id']) && is_numeric($_POST['range_id']) ? intval($_POST['range_id']) : intval($_SESSION['range_id'] ?? 0);

    $sql = "SELECT * FROM `project_monitoring_visits` WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($source_module)) {
        $sql .= " AND source_module = ?";
        $params[] = $source_module;
        $types .= "s";
    }
    if ($project_id > 0) {
        $sql .= " AND project_id = ?";
        $params[] = $project_id;
        $types .= "i";
    }
    if ($beneficiary_id > 0) {
        $sql .= " AND beneficiary_id = ?";
        $params[] = $beneficiary_id;
        $types .= "i";
    }
    if ($dairy_hub_id > 0) {
        $sql .= " AND dairy_hub_id = ?";
        $params[] = $dairy_hub_id;
        $types .= "i";
    }
    if ($range_id > 0) {
        $sql .= " AND (range_id = ? OR range_id IS NULL)";
        $params[] = $range_id;
        $types .= "i";
    }

    $sql .= " ORDER BY visit_date DESC, id DESC";
    $stmt = $mysqli->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $visits = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    sendJson(['success' => true, 'visits' => $visits]);
}

sendJson(['success' => false, 'message' => 'Invalid action request.']);
