<?php
// pages/dashboard/district.php -> District Deputy Director Dashboard
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_roles = ['district_dd', 'deputy_director_district', 'administrator', 'provincial_director', 'deputy_director_hq_1', 'deputy_director_hq_2'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied");
}

require_once './config/db_connect.php';
require_once './includes/header.php';
require_once './includes/sidebar.php';

// Resolve District context
$district_id = $_SESSION['district_id'] ?? null;
$district_session_name = $_SESSION['district'] ?? '';

// Fallback inference if district_id is not set
if (empty($district_id) && !empty($district_session_name)) {
    if (strcasecmp($district_session_name, 'Amparai') === 0 || strcasecmp($district_session_name, 'Ampara') === 0) {
        $district_id = 1;
    } elseif (strcasecmp($district_session_name, 'Batticaloa') === 0) {
        $district_id = 2;
    } elseif (strcasecmp($district_session_name, 'Trincomalee') === 0) {
        $district_id = 3;
    }
}

// Default fallback for testing or HQ preview
if (empty($district_id)) {
    $district_id = 1;
}

// Fetch Official District Name
$district_name = 'District';
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

// ==========================================
// 1. DYNAMIC METRICS FILTERED BY DISTRICT_ID
// ==========================================

// Metric 1: Active Field Staff in this District
$staff_stmt = $mysqli->prepare("SELECT COUNT(id) AS total_staff FROM users WHERE is_active = 1 AND (district_id = ? OR district LIKE ?)");
$dist_like = "%" . $district_name . "%";
$staff_stmt->bind_param("is", $district_id, $dist_like);
$staff_stmt->execute();
$staff_count = (int)($staff_stmt->get_result()->fetch_assoc()['total_staff'] ?? 0);
$staff_stmt->close();

// Metric 2: Active Veterinary Ranges in this District
$range_stmt = $mysqli->prepare("SELECT COUNT(id) AS total_ranges FROM veterinary_ranges WHERE is_active = 1 AND district_id = ?");
$range_stmt->bind_param("i", $district_id);
$range_stmt->execute();
$range_count = (int)($range_stmt->get_result()->fetch_assoc()['total_ranges'] ?? 0);
$range_stmt->close();

