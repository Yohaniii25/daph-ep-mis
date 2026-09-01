<?php
// pages/planning_dd/animal_health.php
// Animal Health Master Summary (Province-Wide)

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
        ahr.*,
        vr.name AS range_name,
        vr.code AS range_code,
        d.name AS district_name,
        u.full_name AS reported_by_name
    FROM animal_health_records ahr
    JOIN veterinary_ranges vr ON ahr.range_id = vr.id
    JOIN districts d ON vr.district_id = d.id
    LEFT JOIN users u ON ahr.created_by = u.id
    ORDER BY ahr.date DESC, ahr.id DESC
";
$records = $mysqli->query($sql)->fetch_all(MYSQLI_ASSOC);

$total_reports = count($records);
$total_occurrences = array_sum(array_column($records, 'occurrence_count'));
$total_doses = array_sum(array_column($records, 'doses'));
?>

<div class="container-fluid px-4 py-3">

    <!-- Header & Breadcrumb -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="range_details.php" class="btn btn-sm btn-outline-secondary rounded-circle" title="Back to Hub">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="fw-bold mb-0 text-dark">Animal Health Summary</h2>
                <span class="badge text-white px-3 py-2 rounded-pill" style="background-color: #4a7bb0;">Animal Health Module</span>
                <span class="badge bg-dark px-3 py-2 rounded-pill">Province-Wide Scope</span>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Global disease surveillance, outbreak incidence logs, and field vaccination containment across all 45 Ranges.
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
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white" style="border-color: #4a7bb0 !important;">
                <small class="text-muted text-uppercase fw-bold">Total Incident Reports</small>
                <h3 class="fw-bold text-dark mb-0 mt-1"><?= $total_reports ?> <small class="fs-6 text-muted">Incidents</small></h3>
                <small class="text-muted">Logged across all 45 veterinary ranges</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white border-danger">
                <small class="text-muted text-uppercase fw-bold">Total Affected Animals</small>
                <h3 class="fw-bold text-danger mb-0 mt-1"><?= number_format($total_occurrences) ?> <small class="fs-6 text-muted">Livestock</small></h3>
                <small class="text-muted">Total head count requiring veterinary intervention</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white border-success">
                <small class="text-muted text-uppercase fw-bold">Clinical Vaccine Doses</small>
                <h3 class="fw-bold text-success mb-0 mt-1"><?= number_format($total_doses) ?> <small class="fs-6 text-muted">doses</small></h3>
                <small class="text-muted">Administered for disease ring containment</small>
            </div>
        </div>
    </div>

    <!-- Master Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-gear-fill me-2" style="color: #4a7bb0;"></i>Disease Outbreak & Incident Records</h5>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="table-responsive">
                <table id="healthTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Range Name</th>
                            <th>District</th>
                            <th>Farmer Reg</th>
                            <th>Species</th>
                            <th>Disease / Condition</th>
                            <th class="text-center">Occurrences</th>
                            <th>Vaccine</th>
                            <th class="text-center">Doses</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $r): 
                            $dist_badge = ($r['district_name'] === 'Ampara') ? 'bg-primary' : (($r['district_name'] === 'Batticaloa') ? 'bg-success' : 'bg-warning text-dark');
                        ?>
                            <tr>
                                <td class="fw-semibold text-secondary"><?= htmlspecialchars($r['date']) ?></td>
                                <td><strong class="text-dark"><?= htmlspecialchars($r['range_name']) ?></strong></td>
                                <td><span class="badge <?= $dist_badge ?> bg-opacity-75 rounded-pill px-2 py-1"><?= htmlspecialchars($r['district_name']) ?></span></td>
                                <td class="font-monospace small"><?= htmlspecialchars($r['farmer_reg_no'] ?: 'N/A') ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['animal_type']) ?></span></td>
                                <td class="fw-bold text-danger"><?= htmlspecialchars($r['disease_name']) ?></td>
                                <td class="text-center font-monospace fw-bold text-dark"><?= number_format($r['occurrence_count']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($r['vaccine_name'] ?: 'None') ?></td>
                                <td class="text-center font-monospace"><?= number_format($r['doses']) ?></td>
                                <td><span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1"><?= htmlspecialchars($r['report_status'] ?: 'Submitted') ?></span></td>
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
    $('#healthTable').DataTable({
        pageLength: 15,
        dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-3"Bf>rt<"d-flex flex-wrap justify-content-between align-items-center mt-3"ip>',
        buttons: [
            { extend: 'csv', text: '<i class="bi bi-filetype-csv me-1"></i> Export CSV', className: 'btn btn-sm btn-success rounded-pill me-2' },
            { extend: 'print', text: '<i class="bi bi-printer me-1"></i> Print', className: 'btn btn-sm btn-dark rounded-pill' }
        ]
    });
});
</script>
