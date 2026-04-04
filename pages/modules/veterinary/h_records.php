<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

if (!isset($_SESSION['full_name'])) {
    $_SESSION['full_name'] = $_SESSION['username'] ?? 'Veterinary Surgeon';
}

$full_name   = $_SESSION['full_name'];
$range_id    = $_SESSION['range_id'] ?? null;
$district_id = $_SESSION['district_id'] ?? null;

if (empty($range_id)) {
    die('<div class="alert alert-danger text-center p-5 m-5">Error: Your account is not assigned to any Veterinary Range.</div>');
}

require_once '../../../config/db_connect.php';

$district_name = 'Unknown District';
$range_name    = 'Unknown Range';

// Fetch District and Range Names
if ($district_id) {
    $stmt = $mysqli->prepare("SELECT name FROM districts WHERE id = ?");
    $stmt->bind_param("i", $district_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $district_name = $row['name'];
    }
    $stmt->close();
}

if ($range_id) {
    $stmt = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
    $stmt->bind_param("i", $range_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $range_name = $row['name'];
    }
    $stmt->close();
}

$current_year = date('Y');

// Demo Data for Letter H
$letter_h_demo = [
    ['date' => '2026-03-15', 'vote' => '222-01-02-2502', 'desc' => 'Purchase of Anti-Rabies Vaccines', 'amount' => 45000.00, 'status' => 'Paid'],
    ['date' => '2026-03-28', 'vote' => '222-01-03-1401', 'desc' => 'LMP Dairy Development Grant - Batch 01', 'amount' => 250000.00, 'status' => 'Pending'],
    ['date' => '2026-04-01', 'vote' => '222-01-01-1001', 'desc' => 'Office Stationary & Consumables', 'amount' => 8500.00, 'status' => 'Approved'],
];

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 mb-0 fw-bold text-uppercase">H Records</h2>
                <small class="text-muted"><?= htmlspecialchars($range_name) ?></small>
            </div>

        </div>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#addHRecordModal">
                            <i class="bi bi-plus-circle fs-3"></i><br>
                            Add H Record
                        </button>
                    </div>


                </div>
            </div>
        </div>


        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list-task me-2"></i>H records</h5>
                <div id="exportButtons"></div>
            </div>

            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small text-uppercase text-muted">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Vote Ledger Code</th>
                                <th>Description / Purpose</th>
                                <th class="text-end">Amount (LKR)</th>
                                <th class="text-center">Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($letter_h_demo as $row): ?>
                                <tr>
                                    <td class="ps-4 small"><?= date('d M, Y', strtotime($row['date'])) ?></td>
                                    <td><code class="fw-bold text-primary"><?= $row['vote'] ?></code></td>
                                    <td>
                                        <div class="fw-bold text-dark small"><?= $row['desc'] ?></div>
                                        <div class="text-muted" style="font-size: 11px;">Ref: DAPH/EP/2026/VOC-<?= rand(100, 999) ?></div>
                                    </td>
                                    <td class="text-end fw-bold text-dark"><?= number_format($row['amount'], 2) ?></td>
                                    <td class="text-center">
                                        <?php
                                        $status_class = [
                                            'Paid' => 'bg-success',
                                            'Pending' => 'bg-warning text-dark',
                                            'Approved' => 'bg-info text-white'
                                        ][$row['status']];
                                        ?>
                                        <span class="badge rounded-pill <?= $status_class ?> px-3" style="font-size: 10px;">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-light border"><i class="bi bi-three-dots"></i></button>
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

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<!-- <?php include 'models/add_h_record_modal.php'; ?> -->


<?php require_once '../../../includes/footer.php'; ?>