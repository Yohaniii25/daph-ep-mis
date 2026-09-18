<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

$user_role      = $_SESSION['role'] ?? '';
$is_supervisory = in_array($user_role, ['deputy_director_hq_1', 'district_dd', 'deputy_director_district', 'provincial_director', 'admin', 'super_admin'], true);
$is_district_dd = in_array($user_role, ['district_dd', 'deputy_director_district'], true);
$is_hq_or_pd    = in_array($user_role, ['deputy_director_hq_1', 'provincial_director', 'admin', 'super_admin'], true);

$full_name      = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Officer');
$session_district_id = $_SESSION['district_id'] ?? null;
$session_range_id    = $_SESSION['range_id'] ?? null;
$selected_year  = isset($_GET['year']) ? intval($_GET['year']) : 2026;

// Supervisory Range and District Selection Resolution
$selected_district_id = isset($_GET['district_id']) ? intval($_GET['district_id']) : ($session_district_id ?? 0);
$selected_range_id    = isset($_GET['range_id']) ? intval($_GET['range_id']) : ($session_range_id ?? 0);

// Load all districts for HQ / PD
$all_districts = [];
if ($is_hq_or_pd) {
    $d_res = $mysqli->query("SELECT id, name FROM districts ORDER BY name ASC");
    if ($d_res) {
        $all_districts = $d_res->fetch_all(MYSQLI_ASSOC);
    }
}

// Load ranges for jurisdiction
$available_ranges = [];
if ($is_supervisory) {
    if ($selected_district_id > 0) {
        $r_stmt = $mysqli->prepare("SELECT id, name, district_id FROM veterinary_ranges WHERE district_id = ? ORDER BY name ASC");
        $r_stmt->bind_param("i", $selected_district_id);
        $r_stmt->execute();
        $available_ranges = $r_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $r_stmt->close();
    } else {
        $r_res = $mysqli->query("SELECT id, name, district_id FROM veterinary_ranges ORDER BY name ASC");
        if ($r_res) {
            $available_ranges = $r_res->fetch_all(MYSQLI_ASSOC);
        }
    }
}

// Determine active range name and district name for display
$range_name    = 'All Ranges';
$district_name = 'Province-Wide';

if ($selected_range_id > 0) {
    $stmt = $mysqli->prepare("
        SELECT vr.name AS range_name, d.name AS district_name, vr.district_id 
        FROM veterinary_ranges vr 
        LEFT JOIN districts d ON vr.district_id = d.id 
        WHERE vr.id = ?
    ");
    $stmt->bind_param("i", $selected_range_id);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) {
        $range_name    = $row['range_name'];
        $district_name = $row['district_name'];
        if (!$selected_district_id) {
            $selected_district_id = $row['district_id'];
        }
    }
    $stmt->close();
} elseif ($selected_district_id > 0) {
    $stmt = $mysqli->prepare("SELECT name FROM districts WHERE id = ?");
    $stmt->bind_param("i", $selected_district_id);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) {
        $district_name = $row['name'] . ' District';
    }
    $stmt->close();
}

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">

<style>
    .gov-card {
        border: none !important;
        border-radius: 12px !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05) !important;
    }
    .kpi-card {
        border-radius: 12px;
        background: #ffffff;
        border-left: 4px solid #820100;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
    }
    .badge-funding {
        background-color: #370709;
        color: #ffffff;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    .badge-variance-surplus {
        background-color: #d1e7dd;
        color: #0f5132;
        border: 1px solid #a3cfbb;
        font-weight: bold;
    }
    .badge-variance-deficit {
        background-color: #f8d7da;
        color: #842029;
        border: 1px solid #f5c2c7;
        font-weight: bold;
    }
    .badge-variance-balanced {
        background-color: #e2e3e5;
        color: #41464b;
        border: 1px solid #d3d6d8;
        font-weight: bold;
    }
</style>

