<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../config/db_connect.php';
require_once __DIR__ . '/processors/dairy_db_migration.php';

/** @var mysqli $mysqli */
global $mysqli;
ensure_dairy_project_tables($mysqli);

// 1. Session and Role Guard
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
    header("Location: ../../../../index.php");
    exit();
}

// 2. Fetch parameters
$param_farm_reg_no = trim($_GET['farm_reg_no'] ?? '');
$param_summary_id  = isset($_GET['summary_id']) && is_numeric($_GET['summary_id']) ? intval($_GET['summary_id']) : 0;
$param_app_id      = isset($_GET['app_id']) && is_numeric($_GET['app_id']) ? intval($_GET['app_id']) : 0;

$app_data = null;
$summary_data = null;
$farmer_data = null;

// If summary_id provided, get summary record
if ($param_summary_id > 0) {
    $sum_stmt = $mysqli->prepare("SELECT * FROM `dairy_project_beneficiaries` WHERE id = ?");
    if ($sum_stmt) {
        $sum_stmt->bind_param("i", $param_summary_id);
        $sum_stmt->execute();
        $summary_data = $sum_stmt->get_result()->fetch_assoc();
        $sum_stmt->close();
        if ($summary_data && empty($param_farm_reg_no)) {
            $param_farm_reg_no = $summary_data['farm_reg_no'];
        }
    }
}

// Check for existing application by ID or farm_reg_no
if ($param_app_id > 0) {
    $app_stmt = $mysqli->prepare("SELECT * FROM `dairy_farm_applications` WHERE id = ?");
    if ($app_stmt) {
        $app_stmt->bind_param("i", $param_app_id);
        $app_stmt->execute();
        $app_data = $app_stmt->get_result()->fetch_assoc();
        $app_stmt->close();
        if ($app_data && empty($param_farm_reg_no)) {
            $param_farm_reg_no = $app_data['farm_reg_no'];
        }
    }
} elseif (!empty($param_farm_reg_no)) {
    $app_stmt = $mysqli->prepare("SELECT * FROM `dairy_farm_applications` WHERE farm_reg_no = ? ORDER BY id DESC LIMIT 1");
    if ($app_stmt) {
        $app_stmt->bind_param("s", $param_farm_reg_no);
        $app_stmt->execute();
        $app_data = $app_stmt->get_result()->fetch_assoc();
        $app_stmt->close();
    }
}

// If no application yet, and no summary yet, search summary by farm_reg_no
if (!$summary_data && !empty($param_farm_reg_no)) {
    $sum_stmt = $mysqli->prepare("SELECT * FROM `dairy_project_beneficiaries` WHERE farm_reg_no = ? ORDER BY id DESC LIMIT 1");
    if ($sum_stmt) {
        $sum_stmt->bind_param("s", $param_farm_reg_no);
        $sum_stmt->execute();
        $summary_data = $sum_stmt->get_result()->fetch_assoc();
        $sum_stmt->close();
    }
}

