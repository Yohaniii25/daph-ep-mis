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

// Step 3: Fetch assigned tasks and module topics dynamically from database
require_once __DIR__ . '/../district/processors/db_migration.php';
ensure_quick_action_assignments_table($mysqli);

// Dynamic module topic fetching for this specific range
$visible_actions = get_assigned_module_topics_for_range(
    $mysqli, 
    $range_id, 
    $range_district_id, 
    !$is_supervisory ? $user_id : null
);

// Check if range has custom override or is inheriting district-wide defaults
$has_custom_range_assignment = false;
if (!empty($range_id)) {
    $chk_override = $mysqli->prepare("SELECT COUNT(*) as c FROM user_quick_action_assignments WHERE range_id = ?");
    if ($chk_override) {
        $chk_override->bind_param("i", $range_id);
        $chk_override->execute();
        $ovr_res = $chk_override->get_result()->fetch_assoc();
        $has_custom_range_assignment = ($ovr_res && (int)$ovr_res['c'] > 0);
        $chk_override->close();
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


        <div class="card shadow-sm mb-4 border-0">
            <div class="card-header bg-white py-3 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Module Topics & Quick Actions</h6>
                    <?php if (!empty($visible_actions)): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 small rounded-pill">
                            <i class="bi bi-check2-circle me-1"></i><?= count($visible_actions) ?> Active Module(s)
                        </span>
                        <?php if ($has_custom_range_assignment): ?>
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1 small rounded-pill">
                                <i class="bi bi-pin-angle-fill me-1"></i>Custom Range Delegation
                            </span>
                        <?php else: ?>
                            <span class="badge bg-light text-muted border px-2.5 py-1 small rounded-pill">
                                <i class="bi bi-globe2 me-1"></i>District Default Delegation
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php if ($is_supervisory && in_array($user_role, ['district_dd', 'deputy_director_district', 'administrator', 'provincial_director'])): ?>
                    <div>
                        <a href="../district/task_assignments.php?range_id=<?= (int)$range_id ?>" class="btn btn-sm text-white shadow-sm" style="background-color: #370709;">
                            <i class="bi bi-sliders me-1"></i> Configure Range Modules
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-body pt-0">
                <?php if ($is_supervisory): ?>
                    <div class="alert alert-light border small py-2 px-3 mb-3 d-flex flex-wrap align-items-center justify-content-between text-muted gap-2">
                        <div>
                            <i class="bi bi-info-circle-fill text-primary me-2"></i>
                            Dynamically displaying active module topics for <strong><?= htmlspecialchars($range_name) ?> Range</strong> as configured by the District Deputy Director.
                        </div>
                        <a href="../district/task_assignments.php?range_id=<?= (int)$range_id ?>" class="small text-decoration-none fw-semibold" style="color: #370709;">
                            <i class="bi bi-pencil-square me-1"></i>Edit Range Modules &rarr;
                        </a>
                    </div>
                <?php endif; ?>

                <?php if (empty($visible_actions)): ?>
                    <div class="py-5 px-4 text-center my-2 rounded-3 bg-light border">
                        <div class="mb-3">
                            <i class="bi bi-shield-lock text-secondary" style="font-size: 3.2rem;"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">No Quick Actions Assigned</h5>
                        <p class="text-muted small mx-auto mb-3" style="max-width: 540px; line-height: 1.6;">
                            Module topics for <strong><?= htmlspecialchars($range_name) ?> Range</strong> are dynamically controlled. 
                            The District Deputy Director has not activated any module topics for this range office yet.
                        </p>
                        <?php if ($is_supervisory && in_array($user_role, ['district_dd', 'deputy_director_district', 'administrator', 'provincial_director'])): ?>
                            <a href="../district/task_assignments.php?range_id=<?= (int)$range_id ?>" class="btn btn-sm text-white shadow-sm px-3 py-2 rounded-pill" style="background-color: #370709;">
                                <i class="bi bi-plus-circle me-1"></i> Assign Modules to <?= htmlspecialchars($range_name) ?> Range
                            </a>
                        <?php endif; ?>
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
$pageScripts = '';
require_once '../../../includes/footer.php';
?>