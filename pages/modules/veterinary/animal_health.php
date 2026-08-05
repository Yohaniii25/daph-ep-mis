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


//Total Records
$total_stmt = $mysqli->prepare("SELECT COUNT(*) as total FROM animal_health_records WHERE range_id = ?");
$total_stmt->bind_param("i", $range_id);
$total_stmt->execute();
$total_count = $total_stmt->get_result()->fetch_assoc()['total'];

// This Month's Records
$month_stmt = $mysqli->prepare("SELECT COUNT(*) as total FROM animal_health_records WHERE range_id = ? AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())");
$month_stmt->bind_param("i", $range_id);
$month_stmt->execute();
$month_count = $month_stmt->get_result()->fetch_assoc()['total'];

// Pending Reports 
$pending_stmt = $mysqli->prepare("SELECT COUNT(*) as total FROM animal_health_records WHERE range_id = ? AND report_status = 'Draft'");
$pending_stmt->bind_param("i", $range_id);
$pending_stmt->execute();
$pending_count = $pending_stmt->get_result()->fetch_assoc()['total'];

// Common Disease
$disease_stmt = $mysqli->prepare("SELECT disease_name, COUNT(disease_name) as count FROM animal_health_records WHERE range_id = ? GROUP BY disease_name ORDER BY count DESC LIMIT 1");
$disease_stmt->bind_param("i", $range_id);
$disease_stmt->execute();
$disease_res = $disease_stmt->get_result()->fetch_assoc();
$common_disease = $disease_res['disease_name'] ?? 'None Reported';

$stmt = $mysqli->prepare("
    SELECT id, date, farmer_reg_no, disease_name, occurrence_count, 
           vaccine_name, doses, treatment_details, report_status, created_at 
    FROM animal_health_records 
    WHERE range_id = ? 
    ORDER BY date DESC, created_at DESC 
    LIMIT 15
");

if (!$stmt) {
    die("SQL Error: " . $mysqli->error);
}

$stmt->bind_param("i", $range_id);
$stmt->execute();
$result = $stmt->get_result();
$treatments = $result->fetch_all(MYSQLI_ASSOC);

require_once '../../../includes/header.php';
?>



        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 mb-0 fw-bold">Record disease occurrence</h2>
                <small class="text-muted"><?= htmlspecialchars($range_name) ?> | DAPH Eastern Province</small>
            </div>

        </div>



        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 text-center p-3 border-start border-primary border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">Total Records</h6>
                    <h3 class="mb-0"><?= $total_count ?></h3>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 text-center p-3 border-start border-warning border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">This Month</h6>
                    <h3 class="mb-0"><?= $month_count ?></h3>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 text-center p-3 border-start border-danger border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">Pending Drafts</h6>
                    <h3 class="mb-0 text-danger"><?= $pending_count ?></h3>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 text-center p-3 border-start border-info border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">Most Common</h6>
                    <h5 class="mb-0 text-truncate text-info"><?= htmlspecialchars($common_disease) ?></h5>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#recordTreatmentModal">
                            <i class="bi bi-plus-circle fs-3"></i><br>
                            Record New Treatment
                        </button>
                    </div>
                    <div class="col-md-4">
                        <a href="animal_health_reports.php" class="btn btn-primary w-100 py-3">
                            <i class="bi bi-search fs-3"></i><br>
                            Search Records
                        </a>
                    </div>

                </div>
            </div>
        </div>


        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Farmer Reg</th>
                        <th>Disease / Vaccine</th>
                        <th>Count</th>
                        <th>Doses</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($treatments as $t): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($t['date'])) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($t['farmer_reg_no'] ?? 'N/A') ?></span></td>
                            <td>
                                <strong><?= htmlspecialchars($t['disease_name']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($t['vaccine_name'] ?? 'No Vaccine') ?></small>
                            </td>
                            <td><?= $t['occurrence_count'] ?></td>
                            <td><?= $t['doses'] ?? 0 ?></td>
                            <td><small><?= htmlspecialchars(substr($t['treatment_details'], 0, 40)) ?>...</small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php

        include 'models/add_health_record.php';
        ?>
    <?php require_once '../../../includes/footer.php'; ?>