<?php
// pages/planning_dd/annual_targets.php
// Annual Targets Master Summary (Province-Wide)

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
        avt.*,
        vr.name AS range_name,
        vr.code AS range_code,
        d.name AS district_name,
        u.full_name AS vaccinator_name
    FROM annual_vaccination_targets avt
    JOIN veterinary_ranges vr ON avt.range_id = vr.id
    JOIN districts d ON vr.district_id = d.id
    LEFT JOIN users u ON avt.assigned_vaccinator_id = u.id
    ORDER BY avt.year DESC, d.name ASC, vr.name ASC
";
$targets = $mysqli->query($sql)->fetch_all(MYSQLI_ASSOC);

// Totals
$total_fmd = array_sum(array_column($targets, 'target_fmd'));
$total_bq = array_sum(array_column($targets, 'target_bq'));
$total_hs = array_sum(array_column($targets, 'target_hs'));
$total_vac_target = $total_fmd + $total_bq + $total_hs;
$total_ldo = array_sum(array_column($targets, 'available_ldo_count'));
$total_man_days = array_sum(array_column($targets, 'allocated_man_days'));
?>

<div class="container-fluid px-4 py-3">

    <!-- Header & Breadcrumb -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="range_details.php" class="btn btn-sm btn-outline-secondary rounded-circle" title="Back to Hub">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="fw-bold mb-0 text-dark">Annual Targets Summary</h2>
                <span class="badge text-white px-3 py-2 rounded-pill" style="background-color: #370709;">Annual Targets Module</span>
                <span class="badge bg-dark px-3 py-2 rounded-pill">Province-Wide Scope</span>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Annual vaccination targets (FMD, BQ, HS), LDO allocations, and logistics requirements across all ranges.
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
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white" style="border-color: #370709 !important;">
                <small class="text-muted text-uppercase fw-bold">Total Target Vaccinations</small>
                <h3 class="fw-bold text-dark mb-0 mt-1"><?= number_format($total_vac_target) ?> <small class="fs-6 text-muted">doses</small></h3>
                <small class="text-muted">FMD + BQ + HS targets</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white border-primary">
                <small class="text-muted text-uppercase fw-bold">FMD Target Doses</small>
                <h3 class="fw-bold text-primary mb-0 mt-1"><?= number_format($total_fmd) ?></h3>
                <small class="text-muted">Foot & Mouth Disease</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white border-warning">
                <small class="text-muted text-uppercase fw-bold">BQ & HS Targets</small>
                <h3 class="fw-bold text-dark mb-0 mt-1"><?= number_format($total_bq + $total_hs) ?></h3>
                <small class="text-muted">Black Quarter & Haemorrhagic Septicaemia</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white border-success">
                <small class="text-muted text-uppercase fw-bold">LDO Officers & Man-Days</small>
                <h3 class="fw-bold text-success mb-0 mt-1"><?= number_format($total_ldo) ?> <small class="fs-6 text-muted">LDOs</small></h3>
                <small class="text-muted"><?= number_format($total_man_days) ?> Allocated Man-Days</small>
            </div>
        </div>
    </div>

    <!-- Master Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-bar-chart-fill me-2" style="color: #370709;"></i>Annual Targets by Range</h5>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="table-responsive">
                <table id="targetsTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Year</th>
                            <th>Range Name</th>
                            <th>District</th>
                            <th>Animal Type</th>
                            <th class="text-center">FMD Target</th>
                            <th class="text-center">BQ Target</th>
                            <th class="text-center">HS Target</th>
                            <th class="text-center">Total Target</th>
                            <th class="text-center">LDO Count</th>
                            <th class="text-center">Casual Needed</th>
                            <th class="text-center">Fuel (L/Mo)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($targets as $t): 
                            $dist_badge = ($t['district_name'] === 'Ampara') ? 'bg-primary' : (($t['district_name'] === 'Batticaloa') ? 'bg-success' : 'bg-warning text-dark');
                            $row_total = $t['target_fmd'] + $t['target_bq'] + $t['target_hs'];
                        ?>
                            <tr>
                                <td class="fw-bold text-secondary"><?= htmlspecialchars($t['year']) ?></td>
                                <td><strong class="text-dark"><?= htmlspecialchars($t['range_name']) ?></strong></td>
                                <td><span class="badge <?= $dist_badge ?> bg-opacity-75 rounded-pill px-2 py-1"><?= htmlspecialchars($t['district_name']) ?></span></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($t['animal_type'] ?: 'Cattle') ?></span></td>
                                <td class="text-center font-monospace fw-semibold"><?= number_format($t['target_fmd']) ?></td>
                                <td class="text-center font-monospace fw-semibold"><?= number_format($t['target_bq']) ?></td>
                                <td class="text-center font-monospace fw-semibold"><?= number_format($t['target_hs']) ?></td>
                                <td class="text-center font-monospace fw-bold text-dark"><?= number_format($row_total) ?></td>
                                <td class="text-center font-monospace"><?= $t['available_ldo_count'] ?></td>
                                <td class="text-center font-monospace"><?= $t['casual_vaccinators_needed'] ?></td>
                                <td class="text-center font-monospace"><?= number_format($t['fuel_liters_per_month'], 1) ?></td>
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
    $('#targetsTable').DataTable({
        pageLength: 15,
        dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-3"Bf>rt<"d-flex flex-wrap justify-content-between align-items-center mt-3"ip>',
        buttons: [
            { extend: 'csv', text: '<i class="bi bi-filetype-csv me-1"></i> Export CSV', className: 'btn btn-sm btn-success rounded-pill me-2' },
            { extend: 'print', text: '<i class="bi bi-printer me-1"></i> Print', className: 'btn btn-sm btn-dark rounded-pill' }
        ]
    });
});
</script>
