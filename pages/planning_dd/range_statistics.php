<?php
// pages/planning_dd/range_statistics.php
// Range Statistics Master Summary (Province-Wide)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_roles = ['deputy_director_hq_1', 'administrator', 'provincial_director'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied. Unauthorized role footprint.");
}

require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../includes/header.php';

// Global Data Fetch (No district filter)
$sql = "
    SELECT 
        vr.id AS range_id,
        vr.name AS range_name,
        vr.code AS range_code,
        vr.is_active AS is_active,
        d.name AS district_name,
        u.full_name AS vs_name,
        u.email AS vs_email,
        u.phone AS vs_phone
    FROM veterinary_ranges vr
    JOIN districts d ON vr.district_id = d.id
    LEFT JOIN users u ON vr.id = u.range_id AND u.role = 'veterinary_surgeon' AND u.is_active = 1
    ORDER BY d.name ASC, vr.name ASC
";
$ranges = $mysqli->query($sql)->fetch_all(MYSQLI_ASSOC);

$total_ranges = count($ranges);
$active_ranges = count(array_filter($ranges, fn($r) => $r['is_active'] == 1));
$assigned_vs = count(array_filter($ranges, fn($r) => !empty($r['vs_name'])));
?>

<div class="container-fluid px-4 py-3">

    <!-- Header & Breadcrumb -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="range_details.php" class="btn btn-sm btn-outline-secondary rounded-circle" title="Back to Hub">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="fw-bold mb-0 text-dark">Range Statistics Summary</h2>
                <span class="badge text-white px-3 py-2 rounded-pill" style="background-color: #820100;">Range Profile Directory</span>
                <span class="badge bg-dark px-3 py-2 rounded-pill">Province-Wide Scope</span>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Global status, official codes, and Veterinary Surgeon staffing across all 45 Veterinary Ranges.
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

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white" style="border-color: #820100 !important;">
                <small class="text-muted text-uppercase fw-bold">Total Veterinary Ranges</small>
                <h3 class="fw-bold text-dark mb-0 mt-1"><?= $total_ranges ?></h3>
                <small class="text-muted">Ampara (20), Batticaloa (14), Trincomalee (11)</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white border-success">
                <small class="text-muted text-uppercase fw-bold">Active Operational Status</small>
                <h3 class="fw-bold text-success mb-0 mt-1"><?= $active_ranges ?> / <?= $total_ranges ?></h3>
                <small class="text-muted">100% Geographic Range Coverage</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white border-info">
                <small class="text-muted text-uppercase fw-bold">Assigned Veterinary Surgeons</small>
                <h3 class="fw-bold text-info mb-0 mt-1"><?= $assigned_vs ?> <small class="fs-6 text-muted">Surgeons Deployed</small></h3>
                <small class="text-muted"><?= round(($assigned_vs / max($total_ranges, 1)) * 100) ?>% Range Staffing Rate</small>
            </div>
        </div>
    </div>

    <!-- Master Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-geo-alt-fill me-2" style="color: #820100;"></i>Veterinary Ranges Master Directory</h5>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="table-responsive">
                <table id="rangesStatTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 80px;">Code</th>
                            <th>Range Name</th>
                            <th>District</th>
                            <th>Assigned Veterinary Surgeon</th>
                            <th>Contact Email</th>
                            <th>Contact Phone</th>
                            <th class="text-center" style="width: 100px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ranges as $r): 
                            $dist_badge = ($r['district_name'] === 'Ampara') ? 'bg-primary' : (($r['district_name'] === 'Batticaloa') ? 'bg-success' : 'bg-warning text-dark');
                        ?>
                            <tr>
                                <td class="text-center font-monospace fw-bold text-secondary"><?= htmlspecialchars($r['range_code'] ?: 'R' . str_pad($r['range_id'], 3, '0', STR_PAD_LEFT)) ?></td>
                                <td><strong class="text-dark"><?= htmlspecialchars($r['range_name']) ?></strong></td>
                                <td><span class="badge <?= $dist_badge ?> bg-opacity-75 rounded-pill px-2 py-1"><?= htmlspecialchars($r['district_name']) ?></span></td>
                                <td>
                                    <?php if (!empty($r['vs_name'])): ?>
                                        <span class="fw-semibold text-dark"><i class="bi bi-person-badge me-1 text-primary"></i><?= htmlspecialchars($r['vs_name']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-25 text-secondary rounded-pill px-2 py-1">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= htmlspecialchars($r['vs_email'] ?: 'N/A') ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($r['vs_phone'] ?: 'N/A') ?></td>
                                <td class="text-center">
                                    <?php if ($r['is_active']): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2 py-1">Inactive</span>
                                    <?php endif; ?>
                                </td>
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
    $('#rangesStatTable').DataTable({
        pageLength: 15,
        lengthMenu: [15, 25, 45, 100],
        order: [[2, 'asc'], [1, 'asc']],
        dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-3"Bf>rt<"d-flex flex-wrap justify-content-between align-items-center mt-3"ip>',
        buttons: [
            { extend: 'csv', text: '<i class="bi bi-filetype-csv me-1"></i> Export CSV', className: 'btn btn-sm btn-success rounded-pill me-2' },
            { extend: 'print', text: '<i class="bi bi-printer me-1"></i> Print', className: 'btn btn-sm btn-dark rounded-pill' }
        ]
    });
});
</script>
