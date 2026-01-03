<?php
require_once '../../../includes/header.php';

if ($_SESSION['role'] !== 'provincial_director') {
    die("Access denied");
}

require_once '../../../config/db_connect.php';

$message = '';

// REDIRECTS FIRST — BEFORE ANY OUTPUT TO PREVENT DUPLICATES
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_entry'])) {
        $entry_date = $_POST['entry_date'];
        $title = trim($_POST['title']);
        $notes = trim($_POST['notes']);
        $user_id = $_SESSION['user_id'];

        $stmt = $mysqli->prepare("INSERT INTO diary_entries (user_id, entry_date, title, notes) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $entry_date, $title, $notes);
        $stmt->execute();

        header("Location: my_diary.php?added=1");
        exit();
    }

    if (isset($_POST['edit_entry'])) {
        $id = (int)$_POST['entry_id'];
        $entry_date = $_POST['entry_date'];
        $title = trim($_POST['title']);
        $notes = trim($_POST['notes']);

        $stmt = $mysqli->prepare("UPDATE diary_entries SET entry_date = ?, title = ?, notes = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sssii", $entry_date, $title, $notes, $id, $_SESSION['user_id']);
        $stmt->execute();

        header("Location: my_diary.php?updated=1");
        exit();
    }

    if (isset($_POST['delete_entry'])) {
        $id = (int)$_POST['entry_id'];
        $stmt = $mysqli->prepare("DELETE FROM diary_entries WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $_SESSION['user_id']);
        $stmt->execute();

        header("Location: my_diary.php?deleted=1");
        exit();
    }

    if (isset($_POST['submit_entry'])) {
        $id = (int)$_POST['entry_id'];
        $stmt = $mysqli->prepare("UPDATE diary_entries SET status = 'Submitted' WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $_SESSION['user_id']);
        $stmt->execute();

        header("Location: my_diary.php?submitted=1");
        exit();
    }
}

// Show messages from redirect
if (isset($_GET['added'])) $message = '<div class="alert alert-success">Diary entry added!</div>';
if (isset($_GET['updated'])) $message = '<div class="alert alert-success">Diary entry updated!</div>';
if (isset($_GET['deleted'])) $message = '<div class="alert alert-success">Diary entry deleted!</div>';
if (isset($_GET['submitted'])) $message = '<div class="alert alert-success">Diary entry submitted for approval!</div>';

// Fetch entries
$user_id = $_SESSION['user_id'];
$stmt = $mysqli->prepare("SELECT * FROM diary_entries WHERE user_id = ? ORDER BY entry_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$entries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// For edit modal
$edit_entry = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $mysqli->prepare("SELECT * FROM diary_entries WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $edit_id, $user_id);
    $stmt->execute();
    $edit_entry = $stmt->get_result()->fetch_assoc();
}
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">My Diary - Provincial Director</h2>

        <?= $message ?>

        <!-- Add New Entry -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Add New Diary Entry</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Entry Date</label>
                            <input type="date" name="entry_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes / Details</label>
                            <textarea name="notes" class="form-control" rows="4" required></textarea>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" name="add_entry" class="btn btn-success">Save Entry</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Diary Entries List -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">My Diary Entries</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>DATE</th>
                                <th>TITLE</th>
                                <th>NOTES</th>
                                <th>STATUS</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($entries)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">No diary entries found</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($entries as $entry): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($entry['entry_date'])) ?></td>
                                <td><strong><?= htmlspecialchars($entry['title']) ?></strong></td>
                                <td><?= nl2br(htmlspecialchars($entry['notes'])) ?></td>
                                <td>
                                    <span class="badge bg-<?= $entry['status'] === 'Submitted' ? 'warning' : ($entry['status'] === 'Approved' ? 'success' : 'secondary') ?>">
                                        <?= $entry['status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?edit=<?= $entry['id'] ?>" class="btn btn-sm btn-primary me-1" data-bs-toggle="modal" data-bs-target="#editModal">
                                        Edit
                                    </a>
                                    <form method="POST" style="display:inline;" onclick="return confirm('Delete this entry?')">
                                        <input type="hidden" name="entry_id" value="<?= $entry['id'] ?>">
                                        <button type="submit" name="delete_entry" class="btn btn-sm btn-danger me-1">Delete</button>
                                    </form>
                                    <?php if ($entry['status'] === 'Draft'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="entry_id" value="<?= $entry['id'] ?>">
                                        <button type="submit" name="submit_entry" class="btn btn-sm btn-info">Submit</button>
                                    </form>
                                    <?php endif; ?>
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

<!-- Edit Modal -->
<?php if ($edit_entry): ?>
<div class="modal fade show" id="editModal" tabindex="-1" style="display:block;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="entry_id" value="<?= $edit_entry['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Diary Entry</h5>
                    <a href="my_diary.php" class="btn-close"></a>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label>Entry Date</label>
                            <input type="date" name="entry_date" class="form-control" value="<?= $edit_entry['entry_date'] ?>" required>
                        </div>
                        <div class="col-md-8">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($edit_entry['title']) ?>" required>
                        </div>
                        <div class="col-12">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="5" required><?= htmlspecialchars($edit_entry['notes']) ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="my_diary.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" name="edit_entry" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal-backdrop fade show"></div>
<?php endif; ?>

<?php require_once '../../../includes/footer.php'; ?>