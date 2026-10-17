<?php
session_start();

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', [
    'veterinary_surgeon',
    'government_veterinary_surgeon',
    'additional_veterinary_surgeon',
    'district_dd',
    'provincial_director',
    'admin',
    'super_admin'
], true)) {
    header("Location: ../../../index.php");
    exit();
}

if (!isset($_SESSION['full_name'])) {
    $_SESSION['full_name'] = $_SESSION['username'] ?? 'Veterinary Surgeon';
}

$full_name   = $_SESSION['full_name'];
$range_id    = $_SESSION['range_id'] ?? null;
$district_id = $_SESSION['district_id'] ?? null;

require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

// Resolve Range ID from User Profiler if empty in session
if (empty($range_id) && !empty($_SESSION['user_id'])) {
    $u_stmt = $mysqli->prepare("SELECT range_id, district_id FROM users WHERE id = ?");
    if ($u_stmt) {
        $u_stmt->bind_param("i", $_SESSION['user_id']);
        $u_stmt->execute();
        $u_row = $u_stmt->get_result()->fetch_assoc();
        if ($u_row) {
            $range_id = $u_row['range_id'];
            $_SESSION['range_id'] = $range_id;
            if (!empty($u_row['district_id'])) {
                $district_id = $u_row['district_id'];
                $_SESSION['district_id'] = $district_id;
            }
        }
        $u_stmt->close();
    }
}

if (empty($range_id)) {
    die('<div class="alert alert-danger text-center p-5 m-5">Error: Your account is not assigned to any Veterinary Range.</div>');
}

$district_name = 'Unknown District';
$range_name    = 'Unknown Range';

// Fetch District and Range Names
if ($district_id) {
    $stmt = $mysqli->prepare("SELECT name FROM districts WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $district_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $district_name = $row['name'];
        }
        $stmt->close();
    }
}

if ($range_id) {
    $stmt = $mysqli->prepare("SELECT name, district_id FROM veterinary_ranges WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $range_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $range_name = $row['name'];
            if (empty($district_id) && !empty($row['district_id'])) {
                $district_id = $row['district_id'];
                $d_res = $mysqli->query("SELECT name FROM districts WHERE id = " . intval($district_id));
                if ($d_row = $d_res->fetch_assoc()) {
                    $district_name = $d_row['name'];
                }
            }
        }
        $stmt->close();
    }
}

$current_year = intval(date('Y'));
$current_month = intval(date('n'));

// KPI 1: Total Cash Receipts Revenue
$total_cash_revenue = 0.0;
$c_stmt = $mysqli->prepare("SELECT COALESCE(SUM(total_amount), 0) AS total_amt FROM cash_book_summaries WHERE range_id = ? AND report_year = ?");
if ($c_stmt) {
    $c_stmt->bind_param("ii", $range_id, $current_year);
    $c_stmt->execute();
    $total_cash_revenue = floatval($c_stmt->get_result()->fetch_assoc()['total_amt'] ?? 0);
    $c_stmt->close();
}

// KPI 2: Total Literature Account Records (Letter H)
$total_letter_h_amt = 0.0;
$h_stmt = $mysqli->prepare("SELECT COALESCE(SUM(amount), 0) AS total_h FROM letter_h_entries WHERE range_id = ? AND report_year = ?");
if ($h_stmt) {
    $h_stmt->bind_param("ii", $range_id, $current_year);
    $h_stmt->execute();
    $total_letter_h_amt = floatval($h_stmt->get_result()->fetch_assoc()['total_h'] ?? 0);
    $h_stmt->close();
}

