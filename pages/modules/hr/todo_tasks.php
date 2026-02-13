
<?php
session_start();
require_once '../../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];

    if (isset($_POST['add_diary'])) {
        $title = trim($_POST['title']);
        $notes = trim($_POST['notes']);
        if ($title && $notes) {
            $stmt = $mysqli->prepare("INSERT INTO admin_diaries_todo (type, title, description, created_by, entry_date) VALUES ('Diary', ?, ?, ?, NOW())");
            $stmt->bind_param("ssi", $title, $notes, $user_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Daily Diary added successfully!";
            } else {
                $_SESSION['error'] = "Error adding diary.";
            }
            $stmt->close();
        }
    }
    if (isset($_POST['add_programme'])) {
        $title = trim($_POST['title']);
        $notes = trim($_POST['notes']);
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : NULL;
        if ($title && $notes) {
            $stmt = $mysqli->prepare("INSERT INTO admin_diaries_todo (type, title, description, due_date, created_by) VALUES ('Task', ?, ?, ?, ?)");
            $stmt->bind_param("sssi", $title, $notes, $due_date, $user_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Advance Programme added successfully!";
            } else {
                $_SESSION['error'] = "Error adding programme.";
            }
            $stmt->close();
        }
    }
    // Mark as complete
    if (isset($_POST['complete_task']) && isset($_POST['task_id'])) {
        $id = (int)$_POST['task_id'];
        $stmt = $mysqli->prepare("UPDATE admin_diaries_todo SET status = 'Completed' WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Task marked as completed!";
        } else {
            $_SESSION['error'] = "Error marking as completed.";
        }
        $stmt->close();
    }
    // Delete entry
    if (isset($_POST['delete_entry']) && isset($_POST['delete_id'])) {
        $id = (int)$_POST['delete_id'];
        $stmt = $mysqli->prepare("DELETE FROM admin_diaries_todo WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Entry deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting entry.";
        }
        $stmt->close();
    }
    // Edit entry
    if (isset($_POST['edit_entry']) && isset($_POST['edit_id'])) {
        $id = (int)$_POST['edit_id'];
        $title = trim($_POST['edit_title']);
        $notes = trim($_POST['edit_notes']);
        $due_date = isset($_POST['edit_due_date']) ? $_POST['edit_due_date'] : null;
        // Get type
        $stmt = $mysqli->prepare("SELECT type FROM admin_diaries_todo WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($type);
        $stmt->fetch();
        $stmt->close();
        if ($type === 'Task') {
            $stmt = $mysqli->prepare("UPDATE admin_diaries_todo SET title = ?, description = ?, due_date = ? WHERE id = ?");
            $stmt->bind_param("sssi", $title, $notes, $due_date, $id);
        } else {
            $stmt = $mysqli->prepare("UPDATE admin_diaries_todo SET title = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $title, $notes, $id);
        }
        if ($stmt->execute()) {
            $_SESSION['success'] = "Entry updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating entry.";
        }
        $stmt->close();
    }
    // Redirect to prevent resubmission on refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'administrator') {
    die("Access denied");
}

// Show flash message
$message = '';
if (isset($_SESSION['success'])) {
    $message = '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $message = '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}

// Fetch all entries
$stmt = $mysqli->prepare("SELECT * FROM admin_diaries_todo ORDER BY entry_date DESC, due_date ASC");
$stmt->execute();
$result = $stmt->get_result();
$entries = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Advance Programmes & Diaries</h2>

        <?= $message ?>

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

        <!-- Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5>All Entries (Tasks & Diary)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entries as $entry): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-<?= $entry['type'] === 'Task' ? 'warning' : 'info' ?>">
                                        <?= $entry['type'] ?>
                                    </span>
                                </td>
                                <td><strong><?= htmlspecialchars($entry['title']) ?></strong></td>
                                <td><?= htmlspecialchars($entry['description']) ?: '<em>No description</em>' ?></td>
                                <td>
                                    <?php if ($entry['type'] === 'Task' && $entry['due_date']): ?>
                                        Due: <?= date('d M Y', strtotime($entry['due_date'])) ?>
                                    <?php else: ?>
                                        <?= isset($entry['entry_date']) && $entry['entry_date'] ? date('d M Y', strtotime($entry['entry_date'])) : '<em>No date</em>' ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-2">
                                        <span class="badge bg-<?= $entry['status'] === 'Completed' ? 'success' : 'secondary' ?>" style="width: fit-content;">
                                            <?= $entry['status'] ?>
                                        </span>
                                        <?php if ($entry['type'] === 'Task' && $entry['status'] === 'Pending'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="task_id" value="<?= $entry['id'] ?>">
                                                <button type="submit" name="complete_task" class="btn btn-sm btn-success" style="white-space: nowrap;">
                                                    <i class="bi bi-check2-circle"></i> Mark as Completed
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <!-- Edit Button -->
                                    <button class="btn btn-sm btn-warning" title="Edit" data-bs-toggle="modal" data-bs-target="#editModal<?= $entry['id'] ?>">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
                                    <!-- Delete Button -->
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this entry?');">
                                        <input type="hidden" name="delete_id" value="<?= $entry['id'] ?>">
                                        <button type="submit" name="delete_entry" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal<?= $entry['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header bg-warning text-dark">
                                                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Entry</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="edit_id" value="<?= $entry['id'] ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Title</label>
                                                            <input type="text" name="edit_title" class="form-control" value="<?= htmlspecialchars($entry['title']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Details / Notes</label>
                                                            <textarea name="edit_notes" class="form-control" rows="4" required><?= htmlspecialchars($entry['description']) ?></textarea>
                                                        </div>
                                                        <?php if ($entry['type'] === 'Task'): ?>
                                                        <div class="mb-3">
                                                            <label class="form-label">Due Date</label>
                                                            <input type="date" name="edit_due_date" class="form-control" value="<?= $entry['due_date'] ?>">
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="edit_entry" class="btn btn-warning">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
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
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Details / Notes</label>
                        <textarea name="notes" class="form-control" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_diary" class="btn btn-success">Save Diary Entry</button>
                </div>
            </form>
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
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Details / Notes</label>
                        <textarea name="notes" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_programme" class="btn btn-primary">Save Programme</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>