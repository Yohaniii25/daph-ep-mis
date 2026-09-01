<?php
// pages/planning_dd/livestock_production.php
// Livestock Production Master Summary (Province-Wide)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_roles = ['deputy_director_hq_1', 'administrator', 'provincial_director'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied. Unauthorized role footprint.");
}

require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../includes/header.php';

// Feed production
$feed_sql = "
    SELECT 
        afp.*,
        vr.name AS range_name,
        d.name AS district_name
    FROM annual_feed_production afp
    JOIN veterinary_ranges vr ON afp.range_id = vr.id
    JOIN districts d ON vr.district_id = d.id
    ORDER BY afp.id DESC
";
$feeds = $mysqli->query($feed_sql)->fetch_all(MYSQLI_ASSOC);

// Pasture
$pasture_sql = "
    SELECT 
        apy.*,
        vr.name AS range_name,
        d.name AS district_name
    FROM annual_pasture_yields apy
    JOIN veterinary_ranges vr ON apy.range_id = vr.id
    JOIN districts d ON vr.district_id = d.id
    ORDER BY apy.id DESC
";
$pastures = $mysqli->query($pasture_sql)->fetch_all(MYSQLI_ASSOC);

// Milk collecting
$milk_sql = "
    SELECT 
        amc.*,
        vr.name AS range_name,
        d.name AS district_name
    FROM annual_milk_collecting_centers amc
    JOIN veterinary_ranges vr ON amc.range_id = vr.id
    JOIN districts d ON vr.district_id = d.id
    ORDER BY amc.id DESC
";
$milks = $mysqli->query($milk_sql)->fetch_all(MYSQLI_ASSOC);

$total_feed_recs = count($feeds);
$total_pasture_recs = count($pastures);
$total_milk_centers = count($milks);
?>

<div class="container-fluid px-4 py-3">

    <!-- Header & Breadcrumb -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="range_details.php" class="btn btn-sm btn-outline-secondary rounded-circle" title="Back to Hub">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="fw-bold mb-0 text-dark">Livestock Production Summary</h2>
                <span class="badge text-white px-3 py-2 rounded-pill" style="background-color: #455a64;">Production Module</span>
                <span class="badge bg-dark px-3 py-2 rounded-pill">Province-Wide Scope</span>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Feed production logs, pasture/fodder lands, and annual milk collection & processing infrastructure.
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
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white" style="border-color: #455a64 !important;">
                <small class="text-muted text-uppercase fw-bold">Feed Production Records</small>
                <h3 class="fw-bold text-dark mb-0 mt-1"><?= $total_feed_recs ?> <small class="fs-6 text-muted">Entries</small></h3>
                <small class="text-muted">Commercial and farm feed production</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white border-success">
                <small class="text-muted text-uppercase fw-bold">Pasture & Fodder Cultivations</small>
                <h3 class="fw-bold text-success mb-0 mt-1"><?= $total_pasture_recs ?> <small class="fs-6 text-muted">Plots</small></h3>
                <small class="text-muted">High-yield fodder development</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white border-primary">
                <small class="text-muted text-uppercase fw-bold">Milk Collection Centers</small>
                <h3 class="fw-bold text-primary mb-0 mt-1"><?= $total_milk_centers ?> <small class="fs-6 text-muted">Centers</small></h3>
                <small class="text-muted">Active dairy collection network</small>
            </div>
        </div>
    </div>

    <!-- Master Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-person-bounding-box me-2" style="color: #455a64;"></i>Milk Collecting Centers by Range</h5>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="table-responsive">
                <table id="prodTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Range Name</th>
                            <th>District</th>
                            <th>Center Name</th>
                            <th>Location / Address</th>
                            <th class="text-center">Daily Collection (L)</th>
                            <th>Managed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($milks as $m): 
                            $dist_badge = ($m['district_name'] === 'Ampara') ? 'bg-primary' : (($m['district_name'] === 'Batticaloa') ? 'bg-success' : 'bg-warning text-dark');
                        ?>
                            <tr>
                                <td class="font-monospace fw-bold text-secondary">#<?= $m['id'] ?></td>
                                <td><strong class="text-dark"><?= htmlspecialchars($m['range_name']) ?></strong></td>
                                <td><span class="badge <?= $dist_badge ?> bg-opacity-75 rounded-pill px-2 py-1"><?= htmlspecialchars($m['district_name']) ?></span></td>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($m['center_name'] ?? $m['name'] ?? 'Milk Center') ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($m['location'] ?? $m['address'] ?? 'Range Field Location') ?></td>
                                <td class="text-center font-monospace fw-bold text-primary"><?= number_format($m['daily_capacity'] ?? $m['collection_litres'] ?? 0) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($m['managed_by'] ?? 'Dairy Cooperative') ?></td>
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
    $('#prodTable').DataTable({
        pageLength: 15,
        dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-3"Bf>rt<"d-flex flex-wrap justify-content-between align-items-center mt-3"ip>',
        buttons: [
            { extend: 'csv', text: '<i class="bi bi-filetype-csv me-1"></i> Export CSV', className: 'btn btn-sm btn-success rounded-pill me-2' },
            { extend: 'print', text: '<i class="bi bi-printer me-1"></i> Print', className: 'btn btn-sm btn-dark rounded-pill' }
        ]
    });
});
</script>
