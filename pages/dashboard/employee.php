<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$emp_roles = ['employee', 'livestock_development_officer', 'development_officer', 'driver', 'dispensary_assistant', 'department_laborer', 'night_watcher'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $emp_roles)) {
    die("Access denied. Please login as an employee.");
}

require_once __DIR__ . '/../../config/db_connect.php';

$user_id = $_SESSION['user_id'];
$query = "
    SELECT u.full_name, u.district_id, u.range_id, 
           d.name as dist_name, vr.name as range_name 
    FROM users u
    LEFT JOIN districts d ON u.district_id = d.id
    LEFT JOIN veterinary_ranges vr ON u.range_id = vr.id
    WHERE u.id = ?
";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();

$full_name     = $user_info['full_name'] ?? $_SESSION['username'];
$district_name = $user_info['dist_name'] ?? 'Not Assigned';
$range_name    = $user_info['range_name'] ?? 'No Range Assigned';

// Update session data silently (keeps session fresh without extra queries)
$_SESSION['district_id'] = $user_info['district_id'];
$_SESSION['range_id']    = $user_info['range_id'];
$_SESSION['full_name']   = $full_name;

$stmt->close();

require_once './includes/sidebar.php'; 


$task_count = 5; 
$leave_balance = 14;
?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1">Employee Workspace</h2>
                <p class="text-muted mb-0">
                    <i class="bi bi-geo-alt-fill text-danger me-1"></i> 
                    District Office | 
                    Logged in as: <strong><?= htmlspecialchars($full_name) ?></strong>
                </p>
            </div>
            <div class="text-end d-none d-md-block">
                <div class="p-2 bg-white shadow-sm rounded border">
                    <span class="small fw-bold text-uppercase text-muted d-block" style="font-size: 0.7rem;">Current Date</span>
                    <span class="text-primary fw-bold"><i class="bi bi-calendar3 me-2"></i><?= date('l, d M Y') ?></span>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">My Daily Tasks</h6>
                        <h2 class="text-primary mb-0"><?= $task_count ?></h2>
                        <small class="text-muted">Activities recorded today</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Leave Balance</h6>
                        <h2 class="text-success mb-0"><?= $leave_balance ?> <span class="fs-6 fw-normal text-muted">Days</span></h2>
                        <small class="text-muted">Annual leave remaining</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Assigned Range</h6>
                        <h2 class="text-warning mb-0" style="font-size: 1.5rem;"><?= htmlspecialchars($range_name) ?></h2>
                        <small class="text-muted">Primary workstation</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Account Status</h6>
                        <h2 class="text-info mb-0">Active</h2>
                        <small class="text-muted">System Verified</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="mb-0 fw-bold text-muted small text-uppercase">Quick Operations</h6>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <button class="btn btn-primary w-100 py-3 border-2 fw-bold">
                                    <i class="bi bi-journal-plus fs-3 d-block mb-2"></i>Daily Diary
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-success w-100 py-3 border-2 fw-bold">
                                    <i class="bi bi-calendar-plus fs-3 d-block mb-2"></i>Request Leave
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-info w-100 py-3 border-2 fw-bold">
                                    <i class="bi bi-person-vcard fs-3 d-block mb-2"></i>My Profile
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-dark w-100 py-3 border-2 fw-bold">
                                    <i class="bi bi-megaphone fs-3 d-block mb-2"></i>Notices
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-muted small text-uppercase">Recent Diary Entries</h6>
                        <a href="#" class="btn btn-sm btn-link text-decoration-none">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr class="small">
                                        <th class="ps-4">Date</th>
                                        <th>Activity Type</th>
                                        <th>Description</th>
                                        <th class="text-end pe-4">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="ps-4 small">22 Apr 2026</td>
                                        <td><span class="badge bg-primary-soft text-primary">Field Visit</span></td>
                                        <td class="small text-truncate" style="max-width: 200px;">Livestock inspection at Ampara Range...</td>
                                        <td class="text-end pe-4"><span class="text-success small"><i class="bi bi-check-circle"></i> Saved</span></td>
                                    </tr>
                                    <tr>
                                        <td class="ps-4 small">21 Apr 2026</td>
                                        <td><span class="badge bg-info-soft text-info">Training</span></td>
                                        <td class="small text-truncate" style="max-width: 200px;">Attended farmer awareness session...</td>
                                        <td class="text-end pe-4"><span class="text-success small"><i class="bi bi-check-circle"></i> Saved</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold text-muted small text-uppercase">Announcements</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 border-bottom pb-3">
                            <span class="badge bg-danger mb-2">Important</span>
                            <p class="small mb-1 fw-bold">Monthly Progress Report Due</p>
                            <p class="text-muted x-small mb-0">Please submit your April reports by Friday.</p>
                        </div>
                        <div class="mb-0">
                            <span class="badge bg-warning text-dark mb-2">General</span>
                            <p class="small mb-1 fw-bold">New Vaccination Drive</p>
                            <p class="text-muted x-small mb-0">Starting next week in Trincomalee district.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
    .bg-primary-soft { background-color: #e7f1ff; }
    .bg-info-soft { background-color: #e1f5fe; }
    .x-small { font-size: 0.75rem; }
</style>