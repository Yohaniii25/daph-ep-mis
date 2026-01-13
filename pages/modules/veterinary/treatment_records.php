<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'veterinary_surgeon') die("Access denied");

// Demo treatments with status
$treatments = [
    ['date' => '2026-01-10', 'code' => 'DAPH-045', 'animal' => 'Cow', 'owner' => 'Farmer A', 'treatment' => 'Antibiotic injection', 'type' => 'Outdoor', 'status' => 'Completed'],
    ['date' => '2026-01-09', 'code' => 'DAPH-112', 'animal' => 'Buffalo', 'owner' => 'Farmer B', 'treatment' => 'Wound dressing', 'type' => 'Indoor', 'status' => 'Ongoing'],
    ['date' => '2026-01-08', 'code' => 'DAPH-078', 'animal' => 'Goat', 'owner' => 'Farmer C', 'treatment' => 'Deworming', 'type' => 'Outdoor', 'status' => 'Completed'],
    ['date' => '2026-01-07', 'code' => 'DAPH-023', 'animal' => 'Cow', 'owner' => 'Farmer D', 'treatment' => 'Vaccination', 'type' => 'Outdoor', 'status' => 'Ongoing'],
    ['date' => '2026-01-06', 'code' => 'DAPH-056', 'animal' => 'Sheep', 'owner' => 'Farmer E', 'treatment' => 'Deworming', 'type' => 'Outdoor', 'status' => 'Completed'],
];

// Filter logic (demo)
$filtered_treatments = $treatments;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    $type = $_GET['type'] ?? '';
    $status = $_GET['status'] ?? '';

    $filtered_treatments = array_filter($treatments, function ($t) use ($start_date, $end_date, $type, $status) {
        $date_match = true;
        if ($start_date) $date_match = $date_match && (strtotime($t['date']) >= strtotime($start_date));
        if ($end_date) $date_match = $date_match && (strtotime($t['date']) <= strtotime($end_date));

        $type_match = !$type || $t['type'] === $type;
        $status_match = !$status || $t['status'] === $status;

        return $date_match && $type_match && $status_match;
    });
}
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Treatment Records Search</h2>

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
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="">All</option>
                            <option value="Outdoor" <?= ($_GET['type'] ?? '') === 'Outdoor' ? 'selected' : '' ?>>Outdoor</option>
                            <option value="Indoor" <?= ($_GET['type'] ?? '') === 'Indoor' ? 'selected' : '' ?>>Indoor</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="Ongoing" <?= ($_GET['status'] ?? '') === 'Ongoing' ? 'selected' : '' ?>>Ongoing</option>
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

        <!-- Records Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark">
                <h5 style="color: white;" class="mb-0">Treatment Records</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>DATE</th>
                                <th>ANIMAL CODE</th>
                                <th>ANIMAL TYPE</th>
                                <th>OWNER</th>
                                <th>TREATMENT</th>
                                <th>TYPE</th>
                                <th>STATUS</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($filtered_treatments)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No records found matching the filters</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($filtered_treatments as $t): ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($t['date'])) ?></td>
                                        <td><strong><?= htmlspecialchars($t['code']) ?></strong></td>
                                        <td><?= htmlspecialchars($t['animal']) ?></td>
                                        <td><?= htmlspecialchars($t['owner']) ?></td>
                                        <td><?= htmlspecialchars($t['treatment']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $t['type'] === 'Indoor' ? 'info' : 'success' ?>">
                                                <?= $t['type'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $t['status'] === 'Completed' ? 'success' : 'warning' ?>">
                                                <?= $t['status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1" disabled>View</button>
                                            <button class="btn btn-sm btn-outline-secondary" disabled>Edit</button>
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