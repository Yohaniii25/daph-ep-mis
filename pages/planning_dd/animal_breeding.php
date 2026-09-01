<?php
// pages/planning_dd/animal_breeding.php
// Animal Breeding Master Summary (Province-Wide)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_roles = ['deputy_director_hq_1', 'administrator', 'provincial_director'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied. Unauthorized role footprint.");
}

require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../includes/header.php';

// Global Data Fetch (A.I. Performance)
$ai_sql = "
    SELECT 
        ai.*,
        vr.name AS range_name,
        vr.code AS range_code,
        d.name AS district_name
    FROM breeding_ai_performance ai
    JOIN veterinary_ranges vr ON ai.range_id = vr.id
    JOIN districts d ON vr.district_id = d.id
    ORDER BY ai.ai_date DESC, ai.id DESC
";
$ai_records = $mysqli->query($ai_sql)->fetch_all(MYSQLI_ASSOC);

// Calving
$calving_sql = "
    SELECT 
        cp.*,
        vr.name AS range_name,
        d.name AS district_name
    FROM breeding_calving_performance cp
    JOIN veterinary_ranges vr ON cp.range_id = vr.id
    JOIN districts d ON vr.district_id = d.id
    ORDER BY cp.id DESC
";
$calvings = $mysqli->query($calving_sql)->fetch_all(MYSQLI_ASSOC);

// PD
$pd_sql = "
    SELECT 
        pd.*,
        vr.name AS range_name,
        d.name AS district_name
    FROM breeding_pd_performance pd
    JOIN veterinary_ranges vr ON pd.range_id = vr.id
    JOIN districts d ON vr.district_id = d.id
    ORDER BY pd.id DESC
";
$pds = $mysqli->query($pd_sql)->fetch_all(MYSQLI_ASSOC);

$total_ai = count($ai_records);
$total_calvings = count($calvings);
$total_pds = count($pds);
?>

<div class="container-fluid px-4 py-3">

    <!-- Header & Breadcrumb -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="range_details.php" class="btn btn-sm btn-outline-secondary rounded-circle" title="Back to Hub">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="fw-bold mb-0 text-dark">Animal Breeding Summary</h2>
                <span class="badge text-white px-3 py-2 rounded-pill" style="background-color: #e65100;">Animal Breeding Module</span>
                <span class="badge bg-dark px-3 py-2 rounded-pill">Province-Wide Scope</span>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Artificial Inseminations (A.I.), Pregnancy Diagnoses (PD), and Calving Returns across all 45 Ranges.
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
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white" style="border-color: #e65100 !important;">
                <small class="text-muted text-uppercase fw-bold">Total A.I. Inseminations</small>
                <h3 class="fw-bold text-dark mb-0 mt-1"><?= number_format($total_ai) ?> <small class="fs-6 text-muted">A.I. Done</small></h3>
                <small class="text-muted">Cattle breeding across all ranges</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white border-success">
                <small class="text-muted text-uppercase fw-bold">Calving Returns Logged</small>
                <h3 class="fw-bold text-success mb-0 mt-1"><?= number_format($total_calvings) ?> <small class="fs-6 text-muted">Births</small></h3>
                <small class="text-muted">Live births from recorded inseminations</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white border-info">
                <small class="text-muted text-uppercase fw-bold">Pregnancy Diagnoses (PD)</small>
                <h3 class="fw-bold text-info mb-0 mt-1"><?= number_format($total_pds) ?> <small class="fs-6 text-muted">PD Exams</small></h3>
                <small class="text-muted">Gestation confirmations</small>
            </div>
        </div>
    </div>

    <!-- Master A.I. Performance Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-file-earmark-text-fill me-2" style="color: #e65100;"></i>Artificial Insemination (A.I.) Field Records</h5>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="table-responsive">
                <table id="aiTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>A.I. Date</th>
                            <th>Range Name</th>
                            <th>District</th>
                            <th>Technician Code</th>
                            <th>Cow ID / Reg</th>
                            <th>Semen Bull Code</th>
                            <th>A.I. Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ai_records as $r): 
                            $dist_badge = ($r['district_name'] === 'Ampara') ? 'bg-primary' : (($r['district_name'] === 'Batticaloa') ? 'bg-success' : 'bg-warning text-dark');
                        ?>
                            <tr>
                                <td class="fw-semibold text-secondary"><?= htmlspecialchars($r['ai_date']) ?></td>
                                <td><strong class="text-dark"><?= htmlspecialchars($r['range_name']) ?></strong></td>
                                <td><span class="badge <?= $dist_badge ?> bg-opacity-75 rounded-pill px-2 py-1"><?= htmlspecialchars($r['district_name']) ?></span></td>
                                <td class="font-monospace fw-bold text-secondary"><?= htmlspecialchars($r['technician_code'] ?: 'TECH-01') ?></td>
                                <td class="font-monospace fw-bold text-dark"><?= htmlspecialchars($r['cow_id']) ?></td>
                                <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($r['semen_code']) ?></span></td>
                                <td><span class="badge bg-warning bg-opacity-10 text-dark rounded-pill px-2 py-1"><?= htmlspecialchars($r['ai_type'] ?: '1st Service') ?></span></td>
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
    $('#aiTable').DataTable({
        pageLength: 15,
        dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-3"Bf>rt<"d-flex flex-wrap justify-content-between align-items-center mt-3"ip>',
        buttons: [
            { extend: 'csv', text: '<i class="bi bi-filetype-csv me-1"></i> Export CSV', className: 'btn btn-sm btn-success rounded-pill me-2' },
            { extend: 'print', text: '<i class="bi bi-printer me-1"></i> Print', className: 'btn btn-sm btn-dark rounded-pill' }
        ]
    });
});
</script>
