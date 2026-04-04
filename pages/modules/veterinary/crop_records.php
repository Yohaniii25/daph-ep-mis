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

// Demo Data for Crop Summary
$crop_summary_demo = [
    ['item' => 'Fresh Milk', 'qty' => '14,200', 'unit' => 'Liters', 'change' => '+4.2%', 'icon' => 'droplet-fill', 'color' => 'primary'],
    ['item' => 'Poultry Meat', 'qty' => '2,850', 'unit' => 'Kg', 'change' => '+1.5%', 'icon' => 'egg-fried', 'color' => 'danger'],
    ['item' => 'Egg Production', 'qty' => '10,400', 'unit' => 'Nos', 'change' => '-0.8%', 'icon' => 'circle-fill', 'color' => 'warning'],
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
                <h2 class="h4 mb-0 fw-bold text-uppercase">Crop Records</h2>
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
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#addCropRecordModal">
                            <i class="bi bi-plus-circle fs-3"></i><br>
                            Add Crop Record
                        </button>
                    </div>


                </div>
            </div>
        </div>


        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list-task me-2"></i>Crop records</h5>
                <div id="exportButtons"></div>
            </div>

            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small text-uppercase text-muted">
                            <tr>
                                <th class="ps-4">Production Item</th>
                                <th>Category</th>
                                <th class="text-center">Reporting Period</th>
                                <th class="text-end">Total Yield</th>
                                <th class="text-center">Trend</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($crop_summary_demo as $row): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="p-2 rounded bg-<?= $row['color'] ?> bg-opacity-10 text-<?= $row['color'] ?> me-3">
                                                <i class="bi bi-<?= $row['icon'] ?>"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?= $row['item'] ?></div>
                                                <div class="text-muted small">Updated: <?= date('M d, Y') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border px-2">Livestock Output</span>
                                    </td>
                                    <td class="text-center small">
                                        <?= date('F Y') ?>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold fs-6 text-dark"><?= $row['qty'] ?></span>
                                        <span class="text-muted small"><?= $row['unit'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $is_up = strpos($row['change'], '+') !== false;
                                        $trend_color = $is_up ? 'text-success' : 'text-danger';
                                        $trend_icon = $is_up ? 'graph-up' : 'graph-down';
                                        ?>
                                        <div class="<?= $trend_color ?> small fw-bold">
                                            <i class="bi bi-<?= $trend_icon ?> me-1"></i> <?= $row['change'] ?>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-primary" title="Details">
                                            <i class="bi bi-bar-chart-fill"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light border ms-1">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
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