<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'veterinary_surgeon') die("Access denied");

// Demo
$reports = [
    ['date' => '2026-01-08', 'case' => 'Illegal Transport', 'act_section' => 'Section 3', 'status' => 'Reported'],
    ['date' => '2026-01-06', 'case' => 'Animal Cruelty', 'act_section' => 'Section 2', 'status' => 'Investigation'],
    ['date' => '2025-12-30', 'case' => 'Post-mortem Examination', 'act_section' => 'Forensic', 'status' => 'Completed'],
];

// Filter logic (demo - filter array)
$filtered_reports = $reports;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $start_date = $_GET['start_date'] ?? '';
    $status = $_GET['status'] ?? '';
    $act_type = $_GET['act_type'] ?? '';

    $filtered_reports = array_filter($reports, function ($r) use ($start_date, $status, $act_type) {
        $date_match = true;
        if ($start_date) $date_match = $date_match && (strtotime($r['date']) >= strtotime($start_date));

        $status_match = !$status || $r['status'] === $status;
        $type_match = !$act_type || stripos($r['act_section'], $act_type) !== false;

        return $date_match && $status_match && $type_match;
    });
}

$statuses = array_unique(array_column($reports, 'status'));
$act_types = array_unique(array_column($reports, 'act_section'));
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Animals Act & Forensic Reporting</h2>

        <!-- Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <?php foreach ($statuses as $s): ?>
                                <option value="<?= htmlspecialchars($s) ?>" <?= ($status === $s) ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Act Section / Type</label>
                        <select name="act_type" class="form-select">
                            <option value="">All</option>
                            <?php foreach ($act_types as $t): ?>
                                <option value="<?= htmlspecialchars($t) ?>" <?= ($act_type === $t) ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
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

        <div class="card shadow-sm" id="reportsTable">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 style="color: white;" class="mb-0">Reports</h5>

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
                                <th>Case Description</th>
                                <th>Act Section / Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($filtered_reports)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No reports found matching the filters</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($filtered_reports as $r): ?>
                                <tr>
                                    <td><?= date('d M Y', strtotime($r['date'])) ?></td>
                                    <td><strong><?= htmlspecialchars($r['case']) ?></strong></td>
                                    <td><?= $r['act_section'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= $r['status'] === 'Completed' ? 'success' : ($r['status'] === 'Investigation' ? 'warning' : 'info') ?>">
                                            <?= $r['status'] ?>
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