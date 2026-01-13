<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'veterinary_surgeon') die("Access denied");

$campaigns = [
    ['date' => '2026-01-15', 'vaccine' => 'FMD', 'animals' => 500, 'location' => 'Amparai', 'status' => 'Pending'],
    ['date' => '2026-01-10', 'vaccine' => 'Rabies', 'animals' => 200, 'location' => 'Karaitivu', 'status' => 'Completed'],
    ['date' => '2026-01-05', 'vaccine' => 'Brucellosis', 'animals' => 300, 'location' => 'Sainthamaruthu', 'status' => 'Completed'],
    ['date' => '2026-01-03', 'vaccine' => 'Rabies', 'animals' => 120, 'location' => 'Karaitivu', 'status' => 'Pending'],
    ['date' => '2025-12-28', 'vaccine' => 'Brucellosis', 'animals' => 280, 'location' => 'Sainthamaruthu', 'status' => 'Completed'],
];

$filtered_campaigns = $campaigns;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    $status = $_GET['status'] ?? '';

    $filtered_campaigns = array_filter($campaigns, function ($c) use ($start_date, $end_date, $status) {
        $date_match = true;
        if ($start_date) $date_match = $date_match && (strtotime($c['date']) >= strtotime($start_date));
        if ($end_date) $date_match = $date_match && (strtotime($c['date']) <= strtotime($end_date));

        $status_match = !$status || $c['status'] === $status;

        return $date_match && $status_match;
    });
}
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Immunization Reports</h2>

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
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="Pending" <?= ($_GET['status'] ?? '') === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Completed" <?= ($_GET['status'] ?? '') === 'Completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                        <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reports Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                <h5 style="color: white;" class="mb-0">Immunization Reports</h5>

                <a href="?export=csv" class="btn btn-sm btn-success">
                    <i class="fas fa-file-csv me-1"></i> Export CSV
                </a>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Vaccine</th>
                                <th>Animals Covered</th>
                                <th>Location</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($filtered_campaigns)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No reports found matching the filters</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($filtered_campaigns as $c): ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($c['date'])) ?></td>
                                        <td><strong><?= $c['vaccine'] ?></strong></td>
                                        <td><?= number_format($c['animals']) ?></td>
                                        <td><?= htmlspecialchars($c['location']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $c['status'] === 'Completed' ? 'success' : 'warning' ?>">
                                                <?= $c['status'] ?>
                                            </span>
                                        </td>
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