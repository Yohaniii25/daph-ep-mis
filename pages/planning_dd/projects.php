<?php
// pages/planning_dd/projects.php
// Projects Progress Master Summary (Province-Wide)

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
        pp.*,
        vr.name AS range_name,
        vr.code AS range_code,
        d.name AS district_name
    FROM projects_progress pp
    LEFT JOIN veterinary_ranges vr ON pp.range_id = vr.id
    LEFT JOIN districts d ON vr.district_id = d.id
    ORDER BY pp.id DESC
";
$projects = $mysqli->query($sql)->fetch_all(MYSQLI_ASSOC);

$total_projects = count($projects);
$completed = count(array_filter($projects, fn($p) => ($p['status'] ?? '') === 'Completed'));
$total_budget = array_sum(array_column($projects, 'allocated_budget'));
?>

<div class="container-fluid px-4 py-3">

    <!-- Header & Breadcrumb -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="range_details.php" class="btn btn-sm btn-outline-secondary rounded-circle" title="Back to Hub">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="fw-bold mb-0 text-dark">Projects Progress Summary</h2>
                <span class="badge text-white px-3 py-2 rounded-pill" style="background-color: #00838f;">Projects Module</span>
                <span class="badge bg-dark px-3 py-2 rounded-pill">Province-Wide Scope</span>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Development projects (PSDG, CBG, NGO), capital works, and implementation milestones across all 45 Ranges.
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
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white" style="border-color: #00838f !important;">
                <small class="text-muted text-uppercase fw-bold">Total Development Projects</small>
                <h3 class="fw-bold text-dark mb-0 mt-1"><?= $total_projects ?> <small class="fs-6 text-muted">Projects</small></h3>
                <small class="text-muted">Managed across all districts</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white border-success">
                <small class="text-muted text-uppercase fw-bold">Completed Projects</small>
                <h3 class="fw-bold text-success mb-0 mt-1"><?= $completed ?> / <?= $total_projects ?></h3>
                <small class="text-muted"><?= round(($completed / max($total_projects, 1)) * 100) ?>% Project Completion Rate</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white border-primary">
                <small class="text-muted text-uppercase fw-bold">Allocated Budget (LKR)</small>
                <h3 class="fw-bold text-primary mb-0 mt-1">Rs. <?= number_format($total_budget, 2) ?></h3>
                <small class="text-muted">Total capital outlay</small>
            </div>
        </div>
    </div>

    <!-- Master Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-geo-alt-fill me-2" style="color: #00838f;"></i>Development Projects Directory</h5>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="table-responsive">
                <table id="projectsTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Project Code</th>
                            <th>Project Title</th>
                            <th>Range / District</th>
                            <th>Funding Source</th>
                            <th class="text-center">Budget (LKR)</th>
                            <th class="text-center">Physical %</th>
                            <th class="text-center">Financial %</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $p): 
                            $status_badge = ($p['status'] === 'Completed') ? 'bg-success' : 'bg-warning text-dark';
                        ?>
                            <tr>
                                <td class="font-monospace fw-bold text-secondary"><?= htmlspecialchars($p['project_code'] ?? 'PRJ-' . $p['id']) ?></td>
                                <td><strong class="text-dark"><?= htmlspecialchars($p['project_title'] ?? $p['title'] ?? 'Livestock Development Project') ?></strong></td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($p['range_name'] ?: 'All Ranges') ?></span>
                                    <small class="text-muted d-block"><?= htmlspecialchars($p['district_name'] ?: 'Eastern Province') ?></small>
                                </td>
                                <td><span class="badge bg-info bg-opacity-10 text-info border"><?= htmlspecialchars($p['funding_source'] ?? 'PSDG') ?></span></td>
                                <td class="text-center font-monospace fw-bold text-dark"><?= number_format($p['allocated_budget'] ?? 0, 2) ?></td>
                                <td class="text-center font-monospace"><?= number_format($p['physical_progress'] ?? 0) ?>%</td>
                                <td class="text-center font-monospace"><?= number_format($p['financial_progress'] ?? 0) ?>%</td>
                                <td class="text-center"><span class="badge <?= $status_badge ?> rounded-pill px-2 py-1"><?= htmlspecialchars($p['status'] ?? 'Ongoing') ?></span></td>
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
    $('#projectsTable').DataTable({
        pageLength: 15,
        dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-3"Bf>rt<"d-flex flex-wrap justify-content-between align-items-center mt-3"ip>',
        buttons: [
            { extend: 'csv', text: '<i class="bi bi-filetype-csv me-1"></i> Export CSV', className: 'btn btn-sm btn-success rounded-pill me-2' },
            { extend: 'print', text: '<i class="bi bi-printer me-1"></i> Print', className: 'btn btn-sm btn-dark rounded-pill' }
        ]
    });
});
</script>
