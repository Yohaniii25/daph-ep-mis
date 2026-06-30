<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

// Regional boundaries scoping (matches your established layout tracking)
$range_id = $_SESSION['range_id'] ?? null;
$range_name = $_SESSION['range_name'] ?? 'Your Range';
$district_id = $_SESSION['district_id'] ?? null;
$district_name = 'Your District';

// [Insert your existing user and regional database name fetching queries here if needed]

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
                <h3 class="fw-bold text-dark">Lands &amp; Buildings Asset Registry</h3>
                <p class="text-muted small">
                    Jurisdiction Range: <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> | 
                    District: <strong class="text-dark"><?= htmlspecialchars($district_name) ?></strong>
                </p>
            </div>
            <div>
                <button class="btn btn-sm btn-outline-secondary me-2" onclick="window.print()">
                    <i class="bi bi-printer-fill me-1"></i> Print Report
                </button>
                <button class="btn text-white shadow-sm" style="background-color: #820100;" data-bs-toggle="modal" data-bs-target="#addAssetModal">
                    <i class="bi bi-plus-circle-fill me-2"></i>Register New Property
                </button>
                <!-- add back button -->
                <button class="btn btn-sm btn-outline-secondary me-2" onclick="window.history.back()">
                    <i class="bi bi-arrow-left-circle-fill me-1"></i> Back
                </button>
            </div>
        </div>

        <!-- Navigation Tabs to keep layout organized -->
        <ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm" id="propertyTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="lands-tab" data-bs-toggle="tab" data-bs-target="#lands-content" type="button" role="tab" style="--bs-nav-pills-link-active-bg: #820100;">
                    <i class="bi bi-geo-alt-fill me-2"></i>Land Profiles &amp; Deeds
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory-content" type="button" role="tab" style="--bs-nav-pills-link-active-bg: #820100;">
                    <i class="bi bi-boxes me-2"></i>Building Inventory Items
                </button>
            </li>
        </ul>

        <div class="tab-content" id="propertyTabsContent">
            
            <!-- TAB 1: LAND & BUILDING SUMMARY DETAILS -->
            <div class="tab-pane fade show active" id="lands-content" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table id="landsTable" class="table table-hover align-middle w-100">
                                <thead class="table-light text-uppercase small">
                                    <tr>
                                        <th>Property Ref</th>
                                        <th>Land Extent</th>
                                        <th>Building Area</th>
                                        <th>Land Status</th>
                                        <th>Deed Reference Details</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Demo Record 01 -->
                                    <tr>
                                        <td><span class="fw-bold text-dark">PROP/LND/041</span><br><small class="text-muted">Main Office Complex Site</small></td>
                                        <td><span class="badge bg-light text-dark border">2 Acres, 1 Rood, 15 Perches</span></td>
                                        <td><span class="badge bg-light text-dark border">4,250 sq. ft.</span></td>
                                        <td><span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">State Owned</span></td>
                                        <td>
                                            <div class="fw-semibold text-secondary small">Deed No: G-5421 / Volume 12</div>
                                            <small class="text-muted text-wrap">Registered under Land Registry Office - Colombo North</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-primary me-1" title="Edit Properties"><i class="bi bi-pencil-square"></i></button>
                                                <button class="btn btn-sm btn-outline-danger" title="Remove" onclick="handleAssetDelete(41, this)"><i class="bi bi-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Demo Record 02 -->
                                    <tr>
                                        <td><span class="fw-bold text-dark">PROP/LND/042</span><br><small class="text-muted">Sub-Quarter Quarters Block A</small></td>
                                        <td><span class="badge bg-light text-dark border">0 Acres, 2 Roods, 10 Perches</span></td>
                                        <td><span class="badge bg-light text-dark border">1,850 sq. ft.</span></td>
                                        <td><span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">Leased (Vested)</span></td>
                                        <td>
                                            <div class="fw-semibold text-secondary small">LRC Transfer Cert: LRC/98/204</div>
                                            <small class="text-muted text-wrap">Vested via Land Reform Commission executive order.</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-primary me-1" title="Edit Properties"><i class="bi bi-pencil-square"></i></button>
                                                <button class="btn btn-sm btn-outline-danger" title="Remove" onclick="handleAssetDelete(42, this)"><i class="bi bi-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: DETAILED BUILDING INVENTORY SYSTEM -->
            <div class="tab-pane fade" id="inventory-content" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table id="inventoryTable" class="table table-hover align-middle w-100">
                                <thead class="table-light text-uppercase small">
                                    <tr>
                                        <th>Inventory Item</th>
                                        <th>Item Specification</th>
                                        <th>Current Condition</th>
                                        <th class="text-center">Available Qty</th>
                                        <th>Additional Notes / Remarks</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Demo Record 01 -->
                                    <tr>
                                        <td class="fw-bold text-dark">A/C Units (Inverter)</td>
                                        <td><span class="text-secondary small">12,000 BTU, Wall-Mounted, Panasonic Eco</span></td>
                                        <td><span class="badge bg-success text-white rounded-pill px-2">Excellent</span></td>
                                        <td class="text-center fw-bold text-primary">04</td>
                                        <td><small class="text-muted">Installed in cold storage area &amp; main pharmacy station space.</small></td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-primary me-1" title="Modify Logs"><i class="bi bi-pencil-square"></i></button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="handleAssetDelete(101, this)"><i class="bi bi-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Demo Record 02 -->
                                    <tr>
                                        <td class="fw-bold text-dark">Fluorescent Ceiling Fixtures</td>
                                        <td><span class="text-secondary small">4ft Dual Tube, 36W LED upgraded layout panels</span></td>
                                        <td><span class="badge bg-warning text-dark rounded-pill px-2">Fair (Needs Service)</span></td>
                                        <td class="text-center fw-bold text-primary">18</td>
                                        <td><small class="text-muted">2 bulbs blinking in public registration reception counter desk hall.</small></td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-primary me-1" title="Modify Logs"><i class="bi bi-pencil-square"></i></button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="handleAssetDelete(102, this)"><i class="bi bi-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Demo Record 03 -->
                                    <tr>
                                        <td class="fw-bold text-dark">Water Sump Pumps</td>
                                        <td><span class="text-secondary small">1.5 HP, Centrifugal single phase motor unit</span></td>
                                        <td><span class="badge bg-danger text-white rounded-pill px-2">Critical Failure</span></td>
                                        <td class="text-center fw-bold text-primary">01</td>
                                        <td><small class="text-dark fw-semibold">Requires total winding replacement immediately by technical suppliers.</small></td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-primary me-1" title="Modify Logs"><i class="bi bi-pencil-square"></i></button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="handleAssetDelete(103, this)"><i class="bi bi-trash"></i></button>
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

<!-- Placeholder Registration Modal Template Structure -->
<div class="modal fade" id="addAssetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #820100;">
                <h5 class="modal-title"><i class="bi bi-building-fill-add me-2"></i>Register Property / Inventory Asset</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center text-muted">
                <p>Asset wizard data inputs form template will connect seamlessly here to pass values into your backend pipeline.</p>
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
        // Initialize independent clean dataTables configurations
        $('#landsTable').DataTable({ "pageLength": 5 });
        $('#inventoryTable').DataTable({ "pageLength": 5 });
    });

    // Beautiful deletion handler using SweetAlert2 framework
    function handleAssetDelete(id, buttonElement) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You are attempting to remove this logged record entity permanently.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#820100',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete Asset',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Asset Deleted!',
                    text: 'The selective registry line entry has been scrubbed successfully.',
                    icon: 'success',
                    confirmButtonColor: '#370709'
                });
                $(buttonElement).closest('tr').fadeOut('slow', function() { $(this).remove(); });
            }
        });
    }
</script>

<?php require_once '../../../includes/footer.php'; ?>