<div class="container-fluid px-4 py-3">

    <!-- Header Section -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1" style="color: #370709;">
                <i class="bi bi-clipboard2-data-fill me-2 text-danger"></i>Production Activities Plan
            </h4>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="badge" style="background-color: #d4c7b7; color: #370709;">
                    <i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($range_name) ?>
                </span>
                <span class="badge text-light" style="background-color: #a07174;">
                    <i class="bi bi-building me-1"></i><?= htmlspecialchars($district_name) ?>
                </span>
                <span class="badge bg-secondary text-white">
                    <i class="bi bi-calendar-event me-1"></i>Year <?= $selected_year ?>
                </span>
            </div>
        </div>

        <!-- Controls: Filters, Log Button, Back -->
        <div class="d-flex flex-wrap gap-2 mt-2 mt-md-0 align-items-center">
            
            <!-- District filter for HQ/PD -->
            <?php if ($is_hq_or_pd && !empty($all_districts)): ?>
                <select class="form-select form-select-sm border-secondary" style="max-width: 170px;" onchange="applyFilters('district_id', this.value)">
                    <option value="0" <?= $selected_district_id == 0 ? 'selected' : '' ?>>All Districts</option>
                    <?php foreach ($all_districts as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $selected_district_id == $d['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['name']) ?> District
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <!-- Range filter for Supervisory roles -->
            <?php if ($is_supervisory && !empty($available_ranges)): ?>
                <select class="form-select form-select-sm border-secondary" style="max-width: 180px;" onchange="applyFilters('range_id', this.value)">
                    <option value="0" <?= $selected_range_id == 0 ? 'selected' : '' ?>>All Ranges</option>
                    <?php foreach ($available_ranges as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $selected_range_id == $r['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r['name']) ?> Range
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <!-- Year Selector -->
            <select class="form-select form-select-sm border-secondary" style="width: 100px;" onchange="applyFilters('year', this.value)">
                <option value="2026" <?= $selected_year == 2026 ? 'selected' : '' ?>>2026</option>
                <option value="2025" <?= $selected_year == 2025 ? 'selected' : '' ?>>2025</option>
                <option value="2024" <?= $selected_year == 2024 ? 'selected' : '' ?>>2024</option>
            </select>

            <!-- Action Buttons -->
            <button class="btn text-light fw-bold text-nowrap btn-sm shadow-sm" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addActivityModal">
                <i class="bi bi-plus-circle me-1"></i> Log Activity Target
            </button>
            <a href="annual_targets.php" class="btn btn-secondary btn-sm shadow-sm text-nowrap">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert alert-<?= $_SESSION['msg_type'] ?? 'info' ?> alert-dismissible fade show shadow-sm py-2 px-3 mb-3 small" role="alert">
            <i class="bi bi-info-circle-fill me-1"></i> <?= $_SESSION['msg'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
    <?php endif; ?>

    <?php
    // Build SQL Query based on filters
    $where_clauses = ["pat.year = ?"];
    $params        = [$selected_year];
    $types         = "i";

    if ($selected_range_id > 0) {
        $where_clauses[] = "pat.range_id = ?";
        $params[]        = $selected_range_id;
        $types          .= "i";
    } elseif ($selected_district_id > 0) {
        $where_clauses[] = "vr.district_id = ?";
        $params[]        = $selected_district_id;
        $types          .= "i";
    } elseif (!$is_supervisory && !empty($session_range_id)) {
        $where_clauses[] = "pat.range_id = ?";
        $params[]        = $session_range_id;
        $types          .= "i";
    }

    $where_sql = implode(" AND ", $where_clauses);

    $query = "
        SELECT pat.*, 
               vr.name AS range_name, 
               d.name AS district_name,
               (SELECT COUNT(*) FROM activity_beneficiaries ab WHERE ab.activity_id = pat.id) AS beneficiary_count
        FROM production_activity_targets pat
        LEFT JOIN veterinary_ranges vr ON pat.range_id = vr.id
        LEFT JOIN districts d ON vr.district_id = d.id
        WHERE {$where_sql}
        ORDER BY pat.id DESC
    ";

    $stmt = $mysqli->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $res = false;
    }

    $activities_list   = [];
    $total_activities  = 0;
    $total_target_qty  = 0;
    $total_achieved_qty= 0;
    $total_beneficiaries_all = 0;

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $activities_list[] = $row;
            $total_activities++;
            $total_target_qty       += intval($row['target_quantity']);
            $total_achieved_qty     += intval($row['achieved_quantity']);
            $total_beneficiaries_all+= intval($row['beneficiary_count']);
        }
        $stmt->close();
    }

    $overall_variance = $total_achieved_qty - $total_target_qty;
    $overall_completion_rate = ($total_target_qty > 0) ? round(($total_achieved_qty / $total_target_qty) * 100, 1) : 0;
    ?>

    <!-- KPI Summary Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card kpi-card h-100 p-3" style="border-left-color: #820100;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Planned Activities</div>
                        <div class="fs-4 fw-bold text-dark mt-1"><?= number_format($total_activities) ?></div>
                    </div>
                    <div class="rounded-circle p-3 text-white" style="background-color: #820100;">
                        <i class="bi bi-list-check fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card kpi-card h-100 p-3" style="border-left-color: #0d6efd;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Target vs Achieved</div>
                        <div class="fs-4 fw-bold text-primary mt-1">
                            <?= number_format($total_achieved_qty) ?> <span class="text-muted fs-6">/ <?= number_format($total_target_qty) ?></span>
                        </div>
                    </div>
                    <div class="rounded-circle p-3 text-white bg-primary">
                        <i class="bi bi-bullseye fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card kpi-card h-100 p-3" style="border-left-color: <?= ($overall_variance >= 0) ? '#198754' : '#dc3545' ?>;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Target Variance</div>
                        <div class="fs-4 fw-bold mt-1 <?= ($overall_variance >= 0) ? 'text-success' : 'text-danger' ?>">
                            <?= ($overall_variance > 0 ? '+' : '') . number_format($overall_variance) ?>
                            <span class="fs-6">(<?= ($overall_variance > 0) ? 'Surplus' : (($overall_variance < 0) ? 'Deficit' : 'Balanced') ?>)</span>
                        </div>
                    </div>
                    <div class="rounded-circle p-3 text-white <?= ($overall_variance >= 0) ? 'bg-success' : 'bg-danger' ?>">
                        <i class="bi <?= ($overall_variance >= 0) ? 'bi-graph-up' : 'bi-graph-down' ?> fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card kpi-card h-100 p-3" style="border-left-color: #6f42c1;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Enrolled Beneficiaries</div>
                        <div class="fs-4 fw-bold text-dark mt-1"><?= number_format($total_beneficiaries_all) ?></div>
                    </div>
                    <div class="rounded-circle p-3 text-white" style="background-color: #6f42c1;">
                        <i class="bi bi-people fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Production Activities Table -->
    <div class="card gov-card mb-4">
        <div class="card-header bg-white pt-4 px-4 border-0 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-1" style="color: #370709;">
                    <i class="bi bi-journal-check me-2 text-danger"></i>Production Activities Matrix & Variance Tracking
                </h5>
                <p class="text-muted small mb-0">Tracks initial targets, live achieved outcomes, automatic surplus/deficit variances, and direct links to beneficiary tracking.</p>
            </div>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="table-responsive">
                <table id="productionActivitiesTable" class="table table-striped table-hover table-bordered align-middle small bg-white text-dark m-0 w-100">
                    <thead style="background-color: #d4c7b7; color: #370709;">
                        <tr>
                            <th>Activity / Metric Title</th>
                            <th>Funding Source</th>
                            <th>Animal Category</th>
                            <?php if ($is_supervisory): ?>
                                <th>Range / District</th>
                            <?php endif; ?>
                            <th class="text-center">Target Qty</th>
                            <th class="text-center">Achieved Qty</th>
                            <th class="text-center">Yearly Variance</th>
                            <th class="text-center">Completion Rate</th>
                            <th class="text-center" style="width: 140px;">Beneficiary Tracking</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($activities_list)): ?>
                            <?php foreach ($activities_list as $row): ?>
                                <?php
                                $category  = ($row['animal_category'] === 'Other') ? $row['animal_category_other'] : ($row['animal_category'] ?? 'General');
                                $target    = intval($row['target_quantity']);
                                $achieved  = intval($row['achieved_quantity']);
                                $funding   = $row['funding_source'] ?? 'PSDG';
                                $variance  = $achieved - $target;
                                $pct       = ($target > 0) ? round(($achieved / $target) * 100, 1) : 0;
                                $ben_count = intval($row['beneficiary_count']);

                                // Badge Class for completion
                                $pct_badge = ($pct >= 100) ? 'bg-success' : (($pct >= 50) ? 'bg-primary' : 'bg-warning text-dark');
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($row['activity_name']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge badge-funding px-2 py-1">
                                            <i class="bi bi-wallet2 me-1"></i><?= htmlspecialchars($funding) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border">
                                            <?= htmlspecialchars($category) ?>
                                        </span>
                                    </td>
                                    <?php if ($is_supervisory): ?>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($row['range_name'] ?? 'Range') ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($row['district_name'] ?? 'District') ?></small>
                                        </td>
                                    <?php endif; ?>
                                    <td class="text-center fw-bold text-secondary"><?= number_format($target) ?></td>
                                    <td class="text-center fw-bold text-danger"><?= number_format($achieved) ?></td>
                                    <td class="text-center">
                                        <?php if ($variance > 0): ?>
                                            <span class="badge badge-variance-surplus px-2 py-1">
                                                <i class="bi bi-arrow-up-right me-1"></i>+<?= number_format($variance) ?> (Surplus)
                                            </span>
                                        <?php elseif ($variance < 0): ?>
                                            <span class="badge badge-variance-deficit px-2 py-1">
                                                <i class="bi bi-arrow-down-right me-1"></i><?= number_format($variance) ?> (Deficit)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-variance-balanced px-2 py-1">
                                                <i class="bi bi-check2 me-1"></i>0 (Balanced)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-2">
                                            <div class="progress" style="width: 70px; height: 6px; border-radius: 3px;">
                                                <div class="progress-bar <?= $pct_badge ?>" role="progressbar" style="width: <?= min(100, $pct) ?>%;"></div>
                                            </div>
                                            <span class="badge <?= $pct_badge ?>"><?= $pct ?>%</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <a href="activity_beneficiaries.php?activity_id=<?= $row['id'] ?>" class="btn btn-sm text-light fw-semibold text-nowrap shadow-sm" style="background-color: #370709;">
                                            <i class="bi bi-people-fill me-1"></i> Beneficiaries
                                            <span class="badge bg-light text-dark ms-1"><?= $ben_count ?></span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Modal: Add New Activity Target -->
<div class="modal fade" id="addActivityModal" tabindex="-1" aria-labelledby="addActivityLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-light py-2" style="background-color: #370709;">
                <h6 class="modal-title" id="addActivityLabel"><i class="bi bi-plus-circle me-2"></i> Log New Production Activity Target</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="processors/save_production_activity.php" method="POST">
                <div class="modal-body p-3 small">
                    
                    <!-- Range Selector in Modal -->
                    <?php if ($is_supervisory && !empty($available_ranges)): ?>
                        <div class="mb-2">
                            <label class="form-label fw-bold mb-1">Assigned Veterinary Range <span class="text-danger">*</span></label>
                            <select name="range_id" class="form-select form-select-sm border-secondary" required>
                                <option value="" disabled selected>-- Select Veterinary Range --</option>
                                <?php foreach ($available_ranges as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= $selected_range_id == $r['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($r['name']) ?> Range
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="range_id" value="<?= htmlspecialchars($selected_range_id ?: ($session_range_id ?? 0)) ?>">
                    <?php endif; ?>

                    <input type="hidden" name="year" value="<?= htmlspecialchars($selected_year) ?>">

                    <div class="mb-2">
                        <label class="form-label fw-bold mb-1">Activity Name / Metric Title <span class="text-danger">*</span></label>
                        <input type="text" name="activity_name" class="form-control form-control-sm border-secondary" placeholder="e.g. Pasture Development, Silage Production, Dairy Model Unit" required>
                    </div>

                    <!-- Mandatory Funding Source Field -->
                    <div class="mb-2">
                        <label class="form-label fw-bold mb-1">Funding Source <span class="text-danger">*</span></label>
                        <select name="funding_source" id="fundingSourceSelect" class="form-select form-select-sm border-secondary" required onchange="toggleOtherFunding(this.value)">
                            <option value="" disabled selected>-- Select Funding Source --</option>
                            <option value="PSDG">PSDG (Provincial Specific Development Grant)</option>
                            <option value="Line Ministry">Line Ministry</option>
                            <option value="NGOs">NGOs / International Grants</option>
                            <option value="Provincial Council">Provincial Council</option>
                            <option value="CBG">CBG (Criteria Based Grant)</option>
                            <option value="Other">Other (Specify Source)</option>
                        </select>
                    </div>
                    <div class="mb-2" id="otherFundingWrapper" style="display: none;">
                        <label class="form-label fw-bold mb-1">Specify Other Funding Source <span class="text-danger">*</span></label>
                        <input type="text" id="otherFundingInput" name="funding_source_other" class="form-control form-control-sm border-secondary" placeholder="e.g. FAO, UNDP, Private Sponsor">
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Animal Category <span class="text-danger">*</span></label>
                            <select id="animalCategorySelect" name="animal_category" class="form-select form-select-sm border-secondary" required onchange="toggleOtherCategoryInput(this.value)">
                                <option value="" selected disabled>-- Select Option --</option>
                                <option value="Cow">Cow</option>
                                <option value="Buffalo">Buffalo</option>
                                <option value="Goat">Goat</option>
                                <option value="Chicken">Chicken</option>
                                <option value="Pig">Pig</option>
                                <option value="Other">Other Species</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">If "Other" (Specify Species)</label>
                            <input type="text" id="otherCategoryInput" name="animal_category_other" class="form-control form-control-sm border-secondary" placeholder="e.g. Rabbit, Sheep, Quail" disabled>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Target Quantity Limit <span class="text-danger">*</span></label>
                            <input type="number" name="target_quantity" class="form-control form-control-sm border-secondary" min="0" value="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold mb-1">Achieved Quantity (To Date) <span class="text-danger">*</span></label>
                            <input type="number" name="achieved_quantity" class="form-control form-control-sm border-secondary" min="0" value="0" required>
                        </div>
                    </div>

                </div>
                <div class="modal-footer py-2 border-top-0">
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light btn-sm px-4 shadow-sm fw-bold" style="background-color: #820100;">Save Activity Target</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    $('#productionActivitiesTable').DataTable({
        responsive: true,
        pageLength: 10,
        dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
        buttons: [
            {
                extend: 'csv',
                text: '<i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV',
                className: 'btn btn-sm btn-success fw-bold me-1',
                exportOptions: { columns: ':not(:last-child)' }
            },
            {
                extend: 'excel',
                text: '<i class="bi bi-file-earmark-excel me-1"></i> Export Excel',
                className: 'btn btn-sm btn-primary fw-bold me-1',
                exportOptions: { columns: ':not(:last-child)' }
            },
            {
                extend: 'print',
                text: '<i class="bi bi-printer me-1"></i> Print',
                className: 'btn btn-sm btn-secondary fw-bold me-1',
                exportOptions: { columns: ':not(:last-child)' }
            }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Filter production metrics..."
        }
    });
});

function applyFilters(param, value) {
    const url = new URL(window.location.href);
    url.searchParams.set(param, value);
    if (param === 'district_id') {
        url.searchParams.delete('range_id'); // reset range when district changes
    }
    window.location = url.toString();
}

function toggleOtherFunding(value) {
    const wrapper = document.getElementById('otherFundingWrapper');
    const input   = document.getElementById('otherFundingInput');
    if (value === 'Other') {
        wrapper.style.display = 'block';
        input.required = true;
        input.focus();
    } else {
        wrapper.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}

function toggleOtherCategoryInput(value) {
    const otherInput = document.getElementById('otherCategoryInput');
    if (value === 'Other') {
        otherInput.disabled = false;
        otherInput.required = true;
        otherInput.focus();
    } else {
        otherInput.disabled = true;
        otherInput.required = false;
        otherInput.value = '';
    }
}
</script>

<?php require_once '../../../includes/footer.php'; ?>