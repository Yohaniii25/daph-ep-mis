<?php
// pages/modules/training/office_details.php -> Training Center Office Inventory & HR Registry
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

$allowed_roles = ['training_officer', 'administrator', 'provincial_director', 'district_dd'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;
$current_center_id = $_SESSION['training_center_id'] ?? null;
if (empty($current_center_id) && isset($_GET['center_id'])) {
    $current_center_id = intval($_GET['center_id']);
}
if (empty($current_center_id)) {
    $c_res = $mysqli->query("SELECT id FROM training_centers WHERE is_active = 1 LIMIT 1");
    if ($c_res && $row = $c_res->fetch_assoc()) {
        $current_center_id = $row['id'];
    } else {
        $current_center_id = 1;
    }
}

// Fetch user & training centre profile details
$user_full_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Training Officer';
$user_designation = 'Training Officer - Vocational Training';
$center_name = 'Training Centre';
$center_location = 'N/A';
$off_phone = 'N/A';
$off_email = 'N/A';

$tc_stmt = $mysqli->prepare("SELECT center_name, location FROM training_centers WHERE id = ?");
if ($tc_stmt) {
    $tc_stmt->bind_param("i", $current_center_id);
    $tc_stmt->execute();
    $res = $tc_stmt->get_result();
    if ($r = $res->fetch_assoc()) {
        $center_name = $r['center_name'];
        $center_location = $r['location'];
    }
    $tc_stmt->close();
}

$user_query = $mysqli->prepare("SELECT full_name, phone, email, designation FROM users WHERE id = ?");
if ($user_query) {
    $user_query->bind_param("i", $user_id);
    $user_query->execute();
    $res = $user_query->get_result();
    if ($row = $res->fetch_assoc()) {
        if (!empty($row['full_name'])) $user_full_name = $row['full_name'];
        if (!empty($row['phone'])) $off_phone = $row['phone'];
        if (!empty($row['email'])) $off_email = $row['email'];
        if (!empty($row['designation'])) $user_designation = $row['designation'];
    }
    $user_query->close();
}

// Ensure training_center_id column exists
$asset_tables = [
    'land_assets', 'building_inventories', 'registered_vehicles', 
    'vehicle_repairs', 'furniture_assets', 'machinery_assets', 
    'instrument_assets', 'counterfoil_assets'
];
foreach ($asset_tables as $tbl) {
    $chk = $mysqli->query("SHOW COLUMNS FROM `$tbl` LIKE 'training_center_id'");
    if ($chk && $chk->num_rows === 0) {
        $mysqli->query("ALTER TABLE `$tbl` ADD COLUMN `training_center_id` INT(11) NULL AFTER `user_id`");
    }
}

// Asset Counters with strict data isolation
function getAssetCount($mysqli, $table, $center_id, $user_id) {
    $where = "is_active = 1";
    if (in_array($table, ['registered_vehicles', 'vehicle_repairs', 'furniture_assets', 'machinery_assets', 'instrument_assets', 'counterfoil_assets'])) {
        $where = "1=1";
    }
    if ($table === 'users') {
        $stmt = $mysqli->prepare("SELECT COUNT(*) AS cnt FROM `users` WHERE is_active = 1 AND training_center_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $center_id);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return intval($res['cnt'] ?? 0);
        }
        return 0;
    }
    $stmt = $mysqli->prepare("SELECT COUNT(*) AS cnt FROM `$table` WHERE $where AND (training_center_id = ? OR (user_id = ? AND user_category = 'training_centers'))");
    if ($stmt) {
        $stmt->bind_param("ii", $center_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return intval($res['cnt'] ?? 0);
    }
    return 0;
}

$cnt_lands       = getAssetCount($mysqli, 'land_assets', $current_center_id, $user_id);
$cnt_buildings   = getAssetCount($mysqli, 'building_inventories', $current_center_id, $user_id);
$cnt_vehicles    = getAssetCount($mysqli, 'registered_vehicles', $current_center_id, $user_id);
$cnt_furniture   = getAssetCount($mysqli, 'furniture_assets', $current_center_id, $user_id);
$cnt_machinery   = getAssetCount($mysqli, 'machinery_assets', $current_center_id, $user_id);
$cnt_instruments = getAssetCount($mysqli, 'instrument_assets', $current_center_id, $user_id);
$cnt_counterfoil = getAssetCount($mysqli, 'counterfoil_assets', $current_center_id, $user_id);
$cnt_staff       = getAssetCount($mysqli, 'users', $current_center_id, $user_id);
?>

<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h3 class="fw-bold text-dark m-0">
            <i class="bi bi-building-fill me-2" style="color: #370709;"></i>Training Centre Office Profile &amp; Assets
        </h3>
        <p class="text-muted mb-0 small">Official inventory directory and asset management for <strong class="text-dark"><?= htmlspecialchars($center_name) ?></strong> (<?= htmlspecialchars($center_location) ?>)</p>
    </div>
    <div>
        <a href="<?= BASE_PATH ?>dashboard.php" class="btn btn-secondary shadow-sm fw-bold">
            <i class="bi bi-speedometer2 me-1"></i> Dashboard
        </a>
    </div>
</div>

<!-- Notification Status SweetAlert -->
<?php if (isset($_GET['status']) && isset($_GET['msg'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: '<?= ($_GET['status'] === 'success') ? 'success' : 'error' ?>',
                    title: '<?= ($_GET['status'] === 'success') ? 'Success!' : 'Error!' ?>',
                    text: <?= json_encode($_GET['msg'] ?? '') ?>,
                    confirmButtonColor: '#370709',
                    timer: 3500,
                    timerProgressBar: true
                });
            }
        });
    </script>
