<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_hq_roles = ['provincial_director', 'deputy_director_hq_1', 'deputy_director_hq_2', 'administrator'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_hq_roles)) {
    die("Access denied. Unauthorized role footprint.");
}

require_once './config/db_connect.php';
require_once './includes/header.php';

$role = $_SESSION['role'] ?? 'provincial_director';

$role_titles = [
    'deputy_director_hq_1' => ['title' => 'Deputy Director - H/Q-1 Dashboard', 'badge' => 'Deputy Director H/Q-1', 'desc' => 'Consolidated province-wide operational summaries and global data access across all functional divisions.'],
    'deputy_director_hq_2' => ['title' => 'Deputy Director - H/Q-2 Dashboard', 'badge' => 'Deputy Director H/Q-2', 'desc' => 'Consolidated province-wide operational summaries and global data access across all functional divisions.'],
    'provincial_director'  => ['title' => 'Provincial Director Dashboard', 'badge' => 'Provincial Director', 'desc' => 'Unified management summaries across all functional veterinary divisions.'],
    'administrator'        => ['title' => 'Executive Director Dashboard', 'badge' => 'Administrator', 'desc' => 'Global management summaries across all provincial divisions.']
];

$dashboard_info = $role_titles[$role] ?? $role_titles['provincial_director'];

// Total Employees (Global Province-wide)
$emp_query = "SELECT COUNT(id) AS total_emp FROM `users` WHERE `is_active` = 1";
$emp_res = $mysqli->query($emp_query);
$total_employees = ($emp_res) ? $emp_res->fetch_assoc()['total_emp'] : 0;

// Active Ranges (Global Province-wide)
$range_query = "SELECT COUNT(id) AS total_ranges FROM `veterinary_ranges` WHERE `is_active` = 1";
$range_res = $mysqli->query($range_query);
$total_ranges = ($range_res) ? $range_res->fetch_assoc()['total_ranges'] : 0;

// Hatchability Rates Summary
$hatch_query = "SELECT 
                    SUM(hatchable_count) AS total_hatchable, 
                    SUM(chicks_hatched) AS total_hatched 
                FROM `hatchery_batches`";
$hatch_res = $mysqli->query($hatch_query);
$hatch_rate = 0.00;
if ($hatch_res) {
    $hatch_data = $hatch_res->fetch_assoc();
    if (($hatch_data['total_hatchable'] ?? 0) > 0) {
        $hatch_rate = ($hatch_data['total_hatched'] / $hatch_data['total_hatchable']) * 100;
    }
}

// Total Hatchery Revenue
$sales_query = "SELECT SUM(quantity_sold * actual_rate) AS total_rev FROM `hatchery_sales`";
$sales_res = $mysqli->query($sales_query);
$total_revenue = ($sales_res) ? $sales_res->fetch_assoc()['total_rev'] : 0.00;

// Total Remaining Regional Vaccine Doses
$drug_query = "SELECT SUM(starter_count_month + during_month_received - used_doses_count - doses_damaged) AS live_stock FROM `drug_records`";
$drug_res = $mysqli->query($drug_query);
$total_vaccines = ($drug_res) ? $drug_res->fetch_assoc()['live_stock'] : 0;

// Today's Present Staff Estimation (Users active without active leaves today)
$attendance_query = "SELECT COUNT(u.id) AS present_today FROM `users` u 
                     WHERE u.is_active = 1 AND u.id NOT IN (
                        SELECT user_id FROM `leave_requests` 
                        WHERE CURDATE() BETWEEN start_date AND resume_date AND status = 'Approved'
                     )";
$attendance_res = $mysqli->query($attendance_query);
$present_today = ($attendance_res) ? $attendance_res->fetch_assoc()['present_today'] : 0;
?>

<?php require_once './includes/sidebar.php'; ?>

