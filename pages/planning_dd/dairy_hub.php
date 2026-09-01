<?php
// pages/planning_dd/dairy_hub.php
// Dairy Hub Master Summary (Province-Wide)

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
        dh.*,
        vr.name AS range_name,
        vr.code AS range_code,
        d.name AS district_name
    FROM dairy_hub_records dh
    JOIN veterinary_ranges vr ON dh.range_id = vr.id
    JOIN districts d ON vr.district_id = d.id
    ORDER BY dh.id DESC
";
$hubs = $mysqli->query($sql)->fetch_all(MYSQLI_ASSOC);

$total_hubs = count($hubs);
$total_farmers = array_sum(array_column($hubs, 'registered_farmers'));
$total_daily_litres = array_sum(array_column($hubs, 'daily_collection_litres'));
?>

<div class="container-fluid px-4 py-3">

    <!-- Header & Breadcrumb -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="range_details.php" class="btn btn-sm btn-outline-secondary rounded-circle" title="Back to Hub">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="fw-bold mb-0 text-dark">Dairy Hub Summary</h2>
                <span class="badge text-white px-3 py-2 rounded-pill" style="background-color: #1565c0;">Dairy Hub Module</span>
                <span class="badge bg-dark px-3 py-2 rounded-pill">Province-Wide Scope</span>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Active dairy farmer hubs, chilling infrastructure, and daily milk collection statistics across all 45 Ranges.
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
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white" style="border-color: #1565c0 !important;">
                <small class="text-muted text-uppercase fw-bold">Active Dairy Hubs</small>
                <h3 class="fw-bold text-dark mb-0 mt-1"><?= $total_hubs ?> <small class="fs-6 text-muted">Hubs</small></h3>
                <small class="text-muted">Established community dairy clusters</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white border-success">
                <small class="text-muted text-uppercase fw-bold">Registered Dairy Farmers</small>
                <h3 class="fw-bold text-success mb-0 mt-1"><?= number_format($total_farmers) ?> <small class="fs-6 text-muted">Farmers</small></h3>
                <small class="text-muted">Supplying milk to central chilling</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white border-warning">
                <small class="text-muted text-uppercase fw-bold">Daily Milk Collection</small>
                <h3 class="fw-bold text-dark mb-0 mt-1"><?= number_format($total_daily_litres) ?> <small class="fs-6 text-muted">Litres / Day</small></h3>
                <small class="text-muted">Chilled and dispatched to processors</small>
            </div>
        </div>
    </div>

    <!-- Master Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-patch-check-fill me-2" style="color: #1565c0;"></i>Dairy Hubs Directory by Range</h5>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="table-responsive">
                <table id="dairyTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Hub Name</th>
                            <th>Range Name</th>
                            <th>District</th>
                            <th>Contact Person / In-Charge</th>
                            <th class="text-center">Registered Farmers</th>
                            <th class="text-center">Daily Milk (L)</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hubs as $h): 
                            $dist_badge = ($h['district_name'] === 'Ampara') ? 'bg-primary' : (($h['district_name'] === 'Batticaloa') ? 'bg-success' : 'bg-warning text-dark');
                        ?>
                            <tr>
                                <td><strong class="text-dark"><?= htmlspecialchars($h['hub_name'] ?? 'Dairy Development Hub') ?></strong></td>
                                <td><strong class="text-dark"><?= htmlspecialchars($h['range_name']) ?></strong></td>
                                <td><span class="badge <?= $dist_badge ?> bg-opacity-75 rounded-pill px-2 py-1"><?= htmlspecialchars($h['district_name']) ?></span></td>
                                <td class="small text-dark fw-semibold"><?= htmlspecialchars($h['contact_person'] ?? 'Field Officer') ?></td>
                                <td class="text-center font-monospace fw-bold text-success"><?= number_format($h['registered_farmers'] ?? 0) ?></td>
                                <td class="text-center font-monospace fw-bold text-primary"><?= number_format($h['daily_collection_litres'] ?? 0) ?></td>
                                <td class="text-center"><span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1">Operational</span></td>
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
    $('#dairyTable').DataTable({
        pageLength: 15,
        dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-3"Bf>rt<"d-flex flex-wrap justify-content-between align-items-center mt-3"ip>',
        buttons: [
            { extend: 'csv', text: '<i class="bi bi-filetype-csv me-1"></i> Export CSV', className: 'btn btn-sm btn-success rounded-pill me-2' },
            { extend: 'print', text: '<i class="bi bi-printer me-1"></i> Print', className: 'btn btn-sm btn-dark rounded-pill' }
        ]
    });
});
</script>
