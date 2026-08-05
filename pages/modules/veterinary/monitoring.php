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

$monitoring_feed = [
    [
        'date' => '2026-04-03',
        'project' => 'LMP Dairy Development',
        'officer' => 'Mr. K. Perera (LDO)',
        'location' => 'Kantalai - Unit 04',
        'observation' => 'Cattle shed roof completed. 10 cross-bred cows arrived. Health check passed.',
        'status' => 'On Track',
        'progress' => 85
    ],
    [
        'date' => '2026-04-01',
        'project' => 'PSDG Poultry Grant',
        'officer' => 'Dr. S. Mohamed (VS)',
        'location' => 'Trincomalee Town',
        'observation' => 'Delay in feed supply from contractor. Chicks showing signs of stress.',
        'status' => 'Delayed',
        'progress' => 40
    ],
    [
        'date' => '2026-03-28',
        'project' => 'Anti-Rabies Campaign',
        'officer' => 'Mr. A. Bandara',
        'location' => 'Kinniya Range',
        'observation' => 'Door-to-door vaccination completed in Zone A. 450 dogs vaccinated.',
        'status' => 'Completed',
        'progress' => 100
    ]
];

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">


        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0 text-dark">Field Monitoring & Inspections</h4>
                <p class="text-muted small">Real-time activity tracking across the Veterinary Range</p>
            </div>
            <div>
                <button class="btn btn-dark btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#addVisitModal">
                    <i class="bi bi-plus-lg me-2"></i>Record New Visit
                </button>
            </div>
        </div>


        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>Recent Inspection Log</h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($monitoring_feed as $log): ?>
                        <div class="mb-4 pb-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge bg-light text-dark border me-2"><?= $log['date'] ?></span>
                                    <span class="fw-bold text-primary"><?= $log['project'] ?></span>
                                </div>
                                <?php
                                $badge = ($log['status'] == 'On Track') ? 'bg-success' : (($log['status'] == 'Completed') ? 'bg-info' : 'bg-danger');
                                ?>
                                <span class="badge rounded-pill <?= $badge ?> px-3 small"><?= $log['status'] ?></span>
                            </div>

                            <div class="row">
                                <div class="col-md-8">
                                    <p class="mb-1 text-dark small fw-medium">
                                        <i class="bi bi-geo-alt me-1 text-danger"></i> <?= $log['location'] ?>
                                        <span class="mx-2 text-muted">|</span>
                                        <i class="bi bi-person-badge me-1"></i> <?= $log['officer'] ?>
                                    </p>
                                    <p class="text-muted small italic">"<?= $log['observation'] ?>"</p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="small fw-bold mb-1">Project Progress: <?= $log['progress'] ?>%</div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar <?= ($log['progress'] == 100) ? 'bg-info' : 'bg-success' ?>" style="width: <?= $log['progress'] ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card-footer bg-white text-center">
                <button class="btn btn-link btn-sm text-decoration-none">View All Historical Logs</button>
            </div>
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