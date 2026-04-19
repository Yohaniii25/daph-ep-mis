<?php
session_start();
require_once '../../../config/db_connect.php';

// Role Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'administrator') {
    header("Location: ../../../index.php");
    exit();
}

$current_user = $_SESSION['user_id'];

// 1. Fetch Statistics for Advanced Programmes
$stats_query = $mysqli->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN mid_term_status = 'Approved' THEN 1 ELSE 0 END) as mid_approved,
    SUM(CASE WHEN final_status = 'Approved' THEN 1 ELSE 0 END) as final_approved
    FROM advanced_programmes WHERE user_id = '$current_user'");
$stats = $stats_query->fetch_assoc();

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold">Advanced Programme Management</h3>
                <p class="text-muted small">Yearly planning with Mid-term (6M) and Annual (1Y) Provincial Director approval.</p>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-lightning-charge me-2 text-warning"></i>Administrative Shortcuts</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <button style="background-color: #370709;" class="btn btn-success w-100 py-3 border-2 d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#manageTypesModal">
                            <div class="text-center">
                                <i class="bi bi-gear-wide-connected fs-3"></i><br>
                                <span class="fw-bold text-uppercase small">Manage Programme Types</span>
                                <p class="mb-0 x-small" style="font-size: 0.75rem; color: white;">Add/Edit global programme categories</p>
                            </div>
                        </button>
                    </div>

                    <div class="col-md-6">
                        <button style="background-color: #ef4016;" class="btn btn-primary w-100 py-3 border-2 d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#addAdvancedModal">
                            <div class="text-center">
                                <i class="bi bi-calendar-plus fs-3"></i><br>
                                <span class="fw-bold text-uppercase small">New Advanced Programme</span>
                                <p class="mb-0 x-small" style="font-size: 0.75rem; color: white;">Create a new yearly activity record</p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-primary text-white">
                    <div class="card-body">
                        <h6 class="small text-uppercase opacity-75">Total Programmes</h6>
                        <h3 class="fw-bold mb-0"><?php echo $stats['total'] ?? 0; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-info text-white">
                    <div class="card-body">
                        <h6 class="small text-uppercase opacity-75">Mid-Term Approved</h6>
                        <h3 class="fw-bold mb-0"><?php echo $stats['mid_approved'] ?? 0; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-success text-white">
                    <div class="card-body">
                        <h6 class="small text-uppercase opacity-75">Final Year Approved</h6>
                        <h3 class="fw-bold mb-0"><?php echo $stats['final_approved'] ?? 0; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-3">
                <table class="table table-hover align-middle" id="advancedProgTable">
                    <thead class="bg-light">
                        <tr>
                            <th>Year</th>
                            <th>Programme Type</th>
                            <th>Location</th>
                            <th>Mid-Term (6M)</th>
                            <th>Annual (1Y)</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Join with master table to get the name
                        $sql = "SELECT ap.*, mpt.programme_name 
                                FROM advanced_programmes ap
                                JOIN master_programme_types mpt ON ap.type_id = mpt.id
                                WHERE ap.user_id = '$current_user' 
                                ORDER BY ap.programme_year DESC";
                        $result = $mysqli->query($sql);

                        while ($row = $result->fetch_assoc()):
                            $mid_badge = ($row['mid_term_status'] == 'Approved') ? 'success' : 'warning';
                            $final_badge = ($row['final_status'] == 'Approved') ? 'success' : 'secondary';
                        ?>
                            <tr>
                                <td><span class="fw-bold"><?php echo $row['programme_year']; ?></span></td>
                                <td><?php echo htmlspecialchars($row['programme_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['place']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $mid_badge; ?> px-3">
                                        <?php echo $row['mid_term_status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $final_badge; ?> px-3">
                                        <?php echo $row['final_status']; ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php if ($row['mid_term_status'] == 'Pending'): ?>
                                        <button class="btn btn-sm btn-outline-primary">Submit 6M</button>
                                    <?php elseif ($row['mid_term_status'] == 'Approved' && $row['final_status'] == 'Pending'): ?>
                                        <button class="btn btn-sm btn-outline-success">Submit Final</button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-light" disabled><i class="bi bi-lock"></i></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php include 'models/add_advanced_programme_modal.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
    $(document).ready(function() {
        $('#advancedProgTable').DataTable({
            "order": [
                [0, "desc"]
            ],
            "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            "buttons": [{
                    extend: 'csv',
                    className: 'btn btn-sm btn-outline-success'
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-sm btn-outline-danger'
                },
                {
                    extend: 'print',
                    className: 'btn btn-sm btn-outline-dark'
                }
            ]
        });
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>