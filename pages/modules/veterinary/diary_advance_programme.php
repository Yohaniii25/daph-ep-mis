<?php
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

if ($_SESSION['role'] !== 'veterinary_surgeon') {
    die("Access denied");
}

$message = '';
$user_id = $_SESSION['user_id'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_diary'])) {
        $entry_date = date('Y-m-d');
        $title = trim($_POST['title']);
        $notes = trim($_POST['notes']);

        if ($title && $notes) {
            $stmt = $mysqli->prepare("INSERT INTO diary_entries (user_id, entry_date, title, notes, status) VALUES (?, ?, ?, ?, 'Draft')");
            $stmt->bind_param("isss", $user_id, $entry_date, $title, $notes);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Diary entry added successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Error adding diary entry.</div>';
            }
            $stmt->close();
        } else {
            $message = '<div class="alert alert-warning">Please fill title and notes.</div>';
        }
    }

    if (isset($_POST['add_programme'])) {
        $entry_date = $_POST['entry_date'];
        $title = trim($_POST['title']);
        $notes = trim($_POST['notes']);

        if ($title && $notes && $entry_date) {
            $stmt = $mysqli->prepare("INSERT INTO diary_entries (user_id, entry_date, title, notes, status) VALUES (?, ?, ?, ?, 'Draft')");
            $stmt->bind_param("isss", $user_id, $entry_date, $title, $notes);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Advance programme added successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Error adding programme.</div>';
            }
            $stmt->close();
        } else {
            $message = '<div class="alert alert-warning">Please fill all fields.</div>';
        }
    }

    if (isset($_POST['submit_entry'])) {
        $id = (int)$_POST['entry_id'];
        $stmt = $mysqli->prepare("UPDATE diary_entries SET status = 'Submitted' WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Entry submitted for approval!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error submitting entry.</div>';
        }
        $stmt->close();
    }
}

// Fetch user's entries
$stmt = $mysqli->prepare("SELECT * FROM diary_entries WHERE user_id = ? ORDER BY entry_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$entries = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">My Diary & Advance Programme</h2>

        <?= $message ?>

        <div class="row g-4 mb-5">
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div style="background-color: #689ccf;" class="card-header text-white">
                        <h5 class="mb-0">Add Today's Diary Entry</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes / Details</label>
                                <textarea name="notes" class="form-control" rows="4" required></textarea>
                            </div>
                            <p class="text-muted small">Entry Date: <strong><?= date('d M Y') ?></strong> (Today)</p>
                            <button style="background-color: #689ccf;" type="submit" name="add_diary" class="btn">Save Diary Entry</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Add Advance Programme -->
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div style="background-color: #370709;" class="card-header text-white">
                        <h5 style="color: white;" class="mb-0">Add Advance Programme / Task</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Programme Date</label>
                                <input type="date" name="entry_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Details</label>
                                <textarea name="notes" class="form-control" rows="4" required></textarea>
                            </div>
                            <button style="background-color: #370709; color: white;" type="submit" name="add_programme" class="btn">Add Programme</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Entries List -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 style="color : white;" class="mb-0">My Diary & Advance Programmes</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Title</th>
                                <th>Notes</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($entries)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No entries found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($entries as $entry): ?>
                                    <?php
                                    $is_today = date('Y-m-d', strtotime($entry['entry_date'])) === date('Y-m-d');
                                    $type = $is_today ? 'Diary' : 'Programme/Task';
                                    ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($entry['entry_date'])) ?></td>
                                        <td>
                                            <span class="badge <?= $type === 'Diary' ? 'bg-info' : '' ?>"
                                                style="<?= $type !== 'Diary' ? 'background-color: #370709; color: white;' : '' ?>">
                                                <?= $type ?>
                                            </span>
                                        </td>
                                        <td><strong><?= htmlspecialchars($entry['title']) ?></strong></td>
                                        <td><?= nl2br(htmlspecialchars($entry['notes'])) ?></td>
                                        <td>
                                            <span class="badge 
                                                <?= $entry['status'] === 'Approved' ? 'bg-success' : ($entry['status'] === 'Submitted' ? '' : 'bg-secondary') ?>"
                                                style="<?= $entry['status'] === 'Submitted' ? 'background-color: #370709; color: white;' : '' ?>">
                                                <?= $entry['status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($entry['status'] === 'Draft'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="entry_id" value="<?= $entry['id'] ?>">
                                                    <button type="submit" name="submit_entry" class="btn btn-sm btn-primary">
                                                        Submit for Approval
                                                    </button>
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

<?php require_once '../../../includes/footer.php'; ?>