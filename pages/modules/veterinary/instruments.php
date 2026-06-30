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
        
        <!-- Header Section (Clean: No action button columns) -->
        <div class="mb-4">
            <h3 class="fw-bold text-dark">Machinery &amp; Equipment Asset Registry</h3>
            <p class="text-muted small mb-0">
                Jurisdiction Range: <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> | 
                District: <strong class="text-dark"><?= htmlspecialchars($district_name) ?></strong>
            </p>
        </div>

        <!-- Main Data Card Component Container -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="machineryTable" class="table table-hover align-middle w-100">
                        <thead class="table-light text-uppercase small">
                            <tr>
                                <th>Type</th>
                                <th>Date of Purchase / Received</th>
                                <th>Condition</th>
                                <th class="text-center">Available Quantity</th>
                                <th>Remarks</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Demo Record 1 -->
                            <tr>
                                <td class="fw-bold text-dark">
                                    <span class="d-block">Autoclave Sterilizer Units</span>
                                    <small class="text-muted font-monospace">MAC-EQP-042</small>
                                </td>
                                <td class="fw-semibold text-secondary">2025-02-10</td>
                                <td><span class="badge bg-success text-white rounded-pill px-2">Excellent</span></td>
                                <td class="text-center fw-bold text-primary">02</td>
                                <td><small class="text-muted">Digital 50L vertical pressure steam models installed in lab room A.</small></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary me-1" title="Edit Item"><i class="bi bi-pencil-square"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" title="Delete Log" onclick="handleMachineryDelete(42, this)"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Demo Record 2 -->
                            <tr>
                                <td class="fw-bold text-dark">
                                    <span class="d-block">Centrifuge Machines</span>
                                    <small class="text-muted font-monospace">MAC-EQP-118</small>
                                </td>
                                <td class="fw-semibold text-secondary">2023-08-24</td>
                                <td><span class="badge bg-warning text-dark rounded-pill px-2">Fair (Needs Calibration)</span></td>
                                <td class="text-center fw-bold text-primary">03</td>
                                <td><small class="text-muted">Tabletop laboratory models. Rotor unit 3 exhibits minor vibrational noise.</small></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary me-1" title="Edit Item"><i class="bi bi-pencil-square"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" title="Delete Log" onclick="handleMachineryDelete(118, this)"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Demo Record 3 -->
                            <tr>
                                <td class="fw-bold text-dark">
                                    <span class="d-block">Backup Diesel Generator</span>
                                    <small class="text-muted font-monospace">MAC-EQP-005</small>
                                </td>
                                <td class="fw-semibold text-secondary">2021-05-14</td>
                                <td><span class="badge bg-danger text-white rounded-pill px-2">Under Repair</span></td>
                                <td class="text-center fw-bold text-primary">01</td>
                                <td><small class="text-dark fw-semibold">15kVA silent canopy model. Fuel injection timing pump replacement underway.</small></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary me-1" title="Edit Item"><i class="bi bi-pencil-square"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" title="Delete Log" onclick="handleMachineryDelete(5, this)"><i class="bi bi-trash"></i></button>
                                    </div>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        // Initialize independent machinery tracking datatable configuration
        $('#machineryTable').DataTable({ "pageLength": 5 });
    });

    // Custom matching SweetAlert2 Deletion tracking logic script 
    function handleMachineryDelete(id, buttonElement) {
        Swal.fire({
            title: 'Remove Machinery Log?',
            text: "This will permanently eliminate this machinery asset entry from your internal inventory records ledger.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4a6984', // Matches the machinery slate-blue accent theme
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete Machinery',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Asset Deleted!',
                    text: 'The equipment tracking reference index entry line row dropped successfully.',
                    icon: 'success',
                    confirmButtonColor: '#370709'
                });
                $(buttonElement).closest('tr').fadeOut('slow', function() { $(this).remove(); });
            }
        });
    }
</script>

<?php require_once '../../../includes/footer.php'; ?>