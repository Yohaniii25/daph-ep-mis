<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

// Fixed/Moved: Establish the range_id and range_name variables before running queries
$range_id = $_SESSION['range_id'] ?? null;
$range_name = $_SESSION['range_name'] ?? 'Your Range';

$district_name = 'Your District';

// Ensure we have district_id & range_id and user details
$user_full_name = 'Unknown';
$user_designation = 'Unknown';

$user_query = $mysqli->prepare("SELECT district_id, range_id, full_name, designation, role, phone, email FROM users WHERE id = ?");
if ($user_query) {
    $user_query->bind_param("i", $_SESSION['user_id']);
    $user_query->execute();
    $user_result = $user_query->get_result();
    if ($user_result->num_rows > 0) {
        $user_data = $user_result->fetch_assoc();
        $_SESSION['district_id'] = $user_data['district_id'];
        $_SESSION['range_id'] = $user_data['range_id'];
        $range_id = $user_data['range_id'];
        $user_full_name = !empty($user_data['full_name']) ? $user_data['full_name'] : 'Unknown';

        // "designan = user role" means we use role as designation if designation is not present or explicitly requested. Let's just use role string or designation.
        $user_designation = !empty($user_data['role']) ? ucwords(str_replace('_', ' ', $user_data['role'])) : 'Unknown';
    }
    $user_query->close();
}

// Fetch district name
if (!empty($_SESSION['district_id'])) {
    $district_query = $mysqli->prepare("SELECT name FROM districts WHERE id = ?");
    if ($district_query) {
        $district_query->bind_param("i", $_SESSION['district_id']);
        $district_query->execute();
        $district_result = $district_query->get_result();
        if ($district_result->num_rows > 0) {
            $district_data = $district_result->fetch_assoc();
            $district_name = $district_data['name'] ?? 'Your District';
        }
        $district_query->close();
    }
}

// Fetch range name if empty
if (!empty($_SESSION['range_id']) && $range_name === 'Your Range') {
    $range_query = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
    if ($range_query) {
        $range_query->bind_param("i", $_SESSION['range_id']);
        $range_query->execute();
        $range_result = $range_query->get_result();
        if ($range_result->num_rows > 0) {
            $range_data = $range_result->fetch_assoc();
            $range_name = $range_data['name'] ?? 'Your Assigned Range';
        }
        $range_query->close();
    }
}

$off_name   = $range_name;
$off_addr   = 'Unknown';
$off_gps    = 'Unknown';
$off_phone  = !empty($user_data['phone']) ? $user_data['phone'] : 'Unknown';
$off_email  = !empty($user_data['email']) ? $user_data['email'] : 'Unknown';

// 1. Fetch Human Resources (Staff)
$staff_stmt = $mysqli->prepare("SELECT * FROM users WHERE district_id = ? AND range_id = ? AND role IN ('employee', 'veterinary_surgeon') AND is_active = 1");
$staff_stmt->bind_param("ii", $_SESSION['district_id'], $range_id);
$staff_stmt->execute();
$staff_list = $staff_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 2. Fetch Immovable Assets
$immov_stmt = $mysqli->prepare("SELECT * FROM assets_immovable WHERE range_id = ?");
$immov_stmt->bind_param("i", $range_id);
$immov_stmt->execute();
$immovable_assets = $immov_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Fetch Movable Assets
$mov_stmt = $mysqli->prepare("SELECT * FROM assets_movable WHERE range_id = ?");
$mov_stmt->bind_param("i", $range_id);
$mov_stmt->execute();
$movable_assets = $mov_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<style>
    .btn-brand-maroon {
        background-color: #370709;
        color: #fff;
        border-color: #370709;
        transition: all 0.2s ease-in-out;
    }

    .btn-brand-maroon:hover {
        background-color: #250406;
        color: #fff;
        border-color: #250406;
        transform: translateY(-2px);
    }

    .btn-brand-rose {
        background-color: #a07174;
        color: #fff;
        border-color: #a07174;
        transition: all 0.2s ease-in-out;
    }

    .btn-brand-rose:hover {
        background-color: #8c5d60;
        color: #fff;
        border-color: #8c5d60;
        transform: translateY(-2px);
    }

    .action-btn-custom {
        transition: all 0.2s ease-in-out;
    }

    .action-btn-custom:hover {
        transform: translateY(-2px);
    }
