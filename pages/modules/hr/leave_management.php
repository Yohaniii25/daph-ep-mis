<?php
require_once '../../../includes/header.php';

if ($_SESSION['role'] !== 'administrator') {
    die("Access denied");
}

require_once '../../../config/db_connect.php';

$message = '';

// Handle approve/reject
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $stmt = $mysqli->prepare("UPDATE leave_requests SET status = 'Approved' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $message = '<div class="alert alert-success">Leave request approved!</div>';
}

if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    $stmt = $mysqli->prepare("UPDATE leave_requests SET status = 'Rejected' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $message = '<div class="alert alert-success">Leave request rejected!</div>';
}

// Filter by status
$status = $_GET['status'] ?? '';

// Build query
$sql = "SELECT lr.*, s.reg_id, s.name, s.department 
        FROM leave_requests lr 
        JOIN staff s ON lr.staff_id = s.id";

if ($status) {
    $sql .= " WHERE lr.status = ?";
}

$sql .= " ORDER BY lr.applied_date DESC";

$stmt = $mysqli->prepare($sql);
if ($status) {
    $stmt->bind_param("s", $status);
}
$stmt->execute();
$result = $stmt->get_result();
$leaves = $result->fetch_all(MYSQLI_ASSOC);

// Count for buttons
$counts = [
    'All' => $mysqli->query("SELECT COUNT(*) FROM leave_requests")->fetch_row()[0],
    'Pending' => $mysqli->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'Pending'")->fetch_row()[0],
    'Approved' => $mysqli->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'Approved'")->fetch_row()[0],
    'Rejected' => $mysqli->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'Rejected'")->fetch_row()[0],
];
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Leave Management</h2>

        <?= $message ?>

        <!-- Status Buttons -->
        <div class="mb-4">
            <a href="leave_management.php" class="btn <?= $status === '' ? 'btn-primary' : 'btn-outline-primary' ?> me-2">
                All Requests (<?= $counts['All'] ?>)
            </a>
            <a href="?status=Pending" class="btn <?= $status === 'Pending' ? 'btn-warning' : 'btn-outline-warning' ?> me-2">
                Pending (<?= $counts['Pending'] ?>)
            </a>
            <a href="?status=Approved" class="btn <?= $status === 'Approved' ? 'btn-success' : 'btn-outline-success' ?> me-2">
                Approved (<?= $counts['Approved'] ?>)
            </a>
            <a href="?status=Rejected" class="btn <?= $status === 'Rejected' ? 'btn-danger' : 'btn-outline-danger' ?> me-2">
                Rejected (<?= $counts['Rejected'] ?>)
            </a>
        </div>

        <!-- Leave Table -->
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between">
                <h5>Leave Requests</h5>
                <button class="btn btn-info">Export CSV</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>REG_ID</th>
                                <th>NAME</th>
                                <th>REASON</th>
                                <th>PERIOD</th>
                                <th>DEPARTMENT</th>
                                <th>STATUS</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($leaves)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">No leave requests found</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($leaves as $leave): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($leave['reg_id']) ?></strong></td>
                                <td><?= htmlspecialchars($leave['name']) ?></td>
                                <td><?= htmlspecialchars($leave['reason']) ?></td>
                                <td><?= date('d M Y', strtotime($leave['from_date'])) ?> - <?= date('d M Y', strtotime($leave['to_date'])) ?></td>
                                <td><?= htmlspecialchars($leave['department']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $leave['status'] === 'Approved' ? 'success' : ($leave['status'] === 'Rejected' ? 'danger' : 'warning') ?>">
                                        <?= $leave['status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($leave['status'] === 'Pending'): ?>
                                    <a href="?approve=<?= $leave['id'] ?>" class="btn btn-sm btn-success me-1" onclick="return confirm('Approve this leave?')">Approve</a>
                                    <a href="?reject=<?= $leave['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject this leave?')">Reject</a>
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