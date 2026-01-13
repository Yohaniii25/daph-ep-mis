<?php
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

if ($_SESSION['role'] !== 'veterinary_surgeon') die("Access denied");

$message = '';

// Handle Add Employee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $reg_id = trim($_POST['reg_id']);
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $reg_date = $_POST['reg_date'];
    $department = 'Veterinary'; 

    if ($reg_id && $name && $address && $reg_date) {
        $stmt = $mysqli->prepare("INSERT INTO staff (reg_id, name, address, reg_date, department) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $reg_id, $name, $address, $reg_date, $department);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Employee added successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-warning">Please fill all fields.</div>';
    }
}

// Fetch only Veterinary staff
$stmt = $mysqli->prepare("SELECT * FROM staff WHERE department = 'Veterinary' ORDER BY reg_date DESC");
$stmt->execute();
$result = $stmt->get_result();
$staff = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4">
        <h2 class="mb-4">Veterinary Staff Management</h2>

        <?= $message ?>

        <!-- Staff Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark">
                <h5 style="color: white;" class="mb-0">Veterinary Staff Members</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Reg ID</th>
                                <th>Name</th>
                                <th>Address</th>
                                <th>Registration Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($staff)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No staff records found in Veterinary section</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($staff as $s): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($s['reg_id']) ?></strong></td>
                                        <td><?= htmlspecialchars($s['name']) ?></td>
                                        <td><?= nl2br(htmlspecialchars($s['address'])) ?></td>
                                        <td><?= date('d M Y', strtotime($s['reg_date'])) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1" >View</button>
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