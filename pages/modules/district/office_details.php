<?php
// pages/modules/district/office_details.php -> District Office Details, Category Hub & Range Drilldown
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

// Resolve District Jurisdiction
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

// Fetch all ranges in this district for filtering
$ranges_stmt = $mysqli->prepare("SELECT id, name, code FROM veterinary_ranges WHERE district_id = ? ORDER BY name ASC");
$ranges_stmt->bind_param("i", $district_id);
$ranges_stmt->execute();
$district_ranges = $ranges_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$ranges_stmt->close();

// Fetch District Deputy Director leadership info
$dd_query = "SELECT u.id, u.full_name, u.username, u.email, u.phone, u.designation, u.registered_date, u.appointment_date, u.last_login 
             FROM users u 
             WHERE u.role IN ('district_dd', 'deputy_director_district') AND (u.district_id = ? OR u.district LIKE ?) 
             LIMIT 1";
$dist_like = "%" . $district_name . "%";
$dd_info = null;
if ($dd_stmt = $mysqli->prepare($dd_query)) {
    $dd_stmt->bind_param("is", $district_id, $dist_like);
    $dd_stmt->execute();
    $dd_info = $dd_stmt->get_result()->fetch_assoc();
    $dd_stmt->close();
}

// Active view parameter
$current_view = $_GET['view'] ?? '';
$selected_range_id = isset($_GET['range_id']) && $_GET['range_id'] !== '' ? intval($_GET['range_id']) : null;
$selected_range_name = '';
if ($selected_range_id) {
    foreach ($district_ranges as $r) {
        if ($r['id'] == $selected_range_id) {
            $selected_range_name = $r['name'];
            break;
        }
    }
}

// Category counts for the District Dashboard
$cnt_lands = 0;
$cnt_buildings = 0;
$cnt_vehicles = 0;
$cnt_furniture = 0;
$cnt_machinery = 0;
$cnt_instruments = 0;
$cnt_counterfoil = 0;
$cnt_hr = 0;
$cnt_pending = 0;

// Lands count
if ($stmt = $mysqli->prepare("SELECT COUNT(*) FROM land_assets WHERE district_id = ? AND is_active = 1")) {
    $stmt->bind_param("i", $district_id);
    $stmt->execute();
    $cnt_lands = $stmt->get_result()->fetch_row()[0] ?? 0;
    $stmt->close();
}

// Buildings count
if ($stmt = $mysqli->prepare("SELECT COUNT(*) FROM building_inventories bi JOIN land_assets la ON bi.land_asset_id = la.id WHERE la.district_id = ?")) {
    $stmt->bind_param("i", $district_id);
    $stmt->execute();
    $cnt_buildings = $stmt->get_result()->fetch_row()[0] ?? 0;
    $stmt->close();
}

// Vehicles count
if ($stmt = $mysqli->prepare("SELECT COUNT(*) FROM registered_vehicles WHERE district_id = ? AND is_active = 1")) {
    $stmt->bind_param("i", $district_id);
    $stmt->execute();
    $cnt_vehicles = $stmt->get_result()->fetch_row()[0] ?? 0;
    $stmt->close();
}

// Furniture count
if ($stmt = $mysqli->prepare("SELECT COUNT(*) FROM furniture_assets WHERE district_id = ? AND is_active = 1")) {
    $stmt->bind_param("i", $district_id);
    $stmt->execute();
    $cnt_furniture = $stmt->get_result()->fetch_row()[0] ?? 0;
    $stmt->close();
}

// Machinery count
if ($stmt = $mysqli->prepare("SELECT COUNT(*) FROM machinery_assets WHERE district_id = ? AND is_active = 1")) {
    $stmt->bind_param("i", $district_id);
    $stmt->execute();
    $cnt_machinery = $stmt->get_result()->fetch_row()[0] ?? 0;
    $stmt->close();
}

// Instruments count
if ($stmt = $mysqli->prepare("SELECT COUNT(*) FROM instrument_assets WHERE district_id = ? AND is_active = 1")) {
    $stmt->bind_param("i", $district_id);
    $stmt->execute();
    $cnt_instruments = $stmt->get_result()->fetch_row()[0] ?? 0;
    $stmt->close();
}

// Counterfoil count
if ($stmt = $mysqli->prepare("SELECT COUNT(*) FROM counterfoil_assets WHERE district_id = ? AND is_active = 1")) {
    $stmt->bind_param("i", $district_id);
    $stmt->execute();
    $cnt_counterfoil = $stmt->get_result()->fetch_row()[0] ?? 0;
    $stmt->close();
}

// HR staff count
if ($stmt = $mysqli->prepare("SELECT COUNT(*) FROM users WHERE district_id = ? AND is_active = 1")) {
    $stmt->bind_param("i", $district_id);
    $stmt->execute();
    $cnt_hr = $stmt->get_result()->fetch_row()[0] ?? 0;
    $stmt->close();
}

// Pending approval staging count for this district
if ($stmt = $mysqli->prepare("SELECT COUNT(*) FROM pending_approvals WHERE district_id = ? AND status = 'pending'")) {
    $stmt->bind_param("i", $district_id);
    $stmt->execute();
    $cnt_pending = $stmt->get_result()->fetch_row()[0] ?? 0;
    $stmt->close();
}
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">

<style>
    .category-card {
        transition: transform 0.22s ease, box-shadow 0.22s ease;
        border-radius: 14px;
        text-decoration: none;
        display: block;
        overflow: hidden;
        position: relative;
    }
    .category-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.18) !important;
    }
    .card-icon-overlay {
        position: absolute;
        right: 18px;
        bottom: 12px;
        font-size: 4rem;
        opacity: 0.14;
        pointer-events: none;
    }
    .badge-count {
        font-size: 0.78rem;
        letter-spacing: 0.3px;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(0,0,0,0.12);
    }
    .filter-card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }
    .table-asset th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
