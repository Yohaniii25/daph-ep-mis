<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'administrator') die("Access denied");
require_once '../../../config/db_connect.php';
require_once dirname(__DIR__, 2) . '/config/constants.php';  // Go up 2 levels from includes → root/config

$message = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_employee'])) {
        $name = trim($_POST['name']);
        $address = trim($_POST['address']);
        $reg_date = $_POST['reg_date'];
        $department = $_POST['department'];

        // Generate REG_ID
        $year = date('y', strtotime($reg_date)); // 25 for 2025
        $result = $mysqli->query("SELECT COUNT(*) as count FROM staff WHERE reg_id LIKE 'REG_$year%'");
        $row = $result->fetch_assoc();
        $next = str_pad($row['count'] + 1, 5, '0', STR_PAD_LEFT);
        $reg_id = "REG_$year$next";

        $stmt = $mysqli->prepare("INSERT INTO staff (reg_id, name, address, reg_date, department) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $reg_id, $name, $address, $reg_date, $department);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Employee added: <strong>' . $reg_id . '</strong></div>';
        } else {
            $message = '<div class="alert alert-danger">Error: Duplicate or invalid data.</div>';
        }
    }

    if (isset($_POST['delete_employee'])) {
        $id = (int)$_POST['id'];
        $mysqli->query("DELETE FROM staff WHERE id = $id");
        $message = '<div class="alert alert-success">Employee deleted.</div>';
    }
}

// Filters (GET)
$where = [];
$params = [];
$types = '';

if (!empty($_GET['reg_date'])) {
    $where[] = "reg_date = ?";
    $params[] = $_GET['reg_date'];
    $types .= 's';
}

if (!empty($_GET['department'])) {
    $where[] = "department = ?";
    $params[] = $_GET['department'];
    $types .= 's';
}

$sql = "SELECT * FROM staff";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY reg_date DESC";

$stmt = $mysqli->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$employees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$depts = $mysqli->query("SELECT DISTINCT department FROM staff ORDER BY department")->fetch_all(MYSQLI_ASSOC);
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Employee Management</h2>

        <?= $message ?>

        <!-- Filters + Add Button -->
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Filter Employees</h5>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                    Add Employee
                </button>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label>Date</label>
                        <input type="date" name="reg_date" class="form-control" value="<?= $_GET['reg_date'] ?? '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label>Department</label>
                        <select name="department" class="form-select">
                            <option value="">All</option>
                            <?php foreach ($depts as $d): ?>
                            <option value="<?= $d['department'] ?>" <?= ($_GET['department'] ?? '') == $d['department'] ? 'selected' : '' ?>>
                                <?= $d['department'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 align-self-end">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="?" class="btn btn-secondary">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Employee Table -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h5>Employees (<?= count($employees) ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>REG_ID</th>
                                <th>NAME</th>
                                <th>ADDRESS</th>
                                <th>REG_DATE</th>
                                <th>DEPARTMENT</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td><strong><?= $emp['reg_id'] ?></strong></td>
                                <td><?= htmlspecialchars($emp['name']) ?></td>
                                <td><?= htmlspecialchars($emp['address']) ?></td>
                                <td><?= date('d M Y', strtotime($emp['reg_date'])) ?></td>
                                <td><?= $emp['department'] ?></td>
                                <td>
                                    <!-- Edit button (future) -->
                                    <button class="btn btn-sm btn-primary me-1" disabled>Edit</button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $emp['id'] ?>">
                                        <button type="submit" name="delete_employee" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">
                                            Delete
                                        </button>
                                    </form>
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

<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>Registration Date</label>
                            <input type="date" name="reg_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label>Department</label>
                            <input type="text" name="department" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label>Address</label>
                            <textarea name="address" class="form-control" rows="3" required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_employee" class="btn btn-success">Add Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>