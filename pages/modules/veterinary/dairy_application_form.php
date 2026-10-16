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
$param_range_id    = isset($_GET['range_id']) && is_numeric($_GET['range_id']) ? intval($_GET['range_id']) : (!empty($_SESSION['range_id']) ? intval($_SESSION['range_id']) : 1);

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
        if ($summary_data && !empty($summary_data['range_id'])) {
            $param_range_id = $summary_data['range_id'];
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
    'napier_co3'       => ['mainly_fed' => 'No', 'expenditure' => 0],
    'cultivated_grass' => ['mainly_fed' => 'No', 'expenditure' => 0],
    'commercial_feed'  => ['mainly_fed' => 'No', 'expenditure' => 0],
    'other_fodder'     => ['mainly_fed' => 'No', 'expenditure' => 0],
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
    /* Modernized Application Form Styling */
    body {
        background-color: #f8fafc;
        color: #1e293b;
    }

    .form-hero-banner {
        background: linear-gradient(135deg, #370709 0%, #631215 50%, #820100 100%);
        border-radius: 16px;
        color: #ffffff;
        box-shadow: 0 10px 25px -5px rgba(55, 7, 9, 0.25);
    }

    .form-card {
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.04), 0 2px 4px -2px rgba(0, 0, 0, 0.03);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .section-icon-box {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .section-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 2px;
    }

    .section-subtitle {
        font-size: 0.8125rem;
        color: #64748b;
    }

    .form-label-modern {
        font-weight: 600;
        font-size: 0.85rem;
        color: #334155;
        margin-bottom: 5px;
    }

    .form-control-modern,
    .form-select-modern {
        border: 1.5px solid #cbd5e1;
        border-radius: 8px;
        padding: 0.55rem 0.85rem;
        font-size: 0.875rem;
        color: #0f172a;
        background-color: #ffffff;
        transition: all 0.2s ease;
    }

    .form-control-modern:focus,
    .form-select-modern:focus {
        border-color: #820100;
        box-shadow: 0 0 0 3px rgba(130, 1, 0, 0.12);
    }

    .cat-pill-card {
        cursor: pointer;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 18px;
        background: #ffffff;
        transition: all 0.2s ease;
        text-align: center;
        font-weight: 600;
        font-size: 0.9rem;
        color: #334155;
    }

    .cat-pill-card:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
    }

    .cat-radio-input:checked+.cat-pill-card {
        border-color: #820100;
        background-color: #fdf2f2;
        color: #820100;
        font-weight: 700;
        box-shadow: 0 2px 8px rgba(130, 1, 0, 0.15);
    }

    .modern-table {
        border-collapse: separate;
        border-spacing: 0;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }

    .modern-table thead {
        background-color: #f1f5f9;
        color: #334155;
        font-size: 0.8125rem;
        font-weight: 700;
    }

    .modern-table th,
    .modern-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #e2e8f0;
        border-right: 1px solid #f1f5f9;
    }

    .modern-table tr:last-child td {
        border-bottom: none;
    }

    .sticky-actions-bar {
        position: sticky;
        bottom: 0;
        z-index: 1020;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-top: 1px solid #e2e8f0;
        box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.05);
    }

    @media print {

        .no-print,
        .sticky-actions-bar,
        header,
        nav,
        .sb-sidenav {
            display: none !important;
        }

        body {
            background-color: #ffffff !important;
        }

        .form-card {
            border: 1px solid #000000 !important;
            box-shadow: none !important;
            page-break-inside: avoid;
            margin-bottom: 20px !important;
        }

        .form-hero-banner {
            background: #ffffff !important;
            color: #000000 !important;
            border: 1px solid #000000 !important;
        }
    }
</style>

