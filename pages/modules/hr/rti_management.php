<?php
session_start();
require_once '../../../config/db_connect.php';

// Check if admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'administrator') {
    header("Location: ../../../index.php");
    exit();
}

// Stats for RTI
$rti_stats = [
    'total' => 154,
    'pending' => 12,
    'completed' => 142,
    'overdue' => 2
];

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold">Right to Information (RTI) Activities</h3>
                <p class="text-muted small">Monitor legal information requests and response timelines</p>
            </div>
            <button class="btn btn-danger shadow-sm" data-bs-toggle="modal" data-bs-target="#newRtiModal">
                <i class="bi bi-file-earmark-medical me-2"></i>Register New RTI
            </button>
        </div>

        <div class="row g-4 mb-4">
            <?php 
            $cards = [
                ['label' => 'Total Received', 'val' => $rti_stats['total'], 'color' => 'primary', 'icon' => 'archive'],
                ['label' => 'Pending Action', 'val' => $rti_stats['pending'], 'color' => 'warning', 'icon' => 'clock-history'],
                ['label' => 'Completed', 'val' => $rti_stats['completed'], 'color' => 'success', 'icon' => 'check-all'],
                ['label' => 'Overdue Requests', 'val' => $rti_stats['overdue'], 'color' => 'danger', 'icon' => 'exclamation-octagon'],
            ];
            foreach($cards as $c): 
            ?>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted small text-uppercase fw-bold"><?= $c['label'] ?></h6>
                                <h2 class="fw-bold mb-0"><?= $c['val'] ?></h2>
                            </div>
                            <div class="text-<?= $c['color'] ?> fs-2">
                                <i class="bi bi-<?= $c['icon'] ?>"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-list-task me-2"></i>RTI Request Log</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="rtiTable" class="table table-hover align-middle w-100">
                        <thead class="table-light">
                            <tr class="small text-uppercase">
                                <th>Ref No</th>
                                <th>Applicant Name</th>
                                <th>Information Requested</th>
                                <th>Received Date</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="fw-bold">RTI/2026/EP/085</span></td>
                                <td>Mr. K. Perera</td>
                                <td class="small">Livestock distribution data for Trincomalee 2025</td>
                                <td>2026-03-25</td>
                                <td><span class="text-danger fw-bold">2026-04-08</span></td>
                                <td><span class="badge bg-danger-subtle text-danger px-3">Overdue</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="fw-bold">RTI/2026/EP/086</span></td>
                                <td>Media Unit - East</td>
                                <td class="small">Staff training expenditure report</td>
                                <td>2026-04-01</td>
                                <td>2026-04-15</td>
                                <td><span class="badge bg-warning-subtle text-warning px-3">In Progress</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        $('#rtiTable').DataTable({
            "pageLength": 10,
            "order": [[3, "desc"]], // Sort by Received Date
            "language": {
                "searchPlaceholder": "Search by Ref No or Name..."
            }
        });
    });
</script>

<style>
    .bg-danger-subtle { background-color: #fceaea; color: #dc3545; }
    .bg-warning-subtle { background-color: #fff8e1; color: #f59e0b; }
</style>

<?php require_once '../../../includes/footer.php'; ?>