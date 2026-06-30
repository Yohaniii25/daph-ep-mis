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
        <div class="mb-4">
            <h3 class="fw-bold text-dark">Counter Foliage &amp; Landscape Asset Registry</h3>
            <p class="text-muted small mb-0">
                Jurisdiction Range: <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> | 
                District: <strong class="text-dark"><?= htmlspecialchars($district_name) ?></strong>
            </p>
        </div>

        <!-- Main Data Card Component Container -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="foliageTable" class="table table-hover align-middle w-100">
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
                                    <span class="d-block">Indoor Decorative Planters</span>
                                    <small class="text-muted font-monospace">FOL-PLN-012</small>
                                </td>
                                <td class="fw-semibold text-secondary">2025-05-20</td>
                                <td><span class="badge bg-success text-white rounded-pill px-2">Excellent</span></td>
                                <td class="text-center fw-bold text-primary">06</td>
                                <td><small class="text-muted">Self-watering ceramic counter pots with low-maintenance air purifier foliage installations.</small></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary me-1" title="Edit Item"><i class="bi bi-pencil-square"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" title="Delete Log" onclick="handleFoliageDelete(12, this)"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Demo Record 2 -->
                            <tr>
                                <td class="fw-bold text-dark">
                                    <span class="d-block">Countertop Synthetic Turf Mats</span>
                                    <small class="text-muted font-monospace">FOL-TUR-045</small>
                                </td>
                                <td class="fw-semibold text-secondary">2024-09-12</td>
                                <td><span class="badge bg-warning text-dark rounded-pill px-2">Fair (Slight Wear)</span></td>
                                <td class="text-center fw-bold text-primary">12</td>
                                <td><small class="text-muted">Anti-slip green accent borders used on public registration sample submission counters.</small></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary me-1" title="Edit Item"><i class="bi bi-pencil-square"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" title="Delete Log" onclick="handleFoliageDelete(45, this)"><i class="bi bi-trash"></i></button>
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
        // Initialize dynamic DataTables configuration
        $('#foliageTable').DataTable({ "pageLength": 5 });
    });

    // Custom matching SweetAlert2 Deletion workflow
    function handleFoliageDelete(id, buttonElement) {
        Swal.fire({
            title: 'Remove Foliage Record?',
            text: "This will permanently remove this botanical asset log entry from your internal desk inventory registry.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#198754', // Matches the foliage emerald green accent theme
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete Record',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Asset Removed!',
                    text: 'The counter foliage layout registry profile entry was dropped successfully.',
                    icon: 'success',
                    confirmButtonColor: '#370709'
                });
                $(buttonElement).closest('tr').fadeOut('slow', function() { $(this).remove(); });
            }
        });
    }
</script>

<?php require_once '../../../includes/footer.php'; ?>