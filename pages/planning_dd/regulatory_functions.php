<?php
// pages/planning_dd/regulatory_functions.php
// Regulatory Functions Master Summary (Province-Wide)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_roles = ['deputy_director_hq_1', 'administrator', 'provincial_director'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied. Unauthorized role footprint.");
}

require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../includes/header.php';

// Health certificates
$certs_sql = "
    SELECT 
        hci.*,
        vr.name AS range_name,
        d.name AS district_name
    FROM health_certificate_issues hci
    JOIN veterinary_ranges vr ON hci.range_id = vr.id
    JOIN districts d ON vr.district_id = d.id
    ORDER BY hci.id DESC
";
$certs = $mysqli->query($certs_sql)->fetch_all(MYSQLI_ASSOC);

// Slaughter statistics
$slaughter_sql = "
    SELECT 
        ss.*,
        vr.name AS range_name,
        d.name AS district_name
    FROM slaughter_statistics ss
    JOIN veterinary_ranges vr ON ss.range_id = vr.id
    JOIN districts d ON vr.district_id = d.id
    ORDER BY ss.id DESC
";
$slaughters = $mysqli->query($slaughter_sql)->fetch_all(MYSQLI_ASSOC);

$total_certs = count($certs);
$total_slaughter_entries = count($slaughters);
?>

<div class="container-fluid px-4 py-3">

    <!-- Header & Breadcrumb -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="range_details.php" class="btn btn-sm btn-outline-secondary rounded-circle" title="Back to Hub">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="fw-bold mb-0 text-dark">Regulatory Functions Summary</h2>
                <span class="badge text-white px-3 py-2 rounded-pill" style="background-color: #a07174;">Regulatory Module</span>
                <span class="badge bg-dark px-3 py-2 rounded-pill">Province-Wide Scope</span>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Health certificates issued, slaughterhouse surveillance statistics, and Animals Act regulatory logs across all 45 Ranges.
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
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white" style="border-color: #a07174 !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-bold">Health Certificates Issued</small>
                        <h3 class="fw-bold text-dark mb-0 mt-1"><?= $total_certs ?> <small class="fs-6 text-muted">Certificates</small></h3>
                        <small class="text-muted">Inter-provincial livestock transportation & sale</small>
                    </div>
                    <i class="bi bi-file-earmark-medical fs-2" style="color: #a07174;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white border-dark">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-bold">Slaughterhouse Surveillance Logs</small>
                        <h3 class="fw-bold text-dark mb-0 mt-1"><?= $total_slaughter_entries ?> <small class="fs-6 text-muted">Records</small></h3>
                        <small class="text-muted">Meat inspection and statutory enforcement</small>
                    </div>
                    <i class="bi bi-shield-check fs-2 text-dark"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Health Certificates Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-file-earmark-plus me-2" style="color: #a07174;"></i>Animal Health Certificates Issued</h5>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="table-responsive">
                <table id="certsTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Cert ID</th>
                            <th>Range Name</th>
                            <th>District</th>
                            <th>Farmer / Applicant</th>
                            <th>Animal Type</th>
                            <th>Purpose / Destination</th>
                            <th>Issue Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($certs as $c): 
                            $dist_badge = ($c['district_name'] === 'Ampara') ? 'bg-primary' : (($c['district_name'] === 'Batticaloa') ? 'bg-success' : 'bg-warning text-dark');
                        ?>
                            <tr>
                                <td class="font-monospace fw-bold text-secondary">#<?= $c['id'] ?></td>
                                <td><strong class="text-dark"><?= htmlspecialchars($c['range_name']) ?></strong></td>
                                <td><span class="badge <?= $dist_badge ?> bg-opacity-75 rounded-pill px-2 py-1"><?= htmlspecialchars($c['district_name']) ?></span></td>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($c['farmer_name'] ?? $c['applicant_name'] ?? 'Registered Owner') ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($c['animal_type'] ?? 'Livestock') ?></span></td>
                                <td class="small text-muted"><?= htmlspecialchars($c['destination'] ?? $c['purpose'] ?? 'Inter-District Movement') ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($c['issue_date'] ?? $c['created_at'] ?? 'N/A') ?></td>
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
    $('#certsTable').DataTable({
        pageLength: 10,
        dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-3"Bf>rt<"d-flex flex-wrap justify-content-between align-items-center mt-3"ip>',
        buttons: [
            { extend: 'csv', text: '<i class="bi bi-filetype-csv me-1"></i> Export CSV', className: 'btn btn-sm btn-success rounded-pill me-2' },
            { extend: 'print', text: '<i class="bi bi-printer me-1"></i> Print', className: 'btn btn-sm btn-dark rounded-pill' }
        ]
    });
});
</script>
