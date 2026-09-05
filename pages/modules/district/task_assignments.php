<?php
// pages/modules/district/task_assignments.php
// Task Assignment Interface for District Deputy Director to delegate Range Details Quick Actions.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_roles = ['district_dd', 'deputy_director_district', 'administrator', 'provincial_director'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied. Authorized District Deputy Directors only.");
}

require_once '../../../config/db_connect.php';
require_once 'processors/db_migration.php';


// Resolve District context
$district_id = $_SESSION['district_id'] ?? null;
$district_name = $_SESSION['district'] ?? '';

if (empty($district_id) && !empty($district_name)) {
    if (strcasecmp($district_name, 'Amparai') === 0 || strcasecmp($district_name, 'Ampara') === 0) {
        $district_id = 1;
    } elseif (strcasecmp($district_name, 'Batticaloa') === 0) {
        $district_id = 2;
    } elseif (strcasecmp($district_name, 'Trincomalee') === 0) {
        $district_id = 3;
    }
}
if (empty($district_id)) $district_id = 1;

// Fetch official district name
$dist_stmt = $mysqli->prepare("SELECT name FROM districts WHERE id = ? LIMIT 1");
if ($dist_stmt) {
    $dist_stmt->bind_param("i", $district_id);
    $dist_stmt->execute();
    $dist_res = $dist_stmt->get_result();
    if ($row = $dist_res->fetch_assoc()) {
        $district_name = $row['name'];
    }
    $dist_stmt->close();
}

// 14 Standardized Quick Actions with official icons, branding, and category metadata
$quick_actions_list = [
    'range_statistics' => [
        'id' => 1,
        'title' => 'Range Statistics',
        'icon' => 'bi-graph-up',
        'color' => '#820100',
        'desc' => 'Statistical returns & population summaries',
        'category' => 'reports',
        'category_label' => 'Reports & Data'
    ],
    'annual_targets' => [
        'id' => 2,
        'title' => 'Annual Targets',
        'icon' => 'bi-bar-chart',
        'color' => '#370709',
        'desc' => 'Annual operational targets & performance',
        'category' => 'reports',
        'category_label' => 'Reports & Data'
    ],
    'monthly_annual_reports' => [
        'id' => 3,
        'title' => 'Monthly/Annual Reports',
        'icon' => 'bi-car-front-fill',
        'color' => '#b08723',
        'desc' => 'Periodic reporting submissions',
        'category' => 'reports',
        'category_label' => 'Reports & Data'
    ],
    'regulatory_functions' => [
        'id' => 4,
        'title' => 'Regulatory Functions',
        'icon' => 'bi-file-earmark-plus',
        'color' => '#a07174',
        'desc' => 'Animals Act compounding & legal functions',
        'category' => 'clinical',
        'category_label' => 'Clinical & Legal'
    ],
    'animal_health' => [
        'id' => 5,
        'title' => 'Animal Health',
        'icon' => 'bi-gear-fill',
        'color' => '#689ccf',
        'desc' => 'Disease surveillance & clinical logs',
        'category' => 'clinical',
        'category_label' => 'Clinical Care'
    ],
    'clinical_services' => [
        'id' => 6,
        'title' => 'Clinical Services',
        'icon' => 'bi-tools',
        'color' => '#2e7d32',
        'desc' => 'Outpatient veterinary clinical care',
        'category' => 'clinical',
        'category_label' => 'Clinical Care'
    ],
    'animal_breeding' => [
        'id' => 7,
        'title' => 'Animal Breeding',
        'icon' => 'bi-file-earmark-text-fill',
        'color' => '#e65100',
        'desc' => 'Artificial insemination & pedigree records',
        'category' => 'clinical',
        'category_label' => 'Breeding & AI'
    ],
    'livestock_production' => [
        'id' => 8,
        'title' => 'Livestock Production',
        'icon' => 'bi-person-bounding-box',
        'color' => '#455a64',
        'desc' => 'Production levels & activity tracking',
        'category' => 'operations',
        'category_label' => 'Field Operations'
    ],
    'dairy_hub' => [
        'id' => 9,
        'title' => 'Dairy Hub',
        'icon' => 'bi-patch-check-fill',
        'color' => '#1565c0',
        'desc' => 'Milk collection & dairy farmer hubs',
        'category' => 'operations',
        'category_label' => 'Dairy & Hubs'
    ],
    'projects' => [
        'id' => 10,
        'title' => 'Projects',
        'icon' => 'bi-geo-alt-fill',
        'color' => '#00838f',
        'desc' => 'Field development project tracking',
        'category' => 'operations',
        'category_label' => 'Special Projects'
    ],
    'monitoring' => [
        'id' => 11,
        'title' => 'Monitoring',
        'icon' => 'bi-folder-fill',
        'color' => '#283593',
        'desc' => 'Field inspections & audit monitoring',
        'category' => 'operations',
        'category_label' => 'Field Monitoring'
    ],
    'accounts' => [
        'id' => 12,
        'title' => 'Accounts',
        'icon' => 'bi-bookmark-dash-fill',
        'color' => '#ad1457',
        'desc' => 'Revenue, cash books & expenditures',
        'category' => 'reports',
        'category_label' => 'Finance & Cash'
    ],
    'clean_sri_lanka' => [
        'id' => 13,
        'title' => 'Clean Sri Lanka',
        'icon' => 'bi-graph-up-arrow',
        'color' => '#d84315',
        'desc' => 'Clean Sri Lanka national initiative',
        'category' => 'operations',
        'category_label' => 'National Program'
    ],
    'trainings' => [
        'id' => 14,
        'title' => 'Trainings',
        'icon' => 'bi-sliders',
        'color' => '#37474f',
        'desc' => 'Farmer workshops & staff capacity sessions',
        'category' => 'operations',
        'category_label' => 'Capacity Building'
    ],
];

