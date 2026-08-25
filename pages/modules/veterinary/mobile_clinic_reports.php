<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

$range_id = $_SESSION['range_id'] ?? null;
$district_id = $_SESSION['district_id'] ?? null;
$range_name = 'Your Range';
$district_name = 'Your District';

if ($range_id) {
    $stmt = $mysqli->prepare("SELECT vr.name as range_name, d.name as district_name FROM veterinary_ranges vr LEFT JOIN districts d ON vr.district_id = d.id WHERE vr.id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $range_id);
        $stmt->execute();
        if ($row = $stmt->get_result()->fetch_assoc()) {
            $range_name = $row['range_name'] ?? 'Your Assigned Range';
            $district_name = $row['district_name'] ?? 'Your District';
        }
        $stmt->close();
    }
}

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">

<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h2 class="h4 fw-bold mb-1" style="color: #370709;">Mobile Clinic Reports</h2>
        <p class="text-muted small mb-0">Mobile Veterinary Clinic & Field Operations Log for <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District)</p>
    </div>
    <a href="monthly-annual-reports.php" class="btn btn-secondary shadow-sm text-nowrap">
        <i class="bi bi-arrow-left me-2"></i>Back
    </a>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4 text-center py-5">
        <i class="bi bi-truck fs-1 text-muted d-block mb-3"></i>
        <h5 class="fw-bold text-dark">Mobile Clinic Reports Log</h5>
        <p class="text-muted small max-w-md mx-auto">No mobile clinic sessions recorded for the active period. New mobile clinic records will populate here automatically.</p>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>
