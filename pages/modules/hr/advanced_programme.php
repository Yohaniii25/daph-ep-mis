<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'administrator') {
    header("Location: ../../../index.php");
    exit();
}

$current_user = $_SESSION['user_id'];

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
                        <button style="background-color: #370709; color: white;" class="btn w-100 py-3 d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#manageTypesModal">
                            <div class="text-center">
                                <i class="bi bi-gear-wide-connected fs-3"></i><br>
                                <span class="fw-bold text-uppercase small">Manage Programme Types</span>
                                <p class="mb-0 x-small" style="font-size: 0.75rem; color: white;">Add/Edit global programme categories</p>
                            </div>
                        </button>
                    </div>

                    <div class="col-md-6">
                        <button style="background-color: #a07174; color: white;" class="btn w-100 py-3 d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#addAdvancedModal">
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
                        <h6 style="color: white;" class="small text-uppercase opacity-75">Total Programmes</h6>
                        <h3 style="color: white;" class="fw-bold mb-0"><?php echo $stats['total'] ?? 0; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div style="background-color: #b08723;" class="card border-0 shadow-sm text-white">
                    <div class="card-body">
                        <h6 style="color: white;" class="small text-uppercase opacity-75">Mid-Term Approved</h6>
                        <h3 style="color: white;" class="fw-bold mb-0"><?php echo $stats['mid_approved'] ?? 0; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-success text-white">
                    <div class="card-body">
                        <h6 style="color: white;" class="small text-uppercase opacity-75">Final Year Approved</h6>
                        <h3 style="color: white;" class="fw-bold mb-0"><?php echo $stats['final_approved'] ?? 0; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-3">
                <table class="table table-hover align-middle" id="advancedProgTable">
                    <thead class="bg-light">
                        <tr>
                            <th>Started Date</th>
                            <th>Year</th>
                            <th>Programme Type</th>
                            <th>Location</th>
                            <th>Duration</th>
                            <th>Mid-Term (6M)</th>
                            <th>Annual (1Y)</th>
                            <th class="text-center">Submission</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT ap.*, mpt.programme_name 
            FROM advanced_programmes ap
            JOIN master_programme_types mpt ON ap.type_id = mpt.id
            WHERE ap.user_id = '$current_user' 
            ORDER BY ap.created_at DESC";
                        $result = $mysqli->query($sql);

                        while ($row = $result->fetch_assoc()):
                            $start_date = new DateTime($row['created_at']);
                            $today = new DateTime();
                            $interval = $start_date->diff($today);
                            $months_passed = ($interval->y * 12) + $interval->m;

                            $mid_status = $row['mid_term_status'];
                            $final_status = $row['final_status'];

                            $mid_badge = ($mid_status == 'Approved') ? 'success' : (($mid_status == 'Submitted') ? 'info' : 'warning');
                            $final_badge = ($final_status == 'Approved') ? 'success' : (($final_status == 'Submitted') ? 'info' : 'secondary');
                            $age_class = ($months_passed >= 6 && $mid_status == 'Pending') ? 'text-danger fw-bold' : 'text-muted';
                        ?>
                            <tr>
                                <td class="small"><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                                <td><span class="badge bg-light text-dark border"><?php echo $row['programme_year']; ?></span></td>
                                <td><?php echo htmlspecialchars($row['programme_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['place']); ?></td>
                                <td><span class="<?php echo $age_class; ?>"><?php echo $months_passed; ?> Months</span></td>
                                <td><span class="badge bg-<?php echo $mid_badge; ?> px-3"><?php echo $mid_status; ?></span></td>
                                <td><span class="badge bg-<?php echo $final_badge; ?> px-3"><?php echo $final_status; ?></span></td>

                                <td class="text-center">
                                    <?php if ($mid_status == 'Pending' || $mid_status == 'Rejected'): ?>
                                        <a href="submit_programme.php?id=<?php echo $row['id']; ?>&stage=mid"
                                            class="btn btn-sm btn-primary"
                                            onclick="return confirm('Submit for 6-Month Review?')">
                                            Submit 6M
                                        </a>
                                    <?php elseif ($mid_status == 'Approved' && ($final_status == 'Pending' || $final_status == 'Rejected')): ?>
                                        <a href="submit_programme.php?id=<?php echo $row['id']; ?>&stage=final"
                                            class="btn btn-sm btn-success"
                                            onclick="return confirm('Submit for Annual Final Review?')">
                                            Submit Final
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">Processing...</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="#"
                                            class="view-btn btn btn-sm btn-outline-info"
                                            data-year="<?php echo $row['programme_year']; ?>"
                                            data-type="<?php echo htmlspecialchars($row['programme_name']); ?>"
                                            data-place="<?php echo htmlspecialchars($row['place']); ?>"
                                            data-description="<?php echo htmlspecialchars($row['activity_description']); ?>"
                                            data-mid="<?php echo $row['mid_term_status']; ?>"
                                            data-final="<?php echo $row['final_status']; ?>"
                                            title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <a href="#"
                                            class="edit-btn btn btn-sm btn-outline-primary"
                                            data-id="<?php echo $row['id']; ?>"
                                            data-year="<?php echo $row['programme_year']; ?>"
                                            data-type="<?php echo htmlspecialchars($row['programme_name']); ?>"
                                            data-place="<?php echo htmlspecialchars($row['place']); ?>"
                                            data-description="<?php echo htmlspecialchars($row['activity_description']); ?>"
                                            title="Edit Details">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <?php if ($mid_status == 'Pending'): ?>
                                            <a href="delete_programme.php?id=<?php echo $row['id']; ?>"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Are you sure you want to delete this programme?\nThis action cannot be undone.')"
                                                title="Delete Programme">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once '../../../includes/footer.php'; ?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

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
        var table = $('#advancedProgTable').DataTable({
            "order": [
                [0, "desc"]
            ],
            "responsive": true,
            "pageLength": 15,
            "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            "buttons": [{
                    extend: 'csv',
                    className: 'btn btn-sm btn-success me-2',
                    text: '<i class="bi bi-file-earmark-spreadsheet"></i> CSV'
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-sm btn-danger me-2',
                    text: '<i class="bi bi-file-earmark-pdf"></i> PDF'
                },
                {
                    extend: 'print',
                    className: 'btn btn-sm btn-dark',
                    text: '<i class="bi bi-printer"></i> Print'
                }
            ],
            "language": {
                "search": "Search programmes:",
                "lengthMenu": "Show _MENU_ records"
            }
        });

        $('#advancedProgTable').on('click', '.edit-btn', function(e) {
            e.preventDefault();

            // Get data from the clicked button attributes
            var id = $(this).data('id');
            var year = $(this).data('year');
            var type = $(this).data('type');
            var place = $(this).data('place');
            var desc = $(this).data('desc');

            // Populate the Modal fields
            $('#edit_prog_id').val(id);
            $('#edit_year').val(year);
            $('#edit_type_name').val(type);
            $('#edit_place').val(place);
            $('#edit_description').val(desc);

            // Show the modal
            $('#editAdvancedModal').modal('show');
        });


        // View Button Click
        $('#advancedProgTable').on('click', '.view-btn', function(e) {
            e.preventDefault();
            var data = $(this).data();

            // Fill the View Modal
            $('#view_year').text(data.year);
            $('#view_type_name').text(data.type);
            $('#view_place').text(data.place);
            $('#view_description').text(data.description || "No specific description entered.");

            // Status Badges
            var midClass = data.mid === 'Approved' ? 'bg-success' : (data.mid === 'Submitted' ? 'bg-info' : 'bg-warning');
            var finalClass = data.final === 'Approved' ? 'bg-success' : (data.final === 'Submitted' ? 'bg-info' : 'bg-secondary');

            $('#view_mid_status').text(data.mid).attr('class', 'badge rounded-pill px-4 ' + midClass);
            $('#view_final_status').text(data.final).attr('class', 'badge rounded-pill px-4 ' + finalClass);

            $('#viewAdvancedModal').modal('show');
        });

        // Edit Button Click (ensure we populate correctly)
        $('#advancedProgTable').on('click', '.edit-btn', function(e) {
            e.preventDefault();
            var data = $(this).data();

            $('#edit_prog_id').val(data.id);
            $('#edit_year').val(data.year);
            $('#edit_type_name').val(data.type);
            $('#edit_place').val(data.place);
            $('#edit_description').val(data.description);

            $('#editAdvancedModal').modal('show');
        });
    });
</script>

<?php

include 'models/add_advanced_programme_modal.php';
include 'models/manage_programme_types_modal.php';
include 'models/edit_advanced_programme_modal.php';
include 'models/view_advanced_programme_modal.php';
?>