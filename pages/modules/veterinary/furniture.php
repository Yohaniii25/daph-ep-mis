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
        
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark">Office Furniture Asset Registry</h3>
                <p class="text-muted small">
                    Jurisdiction Range: <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> | 
                    District: <strong class="text-dark"><?= htmlspecialchars($district_name) ?></strong>
                </p>
            </div>
            <div>
                <button class="btn btn-sm btn-outline-secondary me-2" onclick="window.print()">
                    <i class="bi bi-printer-fill me-1"></i> Print Inventory Report
                </button>
                <button class="btn text-white shadow-sm" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addFurnitureModal">
                    <i class="bi bi-plus-circle-fill me-2"></i>Log Furniture Item
                </button>
            </div>
        </div>

        <!-- Main Data Card Component Container -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="furnitureTable" class="table table-hover align-middle w-100">
                        <thead class="table-light text-uppercase small">
                            <tr>
                                <th>Furniture Type</th>
                                <th>Date of Purchase / Received</th>
                                <th>Current Condition</th>
                                <th class="text-center">Number Available</th>
                                <th>Additional Notes / Remarks</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Demo Record 1 -->
                            <tr>
                                <td class="fw-bold text-dark">
                                    <span class="d-block">Steel Filing Cabinets</span>
                                    <small class="text-muted font-monospace">INV-FRN-0082</small>
                                </td>
                                <td class="fw-semibold text-secondary">2024-03-15</td>
                                <td><span class="badge bg-success text-white rounded-pill px-2">Excellent</span></td>
                                <td class="text-center fw-bold text-primary">04</td>
                                <td><small class="text-muted">4-drawer fireproof units placed in the primary record storage vault room.</small></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary me-1" title="Edit Item"><i class="bi bi-pencil-square"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" title="Delete Log" onclick="handleFurnitureDelete(82, this)"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Demo Record 2 -->
                            <tr>
                                <td class="fw-bold text-dark">
                                    <span class="d-block">Wooden Executive Desks</span>
                                    <small class="text-muted font-monospace">INV-FRN-0105</small>
                                </td>
                                <td class="fw-semibold text-secondary">2021-11-02</td>
                                <td><span class="badge bg-warning text-dark rounded-pill px-2">Fair (Usable)</span></td>
                                <td class="text-center fw-bold text-primary">02</td>
                                <td><small class="text-muted">Polished teakwood desks used by the core clinical administration staff units.</small></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary me-1" title="Edit Item"><i class="bi bi-pencil-square"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" title="Delete Log" onclick="handleFurnitureDelete(105, this)"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Demo Record 3 -->
                            <tr>
                                <td class="fw-bold text-dark">
                                    <span class="d-block">Ergonomic Mesh Chairs</span>
                                    <small class="text-muted font-monospace">INV-FRN-0144</small>
                                </td>
                                <td class="fw-semibold text-secondary">2025-06-18</td>
                                <td><span class="badge bg-danger text-white rounded-pill px-2">Damaged</span></td>
                                <td class="text-center fw-bold text-primary">01</td>
                                <td><small class="text-dark fw-semibold">Hydraulic piston mechanism failure. Awaiting vendor replacement under warranty.</small></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary me-1" title="Edit Item"><i class="bi bi-pencil-square"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" title="Delete Log" onclick="handleFurnitureDelete(144, this)"><i class="bi bi-trash"></i></button>
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

<!-- Furniture Item Registration Modal -->
<div class="modal fade" id="addFurnitureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #820100;">
                <h5 class="modal-title"><i class="bi bi-file-earmark-plus-fill me-2"></i>Register New Furniture Inventory Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center text-muted">
                <p>Input variables targeting description metadata mapping will link to backend query controllers directly inside this structural element layout container.</p>
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
        // Initialize simple clean furniture tracking layout datatable configuration
        $('#furnitureTable').DataTable({ "pageLength": 5 });
    });

    // Elegant SweetAlert2 Deletion execution tracking script wrapper logic block
    function handleFurnitureDelete(id, buttonElement) {
        Swal.fire({
            title: 'Remove Furniture Item?',
            text: "This will permanently delete this logged furniture record from your system directory inventory.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#a07174', // Matches the customized rose-toned menu palette 
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete Item',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Record Dropped!',
                    text: 'The dynamic asset reference ledger entry row has been successfully removed.',
                    icon: 'success',
                    confirmButtonColor: '#370709'
                });
                $(buttonElement).closest('tr').fadeOut('slow', function() { $(this).remove(); });
            }
        });
    }
</script>

<?php require_once '../../../includes/footer.php'; ?>