<?php endif; ?>

<!-- Training Centre Profile Card -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; background: linear-gradient(135deg, #ffffff 0%, #fdf8f8 100%);">
    <div class="card-header bg-white py-3 border-0" style="border-left: 5px solid #370709 !important;">
        <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-person-badge-fill me-2 text-danger"></i>Training Centre Profile Details</h5>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-lg-4 col-md-6">
                <div class="p-2">
                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Training Facility</small>
                    <span class="fw-bold text-dark fs-6"><?= htmlspecialchars($center_name) ?></span>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="p-2">
                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Officer In-Charge</small>
                    <span class="text-dark fw-semibold"><?= htmlspecialchars($user_full_name) ?></span>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="p-2">
                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Designation</small>
                    <span class="text-secondary fw-medium"><?= htmlspecialchars($user_designation) ?></span>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="p-2">
                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Phone Number</small>
                    <span class="text-dark fw-semibold"><i class="bi bi-telephone-fill me-2 text-muted"></i><?= htmlspecialchars($off_phone) ?></span>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="p-2">
                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Email Address</small>
                    <span class="text-dark fw-semibold"><i class="bi bi-envelope-at-fill me-2 text-muted"></i><?= htmlspecialchars($off_email) ?></span>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="p-2">
                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Data Isolation Status</small>
                    <span class="badge bg-success-subtle text-success border border-success px-3 py-1 fs-7 rounded-pill">
                        <i class="bi bi-shield-check me-1"></i>Isolated for Training Centre #<?= intval($current_center_id) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions 7 Sub-Modules Grid -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap-fill me-2 text-primary"></i>Office Details Asset Registries</h5>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            <!-- 1. Lands & Buildings -->
            <div class="col-md-4 col-lg-4">
                <a href="lands_buildings.php" class="card text-decoration-none text-light h-100 shadow-sm p-3 border-0" style="background-color: #370709; border-radius: 10px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-white text-dark mb-2"><?= $cnt_lands ?> Lands / <?= $cnt_buildings ?> Items</span>
                            <h5 class="fw-bold m-0">Lands &amp; Buildings</h5>
                            <small class="text-light-50">Property deeds &amp; building inventory</small>
                        </div>
                        <i class="bi bi-building-fill fs-1 text-light-50"></i>
                    </div>
                </a>
            </div>

            <!-- 2. Vehicles Management -->
            <div class="col-md-4 col-lg-4">
                <a href="vehicles.php" class="card text-decoration-none text-light h-100 shadow-sm p-3 border-0" style="background-color: #b08723; border-radius: 10px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-white text-dark mb-2"><?= $cnt_vehicles ?> Fleet Vehicles</span>
                            <h5 class="fw-bold m-0">Vehicles Management</h5>
                            <small class="text-light-50">Vehicles, vans &amp; repair logs</small>
                        </div>
                        <i class="bi bi-car-front-fill fs-1 text-light-50"></i>
                    </div>
                </a>
            </div>

            <!-- 3. Furniture Management -->
            <div class="col-md-4 col-lg-4">
                <a href="furniture.php" class="card text-decoration-none text-light h-100 shadow-sm p-3 border-0" style="background-color: #a07174; border-radius: 10px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-white text-dark mb-2"><?= $cnt_furniture ?> Furniture Items</span>
                            <h5 class="fw-bold m-0">Furniture Management</h5>
                            <small class="text-light-50">Office desks, chairs &amp; fittings</small>
                        </div>
                        <i class="bi bi-file-earmark-plus fs-1 text-light-50"></i>
                    </div>
                </a>
            </div>

            <!-- 4. Machineries Management -->
            <div class="col-md-4 col-lg-4">
                <a href="machineries.php" class="card text-decoration-none text-light h-100 shadow-sm p-3 border-0" style="background-color: #1e3c72; border-radius: 10px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-white text-dark mb-2"><?= $cnt_machinery ?> Machines</span>
                            <h5 class="fw-bold m-0">Machineries Management</h5>
                            <small class="text-light-50">Generators, pumps &amp; lab machinery</small>
                        </div>
                        <i class="bi bi-gear-fill fs-1 text-light-50"></i>
                    </div>
                </a>
            </div>

            <!-- 5. Instruments Management -->
            <div class="col-md-4 col-lg-4">
                <a href="instruments.php" class="card text-decoration-none text-light h-100 shadow-sm p-3 border-0" style="background-color: #2e7d32; border-radius: 10px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-white text-dark mb-2"><?= $cnt_instruments ?> Instruments</span>
                            <h5 class="fw-bold m-0">Instruments Management</h5>
                            <small class="text-light-50">Training, AV &amp; demonstration tools</small>
                        </div>
                        <i class="bi bi-tools fs-1 text-light-50"></i>
                    </div>
                </a>
            </div>

            <!-- 6. Counter Foil Management -->
            <div class="col-md-4 col-lg-4">
                <a href="counter_foilage.php" class="card text-decoration-none text-light h-100 shadow-sm p-3 border-0" style="background-color: #e65100; border-radius: 10px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-white text-dark mb-2"><?= $cnt_counterfoil ?> Counter Foils</span>
                            <h5 class="fw-bold m-0">Counter Foil Management</h5>
                            <small class="text-light-50">Receipt &amp; voucher book registries</small>
                        </div>
                        <i class="bi bi-file-earmark-text-fill fs-1 text-light-50"></i>
                    </div>
                </a>
            </div>

            <!-- 7. Human Resource (HR) -->
            <div class="col-md-4 col-lg-4">
                <a href="employee_managment.php" class="card text-decoration-none text-light h-100 shadow-sm p-3 border-0" style="background-color: #820100; border-radius: 10px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-white text-dark mb-2"><?= $cnt_staff ?> Staff Officers</span>
                            <h5 class="fw-bold m-0">Human Resource (HR)</h5>
                            <small class="text-light-50">Training staff details, roles &amp; appointments</small>
                        </div>
                        <i class="bi bi-people-fill fs-1 text-light-50"></i>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>
