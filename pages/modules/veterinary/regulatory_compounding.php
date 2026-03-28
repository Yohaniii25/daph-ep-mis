<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

require_once '../../../config/db_connect.php';

$range_id = $_SESSION['range_id'] ?? null;
$range_name = "Your Range";

if ($range_id) {
    $stmt = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
    $stmt->bind_param("i", $range_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) $range_name = $row['name'];
}

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 mb-0 fw-bold">Compounding Records</h2>
            <small class="text-muted"><?= htmlspecialchars($range_name) ?> | Regulatory Functions</small>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Compound No</th>
                                <th>Offence Type</th>
                                <th>Offender Name</th>
                                <th>Amount (Rs.)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>2026-03-12</td>
                                <td><strong>CMP-2026-034</strong></td>
                                <td>Illegal Transport of Animals</td>
                                <td>Mr. P. Kumar</td>
                                <td>5,000</td>
                                <td><span class="badge bg-success">Paid</span></td>
                            </tr>
                            <tr>
                                <td>2026-03-20</td>
                                <td><strong>CMP-2026-035</strong></td>
                                <td>No Health Certificate</td>
                                <td>Mr. N. Ismail</td>
                                <td>3,500</td>
                                <td><span class="badge bg-warning">Pending</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once '../../../includes/footer.php'; ?>