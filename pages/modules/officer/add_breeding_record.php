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

// Stats Calculations (AI, PD, and Calving totals for current year)
$current_year = date('Y');

$stats_stmt = $mysqli->prepare("
    SELECT 
        SUM(ai_count) as total_ai, 
        SUM(pd_count) as total_pd, 
        SUM(calving_count) as total_calvings 
    FROM breeding_progress 
    WHERE range_id = ? AND year = ?
");
$stats_stmt->bind_param("ii", $range_id, $current_year);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Fetch Officer List for Modal Suggestion
$off_stmt = $mysqli->prepare("SELECT id, officer_name FROM office_details WHERE range_id = ? AND status = 'Active'");
$off_stmt->bind_param("i", $range_id);
$off_stmt->execute();
$officer_suggestions = $off_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Main Table Query (Modified to match your HTML expectations)
$stmt = $mysqli->prepare("
    SELECT 
        od.id as officer_id,
        od.officer_name, 
        od.designation,
        -- We use 0 as month_number because this is an annual summary row
        0 as month_number,
        -- Combine targets into one 'target_year' for your progress bar logic
        (COALESCE(tt.target_ai, 0) + COALESCE(tt.target_pd, 0) + COALESCE(tt.target_calving, 0)) as target_year,
        -- Match the names your HTML is looking for
        COALESCE(SUM(bp.ai_count), 0) as ai_count, 
        COALESCE(SUM(bp.pd_count), 0) as pd_count, 
        COALESCE(SUM(bp.calving_count), 0) as calving_count
    FROM office_details od
    LEFT JOIN breeding_target_templates tt 
        ON od.designation = tt.designation 
        AND od.range_id = tt.range_id 
        AND tt.year = ?
    LEFT JOIN breeding_progress bp 
        ON od.id = bp.officer_id 
        AND bp.year = ?
    WHERE od.range_id = ? AND od.status = 'Active'
    GROUP BY od.id, od.officer_name, od.designation, tt.target_ai, tt.target_pd, tt.target_calving
    ORDER BY od.officer_name ASC
");

$stmt->bind_param("iii", $current_year, $current_year, $range_id);
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


if (!$stmt) {
    die("SQL Error: " . $mysqli->error);
}

$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 mb-0 fw-bold">Breeding Activities & Progress</h2>
                <small class="text-muted"><?= htmlspecialchars($range_name) ?> | <?= $current_year ?> Monitoring</small>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 text-center p-3 border-start border-primary border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">Total AI</h6>
                    <h3 class="mb-0 text-primary"><?= number_format($stats['total_ai'] ?? 0) ?></h3>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 text-center p-3 border-start border-info border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">Total PD</h6>
                    <h3 class="mb-0 text-info"><?= number_format($stats['total_pd'] ?? 0) ?></h3>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 text-center p-3 border-start border-success border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">Total Calvings</h6>
                    <h3 class="mb-0 text-success"><?= number_format($stats['total_calvings'] ?? 0) ?></h3>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-bold">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <button style="color:white;" class="btn btn-info w-100 py-3" data-bs-toggle="modal" data-bs-target="#addTargetModal">
                            <i class="bi bi-plus-circle fs-3"></i><br>
                            Add Target for Year
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button style="color:white;" class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#addBreedingModal">
                            <i class="bi bi-plus-circle fs-3"></i><br>
                            Add Breeding Record
                        </button>
                    </div>
                    <div class="col-md-4">
                        <a href="animal_breeding_reports.php" class="btn btn-primary w-100 py-3">
                            <i class="bi bi-search fs-3"></i><br>
                            Search Records
                        </a>
                    </div>
                    <!-- <div class="col-md-4">
                        <a href="officer_management.php" class="btn btn-dark w-100 py-3 text-white fw-bold">
                            <i class="bi bi-people fs-3"></i><br>
                            Manage Officers
                        </a>
                    </div> -->
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="breedingTable" class="table table-hover align-middle w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Month</th>
                                <th>Officer Name</th>
                                <th class="text-center">Annual Target</th>
                                <th class="text-center">AI</th>
                                <th class="text-center">PD</th>
                                <th class="text-center">Calving</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $r): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-dark">
                                            <?= ($r['month_number'] == 0) ? 'Annual' : 'Month ' . $r['month_number'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($r['officer_name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($r['designation']) ?></small>
                                    </td>
                                    <td class="text-center fw-bold text-secondary">
                                        <?= number_format($r['target_year']) ?>
                                    </td>
                                    <td class="text-center fw-bold text-primary">
                                        <?= number_format($r['ai_count']) ?>
                                    </td>
                                    <td class="text-center fw-bold text-info">
                                        <?= number_format($r['pd_count']) ?>
                                    </td>
                                    <td class="text-center fw-bold text-success">
                                        <?= number_format($r['calving_count']) ?>
                                    </td>
                                    <td>
                                        <?php
                                        $total_actual = $r['ai_count'] + $r['pd_count'] + $r['calving_count'];
                                        $target = $r['target_year'];
                                        $prog = ($target > 0) ? round(($total_actual / $target) * 100) : 0;

                                        // Determine color based on progress
                                        $bar_class = 'bg-danger';
                                        if ($prog >= 100) $bar_class = 'bg-success';
                                        elseif ($prog >= 50) $bar_class = 'bg-primary';
                                        elseif ($prog > 0) $bar_class = 'bg-warning';
                                        ?>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar <?= $bar_class ?>" style="width: <?= min($prog, 100) ?>%"></div>
                                        </div>
                                        <small class="small fw-bold"><?= $prog ?>% of target</small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php include 'models/add_target_modal.php'; ?>
        <?php include 'models/add_breeding_record.php'; ?>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

<script>
    $(document).ready(function() {
        var table = $('#breedingTable').DataTable({
            "dom": 'rtip', // Hiding the default buttons to use our custom Quick Action button
            "buttons": [{
                extend: 'csv',
                title: 'Breeding_Records_<?= $range_name ?>_<?= $current_year ?>',
                exportOptions: {
                    columns: ':visible'
                }
            }]
        });

        // Link the custom Quick Action button to the DataTables Export
        $('#exportCSVBtn').on('click', function() {
            table.button('.buttons-csv').trigger();
        });
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>