// Fallback lookup from Master Registry (farm_registration_renewals)
if (!empty($param_farm_reg_no)) {
    $m_stmt = $mysqli->prepare("
        SELECT * FROM `farm_registration_renewals` 
        WHERE registration_no = ? OR nic = ? 
        ORDER BY id DESC LIMIT 1
    ");
    if ($m_stmt) {
        $m_stmt->bind_param("ss", $param_farm_reg_no, $param_farm_reg_no);
        $m_stmt->execute();
        $farmer_data = $m_stmt->get_result()->fetch_assoc();
        $m_stmt->close();
    }
}

// Decode JSON fields if present in app_data
$herd_rows = [
    'milking_cows'     => ['cow_pure' => 0, 'cow_nonpure' => 0, 'buf_pure' => 0, 'buf_nonpure' => 0],
    'non_milking_cows' => ['cow_pure' => 0, 'cow_nonpure' => 0, 'buf_pure' => 0, 'buf_nonpure' => 0],
    'heifers'          => ['cow_pure' => 0, 'cow_nonpure' => 0, 'buf_pure' => 0, 'buf_nonpure' => 0],
    'female_calves'    => ['cow_pure' => 0, 'cow_nonpure' => 0, 'buf_pure' => 0, 'buf_nonpure' => 0],
    'male_calves'      => ['cow_pure' => 0, 'cow_nonpure' => 0, 'buf_pure' => 0, 'buf_nonpure' => 0],
    'bulls'            => ['cow_pure' => 0, 'cow_nonpure' => 0, 'buf_pure' => 0, 'buf_nonpure' => 0],
];

if (!empty($app_data['herd_composition_data'])) {
    $decoded_herd = json_decode($app_data['herd_composition_data'], true);
    if (is_array($decoded_herd)) {
        foreach ($decoded_herd as $k => $vals) {
            if (isset($herd_rows[$k]) && is_array($vals)) {
                $herd_rows[$k] = array_merge($herd_rows[$k], $vals);
            }
        }
    }
}

$feed_types = [
    'napier_co3'      => ['mainly_fed' => 'No', 'expenditure' => 0],
    'cultivated_grass'=> ['mainly_fed' => 'No', 'expenditure' => 0],
    'commercial_feed' => ['mainly_fed' => 'No', 'expenditure' => 0],
    'other_fodder'    => ['mainly_fed' => 'No', 'expenditure' => 0],
];

if (!empty($app_data['feeding_types_data'])) {
    $decoded_feed = json_decode($app_data['feeding_types_data'], true);
    if (is_array($decoded_feed)) {
        foreach ($decoded_feed as $k => $vals) {
            if (isset($feed_types[$k]) && is_array($vals)) {
                $feed_types[$k] = array_merge($feed_types[$k], $vals);
            }
        }
    }
}

$training_courses = [
    ['course' => '', 'institute' => ''],
    ['course' => '', 'institute' => '']
];

if (!empty($app_data['training_courses_data'])) {
    $decoded_train = json_decode($app_data['training_courses_data'], true);
    if (is_array($decoded_train) && count($decoded_train) > 0) {
        $training_courses = $decoded_train;
    }
}

// Prefill values
$val_farm_reg_no = $app_data['farm_reg_no'] ?? ($summary_data['farm_reg_no'] ?? ($farmer_data['registration_no'] ?? $param_farm_reg_no));
$val_name        = $app_data['producer_name'] ?? ($summary_data['beneficiary_name'] ?? ($farmer_data['farmer_name'] ?? ''));
$val_nic         = $app_data['nic_no'] ?? ($summary_data['nic'] ?? ($farmer_data['nic'] ?? ''));
$val_phone       = $app_data['contact_mobile'] ?? ($summary_data['phone'] ?? ($farmer_data['telephone_no'] ?? ''));
$val_tel         = $app_data['telephone_no'] ?? ($farmer_data['telephone_no'] ?? '');
$val_address     = $app_data['address'] ?? ($summary_data['address'] ?? ($farmer_data['farmer_address'] ?? ''));
$val_daph_no     = $app_data['daph_register_no'] ?? '';
$val_ai_no       = $app_data['ai_no'] ?? ($farmer_data['vs_division'] ?? '');
$val_survey_date = $app_data['survey_date'] ?? date('Y-m-d');
$val_main_cat    = $app_data['main_category'] ?? ($summary_data['category'] ?? '05 Liters');
$val_gender      = $app_data['gender'] ?? 'Male';
$val_ds_gn       = $app_data['ds_gn_division'] ?? (trim(($farmer_data['ds_division'] ?? '') . ' / ' . ($farmer_data['gn_division'] ?? ''), ' /'));
$val_coords      = $app_data['geo_coordinates'] ?? ($farmer_data['gps_location'] ?? '');
$val_council     = $app_data['livestock_council'] ?? 'No';
$val_dist_km     = $app_data['transport_distance_km'] ?? '0.00';
$val_prev_ben    = $app_data['previous_project_benefit'] ?? 'No';
$val_prev_det    = $app_data['previous_project_details'] ?? '';
$val_water       = $app_data['water_source'] ?? 'Well';
$val_land_ext    = $app_data['land_extent'] ?? '';
$val_past_ext    = $app_data['pasture_extent'] ?? '';
$val_shed_adults = $app_data['shed_capacity_adults'] ?? 0;
$val_shed_calves = $app_data['shed_capacity_calves'] ?? 0;
$val_grazing     = $app_data['common_grazing_land'] ?? '';
$val_past_land   = $app_data['pasture_cultivation_land'] ?? '';
$val_chopper     = $app_data['has_grass_chopper'] ?? 0;
$val_cans        = $app_data['has_milk_cans'] ?? 0;
$val_machine     = $app_data['has_milking_machine'] ?? 0;
$val_chilling    = $app_data['has_chilling_equip'] ?? 0;
$val_biogas      = $app_data['has_biogas_plant'] ?? 0;
$val_biogas_cap  = $app_data['biogas_capacity_m3'] ?? '';
$val_tag_no      = $app_data['id_ear_tag_no'] ?? '';
$val_brand_no    = $app_data['id_hot_brand_no'] ?? '';
$val_ai_cc       = $app_data['agri_env_ai_cc'] ?? ($farmer_data['vs_division'] ?? '');
$val_ds_div      = $app_data['agri_env_ds_division'] ?? ($farmer_data['ds_division'] ?? '');
$val_gn_div      = $app_data['agri_env_gn_division'] ?? ($farmer_data['gn_division'] ?? '');

$val_milk_morn   = $app_data['milk_morning_liters'] ?? 0.0;
$val_milk_eve    = $app_data['milk_evening_liters'] ?? 0.0;
$val_milk_tot    = $app_data['milk_total_liters'] ?? ($summary_data['daily_milk_current'] ?? ($farmer_data['daily_milk_production'] ?? 0.0));
$val_milk_exp    = $app_data['milk_expected_liters'] ?? ($summary_data['daily_milk_expected'] ?? 0.0);
$val_hygiene     = $app_data['milk_hygiene_measures'] ?? '';
$val_milk_price  = $app_data['milk_price_per_liter'] ?? 0.0;

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">

<style>
    /* Government Document Survey Theme matching reference design */
    body {
        background-color: #555555;
    }
    .survey-container {
        max-width: 960px;
        margin: 20px auto 50px auto;
        background-color: #d1d5db;
        box-shadow: 0 10px 30px rgba(0,0,0,0.35);
        border: 1px solid #9ca3af;
    }
    .survey-header-bar {
        background-color: #3f3f46;
        color: #ffffff;
        padding: 16px 20px;
        border-bottom: 2px solid #27272a;
    }
    .survey-section-title {
        color: #b91c1c;
        font-weight: 700;
        font-size: 1.05rem;
        margin-bottom: 12px;
        border-bottom: 1px dashed #b91c1c;
        padding-bottom: 4px;
    }
    .survey-block {
        background-color: #d1d5db;
        padding: 18px 24px;
        border-bottom: 2px solid #9ca3af;
    }
    .form-label-custom {
        font-weight: 600;
        color: #1f2937;
        font-size: 0.875rem;
        margin-bottom: 4px;
    }
    .form-control-custom, .form-select-custom {
        background-color: #ffffff !important;
        border: 1px solid #9ca3af !important;
        border-radius: 4px;
        padding: 5px 10px;
        font-size: 0.875rem;
        color: #111827;
    }
    .form-control-custom:focus, .form-select-custom:focus {
        border-color: #820100 !important;
        box-shadow: 0 0 0 2px rgba(130, 1, 0, 0.2) !important;
    }
    .radio-card-group .form-check-label {
        font-weight: 600;
        color: #1f2937;
        font-size: 0.875rem;
    }
    .survey-table {
        background-color: #ffffff;
        border: 1px solid #9ca3af;
        font-size: 0.825rem;
        width: 100%;
        margin-bottom: 0;
    }
    .survey-table th {
        background-color: #d1d5db;
        color: #111827;
        font-weight: 700;
        text-align: center;
        vertical-align: middle;
        border: 1px solid #9ca3af;
        padding: 6px 8px;
    }
    .survey-table td {
        border: 1px solid #9ca3af;
        vertical-align: middle;
        padding: 4px 6px;
        background-color: #ffffff;
    }
    .survey-table input[type="number"], .survey-table input[type="text"] {
        background-color: #ffffff;
        border: 1px solid #d1d5db;
        border-radius: 3px;
        padding: 3px 6px;
        text-align: center;
        width: 100%;
        font-size: 0.825rem;
    }
    .btn-submit-survey {
        background-color: #1d4ed8;
        color: #ffffff;
        font-weight: 700;
        padding: 10px 32px;
        font-size: 1rem;
        border: none;
        border-radius: 4px;
        box-shadow: 0 4px 10px rgba(29, 78, 216, 0.3);
    }
    .btn-submit-survey:hover {
        background-color: #1e40af;
        color: #ffffff;
    }
    @media print {
        body {
            background-color: #ffffff !important;
        }
        .no-print, header, nav, .sb-sidenav, footer {
            display: none !important;
        }
        .survey-container {
            margin: 0;
            max-width: 100% !important;
            box-shadow: none !important;
            border: none !important;
            background-color: #ffffff !important;
        }
        .survey-block {
            background-color: #ffffff !important;
            border-bottom: 1px solid #000000 !important;
            page-break-inside: avoid;
        }
        .survey-header-bar {
            background-color: #111827 !important;
            color: #ffffff !important;
            -webkit-print-color-adjust: exact;
        }
    }
</style>

<div class="container-fluid px-0 py-2">

    <!-- Top floating navigation bar (no-print) -->
    <div class="container-fluid px-4 py-2 no-print bg-white border-bottom shadow-sm d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <span class="badge bg-danger me-2">National Dairy Production Programme</span>
            <span class="fw-bold text-dark">Information Collection on Dairy Farm Development Potential</span>
            <?php if (!empty($summary_data)): ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle ms-2">
                    <i class="bi bi-link-45deg"></i> Linked to Summary Sheet Record #<?= $summary_data['id'] ?>
                </span>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <a href="dairy_project_summary.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Summary Sheet
            </a>
            <button type="button" class="btn btn-sm btn-outline-dark" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print / PDF
            </button>
            <button type="button" class="btn btn-sm btn-primary fw-bold px-3" onclick="$('#dairyApplicationForm').submit()">
                <i class="bi bi-save me-1"></i> Save Form
            </button>
        </div>
    </div>

    <!-- Main Survey Document Container -->
    <div class="survey-container">
        
        <!-- Header banner exactly matching the reference form -->
        <div class="survey-header-bar text-center">
            <h4 class="fw-bold mb-1 letter-spacing-1">Information Collection on Dairy Farm Development Potential - <?= date('Y') ?></h4>
            <div class="small opacity-75">Department of Animal Production &amp; Health &middot; Eastern Province, Sri Lanka</div>
        </div>

        <form id="dairyApplicationForm">
            <input type="hidden" name="action" value="save_application">
            <input type="hidden" name="summary_id" value="<?= htmlspecialchars($param_summary_id) ?>">

            <!-- ========================================================================= -->
            <!-- SECTION 1: PARTICULARS OF MILK PRODUCER                                   -->
            <!-- ========================================================================= -->
            <div class="survey-block">
                <div class="survey-section-title">
                    <i class="bi bi-person-lines-fill me-1"></i> Particulars of Milk Producer
                </div>

                <!-- Top Row: General Info / AI No / Date -->
                <div class="row g-2 align-items-center mb-3 p-2 bg-light rounded border border-secondary border-opacity-25">
                    <div class="col-md-5">
                        <span class="fw-bold text-dark small">General Info / Fill the following Information:</span>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white fw-semibold">AI No</span>
                            <input type="text" name="ai_no" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($val_ai_no) ?>" placeholder="AI Center / Range">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white fw-semibold">Date</span>
                            <input type="date" name="survey_date" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($val_survey_date) ?>">
                        </div>
                    </div>
                </div>

                <!-- Main Category Selection -->
                <div class="mb-3 p-2 bg-light rounded border border-secondary border-opacity-25">
                    <label class="form-label-custom d-block mb-1">
                        Main Category of recording milk production farmer:
                    </label>
                    <div class="d-flex flex-wrap gap-4 radio-card-group">
                        <?php
                        $categories = ['05 Liters', '10 Liters', '20 Liters', '50 Liters', '100 Liters'];
                        foreach ($categories as $cat):
                            $checked = (strpos($val_main_cat, str_replace(' Liters', '', $cat)) !== false || $val_main_cat === $cat) ? 'checked' : '';
                        ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="main_category" id="cat_<?= str_replace(' ', '_', $cat) ?>" value="<?= $cat ?>" <?= $checked ?>>
                                <label class="form-check-label" for="cat_<?= str_replace(' ', '_', $cat) ?>"><?= $cat ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Numbered Form Fields 01 - 12 -->
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label-custom">01. Farmer Registration No <span class="text-danger">*</span></label>
                    </div>
                    <div class="col-md-8">
                        <div class="input-group input-group-sm">
                            <input type="text" id="app_farm_reg_no" name="farm_reg_no" class="form-control form-control-sm form-control-custom font-monospace fw-bold" value="<?= htmlspecialchars($val_farm_reg_no) ?>" placeholder="e.g. 530112345 or FRN-AMP-001" required>
                            <button class="btn btn-sm btn-dark no-print" type="button" id="btnAppAutoFetch" title="Auto-fetch profile from Master Registry">
                                <i class="bi bi-cloud-arrow-down-fill me-1"></i> Auto-Fetch
                            </button>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-custom">02. Full name of the milk producer <span class="text-danger">*</span></label>
                    </div>
                    <div class="col-md-8">
                        <input type="text" id="app_producer_name" name="producer_name" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($val_name) ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-custom">03. NIC No <span class="text-danger">*</span></label>
                    </div>
                    <div class="col-md-8">
                        <input type="text" id="app_nic_no" name="nic_no" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($val_nic) ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-custom">04. Address of the producer <span class="text-danger">*</span></label>
                    </div>
                    <div class="col-md-8">
                        <textarea id="app_address" name="address" rows="2" class="form-control form-control-sm form-control-custom" required><?= htmlspecialchars($val_address) ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-custom">05. Register Number in DAPH</label>
                    </div>
                    <div class="col-md-8">
                        <input type="text" name="daph_register_no" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($val_daph_no) ?>" placeholder="Optional DAPH Reg No">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-custom">06. Contact Mobile Number</label>
                    </div>
                    <div class="col-md-8">
                        <input type="tel" id="app_contact_mobile" name="contact_mobile" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($val_phone) ?>" placeholder="e.g. 0771234567">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-custom">07. Telephone Number</label>
                    </div>
                    <div class="col-md-8">
                        <input type="tel" id="app_telephone_no" name="telephone_no" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($val_tel) ?>" placeholder="Landline / Alternate">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-custom">08. DS Division / GN Division</label>
                    </div>
                    <div class="col-md-8">
                        <input type="text" id="app_ds_gn_division" name="ds_gn_division" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($val_ds_gn) ?>" placeholder="e.g. Sammanthurai / GN-12">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-custom">09. Gender</label>
                    </div>
                    <div class="col-md-8">
                        <div class="d-flex gap-4 radio-card-group pt-1">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="gender_m" value="Male" <?= ($val_gender === 'Male') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="gender_m">Male</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="gender_f" value="Female" <?= ($val_gender === 'Female') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="gender_f">Female</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-custom">10. Livestock Council</label>
                    </div>
                    <div class="col-md-8">
                        <select name="livestock_council" class="form-select form-select-sm form-select-custom" style="max-width: 140px;">
                            <option value="Yes" <?= ($val_council === 'Yes') ? 'selected' : '' ?>>Yes</option>
                            <option value="No" <?= ($val_council === 'No') ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-custom">11. Distance to transport milk to milk collection point?</label>
                    </div>
                    <div class="col-md-8">
                        <div class="input-group input-group-sm" style="max-width: 220px;">
                            <input type="number" step="0.1" name="transport_distance_km" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($val_dist_km) ?>">
                            <span class="input-group-text bg-white">Km</span>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-custom">12. Are you getting benefits through this project before or under any other project?</label>
                    </div>
                    <div class="col-md-8">
                        <div class="d-flex align-items-center gap-3">
                            <select name="previous_project_benefit" class="form-select form-select-sm form-select-custom" style="max-width: 140px;">
                                <option value="No" <?= ($val_prev_ben === 'No') ? 'selected' : '' ?>>No</option>
                                <option value="Yes" <?= ($val_prev_ben === 'Yes') ? 'selected' : '' ?>>Yes</option>
                            </select>
                            <input type="text" name="previous_project_details" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($val_prev_det) ?>" placeholder="Details of Previous Projects / Other Projects if Yes">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- SECTION 02: FARM DETAILS                                                  -->
            <!-- ========================================================================= -->
            <div class="survey-block">
                <div class="survey-section-title">
                    <i class="bi bi-house-door-fill me-1"></i> 02. Farm Details
                </div>

                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label-custom">2.1 Farm Registration Number</label>
                    </div>
                    <div class="col-md-8">
                        <input type="text" id="app_farm_reg_display" class="form-control form-control-sm form-control-custom font-monospace" value="<?= htmlspecialchars($val_farm_reg_no) ?>" readonly style="background-color: #f3f4f6 !important;">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-custom">2.2 Water Source / Facilities</label>
                    </div>
                    <div class="col-md-8">
                        <select name="water_source" class="form-select form-select-sm form-select-custom" style="max-width: 220px;">
                            <option value="Well" <?= ($val_water === 'Well') ? 'selected' : '' ?>>Well</option>
                            <option value="Pipe borne" <?= ($val_water === 'Pipe borne') ? 'selected' : '' ?>>Pipe borne</option>
                            <option value="Tube Well" <?= ($val_water === 'Tube Well') ? 'selected' : '' ?>>Tube Well</option>
                            <option value="River / Stream" <?= ($val_water === 'River / Stream') ? 'selected' : '' ?>>River / Stream</option>
                            <option value="Other" <?= ($val_water === 'Other') ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-custom">2.3 Extent of land owned by the farmer</label>
                    </div>
                    <div class="col-md-8">
                        <input type="text" name="land_extent" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($val_land_ext) ?>" placeholder="e.g. 2 Acres / 20 Perches">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-custom">2.4 Pasture Extent</label>
                    </div>
                    <div class="col-md-8">
                        <input type="text" name="pasture_extent" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($val_past_ext) ?>" placeholder="e.g. 1 Acre">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-custom">2.5 Capacity of cattle shed (no of animals it can hold)</label>
                    </div>
                    <div class="col-md-8">
                        <div class="row g-2" style="max-width: 400px;">
                            <div class="col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">Adult animals</span>
                                    <input type="number" min="0" name="shed_capacity_adults" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($val_shed_adults) ?>">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">Calves</span>
                                    <input type="number" min="0" name="shed_capacity_calves" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($val_shed_calves) ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-custom">2.6 Common grazing land or cultivated (producing) land available with the farmer</label>
                    </div>
                    <div class="col-md-8">
                        <input type="text" name="common_grazing_land" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($val_grazing) ?>" placeholder="Extent or description">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-custom">2.7 Total land available for pasture cultivation</label>
                    </div>
                    <div class="col-md-8">
                        <input type="text" name="pasture_cultivation_land" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($val_past_land) ?>" placeholder="e.g. 1.5 Acres">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-custom">2.8 Machinery and Equipment</label>
                    </div>
                    <div class="col-md-8">
                        <div class="p-2 bg-light rounded border border-secondary border-opacity-25 small">
                            <div class="row g-2 align-items-center mb-1">
                                <div class="col-5">Grass chopper:</div>
                                <div class="col-7">
                                    <div class="d-flex gap-3">
                                        <label><input type="radio" name="has_grass_chopper" value="1" <?= ($val_chopper == 1) ? 'checked' : '' ?>> Yes</label>
                                        <label><input type="radio" name="has_grass_chopper" value="0" <?= ($val_chopper == 0) ? 'checked' : '' ?>> No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-2 align-items-center mb-1">
                                <div class="col-5">Milk cans / Churn:</div>
                                <div class="col-7">
                                    <div class="d-flex gap-3">
                                        <label><input type="radio" name="has_milk_cans" value="1" <?= ($val_cans == 1) ? 'checked' : '' ?>> Yes</label>
                                        <label><input type="radio" name="has_milk_cans" value="0" <?= ($val_cans == 0) ? 'checked' : '' ?>> No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-2 align-items-center mb-1">
                                <div class="col-5">Milking machine:</div>
                                <div class="col-7">
                                    <div class="d-flex gap-3">
                                        <label><input type="radio" name="has_milking_machine" value="1" <?= ($val_machine == 1) ? 'checked' : '' ?>> Yes</label>
                                        <label><input type="radio" name="has_milking_machine" value="0" <?= ($val_machine == 0) ? 'checked' : '' ?>> No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-2 align-items-center mb-1">
                                <div class="col-5">Milk chilling equipment:</div>
                                <div class="col-7">
                                    <div class="d-flex gap-3">
                                        <label><input type="radio" name="has_chilling_equip" value="1" <?= ($val_chilling == 1) ? 'checked' : '' ?>> Yes</label>
                                        <label><input type="radio" name="has_chilling_equip" value="0" <?= ($val_chilling == 0) ? 'checked' : '' ?>> No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-2 align-items-center">
                                <div class="col-5">Biogas plant:</div>
                                <div class="col-7">
                                    <div class="d-flex align-items-center gap-2">
                                        <label><input type="radio" name="has_biogas_plant" value="1" <?= ($val_biogas == 1) ? 'checked' : '' ?>> Yes</label>
                                        <label><input type="radio" name="has_biogas_plant" value="0" <?= ($val_biogas == 0) ? 'checked' : '' ?>> No</label>
                                        <input type="text" name="biogas_capacity_m3" class="form-control form-control-sm form-control-custom ms-2" value="<?= htmlspecialchars($val_biogas_cap) ?>" placeholder="Capacity in m³" style="max-width: 150px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-custom">2.9 Animal ID Method</label>
                    </div>
                    <div class="col-md-8">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">Ear Tag No</span>
                                    <input type="text" name="id_ear_tag_no" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($val_tag_no) ?>" placeholder="AI/Range No">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">Brand No</span>
                                    <input type="text" name="id_hot_brand_no" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($val_brand_no) ?>" placeholder="Hot Iron Brand">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- SECTION 03: INFORMATION ABOUT THE FARM                                    -->
            <!-- ========================================================================= -->
            <div class="survey-block">
                <div class="survey-section-title">
                    <i class="bi bi-geo-alt-fill me-1"></i> 03. Information about the Farm
                </div>

                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label-custom">3.1 Geographic coordinates of the farm</label>
                    </div>
                    <div class="col-md-8">
                        <div class="input-group input-group-sm">
                            <input type="text" id="app_geo_coordinates" name="geo_coordinates" class="form-control form-control-sm form-control-custom font-monospace" value="<?= htmlspecialchars($val_coords) ?>" placeholder="e.g. 7.2185, 81.8492 (Latitude, Longitude)">
                            <button class="btn btn-outline-secondary bg-white no-print" type="button" onclick="getCurrentAppGPS()" title="Get Device Location">
                                <i class="bi bi-crosshair"></i> Current GPS
                            </button>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-custom">3.2 Agricultural Environment</label>
                    </div>
                    <div class="col-md-8">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">AI / CC</span>
                                    <input type="text" name="agri_env_ai_cc" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($val_ai_cc) ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">DS Div</span>
                                    <input type="text" name="agri_env_ds_division" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($val_ds_div) ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">GN Div</span>
                                    <input type="text" name="agri_env_gn_division" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($val_gn_div) ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- SECTION 04: HERD COMPOSITION (ANIMALS CURRENTLY ON THE FARM)              -->
            <!-- ========================================================================= -->
            <div class="survey-block">
                <div class="survey-section-title">
                    <i class="bi bi-grid-3x3-gap-fill me-1"></i> 04. Herd composition (Animals currently on the farm)
                </div>

                <div class="table-responsive">
                    <table class="survey-table table-bordered">
                        <thead>
                            <tr>
                                <th rowspan="2" style="width: 28%; text-align: left; padding-left: 10px;">Category</th>
                                <th colspan="4">Number</th>
                            </tr>
                            <tr>
                                <th colspan="2" style="width: 36%;">Cows</th>
                                <th colspan="2" style="width: 36%;">Buffaloes</th>
                            </tr>
                            <tr style="background-color: #e5e7eb; font-size: 0.775rem;">
                                <th></th>
                                <th>Pure breed</th>
                                <th>Non pure breed</th>
                                <th>Pure breed</th>
                                <th>Non pure breed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $herd_labels = [
                                'milking_cows'     => 'Milking cows',
                                'non_milking_cows' => 'Non milking cows',
                                'heifers'          => 'Heifers',
                                'female_calves'    => 'Female Calves (0-1 Year)',
                                'male_calves'      => 'Male Calves (0-1 Year)',
                                'bulls'            => 'Bulls',
                            ];
                            foreach ($herd_labels as $key => $lbl):
                                $r = $herd_rows[$key];
                            ?>
                                <tr>
                                    <td class="fw-semibold px-2"><?= $lbl ?></td>
                                    <td><input type="number" min="0" class="herd-input" name="herd_composition_data[<?= $key ?>][cow_pure]" data-type="cow_pure" value="<?= intval($r['cow_pure']) ?>"></td>
                                    <td><input type="number" min="0" class="herd-input" name="herd_composition_data[<?= $key ?>][cow_nonpure]" data-type="cow_nonpure" value="<?= intval($r['cow_nonpure']) ?>"></td>
                                    <td><input type="number" min="0" class="herd-input" name="herd_composition_data[<?= $key ?>][buf_pure]" data-type="buf_pure" value="<?= intval($r['buf_pure']) ?>"></td>
                                    <td><input type="number" min="0" class="herd-input" name="herd_composition_data[<?= $key ?>][buf_nonpure]" data-type="buf_nonpure" value="<?= intval($r['buf_nonpure']) ?>"></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="fw-bold" style="background-color: #f3f4f6;">
                                <td class="px-2 text-danger">Total number of animals</td>
                                <td class="text-center" id="total_cow_pure">0</td>
                                <td class="text-center" id="total_cow_nonpure">0</td>
                                <td class="text-center" id="total_buf_pure">0</td>
                                <td class="text-center" id="total_buf_nonpure">0</td>
                            </tr>
                            <tr class="fw-bold text-center" style="background-color: #e5e7eb;">
                                <td class="px-2 text-start">Grand Total Animals:</td>
                                <td colspan="4" class="text-center fs-6 text-dark" id="grand_total_herd">0 Head</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- SECTION 05: FEEDING                                                       -->
            <!-- ========================================================================= -->
            <div class="survey-block">
                <div class="survey-section-title">
                    <i class="bi bi-flower1 me-1"></i> 05. Feeding
                </div>

                <div class="mb-3">
                    <label class="form-label-custom mb-1">5.1 Types of fodder / feed:</label>
                    <div class="table-responsive">
                        <table class="survey-table table-bordered">
                            <thead>
                                <tr>
                                    <th style="width: 35%; text-align: left; padding-left: 10px;">Feed Type</th>
                                    <th style="width: 30%;">Mainly Fed (Yes/No)</th>
                                    <th style="width: 35%;">If purchased, monthly expenditure (Rs.)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $feed_labels = [
                                    'napier_co3'       => 'Napier / Co3 grass',
                                    'cultivated_grass' => 'Cultivated grass',
                                    'commercial_feed'  => 'Commercial feed',
                                    'other_fodder'     => 'Other fodder',
                                ];
                                foreach ($feed_labels as $fkey => $flbl):
                                    $fitem = $feed_types[$fkey];
                                ?>
                                    <tr>
                                        <td class="fw-semibold px-2"><?= $flbl ?></td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-3">
                                                <label><input type="radio" name="feeding_types_data[<?= $fkey ?>][mainly_fed]" value="Yes" <?= ($fitem['mainly_fed'] === 'Yes') ? 'checked' : '' ?>> Yes</label>
                                                <label><input type="radio" name="feeding_types_data[<?= $fkey ?>][mainly_fed]" value="No" <?= ($fitem['mainly_fed'] === 'No') ? 'checked' : '' ?>> No</label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" name="feeding_types_data[<?= $fkey ?>][expenditure]" value="<?= floatval($fitem['expenditure']) ?>" placeholder="0.00" class="text-end">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sub-Section: Fodder Cultivation -->
                <div class="p-3 bg-light rounded border border-secondary border-opacity-25 mt-3">
                    <div class="fw-bold text-dark mb-2 border-bottom pb-1">Fodder Cultivation</div>

                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-md-6">
                            <label class="form-label-custom mb-0">5.2 Cultivating grass on your own land?</label>
                        </div>
                        <div class="col-md-6 d-flex align-items-center gap-3">
                            <label><input type="radio" name="cultivating_grass_own_land" value="1" <?= ($app_data['cultivating_grass_own_land'] ?? 0) == 1 ? 'checked' : '' ?>> Yes</label>
                            <label><input type="radio" name="cultivating_grass_own_land" value="0" <?= ($app_data['cultivating_grass_own_land'] ?? 0) == 0 ? 'checked' : '' ?>> No</label>
                            <input type="text" name="own_land_grass_extent" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($app_data['own_land_grass_extent'] ?? '') ?>" placeholder="If yes, total extent (Acres)" style="max-width: 220px;">
                        </div>
                    </div>

                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-md-6">
                            <label class="form-label-custom mb-0">5.3 Type of grass cultivated</label>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="type_of_grass" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($app_data['type_of_grass'] ?? '') ?>" placeholder="e.g. CO-3, CO-4, Super Napier, Pakchong, Guinea">
                        </div>
                    </div>

                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-md-6">
                            <label class="form-label-custom mb-0">5.4 Method of cutting grass and harvesting</label>
                        </div>
                        <div class="col-md-6">
                            <?php $cm = $app_data['grass_cutting_method'] ?? 'Hand'; ?>
                            <select name="grass_cutting_method" class="form-select form-select-sm form-select-custom" style="max-width: 180px;">
                                <option value="Hand" <?= ($cm === 'Hand') ? 'selected' : '' ?>>Hand</option>
                                <option value="Machinery" <?= ($cm === 'Machinery') ? 'selected' : '' ?>>Machinery</option>
                                <option value="Both" <?= ($cm === 'Both') ? 'selected' : '' ?>>Both</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-md-6">
                            <label class="form-label-custom mb-0">5.5 Do you chop the fodder before feeding to the animals?</label>
                        </div>
                        <div class="col-md-6 d-flex gap-3">
                            <label><input type="radio" name="chop_fodder_before_feeding" value="1" <?= ($app_data['chop_fodder_before_feeding'] ?? 0) == 1 ? 'checked' : '' ?>> Yes</label>
                            <label><input type="radio" name="chop_fodder_before_feeding" value="0" <?= ($app_data['chop_fodder_before_feeding'] ?? 0) == 0 ? 'checked' : '' ?>> No</label>
                        </div>
                    </div>

                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-md-6">
                            <label class="form-label-custom mb-0">5.6 Have you made silage/hay production before?</label>
                        </div>
                        <div class="col-md-6 d-flex gap-3">
                            <label><input type="radio" name="made_silage_before" value="1" <?= ($app_data['made_silage_before'] ?? 0) == 1 ? 'checked' : '' ?>> Yes</label>
                            <label><input type="radio" name="made_silage_before" value="0" <?= ($app_data['made_silage_before'] ?? 0) == 0 ? 'checked' : '' ?>> No</label>
                        </div>
                    </div>

                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-md-6">
                            <label class="form-label-custom mb-0">5.7 Are you willing to make silage?</label>
                        </div>
                        <div class="col-md-6 d-flex gap-3">
                            <label><input type="radio" name="willing_to_make_silage" value="1" <?= ($app_data['willing_to_make_silage'] ?? 0) == 1 ? 'checked' : '' ?>> Yes</label>
                            <label><input type="radio" name="willing_to_make_silage" value="0" <?= ($app_data['willing_to_make_silage'] ?? 0) == 0 ? 'checked' : '' ?>> No</label>
                        </div>
                    </div>

                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-md-6">
                            <label class="form-label-custom mb-0">5.8 Do you feed silage?</label>
                        </div>
                        <div class="col-md-6 d-flex gap-3">
                            <label><input type="radio" name="feed_silage" value="1" <?= ($app_data['feed_silage'] ?? 0) == 1 ? 'checked' : '' ?>> Yes</label>
                            <label><input type="radio" name="feed_silage" value="0" <?= ($app_data['feed_silage'] ?? 0) == 0 ? 'checked' : '' ?>> No</label>
                        </div>
                    </div>

                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-md-6">
                            <label class="form-label-custom mb-0">5.9 Do you buy and feed agro-industrial by-products / crop residues?</label>
                        </div>
                        <div class="col-md-6 d-flex gap-3">
                            <label><input type="radio" name="buy_agro_byproducts" value="1" <?= ($app_data['buy_agro_byproducts'] ?? 0) == 1 ? 'checked' : '' ?>> Yes</label>
                            <label><input type="radio" name="buy_agro_byproducts" value="0" <?= ($app_data['buy_agro_byproducts'] ?? 0) == 0 ? 'checked' : '' ?>> No</label>
                        </div>
                    </div>

                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-md-6">
                            <label class="form-label-custom mb-0">5.10 Do you buy and feed concentrated cattle feed or mix own balanced cattle ration?</label>
                        </div>
                        <div class="col-md-6 d-flex gap-3">
                            <label><input type="radio" name="buy_concentrated_feed" value="1" <?= ($app_data['buy_concentrated_feed'] ?? 0) == 1 ? 'checked' : '' ?>> Yes</label>
                            <label><input type="radio" name="buy_concentrated_feed" value="0" <?= ($app_data['buy_concentrated_feed'] ?? 0) == 0 ? 'checked' : '' ?>> No</label>
                        </div>
                    </div>

                    <div class="row g-2 align-items-center">
                        <div class="col-md-6">
                            <label class="form-label-custom mb-0">5.11 If you make own ration, do you mix commercial mineral mixture / trace minerals?</label>
                        </div>
                        <div class="col-md-6 d-flex gap-3">
                            <label><input type="radio" name="mix_mineral_mixture" value="1" <?= ($app_data['mix_mineral_mixture'] ?? 0) == 1 ? 'checked' : '' ?>> Yes</label>
                            <label><input type="radio" name="mix_mineral_mixture" value="0" <?= ($app_data['mix_mineral_mixture'] ?? 0) == 0 ? 'checked' : '' ?>> No</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- SECTION 06: MILK PRODUCTION                                               -->
            <!-- ========================================================================= -->
            <div class="survey-block">
                <div class="survey-section-title">
                    <i class="bi bi-droplet-half me-1"></i> 06. Milk Production
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label-custom">6.1 Current daily milk production (Liters/day)</label>
                    </div>
                    <div class="col-md-8">
                        <div class="row g-2" style="max-width: 480px;">
                            <div class="col-4">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">Morning</span>
                                    <input type="number" step="0.1" min="0" id="app_milk_morning" name="milk_morning_liters" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($val_milk_morn) ?>">
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">Evening</span>
                                    <input type="number" step="0.1" min="0" id="app_milk_evening" name="milk_evening_liters" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($val_milk_eve) ?>">
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white fw-bold">Total</span>
                                    <input type="number" step="0.1" min="0" id="app_milk_total" name="milk_total_liters" class="form-control form-control-sm form-control-custom fw-bold" value="<?= htmlspecialchars($val_milk_tot) ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-custom">6.2 Expected daily milk production (Liters/day)</label>
                    </div>
                    <div class="col-md-8">
                        <div class="input-group input-group-sm" style="max-width: 220px;">
                            <input type="number" step="0.1" min="0" name="milk_expected_liters" class="form-control form-control-sm form-control-custom fw-bold text-success" value="<?= htmlspecialchars($val_milk_exp) ?>">
                            <span class="input-group-text bg-white">L/day</span>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-custom">6.3 Milk hygiene measures taken before milking</label>
                    </div>
                    <div class="col-md-8">
                        <textarea name="milk_hygiene_measures" rows="2" class="form-control form-control-sm form-control-custom" placeholder="e.g. Udder washing with warm water, teat dipping, clean utensils, hand washing"><?= htmlspecialchars($val_hygiene) ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-custom">6.4 Prevailing price of 1 Liter of raw milk</label>
                    </div>
                    <div class="col-md-8">
                        <div class="input-group input-group-sm" style="max-width: 220px;">
                            <span class="input-group-text bg-white">Rs.</span>
                            <input type="number" step="0.01" min="0" name="milk_price_per_liter" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($val_milk_price) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- SECTION 07: MILK MARKETING                                                -->
            <!-- ========================================================================= -->
            <div class="survey-block">
                <div class="survey-section-title">
                    <i class="bi bi-shop me-1"></i> 07. Milk Marketing
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label-custom mb-0">7.1 Are you selling milk to a private collector?</label>
                    </div>
                    <div class="col-md-6 d-flex gap-3">
                        <label><input type="radio" name="sell_to_private_collector" value="1" <?= ($app_data['sell_to_private_collector'] ?? 0) == 1 ? 'checked' : '' ?>> Yes</label>
                        <label><input type="radio" name="sell_to_private_collector" value="0" <?= ($app_data['sell_to_private_collector'] ?? 0) == 0 ? 'checked' : '' ?>> No</label>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label-custom mb-0">7.2 Are you selling milk to an organized milk collector?</label>
                    </div>
                    <div class="col-md-6 d-flex gap-3">
                        <label><input type="radio" name="sell_to_organized_collector" value="1" <?= ($app_data['sell_to_organized_collector'] ?? 0) == 1 ? 'checked' : '' ?>> Yes</label>
                        <label><input type="radio" name="sell_to_organized_collector" value="0" <?= ($app_data['sell_to_organized_collector'] ?? 0) == 0 ? 'checked' : '' ?>> No</label>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label-custom mb-0">7.3 Quantity sold / price per Liter</label>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="marketing_details" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($app_data['marketing_details'] ?? '') ?>" placeholder="e.g. 15 Liters @ Rs. 160.00/L">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label-custom mb-0">7.4 Do you keep records on daily milk production and financial accounts of the farm?</label>
                    </div>
                    <div class="col-md-6 d-flex gap-3">
                        <label><input type="radio" name="keep_daily_records" value="1" <?= ($app_data['keep_daily_records'] ?? 0) == 1 ? 'checked' : '' ?>> Yes</label>
                        <label><input type="radio" name="keep_daily_records" value="0" <?= ($app_data['keep_daily_records'] ?? 0) == 0 ? 'checked' : '' ?>> No</label>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- SECTION 08: TRAINING AND TECHNICAL ADVICE                                 -->
            <!-- ========================================================================= -->
            <div class="survey-block">
                <div class="survey-section-title">
                    <i class="bi bi-mortarboard-fill me-1"></i> 08. Training and Technical Advice
                </div>

                <div class="table-responsive">
                    <table class="survey-table table-bordered" id="tblTrainings">
                        <thead>
                            <tr>
                                <th style="width: 50%; text-align: left; padding-left: 10px;">Training / Course Name</th>
                                <th style="width: 45%; text-align: left; padding-left: 10px;">Institute</th>
                                <th style="width: 5%;" class="no-print">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($training_courses as $idx => $t): ?>
                                <tr>
                                    <td>
                                        <input type="text" name="training_courses_data[<?= $idx ?>][course]" value="<?= htmlspecialchars($t['course'] ?? '') ?>" placeholder="e.g. Clean Milk Production, Fodder Processing" style="text-align: left;">
                                    </td>
                                    <td>
                                        <input type="text" name="training_courses_data[<?= $idx ?>][institute]" value="<?= htmlspecialchars($t['institute'] ?? '') ?>" placeholder="e.g. DAPH, Veterinary Office, NLDB" style="text-align: left;">
                                    </td>
                                    <td class="text-center no-print">
                                        <button type="button" class="btn btn-sm btn-outline-danger p-0 px-2" onclick="$(this).closest('tr').remove()">&times;</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="mt-2 no-print">
                        <button type="button" class="btn btn-sm btn-outline-dark" id="btnAddTrainingRow">
                            <i class="bi bi-plus me-1"></i> Add Course Row
                        </button>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- SECTION 09: WASTE MANAGEMENT & HEALTH                                     -->
            <!-- ========================================================================= -->
            <div class="survey-block">
                <div class="survey-section-title">
                    <i class="bi bi-recycle me-1"></i> Waste Management &amp; Animal Health
                </div>

                <div class="mb-3">
                    <label class="form-label-custom mb-1">Disposal of farm waste (cow dung):</label>
                    <div class="d-flex flex-wrap gap-4 pt-1">
                        <label><input type="checkbox" name="waste_pasture" value="1" <?= ($app_data['waste_pasture'] ?? 0) == 1 ? 'checked' : '' ?>> Direct to pasture / field</label>
                        <label><input type="checkbox" name="waste_biogas" value="1" <?= ($app_data['waste_biogas'] ?? 0) == 1 ? 'checked' : '' ?>> Biogas production</label>
                        <label><input type="checkbox" name="waste_composting" value="1" <?= ($app_data['waste_composting'] ?? 0) == 1 ? 'checked' : '' ?>> Composting</label>
                        <label><input type="checkbox" name="waste_sold_manure" value="1" <?= ($app_data['waste_sold_manure'] ?? 0) == 1 ? 'checked' : '' ?>> Sold as manure</label>
                    </div>
                    <div class="mt-2" style="max-width: 400px;">
                        <input type="text" name="waste_other" class="form-control form-control-sm form-control-custom" value="<?= htmlspecialchars($app_data['waste_other'] ?? '') ?>" placeholder="Other disposal method if applicable">
                    </div>
                </div>

                <div class="row g-2 align-items-center mb-2">
                    <div class="col-md-7">
                        <label class="form-label-custom mb-0">Have you received training on clean milk production and farm management?</label>
                    </div>
                    <div class="col-md-5 d-flex gap-3">
                        <label><input type="radio" name="training_clean_milk" value="1" <?= ($app_data['training_clean_milk'] ?? 0) == 1 ? 'checked' : '' ?>> Yes</label>
                        <label><input type="radio" name="training_clean_milk" value="0" <?= ($app_data['training_clean_milk'] ?? 0) == 0 ? 'checked' : '' ?>> No</label>
                    </div>
                </div>

                <div class="row g-2 align-items-center mb-2">
                    <div class="col-md-7">
                        <label class="form-label-custom mb-0">Do you measure milk yield regularly?</label>
                    </div>
                    <div class="col-md-5 d-flex gap-3">
                        <label><input type="radio" name="measure_milk_yield" value="1" <?= ($app_data['measure_milk_yield'] ?? 0) == 1 ? 'checked' : '' ?>> Yes</label>
                        <label><input type="radio" name="measure_milk_yield" value="0" <?= ($app_data['measure_milk_yield'] ?? 0) == 0 ? 'checked' : '' ?>> No</label>
                    </div>
                </div>

                <div class="row g-2 align-items-center mb-3">
                    <div class="col-md-7">
                        <label class="form-label-custom mb-0">Did you participate in any animal health camps / extension programmes?</label>
                    </div>
                    <div class="col-md-5 d-flex gap-3">
                        <label><input type="radio" name="participate_health_camps" value="1" <?= ($app_data['participate_health_camps'] ?? 0) == 1 ? 'checked' : '' ?>> Yes</label>
                        <label><input type="radio" name="participate_health_camps" value="0" <?= ($app_data['participate_health_camps'] ?? 0) == 0 ? 'checked' : '' ?>> No</label>
                    </div>
                </div>

                <div class="p-3 bg-light rounded border border-secondary border-opacity-25">
                    <div class="fw-bold text-danger mb-2">Diseases / Vaccinations:</div>

                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-md-7">
                            <label class="form-label-custom mb-0">Did you vaccinate your animals against Hemorrhagic Septicemia (HS)?</label>
                        </div>
                        <div class="col-md-5 d-flex gap-3">
                            <label><input type="radio" name="vaccine_hs" value="1" <?= ($app_data['vaccine_hs'] ?? 0) == 1 ? 'checked' : '' ?>> Yes</label>
                            <label><input type="radio" name="vaccine_hs" value="0" <?= ($app_data['vaccine_hs'] ?? 0) == 0 ? 'checked' : '' ?>> No</label>
                        </div>
                    </div>

                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-md-7">
                            <label class="form-label-custom mb-0">Black Quarter (BQ) vaccination?</label>
                        </div>
                        <div class="col-md-5 d-flex gap-3">
                            <label><input type="radio" name="vaccine_bq" value="1" <?= ($app_data['vaccine_bq'] ?? 0) == 1 ? 'checked' : '' ?>> Yes</label>
                            <label><input type="radio" name="vaccine_bq" value="0" <?= ($app_data['vaccine_bq'] ?? 0) == 0 ? 'checked' : '' ?>> No</label>
                        </div>
                    </div>

                    <div class="row g-2 align-items-center">
                        <div class="col-md-7">
                            <label class="form-label-custom mb-0">Foot and Mouth Disease (FMD) vaccination?</label>
                        </div>
                        <div class="col-md-5 d-flex gap-3">
                            <label><input type="radio" name="vaccine_fmd" value="1" <?= ($app_data['vaccine_fmd'] ?? 0) == 1 ? 'checked' : '' ?>> Yes</label>
                            <label><input type="radio" name="vaccine_fmd" value="0" <?= ($app_data['vaccine_fmd'] ?? 0) == 0 ? 'checked' : '' ?>> No</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Action Bar inside document -->
            <div class="p-4 bg-light text-center no-print border-top">
                <button type="submit" id="btnSubmitApp" class="btn btn-submit-survey me-3">
                    <i class="bi bi-cloud-check-fill me-1"></i> Submit Application Survey
                </button>
                <a href="dairy_project_summary.php" class="btn btn-secondary px-4 py-2">
                    <i class="bi bi-arrow-left me-1"></i> Return to Summary Report
                </a>
            </div>

        </form>
    </div>

</div>

<script src="../../../assets/js/jquery.min.js"></script>
<script src="../../../assets/js/bootstrap.bundle.min.js"></script>
<script src="../../../assets/js/sweetalert2.all.min.js"></script>

<script>
// Auto-calculation of Herd Totals
function calculateHerdTotals() {
    let cp = 0, cnp = 0, bp = 0, bnp = 0;
    $('.herd-input').each(function() {
        const val = parseInt($(this).val()) || 0;
        const type = $(this).data('type');
        if (type === 'cow_pure') cp += val;
        else if (type === 'cow_nonpure') cnp += val;
        else if (type === 'buf_pure') bp += val;
        else if (type === 'buf_nonpure') bnp += val;
    });

    $('#total_cow_pure').text(cp);
    $('#total_cow_nonpure').text(cnp);
    $('#total_buf_pure').text(bp);
    $('#total_buf_nonpure').text(bnp);

    const grand = cp + cnp + bp + bnp;
    $('#grand_total_herd').text(`${grand} Head (${cp + cnp} Cows, ${bp + bnp} Buffaloes)`);
}

// Auto-calculation of Milk Totals
function calculateMilkTotals() {
    const m = parseFloat($('#app_milk_morning').val()) || 0;
    const e = parseFloat($('#app_milk_evening').val()) || 0;
    if (m > 0 || e > 0) {
        $('#app_milk_total').val((m + e).toFixed(1));
    }
}

// Device GPS
function getCurrentAppGPS() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos) {
            const coords = `${pos.coords.latitude.toFixed(6)}, ${pos.coords.longitude.toFixed(6)}`;
            $('#app_geo_coordinates').val(coords);
            Swal.fire({
                icon: 'success',
                title: 'Coordinates Acquired',
                text: coords,
                timer: 1500,
                showConfirmButton: false
            });
        }, function(err) {
            Swal.fire('GPS Error', err.message, 'warning');
        });
    } else {
        Swal.fire('Error', 'Geolocation is not supported by your browser.', 'error');
    }
}

