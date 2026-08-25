<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_roles = ['provincial_director', 'deputy_director_hq_1', 'deputy_director_hq_2', 'administrator'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../../../index.php");
    exit();
}

require_once '../../../config/db_connect.php';
require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';

// Filter by District if requested
$filter_district_id = isset($_GET['district_id']) && $_GET['district_id'] !== '' ? intval($_GET['district_id']) : null;

// Summary stats (Province-wide or filtered)
$where_clause = "1=1";
$params = [];
$types = "";

if ($filter_district_id) {
    $where_clause .= " AND vr.district_id = ?";
    $params[] = $filter_district_id;
    $types .= "i";
}

$count_sql = "SELECT COUNT(a.id) AS total_records, 
                     IFNULL(SUM(a.no_of_animals_treated), 0) AS total_treated
              FROM animal_health_records a
              LEFT JOIN veterinary_ranges vr ON a.range_id = vr.id
              WHERE $where_clause";

$count_stmt = $mysqli->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$stats = $count_stmt->get_result()->fetch_assoc();
$total_records = (int)($stats['total_records'] ?? 0);
$total_treated = (int)($stats['total_treated'] ?? 0);
$count_stmt->close();

// Fetch Records
$records_sql = "SELECT a.*, vr.name AS range_name, d.name AS district_name 
                FROM animal_health_records a
                LEFT JOIN veterinary_ranges vr ON a.range_id = vr.id
                LEFT JOIN districts d ON vr.district_id = d.id
                WHERE $where_clause
                ORDER BY a.date DESC, a.id DESC LIMIT 100";

$rec_stmt = $mysqli->prepare($records_sql);
if (!empty($params)) {
    $rec_stmt->bind_param($types, ...$params);
}
$rec_stmt->execute();
$records = $rec_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$rec_stmt->close();

// Fetch districts for filter dropdown
$districts = $mysqli->query("SELECT id, name FROM districts ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <div>
                <h2 class="text-dark fw-bold mb-0">Animal Health Reports Log</h2>
                <p class="text-muted small mb-0">Province-wide veterinary disease logs, treatments, and clinical reports.</p>
            </div>
            <a href="../../../dashboard.php" class="btn btn-secondary shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>

        <!-- Filter Card -->
        <div class="card shadow-sm border-0 mb-4 rounded-3">
            <div class="card-body p-3">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-secondary">Filter by District</label>
                        <select name="district_id" class="form-select">
                            <option value="">-- All Districts (Province-Wide) --</option>
                            <?php foreach ($districts as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= $filter_district_id == $d['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-filter me-1"></i> Apply Filter
                        </button>
                    </div>
                    <?php if ($filter_district_id): ?>
                        <div class="col-md-2">
                            <a href="animal_health_reports.php" class="btn btn-outline-secondary w-100">Reset</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Summary KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm border-start border-primary border-4 p-3">
                    <small class="text-muted text-uppercase fw-bold">Total Clinical Logs</small>
                    <h3 class="text-primary fw-bold mb-0"><?= number_format($total_records) ?></h3>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm border-start border-success border-4 p-3">
                    <small class="text-muted text-uppercase fw-bold">Total Animals Treated</small>
                    <h3 class="text-success fw-bold mb-0"><?= number_format($total_treated) ?></h3>
                </div>
            </div>
        </div>

        <!-- Records Table -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="m-0 fw-bold text-dark"><i class="bi bi-list-check me-2 text-danger"></i>Clinical Logs &amp; Diagnosis Data</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="animalHealthTable" class="table table-hover table-striped table-bordered align-middle w-100 small">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>District</th>
                                <th>Range Office</th>
                                <th>Species</th>
                                <th>Disease / Condition</th>
                                <th class="text-center">Treated</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($records)): ?>
                                <?php foreach ($records as $r): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($r['date'] ?? '') ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($r['district_name'] ?? 'N/A') ?></span></td>
                                        <td class="fw-bold"><?= htmlspecialchars($r['range_name'] ?? 'General Range') ?></td>
                                        <td><?= htmlspecialchars($r['species'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($r['disease_condition'] ?? $r['disease_name'] ?? 'N/A') ?></td>
                                        <td class="text-center font-monospace fw-bold"><?= (int)($r['no_of_animals_treated'] ?? 0) ?></td>
                                        <td>
                                            <span class="badge bg-<?= ($r['report_status'] ?? '') === 'Approved' ? 'success' : 'warning' ?>">
                                                <?= htmlspecialchars($r['report_status'] ?? 'Draft') ?>
                                            </span>
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

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#animalHealthTable').DataTable({
        "pageLength": 15,
        "order": [[0, "desc"]]
    });
});
</script>
