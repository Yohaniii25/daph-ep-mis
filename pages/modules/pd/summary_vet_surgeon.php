<?php
/**
 * pages/modules/pd/summary_vet_surgeon.php
 * Dedicated Role Summary: Veterinary Surgeons (GVS / VS)
 * Province-wide aggregated statistics, Chart.js visuals, and range personnel directory
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

// 1. Fetch All Districts for Filter
$districts_res = $mysqli->query("SELECT id, name FROM districts ORDER BY name ASC");
$all_districts = $districts_res ? $districts_res->fetch_all(MYSQLI_ASSOC) : [];

// 2. Build Where Filter for Personnel
$vs_roles = ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon'];
$vs_placeholders = "'" . implode("','", $vs_roles) . "'";

$p_where = "u.role IN ($vs_placeholders)";
$params = [];
$types = "";

if ($filter_district_id) {
    $p_where .= " AND u.district_id = ?";
    $params[] = $filter_district_id;
    $types .= "i";
}

// Total VS count & Active
$count_query = "SELECT COUNT(*) AS total_vs,
                       SUM(CASE WHEN u.is_active = 1 THEN 1 ELSE 0 END) AS active_vs,
                       COUNT(DISTINCT u.range_id) AS ranges_manned
                FROM users u 
                WHERE $p_where";
$stmt = $mysqli->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$vs_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total_vs = intval($vs_stats['total_vs'] ?? 0);
$active_vs = intval($vs_stats['active_vs'] ?? 0);
$ranges_manned = intval($vs_stats['ranges_manned'] ?? 0);

// Total Ranges in Province / Selected District
$r_where = $filter_district_id ? "WHERE district_id = $filter_district_id" : "";
$ranges_total_query = $mysqli->query("SELECT COUNT(*) AS total_ranges FROM veterinary_ranges $r_where");
$total_ranges = $ranges_total_query ? intval($ranges_total_query->fetch_assoc()['total_ranges']) : 45;
$range_coverage_pct = $total_ranges > 0 ? round(($ranges_manned / $total_ranges) * 100, 1) : 0;

// Total Clinical Treatments (from animal_health_records)
$ah_where = $filter_district_id ? "WHERE vr.district_id = $filter_district_id" : "";
$ah_res = $mysqli->query("SELECT COUNT(a.id) AS total_cases, IFNULL(SUM(a.no_of_animals_treated), 0) AS total_treated 
                          FROM animal_health_records a 
                          LEFT JOIN veterinary_ranges vr ON a.range_id = vr.id 
                          $ah_where");
$ah_data = $ah_res ? $ah_res->fetch_assoc() : ['total_cases' => 0, 'total_treated' => 0];
$total_treated = intval($ah_data['total_treated'] ?? 0);
$total_cases = intval($ah_data['total_cases'] ?? 0);

// 3. District Distribution Data for Chart 1
$dist_chart_sql = "SELECT d.name AS district_name, COUNT(u.id) AS vs_count
                   FROM districts d
                   LEFT JOIN users u ON d.id = u.district_id AND u.role IN ($vs_placeholders) AND u.is_active = 1
                   GROUP BY d.id, d.name
                   ORDER BY d.name ASC";
$dist_chart_res = $mysqli->query($dist_chart_sql);
$chart_dist_labels = [];
$chart_dist_counts = [];
if ($dist_chart_res) {
    while ($row = $dist_chart_res->fetch_assoc()) {
        $chart_dist_labels[] = $row['district_name'];
        $chart_dist_counts[] = intval($row['vs_count']);
    }
}

// 4. Clinical Treatments Distribution for Chart 2
$cases_chart_sql = "SELECT d.name AS district_name, IFNULL(SUM(a.no_of_animals_treated), 0) AS treated_count
                    FROM districts d
                    LEFT JOIN veterinary_ranges vr ON d.id = vr.district_id
                    LEFT JOIN animal_health_records a ON vr.id = a.range_id
                    GROUP BY d.id, d.name
                    ORDER BY d.name ASC";
$cases_chart_res = $mysqli->query($cases_chart_sql);
$chart_case_labels = [];
$chart_case_counts = [];
if ($cases_chart_res) {
    while ($row = $cases_chart_res->fetch_assoc()) {
        $chart_case_labels[] = $row['district_name'];
        $chart_case_counts[] = intval($row['treated_count']);
    }
}

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

        <!-- Top Header & Breadcrumbs -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 border-bottom pb-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?= $base_path ?>dashboard.php" class="text-decoration-none text-muted">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="role_hub.php?role=vet_surgeon" class="text-decoration-none text-muted">Veterinary Surgeons Hub</a></li>
                        <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Summary Analytics</li>
                    </ol>
                </nav>
                <h3 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-hospital-fill text-danger"></i> Veterinary Surgeons Performance & Distribution
                </h3>
                <p class="text-muted small mb-0">Aggregated province-wide clinical caseloads, veterinary surgeon deployments, and range coverage metrics</p>
            </div>

            <!-- District Filter Dropdown -->
            <form method="GET" action="summary_vet_surgeon.php" class="d-flex align-items-center gap-2">
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
                    <a href="summary_vet_surgeon.php" class="btn btn-outline-secondary btn-sm" title="Clear Filter">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
                <a href="role_hub.php?role=vet_surgeon" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i> Hub
                </a>
            </form>
        </div>

        <!-- KPI Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-danger h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Total Surgeons</div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $total_vs ?></h3>
                            <span class="small text-muted"><?= $active_vs ?> Active in service</span>
                        </div>
                        <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-people-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-primary h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Range Coverage</div>
                            <h3 class="fw-bold mb-0 text-primary"><?= $range_coverage_pct ?>%</h3>
                            <span class="small text-muted"><?= $ranges_manned ?> of <?= $total_ranges ?> Ranges</span>
                        </div>
                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-geo-alt-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-success h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Animals Treated</div>
                            <h3 class="fw-bold mb-0 text-success"><?= number_format($total_treated) ?></h3>
                            <span class="small text-muted"><?= number_format($total_cases) ?> clinical case logs</span>
                        </div>
                        <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-heart-pulse-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-warning h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Surgeon Readiness</div>
                            <h3 class="fw-bold mb-0 text-dark"><?= ($total_vs > 0 && $active_vs > 0) ? round(($active_vs / $total_vs) * 100) : 0 ?>%</h3>
                            <span class="small text-success fw-semibold"><i class="bi bi-check-circle"></i> Operational</span>
                        </div>
                        <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-shield-check fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Visual Analytics Charts (Chart.js) -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Veterinary Surgeons by District</h5>
                            <p class="text-muted small mb-0">Active surgeon deployment across Eastern Province</p>
                        </div>
                        <span class="badge bg-danger rounded-pill">Personnel Count</span>
                    </div>
                    <div style="height: 280px; position: relative;">
                        <canvas id="vsDistrictChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Clinical Treatments Distribution</h5>
                            <p class="text-muted small mb-0">Total animals treated across districts</p>
                        </div>
                        <span class="badge bg-success rounded-pill">Clinical Volume</span>
                    </div>
                    <div style="height: 280px; position: relative;">
                        <canvas id="vsCasesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Personnel Directory Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Veterinary Surgeons Roster</h5>
                    <p class="text-muted small mb-0">Assigned government and range veterinary surgeons</p>
                </div>
                <span class="badge bg-light text-dark border"><?= count($personnel_list) ?> Records</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="vsTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Officer Name</th>
                                <th>Designation</th>
                                <th>Assigned District</th>
                                <th>Assigned Range</th>
                                <th>Service / Emp ID</th>
                                <th>Contact Info</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($personnel_list)): ?>
                                <?php foreach ($personnel_list as $row): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; font-size: 12px;">
                                                    <?= strtoupper(substr($row['full_name'] ?: $row['username'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['full_name'] ?: $row['username']) ?></div>
                                                    <small class="text-muted">@<?= htmlspecialchars($row['username']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['designation'] ?: 'Veterinary Surgeon') ?></span></td>
                                        <td><?= htmlspecialchars($row['district_name'] ?: 'Not Assigned') ?></td>
                                        <td><span class="fw-semibold text-dark"><?= htmlspecialchars($row['range_name'] ?: 'General Roster') ?></span></td>
                                        <td><code><?= htmlspecialchars($row['service_number'] ?: 'VS-' . $row['id']) ?></code></td>
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
                                    <td colspan="7" class="text-center py-4 text-muted">No Veterinary Surgeons found for the selected scope.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- Chart.js Initialization Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. District Deployment Chart
    const ctxDist = document.getElementById('vsDistrictChart');
    if (ctxDist) {
        new Chart(ctxDist, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_dist_labels) ?>,
                datasets: [{
                    label: 'Veterinary Surgeons',
                    data: <?= json_encode($chart_dist_counts) ?>,
                    backgroundColor: '#7f1d1d',
                    borderRadius: 8,
                    barThickness: 35
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }

    // 2. Clinical Treatments by District Chart
    const ctxCases = document.getElementById('vsCasesChart');
    if (ctxCases) {
        new Chart(ctxCases, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($chart_case_labels) ?>,
                datasets: [{
                    data: <?= json_encode($chart_case_counts) ?>,
                    backgroundColor: ['#500707', '#2563eb', '#16a34a', '#d97706'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
