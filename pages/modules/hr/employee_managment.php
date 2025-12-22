<?php
require_once '../../../includes/header.php'; 

if ($_SESSION['role'] !== 'administrator') {
    die("Access denied");
}
require_once '../../../config/db_connect.php'; 
// Message
$message = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Employee
    if (isset($_POST['add_employee'])) {
        $name = trim($_POST['name']);
        $address = trim($_POST['address']);
        $reg_date = $_POST['reg_date'];
        $department = trim($_POST['department']);

        // Generate REG_ID: REG_2500001 (for 2025)
        $year = date('y', strtotime($reg_date));
        $result = $mysqli->query("SELECT COUNT(*) as count FROM staff WHERE reg_id LIKE 'REG_$year%'");
        $row = $result->fetch_assoc();
        $next = str_pad($row['count'] + 1, 5, '0', STR_PAD_LEFT);
        $reg_id = "REG_$year$next";

        $stmt = $mysqli->prepare("INSERT INTO staff (reg_id, name, address, reg_date, department) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $reg_id, $name, $address, $reg_date, $department);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Employee added successfully! REG_ID: <strong>' . $reg_id . '</strong></div>';
        } else {
            $message = '<div class="alert alert-danger">Error: Could not add employee (duplicate REG_ID or DB error).</div>';
        }
    }

    // Edit Employee
    if (isset($_POST['edit_employee'])) {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name']);
        $address = trim($_POST['address']);
        $reg_date = $_POST['reg_date'];
        $department = trim($_POST['department']);

        $stmt = $mysqli->prepare("UPDATE staff SET name = ?, address = ?, reg_date = ?, department = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $address, $reg_date, $department, $id);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Employee updated successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error updating employee.</div>';
        }
    }

    // Delete Employee
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
    $where[] = "department LIKE ?";
    $params[] = '%' . $_GET['department'] . '%';
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

// Get departments for filter
$depts_result = $mysqli->query("SELECT DISTINCT department FROM staff ORDER BY department");
$depts = $depts_result->fetch_all(MYSQLI_ASSOC);

// For edit modal
$edit_employee = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_stmt = $mysqli->prepare("SELECT * FROM staff WHERE id = ?");
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_employee = $edit_stmt->get_result()->fetch_assoc();
}
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Employee Management</h2>

        <?= $message ?>

        <!-- Filters + Add Button -->
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center bg-light">
                <h5 class="mb-0">Filter Employees</h5>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                    <i class="bi bi-plus-circle"></i> Add Employee
                </button>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Registration Date</label>
                        <input type="date" name="reg_date" class="form-control" value="<?= $_GET['reg_date'] ?? '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Department</label>
                        <select name="department" class="form-select">
                            <option value="">All Departments</option>
                            <?php foreach ($depts as $d): ?>
                            <option value="<?= $d['department'] ?>" <?= ($_GET['department'] ?? '') === $d['department'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['department']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">Apply Filter</button>
                        <a href="employee_management.php" class="btn btn-secondary">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Employees Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Registered Employees (<?= count($employees) ?>)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>REG_ID</th>
                                <th>NAME</th>
                                <th>ADDRESS</th>
                                <th>REG_DATE</th>
                                <th>DEPARTMENT</th>
                                <th class="text-center">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($employees)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">No employees found</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($emp['reg_id']) ?></strong></td>
                                <td><?= htmlspecialchars($emp['name']) ?></td>
                                <td><?= htmlspecialchars($emp['address']) ?></td>
                                <td><?= date('d M Y', strtotime($emp['reg_date'])) ?></td>
                                <td><?= htmlspecialchars($emp['department']) ?></td>
                                <td class="text-center">
                                    <a href="?edit=<?= $emp['id'] ?>" class="btn btn-sm btn-primary me-1" data-bs-toggle="modal" data-bs-target="#editEmployeeModal">
                                        Edit
                                    </a>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $emp['id'] ?>">
                                        <button type="submit" name="delete_employee" class="btn btn-sm btn-danger" onclick="return confirm('Delete this employee?')">
                                            Delete
                                        </button>
                                    </form>
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

<!-- Edit Employee Modal (pre-filled) -->
<?php if ($edit_employee): ?>
<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="id" value="<?= $edit_employee['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEmployeeModalLabel">Edit Employee - <?= htmlspecialchars($edit_employee['reg_id']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($edit_employee['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label>Registration Date</label>
                            <input type="date" name="reg_date" class="form-control" value="<?= $edit_employee['reg_date'] ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label>Department</label>
                            <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($edit_employee['department']) ?>" required>
                        </div>
                        <div class="col-12">
                            <label>Address</label>
                            <textarea name="address" class="form-control" rows="3" required><?= htmlspecialchars($edit_employee['address']) ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_employee" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-open edit modal if ?edit=ID is in URL
document.addEventListener('DOMContentLoaded', function() {
    var editModal = new bootstrap.Modal(document.getElementById('editEmployeeModal'));
    editModal.show();
});
</script>
<?php endif; ?>

<?php require_once '../../../includes/footer.php'; ?>