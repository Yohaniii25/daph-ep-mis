<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'veterinary_surgeon') die("Access denied");

// Demo training reports
$trainings = [
    ['date' => '2026-01-15', 'topic' => 'Modern Dairy Farming', 'farmers' => 35, 'location' => 'Amparai', 'status' => 'Completed'],
    ['date' => '2026-01-10', 'topic' => 'Animal Health Management', 'farmers' => 28, 'location' => 'Sainthamaruthu', 'status' => 'Completed'],
    ['date' => '2025-12-20', 'topic' => 'Fodder Cultivation', 'farmers' => 42, 'location' => 'Karaitivu', 'status' => 'Pending'],
    ['date' => '2025-12-15', 'topic' => 'Artificial Insemination', 'farmers' => 20, 'location' => 'Office Hall', 'status' => 'Completed'],
];

// Filter logic (demo)
$filtered_trainings = $trainings;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    $status = $_GET['status'] ?? '';

    $filtered_trainings = array_filter($trainings, function ($t) use ($start_date, $end_date, $status) {
        $date_match = true;
        if ($start_date) $date_match = $date_match && (strtotime($t['date']) >= strtotime($start_date));
        if ($end_date) $date_match = $date_match && (strtotime($t['date']) <= strtotime($end_date));

        $status_match = !$status || $t['status'] === $status;

        return $date_match && $status_match;
    });
}
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Training Reports</h2>

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
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="Completed" <?= ($_GET['status'] ?? '') === 'Completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="Pending" <?= ($_GET['status'] ?? '') === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        </select>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                        <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Training Reports Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 style="color: white;" class="mb-0">Training Reports</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Topic</th>
                                <th>Farmers Attended</th>
                                <th>Location</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($filtered_trainings)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No training reports found matching the filters</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($filtered_trainings as $t): ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($t['date'])) ?></td>
                                        <td><strong><?= htmlspecialchars($t['topic']) ?></strong></td>
                                        <td><?= $t['farmers'] ?></td>
                                        <td><?= htmlspecialchars($t['location']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $t['status'] === 'Completed' ? 'success' : 'warning' ?>">
                                                <?= $t['status'] ?>
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