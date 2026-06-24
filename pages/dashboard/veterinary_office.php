<?php

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../index.php");
    exit();
}

// full name 
if (!isset($_SESSION['full_name'])) {
    $_SESSION['full_name'] = $_SESSION['username'] ?? 'Veterinary Surgeon';
}

$full_name = $_SESSION['full_name'];

require_once __DIR__ . '/../../config/db_connect.php';

$district_name = 'Your District';
$range_name    = 'Your Assigned Range';

// Ensure we have district_id & range_id
if (empty($_SESSION['district_id'])) {
    $user_query = $mysqli->prepare("SELECT district_id, range_id FROM users WHERE id = ?");
    if ($user_query) {
        $user_query->bind_param("i", $_SESSION['user_id']);
        $user_query->execute();
        $user_result = $user_query->get_result();
        if ($user_result->num_rows > 0) {
            $user_data = $user_result->fetch_assoc();
            $_SESSION['district_id'] = $user_data['district_id'];
            $_SESSION['range_id'] = $user_data['range_id'];
        }
        $user_query->close();
    }
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

// Fetch range name
if (!empty($_SESSION['range_id'])) {
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

// --- DYNAMIC STATISTICS FETCHING FROM DB ---
$stats = [
    'health'     => 0,
    'breeding'   => 0,
    'regulatory' => 4, // Keeping original or placeholder value as requested
    'office'     => 0,
    'projects'   => 0
];

if (!empty($_SESSION['range_id'])) {
    $range_id = $_SESSION['range_id'];

    // 1. Animal Health Total Cases
    $health_q = $mysqli->prepare("SELECT COUNT(*) as total FROM animal_health_records WHERE range_id = ?");
    if ($health_q) {
        $health_q->bind_param("i", $range_id);
        $health_q->execute();
        if ($res = $health_q->get_result()->fetch_assoc()) {
            $stats['health'] = $res['total'];
        }
        $health_q->close();
    }

    // 2. Animal Breeding Progress Logs
    $breeding_q = $mysqli->prepare("SELECT COUNT(*) as total FROM breeding_progress WHERE range_id = ?");
    if ($breeding_q) {
        $breeding_q->bind_param("i", $range_id);
        $breeding_q->execute();
        if ($res = $breeding_q->get_result()->fetch_assoc()) {
            $stats['breeding'] = $res['total'];
        }
        $breeding_q->close();
    }

    // 3. Office Details (Units/Staff Allocated)
    $office_q = $mysqli->prepare("SELECT COUNT(*) as total FROM office_details WHERE range_id = ?");
    if ($office_q) {
        $office_q->bind_param("i", $range_id);
        $office_q->execute();
        if ($res = $office_q->get_result()->fetch_assoc()) {
            $stats['office'] = $res['total'];
        }
        $office_q->close();
    }

    // 4. Projects Progress assigned to this Range
    $projects_q = $mysqli->prepare("SELECT COUNT(*) as total FROM projects_progress WHERE range_id = ?");
    if ($projects_q) {
        $projects_q->bind_param("i", $range_id);
        $projects_q->execute();
        if ($res = $projects_q->get_result()->fetch_assoc()) {
            $stats['projects'] = $res['total'];
        }
        $projects_q->close();
    }
}

require_once __DIR__ . '/../../config/constants.php';
?>

<style>
    .nav-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.25rem 1.5rem;
        border-radius: 12px;
        text-decoration: none;
        color: inherit;
        border: 1px solid #e5e7eb;
        background: #fff;
        transition: background 0.2s, border-color 0.2s;
    }

    .nav-card:hover {
        color: #fff !important;
    }

    /* Updated to requested #370709 color rule */
    .nav-card-range:hover {
        background: #370709 !important;
        border-color: #370709 !important;
    }

    /* Updated to requested #a07174 color rule */
    .nav-card-office:hover {
        background: #a07174 !important;
        border-color: #a07174 !important;
    }

    .nav-card-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
        transition: background 0.2s, color 0.2s;
    }

    .nav-card-icon-blue {
        background: #fdf2f2;
        color: #370709;
    }

    .nav-card-icon-red {
        background: #fdf4f5;
        color: #a07174;
    }

    .nav-card:hover .nav-card-icon {
        background: rgba(255, 255, 255, 0.15) !important;
        color: #fff !important;
    }

    .nav-card-title {
        font-size: 15px;
        font-weight: 600;
        color: #111;
        margin: 0;
        transition: color 0.2s;
    }

    .nav-card-sub {
        font-size: 12px;
        color: #6b7280;
        margin: 2px 0 0;
        transition: color 0.2s;
    }

    .nav-card:hover .nav-card-title,
    .nav-card:hover .nav-card-sub {
        color: #fff !important;
    }

    .nav-card:hover .nav-card-sub {
        opacity: 0.75;
    }

    .nav-card-arrow {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
        font-size: 13px;
        flex-shrink: 0;
        transition: background 0.2s, color 0.2s;
    }

    .nav-card:hover .nav-card-arrow {
        background: rgba(255, 255, 255, 0.2) !important;
        color: #fff !important;
    }
