<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

if (!isset($_SESSION['full_name'])) {
    $_SESSION['full_name'] = $_SESSION['username'] ?? 'Veterinary Surgeon';
}

$full_name   = $_SESSION['full_name'];
$range_id    = $_SESSION['range_id'] ?? null;
$district_id = $_SESSION['district_id'] ?? null;

if (empty($range_id)) {
    die('<div class="alert alert-danger text-center p-5 m-5">Error: Your account is not assigned to any Veterinary Range.</div>');
}

require_once '../../../config/db_connect.php';

$district_name = 'Unknown District';
$range_name    = 'Unknown Range';

// Fetch District and Range Names
if ($district_id) {
    $stmt = $mysqli->prepare("SELECT name FROM districts WHERE id = ?");
    $stmt->bind_param("i", $district_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $district_name = $row['name'];
    }
    $stmt->close();
}

if ($range_id) {
    $stmt = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
    $stmt->bind_param("i", $range_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $range_name = $row['name'];
    }
    $stmt->close();
}

$sanitation_tasks = [
    [
        'area' => 'Trincomalee Public Slaughterhouse',
        'type' => 'Bio-Waste Disposal',
        'last_cleaned' => '2026-04-03',
        'officer' => 'Public Health Inspector (PHI)',
        'status' => 'Sanitized',
        'score' => 95
    ],
    [
        'area' => 'Kantalai Dairy Collection Center',
        'type' => 'Waste Water Management',
        'last_cleaned' => '2026-04-01',
        'officer' => 'Range Assistant',
        'status' => 'Pending Inspection',
        'score' => 70
    ],
    [
        'area' => 'Kinniya Livestock Market',
        'type' => 'General Sanitation',
        'last_cleaned' => '2026-03-25',
        'officer' => 'Municipal Team',
        'status' => 'Needs Attention',
        'score' => 45
    ]
];

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">


<div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-success"><i class="bi bi-leaf me-2"></i>Clean Sri Lanka Initiative</h4>
            <p class="text-muted small">Waste Management & Sanitation Tracking - Eastern Province</p>
        </div>
        <button class="btn btn-success btn-sm shadow-sm">
            <i class="bi bi-plus-circle me-2"></i>New Inspection
        </button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="text-muted small fw-bold text-uppercase">Hygiene Compliance</div>
                <h2 class="text-success fw-bold">88%</h2>
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar bg-success" style="width: 88%"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="text-muted small fw-bold text-uppercase">Pending Actions</div>
                <h2 class="text-warning fw-bold">05</h2>
                <small class="text-muted">Targeting slaughterhouses & markets</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="text-muted small fw-bold text-uppercase">Waste Removed (MT)</div>
                <h2 class="text-primary fw-bold">1.2</h2>
                <small class="text-muted">Total bio-waste processed this month</small>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <?php foreach ($sanitation_tasks as $task): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="badge bg-light text-success border border-success"><?= $task['type'] ?></span>
                        <div class="dropdown">
                            <i class="bi bi-three-dots-vertical cursor-pointer" data-bs-toggle="dropdown"></i>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item small" href="#">Edit Record</a></li>
                                <li><a class="dropdown-item small text-danger" href="#">Flag Issue</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <h6 class="fw-bold mb-1"><?= $task['area'] ?></h6>
                    <p class="text-muted small mb-3"><i class="bi bi-person me-1"></i> Responsible: <?= $task['officer'] ?></p>
                    
                    <div class="d-flex justify-content-between align-items-end mt-4">
                        <div>
                            <div class="text-muted x-small text-uppercase">Last Activity</div>
                            <div class="small fw-bold"><?= date('d M, Y', strtotime($task['last_cleaned'])) ?></div>
                        </div>
                        <div class="text-end">
                            <div class="small fw-bold mb-1">Hygiene Score</div>
                            <span class="badge <?= ($task['score'] > 80) ? 'bg-success' : (($task['score'] > 50) ? 'bg-warning text-dark' : 'bg-danger') ?>">
                                <?= $task['score'] ?>/100
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top-0 p-3">
                    <button class="btn btn-sm btn-outline-success w-100"><?= $task['status'] ?></button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>