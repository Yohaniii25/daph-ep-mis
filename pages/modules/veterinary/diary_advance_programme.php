<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'veterinary_surgeon') die("Access denied");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    header("Location: " . $_SERVER['PHP_SELF'] . (isset($_GET['type']) ? "?type=" . urlencode($_GET['type']) : ""));
    exit;
}

// Demo entries
$entries = [
    ['type' => 'Diary', 'title' => 'Meeting with Provincial Director', 'notes' => 'Attended meeting with Provincial Director at DAPH Head Office. Submitted district revenue report and discussed pending vehicle repairs.', 'date' => '2026-01-12', 'status' => 'Completed'],
    ['type' => 'Programme', 'title' => 'Inspection of Veterinary', 'notes' => 'Inspected Sainthamaruthu Veterinary Office. Checked drug stock level.', 'date' => '2026-01-07', 'status' => 'Completed'],
    ['type' => 'Programme', 'title' => 'Inspect VS Office', 'notes' => 'Check stock levels...', 'date' => '2026-01-15', 'status' => 'Pending'],
    ['type' => 'Diary', 'title' => 'Field Visit Report', 'notes' => 'Inspected farms in Amparai...', 'date' => '2026-01-10', 'status' => 'Completed'],
    ['type' => 'Programme', 'title' => 'Prepare Monthly Report', 'notes' => 'Compile revenue data...', 'date' => '2026-01-20', 'status' => 'Pending'],
];

// Filter logic
$filtered_entries = $entries;
if (isset($_GET['type']) && $_GET['type'] !== 'All') {
    $filter_type = $_GET['type'];
    $filtered_entries = array_filter($entries, fn($e) => $e['type'] === $filter_type);
}
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Advance Programmes & Diaries</h2>

        <!-- Quick Actions -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#addDiaryModal">
                            <i class="bi bi-journal-text"></i><br>
                            Add Daily Entry
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100 py-3" data-bs-toggle="modal" data-bs-target="#addProgrammeModal">
                            <i class="bi bi-list-task"></i><br>
                            Add Advance Programme
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5>Filter Entries</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="All" <?= !isset($_GET['type']) || $_GET['type'] === 'All' ? 'selected' : '' ?>>All</option>
                            <option value="Diary" <?= isset($_GET['type']) && $_GET['type'] === 'Diary' ? 'selected' : '' ?>>Diary</option>
                            <option value="Programme" <?= isset($_GET['type']) && $_GET['type'] === 'Programme' ? 'selected' : '' ?>>Advance Programme</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">Apply Filter</button>
                        <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Main Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 style="color: white;">All Entries</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-hover mb-0">
                        <thead class="table-light" style="position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th>Type</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th style="min-width: 200px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($filtered_entries)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No entries found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($filtered_entries as $entry): ?>
                                    <tr>
                                        <td style="white-space: nowrap;">
                                            <?php
                                            $color = '';
                                            if ($entry['type'] === 'Diary') {
                                                $color = '#820100';
                                            } elseif ($entry['type'] === 'Programme') {
                                                $color = '#370709';
                                            }
                                            ?>
                                            <span class="badge" style="background-color: <?= $color ?>; color: #fff;">
                                                <?= htmlspecialchars($entry['type']) ?>
                                            </span>
                                        </td>
                                        <td style="white-space: nowrap;"><strong><?= htmlspecialchars($entry['title']) ?></strong></td>
                                        <td><?= htmlspecialchars($entry['notes']) ?></td>
                                        <td style="white-space: nowrap;"><?= $entry['date'] ?></td>
                                        <td style="white-space: nowrap;">
                                            <span class="badge bg-<?= $entry['status'] === 'Completed' ? 'success' : 'secondary' ?>">
                                                <?= $entry['status'] ?>
                                            </span>
                                            <?php if ($entry['type'] === 'Programme' && $entry['status'] === 'Pending'): ?>
                                                <div style="margin-top: 8px;">

                                                    <button type="submit" name="complete_task" class="btn btn-sm btn-success" style="white-space: nowrap;">
                                                        <i class="bi bi-check2-circle"></i> Mark as Completed
                                                    </button>
                                                </div>

                                            <?php endif; ?>
                                        </td>
                                        <td style="white-space: nowrap;">
                                            <button class="btn btn-sm btn-outline-primary me-1">View</button>
                                            <button class="btn btn-sm btn-warning me-1">Edit</button>
                                            <button class="btn btn-sm btn-danger">Delete</button>
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

<!-- Add Daily Diary Modal -->
<div class="modal fade" id="addDiaryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-journal-text me-2"></i>Add Daily Diary Entry</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Details / Notes</label>
                        <textarea class="form-control" rows="5" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" disabled>Save Diary Entry</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Advance Programme Modal -->
<div class="modal fade" id="addProgrammeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-list-task me-2"></i>Add Advance Programme / Task</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Details / Notes</label>
                        <textarea class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" class="form-control">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" disabled>Save Programme</button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>