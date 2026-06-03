<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'training_officer') die("Access denied");
require_once '../../../config/db_connect.php';

?>

<?php require_once '../../../includes/sidebar.php'; ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Training Activities</h2>

        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Identified Target Groups</h6>
                        <h2 class="text-primary mb-0 fw-bold">30</h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Total Participants</h6>
                        <h2 class="text-warning mb-0 fw-bold">1,200</h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Total Allocations</h6>
                        <h2 class="text-success mb-0 fw-bold">800</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#addTrainingModal">
                            <i class="bi bi-plus-circle"></i><br>
                            Create Training Program
                        </button>
                    </div>
                    <div class="col-md-3">
                        <a style="background-color: #370709; color: white;" href="vaccine_types.php" class="btn w-100 py-3">
                            <i class="bi bi-people"></i><br>
                            Add New Target Group
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a style="background-color: #b08723; color: white;" href="accommodation.php" class="btn w-100 py-3">
                            <i class="bi bi-house-add"></i><br>
                            Maintain Accommodation Inventory
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="m-0 fw-bold text-dark"><i class="bi bi-shield-plus me-2 text-success"></i>Training Programmes</h5>
            </div>
            <div class="card-body">
                <table id="immunizationTable" class="table table-bordered table-striped align-middle row-border" style="width:100%">

                </table>
            </div>
        </div>
    </main>
</div>


<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
    $(document).ready(function() {
        $('#immunizationTable').DataTable({
            "order": [
                [0, "desc"]
            ],
            "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search ledger rows..."
            },
            "buttons": [{
                    extend: 'csv',
                    text: '<i class="bi bi-filetype-csv"></i> CSV',
                    className: 'btn btn-sm btn-success me-1 rounded shadow-sm'
                },
                {
                    extend: 'pdf',
                    text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                    className: 'btn btn-sm btn-danger me-1 rounded shadow-sm',
                    title: 'Monthly Immunization Stock Ledger Summary'
                },
                {
                    extend: 'print',
                    text: '<i class="bi bi-printer"></i> Print',
                    className: 'btn btn-sm btn-warning text-dark rounded shadow-sm'
                }
            ]
        });
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>