<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;
require_once __DIR__ . '/../district/processors/db_migration.php';

ensure_quick_action_assignments_table($mysqli);

if (!isset($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    header("Location: ../../../index.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? '';

// Check if user has administrative or supervisory privileges
$is_supervisory = in_array($user_role, [
    'district_dd',
    'deputy_director_district',
    'administrator',
    'provincial_director',
    'deputy_director_hq_1',
    'deputy_director_hq_2'
]);

// Check if range_id was explicitly requested via GET
$requested_range_id = isset($_GET['range_id']) && is_numeric($_GET['range_id']) ? (int)$_GET['range_id'] : null;

// Extract base operational keys from session
$user_range_id = null;
$user_district_id = null;
$user_district_name = $_SESSION['district'] ?? '';

// Step 1: Query the user's data profile (range_id, district_id, district)
if (!empty($user_id)) {
    $user_query = $mysqli->prepare("SELECT range_id, district_id, district FROM users WHERE id = ?");
    if ($user_query) {
        $user_query->bind_param("i", $user_id);
        $user_query->execute();
        $user_result = $user_query->get_result();
        if ($row = $user_result->fetch_assoc()) {
            $user_range_id = !empty($row['range_id']) ? (int)$row['range_id'] : null;
            $user_district_id = !empty($row['district_id']) ? (int)$row['district_id'] : null;
            if (!empty($row['district'])) {
                $user_district_name = $row['district'];
            }
        }
        $user_query->close();
    }
}

// Fallback: If user_district_id is not set on user, resolve from their primary range
if (empty($user_district_id) && !empty($user_range_id)) {
    $rng_dist_q = $mysqli->prepare("SELECT district_id FROM veterinary_ranges WHERE id = ?");
    if ($rng_dist_q) {
        $rng_dist_q->bind_param("i", $user_range_id);
        $rng_dist_q->execute();
        if ($r_row = $rng_dist_q->get_result()->fetch_assoc()) {
            $user_district_id = (int)$r_row['district_id'];
        }
        $rng_dist_q->close();
    }
}

// Determine active range_id based on role and assignment:
// Non-supervisory veterinary users can ONLY access their own assigned range
if (!$is_supervisory) {
    $assigned_range = $user_range_id ?: (!empty($_SESSION['range_id']) ? (int)$_SESSION['range_id'] : null);

    // If an external range_id was requested via GET and doesn't match their assigned range, redirect
    if ($requested_range_id !== null && $requested_range_id !== $assigned_range) {
        header("Location: range_details.php");
        exit();
    }

    $range_id = $assigned_range;
} else {
    // Supervisory roles (Admin, Provincial Director, District DD) can view specific ranges via GET or fallback
    $range_id = $requested_range_id ?: ($user_range_id ?: (!empty($_SESSION['range_id']) ? (int)$_SESSION['range_id'] : null));

    // If still empty and we know user_district_id, default to first range in that district
    if (empty($range_id) && !empty($user_district_id)) {
        $first_rng = $mysqli->prepare("SELECT id FROM veterinary_ranges WHERE district_id = ? ORDER BY id ASC LIMIT 1");
        if ($first_rng) {
            $first_rng->bind_param("i", $user_district_id);
            $first_rng->execute();
            if ($fr_row = $first_rng->get_result()->fetch_assoc()) {
                $range_id = (int)$fr_row['id'];
            }
            $first_rng->close();
        }
    }
}

// Step 2: Extract Range Name, District Name, and Map URL using a clean, relational JOIN
$range_name = 'Your Range';
$range_district_id = $user_district_id;
$district_name = $user_district_name ?: 'Your District';
$iframe_url = '';

if (!empty($range_id)) {
    $details_sql = "
        SELECT 
            vr.id AS range_id,
            vr.name AS range_name,
            vr.district_id,
            d.name AS district_name,
            vrm.iframe_url
        FROM veterinary_ranges vr
        LEFT JOIN districts d ON vr.district_id = d.id
        LEFT JOIN veterinary_range_maps vrm ON vr.id = vrm.range_id
        WHERE vr.id = ?
    ";

    $details_query = $mysqli->prepare($details_sql);
    if ($details_query) {
        $details_query->bind_param("i", $range_id);
        $details_query->execute();
        $details_result = $details_query->get_result();
        if ($data = $details_result->fetch_assoc()) {
            $range_name = $data['range_name'] ?? 'Your Assigned Range';
            $range_district_id = !empty($data['district_id']) ? (int)$data['district_id'] : $user_district_id;
            if (!empty($data['district_name'])) {
                $district_name = $data['district_name'];
            }
            $iframe_url = $data['iframe_url'] ?? '';
        }
        $details_query->close();
    }
}

// Step 3: Fetch assigned tasks for the logged-in user
// District-Wide Permission Scope:
// When a District Deputy Director assigns specific Quick Actions to a Veterinary Surgeon,
// those actions automatically apply to all veterinary ranges within that district.
$assigned_action_keys = [];
if (!$is_supervisory) {
    $target_dist_id = $range_district_id ?: $user_district_id;
    $assigned_stmt = $mysqli->prepare("
        SELECT DISTINCT action_id 
        FROM user_quick_action_assignments 
        WHERE (
            user_id = ? 
            OR (
                target_role IN ('government_veterinary_surgeon', 'veterinary_surgeon')
                AND (district_id = ? OR district_id IS NULL)
            )
        )
        AND (
            district_id = ? 
            OR district_id IS NULL
            OR range_id = ?
        )
    ");
    if ($assigned_stmt) {
        $assigned_stmt->bind_param("iiii", $user_id, $target_dist_id, $target_dist_id, $range_id);
        $assigned_stmt->execute();
        $assigned_res = $assigned_stmt->get_result();
        while ($row = $assigned_res->fetch_assoc()) {
            $assigned_action_keys[] = $row['action_id'];
        }
        $assigned_stmt->close();
    }
}

// Step 4: Fetch registered milk producers for active range to display in summary
$range_producers = [];
if (!empty($range_id)) {
    $mp_sql = "
        SELECT 
            COALESCE(r.registration_no, f.farm_registration_no, a.farm_reg_no) AS farm_reg_no,
            COALESCE(r.farmer_name, f.full_name, a.producer_name) AS farmer_name,
            COALESCE(r.nic, f.nic_no, a.nic_no) AS nic,
            COALESCE(r.telephone_no, f.contact_no, a.contact_mobile) AS phone,
            COALESCE(r.farmer_address, f.location_address, a.address) AS address,
            COALESCE(r.gender, f.gender, a.gender, 'Male') AS gender,
            COALESCE(r.ethnicity, f.ethnicity, a.ethnicity, 'Sinhala') AS ethnicity,
            COALESCE(r.milk_production_volume, f.milk_production_volume, a.milk_production_volume, '5–10 liters') AS milk_production_volume
        FROM farm_registration_renewals r
        LEFT JOIN farmers f ON r.registration_no = f.farm_registration_no
        LEFT JOIN dairy_farm_applications a ON r.registration_no = a.farm_reg_no
        WHERE r.range_id = ?
        ORDER BY r.id DESC LIMIT 8
    ";
    $mp_stmt = $mysqli->prepare($mp_sql);
    if ($mp_stmt) {
        $mp_stmt->bind_param("i", $range_id);
        $mp_stmt->execute();
        $range_producers = $mp_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $mp_stmt->close();
    }

    if (empty($range_producers)) {
        $f_stmt = $mysqli->prepare("
            SELECT 
                farm_registration_no AS farm_reg_no,
                full_name AS farmer_name,
                nic_no AS nic,
                contact_no AS phone,
                location_address AS address,
                COALESCE(gender, 'Male') AS gender,
                COALESCE(ethnicity, 'Sinhala') AS ethnicity,
                COALESCE(milk_production_volume, '5–10 liters') AS milk_production_volume
            FROM farmers
            WHERE range_id = ? AND farm_registration_no IS NOT NULL AND farm_registration_no != ''
            ORDER BY id DESC LIMIT 8
        ");
        if ($f_stmt) {
            $f_stmt->bind_param("i", $range_id);
            $f_stmt->execute();
            $range_producers = $f_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $f_stmt->close();
        }
    }
}

// Standard 14 Quick Actions Catalog
$all_quick_actions = [
    'range_statistics' => [
        'id' => 1,
        'title' => 'Range Statistics',
        'icon'  => 'bi-graph-up',
        'color' => '#820100',
        'link'  => 'range_statistics.php' . ($range_id ? '?range_id=' . $range_id : '')
    ],
    'annual_targets' => [
        'id' => 2,
        'title' => 'Annual Targets',
        'icon'  => 'bi-bar-chart',
        'color' => '#370709',
        'link'  => 'annual_targets.php' . ($range_id ? '?range_id=' . $range_id : '')
    ],
    'monthly_annual_reports' => [
        'id' => 3,
        'title' => 'Monthly/Annual Reports',
        'icon'  => 'bi-car-front-fill',
        'color' => '#b08723',
        'link'  => 'monthly-annual-reports.php' . ($range_id ? '?range_id=' . $range_id : '')
    ],
    'regulatory_functions' => [
        'id' => 4,
        'title' => 'Regulatory Functions',
        'icon'  => 'bi-file-earmark-plus',
        'color' => '#a07174',
        'link'  => 'regulatory_functions.php' . ($range_id ? '?range_id=' . $range_id : '')
    ],
    'animal_health' => [
        'id' => 5,
        'title' => 'Animal Health',
        'icon'  => 'bi-gear-fill',
        'color' => '#689ccf',
        'link'  => 'animal_health.php' . ($range_id ? '?range_id=' . $range_id : '')
    ],
    'clinical_services' => [
        'id' => 6,
        'title' => 'Clinical Services',
        'icon'  => 'bi-clipboard2-pulse',
        'color' => '#2e7d32',
        'link'  => 'clinical_services.php' . ($range_id ? '?range_id=' . $range_id : '')
    ],
    'citizen_charter' => [
        'id' => 8,
        'title' => 'Other Services (Citizen Charter)',
        'icon'  => 'bi-file-earmark-person-fill',
        'color' => '#b45309',
        'link'  => 'citizen_charter.php' . ($range_id ? '?range_id=' . $range_id : '')
    ],
    'animal_breeding' => [
        'id' => 7,
        'title' => 'Breeding and Production',
        'icon'  => 'bi-file-earmark-text-fill',
        'color' => '#e65100',
        'link'  => 'animal_breeding.php' . ($range_id ? '?range_id=' . $range_id : '')
    ],
    'dairy_hub' => [
        'id' => 9,
        'title' => 'Dairy Hub',
        'icon'  => 'bi-patch-check-fill',
        'color' => '#1565c0',
        'link'  => 'dairy_hub.php' . ($range_id ? '?range_id=' . $range_id : '')
    ],
    'projects_monitoring' => [
        'id' => 10,
        'title' => 'Projects and Monitoring',
        'icon'  => 'bi-kanban-fill',
        'color' => '#00838f',
        'link'  => 'projects_progress.php' . ($range_id ? '?range_id=' . $range_id : '')
    ],
    'accounts' => [
        'id' => 12,
        'title' => 'Accounts',
        'icon'  => 'bi-bookmark-dash-fill',
        'color' => '#ad1457',
        'link'  => 'accounts.php' . ($range_id ? '?range_id=' . $range_id : '')
    ],
    'clean_sri_lanka' => [
        'id' => 13,
        'title' => 'Clean Sri Lanka',
        'icon'  => 'bi-graph-up-arrow',
        'color' => '#d84315',
        'link'  => 'clean_sri_lanka.php' . ($range_id ? '?range_id=' . $range_id : '')
    ],
    'trainings' => [
        'id' => 14,
        'title' => 'Trainings',
        'icon'  => 'bi-sliders',
        'color' => '#37474f',
        'link'  => 'training.php' . ($range_id ? '?range_id=' . $range_id : '')
    ]
];

// Determine visible actions based on delegation:
// - Supervisory roles see all 14 actions
// - Subordinate officers see only explicitly assigned actions
if ($is_supervisory) {
    $visible_actions = $all_quick_actions;
} else {
    $visible_actions = [];
    foreach ($all_quick_actions as $key => $act) {
        if (in_array($key, $assigned_action_keys)) {
            $visible_actions[$key] = $act;
        }
    }
}

require_once '../../../includes/header.php';

?>

<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">



        <div class="mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">Range Details</h2>
                <p class="text-muted small mb-0">Official mapping profile metrics dynamically captured for <strong class="text-dark"><?= htmlspecialchars($range_name) ?> Range</strong> (<?= htmlspecialchars($district_name) ?> District)</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <?php if (isset($_SESSION['msg'])): ?>
                    <div class="alert alert-<?= $_SESSION['msg_type'] ?> py-2 px-3 mb-0 small">
                        <?= $_SESSION['msg'] ?>
                    </div>
                    <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($range_id)): ?>
            <div class="alert alert-warning border d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5 text-warning flex-shrink-0"></i>
                <div>
                    <strong>No Range Assigned:</strong> Your user account is currently not assigned to a specific veterinary range. Please contact your District Deputy Director or MIS Administrator.
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-4">
            <div class="col-12 col-lg-4">
                <div class="card gov-card h-100">
                    <div class="card-header bg-white pt-4 px-4 border-0">
                        <h5 class="fw-bold mb-1" style="color: #370709;"><i class="bi bi-geo-fill me-2"></i>Range Profile</h5>
                        <p class="text-muted small mb-0">Operational indicators for active range viewport.</p>
                    </div>
                    <div class="card-body px-4">
                        <div class="table-responsive">
                            <table class="table table-bordered table-profile align-middle m-0">
                                <tbody>
                                    <tr>
                                        <th>Range ID</th>
                                        <td class="font-monospace fw-bold text-secondary"><?= htmlspecialchars($range_id ?? '-') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Range Name</th>
                                        <td class="fw-bold" style="color: #370709;"><?= htmlspecialchars($range_name) ?></td>
                                    </tr>
                                    <tr>
                                        <th>District</th>
                                        <td><?= htmlspecialchars($district_name) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Permission Scope</th>
                                        <td>
                                            <?php if ($is_supervisory): ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                    <i class="bi bi-globe2 me-1"></i><?= !empty($district_name) && $district_name !== 'Your District' ? htmlspecialchars($district_name) . ' District-Wide' : 'Supervisory' ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    <i class="bi bi-geo-alt-fill me-1"></i>Assigned Range (<?= htmlspecialchars($range_name) ?>)
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-8">
                <div class="card gov-card h-100">
                    <div class="card-header bg-white pt-4 px-4 border-0">
                        <h5 class="fw-bold mb-1" style="color: #370709;"><i class="bi bi-map-fill me-2"></i>Range Map View</h5>
                        <p class="text-muted small mb-0">Live interactive coordinate reference tracking viewport.</p>
                    </div>
                    <div class="card-body px-4 pb-4 pt-1">

                        <div class="map-frame-wrapper shadow-sm">
                            <?php if (!empty($iframe_url) && filter_var($iframe_url, FILTER_VALIDATE_URL)): ?>

                                <iframe
                                    src="<?= htmlspecialchars($iframe_url, ENT_QUOTES, 'UTF-8') ?>"
                                    width="100%"
                                    height="420"
                                    style="border:0;"
                                    allowfullscreen=""
                                    loading="lazy">
                                </iframe>

                            <?php else: ?>
                                <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center bg-light text-muted" style="min-height: 420px;">
                                    <i class="bi bi-exclamation-triangle mb-2 h3 text-warning"></i>
                                    <span class="small fw-semibold">No valid map URL configured for this range.</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Milk Producers Particulars (Range Details) -->
        <div class="card shadow-sm mb-4 border-0" id="milkProducersParticularsCard">
            <div class="card-header bg-white py-3 px-4 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="fw-bold mb-1" style="color: #370709;">
                        <i class="bi bi-person-lines-fill me-2"></i>Milk Producers Particulars
                    </h5>
                    <p class="text-muted small mb-0">Manage registered milk producer profiles, demographic reporting, and standardized production ranges for this range.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill small">
                        <i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($range_name) ?>
                    </span>
                </div>
            </div>
            <div class="card-body px-4 pt-2 pb-4">
                <form id="formMilkProducerParticulars" autocomplete="off">
                    <input type="hidden" name="range_id" id="mp_range_id" value="<?= (int)$range_id ?>">
                    
                    <div class="alert alert-light border small py-2 px-3 mb-3 d-flex align-items-center text-muted">
                        <i class="bi bi-info-circle-fill text-primary me-2 fs-5"></i>
                        <span>
                            Enter an existing <strong>Farm Registration Number</strong> (e.g., <code>530112345</code>) to automatically fetch and populate historical producer data.
                            Demographic fields (<strong>Sex</strong> &amp; <strong>Ethnicity</strong>) and standardized <strong>Milk Production Ranges</strong> are strictly required.
                        </span>
                    </div>

                    <div class="row g-3">
                        <!-- Farm Registration Number with Auto-Fill -->
                        <div class="col-md-4">
                            <label for="mp_farm_reg_no" class="form-label fw-semibold small text-secondary">
                                Farm Registration Number <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted"><i class="bi bi-qr-code"></i></span>
                                <input type="text" class="form-control" id="mp_farm_reg_no" name="farm_reg_no" placeholder="e.g. 530112345" required>
                                <button type="button" class="btn btn-outline-primary" id="btnAutoFetchProducer" title="Fetch historical farmer details">
                                    <i class="bi bi-arrow-repeat me-1"></i> Auto-Fill
                                </button>
                            </div>
                            <div id="mp_autofill_status" class="form-text small mt-1"></div>
                        </div>

                        <!-- Producer's Full Name -->
                        <div class="col-md-5">
                            <label for="mp_farmer_name" class="form-label fw-semibold small text-secondary">
                                Milk Producer's Full Name <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="mp_farmer_name" name="farmer_name" placeholder="Full name of milk producer" required>
                            </div>
                        </div>

                        <!-- Identity Card Number (NIC) -->
                        <div class="col-md-3">
                            <label for="mp_nic" class="form-label fw-semibold small text-secondary">
                                Identity Card No (NIC) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted"><i class="bi bi-card-heading"></i></span>
                                <input type="text" class="form-control" id="mp_nic" name="nic" placeholder="e.g. 198512345678" required>
                            </div>
                        </div>

                        <!-- Sex Dropdown (Mandatory) -->
                        <div class="col-md-3">
                            <label for="mp_sex" class="form-label fw-semibold small text-secondary">
                                Sex <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="mp_sex" name="sex" required>
                                <option value="">-- Select Sex --</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>

                        <!-- Ethnicity Dropdown (Mandatory) -->
                        <div class="col-md-3">
                            <label for="mp_ethnicity" class="form-label fw-semibold small text-secondary">
                                Ethnicity <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="mp_ethnicity" name="ethnicity" required>
                                <option value="">-- Select Ethnicity --</option>
                                <option value="Sinhala">Sinhala</option>
                                <option value="Tamil">Tamil</option>
                                <option value="Muslim">Muslim</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <!-- Contact Mobile -->
                        <div class="col-md-3">
                            <label for="mp_phone" class="form-label fw-semibold small text-secondary">
                                Contact Phone Number <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted"><i class="bi bi-telephone"></i></span>
                                <input type="text" class="form-control" id="mp_phone" name="phone" placeholder="07XXXXXXXX" required>
                            </div>
                        </div>

                        <!-- Standardized Milk Production Range (Strict Dropdown) -->
                        <div class="col-md-3">
                            <label for="mp_milk_production_volume" class="form-label fw-semibold small text-secondary">
                                Milk Production Volume <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="mp_milk_production_volume" name="milk_production_volume" required>
                                <option value="">-- Select Production Range --</option>
                                <option value="Below 5 liters">Below 5 liters</option>
                                <option value="5–10 liters">5–10 liters</option>
                                <option value="10–20 liters">10–20 liters</option>
                                <option value="20–40 liters">20–40 liters</option>
                                <option value="40–100 liters">40–100 liters</option>
                                <option value="Above 100 liters">Above 100 liters</option>
                            </select>
                        </div>

                        <!-- Address -->
                        <div class="col-md-7">
                            <label for="mp_address" class="form-label fw-semibold small text-secondary">
                                Residential / Farm Location Address <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" class="form-control" id="mp_address" name="address" placeholder="Full postal or farm location address" required>
                            </div>
                        </div>

                        <!-- D.S. & G.N. Division -->
                        <div class="col-md-5">
                            <label for="mp_ds_gn_division" class="form-label fw-semibold small text-secondary">
                                D.S. &amp; G.N. Division
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted"><i class="bi bi-pin-map"></i></span>
                                <input type="text" class="form-control" id="mp_ds_gn_division" name="ds_gn_division" placeholder="e.g. Medirigiriya / Div 04">
                            </div>
                        </div>

                        <div class="col-12 d-flex justify-content-end gap-2 pt-2">
                            <button type="button" class="btn btn-light border px-3" id="btnResetProducerForm">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Clear Form
                            </button>
                            <button type="submit" class="btn text-white px-4 shadow-sm" style="background-color: #370709;" id="btnSubmitProducer">
                                <i class="bi bi-check2-circle me-1"></i> Save Milk Producer Particulars
                            </button>
                        </div>
                    </div>
                </form>

                <?php if (!empty($range_producers)): ?>
                    <hr class="my-4 text-muted">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-clock-history me-1 text-secondary"></i> Recently Registered / Updated Producers in Range
                        </h6>
                        <span class="text-muted small">Showing up to <?= count($range_producers) ?> records</span>
                    </div>
                    <div class="table-responsive border rounded-3 bg-white">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Farm Reg No</th>
                                    <th>Producer Name</th>
                                    <th>Sex</th>
                                    <th>Ethnicity</th>
                                    <th>Contact</th>
                                    <th>Production Range</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($range_producers as $rp): ?>
                                    <tr>
                                        <td class="fw-bold text-primary font-monospace"><?= htmlspecialchars($rp['farm_reg_no'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($rp['farmer_name'] ?? 'N/A') ?></td>
                                        <td><span class="badge bg-secondary-subtle text-secondary border"><?= htmlspecialchars($rp['gender'] ?? 'N/A') ?></span></td>
                                        <td><span class="badge bg-info-subtle text-info border"><?= htmlspecialchars($rp['ethnicity'] ?? 'N/A') ?></span></td>
                                        <td><?= htmlspecialchars($rp['phone'] ?? 'N/A') ?></td>
                                        <td><span class="badge bg-success-subtle text-success border fw-semibold"><?= htmlspecialchars($rp['milk_production_volume'] ?? 'N/A') ?></span></td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 btn-load-producer" 
                                                data-reg="<?= htmlspecialchars($rp['farm_reg_no'] ?? '') ?>"
                                                data-name="<?= htmlspecialchars($rp['farmer_name'] ?? '') ?>"
                                                data-nic="<?= htmlspecialchars($rp['nic'] ?? '') ?>"
                                                data-sex="<?= htmlspecialchars($rp['gender'] ?? 'Male') ?>"
                                                data-ethnicity="<?= htmlspecialchars($rp['ethnicity'] ?? 'Sinhala') ?>"
                                                data-phone="<?= htmlspecialchars($rp['phone'] ?? '') ?>"
                                                data-volume="<?= htmlspecialchars($rp['milk_production_volume'] ?? '5–10 liters') ?>"
                                                data-address="<?= htmlspecialchars($rp['address'] ?? '') ?>">
                                                <i class="bi bi-pencil-square me-1"></i> Load to Form
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm mb-4 border-0">
            <div class="card-header bg-white py-3 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Quick Actions</h6>
                    <?php if (!$is_supervisory && !empty($visible_actions)): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small rounded-pill">
                            <i class="bi bi-check2-circle me-1"></i><?= count($visible_actions) ?> Module(s) Assigned
                        </span>
                        <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 small rounded-pill">
                            <i class="bi bi-geo-alt-fill me-1"></i>Assigned Range (<?= htmlspecialchars($range_name) ?>)
                        </span>
                    <?php endif; ?>
                </div>
                <?php if ($is_supervisory && in_array($user_role, ['district_dd', 'deputy_director_district'])): ?>
                    <div>
                        <a href="../district/task_assignments.php" class="btn btn-sm text-white shadow-sm" style="background-color: #370709;">
                            <i class="bi bi-person-check-fill me-1"></i> Manage Task Delegations
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-body pt-0">
                <?php if ($is_supervisory): ?>
                    <div class="alert alert-light border small py-2 px-3 mb-3 d-flex align-items-center justify-content-between text-muted">
                        <div>
                            <i class="bi bi-info-circle-fill text-primary me-2"></i>
                            Viewing full catalog as <strong><?= ucwords(str_replace('_', ' ', $user_role)) ?></strong>. Subordinates only view modules explicitly delegated to them.
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (empty($visible_actions)): ?>
                    <div class="py-5 px-4 text-center my-2 rounded-3 bg-light border">
                        <div class="mb-3">
                            <i class="bi bi-shield-lock text-secondary" style="font-size: 3.2rem;"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">No Quick Actions Assigned</h5>
                        <p class="text-muted small mx-auto mb-0" style="max-width: 540px; line-height: 1.6;">
                            Quick Action buttons are restricted and hidden by default. 
                            Your District Deputy Director has not delegated any operational modules (such as Clinical Services, Animal Health, or Range Statistics) to your profile yet. 
                            Please contact your District Deputy Director for task delegation.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-3">
                        <?php foreach ($visible_actions as $action_key => $act): ?>
                            <div class="col">
                                <a href="<?= htmlspecialchars($act['link']) ?>" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: <?= $act['color'] ?>; min-height: 105px;">
                                    <i class="bi <?= $act['icon'] ?> fs-3 mb-1"></i>
                                    <span class="text-center"><?= htmlspecialchars($act['title']) ?></span>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

<?php
ob_start();
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const regInput = document.getElementById('mp_farm_reg_no');
    const autoFetchBtn = document.getElementById('btnAutoFetchProducer');
    const statusDiv = document.getElementById('mp_autofill_status');
    const producerForm = document.getElementById('formMilkProducerParticulars');
    const resetBtn = document.getElementById('btnResetProducerForm');

    let debounceTimer = null;

    function fetchProducerData(regNo, manualTrigger = false) {
        regNo = (regNo || '').trim();
        if (!regNo) {
            if (manualTrigger && statusDiv) {
                statusDiv.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-circle me-1"></i>Please enter a Farm Registration Number first.</span>';
            }
            return;
        }

        if (statusDiv) {
            statusDiv.innerHTML = '<span class="text-primary"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Checking historical records...</span>';
        }

        fetch('processors/milk_producer_crud.php?action=lookup_producer&farm_reg_no=' + encodeURIComponent(regNo))
            .then(res => res.json())
            .then(data => {
                if (data.success && data.found) {
                    const p = data.producer;
                    const nameEl = document.getElementById('mp_farmer_name');
                    const nicEl = document.getElementById('mp_nic');
                    const phoneEl = document.getElementById('mp_phone');
                    const addrEl = document.getElementById('mp_address');
                    const dsEl = document.getElementById('mp_ds_gn_division');
                    const sexEl = document.getElementById('mp_sex');
                    const ethEl = document.getElementById('mp_ethnicity');
                    const volEl = document.getElementById('mp_milk_production_volume');

                    if (nameEl && p.farmer_name) nameEl.value = p.farmer_name;
                    if (nicEl && p.nic) nicEl.value = p.nic;
                    if (phoneEl && p.phone) phoneEl.value = p.phone;
                    if (addrEl && p.address) addrEl.value = p.address;
                    if (dsEl && p.ds_gn_division) dsEl.value = p.ds_gn_division;
                    if (sexEl && p.sex) sexEl.value = p.sex;
                    if (ethEl && p.ethnicity) ethEl.value = p.ethnicity;
                    if (volEl && p.milk_production_volume) volEl.value = p.milk_production_volume;

                    if (statusDiv) {
                        statusDiv.innerHTML = '<span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>' + (data.message || 'Historical data populated successfully!') + '</span>';
                    }

                    if (typeof Swal !== 'undefined' && manualTrigger) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Data Auto-Populated',
                            text: 'Found record for ' + (p.farmer_name || regNo) + ' (' + (p.source || 'Historical records') + ').',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                } else if (data.success && !data.found) {
                    if (statusDiv) {
                        statusDiv.innerHTML = '<span class="text-muted"><i class="bi bi-info-circle me-1"></i>New registration number. Enter details manually.</span>';
                    }
                    if (manualTrigger && typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'info',
                            title: 'No Historical Data Found',
                            text: 'No prior records found for "' + regNo + '". You can complete and save new producer particulars.',
                            timer: 2500,
                            showConfirmButton: false
                        });
                    }
                } else {
                    if (statusDiv) {
                        statusDiv.innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>' + (data.message || 'Lookup encountered an issue.') + '</span>';
                    }
                }
            })
            .catch(err => {
                console.error('Error fetching producer:', err);
                if (statusDiv) {
                    statusDiv.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Error connecting to lookup service.</span>';
                }
            });
    }

    if (regInput) {
        regInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const val = this.value.trim();
            if (val.length >= 4) {
                debounceTimer = setTimeout(() => {
                    fetchProducerData(val, false);
                }, 500);
            } else if (statusDiv) {
                statusDiv.innerHTML = '';
            }
        });

        regInput.addEventListener('blur', function () {
            const val = this.value.trim();
            if (val.length >= 3) {
                fetchProducerData(val, false);
            }
        });
    }

    if (autoFetchBtn) {
        autoFetchBtn.addEventListener('click', function () {
            const val = regInput ? regInput.value.trim() : '';
            fetchProducerData(val, true);
        });
    }

    // Reset button
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            if (producerForm) producerForm.reset();
            if (statusDiv) statusDiv.innerHTML = '';
        });
    }

    // Form submission
    if (producerForm) {
        producerForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const sex = document.getElementById('mp_sex') ? document.getElementById('mp_sex').value : '';
            const ethnicity = document.getElementById('mp_ethnicity') ? document.getElementById('mp_ethnicity').value : '';
            const volume = document.getElementById('mp_milk_production_volume') ? document.getElementById('mp_milk_production_volume').value : '';

            if (!sex) {
                if (typeof Swal !== 'undefined') Swal.fire('Required Field', 'Please select Sex (Male / Female).', 'warning');
                return;
            }
            if (!ethnicity) {
                if (typeof Swal !== 'undefined') Swal.fire('Required Field', 'Please select Ethnicity.', 'warning');
                return;
            }
            if (!volume) {
                if (typeof Swal !== 'undefined') Swal.fire('Required Field', 'Please select a Milk Production Volume range.', 'warning');
                return;
            }

            const formData = new FormData(producerForm);
            const submitBtn = document.getElementById('btnSubmitProducer');
            const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...';
            }

            fetch('processors/milk_producer_crud.php?action=save_producer', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }

                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Saved Successfully',
                            text: data.message || 'Milk Producer Particulars saved successfully!',
                            confirmButtonColor: '#370709'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        alert(data.message || 'Saved successfully!');
                        window.location.reload();
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: data.message || 'Failed to save particulars.',
                            confirmButtonColor: '#370709'
                        });
                    } else {
                        alert(data.message || 'Error saving producer.');
                    }
                }
            })
            .catch(err => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
                console.error('Save error:', err);
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Network Error', 'Could not complete the request. Please try again.', 'error');
                } else {
                    alert('Network error.');
                }
            });
        });
    }

    // Load from recent table
    document.querySelectorAll('.btn-load-producer').forEach(btn => {
        btn.addEventListener('click', function () {
            const reg = this.getAttribute('data-reg') || '';
            const name = this.getAttribute('data-name') || '';
            const nic = this.getAttribute('data-nic') || '';
            const sex = this.getAttribute('data-sex') || 'Male';
            const ethnicity = this.getAttribute('data-ethnicity') || 'Sinhala';
            const phone = this.getAttribute('data-phone') || '';
            const volume = this.getAttribute('data-volume') || '5–10 liters';
            const address = this.getAttribute('data-address') || '';

            if (regInput) regInput.value = reg;
            const nameEl = document.getElementById('mp_farmer_name');
            const nicEl = document.getElementById('mp_nic');
            const sexEl = document.getElementById('mp_sex');
            const ethEl = document.getElementById('mp_ethnicity');
            const phoneEl = document.getElementById('mp_phone');
            const volEl = document.getElementById('mp_milk_production_volume');
            const addrEl = document.getElementById('mp_address');

            if (nameEl) nameEl.value = name;
            if (nicEl) nicEl.value = nic;
            if (sexEl) sexEl.value = sex;
            if (ethEl) ethEl.value = ethnicity;
            if (phoneEl) phoneEl.value = phone;
            if (volEl) volEl.value = volume;
            if (addrEl) addrEl.value = address;

            if (statusDiv) {
                statusDiv.innerHTML = '<span class="text-success"><i class="bi bi-pencil me-1"></i>Loaded producer ' + reg + ' for editing.</span>';
            }

            const card = document.getElementById('milkProducersParticularsCard');
            if (card) {
                card.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
});
</script>
<?php
$pageScripts = ob_get_clean();
require_once '../../../includes/footer.php';
?>