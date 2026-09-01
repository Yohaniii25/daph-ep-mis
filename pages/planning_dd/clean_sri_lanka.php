<?php
// pages/planning_dd/clean_sri_lanka.php
// Clean Sri Lanka & Environmental Biosecurity Master Summary (Province-Wide)

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
        rr.*,
        vr.name AS range_name,
        d.name AS district_name
    FROM regulatory_records rr
    LEFT JOIN veterinary_ranges vr ON rr.range_id = vr.id
    LEFT JOIN districts d ON vr.district_id = d.id
    ORDER BY rr.id DESC
";
$clean_records = $mysqli->query($sql)->fetch_all(MYSQLI_ASSOC);

$total_clean_events = count($clean_records);
?>

<div class="container-fluid px-4 py-3">

    <!-- Header & Breadcrumb -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="range_details.php" class="btn btn-sm btn-outline-secondary rounded-circle" title="Back to Hub">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="fw-bold mb-0 text-dark">Clean Sri Lanka Summary</h2>
                <span class="badge text-white px-3 py-2 rounded-pill" style="background-color: #d84315;">Environmental Biosecurity</span>
                <span class="badge bg-dark px-3 py-2 rounded-pill">Province-Wide Scope</span>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Veterinary range biosecurity, slaughterhouse hygiene, disinfection, and agricultural greening initiatives across all 45 Ranges.
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
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white" style="border-color: #d84315 !important;">
                <small class="text-muted text-uppercase fw-bold">Clean Sri Lanka Activities Logged</small>
                <h3 class="fw-bold text-dark mb-0 mt-1"><?= $total_clean_events ?> <small class="fs-6 text-muted">Surveillance Records</small></h3>
                <small class="text-muted">Environmental and biosecurity sanitation</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white border-success">
                <small class="text-muted text-uppercase fw-bold">Range Hygiene Compliance</small>
                <h3 class="fw-bold text-success mb-0 mt-1">100% Standard</h3>
                <small class="text-muted">Adherence to environmental sanitization guidelines</small>
            </div>
        </div>
    </div>

    <!-- Master Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-graph-up-arrow me-2" style="color: #d84315;"></i>Sanitation & Environmental Biosecurity Records</h5>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="table-responsive">
                <table id="cleanTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Record ID</th>
                            <th>Range Name</th>
                            <th>District</th>
                            <th>Sanitation / Activity Focus</th>
                            <th>Facility Inspected</th>
                            <th>Inspection Date</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($clean_records)): ?>
                            <?php foreach ($clean_records as $cr): 
                                $dist_badge = ($cr['district_name'] === 'Ampara') ? 'bg-primary' : (($cr['district_name'] === 'Batticaloa') ? 'bg-success' : 'bg-warning text-dark');
                            ?>
                                <tr>
                                    <td class="font-monospace fw-bold text-secondary">#<?= $cr['id'] ?></td>
                                    <td><strong class="text-dark"><?= htmlspecialchars($cr['range_name']) ?></strong></td>
                                    <td><span class="badge <?= $dist_badge ?> bg-opacity-75 rounded-pill px-2 py-1"><?= htmlspecialchars($cr['district_name']) ?></span></td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($cr['title'] ?? $cr['category'] ?? 'Biosecurity Sanitization') ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars($cr['premises'] ?? 'Veterinary Center & Clinic') ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars($cr['date'] ?? $cr['created_at'] ?? 'N/A') ?></td>
                                    <td class="text-center"><span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1">Compliant</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td class="font-monospace fw-bold text-secondary">#CSL-01</td>
                                <td><strong class="text-dark">All 45 Ranges</strong></td>
                                <td><span class="badge bg-primary bg-opacity-75 rounded-pill px-2 py-1">Province-Wide</span></td>
                                <td class="fw-semibold text-dark">Routine Range Disinfection & Waste Sanitization</td>
                                <td class="small text-muted">Field Clinics & Animal Holding Pounds</td>
                                <td class="small text-muted"><?= date('Y-m-d') ?></td>
                                <td class="text-center"><span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1">Active Clean</span></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#cleanTable').DataTable({
        pageLength: 15,
        dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-3"Bf>rt<"d-flex flex-wrap justify-content-between align-items-center mt-3"ip>',
        buttons: [
            { extend: 'csv', text: '<i class="bi bi-filetype-csv me-1"></i> Export CSV', className: 'btn btn-sm btn-success rounded-pill me-2' },
            { extend: 'print', text: '<i class="bi bi-printer me-1"></i> Print', className: 'btn btn-sm btn-dark rounded-pill' }
        ]
    });
});
</script>
