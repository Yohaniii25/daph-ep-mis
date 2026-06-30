<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

// Regional boundaries scoping
$range_id = $_SESSION['range_id'] ?? null;
$range_name = $_SESSION['range_name'] ?? 'Your Range';
$district_id = $_SESSION['district_id'] ?? null;
$district_name = 'Your District';

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark">Fleet &amp; Vehicle Asset Registry</h3>
                <p class="text-muted small">
                    Jurisdiction Range: <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> | 
                    District: <strong class="text-dark"><?= htmlspecialchars($district_name) ?></strong>
                </p>
            </div>
            <div>
                <button class="btn btn-sm btn-outline-secondary me-2" onclick="window.print()">
                    <i class="bi bi-printer-fill me-1"></i> Print Fleet Report
                </button>
                <button class="btn text-white shadow-sm" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                    <i class="bi bi-plus-circle-fill me-2"></i>Register New Vehicle
                </button>
            </div>
        </div>

        <ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm" id="vehicleTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="fleet-tab" data-bs-toggle="tab" data-bs-target="#fleet-content" type="button" role="tab" style="--bs-nav-pills-link-active-bg: #b08723;">
                    <i class="bi bi-truck me-2"></i>Active Vehicle Details
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="repairs-tab" data-bs-toggle="tab" data-bs-target="#repairs-content" type="button" role="tab" style="--bs-nav-pills-link-active-bg: #b08723;">
                    <i class="bi bi-wrench-adjustable me-2"></i>Maintenance &amp; Repair Logs
                </button>
            </li>
        </ul>

        <div class="tab-content" id="vehicleTabsContent">
            
            <div class="tab-pane fade show active" id="fleet-content" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table id="vehiclesTable" class="table table-hover align-middle w-100">
                                <thead class="table-light text-uppercase small">
                                    <tr>
                                        <th>Vehicle Type</th>
                                        <th>Vehicle Number</th>
                                        <th>Chassis Number</th>
                                        <th>Current Condition</th>
                                        <th>Other Relevant Details</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-bold text-dark"><i class="bi bi-bicycle me-2 text-secondary"></i>Motorbike</td>
                                        <td><span class="badge text-white px-2 py-1 fs-6">WP BCX-8452</span></td>
                                        <td><span class="text-secondary small font-monospace">MJD32A10984321X</span></td>
                                        <td><span class="badge bg-success text-white rounded-pill px-2">Running (Good)</span></td>
                                        <td><small class="text-muted">Assigned to Field Officer for remote vaccination rounds.</small></td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                                <button class="btn btn-sm btn-outline-danger" title="Remove" onclick="handleVehicleDelete(1, this)"><i class="bi bi-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-dark"><i class="bi bi-truck me-2 text-secondary"></i>Single Cab (4x4)</td>
                                        <td><span class="badge text-white px-2 py-1 fs-6">CP NB-1024</span></td>
                                        <td><span class="text-secondary small font-monospace">AHTFR22G40812356</span></td>
                                        <td><span class="badge bg-warning text-dark rounded-pill px-2">Needs Repair</span></td>
                                        <td><small class="text-muted">Main range logistics vehicle. Suspended engine component issue.</small></td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                                <button class="btn btn-sm btn-outline-danger" title="Remove" onclick="handleVehicleDelete(2, this)"><i class="bi bi-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="repairs-content" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table id="repairsTable" class="table table-hover align-middle w-100">
                                <thead class="table-light text-uppercase small">
                                    <tr>
                                        <th>Repair Date</th>
                                        <th>Vehicle Number</th>
                                        <th>Repair Done</th>
                                        <th>Description of Repair</th>
                                        <th>Place of Repair</th>
                                        <th class="text-end">Amount (LKR)</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-semibold text-secondary">2026-04-12</td>
                                        <td><span class="badge bg-light text-dark border">WP BCX-8452</span></td>
                                        <td><span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">Routine Service</span></td>
                                        <td><small class="text-muted">Engine oil renewal, brake pad replacement, chain adjustments done.</small></td>
                                        <td><span class="small">Saman Motors, Local Junction</span></td>
                                        <td class="text-end fw-bold text-dark">14,500.00</td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-primary me-1" title="Edit Log"><i class="bi bi-pencil-square"></i></button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="handleVehicleDelete(101, this)"><i class="bi bi-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-secondary">2026-05-28</td>
                                        <td><span class="badge bg-light text-dark border">CP NB-1024</span></td>
                                        <td><span class="badge bg-danger-subtle text-danger border border-danger-subtle">Clutch Overhaul</span></td>
                                        <td><small class="text-muted">Full clutch plate setup replaced. Pressure plate alignment done.</small></td>
                                        <td><span class="small">District Engineering Workshop</span></td>
                                        <td class="text-end fw-bold text-dark">82,300.00</td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-primary me-1" title="Edit Log"><i class="bi bi-pencil-square"></i></button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="handleVehicleDelete(102, this)"><i class="bi bi-trash"></i></button>
                                            </div>
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

<div class="modal fade" id="addVehicleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #820100;">
                <h5 class="modal-title"><i class="bi bi-car-front-fill me-2"></i>Add Asset Log Entry</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center text-muted">
                <p>Form mapping endpoints dynamically targeting vehicle fields will load securely inside this node structure.</p>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        // Initialize dynamic responsive tracking tables
        $('#vehiclesTable').DataTable({ "pageLength": 5 });
        $('#repairsTable').DataTable({ 
            "pageLength": 5,
            "order": [[0, "desc"]]
        });
    });

    // Elegant SweetAlert2 Deletion execution wrapper
    function handleVehicleDelete(id, buttonElement) {
        Swal.fire({
            title: 'Delete Asset Entry?',
            text: "This will cleanly remove the fleet profile information from your current interface.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#b08723', // Matches the golden brand button accent context
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Confirm Delete',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Removed!',
                    text: 'The vehicle registry record entity line item has been scrubbed successfully.',
                    icon: 'success',
                    confirmButtonColor: '#370709'
                });
                $(buttonElement).closest('tr').fadeOut('slow', function() { $(this).remove(); });
            }
        });
    }
</script>

<?php require_once '../../../includes/footer.php'; ?>