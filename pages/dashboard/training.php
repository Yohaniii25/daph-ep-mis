<?php
// pages/dashboard/training.php
if ($_SESSION['role'] !== 'training_officer') die("Access denied");
require_once './includes/header.php';
require_once './includes/sidebar.php';
require_once './config/db_connect.php';

$current_training_center = null;
$current_center_id = $_SESSION['training_center_id'] ?? null;
$current_center_location = $_SESSION['training_center_location'] ?? null;

if (!empty($current_center_id)) {
    $training_center_stmt = $mysqli->prepare("SELECT id, center_name, location FROM training_centers WHERE id = ? AND is_active = 1 LIMIT 1");
    if ($training_center_stmt) {
        $training_center_stmt->bind_param("i", $current_center_id);
        $training_center_stmt->execute();
        $training_center_result = $training_center_stmt->get_result();
        if ($training_center_result && $training_center_result->num_rows > 0) {
            $current_training_center = $training_center_result->fetch_assoc();
        }
        $training_center_stmt->close();
    }
} elseif (!empty($current_center_location)) {
    $training_center_stmt = $mysqli->prepare("SELECT id, center_name, location FROM training_centers WHERE location = ? AND is_active = 1 ORDER BY center_name ASC LIMIT 1");
    if ($training_center_stmt) {
        $training_center_stmt->bind_param("s", $current_center_location);
        $training_center_stmt->execute();
        $training_center_result = $training_center_stmt->get_result();
        if ($training_center_result && $training_center_result->num_rows > 0) {
            $current_training_center = $training_center_result->fetch_assoc();
        }
        $training_center_stmt->close();
    }
}
?>


