<?php
// pages/modules/district/users_summary.php -> All Users Summary for District Deputy Director
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_roles = ['district_dd', 'deputy_director_district', 'administrator', 'provincial_director', 'deputy_director_hq_1', 'deputy_director_hq_2'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied");
}

require_once '../../../config/db_connect.php';
require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';

// Resolve District
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

$dist_like = "%" . $district_name . "%";

// Optional filter by role from query param
$filter_role = isset($_GET['role']) ? trim($_GET['role']) : '';

$users_query = "SELECT u.id, u.username, u.full_name, u.email, u.phone, u.role, u.designation, u.is_active, u.last_login, u.created_at,
                       vr.name AS range_name, rf.farm_name, tc.center_name
                FROM users u
                LEFT JOIN veterinary_ranges vr ON u.range_id = vr.id
                LEFT JOIN regional_farms rf ON u.farm_id = rf.id
                LEFT JOIN training_centers tc ON u.training_center_id = tc.id
                WHERE (u.district_id = ? OR u.district LIKE ? OR vr.district_id = ?)";

$params = [$district_id, $dist_like, $district_id];
$types = "isi";

if (!empty($filter_role)) {
    $users_query .= " AND u.role = ?";
    $params[] = $filter_role;
    $types .= "s";
}

$users_query .= " ORDER BY u.role ASC, u.full_name ASC";

$u_stmt = $mysqli->prepare($users_query);
$u_stmt->bind_param($types, ...$params);
$u_stmt->execute();
$users_list = $u_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$u_stmt->close();

