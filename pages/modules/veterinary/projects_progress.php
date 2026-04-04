<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

$range_id = $_SESSION['range_id'] ?? null;
if (empty($range_id)) {
    die('<div class="alert alert-danger text-center p-5 m-5">Error: Account not assigned to a Range.</div>');
}

require_once '../../../config/db_connect.php';

$range_name = 'Unknown Range';
$stmt = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
$stmt->bind_param("i", $range_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if ($res) $range_name = $res['name'];
$stmt->close();

// Initialize counts to zero
$counts = [
    'PSDG' => 0,
    'LMP' => 0,
    'CBG' => 0,
    'Special' => 0,
    'Other' => 0
];

$count_query = "SELECT project_type, COUNT(*) as total 
                FROM projects_progress 
                WHERE range_id = ? 
                GROUP BY project_type";

$c_stmt = $mysqli->prepare($count_query);
$c_stmt->bind_param("i", $range_id);
$c_stmt->execute();
$count_result = $c_stmt->get_result();

while ($row = $count_result->fetch_assoc()) {
    $counts[$row['project_type']] = $row['total'];
}
$c_stmt->close();

// Mapping to your specific variables
$psdg_count    = $counts['PSDG'];
$lmp_count     = $counts['LMP'];
$cbg_count     = $counts['CBG'];
$special_count = $counts['Special'];
$other_count   = $counts['Other'];

$list_stmt = $mysqli->prepare("SELECT * FROM projects_progress WHERE range_id = ? ORDER BY priority DESC, start_date DESC");
$list_stmt->bind_param("i", $range_id);
$list_stmt->execute();
$projects = $list_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 mb-0 fw-bold text-uppercase">Project Progress & Operations</h2>
                <small class="text-muted"><?= htmlspecialchars($range_name) ?> | Monitoring & Evaluation</small>
            </div>

        </div>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#addProjectModal">
                            <i class="bi bi-plus-circle fs-3"></i><br>
                            Add Project
                        </button>
                    </div>


                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-3 border-start border-primary border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">PSDG Projects</h6>
                    <h3 class="mb-0"><?= $psdg_count ?> </h3>
                </div>
            </div>

            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-3 border-start border-success border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">LMP Projects</h6>
                    <h3 class="mb-0"><?= $lmp_count ?></h3>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-3 border-start border-warning border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">CBG Projects</h6>
                    <h3 class="mb-0"><?= $cbg_count ?> </h3>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-3 border-start border-danger border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">Special Projects</h6>
                    <h3 class="mb-0"><?= $special_count ?> </h3>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-3 border-start border-secondary border-4">
                    <h6 class="text-muted small fw-bold text-uppercase">Other Projects</h6>
                    <h3 class="mb-0"><?= $other_count ?></h3>
                </div>
            </div>

        </div>


        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="bi bi-list-task me-2"></i>Project Progress Records</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="projectsTable">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Project Name & Type</th>
                                <th>Location</th>
                                <th>Duration</th>
                                <th>Priority</th>
                                <th style="width: 200px;">Progress</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $proj): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-primary"><?= htmlspecialchars($proj['project_name']) ?></div>
                                        <small class="badge bg-light text-dark border"><?= $proj['project_type'] ?></small>
                                    </td>
                                    <td><i class="bi bi-geo-alt me-1 text-danger"></i><?= htmlspecialchars($proj['location']) ?></td>
                                    <td class="small">
                                        <?= date('M d', strtotime($proj['start_date'])) ?> - <?= date('M d, Y', strtotime($proj['end_date'])) ?>
                                    </td>
                                    <td>
                                        <?php
                                        $pClass = ($proj['priority'] == 'Urgent' || $proj['priority'] == 'High') ? 'text-danger' : 'text-dark';
                                        ?>
                                        <span class="fw-bold <?= $pClass ?>"><?= $proj['priority'] ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                <div class="progress-bar bg-success" style="width: <?= $proj['progress_percent'] ?>%"></div>
                                            </div>
                                            <small class="fw-bold"><?= $proj['progress_percent'] ?>%</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill bg-<?= $proj['status'] == 'Completed' ? 'success' : 'info' ?>">
                                            <?= $proj['status'] ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-secondary border"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-sm btn-primary border"><i class="bi bi-eye"></i></button>
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

<?php include 'models/add_project_modal.php'; ?>

<script>
    $(document).ready(function() {
        $('#projectsTable').DataTable({
            "pageLength": 10,
            "order": [
                [3, "desc"]
            ] // Priority
        });
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>