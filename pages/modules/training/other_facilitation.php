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
        <h2 class="mb-4 text-dark fw-bold">Other Facilitation Details</h2>

        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Total Hall Bookings</h6>
                        <h2 class="text-warning mb-0 fw-bold">01</h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Accommodation Bookings</h6>
                        <h2 class="text-success mb-0 fw-bold">10</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3 rounded-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#addHallBookingModal">
                            <i class="bi bi-plus-circle mb-1 d-block"></i>
                            Hall Booking Management
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button style="background-color: #370709; color: white;" class="btn w-100 py-3 rounded-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#addAccommodationModal">
                            <i class="bi bi-house-add mb-1 d-block"></i>
                            Add New Accommodation
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <h5 class="m-0 fw-bold text-dark"><i class="bi bi-building me-2 text-primary"></i>Facilitation &amp; Inventory Ledger</h5>
                
                <ul class="nav nav-pills card-header-pills" id="facilitationTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold px-4" id="halls-tab" data-bs-toggle="tab" data-bs-target="#halls-view" type="button" role="tab" aria-controls="halls-view" aria-selected="true">
                            <i class="bi bi-door-open-fill me-2"></i>Hall Bookings
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold px-4" id="accommodation-tab" data-bs-toggle="tab" data-bs-target="#accommodation-view" type="button" role="tab" aria-controls="accommodation-view" aria-selected="false">
                            <i class="bi bi-moon-stars-fill me-2"></i>Accommodation Bookings
                        </button>
                    </li>
                </ul>
            </div>
            
            <div class="card-body">
                <div class="tab-content" id="facilitationTabsContent">
                    
                    <div class="tab-pane fade show active" id="halls-view" role="tabpanel" aria-labelledby="halls-tab">
                        <div class="table-responsive">
                            <table id="hallBookingsTable" class="table table-hover align-middle table-striped border row-border w-100">
                                <thead class="table-light text-secondary">
                                    <tr>
                                        <th style="width: 10%;">ID</th>
                                        <th style="width: 25%;">Hall / Venue Name</th>
                                        <th style="width: 35%;">Assigned Training Program</th>
                                        <th class="text-center" style="width: 15%;">Booking Date</th>
                                        <th class="text-center" style="width: 15%;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="font-monospace fw-bold text-muted">#HB-001</td>
                                        <td class="fw-bold text-dark">Main Lecture Hall A</td>
                                        <td>
                                            <div class="fw-semibold">Modern Milking Hygiene &amp; Quality Parameters</div>
                                            <small class="text-muted font-monospace bg-light px-1.5 py-0.5 rounded border" style="font-size: 11px;">TG-041</small>
                                        </td>
                                        <td class="text-center font-monospace small">2026-06-15</td>
                                        <td class="text-center">
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-2.5 py-1">Confirmed</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="accommodation-view" role="tabpanel" aria-labelledby="accommodation-tab">
                        <div class="table-responsive">
                            <table id="accommodationTable" class="table table-hover align-middle table-striped border row-border w-100">
                                <thead class="table-light text-secondary">
                                    <tr>
                                        <th style="width: 10%;">Reference ID</th>
                                        <th style="width: 25%;">Hostel / Room Details</th>
                                        <th style="width: 30%;">Guest / Officer Alignment</th>
                                        <th class="text-center" style="width: 10%;">Beds Allocated</th>
                                        <th class="text-center" style="width: 15%;">Duration Dates</th>
                                        <th class="text-center" style="width: 10%;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="font-monospace fw-bold text-muted">#AB-1092</td>
                                        <td class="fw-bold text-dark">Veterinary Training Hostel - Block B (Room 04)</td>
                                        <td>
                                            <div class="fw-semibold">Ampara Range Field Officers</div>
                                            <small class="text-muted small">Batch 03 Induction Seminar</small>
                                        </td>
                                        <td class="text-center font-monospace fw-bold">04</td>
                                        <td class="text-center small">
                                            <div class="font-monospace text-dark">06-12 to 06-15</div>
                                            <small class="text-muted">(3 Nights)</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge rounded-pill px-2.5 py-1" style="background-color: #370709; color: #ffffff;">Checked-In</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>
</div>

<?php include './models/add_hall_booking.php'; ?>
<?php include './models/add_accommodation.php'; ?>

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
        // Shared boilerplate config structure matching your exact datatable rules
        const tableOptions = {
            "order": [[0, "desc"]],
            "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search ledger rows..."
            },
            "buttons": [
                { extend: 'csv', text: '<i class="bi bi-filetype-csv"></i> CSV', className: 'btn btn-sm btn-success me-1 rounded shadow-sm' },
                { extend: 'pdf', text: '<i class="bi bi-file-earmark-pdf"></i> PDF', className: 'btn btn-sm btn-danger me-1 rounded shadow-sm', title: 'Facilitation Bookings Log Summary' },
                { extend: 'print', text: '<i class="bi bi-printer"></i> Print', className: 'btn btn-sm btn-warning text-dark rounded shadow-sm' }
            ]
        };

        // Initialize both separate list instances safely
        $('#hallBookingsTable').DataTable(tableOptions);
        $('#accommodationTable').DataTable(tableOptions);

        // Fix column alignment problems instantly when switching tabs view
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        });
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>