</style>

<div class="content-wrapper">
    <div class="container-fluid py-4">

        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h4 style="color: #370709; text-align: left;" class="mb-1 fw-bold">Veterinary Office Dashboard</h4>
                            <p class="mb-0 text-secondary">
                                <span class="text-dark fw-semibold"><i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($full_name) ?></span>
                                <span class="mx-2 text-muted">|</span>
                                <small>District: <strong class="text-dark"><?= htmlspecialchars($district_name) ?></strong></small>
                                <span class="mx-2 text-muted">|</span>
                                <small>Range: <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong></small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <a href="range_details.php" class="nav-card nav-card-range shadow-sm">
                    <div class="d-flex align-items-center gap-3">
                        <div class="nav-card-icon nav-card-icon-blue">
                            <i class="bi bi-map-fill"></i>
                        </div>
                        <div>
                            <p class="nav-card-title">View Range Details</p>
                            <p class="nav-card-sub">Geographic zones, production hubs & field maps</p>
                        </div>
                    </div>
                    <div class="nav-card-arrow">
                        <i class="bi bi-chevron-right"></i>
                    </div>
                </a>
            </div>
            <div class="col-md-6">
                <a href="<?= $base_path ?>pages/modules/veterinary/office_details.php" class="nav-card nav-card-office shadow-sm">
                    <div class="d-flex align-items-center gap-3">
                        <div class="nav-card-icon nav-card-icon-red">
                            <i class="bi bi-building-fill"></i>
                        </div>
                        <div>
                            <p class="nav-card-title">View Office Details</p>
                            <p class="nav-card-sub">Staff allocations, equipment logs & inventory</p>
                        </div>
                    </div>
                    <div class="nav-card-arrow">
                        <i class="bi bi-chevron-right"></i>
                    </div>
                </a>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3 font-semibold tracking-wider">Animal Health</h6>
                    <h2 class="text-primary mb-2 fw-bold"><?= $stats['health'] ?></h2>
                    <small class="text-success fw-medium"><i class="bi bi-arrow-up"></i> Total entries</small>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3 font-semibold tracking-wider">Animal Breeding</h6>
                    <h2 class="text-warning mb-2 fw-bold"><?= $stats['breeding'] ?></h2>
                    <small class="text-success fw-medium"><i class="bi bi-arrow-up"></i> Total entries</small>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3 font-semibold tracking-wider">Regulatory Functions</h6>
                    <h2 class="text-danger mb-2 fw-bold"><?= $stats['regulatory'] ?></h2>
                    <small class="text-danger fw-medium"><i class="bi bi-arrow-down"></i> Active records</small>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 col-sm-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3 font-semibold tracking-wider">Office Details</h6>
                    <h2 class="text-info mb-2 fw-bold"><?= $stats['office'] ?></h2>
                    <small class="text-success fw-medium"><i class="bi bi-arrow-up"></i> Units registered</small>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 col-sm-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3 font-semibold tracking-wider">Projects</h6>
                    <h2 class="text-secondary mb-2 fw-bold"><?= $stats['projects'] ?></h2>
                    <small class="text-success fw-medium"><i class="bi bi-briefcase-fill"></i> Ongoing initiatives</small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>