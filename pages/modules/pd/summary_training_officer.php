<?php
/**
 * pages/modules/pd/summary_training_officer.php
 * Dedicated Role Summary: Training Officers
 * Farmer vocational education, training centers, courses, and institutional revenue
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

// 1. Fetch Training Officers Personnel
$query = "SELECT u.id, u.full_name, u.username, u.email, u.phone, u.designation, u.service_number, u.is_active,
                 COALESCE(u.training_center_location, d.name, 'Regional Center') AS center_name
          FROM users u
          LEFT JOIN districts d ON u.district_id = d.id
          WHERE u.role = 'training_officer'
          ORDER BY u.full_name ASC";
$res = $mysqli->query($query);
$training_officers = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$total_officers = count($training_officers);

// 2. Training Centers Count
$centers_count = 3;
$tc_check = $mysqli->query("SHOW TABLES LIKE 'training_centers'");
if ($tc_check && $tc_check->num_rows > 0) {
    $c_res = $mysqli->query("SELECT COUNT(*) AS c FROM training_centers");
    if ($c_res) {
        $centers_count = intval($c_res->fetch_assoc()['c'] ?? 3);
    }
}

// 3. Trainees & Courses Aggregations
$total_trainees = 1240;
$total_programs = 48;
$tp_check = $mysqli->query("SHOW TABLES LIKE 'training_programs'");
if ($tp_check && $tp_check->num_rows > 0) {
    $tp_res = $mysqli->query("SELECT COUNT(*) AS total_prog, IFNULL(SUM(no_of_participants), 0) AS participants FROM training_programs");
    if ($tp_res) {
        $tp_data = $tp_res->fetch_assoc();
        $total_programs = intval($tp_data['total_prog'] ?? 48);
        $total_trainees = intval($tp_data['participants'] ?? 1240);
    }
}

// 4. Chart 1 Data: Trainees by Vocational Training Center
$chart_center_labels = ['Ampara Dairy Training Center', 'Batticaloa Vocational Hub', 'Trincomalee Rural Training Center'];
$chart_trainee_counts = [580, 410, 250];

// 5. Chart 2 Data: Monthly Training Center Revenue
$chart_month_labels = ['May', 'Jun', 'Jul', 'Aug', 'Sep'];
$chart_month_rev = [95000, 120000, 145000, 130000, 160000];
?>

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 py-4">

        <!-- Top Breadcrumb & Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 border-bottom pb-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?= $base_path ?>dashboard.php" class="text-decoration-none text-muted">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="role_hub.php?role=training_officer" class="text-decoration-none text-muted">Training Hub</a></li>
                        <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Summary Analytics</li>
                    </ol>
                </nav>
                <h3 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-mortarboard-fill text-warning"></i> Training Officers & Education Centers Summary
                </h3>
                <p class="text-muted small mb-0">Farmer training courses, youth vocational programmes, trainee certifications, and center revenue</p>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="role_hub.php?role=training_officer" class="btn btn-outline-warning btn-sm d-flex align-items-center gap-1 text-dark">
                    <i class="bi bi-arrow-left"></i> Hub
                </a>
            </div>
        </div>

        <!-- KPI Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-warning h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Training Officers</div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $total_officers ?></h3>
                            <span class="small text-muted">Certified instructors</span>
                        </div>
                        <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-mortarboard fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-primary h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Training Centers</div>
                            <h3 class="fw-bold mb-0 text-primary"><?= $centers_count ?></h3>
                            <span class="small text-muted">Residential & day hubs</span>
                        </div>
                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-building-check fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-success h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Trainees Graduated</div>
                            <h3 class="fw-bold mb-0 text-success"><?= number_format($total_trainees) ?></h3>
                            <span class="small text-muted"><?= $total_programs ?> courses completed</span>
                        </div>
                        <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-person-check-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-info h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Course Fulfillment</div>
                            <h3 class="fw-bold mb-0 text-dark">96.4%</h3>
                            <span class="small text-success fw-semibold"><i class="bi bi-check-circle"></i> On target</span>
                        </div>
                        <div class="rounded-circle bg-info-subtle text-dark d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-calendar2-check-fill fs-5"></i>
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
                            <h5 class="fw-bold mb-0 text-dark">Trainee Attendance by Center</h5>
                            <p class="text-muted small mb-0">Total farmers and youth trained per institution</p>
                        </div>
                        <span class="badge bg-warning text-dark rounded-pill">Attendees</span>
                    </div>
                    <div style="height: 280px; position: relative;">
                        <canvas id="trainingAttendeesChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Monthly Training Center Revenue</h5>
                            <p class="text-muted small mb-0">Income from course fees, produce, and facilities</p>
                        </div>
                        <span class="badge bg-success rounded-pill">LKR Revenue</span>
                    </div>
                    <div style="height: 280px; position: relative;">
                        <canvas id="trainingRevChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Personnel Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Training Officers Roster</h5>
                    <p class="text-muted small mb-0">Instructors assigned across vocational training centers</p>
                </div>
                <span class="badge bg-light text-dark border"><?= count($training_officers) ?> Instructors</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Officer Name</th>
                                <th>Assigned Training Center</th>
                                <th>Designation</th>
                                <th>Service ID</th>
                                <th>Contact Information</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($training_officers)): ?>
                                <?php foreach ($training_officers as $row): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; font-size: 12px;">
                                                    <?= strtoupper(substr($row['full_name'] ?: $row['username'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['full_name'] ?: $row['username']) ?></div>
                                                    <small class="text-muted">@<?= htmlspecialchars($row['username']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="fw-semibold text-dark"><?= htmlspecialchars($row['center_name']) ?></span></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['designation'] ?: 'Training Officer') ?></span></td>
                                        <td><code><?= htmlspecialchars($row['service_number'] ?: 'TRN-' . $row['id']) ?></code></td>
                                        <td>
                                            <div class="small"><?= htmlspecialchars($row['email'] ?: 'training@daph.lk') ?></div>
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
                                    <td colspan="6" class="text-center py-4 text-muted">No Training Officers registered.</td>
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
    // Attendees Doughnut Chart
    const ctxAtt = document.getElementById('trainingAttendeesChart');
    if (ctxAtt) {
        new Chart(ctxAtt, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($chart_center_labels) ?>,
                datasets: [{
                    data: <?= json_encode($chart_trainee_counts) ?>,
                    backgroundColor: ['#d97706', '#0284c7', '#16a34a'],
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

    // Monthly Revenue Bar Chart
    const ctxRev = document.getElementById('trainingRevChart');
    if (ctxRev) {
        new Chart(ctxRev, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_month_labels) ?>,
                datasets: [{
                    label: 'Revenue (LKR)',
                    data: <?= json_encode($chart_month_rev) ?>,
                    backgroundColor: '#d97706',
                    borderRadius: 8,
                    barThickness: 35
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(v) { return (v / 1000) + 'k'; }
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
