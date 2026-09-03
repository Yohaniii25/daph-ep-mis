<?php
// pages/modules/district/office_details.php -> District Office Details & Range Infrastructure
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

// Fetch District Deputy Director details
$dd_query = "SELECT u.id, u.full_name, u.username, u.email, u.phone, u.designation, u.registered_date, u.appointment_date, u.last_login 
             FROM users u 
             WHERE u.role = 'district_dd' AND (u.district_id = ? OR u.district LIKE ?) 
             LIMIT 1";
$dist_like = "%" . $district_name . "%";
$dd_info = null;
if ($dd_stmt = $mysqli->prepare($dd_query)) {
    $dd_stmt->bind_param("is", $district_id, $dist_like);
    $dd_stmt->execute();
    $dd_info = $dd_stmt->get_result()->fetch_assoc();
    $dd_stmt->close();
}

// Fetch Range Offices in District
$range_query = "SELECT vr.id, vr.name AS range_name, vr.code, vr.is_active,
                       u.id AS vs_id, u.full_name AS vs_name, u.email AS vs_email, u.phone AS vs_phone,
                       (SELECT COUNT(*) FROM users staff WHERE staff.range_id = vr.id AND staff.is_active = 1) AS staff_count,
                       (SELECT COUNT(*) FROM land_assets la WHERE la.range_id = vr.id) AS land_count,
                       (SELECT COUNT(*) FROM building_inventories bi JOIN land_assets la ON bi.land_asset_id = la.id WHERE la.range_id = vr.id) AS building_count,
                       (SELECT COUNT(*) FROM registered_vehicles rv WHERE rv.range_id = vr.id) AS vehicle_count
                FROM veterinary_ranges vr
                LEFT JOIN users u ON u.range_id = vr.id AND u.role IN ('veterinary_surgeon', 'government_veterinary_surgeon') AND u.is_active = 1
                WHERE vr.district_id = ?
                ORDER BY vr.name ASC";

$range_offices = [];
if ($r_stmt = $mysqli->prepare($range_query)) {
    $r_stmt->bind_param("i", $district_id);
    $r_stmt->execute();
    $range_offices = $r_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $r_stmt->close();
}

$total_offices = count($range_offices);
$total_staff = array_sum(array_column($range_offices, 'staff_count'));
$total_vehicles = array_sum(array_column($range_offices, 'vehicle_count'));
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <div>
                <h2 class="text-dark fw-bold mb-0"><?= htmlspecialchars($district_name) ?> District Office &amp; Range Infrastructure</h2>
                <p class="text-muted small mb-0">Administrative division structure, district leadership, and subordinate VS office network.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="../../../dashboard.php" class="btn btn-secondary shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- District Leadership Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 border-start border-primary border-4">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-2 text-center text-md-start mb-3 mb-md-0">
                        <div class="bg-primary text-white p-3 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 72px; height: 72px;">
                            <i class="bi bi-person-badge fs-1"></i>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <span class="badge bg-primary mb-2">District Leadership</span>
                        <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($dd_info['full_name'] ?? 'District Deputy Director (' . $district_name . ')') ?></h4>
                        <p class="text-muted small mb-2">
                            <i class="bi bi-geo-alt-fill text-danger me-1"></i> District Deputy Director Office, <?= htmlspecialchars($district_name) ?>
                        </p>
                        <div class="d-flex flex-wrap gap-3 small text-muted">
                            <span><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($dd_info['email'] ?? 'dd.district@gmail.com') ?></span>
                            <span><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($dd_info['phone'] ?? '+94 (0) 63 222 2222') ?></span>
                        </div>
                    </div>
                    <div class="col-md-4 mt-3 mt-md-0 border-start ps-md-4">
                        <div class="small text-muted mb-1">Administrative Jurisdiction</div>
                        <div class="fw-bold fs-5 text-dark"><?= htmlspecialchars($district_name) ?> District</div>
                        <div class="small text-muted mt-2">Active Field Personnel: <strong><?= number_format($total_staff) ?> Officers</strong></div>
                        <div class="small text-muted">Range Network: <strong><?= number_format($total_offices) ?> Operational Offices</strong></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm border-start border-secondary border-4 p-3 h-100">
                    <small class="text-muted text-uppercase fw-bold">Total Veterinary Offices</small>
                    <h3 class="text-secondary fw-bold mb-0"><?= number_format($total_offices) ?></h3>
                    <small class="text-muted">Divisional field stations</small>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm border-start border-success border-4 p-3 h-100">
                    <small class="text-muted text-uppercase fw-bold">Total Staff Deployed</small>
                    <h3 class="text-success fw-bold mb-0"><?= number_format($total_staff) ?></h3>
                    <small class="text-success"><i class="bi bi-check2-all"></i> Active range staff</small>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm border-start border-info border-4 p-3 h-100">
                    <small class="text-muted text-uppercase fw-bold">Department Vehicles</small>
                    <h3 class="text-info fw-bold mb-0"><?= number_format($total_vehicles) ?></h3>
                    <small class="text-muted">Assigned fleet inventory</small>
                </div>
            </div>
        </div>

        <!-- Range Offices Table -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="m-0 fw-bold text-dark"><i class="bi bi-building me-2 text-danger"></i>Veterinary Range Offices Directory</h5>
                <span class="badge bg-primary"><?= count($range_offices) ?> Offices</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="officesTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                        <thead class="table-light">
                            <tr>
                                <th>Range Office</th>
                                <th>Assigned Veterinary Surgeon</th>
                                <th>Contact Information</th>
                                <th class="text-center">Staff Count</th>
                                <th class="text-center">Buildings</th>
                                <th class="text-center">Vehicles</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($range_offices)): ?>
                                <?php foreach ($range_offices as $ro): ?>
                                    <tr>
                                        <td class="fw-bold text-dark">
                                            <i class="bi bi-geo-alt me-1 text-primary"></i><?= htmlspecialchars($ro['range_name']) ?>
                                            <?php if (!empty($ro['code'])): ?>
                                                <small class="text-muted font-monospace d-block">Code: <?= htmlspecialchars($ro['code']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($ro['vs_name'])): ?>
                                                <div class="fw-bold text-primary"><?= htmlspecialchars($ro['vs_name']) ?></div>
                                                <small class="text-muted">In-Charge VS</small>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Vacant / Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($ro['vs_email'])): ?>
                                                <div><i class="bi bi-envelope me-1 text-secondary"></i><?= htmlspecialchars($ro['vs_email']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($ro['vs_phone'])): ?>
                                                <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($ro['vs_phone']) ?></small>
                                            <?php endif; ?>
                                            <?php if (empty($ro['vs_email']) && empty($ro['vs_phone'])): ?>
                                                <span class="text-muted">General Range Contact</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center font-monospace fw-bold"><?= (int)$ro['staff_count'] ?></td>
                                        <td class="text-center font-monospace"><?= (int)$ro['building_count'] ?></td>
                                        <td class="text-center font-monospace"><?= (int)$ro['vehicle_count'] ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= !empty($ro['is_active']) ? 'success' : 'danger' ?>">
                                                <?= !empty($ro['is_active']) ? 'Operational' : 'Closed' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No Veterinary Range Offices registered in <?= htmlspecialchars($district_name) ?> District.</td>
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
    $('#officesTable').DataTable({
        "pageLength": 10,
        "order": [[0, "asc"]]
    });
});
</script>