// KPI 3: Section E Active Tracked Items
$total_sec_e_items = 0;
$e_stmt = $mysqli->prepare("SELECT COUNT(DISTINCT item_id) AS cnt FROM section_e WHERE range_id = ? AND report_year = ? AND amount > 0");
if ($e_stmt) {
    $e_stmt->bind_param("ii", $range_id, $current_year);
    $e_stmt->execute();
    $total_sec_e_items = intval($e_stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
    $e_stmt->close();
}

// Recent Cash Receipts
$recent_receipts = [];
$r_stmt = $mysqli->prepare("
    SELECT receipt_no, receipt_date, client_name, item_name, total_amount 
    FROM cash_book_summaries 
    WHERE range_id = ? 
    ORDER BY receipt_date DESC, id DESC 
    LIMIT 6
");
if ($r_stmt) {
    $r_stmt->bind_param("i", $range_id);
    $r_stmt->execute();
    $r_res = $r_stmt->get_result();
    while ($rw = $r_res->fetch_assoc()) {
        $recent_receipts[] = $rw;
    }
    $r_stmt->close();
}

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">

<style>
.accounts-card {
    transition: all 0.25s ease-in-out;
    border-radius: 12px;
    overflow: hidden;
}
.accounts-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
}
.stat-pill {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}
.badge-soft-success {
    background-color: #d1fae5;
    color: #065f46;
}
.badge-soft-primary {
    background-color: #dbeafe;
    color: #1e40af;
}
.badge-soft-danger {
    background-color: #fee2e2;
    color: #991b1b;
}
</style>

<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h3 class="fw-bold mb-1" style="color: #370709;">Accounts & Financial Monitoring</h3>
        <p class="text-muted small mb-0">
            Consolidated financial management for <strong class="text-dark"><?= htmlspecialchars($range_name) ?> Range</strong>, <?= htmlspecialchars($district_name) ?> District (<?= $current_year ?>)
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="range_details.php" class="btn btn-secondary shadow-sm text-nowrap">
            <i class="bi bi-arrow-left me-2"></i>Back to Range Details
        </a>
    </div>
</div>

<!-- ============================================================ -->
<!-- 1. FINANCIAL SUMMARY KPI CARDS -->
<!-- ============================================================ -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-pill d-flex align-items-center justify-content-between border-start border-4 border-success">
            <div>
                <div class="text-muted small fw-semibold text-uppercase">Cashbook Summaries Revenue</div>
                <div class="fw-bold fs-4 text-dark mt-1">Rs. <?= number_format($total_cash_revenue, 2) ?></div>
                <small class="text-success"><i class="bi bi-check-circle me-1"></i>Official Cash Receipts (<?= $current_year ?>)</small>
            </div>
            <div class="rounded-circle p-3 bg-success-subtle text-success">
                <i class="bi bi-cash-coin fs-2"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-pill d-flex align-items-center justify-content-between border-start border-4 border-warning">
            <div>
                <div class="text-muted small fw-semibold text-uppercase">Literature Account (Letter 'H')</div>
                <div class="fw-bold fs-4 text-dark mt-1">Rs. <?= number_format($total_letter_h_amt, 2) ?></div>
                <small class="text-muted"><i class="bi bi-bank me-1"></i>Bank Remittances Logged</small>
            </div>
            <div class="rounded-circle p-3 bg-warning-subtle text-warning">
                <i class="bi bi-journal-bookmark-fill fs-2"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-pill d-flex align-items-center justify-content-between border-start border-4 border-primary">
            <div>
                <div class="text-muted small fw-semibold text-uppercase">Section E Active Metrics</div>
                <div class="fw-bold fs-4 text-dark mt-1"><?= number_format($total_sec_e_items) ?> Metrics</div>
                <small class="text-primary"><i class="bi bi-arrow-repeat me-1"></i>AI & Production Balances</small>
            </div>
            <div class="rounded-circle p-3 bg-primary-subtle text-primary">
                <i class="bi bi-bar-chart-line-fill fs-2"></i>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- 2. RETAINED CORE ACCOUNTS MODULES (ACTIVE & ACCESSIBLE) -->
<!-- ============================================================ -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-grid-fill me-2 text-danger"></i>Active Financial & Accounts Modules
        </h6>
        <span class="badge bg-light text-dark border">Standard Government Accounting Framework</span>
    </div>
    <div class="card-body p-4">
        <div class="row g-4">

            <!-- MODULE 1: SECTION E -->
            <div class="col-md-4">
                <div class="card h-100 accounts-card shadow-sm border" style="border-top: 4px solid #370709 !important;">
                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="rounded-3 p-3 text-white" style="background-color: #370709;">
                                    <i class="bi bi-bar-chart-steps fs-3"></i>
                                </div>
                                <span class="badge badge-soft-primary px-3 py-1 rounded-pill">Active Module</span>
                            </div>
                            <h5 class="fw-bold text-dark mb-2">Section E</h5>
                            <p class="text-muted small mb-3">
                                Animal production metrics, automated Artificial Insemination (AI) reports pull, revenue totals directly from cash receipts, and manual recording for milk, meat, and eggs.
                            </p>
                        </div>
                        <a href="section_e.php" class="btn text-white w-100 py-2 fw-semibold shadow-sm" style="background-color: #370709;">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Open Section E
                        </a>
                    </div>
                </div>
            </div>

            <!-- MODULE 2: LITERATURE ACCOUNT RECORDS (LETTER 'H') -->
            <div class="col-md-4">
                <div class="card h-100 accounts-card shadow-sm border" style="border-top: 4px solid #b08723 !important;">
                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="rounded-3 p-3 text-white" style="background-color: #b08723;">
                                    <i class="bi bi-journal-check fs-3"></i>
                                </div>
                                <span class="badge badge-soft-success px-3 py-1 rounded-pill">Active Module</span>
                            </div>
                            <h5 class="fw-bold text-dark mb-2">Literature Account Records</h5>
                            <p class="text-muted small mb-3">
                                Official Letter 'H' accounts capturing departmental literature, publications, PSDG, and Divineguma revenue receipts with monthly bank settlement tracking.
                            </p>
                        </div>
                        <a href="letter_h_record.php" class="btn text-white w-100 py-2 fw-semibold shadow-sm" style="background-color: #b08723;">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Open Literature Records (Letter H)
                        </a>
                    </div>
                </div>
            </div>

            <!-- MODULE 3: CASHBOOK SUMMARIES -->
            <div class="col-md-4">
                <div class="card h-100 accounts-card shadow-sm border" style="border-top: 4px solid #00838f !important;">
                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="rounded-3 p-3 text-white" style="background-color: #00838f;">
                                    <i class="bi bi-receipt fs-3"></i>
                                </div>
                                <span class="badge badge-soft-success px-3 py-1 rounded-pill">Active Module</span>
                            </div>
                            <h5 class="fw-bold text-dark mb-2">Cashbook Summaries</h5>
                            <p class="text-muted small mb-3">
                                Detailed cash receipts register, counterfoil receipt books, daily collections, sales of services/goods, and bank remittance reconciliation summaries.
                            </p>
                        </div>
                        <a href="cash_book_summary.php" class="btn text-white w-100 py-2 fw-semibold shadow-sm" style="background-color: #00838f;">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Open Cashbook Summaries
                        </a>
                    </div>
                </div>
            </div>

            <!-- MODULE 4: VACCINE RETURN (VACCINE BALANCE) -->
            <div class="col-md-12">
                <div class="card shadow-sm border" style="border-left: 5px solid #820100 !important; background-color: #fafafa;">
                    <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 p-3 text-white" style="background-color: #820100;">
                                <i class="bi bi-capsule fs-3"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-1">Vaccine Return (Vaccine Balance)</h5>
                                <p class="text-muted small mb-0">
                                    Official biological stock, vaccine balances, receipt and issuance registers for veterinary preventative health programs.
                                </p>
                            </div>
                        </div>
                        <div>
                            <a href="crop_returns.php" class="btn text-white px-4 py-2 fw-semibold shadow-sm" style="background-color: #820100;">
                                <i class="bi bi-box-arrow-in-right me-2"></i>View Vaccine Return
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- 3. RECENT CASH RECEIPTS PREVIEW -->
<!-- ============================================================ -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-clock-history me-2 text-danger"></i>Recent Cash Receipts Captured
        </h6>
        <a href="cash_book_summary.php" class="btn btn-sm btn-outline-danger">
            View All Receipts <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th class="ps-4">Receipt No</th>
                        <th>Date</th>
                        <th>Client / Farmer</th>
                        <th>Item / Service</th>
                        <th class="text-end pe-4">Total Amount (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_receipts)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No recent cash receipts logged for this range.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_receipts as $rc): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($rc['receipt_no']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($rc['receipt_date']) ?></td>
                                <td><?= htmlspecialchars($rc['client_name']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($rc['item_name']) ?></span></td>
                                <td class="text-end pe-4 fw-bold text-success font-monospace">Rs. <?= number_format($rc['total_amount'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</main>
</div>

<?php require_once '../../../includes/footer.php'; ?>