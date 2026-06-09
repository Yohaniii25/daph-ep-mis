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
        <h2 class="mb-4 text-dark fw-bold">Production Details</h2>

        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Total Production Runs</h6>
                        <h2 class="text-warning mb-0 fw-bold">04</h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Total Production Hours</h6>
                        <h2 class="text-success mb-0 fw-bold">10</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3 rounded-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#addProductionModal">
                            <i class="bi bi-plus-circle mb-1 d-block"></i>
                            Production Management
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="m-0 fw-bold text-dark"><i class="bi bi-gear-wide-connected me-2 text-success"></i>Production Output Logs</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="productionTable" class="table table-bordered table-striped align-middle row-border w-100">
                        <thead class="table-light text-secondary">
                            <tr>
                                <th style="width: 10%;">Run ID</th>
                                <th style="width: 25%;">Produced Item / Output</th>
                                <th class="text-end" style="width: 15%;">Amount / Yield</th>
                                <th style="width: 20%;">Operational Range</th>
                                <th class="text-center" style="width: 15%;">Run Date</th>
                                <th class="text-center" style="width: 15%;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="font-monospace fw-bold text-muted">#PRD-041</td>
                                <td class="fw-bold text-dark">Pasteurized Whole Milk</td>
                                <td class="text-end font-monospace fw-bold text-primary">450.00 Ltr</td>
                                <td>Ampara Veterinary Center</td>
                                <td class="text-center font-monospace small">2026-06-08</td>
                                <td class="text-center">
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-2.5 py-1">Verified</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="font-monospace fw-bold text-muted">#PRD-042</td>
                                <td class="fw-bold text-dark">Layer Poultry Feed (Batch A)</td>
                                <td class="text-end font-monospace fw-bold text-primary">1,200.00 Kg</td>
                                <td>Trincomalee Field Station</td>
                                <td class="text-center font-monospace small">2026-06-09</td>
                                <td class="text-center">
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning-subtle rounded-pill px-2.5 py-1">Pending Check</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include './models/add_production_item.php'; ?>

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
        $('#productionTable').DataTable({
            "order": [
                [0, "desc"]
            ],
            "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search production logs..."
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
                    title: 'Production Runs Output Summary Report'
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