<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

$range_id = $_SESSION['range_id'] ?? null;
$range_name = $_SESSION['range_name'] ?? 'Your Range';
$district_id = $_SESSION['district_id'] ?? null;
$district_name = 'Your District';

// Pull dynamic naming configurations
if (!empty($district_id)) {
    $dst_stmt = $mysqli->prepare("SELECT name FROM districts WHERE id = ?");
    $dst_stmt->bind_param("i", $district_id);
    $dst_stmt->execute();
    $dst_res = $dst_stmt->get_result();
    if ($row = $dst_res->fetch_assoc()) $district_name = $row['name'];
    $dst_stmt->close();
}
if (!empty($range_id)) {
    $rng_stmt = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
    $rng_stmt->bind_param("i", $range_id);
    $rng_stmt->execute();
    $rng_res = $rng_stmt->get_result();
    if ($row = $rng_res->fetch_assoc()) $range_name = $row['name'];
    $rng_stmt->close();
}

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">



        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h3 class="fw-bold text-dark mb-1">Lands &amp; Buildings Asset Registry</h3>
                <p class="text-muted small mb-0">
                    Jurisdiction Range: <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> |
                    District: <strong class="text-dark"><?= htmlspecialchars($district_name) ?></strong>
                </p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn text-light shadow-sm" style="background-color: #370709;" data-bs-toggle="modal" data-bs-target="#addAssetModal">
                    <i class="bi bi-plus-circle-fill me-2"></i>Register Property
                </button>
                <button class="btn btn-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#addInventoryModal">
                    <i class="bi bi-box-seam-fill me-2"></i>Log Inventory
                </button>
                <a href="office_details.php" class="btn btn-secondary shadow-sm">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm" id="propertyTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="lands-tab" data-bs-toggle="tab" data-bs-target="#lands-content" type="button" role="tab" style="--bs-nav-pills-link-active-bg: #820100;">
                    <i class="bi bi-geo-alt-fill me-2"></i>Land Profiles &amp; Deeds
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory-content" type="button" role="tab" style="--bs-nav-pills-link-active-bg: #820100;">
                    <i class="bi bi-boxes me-2"></i>Building Inventory Items
                </button>
            </li>
        </ul>

        <div class="tab-content" id="propertyTabsContent">

            <!-- TAB 1: LAND PROFILES -->
            <div class="tab-pane fade show active" id="lands-content" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table id="landsTable" class="table table-hover align-middle w-100">
                                <thead class="table-light text-uppercase small">
                                    <tr>
                                        <th>Property Name</th>
                                        <th>Land Extent</th>
                                        <th>Building Area</th>
                                        <th>Land Status</th>
                                        <th>Deed Reference Details</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $lands_query = $mysqli->prepare("SELECT * FROM land_assets WHERE district_id = ? AND range_id = ? AND is_active = 1 ORDER BY id DESC");
                                    $lands_query->bind_param("ii", $district_id, $range_id);
                                    $lands_query->execute();
                                    $lands_result = $lands_query->get_result();

                                    // Cache lands into an array to populate our dropdown later
                                    $lands_cache = [];

                                    while ($row = $lands_result->fetch_assoc()):
                                        $lands_cache[] = $row;
                                    ?>
                                        <tr id="property-row-<?= $row['id'] ?>">
                                            <td><span class="fw-bold text-dark"><?= htmlspecialchars($row['property_name']) ?></span></td>
                                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['land_extent']) ?></span></td>
                                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['building_area']) ?></span></td>
                                            <td><span class="badge bg-success-subtle text-success border px-2 py-1"><?= htmlspecialchars($row['land_status']) ?></span></td>
                                            <td>
                                                <div class="fw-semibold text-secondary small"><?= htmlspecialchars($row['deed_reference']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($row['deed_description']) ?></small>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-danger" onclick="handleAssetDelete(<?= $row['id'] ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile;
                                    $lands_query->close(); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: BUILDING INVENTORY SYSTEM -->
            <div class="tab-pane fade" id="inventory-content" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table id="inventoryTable" class="table table-hover align-middle w-100">
                                <thead class="table-light text-uppercase small">
                                    <tr>
                                        <th>Inventory Item</th>
                                        <th>Located Property</th>
                                        <th>Item Specification</th>
                                        <th>Condition</th>
                                        <th class="text-center">Qty</th>
                                        <th>Remarks</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $inv_query = $mysqli->prepare("
                                        SELECT bi.*, la.property_name 
                                        FROM building_inventories bi
                                        JOIN land_assets la ON bi.land_asset_id = la.id
                                        WHERE la.district_id = ? AND la.range_id = ? AND bi.is_active = 1 
                                        ORDER BY bi.id DESC
                                    ");
                                    $inv_query->bind_param("ii", $district_id, $range_id);
                                    $inv_query->execute();
                                    $inv_result = $inv_query->get_result();

                                    while ($row = $inv_result->fetch_assoc()):
                                        // Condition badge styling engine
                                        $badge_class = 'bg-secondary';
                                        if ($row['current_condition'] === 'Excellent' || $row['current_condition'] === 'Good') $badge_class = 'bg-success';
                                        elseif ($row['current_condition'] === 'Fair (Needs Service)') $badge_class = 'bg-warning text-dark';
                                        elseif ($row['current_condition'] === 'Critical Failure' || $row['current_condition'] === 'Damaged') $badge_class = 'bg-danger';
                                    ?>
                                        <tr id="inventory-row-<?= $row['id'] ?>">
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($row['inventory_item']) ?></td>
                                            <td><span class="text-secondary small fw-semibold"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($row['property_name']) ?></span></td>
                                            <td><small class="text-muted"><?= htmlspecialchars($row['specification']) ?></small></td>
                                            <td><span class="badge <?= $badge_class ?> rounded-pill px-2"><?= htmlspecialchars($row['current_condition']) ?></span></td>
                                            <td class="text-center fw-bold text-primary"><?= sprintf("%02d", $row['available_quantity']) ?></td>
                                            <td><small class="text-muted"><?= htmlspecialchars($row['remarks']) ?></small></td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-danger" onclick="handleInventoryDelete(<?= $row['id'] ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile;
                                    $inv_query->close(); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<?php include 'models/add_land_property.php'; ?>

<?php include 'models/add_building_inventory.php'; ?>


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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    var landsTable, inventoryTable;
    $(document).ready(function() {
        landsTable = $('#landsTable').DataTable({
            "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rtip',
            "buttons": [{
                    extend: 'csv',
                    className: 'btn btn-sm btn-success shadow-sm',
                    text: '<i class="bi bi-file-spreadsheet"></i> CSV'
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-sm btn-danger shadow-sm',
                    text: '<i class="bi bi-file-pdf"></i> PDF',
                    title: 'Land Profiles and Deeds'
                },
                {
                    extend: 'print',
                    className: 'btn btn-sm btn-dark shadow-sm',
                    text: '<i class="bi bi-printer"></i> Print'
                }
            ],
            "pageLength": 10
        });

        inventoryTable = $('#inventoryTable').DataTable({
            "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rtip',
            "buttons": [{
                    extend: 'csv',
                    className: 'btn btn-sm btn-success shadow-sm',
                    text: '<i class="bi bi-file-spreadsheet"></i> CSV'
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-sm btn-danger shadow-sm',
                    text: '<i class="bi bi-file-pdf"></i> PDF',
                    title: 'Building Inventory Items'
                },
                {
                    extend: 'print',
                    className: 'btn btn-sm btn-dark shadow-sm',
                    text: '<i class="bi bi-printer"></i> Print'
                }
            ],
            "pageLength": 10
        });

        // Submit Land Form
        $('#addAssetForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'processors/add_land_asset.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Saved!', response.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }
            });
        });

        // Submit Inventory Form
        $('#addInventoryForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'processors/add_building_inventory.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Logged!', response.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }
            });
        });
    });

    function handleAssetDelete(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will remove this active property tracking row profile.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#820100',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'processors/delete_land_asset.php',
                    type: 'POST',
                    data: {
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success');
                            landsTable.row('#property-row-' + id).remove().draw(false);
                        } else {
                            Swal.fire('Failed', response.message, 'error');
                        }
                    }
                });
            }
        });
    }

    function handleInventoryDelete(id) {
        Swal.fire({
            title: 'Delete Inventory Item?',
            text: "This will remove this specific item log from the building inventory.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#212529',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'processors/delete_building_inventory.php',
                    type: 'POST',
                    data: {
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Removed!', response.message, 'success');
                            inventoryTable.row('#inventory-row-' + id).remove().draw(false);
                        } else {
                            Swal.fire('Failed', response.message, 'error');
                        }
                    }
                });
            }
        });
    }
</script>

<?php require_once '../../../includes/footer.php'; ?>