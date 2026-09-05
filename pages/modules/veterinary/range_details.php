<?php
session_start();
require_once '../../../config/db_connect.php';
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

// Extract base operational keys from the live user session wrapper
$range_id = $_SESSION['range_id'] ?? null;

$range_name = 'Your Range';
$district_name = $_SESSION['district'] ?? 'Your District';
$iframe_url = '';

// Step 1: Query the user's data profile if it's missing from the active session context
if (!empty($user_id)) {
    $user_query = $mysqli->prepare("SELECT range_id, district_id, district FROM users WHERE id = ?");
    if ($user_query) {
        $user_query->bind_param("i", $user_id);
        $user_query->execute();
        $user_result = $user_query->get_result();
        if ($row = $user_result->fetch_assoc()) {
            if (empty($range_id) && !empty($row['range_id'])) {
                $_SESSION['range_id'] = $row['range_id'];
                $range_id = $row['range_id'];
            }
            if (!empty($row['district'])) {
                $district_name = $row['district'];
            }
        }
        $user_query->close();
    }
}

// Step 2: Extract Range Name, District Name, and Map URL using a clean, relational JOIN
if (!empty($range_id)) {
    $details_sql = "
        SELECT 
            vr.name AS range_name,
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
            $district_name = $data['district_name'] ?? $district_name;
            $iframe_url = $data['iframe_url'] ?? '';
        }
        $details_query->close();
    }
}

// Step 3: Fetch assigned tasks for the logged in user
$assigned_action_keys = [];
$assigned_stmt = $mysqli->prepare("SELECT action_id FROM user_quick_action_assignments WHERE user_id = ?");
if ($assigned_stmt) {
    $assigned_stmt->bind_param("i", $user_id);
    $assigned_stmt->execute();
    $assigned_res = $assigned_stmt->get_result();
    while ($row = $assigned_res->fetch_assoc()) {
        $assigned_action_keys[] = $row['action_id'];
    }
    $assigned_stmt->close();
}

// Standard 14 Quick Actions Catalog
$all_quick_actions = [
    'range_statistics' => [
        'id' => 1,
        'title' => 'Range Statistics',
        'icon'  => 'bi-graph-up',
        'color' => '#820100',
        'link'  => 'range_statistics.php'
    ],
    'annual_targets' => [
        'id' => 2,
        'title' => 'Annual Targets',
        'icon'  => 'bi-bar-chart',
        'color' => '#370709',
        'link'  => 'annual_targets.php'
    ],
    'monthly_annual_reports' => [
        'id' => 3,
        'title' => 'Monthly/Annual Reports',
        'icon'  => 'bi-car-front-fill',
        'color' => '#b08723',
        'link'  => 'monthly-annual-reports.php'
    ],
    'regulatory_functions' => [
        'id' => 4,
        'title' => 'Regulatory Functions',
        'icon'  => 'bi-file-earmark-plus',
        'color' => '#a07174',
        'link'  => 'regulatory_functions.php'
    ],
    'animal_health' => [
        'id' => 5,
        'title' => 'Animal Health',
        'icon'  => 'bi-gear-fill',
        'color' => '#689ccf',
        'link'  => 'animal_health.php'
    ],
    'clinical_services' => [
        'id' => 6,
        'title' => 'Clinical Services',
        'icon'  => 'bi-tools',
        'color' => '#2e7d32',
        'link'  => '#'
    ],
    'animal_breeding' => [
        'id' => 7,
        'title' => 'Animal Breeding',
        'icon'  => 'bi-file-earmark-text-fill',
        'color' => '#e65100',
        'link'  => 'animal_breeding.php'
    ],
    'livestock_production' => [
        'id' => 8,
        'title' => 'Livestock Production',
        'icon'  => 'bi-person-bounding-box',
        'color' => '#455a64',
        'link'  => '#'
    ],
    'dairy_hub' => [
        'id' => 9,
        'title' => 'Dairy Hub',
        'icon'  => 'bi-patch-check-fill',
        'color' => '#1565c0',
        'link'  => 'dairy_hub.php'
    ],
    'projects' => [
        'id' => 10,
        'title' => 'Projects',
        'icon'  => 'bi-geo-alt-fill',
        'color' => '#00838f',
        'link'  => 'projects_progress.php'
    ],
    'monitoring' => [
        'id' => 11,
        'title' => 'Monitoring',
        'icon'  => 'bi-folder-fill',
        'color' => '#283593',
        'link'  => 'monitoring.php'
    ],
    'accounts' => [
        'id' => 12,
        'title' => 'Accounts',
        'icon'  => 'bi-bookmark-dash-fill',
        'color' => '#ad1457',
        'link'  => 'accounts.php'
    ],
    'clean_sri_lanka' => [
        'id' => 13,
        'title' => 'Clean Sri Lanka',
        'icon'  => 'bi-graph-up-arrow',
        'color' => '#d84315',
        'link'  => 'clean_sri_lanka.php'
    ],
    'trainings' => [
        'id' => 14,
        'title' => 'Trainings',
        'icon'  => 'bi-sliders',
        'color' => '#37474f',
        'link'  => 'training.php'
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



        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">Range Details</h2>
                <p class="text-muted small mb-0">Official mapping profile metrics dynamically captured for <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong></p>
            </div>
            <?php if (isset($_SESSION['msg'])): ?>
                <div class="alert alert-<?= $_SESSION['msg_type'] ?> py-2 px-3 mb-0 small">
                    <?= $_SESSION['msg'] ?>
                </div>
                <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
            <?php endif; ?>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-12 col-lg-4">
                <div class="card gov-card h-100">
                    <div class="card-header bg-white pt-4 px-4 border-0">
                        <h5 class="fw-bold mb-1" style="color: #370709;"><i class="bi bi-geo-fill me-2"></i>Range Profile</h5>
                        <p class="text-muted small mb-0">Operational indicators assigned to your profile identity.</p>
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

        <div class="card shadow-sm mb-4 border-0">
            <div class="card-header bg-white py-3 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Quick Actions</h6>
                    <?php if (!$is_supervisory && !empty($visible_actions)): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small rounded-pill">
                            <i class="bi bi-check2-circle me-1"></i><?= count($visible_actions) ?> Module(s) Assigned
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

<?php require_once '../../../includes/footer.php'; ?>