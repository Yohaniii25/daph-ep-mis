<?php
// pages/modules/district/training_centers.php -> Training Centers Summary for District DD
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

// Fetch all Training Centers and assigned Training Officers
$tc_query = "SELECT tc.id AS center_id, tc.center_name, tc.location, tc.is_active,
                    u.id AS officer_id, u.full_name AS officer_name, u.username AS officer_username, u.email AS officer_email, u.phone AS officer_phone,
                    (SELECT COUNT(*) FROM training_advanced_programmes tap WHERE tap.training_center_id = tc.id) AS total_programmes,
                    (SELECT IFNULL(SUM(tir.amount), 0) FROM training_income_receipts tir WHERE tir.training_center_id = tc.id) AS total_revenue
             FROM training_centers tc
             LEFT JOIN users u ON (u.training_center_id = tc.id OR u.training_center_location LIKE tc.location) AND u.role = 'training_officer' AND u.is_active = 1
             ORDER BY tc.id ASC";

$tc_res = $mysqli->query($tc_query);
$all_centers = $tc_res ? $tc_res->fetch_all(MYSQLI_ASSOC) : [];

// Map training center locations to districts
$tc_district_map = [
    'uppuveli' => 'Trincomalee',
    'uppuweli' => 'Trincomalee',
    'kallady' => 'Batticaloa',
    'kanchirankuda' => 'Amparai'
];

$district_centers = [];
foreach ($all_centers as $center) {
    $loc = strtolower(trim($center['location'] ?? ''));
    $c_district = $tc_district_map[$loc] ?? 'Provincial';
    $center['district_name'] = $c_district;
    
    if (strcasecmp($c_district, $district_name) === 0 || (strcasecmp($c_district, 'Amparai') === 0 && strcasecmp($district_name, 'Ampara') === 0)) {
        $center['is_local'] = true;
    } else {
        $center['is_local'] = false;
    }
    $district_centers[] = $center;
}

$local_tc_count = count(array_filter($district_centers, fn($c) => !empty($c['is_local'])));
$total_progs = array_sum(array_column($all_centers, 'total_programmes'));
$total_rev = array_sum(array_column($all_centers, 'total_revenue'));
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <div>
                <h2 class="text-dark fw-bold mb-0">Training Centers Summary</h2>
                <p class="text-muted small mb-0">Animal Husbandry &amp; Farmer Training institutions overview for <?= htmlspecialchars($district_name) ?> District.</p>
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
                <div class="card border-0 shadow-sm border-start border-success border-4 p-3 h-100">
                    <small class="text-muted text-uppercase fw-bold">District Training Centers</small>
                    <h3 class="text-dark fw-bold mb-0"><?= number_format($local_tc_count) ?></h3>
                    <small class="text-success"><i class="bi bi-geo-alt-fill"></i> In <?= htmlspecialchars($district_name) ?> District</small>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm border-start border-primary border-4 p-3 h-100">
                    <small class="text-muted text-uppercase fw-bold">Total Training Programmes</small>
                    <h3 class="text-primary fw-bold mb-0"><?= number_format($total_progs) ?></h3>
                    <small class="text-muted">Farmer &amp; staff capability modules</small>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm border-start border-warning border-4 p-3 h-100">
                    <small class="text-muted text-uppercase fw-bold">Training Centers Revenue</small>
                    <h3 class="text-dark fw-bold mb-0">LKR <?= number_format($total_rev, 2) ?></h3>
                    <small class="text-warning"><i class="bi bi-currency-exchange"></i> Receipts recognized</small>
                </div>
            </div>
        </div>

        <!-- Training Centers Table -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="m-0 fw-bold text-dark"><i class="bi bi-easel me-2 text-success"></i>Training Centers &amp; Appointed Officers</h5>
                <span class="badge bg-success"><?= count($district_centers) ?> Centers</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tcTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                        <thead class="table-light">
                            <tr>
                                <th>Training Center</th>
                                <th>Location &amp; District</th>
                                <th>Assigned Training Officer</th>
                                <th>Contact Information</th>
                                <th class="text-center">Programmes</th>
                                <th class="text-center">Jurisdiction</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($district_centers)): ?>
                                <?php foreach ($district_centers as $tc): ?>
                                    <tr class="<?= !empty($tc['is_local']) ? 'table-success bg-opacity-25' : '' ?>">
                                        <td class="fw-bold text-dark">
                                            <i class="bi bi-building me-1 text-success"></i><?= htmlspecialchars($tc['center_name']) ?>
                                        </td>
                                        <td>
                                            <span class="fw-semibold"><i class="bi bi-geo-alt me-1 text-danger"></i><?= htmlspecialchars($tc['location'] ?? 'N/A') ?></span>
                                            <br><small class="text-muted"><?= htmlspecialchars($tc['district_name']) ?> District</small>
                                        </td>
                                        <td>
                                            <?php if (!empty($tc['officer_name'])): ?>
                                                <div class="fw-bold text-primary"><?= htmlspecialchars($tc['officer_name']) ?></div>
                                                <small class="text-muted font-monospace"><?= htmlspecialchars($tc['officer_username']) ?></small>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Vacant / Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($tc['officer_email'])): ?>
                                                <div><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($tc['officer_email']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($tc['officer_phone'])): ?>
                                                <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($tc['officer_phone']) ?></small>
                                            <?php endif; ?>
                                            <?php if (empty($tc['officer_email']) && empty($tc['officer_phone'])): ?>
                                                <span class="text-muted">Direct Center Contact</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center font-monospace fw-bold text-primary"><?= (int)$tc['total_programmes'] ?></td>
                                        <td class="text-center">
                                            <?php if (!empty($tc['is_local'])): ?>
                                                <span class="badge bg-primary">Local (<?= htmlspecialchars($district_name) ?>)</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark border">Regional</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= !empty($tc['is_active']) ? 'success' : 'danger' ?>">
                                                <?= !empty($tc['is_active']) ? 'Active' : 'Inactive' ?>
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
    $('#tcTable').DataTable({
        "pageLength": 10,
        "order": [[5, "asc"]]
    });
});
</script>