<link rel="stylesheet" href="../../assets/css/dataTables.bootstrap5.min.css">

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <div>
                <h2 class="text-dark fw-bold mb-0"><?= htmlspecialchars($dashboard_info['title']) ?></h2>
                <p class="text-muted small mb-0"><?= htmlspecialchars($dashboard_info['desc']) ?></p>
            </div>
            <div class="d-flex gap-2">
                <span class="badge bg-danger p-2 font-monospace shadow-sm"><?= htmlspecialchars($dashboard_info['badge']) ?></span>
                <span class="badge bg-dark p-2 font-monospace shadow-sm">All Districts Scope</span>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-4 col-md-6 col-sm-12">
                <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
                    <div class="card-body p-3">
                        <small class="text-muted text-uppercase fw-bold d-block mb-1">On-Duty Today</small>
                        <h3 class="text-dark fw-bold mb-0"><?= number_format($present_today) ?></h3>
                        <small class="text-success text-nowrap"><i class="bi bi-person-check"></i> Estimated active</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6 col-sm-12">
                <div class="card border-0 shadow-sm border-start border-info border-4 h-100">
                    <div class="card-body p-3">
                        <small class="text-muted text-uppercase fw-bold d-block mb-1">Total Employees</small>
                        <h3 class="text-dark fw-bold mb-0"><?= number_format($total_employees) ?></h3>
                        <small class="text-muted text-nowrap">Active profiles across all districts</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6 col-sm-12">
                <div class="card border-0 shadow-sm border-start border-secondary border-4 h-100">
                    <div class="card-body p-3">
                        <small class="text-muted text-uppercase fw-bold d-block mb-1">Active Ranges</small>
                        <h3 class="text-dark fw-bold mb-0"><?= number_format($total_ranges) ?></h3>
                        <small class="text-muted text-nowrap">Regional field divisions</small>
                    </div>
                </div>
            </div>

        </div>
        <div class="row g-3 mb-4">

            <div class="col-xl-4 col-md-6 col-sm-12">
                <div class="card border-0 shadow-sm border-start border-warning border-4 h-100">
                    <div class="card-body p-3">
                        <small class="text-muted text-uppercase fw-bold d-block mb-1">Hatchability Rate</small>
                        <h3 class="text-dark fw-bold mb-0"><?= number_format($hatch_rate, 1) ?>%</h3>
                        <small class="text-muted text-nowrap">Average hatch yield metrics</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6 col-sm-12">
                <div class="card border-0 shadow-sm border-start border-danger border-4 h-100">
                    <div class="card-body p-3">
                        <small class="text-muted text-uppercase fw-bold d-block mb-1">Hatchery Sales</small>
                        <h3 class="text-dark fw-bold mb-0">LKR <?= number_format($total_revenue, 2) ?></h3>
                        <small class="text-muted text-nowrap">Total revenue recognized</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6 col-sm-12">
                <div class="card border-0 shadow-sm border-start border-success border-4 h-100">
                    <div class="card-body p-3">
                        <small class="text-muted text-uppercase fw-bold d-block mb-1">Drug Balances</small>
                        <h3 class="text-dark fw-bold mb-0"><?= number_format($total_vaccines) ?></h3>
                        <small class="text-success text-nowrap"><i class="bi bi-shield-check"></i> Live operational doses</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4 rounded-3">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap me-2 text-danger"></i>Module Control Centers</h5>
            </div>
            <div class="card-body bg-white">
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
                    <div class="col">
                        <a style="background-color: #820100;" href="pages/modules/pd/employee_managment.php" class="btn w-100 py-3 text-start shadow-sm d-flex align-items-center">
                            <i class="bi bi-people-fill fs-3 me-3 text-light"></i>
                            <div>
                                <span class="d-block fw-bold text-light">Global HR Directory</span>
                                <small style="color: white;">Personnel, roles & appointments</small>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="pages/modules/pd/animal_health_reports.php" class="btn btn-primary w-100 py-3 text-start shadow-sm d-flex align-items-center">
                            <i class="bi bi-heart-pulse-fill fs-3 me-3"></i>
                            <div>
                                <span class="d-block fw-bold text-light">Animal Health Log</span>
                                <small style="color: white;">Diseases and treatments tracking</small>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a style="background-color: #370709;" href="pages/dashboard/veterinary_office.php" class="btn w-100 py-3 text-start shadow-sm d-flex align-items-center">
                            <i class="bi bi-activity fs-3 me-3 text-light"></i>
                            <div>
                                <span class="d-block fw-bold text-light">Breeding & Range Hub</span>
                                <small style="color: white;">AI and calving performance</small>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a style="background-color: #c6aa4b;" href="pages/dashboard/farms.php" class="btn btn-warning w-100 py-3 text-start shadow-sm d-flex align-items-center">
                            <i class="bi bi-egg-fried fs-3 me-3 text-dark"></i>
                            <div>
                                <span class="d-block fw-bold text-dark">Regional Farms Hub</span>
                                <small class="text-dark">Batches, rates, and item sales data</small>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="pages/dashboard/training.php" class="btn btn-success w-100 py-3 text-start shadow-sm d-flex align-items-center">
                            <i class="bi bi-easel fs-3 me-3 text-light"></i>
                            <div>
                                <span class="d-block fw-bold text-light">Training Centers Hub</span>
                                <small class="text-light">Farmer training & programmes</small>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="pages/modules/pd/approval_diaries.php" class="btn btn-secondary w-100 py-3 text-start shadow-sm d-flex align-items-center">
                            <i class="bi bi-calendar4-event fs-3 me-3 text-light"></i>
                            <div>
                                <span class="d-block fw-bold text-light">Advanced Programs</span>
                                <small class="text-light">Review mid/final program status</small>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a style="background-color: #8d170e;" href="pages/modules/hr/leave_management.php" class="btn btn-danger w-100 py-3 text-start shadow-sm d-flex align-items-center">
                            <i class="bi bi-person-badge fs-3 me-3 text-light"></i>
                            <div>
                                <span class="d-block fw-bold text-light">Leave Management</span>
                                <small class="text-light">Approve fields and officer leaves</small>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="pages/modules/finance/assets_management.php" class="btn btn-dark w-100 py-3 text-start shadow-sm d-flex align-items-center">
                            <i class="bi bi-tools fs-3 me-3 text-light"></i>
                            <div>
                                <span class="d-block fw-bold text-light">Asset Inventory</span>
                                <small class="text-light">Track regional property</small>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="pages/modules/pd/provincial_reports.php" class="btn btn-light text-dark border-secondary w-100 py-3 text-start shadow-sm d-flex align-items-center">
                            <i class="bi bi-file-earmark-bar-graph fs-3 me-3 text-muted"></i>
                            <div>
                                <span class="d-block fw-bold text-dark">Provincial Reports</span>
                                <small class="text-muted">High-level consolidated reports</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            
            <div class="col-xl-6">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3">
                        <h5 class="m-0 fw-bold text-dark"><i class="bi bi-geo-alt me-2 text-primary"></i>Range Performance Statistics (Breeding)</h5>
                    </div>
                    <div class="card-body">
                        <table id="rangeBreedingTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Range Name</th>
                                    <th class="text-center">Year/Month</th>
                                    <th class="text-center">AI Count</th>
                                    <th class="text-center">PD Count</th>
                                    <th class="text-center">Calving Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $range_perf_query = "SELECT r.name AS range_name, a.report_year AS year, a.report_month AS month_number,
                                                            COUNT(DISTINCT a.id) AS ai_count,
                                                            (SELECT COUNT(DISTINCT pd.id) FROM breeding_pd_performance pd WHERE pd.range_id = a.range_id AND pd.report_year = a.report_year AND pd.report_month = a.report_month) AS pd_count,
                                                            (SELECT COUNT(DISTINCT c.id) FROM breeding_calving_performance c WHERE c.range_id = a.range_id AND c.report_year = a.report_year AND c.report_month = a.report_month) AS calving_count
                                                     FROM breeding_ai_performance a
                                                     LEFT JOIN veterinary_ranges r ON a.range_id = r.id
                                                     GROUP BY a.range_id, a.report_year, a.report_month
                                                     ORDER BY a.report_year DESC, a.report_month DESC LIMIT 10";
                                $range_perf_res = $mysqli->query($range_perf_query);
                                if ($range_perf_res && $range_perf_res->num_rows > 0):
                                    while ($row = $range_perf_res->fetch_assoc()):
                                ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($row['range_name'] ?? 'General Range') ?></td>
                                            <td class="text-center"><?= $row['year'] ?> / M-<?= sprintf("%02d", $row['month_number']) ?></td>
                                            <td class="text-center font-monospace text-primary fw-bold"><?= number_format($row['ai_count']) ?></td>
                                            <td class="text-center font-monospace text-warning fw-bold"><?= number_format($row['pd_count']) ?></td>
                                            <td class="text-center font-monospace text-success fw-bold"><?= number_format($row['calving_count']) ?></td>
                                        </tr>
                                <?php
                                    endwhile;
                                endif;
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3">
                        <h5 class="m-0 fw-bold text-dark"><i class="bi bi-shield-plus me-2 text-success"></i>Live Regional Vaccine Ledger</h5>
                    </div>
                    <div class="card-body">
                        <table id="directorDrugSummaryTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Vaccine/Drug Name</th>
                                    <th class="text-center">Opening Doses</th>
                                    <th class="text-center">Received</th>
                                    <th class="text-center">Used</th>
                                    <th class="text-center bg-light fw-bold">Live Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $matrix_query = "SELECT r.*, t.vaccine_name 
                                                 FROM `drug_records` r
                                                 LEFT JOIN `drug_types` t ON r.drug_type_id = t.id
                                                 ORDER BY r.id DESC LIMIT 10";
                                $matrix_res = $mysqli->query($matrix_query);
                                if ($matrix_res && $matrix_res->num_rows > 0):
                                    while ($row = $matrix_res->fetch_assoc()):
                                ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($row['vaccine_name'] ?? 'Unknown Vaccine') ?></td>
                                            <td class="text-center font-monospace"><?= number_format($row['starter_count_month']) ?></td>
                                            <td class="text-center font-monospace text-success">+<?= number_format($row['during_month_received']) ?></td>
                                            <td class="text-center font-monospace text-danger">-<?= number_format($row['used_doses_count']) ?></td>
                                            <td class="text-center font-monospace fw-bold text-success bg-light"><?= number_format($row['balance_end_month']) ?></td>
                                        </tr>
                                <?php
                                    endwhile;
                                endif;
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    </main>
</div>

<?php require_once './includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        $('#rangeBreedingTable').DataTable({
            "pageLength": 5,
            "lengthMenu": [5, 10, 25],
            "searching": true,
            "info": false,
            "order": [[1, "desc"]]
        });

        $('#directorDrugSummaryTable').DataTable({
            "pageLength": 5,
            "lengthMenu": [5, 10, 25],
            "searching": true,
            "info": false,
            "order": [[4, "asc"]]
        });
    });
</script>