</style>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">

        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">Office Inventory & HR Registry</h2>
                <p class="text-muted small mb-0">Official records for <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong></p>
            </div>
            <?php if (isset($_SESSION['msg'])): ?>
                <div class="alert alert-<?= $_SESSION['msg_type'] ?> py-2 px-3 mb-0 small">
                    <?= $_SESSION['msg'] ?>
                </div>
                <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
            <?php endif; ?>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3" style="border-left: 4px solid #370709;">
                <h5 class="card-title mb-0 fw-bold text-dark small text-uppercase tracking-wider"><i class="bi bi-building-fill me-2"></i>Office Profile Details</h5>
            </div>
            <div class="card-body bg-light-subtle">
                <div class="row g-3">
                    <div class="col-lg-4 col-md-6">
                        <div class="p-2">
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Name of the Office</small>
                            <span class="fw-bold text-dark fs-6"><?= htmlspecialchars($off_name) ?></span>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="p-2">
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Officer Name</small>
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
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Address</small>
                            <span class="text-secondary fw-medium"><?= htmlspecialchars($off_addr) ?></span>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="p-2">
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">GPS Location</small>
                            <span class="text-danger fw-semibold"><i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($off_gps) ?></span>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="p-2">
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Phone Number</small>
                            <span class="text-dark fw-semibold"><i class="bi bi-telephone-fill me-2 text-muted"></i><?= htmlspecialchars($off_phone) ?></span>
                        </div>
                    </div>
                    <div class="col-lg-12 col-md-12">
                        <div class="p-2">
                            <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 11px;">Email Address</small>
                            <span class="text-dark fw-semibold"><i class="bi bi-envelope-at-fill me-2 text-muted"></i><?= htmlspecialchars($off_email) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-bold">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="employee_managment.php" class="btn w-100 py-3" style="background-color: #820100; color: #fff; border-color: #820100;">
                            <i class="bi bi-people-fill fs-3"></i><br>
                            Human Resource
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="lands_buildings.php" class="btn w-100 py-3" style="background-color: #370709; color: #fff; border-color: #370709;">
                            <i class="bi bi-building-fill fs-3"></i><br>
                            Lands/Buildings
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="vehicles.php" class="btn w-100 py-3" style="background-color: #b08723; color: #fff; border-color: #b08723;">
                            <i class="bi bi-car-front-fill fs-3"></i><br>
                            Vehicles
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="furniture.php" class="btn w-100 py-3" style="background-color: #a07174; color: #fff; border-color: #a07174;">
                            <i class="bi bi-file-earmark-plus fs-3"></i><br>
                            Furniture
                        </a>
                    </div>

                    <div class="col-md-3">
                        <a href="machineries.php" class="btn w-100 py-3" style="background-color: #689ccf; color: #fff; border-color: #689ccf;">
                            <i class="bi bi-gear-fill fs-3"></i><br>
                            Machineries
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="instruments.php" class="btn w-100 py-3" style="background-color: #2e7d32; color: #fff; border-color: #2e7d32;">
                            <i class="bi bi-tools fs-3"></i><br>
                            Instruments
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="counter_foilage.php" class="btn w-100 py-3" style="background-color: #e65100; color: #fff; border-color: #e65100;">
                            <i class="bi bi-file-earmark-text-fill fs-3"></i><br>
                            Counter Foil
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'models/asset_modals.php'; ?>

    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selector = document.getElementById('assetTypeSelector');
        if (selector) {
            selector.addEventListener('change', function() {
                const type = this.value;
                const immovFields = document.getElementById('immovableFields');
                const movFields = document.getElementById('movableFields');
                const header = document.getElementById('modalHeaderColor');
                const btn = document.getElementById('submitBtn');

                // Toggle Visibility
                immovFields.style.display = (type === 'immovable') ? 'block' : 'none';
                movFields.style.display = (type === 'movable') ? 'block' : 'none';
                btn.disabled = false;

                // Change Colors
                if (type === 'immovable') {
                    header.className = 'modal-header bg-success text-white';
                    btn.className = 'btn btn-success w-100';
                } else {
                    header.className = 'modal-header bg-info text-white';
                    btn.className = 'btn btn-info text-white w-100';
                }
            });
        }
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>