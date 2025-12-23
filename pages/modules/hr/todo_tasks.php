<?php
require_once '../../../includes/header.php';

if ($_SESSION['role'] !== 'administrator') {
    die("Access denied");
}

require_once '../../../config/db_connect.php';

$message = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_task'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $due_date = $_POST['due_date'] ?: NULL;

        $stmt = $mysqli->prepare("INSERT INTO admin_diaries_todo (type, title, description, due_date, created_by) VALUES ('Task', ?, ?, ?, ?)");
        $stmt->bind_param("sssi", $title, $description, $due_date, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Task added successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error adding task.</div>';
        }
    }

    if (isset($_POST['add_diary'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);

        $stmt = $mysqli->prepare("INSERT INTO admin_diaries_todo (type, title, description, created_by) VALUES ('Diary', ?, ?, ?)");
        $stmt->bind_param("ssi", $title, $description, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Diary entry added!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error adding diary entry.</div>';
        }
    }

    if (isset($_POST['complete_task'])) {
        $id = (int)$_POST['task_id'];
        $stmt = $mysqli->prepare("UPDATE admin_diaries_todo SET status = 'Completed' WHERE id = ? AND type = 'Task'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $message = '<div class="alert alert-success">Task marked as completed!</div>';
    }
}

// Fetch all entries
$stmt = $mysqli->prepare("SELECT * FROM admin_diaries_todo ORDER BY entry_date DESC, due_date ASC");
$stmt->execute();
$result = $stmt->get_result();
$entries = $result->fetch_all(MYSQLI_ASSOC);
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Advance Programmes & Diaries - Additional Provincial Director</h2>

        <?= $message ?>

        <div class="row g-4">
            <!-- Add Task -->
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5>Add New Task (Advance Programme)</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Due Date</label>
                                <input type="date" name="due_date" class="form-control">
                            </div>
                            <button type="submit" name="add_task" class="btn btn-primary">Add Task</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Add Diary Entry -->
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5>Add Diary Entry</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Title (e.g., Meeting with District DD)</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Details / Notes</label>
                                <textarea name="description" class="form-control" rows="3" required></textarea>
                            </div>
                            <button type="submit" name="add_diary" class="btn btn-info">Add Diary Entry</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- List -->
        <div class="card shadow-sm mt-4">
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
                                        <?= date('d M Y', strtotime($entry['entry_date'])) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $entry['status'] === 'Completed' ? 'success' : 'secondary' ?>">
                                        <?= $entry['status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($entry['type'] === 'Task' && $entry['status'] === 'Pending'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="task_id" value="<?= $entry['id'] ?>">
                                        <button type="submit" name="complete_task" class="btn btn-sm btn-success">
                                            Mark Complete
                                        </button>
                                    </form>
                                    <?php endif; ?>
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

<?php require_once '../../../includes/footer.php'; ?>