<div class="container-xl py-4">

    <!-- Top Breadcrumb & Quick Actions -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 no-print">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="../../../dashboard.php" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="dairy_hub.php?range_id=<?= $param_range_id ?>" class="text-decoration-none text-muted">Dairy Hub</a></li>
                    <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">Application Survey</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0" style="color: #370709;">
                <i class="bi bi-file-earmark-spreadsheet-fill me-2 text-danger"></i>Dairy Farm Development Potential Survey
            </h4>
        </div>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <a href="dairy_hub.php?range_id=<?= $param_range_id ?>&tab=approvals" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Return to Dairy Hub
            </a>
            <button type="button" class="btn btn-outline-dark btn-sm px-3 shadow-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print / PDF
            </button>
            <button type="button" class="btn text-white btn-sm px-4 fw-bold shadow-sm" style="background-color: #820100;" onclick="$('#modernApplicationForm').submit()">
                <i class="bi bi-check2-circle me-1"></i> Save Survey
            </button>
        </div>
    </div>

    <!-- Hero Banner with Live Link State -->
    <div class="card form-hero-banner mb-4 border-0 p-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge bg-light text-dark fw-bold px-3 py-1">
                        <i class="bi bi-award-fill me-1 text-danger"></i> National Dairy Production Programme
                    </span>
                    <span class="badge bg-warning text-dark fw-bold px-3 py-1">
                        <i class="bi bi-calendar3 me-1"></i> Year <?= date('Y') ?>
                    </span>
                    <?php if (!empty($summary_data)): ?>
                        <span class="badge bg-success-subtle text-white border border-light px-3 py-1">
                            <i class="bi bi-link-45deg me-1"></i> Linked to Dairy Hub Record #<?= $summary_data['id'] ?>
                        </span>
                    <?php endif; ?>
                </div>
                <h3 class="fw-bold mb-1">Information Collection &amp; Potential Assessment</h3>
                <p class="mb-0 text-light opacity-90 small">
                    Comprehensive digital survey capturing farm profile, herd composition, fodder practices, and yield projections.
                </p>
            </div>
            <div class="col-lg-4 mt-3 mt-lg-0 text-lg-end no-print">
                <div class="bg-white bg-opacity-10 p-3 rounded-3 d-inline-block text-start w-100" style="max-width: 320px;">
                    <div class="small text-light mb-1">Active Registration No:</div>
                    <div class="font-monospace fw-bold text-white fs-6 mb-2">
                        <?= !empty($val_farm_reg_no) ? htmlspecialchars($val_farm_reg_no) : '<span class="text-warning fst-italic">Not Specified</span>' ?>
                    </div>
                    <div class="small text-light">Beneficiary Name:</div>
                    <div class="fw-bold text-white"><?= !empty($val_name) ? htmlspecialchars($val_name) : '—' ?></div>
                </div>
            </div>
        </div>
    </div>

    <form id="modernApplicationForm">
        <input type="hidden" name="action" value="save_application">
        <input type="hidden" name="summary_id" value="<?= htmlspecialchars($param_summary_id) ?>">

        <!-- ========================================================================= -->
        <!-- SECTION 1: PRODUCER GENERAL INFORMATION                                   -->
        <!-- ========================================================================= -->
        <div class="card form-card mb-4 p-4">
            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                <div class="section-icon-box bg-danger-subtle text-danger">
                    <i class="bi bi-person-lines-fill"></i>
                </div>
                <div>
                    <h5 class="section-title">01. Particulars of Milk Producer</h5>
                    <div class="section-subtitle">Basic identity, registration numbers, and communication details</div>
                </div>
            </div>

            <!-- Main Category Selector (Modern Radio Pills) -->
            <div class="mb-4 p-3 bg-light rounded-3 border">
                <label class="form-label-modern d-block mb-2 text-dark">
                    <i class="bi bi-speedometer2 me-1 text-danger"></i> Main Category of Milk Production Recording:
                </label>
                <div class="row g-2">
                    <?php
                    $categories = [
                        '05 Liters' => '5L Tier',
                        '10 Liters' => '10L Tier',
                        '20 Liters' => '20L Tier',
                        '50 Liters' => '50L Tier',
                        '100 Liters' => '100L Tier'
                    ];
                    foreach ($categories as $catVal => $catLabel):
                        $checked = (strpos($val_main_cat, str_replace(' Liters', '', $catVal)) !== false || $val_main_cat === $catVal) ? 'checked' : '';
                    ?>
                        <div class="col-6 col-md">
                            <label class="d-block">
                                <input type="radio" class="d-none cat-radio-input" name="main_category" value="<?= $catVal ?>" <?= $checked ?>>
                                <div class="cat-pill-card">
                                    <div class="small text-muted"><?= $catLabel ?></div>
                                    <div class="fs-6"><?= $catVal ?></div>
                                </div>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Fields Grid -->
            <div class="row g-3">
                <!-- Farm Reg No with Auto-Fetch -->
                <div class="col-md-6">
                    <label class="form-label-modern">Farmer Registration No <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" id="app_farm_reg_no" name="farm_reg_no" class="form-control form-control-modern font-monospace fw-bold" value="<?= htmlspecialchars($val_farm_reg_no) ?>" placeholder="e.g. 530112345 or FRN-AMP-001" required>
                        <button class="btn text-white fw-bold px-3 no-print" type="button" id="btnAppAutoFetch" style="background-color: #820100;" title="Pull records from Master Registry">
                            <i class="bi bi-cloud-arrow-down-fill me-1"></i> Auto-Fetch
                        </button>
                    </div>
                    <div class="form-text small text-muted">Click Auto-Fetch to automatically retrieve farmer profile from Master Registry.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label-modern">Full Name of Milk Producer <span class="text-danger">*</span></label>
                    <input type="text" id="app_producer_name" name="producer_name" class="form-control form-control-modern" value="<?= htmlspecialchars($val_name) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label-modern">NIC Number <span class="text-danger">*</span></label>
                    <input type="text" id="app_nic_no" name="nic_no" class="form-control form-control-modern font-monospace" value="<?= htmlspecialchars($val_nic) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label-modern">Contact Mobile Number <span class="text-danger">*</span></label>
                    <input type="tel" id="app_contact_mobile" name="contact_mobile" class="form-control form-control-modern" value="<?= htmlspecialchars($val_phone) ?>" placeholder="e.g. 0771234567" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label-modern">Telephone Number (Alternate)</label>
                    <input type="tel" id="app_telephone_no" name="telephone_no" class="form-control form-control-modern" value="<?= htmlspecialchars($val_tel) ?>" placeholder="Landline">
                </div>

                <div class="col-md-8">
                    <label class="form-label-modern">Address of Producer <span class="text-danger">*</span></label>
                    <textarea id="app_address" name="address" rows="2" class="form-control form-control-modern" required><?= htmlspecialchars($val_address) ?></textarea>
                </div>

                <div class="col-md-4">
                    <label class="form-label-modern">DAPH Register Number</label>
                    <input type="text" name="daph_register_no" class="form-control form-control-modern" value="<?= htmlspecialchars($val_daph_no) ?>" placeholder="Optional Reg No">
                    <div class="form-text small text-muted">Official DAPH departmental code.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label-modern">AI Number / Center</label>
                    <input type="text" name="ai_no" class="form-control form-control-modern" value="<?= htmlspecialchars($val_ai_no) ?>" placeholder="AI Division / Range">
                </div>

                <div class="col-md-4">
                    <label class="form-label-modern">Survey Date</label>
                    <input type="date" name="survey_date" class="form-control form-control-modern" value="<?= htmlspecialchars($val_survey_date) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label-modern">DS Division / GN Division</label>
                    <input type="text" id="app_ds_gn_division" name="ds_gn_division" class="form-control form-control-modern" value="<?= htmlspecialchars($val_ds_gn) ?>" placeholder="e.g. Sammanthurai / GN-04">
                </div>

                <div class="col-md-4">
                    <label class="form-label-modern">Gender</label>
                    <div class="d-flex gap-3 pt-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="gender" id="gender_m" value="Male" <?= ($val_gender === 'Male') ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="gender_m">Male</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="gender" id="gender_f" value="Female" <?= ($val_gender === 'Female') ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="gender_f">Female</label>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label-modern">Livestock Council Member</label>
                    <select name="livestock_council" class="form-select form-select-modern">
                        <option value="Yes" <?= ($val_council === 'Yes') ? 'selected' : '' ?>>Yes, Member</option>
                        <option value="No" <?= ($val_council === 'No') ? 'selected' : '' ?>>No</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label-modern">Transport Distance to Milk Chilling / Collection</label>
                    <div class="input-group">
                        <input type="number" step="0.1" min="0" name="transport_distance_km" class="form-control form-control-modern" value="<?= htmlspecialchars($val_dist_km) ?>">
                        <span class="input-group-text bg-light">Km</span>
                    </div>
                </div>

                <div class="col-md-12">
                    <label class="form-label-modern">Previous Project Benefits</label>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <select name="previous_project_benefit" class="form-select form-select-modern">
                                <option value="No" <?= ($val_prev_ben === 'No') ? 'selected' : '' ?>>No Previous Benefits</option>
                                <option value="Yes" <?= ($val_prev_ben === 'Yes') ? 'selected' : '' ?>>Yes, Benefited Earlier</option>
                            </select>
                        </div>
                        <div class="col-md-9">
                            <input type="text" name="previous_project_details" class="form-control form-control-modern" value="<?= htmlspecialchars($val_prev_det) ?>" placeholder="Specify details of previous projects or other subsidies received">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SECTION 2: FARM DETAILS & EQUIPMENT                                       -->
        <!-- ========================================================================= -->
        <div class="card form-card mb-4 p-4">
            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                <div class="section-icon-box bg-success-subtle text-success">
                    <i class="bi bi-house-door-fill"></i>
                </div>
                <div>
                    <h5 class="section-title">02. Farm Details &amp; Equipment</h5>
                    <div class="section-subtitle">Land ownership, cattle shed capacity, and mechanization inventory</div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label-modern">Water Source &amp; Facilities</label>
                    <select name="water_source" class="form-select form-select-modern">
                        <option value="Well" <?= ($val_water === 'Well') ? 'selected' : '' ?>>Dug Well</option>
                        <option value="Pipe borne" <?= ($val_water === 'Pipe borne') ? 'selected' : '' ?>>Pipe-Borne Water</option>
                        <option value="Tube Well" <?= ($val_water === 'Tube Well') ? 'selected' : '' ?>>Tube Well</option>
                        <option value="River / Stream" <?= ($val_water === 'River / Stream') ? 'selected' : '' ?>>River / Stream</option>
                        <option value="Other" <?= ($val_water === 'Other') ? 'selected' : '' ?>>Other Source</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label-modern">Total Land Extent Owned</label>
                    <input type="text" name="land_extent" class="form-control form-control-modern" value="<?= htmlspecialchars($val_land_ext) ?>" placeholder="e.g. 2 Acres, 30 Perches">
                </div>

                <div class="col-md-4">
                    <label class="form-label-modern">Pasture Cultivation Extent</label>
                    <input type="text" name="pasture_extent" class="form-control form-control-modern" value="<?= htmlspecialchars($val_past_ext) ?>" placeholder="e.g. 1 Acre">
                </div>

                <div class="col-md-6">
                    <label class="form-label-modern">Cattle Shed Holding Capacity</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="input-group">
                                <span class="input-group-text bg-light small">Adults</span>
                                <input type="number" min="0" name="shed_capacity_adults" class="form-control form-control-modern" value="<?= htmlspecialchars($val_shed_adults) ?>">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="input-group">
                                <span class="input-group-text bg-light small">Calves</span>
                                <input type="number" min="0" name="shed_capacity_calves" class="form-control form-control-modern" value="<?= htmlspecialchars($val_shed_calves) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label-modern">Common Grazing Land Available</label>
                    <input type="text" name="common_grazing_land" class="form-control form-control-modern" value="<?= htmlspecialchars($val_grazing) ?>" placeholder="e.g. 3 Acres communal reserve / scrubland">
                </div>

                <div class="col-md-12">
                    <label class="form-label-modern">Total Land Available for Future Pasture Expansion</label>
                    <input type="text" name="pasture_cultivation_land" class="form-control form-control-modern" value="<?= htmlspecialchars($val_past_land) ?>" placeholder="Extent or location of expandable plot">
                </div>

                <!-- Machinery & Equipment Matrix -->
                <div class="col-12 mt-2">
                    <label class="form-label-modern d-block mb-2">Available Machinery &amp; Equipment Inventory:</label>
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="p-2 bg-white rounded border d-flex justify-content-between align-items-center">
                                    <span class="small fw-semibold"><i class="bi bi-gear me-1 text-primary"></i> Grass Chopper</span>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <input type="radio" class="btn-check" name="has_grass_chopper" id="gc_yes" value="1" <?= ($val_chopper == 1) ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-success" for="gc_yes">Yes</label>
                                        <input type="radio" class="btn-check" name="has_grass_chopper" id="gc_no" value="0" <?= ($val_chopper == 0) ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-secondary" for="gc_no">No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-2 bg-white rounded border d-flex justify-content-between align-items-center">
                                    <span class="small fw-semibold"><i class="bi bi-bucket me-1 text-primary"></i> Milk Cans / Churns</span>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <input type="radio" class="btn-check" name="has_milk_cans" id="mc_yes" value="1" <?= ($val_cans == 1) ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-success" for="mc_yes">Yes</label>
                                        <input type="radio" class="btn-check" name="has_milk_cans" id="mc_no" value="0" <?= ($val_cans == 0) ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-secondary" for="mc_no">No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-2 bg-white rounded border d-flex justify-content-between align-items-center">
                                    <span class="small fw-semibold"><i class="bi bi-cpu me-1 text-primary"></i> Milking Machine</span>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <input type="radio" class="btn-check" name="has_milking_machine" id="mm_yes" value="1" <?= ($val_machine == 1) ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-success" for="mm_yes">Yes</label>
                                        <input type="radio" class="btn-check" name="has_milking_machine" id="mm_no" value="0" <?= ($val_machine == 0) ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-secondary" for="mm_no">No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-2 bg-white rounded border d-flex justify-content-between align-items-center">
                                    <span class="small fw-semibold"><i class="bi bi-snow me-1 text-primary"></i> Chilling Equipment</span>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <input type="radio" class="btn-check" name="has_chilling_equip" id="ce_yes" value="1" <?= ($val_chilling == 1) ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-success" for="ce_yes">Yes</label>
                                        <input type="radio" class="btn-check" name="has_chilling_equip" id="ce_no" value="0" <?= ($val_chilling == 0) ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-secondary" for="ce_no">No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="p-2 bg-white rounded border d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="small fw-semibold"><i class="bi bi-fire me-1 text-danger"></i> Biogas Plant</span>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <input type="radio" class="btn-check" name="has_biogas_plant" id="bg_yes" value="1" <?= ($val_biogas == 1) ? 'checked' : '' ?>>
                                            <label class="btn btn-outline-success" for="bg_yes">Yes</label>
                                            <input type="radio" class="btn-check" name="has_biogas_plant" id="bg_no" value="0" <?= ($val_biogas == 0) ? 'checked' : '' ?>>
                                            <label class="btn btn-outline-secondary" for="bg_no">No</label>
                                        </div>
                                    </div>
                                    <div class="input-group input-group-sm" style="max-width: 220px;">
                                        <span class="input-group-text bg-light">Capacity</span>
                                        <input type="text" name="biogas_capacity_m3" class="form-control form-control-sm" value="<?= htmlspecialchars($val_biogas_cap) ?>" placeholder="m³ volume">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Animal ID Method -->
                <div class="col-md-6">
                    <label class="form-label-modern">Ear Tagging Number (AI / Range)</label>
                    <input type="text" name="id_ear_tag_no" class="form-control form-control-modern font-monospace" value="<?= htmlspecialchars($val_tag_no) ?>" placeholder="Tag ID series">
                </div>
                <div class="col-md-6">
                    <label class="form-label-modern">Hot Iron Brand Number (AI / Range)</label>
                    <input type="text" name="id_hot_brand_no" class="form-control form-control-modern font-monospace" value="<?= htmlspecialchars($val_brand_no) ?>" placeholder="Brand Mark / Code">
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SECTION 3: ENVIRONMENT & GEOGRAPHIC COORDINATES                           -->
        <!-- ========================================================================= -->
        <div class="card form-card mb-4 p-4">
            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                <div class="section-icon-box bg-info-subtle text-info">
                    <i class="bi bi-geo-alt-fill"></i>
                </div>
                <div>
                    <h5 class="section-title">03. Geographic Coordinates &amp; Environment</h5>
                    <div class="section-subtitle">Precise farm location and veterinary administrative divisions</div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label-modern">Geographic GPS Coordinates (Latitude, Longitude)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-pin-map text-danger"></i></span>
                        <input type="text" id="app_geo_coordinates" name="geo_coordinates" class="form-control form-control-modern font-monospace" value="<?= htmlspecialchars($val_coords) ?>" placeholder="e.g. 7.218500, 81.849200">
                        <button class="btn btn-outline-secondary no-print" type="button" onclick="getCurrentAppGPS()">
                            <i class="bi bi-crosshair me-1"></i> Detect GPS
                        </button>
                    </div>
                </div>

                <div class="col-md-2">
                    <label class="form-label-modern">AI / Customer Center</label>
                    <input type="text" name="agri_env_ai_cc" class="form-control form-control-modern" value="<?= htmlspecialchars($val_ai_cc) ?>" placeholder="AI Division">
                </div>

                <div class="col-md-2">
                    <label class="form-label-modern">DS Division</label>
                    <input type="text" name="agri_env_ds_division" class="form-control form-control-modern" value="<?= htmlspecialchars($val_ds_div) ?>" placeholder="DS Office">
                </div>

                <div class="col-md-2">
                    <label class="form-label-modern">GN Division</label>
                    <input type="text" name="agri_env_gn_division" class="form-control form-control-modern" value="<?= htmlspecialchars($val_gn_div) ?>" placeholder="Grama Niladhari">
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SECTION 4: HERD COMPOSITION (INTERACTIVE MATRIX)                           -->
        <!-- ========================================================================= -->
        <div class="card form-card mb-4 p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <div class="section-icon-box bg-warning-subtle text-warning">
                        <i class="bi bi-grid-3x3-gap-fill"></i>
                    </div>
                    <div>
                        <h5 class="section-title">04. Herd Composition (Animals currently on the farm)</h5>
                        <div class="section-subtitle">Livestock census segregated by breed purity across cows and buffaloes</div>
                    </div>
                </div>
                <div class="mt-2 mt-md-0">
                    <span class="badge bg-dark fs-6 px-3 py-2 shadow-sm" id="grand_total_badge">
                        Grand Total: 0 Animals
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table modern-table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th rowspan="2" class="align-middle">Category</th>
                            <th colspan="2" class="text-center bg-danger-subtle text-danger border-start">Cows</th>
                            <th colspan="2" class="text-center bg-primary-subtle text-primary border-start">Buffaloes</th>
                            <th rowspan="2" class="text-center align-middle bg-light border-start" style="width: 120px;">Row Total</th>
                        </tr>
                        <tr class="small text-center">
                            <th class="border-start">Pure Breed</th>
                            <th>Non-Pure</th>
                            <th class="border-start">Pure Breed</th>
                            <th>Non-Pure</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $herd_labels = [
                            'milking_cows'     => 'Milking Cows',
                            'non_milking_cows' => 'Non-Milking Cows',
                            'heifers'          => 'Heifers',
                            'female_calves'    => 'Female Calves (0-1 Year)',
                            'male_calves'      => 'Male Calves (0-1 Year)',
                            'bulls'            => 'Bulls',
                        ];
                        foreach ($herd_labels as $key => $lbl):
                            $r = $herd_rows[$key];
                        ?>
                            <tr class="herd-row">
                                <td class="fw-bold text-dark"><?= $lbl ?></td>
                                <td class="border-start">
                                    <input type="number" min="0" class="form-control form-control-sm text-center herd-input" name="herd_composition_data[<?= $key ?>][cow_pure]" data-type="cow_pure" value="<?= intval($r['cow_pure']) ?>">
                                </td>
                                <td>
                                    <input type="number" min="0" class="form-control form-control-sm text-center herd-input" name="herd_composition_data[<?= $key ?>][cow_nonpure]" data-type="cow_nonpure" value="<?= intval($r['cow_nonpure']) ?>">
                                </td>
                                <td class="border-start">
                                    <input type="number" min="0" class="form-control form-control-sm text-center herd-input" name="herd_composition_data[<?= $key ?>][buf_pure]" data-type="buf_pure" value="<?= intval($r['buf_pure']) ?>">
                                </td>
                                <td>
                                    <input type="number" min="0" class="form-control form-control-sm text-center herd-input" name="herd_composition_data[<?= $key ?>][buf_nonpure]" data-type="buf_nonpure" value="<?= intval($r['buf_nonpure']) ?>">
                                </td>
                                <td class="text-center fw-bold text-dark border-start row-total-cell">0</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-light fw-bold">
                        <tr>
                            <td class="text-danger">Column Subtotals:</td>
                            <td class="text-center border-start text-danger" id="total_cow_pure">0</td>
                            <td class="text-center text-danger" id="total_cow_nonpure">0</td>
                            <td class="text-center border-start text-primary" id="total_buf_pure">0</td>
                            <td class="text-center text-primary" id="total_buf_nonpure">0</td>
                            <td class="text-center border-start fs-6 text-dark" id="grand_total_cell">0</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SECTION 5: FEEDING PRACTICES & FODDER CULTIVATION                         -->
        <!-- ========================================================================= -->
        <div class="card form-card mb-4 p-4">
            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                <div class="section-icon-box bg-success-subtle text-success">
                    <i class="bi bi-flower1"></i>
                </div>
                <div>
                    <h5 class="section-title">05. Feeding &amp; Fodder Management</h5>
                    <div class="section-subtitle">Grass cultivation, silage adoption, and expenditure tracking</div>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label-modern d-block mb-2">5.1 Feed Types &amp; Monthly Expenditure:</label>
                <div class="table-responsive">
                    <table class="table modern-table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 35%;">Feed Classification</th>
                                <th style="width: 30%;" class="text-center">Mainly Fed</th>
                                <th style="width: 35%;" class="text-end">If Purchased, Monthly Expenditure (Rs.)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $feed_labels = [
                                'napier_co3'       => 'Napier / CO-3 Grass',
                                'cultivated_grass' => 'Cultivated Grass (Guinea, Pakchong, etc.)',
                                'commercial_feed'  => 'Commercial Concentrates & Pellets',
                                'other_fodder'     => 'Crop Residues / Straw / Other Fodder',
                            ];
                            foreach ($feed_labels as $fkey => $flbl):
                                $fitem = $feed_types[$fkey];
                            ?>
                                <tr>
                                    <td class="fw-semibold text-dark"><?= $flbl ?></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <input type="radio" class="btn-check" name="feeding_types_data[<?= $fkey ?>][mainly_fed]" id="fed_<?= $fkey ?>_yes" value="Yes" <?= ($fitem['mainly_fed'] === 'Yes') ? 'checked' : '' ?>>
                                            <label class="btn btn-outline-success" for="fed_<?= $fkey ?>_yes">Yes</label>
                                            <input type="radio" class="btn-check" name="feeding_types_data[<?= $fkey ?>][mainly_fed]" id="fed_<?= $fkey ?>_no" value="No" <?= ($fitem['mainly_fed'] === 'No') ? 'checked' : '' ?>>
                                            <label class="btn btn-outline-secondary" for="fed_<?= $fkey ?>_no">No</label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light">Rs.</span>
                                            <input type="number" step="0.01" min="0" name="feeding_types_data[<?= $fkey ?>][expenditure]" class="form-control text-end" value="<?= floatval($fitem['expenditure']) ?>" placeholder="0.00">
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Fodder Cultivation Questions Grid -->
            <div class="p-3 bg-light rounded-3 border">
                <div class="fw-bold text-dark mb-3 border-bottom pb-2">Fodder Cultivation &amp; Processing Details</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label-modern">5.2 Cultivating grass on your own land?</label>
                        <div class="d-flex align-items-center gap-3">
                            <div class="btn-group btn-group-sm" role="group">
                                <input type="radio" class="btn-check" name="cultivating_grass_own_land" id="cg_yes" value="1" <?= ($app_data['cultivating_grass_own_land'] ?? 0) == 1 ? 'checked' : '' ?>>
                                <label class="btn btn-outline-success" for="cg_yes">Yes</label>
                                <input type="radio" class="btn-check" name="cultivating_grass_own_land" id="cg_no" value="0" <?= ($app_data['cultivating_grass_own_land'] ?? 0) == 0 ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary" for="cg_no">No</label>
                            </div>
                            <input type="text" name="own_land_grass_extent" class="form-control form-control-sm form-control-modern" value="<?= htmlspecialchars($app_data['own_land_grass_extent'] ?? '') ?>" placeholder="If yes, extent in Acres">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label-modern">5.3 Type of Grass Cultivated</label>
                        <input type="text" name="type_of_grass" class="form-control form-control-modern" value="<?= htmlspecialchars($app_data['type_of_grass'] ?? '') ?>" placeholder="e.g. CO-3, CO-4, Super Napier, Pakchong, Guinea">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-modern">5.4 Grass Harvesting Method</label>
                        <select name="grass_cutting_method" class="form-select form-select-modern">
                            <option value="Hand" <?= (($app_data['grass_cutting_method'] ?? 'Hand') === 'Hand') ? 'selected' : '' ?>>Manual / Hand Cut</option>
                            <option value="Machinery" <?= (($app_data['grass_cutting_method'] ?? '') === 'Machinery') ? 'selected' : '' ?>>Mechanized Harvester</option>
                            <option value="Both" <?= (($app_data['grass_cutting_method'] ?? '') === 'Both') ? 'selected' : '' ?>>Both Methods</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-modern">5.5 Chop fodder before feeding?</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="chop_fodder_before_feeding" id="cf_yes" value="1" <?= ($app_data['chop_fodder_before_feeding'] ?? 0) == 1 ? 'checked' : '' ?>>
                            <label class="btn btn-outline-success" for="cf_yes">Yes, Chopped</label>
                            <input type="radio" class="btn-check" name="chop_fodder_before_feeding" id="cf_no" value="0" <?= ($app_data['chop_fodder_before_feeding'] ?? 0) == 0 ? 'checked' : '' ?>>
                            <label class="btn btn-outline-secondary" for="cf_no">No, Whole</label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-modern">5.6 Made silage / hay before?</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="made_silage_before" id="ms_yes" value="1" <?= ($app_data['made_silage_before'] ?? 0) == 1 ? 'checked' : '' ?>>
                            <label class="btn btn-outline-success" for="ms_yes">Yes, Experienced</label>
                            <input type="radio" class="btn-check" name="made_silage_before" id="ms_no" value="0" <?= ($app_data['made_silage_before'] ?? 0) == 0 ? 'checked' : '' ?>>
                            <label class="btn btn-outline-secondary" for="ms_no">No</label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-modern">5.7 Willing to make silage?</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="willing_to_make_silage" id="ws_yes" value="1" <?= ($app_data['willing_to_make_silage'] ?? 0) == 1 ? 'checked' : '' ?>>
                            <label class="btn btn-outline-success" for="ws_yes">Yes, Willing</label>
                            <input type="radio" class="btn-check" name="willing_to_make_silage" id="ws_no" value="0" <?= ($app_data['willing_to_make_silage'] ?? 0) == 0 ? 'checked' : '' ?>>
                            <label class="btn btn-outline-secondary" for="ws_no">No</label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-modern">5.8 Currently feed silage?</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="feed_silage" id="fs_yes" value="1" <?= ($app_data['feed_silage'] ?? 0) == 1 ? 'checked' : '' ?>>
                            <label class="btn btn-outline-success" for="fs_yes">Yes, Feeding</label>
                            <input type="radio" class="btn-check" name="feed_silage" id="fs_no" value="0" <?= ($app_data['feed_silage'] ?? 0) == 0 ? 'checked' : '' ?>>
                            <label class="btn btn-outline-secondary" for="fs_no">No</label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label-modern">5.9 Buy agro-industrial by-products?</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="buy_agro_byproducts" id="ab_yes" value="1" <?= ($app_data['buy_agro_byproducts'] ?? 0) == 1 ? 'checked' : '' ?>>
                            <label class="btn btn-outline-success" for="ab_yes">Yes</label>
                            <input type="radio" class="btn-check" name="buy_agro_byproducts" id="ab_no" value="0" <?= ($app_data['buy_agro_byproducts'] ?? 0) == 0 ? 'checked' : '' ?>>
                            <label class="btn btn-outline-secondary" for="ab_no">No</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label-modern">5.10 Buy concentrated feed or mix own balanced ration?</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="buy_concentrated_feed" id="bc_yes" value="1" <?= ($app_data['buy_concentrated_feed'] ?? 0) == 1 ? 'checked' : '' ?>>
                            <label class="btn btn-outline-success" for="bc_yes">Yes, Feed Concentrates / Balanced Ration</label>
                            <input type="radio" class="btn-check" name="buy_concentrated_feed" id="bc_no" value="0" <?= ($app_data['buy_concentrated_feed'] ?? 0) == 0 ? 'checked' : '' ?>>
                            <label class="btn btn-outline-secondary" for="bc_no">No</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label-modern">5.11 Supplement mineral mixture / trace elements?</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="mix_mineral_mixture" id="mm_mix_yes" value="1" <?= ($app_data['mix_mineral_mixture'] ?? 0) == 1 ? 'checked' : '' ?>>
                            <label class="btn btn-outline-success" for="mm_mix_yes">Yes, Mix Minerals</label>
                            <input type="radio" class="btn-check" name="mix_mineral_mixture" id="mm_mix_no" value="0" <?= ($app_data['mix_mineral_mixture'] ?? 0) == 0 ? 'checked' : '' ?>>
                            <label class="btn btn-outline-secondary" for="mm_mix_no">No</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SECTION 6: MILK PRODUCTION & HYGIENE                                      -->
        <!-- ========================================================================= -->
        <div class="card form-card mb-4 p-4">
            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                <div class="section-icon-box bg-primary-subtle text-primary">
                    <i class="bi bi-droplet-half"></i>
                </div>
                <div>
                    <h5 class="section-title">06. Milk Production &amp; Quality Hygiene</h5>
                    <div class="section-subtitle">Daily milk yield shifts, pre-milking cleanliness, and pricing</div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label-modern">6.1 Current Daily Milk Production Breakdown (Liters/day)</label>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-light small">Morning</span>
                                <input type="number" step="0.1" min="0" id="app_milk_morning" name="milk_morning_liters" class="form-control form-control-modern" value="<?= htmlspecialchars($val_milk_morn) ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-light small">Evening</span>
                                <input type="number" step="0.1" min="0" id="app_milk_evening" name="milk_evening_liters" class="form-control form-control-modern" value="<?= htmlspecialchars($val_milk_eve) ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-primary text-white small fw-bold">Total L/day</span>
                                <input type="number" step="0.1" min="0" id="app_milk_total" name="milk_total_liters" class="form-control form-control-modern fw-bold text-primary" value="<?= htmlspecialchars($val_milk_tot) ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-text small text-muted">Total yield automatically calculates from Morning and Evening inputs.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label-modern">6.2 Expected Daily Production (Liters/day)</label>
                    <div class="input-group">
                        <input type="number" step="0.1" min="0" name="milk_expected_liters" class="form-control form-control-modern fw-bold text-success" value="<?= htmlspecialchars($val_milk_exp) ?>">
                        <span class="input-group-text bg-success text-white">L/day</span>
                    </div>
                    <div class="form-text small text-muted">Target daily quota under the development programme.</div>
                </div>

                <div class="col-md-8">
                    <label class="form-label-modern">6.3 Milk Hygiene Measures Taken Before Milking</label>
                    <textarea name="milk_hygiene_measures" rows="2" class="form-control form-control-modern" placeholder="e.g. Udder washing with warm potassium permanganate solution, teat dipping, sanitizing hands and milking utensils"><?= htmlspecialchars($val_hygiene) ?></textarea>
                </div>

                <div class="col-md-4">
                    <label class="form-label-modern">6.4 Prevailing Price of 1 Liter Raw Milk</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">Rs.</span>
                        <input type="number" step="0.01" min="0" name="milk_price_per_liter" class="form-control form-control-modern" value="<?= htmlspecialchars($val_milk_price) ?>" placeholder="e.g. 165.00">
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SECTION 7: MILK MARKETING & RECORD KEEPING                                -->
        <!-- ========================================================================= -->
        <div class="card form-card mb-4 p-4">
            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                <div class="section-icon-box bg-secondary-subtle text-secondary">
                    <i class="bi bi-shop"></i>
                </div>
                <div>
                    <h5 class="section-title">07. Milk Marketing &amp; Record Keeping</h5>
                    <div class="section-subtitle">Commercial sales avenues, collection centers, and bookkeeping habits</div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 border h-100">
                        <label class="form-label-modern d-block">7.1 Selling Milk to Private Collector?</label>
                        <div class="btn-group btn-group-sm w-100 mb-2" role="group">
                            <input type="radio" class="btn-check" name="sell_to_private_collector" id="sp_yes" value="1" <?= ($app_data['sell_to_private_collector'] ?? 0) == 1 ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary" for="sp_yes">Yes, Private Collector</label>
                            <input type="radio" class="btn-check" name="sell_to_private_collector" id="sp_no" value="0" <?= ($app_data['sell_to_private_collector'] ?? 0) == 0 ? 'checked' : '' ?>>
                            <label class="btn btn-outline-secondary" for="sp_no">No</label>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 border h-100">
                        <label class="form-label-modern d-block">7.2 Selling to Organized Collector?</label>
                        <div class="btn-group btn-group-sm w-100 mb-2" role="group">
                            <input type="radio" class="btn-check" name="sell_to_organized_collector" id="so_yes" value="1" <?= ($app_data['sell_to_organized_collector'] ?? 0) == 1 ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary" for="so_yes">Yes, Organized / Cooperative (Milco, Nestlé, etc.)</label>
                            <input type="radio" class="btn-check" name="sell_to_organized_collector" id="so_no" value="0" <?= ($app_data['sell_to_organized_collector'] ?? 0) == 0 ? 'checked' : '' ?>>
                            <label class="btn btn-outline-secondary" for="so_no">No</label>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <label class="form-label-modern">7.3 Quantity Sold &amp; Price per Liter Details</label>
                    <input type="text" name="marketing_details" class="form-control form-control-modern" value="<?= htmlspecialchars($app_data['marketing_details'] ?? '') ?>" placeholder="e.g. 18 Liters/day supplied to Milco center @ Rs. 165.00">
                </div>

                <div class="col-md-4">
                    <label class="form-label-modern">7.4 Farm Bookkeeping &amp; Daily Records</label>
                    <div class="btn-group btn-group-sm w-100" role="group">
                        <input type="radio" class="btn-check" name="keep_daily_records" id="kr_yes" value="1" <?= ($app_data['keep_daily_records'] ?? 0) == 1 ? 'checked' : '' ?>>
                        <label class="btn btn-outline-success" for="kr_yes">Yes, Maintains Accounts</label>
                        <input type="radio" class="btn-check" name="keep_daily_records" id="kr_no" value="0" <?= ($app_data['keep_daily_records'] ?? 0) == 0 ? 'checked' : '' ?>>
                        <label class="btn btn-outline-secondary" for="kr_no">No</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SECTION 8: TRAINING AND TECHNICAL ADVICE                                  -->
        <!-- ========================================================================= -->
        <div class="card form-card mb-4 p-4">
            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                <div class="section-icon-box bg-purple-subtle text-purple" style="background-color: #f3e8ff; color: #7e22ce;">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div>
                    <h5 class="section-title">08. Training &amp; Technical Advice</h5>
                    <div class="section-subtitle">Livestock capacity development programmes completed by farmer</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table modern-table table-hover align-middle mb-0" id="tblTrainings">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Training / Course Name</th>
                            <th style="width: 42%;">Conducting Institute / Agency</th>
                            <th style="width: 8%;" class="text-center no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($training_courses as $idx => $t): ?>
                            <tr>
                                <td>
                                    <input type="text" name="training_courses_data[<?= $idx ?>][course]" class="form-control form-control-sm form-control-modern" value="<?= htmlspecialchars($t['course'] ?? '') ?>" placeholder="e.g. Dairy Cattle Management, Clean Milk Hygiene">
                                </td>
                                <td>
                                    <input type="text" name="training_courses_data[<?= $idx ?>][institute]" class="form-control form-control-sm form-control-modern" value="<?= htmlspecialchars($t['institute'] ?? '') ?>" placeholder="e.g. Department of Animal Production & Health">
                                </td>
                                <td class="text-center no-print">
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="$(this).closest('tr').remove()" title="Remove course">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="mt-3 no-print">
                    <button type="button" class="btn btn-sm btn-outline-dark" id="btnAddTrainingRow">
                        <i class="bi bi-plus-circle me-1"></i> Add Another Course
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SECTION 9: WASTE MANAGEMENT & ANIMAL HEALTH                               -->
        <!-- ========================================================================= -->
        <div class="card form-card mb-4 p-4">
            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                <div class="section-icon-box bg-danger-subtle text-danger">
                    <i class="bi bi-shield-plus"></i>
                </div>
                <div>
                    <h5 class="section-title">09. Waste Management &amp; Preventive Health</h5>
                    <div class="section-subtitle">Bio-waste conversion and mandatory veterinary vaccination coverage</div>
                </div>
            </div>

            <!-- Waste Disposal Options -->
            <div class="mb-4">
                <label class="form-label-modern d-block mb-2">9.1 Farm Waste (Cow Dung) Disposal Methods:</label>
                <div class="row g-2">
                    <div class="col-md-3">
                        <div class="p-2 bg-light rounded border">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="waste_pasture" id="wp_pasture" value="1" <?= ($app_data['waste_pasture'] ?? 0) == 1 ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold small" for="wp_pasture">Direct to Pasture / Field</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-2 bg-light rounded border">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="waste_biogas" id="wp_biogas" value="1" <?= ($app_data['waste_biogas'] ?? 0) == 1 ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold small" for="wp_biogas">Biogas Energy Production</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-2 bg-light rounded border">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="waste_composting" id="wp_compost" value="1" <?= ($app_data['waste_composting'] ?? 0) == 1 ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold small" for="wp_compost">Composting / Organic Fertilizer</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-2 bg-light rounded border">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="waste_sold_manure" id="wp_sold" value="1" <?= ($app_data['waste_sold_manure'] ?? 0) == 1 ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold small" for="wp_sold">Sold as Dried Farm Manure</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <input type="text" name="waste_other" class="form-control form-control-modern" value="<?= htmlspecialchars($app_data['waste_other'] ?? '') ?>" placeholder="Other specific waste management practices">
                    </div>
                </div>
            </div>

            <!-- Health & Training Indicators -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label-modern">9.2 Received clean milk training?</label>
                    <div class="btn-group btn-group-sm w-100" role="group">
                        <input type="radio" class="btn-check" name="training_clean_milk" id="tcm_yes" value="1" <?= ($app_data['training_clean_milk'] ?? 0) == 1 ? 'checked' : '' ?>>
                        <label class="btn btn-outline-success" for="tcm_yes">Yes, Trained</label>
                        <input type="radio" class="btn-check" name="training_clean_milk" id="tcm_no" value="0" <?= ($app_data['training_clean_milk'] ?? 0) == 0 ? 'checked' : '' ?>>
                        <label class="btn btn-outline-secondary" for="tcm_no">No</label>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label-modern">9.3 Measure milk yield regularly?</label>
                    <div class="btn-group btn-group-sm w-100" role="group">
                        <input type="radio" class="btn-check" name="measure_milk_yield" id="mmy_yes" value="1" <?= ($app_data['measure_milk_yield'] ?? 0) == 1 ? 'checked' : '' ?>>
                        <label class="btn btn-outline-success" for="mmy_yes">Yes, Regularly</label>
                        <input type="radio" class="btn-check" name="measure_milk_yield" id="mmy_no" value="0" <?= ($app_data['measure_milk_yield'] ?? 0) == 0 ? 'checked' : '' ?>>
                        <label class="btn btn-outline-secondary" for="mmy_no">No</label>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label-modern">9.4 Participated in animal health camps?</label>
                    <div class="btn-group btn-group-sm w-100" role="group">
                        <input type="radio" class="btn-check" name="participate_health_camps" id="phc_yes" value="1" <?= ($app_data['participate_health_camps'] ?? 0) == 1 ? 'checked' : '' ?>>
                        <label class="btn btn-outline-success" for="phc_yes">Yes, Participated</label>
                        <input type="radio" class="btn-check" name="participate_health_camps" id="phc_no" value="0" <?= ($app_data['participate_health_camps'] ?? 0) == 0 ? 'checked' : '' ?>>
                        <label class="btn btn-outline-secondary" for="phc_no">No</label>
                    </div>
                </div>
            </div>

            <!-- Vaccinations -->
            <div class="p-3 bg-light rounded-3 border">
                <div class="fw-bold text-danger mb-3">
                    <i class="bi bi-shield-check me-1"></i> 9.5 Mandatory Preventive Vaccinations Coverage:
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 bg-white rounded border d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold text-dark small">Hemorrhagic Septicemia (HS)</div>
                                <div class="small text-muted" style="font-size: 0.75rem;">HS Vaccine Protection</div>
                            </div>
                            <div class="btn-group btn-group-sm" role="group">
                                <input type="radio" class="btn-check" name="vaccine_hs" id="vhs_yes" value="1" <?= ($app_data['vaccine_hs'] ?? 0) == 1 ? 'checked' : '' ?>>
                                <label class="btn btn-outline-success" for="vhs_yes">Yes</label>
                                <input type="radio" class="btn-check" name="vaccine_hs" id="vhs_no" value="0" <?= ($app_data['vaccine_hs'] ?? 0) == 0 ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary" for="vhs_no">No</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 bg-white rounded border d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold text-dark small">Black Quarter (BQ)</div>
                                <div class="small text-muted" style="font-size: 0.75rem;">BQ Vaccine Protection</div>
                            </div>
                            <div class="btn-group btn-group-sm" role="group">
                                <input type="radio" class="btn-check" name="vaccine_bq" id="vbq_yes" value="1" <?= ($app_data['vaccine_bq'] ?? 0) == 1 ? 'checked' : '' ?>>
                                <label class="btn btn-outline-success" for="vbq_yes">Yes</label>
                                <input type="radio" class="btn-check" name="vaccine_bq" id="vbq_no" value="0" <?= ($app_data['vaccine_bq'] ?? 0) == 0 ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary" for="vbq_no">No</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 bg-white rounded border d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold text-dark small">Foot and Mouth Disease (FMD)</div>
                                <div class="small text-muted" style="font-size: 0.75rem;">FMD Vaccine Protection</div>
                            </div>
                            <div class="btn-group btn-group-sm" role="group">
                                <input type="radio" class="btn-check" name="vaccine_fmd" id="vfmd_yes" value="1" <?= ($app_data['vaccine_fmd'] ?? 0) == 1 ? 'checked' : '' ?>>
                                <label class="btn btn-outline-success" for="vfmd_yes">Yes</label>
                                <input type="radio" class="btn-check" name="vaccine_fmd" id="vfmd_no" value="0" <?= ($app_data['vaccine_fmd'] ?? 0) == 0 ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary" for="vfmd_no">No</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- FLOATING ACTION TOOLBAR (STICKY BOTTOM)                                   -->
        <!-- ========================================================================= -->
        <div class="sticky-actions-bar py-3 px-4 rounded-3 mb-5 no-print">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <a href="dairy_hub.php?range_id=<?= $param_range_id ?>&tab=approvals" class="btn btn-outline-secondary px-3">
                        <i class="bi bi-arrow-left me-1"></i> Return to Dairy Hub
                    </a>
                    <button type="button" class="btn btn-outline-dark px-3" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print Survey Document
                    </button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="submit" id="btnSubmitApp" class="btn text-white px-4 py-2 fw-bold shadow-sm" style="background-color: #820100; font-size: 0.95rem;">
                        <i class="bi bi-cloud-check-fill me-1"></i> Save Survey Application
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>

