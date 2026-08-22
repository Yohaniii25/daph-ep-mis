<?php
// pages/modules/district/subject_matter_specialists.php -> Subject Matter Specialist (Under Construction)
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
?>

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4 pb-5">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <div>
                <h2 class="text-dark fw-bold mb-0">Subject Matter Specialists (SMS) Summary</h2>
                <p class="text-muted small mb-0">Technical oversight, immunization campaigns &amp; epidemiology control for <?= htmlspecialchars($district_name) ?> District.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="../../../dashboard.php" class="btn btn-secondary shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Under Construction Card -->
        <div class="row justify-content-center my-4">
            <div class="col-lg-8 col-xl-7">
                <div class="card border-0 shadow-sm rounded-4 text-center p-5 bg-white position-relative overflow-hidden">
                    
                    <!-- Decorative Top Bar -->
                    <div class="position-absolute top-0 start-0 w-100" style="height: 5px; background: linear-gradient(90deg, #c6aa4b, #6B0F1A, #c6aa4b);"></div>

                    <!-- Icon Container -->
                    <div class="mb-4">
                        <div class="bg-warning bg-opacity-10 text-warning d-inline-flex align-items-center justify-content-center rounded-circle p-4 shadow-sm" style="width: 110px; height: 110px;">
                            <i class="bi bi-tools fs-1 text-dark"></i>
                        </div>
                    </div>

                    <!-- Title & Badge -->
                    <div>
                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill font-monospace mb-3 text-uppercase fw-bold">
                            <i class="bi bi-cone-striped me-1"></i> Under Active Development
                        </span>
                        <h3 class="fw-bold text-dark mb-2">Module Under Construction</h3>
                        <p class="text-muted fs-6 mb-4 mx-auto" style="max-width: 520px;">
                            The <strong>Subject Matter Specialist (SMS)</strong> module for <strong><?= htmlspecialchars($district_name) ?> District</strong> is currently being refined to integrate advanced epidemiological reporting and campaign analytics.
                        </p>
                    </div>

                    <!-- Upcoming Features Preview -->
                    <div class="bg-light rounded-3 p-4 mb-4 text-start border">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-stars text-warning me-2"></i>Upcoming Module Features:</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-check-circle-fill text-success mt-1"></i>
                                    <div>
                                        <div class="fw-semibold small text-dark">District Immunization Progress</div>
                                        <small class="text-muted">Target vs. achieved vaccination statistics</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-check-circle-fill text-success mt-1"></i>
                                    <div>
                                        <div class="fw-semibold small text-dark">Epidemiology &amp; Outbreaks</div>
                                        <small class="text-muted">Disease surveillance and alert logging</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-check-circle-fill text-success mt-1"></i>
                                    <div>
                                        <div class="fw-semibold small text-dark">Mobile Clinical Operations</div>
                                        <small class="text-muted">Specialized veterinary clinic scheduling</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-check-circle-fill text-success mt-1"></i>
                                    <div>
                                        <div class="fw-semibold small text-dark">SMS Field Consultations</div>
                                        <small class="text-muted">Technical guidance records for VS ranges</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Action Buttons -->
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <a href="../../../dashboard.php" class="btn btn-primary px-4 py-2 shadow-sm">
                            <i class="bi bi-speedometer2 me-1"></i> Return to Dashboard
                        </a>
                        <a href="range_veterinary_officers.php" class="btn btn-outline-secondary px-3 py-2">
                            <i class="bi bi-people me-1"></i> Range Veterinary Officers
                        </a>
                        <a href="users_summary.php" class="btn btn-outline-dark px-3 py-2">
                            <i class="bi bi-person-lines-fill me-1"></i> Users Summary
                        </a>
                    </div>

                </div>
            </div>
        </div>

    </main>
</div>

<?php require_once '../../../includes/footer.php'; ?>
