<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_roles = ['deputy_director_hq_1', 'administrator', 'provincial_director'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied. Unauthorized role footprint.");
}

require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../includes/header.php';

// Quick Global Counts
$total_ranges = (int)($mysqli->query("SELECT COUNT(*) AS c FROM veterinary_ranges")->fetch_assoc()['c'] ?? 45);
$active_ranges = (int)($mysqli->query("SELECT COUNT(*) AS c FROM veterinary_ranges WHERE is_active = 1")->fetch_assoc()['c'] ?? 45);
$total_vs = (int)($mysqli->query("SELECT COUNT(*) AS c FROM users WHERE role = 'veterinary_surgeon' AND is_active = 1")->fetch_assoc()['c'] ?? 0);
?>

<div class="container-fluid px-4 py-3">

    <!-- Header & Breadcrumb -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="fw-bold mb-0 text-dark">Range Details Hub</h2>
                <span class="badge bg-primary px-3 py-2 rounded-pill fw-semibold">HQ-1 Planning Hub</span>
                <span class="badge bg-dark px-3 py-2 rounded-pill fw-normal">45 Ranges (Province-Wide)</span>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Select any operational module below to access comprehensive province-wide data summaries and statistical returns.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 mt-3 mt-md-0">
            <a href="<?= BASE_PATH ?>dashboard.php" class="btn btn-outline-dark btn-sm shadow-sm">
                <i class="bi bi-speedometer2 me-1"></i> Visual Dashboard
            </a>
        </div>
    </div>

    <!-- Global Quick Overview Ribbon -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="p-3 bg-white rounded-3 shadow-sm border-start border-primary border-4 d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted text-uppercase fw-bold">Total Veterinary Ranges</small>
                    <h4 class="fw-bold text-dark mb-0"><?= $total_ranges ?> Ranges</h4>
                </div>
                <i class="bi bi-geo-alt-fill fs-2 text-primary"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 bg-white rounded-3 shadow-sm border-start border-success border-4 d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted text-uppercase fw-bold">Active Operational Status</small>
                    <h4 class="fw-bold text-success mb-0"><?= $active_ranges ?> / <?= $total_ranges ?> Active</h4>
                </div>
                <i class="bi bi-check-circle-fill fs-2 text-success"></i>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 bg-white rounded-3 shadow-sm border-start border-info border-4 d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted text-uppercase fw-bold">Districts Covered</small>
                    <h4 class="fw-bold text-info mb-0">Ampara, Batticaloa, Trincomalee</h4>
                </div>
                <i class="bi bi-building fs-2 text-info"></i>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- RANGE DETAILS 14-BUTTON ACTION GRID (EXACT LAYOUT & COLOR SCHEME)          -->
    <!-- ========================================================================= -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white py-3 px-4 border-0">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap-fill me-2 text-primary"></i>Range Details Operational Modules</h6>
        </div>
        <div class="card-body px-4 pb-4 pt-0">
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-3">
                <div class="col">
                    <a href="range_statistics.php" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #820100; min-height: 105px;">
                        <i class="bi bi-graph-up fs-3 mb-1"></i>
                        <span class="text-center">Range Statistics</span>
                    </a>
                </div>
                <div class="col">
                    <a href="annual_targets.php" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #370709; min-height: 105px;">
                        <i class="bi bi-bar-chart fs-3 mb-1"></i>
                        <span class="text-center">Annual Targets</span>
                    </a>
                </div>
                <div class="col">
                    <a href="monthly-annual-reports.php" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #b08723; min-height: 105px;">
                        <i class="bi bi-car-front-fill fs-3 mb-1"></i>
                        <span class="text-center">Monthly/Annual Reports</span>
                    </a>
                </div>
                <div class="col">
                    <a href="#" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #a07174; min-height: 105px;">
                        <i class="bi bi-file-earmark-plus fs-3 mb-1"></i>
                        <span class="text-center">Regulatory Functions</span>
                    </a>
                </div>
                <div class="col">
                    <a href="#" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #689ccf; min-height: 105px;">
                        <i class="bi bi-gear-fill fs-3 mb-1"></i>
                        <span class="text-center">Animal Health</span>
                    </a>
                </div>
                <div class="col">
                    <a href="#" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #2e7d32; min-height: 105px;">
                        <i class="bi bi-tools fs-3 mb-1"></i>
                        <span class="text-center">Clinical Services</span>
                    </a>
                </div>
                <div class="col">
                    <a href="#" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #e65100; min-height: 105px;">
                        <i class="bi bi-file-earmark-text-fill fs-3 mb-1"></i>
                        <span class="text-center">Animal Breeding</span>
                    </a>
                </div>
                <div class="col">
                    <a href="#" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #455a64; min-height: 105px;">
                        <i class="bi bi-person-bounding-box fs-3 mb-1"></i>
                        <span class="text-center">Livestock Production</span>
                    </a>
                </div>
                <div class="col">
                    <a href="#" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #1565c0; min-height: 105px;">
                        <i class="bi bi-patch-check-fill fs-3 mb-1"></i>
                        <span class="text-center">Dairy Hub</span>
                    </a>
                </div>
                <div class="col">
                    <a href="#" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #00838f; min-height: 105px;">
                        <i class="bi bi-geo-alt-fill fs-3 mb-1"></i>
                        <span class="text-center">Projects</span>
                    </a>
                </div>
                <div class="col">
                    <a href="#" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #283593; min-height: 105px;">
                        <i class="bi bi-folder-fill fs-3 mb-1"></i>
                        <span class="text-center">Monitoring</span>
                    </a>
                </div>
                <div class="col">
                    <a href="#" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #ad1457; min-height: 105px;">
                        <i class="bi bi-bookmark-dash-fill fs-3 mb-1"></i>
                        <span class="text-center">Accounts</span>
                    </a>
                </div>
                <div class="col">
                    <a href="#" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #d84315; min-height: 105px;">
                        <i class="bi bi-graph-up-arrow fs-3 mb-1"></i>
                        <span class="text-center">Clean Sri Lanka</span>
                    </a>
                </div>
                <div class="col">
                    <a href="#" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #37474f; min-height: 105px;">
                        <i class="bi bi-sliders fs-3 mb-1"></i>
                        <span class="text-center">Trainings</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
