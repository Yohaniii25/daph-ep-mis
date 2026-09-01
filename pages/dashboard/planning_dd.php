<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_roles = ['deputy_director_hq_1'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied. Unauthorized role footprint.");
}

require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../includes/header.php';

// =========================================================================
// GLOBAL PROVINCE-WIDE AGGREGATIONS (NO DISTRICT WHERE FILTER)
// =========================================================================

// Range & VS Metrics
$range_summary = $mysqli->query("
    SELECT 
        COUNT(id) AS total_ranges,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_ranges
    FROM veterinary_ranges
")->fetch_assoc();
$total_ranges  = (int)($range_summary['total_ranges'] ?? 0);
$active_ranges = (int)($range_summary['active_ranges'] ?? 0);

$vs_stats = $mysqli->query("SELECT COUNT(id) AS total_vs FROM users WHERE role = 'veterinary_surgeon' AND is_active = 1")->fetch_assoc();
$total_vs = (int)($vs_stats['total_vs'] ?? 0);

// Breeding Metrics
$ai_total = (int)($mysqli->query("SELECT COUNT(id) AS c FROM breeding_ai_performance")->fetch_assoc()['c'] ?? 0);
$calving_total = (int)($mysqli->query("SELECT COUNT(id) AS c FROM breeding_calving_performance")->fetch_assoc()['c'] ?? 0);
$pd_total = (int)($mysqli->query("SELECT COUNT(id) AS c FROM breeding_pd_performance")->fetch_assoc()['c'] ?? 0);

// Vaccination Metrics
$vac_stats = $mysqli->query("
    SELECT 
        COALESCE(SUM(used_doses), 0) AS used_doses,
        COALESCE(SUM(closing_balance), 0) AS stock_balance
    FROM monthly_vaccine_balances
")->fetch_assoc();
$total_vac_used = (int)($vac_stats['used_doses'] ?? 0);
$total_vac_stock = (int)($vac_stats['stock_balance'] ?? 0);

// Annual Target vs Achievement
$target_stats = $mysqli->query("
    SELECT 
        COALESCE(SUM(target_fmd), 0) AS fmd_tgt,
        COALESCE(SUM(target_bq), 0) AS bq_tgt,
        COALESCE(SUM(target_hs), 0) AS hs_tgt,
        COALESCE(SUM(target_fmd + target_bq + target_hs), 0) AS total_tgt
    FROM annual_vaccination_targets
")->fetch_assoc();
$total_vac_target = (int)($target_stats['total_tgt'] ?? 0);
$target_fmd = (int)($target_stats['fmd_tgt'] ?? 0);
$target_bq = (int)($target_stats['bq_tgt'] ?? 0);
$target_hs = (int)($target_stats['hs_tgt'] ?? 0);

// Clinical & Disease Metrics
$health_stats = $mysqli->query("
    SELECT 
        COUNT(id) AS total_case_reports,
        COALESCE(SUM(occurrence_count), 0) AS total_occurrences
    FROM animal_health_records
")->fetch_assoc();
$total_health_reports = (int)($health_stats['total_case_reports'] ?? 0);
$total_disease_cases = (int)($health_stats['total_occurrences'] ?? 0);

// District Comparative Aggregation
$district_sql = "
    SELECT 
        d.id AS district_id, 
        d.name AS district_name,
        COUNT(DISTINCT vr.id) AS range_count,
        COUNT(DISTINCT u.id) AS vs_count,
        COALESCE(ai_sub.total_ai, 0) AS total_ai,
        COALESCE(vac_sub.total_vac_used, 0) AS total_vac_used,
        COALESCE(health_sub.total_cases, 0) AS total_cases,
        COALESCE(health_sub.total_occurrences, 0) AS total_occurrences
    FROM districts d
    LEFT JOIN veterinary_ranges vr ON d.id = vr.district_id
    LEFT JOIN users u ON vr.id = u.range_id AND u.role = 'veterinary_surgeon' AND u.is_active = 1
    LEFT JOIN (
        SELECT vr2.district_id, COUNT(ai.id) AS total_ai
        FROM breeding_ai_performance ai
        JOIN veterinary_ranges vr2 ON ai.range_id = vr2.id
        GROUP BY vr2.district_id
    ) ai_sub ON d.id = ai_sub.district_id
    LEFT JOIN (
        SELECT mv.district_id, SUM(mv.used_doses) AS total_vac_used
        FROM monthly_vaccine_balances mv
        GROUP BY mv.district_id
    ) vac_sub ON d.id = vac_sub.district_id
    LEFT JOIN (
        SELECT vr3.district_id, COUNT(ah.id) AS total_cases, SUM(ah.occurrence_count) AS total_occurrences
        FROM animal_health_records ah
        JOIN veterinary_ranges vr3 ON ah.range_id = vr3.id
        GROUP BY vr3.district_id
    ) health_sub ON d.id = health_sub.district_id
    GROUP BY d.id, d.name
    ORDER BY d.id ASC
";
$district_res = $mysqli->query($district_sql);
$district_data = [];
$chart_districts = [];
$chart_ai = [];
$chart_vaccines = [];
$chart_cases = [];

if ($district_res) {
    while ($row = $district_res->fetch_assoc()) {
        $district_data[] = $row;
        $chart_districts[] = $row['district_name'];
        $chart_ai[] = (int)$row['total_ai'];
        $chart_vaccines[] = (int)$row['total_vac_used'];
        $chart_cases[] = (int)$row['total_cases'];
    }
}

// Species breakdown for Donut Chart
$species_res = $mysqli->query("
    SELECT animal_type, COALESCE(SUM(occurrence_count), 0) AS total_occurrences
    FROM animal_health_records
    GROUP BY animal_type
    ORDER BY total_occurrences DESC
");
$species_labels = [];
$species_counts = [];
if ($species_res) {
    while ($sp = $species_res->fetch_assoc()) {
        $species_labels[] = $sp['animal_type'];
        $species_counts[] = (int)$sp['total_occurrences'];
    }
}
if (empty($species_labels)) {
    $species_labels = ['Cattle', 'Buffalo', 'Goat', 'Poultry'];
    $species_counts = [0, 0, 0, 0];
}
?>

<div class="container-fluid px-4 py-3">

    <!-- Header & Navigation Link -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="fw-bold mb-0 text-dark">Planning Deputy Director Visual Dashboard</h2>
                <span class="badge bg-primary px-3 py-2 rounded-pill fw-semibold">HQ-1 Visual Overview</span>
                <span class="badge bg-dark px-3 py-2 rounded-pill fw-normal">Province-Wide Analytics</span>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Executive High-Level Visual Summary & Comparative Analytics Across All 45 Veterinary Ranges in the Eastern Province.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 mt-3 mt-md-0">
            <button type="button" class="btn btn-outline-secondary btn-sm shadow-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print Dashboard
            </button>
            <a href="<?= BASE_PATH ?>pages/planning_dd/range_details.php" class="btn btn-danger btn-sm shadow-sm">
                <i class="bi bi-grid-3x3-gap-fill me-1"></i> Range Details Hub
            </a>
        </div>
    </div>

    <!-- 1. Executive Visual KPI Counters -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 border-start border-primary border-4 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-bold">Total Veterinary Ranges</small>
                        <h3 class="fw-bold text-dark mb-0 mt-1"><?= number_format($total_ranges) ?></h3>
                        <small class="text-success"><i class="bi bi-check-circle-fill"></i> <?= $active_ranges ?> Active Across 3 Districts</small>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle">
                        <i class="bi bi-geo-alt-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 border-start border-success border-4 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-bold">Total A.I. Operations</small>
                        <h3 class="fw-bold text-success mb-0 mt-1"><?= number_format($ai_total) ?></h3>
                        <small class="text-muted"><?= $calving_total ?> Calvings / <?= $pd_total ?> PDs Logged</small>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle">
                        <i class="bi bi-activity fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 border-start border-warning border-4 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-bold">Vaccines Administered</small>
                        <h3 class="fw-bold text-dark mb-0 mt-1"><?= number_format($total_vac_used) ?> <small class="fs-6 text-muted">doses</small></h3>
                        <small class="text-muted"><?= number_format($total_vac_stock) ?> Doses in Stock</small>
                    </div>
                    <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle">
                        <i class="bi bi-shield-shaded fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 border-start border-danger border-4 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-bold">Clinical & Disease Cases</small>
                        <h3 class="fw-bold text-danger mb-0 mt-1"><?= number_format($total_disease_cases) ?> <small class="fs-6 text-muted">animals</small></h3>
                        <small class="text-muted"><?= $total_health_reports ?> Incident Reports Logged</small>
                    </div>
                    <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle">
                        <i class="bi bi-virus fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Primary Charts Row: Inter-District Comparison & Species Distribution -->
    <div class="row g-3 mb-4">
        <!-- Main Comparison Bar Chart -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-bar-chart-fill me-2 text-primary"></i>Inter-District Operational Comparison
                        </h5>
                        <small class="text-muted">Artificial Inseminations, Vaccine Doses, and Clinical Incidents across Ampara, Batticaloa, and Trincomalee</small>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    <div style="height: 320px; position: relative;">
                        <canvas id="districtComparisonChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Species Distribution Donut Chart -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-header bg-white border-0 py-3 px-4">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-pie-chart-fill me-2 text-danger"></i>Clinical & Species Distribution
                    </h5>
                    <small class="text-muted">Proportion of animal cases treated</small>
                </div>
                <div class="card-body px-4 pb-4 d-flex flex-column justify-content-center">
                    <div style="height: 240px; position: relative;">
                        <canvas id="speciesDonutChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Secondary Visual Analytics: Breeding Program Breakdown & District Overview -->
    <div class="row g-3 mb-4">
        <!-- Breeding Program Chart -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-header bg-white border-0 py-3 px-4">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-diagram-3-fill me-2 text-success"></i>Breeding Operations Distribution
                    </h5>
                    <small class="text-muted">A.I. Done vs Calving Returns vs Pregnancy Diagnoses</small>
                </div>
                <div class="card-body px-4 pb-4">
                    <div style="height: 250px; position: relative;">
                        <canvas id="breedingChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- District Performance Cards -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-header bg-white border-0 py-3 px-4">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-building me-2 text-dark"></i>District Summary Cards
                    </h5>
                    <small class="text-muted">High-level district operational snapshot</small>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="row g-2">
                        <?php foreach ($district_data as $dist): 
                            $d_border = ($dist['district_id'] == 1) ? '#1e3c72' : (($dist['district_id'] == 2) ? '#2e7d32' : '#b08723');
                        ?>
                            <div class="col-12">
                                <div class="p-3 rounded-3 border-start border-4 bg-light shadow-sm" style="border-color: <?= $d_border ?> !important;">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($dist['district_name']) ?> District</h6>
                                        <span class="badge bg-dark rounded-pill"><?= $dist['range_count'] ?> Ranges (<?= $dist['vs_count'] ?> Surgeons)</span>
                                    </div>
                                    <div class="row text-center g-2 pt-1">
                                        <div class="col-4 bg-white p-2 rounded-2 border">
                                            <small class="text-muted d-block">A.I. Count</small>
                                            <strong class="text-success fs-6"><?= number_format($dist['total_ai']) ?></strong>
                                        </div>
                                        <div class="col-4 bg-white p-2 rounded-2 border">
                                            <small class="text-muted d-block">Vaccines Used</small>
                                            <strong class="text-dark fs-6"><?= number_format($dist['total_vac_used']) ?></strong>
                                        </div>
                                        <div class="col-4 bg-white p-2 rounded-2 border">
                                            <small class="text-muted d-block">Clinical Cases</small>
                                            <strong class="text-danger fs-6"><?= number_format($dist['total_cases']) ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<!-- Chart.js Script -->
<script src="<?= BASE_PATH ?>assets/js/chart.min.js"></script>

<script>
$(document).ready(function() {
    // 1. Inter-District Bar Chart
    var ctxDistrict = document.getElementById('districtComparisonChart');
    if (ctxDistrict) {
        new Chart(ctxDistrict.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_districts) ?>,
                datasets: [
                    {
                        label: 'Artificial Inseminations',
                        data: <?= json_encode($chart_ai) ?>,
                        backgroundColor: 'rgba(230, 81, 0, 0.85)',
                        borderColor: '#e65100',
                        borderWidth: 1,
                        borderRadius: 6
                    },
                    {
                        label: 'Vaccines Used (Doses)',
                        data: <?= json_encode($chart_vaccines) ?>,
                        backgroundColor: 'rgba(176, 135, 35, 0.85)',
                        borderColor: '#b08723',
                        borderWidth: 1,
                        borderRadius: 6
                    },
                    {
                        label: 'Clinical Incidents',
                        data: <?= json_encode($chart_cases) ?>,
                        backgroundColor: 'rgba(46, 125, 50, 0.85)',
                        borderColor: '#2e7d32',
                        borderWidth: 1,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0, 0, 0, 0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // 2. Species Donut Chart
    var ctxSpecies = document.getElementById('speciesDonutChart');
    if (ctxSpecies) {
        new Chart(ctxSpecies.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($species_labels) ?>,
                datasets: [{
                    data: <?= json_encode($species_counts) ?>,
                    backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#0dcaf0', '#fd7e14'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
                }
            }
        });
    }

    // 3. Breeding Operations Chart
    var ctxBreeding = document.getElementById('breedingChart');
    if (ctxBreeding) {
        new Chart(ctxBreeding.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Artificial Insemination (AI)', 'Calving Returns', 'Pregnancy Diagnosis (PD)'],
                datasets: [{
                    label: 'Total Operations',
                    data: [<?= $ai_total ?>, <?= $calving_total ?>, <?= $pd_total ?>],
                    backgroundColor: ['rgba(230, 81, 0, 0.85)', 'rgba(46, 125, 50, 0.85)', 'rgba(21, 101, 192, 0.85)'],
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0, 0, 0, 0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
});
</script>