// Role counts in district
$total_users = count($users_list);
$vs_roles_list = ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon'];
$emp_roles_list = ['employee', 'livestock_development_officer', 'development_officer', 'driver', 'dispensary_assistant', 'department_laborer', 'night_watcher'];
$vs_count = count(array_filter($users_list, fn($u) => in_array($u['role'], $vs_roles_list)));
$emp_count = count(array_filter($users_list, fn($u) => in_array($u['role'], $emp_roles_list)));
$active_users = count(array_filter($users_list, fn($u) => !empty($u['is_active'])));
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <div>
                <h2 class="text-dark fw-bold mb-0">District Users Summary Directory</h2>
                <p class="text-muted small mb-0">Consolidated staff and officer accounts assigned to <?= htmlspecialchars($district_name) ?> District.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="../../../dashboard.php" class="btn btn-secondary shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Metric Cards -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm border-start border-primary border-4 p-3 h-100">
                    <small class="text-muted text-uppercase fw-bold">Total District Users</small>
                    <h3 class="text-primary fw-bold mb-0"><?= number_format($total_users) ?></h3>
                    <small class="text-muted">Registered profiles in <?= htmlspecialchars($district_name) ?></small>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm border-start border-danger border-4 p-3 h-100">
                    <small class="text-muted text-uppercase fw-bold">Veterinary Surgeons</small>
                    <h3 class="text-danger fw-bold mb-0"><?= number_format($vs_count) ?></h3>
                    <small class="text-muted">In-Charge Range Officers (GVS/AVS)</small>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm border-start border-info border-4 p-3 h-100">
                    <small class="text-muted text-uppercase fw-bold">Field Staff &amp; Officers</small>
                    <h3 class="text-info fw-bold mb-0"><?= number_format($emp_count) ?></h3>
                    <small class="text-muted">LDOs, DOs, Drivers, DAs, Laborers, Watchers</small>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm border-start border-success border-4 p-3 h-100">
                    <small class="text-muted text-uppercase fw-bold">Active Accounts</small>
                    <h3 class="text-success fw-bold mb-0"><?= number_format($active_users) ?> / <?= number_format($total_users) ?></h3>
                    <small class="text-success"><i class="bi bi-check-circle-fill"></i> Operational access enabled</small>
                </div>
            </div>
        </div>

        <!-- Role Quick Filters -->
        <div class="mb-3 d-flex flex-wrap gap-2">
            <a href="users_summary.php" class="btn btn-sm <?= empty($filter_role) ? 'btn-dark' : 'btn-outline-dark' ?>">All Users (<?= $total_users ?>)</a>
            <a href="users_summary.php?role=veterinary_surgeon" class="btn btn-sm <?= $filter_role === 'veterinary_surgeon' ? 'btn-primary' : 'btn-outline-primary' ?>">Veterinary Surgeons</a>
            <a href="users_summary.php?role=employee" class="btn btn-sm <?= $filter_role === 'employee' ? 'btn-info' : 'btn-outline-info' ?>">Field Employees</a>
            <a href="users_summary.php?role=training_officer" class="btn btn-sm <?= $filter_role === 'training_officer' ? 'btn-success' : 'btn-outline-success' ?>">Training Officers</a>
            <a href="users_summary.php?role=farms_dd" class="btn btn-sm <?= $filter_role === 'farms_dd' ? 'btn-warning' : 'btn-outline-warning' ?>">Farms Officers</a>
        </div>

        <!-- Users Table -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="m-0 fw-bold text-dark"><i class="bi bi-person-lines-fill me-2 text-primary"></i><?= htmlspecialchars($district_name) ?> District Staff Directory</h5>
                <span class="badge bg-primary"><?= count($users_list) ?> Profiles Found</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="usersTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                        <thead class="table-light">
                            <tr>
                                <th>Name &amp; Username</th>
                                <th>Role / Category</th>
                                <th>Assigned Range / Unit</th>
                                <th>Designation</th>
                                <th>Contact Information</th>
                                <th class="text-center">Status</th>
                                <th>Last Login</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users_list)): ?>
                                <?php foreach ($users_list as $u): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($u['full_name']) ?></div>
                                            <small class="text-muted font-monospace"><?= htmlspecialchars($u['username']) ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $role_badge_class = 'secondary';
                                            if (in_array($u['role'], ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon'])) $role_badge_class = 'primary';
                                            elseif ($u['role'] === 'district_dd') $role_badge_class = 'danger';
                                            elseif ($u['role'] === 'training_officer') $role_badge_class = 'success';
                                            elseif ($u['role'] === 'farms_dd') $role_badge_class = 'warning text-dark';
                                            elseif (in_array($u['role'], ['employee', 'livestock_development_officer', 'development_officer', 'driver', 'dispensary_assistant', 'department_laborer', 'night_watcher'])) $role_badge_class = 'info text-dark';
                                            ?>
                                            <span class="badge bg-<?= $role_badge_class ?>">
                                                <?= ucwords(str_replace('_', ' ', $u['role'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($u['range_name'])): ?>
                                                <i class="bi bi-building me-1 text-primary"></i><?= htmlspecialchars($u['range_name']) ?>
                                            <?php elseif (!empty($u['farm_name'])): ?>
                                                <i class="bi bi-egg-fried me-1 text-warning"></i><?= htmlspecialchars($u['farm_name']) ?>
                                            <?php elseif (!empty($u['center_name'])): ?>
                                                <i class="bi bi-easel me-1 text-success"></i><?= htmlspecialchars($u['center_name']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">District Office</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($u['designation'] ?? 'Field Officer') ?>
                                        </td>
                                        <td>
                                            <div><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($u['email'] ?? 'N/A') ?></div>
                                            <?php if (!empty($u['phone'])): ?>
                                                <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($u['phone']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= !empty($u['is_active']) ? 'success' : 'danger' ?>">
                                                <?= !empty($u['is_active']) ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted font-monospace">
                                            <?= !empty($u['last_login']) ? date('d M Y h:i A', strtotime($u['last_login'])) : 'Never' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No users found matching the criteria in <?= htmlspecialchars($district_name) ?> District.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<?php require_once '../../../includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#usersTable').DataTable({
        "pageLength": 15,
        "order": [[0, "asc"]]
    });
});
</script>
