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
               total_neat_cattle, total_buffaloes, daily_milk_production, gps_location
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
                'farm_reg_no'   => $r['registration_no'] ?: $farm_reg_no,
                'farmer_name'   => $r['farmer_name'] ?: '',
                'nic'           => $r['nic'] ?: '',
                'address'       => $r['farmer_address'] ?: '',
                'phone'         => $r['telephone_no'] ?: '',
                'daily_milk'    => floatval($r['daily_milk_production'] ?? 0),
                'cattle_count'  => intval($r['total_neat_cattle'] ?? 0),
                'buffalo_count' => intval($r['total_buffaloes'] ?? 0),
                'gps_location'  => $r['gps_location'] ?: '',
                'ds_division'   => $r['ds_division'] ?: '',
                'vs_division'   => $r['vs_division'] ?: '',
                'gn_division'   => $r['gn_division'] ?: '',
                'district'      => $r['district'] ?: '',
                'range_id'      => intval($r['range_id'] ?? 0),
                'district_id'   => intval($r['district_id'] ?? 0),
                'source'        => 'Master Registry (farm_registration_renewals)'
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
                    'farm_reg_no'   => $r2['farm_registration_no'] ?: $farm_reg_no,
                    'farmer_name'   => $r2['full_name'] ?: '',
                    'nic'           => $r2['nic_no'] ?: '',
                    'address'       => $r2['location_address'] ?: '',
                    'phone'         => $r2['contact_no'] ?: '',
                    'daily_milk'    => 0.00,
                    'cattle_count'  => intval($r2['cattle_count'] ?? 0),
                    'buffalo_count' => intval($r2['buffalo_count'] ?? 0),
                    'gps_location'  => '',
                    'ds_division'   => '',
                    'vs_division'   => $r2['vs_division'] ?: '',
                    'gn_division'   => '',
                    'district'      => $r2['district'] ?: '',
                    'range_id'      => intval($r2['range_id'] ?? 0),
                    'district_id'   => intval($r2['district_id'] ?? 0),
                    'source'        => 'Farmers Directory'
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
            jsonResponse(['success' => true, 'id' => $id, 'message' => 'Beneficiary summary record updated successfully!']);
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
            // Link existing application if present
            $mysqli->query("UPDATE `dairy_farm_applications` SET `summary_id` = {$new_id} WHERE `farm_reg_no` = '" . $mysqli->real_escape_string($farm_reg_no) . "'");

            jsonResponse(['success' => true, 'id' => $new_id, 'message' => 'Beneficiary added to the Summary Approval Register successfully!']);
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
// 4. ACTION: DELETE SUMMARY RECORD
// -------------------------------------------------------------------------
if ($action === 'delete_summary') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid ID.']);
    }

    $stmt = $mysqli->prepare("DELETE FROM `dairy_project_beneficiaries` WHERE id = ?");
    $stmt->bind_param("i", $id);
    $res = $stmt->execute();
    $stmt->close();

    if ($res) {
        jsonResponse(['success' => true, 'message' => 'Beneficiary entry successfully deleted from summary.']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Error deleting record: ' . $mysqli->error]);
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
    $chk_app = $mysqli->prepare("SELECT id FROM `dairy_farm_applications` WHERE farm_reg_no = ? ORDER BY id DESC LIMIT 1");
    $chk_app->bind_param("s", $farm_reg_no);
    $chk_app->execute();
    $exist_app = $chk_app->get_result()->fetch_assoc();
    $chk_app->close();

    if ($exist_app) {
        $app_id = intval($exist_app['id']);
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
                `vaccine_fmd` = ?
            WHERE `id` = ?
        ";
        $stmt_upd = $mysqli->prepare($sql_upd);
        $stmt_upd->bind_param(
            "issssssssssssdsdsssiissiiiiissssssssisiiiiiiiiiddddsssisiiiiisiiiiiiii",
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
                `vaccine_hs`, `vaccine_bq`, `vaccine_fmd`, `created_by`
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
                ?, ?, ?, ?
            )
        ";
        $stmt_ins = $mysqli->prepare($sql_ins);
        $stmt_ins->bind_param(
            "issssssssssssdsdsssiissiiiiissssssssisiiiiiiiiiddddsssisiiiiisiiiiiiiii",
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
            $vaccine_hs, $vaccine_bq, $vaccine_fmd, $user_id
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

    if ($res) {
        jsonResponse([
            'success'     => true,
            'app_id'      => $app_id,
            'summary_id'  => $summary_id,
            'farm_reg_no' => $farm_reg_no,
            'message'     => 'Dairy Farm Development Potential Survey Application saved successfully!'
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to save application: ' . $mysqli->error]);
    }
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

jsonResponse(['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($action)]);
