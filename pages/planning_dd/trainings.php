<?php
// pages/planning_dd/trainings.php
// Farmer Training & Extension Master Summary (Province-Wide)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_roles = ['deputy_director_hq_1', 'administrator', 'provincial_director'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied. Unauthorized role footprint.");
}

require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../includes/header.php';

// Global Data Fetch
$sql = "
    SELECT 
        tap.*,
        vr.name AS range_name,
        d.name AS district_name
    FROM training_advanced_programmes tap
    LEFT JOIN veterinary_ranges vr ON tap.range_id = vr.id
    LEFT JOIN districts d ON vr.district_id = d.id
    ORDER BY tap.id DESC
";
$trainings = $mysqli->query($sql)->fetch_all(MYSQLI_ASSOC);

$total_sessions = count($trainings);
$total_target_participants = array_sum(array_column($trainings, 'target_participants'));
$total_actual_participants = array_sum(array_column($trainings, 'actual_participants'));
?>

<div class="container-fluid px-4 py-3">

    <!-- Header & Breadcrumb -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="range_details.php" class="btn btn-sm btn-outline-secondary rounded-circle" title="Back to Hub">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="fw-bold mb-0 text-dark">Trainings Summary</h2>
                <span class="badge text-white px-3 py-2 rounded-pill" style="background-color: #37474f;">Trainings Module</span>
                <span class="badge bg-dark px-3 py-2 rounded-pill">Province-Wide Scope</span>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Farmer capacity building sessions, extension awareness programs, and training metrics across all 45 Ranges.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 mt-3 mt-md-0">
            <a href="range_details.php" class="btn btn-outline-secondary btn-sm shadow-sm">
                <i class="bi bi-grid-3x3-gap-fill me-1"></i> Range Details Hub
            </a>
            <button type="button" class="btn btn-dark btn-sm shadow-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print
            </button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white" style="border-color: #37474f !important;">
                <small class="text-muted text-uppercase fw-bold">Training Sessions Conducted</small>
                <h3 class="fw-bold text-dark mb-0 mt-1"><?= $total_sessions ?> <small class="fs-6 text-muted">Sessions</small></h3>
                <small class="text-muted">Farmer training & technology transfer</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white border-success">
                <small class="text-muted text-uppercase fw-bold">Farmers & Officers Trained</small>
                <h3 class="fw-bold text-success mb-0 mt-1"><?= number_format($total_actual_participants) ?> <small class="fs-6 text-muted">Participants</small></h3>
                <small class="text-muted">Direct beneficiaries of extension programs</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white border-primary">
                <small class="text-muted text-uppercase fw-bold">Targeted Participation</small>
                <h3 class="fw-bold text-primary mb-0 mt-1"><?= number_format($total_target_participants) ?> <small class="fs-6 text-muted">Target</small></h3>
                <small class="text-muted"><?= round(($total_actual_participants / max($total_target_participants, 1)) * 100) ?>% Target Achievement</small>
            </div>
        </div>
    </div>

    <!-- Master Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-sliders me-2" style="color: #37474f;"></i>Farmer Training Programs Directory</h5>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="table-responsive">
                <table id="trainingsTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Program Title</th>
                            <th>Range / Location</th>
                            <th>District</th>
                            <th>Target Group</th>
                            <th class="text-center">Target</th>
                            <th class="text-center">Actual Attendees</th>
                            <th>Trainer / Officer</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trainings as $tr): 
                            $dist_badge = ($tr['district_name'] === 'Ampara') ? 'bg-primary' : (($tr['district_name'] === 'Batticaloa') ? 'bg-success' : 'bg-warning text-dark');
                        ?>
                            <tr>
                                <td><strong class="text-dark"><?= htmlspecialchars($tr['programme_title'] ?? $tr['title'] ?? 'Dairy Farming Extension') ?></strong></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($tr['range_name'] ?: 'Range Training Center') ?></span></td>
                                <td><span class="badge <?= $dist_badge ?> bg-opacity-75 rounded-pill px-2 py-1"><?= htmlspecialchars($tr['district_name'] ?: 'Eastern Province') ?></span></td>
                                <td class="small text-muted"><?= htmlspecialchars($tr['target_group'] ?? 'Dairy Cattle Farmers') ?></td>
                                <td class="text-center font-monospace"><?= number_format($tr['target_participants'] ?? 0) ?></td>
                                <td class="text-center font-monospace fw-bold text-success"><?= number_format($tr['actual_participants'] ?? 0) ?></td>
                                <td class="small fw-semibold"><?= htmlspecialchars($tr['resource_person'] ?? 'Veterinary Surgeon / LDO') ?></td>
                                <td class="text-center"><span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1">Completed</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#trainingsTable').DataTable({
        pageLength: 15,
        dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-3"Bf>rt<"d-flex flex-wrap justify-content-between align-items-center mt-3"ip>',
        buttons: [
            { extend: 'csv', text: '<i class="bi bi-filetype-csv me-1"></i> Export CSV', className: 'btn btn-sm btn-success rounded-pill me-2' },
            { extend: 'print', text: '<i class="bi bi-printer me-1"></i> Print', className: 'btn btn-sm btn-dark rounded-pill' }
        ]
    });
});
</script>
