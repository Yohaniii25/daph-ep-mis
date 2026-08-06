<?php
// pages/modules/farm/office_details.php -> Regional Farm Office Inventory & HR Registry
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';


if ($_SESSION['role'] !== 'farms_dd') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;
$farm_id = $_SESSION['farm_id'] ?? null;
$district_id = $_SESSION['district_id'] ?? null;

// Fetch user & farm profile details
$user_full_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Farm Manager';
$user_designation = 'Deputy Director - Regional Farm';
$farm_name = 'Regional Farm';
$off_phone = 'N/A';
$off_email = 'N/A';

$user_query = $mysqli->prepare("SELECT u.full_name, u.phone, u.email, f.name AS farm_name FROM users u LEFT JOIN farms f ON u.farm_id = f.id WHERE u.id = ?");
if ($user_query) {
    $user_query->bind_param("i", $user_id);
    $user_query->execute();
    $res = $user_query->get_result();
    if ($row = $res->fetch_assoc()) {
        if (!empty($row['full_name'])) $user_full_name = $row['full_name'];
        if (!empty($row['phone'])) $off_phone = $row['phone'];
        if (!empty($row['email'])) $off_email = $row['email'];
        if (!empty($row['farm_name'])) $farm_name = $row['farm_name'];
    }
    $user_query->close();
}

// Asset Counters with strict data isolation
function getAssetCount($mysqli, $table, $farm_id, $user_id) {
    $where = "is_active = 1";
    if (in_array($table, ['registered_vehicles', 'registered_vehicle_repairs', 'furniture_assets', 'machinery_assets', 'instrument_assets', 'counterfoil_assets'])) {
        $where = "1=1";
    }
    $stmt = $mysqli->prepare("SELECT COUNT(*) AS cnt FROM `$table` WHERE $where AND (farm_id = ? OR user_id = ?)");
    if ($stmt) {
        $stmt->bind_param("ii", $farm_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return intval($res['cnt'] ?? 0);
    }
    return 0;
}

$cnt_lands       = getAssetCount($mysqli, 'land_assets', $farm_id, $user_id);
$cnt_buildings   = getAssetCount($mysqli, 'building_inventories', $farm_id, $user_id);
$cnt_vehicles    = getAssetCount($mysqli, 'registered_vehicles', $farm_id, $user_id);
$cnt_furniture   = getAssetCount($mysqli, 'furniture_assets', $farm_id, $user_id);
$cnt_machinery   = getAssetCount($mysqli, 'machinery_assets', $farm_id, $user_id);
$cnt_instruments = getAssetCount($mysqli, 'instrument_assets', $farm_id, $user_id);
$cnt_counterfoil = getAssetCount($mysqli, 'counterfoil_assets', $farm_id, $user_id);
?>

<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h3 class="fw-bold text-dark m-0">
            <i class="bi bi-building-fill me-2" style="color: #370709;"></i>Regional Farm Office Profile & Assets
        </h3>
        <p class="text-muted mb-0 small">Official inventory directory and asset management for <strong class="text-dark"><?= htmlspecialchars($farm_name) ?></strong></p>
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

<!-- Farm Profile Card -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; background: linear-gradient(135deg, #ffffff 0%, #fdf8f8 100%);">
    <div class="card-header bg-white py-3 border-0" style="border-left: 5px solid #370709 !important;">
        <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-person-badge-fill me-2 text-danger"></i>Farm Office Profile Details</h5>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-lg-4 col-md-6">
                <div class="p-2">
                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Regional Farm</small>
                    <span class="fw-bold text-dark fs-6"><?= htmlspecialchars($farm_name) ?></span>
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
                        <i class="bi bi-shield-check me-1"></i>Isolated for Farm ID #<?= intval($farm_id) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions 6 Sub-Modules Grid -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap-fill me-2 text-primary"></i>Office Details Asset Registries</h5>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-4 col-lg-4">
                <a href="lands_buildings.php" class="card text-decoration-none text-white h-100 shadow-sm p-3 farm-card border-0" style="background-color: #370709;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-white text-dark mb-2"><?= $cnt_lands ?> Lands / <?= $cnt_buildings ?> Items</span>
                            <h5 class="fw-bold m-0">Lands &amp; Buildings</h5>
                            <small class="text-white-50">Property deeds &amp; building inventory</small>
                        </div>
                        <i class="bi bi-building-fill fs-1 text-white-50"></i>
                    </div>
                </a>
            </div>

            <div class="col-md-4 col-lg-4">
                <a href="vehicles.php" class="card text-decoration-none text-white h-100 shadow-sm p-3 farm-card border-0" style="background-color: #b08723;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-white text-dark mb-2"><?= $cnt_vehicles ?> Fleet Vehicles</span>
                            <h5 class="fw-bold m-0">Vehicles Management</h5>
                            <small class="text-white-50">Vehicles, tractors &amp; repair logs</small>
                        </div>
                        <i class="bi bi-car-front-fill fs-1 text-white-50"></i>
                    </div>
                </a>
            </div>

            <div class="col-md-4 col-lg-4">
                <a href="furniture.php" class="card text-decoration-none text-white h-100 shadow-sm p-3 farm-card border-0" style="background-color: #a07174;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-white text-dark mb-2"><?= $cnt_furniture ?> Furniture Items</span>
                            <h5 class="fw-bold m-0">Furniture Management</h5>
                            <small class="text-white-50">Office desks, chairs &amp; fittings</small>
                        </div>
                        <i class="bi bi-file-earmark-plus fs-1 text-white-50"></i>
                    </div>
                </a>
            </div>

            <div class="col-md-4 col-lg-4">
                <a href="machineries.php" class="card text-decoration-none text-white h-100 shadow-sm p-3 farm-card border-0" style="background-color: #689ccf;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-white text-dark mb-2"><?= $cnt_machinery ?> Machines</span>
                            <h5 class="fw-bold m-0">Machineries Management</h5>
                            <small class="text-white-50">Cutters, pumps, milking equipment</small>
                        </div>
                        <i class="bi bi-gear-fill fs-1 text-white-50"></i>
                    </div>
                </a>
            </div>

            <div class="col-md-4 col-lg-4">
                <a href="instruments.php" class="card text-decoration-none text-white h-100 shadow-sm p-3 farm-card border-0" style="background-color: #2e7d32;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-white text-dark mb-2"><?= $cnt_instruments ?> Instruments</span>
                            <h5 class="fw-bold m-0">Instruments Management</h5>
                            <small class="text-white-50">Clinical, AI &amp; lab tools</small>
                        </div>
                        <i class="bi bi-tools fs-1 text-white-50"></i>
                    </div>
                </a>
            </div>

            <div class="col-md-4 col-lg-4">
                <a href="counter_foilage.php" class="card text-decoration-none text-white h-100 shadow-sm p-3 farm-card border-0" style="background-color: #e65100;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-white text-dark mb-2"><?= $cnt_counterfoil ?> Counter Foils</span>
                            <h5 class="fw-bold m-0">Counter Foil Management</h5>
                            <small class="text-white-50">Receipt &amp; voucher books</small>
                        </div>
                        <i class="bi bi-file-earmark-text-fill fs-1 text-white-50"></i>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>
