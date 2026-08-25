<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'administrator') {
    header("Location: ../../../index.php");
    exit();
}

$current_user = $_SESSION['user_id'];

// Stats for Amended Programmes
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN amendment_reason IS NOT NULL AND amendment_reason != '' THEN 1 ELSE 0 END) as documented_reasons
    FROM amended_programmes 
    WHERE user_id = '$current_user'"; // Ensure user_id exists in your amended table

$stats_result = $mysqli->query($stats_query);
$stats = $stats_result->fetch_assoc();

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold">Amended Programme Management</h3>
                <p class="text-muted small">Modified Advanced Programmes</p>
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
                                <span class="fw-bold text-uppercase small">Add Amended Programme</span>
                                <p class="mb-0 x-small" style="font-size: 0.75rem; color: white;">Add/Edit global programme categories</p>
                            </div>
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm bg-success text-white">
                        <div class="card-body">
                            <h6 class="small text-uppercase opacity-75">Total Amendments</h6>
                            <h3 class="fw-bold mb-0"><?php echo $stats['total'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="background-color: #b08723;" class="card border-0 shadow-sm text-white">
                        <div class="card-body">
                            <h6 class="small text-uppercase opacity-75">Reasoned Modifications</h6>
                            <h3 class="fw-bold mb-0"><?php echo $stats['documented_reasons'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="card shadow-sm border-0">
            <div class="card-body p-3">
                <table class="table table-hover align-middle" id="amendedProgTable">
                    <thead class="bg-light">
                        <tr>
                            <th>Date Created</th>
                            <th>Original Ref</th>
                            <th>Year</th>
                            <th>Programme Name</th>
                            <th>New Location</th>
                            <th>Reason for Amendment</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Updated Query for the Amended Table
                        $sql = "SELECT am.*, mpt.programme_name 
                                FROM amended_programmes am
                                JOIN master_programme_types mpt ON am.type_id = mpt.id
                                WHERE am.user_id = '$current_user' 
                                ORDER BY am.created_at DESC";

                        $result = $mysqli->query($sql);

                        while ($row = $result->fetch_assoc()):
                        ?>
                            <tr>
                                <td class="small"><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                                <td><span class="badge bg-secondary">#<?php echo $row['original_id']; ?></span></td>
                                <td><span class="badge bg-light text-dark border"><?php echo $row['programme_year']; ?></span></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($row['programme_name']); ?></td>
                                <td><i class="bi bi-geo-alt me-1 text-danger"></i><?php echo htmlspecialchars($row['place']); ?></td>
                                <td>
                                    <small class="text-muted italic">"<?php echo htmlspecialchars($row['amendment_reason']); ?>"</small>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <button class="btn btn-sm btn-outline-info view-amend-btn"
                                            data-id="<?php echo $row['id']; ?>"
                                            data-reason="<?php echo htmlspecialchars($row['amendment_reason']); ?>"
                                            data-desc="<?php echo htmlspecialchars($row['activity_description']); ?>">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <a href="edit_amendment.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
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

<style>
    /* Important: Fix jQuery UI Z-Index to show above Bootstrap Modal */
    .ui-autocomplete {
        z-index: 2150000000 !important;
    }
</style>

<?php require_once '../../../includes/footer.php'; ?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">

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
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<link rel="stylesheet" href="../../../assets/css/jquery-ui.css">

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

        $("#search_original").autocomplete({
            source: "fetch_programmes.php",
            minLength: 2,
            // Ensure the menu stays above the modal
            appendTo: "#manageTypesModal",
            select: function(event, ui) {
                // Fill the form fields with the data found
                $("#original_id").val(ui.item.id);
                $("#amend_year").val(ui.item.year);
                $("#amend_place").val(ui.item.place);
                $("#amend_description").val(ui.item.desc);

                // Visual feedback
                $("#search_original").addClass("is-valid");
            }
        });
    });
</script>

<?php
require_once 'models/add_amended_programme_modal.php';
?>