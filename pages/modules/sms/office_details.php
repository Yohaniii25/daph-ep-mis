<?php
// pages/modules/sms/office_details.php -> Subject Matter Specialist Office Inventory & Technical HR Registry
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

$allowed_roles = ['sms', 'administrator', 'provincial_director', 'district_dd'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 12;
$district_id = $_SESSION['district_id'] ?? null;
$district_name = $_SESSION['district'] ?? 'Provincial';

// Fetch Specialist User Profile Details
$user_full_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Subject Matter Specialist';
$user_designation = 'Subject Matter Specialist (Epidemiology & Disease Control)';
$office_title = 'SMS Technical & Epidemiology Directorate';
$off_phone = 'N/A';
$off_email = 'sms@gmail.com';

$user_query = $mysqli->prepare("SELECT full_name, phone, email, designation, district FROM users WHERE id = ?");
if ($user_query) {
    $user_query->bind_param("i", $user_id);
    $user_query->execute();
    $res = $user_query->get_result();
    if ($row = $res->fetch_assoc()) {
        if (!empty($row['full_name'])) $user_full_name = $row['full_name'];
        if (!empty($row['phone'])) $off_phone = $row['phone'];
        if (!empty($row['email'])) $off_email = $row['email'];
        if (!empty($row['designation'])) $user_designation = $row['designation'];
        if (!empty($row['district'])) $district_name = $row['district'];
    }
    $user_query->close();
}

// Asset Counters with strict Subject Matter Specialist data isolation
function getSmsAssetCount($mysqli, $table, $user_id) {
    $where = "is_active = 1";
    if (in_array($table, ['registered_vehicles', 'vehicle_repairs', 'furniture_assets', 'machinery_assets', 'instrument_assets', 'counterfoil_assets'])) {
        $where = "1=1";
    }
    if ($table === 'users') {
        $stmt = $mysqli->prepare("SELECT COUNT(*) AS cnt FROM `users` WHERE is_active = 1 AND (role = 'sms' OR id = ? OR (role = 'employee' AND (designation LIKE '%SMS%' OR service_category LIKE '%Technical%' OR service_category LIKE '%Epidemiology%')))");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return intval($res['cnt'] ?? 0);
        }
        return 0;
    }
    $stmt = $mysqli->prepare("SELECT COUNT(*) AS cnt FROM `$table` WHERE $where AND (user_category = 'subject_matter_specialist' OR user_id = ?)");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return intval($res['cnt'] ?? 0);
    }
    return 0;
}

$cnt_lands       = getSmsAssetCount($mysqli, 'land_assets', $user_id);
$cnt_buildings   = getSmsAssetCount($mysqli, 'building_inventories', $user_id);
$cnt_vehicles    = getSmsAssetCount($mysqli, 'registered_vehicles', $user_id);
$cnt_furniture   = getSmsAssetCount($mysqli, 'furniture_assets', $user_id);
$cnt_machinery   = getSmsAssetCount($mysqli, 'machinery_assets', $user_id);
$cnt_instruments = getSmsAssetCount($mysqli, 'instrument_assets', $user_id);
$cnt_counterfoil = getSmsAssetCount($mysqli, 'counterfoil_assets', $user_id);
$cnt_staff       = getSmsAssetCount($mysqli, 'users', $user_id);
?>

<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h3 class="fw-bold text-dark m-0">
            <i class="bi bi-shield-shaded me-2" style="color: #370709;"></i>Subject Matter Specialist Office Profile &amp; Assets
        </h3>
        <p class="text-muted mb-0 small">Official epidemiological inventory directory, mobile clinic assets and technical staff management for <strong class="text-dark"><?= htmlspecialchars($office_title) ?></strong> (<?= htmlspecialchars($district_name) ?>)</p>
    </div>
    <div class="d-flex gap-2">
        <a href="../../../dashboard.php" class="btn btn-secondary shadow-sm fw-bold">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
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

<!-- Subject Matter Specialist Profile Card -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; background: linear-gradient(135deg, #ffffff 0%, #fbf8f8 100%);">
    <div class="card-header bg-white py-3 border-0" style="border-left: 5px solid #370709 !important;">
        <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-person-badge-fill me-2 text-danger"></i>Specialist Office Profile Details</h5>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-lg-4 col-md-6">
                <div class="p-2">
                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Technical Division</small>
                    <span class="fw-bold text-dark fs-6"><?= htmlspecialchars($office_title) ?></span>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="p-2">
                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Specialist In-Charge</small>
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
                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Official Email</small>
                    <span class="text-dark fw-semibold"><i class="bi bi-envelope-at-fill me-2 text-muted"></i><?= htmlspecialchars($off_email) ?></span>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="p-2">
                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Data Isolation Status</small>
                    <span class="badge bg-success-subtle text-success border border-success px-3 py-1 fs-7 rounded-pill">
                        <i class="bi bi-shield-check me-1"></i>Isolated for Subject Matter Specialist Category
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions 7 Sub-Modules Grid for SMS -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap-fill me-2 text-primary"></i>Specialist Office Asset Registries</h5>
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
                            <small class="text-light-50">Technical HQ, cold depots &amp; diagnostic units</small>
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
                            <small class="text-light-50">Mobile clinics, surveillance vans &amp; repair logs</small>
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
                            <small class="text-light-50">Specialist workstations, cabinets &amp; fittings</small>
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
                            <small class="text-light-50">Cold chain freezers, solar fridges &amp; generators</small>
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
                            <small class="text-light-50">Diagnostic kits, dart guns &amp; cold boxes</small>
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
                            <small class="text-light-50">Outbreak registers, certificates &amp; permits</small>
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
                            <span class="badge bg-white text-dark mb-2"><?= $cnt_staff ?> Technical Staff</span>
                            <h5 class="fw-bold m-0">Human Resource (HR)</h5>
                            <small class="text-light-50">Field assistants, vaccinators &amp; technicians</small>
                        </div>
                        <i class="bi bi-people-fill fs-1 text-light-50"></i>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>
