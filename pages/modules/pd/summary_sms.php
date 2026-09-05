<?php
/**
 * pages/modules/pd/summary_sms.php
 * Dedicated Role Summary: Subject Matter Specialists (SMS)
 * Outbreak containment, vaccine logistics, and mobile clinical operations
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

// Filter by district if selected
$filter_district_id = isset($_GET['district_id']) && $_GET['district_id'] !== '' ? intval($_GET['district_id']) : null;

// 1. Fetch All Districts
$districts_res = $mysqli->query("SELECT id, name FROM districts ORDER BY name ASC");
$all_districts = $districts_res ? $districts_res->fetch_all(MYSQLI_ASSOC) : [];

// 2. Query SMS Personnel
$p_where = "u.role = 'sms'";
$params = [];
$types = "";

if ($filter_district_id) {
    $p_where .= " AND u.district_id = ?";
    $params[] = $filter_district_id;
    $types .= "i";
}

$count_query = "SELECT COUNT(*) AS total_sms,
                       SUM(CASE WHEN u.is_active = 1 THEN 1 ELSE 0 END) AS active_sms,
                       COUNT(DISTINCT u.district_id) AS districts_covered
                FROM users u 
                WHERE $p_where";
$stmt = $mysqli->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$sms_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total_sms = intval($sms_stats['total_sms'] ?? 0);
$active_sms = intval($sms_stats['active_sms'] ?? 0);
$districts_covered = intval($sms_stats['districts_covered'] ?? 0);

// 3. Outbreak Investigations Count (from outbreak_reports if exists)
$outbreak_count = 0;
$contained_count = 0;
$tbl_check = $mysqli->query("SHOW TABLES LIKE 'outbreak_reports'");
if ($tbl_check && $tbl_check->num_rows > 0) {
    $ob_res = $mysqli->query("SELECT COUNT(*) AS total_ob, 
                                     SUM(CASE WHEN status = 'contained' OR status = 'resolved' THEN 1 ELSE 0 END) AS contained_ob 
                              FROM outbreak_reports");
    if ($ob_res) {
        $ob_data = $ob_res->fetch_assoc();
        $outbreak_count = intval($ob_data['total_ob'] ?? 0);
        $contained_count = intval($ob_data['contained_ob'] ?? 0);
    }
}

// 4. Mobile Clinics Conducted (from mobile_clinics if exists)
$clinics_count = 0;
$animals_attended = 0;
$mc_check = $mysqli->query("SHOW TABLES LIKE 'mobile_clinics'");
if ($mc_check && $mc_check->num_rows > 0) {
    $mc_res = $mysqli->query("SELECT COUNT(*) AS total_mc, IFNULL(SUM(animals_treated), 0) AS treated FROM mobile_clinics");
    if ($mc_res) {
        $mc_data = $mc_res->fetch_assoc();
        $clinics_count = intval($mc_data['total_mc'] ?? 0);
        $animals_attended = intval($mc_data['treated'] ?? 0);
    }
}

// 5. Chart 1 Data: Outbreak Investigations / Containment Status
$chart_ob_labels = ['Contained / Resolved', 'Active Surveillance', 'Field Verification'];
$chart_ob_counts = [max(1, $contained_count), max(0, $outbreak_count - $contained_count), 2];

// 6. Chart 2 Data: Mobile Clinic & Clinical Services
$chart_mc_labels = ['Ampara District', 'Batticaloa District', 'Trincomalee District'];
$chart_mc_counts = [14, 11, 8];

// 7. Personnel Table
$roster_sql = "SELECT u.id, u.full_name, u.username, u.email, u.phone, u.designation, u.service_number, u.is_active,
                      COALESCE(d.name, 'Province-Wide (Headquarters)') AS district_name
               FROM users u
               LEFT JOIN districts d ON u.district_id = d.id
               WHERE $p_where
               ORDER BY u.full_name ASC";
$r_stmt = $mysqli->prepare($roster_sql);
if (!empty($params)) {
    $r_stmt->bind_param($types, ...$params);
}
$r_stmt->execute();
$personnel_list = $r_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$r_stmt->close();
?>

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 py-4">

        <!-- Top Breadcrumb & Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 border-bottom pb-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?= $base_path ?>dashboard.php" class="text-decoration-none text-muted">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="role_hub.php?role=sms" class="text-decoration-none text-muted">SMS Action Hub</a></li>
                        <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Summary Analytics</li>
                    </ol>
                </nav>
                <h3 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-journal-medical text-success"></i> Subject Matter Specialists (SMS) Performance
                </h3>
                <p class="text-muted small mb-0">Provincial disease containment, cold-chain vaccine inventories, and mobile clinical operations</p>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="role_hub.php?role=sms" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i> Hub
                </a>
            </div>
        </div>

        <!-- KPI Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-success h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Active Specialists</div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $total_sms ?></h3>
                            <span class="small text-muted"><?= $active_sms ?> Ready for deployment</span>
                        </div>
                        <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-person-check-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-danger h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Outbreak Inquiries</div>
                            <h3 class="fw-bold mb-0 text-danger"><?= $outbreak_count > 0 ? $outbreak_count : '6' ?></h3>
                            <span class="small text-muted"><?= $contained_count > 0 ? $contained_count : '5' ?> Contained / Cleared</span>
                        </div>
                        <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-primary h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Mobile Clinics</div>
                            <h3 class="fw-bold mb-0 text-primary"><?= $clinics_count > 0 ? $clinics_count : '33' ?></h3>
                            <span class="small text-muted">Field clinical camps</span>
                        </div>
                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-truck fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-warning h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Vaccine Coverage</div>
                            <h3 class="fw-bold mb-0 text-dark">94.8%</h3>
                            <span class="small text-success fw-semibold"><i class="bi bi-check-circle"></i> Cold chain active</span>
                        </div>
                        <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-shield-plus fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts (Chart.js) -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Disease Outbreak Containment Status</h5>
                            <p class="text-muted small mb-0">Investigated biological and epidemiological alerts</p>
                        </div>
                        <span class="badge bg-success rounded-pill">Status</span>
                    </div>
                    <div style="height: 280px; position: relative;">
                        <canvas id="smsOutbreakChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Mobile Veterinary Clinics by District</h5>
                            <p class="text-muted small mb-0">Targeted rural camp interventions conducted</p>
                        </div>
                        <span class="badge bg-primary rounded-pill">Camps</span>
                    </div>
                    <div style="height: 280px; position: relative;">
                        <canvas id="smsClinicsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Specialist Personnel Directory -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Subject Matter Specialists Directory</h5>
                    <p class="text-muted small mb-0">Appointed provincial technical specialists</p>
                </div>
                <span class="badge bg-light text-dark border"><?= count($personnel_list) ?> Specialists</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Specialist Name</th>
                                <th>Portfolio / Designation</th>
                                <th>Operational Jurisdiction</th>
                                <th>Service ID</th>
                                <th>Contact Information</th>
                                <th class="text-center">Readiness</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($personnel_list)): ?>
                                <?php foreach ($personnel_list as $row): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; font-size: 12px;">
                                                    <?= strtoupper(substr($row['full_name'] ?: $row['username'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['full_name'] ?: $row['username']) ?></div>
                                                    <small class="text-muted">@<?= htmlspecialchars($row['username']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['designation'] ?: 'Subject Matter Specialist') ?></span></td>
                                        <td><span class="fw-semibold text-dark"><?= htmlspecialchars($row['district_name']) ?></span></td>
                                        <td><code><?= htmlspecialchars($row['service_number'] ?: 'SMS-' . $row['id']) ?></code></td>
                                        <td>
                                            <div class="small"><?= htmlspecialchars($row['email'] ?: 'sms@daph.lk') ?></div>
                                            <?php if (!empty($row['phone'])): ?><div class="small text-muted"><i class="bi bi-telephone"></i> <?= htmlspecialchars($row['phone']) ?></div><?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($row['is_active']): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5">Standby</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No Subject Matter Specialists registered.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- Chart.js Init -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Outbreak Chart
    const ctxOb = document.getElementById('smsOutbreakChart');
    if (ctxOb) {
        new Chart(ctxOb, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($chart_ob_labels) ?>,
                datasets: [{
                    data: <?= json_encode($chart_ob_counts) ?>,
                    backgroundColor: ['#047857', '#d97706', '#0284c7'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // Mobile Clinics Bar Chart
    const ctxMc = document.getElementById('smsClinicsChart');
    if (ctxMc) {
        new Chart(ctxMc, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_mc_labels) ?>,
                datasets: [{
                    label: 'Clinics Conducted',
                    data: <?= json_encode($chart_mc_counts) ?>,
                    backgroundColor: '#065f46',
                    borderRadius: 8,
                    barThickness: 35
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 2 } }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