$(document).ready(function() {
    calculateHerdTotals();
    calculateMilkTotals();

    $('.herd-input').on('input', calculateHerdTotals);
    $('#app_milk_morning, #app_milk_evening').on('input', calculateMilkTotals);

    // Auto-fetch in Application form
    $('#btnAppAutoFetch').on('click', function() {
        const regVal = $('#app_farm_reg_no').val().trim();
        if (!regVal) {
            Swal.fire('Error', 'Please input Farm Registration No or NIC.', 'warning');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Fetching...');

        $.ajax({
            url: 'processors/dairy_project_crud.php',
            method: 'POST',
            dataType: 'json',
            data: { action: 'lookup_farmer', farm_reg_no: regVal },
            success: function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-cloud-arrow-down-fill me-1"></i> Auto-Fetch');
                if (res.success && res.found) {
                    const f = res.farmer;
                    $('#app_producer_name').val(f.farmer_name || '');
                    $('#app_nic_no').val(f.nic || '');
                    $('#app_address').val(f.address || '');
                    $('#app_contact_mobile').val(f.phone || '');
                    $('#app_telephone_no').val(f.phone || '');
                    $('#app_farm_reg_display').val(f.farm_reg_no || regVal);

                    if (f.ds_division || f.gn_division) {
                        $('#app_ds_gn_division').val(`${f.ds_division || ''} / ${f.gn_division || ''}`.trim());
                    }
                    if (f.gps_location) {
                        $('#app_geo_coordinates').val(f.gps_location);
                    }
                    if (f.daily_milk > 0 && parseFloat($('#app_milk_total').val() || 0) === 0) {
                        $('#app_milk_total').val(f.daily_milk);
                        $('#app_milk_morning').val((f.daily_milk * 0.6).toFixed(1));
                        $('#app_milk_evening').val((f.daily_milk * 0.4).toFixed(1));
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Master Registry Match!',
                        text: `Data populated for ${f.farmer_name} from ${f.source}.`,
                        confirmButtonColor: '#820100'
                    });
                } else {
                    Swal.fire('Notice', res.message || 'No matching farmer in registry.', 'info');
                }
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="bi bi-cloud-arrow-down-fill me-1"></i> Auto-Fetch');
                Swal.fire('Error', 'Connection failure.', 'error');
            }
        });
    });

    // Synchronize farm reg display
    $('#app_farm_reg_no').on('input', function() {
        $('#app_farm_reg_display').val($(this).val());
    });

    // Add training row
    let trainingIndex = <?= count($training_courses) ?>;
    $('#btnAddTrainingRow').on('click', function() {
        trainingIndex++;
        const row = `
            <tr>
                <td>
                    <input type="text" name="training_courses_data[${trainingIndex}][course]" placeholder="Course Name" style="text-align: left;">
                </td>
                <td>
                    <input type="text" name="training_courses_data[${trainingIndex}][institute]" placeholder="Institute" style="text-align: left;">
                </td>
                <td class="text-center no-print">
                    <button type="button" class="btn btn-sm btn-outline-danger p-0 px-2" onclick="$(this).closest('tr').remove()">&times;</button>
                </td>
            </tr>
        `;
        $('#tblTrainings tbody').append(row);
    });

    // Save Application Form via AJAX
    $('#dairyApplicationForm').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const submitBtn = $('#btnSubmitApp');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving Form...');

        $.ajax({
            url: 'processors/dairy_project_crud.php',
            method: 'POST',
            dataType: 'json',
            data: form.serialize(),
            success: function(res) {
                submitBtn.prop('disabled', false).html('<i class="bi bi-cloud-check-fill me-1"></i> Submit Application Survey');
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Survey Saved Successfully!',
                        text: res.message,
                        confirmButtonColor: '#1d4ed8'
                    }).then(() => {
                        window.location.href = `dairy_project_summary.php?farm_reg_no=${encodeURIComponent(res.farm_reg_no)}`;
                    });
                } else {
                    Swal.fire('Error Saving Survey', res.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                submitBtn.prop('disabled', false).html('<i class="bi bi-cloud-check-fill me-1"></i> Submit Application Survey');
                Swal.fire('Submission Error', 'Failed to communicate with server: ' + error, 'error');
            }
        });
    });
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
