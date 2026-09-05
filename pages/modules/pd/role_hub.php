<?php
/**
 * pages/modules/pd/role_hub.php
 * Action Hub UI for Provincial Director User Roles
 * Stripped-back, clean interface with a single prominent "View Summary" call-to-action
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_roles = ['provincial_director', 'deputy_director_hq_1', 'deputy_director_hq_2', 'administrator'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../../../index.php");
    exit();
}

require_once __DIR__ . '/../../../config/db_connect.php';
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';

$requested_role = trim($_GET['role'] ?? 'vet_surgeon');

// Registry of supported user roles in the Provincial Director module
$role_definitions = [
    'vet_surgeon' => [
        'title' => 'Veterinary Surgeons (GVS / VS)',
        'subtitle' => 'Field clinical services, animal health management, and artificial insemination across veterinary ranges',
        'icon' => 'bi-hospital-fill',
        'badge' => 'Clinical & Range Veterinary Cadre',
        'gradient' => 'linear-gradient(135deg, #500707 0%, #7f1d1d 100%)',
        'summary_url' => 'summary_vet_surgeon.php',
        'db_role' => ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon'],
        'designation_match' => null,
        'mandate' => 'Veterinary Surgeons provide core clinical treatments, preventive vaccinations, disease surveillance, and breeding logistics for all 45 veterinary ranges across the Eastern Province.',
        'key_deliverables' => [
            'Daily clinical outpatient treatments and surgical interventions',
            'Bovine & caprine artificial insemination and fertility monitoring',
            'Proactive disease outbreak containment and rabies eradication campaigns'
        ]
    ],
    'ldo' => [
        'title' => 'Livestock Development Officers (LDO)',
        'subtitle' => 'Extension outreach, farmer advisory, artificial insemination, and field data collection',
        'icon' => 'bi-person-badge-fill',
        'badge' => 'Livestock Extension Cadre',
        'gradient' => 'linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%)',
        'summary_url' => 'summary_ldo.php',
        'db_role' => ['employee', 'livestock_development_officer'],
        'designation_match' => 'LDO',
        'mandate' => 'Livestock Development Officers serve as the frontline extension and advisory link to farmers, driving field programmes, dairy development, and livestock census reporting.',
        'key_deliverables' => [
            'Farmer group trainings and field husbandry demonstrations',
            'Pasture cultivation and silage making advisory services',
            'Livestock breeding records and monthly field dairy reporting'
        ]
    ],
    'sms' => [
        'title' => 'Subject Matter Specialists (SMS)',
        'subtitle' => 'Provincial epidemiology, disease outbreak containment, vaccine logistics, and mobile clinical operations',
        'icon' => 'bi-journal-medical',
        'badge' => 'Specialist Technical Cadre',
        'gradient' => 'linear-gradient(135deg, #065f46 0%, #047857 100%)',
        'summary_url' => 'summary_sms.php',
        'db_role' => ['sms'],
        'designation_match' => null,
        'mandate' => 'Subject Matter Specialists oversee provincial veterinary protocols, epidemiological investigations, emergency containment, and province-wide cold-chain vaccine inventories.',
        'key_deliverables' => [
            'Rapid investigation of suspected animal disease outbreaks',
            'Vaccine balance monitoring and cold-chain distribution logistics',
            'Organization and deployment of targeted mobile veterinary clinics'
        ]
    ],
    'district_dd' => [
        'title' => 'District Deputy Directors',
        'subtitle' => 'District-level administration, range supervision, revenue management, and task delegation',
        'icon' => 'bi-geo-alt-fill',
        'badge' => 'District Executive Leadership',
        'gradient' => 'linear-gradient(135deg, #581c87 0%, #6b21a8 100%)',
        'summary_url' => 'summary_district_dd.php',
        'db_role' => ['district_dd', 'deputy_director_district'],
        'designation_match' => null,
        'mandate' => 'District Deputy Directors lead the district veterinary offices in Ampara, Batticaloa, and Trincomalee, supervising Range Officers, delegating critical operations, and overseeing public revenues.',
        'key_deliverables' => [
            'Comprehensive district office asset and inventory administration',
            'District-wide task delegation and maker-checker approval coordination',
            'District revenue collection, licensing, and financial accountability'
        ]
    ],
    'training_officer' => [
        'title' => 'Training Officers',
        'subtitle' => 'Farmer education centers, vocational youth courses, advanced programmes, and demonstration farms',
        'icon' => 'bi-mortarboard-fill',
        'badge' => 'Vocational & Farmer Education',
        'gradient' => 'linear-gradient(135deg, #854d0e 0%, #a16207 100%)',
        'summary_url' => 'summary_training_officer.php',
        'db_role' => ['training_officer'],
        'designation_match' => null,
        'mandate' => 'Training Officers oversee the regional vocational training centers in Eastern Province, delivering structured practical training to dairy farmers, youth entrepreneurs, and departmental staff.',
        'key_deliverables' => [
            'Conducting certified residential and field dairy training courses',
            'Managing demonstration livestock units and produce registers',
            'Preparing monthly institutional revenue reports and attendance records'
        ]
    ],
    'farms' => [
        'title' => 'Farm Officers & Managers',
        'subtitle' => 'Regional livestock breeding farms, commercial hatcheries, parent stock flocks, and dairy herd genetics',
        'icon' => 'bi-flower1',
        'badge' => 'Livestock Production Cadre',
        'gradient' => 'linear-gradient(135deg, #155e75 0%, #0e7490 100%)',
        'summary_url' => 'summary_farms.php',
        'db_role' => ['farms_dd'],
        'designation_match' => null,
        'mandate' => 'Farm Officers manage state livestock and poultry breeding centers, producing day-old chicks, breeding heifers, and high-yield genetic parent stock for provincial farmers.',
        'key_deliverables' => [
            'Parent stock flock mortality, egg collection, and hatchery management',
            'Dairy cattle breeding, milking logs, and feed conversion optimization',
            'State farm infrastructure, pastureland, and capital equipment upkeep'
        ]
    ]
];

if (!isset($role_definitions[$requested_role])) {
    $requested_role = 'vet_surgeon';
}

$role_info = $role_definitions[$requested_role];

// Retrieve live counts from database
$db_roles = $role_info['db_role'];
$placeholders = implode(',', array_fill(0, count($db_roles), '?'));

$query = "SELECT COUNT(*) AS total_officers, 
                 SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_officers,
                 COUNT(DISTINCT district_id) AS districts_covered,
                 COUNT(DISTINCT range_id) AS ranges_covered
          FROM users 
          WHERE role IN ($placeholders)";

$params = $db_roles;
$types = str_repeat('s', count($db_roles));

if (!empty($role_info['designation_match'])) {
    $query .= " AND (designation = ? OR service_category LIKE ?)";
    $params[] = $role_info['designation_match'];
    $params[] = '%' . $role_info['designation_match'] . '%';
    $types .= 'ss';
}

$stmt = $mysqli->prepare($query);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    $stats = ['total_officers' => 0, 'active_officers' => 0, 'districts_covered' => 0, 'ranges_covered' => 0];
}

$total_officers = intval($stats['total_officers'] ?? 0);
$active_officers = intval($stats['active_officers'] ?? 0);
$districts_covered = intval($stats['districts_covered'] ?? 0);
$ranges_covered = intval($stats['ranges_covered'] ?? 0);
?>

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 py-4">

        <!-- Breadcrumb & Top Bar -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_path ?>dashboard.php" class="text-decoration-none text-muted">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_path ?>pages/categories/view.php?cat=provincial_director" class="text-decoration-none text-muted">Provincial Director</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Action Hub</li>
            </ol>
        </nav>

        <!-- Role Quick Switcher Pills -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
            <div class="card-body p-2 bg-white">
                <div class="d-flex align-items-center gap-2 overflow-auto py-1 px-2 text-nowrap">
                    <span class="small fw-bold text-muted text-uppercase me-2" style="font-size: 11px;">Select User Role:</span>
                    <?php foreach ($role_definitions as $r_key => $r_data): ?>
                        <a href="role_hub.php?role=<?= $r_key ?>" 
                           class="btn btn-sm rounded-pill px-3 py-1.5 fw-semibold <?= ($r_key === $requested_role) ? 'btn-danger text-white shadow-sm' : 'btn-outline-secondary bg-white' ?>"
                           style="font-size: 12px; transition: all 0.2s;">
                            <i class="bi <?= $r_data['icon'] ?> me-1"></i> <?= htmlspecialchars($r_data['title']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Clean, Stripped-Back Action Hub Hero Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden text-white" style="background: <?= $role_info['gradient'] ?>;">
            <div class="card-body p-4 p-md-5 position-relative">
                <div class="position-absolute" style="right: -20px; bottom: -30px; opacity: 0.12; pointer-events: none;">
                    <i class="bi <?= $role_info['icon'] ?>" style="font-size: 15rem;"></i>
                </div>
                
                <div class="position-relative" style="z-index: 2; max-width: 820px;">
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white text-dark small fw-bold mb-3 shadow-sm">
                        <i class="bi <?= $role_info['icon'] ?> text-danger"></i> <?= htmlspecialchars($role_info['badge']) ?>
                        <span class="badge bg-secondary text-white rounded-pill px-2" style="font-size: 10px;">Province-Wide Oversight</span>
                    </div>

                    <h1 class="fw-bold display-6 mb-2 text-white"><?= htmlspecialchars($role_info['title']) ?></h1>
                    <p class="fs-6 opacity-90 mb-4"><?= htmlspecialchars($role_info['subtitle']) ?></p>

                    <!-- Scope KPI Badges -->
                    <div class="d-flex flex-wrap gap-3 mb-4">
                        <div class="bg-white bg-opacity-10 border border-white border-opacity-25 rounded-3 px-3 py-2 text-white">
                            <div class="small opacity-75 text-uppercase fw-semibold" style="font-size: 11px;">Total Personnel</div>
                            <div class="fs-4 fw-bold"><?= $total_officers ?> Officers</div>
                        </div>
                        <div class="bg-white bg-opacity-10 border border-white border-opacity-25 rounded-3 px-3 py-2 text-white">
                            <div class="small opacity-75 text-uppercase fw-semibold" style="font-size: 11px;">Active In Service</div>
                            <div class="fs-4 fw-bold"><?= $active_officers ?> Active</div>
                        </div>
                        <div class="bg-white bg-opacity-10 border border-white border-opacity-25 rounded-3 px-3 py-2 text-white">
                            <div class="small opacity-75 text-uppercase fw-semibold" style="font-size: 11px;">Districts Active</div>
                            <div class="fs-4 fw-bold"><?= $districts_covered ?> / 3 Districts</div>
                        </div>
                    </div>

                    <!-- Single Prominent Call-To-Action Button -->
                    <div class="pt-2">
                        <a href="<?= $role_info['summary_url'] ?>" class="btn btn-light btn-lg px-5 py-3 rounded-pill fw-bold text-dark d-inline-flex align-items-center gap-3 shadow-lg hover-elevate">
                            <i class="bi bi-bar-chart-line-fill text-danger fs-4"></i>
                            <span class="fs-5">View Summary</span>
                            <i class="bi bi-arrow-right fs-5"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Role Overview & Governance Mandate -->
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4 bg-white">
                    <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-shield-check text-danger"></i> Role Mandate & Governance Scope
                    </h5>
                    <p class="text-secondary leading-relaxed mb-4">
                        <?= htmlspecialchars($role_info['mandate']) ?>
                    </p>
                    <h6 class="fw-bold text-dark small text-uppercase mb-2">Core Operational Deliverables:</h6>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($role_info['key_deliverables'] as $item): ?>
                            <li class="d-flex align-items-start gap-2 mb-2 text-secondary">
                                <i class="bi bi-check-circle-fill text-success mt-1 flex-shrink-0"></i>
                                <span><?= htmlspecialchars($item) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4 bg-white d-flex flex-column justify-content-between">
                    <div>
                        <h5 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                            <i class="bi bi-pie-chart-fill text-danger"></i> Dedicated Role Summary
                        </h5>
                        <p class="text-muted small mb-4">
                            Access detailed statistical charts, geographical personnel distribution, activity trends, and individual officer performance records.
                        </p>

                        <div class="p-3 bg-light rounded-3 mb-3 border">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small text-muted fw-semibold">Reporting Scope:</span>
                                <span class="badge bg-danger rounded-pill">Province-Wide</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small text-muted fw-semibold">Data Visuals:</span>
                                <span class="small fw-bold text-dark">Chart.js Analytics</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small text-muted fw-semibold">Export Format:</span>
                                <span class="small fw-bold text-dark">Print & DataTables</span>
                            </div>
                        </div>
                    </div>

                    <a href="<?= $role_info['summary_url'] ?>" class="btn btn-danger w-100 py-2.5 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2">
                        <span>Launch <?= htmlspecialchars($role_info['title']) ?> Summary</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>

    </main>
</div>

<style>
.hover-elevate {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-elevate:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2) !important;
}
</style>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