</style>

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4 pb-5">

        <!-- Top Header & Breadcrumbs -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="../../../dashboard.php" class="text-decoration-none">Dashboard</a></li>
                        <li class="breadcrumb-item <?= empty($current_view) ? 'active' : '' ?>">
                            <?= empty($current_view) ? 'Office Details' : '<a href="office_details.php" class="text-decoration-none">Office Details</a>' ?>
                        </li>
                        <?php if (!empty($current_view)): ?>
                            <li class="breadcrumb-item active text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $current_view)) ?></li>
                        <?php endif; ?>
                    </ol>
                </nav>
                <h2 class="text-dark fw-bold mb-0">
                    <?= htmlspecialchars($district_name) ?> District Office Details &amp; Inventory Management
                </h2>
                <p class="text-muted small mb-0">
                    District Jurisdiction: <strong class="text-primary"><?= htmlspecialchars($district_name) ?></strong> | 
                    <?= count($district_ranges) ?> Subordinate Range Offices | 
                    Maker-Checker Approval Enabled
                </p>
            </div>
            <div class="d-flex gap-2">
                <?php if (!empty($current_view)): ?>
                    <a href="office_details.php" class="btn btn-outline-secondary shadow-sm">
                        <i class="bi bi-arrow-left me-1"></i> Back to Categories
                    </a>
                <?php else: ?>
                    <a href="task_assignments.php" class="btn btn-outline-primary shadow-sm">
                        <i class="bi bi-person-check me-1"></i> Task Delegation
                    </a>
                    <a href="../../../dashboard.php" class="btn btn-secondary shadow-sm">
                        <i class="bi bi-speedometer2 me-1"></i> Dashboard
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($cnt_pending > 0): ?>
            <!-- Staging Pending Alert -->
            <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center justify-content-between mb-4 rounded-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-hourglass-split fs-3 me-3 text-warning"></i>
                    <div>
                        <strong class="d-block text-dark">Staged Edits Pending Approval</strong>
                        <span class="small text-muted">You have <strong><?= $cnt_pending ?></strong> record edit(s) awaiting review by the Provincial Director. Live records will remain unchanged until authorized.</span>
                    </div>
                </div>
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><?= $cnt_pending ?> Pending</span>
            </div>
        <?php endif; ?>

        <?php if (empty($current_view)): ?>
            <!-- ======================================================= -->
            <!-- 1. DASHBOARD OVERVIEW: 7 CATEGORY CARDS GRID            -->
            <!-- ======================================================= -->

            <!-- District Leadership Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 border-start border-primary border-4">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center text-md-start mb-3 mb-md-0">
                            <div class="bg-primary text-white p-3 rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 72px; height: 72px;">
                                <i class="bi bi-person-badge fs-1"></i>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <span class="badge bg-primary mb-2">District Leadership</span>
                            <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($dd_info['full_name'] ?? 'District Deputy Director (' . $district_name . ')') ?></h4>
                            <p class="text-muted small mb-2">
                                <i class="bi bi-geo-alt-fill text-danger me-1"></i> District Deputy Director Office, <?= htmlspecialchars($district_name) ?> District
                            </p>
                            <div class="d-flex flex-wrap gap-3 small text-muted">
                                <span><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($dd_info['email'] ?? 'dd.district@gmail.com') ?></span>
                                <span><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($dd_info['phone'] ?? '+94 (0) 63 222 2222') ?></span>
                            </div>
                        </div>
                        <div class="col-md-4 mt-3 mt-md-0 border-start ps-md-4">
                            <div class="small text-muted mb-1">Administrative Jurisdiction</div>
                            <div class="fw-bold fs-5 text-dark"><?= htmlspecialchars($district_name) ?> District</div>
                            <div class="small text-muted mt-2">Active Field Personnel: <strong><?= number_format($cnt_hr) ?> Staff</strong></div>
                            <div class="small text-muted">Veterinary Ranges: <strong><?= count($district_ranges) ?> Operational Ranges</strong></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 7 Category Summary Cards Section -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-grid-3x3-gap-fill me-2 text-primary"></i>Office Inventory &amp; HR Categories
                </h5>
                <span class="text-muted small">Select any category to inspect data and filter by veterinary range</span>
            </div>

            <div class="row g-3 mb-4">
                <!-- 1. Lands & Buildings -->
                <div class="col-xl-4 col-md-6">
                    <a href="office_details.php?view=lands_buildings" class="card text-light h-100 shadow-sm p-4 border-0 category-card" style="background: linear-gradient(135deg, #370709, #541115);">
                        <i class="bi bi-building-fill card-icon-overlay"></i>
                        <div class="d-flex justify-content-between align-items-start position-relative">
                            <div>
                                <span class="badge bg-white text-dark mb-2 px-2.5 py-1.5 badge-count"><?= number_format($cnt_lands) ?> Lands / <?= number_format($cnt_buildings) ?> Items</span>
                                <h5 class="fw-bold mb-1 text-white">Lands &amp; Buildings</h5>
                                <small class="text-white-50 d-block">Technical HQ, cold depots &amp; property deeds</small>
                            </div>
                            <div class="bg-white bg-opacity-10 p-2 rounded-circle text-white">
                                <i class="bi bi-building-fill fs-3"></i>
                            </div>
                        </div>
                        <div class="mt-4 pt-2 border-top border-white border-opacity-10 d-flex justify-content-between align-items-center position-relative">
                            <span class="small text-white-50">Manage Properties</span>
                            <i class="bi bi-arrow-right-circle text-white"></i>
                        </div>
                    </a>
                </div>

                <!-- 2. Vehicles Management -->
                <div class="col-xl-4 col-md-6">
                    <a href="office_details.php?view=vehicles" class="card text-light h-100 shadow-sm p-4 border-0 category-card" style="background: linear-gradient(135deg, #b08723, #d4a028);">
                        <i class="bi bi-car-front-fill card-icon-overlay"></i>
                        <div class="d-flex justify-content-between align-items-start position-relative">
                            <div>
                                <span class="badge bg-white text-dark mb-2 px-2.5 py-1.5 badge-count"><?= number_format($cnt_vehicles) ?> Fleet Vehicles</span>
                                <h5 class="fw-bold mb-1 text-white">Vehicles Management</h5>
                                <small class="text-white-50 d-block">Mobile clinics, surveillance vans &amp; fleet registry</small>
                            </div>
                            <div class="bg-white bg-opacity-10 p-2 rounded-circle text-white">
                                <i class="bi bi-car-front-fill fs-3"></i>
                            </div>
                        </div>
                        <div class="mt-4 pt-2 border-top border-white border-opacity-10 d-flex justify-content-between align-items-center position-relative">
                            <span class="small text-white-50">Manage Vehicles</span>
                            <i class="bi bi-arrow-right-circle text-white"></i>
                        </div>
                    </a>
                </div>

                <!-- 3. Furniture Management -->
                <div class="col-xl-4 col-md-6">
                    <a href="office_details.php?view=furniture" class="card text-light h-100 shadow-sm p-4 border-0 category-card" style="background: linear-gradient(135deg, #a07174, #b58386);">
                        <i class="bi bi-file-earmark-plus card-icon-overlay"></i>
                        <div class="d-flex justify-content-between align-items-start position-relative">
                            <div>
                                <span class="badge bg-white text-dark mb-2 px-2.5 py-1.5 badge-count"><?= number_format($cnt_furniture) ?> Furniture Items</span>
                                <h5 class="fw-bold mb-1 text-white">Furniture Management</h5>
                                <small class="text-white-50 d-block">Workstations, cabinets, desks &amp; office fixtures</small>
                            </div>
                            <div class="bg-white bg-opacity-10 p-2 rounded-circle text-white">
                                <i class="bi bi-file-earmark-plus fs-3"></i>
                            </div>
                        </div>
                        <div class="mt-4 pt-2 border-top border-white border-opacity-10 d-flex justify-content-between align-items-center position-relative">
                            <span class="small text-white-50">Manage Furniture</span>
                            <i class="bi bi-arrow-right-circle text-white"></i>
                        </div>
                    </a>
                </div>

                <!-- 4. Machineries Management -->
                <div class="col-xl-4 col-md-6">
                    <a href="office_details.php?view=machineries" class="card text-light h-100 shadow-sm p-4 border-0 category-card" style="background: linear-gradient(135deg, #1e3c72, #2a5298);">
                        <i class="bi bi-gear-fill card-icon-overlay"></i>
                        <div class="d-flex justify-content-between align-items-start position-relative">
                            <div>
                                <span class="badge bg-white text-dark mb-2 px-2.5 py-1.5 badge-count"><?= number_format($cnt_machinery) ?> Machines</span>
                                <h5 class="fw-bold mb-1 text-white">Machineries Management</h5>
                                <small class="text-white-50 d-block">Cold chain freezers, solar fridges &amp; generators</small>
                            </div>
                            <div class="bg-white bg-opacity-10 p-2 rounded-circle text-white">
                                <i class="bi bi-gear-fill fs-3"></i>
                            </div>
                        </div>
                        <div class="mt-4 pt-2 border-top border-white border-opacity-10 d-flex justify-content-between align-items-center position-relative">
                            <span class="small text-white-50">Manage Machineries</span>
                            <i class="bi bi-arrow-right-circle text-white"></i>
                        </div>
                    </a>
                </div>

                <!-- 5. Instruments Management -->
                <div class="col-xl-4 col-md-6">
                    <a href="office_details.php?view=instruments" class="card text-light h-100 shadow-sm p-4 border-0 category-card" style="background: linear-gradient(135deg, #2e7d32, #388e3c);">
                        <i class="bi bi-tools card-icon-overlay"></i>
                        <div class="d-flex justify-content-between align-items-start position-relative">
                            <div>
                                <span class="badge bg-white text-dark mb-2 px-2.5 py-1.5 badge-count"><?= number_format($cnt_instruments) ?> Instruments</span>
                                <h5 class="fw-bold mb-1 text-white">Instruments Management</h5>
                                <small class="text-white-50 d-block">Diagnostic kits, surgical sets &amp; field tools</small>
                            </div>
                            <div class="bg-white bg-opacity-10 p-2 rounded-circle text-white">
                                <i class="bi bi-tools fs-3"></i>
                            </div>
                        </div>
                        <div class="mt-4 pt-2 border-top border-white border-opacity-10 d-flex justify-content-between align-items-center position-relative">
                            <span class="small text-white-50">Manage Instruments</span>
                            <i class="bi bi-arrow-right-circle text-white"></i>
                        </div>
                    </a>
                </div>

                <!-- 6. Counter Foil Management -->
                <div class="col-xl-4 col-md-6">
                    <a href="office_details.php?view=counterfoil" class="card text-light h-100 shadow-sm p-4 border-0 category-card" style="background: linear-gradient(135deg, #e65100, #f57c00);">
                        <i class="bi bi-file-earmark-text-fill card-icon-overlay"></i>
                        <div class="d-flex justify-content-between align-items-start position-relative">
                            <div>
                                <span class="badge bg-white text-dark mb-2 px-2.5 py-1.5 badge-count"><?= number_format($cnt_counterfoil) ?> Counter Foils</span>
                                <h5 class="fw-bold mb-1 text-white">Counter Foil Management</h5>
                                <small class="text-white-50 d-block">Outbreak registers, receipt books &amp; official permits</small>
                            </div>
                            <div class="bg-white bg-opacity-10 p-2 rounded-circle text-white">
                                <i class="bi bi-file-earmark-text-fill fs-3"></i>
                            </div>
                        </div>
                        <div class="mt-4 pt-2 border-top border-white border-opacity-10 d-flex justify-content-between align-items-center position-relative">
                            <span class="small text-white-50">Manage Counter Foils</span>
                            <i class="bi bi-arrow-right-circle text-white"></i>
                        </div>
                    </a>
                </div>

                <!-- 7. Human Resource (HR) -->
                <div class="col-xl-4 col-md-6">
                    <a href="office_details.php?view=hr" class="card text-light h-100 shadow-sm p-4 border-0 category-card" style="background: linear-gradient(135deg, #820100, #a00b0a);">
                        <i class="bi bi-people-fill card-icon-overlay"></i>
                        <div class="d-flex justify-content-between align-items-start position-relative">
                            <div>
                                <span class="badge bg-white text-dark mb-2 px-2.5 py-1.5 badge-count"><?= number_format($cnt_hr) ?> Active Staff</span>
                                <h5 class="fw-bold mb-1 text-white">Human Resource (HR)</h5>
                                <small class="text-white-50 d-block">Veterinary surgeons, officers, drivers &amp; field staff</small>
                            </div>
                            <div class="bg-white bg-opacity-10 p-2 rounded-circle text-white">
                                <i class="bi bi-people-fill fs-3"></i>
                            </div>
                        </div>
                        <div class="mt-4 pt-2 border-top border-white border-opacity-10 d-flex justify-content-between align-items-center position-relative">
                            <span class="small text-white-50">Manage Personnel</span>
                            <i class="bi bi-arrow-right-circle text-white"></i>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Range Offices Directory Section -->
            <?php
            // Fetch detailed list of Range Offices in District
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
            ?>
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="m-0 fw-bold text-dark"><i class="bi bi-building me-2 text-danger"></i>Veterinary Range Offices Directory (<?= count($range_offices) ?> Operational Offices)</h5>
                    <span class="badge bg-primary"><?= count($range_offices) ?> Offices</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="districtOfficesTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
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
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- ======================================================= -->
            <!-- 2. CATEGORY DETAIL VIEW & RANGE DRILLDOWN               -->
            <!-- ======================================================= -->

            <!-- Category Header & Range Filter Bar -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 filter-card">
                <div class="card-body p-3">
                    <div class="row align-items-center">
                        <div class="col-lg-5 col-md-6 mb-2 mb-md-0">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-1">
                                <i class="bi bi-funnel-fill me-1 text-primary"></i>Select Range Filter
                            </label>
                            <select id="rangeFilterSelect" class="form-select form-select-sm shadow-sm" onchange="filterCategoryByRange(this.value)">
                                <option value="">All Ranges in <?= htmlspecialchars($district_name) ?> District (<?= count($district_ranges) ?> Ranges)</option>
                                <?php foreach ($district_ranges as $rng): ?>
                                    <option value="<?= $rng['id'] ?>" <?= ($selected_range_id == $rng['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($rng['name']) ?><?= !empty($rng['code']) ? ' (' . htmlspecialchars($rng['code']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-7 col-md-6 text-md-end">
                            <span class="badge bg-light text-dark border px-3 py-2 me-1">
                                <i class="bi bi-geo-alt me-1 text-danger"></i>District: <strong><?= htmlspecialchars($district_name) ?></strong>
                            </span>
                            <span class="badge bg-light text-dark border px-3 py-2 me-1">
                                <i class="bi bi-funnel me-1 text-primary"></i>Range: 
                                <strong><?= !empty($selected_range_name) ? htmlspecialchars($selected_range_name) : 'All Ranges' ?></strong>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            // ==========================================
            // VIEW: MACHINERIES MANAGEMENT
            // ==========================================
            if ($current_view === 'machineries'):
                $query = "SELECT ma.*, vr.name AS range_name 
                          FROM machinery_assets ma 
                          LEFT JOIN veterinary_ranges vr ON ma.range_id = vr.id 
                          WHERE ma.district_id = ? AND ma.is_active = 1";
                $params = [$district_id];
                $types = "i";

                if ($selected_range_id) {
                    $query .= " AND ma.range_id = ?";
                    $params[] = $selected_range_id;
                    $types .= "i";
                }
                $query .= " ORDER BY ma.id DESC";

                $stmt = $mysqli->prepare($query);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $machineries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            ?>
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-gear-fill me-2 text-primary"></i>Machineries Inventory Directory</h5>
                            <small class="text-muted">Strictly scoped to <?= htmlspecialchars($district_name) ?> District</small>
                        </div>
                        <span class="badge bg-primary px-3 py-2"><?= count($machineries) ?> Records</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="districtCategoryTable" class="table table-hover table-striped table-bordered align-middle w-100 small table-asset">
                                <thead>
                                    <tr>
                                        <th>Range Office</th>
                                        <th>Machinery Type</th>
                                        <th>Condition</th>
                                        <th class="text-center">Available Quantity</th>
                                        <th>Purchase / Received Date</th>
                                        <th>Remarks</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($machineries as $item): ?>
                                        <tr>
                                            <td class="fw-bold text-primary">
                                                <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($item['range_name'] ?? 'District Central') ?>
                                            </td>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($item['machinery_type']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= ($item['current_condition'] === 'Good' || $item['current_condition'] === 'Operational') ? 'success' : (($item['current_condition'] === 'Needs Repair') ? 'warning text-dark' : 'danger') ?>">
                                                    <?= htmlspecialchars($item['current_condition']) ?>
                                                </span>
                                            </td>
                                            <td class="text-center fw-bold"><?= sprintf("%02d", $item['available_quantity']) ?></td>
                                            <td><?= htmlspecialchars($item['purchase_date'] ?: '-') ?></td>
                                            <td><?= htmlspecialchars($item['remarks'] ?: '-') ?></td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-info me-1" onclick='viewMachinery(<?= json_encode($item) ?>)' title="View Details">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary" onclick='editMachinery(<?= json_encode($item) ?>)' title="Edit Record">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php
            // ==========================================
            // VIEW: VEHICLES MANAGEMENT
            // ==========================================
            elseif ($current_view === 'vehicles'):
                $query = "SELECT rv.*, vr.name AS range_name 
                          FROM registered_vehicles rv 
                          LEFT JOIN veterinary_ranges vr ON rv.range_id = vr.id 
                          WHERE rv.district_id = ? AND rv.is_active = 1";
                $params = [$district_id];
                $types = "i";

                if ($selected_range_id) {
                    $query .= " AND rv.range_id = ?";
                    $params[] = $selected_range_id;
                    $types .= "i";
                }
                $query .= " ORDER BY rv.id DESC";

                $stmt = $mysqli->prepare($query);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $vehicles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            ?>
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-car-front-fill me-2" style="color: #b08723;"></i>Fleet Vehicles Inventory Directory</h5>
                            <small class="text-muted">Strictly scoped to <?= htmlspecialchars($district_name) ?> District</small>
                        </div>
                        <span class="badge bg-warning text-dark px-3 py-2"><?= count($vehicles) ?> Records</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="districtCategoryTable" class="table table-hover table-striped table-bordered align-middle w-100 small table-asset">
                                <thead>
                                    <tr>
                                        <th>Range Office</th>
                                        <th>Vehicle Type</th>
                                        <th>Vehicle Number</th>
                                        <th>Chassis Number</th>
                                        <th>Condition</th>
                                        <th>Other Details</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vehicles as $item): ?>
                                        <tr>
                                            <td class="fw-bold text-primary">
                                                <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($item['range_name'] ?? 'District Fleet') ?>
                                            </td>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($item['vehicle_type']) ?></td>
                                            <td class="font-monospace fw-bold text-dark"><?= htmlspecialchars($item['vehicle_number']) ?></td>
                                            <td class="font-monospace text-muted"><?= htmlspecialchars($item['chassis_number'] ?: '-') ?></td>
                                            <td>
                                                <span class="badge bg-<?= ($item['current_condition'] === 'Good' || $item['current_condition'] === 'Operational') ? 'success' : (($item['current_condition'] === 'Needs Repair') ? 'warning text-dark' : 'danger') ?>">
                                                    <?= htmlspecialchars($item['current_condition']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($item['other_details'] ?: '-') ?></td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-info me-1" onclick='viewVehicle(<?= json_encode($item) ?>)' title="View Details">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary" onclick='editVehicle(<?= json_encode($item) ?>)' title="Edit Record">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php
            // ==========================================
            // VIEW: FURNITURE MANAGEMENT
            // ==========================================
            elseif ($current_view === 'furniture'):
                $query = "SELECT fa.*, vr.name AS range_name 
                          FROM furniture_assets fa 
                          LEFT JOIN veterinary_ranges vr ON fa.range_id = vr.id 
                          WHERE fa.district_id = ? AND fa.is_active = 1";
                $params = [$district_id];
                $types = "i";

                if ($selected_range_id) {
                    $query .= " AND fa.range_id = ?";
                    $params[] = $selected_range_id;
                    $types .= "i";
                }
                $query .= " ORDER BY fa.id DESC";

                $stmt = $mysqli->prepare($query);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $furniture = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            ?>
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-file-earmark-plus me-2" style="color: #a07174;"></i>Furniture Assets Directory</h5>
                            <small class="text-muted">Strictly scoped to <?= htmlspecialchars($district_name) ?> District</small>
                        </div>
                        <span class="badge px-3 py-2 text-white" style="background-color: #a07174;"><?= count($furniture) ?> Records</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="districtCategoryTable" class="table table-hover table-striped table-bordered align-middle w-100 small table-asset">
                                <thead>
                                    <tr>
                                        <th>Range Office</th>
                                        <th>Furniture Type / Item</th>
                                        <th class="text-center">Available Quantity</th>
                                        <th>Date Received</th>
                                        <th>Condition</th>
                                        <th>Remarks</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($furniture as $item): ?>
                                        <tr>
                                            <td class="fw-bold text-primary">
                                                <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($item['range_name'] ?? 'District Store') ?>
                                            </td>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($item['furniture_type'] ?? $item['item_name']) ?></td>
                                            <td class="text-center fw-bold"><?= sprintf("%02d", $item['available_quantity']) ?></td>
                                            <td><?= htmlspecialchars($item['date_received'] ?? $item['purchase_date'] ?: '-') ?></td>
                                            <td>
                                                <span class="badge bg-<?= ($item['current_condition'] === 'Good' || $item['current_condition'] === 'Operational') ? 'success' : (($item['current_condition'] === 'Needs Repair') ? 'warning text-dark' : 'danger') ?>">
                                                    <?= htmlspecialchars($item['current_condition']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($item['remarks'] ?: '-') ?></td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-info me-1" onclick='viewFurniture(<?= json_encode($item) ?>)' title="View Details">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary" onclick='editFurniture(<?= json_encode($item) ?>)' title="Edit Record">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php
            // ==========================================
            // VIEW: INSTRUMENTS MANAGEMENT
            // ==========================================
            elseif ($current_view === 'instruments'):
                $query = "SELECT ia.*, vr.name AS range_name 
                          FROM instrument_assets ia 
                          LEFT JOIN veterinary_ranges vr ON ia.range_id = vr.id 
                          WHERE ia.district_id = ? AND ia.is_active = 1";
                $params = [$district_id];
                $types = "i";

                if ($selected_range_id) {
                    $query .= " AND ia.range_id = ?";
                    $params[] = $selected_range_id;
                    $types .= "i";
                }
                $query .= " ORDER BY ia.id DESC";

                $stmt = $mysqli->prepare($query);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $instruments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            ?>
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-tools me-2 text-success"></i>Instruments &amp; Diagnostic Tools Directory</h5>
                            <small class="text-muted">Strictly scoped to <?= htmlspecialchars($district_name) ?> District</small>
                        </div>
                        <span class="badge bg-success px-3 py-2"><?= count($instruments) ?> Records</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="districtCategoryTable" class="table table-hover table-striped table-bordered align-middle w-100 small table-asset">
                                <thead>
                                    <tr>
                                        <th>Range Office</th>
                                        <th>Instrument Type</th>
                                        <th>Condition</th>
                                        <th class="text-center">Available Quantity</th>
                                        <th>Purchase / Received Date</th>
                                        <th>Remarks</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($instruments as $item): ?>
                                        <tr>
                                            <td class="fw-bold text-primary">
                                                <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($item['range_name'] ?? 'District Central') ?>
                                            </td>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($item['instrument_type'] ?? $item['instrument_name']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= ($item['current_condition'] === 'Good' || $item['current_condition'] === 'Operational') ? 'success' : (($item['current_condition'] === 'Needs Repair') ? 'warning text-dark' : 'danger') ?>">
                                                    <?= htmlspecialchars($item['current_condition']) ?>
                                                </span>
                                            </td>
                                            <td class="text-center fw-bold"><?= sprintf("%02d", $item['available_quantity']) ?></td>
                                            <td><?= htmlspecialchars($item['purchase_date'] ?: '-') ?></td>
                                            <td><?= htmlspecialchars($item['remarks'] ?: '-') ?></td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-info me-1" onclick='viewInstrument(<?= json_encode($item) ?>)' title="View Details">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary" onclick='editInstrument(<?= json_encode($item) ?>)' title="Edit Record">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php
            // ==========================================
            // VIEW: COUNTER FOIL MANAGEMENT
            // ==========================================
            elseif ($current_view === 'counterfoil'):
                $query = "SELECT ca.*, vr.name AS range_name 
                          FROM counterfoil_assets ca 
                          LEFT JOIN veterinary_ranges vr ON ca.range_id = vr.id 
                          WHERE ca.district_id = ? AND ca.is_active = 1";
                $params = [$district_id];
                $types = "i";

                if ($selected_range_id) {
                    $query .= " AND ca.range_id = ?";
                    $params[] = $selected_range_id;
                    $types .= "i";
                }
                $query .= " ORDER BY ca.id DESC";

                $stmt = $mysqli->prepare($query);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $counterfoils = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            ?>
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-file-earmark-text-fill me-2" style="color: #e65100;"></i>Counter Foil &amp; Registers Directory</h5>
                            <small class="text-muted">Strictly scoped to <?= htmlspecialchars($district_name) ?> District</small>
                        </div>
                        <span class="badge px-3 py-2 text-white" style="background-color: #e65100;"><?= count($counterfoils) ?> Records</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="districtCategoryTable" class="table table-hover table-striped table-bordered align-middle w-100 small table-asset">
                                <thead>
                                    <tr>
                                        <th>Range Office</th>
                                        <th>Counterfoil / Book Type</th>
                                        <th>Status / Condition</th>
                                        <th class="text-center">Available Quantity</th>
                                        <th>Received Date</th>
                                        <th>Remarks</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($counterfoils as $item): ?>
                                        <tr>
                                            <td class="fw-bold text-primary">
                                                <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($item['range_name'] ?? 'District Office') ?>
                                            </td>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($item['counterfoil_type'] ?? $item['book_type']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= ($item['current_condition'] === 'Good' || $item['current_condition'] === 'Operational' || $item['current_condition'] === 'Active') ? 'success' : 'secondary' ?>">
                                                    <?= htmlspecialchars($item['current_condition'] ?? $item['current_status'] ?? 'Active') ?>
                                                </span>
                                            </td>
                                            <td class="text-center fw-bold"><?= sprintf("%02d", $item['available_quantity']) ?></td>
                                            <td><?= htmlspecialchars($item['purchase_date'] ?? $item['received_date'] ?: '-') ?></td>
                                            <td><?= htmlspecialchars($item['remarks'] ?: '-') ?></td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-info me-1" onclick='viewCounterfoil(<?= json_encode($item) ?>)' title="View Details">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary" onclick='editCounterfoil(<?= json_encode($item) ?>)' title="Edit Record">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php
            // ==========================================
            // VIEW: LANDS & BUILDINGS MANAGEMENT
            // ==========================================
            elseif ($current_view === 'lands_buildings'):
                // Fetch Lands
                $l_query = "SELECT la.*, vr.name AS range_name 
                            FROM land_assets la 
                            LEFT JOIN veterinary_ranges vr ON la.range_id = vr.id 
                            WHERE la.district_id = ? AND la.is_active = 1";
                $l_params = [$district_id];
                $l_types = "i";
                if ($selected_range_id) {
                    $l_query .= " AND la.range_id = ?";
                    $l_params[] = $selected_range_id;
                    $l_types .= "i";
                }
                $l_query .= " ORDER BY la.id DESC";
                $stmt = $mysqli->prepare($l_query);
                $stmt->bind_param($l_types, ...$l_params);
                $stmt->execute();
                $lands = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();

                // Fetch Buildings
                $b_query = "SELECT bi.*, la.property_name, la.district_id, la.range_id, vr.name AS range_name 
                            FROM building_inventories bi 
                            JOIN land_assets la ON bi.land_asset_id = la.id 
                            LEFT JOIN veterinary_ranges vr ON la.range_id = vr.id 
                            WHERE la.district_id = ?";
                $b_params = [$district_id];
                $b_types = "i";
                if ($selected_range_id) {
                    $b_query .= " AND la.range_id = ?";
                    $b_params[] = $selected_range_id;
                    $b_types .= "i";
                }
                $b_query .= " ORDER BY bi.id DESC";
                $stmt = $mysqli->prepare($b_query);
                $stmt->bind_param($b_types, ...$b_params);
                $stmt->execute();
                $buildings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            ?>
                <!-- Navigation Tabs between Lands and Buildings -->
                <ul class="nav nav-pills mb-3 bg-white p-2 rounded shadow-sm" id="landTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="lands-tab" data-bs-toggle="tab" data-bs-target="#lands-pane" type="button" role="tab">
                            <i class="bi bi-geo-alt-fill me-1"></i> Land Profiles &amp; Deeds (<?= count($lands) ?>)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="buildings-tab" data-bs-toggle="tab" data-bs-target="#buildings-pane" type="button" role="tab">
                            <i class="bi bi-building-fill me-1"></i> Building Inventory Items (<?= count($buildings) ?>)
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="landTabsContent">
                    <!-- Lands Tab -->
                    <div class="tab-pane fade show active" id="lands-pane" role="tabpanel">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-geo-alt-fill me-2" style="color: #370709;"></i>Registered Land Properties</h5>
                                    <small class="text-muted">Strictly scoped to <?= htmlspecialchars($district_name) ?> District</small>
                                </div>
                                <span class="badge bg-dark px-3 py-2"><?= count($lands) ?> Land Parcels</span>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="districtLandsTable" class="table table-hover table-striped table-bordered align-middle w-100 small table-asset">
                                        <thead>
                                            <tr>
                                                <th>Range Office</th>
                                                <th>Property Name</th>
                                                <th>Land Extent</th>
                                                <th>Building Area</th>
                                                <th>Status</th>
                                                <th>Deed Reference</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($lands as $item): ?>
                                                <tr>
                                                    <td class="fw-bold text-primary">
                                                        <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($item['range_name'] ?? 'District') ?>
                                                    </td>
                                                    <td class="fw-bold text-dark"><?= htmlspecialchars($item['property_name'] ?? $item['land_name']) ?></td>
                                                    <td><?= htmlspecialchars($item['land_extent'] ?: '-') ?></td>
                                                    <td><?= htmlspecialchars($item['building_area'] ?: '-') ?></td>
                                                    <td><span class="badge bg-info text-dark"><?= htmlspecialchars($item['land_status'] ?: 'Active') ?></span></td>
                                                    <td><?= htmlspecialchars($item['deed_reference'] ?: '-') ?></td>
                                                    <td class="text-center">
                                                        <button class="btn btn-sm btn-outline-info me-1" onclick='viewLand(<?= json_encode($item) ?>)' title="View Details">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-primary" onclick='editLand(<?= json_encode($item) ?>)' title="Edit Record">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Buildings Tab -->
                    <div class="tab-pane fade" id="buildings-pane" role="tabpanel">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-boxes me-2" style="color: #370709;"></i>Building Inventory Directory</h5>
                                    <small class="text-muted">Strictly scoped to <?= htmlspecialchars($district_name) ?> District</small>
                                </div>
                                <span class="badge bg-dark px-3 py-2"><?= count($buildings) ?> Items</span>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="districtBuildingsTable" class="table table-hover table-striped table-bordered align-middle w-100 small table-asset">
                                        <thead>
                                            <tr>
                                                <th>Range Office</th>
                                                <th>Inventory Item</th>
                                                <th>Parent Property</th>
                                                <th class="text-center">Quantity</th>
                                                <th>Condition</th>
                                                <th>Specification</th>
                                                <th>Remarks</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($buildings as $item): ?>
                                                <tr>
                                                    <td class="fw-bold text-primary">
                                                        <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($item['range_name'] ?? 'District') ?>
                                                    </td>
                                                    <td class="fw-bold text-dark"><?= htmlspecialchars($item['inventory_item'] ?? $item['building_name']) ?></td>
                                                    <td><?= htmlspecialchars($item['property_name'] ?: '-') ?></td>
                                                    <td class="text-center fw-bold"><?= sprintf("%02d", $item['available_quantity']) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= ($item['current_condition'] === 'Good' || $item['current_condition'] === 'Operational') ? 'success' : 'warning text-dark' ?>">
                                                            <?= htmlspecialchars($item['current_condition']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($item['specification'] ?: '-') ?></td>
                                                    <td><?= htmlspecialchars($item['remarks'] ?: '-') ?></td>
                                                    <td class="text-center">
                                                        <button class="btn btn-sm btn-outline-info me-1" onclick='viewBuilding(<?= json_encode($item) ?>)' title="View Details">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-primary" onclick='editBuilding(<?= json_encode($item) ?>)' title="Edit Record">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php
            // ==========================================
            // VIEW: HUMAN RESOURCE (HR) MANAGEMENT
            // ==========================================
            elseif ($current_view === 'hr'):
                $query = "SELECT u.*, vr.name AS range_name 
                          FROM users u 
                          LEFT JOIN veterinary_ranges vr ON u.range_id = vr.id 
                          WHERE u.district_id = ? AND u.is_active = 1";
                $params = [$district_id];
                $types = "i";

                if ($selected_range_id) {
                    $query .= " AND u.range_id = ?";
                    $params[] = $selected_range_id;
                    $types .= "i";
                }
                $query .= " ORDER BY u.full_name ASC";

                $stmt = $mysqli->prepare($query);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $staff = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            ?>
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-people-fill me-2" style="color: #820100;"></i>District Human Resources Directory</h5>
                            <small class="text-muted">Strictly scoped to <?= htmlspecialchars($district_name) ?> District</small>
                        </div>
                        <span class="badge px-3 py-2 text-white" style="background-color: #820100;"><?= count($staff) ?> Staff Members</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="districtCategoryTable" class="table table-hover table-striped table-bordered align-middle w-100 small table-asset">
                                <thead>
                                    <tr>
                                        <th>Range / Station</th>
                                        <th>Officer Full Name</th>
                                        <th>Service Number</th>
                                        <th>Designation</th>
                                        <th>System Role</th>
                                        <th>Category</th>
                                        <th>Contact</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($staff as $item): ?>
                                        <tr>
                                            <td class="fw-bold text-primary">
                                                <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($item['range_name'] ?? 'District HQ') ?>
                                            </td>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($item['full_name']) ?></td>
                                            <td class="font-monospace"><?= htmlspecialchars($item['service_number'] ?: $item['emp_id'] ?: '-') ?></td>
                                            <td><?= htmlspecialchars($item['designation'] ?: '-') ?></td>
                                            <td>
                                                <span class="badge bg-secondary text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $item['role'])) ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($item['service_category'] ?: '-') ?></td>
                                            <td>
                                                <?php if (!empty($item['email'])): ?>
                                                    <div><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($item['email']) ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($item['phone'])): ?>
                                                    <div><i class="bi bi-telephone me-1 text-muted"></i><?= htmlspecialchars($item['phone']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-info me-1" onclick='viewStaff(<?= json_encode($item) ?>)' title="View Details">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary" onclick='editStaff(<?= json_encode($item) ?>)' title="Edit Record">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </main>
</div>

<!-- =============================================================== -->
<!-- MODALS SECTION: VIEW & EDIT FOR ALL 7 CATEGORIES               -->
<!-- =============================================================== -->

<!-- View Modal (Universal) -->
<div class="modal fade" id="universalViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="universalViewTitle">Record Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="universalViewBody">
                <!-- Dynamically filled -->
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Machinery Edit Modal -->
<div class="modal fade" id="editMachineryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-gear-fill me-2"></i>Edit Machinery Asset</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editMachineryForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="edit_mac_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Unit *</label>
                        <select name="unit" id="edit_mac_unit" class="form-select" required>
                            <option value="" disabled selected>-- Select Unit --</option>
                            <option value="provincial_director">Provincial Director</option>
                            <option value="additional_provincial_director">Additional Provincial Director</option>
                            <option value="subject_matter_specialist">Subject Matter Specialist</option>
                            <option value="deputy_director_hq_1">Deputy Director - H/Q-1</option>
                            <option value="deputy_director_hq_2">Deputy Director - H/Q-2</option>
                            <option value="deputy_director_district">Deputy Director - District</option>
                            <option value="range_veterinary_officer">Range Veterinary Officer</option>
                            <option value="training_centers">Training Centers</option>
                            <option value="regional_farms">Regional Farms</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Machinery Type / Model *</label>
                        <input type="text" name="machinery_type" id="edit_mac_type" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Current Condition *</label>
                            <select name="current_condition" id="edit_mac_condition" class="form-select" required>
                                <option value="Good">Good</option>
                                <option value="Operational">Operational</option>
                                <option value="Needs Repair">Needs Repair</option>
                                <option value="Unserviceable">Unserviceable</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Available Quantity *</label>
                            <input type="number" name="available_quantity" id="edit_mac_quantity" class="form-control" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Purchase / Received Date</label>
                        <input type="date" name="purchase_date" id="edit_mac_purchase_date" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Remarks</label>
                        <textarea name="remarks" id="edit_mac_remarks" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="alert alert-info py-2 px-3 small mb-0">
                        <i class="bi bi-info-circle me-1"></i> Changes will be staged for Provincial Director approval before going live.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">Submit Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Vehicle Edit Modal -->
<div class="modal fade" id="editVehicleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-car-front-fill me-2"></i>Edit Vehicle Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editVehicleForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="edit_veh_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Unit *</label>
                        <select name="unit" id="edit_veh_unit" class="form-select" required>
                            <option value="" disabled selected>-- Select Unit --</option>
                            <option value="provincial_director">Provincial Director</option>
                            <option value="additional_provincial_director">Additional Provincial Director</option>
                            <option value="subject_matter_specialist">Subject Matter Specialist</option>
                            <option value="deputy_director_hq_1">Deputy Director - H/Q-1</option>
                            <option value="deputy_director_hq_2">Deputy Director - H/Q-2</option>
                            <option value="deputy_director_district">Deputy Director - District</option>
                            <option value="range_veterinary_officer">Range Veterinary Officer</option>
                            <option value="training_centers">Training Centers</option>
                            <option value="regional_farms">Regional Farms</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Vehicle Type *</label>
                        <input type="text" name="vehicle_type" id="edit_veh_type" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Vehicle Registration No. *</label>
                            <input type="text" name="vehicle_number" id="edit_veh_number" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Chassis Number</label>
                            <input type="text" name="chassis_number" id="edit_veh_chassis" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Current Condition *</label>
                        <select name="current_condition" id="edit_veh_condition" class="form-select" required>
                            <option value="Good">Good</option>
                            <option value="Operational">Operational</option>
                            <option value="Needs Repair">Needs Repair</option>
                            <option value="Unserviceable">Unserviceable</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Other Details</label>
                        <textarea name="other_details" id="edit_veh_details" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="alert alert-info py-2 px-3 small mb-0">
                        <i class="bi bi-info-circle me-1"></i> Changes will be staged for Provincial Director approval before going live.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">Submit Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Furniture Edit Modal -->
<div class="modal fade" id="editFurnitureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-plus me-2"></i>Edit Furniture Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editFurnitureForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="edit_fur_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Unit *</label>
                        <select name="unit" id="edit_fur_unit" class="form-select" required>
                            <option value="" disabled selected>-- Select Unit --</option>
                            <option value="provincial_director">Provincial Director</option>
                            <option value="additional_provincial_director">Additional Provincial Director</option>
                            <option value="subject_matter_specialist">Subject Matter Specialist</option>
                            <option value="deputy_director_hq_1">Deputy Director - H/Q-1</option>
                            <option value="deputy_director_hq_2">Deputy Director - H/Q-2</option>
                            <option value="deputy_director_district">Deputy Director - District</option>
                            <option value="range_veterinary_officer">Range Veterinary Officer</option>
                            <option value="training_centers">Training Centers</option>
                            <option value="regional_farms">Regional Farms</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Furniture Item / Type *</label>
                        <input type="text" name="furniture_type" id="edit_fur_type" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Available Quantity *</label>
                            <input type="number" name="available_quantity" id="edit_fur_quantity" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Condition *</label>
                            <select name="current_condition" id="edit_fur_condition" class="form-select" required>
                                <option value="Good">Good</option>
                                <option value="Operational">Operational</option>
                                <option value="Needs Repair">Needs Repair</option>
                                <option value="Damaged">Damaged</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Date Received</label>
                        <input type="date" name="date_received" id="edit_fur_date" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Remarks</label>
                        <textarea name="remarks" id="edit_fur_remarks" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="alert alert-info py-2 px-3 small mb-0">
                        <i class="bi bi-info-circle me-1"></i> Changes will be staged for Provincial Director approval before going live.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">Submit Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Instrument Edit Modal -->
<div class="modal fade" id="editInstrumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-tools me-2"></i>Edit Instrument Asset</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editInstrumentForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="edit_ins_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Unit *</label>
                        <select name="unit" id="edit_ins_unit" class="form-select" required>
                            <option value="" disabled selected>-- Select Unit --</option>
                            <option value="provincial_director">Provincial Director</option>
                            <option value="additional_provincial_director">Additional Provincial Director</option>
                            <option value="subject_matter_specialist">Subject Matter Specialist</option>
                            <option value="deputy_director_hq_1">Deputy Director - H/Q-1</option>
                            <option value="deputy_director_hq_2">Deputy Director - H/Q-2</option>
                            <option value="deputy_director_district">Deputy Director - District</option>
                            <option value="range_veterinary_officer">Range Veterinary Officer</option>
                            <option value="training_centers">Training Centers</option>
                            <option value="regional_farms">Regional Farms</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Instrument Type *</label>
                        <input type="text" name="instrument_type" id="edit_ins_type" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Condition *</label>
                            <select name="current_condition" id="edit_ins_condition" class="form-select" required>
                                <option value="Good">Good</option>
                                <option value="Operational">Operational</option>
                                <option value="Needs Repair">Needs Repair</option>
                                <option value="Unserviceable">Unserviceable</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Available Quantity *</label>
                            <input type="number" name="available_quantity" id="edit_ins_quantity" class="form-control" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Purchase / Received Date</label>
                        <input type="date" name="purchase_date" id="edit_ins_date" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Remarks</label>
                        <textarea name="remarks" id="edit_ins_remarks" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="alert alert-info py-2 px-3 small mb-0">
                        <i class="bi bi-info-circle me-1"></i> Changes will be staged for Provincial Director approval before going live.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">Submit Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Counterfoil Edit Modal -->
<div class="modal fade" id="editCounterfoilModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-text-fill me-2"></i>Edit Counter Foil Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editCounterfoilForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="edit_cou_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Unit *</label>
                        <select name="unit" id="edit_cou_unit" class="form-select" required>
                            <option value="" disabled selected>-- Select Unit --</option>
                            <option value="provincial_director">Provincial Director</option>
                            <option value="additional_provincial_director">Additional Provincial Director</option>
                            <option value="subject_matter_specialist">Subject Matter Specialist</option>
                            <option value="deputy_director_hq_1">Deputy Director - H/Q-1</option>
                            <option value="deputy_director_hq_2">Deputy Director - H/Q-2</option>
                            <option value="deputy_director_district">Deputy Director - District</option>
                            <option value="range_veterinary_officer">Range Veterinary Officer</option>
                            <option value="training_centers">Training Centers</option>
                            <option value="regional_farms">Regional Farms</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Counterfoil / Book Type *</label>
                        <input type="text" name="counterfoil_type" id="edit_cou_type" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Condition / Status *</label>
                            <select name="current_condition" id="edit_cou_condition" class="form-select" required>
                                <option value="Good">Good</option>
                                <option value="Operational">Operational</option>
                                <option value="Active">Active</option>
                                <option value="Exhausted">Exhausted</option>
                                <option value="Damaged">Damaged</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Available Quantity *</label>
                            <input type="number" name="available_quantity" id="edit_cou_quantity" class="form-control" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Purchase / Received Date</label>
                        <input type="date" name="purchase_date" id="edit_cou_date" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Remarks</label>
                        <textarea name="remarks" id="edit_cou_remarks" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="alert alert-info py-2 px-3 small mb-0">
                        <i class="bi bi-info-circle me-1"></i> Changes will be staged for Provincial Director approval before going live.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">Submit Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Land Edit Modal -->
<div class="modal fade" id="editLandModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-geo-alt-fill me-2"></i>Edit Land Property Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editLandForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="edit_land_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Unit *</label>
                        <select name="unit" id="edit_land_unit" class="form-select" required>
                            <option value="" disabled selected>-- Select Unit --</option>
                            <option value="provincial_director">Provincial Director</option>
                            <option value="additional_provincial_director">Additional Provincial Director</option>
                            <option value="subject_matter_specialist">Subject Matter Specialist</option>
                            <option value="deputy_director_hq_1">Deputy Director - H/Q-1</option>
                            <option value="deputy_director_hq_2">Deputy Director - H/Q-2</option>
                            <option value="deputy_director_district">Deputy Director - District</option>
                            <option value="range_veterinary_officer">Range Veterinary Officer</option>
                            <option value="training_centers">Training Centers</option>
                            <option value="regional_farms">Regional Farms</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Property Name *</label>
                        <input type="text" name="property_name" id="edit_land_name" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Land Extent</label>
                            <input type="text" name="land_extent" id="edit_land_extent" class="form-control" placeholder="e.g. 2A 1R 10P">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Building Area (Sqft)</label>
                            <input type="text" name="building_area" id="edit_land_building_area" class="form-control">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Land Status</label>
                            <input type="text" name="land_status" id="edit_land_status" class="form-control" placeholder="e.g. Crown / Private">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Deed Reference No.</label>
                            <input type="text" name="deed_reference" id="edit_land_deed" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Deed Description / Remarks</label>
                        <textarea name="deed_description" id="edit_land_description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="alert alert-info py-2 px-3 small mb-0">
                        <i class="bi bi-info-circle me-1"></i> Changes will be staged for Provincial Director approval before going live.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">Submit Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Building Edit Modal -->
<div class="modal fade" id="editBuildingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-boxes me-2"></i>Edit Building Inventory Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editBuildingForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="edit_bld_id">
                    <input type="hidden" name="land_asset_id" id="edit_bld_land_asset_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Unit *</label>
                        <select name="unit" id="edit_bld_unit" class="form-select" required>
                            <option value="" disabled selected>-- Select Unit --</option>
                            <option value="provincial_director">Provincial Director</option>
                            <option value="additional_provincial_director">Additional Provincial Director</option>
                            <option value="subject_matter_specialist">Subject Matter Specialist</option>
                            <option value="deputy_director_hq_1">Deputy Director - H/Q-1</option>
                            <option value="deputy_director_hq_2">Deputy Director - H/Q-2</option>
                            <option value="deputy_director_district">Deputy Director - District</option>
                            <option value="range_veterinary_officer">Range Veterinary Officer</option>
                            <option value="training_centers">Training Centers</option>
                            <option value="regional_farms">Regional Farms</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Inventory Item Name *</label>
                        <input type="text" name="inventory_item" id="edit_bld_item" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Available Quantity *</label>
                            <input type="number" name="available_quantity" id="edit_bld_quantity" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Condition *</label>
                            <select name="current_condition" id="edit_bld_condition" class="form-select" required>
                                <option value="Good">Good</option>
                                <option value="Operational">Operational</option>
                                <option value="Needs Repair">Needs Repair</option>
                                <option value="Damaged">Damaged</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Specification</label>
                        <input type="text" name="specification" id="edit_bld_spec" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Remarks</label>
                        <textarea name="remarks" id="edit_bld_remarks" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="alert alert-info py-2 px-3 small mb-0">
                        <i class="bi bi-info-circle me-1"></i> Changes will be staged for Provincial Director approval before going live.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">Submit Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- HR Staff Edit Modal -->
<div class="modal fade" id="editStaffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-people-fill me-2"></i>Edit Officer HR Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editStaffForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="edit_staff_id">
                    <input type="hidden" name="ajax" value="1">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Unit *</label>
                        <select name="unit" id="edit_staff_unit" class="form-select" required>
                            <option value="" disabled selected>-- Select Unit --</option>
                            <option value="provincial_director">Provincial Director</option>
                            <option value="additional_provincial_director">Additional Provincial Director</option>
                            <option value="subject_matter_specialist">Subject Matter Specialist</option>
                            <option value="deputy_director_hq_1">Deputy Director - H/Q-1</option>
                            <option value="deputy_director_hq_2">Deputy Director - H/Q-2</option>
                            <option value="deputy_director_district">Deputy Director - District</option>
                            <option value="range_veterinary_officer">Range Veterinary Officer</option>
                            <option value="training_centers">Training Centers</option>
                            <option value="regional_farms">Regional Farms</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Officer Full Name *</label>
                            <input type="text" name="officer_name" id="edit_staff_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Service / Employee Number</label>
                            <input type="text" name="service_number" id="edit_staff_number" class="form-control">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Designation</label>
                            <input type="text" name="designation" id="edit_staff_designation" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">System Role</label>
                            <select name="user_role" id="edit_staff_role" class="form-select">
                                <option value="veterinary_surgeon">Veterinary Surgeon</option>
                                <option value="government_veterinary_surgeon">Government Veterinary Surgeon</option>
                                <option value="additional_veterinary_surgeon">Additional Veterinary Surgeon</option>
                                <option value="livestock_development_officer">Livestock Development Officer</option>
                                <option value="development_officer">Development Officer</option>
                                <option value="driver">Driver</option>
                                <option value="dispensary_assistant">Dispensary Assistant</option>
                                <option value="department_laborer">Department Laborer</option>
                                <option value="night_watcher">Night Watcher</option>
                                <option value="employee">General Employee</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Service Category</label>
                            <input type="text" name="service_category" id="edit_staff_category" class="form-control" placeholder="e.g. Range Veterinary Officer">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Contact Phone</label>
                            <input type="text" name="contact_number" id="edit_staff_phone" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Email Address</label>
                        <input type="email" name="email" id="edit_staff_email" class="form-control">
                    </div>
                    <div class="alert alert-info py-2 px-3 small mb-0">
                        <i class="bi bi-info-circle me-1"></i> HR profile changes will be staged for Provincial Director approval before updating live records.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">Submit Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>

<script>
// Safe DataTable Initializer to prevent reinitialization warnings
function initSafeDataTable(selector, options = {}) {
    if ($(selector).length) {
        if ($.fn.DataTable && $.fn.DataTable.isDataTable(selector)) {
            $(selector).DataTable().destroy();
        }
        return $(selector).DataTable($.extend({
            destroy: true,
            pageLength: 10,
            order: [[0, 'asc']]
        }, options));
    }
}

$(document).ready(function() {
    // Initialize DataTables with safe destroyed re-init
    initSafeDataTable('#districtOfficesTable', { pageLength: 10, order: [[0, 'asc']] });
    initSafeDataTable('#districtCategoryTable', { pageLength: 15, order: [[0, 'asc']] });
    initSafeDataTable('#districtLandsTable', { pageLength: 10, order: [[0, 'asc']] });
    initSafeDataTable('#districtBuildingsTable', { pageLength: 10, order: [[0, 'asc']] });

    // Handle tab switching recalculations for Lands and Buildings
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function() {
        if ($.fn.DataTable) {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        }
    });

    // Generic Form Submission Handler for Staged Edits
    function handleStagedFormSubmit(formSelector, endpointUrl, modalSelector) {
        $(formSelector).on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Submitting...');

            $.ajax({
                url: endpointUrl,
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(res) {
                    submitBtn.prop('disabled', false).text('Submit Changes');
                    if (res.success) {
                        $(modalSelector).modal('hide');
                        // Exact required SweetAlert2 UI feedback
                        Swal.fire({
                            icon: 'info',
                            title: 'Changes Submitted',
                            text: 'Changes submitted successfully. Awaiting final approval from the Provincial Director.',
                            confirmButtonColor: '#820100'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Action Failed',
                            text: res.message || 'An error occurred while saving the edit.'
                        });
                    }
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).text('Submit Changes');
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'Unable to communicate with the server. Please try again.'
                    });
                }
            });
        });
    }

    // Bind edit forms to processors
    handleStagedFormSubmit('#editMachineryForm', '../veterinary/processors/update_machinery.php', '#editMachineryModal');
    handleStagedFormSubmit('#editVehicleForm', '../veterinary/processors/update_vehicle.php', '#editVehicleModal');
    handleStagedFormSubmit('#editFurnitureForm', '../veterinary/processors/update_furniture.php', '#editFurnitureModal');
    handleStagedFormSubmit('#editInstrumentForm', '../veterinary/processors/update_instrument.php', '#editInstrumentModal');
    handleStagedFormSubmit('#editCounterfoilForm', '../veterinary/processors/update_counterfoil.php', '#editCounterfoilModal');
    handleStagedFormSubmit('#editLandForm', '../veterinary/processors/update_land_asset.php', '#editLandModal');
    handleStagedFormSubmit('#editBuildingForm', '../veterinary/processors/update_building_inventory.php', '#editBuildingModal');
    handleStagedFormSubmit('#editStaffForm', '../veterinary/processors/update_employee.php', '#editStaffModal');
});

// Filter Category by Range
function filterCategoryByRange(rangeId) {
    const url = new URL(window.location.href);
    if (rangeId) {
        url.searchParams.set('range_id', rangeId);
    } else {
        url.searchParams.delete('range_id');
    }
    window.location.href = url.toString();
}

// Universal View Builder
function showUniversalModal(title, detailsObj) {
    $('#universalViewTitle').text(title);
    let html = '<div class="table-responsive"><table class="table table-bordered small mb-0">';
    for (const [key, val] of Object.entries(detailsObj)) {
        html += `<tr><th class="bg-light text-muted w-40" style="width: 38%;">${key}</th><td class="fw-bold text-dark">${val || '-'}</td></tr>`;
    }
    html += '</table></div>';
    $('#universalViewBody').html(html);
    new bootstrap.Modal(document.getElementById('universalViewModal')).show();
}

// Machinery Modal Handlers
function viewMachinery(data) {
    showUniversalModal('Machinery Asset Details', {
        'Range Office': data.range_name || 'District Central',
        'Machinery Type': data.machinery_type,
        'Current Condition': data.current_condition,
        'Available Quantity': data.available_quantity,
        'Purchase Date': data.purchase_date,
        'Remarks': data.remarks
    });
}
function editMachinery(data) {
    $('#edit_mac_id').val(data.id || '');
    $('#edit_mac_unit').val(data.unit || '');
    $('#edit_mac_type').val(data.machinery_type || '');
    $('#edit_mac_condition').val(data.current_condition || 'Good');
    $('#edit_mac_quantity').val(data.available_quantity || 1);
    $('#edit_mac_purchase_date').val(data.purchase_date || '');
    $('#edit_mac_remarks').val(data.remarks || '');
    new bootstrap.Modal(document.getElementById('editMachineryModal')).show();
}

// Vehicle Modal Handlers
function viewVehicle(data) {
    showUniversalModal('Vehicle Record Details', {
        'Range Office': data.range_name || 'District Fleet',
        'Vehicle Type': data.vehicle_type,
        'Vehicle Registration': data.vehicle_number,
        'Chassis Number': data.chassis_number,
        'Condition': data.current_condition,
        'Other Details': data.other_details
    });
}
function editVehicle(data) {
    $('#edit_veh_id').val(data.id || '');
    $('#edit_veh_unit').val(data.unit || '');
    $('#edit_veh_type').val(data.vehicle_type || '');
    $('#edit_veh_number').val(data.vehicle_number || '');
    $('#edit_veh_chassis').val(data.chassis_number || '');
    $('#edit_veh_condition').val(data.current_condition || 'Good');
    $('#edit_veh_details').val(data.other_details || '');
    new bootstrap.Modal(document.getElementById('editVehicleModal')).show();
}

// Furniture Modal Handlers
function viewFurniture(data) {
    showUniversalModal('Furniture Item Details', {
        'Range Office': data.range_name || 'District Store',
        'Item Type': data.furniture_type || data.item_name,
        'Quantity': data.available_quantity,
        'Condition': data.current_condition,
        'Date Received': data.date_received || data.purchase_date,
        'Remarks': data.remarks
    });
}
function editFurniture(data) {
    $('#edit_fur_id').val(data.id || '');
    $('#edit_fur_unit').val(data.unit || '');
    $('#edit_fur_type').val(data.furniture_type || data.item_name || '');
    $('#edit_fur_quantity').val(data.available_quantity || 1);
    $('#edit_fur_condition').val(data.current_condition || 'Good');
    $('#edit_fur_date').val(data.date_received || data.purchase_date || '');
    $('#edit_fur_remarks').val(data.remarks || '');
    new bootstrap.Modal(document.getElementById('editFurnitureModal')).show();
}

// Instrument Modal Handlers
function viewInstrument(data) {
    showUniversalModal('Instrument Asset Details', {
        'Range Office': data.range_name || 'District Central',
        'Instrument Type': data.instrument_type || data.instrument_name,
        'Quantity': data.available_quantity,
        'Condition': data.current_condition,
        'Purchase Date': data.purchase_date,
        'Remarks': data.remarks
    });
}
function editInstrument(data) {
    $('#edit_ins_id').val(data.id || '');
    $('#edit_ins_unit').val(data.unit || '');
    $('#edit_ins_type').val(data.instrument_type || data.instrument_name || '');
    $('#edit_ins_condition').val(data.current_condition || 'Good');
    $('#edit_ins_quantity').val(data.available_quantity || 1);
    $('#edit_ins_date').val(data.purchase_date || '');
    $('#edit_ins_remarks').val(data.remarks || '');
    new bootstrap.Modal(document.getElementById('editInstrumentModal')).show();
}

// Counterfoil Modal Handlers
function viewCounterfoil(data) {
    showUniversalModal('Counter Foil Details', {
        'Range Office': data.range_name || 'District Office',
        'Book / Register Type': data.counterfoil_type || data.book_type,
        'Status / Condition': data.current_condition || data.current_status,
        'Available Quantity': data.available_quantity,
        'Date Received': data.purchase_date || data.received_date,
        'Remarks': data.remarks
    });
}
function editCounterfoil(data) {
    $('#edit_cou_id').val(data.id || '');
    $('#edit_cou_unit').val(data.unit || '');
    $('#edit_cou_type').val(data.counterfoil_type || data.book_type || '');
    $('#edit_cou_condition').val(data.current_condition || data.current_status || 'Good');
    $('#edit_cou_quantity').val(data.available_quantity || 1);
    $('#edit_cou_date').val(data.purchase_date || data.received_date || '');
    $('#edit_cou_remarks').val(data.remarks || '');
    new bootstrap.Modal(document.getElementById('editCounterfoilModal')).show();
}

// Land Modal Handlers
function viewLand(data) {
    showUniversalModal('Land Property Details', {
        'Range Office': data.range_name || 'District',
        'Property Name': data.property_name || data.land_name,
        'Land Extent': data.land_extent,
        'Building Area': data.building_area,
        'Status': data.land_status,
        'Deed Reference': data.deed_reference,
        'Deed Description': data.deed_description
    });
}
function editLand(data) {
    $('#edit_land_id').val(data.id || '');
    $('#edit_land_unit').val(data.unit || '');
    $('#edit_land_name').val(data.property_name || data.land_name || '');
    $('#edit_land_extent').val(data.land_extent || '');
    $('#edit_land_building_area').val(data.building_area || '');
    $('#edit_land_status').val(data.land_status || '');
    $('#edit_land_deed').val(data.deed_reference || '');
    $('#edit_land_description').val(data.deed_description || '');
    new bootstrap.Modal(document.getElementById('editLandModal')).show();
}

// Building Modal Handlers
function viewBuilding(data) {
    showUniversalModal('Building Item Details', {
        'Range Office': data.range_name || 'District',
        'Item Name': data.inventory_item || data.building_name,
        'Parent Property': data.property_name,
        'Available Quantity': data.available_quantity,
        'Condition': data.current_condition,
        'Specification': data.specification,
        'Remarks': data.remarks
    });
}
function editBuilding(data) {
    $('#edit_bld_id').val(data.id || '');
    $('#edit_bld_unit').val(data.unit || '');
    $('#edit_bld_land_asset_id').val(data.land_asset_id || '');
    $('#edit_bld_item').val(data.inventory_item || data.building_name || '');
    $('#edit_bld_quantity').val(data.available_quantity || 1);
    $('#edit_bld_condition').val(data.current_condition || 'Good');
    $('#edit_bld_spec').val(data.specification || '');
    $('#edit_bld_remarks').val(data.remarks || '');
    new bootstrap.Modal(document.getElementById('editBuildingModal')).show();
}

// HR Staff Modal Handlers
function viewStaff(data) {
    showUniversalModal('Staff HR Details', {
        'Range Office / Station': data.range_name || 'District HQ',
        'Full Name': data.full_name,
        'Service Number': data.service_number || data.emp_id,
        'Designation': data.designation,
        'Role': data.role,
        'Service Category': data.service_category,
        'Email Address': data.email,
        'Contact Phone': data.phone
    });
}
function editStaff(data) {
    $('#edit_staff_id').val(data.id || '');
    $('#edit_staff_unit').val(data.unit || '');
    $('#edit_staff_name').val(data.full_name || '');
    $('#edit_staff_number').val(data.service_number || data.emp_id || '');
    $('#edit_staff_designation').val(data.designation || '');
    $('#edit_staff_role').val(data.role || 'employee');
    $('#edit_staff_category').val(data.service_category || '');
    $('#edit_staff_phone').val(data.phone || '');
    $('#edit_staff_email').val(data.email || '');
    new bootstrap.Modal(document.getElementById('editStaffModal')).show();
}
</script>
