<?php
// pages/planning_dd/accounts.php
// Accounts Master Summary (Province-Wide)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_roles = ['deputy_director_hq_1', 'administrator', 'provincial_director'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied. Unauthorized role footprint.");
}

require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../includes/header.php';

// Global Data Fetch (Letter H & Cash Book)
$h_sql = "
    SELECT 
        lha.*,
        vr.name AS range_name,
        d.name AS district_name
    FROM letter_h_accounts lha
    JOIN veterinary_ranges vr ON lha.range_id = vr.id
    JOIN districts d ON vr.district_id = d.id
    ORDER BY lha.id DESC
";
$letter_h = $mysqli->query($h_sql)->fetch_all(MYSQLI_ASSOC);

// Cash book
$cash_sql = "
    SELECT 
        cbs.*,
        vr.name AS range_name,
        d.name AS district_name
    FROM cash_book_summaries cbs
    JOIN veterinary_ranges vr ON cbs.range_id = vr.id
    JOIN districts d ON vr.district_id = d.id
    ORDER BY cbs.id DESC
";
$cash_books = $mysqli->query($cash_sql)->fetch_all(MYSQLI_ASSOC);

$total_h = count($letter_h);
$total_cash = count($cash_books);
?>

<div class="container-fluid px-4 py-3">

    <!-- Header & Breadcrumb -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="range_details.php" class="btn btn-sm btn-outline-secondary rounded-circle" title="Back to Hub">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="fw-bold mb-0 text-dark">Accounts Summary</h2>
                <span class="badge text-white px-3 py-2 rounded-pill" style="background-color: #ad1457;">Accounts Module</span>
                <span class="badge bg-dark px-3 py-2 rounded-pill">Province-Wide Scope</span>
            </div>
            <p class="text-muted small mb-0 mt-1">
                Letter H monthly expenditure accounts, cash book summaries, and revenue collection across all 45 Ranges.
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
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white" style="border-color: #ad1457 !important;">
                <small class="text-muted text-uppercase fw-bold">Letter H Account Returns</small>
                <h3 class="fw-bold text-dark mb-0 mt-1"><?= $total_h ?> <small class="fs-6 text-muted">Returns</small></h3>
                <small class="text-muted">Monthly field expenditure reconciliations</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-4 bg-white border-primary">
                <small class="text-muted text-uppercase fw-bold">Cash Book Summaries</small>
                <h3 class="fw-bold text-primary mb-0 mt-1"><?= $total_cash ?> <small class="fs-6 text-muted">Statements</small></h3>
                <small class="text-muted">Revenue & receipt reconciliations</small>
            </div>
        </div>
    </div>

    <!-- Master Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-bookmark-dash-fill me-2" style="color: #ad1457;"></i>Letter H Monthly Accounts Reconciliations</h5>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="table-responsive">
                <table id="accountsTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Period</th>
                            <th>Range Name</th>
                            <th>District</th>
                            <th>Account Code</th>
                            <th class="text-center">Opening Balance</th>
                            <th class="text-center">Disbursements</th>
                            <th class="text-center">Expenditure</th>
                            <th class="text-center">Closing Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($letter_h as $h): 
                            $dist_badge = ($h['district_name'] === 'Ampara') ? 'bg-primary' : (($h['district_name'] === 'Batticaloa') ? 'bg-success' : 'bg-warning text-dark');
                        ?>
                            <tr>
                                <td class="fw-semibold text-secondary"><?= htmlspecialchars($h['report_month'] ?? 'Current Month') ?></td>
                                <td><strong class="text-dark"><?= htmlspecialchars($h['range_name']) ?></strong></td>
                                <td><span class="badge <?= $dist_badge ?> bg-opacity-75 rounded-pill px-2 py-1"><?= htmlspecialchars($h['district_name']) ?></span></td>
                                <td class="font-monospace fw-bold text-dark"><?= htmlspecialchars($h['account_code'] ?? 'ACC-H01') ?></td>
                                <td class="text-center font-monospace"><?= number_format($h['opening_balance'] ?? 0, 2) ?></td>
                                <td class="text-center font-monospace text-primary"><?= number_format($h['received_amount'] ?? $h['disbursement'] ?? 0, 2) ?></td>
                                <td class="text-center font-monospace text-danger"><?= number_format($h['expenditure'] ?? $h['spent_amount'] ?? 0, 2) ?></td>
                                <td class="text-center font-monospace text-success fw-bold"><?= number_format($h['closing_balance'] ?? 0, 2) ?></td>
                                <td><span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1">Audited</span></td>
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
    $('#accountsTable').DataTable({
        pageLength: 15,
        dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-3"Bf>rt<"d-flex flex-wrap justify-content-between align-items-center mt-3"ip>',
        buttons: [
            { extend: 'csv', text: '<i class="bi bi-filetype-csv me-1"></i> Export CSV', className: 'btn btn-sm btn-success rounded-pill me-2' },
            { extend: 'print', text: '<i class="bi bi-printer me-1"></i> Print', className: 'btn btn-sm btn-dark rounded-pill' }
        ]
    });
});
</script>
