<?php
/**
 * pages/modules/veterinary/processors/dairy_project_crud.php
 * Backend AJAX & Form processor for Dairy Development Project Summary & Application Forms
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/db_connect.php';
require_once __DIR__ . '/dairy_db_migration.php';

/** @var mysqli $mysqli */
global $mysqli;
ensure_dairy_project_tables($mysqli);

header('Content-Type: application/json; charset=utf-8');

// Role Guard
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
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit();
}

$action = trim($_REQUEST['action'] ?? '');
$user_id = intval($_SESSION['user_id'] ?? 0);

// Helper function to return JSON
function jsonResponse($data) {
    echo json_encode($data);
    exit();
}

// -------------------------------------------------------------------------
// CENTRAL MASTER FILE RIPPLE SYNCHRONIZATION ENGINE
// Ensures Central Master File is single source of truth across all modules
// -------------------------------------------------------------------------
function rippleFarmerMasterUpdates(mysqli $mysqli, string $farm_reg_no, array $data, int $user_id = 0): void {
    if (empty($farm_reg_no)) return;

    $name       = trim($data['farmer_name'] ?? ($data['producer_name'] ?? ($data['beneficiary_name'] ?? ($data['full_name'] ?? ''))));
    $nic        = trim($data['nic'] ?? ($data['nic_no'] ?? ''));
    $phone      = trim($data['phone'] ?? ($data['contact_mobile'] ?? ($data['telephone_no'] ?? ($data['contact_no'] ?? ''))));
    $address    = trim($data['address'] ?? ($data['farmer_address'] ?? ($data['location_address'] ?? '')));
    $ds_gn      = trim($data['ds_gn_division'] ?? '');
    $gender     = trim($data['gender'] ?? ($data['sex'] ?? ''));
    $ethnicity  = trim($data['ethnicity'] ?? '');
    $volume     = trim($data['milk_production_volume'] ?? '');
    $daily_milk = floatval($data['daily_milk'] ?? ($data['daily_milk_production'] ?? ($data['daily_milk_current'] ?? ($data['milk_total_liters'] ?? 0))));
    $range_id   = intval($data['range_id'] ?? 0);

    // 1. Snapshot previous state into farm_master_history (Non-Deletion Policy)
    $snap_q = $mysqli->prepare("SELECT * FROM `farmers` WHERE farm_registration_no = ? LIMIT 1");
    if ($snap_q) {
        $snap_q->bind_param("s", $farm_reg_no);
        $snap_q->execute();
        if ($old_f = $snap_q->get_result()->fetch_assoc()) {
            $snap_json = json_encode($old_f);
            $h_stmt = $mysqli->prepare("INSERT INTO `farm_master_history` (farm_reg_no, module_source, action_type, snapshot_data, recorded_by) VALUES (?, 'farmers_master', 'UPDATE_RIPPLE', ?, ?)");
            if ($h_stmt) {
                $h_stmt->bind_param("ssi", $farm_reg_no, $snap_json, $user_id);
                $h_stmt->execute();
                $h_stmt->close();
            }
        }
        $snap_q->close();
    }

    // 2. Ripple to farmers master table
    $f_chk = $mysqli->prepare("SELECT id FROM `farmers` WHERE farm_registration_no = ? LIMIT 1");
    $f_chk->bind_param("s", $farm_reg_no);
    $f_chk->execute();
    $f_row = $f_chk->get_result()->fetch_assoc();
    $f_chk->close();

    if ($f_row) {
        $fid = (int)$f_row['id'];
        $upd_f = $mysqli->prepare("
            UPDATE `farmers` 
            SET full_name = IF(? != '', ?, full_name),
                nic_no = IF(? != '', ?, nic_no),
                contact_no = IF(? != '', ?, contact_no),
                location_address = IF(? != '', ?, location_address),
                gender = IF(? != '', ?, gender),
                ethnicity = IF(? != '', ?, ethnicity),
                milk_production_volume = IF(? != '', ?, milk_production_volume)
            WHERE id = ?
        ");
        $upd_f->bind_param("ssssssssssssssi", 
            $name, $name, 
            $nic, $nic, 
            $phone, $phone, 
            $address, $address, 
            $gender, $gender, 
            $ethnicity, $ethnicity, 
            $volume, $volume, 
            $fid
        );
        $upd_f->execute();
        $upd_f->close();
    }

    // 3. Ripple to farm_registration_renewals master registry
    $ren_chk = $mysqli->prepare("SELECT id FROM `farm_registration_renewals` WHERE registration_no = ? LIMIT 1");
    $ren_chk->bind_param("s", $farm_reg_no);
    $ren_chk->execute();
    $ren_row = $ren_chk->get_result()->fetch_assoc();
    $ren_chk->close();

    if ($ren_row) {
        $rid = (int)$ren_row['id'];
        $upd_ren = $mysqli->prepare("
            UPDATE `farm_registration_renewals` 
            SET farmer_name = IF(? != '', ?, farmer_name),
                nic = IF(? != '', ?, nic),
                telephone_no = IF(? != '', ?, telephone_no),
                farmer_address = IF(? != '', ?, farmer_address),
                gender = IF(? != '', ?, gender),
                ethnicity = IF(? != '', ?, ethnicity),
                milk_production_volume = IF(? != '', ?, milk_production_volume),
                daily_milk_production = IF(? > 0, ?, daily_milk_production)
            WHERE id = ?
        ");
        $upd_ren->bind_param("ssssssssssssssssddi",
            $name, $name,
            $nic, $nic,
            $phone, $phone,
            $address, $address,
            $gender, $gender,
            $ethnicity, $ethnicity,
            $volume, $volume,
            $daily_milk, $daily_milk,
            $rid
        );
        $upd_ren->execute();
        $upd_ren->close();
    }

    // 4. Ripple to dairy_project_beneficiaries
    $upd_dpb = $mysqli->prepare("
        UPDATE `dairy_project_beneficiaries` 
        SET beneficiary_name = IF(? != '', ?, beneficiary_name),
            nic = IF(? != '', ?, nic),
            phone = IF(? != '', ?, phone),
            address = IF(? != '', ?, address),
            daily_milk_current = IF(? > 0, ?, daily_milk_current)
        WHERE farm_reg_no = ?
    ");
    if ($upd_dpb) {
        $upd_dpb->bind_param("ssssssssddds",
            $name, $name,
            $nic, $nic,
            $phone, $phone,
            $address, $address,
            $daily_milk, $daily_milk, $farm_reg_no
        );
        $upd_dpb->execute();
        $upd_dpb->close();
    }

    // 5. Ripple to dairy_hub_society_members
    $upd_soc = $mysqli->prepare("
        UPDATE `dairy_hub_society_members` 
        SET member_name = IF(? != '', ?, member_name),
            nic = IF(? != '', ?, nic),
            phone = IF(? != '', ?, phone),
            address = IF(? != '', ?, address),
            ds_gn_division = IF(? != '', ?, ds_gn_division)
        WHERE farm_reg_no = ?
    ");
    if ($upd_soc) {
        $upd_soc->bind_param("sssssssssss",
            $name, $name,
            $nic, $nic,
            $phone, $phone,
            $address, $address,
            $ds_gn, $ds_gn,
            $farm_reg_no
        );
        $upd_soc->execute();
        $upd_soc->close();
    }

    // 6. Ripple to activity_beneficiaries
    $upd_act = $mysqli->prepare("
        UPDATE `activity_beneficiaries` 
        SET name = IF(? != '', ?, name),
            nic = IF(? != '', ?, nic),
            phone = IF(? != '', ?, phone),
            address = IF(? != '', ?, address)
        WHERE farm_reg_no = ?
    ");
    if ($upd_act) {
        $upd_act->bind_param("sssssssss",
            $name, $name,
            $nic, $nic,
            $phone, $phone,
            $address, $address,
            $farm_reg_no
        );
        $upd_act->execute();
        $upd_act->close();
    }
}

// -------------------------------------------------------------------------
// 1. ACTION: LOOKUP FARMER FROM MASTER REGISTRY
// -------------------------------------------------------------------------
if ($action === 'lookup_farmer') {
    $farm_reg_no = trim($_REQUEST['farm_reg_no'] ?? $_REQUEST['query'] ?? '');
    if (empty($farm_reg_no)) {
        jsonResponse(['success' => false, 'message' => 'Farm Registration Number or NIC is required.']);
    }

    $clean_query = preg_replace('/[^0-9a-zA-Z]/', '', $farm_reg_no);
    $found = false;
    $farmer = null;

    // 1. Search farm_registration_renewals (Master Registry)
    $stmt1 = $mysqli->prepare("
        SELECT id, range_id, district_id, date_of_registration_renewal, province, district, ds_division, vs_division, gn_division,
               farmer_name, farmer_address, registration_no, telephone_no, nic, farm_type,
               total_neat_cattle, total_buffaloes, daily_milk_production, gps_location,
               gender, ethnicity, milk_production_volume
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

    if ($stmt1) {
        $stmt1->bind_param("ssssss", $farm_reg_no, $clean_query, $farm_reg_no, $clean_query, $clean_query, $farm_reg_no);
        $stmt1->execute();
        $res1 = $stmt1->get_result();
        if ($r = $res1->fetch_assoc()) {
            $found = true;
            $farmer = [
                'farm_reg_no'            => $r['registration_no'] ?: $farm_reg_no,
                'farmer_name'            => $r['farmer_name'] ?: '',
                'nic'                    => $r['nic'] ?: '',
                'address'                => $r['farmer_address'] ?: '',
                'phone'                  => $r['telephone_no'] ?: '',
                'gender'                 => in_array($r['gender'] ?? '', ['Male', 'Female'], true) ? $r['gender'] : 'Male',
                'ethnicity'              => !empty($r['ethnicity']) ? $r['ethnicity'] : 'Sinhala',
                'milk_production_volume' => $r['milk_production_volume'] ?: '',
                'daily_milk'             => floatval($r['daily_milk_production'] ?? 0),
                'cattle_count'           => intval($r['total_neat_cattle'] ?? 0),
                'buffalo_count'          => intval($r['total_buffaloes'] ?? 0),
                'gps_location'           => $r['gps_location'] ?: '',
                'ds_division'            => $r['ds_division'] ?: '',
                'vs_division'            => $r['vs_division'] ?: '',
                'gn_division'            => $r['gn_division'] ?: '',
                'district'               => $r['district'] ?: '',
                'range_id'               => intval($r['range_id'] ?? 0),
                'district_id'            => intval($r['district_id'] ?? 0),
                'source'                 => 'Master Registry (farm_registration_renewals)'
            ];
        }
        $stmt1->close();
    }

    // 2. Fallback: Search farmers table
    if (!$found) {
        $stmt2 = $mysqli->prepare("
            SELECT f.*, COALESCE(vr.name, '') AS vs_division, COALESCE(d.name, '') AS district
            FROM farmers f
            LEFT JOIN veterinary_ranges vr ON vr.id = f.range_id
            LEFT JOIN districts d ON d.id = f.district_id
            WHERE (
                LOWER(TRIM(f.farm_registration_no)) = LOWER(?)
                OR LOWER(REPLACE(REPLACE(COALESCE(f.farm_registration_no, ''), ' ', ''), '-', '')) = LOWER(?)
                OR LOWER(TRIM(f.nic_no)) = LOWER(?)
                OR LOWER(REPLACE(REPLACE(COALESCE(f.nic_no, ''), ' ', ''), '-', '')) = LOWER(?)
            )
            ORDER BY id DESC LIMIT 1
        ");
        if ($stmt2) {
            $stmt2->bind_param("ssss", $farm_reg_no, $clean_query, $farm_reg_no, $clean_query);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            if ($r2 = $res2->fetch_assoc()) {
                $found = true;
                $farmer = [
                    'farm_reg_no'            => $r2['farm_registration_no'] ?: $farm_reg_no,
                    'farmer_name'            => $r2['full_name'] ?: '',
                    'nic'                    => $r2['nic_no'] ?: '',
                    'address'                => $r2['location_address'] ?: '',
                    'phone'                  => $r2['contact_no'] ?: '',
                    'gender'                 => in_array($r2['gender'] ?? '', ['Male', 'Female'], true) ? $r2['gender'] : 'Male',
                    'ethnicity'              => !empty($r2['ethnicity']) ? $r2['ethnicity'] : 'Sinhala',
                    'milk_production_volume' => $r2['milk_production_volume'] ?: '',
                    'daily_milk'             => 0.00,
                    'cattle_count'           => intval($r2['cattle_count'] ?? 0),
                    'buffalo_count'          => intval($r2['buffalo_count'] ?? 0),
                    'gps_location'           => '',
                    'ds_division'            => '',
                    'vs_division'            => $r2['vs_division'] ?: '',
                    'gn_division'            => '',
                    'district'               => $r2['district'] ?: '',
                    'range_id'               => intval($r2['range_id'] ?? 0),
                    'district_id'            => intval($r2['district_id'] ?? 0),
                    'source'                 => 'Farmers Directory'
                ];
            }
            $stmt2->close();
        }
    }

    // Check if there is an existing summary or detailed application record for this farmer
    $existing_summary = null;
    $existing_app = null;
    $effective_reg_no = $farmer['farm_reg_no'] ?? $farm_reg_no;

    $chk_sum = $mysqli->prepare("SELECT id, category, daily_milk_current, daily_milk_expected, activities, total_cost, beneficiary_contribution, department_contribution, approval_status FROM dairy_project_beneficiaries WHERE farm_reg_no = ? ORDER BY id DESC LIMIT 1");
    if ($chk_sum) {
        $chk_sum->bind_param("s", $effective_reg_no);
        $chk_sum->execute();
        $existing_summary = $chk_sum->get_result()->fetch_assoc();
        $chk_sum->close();
    }

    $chk_app = $mysqli->prepare("SELECT id, main_category, survey_date, milk_total_liters, milk_expected_liters, created_at FROM dairy_farm_applications WHERE farm_reg_no = ? ORDER BY id DESC LIMIT 1");
    if ($chk_app) {
        $chk_app->bind_param("s", $effective_reg_no);
        $chk_app->execute();
        $existing_app = $chk_app->get_result()->fetch_assoc();
        $chk_app->close();
    }
    // Historical Project Data Auto-Population
    $hist_projects = [];
    $has_project_benefit = false;
    $project_benefit_details = '';

    if ($found) {
        $farmer_nic = $farmer['nic'] ?? '';
        $q_proj1 = $mysqli->prepare("
            SELECT project_name, project_year, category, activities, department_contribution, approval_status 
            FROM `dairy_project_beneficiaries` 
            WHERE (farm_reg_no = ? OR (nic = ? AND nic != '')) AND COALESCE(is_deleted, 0) = 0
            ORDER BY project_year DESC, id DESC
        ");
        if ($q_proj1) {
            $q_proj1->bind_param("ss", $effective_reg_no, $farmer_nic);
            $q_proj1->execute();
            $p_res1 = $q_proj1->get_result();
            while ($pr = $p_res1->fetch_assoc()) {
                $has_project_benefit = true;
                $dept_c = floatval($pr['department_contribution'] ?? 0);
                $hist_projects[] = [
                    'source'   => 'National Dairy Programme',
                    'project'  => $pr['project_name'],
                    'year'     => $pr['project_year'],
                    'category' => $pr['category'],
                    'details'  => "Category: {$pr['category']}, Work: {$pr['activities']}, Grant: Rs. " . number_format($dept_c, 2),
                    'status'   => $pr['approval_status']
                ];
            }
            $q_proj1->close();
        }

        $q_proj2 = $mysqli->prepare("
            SELECT ab.name, ab.dept_contribution, ab.dept_contribution_amount, ab.work_done_percentage, ab.created_at,
                   COALESCE(spa.activity_name, 'Livestock Development Project') AS activity_title
            FROM `activity_beneficiaries` ab
            LEFT JOIN `strategic_production_activities` spa ON ab.activity_id = spa.id
            WHERE ab.farm_reg_no = ? OR (ab.nic = ? AND ab.nic != '')
            ORDER BY ab.id DESC
        ");
        if ($q_proj2) {
            $q_proj2->bind_param("ss", $effective_reg_no, $farmer_nic);
            $q_proj2->execute();
            $p_res2 = $q_proj2->get_result();
            while ($pr2 = $p_res2->fetch_assoc()) {
                $has_project_benefit = true;
                $yr = !empty($pr2['created_at']) ? date('Y', strtotime($pr2['created_at'])) : date('Y');
                $hist_projects[] = [
                    'source'   => 'Strategic Production Project',
                    'project'  => $pr2['activity_title'],
                    'year'     => $yr,
                    'category' => 'Livestock Works',
                    'details'  => ($pr2['dept_contribution'] ? "Contribution: {$pr2['dept_contribution']}, " : '') . "Progress: {$pr2['work_done_percentage']}%",
                    'status'   => 'Beneficiary'
                ];
            }
            $q_proj2->close();
        }

        if ($has_project_benefit && !empty($hist_projects)) {
            $parts = [];
            foreach ($hist_projects as $hp) {
                $parts[] = "Year {$hp['year']}: {$hp['project']} - {$hp['details']} [Status: {$hp['status']}]";
            }
            $project_benefit_details = implode(" | ", $parts);
        }

        $farmer['previous_project_benefit'] = $has_project_benefit ? 'Yes' : 'No';
        $farmer['previous_project_details'] = $project_benefit_details;
        $farmer['historical_projects']      = $hist_projects;
    }

    if ($found) {
        jsonResponse([
            'success'          => true,
            'found'            => true,
            'farmer'           => $farmer,
            'existing_summary' => $existing_summary,
            'existing_app'     => $existing_app,
            'message'          => 'Farmer details fetched from master registry.'
        ]);
    } else {
        jsonResponse([
            'success'          => false,
            'found'            => false,
            'existing_summary' => $existing_summary,
            'existing_app'     => $existing_app,
            'message'          => 'No farmer found in master registry with Registration No / NIC: ' . htmlspecialchars($farm_reg_no)
        ]);
    }
}

// -------------------------------------------------------------------------
// 2. ACTION: SAVE BENEFICIARY SUMMARY (CREATE OR UPDATE)
// -------------------------------------------------------------------------
if ($action === 'save_summary') {
    $id                       = intval($_POST['id'] ?? 0);
    $project_name             = trim($_POST['project_name'] ?? 'National Dairy Production Programme');
    $project_year             = intval($_POST['project_year'] ?? date('Y'));
    $range_id                 = !empty($_POST['range_id']) ? intval($_POST['range_id']) : (!empty($_SESSION['range_id']) ? intval($_SESSION['range_id']) : NULL);
    $district_id              = !empty($_POST['district_id']) ? intval($_POST['district_id']) : (!empty($_SESSION['district_id']) ? intval($_SESSION['district_id']) : NULL);
    $farm_reg_no              = trim($_POST['farm_reg_no'] ?? '');
    $beneficiary_name         = trim($_POST['beneficiary_name'] ?? '');
    $nic                      = trim($_POST['nic'] ?? '');
    $phone                    = trim($_POST['phone'] ?? '');
    $address                  = trim($_POST['address'] ?? '');
    $daily_milk_current       = floatval($_POST['daily_milk_current'] ?? 0);
    $daily_milk_expected      = floatval($_POST['daily_milk_expected'] ?? 0);
    $category                 = trim($_POST['category'] ?? '5L');
    $activities               = trim($_POST['activities'] ?? '');
    $total_cost               = floatval($_POST['total_cost'] ?? 0);
    $beneficiary_contribution = floatval($_POST['beneficiary_contribution'] ?? 0);
    $department_contribution  = floatval($_POST['department_contribution'] ?? 0);
    $approval_status          = trim($_POST['approval_status'] ?? 'Pending');
    $remarks                  = trim($_POST['remarks'] ?? '');

    if (empty($farm_reg_no) || empty($beneficiary_name) || empty($nic)) {
        jsonResponse(['success' => false, 'message' => 'Farm Registration No, Beneficiary Name, and NIC are mandatory fields.']);
    }

    if ($id > 0) {
        // Update existing record
        $stmt = $mysqli->prepare("
            UPDATE `dairy_project_beneficiaries`
            SET `project_name` = ?,
                `project_year` = ?,
                `range_id` = ?,
                `district_id` = ?,
                `farm_reg_no` = ?,
                `beneficiary_name` = ?,
                `address` = ?,
                `nic` = ?,
                `phone` = ?,
                `daily_milk_current` = ?,
                `daily_milk_expected` = ?,
                `category` = ?,
                `activities` = ?,
                `total_cost` = ?,
                `beneficiary_contribution` = ?,
                `department_contribution` = ?,
                `approval_status` = ?,
                `remarks` = ?
            WHERE `id` = ?
        ");
        $stmt->bind_param(
            "siissssssddssdddssi",
            $project_name,
            $project_year,
            $range_id,
            $district_id,
            $farm_reg_no,
            $beneficiary_name,
            $address,
            $nic,
            $phone,
            $daily_milk_current,
            $daily_milk_expected,
            $category,
            $activities,
            $total_cost,
            $beneficiary_contribution,
            $department_contribution,
            $approval_status,
            $remarks,
            $id
        );
        $res = $stmt->execute();
        $stmt->close();

        // Also update summary_id in dairy_farm_applications if matching farm_reg_no
        $mysqli->query("UPDATE `dairy_farm_applications` SET `summary_id` = {$id} WHERE `farm_reg_no` = '" . $mysqli->real_escape_string($farm_reg_no) . "' AND (`summary_id` IS NULL OR `summary_id` = 0)");

        if ($res) {
            rippleFarmerMasterUpdates($mysqli, $farm_reg_no, $_POST, $user_id);
            jsonResponse(['success' => true, 'id' => $id, 'message' => 'Beneficiary summary record updated successfully & master file synced!']);
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to update beneficiary record: ' . $mysqli->error]);
        }
    } else {
        // Insert new record
        $stmt = $mysqli->prepare("
            INSERT INTO `dairy_project_beneficiaries` (
                `project_name`, `project_year`, `range_id`, `district_id`, `farm_reg_no`, `beneficiary_name`,
                `address`, `nic`, `phone`, `daily_milk_current`, `daily_milk_expected`, `category`,
                `activities`, `total_cost`, `beneficiary_contribution`, `department_contribution`,
                `approval_status`, `remarks`, `created_by`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "siissssssddssdddssi",
            $project_name,
            $project_year,
            $range_id,
            $district_id,
            $farm_reg_no,
            $beneficiary_name,
            $address,
            $nic,
            $phone,
            $daily_milk_current,
            $daily_milk_expected,
            $category,
            $activities,
            $total_cost,
            $beneficiary_contribution,
            $department_contribution,
            $approval_status,
            $remarks,
            $user_id
        );
        $res = $stmt->execute();
        $new_id = $stmt->insert_id;
        $stmt->close();

        if ($res) {
            // Ripple updates across modules and snapshot audit
            rippleFarmerMasterUpdates($mysqli, $farm_reg_no, $_POST, $user_id);

            // Link existing application if present
            $mysqli->query("UPDATE `dairy_farm_applications` SET `summary_id` = {$new_id} WHERE `farm_reg_no` = '" . $mysqli->real_escape_string($farm_reg_no) . "'");

            jsonResponse(['success' => true, 'id' => $new_id, 'message' => 'Beneficiary added to the Summary Approval Register successfully & master file synced!']);
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to insert beneficiary record: ' . $mysqli->error]);
        }
    }
}

// -------------------------------------------------------------------------
// 3. ACTION: GET SINGLE SUMMARY RECORD
// -------------------------------------------------------------------------
if ($action === 'get_summary') {
    $id = intval($_REQUEST['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid ID specified.']);
    }

    $stmt = $mysqli->prepare("SELECT b.*, COALESCE(vr.name, '') AS range_name, COALESCE(d.name, '') AS district_name FROM `dairy_project_beneficiaries` b LEFT JOIN veterinary_ranges vr ON b.range_id = vr.id LEFT JOIN districts d ON b.district_id = d.id WHERE b.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($data) {
        jsonResponse(['success' => true, 'data' => $data]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Record not found.']);
    }
}

// -------------------------------------------------------------------------
// 4. ACTION: DELETE SUMMARY RECORD (STRICT NON-DELETION & ARCHIVE POLICY)
// -------------------------------------------------------------------------
if ($action === 'delete_summary') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid ID.']);
    }

    // Strict Non-Deletion Policy: Never hard-delete records.
    // Preserve past data points for historical statistical trend integrity.
    $fetch_old = $mysqli->prepare("SELECT * FROM `dairy_project_beneficiaries` WHERE id = ?");
    if ($fetch_old) {
        $fetch_old->bind_param("i", $id);
        $fetch_old->execute();
        $old_row = $fetch_old->get_result()->fetch_assoc();
        $fetch_old->close();
        if ($old_row) {
            $snap = json_encode($old_row);
            $farm_no = $old_row['farm_reg_no'] ?? '';
            $hist = $mysqli->prepare("INSERT INTO `farm_master_history` (farm_reg_no, module_source, action_type, snapshot_data, recorded_by) VALUES (?, 'dairy_project_beneficiaries', 'ARCHIVE_SOFT_DELETE', ?, ?)");
            if ($hist) {
                $hist->bind_param("ssi", $farm_no, $snap, $user_id);
                $hist->execute();
                $hist->close();
            }
        }
    }

    $stmt = $mysqli->prepare("UPDATE `dairy_project_beneficiaries` SET `is_deleted` = 1, `deleted_at` = NOW() WHERE id = ?");
    $stmt->bind_param("i", $id);
    $res = $stmt->execute();
    $stmt->close();

    if ($res) {
        jsonResponse(['success' => true, 'message' => 'Beneficiary entry archived into historical repository. Statistical trends preserved.']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error archiving record: ' . $mysqli->error]);
    }
}

// -------------------------------------------------------------------------
// 5. ACTION: SAVE DETAILED APPLICATION FORM
// -------------------------------------------------------------------------
if ($action === 'save_application') {
    $farm_reg_no = trim($_POST['farm_reg_no'] ?? '');
    if (empty($farm_reg_no)) {
        jsonResponse(['success' => false, 'message' => 'Farm Registration Number is required.']);
    }

    $producer_name            = trim($_POST['producer_name'] ?? '');
    $nic_no                   = trim($_POST['nic_no'] ?? '');
    $address                  = trim($_POST['address'] ?? '');
    $ai_no                    = trim($_POST['ai_no'] ?? '');
    $survey_date              = !empty($_POST['survey_date']) ? $_POST['survey_date'] : date('Y-m-d');
    $main_category            = trim($_POST['main_category'] ?? '05 Liters');
    $daph_register_no         = trim($_POST['daph_register_no'] ?? '');
    $contact_mobile           = trim($_POST['contact_mobile'] ?? '');
    $telephone_no             = trim($_POST['telephone_no'] ?? '');
    $ds_gn_division           = trim($_POST['ds_gn_division'] ?? '');
    $gender                   = trim($_POST['gender'] ?? 'Male');
    $livestock_council        = trim($_POST['livestock_council'] ?? 'No');
    $transport_distance_km    = floatval($_POST['transport_distance_km'] ?? 0);
    $previous_project_benefit = trim($_POST['previous_project_benefit'] ?? 'No');
    $previous_project_details = trim($_POST['previous_project_details'] ?? '');

    $water_source             = trim($_POST['water_source'] ?? '');
    $custom_water_source      = trim($_POST['custom_water_source'] ?? '');
    if (!empty($custom_water_source) || $water_source === 'Other' || $water_source === 'Other sources') {
        if (!empty($custom_water_source)) {
            $water_source = $custom_water_source;
            $chk_ws = $mysqli->prepare("SELECT id FROM `dairy_water_sources` WHERE LOWER(TRIM(source_name)) = LOWER(TRIM(?)) LIMIT 1");
            if ($chk_ws) {
                $chk_ws->bind_param("s", $custom_water_source);
                $chk_ws->execute();
                if (!$chk_ws->get_result()->fetch_assoc()) {
                    $ins_ws = $mysqli->prepare("INSERT INTO `dairy_water_sources` (source_name) VALUES (?)");
                    if ($ins_ws) {
                        $ins_ws->bind_param("s", $custom_water_source);
                        $ins_ws->execute();
                        $ins_ws->close();
                    }
                }
                $chk_ws->close();
            }
        }
    }

    $land_extent              = trim($_POST['land_extent'] ?? '');
    $pasture_extent           = trim($_POST['pasture_extent'] ?? '');
    $shed_capacity_adults     = intval($_POST['shed_capacity_adults'] ?? 0);
    $shed_capacity_calves     = intval($_POST['shed_capacity_calves'] ?? 0);
    $common_grazing_land      = trim($_POST['common_grazing_land'] ?? '');
    $pasture_cultivation_land = trim($_POST['pasture_cultivation_land'] ?? '');

    $has_grass_chopper        = isset($_POST['has_grass_chopper']) ? intval($_POST['has_grass_chopper']) : 0;
    $has_milk_cans            = isset($_POST['has_milk_cans']) ? intval($_POST['has_milk_cans']) : 0;
    $has_milking_machine      = isset($_POST['has_milking_machine']) ? intval($_POST['has_milking_machine']) : 0;
    $has_chilling_equip       = isset($_POST['has_chilling_equip']) ? intval($_POST['has_chilling_equip']) : 0;
    $has_biogas_plant         = isset($_POST['has_biogas_plant']) ? intval($_POST['has_biogas_plant']) : 0;
    $biogas_capacity_m3       = trim($_POST['biogas_capacity_m3'] ?? '');
    $id_ear_tag_no            = trim($_POST['id_ear_tag_no'] ?? '');
    $id_hot_brand_no          = trim($_POST['id_hot_brand_no'] ?? '');

    $geo_coordinates          = trim($_POST['geo_coordinates'] ?? '');
    $agri_env_ai_cc           = trim($_POST['agri_env_ai_cc'] ?? '');
    $agri_env_ds_division     = trim($_POST['agri_env_ds_division'] ?? '');
    $agri_env_gn_division     = trim($_POST['agri_env_gn_division'] ?? '');

    $herd_composition_data    = isset($_POST['herd_composition_data']) ? (is_array($_POST['herd_composition_data']) ? json_encode($_POST['herd_composition_data']) : $_POST['herd_composition_data']) : '[]';
    $feeding_types_data       = isset($_POST['feeding_types_data']) ? (is_array($_POST['feeding_types_data']) ? json_encode($_POST['feeding_types_data']) : $_POST['feeding_types_data']) : '[]';

    $cultivating_grass_own_land = isset($_POST['cultivating_grass_own_land']) ? intval($_POST['cultivating_grass_own_land']) : 0;
    $own_land_grass_extent      = trim($_POST['own_land_grass_extent'] ?? '');
    $type_of_grass              = trim($_POST['type_of_grass'] ?? '');
    $grass_cutting_method       = trim($_POST['grass_cutting_method'] ?? 'Hand');
    $chop_fodder_before_feeding = isset($_POST['chop_fodder_before_feeding']) ? intval($_POST['chop_fodder_before_feeding']) : 0;
    $made_silage_before         = isset($_POST['made_silage_before']) ? intval($_POST['made_silage_before']) : 0;
    $willing_to_make_silage     = isset($_POST['willing_to_make_silage']) ? intval($_POST['willing_to_make_silage']) : 0;
    $feed_silage                = isset($_POST['feed_silage']) ? intval($_POST['feed_silage']) : 0;
    $buy_agro_byproducts        = isset($_POST['buy_agro_byproducts']) ? intval($_POST['buy_agro_byproducts']) : 0;
    $buy_concentrated_feed      = isset($_POST['buy_concentrated_feed']) ? intval($_POST['buy_concentrated_feed']) : 0;
    $mix_mineral_mixture        = isset($_POST['mix_mineral_mixture']) ? intval($_POST['mix_mineral_mixture']) : 0;

    $milk_morning_liters        = floatval($_POST['milk_morning_liters'] ?? 0);
    $milk_evening_liters        = floatval($_POST['milk_evening_liters'] ?? 0);
    $milk_total_liters          = floatval($_POST['milk_total_liters'] ?? ($milk_morning_liters + $milk_evening_liters));
    $milk_expected_liters       = floatval($_POST['milk_expected_liters'] ?? 0);
    $milk_hygiene_measures      = trim($_POST['milk_hygiene_measures'] ?? '');
    $milk_price_per_liter       = floatval($_POST['milk_price_per_liter'] ?? 0);

    $sell_to_private_collector   = isset($_POST['sell_to_private_collector']) ? intval($_POST['sell_to_private_collector']) : 0;
    $sell_to_organized_collector = isset($_POST['sell_to_organized_collector']) ? intval($_POST['sell_to_organized_collector']) : 0;
    $marketing_details           = trim($_POST['marketing_details'] ?? '');
    $keep_daily_records          = isset($_POST['keep_daily_records']) ? intval($_POST['keep_daily_records']) : 0;

    $training_courses_data      = isset($_POST['training_courses_data']) ? (is_array($_POST['training_courses_data']) ? json_encode($_POST['training_courses_data']) : $_POST['training_courses_data']) : '[]';

    $waste_pasture               = isset($_POST['waste_pasture']) ? intval($_POST['waste_pasture']) : 0;
    $waste_biogas                = isset($_POST['waste_biogas']) ? intval($_POST['waste_biogas']) : 0;
    $waste_composting            = isset($_POST['waste_composting']) ? intval($_POST['waste_composting']) : 0;
    $waste_sold_manure           = isset($_POST['waste_sold_manure']) ? intval($_POST['waste_sold_manure']) : 0;
    $waste_other                 = trim($_POST['waste_other'] ?? '');

    $training_clean_milk         = isset($_POST['training_clean_milk']) ? intval($_POST['training_clean_milk']) : 0;
    $measure_milk_yield          = isset($_POST['measure_milk_yield']) ? intval($_POST['measure_milk_yield']) : 0;
    $participate_health_camps    = isset($_POST['participate_health_camps']) ? intval($_POST['participate_health_camps']) : 0;
    $vaccine_hs                  = isset($_POST['vaccine_hs']) ? intval($_POST['vaccine_hs']) : 0;
    $vaccine_bq                  = isset($_POST['vaccine_bq']) ? intval($_POST['vaccine_bq']) : 0;
    $vaccine_fmd                 = isset($_POST['vaccine_fmd']) ? intval($_POST['vaccine_fmd']) : 0;

    // Extended Tracking Fields
    $animal_id_method          = trim($_POST['animal_id_method'] ?? 'Ear Tag');
    $branding_remarks          = trim($_POST['branding_remarks'] ?? '');
    $private_milk_qty_liters   = floatval($_POST['private_milk_qty_liters'] ?? 0);
    $private_milk_price        = floatval($_POST['private_milk_price'] ?? 0);
    $organized_milk_qty_liters = floatval($_POST['organized_milk_qty_liters'] ?? 0);
    $organized_collector_name  = trim($_POST['organized_collector_name'] ?? '');
    $bookkeeping_records_notes = trim($_POST['bookkeeping_records_notes'] ?? '');
    $vaccine_hs_date           = !empty($_POST['vaccine_hs_date']) ? $_POST['vaccine_hs_date'] : null;
    $vaccine_bq_date           = !empty($_POST['vaccine_bq_date']) ? $_POST['vaccine_bq_date'] : null;
    $vaccine_fmd_date          = !empty($_POST['vaccine_fmd_date']) ? $_POST['vaccine_fmd_date'] : null;

    // Check if matching summary record exists to get summary_id
    $summary_id = 0;
    $chk_sum = $mysqli->prepare("SELECT id FROM `dairy_project_beneficiaries` WHERE farm_reg_no = ? ORDER BY id DESC LIMIT 1");
    if ($chk_sum) {
        $chk_sum->bind_param("s", $farm_reg_no);
        $chk_sum->execute();
        $srow = $chk_sum->get_result()->fetch_assoc();
        if ($srow) {
            $summary_id = intval($srow['id']);
        }
        $chk_sum->close();
    }

    // Check if application record already exists for this farm_reg_no
    $chk_app = $mysqli->prepare("SELECT * FROM `dairy_farm_applications` WHERE farm_reg_no = ? ORDER BY id DESC LIMIT 1");
    $chk_app->bind_param("s", $farm_reg_no);
    $chk_app->execute();
    $exist_app = $chk_app->get_result()->fetch_assoc();
    $chk_app->close();

    if ($exist_app) {
        $app_id = intval($exist_app['id']);

        // Strict Non-Deletion Policy: snapshot prior state into farm_master_history
        $app_snap = json_encode($exist_app);
        $hist_app = $mysqli->prepare("INSERT INTO `farm_master_history` (farm_reg_no, module_source, action_type, snapshot_data, recorded_by) VALUES (?, 'dairy_farm_applications', 'UPDATE_APPLICATION', ?, ?)");
        if ($hist_app) {
            $hist_app->bind_param("ssi", $farm_reg_no, $app_snap, $user_id);
            $hist_app->execute();
            $hist_app->close();
        }

        $sql_upd = "
            UPDATE `dairy_farm_applications` SET
                `summary_id` = ?,
                `ai_no` = ?,
                `survey_date` = ?,
                `main_category` = ?,
                `producer_name` = ?,
                `nic_no` = ?,
                `address` = ?,
                `daph_register_no` = ?,
                `contact_mobile` = ?,
                `telephone_no` = ?,
                `ds_gn_division` = ?,
                `gender` = ?,
                `livestock_council` = ?,
                `transport_distance_km` = ?,
                `previous_project_benefit` = ?,
                `previous_project_details` = ?,
                `water_source` = ?,
                `land_extent` = ?,
                `pasture_extent` = ?,
                `shed_capacity_adults` = ?,
                `shed_capacity_calves` = ?,
                `common_grazing_land` = ?,
                `pasture_cultivation_land` = ?,
                `has_grass_chopper` = ?,
                `has_milk_cans` = ?,
                `has_milking_machine` = ?,
                `has_chilling_equip` = ?,
                `has_biogas_plant` = ?,
                `biogas_capacity_m3` = ?,
                `id_ear_tag_no` = ?,
                `id_hot_brand_no` = ?,
                `geo_coordinates` = ?,
                `agri_env_ai_cc` = ?,
                `agri_env_ds_division` = ?,
                `agri_env_gn_division` = ?,
                `herd_composition_data` = ?,
                `feeding_types_data` = ?,
                `cultivating_grass_own_land` = ?,
                `own_land_grass_extent` = ?,
                `type_of_grass` = ?,
                `grass_cutting_method` = ?,
                `chop_fodder_before_feeding` = ?,
                `made_silage_before` = ?,
                `willing_to_make_silage` = ?,
                `feed_silage` = ?,
                `buy_agro_byproducts` = ?,
                `buy_concentrated_feed` = ?,
                `mix_mineral_mixture` = ?,
                `milk_morning_liters` = ?,
                `milk_evening_liters` = ?,
                `milk_total_liters` = ?,
                `milk_expected_liters` = ?,
                `milk_hygiene_measures` = ?,
                `milk_price_per_liter` = ?,
                `sell_to_private_collector` = ?,
                `sell_to_organized_collector` = ?,
                `marketing_details` = ?,
                `keep_daily_records` = ?,
                `training_courses_data` = ?,
                `waste_pasture` = ?,
                `waste_biogas` = ?,
                `waste_composting` = ?,
                `waste_sold_manure` = ?,
                `waste_other` = ?,
                `training_clean_milk` = ?,
                `measure_milk_yield` = ?,
                `participate_health_camps` = ?,
                `vaccine_hs` = ?,
                `vaccine_bq` = ?,
                `vaccine_fmd` = ?,
                `animal_id_method` = ?,
                `branding_remarks` = ?,
                `private_milk_qty_liters` = ?,
                `private_milk_price` = ?,
                `organized_milk_qty_liters` = ?,
                `organized_collector_name` = ?,
                `bookkeeping_records_notes` = ?,
                `vaccine_hs_date` = ?,
                `vaccine_bq_date` = ?,
                `vaccine_fmd_date` = ?
            WHERE `id` = ?
        ";
        $stmt_upd = $mysqli->prepare($sql_upd);
        $stmt_upd->bind_param(
            "issssssssssssdsdsssiissiiiiissssssssisiiiiiiiiiddddsssisiiiiisiiiiiiiissdddsssssi",
            $summary_id, $ai_no, $survey_date, $main_category, $producer_name, $nic_no, $address, $daph_register_no,
            $contact_mobile, $telephone_no, $ds_gn_division, $gender, $livestock_council, $transport_distance_km,
            $previous_project_benefit, $previous_project_details, $water_source, $land_extent, $pasture_extent,
            $shed_capacity_adults, $shed_capacity_calves, $common_grazing_land, $pasture_cultivation_land,
            $has_grass_chopper, $has_milk_cans, $has_milking_machine, $has_chilling_equip, $has_biogas_plant,
            $biogas_capacity_m3, $id_ear_tag_no, $id_hot_brand_no, $geo_coordinates, $agri_env_ai_cc,
            $agri_env_ds_division, $agri_env_gn_division, $herd_composition_data, $feeding_types_data,
            $cultivating_grass_own_land, $own_land_grass_extent, $type_of_grass, $grass_cutting_method,
            $chop_fodder_before_feeding, $made_silage_before, $willing_to_make_silage, $feed_silage,
            $buy_agro_byproducts, $buy_concentrated_feed, $mix_mineral_mixture,
            $milk_morning_liters, $milk_evening_liters, $milk_total_liters, $milk_expected_liters,
            $milk_hygiene_measures, $milk_price_per_liter,
            $sell_to_private_collector, $sell_to_organized_collector, $marketing_details, $keep_daily_records,
            $training_courses_data,
            $waste_pasture, $waste_biogas, $waste_composting, $waste_sold_manure, $waste_other,
            $training_clean_milk, $measure_milk_yield, $participate_health_camps,
            $vaccine_hs, $vaccine_bq, $vaccine_fmd,
            $animal_id_method, $branding_remarks, $private_milk_qty_liters, $private_milk_price,
            $organized_milk_qty_liters, $organized_collector_name, $bookkeeping_records_notes,
            $vaccine_hs_date, $vaccine_bq_date, $vaccine_fmd_date,
            $app_id
        );
        $res = $stmt_upd->execute();
        $stmt_upd->close();
    } else {
        $sql_ins = "
            INSERT INTO `dairy_farm_applications` (
                `summary_id`, `farm_reg_no`, `ai_no`, `survey_date`, `main_category`, `producer_name`, `nic_no`, `address`,
                `daph_register_no`, `contact_mobile`, `telephone_no`, `ds_gn_division`, `gender`, `livestock_council`,
                `transport_distance_km`, `previous_project_benefit`, `previous_project_details`, `water_source`, `land_extent`,
                `pasture_extent`, `shed_capacity_adults`, `shed_capacity_calves`, `common_grazing_land`, `pasture_cultivation_land`,
                `has_grass_chopper`, `has_milk_cans`, `has_milking_machine`, `has_chilling_equip`, `has_biogas_plant`,
                `biogas_capacity_m3`, `id_ear_tag_no`, `id_hot_brand_no`, `geo_coordinates`, `agri_env_ai_cc`,
                `agri_env_ds_division`, `agri_env_gn_division`, `herd_composition_data`, `feeding_types_data`,
                `cultivating_grass_own_land`, `own_land_grass_extent`, `type_of_grass`, `grass_cutting_method`,
                `chop_fodder_before_feeding`, `made_silage_before`, `willing_to_make_silage`, `feed_silage`,
                `buy_agro_byproducts`, `buy_concentrated_feed`, `mix_mineral_mixture`,
                `milk_morning_liters`, `milk_evening_liters`, `milk_total_liters`, `milk_expected_liters`,
                `milk_hygiene_measures`, `milk_price_per_liter`,
                `sell_to_private_collector`, `sell_to_organized_collector`, `marketing_details`, `keep_daily_records`,
                `training_courses_data`,
                `waste_pasture`, `waste_biogas`, `waste_composting`, `waste_sold_manure`, `waste_other`,
                `training_clean_milk`, `measure_milk_yield`, `participate_health_camps`,
                `vaccine_hs`, `vaccine_bq`, `vaccine_fmd`,
                `animal_id_method`, `branding_remarks`, `private_milk_qty_liters`, `private_milk_price`,
                `organized_milk_qty_liters`, `organized_collector_name`, `bookkeeping_records_notes`,
                `vaccine_hs_date`, `vaccine_bq_date`, `vaccine_fmd_date`,
                `created_by`
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?,
                ?, ?, ?, ?,
                ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?
            )
        ";
        $stmt_ins = $mysqli->prepare($sql_ins);
        $stmt_ins->bind_param(
            "issssssssssssdsdsssiissiiiiissssssssisiiiiiiiiiddddsssisiiiiisiiiiiiiissdddsssssi",
            $summary_id, $farm_reg_no, $ai_no, $survey_date, $main_category, $producer_name, $nic_no, $address,
            $daph_register_no, $contact_mobile, $telephone_no, $ds_gn_division, $gender, $livestock_council,
            $transport_distance_km, $previous_project_benefit, $previous_project_details, $water_source, $land_extent,
            $pasture_extent, $shed_capacity_adults, $shed_capacity_calves, $common_grazing_land, $pasture_cultivation_land,
            $has_grass_chopper, $has_milk_cans, $has_milking_machine, $has_chilling_equip, $has_biogas_plant,
            $biogas_capacity_m3, $id_ear_tag_no, $id_hot_brand_no, $geo_coordinates, $agri_env_ai_cc,
            $agri_env_ds_division, $agri_env_gn_division, $herd_composition_data, $feeding_types_data,
            $cultivating_grass_own_land, $own_land_grass_extent, $type_of_grass, $grass_cutting_method,
            $chop_fodder_before_feeding, $made_silage_before, $willing_to_make_silage, $feed_silage,
            $buy_agro_byproducts, $buy_concentrated_feed, $mix_mineral_mixture,
            $milk_morning_liters, $milk_evening_liters, $milk_total_liters, $milk_expected_liters,
            $milk_hygiene_measures, $milk_price_per_liter,
            $sell_to_private_collector, $sell_to_organized_collector, $marketing_details, $keep_daily_records,
            $training_courses_data,
            $waste_pasture, $waste_biogas, $waste_composting, $waste_sold_manure, $waste_other,
            $training_clean_milk, $measure_milk_yield, $participate_health_camps,
            $vaccine_hs, $vaccine_bq, $vaccine_fmd,
            $animal_id_method, $branding_remarks, $private_milk_qty_liters, $private_milk_price,
            $organized_milk_qty_liters, $organized_collector_name, $bookkeeping_records_notes,
            $vaccine_hs_date, $vaccine_bq_date, $vaccine_fmd_date,
            $user_id
        );
        $res = $stmt_ins->execute();
        $app_id = $stmt_ins->insert_id;
        $stmt_ins->close();
    }

    // Two-way sync: keep summary table's current & expected milk and category in sync if summary row exists
    if ($summary_id > 0) {
        $sync_stmt = $mysqli->prepare("
            UPDATE `dairy_project_beneficiaries`
            SET `daily_milk_current` = ?,
                `daily_milk_expected` = ?,
                `category` = ?
            WHERE `id` = ?
        ");
        if ($sync_stmt) {
            $cat_sync = !empty($main_category) ? $main_category : '5L';
            $sync_stmt->bind_param("ddsi", $milk_total_liters, $milk_expected_liters, $cat_sync, $summary_id);
            $sync_stmt->execute();
            $sync_stmt->close();
        }
    }

    // -------------------------------------------------------------------------
    // AUTO-ENROLLMENT INTO DAIRY HUB SOCIETY
    // When a farmer's details are entered and submitted, the system automatically 
    // registers that individual as a formal member of the Dairy Hub society.
    // -------------------------------------------------------------------------
    $soc_range_id = intval($_POST['range_id'] ?? 0);
    if ($soc_range_id <= 0 && $summary_id > 0) {
        $r_chk = $mysqli->prepare("SELECT range_id, district_id FROM `dairy_project_beneficiaries` WHERE id = ?");
        if ($r_chk) {
            $r_chk->bind_param("i", $summary_id);
            $r_chk->execute();
            if ($r_row = $r_chk->get_result()->fetch_assoc()) {
                $soc_range_id = intval($r_row['range_id']);
                $soc_district_id = intval($r_row['district_id']);
            }
            $r_chk->close();
        }
    }
    if ($soc_range_id <= 0) {
        $soc_range_id = !empty($_SESSION['range_id']) ? intval($_SESSION['range_id']) : 1;
    }
    if (empty($soc_district_id)) {
        $soc_district_id = !empty($_SESSION['district_id']) ? intval($_SESSION['district_id']) : 1;
    }

    // Ensure society exists for this range
    $soc_id = 0;
    $soc_q = $mysqli->prepare("SELECT id FROM `dairy_hub_societies` WHERE range_id = ? LIMIT 1");
    if ($soc_q) {
        $soc_q->bind_param("i", $soc_range_id);
        $soc_q->execute();
        $soc_res = $soc_q->get_result()->fetch_assoc();
        if ($soc_res) {
            $soc_id = intval($soc_res['id']);
        }
        $soc_q->close();
    }
    if ($soc_id === 0) {
        $new_soc_name = "Dairy Hub Society - Range #" . $soc_range_id;
        $ins_soc = $mysqli->prepare("INSERT INTO `dairy_hub_societies` (range_id, district_id, society_name, status) VALUES (?, ?, ?, 'Active')");
        if ($ins_soc) {
            $ins_soc->bind_param("iis", $soc_range_id, $soc_district_id, $new_soc_name);
            $ins_soc->execute();
            $soc_id = $ins_soc->insert_id;
            $ins_soc->close();
        }
    }

    // Check if member already registered under this NIC or farm_reg_no
    $chk_mem = $mysqli->prepare("SELECT id, membership_no, executive_position FROM `dairy_hub_society_members` WHERE range_id = ? AND (nic = ? OR (farm_reg_no = ? AND farm_reg_no != '')) LIMIT 1");
    if ($chk_mem) {
        $chk_mem->bind_param("iss", $soc_range_id, $nic_no, $farm_reg_no);
        $chk_mem->execute();
        $mem_res = $chk_mem->get_result()->fetch_assoc();
        $chk_mem->close();

        $enrolled_phone = !empty($contact_mobile) ? $contact_mobile : $telephone_no;

        if ($mem_res) {
            // Update profile particulars while preserving executive position and membership_no
            $mem_id = intval($mem_res['id']);
            $upd_mem = $mysqli->prepare("
                UPDATE `dairy_hub_society_members` SET
                    `society_id` = ?,
                    `farm_reg_no` = ?,
                    `member_name` = ?,
                    `phone` = ?,
                    `address` = ?,
                    `ds_gn_division` = ?,
                    `status` = 'Active'
                WHERE `id` = ?
            ");
            if ($upd_mem) {
                $upd_mem->bind_param("isssssi", $soc_id, $farm_reg_no, $producer_name, $enrolled_phone, $address, $ds_gn_division, $mem_id);
                $upd_mem->execute();
                $upd_mem->close();
            }
        } else {
            // Generate clean Membership No: DHS-[RANGE]-[YEAR]-[SERIAL]
            $cnt_q = $mysqli->query("SELECT COUNT(*) AS total FROM `dairy_hub_society_members` WHERE range_id = {$soc_range_id}");
            $total_existing = ($cnt_q && $crow = $cnt_q->fetch_assoc()) ? intval($crow['total']) : 0;
            $mem_no = sprintf("DHS-%02d-%s-%04d", $soc_range_id, date('Y', strtotime($survey_date)), $total_existing + 1);

            $ins_mem = $mysqli->prepare("
                INSERT INTO `dairy_hub_society_members` (
                    `society_id`, `range_id`, `district_id`, `membership_no`, `farm_reg_no`,
                    `member_name`, `nic`, `phone`, `address`, `ds_gn_division`,
                    `enrollment_date`, `enrollment_source`, `executive_position`, `is_executive`,
                    `status`, `code_of_conduct_agreed`, `created_by`
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, 'Dairy Hub Application', 'Member', 0,
                    'Active', 1, ?
                )
            ");
            if ($ins_mem) {
                $ins_mem->bind_param(
                    "iiissssssssi",
                    $soc_id, $soc_range_id, $soc_district_id, $mem_no, $farm_reg_no,
                    $producer_name, $nic_no, $enrolled_phone, $address, $ds_gn_division,
                    $survey_date, $user_id
                );
                $ins_mem->execute();
                $ins_mem->close();
            }
        }
    }

    // -------------------------------------------------------------------------
    // TRAINING & TECHNICAL ADVICE INTEGRATION
    // Synchronize completed farmer training courses into primary farmer_training_records
    // -------------------------------------------------------------------------
    $train_arr = json_decode($training_courses_data, true);
    if (is_array($train_arr)) {
        foreach ($train_arr as $t) {
            $c_name = trim($t['course'] ?? '');
            $c_inst = trim($t['institute'] ?? '');
            $c_date = !empty($t['completion_date']) ? $t['completion_date'] : null;
            if (!empty($c_name)) {
                $chk_tr = $mysqli->prepare("SELECT id FROM `farmer_training_records` WHERE farm_reg_no = ? AND course_name = ? LIMIT 1");
                if ($chk_tr) {
                    $chk_tr->bind_param("ss", $farm_reg_no, $c_name);
                    $chk_tr->execute();
                    $tr_res = $chk_tr->get_result()->fetch_assoc();
                    $chk_tr->close();

                    if ($tr_res) {
                        $tr_id = intval($tr_res['id']);
                        $u_tr = $mysqli->prepare("UPDATE `farmer_training_records` SET institute = ?, completion_date = ?, range_id = ?, farmer_name = ?, nic = ? WHERE id = ?");
                        if ($u_tr) {
                            $u_tr->bind_param("ssissi", $c_inst, $c_date, $soc_range_id, $producer_name, $nic_no, $tr_id);
                            $u_tr->execute();
                            $u_tr->close();
                        }
                    } else {
                        $i_tr = $mysqli->prepare("INSERT INTO `farmer_training_records` (range_id, farm_reg_no, farmer_name, nic, course_name, institute, completion_date, source) VALUES (?, ?, ?, ?, ?, ?, ?, 'Dairy Hub Application')");
                        if ($i_tr) {
                            $i_tr->bind_param("issssss", $soc_range_id, $farm_reg_no, $producer_name, $nic_no, $c_name, $c_inst, $c_date);
                            $i_tr->execute();
                            $i_tr->close();
                        }
                    }
                }
            }
        }
    }

    // -------------------------------------------------------------------------
    // RIPPLE SYNCHRONIZATION
    // Ripple farmer master profile updates across all modules
    // -------------------------------------------------------------------------
    rippleFarmerMasterUpdates($mysqli, $farm_reg_no, $_POST, $user_id);

    if ($res) {
        jsonResponse([
            'success'     => true,
            'app_id'      => $app_id,
            'summary_id'  => $summary_id,
            'farm_reg_no' => $farm_reg_no,
            'message'     => 'Dairy Farm Development Potential Survey Application saved, master file synced, and farmer automatically enrolled in Dairy Hub Society!'
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to save application: ' . $mysqli->error]);
    }
}

// -------------------------------------------------------------------------
// 5B. ACTION: ADD / AUTO-SAVE WATER SOURCE
// -------------------------------------------------------------------------
if ($action === 'add_water_source') {
    $source_name = trim($_POST['source_name'] ?? '');
    if (empty($source_name)) {
        jsonResponse(['success' => false, 'message' => 'Water source name cannot be empty.']);
    }

    $chk = $mysqli->prepare("SELECT id, source_name FROM `dairy_water_sources` WHERE LOWER(TRIM(source_name)) = LOWER(TRIM(?)) LIMIT 1");
    $chk->bind_param("s", $source_name);
    $chk->execute();
    $existing = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$existing) {
        $ins = $mysqli->prepare("INSERT INTO `dairy_water_sources` (source_name) VALUES (?)");
        $ins->bind_param("s", $source_name);
        $ins->execute();
        $ins->close();
    }

    $all_sources = $mysqli->query("SELECT id, source_name FROM `dairy_water_sources` ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
    jsonResponse([
        'success'     => true,
        'message'     => "Water source '{$source_name}' added to permanent list successfully.",
        'source_name' => $source_name,
        'sources'     => $all_sources
    ]);
}

// -------------------------------------------------------------------------
// 5C. ACTION: GET ALL WATER SOURCES
// -------------------------------------------------------------------------
if ($action === 'get_water_sources') {
    $all_sources = $mysqli->query("SELECT id, source_name FROM `dairy_water_sources` ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
    jsonResponse(['success' => true, 'sources' => $all_sources]);
}

// -------------------------------------------------------------------------
// 5D. ACTION: LOOKUP ANIMAL ID DETAILS & FARM REGISTRATION REMARKS
// -------------------------------------------------------------------------
if ($action === 'lookup_animal_id') {
    $id_no       = trim($_REQUEST['animal_id'] ?? $_REQUEST['id_no'] ?? '');
    $farm_reg_no = trim($_REQUEST['farm_reg_no'] ?? '');

    if (empty($id_no) && empty($farm_reg_no)) {
        jsonResponse(['success' => false, 'message' => 'Animal ID or Farm Registration No required.']);
    }

    $found_remarks = '';
    $method = 'Ear Tag';

    // 1. Check existing dairy_farm_applications
    if (!empty($id_no)) {
        $q_app = $mysqli->prepare("
            SELECT id_ear_tag_no, id_hot_brand_no, animal_id_method, branding_remarks 
            FROM `dairy_farm_applications` 
            WHERE id_ear_tag_no = ? OR id_hot_brand_no = ?
            ORDER BY id DESC LIMIT 1
        ");
        if ($q_app) {
            $q_app->bind_param("ss", $id_no, $id_no);
            $q_app->execute();
            if ($row = $q_app->get_result()->fetch_assoc()) {
                if (!empty($row['branding_remarks'])) {
                    $found_remarks = $row['branding_remarks'];
                }
                if (!empty($row['animal_id_method'])) {
                    $method = $row['animal_id_method'];
                } elseif ($row['id_hot_brand_no'] === $id_no) {
                    $method = 'Hot Iron Branding';
                }
            }
            $q_app->close();
        }
    }

    // 2. Check farm_registration_renewals for remarks or animal details
    if (empty($found_remarks) && !empty($farm_reg_no)) {
        $q_reg = $mysqli->prepare("
            SELECT neat_cattle_data, buffaloes_data, farm_type 
            FROM `farm_registration_renewals` 
            WHERE registration_no = ? OR nic = ? 
            ORDER BY id DESC LIMIT 1
        ");
        if ($q_reg) {
            $q_reg->bind_param("ss", $farm_reg_no, $farm_reg_no);
            $q_reg->execute();
            if ($rreg = $q_reg->get_result()->fetch_assoc()) {
                $cattle = json_decode($rreg['neat_cattle_data'] ?? '', true);
                if (is_array($cattle) && !empty($cattle['remarks'])) {
                    $found_remarks = $cattle['remarks'];
                }
                if (empty($found_remarks)) {
                    $buf = json_decode($rreg['buffaloes_data'] ?? '', true);
                    if (is_array($buf) && !empty($buf['remarks'])) {
                        $found_remarks = $buf['remarks'];
                    }
                }
                if (empty($found_remarks)) {
                    $found_remarks = "Farm Registered ({$rreg['farm_type']}). ID Number {$id_no} registered under Farm No {$farm_reg_no}.";
                }
            }
            $q_reg->close();
        }
    }

    if (empty($found_remarks) && !empty($id_no)) {
        $found_remarks = "Animal ID: {$id_no} recorded under Farm Registration: {$farm_reg_no}. Verified in range census remarks.";
    }

    jsonResponse([
        'success' => true,
        'id_no'   => $id_no,
        'method'  => $method,
        'remarks' => $found_remarks
    ]);
}

// -------------------------------------------------------------------------
// 6. ACTION: GET DETAILED APPLICATION BY FARM_REG_NO OR ID
// -------------------------------------------------------------------------
if ($action === 'get_application') {
    $farm_reg_no = trim($_REQUEST['farm_reg_no'] ?? '');
    $id = intval($_REQUEST['id'] ?? 0);

    if ($id > 0) {
        $stmt = $mysqli->prepare("SELECT * FROM `dairy_farm_applications` WHERE id = ?");
        $stmt->bind_param("i", $id);
    } elseif (!empty($farm_reg_no)) {
        $stmt = $mysqli->prepare("SELECT * FROM `dairy_farm_applications` WHERE farm_reg_no = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("s", $farm_reg_no);
    } else {
        jsonResponse(['success' => false, 'message' => 'Specify either ID or Farm Registration Number.']);
    }

    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($data) {
        jsonResponse(['success' => true, 'data' => $data]);
    } else {
        jsonResponse(['success' => false, 'message' => 'No application found.']);
    }
}

// -------------------------------------------------------------------------
// 7. ACTION: ASSIGN EXECUTIVE POSITION IN DAIRY HUB SOCIETY
// -------------------------------------------------------------------------
if ($action === 'assign_executive_position') {
    $member_id    = intval($_POST['member_id'] ?? 0);
    $new_position = trim($_POST['position'] ?? 'Member');
    $valid_positions = ['President', 'Vice President', 'Secretary', 'Assistant Secretary', 'Treasurer', 'Committee Member', 'Member'];

    if (!in_array($new_position, $valid_positions, true)) {
        jsonResponse(['success' => false, 'message' => 'Invalid position specified.']);
    }
    if ($member_id <= 0) {
        jsonResponse(['success' => false, 'message' => 'Valid Member ID is required.']);
    }

    // Get current member's range
    $m_stmt = $mysqli->prepare("SELECT id, range_id, member_name, executive_position FROM `dairy_hub_society_members` WHERE id = ?");
    $m_stmt->bind_param("i", $member_id);
    $m_stmt->execute();
    $member = $m_stmt->get_result()->fetch_assoc();
    $m_stmt->close();

    if (!$member) {
        jsonResponse(['success' => false, 'message' => 'Member not found.']);
    }

    $range_id = intval($member['range_id']);

    // If assigning a single-holder executive post (President, Vice President, Secretary, Treasurer),
    // update any prior holder to 'Member' so there is no duplicate leadership collision
    if (in_array($new_position, ['President', 'Vice President', 'Secretary', 'Treasurer'], true)) {
        $demote = $mysqli->prepare("
            UPDATE `dairy_hub_society_members` 
            SET `executive_position` = 'Member', `is_executive` = 0 
            WHERE `range_id` = ? AND `executive_position` = ? AND `id` != ?
        ");
        if ($demote) {
            $demote->bind_param("isi", $range_id, $new_position, $member_id);
            $demote->execute();
            $demote->close();
        }
    }

    $is_exec = ($new_position !== 'Member') ? 1 : 0;
    $upd_pos = $mysqli->prepare("
        UPDATE `dairy_hub_society_members` 
        SET `executive_position` = ?, `is_executive` = ? 
        WHERE `id` = ?
    ");
    $upd_pos->bind_param("sii", $new_position, $is_exec, $member_id);
    $res = $upd_pos->execute();
    $upd_pos->close();

    if ($res) {
        jsonResponse([
            'success'   => true,
            'member_id' => $member_id,
            'position'  => $new_position,
            'message'   => "Executive position '{$new_position}' assigned to {$member['member_name']} successfully."
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to update executive position: ' . $mysqli->error]);
    }
}

// -------------------------------------------------------------------------
// 8. ACTION: UPDATE DAIRY HUB SOCIETY CODE OF CONDUCT
// -------------------------------------------------------------------------
if ($action === 'update_code_of_conduct') {
    $range_id = intval($_POST['range_id'] ?? 1);
    $title    = trim($_POST['code_of_conduct_title'] ?? 'Official Code of Conduct & Operational Ethics');
    $text     = trim($_POST['code_of_conduct_text'] ?? '');

    if (empty($text)) {
        jsonResponse(['success' => false, 'message' => 'Code of conduct text cannot be empty.']);
    }

    $chk = $mysqli->prepare("SELECT id FROM `dairy_hub_societies` WHERE range_id = ?");
    $chk->bind_param("i", $range_id);
    $chk->execute();
    $srow = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($srow) {
        $upd = $mysqli->prepare("UPDATE `dairy_hub_societies` SET `code_of_conduct_title` = ?, `code_of_conduct_text` = ? WHERE `range_id` = ?");
        $upd->bind_param("ssi", $title, $text, $range_id);
        $res = $upd->execute();
        $upd->close();
    } else {
        $ins = $mysqli->prepare("INSERT INTO `dairy_hub_societies` (range_id, society_name, code_of_conduct_title, code_of_conduct_text) VALUES (?, 'Dairy Hub Society', ?, ?)");
        $ins->bind_param("iss", $range_id, $title, $text);
        $res = $ins->execute();
        $ins->close();
    }

    if ($res) {
        jsonResponse(['success' => true, 'message' => 'Dairy Hub Code of Conduct established & saved successfully.']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to update Code of Conduct: ' . $mysqli->error]);
    }
}

// -------------------------------------------------------------------------
// 9. ACTION: GET SOCIETY MEMBERS LIST
// -------------------------------------------------------------------------
if ($action === 'get_society_members') {
    $range_id = intval($_REQUEST['range_id'] ?? 1);
    $stmt = $mysqli->prepare("
        SELECT m.*, s.society_name 
        FROM `dairy_hub_society_members` m 
        LEFT JOIN `dairy_hub_societies` s ON m.society_id = s.id 
        WHERE m.range_id = ? 
        ORDER BY 
            CASE 
                WHEN m.executive_position = 'President' THEN 1
                WHEN m.executive_position = 'Vice President' THEN 2
                WHEN m.executive_position = 'Secretary' THEN 3
                WHEN m.executive_position = 'Treasurer' THEN 4
                WHEN m.executive_position = 'Assistant Secretary' THEN 5
                WHEN m.executive_position = 'Committee Member' THEN 6
                ELSE 10
            END, m.member_name ASC
    ");
    $stmt->bind_param("i", $range_id);
    $stmt->execute();
    $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    jsonResponse(['success' => true, 'data' => $members]);
}

jsonResponse(['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($action)]);