// Metric 3: Pending Diary & Advance Programme Approvals in this District
$pending_diaries = 0;
$diary_stmt = $mysqli->prepare("SELECT COUNT(t.id) AS pending_cnt 
                                FROM diary_tasks t 
                                JOIN users u ON t.user_id = u.id 
                                WHERE (u.district_id = ? OR u.district LIKE ?) AND t.status = 'Pending'");
if ($diary_stmt) {
    $diary_stmt->bind_param("is", $district_id, $dist_like);
    $diary_stmt->execute();
    $pending_diaries = (int)($diary_stmt->get_result()->fetch_assoc()['pending_cnt'] ?? 0);
    $diary_stmt->close();
}

// Metric 4: Monthly Revenue across Range Offices in this District
$district_revenue = 0.00;
$rev_stmt = $mysqli->prepare("SELECT IFNULL(SUM(closing_balance), 0) AS total_rev 
                              FROM cash_book_summaries 
                              WHERE district_id = ?");
if ($rev_stmt) {
    $rev_stmt->bind_param("i", $district_id);
    $rev_stmt->execute();
    $district_revenue = (float)($rev_stmt->get_result()->fetch_assoc()['total_rev'] ?? 0.00);
    $rev_stmt->close();
}

// Metric 5: Total Animal Health Records / Treatments in District
$cases_stmt = $mysqli->prepare("SELECT COUNT(a.id) AS total_cases 
                                FROM animal_health_records a 
                                JOIN veterinary_ranges vr ON a.range_id = vr.id 
                                WHERE vr.district_id = ?");
$district_cases = 0;
if ($cases_stmt) {
    $cases_stmt->bind_param("i", $district_id);
    $cases_stmt->execute();
    $district_cases = (int)($cases_stmt->get_result()->fetch_assoc()['total_cases'] ?? 0);
    $cases_stmt->close();
}

// Metric 6: Artificial Inseminations in District
$ai_stmt = $mysqli->prepare("SELECT COUNT(ai.id) AS total_ai 
                             FROM breeding_ai_performance ai 
                             JOIN veterinary_ranges vr ON ai.range_id = vr.id 
                             WHERE vr.district_id = ?");
$district_ai = 0;
if ($ai_stmt) {
    $ai_stmt->bind_param("i", $district_id);
    $ai_stmt->execute();
    $district_ai = (int)($ai_stmt->get_result()->fetch_assoc()['total_ai'] ?? 0);
    $ai_stmt->close();
}

// ==========================================
// 2. RANGE OFFICES LIST IN THIS DISTRICT
// ==========================================
$ranges_list = [];
$ranges_sql = "SELECT vr.id, vr.name AS range_name, vr.location, 
                      u.full_name AS vs_name, u.email AS vs_email, u.phone AS vs_phone,
                      (SELECT COUNT(*) FROM users sub WHERE sub.range_id = vr.id AND sub.is_active = 1) AS staff_count
               FROM veterinary_ranges vr
               LEFT JOIN users u ON u.range_id = vr.id AND u.role = 'veterinary_surgeon' AND u.is_active = 1
               WHERE vr.district_id = ? AND vr.is_active = 1
               ORDER BY vr.name ASC";
$ranges_res_stmt = $mysqli->prepare($ranges_sql);
if ($ranges_res_stmt) {
    $ranges_res_stmt->bind_param("i", $district_id);
    $ranges_res_stmt->execute();
    $ranges_res = $ranges_res_stmt->get_result();
    while ($r = $ranges_res->fetch_assoc()) {
        $ranges_list[] = $r;
    }
    $ranges_res_stmt->close();
}

// ==========================================
// 3. RECENT ANIMAL HEALTH LOGS IN DISTRICT
// ==========================================
$recent_health_logs = [];
$logs_stmt = $mysqli->prepare("SELECT a.id, a.date, a.species, a.disease_condition, a.no_of_animals_treated, a.report_status, vr.name AS range_name 
                               FROM animal_health_records a 
                               JOIN veterinary_ranges vr ON a.range_id = vr.id 
                               WHERE vr.district_id = ? 
                               ORDER BY a.date DESC, a.id DESC 
                               LIMIT 5");
if ($logs_stmt) {
    $logs_stmt->bind_param("i", $district_id);
    $logs_stmt->execute();
    $logs_res = $logs_stmt->get_result();
    while ($log = $logs_res->fetch_assoc()) {
        $recent_health_logs[] = $log;
    }
    $logs_stmt->close();
}
?>

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">
        
        <!-- Header Banner -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <div>
                <h2 class="text-dark fw-bold mb-0"><?= htmlspecialchars($district_name) ?> District Deputy Director Dashboard</h2>
                <p class="text-muted small mb-0">Consolidated operational summary and management oversight for <?= htmlspecialchars($district_name) ?> District.</p>
            </div>
            <div class="d-flex gap-2">
                <span class="badge bg-primary p-2 font-monospace shadow-sm"><?= htmlspecialchars($district_name) ?> District</span>
                <span class="badge bg-dark p-2 font-monospace shadow-sm">District Scope (ID: <?= (int)$district_id ?>)</span>
            </div>
        </div>

        <!-- 4 Key Metric Cards -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6 col-sm-12">
                <div class="card border-0 shadow-sm border-start border-primary border-4 h-100 p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted text-uppercase fw-bold d-block mb-1">Active Field Staff</small>
                            <h3 class="text-dark fw-bold mb-0"><?= number_format($staff_count) ?></h3>
                            <small class="text-success"><i class="bi bi-people-fill"></i> Assigned to <?= htmlspecialchars($district_name) ?></small>
                        </div>
                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle">
                            <i class="bi bi-person-badge fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 col-sm-12">
                <div class="card border-0 shadow-sm border-start border-secondary border-4 h-100 p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted text-uppercase fw-bold d-block mb-1">Veterinary Ranges</small>
                            <h3 class="text-dark fw-bold mb-0"><?= number_format($range_count) ?></h3>
                            <small class="text-muted"><i class="bi bi-geo-alt-fill"></i> Range Offices Active</small>
                        </div>
                        <div class="bg-secondary bg-opacity-10 text-secondary p-3 rounded-circle">
                            <i class="bi bi-building fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 col-sm-12">
                <div class="card border-0 shadow-sm border-start border-warning border-4 h-100 p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted text-uppercase fw-bold d-block mb-1">Pending Approvals</small>
                            <h3 class="text-dark fw-bold mb-0"><?= number_format($pending_diaries) ?></h3>
                            <small class="text-warning"><i class="bi bi-clock-history"></i> Diaries & Programmes</small>
                        </div>
                        <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle">
                            <i class="bi bi-journal-check fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 col-sm-12">
                <div class="card border-0 shadow-sm border-start border-success border-4 h-100 p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted text-uppercase fw-bold d-block mb-1">District Revenue (Rs)</small>
                            <h3 class="text-dark fw-bold mb-0">LKR <?= number_format($district_revenue, 2) ?></h3>
                            <small class="text-success"><i class="bi bi-cash-stack"></i> Cash book balances</small>
                        </div>
                        <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle">
                            <i class="bi bi-currency-exchange fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Secondary District KPI Row -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm p-3 border-start border-info border-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted text-uppercase fw-bold d-block mb-1">Total Animals Treated</small>
                            <h3 class="text-info fw-bold mb-0"><?= number_format($district_cases) ?> Cases</h3>
                            <small class="text-muted">Recorded by range clinical units</small>
                        </div>
                        <i class="bi bi-heart-pulse text-info fs-1"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm p-3 border-start border-danger border-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted text-uppercase fw-bold d-block mb-1">Artificial Insemination (AI)</small>
                            <h3 class="text-danger fw-bold mb-0"><?= number_format($district_ai) ?> Inseminations</h3>
                            <small class="text-muted">Breeding performance records</small>
                        </div>
                        <i class="bi bi-activity text-danger fs-1"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Module Navigation Hub -->
        <div class="card shadow-sm border-0 mb-4 rounded-3">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap me-2 text-danger"></i><?= htmlspecialchars($district_name) ?> District Control &amp; User Summaries</h5>
            </div>
            <div class="card-body bg-white">
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
                    <div class="col">
                        <a href="pages/modules/district/task_assignments.php" class="btn w-100 py-3 text-start shadow-sm d-flex align-items-center text-light" style="background: linear-gradient(135deg, #370709 0%, #680d11 100%);">
                            <i class="bi bi-person-check-fill fs-3 me-3 text-warning"></i>
                            <div>
                                <span style="color: white !important;" class="d-block fw-bold text-light">Task Delegation</span>
                                <small style="color: white;">Delegate Quick Actions &amp; tasks</small>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="pages/modules/district/range_veterinary_officers.php" class="btn btn-primary w-100 py-3 text-start shadow-sm d-flex align-items-center">
                            <i class="bi bi-people-fill fs-3 me-3 text-light"></i>
                            <div>
                                <span style="color: white !important;" class="d-block fw-bold text-light">Range Veterinary Officers</span>
                                <small style="color: white;">Surgeons &amp; range staff summary</small>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a style="background-color: #c6aa4b;" href="pages/modules/district/regional_farms.php" class="btn btn-warning w-100 py-3 text-start shadow-sm d-flex align-items-center">
                            <i class="bi bi-house-fill fs-3 me-3 text-dark"></i>
                            <div>
                                <span class="d-block fw-bold text-dark">Regional Farms</span>
                                <small class="text-dark">Stations &amp; farm operations</small>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="pages/modules/district/office_details.php" class="btn btn-secondary w-100 py-3 text-start shadow-sm d-flex align-items-center">
                            <i style="color: white !important;" class="bi bi-building fs-3 me-3 text-light"></i>
                            <div>
                                <span style="color: white !important;" class="d-block fw-bold text-light">Office Details</span>
                                <small style="color: white !important;">District &amp; VS range offices</small>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="pages/modules/district/training_centers.php" class="btn btn-success w-100 py-3 text-start shadow-sm d-flex align-items-center">
                            <i style="color: white !important;" class="bi bi-easel fs-3 me-3 text-light"></i>
                            <div>
                                <span style="color: white !important;" class="d-block fw-bold text-light">Training Centers</span>
                                <small style="color: white !important;">Centers &amp; training programmes</small>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a style="background-color: #8d170e;" href="pages/modules/district/subject_matter_specialists.php" class="btn btn-danger w-100 py-3 text-start shadow-sm d-flex align-items-center">
                            <i style="color: white !important;" class="bi bi-shield-shaded fs-3 me-3 text-light"></i>
                            <div>
                                <span style="color: white !important;" class="d-block fw-bold text-light">Subject Matter Specialists</span>
                                <small style="color: white !important;">SMS officers &amp; disease control</small>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="pages/modules/district/users_summary.php" class="btn btn-dark w-100 py-3 text-start shadow-sm d-flex align-items-center">
                            <i style="color: white !important;" class="bi bi-person-lines-fill fs-3 me-3"></i>
                            <div>
                                <span style="color: white !important;" class="d-block fw-bold text-light">All Users Summary</span>
                                <small style="color: white !important;">Directory of all district staff</small>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="pages/modules/district/diary_management.php" class="btn btn-outline-primary w-100 py-3 text-start shadow-sm d-flex align-items-center">
                            <i class="bi bi-journal-text fs-3 me-3 text-primary"></i>
                            <div>
                                <span class="d-block fw-bold text-dark">Diary Management</span>
                                <small class="text-muted">Review &amp; daily entries</small>
                            </div>
                        </a>
                    </div>

                </div>
            </div>
        </div>



    </main>
</div>

<?php require_once './includes/footer.php'; ?>