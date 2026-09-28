<?php
session_start();
require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$allowed_roles = [
    'veterinary_surgeon',
    'government_veterinary_surgeon',
    'additional_veterinary_surgeon',
    'sms',
    'district_dd',
    'deputy_director_district',
    'deputy_director_hq_1',
    'provincial_director',
    'admin',
    'super_admin'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    die("Access denied: Invalid authentication clearance profile.");
}

$user_id = $_SESSION['user_id'] ?? null;
$session_range_id = $_SESSION['range_id'] ?? null;

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$report_year = intval($_POST['year'] ?? $_POST['report_year'] ?? $_GET['year'] ?? date('Y'));
$range_id = intval($_POST['range_id'] ?? $_GET['range_id'] ?? $session_range_id ?? 1);

// Clean descriptive indicator lookup (NO c.1, c.2 prefix tags)
$indicator_map = [
    'trainings_conducted'      => ['title' => 'Conducting trainings',      'indicator' => 'No of training conducted'],
    'farmers_sent_training'    => ['title' => 'Farmers send to training',  'indicator' => 'No of Farmer send to training'],
    'attending_training'       => ['title' => 'Attending training',       'indicator' => 'No of training attended'],
    'pasture_unit_est'         => ['title' => 'Est of pasture unit',       'indicator' => 'No of Acres Established'],
    'financial_access'         => ['title' => 'Assisting financial Access', 'indicator' => 'No of Bank loan approved'],
    'farmer_society_est'       => ['title' => 'Est of farmer society',     'indicator' => 'No. of farmer societies established'],
    'mobile_clinic_conducted'  => ['title' => 'Conducting mobile clinic',  'indicator' => 'No of mobile clinic conducted'],
    'exhibition_conducted'     => ['title' => 'Conducting exhibition',     'indicator' => 'No of exhibition Conducted'],
    'field_days_conducted'     => ['title' => 'Conducting field days',     'indicator' => 'No of field days conducted'],
    'poultry_farm_reg'         => ['title' => 'Poultry farm registration', 'indicator' => 'No of poultry farm registered'],
    'cattle_farm_reg'          => ['title' => 'Cattle farm registration',  'indicator' => 'No of cattle farm registered'],
    'new_farm_reg'             => ['title' => 'New farm registration',     'indicator' => 'No of New farm registered'],
    'monthly_reports'          => ['title' => 'Monthly reports',           'indicator' => 'No of Monthly report submitted'],
    'animal_identification'    => ['title' => 'Animal Identification',     'indicator' => 'No. of Animal Identification done'],
    'data_collection'          => ['title' => 'Data Collection',           'indicator' => 'No of Data Collection program done'],
];

