<?php
// pages/modules/district/regional_farms.php -> Regional Farms Summary
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

// Fetch Regional Farms list and assigned officers
$farms_query = "SELECT rf.id AS farm_id, rf.farm_name, rf.location, rf.is_active,
                       u.id AS officer_id, u.full_name AS officer_name, u.username AS officer_username, u.email AS officer_email, u.phone AS officer_phone,
                       (SELECT COUNT(*) FROM users fu WHERE fu.farm_id = rf.id AND fu.is_active = 1) AS total_farm_staff,
                       (SELECT COUNT(*) FROM hatchery_batches hb WHERE hb.user_id = u.id) AS total_batches,
                       (SELECT IFNULL(SUM(dep.total_eggs), 0) FROM daily_egg_production dep) AS total_eggs
                FROM regional_farms rf
                LEFT JOIN users u ON u.farm_id = rf.id AND u.role = 'farms_dd' AND u.is_active = 1
                ORDER BY rf.id ASC";

$farms_res = $mysqli->query($farms_query);
$all_farms = $farms_res ? $farms_res->fetch_all(MYSQLI_ASSOC) : [];

// Map farm locations to districts
$location_district_map = [
    'uppuveli' => 'Trincomalee',
    'kantalai' => 'Trincomalee',
    'morawewa' => 'Trincomalee',
    'mandoor' => 'Batticaloa',
    'sathurukonda' => 'Batticaloa',
    'thumpankerny' => 'Batticaloa',
    'thirukkovil' => 'Amparai'
];

$district_farms = [];
foreach ($all_farms as $farm) {
    $loc = strtolower(trim($farm['location'] ?? ''));
    $farm_district = $location_district_map[$loc] ?? 'Provincial';
    $farm['district_name'] = $farm_district;
    
    // Check if matches district or DD wants to view full farm overview
    if (strcasecmp($farm_district, $district_name) === 0 || strcasecmp($farm_district, 'Amparai') === 0 && strcasecmp($district_name, 'Ampara') === 0) {
        $farm['is_local'] = true;
    } else {
        $farm['is_local'] = false;
    }
    $district_farms[] = $farm;
}

$local_farms_count = count(array_filter($district_farms, fn($f) => !empty($f['is_local'])));
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <div>
                <h2 class="text-dark fw-bold mb-0">Regional Farms Operations Summary</h2>
                <p class="text-muted small mb-0">Livestock breeding stations, stud centers, and farm production facilities for <?= htmlspecialchars($district_name) ?> District.</p>
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
                <div class="card border-0 shadow-sm border-start border-warning border-4 p-3 h-100">
                    <small class="text-muted text-uppercase fw-bold">District Farm Stations</small>
                    <h3 class="text-dark fw-bold mb-0"><?= number_format($local_farms_count) ?></h3>
                    <small class="text-warning"><i class="bi bi-geo-alt-fill"></i> Located in <?= htmlspecialchars($district_name) ?></small>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm border-start border-primary border-4 p-3 h-100">
                    <small class="text-muted text-uppercase fw-bold">Total Regional Farms</small>
                    <h3 class="text-primary fw-bold mb-0"><?= count($all_farms) ?></h3>
                    <small class="text-muted">Intergrade, Goat, Buffalo &amp; Stud Centers</small>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm border-start border-success border-4 p-3 h-100">
                    <small class="text-muted text-uppercase fw-bold">Active Farm Officers</small>
                    <h3 class="text-success fw-bold mb-0"><?= count(array_filter($all_farms, fn($f) => !empty($f['officer_name']))) ?></h3>
                    <small class="text-success"><i class="bi bi-person-check-fill"></i> Assigned Operations DDs</small>
                </div>
            </div>
        </div>

        <!-- Farms Table -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="m-0 fw-bold text-dark"><i class="bi bi-egg-fried me-2 text-warning"></i>Regional Farm Stations &amp; Assigned Officers</h5>
                <span class="badge bg-dark"><?= count($district_farms) ?> Farms Listed</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="farmsTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                        <thead class="table-light">
                            <tr>
                                <th>Farm Name</th>
                                <th>Location &amp; District</th>
                                <th>Assigned Farms Officer</th>
                                <th>Contact Information</th>
                                <th class="text-center">Staff Count</th>
                                <th class="text-center">Jurisdiction</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($district_farms)): ?>
                                <?php foreach ($district_farms as $f): ?>
                                    <tr class="<?= !empty($f['is_local']) ? 'table-warning bg-opacity-25' : '' ?>">
                                        <td class="fw-bold text-dark">
                                            <i class="bi bi-building me-1 text-secondary"></i><?= htmlspecialchars($f['farm_name']) ?>
                                        </td>
                                        <td>
                                            <span class="fw-semibold"><i class="bi bi-geo-alt me-1 text-danger"></i><?= htmlspecialchars($f['location'] ?? 'N/A') ?></span>
                                            <br><small class="text-muted"><?= htmlspecialchars($f['district_name']) ?> District</small>
                                        </td>
                                        <td>
                                            <?php if (!empty($f['officer_name'])): ?>
                                                <div class="fw-bold text-primary"><?= htmlspecialchars($f['officer_name']) ?></div>
                                                <small class="text-muted font-monospace"><?= htmlspecialchars($f['officer_username']) ?></small>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Central / Operations Pool</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($f['officer_email'])): ?>
                                                <div><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($f['officer_email']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($f['officer_phone'])): ?>
                                                <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($f['officer_phone']) ?></small>
                                            <?php endif; ?>
                                            <?php if (empty($f['officer_email']) && empty($f['officer_phone'])): ?>
                                                <span class="text-muted">Direct Department Management</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center font-monospace fw-bold"><?= (int)$f['total_farm_staff'] ?></td>
                                        <td class="text-center">
                                            <?php if (!empty($f['is_local'])): ?>
                                                <span class="badge bg-primary">Local (<?= htmlspecialchars($district_name) ?>)</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark border">Regional</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= !empty($f['is_active']) ? 'success' : 'danger' ?>">
                                                <?= !empty($f['is_active']) ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
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
    $('#farmsTable').DataTable({
        "pageLength": 10,
        "order": [[5, "asc"]]
    });
});
</script>