<div id="layoutSidenav_content">
    <main class="container-fluid px-4">
        <h2 class="mt-4 mb-3 text-black fw-normal">
            <?= !empty($current_training_center) ? htmlspecialchars($current_training_center['center_name']) . ' Dashboard' : 'Training Center Dashboard' ?>
        </h2>

        <?php if (!empty($current_training_center)): ?>
            <div class="alert alert-info mb-4">
                <strong>Logged training center:</strong>
                <?= htmlspecialchars($current_training_center['center_name']) ?>
                - <?= htmlspecialchars($current_training_center['location']) ?>
            </div>
        <?php endif; ?>

        <!-- 4 Cards -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="text-muted mb-3">Total Trainings This Month</h6>
                        <h2 class="text-primary mb-2">08</h2>
                        <small class="text-success"><i class="bi bi-arrow-up"></i> 8.5% Up from yesterday</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="text-muted mb-3">Farmers Trained</h6>
                        <h2 class="text-danger mb-2">14</h2>
                        <small class="text-success"><i class="bi bi-arrow-up"></i> 1.3% Up from past week</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="text-muted mb-3">Ongoing Trainings</h6>
                        <h2 class="text-warning mb-2">14</h2>
                        <small class="text-danger"><i class="bi bi-arrow-down"></i> 4.3% Down from yesterday</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="text-muted mb-3">Total Production</h6>
                        <h2 class="text-success mb-2">80%</h2>
                        <small class="text-success"><i class="bi bi-arrow-up"></i> 1.8% Up from yesterday</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-muted small text-uppercase"><i class="bi bi-lightning-charge me-2 text-warning"></i>Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <a href="<?= BASE_PATH ?>pages/modules/training/training_details.php" class="btn btn-success w-100 py-3 shadow-sm border-0 text-white d-block">
                            <i style="color: white;" class="bi bi-people fs-4"></i><br>
                            <span style="color:white">Training Details</span>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="<?= BASE_PATH ?>pages/modules/training/other_facilities.php" style="background-color: #b08723;" class="btn btn-primary w-100 py-3 shadow-sm border-0 text-white d-block">
                            <i style="color: white;" class="bi bi-shield-check fs-4"></i><br>
                            <span style="color:white">Other Facilities</span>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="<?= BASE_PATH ?>pages/modules/training/production.php" style="background-color: #689ccf;" class="btn btn-info w-100 py-3 shadow-sm border-0 text-white d-block">
                            <i style="color: white;" class="bi bi-box fs-4"></i><br>
                            <span style="color:white">Production</span>
                        </a>
                    </div>

                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">

            <!-- SECTION 1: TRAINING PROGRAMS METRICS -->
            <div class="col-12 col-xl-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-primary bg-opacity-10 text-primary p-2 rounded-3">
                                    <i class="bi bi-people-fill fs-6"></i>
                                </span>
                                <h5 class="m-0 text-dark fw-bold tracking-tight">Activities on the Training of Farmers</h5>
                            </div>
                            <p class="text-muted small mb-4">Target group distributions &amp; active completion rates</p>
                        </div>

                        <div class="row align-items-center my-auto g-4">
                            <!-- Visual Chart Representation with Entry Load Animation -->
                            <div class="col-12 col-sm-5 d-flex justify-content-center">
                                <div class="position-relative d-inline-flex align-items-center justify-content-center">

                                    <!-- Embedded Inline CSS for Chart Loading Behavior -->
                                    <style>
                                        @keyframes chartDraw {
                                            to {
                                                stroke-dashoffset: 0;
                                            }
                                        }

                                        .animated-ring {
                                            transform: rotate(-90deg);
                                            transform-origin: center;
                                        }

                                        .segment-completed {
                                            stroke-dasharray: 65, 100;
                                            stroke-dashoffset: 65;
                                            animation: chartDraw 1.2s cubic-bezier(0.4, 0, 0.2, 1) forwards;
                                        }

                                        .segment-ongoing {
                                            stroke-dasharray: 35, 100;
                                            stroke-dashoffset: 35;
                                            animation: chartDraw 0.8s cubic-bezier(0.4, 0, 0.2, 1) 0.4s forwards;
                                        }

                                        .animated-fade-in {
                                            opacity: 0;
                                            animation: fadeInText 0.6s ease-out 1.2s forwards;
                                        }

                                        @keyframes fadeInText {
                                            to {
                                                opacity: 1;
                                            }
                                        }
                                    </style>

                                    <svg width="170" height="170" viewBox="0 0 36 36" class="animated-ring">
                                        <circle cx="18" cy="18" r="15.915" fill="none" stroke="#f0f2f5" stroke-width="3.5"></circle>

                                        <circle class="segment-completed" cx="18" cy="18" r="15.915" fill="none" stroke="#370709" stroke-width="3.5" stroke-linecap="round"></circle>

                                        <circle class="segment-ongoing" cx="18" cy="18" r="15.915" fill="none" stroke="#ffc107" stroke-width="3.5" stroke-dasharray="35 100" transform="rotate(234 18 18)" stroke-linecap="round"></circle>
                                    </svg>
                                    <div class="position-absolute text-center animated-fade-in">
                                        <span class="d-block fs-2 fw-bold text-dark lh-1">65%</span>
                                        <small class="text-muted text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.8px;">Complete</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Legend & Micro Metrics Context -->
                            <div class="col-12 col-sm-7">
                                <div class="d-flex flex-column gap-3">
                                    <div style="border-left-color: #370709 !important;" class="p-3 bg-light rounded-3 border-start border-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="small fw-bold text-secondary">Completed Allocations</span>
                                            <span style="background-color: #370709; color: #ffffff;" class="badge rounded-pill font-monospace">26 Programs</span>
                                        </div>
                                        <div class="small text-muted">Ampara Range Operators <span class="font-monospace text-dark fw-semibold small">(TG-041)</span> &amp; historical runs.</div>
                                    </div>

                                    <div class="p-3 bg-light rounded-3 border-start border-3 border-warning">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="small fw-bold text-secondary">Ongoing / Active Sessions</span>
                                            <span class="badge bg-warning text-dark rounded-pill font-monospace">14 Programs</span>
                                        </div>
                                        <div class="small text-muted">Trincomalee Poultry Holders <span class="font-monospace text-dark fw-semibold small">(TG-098)</span> active at Main Center.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-top border-light d-flex justify-content-between align-items-center mt-4">
                            <span class="text-muted small"><i class="bi bi-clock-history me-1"></i> Total regional participants recorded:</span>
                            <span class="fw-bold text-dark font-monospace bg-light px-2.5 py-1 rounded border">65 Farmers</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-5 d-flex flex-column gap-4">

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-success bg-opacity-10 text-success p-2 rounded-3">
                                <i class="bi bi-building fs-6"></i>
                            </span>
                            <h5 class="m-0 text-dark fw-bold tracking-tight">Facilities &amp; Accommodations Availability</h5>
                        </div>
                        <p class="text-muted small mb-4">Live operational status across training assets</p>

                        <div class="d-flex flex-column gap-4">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small fw-bold text-dark"><i class="bi bi-window-sidebar me-2 text-muted"></i>Training Halls Utilization</span>
                                    <span class="small text-muted fw-medium">1 of 2 Available</span>
                                </div>
                                <div class="progress rounded-pill shadow-inner" style="height: 10px; background-color: #f0f2f5;">
                                    <div class="progress-bar bg-danger rounded-pill" role="progressbar" style="width: 50%;" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="d-flex justify-content-between mt-1.5" style="font-size: 11px;">
                                    <span class="text-danger fw-medium"><i class="bi bi-dot"></i> Hall 01: Occupied (Vet Review)</span>
                                    <span class="text-success fw-medium"><i class="bi bi-dot"></i> Hall 02: Ready</span>
                                </div>
                            </div>

                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small fw-bold text-dark"><i class="bi bi-moon-stars me-2 text-muted"></i>Accommodation Availability</span>
                                    <span class="small text-muted fw-medium">12 Beds Active Stay</span>
                                </div>
                                <div class="progress rounded-pill shadow-inner" style="height: 10px; background-color: #f0f2f5;">
                                    <div class="progress-bar bg-warning rounded-pill" role="progressbar" style="width: 40%;" ariavaluenow="40" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="mt-1.5" style="font-size: 11px;">
                                    <span class="text-warning fw-medium"><i class="bi bi-info-circle-fill me-1"></i> Partial vacancy remains for incoming trainee allocations</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-teal-custom text-teal-custom p-2 rounded-3" style="background-color: rgba(20, 184, 166, 0.1); color: #14b8a6;">
                                <i class="bi bi-tree fs-6"></i>
                            </span>
                            <h5 class="m-0 text-dark fw-bold tracking-tight">Total Production &amp; Yield Volumes</h5>
                        </div>
                        <p class="text-muted small mb-4">Comparative run matrix across agricultural output sectors</p>

                        <div class="d-flex flex-column gap-3">
                            <div>
                                <div class="d-flex justify-content-between text-xs align-items-center mb-1">
                                    <span class="fw-semibold text-secondary" style="font-size:12px;">Pasture Run (CO-3 Grass)</span>
                                    <span class="font-monospace fw-bold text-success" style="font-size:12px;">1,200 Kg</span>
                                </div>
                                <div class="progress rounded-2" style="height: 8px; background-color: #f0f2f5;">
                                    <div class="progress-bar bg-success rounded-2" role="progressbar" style="width: 70%;" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>

                            <div>
                                <div class="d-flex justify-content-between text-xs align-items-center mb-1">
                                    <span class="fw-semibold text-secondary" style="font-size:12px;">Fruits (Passion Fruit Harvest)</span>
                                    <span class="font-monospace fw-bold text-success" style="font-size:12px;">450 Kg</span>
                                </div>
                                <div class="progress rounded-2" style="height: 8px; background-color: #f0f2f5;">
                                    <div class="progress-bar bg-warning rounded-2" role="progressbar" style="width: 35%;" aria-valuenow="35" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>

                            <div>
                                <div class="d-flex justify-content-between text-xs align-items-center mb-1">
                                    <span class="fw-semibold text-secondary" style="font-size:12px;">Other Yield (Silage Feed Packs)</span>
                                    <span class="font-monospace fw-bold text-success" style="font-size:12px;">80 Packs</span>
                                </div>
                                <div class="progress rounded-2" style="height: 8px; background-color: #f0f2f5;">
                                    <div class="progress-bar bg-info rounded-2" role="progressbar" style="width: 20%;" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>



<?php require_once './includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>

</script>