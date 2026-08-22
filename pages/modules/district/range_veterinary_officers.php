<?php
// pages/modules/district/range_veterinary_officers.php -> Range Veterinary Officers Summary
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

// Fetch all Range Veterinary Surgeons in this district
$vs_query = "SELECT u.id AS user_id, u.username, u.full_name, u.email, u.phone, u.designation, u.is_active, u.last_login,
                    vr.id AS range_id, vr.name AS range_name,
                    (SELECT COUNT(*) FROM users staff WHERE staff.range_id = vr.id AND staff.is_active = 1) AS total_staff,
                    (SELECT COUNT(*) FROM animal_health_records ahr WHERE ahr.range_id = vr.id) AS total_treatments,
                    (SELECT COUNT(*) FROM breeding_ai_performance bai WHERE bai.range_id = vr.id) AS total_ai
             FROM users u
             LEFT JOIN veterinary_ranges vr ON u.range_id = vr.id
             WHERE u.role = 'veterinary_surgeon' AND (u.district_id = ? OR vr.district_id = ?)
             ORDER BY vr.name ASC, u.full_name ASC";

$vs_list = [];
if ($vs_stmt = $mysqli->prepare($vs_query)) {
    $vs_stmt->bind_param("ii", $district_id, $district_id);
    $vs_stmt->execute();
    $vs_list = $vs_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $vs_stmt->close();
}

// Metric Totals
$total_vs = count($vs_list);
$active_vs = count(array_filter($vs_list, fn($v) => !empty($v['is_active'])));
$total_range_staff = array_sum(array_column($vs_list, 'total_staff'));
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <div>
                <h2 class="text-dark fw-bold mb-0">Range Veterinary Officers Summary</h2>
                <p class="text-muted small mb-0">Registered Veterinary Surgeons and field unit personnel in <?= htmlspecialchars($district_name) ?> District.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="../../../dashboard.php" class="btn btn-secondary shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Metric Cards -->
        <div class="row g-3 mb-4">
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm border-start border-primary border-4 p-3 h-100">
                    <small class="text-muted text-uppercase fw-bold">Total Veterinary Surgeons</small>
                    <h3 class="text-primary fw-bold mb-0"><?= number_format($total_vs) ?></h3>
                    <small class="text-muted">Assigned to <?= htmlspecialchars($district_name) ?> Range Offices</small>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm border-start border-success border-4 p-3 h-100">
                    <small class="text-muted text-uppercase fw-bold">Active VS Status</small>
                    <h3 class="text-success fw-bold mb-0"><?= number_format($active_vs) ?> / <?= number_format($total_vs) ?></h3>
                    <small class="text-success"><i class="bi bi-check-circle-fill"></i> Operational accounts</small>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm border-start border-info border-4 p-3 h-100">
                    <small class="text-muted text-uppercase fw-bold">Subordinate Field Staff</small>
                    <h3 class="text-info fw-bold mb-0"><?= number_format($total_range_staff) ?></h3>
                    <small class="text-muted">LDOs, CDOs, PDOs & Support Officers</small>
                </div>
            </div>
        </div>

        <!-- VS Table -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="m-0 fw-bold text-dark"><i class="bi bi-people-fill me-2 text-danger"></i><?= htmlspecialchars($district_name) ?> Range Veterinary Surgeons</h5>
                <span class="badge bg-primary"><?= count($vs_list) ?> Officers</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="vsTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                        <thead class="table-light">
                            <tr>
                                <th>Veterinary Surgeon</th>
                                <th>Assigned Range</th>
                                <th>Contact Information</th>
                                <th class="text-center">Range Staff</th>
                                <th class="text-center">Treatments</th>
                                <th class="text-center">AI Done</th>
                                <th class="text-center">Status</th>
                                <th>Last Active</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($vs_list)): ?>
                                <?php foreach ($vs_list as $vs): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($vs['full_name']) ?></div>
                                            <small class="text-muted font-monospace"><?= htmlspecialchars($vs['username']) ?></small>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-primary"><i class="bi bi-building me-1"></i><?= htmlspecialchars($vs['range_name'] ?? 'Unassigned Range') ?></span>
                                        </td>
                                        <td>
                                            <div><i class="bi bi-envelope me-1 text-secondary"></i><?= htmlspecialchars($vs['email'] ?? 'N/A') ?></div>
                                            <?php if (!empty($vs['phone'])): ?>
                                                <small class="text-muted"><i class="bi bi-telephone me-1 text-secondary"></i><?= htmlspecialchars($vs['phone']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center font-monospace fw-bold"><?= (int)$vs['total_staff'] ?></td>
                                        <td class="text-center font-monospace text-primary fw-bold"><?= (int)$vs['total_treatments'] ?></td>
                                        <td class="text-center font-monospace text-success fw-bold"><?= (int)$vs['total_ai'] ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= !empty($vs['is_active']) ? 'success' : 'danger' ?>">
                                                <?= !empty($vs['is_active']) ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted font-monospace">
                                            <?= !empty($vs['last_login']) ? date('d M Y h:i A', strtotime($vs['last_login'])) : 'Never' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No Veterinary Surgeons registered in <?= htmlspecialchars($district_name) ?> District.</td>
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
    $('#vsTable').DataTable({
        "pageLength": 10,
        "order": [[0, "asc"]]
    });
});
</script>