function redirect_back($year, $range, $status, $msg) {
    $_SESSION['msg'] = $msg;
    $_SESSION['msg_type'] = ($status === 'success') ? 'success' : 'danger';
    
    $return_to = $_POST['return_to'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    
    if ($return_to === 'annual_targets' || strpos($referer, 'annual_targets.php') !== false) {
        header("Location: ../annual_targets.php?year={$year}&range_id={$range}#extensionServicesSection");
    } else {
        header("Location: ../extension_services.php?year={$year}&range_id={$range}&status={$status}&msg=" . urlencode($msg));
    }
    exit();
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. SAVE / UPDATE ANNUAL EXTENSION TARGETS
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'save_targets') {
    $t_trainings_conducted     = intval($_POST['target_trainings_conducted'] ?? 25);
    $t_farmers_sent_training   = intval($_POST['target_farmers_sent_training'] ?? 100);
    $t_attending_training      = intval($_POST['target_attending_training'] ?? 10);
    $t_pasture_unit_est        = intval($_POST['target_pasture_unit_est'] ?? 3);
    $t_financial_access        = intval($_POST['target_financial_access'] ?? 0);
    $t_farmer_society_est      = intval($_POST['target_farmer_society_est'] ?? 0);
    $t_mobile_clinic_conducted = intval($_POST['target_mobile_clinic_conducted'] ?? 24);
    $t_exhibition_conducted    = intval($_POST['target_exhibition_conducted'] ?? 3);
    $t_field_days_conducted    = intval($_POST['target_field_days_conducted'] ?? 50);
    $t_poultry_farm_reg        = intval($_POST['target_poultry_farm_reg'] ?? 100);
    $t_cattle_farm_reg         = intval($_POST['target_cattle_farm_reg'] ?? 100);
    $t_new_farm_reg            = intval($_POST['target_new_farm_reg'] ?? 12);
    $t_monthly_reports         = intval($_POST['target_monthly_reports'] ?? 12);
    $t_animal_identification   = intval($_POST['target_animal_identification'] ?? 100);
    $t_data_collection         = intval($_POST['target_data_collection'] ?? 5);

    $check_stmt = $mysqli->prepare("SELECT id FROM annual_extension_targets WHERE range_id = ? AND year = ?");
    $check_stmt->bind_param("ii", $range_id, $report_year);
    $check_stmt->execute();
    $existing = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    if ($existing) {
        $upd_stmt = $mysqli->prepare("
            UPDATE annual_extension_targets SET
                target_trainings_conducted = ?,
                target_farmers_sent_training = ?,
                target_attending_training = ?,
                target_pasture_unit_est = ?,
                target_financial_access = ?,
                target_farmer_society_est = ?,
                target_mobile_clinic_conducted = ?,
                target_exhibition_conducted = ?,
                target_field_days_conducted = ?,
                target_poultry_farm_reg = ?,
                target_cattle_farm_reg = ?,
                target_new_farm_reg = ?,
                target_monthly_reports = ?,
                target_animal_identification = ?,
                target_data_collection = ?
            WHERE range_id = ? AND year = ?
        ");
        $upd_stmt->bind_param(
            "iiiiiiiiiiiiiiiii",
            $t_trainings_conducted,
            $t_farmers_sent_training,
            $t_attending_training,
            $t_pasture_unit_est,
            $t_financial_access,
            $t_farmer_society_est,
            $t_mobile_clinic_conducted,
            $t_exhibition_conducted,
            $t_field_days_conducted,
            $t_poultry_farm_reg,
            $t_cattle_farm_reg,
            $t_new_farm_reg,
            $t_monthly_reports,
            $t_animal_identification,
            $t_data_collection,
            $range_id,
            $report_year
        );
        $upd_stmt->execute();
        $upd_stmt->close();
    } else {
        $ins_stmt = $mysqli->prepare("
            INSERT INTO annual_extension_targets (
                range_id, year,
                target_trainings_conducted,
                target_farmers_sent_training,
                target_attending_training,
                target_pasture_unit_est,
                target_financial_access,
                target_farmer_society_est,
                target_mobile_clinic_conducted,
                target_exhibition_conducted,
                target_field_days_conducted,
                target_poultry_farm_reg,
                target_cattle_farm_reg,
                target_new_farm_reg,
                target_monthly_reports,
                target_animal_identification,
                target_data_collection
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $ins_stmt->bind_param(
            "iiiiiiiiiiiiiiiii",
            $range_id,
            $report_year,
            $t_trainings_conducted,
            $t_farmers_sent_training,
            $t_attending_training,
            $t_pasture_unit_est,
            $t_financial_access,
            $t_farmer_society_est,
            $t_mobile_clinic_conducted,
            $t_exhibition_conducted,
            $t_field_days_conducted,
            $t_poultry_farm_reg,
            $t_cattle_farm_reg,
            $t_new_farm_reg,
            $t_monthly_reports,
            $t_animal_identification,
            $t_data_collection
        );
        $ins_stmt->execute();
        $ins_stmt->close();
    }

    redirect_back($report_year, $range_id, 'success', "Extension Services annual targets successfully saved for Year {$report_year}.");
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. LOG NEW EXTENSION SERVICE RECORD
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'create_record') {
    $activity_date    = trim($_POST['activity_date'] ?? date('Y-m-d'));
    $report_month     = intval($_POST['report_month'] ?? date('n', strtotime($activity_date)));
    $indicator_code   = trim($_POST['indicator_code'] ?? '');
    $meta             = $indicator_map[$indicator_code] ?? null;
    $indicator_name   = $meta ? $meta['title'] : trim($_POST['indicator_name'] ?? $indicator_code);
    $quantity         = intval($_POST['quantity'] ?? 0);
    $beneficiary_name = trim($_POST['beneficiary_name'] ?? '');
    $location         = trim($_POST['location'] ?? '');
    $remarks          = trim($_POST['remarks'] ?? '');

    if (empty($indicator_code) || $quantity <= 0) {
        redirect_back($report_year, $range_id, 'error', "Please select a valid indicator and positive quantity/count.");
    }

    $ins_stmt = $mysqli->prepare("
        INSERT INTO extension_service_records (
            range_id, report_year, report_month, activity_date,
            indicator_code, indicator_name, quantity,
            beneficiary_name, location, remarks, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $ins_stmt->bind_param(
        "iiisssisssi",
        $range_id,
        $report_year,
        $report_month,
        $activity_date,
        $indicator_code,
        $indicator_name,
        $quantity,
        $beneficiary_name,
        $location,
        $remarks,
        $user_id
    );
    $ins_stmt->execute();
    $ins_stmt->close();

    redirect_back($report_year, $range_id, 'success', "Successfully recorded {$quantity} for '{$indicator_name}'.");
}

// ─────────────────────────────────────────────────────────────────────────────
// 3. UPDATE EXTENSION SERVICE RECORD
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'update_record') {
    $record_id        = intval($_POST['id'] ?? 0);
    $activity_date    = trim($_POST['activity_date'] ?? date('Y-m-d'));
    $report_month     = intval($_POST['report_month'] ?? date('n', strtotime($activity_date)));
    $indicator_code   = trim($_POST['indicator_code'] ?? '');
    $meta             = $indicator_map[$indicator_code] ?? null;
    $indicator_name   = $meta ? $meta['title'] : trim($_POST['indicator_name'] ?? $indicator_code);
    $quantity         = intval($_POST['quantity'] ?? 0);
    $beneficiary_name = trim($_POST['beneficiary_name'] ?? '');
    $location         = trim($_POST['location'] ?? '');
    $remarks          = trim($_POST['remarks'] ?? '');

    if ($record_id <= 0 || empty($indicator_code) || $quantity <= 0) {
        redirect_back($report_year, $range_id, 'error', "Invalid record parameters for update.");
    }

    $upd_stmt = $mysqli->prepare("
        UPDATE extension_service_records SET
            activity_date = ?,
            report_month = ?,
            indicator_code = ?,
            indicator_name = ?,
            quantity = ?,
            beneficiary_name = ?,
            location = ?,
            remarks = ?
        WHERE id = ? AND range_id = ?
    ");
    $upd_stmt->bind_param(
        "sississsii",
        $activity_date,
        $report_month,
        $indicator_code,
        $indicator_name,
        $quantity,
        $beneficiary_name,
        $location,
        $remarks,
        $record_id,
        $range_id
    );
    $upd_stmt->execute();
    $upd_stmt->close();

    redirect_back($report_year, $range_id, 'success', "Extension record #{$record_id} successfully updated.");
}

// ─────────────────────────────────────────────────────────────────────────────
// 4. DELETE EXTENSION SERVICE RECORD
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'delete_record') {
    $record_id = intval($_POST['id'] ?? $_GET['id'] ?? 0);

    if ($record_id > 0) {
        $del_stmt = $mysqli->prepare("DELETE FROM extension_service_records WHERE id = ? AND range_id = ?");
        $del_stmt->bind_param("ii", $record_id, $range_id);
        $del_stmt->execute();
        $del_stmt->close();

        redirect_back($report_year, $range_id, 'success', "Record #{$record_id} has been deleted.");
    }

    redirect_back($report_year, $range_id, 'error', "Invalid record specified for deletion.");
}

// Fallback
redirect_back($report_year, $range_id, 'error', "Unrecognized action requested.");