// Fetch active assignments overview within this District DD jurisdiction
$district_assignments = [];

// 1. First check if any assignments exist for Government Veterinary Surgeons in this district
$dist_alt = $district_name . 'i';
$vs_actions_q = $mysqli->query("
    SELECT DISTINCT a.action_id, MAX(a.assigned_at) as last_assigned_at, assigner.full_name as assigned_by_name
    FROM user_quick_action_assignments a
    LEFT JOIN users assigner ON a.assigned_by = assigner.id
    WHERE (
        a.target_role IN ('government_veterinary_surgeon', 'veterinary_surgeon')
        OR a.user_id IN (
            SELECT id FROM users 
            WHERE role IN ('government_veterinary_surgeon', 'veterinary_surgeon') 
              AND (district_id = $district_id OR district = '$district_name' OR district = '$dist_alt')
        )
    ) AND (a.district_id = $district_id OR a.district_id IS NULL)
    GROUP BY a.action_id
");
$vs_actions = [];
$vs_last_time = null;
$vs_assigner_name = 'District Deputy Director';
if ($vs_actions_q) {
    while ($arow = $vs_actions_q->fetch_assoc()) {
        $vs_actions[] = $arow['action_id'];
        if (!$vs_last_time || strtotime($arow['last_assigned_at']) > strtotime($vs_last_time)) {
            $vs_last_time = $arow['last_assigned_at'];
        }
        if (!empty($arow['assigned_by_name'])) {
            $vs_assigner_name = $arow['assigned_by_name'];
        }
    }
}

if (!empty($vs_actions)) {
    $district_assignments[] = [
        'user_id' => 'all_veterinary_surgeons',
        'username' => 'all_vs_district',
        'full_name' => 'All Government Veterinary Surgeons',
        'role' => 'government_veterinary_surgeon',
        'designation' => 'Government Veterinary Surgeon (District-Wide)',
        'range_name' => null,
        'assigned_action_keys' => implode(',', $vs_actions),
        'actions_array' => $vs_actions,
        'action_count' => count($vs_actions),
        'last_assigned_at' => $vs_last_time,
        'assigned_by_name' => $vs_assigner_name,
        'is_role_target' => true
    ];
}

// 2. Fetch non-VS officers who have individual assignments
$overview_sql = "
    SELECT u.id AS user_id, u.username, u.full_name, u.role, u.designation,
           vr.name AS range_name,
           GROUP_CONCAT(DISTINCT a.action_id ORDER BY a.id ASC) AS assigned_action_keys,
           COUNT(DISTINCT a.action_id) AS action_count,
           MAX(a.assigned_at) AS last_assigned_at,
           assigner.full_name AS assigned_by_name
    FROM user_quick_action_assignments a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN veterinary_ranges vr ON u.range_id = vr.id
    LEFT JOIN users assigner ON a.assigned_by = assigner.id
    WHERE u.role NOT IN ('government_veterinary_surgeon', 'veterinary_surgeon')
      AND (
        vr.district_id = $district_id 
        OR u.district_id = $district_id 
        OR u.district = '$district_name' 
        OR u.district = '$dist_alt'
    )
    GROUP BY u.id
    ORDER BY u.full_name ASC
";

$overview_res = $mysqli->query($overview_sql);
if ($overview_res) {
    while ($row = $overview_res->fetch_assoc()) {
        $row['actions_array'] = $row['assigned_action_keys'] ? explode(',', $row['assigned_action_keys']) : [];
        $row['is_role_target'] = false;
        $district_assignments[] = $row;
    }
}

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4 pb-5">
        
        <!-- Header Banner -->
        <div class="delegation-header mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h2 class="fw-bold mb-0 text-light">Range Quick Actions Delegation Hub</h2>
                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold">
                        <i class="bi bi-shield-lock-fill me-1"></i> <?= htmlspecialchars($district_name) ?> District
                    </span>
                </div>
                <p class="text-light-50 mb-0">
                    Configure staff permissions and dynamically control visibility for the 14 Quick Actions on the Range Details page.
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="../veterinary/range_details.php" class="btn btn-outline-light btn-sm px-3 shadow-sm" target="_blank">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Preview Range Details
                </a>
                <a href="../../../dashboard.php" class="btn btn-light btn-sm px-3 shadow-sm text-dark fw-semibold">
                    <i class="bi bi-speedometer2 me-1"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Task Assignment Workflow Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 px-4 border-bottom">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="fw-bold mb-1" style="color: #370709;">
                            <i class="bi bi-person-gear me-2"></i>Task Assignment Form
                        </h5>
                        <p class="text-muted small mb-0">
                            Select user category and role to delegate Quick Actions district-wide across all ranges.
                        </p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fw-semibold">
                            <i class="bi bi-globe2 me-1"></i> District-Wide Permission Scope
                        </span>
                        <span class="badge bg-light text-muted border px-3 py-2">
                            Jurisdiction: <strong><?= htmlspecialchars($district_name) ?> District</strong>
                        </span>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <!-- District-Wide Scope Rule Banner -->
                <div class="alert alert-primary-subtle border border-primary-subtle py-2 px-3 mb-4 rounded-3 d-flex align-items-center gap-3 small text-primary-emphasis">
                    <i class="bi bi-info-circle-fill fs-4 text-primary flex-shrink-0"></i>
                    <div>
                        <strong>District-Wide Delegation Rule:</strong> When assigning Quick Actions to Government Veterinary Surgeons, there is no need to select or mention an individual range officer. Assigned tasks automatically apply <strong>to all veterinary ranges</strong> across <?= htmlspecialchars($district_name) ?> District.
                    </div>
                </div>

                <form id="taskAssignmentForm">
                    
                    <!-- Row 1: Cascading Dropdowns 1, 2, and 3 -->
                    <div class="row g-3 mb-4">
                        
                        <!-- Dropdown 1: User Category -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">
                                <span class="step-number">1</span> User Category
                            </label>
                            <select class="form-select form-select-lg border-2" id="categorySelect" required>
                                <option value="">-- Choose User Category --</option>
                                <option value="range_veterinary_officer" selected>Range Veterinary Officer</option>
                                <option value="subject_matter_specialist">Subject Matter Specialist</option>
                                <option value="training_centers">Training Centers</option>
                                <option value="regional_farms">Regional Farms</option>
                            </select>
                            <small class="text-muted d-block mt-1">Select broad organizational division</small>
                        </div>

                        <!-- Dropdown 2: User Role (Cascading) -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">
                                <span class="step-number">2</span> User Role
                            </label>
                            <select class="form-select form-select-lg border-2" id="roleSelect" required>
                                <!-- Populated dynamically based on Dropdown 1 -->
                            </select>
                            <small class="text-muted d-block mt-1">Select specific staff sub-role</small>
                        </div>

                        <!-- Dropdown 3: Select Target / Officer (Filtered by Jurisdiction) -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">
                                <span class="step-number">3</span> Assignment Target
                            </label>
                            <select class="form-select form-select-lg border-2" id="officerSelect" required>
                                <option value="">-- Select Target --</option>
                            </select>
                            <small class="text-muted d-block mt-1" id="officerWorkstationHint">Loading targets...</small>
                        </div>

                    </div>

                    <!-- Row 2: Step 4 / 14 Quick Actions Matrix with Category Filter Tabs -->
                    <div class="mb-4 pt-2">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-3 border-bottom gap-3">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <label class="form-label fw-bold small text-muted text-uppercase mb-0">
                                        <span class="step-number">4</span> Assign Quick Actions (14 Modules &bull; District-Wide)
                                    </label>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 small fw-semibold">
                                        All Ranges Covered
                                    </span>
                                </div>
                                <p class="text-muted small mb-0">
                                    Click module cards to toggle visibility across all veterinary ranges in <?= htmlspecialchars($district_name) ?> District.
                                </p>
                            </div>

                            <!-- Filter Pills & Batch Controls -->
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <div class="btn-group btn-group-sm p-1 bg-light rounded-pill border" role="group" id="filterPillsGroup">
                                    <button type="button" class="btn btn-sm rounded-pill btn-dark active px-3 py-1 fw-semibold filter-pill-btn" data-filter="all">
                                        All (14)
                                    </button>
                                    <button type="button" class="btn btn-sm rounded-pill btn-light px-3 py-1 fw-semibold filter-pill-btn text-muted" data-filter="clinical">
                                        <i class="bi bi-heart-pulse-fill me-1 text-danger"></i> Clinical (4)
                                    </button>
                                    <button type="button" class="btn btn-sm rounded-pill btn-light px-3 py-1 fw-semibold filter-pill-btn text-muted" data-filter="reports">
                                        <i class="bi bi-bar-chart-fill me-1 text-primary"></i> Reports (4)
                                    </button>
                                    <button type="button" class="btn btn-sm rounded-pill btn-light px-3 py-1 fw-semibold filter-pill-btn text-muted" data-filter="operations">
                                        <i class="bi bi-briefcase-fill me-1 text-success"></i> Operations (6)
                                    </button>
                                </div>

                                <div class="vr mx-1 d-none d-lg-block"></div>

                                <button type="button" class="btn btn-sm btn-outline-dark px-3 rounded-pill fw-semibold shadow-xs" id="btnSelectAll">
                                    <i class="bi bi-check-all me-1"></i> Select All
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary px-3 rounded-pill fw-semibold shadow-xs" id="btnClearAll">
                                    <i class="bi bi-x-circle me-1"></i> Clear All
                                </button>
                            </div>
                        </div>

                        <!-- 14 Interactive Action Cards Grid -->
                        <div class="row g-3" id="quickActionsGrid">
                            <?php foreach ($quick_actions_list as $action_key => $action): ?>
                                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 action-col" data-category="<?= $action['category'] ?>">
                                    <div class="action-card-select" data-action-key="<?= $action_key ?>" style="--card-theme-color: <?= $action['color'] ?>;">
                                        <!-- Top Color Accent Bar -->
                                        <div class="action-card-accent" style="background-color: <?= $action['color'] ?>;"></div>
                                        
                                        <div class="p-3 d-flex flex-column h-100">
                                            <!-- Card Header: Icon, ID Badge & Custom Check Indicator -->
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="action-icon-box" style="background: linear-gradient(135deg, <?= $action['color'] ?> 0%, <?= $action['color'] ?>cc 100%);">
                                                        <i class="bi <?= $action['icon'] ?>"></i>
                                                    </div>
                                                    <span class="action-id-pill">#<?= sprintf('%02d', $action['id']) ?></span>
                                                </div>
                                                <div class="check-indicator" title="Toggle delegation">
                                                    <i class="bi bi-check-lg"></i>
                                                </div>
                                            </div>

                                            <!-- Card Title & Description -->
                                            <h6 class="action-title mb-1"><?= htmlspecialchars($action['title']) ?></h6>
                                            <p class="action-desc mb-3"><?= htmlspecialchars($action['desc']) ?></p>

                                            <!-- Card Footer: Status Pill & Category Tag -->
                                            <div class="action-card-footer pt-2 border-top d-flex align-items-center justify-content-between mt-auto">
                                                <span class="action-status-chip">
                                                    <i class="status-dot"></i>
                                                    <span class="status-text">Hidden</span>
                                                </span>
                                                <span class="action-category-label"><?= htmlspecialchars($action['category_label']) ?></span>
                                            </div>

                                            <input type="checkbox" class="action-checkbox d-none" value="<?= $action_key ?>" id="chk_<?= $action_key ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Row 3: Action Controls -->
                    <div class="d-flex flex-wrap justify-content-between align-items-center pt-3 border-top gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <span id="selectedCountBadge" class="badge bg-secondary px-3 py-2 fs-6 rounded-pill">0 Actions Selected</span>
                            <span class="text-muted small" id="officerStatusHint">Select an officer or role to manage delegation.</span>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-danger px-4 rounded-pill" id="btnRevokeAll" style="display: none;">
                                <i class="bi bi-trash3 me-1"></i> Revoke All Tasks
                            </button>
                            <button type="submit" class="btn btn-dark px-4 rounded-pill shadow-sm" id="btnSaveAssignments" style="background-color: #370709; border-color: #370709;">
                                <i class="bi bi-check2-circle me-1"></i> Save Task Assignments
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>

        <!-- Overview Directory Table -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 px-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-1" style="color: #370709;">
                            <i class="bi bi-people-fill me-2"></i>Active Staff Delegations Overview
                        </h5>
                        <p class="text-muted small mb-0">
                            Current visibility permissions across all veterinary ranges in <?= htmlspecialchars($district_name) ?> District.
                        </p>
                    </div>
                    <span class="badge bg-light text-dark border px-3 py-2 fw-semibold">
                        <?= count($district_assignments) ?> Configured Assignment(s)
                    </span>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="assignmentsTable">
                        <thead class="table-light">
                            <tr class="small text-uppercase text-muted">
                                <th class="ps-4">Target Staff / Role</th>
                                <th>Role & Designation</th>
                                <th>Assigned Scope & Base</th>
                                <th>Delegated Quick Actions</th>
                                <th>Last Updated</th>
                                <th class="text-end pe-4">Manage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($district_assignments)): ?>
                                <tr id="noAssignmentsRow">
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-shield-x fs-1 text-secondary d-block mb-2"></i>
                                        <h6 class="fw-bold mb-1">No Task Assignments Configured Yet</h6>
                                        <p class="small text-muted mb-0">All subordinates are currently hidden by default. Use the form above to assign tasks.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($district_assignments as $item): 
                                    $is_vs_target = ($item['user_id'] === 'all_veterinary_surgeons' || !empty($item['is_role_target']) || $item['role'] === 'government_veterinary_surgeon' || $item['role'] === 'veterinary_surgeon');
                                ?>
                                    <tr data-user-id="<?= $item['user_id'] ?>">
                                        <td class="ps-4">
                                            <?php if ($is_vs_target): ?>
                                                <div class="fw-bold text-dark"><i class="bi bi-people-fill text-primary me-1"></i>All Government Veterinary Surgeons</div>
                                                <small class="text-primary fw-semibold">District-Wide Staff (All Ranges)</small>
                                            <?php else: ?>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($item['full_name']) ?></div>
                                                <small class="text-muted font-monospace">@<?= htmlspecialchars($item['username']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($is_vs_target): ?>
                                                <span class="badge bg-primary">Government Veterinary Surgeon</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($item['designation'] ?: ucwords(str_replace('_', ' ', $item['role']))) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($is_vs_target): ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 mb-1">
                                                    <i class="bi bi-globe2 me-1"></i>District-Wide (All Ranges)
                                                </span>
                                                <div class="small text-muted">
                                                    <i class="bi bi-shield-check me-1 text-success"></i>Covers all ranges in <?= htmlspecialchars($district_name) ?> District
                                                </div>
                                            <?php else: ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 mb-1">
                                                    <i class="bi bi-globe2 me-1"></i>District-Wide
                                                </span>
                                                <div class="small text-muted">
                                                    <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($item['range_name'] ? "Office: " . $item['range_name'] : "District Office") ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap">
                                                <?php foreach ($item['actions_array'] as $act_key): 
                                                    $act_info = $quick_actions_list[$act_key] ?? null;
                                                    if ($act_info):
                                                ?>
                                                    <span class="badge-action-pill" style="background-color: <?= $act_info['color'] ?>;">
                                                        <i class="bi <?= $act_info['icon'] ?>"></i> <?= htmlspecialchars($act_info['title']) ?>
                                                    </span>
                                                <?php 
                                                    endif;
                                                endforeach; 
                                                ?>
                                            </div>
                                            <small class="text-muted fw-bold d-block mt-1"><?= count($item['actions_array']) ?> of 14 modules delegated</small>
                                        </td>
                                        <td class="small text-muted">
                                            <?= $item['last_assigned_at'] ? date('d M Y, h:i A', strtotime($item['last_assigned_at'])) : '-' ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-edit-officer me-1" data-user-id="<?= $item['user_id'] ?>" title="Edit Assignments">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-revoke-officer" data-user-id="<?= $item['user_id'] ?>" title="Revoke All">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- SweetAlert2 JS (included in header if available, fallback cdn safe) -->
<script src="../../../assets/js/sweetalert2.all.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const categorySelect = document.getElementById('categorySelect');
    const roleSelect = document.getElementById('roleSelect');
    const officerSelect = document.getElementById('officerSelect');
    const officerWorkstationHint = document.getElementById('officerWorkstationHint');
    const selectedCountBadge = document.getElementById('selectedCountBadge');
    const officerStatusHint = document.getElementById('officerStatusHint');
    const btnRevokeAll = document.getElementById('btnRevokeAll');
    const taskAssignmentForm = document.getElementById('taskAssignmentForm');
    const filterTableInput = document.getElementById('filterTableInput');

    // Exact sub-roles mapping required for Dropdown 2
    const categoryRolesMap = {
        'range_veterinary_officer': [
            { value: '', label: '-- All Range Staff Sub-Roles --' },
            { value: 'government_veterinary_surgeon', label: 'Government Veterinary Surgeon' },
            { value: 'additional_veterinary_surgeon', label: 'Additional Veterinary Surgeon' },
            { value: 'livestock_development_officer', label: 'Livestock Development Officer (or Instructor)' },
            { value: 'development_officer', label: 'Development Officer' },
            { value: 'driver', label: 'Driver' },
            { value: 'dispensary_assistant', label: 'Dispensary Assistant' },
            { value: 'department_laborer', label: 'Department Laborer' },
            { value: 'night_watcher', label: 'Night Watcher' }
        ],
        'subject_matter_specialist': [
            { value: 'sms', label: 'Subject Matter Specialist' }
        ],
        'training_centers': [
            { value: 'training_officer', label: 'Training Officer' }
        ],
        'regional_farms': [
            { value: 'farms_dd', label: 'Deputy Director (Farms Operation)' }
        ]
    };

    // Cache of fetched officers
    let currentOfficers = [];

    // Helper: update role dropdown based on category
    function populateRoles() {
        const cat = categorySelect.value;
        roleSelect.innerHTML = '';
        const roles = categoryRolesMap[cat] || [];
        roles.forEach(r => {
            const opt = document.createElement('option');
            opt.value = r.value;
            opt.textContent = r.label;
            roleSelect.appendChild(opt);
        });
        loadOfficers();
    }

    // Helper: load officers matching category and role
    function loadOfficers(preselectUserId = null) {
        const cat = categorySelect.value;
        const subRole = roleSelect.value;
        officerSelect.innerHTML = '<option value="">Loading targets...</option>';
        officerWorkstationHint.textContent = 'Fetching staff in jurisdiction...';

        const url = `processors/task_assignment_crud.php?action=fetch_officers&category=${encodeURIComponent(cat)}&sub_role=${encodeURIComponent(subRole)}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                officerSelect.innerHTML = '<option value="">-- Select Target --</option>';
                if (data.status === 'success' && data.officers.length > 0) {
                    currentOfficers = data.officers;
                    data.officers.forEach(o => {
                        const opt = document.createElement('option');
                        opt.value = o.id;
                        const badgeTxt = o.assigned_count > 0 ? ` (${o.assigned_count} active tasks)` : ' (0 tasks)';
                        if (o.is_role_target) {
                            opt.textContent = `${o.full_name} (${o.workstation})${badgeTxt}`;
                        } else {
                            opt.textContent = `${o.full_name} (@${o.username}) - ${o.workstation}${badgeTxt}`;
                        }
                        officerSelect.appendChild(opt);
                    });
                    officerWorkstationHint.textContent = `${data.officers.length} target(s) found in ${data.district_name} jurisdiction.`;

                    if (preselectUserId) {
                        officerSelect.value = preselectUserId;
                        onOfficerChange();
                    } else if (cat === 'range_veterinary_officer' && subRole === 'government_veterinary_surgeon') {
                        // Automatically select the district-wide role target
                        officerSelect.value = 'all_veterinary_surgeons';
                        onOfficerChange();
                    } else {
                        clearChecklist();
                    }
                } else {
                    currentOfficers = [];
                    officerSelect.innerHTML = '<option value="">-- No matching targets found --</option>';
                    officerWorkstationHint.textContent = 'No active staff or targets found for this filter in your district.';
                    clearChecklist();
                }
            })
            .catch(err => {
                console.error(err);
                officerSelect.innerHTML = '<option value="">-- Error loading targets --</option>';
                officerWorkstationHint.textContent = 'Error connecting to server.';
            });
    }

    // Helper: Update card selection visual state
    function updateCardVisual(card, isChecked) {
        const checkbox = card.querySelector('.action-checkbox');
        if (checkbox) checkbox.checked = isChecked;
        const statusText = card.querySelector('.status-text');
        if (isChecked) {
            card.classList.add('is-selected');
            if (statusText) statusText.textContent = 'Delegated';
        } else {
            card.classList.remove('is-selected');
            if (statusText) statusText.textContent = 'Hidden';
        }
    }

    // Helper: Refresh selection count badge
    function refreshSelectedCount() {
        const checkedBoxes = document.querySelectorAll('.action-checkbox:checked');
        const count = checkedBoxes.length;
        selectedCountBadge.textContent = `${count} of 14 Actions Delegated`;
        if (count > 0) {
            selectedCountBadge.className = 'badge bg-success px-3 py-2 fs-6 rounded-pill shadow-xs';
        } else {
            selectedCountBadge.className = 'badge bg-secondary px-3 py-2 fs-6 rounded-pill';
        }
    }

    function clearChecklist() {
        document.querySelectorAll('.action-card-select').forEach(card => {
            updateCardVisual(card, false);
        });
        refreshSelectedCount();
        btnRevokeAll.style.display = 'none';
        officerStatusHint.textContent = 'Please select a target to manage delegation.';
    }

    function selectAllChecklist() {
        document.querySelectorAll('.action-card-select').forEach(card => {
            updateCardVisual(card, true);
        });
        refreshSelectedCount();
    }

    // Interactive card clicks
    document.querySelectorAll('.action-card-select').forEach(card => {
        card.addEventListener('click', function(e) {
            const checkbox = this.querySelector('.action-checkbox');
            const newCheckedState = !checkbox.checked;
            updateCardVisual(this, newCheckedState);
            refreshSelectedCount();
        });
    });

    // Select All / Clear All buttons
    document.getElementById('btnSelectAll').addEventListener('click', selectAllChecklist);
    document.getElementById('btnClearAll').addEventListener('click', clearChecklist);

    // Category Filter Pills handler
    document.querySelectorAll('.filter-pill-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-pill-btn').forEach(b => {
                b.classList.remove('active', 'btn-dark');
                b.classList.add('btn-light', 'text-muted');
            });
            this.classList.add('active', 'btn-dark');
            this.classList.remove('btn-light', 'text-muted');

            const filter = this.getAttribute('data-filter');
            document.querySelectorAll('.action-col').forEach(col => {
                if (filter === 'all' || col.getAttribute('data-category') === filter) {
                    col.style.display = '';
                } else {
                    col.style.display = 'none';
                }
            });
        });
    });

    // Dropdown change events
    categorySelect.addEventListener('change', populateRoles);
    roleSelect.addEventListener('change', () => loadOfficers());

    function onOfficerChange() {
        const userId = officerSelect.value;
        if (!userId) {
            clearChecklist();
            return;
        }

        if (userId === 'all_veterinary_surgeons') {
            officerStatusHint.textContent = `Managing district-wide permissions for All Government Veterinary Surgeons across <?= htmlspecialchars($district_name) ?> District.`;
            officerWorkstationHint.innerHTML = `<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fw-semibold"><i class="bi bi-globe2 me-1"></i>District-Wide Delegation: Automatically applies across all ranges in <?= htmlspecialchars($district_name) ?> District. No individual range officer needed.</span>`;
        } else {
            const officer = currentOfficers.find(o => o.id == userId);
            officerStatusHint.textContent = `Managing district-wide permissions for ${officer ? officer.full_name : 'selected officer'}.`;
            officerWorkstationHint.innerHTML = officer ? `<strong>${officer.designation}</strong> &bull; Workstation: ${officer.workstation}<br><span class="text-primary fw-semibold"><i class="bi bi-globe2 me-1"></i>Scope: District-Wide (Covers all ranges in <?= htmlspecialchars($district_name) ?> District)</span>` : '';
        }

        // Fetch officer's assigned tasks
        fetch(`processors/task_assignment_crud.php?action=fetch_officer_assignments&user_id=${encodeURIComponent(userId)}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const assignedSet = new Set(data.assignments || []);
                    document.querySelectorAll('.action-card-select').forEach(card => {
                        const key = card.getAttribute('data-action-key');
                        const isAssigned = assignedSet.has(key);
                        updateCardVisual(card, isAssigned);
                    });
                    refreshSelectedCount();
                    btnRevokeAll.style.display = assignedSet.size > 0 ? 'inline-block' : 'none';
                }
            })
            .catch(err => {
                console.error(err);
            });
    }

    officerSelect.addEventListener('change', onOfficerChange);

    // Save assignments handler
    taskAssignmentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const userId = officerSelect.value;
        if (!userId) {
            Swal.fire({
                icon: 'warning',
                title: 'No Officer Selected',
                text: 'Please select an officer from Dropdown 3 before saving.',
                confirmButtonColor: '#370709'
            });
            return;
        }

        const selectedActions = [];
        document.querySelectorAll('.action-checkbox:checked').forEach(cb => {
            selectedActions.push(cb.value);
        });

        const formData = new FormData();
        formData.append('action', 'save_assignments');
        formData.append('user_id', userId);
        selectedActions.forEach(act => formData.append('action_ids[]', act));

        const btnSave = document.getElementById('btnSaveAssignments');
        const origHtml = btnSave.innerHTML;
        btnSave.disabled = true;
        btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

        fetch('processors/task_assignment_crud.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btnSave.disabled = false;
            btnSave.innerHTML = origHtml;

            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Task Assignments Saved!',
                    text: `${data.assigned_count} Quick Action(s) have been successfully delegated to this officer.`,
                    confirmButtonColor: '#370709'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Failed to save task assignments.',
                    confirmButtonColor: '#370709'
                });
            }
        })
        .catch(err => {
            btnSave.disabled = false;
            btnSave.innerHTML = origHtml;
            Swal.fire({
                icon: 'error',
                title: 'Network Error',
                text: 'Could not connect to the assignment processor.',
                confirmButtonColor: '#370709'
            });
        });
    });

    // Revoke all handler
    btnRevokeAll.addEventListener('click', function() {
        const userId = officerSelect.value;
        if (!userId) return;

        Swal.fire({
            title: 'Revoke All Tasks?',
            text: 'This will hide all 14 Quick Actions on the Range Details page for this officer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Revoke All'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'revoke_assignments');
                formData.append('user_id', userId);

                fetch('processors/task_assignment_crud.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Assignments Revoked',
                            text: data.message,
                            confirmButtonColor: '#370709'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message,
                            confirmButtonColor: '#370709'
                        });
                    }
                });
            }
        });
    });

    // Quick Edit from Directory table
    document.querySelectorAll('.btn-edit-officer').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetUserId = this.getAttribute('data-user-id');
            // Scroll smoothly to form
            window.scrollTo({ top: 0, behavior: 'smooth' });

            categorySelect.value = 'range_veterinary_officer';
            populateRoles();
            setTimeout(() => {
                if (targetUserId === 'all_veterinary_surgeons') {
                    roleSelect.value = 'government_veterinary_surgeon';
                    loadOfficers('all_veterinary_surgeons');
                } else {
                    roleSelect.value = '';
                    loadOfficers(targetUserId);
                }
            }, 100);
        });
    });

    // Quick Revoke from Directory table
    document.querySelectorAll('.btn-revoke-officer').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetUserId = this.getAttribute('data-user-id');
            Swal.fire({
                title: 'Revoke Officer Delegations?',
                text: 'This will reset the officer\'s visibility to default (empty).',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, Revoke'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'revoke_assignments');
                    formData.append('user_id', targetUserId);

                    fetch('processors/task_assignment_crud.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Revoked',
                                text: data.message,
                                confirmButtonColor: '#370709'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message
                            });
                        }
                    });
                }
            });
        });
    });

    // Instant table search filter
    if (filterTableInput) {
        filterTableInput.addEventListener('keyup', function() {
            const term = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#assignmentsTable tbody tr:not(#noAssignmentsRow)');
            rows.forEach(r => {
                const text = r.textContent.toLowerCase();
                r.style.display = text.includes(term) ? '' : 'none';
            });
        });
    }

    // Initial setup on page load
    populateRoles();
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
