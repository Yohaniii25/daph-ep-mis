<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'planning_officer') die("Access denied");

function humanizeProjectType(string $projectId): string
{
    if (str_starts_with($projectId, 'PSDG')) return 'PSDG Programme';
    if (str_starts_with($projectId, 'CBG'))  return 'Community Based Grant';
    if (str_starts_with($projectId, 'NGO'))  return 'NGO Implemented Project';
    if (str_starts_with($projectId, 'LM'))   return 'Line Ministry Project';
    return 'Other';
}

// Demo progress data
$progress = [
    [
        'project_id' => 'PSDG-001',
        'project' => 'Dairy Development Programme',
        'physical' => 49,
        'financial' => 49,
        'updated' => '2026-01-10',
        'status' => 'Ongoing'
    ],
    [
        'project_id' => 'CBG-002',
        'project' => 'Fodder Cultivation & Distribution',
        'physical' => 56,
        'financial' => 56,
        'updated' => '2026-01-08',
        'status' => 'Ongoing'
    ],
    [
        'project_id' => 'NGO-003',
        'project' => 'Goat Rearing Project - Batticaloa',
        'physical' => 43,
        'financial' => 43,
        'updated' => '2026-01-05',
        'status' => 'Ongoing'
    ],
    [
        'project_id' => 'LM-004',
        'project' => 'Artificial Insemination Enhancement',
        'physical' => 100,
        'financial' => 100,
        'updated' => '2025-12-31',
        'status' => 'Completed'
    ],
];


$filtered_progress = $progress;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $status = $_GET['status'] ?? '';
    $project_type = $_GET['project_type'] ?? '';

    $filtered_progress = array_filter($progress, function ($p) use ($status, $project_type) {
        $status_match = !$status || $p['status'] === $status;
        $type_match   = !$project_type || stripos($p['project_id'], $project_type) !== false;
        return $status_match && $type_match;
    });
}
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Progress Reports (Physical & Financial)</h2>
            <button style="font-size: 16px;" class="btn btn-success btn-lg">
                <i class="bi bi-download me-2"></i>Export Report
            </button>
        </div>


        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Project Type</label>
                        <select name="project_type" class="form-select">
                            <option value="">All</option>
                            <option value="PSDG">PSDG</option>
                            <option value="CBG">CBG</option>
                            <option value="NGO">NGO</option>
                            <option value="LM">Line Ministry</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="Ongoing">Ongoing</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                        <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Progress Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 style="color: white;" class="mb-0">Physical & Financial Progress Reports</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Project ID</th>
                                <th>Project Name</th>
                                <th>Project Type</th>
                                <th>Physical Progress (%)</th>
                                <th>Financial Progress (%)</th>
                                <th>Last Updated</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($filtered_progress)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No progress reports found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($filtered_progress as $p): ?>
                                <tr>
                                    <td><strong><?= $p['project_id'] ?></strong></td>
                                    <td><?= htmlspecialchars($p['project']) ?></td>
                                    <td><?= humanizeProjectType($p['project_id']) ?></td>

                                    <td>
                                        <div class="progress" style="height: 28px;">
                                            <div class="progress-bar 
                                                <?= $p['physical'] >= 80 ? 'bg-success' : 'bg-warning' ?>"
                                                style="width: <?= $p['physical'] ?>%; min-width: 45px;">
                                                <?= $p['physical'] ?>%
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="progress" style="height: 28px;">
                                            <div class="progress-bar 
                                                <?= $p['financial'] >= 80 ? 'bg-success' : 'bg-warning' ?>"
                                                style="width: <?= $p['financial'] ?>%; min-width: 45px;">
                                                <?= $p['financial'] ?>%
                                            </div>
                                        </div>
                                    </td>

                                    <td><?= date('d M Y', strtotime($p['updated'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $p['status'] === 'Completed' ? 'success' : 'warning' ?>">
                                            <?= $p['status'] ?>
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
