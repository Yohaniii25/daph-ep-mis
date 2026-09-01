<?php
// pages/planning_dd/monthly-annual-reports.php
// Monthly/Annual Reports Master Summary (Province-Wide)

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
        mvb.*,
        vr.name AS range_name,
        vr.code AS range_code,
        d.name AS district_name,
        u.full_name AS created_by_name
    FROM monthly_vaccine_balances mvb
    JOIN veterinary_ranges vr ON mvb.range_id = vr.id
    JOIN districts d ON mvb.district_id = d.id
    LEFT JOIN users u ON mvb.created_by = u.id
    ORDER BY mvb.report_year DESC, mvb.report_month DESC, d.name ASC, vr.name ASC
";
$balances = $mysqli->query($sql)->fetch_all(MYSQLI_ASSOC);

$total_used = array_sum(array_column($balances, 'used_doses'));
$total_received = array_sum(array_column($balances, 'received_doses'));
$total_stock = array_sum(array_column($balances, 'closing_balance'));
$total_spoilt = array_sum(array_column($balances, 'spoilt_damaged_doses'));
?>

<div class="container-fluid px-4 py-3">

    <!-- Header & Breadcrumb -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="range_details.php" class="btn btn-sm btn-outline-secondary rounded-circle" title="Back to Hub">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="fw-bold mb-0 text-dark">Monthly & Annual Reports Summary</h2>
                <span class="badge text-white px-3 py-2 rounded-pill" style="background-color: #b08723;">Monthly Returns Module</span>
                <span class="badge bg-dark px-3 py-2 rounded-pill">Province-Wide Scope</span>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Global monthly returns, vaccine receipts, dosages administered, wastage, and cold chain stock balances.
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
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white" style="border-color: #b08723 !important;">
                <small class="text-muted text-uppercase fw-bold">Total Vaccines Administered</small>
                <h3 class="fw-bold text-dark mb-0 mt-1"><?= number_format($total_used) ?> <small class="fs-6 text-muted">doses</small></h3>
                <small class="text-muted">Used across all ranges</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white border-primary">
                <small class="text-muted text-uppercase fw-bold">Total Vaccines Received</small>
                <h3 class="fw-bold text-primary mb-0 mt-1"><?= number_format($total_received) ?> <small class="fs-6 text-muted">doses</small></h3>
                <small class="text-muted">Dispatched from central stores</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white border-success">
                <small class="text-muted text-uppercase fw-bold">Current Stock Balance</small>
                <h3 class="fw-bold text-success mb-0 mt-1"><?= number_format($total_stock) ?> <small class="fs-6 text-muted">doses</small></h3>
                <small class="text-muted">Available in field storage</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white border-danger">
                <small class="text-muted text-uppercase fw-bold">Spoilt / Damaged</small>
                <h3 class="fw-bold text-danger mb-0 mt-1"><?= number_format($total_spoilt) ?> <small class="fs-6 text-muted">doses</small></h3>
                <small class="text-muted">Cold chain breakages / expired</small>
            </div>
        </div>
    </div>

    <!-- Master Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-car-front-fill me-2" style="color: #b08723;"></i>Monthly Vaccine Balances & Returns Directory</h5>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="table-responsive">
                <table id="monthlyReportsTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Period</th>
                            <th>Range Name</th>
                            <th>District</th>
                            <th>Vaccine Name</th>
                            <th>Batch No</th>
                            <th class="text-center">Opening</th>
                            <th class="text-center">Received</th>
                            <th class="text-center">Used</th>
                            <th class="text-center">Spoilt</th>
                            <th class="text-center">Closing Balance</th>
                            <th>Expiry</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($balances as $b): 
                            $dist_badge = ($b['district_name'] === 'Ampara') ? 'bg-primary' : (($b['district_name'] === 'Batticaloa') ? 'bg-success' : 'bg-warning text-dark');
                        ?>
                            <tr>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($b['report_year'] . '-' . str_pad($b['report_month'], 2, '0', STR_PAD_LEFT)) ?></td>
                                <td><strong class="text-dark"><?= htmlspecialchars($b['range_name']) ?></strong></td>
                                <td><span class="badge <?= $dist_badge ?> bg-opacity-75 rounded-pill px-2 py-1"><?= htmlspecialchars($b['district_name']) ?></span></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($b['vaccine_name']) ?></span></td>
                                <td class="font-monospace small text-muted"><?= htmlspecialchars($b['batch_no'] ?: 'N/A') ?></td>
                                <td class="text-center font-monospace"><?= number_format($b['opening_balance']) ?></td>
                                <td class="text-center font-monospace text-primary fw-semibold"><?= number_format($b['received_doses']) ?></td>
                                <td class="text-center font-monospace text-dark fw-bold"><?= number_format($b['used_doses']) ?></td>
                                <td class="text-center font-monospace text-danger"><?= number_format($b['spoilt_damaged_doses']) ?></td>
                                <td class="text-center font-monospace text-success fw-bold"><?= number_format($b['closing_balance']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($b['expiry_date'] ?: 'N/A') ?></td>
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
    $('#monthlyReportsTable').DataTable({
        pageLength: 15,
        dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-3"Bf>rt<"d-flex flex-wrap justify-content-between align-items-center mt-3"ip>',
        buttons: [
            { extend: 'csv', text: '<i class="bi bi-filetype-csv me-1"></i> Export CSV', className: 'btn btn-sm btn-success rounded-pill me-2' },
            { extend: 'print', text: '<i class="bi bi-printer me-1"></i> Print', className: 'btn btn-sm btn-dark rounded-pill' }
        ]
    });
});
</script>
