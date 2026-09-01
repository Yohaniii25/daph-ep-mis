<?php
// pages/planning_dd/monitoring.php
// Monitoring & Strategic Indicators Master Summary (Province-Wide)

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
        sai.*,
        vr.name AS range_name,
        d.name AS district_name
    FROM strategic_action_indicators sai
    LEFT JOIN veterinary_ranges vr ON sai.range_id = vr.id
    LEFT JOIN districts d ON vr.district_id = d.id
    ORDER BY sai.id DESC
";
$indicators = $mysqli->query($sql)->fetch_all(MYSQLI_ASSOC);

$total_indicators = count($indicators);
?>

<div class="container-fluid px-4 py-3">

    <!-- Header & Breadcrumb -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="range_details.php" class="btn btn-sm btn-outline-secondary rounded-circle" title="Back to Hub">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="fw-bold mb-0 text-dark">Monitoring Summary</h2>
                <span class="badge text-white px-3 py-2 rounded-pill" style="background-color: #283593;">Monitoring Module</span>
                <span class="badge bg-dark px-3 py-2 rounded-pill">Province-Wide Scope</span>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Strategic action indicators, monthly KPI compliance, and operational inspection tracking across all 45 Ranges.
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
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white" style="border-color: #283593 !important;">
                <small class="text-muted text-uppercase fw-bold">Active Strategic Action Indicators</small>
                <h3 class="fw-bold text-dark mb-0 mt-1"><?= $total_indicators ?> <small class="fs-6 text-muted">Key Indicators</small></h3>
                <small class="text-muted">Monitored across field operations</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white border-success">
                <small class="text-muted text-uppercase fw-bold">Province-Wide Monitoring Compliance</small>
                <h3 class="fw-bold text-success mb-0 mt-1">100% Active</h3>
                <small class="text-muted">Structured strategic framework</small>
            </div>
        </div>
    </div>

    <!-- Master Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-folder-fill me-2" style="color: #283593;"></i>Strategic Action Indicators Directory</h5>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="table-responsive">
                <table id="monitoringTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Indicator Code</th>
                            <th>Strategic Objective / Action</th>
                            <th>Target Group / Scope</th>
                            <th class="text-center">Target Metric</th>
                            <th class="text-center">Actual Progress</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($indicators as $ind): ?>
                            <tr>
                                <td class="font-monospace fw-bold text-secondary"><?= htmlspecialchars($ind['indicator_code'] ?? 'KPI-' . $ind['id']) ?></td>
                                <td><strong class="text-dark"><?= htmlspecialchars($ind['indicator_name'] ?? $ind['action_title'] ?? 'Strategic Action Target') ?></strong></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($ind['target_scope'] ?? 'Province-Wide') ?></span></td>
                                <td class="text-center font-monospace fw-bold"><?= htmlspecialchars($ind['target_value'] ?? '100%') ?></td>
                                <td class="text-center font-monospace fw-bold text-success"><?= htmlspecialchars($ind['actual_value'] ?? 'On-Track') ?></td>
                                <td class="text-center"><span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1"><?= htmlspecialchars($ind['status'] ?? 'Active') ?></span></td>
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
    $('#monitoringTable').DataTable({
        pageLength: 15,
        dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-3"Bf>rt<"d-flex flex-wrap justify-content-between align-items-center mt-3"ip>',
        buttons: [
            { extend: 'csv', text: '<i class="bi bi-filetype-csv me-1"></i> Export CSV', className: 'btn btn-sm btn-success rounded-pill me-2' },
            { extend: 'print', text: '<i class="bi bi-printer me-1"></i> Print', className: 'btn btn-sm btn-dark rounded-pill' }
        ]
    });
});
</script>
