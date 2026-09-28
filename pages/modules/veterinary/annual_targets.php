<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

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
    'super_admin'
];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    header("Location: ../../../../index.php");
    exit();
}

if (!isset($_SESSION['full_name'])) {
    $_SESSION['full_name'] = $_SESSION['username'] ?? 'Officer';
}

$full_name   = $_SESSION['full_name'];
$range_id    = isset($_GET['range_id']) && intval($_GET['range_id']) > 0 ? intval($_GET['range_id']) : ($_SESSION['range_id'] ?? null);
$district_id = $_SESSION['district_id'] ?? null;

if (empty($range_id)) {
    if ($district_id) {
        $r_stmt = $mysqli->prepare("SELECT id FROM veterinary_ranges WHERE district_id = ? LIMIT 1");
        $r_stmt->bind_param("i", $district_id);
        $r_stmt->execute();
        if ($r_row = $r_stmt->get_result()->fetch_assoc()) {
            $range_id = $r_row['id'];
        }
        $r_stmt->close();
    } else {
        $r_res = $mysqli->query("SELECT id FROM veterinary_ranges LIMIT 1");
        if ($r_res && $r_row = $r_res->fetch_assoc()) {
            $range_id = $r_row['id'];
        }
    }
}

// 2. Fallback Definitions
$district_name = 'Unknown District';
$range_name    = 'Unknown Range';
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : 2026;

// 3. Fetch Core Structural Meta Information
if ($district_id) {
    $stmt = $mysqli->prepare("SELECT name FROM districts WHERE id = ?");
    $stmt->bind_param("i", $district_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $district_name = $row['name'];
    }
    $stmt->close();
}

if ($range_id) {
    $stmt = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
    $stmt->bind_param("i", $range_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $range_name = $row['name'];
    }
    $stmt->close();
}

// 4. Dynamic Data Fetch: Live Lookups against existing animal_populations table
$total_population = 0;
$pop_stmt = $mysqli->prepare("SELECT SUM(quantity) as total FROM animal_populations WHERE range_id = ? AND year = ?");
$pop_stmt->bind_param("ii", $range_id, $selected_year);
$pop_stmt->execute();
$pop_result = $pop_stmt->get_result()->fetch_assoc();
if ($pop_result && $pop_result['total']) {
    $total_population = $pop_result['total'];
}
$pop_stmt->close();

// 5. Fetch Target Data from annual_vaccination_targets
$vax_targets = [
    'id' => null,
    'target_fmd' => 0,
    'target_bq' => 0,
    'target_hs' => 0,
    'available_ldo_count' => 0,
    'allocated_ldo_target' => 0,
    'casual_vaccinators_needed' => 0,
    'allocated_man_days' => 0,
    'syringes_10cc_req' => 0,
    'needles_14g_dozen_req' => 0,
    'fuel_liters_per_month' => 0.00
];

$vax_stmt = $mysqli->prepare("SELECT * FROM annual_vaccination_targets WHERE range_id = ? AND year = ?");
$vax_stmt->bind_param("ii", $range_id, $selected_year);
$vax_stmt->execute();
$vax_res = $vax_stmt->get_result()->fetch_assoc();
if ($vax_res) {
    $vax_targets = $vax_res;
}
$vax_stmt->close();

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">




        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">Annual Targets</h2>
                <p class="text-muted small mb-0">Annual Target Details</p>
            </div>
            <?php if (isset($_SESSION['msg'])): ?>
                <div class="alert alert-<?= $_SESSION['msg_type'] ?> py-2 px-3 mb-0 small">
                    <?= $_SESSION['msg'] ?>
                </div>
                <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
            <?php endif; ?>
        </div>
        <!-- Quick Actions & Target Modules Section -->
        <style>
            .target-action-card {
                transition: all 0.25s cubic-bezier(0.165, 0.84, 0.44, 1);
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
                position: relative;
                overflow: hidden;
            }
            .target-action-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(135deg, rgba(255,255,255,0.12) 0%, rgba(255,255,255,0) 100%);
                pointer-events: none;
            }
            .target-action-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 10px 22px rgba(0, 0, 0, 0.18);
                filter: brightness(1.06);
                color: #fff !important;
            }
            .target-action-card:active {
                transform: translateY(-1px);
            }
            .target-action-card i {
                transition: transform 0.25s ease;
            }
            .target-action-card:hover i {
                transform: scale(1.12);
            }
        </style>

        <?php
        $range_param  = $range_id ? '&range_id=' . $range_id : '';
        $range_qparam = $range_id ? '?range_id=' . $range_id : '';

        $target_buttons = [
            [
                'title' => 'Animal Health',
                'icon'  => 'bi-shield-check',
                'color' => '#820100',
                'link'  => 'vaccination_targets.php?year=' . $selected_year . $range_param
            ],
            [
                'title' => 'Breeding and Production',
                'icon'  => 'bi-gender-ambiguous',
                'color' => '#a07174',
                'link'  => 'animal_breeding.php?year=' . $selected_year . $range_param
            ],
            [
                'title' => 'Extension Services',
                'icon'  => 'bi-people-fill',
                'color' => '#185dbd',
                'link'  => 'extension_services.php?year=' . $selected_year . $range_param
            ],
            [
                'title' => 'Special Projects',
                'icon'  => 'bi-stars',
                'color' => '#b08723',
                'link'  => 'projects_progress.php?type=Special' . $range_param
            ],
            [
                'title' => 'Line Ministry Projects',
                'icon'  => 'bi-building-fill-gear',
                'color' => '#ca340fff',
                'link'  => 'projects_progress.php?type=LMP' . $range_param
            ],
            [
                'title' => 'Other Projects',
                'icon'  => 'bi-folder-fill',
                'color' => '#475569',
                'link'  => 'projects_progress.php?type=Other' . $range_param
            ],
            [
                'title' => 'Production Activities Plan',
                'icon'  => 'bi-calendar-check',
                'color' => '#370709',
                'link'  => 'production_activities.php?year=' . $selected_year . $range_param
            ]
        ];
        ?>

        <div class="card gov-card mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0 fw-bold" style="color: #370709;"><i class="bi bi-grid-fill me-2"></i>Annual Target Categories & Quick Actions</h6>
                    <small class="text-muted">Direct access to target management modules, performance plans, and development projects</small>
                </div>
            </div>
            <div class="card-body pt-1">
                <div class="row row-cols-2 row-cols-md-4 g-3">
                    <?php foreach ($target_buttons as $btn): ?>
                        <div class="col">
                            <a href="<?= htmlspecialchars($btn['link']) ?>" class="btn w-100 p-3 text-light border-0 target-action-card d-flex flex-column align-items-center justify-content-center text-decoration-none" style="background-color: <?= $btn['color'] ?>; min-height: 110px; border-radius: 10px;">
                                <i class="bi <?= $btn['icon'] ?> fs-2 mb-1"></i>
                                <span class="text-center text-white"><?= htmlspecialchars($btn['title']) ?></span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

<?php include 'model/asset_modals.php'; ?>

<?php
require_once '../../../includes/footer.php';
?>