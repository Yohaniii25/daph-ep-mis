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


// 1. Total Milk Production (Current Month Sum)
$milk_stmt = $mysqli->prepare("
    SELECT SUM(mpr.amount) as total 
    FROM monthly_production_records mpr
    JOIN production_items pi ON mpr.item_id = pi.id
    JOIN production_categories pc ON pi.category_id = pc.id
    WHERE mpr.range_id = ? 
    AND pc.category_name LIKE '%Milk%'
    AND MONTH(mpr.report_date) = MONTH(CURRENT_DATE())
    AND YEAR(mpr.report_date) = YEAR(CURRENT_DATE())
");
$milk_stmt->bind_param("i", $range_id);
$milk_stmt->execute();
$total_milk = $milk_stmt->get_result()->fetch_assoc()['total'] ?? 0;

// 2. Total Meat Production (Current Month Sum)
$meat_stmt = $mysqli->prepare("
    SELECT SUM(mpr.amount) as total 
    FROM monthly_production_records mpr
    JOIN production_items pi ON mpr.item_id = pi.id
    JOIN production_categories pc ON pi.category_id = pc.id
    WHERE mpr.range_id = ? 
    AND pc.category_name LIKE '%Meat%'
    AND MONTH(mpr.report_date) = MONTH(CURRENT_DATE())
    AND YEAR(mpr.report_date) = YEAR(CURRENT_DATE())
");
$meat_stmt->bind_param("i", $range_id);
$meat_stmt->execute();
$total_meat = $meat_stmt->get_result()->fetch_assoc()['total'] ?? 0;

// 3. Total Slaughtered Animals
$slaughter_stmt = $mysqli->prepare("
    SELECT SUM(animal_count) as total 
    FROM slaughter_statistics 
    WHERE range_id = ? 
    AND report_month = MONTH(CURRENT_DATE()) 
    AND report_year = YEAR(CURRENT_DATE())
");
$slaughter_stmt->bind_param("i", $range_id);
$slaughter_stmt->execute();
$total_slaughtered = $slaughter_stmt->get_result()->fetch_assoc()['total'] ?? 0;

// 4. Total Semen Used 
$semen_stmt = $mysqli->prepare("
    SELECT SUM(used_qty) as total 
    FROM semen_logs 
    WHERE range_id = ? 
    AND report_month = MONTH(CURRENT_DATE()) 
    AND report_year = YEAR(CURRENT_DATE())
");
$semen_stmt->bind_param("i", $range_id);
$semen_stmt->execute();
$total_semen_used = $semen_stmt->get_result()->fetch_assoc()['total'] ?? 0;

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">

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
                    <h6 class="text-muted small fw-bold text-uppercase">Milk Production</h6>
                    <h3 class="mb-0"><?= number_format($total_milk, 1) ?> <span class="fs-6 text-muted">L</span></h3>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 text-center p-3 border-start border-success border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">Meat Production</h6>
                    <h3 class="mb-0"><?= number_format($total_meat, 1) ?> <span class="fs-6 text-muted">Kg</span></h3>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 text-center p-3 border-start border-danger border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">Slaughtered</h6>
                    <h3 class="mb-0 text-danger"><?= $total_slaughtered ?> <span class="fs-6 text-muted">Nos</span></h3>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 text-center p-3 border-start border-info border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">Semen Used</h6>
                    <h3 class="mb-0 text-info"><?= $total_semen_used ?> <span class="fs-6 text-muted">Doses</span></h3>
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
                        <a href="livestock_production.php" class="btn btn-success w-100 py-3">
                            <i class="bi bi-search fs-3"></i><br>
                            Livestock Production
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="slaughter_stats.php" class="btn btn-primary w-100 py-3">
                            <i class="bi bi-search fs-3"></i><br>
                            Slaughter Statistics
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="breeding_logs.php" class="btn btn-danger w-100 py-3">
                            <i class="bi bi-search fs-3"></i><br>
                            Breeding and Semen Logs
                        </a>
                    </div>

                </div>
            </div>
        </div>




        <?php

        include 'models/add_health_record.php';
        ?>
    </main>
</div>


<?php require_once '../../../includes/footer.php'; ?>