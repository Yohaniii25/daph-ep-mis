<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'veterinary_surgeon' && $_SESSION['role'] !== 'district_dd') die("Access denied");

// Demo data
$revenue = [
    ['date' => '2026-01-10', 'source' => 'Drug Sales', 'amount' => 45000],
    ['date' => '2026-01-09', 'source' => 'Health Certificates', 'amount' => 28000],
    ['date' => '2026-01-08', 'source' => 'Breeding Materials', 'amount' => 35000],
    ['date' => '2026-01-07', 'source' => 'Treatment Fees', 'amount' => 18000],
    ['date' => '2026-01-06', 'source' => 'Other Services', 'amount' => 15000],
];

$total = array_sum(array_column($revenue, 'amount'));
$sources = array_unique(array_column($revenue, 'source'));

// Filter logic (demo - filter array)
$filtered_revenue = $revenue;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $start_date = $_GET['start_date'] ?? '';
    $source = $_GET['source'] ?? '';

    $filtered_revenue = array_filter($revenue, function ($r) use ($start_date, $source) {
        $date_match = true;
        if ($start_date) $date_match = $date_match && (strtotime($r['date']) >= strtotime($start_date));

        $source_match = !$source || $r['source'] === $source;

        return $date_match && $source_match;
    });
}
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Revenue Reporting</h2>

        <!-- Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Source</label>
                        <select name="source" class="form-select">
                            <option value="">All</option>
                            <?php foreach ($sources as $s): ?>
                                <option value="<?= htmlspecialchars($s) ?>" <?= ($source === $s) ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                        <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="card shadow-sm" id="reportsTable">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 style="color: white;" class="mb-0">Revenue Entries</h5>

                <a href="?export=csv" class="btn btn-sm btn-success">
                    <i class="fas fa-file-csv me-1"></i> Export CSV
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>DATE</th>
                                <th>SOURCE</th>
                                <th>AMOUNT (LKR)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($filtered_revenue)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">No revenue entries found matching the filters</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($filtered_revenue as $r): ?>
                                <tr>
                                    <td><?= date('d M Y', strtotime($r['date'])) ?></td>
                                    <td><strong><?= htmlspecialchars($r['source']) ?></strong></td>
                                    <td class="text-success fw-bold">Rs <?= number_format($r['amount']) ?></td>
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