<?php
// Buffer page-specific scripts to be injected by includes/footer.php
ob_start();
?>
<script>
    // Real-time calculation of Herd Composition Matrix
    function calculateHerdTotals() {
        let cp = 0,
            cnp = 0,
            bp = 0,
            bnp = 0;

        $('.herd-row').each(function() {
            const row = $(this);
            const r_cp = parseInt(row.find('input[data-type="cow_pure"]').val()) || 0;
            const r_cnp = parseInt(row.find('input[data-type="cow_nonpure"]').val()) || 0;
            const r_bp = parseInt(row.find('input[data-type="buf_pure"]').val()) || 0;
            const r_bnp = parseInt(row.find('input[data-type="buf_nonpure"]').val()) || 0;

            const rowTotal = r_cp + r_cnp + r_bp + r_bnp;
            row.find('.row-total-cell').text(rowTotal);

            cp += r_cp;
            cnp += r_cnp;
            bp += r_bp;
            bnp += r_bnp;
        });

        $('#total_cow_pure').text(cp);
        $('#total_cow_nonpure').text(cnp);
        $('#total_buf_pure').text(bp);
        $('#total_buf_nonpure').text(bnp);

        const grand = cp + cnp + bp + bnp;
        $('#grand_total_cell').text(grand);
        $('#grand_total_badge').text(`Grand Total: ${grand} Animals (${cp + cnp} Cows, ${bp + bnp} Buffaloes)`);
    }

    // Real-time calculation of Milk Production
    function calculateMilkTotals() {
        const m = parseFloat($('#app_milk_morning').val()) || 0;
        const e = parseFloat($('#app_milk_evening').val()) || 0;
        if (m > 0 || e > 0) {
            $('#app_milk_total').val((m + e).toFixed(1));
        }
    }

    // GPS Location Acquisition
    function getCurrentAppGPS() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                const coords = `${pos.coords.latitude.toFixed(6)}, ${pos.coords.longitude.toFixed(6)}`;
                $('#app_geo_coordinates').val(coords);
                Swal.fire({
                    icon: 'success',
                    title: 'Coordinates Detected',
                    text: coords,
                    timer: 1600,
                    showConfirmButton: false
                });
            }, function(err) {
                Swal.fire('GPS Notice', err.message, 'warning');
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
        let appLastFetchedReg = '';
        let appAutoFetchTimer = null;

        function triggerAppAutoFetch(isManual = false) {
            const regVal = $('#app_farm_reg_no').val().trim();
            if (!regVal) {
                if (isManual) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Input Required',
                        text: 'Please input Farm Registration No or NIC.',
                        confirmButtonColor: '#820100'
                    });
                }
                return;
            }

            if (!isManual && regVal === appLastFetchedReg) {
                return;
            }

            const btn = $('#btnAppAutoFetch');
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Fetching...');

            $.ajax({
                url: 'processors/dairy_project_crud.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'lookup_farmer',
                    farm_reg_no: regVal
                },
                success: function(res) {
                    btn.prop('disabled', false).html('<i class="bi bi-cloud-arrow-down-fill me-1"></i> Auto-Fetch');
                    if (res.success && res.found) {
                        appLastFetchedReg = regVal;
                        const f = res.farmer;
                        $('#app_producer_name').val(f.farmer_name || '');
                        $('#app_nic_no').val(f.nic || '');
                        $('#app_address').val(f.address || '');
                        $('#app_contact_mobile').val(f.phone || '');
                        $('#app_telephone_no').val(f.phone || '');

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

                        const $flashed = $('#app_producer_name, #app_nic_no, #app_address, #app_contact_mobile, #app_telephone_no, #app_ds_gn_division, #app_geo_coordinates');
                        $flashed.addClass('auto-fill-flash');
                        setTimeout(() => { $flashed.removeClass('auto-fill-flash'); }, 1400);

                        Swal.fire({
                            icon: 'success',
                            title: 'Master Registry Match!',
                            text: `Auto-populated data for ${f.farmer_name} from ${f.source}.`,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else if (isManual) {
                        Swal.fire('Notice', res.message || 'No matching farmer in registry.', 'info');
                    }
                },
                error: function() {
                    btn.prop('disabled', false).html('<i class="bi bi-cloud-arrow-down-fill me-1"></i> Auto-Fetch');
                    if (isManual) {
                        Swal.fire('Error', 'Connection failure with server.', 'error');
                    }
                }
            });
        }

        $('#btnAppAutoFetch').on('click', function(e) {
            e.preventDefault();
            triggerAppAutoFetch(true);
        });

        $('#app_farm_reg_no').on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                triggerAppAutoFetch(true);
            }
        });

        $('#app_farm_reg_no').on('blur', function() {
            const val = $(this).val().trim();
            if (val.length >= 3) {
                triggerAppAutoFetch(false);
            }
        });

        $('#app_farm_reg_no').on('input', function() {
            clearTimeout(appAutoFetchTimer);
            const val = $(this).val().trim();
            if (val.length >= 4) {
                appAutoFetchTimer = setTimeout(function() {
                    triggerAppAutoFetch(false);
                }, 600);
            }
        });

        // Add training row
        let trainingIndex = <?= count($training_courses) ?>;
        $('#btnAddTrainingRow').on('click', function() {
            trainingIndex++;
            const row = `
            <tr>
                <td>
                    <input type="text" name="training_courses_data[${trainingIndex}][course]" class="form-control form-control-sm form-control-modern" placeholder="Course Name">
                </td>
                <td>
                    <input type="text" name="training_courses_data[${trainingIndex}][institute]" class="form-control form-control-sm form-control-modern" placeholder="Institute / Agency">
                </td>
                <td class="text-center no-print">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="$(this).closest('tr').remove()">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
            $('#tblTrainings tbody').append(row);
        });

        // Save Application Form via AJAX
        $('#modernApplicationForm').on('submit', function(e) {
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
                    submitBtn.prop('disabled', false).html('<i class="bi bi-cloud-check-fill me-1"></i> Save Survey Application');
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Survey Saved Successfully!',
                            text: res.message,
                            confirmButtonColor: '#820100'
                        }).then(() => {
                            window.location.href = `dairy_hub.php?range_id=<?= $param_range_id ?>&tab=approvals&farm_reg_no=${encodeURIComponent(res.farm_reg_no)}`;
                        });
                    } else {
                        Swal.fire('Error Saving Survey', res.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    submitBtn.prop('disabled', false).html('<i class="bi bi-cloud-check-fill me-1"></i> Save Survey Application');
                    Swal.fire('Submission Error', 'Failed to communicate with server: ' + error, 'error');
                }
            });
        });
    });
</script>
<?php
$pageScripts = ob_get_clean();
require_once __DIR__ . '/../../../includes/footer.php';
?>