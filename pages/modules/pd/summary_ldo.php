<?php
/**
 * pages/modules/pd/summary_ldo.php
 * Dedicated Role Summary: Livestock Development Officers (LDO)
 * Province-wide aggregated statistics, field coverage, and extension officer directory
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

// 2. Query LDO & Field Extension Personnel
$ldo_roles = ['employee', 'livestock_development_officer', 'development_officer'];
$ldo_placeholders = "'" . implode("','", $ldo_roles) . "'";

$p_where = "(u.role IN ($ldo_placeholders) OR u.designation LIKE '%LDO%' OR u.designation LIKE '%DO%')";
$params = [];
$types = "";

if ($filter_district_id) {
    $p_where .= " AND u.district_id = ?";
    $params[] = $filter_district_id;
    $types .= "i";
}

// Stats count
$count_query = "SELECT COUNT(*) AS total_officers,
                       SUM(CASE WHEN u.is_active = 1 THEN 1 ELSE 0 END) AS active_officers,
                       COUNT(DISTINCT u.range_id) AS ranges_covered,
                       COUNT(DISTINCT u.district_id) AS districts_covered
                FROM users u 
                WHERE $p_where";
$stmt = $mysqli->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$ldo_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total_officers = intval($ldo_stats['total_officers'] ?? 0);
$active_officers = intval($ldo_stats['active_officers'] ?? 0);
$ranges_covered = intval($ldo_stats['ranges_covered'] ?? 0);
$districts_covered = intval($ldo_stats['districts_covered'] ?? 0);

// Total Ranges for coverage calculation
$r_where = $filter_district_id ? "WHERE district_id = $filter_district_id" : "";
$ranges_total_query = $mysqli->query("SELECT COUNT(*) AS total_ranges FROM veterinary_ranges $r_where");
$total_ranges = $ranges_total_query ? intval($ranges_total_query->fetch_assoc()['total_ranges']) : 45;
$coverage_pct = $total_ranges > 0 ? round(($ranges_covered / $total_ranges) * 100, 1) : 0;

// 3. District Distribution Data for Chart 1
$dist_chart_sql = "SELECT d.name AS district_name, COUNT(u.id) AS officer_count
                   FROM districts d
                   LEFT JOIN users u ON d.id = u.district_id AND (u.role IN ($ldo_placeholders) OR u.designation LIKE '%LDO%') AND u.is_active = 1
                   GROUP BY d.id, d.name
                   ORDER BY d.name ASC";
$dist_chart_res = $mysqli->query($dist_chart_sql);
$chart_dist_labels = [];
$chart_dist_counts = [];
if ($dist_chart_res) {
    while ($row = $dist_chart_res->fetch_assoc()) {
        $chart_dist_labels[] = $row['district_name'];
        $chart_dist_counts[] = intval($row['officer_count']);
    }
}

// 4. Cadre Breakdown Data for Chart 2
$desig_chart_sql = "SELECT COALESCE(NULLIF(u.designation, ''), 'General Extension Officer') AS desig_label, COUNT(u.id) AS count
                    FROM users u
                    WHERE $p_where
                    GROUP BY desig_label
                    ORDER BY count DESC LIMIT 5";
$desig_stmt = $mysqli->prepare($desig_chart_sql);
if (!empty($params)) {
    $desig_stmt->bind_param($types, ...$params);
}
$desig_stmt->execute();
$desig_res = $desig_stmt->get_result();
$chart_desig_labels = [];
$chart_desig_counts = [];
while ($row = $desig_res->fetch_assoc()) {
    $chart_desig_labels[] = $row['desig_label'];
    $chart_desig_counts[] = intval($row['count']);
}
$desig_stmt->close();

// 5. Personnel Roster Table
$roster_sql = "SELECT u.id, u.full_name, u.username, u.email, u.phone, u.designation, u.service_number, u.is_active,
                      d.name AS district_name, vr.name AS range_name
               FROM users u
               LEFT JOIN districts d ON u.district_id = d.id
               LEFT JOIN veterinary_ranges vr ON u.range_id = vr.id
               WHERE $p_where
               ORDER BY d.name ASC, vr.name ASC, u.full_name ASC";
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

        <!-- Header & Breadcrumb -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 border-bottom pb-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?= $base_path ?>dashboard.php" class="text-decoration-none text-muted">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="role_hub.php?role=ldo" class="text-decoration-none text-muted">LDO Action Hub</a></li>
                        <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Summary Analytics</li>
                    </ol>
                </nav>
                <h3 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-person-badge-fill text-primary"></i> Livestock Development Officers (LDO) Summary
                </h3>
                <p class="text-muted small mb-0">Province-wide field extension coverage, agricultural advisory staff deployment, and range allocations</p>
            </div>

            <!-- District Filter Form -->
            <form method="GET" action="summary_ldo.php" class="d-flex align-items-center gap-2">
                <label class="small fw-semibold text-muted text-nowrap"><i class="bi bi-funnel"></i> District:</label>
                <select name="district_id" class="form-select form-select-sm shadow-sm" onchange="this.form.submit()" style="min-width: 170px;">
                    <option value="">All Province (All 3)</option>
                    <?php foreach ($all_districts as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $filter_district_id === intval($d['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($filter_district_id): ?>
                    <a href="summary_ldo.php" class="btn btn-outline-secondary btn-sm" title="Clear Filter">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
                <a href="role_hub.php?role=ldo" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i> Hub
                </a>
            </form>
        </div>

        <!-- KPI Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-primary h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Total Extension Staff</div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $total_officers ?></h3>
                            <span class="small text-muted"><?= $active_officers ?> Active in field</span>
                        </div>
                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-person-badge fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-success h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Field Coverage</div>
                            <h3 class="fw-bold mb-0 text-success"><?= $coverage_pct ?>%</h3>
                            <span class="small text-muted"><?= $ranges_covered ?> of <?= $total_ranges ?> Ranges</span>
                        </div>
                        <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-geo-alt-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-info h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Districts Active</div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $districts_covered ?> / 3</h3>
                            <span class="small text-muted">Field extension hubs</span>
                        </div>
                        <div class="rounded-circle bg-info-subtle text-dark d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-map-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-warning h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Field Readiness</div>
                            <h3 class="fw-bold mb-0 text-dark"><?= ($total_officers > 0 && $active_officers > 0) ? round(($active_officers / $total_officers) * 100) : 0 ?>%</h3>
                            <span class="small text-success fw-semibold"><i class="bi bi-check-circle"></i> Deployed</span>
                        </div>
                        <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-award fs-5"></i>
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
                            <h5 class="fw-bold mb-0 text-dark">LDO Deployment by District</h5>
                            <p class="text-muted small mb-0">Field officer distribution across the Eastern Province</p>
                        </div>
                        <span class="badge bg-primary rounded-pill">Personnel Count</span>
                    </div>
                    <div style="height: 280px; position: relative;">
                        <canvas id="ldoDistrictChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Extension Cadre Breakdown</h5>
                            <p class="text-muted small mb-0">Designation distribution among field personnel</p>
                        </div>
                        <span class="badge bg-info text-dark rounded-pill">Cadres</span>
                    </div>
                    <div style="height: 280px; position: relative;">
                        <canvas id="ldoCadreChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Personnel Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Field Extension Officers Directory</h5>
                    <p class="text-muted small mb-0">Assigned LDOs and Development Officers</p>
                </div>
                <span class="badge bg-light text-dark border"><?= count($personnel_list) ?> Personnel</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Officer Name</th>
                                <th>Designation</th>
                                <th>District</th>
                                <th>Assigned Range</th>
                                <th>Service Number</th>
                                <th>Contact</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($personnel_list)): ?>
                                <?php foreach ($personnel_list as $row): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; font-size: 12px;">
                                                    <?= strtoupper(substr($row['full_name'] ?: $row['username'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['full_name'] ?: $row['username']) ?></div>
                                                    <small class="text-muted">@<?= htmlspecialchars($row['username']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['designation'] ?: 'Livestock Development Officer') ?></span></td>
                                        <td><?= htmlspecialchars($row['district_name'] ?: 'Eastern Province') ?></td>
                                        <td><span class="fw-semibold text-dark"><?= htmlspecialchars($row['range_name'] ?: 'General Field Duty') ?></span></td>
                                        <td><code><?= htmlspecialchars($row['service_number'] ?: 'LDO-' . $row['id']) ?></code></td>
                                        <td>
                                            <div class="small"><?= htmlspecialchars($row['email'] ?: 'No email') ?></div>
                                            <?php if (!empty($row['phone'])): ?><div class="small text-muted"><i class="bi bi-telephone"></i> <?= htmlspecialchars($row['phone']) ?></div><?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($row['is_active']): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No Livestock Development Officers found for the selected scope.</td>
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
    // District Chart
    const ctxDist = document.getElementById('ldoDistrictChart');
    if (ctxDist) {
        new Chart(ctxDist, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_dist_labels) ?>,
                datasets: [{
                    label: 'Officers Deployed',
                    data: <?= json_encode($chart_dist_counts) ?>,
                    backgroundColor: '#1e40af',
                    borderRadius: 8,
                    barThickness: 35
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }

    // Cadre Doughnut Chart
    const ctxCadre = document.getElementById('ldoCadreChart');
    if (ctxCadre) {
        new Chart(ctxCadre, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($chart_desig_labels) ?>,
                datasets: [{
                    data: <?= json_encode($chart_desig_counts) ?>,
                    backgroundColor: ['#1e40af', '#0284c7', '#0d9488', '#f59e0b', '#84cc16'],
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
});
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
