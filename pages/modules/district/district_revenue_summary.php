<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'district_dd') die("Access denied");

// Demo revenue data (with dates and sources)
$revenue_by_unit = [
    ['date' => '2026-01-10', 'unit' => 'Amparai VS Office', 'source' => 'Drug Sales', 'total' => 1200000, 'pending' => 150000, 'approved' => 1050000, 'transactions' => 45],
    ['date' => '2026-01-09', 'unit' => 'Sainthamaruthu Clinic', 'source' => 'Health Certificates', 'total' => 850000, 'pending' => 80000, 'approved' => 770000, 'transactions' => 28],
    ['date' => '2026-01-08', 'unit' => 'Karaitivu VS Office', 'source' => 'Breeding Materials', 'total' => 680000, 'pending' => 90000, 'approved' => 590000, 'transactions' => 22],
    ['date' => '2026-01-07', 'unit' => 'Other Units', 'source' => 'Treatment Fees', 'total' => 450000, 'pending' => 0, 'approved' => 450000, 'transactions' => 15],
    ['date' => '2025-12-30', 'unit' => 'Amparai VS Office', 'source' => 'Vaccination Fees', 'total' => 320000, 'pending' => 50000, 'approved' => 270000, 'transactions' => 12],
];

// Filter logic (demo)
$filtered_revenue = $revenue_by_unit;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    $keyword = trim($_GET['keyword'] ?? '');

    $filtered_revenue = array_filter($revenue_by_unit, function ($row) use ($start_date, $end_date, $keyword) {
        $date_match = true;
        $row_date = strtotime($row['date']);

        if ($start_date) $date_match = $date_match && ($row_date >= strtotime($start_date));
        if ($end_date) $date_match = $date_match && ($row_date <= strtotime($end_date));

        $keyword_match = true;
        if ($keyword) {
            $keyword_match = stripos($row['unit'], $keyword) !== false || stripos($row['source'], $keyword) !== false;
        }

        return $date_match && $keyword_match;
    });
}

// Totals for filtered data
$total_revenue = array_sum(array_column($filtered_revenue, 'total'));
$total_pending = array_sum(array_column($filtered_revenue, 'pending'));
$total_approved = array_sum(array_column($filtered_revenue, 'approved'));
$total_transactions = array_sum(array_column($filtered_revenue, 'transactions'));
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">District Revenue Summary</h2>

        <!-- Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Search (Unit / Source)</label>
                        <input type="text" name="keyword" class="form-control" placeholder="e.g., Amparai" value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100 me-2">Apply Filters</button>
                        <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="btn btn-outline-secondary w-100">Reset</a>
                    </div>
                </form>
            </div>
        </div>


        <!-- Revenue by Unit Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 style="color: white;" class="mb-0">Revenue Breakdown by Unit</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Unit / Office</th>
                                <th>Source</th>
                                <th>Total Revenue (LKR)</th>
                                <th>Pending (LKR)</th>
                                <th>Approved (LKR)</th>
                                <th>Transactions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($filtered_revenue)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No revenue data found matching filters</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($filtered_revenue as $row): ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($row['date'])) ?></td>
                                        <td><strong><?= htmlspecialchars($row['unit']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['source']) ?></td>
                                        <td>Rs <?= number_format($row['total']) ?></td>
                                        <td>Rs <?= number_format($row['pending']) ?></td>
                                        <td>Rs <?= number_format($row['approved']) ?></td>
                                        <td><?= number_format($row['transactions']) ?></td>
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