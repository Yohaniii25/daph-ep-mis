<?php
// pages/modules/sms/lands_buildings.php -> SMS Lands & Buildings Asset Registry
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

$allowed_roles = ['sms', 'administrator', 'provincial_director', 'district_dd'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 12;

// Fetch Land Assets for current Subject Matter Specialist
$lands_stmt = $mysqli->prepare("SELECT * FROM land_assets WHERE (user_category = 'subject_matter_specialist' OR user_id = ?) AND is_active = 1 ORDER BY id DESC");
$lands_stmt->bind_param("i", $user_id);
$lands_stmt->execute();
$lands_list = $lands_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$lands_stmt->close();

// Fetch Building Inventories for current Subject Matter Specialist
$inv_stmt = $mysqli->prepare("SELECT bi.*, la.property_name FROM building_inventories bi LEFT JOIN land_assets la ON bi.land_asset_id = la.id WHERE (bi.user_category = 'subject_matter_specialist' OR bi.user_id = ?) ORDER BY bi.id DESC");
$inv_stmt->bind_param("i", $user_id);
$inv_stmt->execute();
$inventory_list = $inv_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$inv_stmt->close();

$active_tab = $_GET['tab'] ?? 'lands';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="bi bi-building-fill me-2" style="color: #370709;"></i>Lands &amp; Buildings Asset Registry
        </h3>
        <p class="text-muted small mb-0">Specialist facilities, cold chain depots, epidemiology labs &amp; building inventory management</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn text-light shadow-sm fw-bold" style="background-color: #370709;" data-bs-toggle="modal" data-bs-target="#addLandModal">
            <i class="bi bi-plus-circle-fill me-2"></i>Register Property
        </button>
        <button class="btn btn-dark shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addBuildingInventoryModal">
            <i class="bi bi-box-seam-fill me-2"></i>Log Inventory
        </button>
        <a href="office_details.php" class="btn btn-secondary shadow-sm fw-bold">
            <i class="bi bi-arrow-left me-2"></i>Back to Office Details
        </a>
    </div>
</div>

<!-- Notification Status SweetAlert -->
<?php if (isset($_GET['status']) && isset($_GET['msg'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: '<?= ($_GET['status'] === 'success') ? 'success' : 'error' ?>',
                    title: '<?= ($_GET['status'] === 'success') ? 'Success!' : 'Error!' ?>',
                    text: <?= json_encode($_GET['msg'] ?? '') ?>,
                    confirmButtonColor: '#370709',
                    timer: 3500,
                    timerProgressBar: true
                });
            }
        });
    </script>
<?php endif; ?>

<!-- Navigation Tabs -->
<ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm" id="propertyTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link fw-bold <?= ($active_tab === 'lands') ? 'active' : '' ?>" id="lands-tab" data-bs-toggle="tab" data-bs-target="#lands-content" type="button" role="tab" style="--bs-nav-pills-link-active-bg: #370709;">
            <i class="bi bi-geo-alt-fill me-2"></i>Specialist Land Profiles &amp; Deeds (<?= count($lands_list) ?>)
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-bold <?= ($active_tab === 'inventory') ? 'active' : '' ?>" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory-content" type="button" role="tab" style="--bs-nav-pills-link-active-bg: #370709;">
            <i class="bi bi-boxes me-2"></i>Facility Building Items (<?= count($inventory_list) ?>)
        </button>
    </li>
</ul>

<div class="tab-content" id="propertyTabsContent">

    <!-- TAB 1: LAND PROFILES -->
    <div class="tab-pane fade <?= ($active_tab === 'lands') ? 'show active' : '' ?>" id="lands-content" role="tabpanel">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="landsTable" class="table table-hover align-middle w-100">
                        <thead class="table-dark" style="background-color: #370709;">
                            <tr>
                                <th>Property Name / Unit</th>
                                <th>Land Extent</th>
                                <th>Building Area</th>
                                <th>Land Status</th>
                                <th>Deed Reference</th>
                                <th>Deed Details / Purpose</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lands_list as $land): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($land['property_name']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($land['land_extent'] ?: '-') ?></span></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($land['building_area'] ?: '-') ?></span></td>
                                    <td>
                                        <span class="badge <?= (strpos($land['land_status'], 'State') !== false) ? 'bg-success' : 'bg-primary' ?>">
                                            <?= htmlspecialchars($land['land_status']) ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold text-primary small"><?= htmlspecialchars($land['deed_reference'] ?: '-') ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars($land['deed_description'] ?: '-') ?></td>
                                    <td class="text-center text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary me-1 btn-edit-land" 
                                            data-id="<?= $land['id'] ?>"
                                            data-name="<?= htmlspecialchars($land['property_name']) ?>"
                                            data-extent="<?= htmlspecialchars($land['land_extent']) ?>"
                                            data-area="<?= htmlspecialchars($land['building_area']) ?>"
                                            data-status="<?= htmlspecialchars($land['land_status']) ?>"
                                            data-deed="<?= htmlspecialchars($land['deed_reference']) ?>"
                                            data-desc="<?= htmlspecialchars($land['deed_description']) ?>"
                                            data-bs-toggle="modal" data-bs-target="#editLandModal"
                                            title="Edit Property">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <a href="processors/office_assets_crud.php?action=delete_land&id=<?= $land['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Deactivate">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: BUILDING INVENTORIES -->
    <div class="tab-pane fade <?= ($active_tab === 'inventory') ? 'show active' : '' ?>" id="inventory-content" role="tabpanel">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="inventoryTable" class="table table-hover align-middle w-100">
                        <thead class="table-dark" style="background-color: #370709;">
                            <tr>
                                <th>Property Location</th>
                                <th>Inventory Item / Room</th>
                                <th>Specification</th>
                                <th>Available Qty</th>
                                <th>Current Condition</th>
                                <th>Remarks</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventory_list as $inv): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($inv['property_name'] ?: 'SMS Central Unit') ?></td>
                                    <td><span class="fw-semibold text-primary"><?= htmlspecialchars($inv['inventory_item']) ?></span></td>
                                    <td class="small text-muted"><?= htmlspecialchars($inv['specification'] ?: '-') ?></td>
                                    <td><span class="badge bg-light text-dark border px-2 py-1"><?= intval($inv['available_quantity']) ?></span></td>
                                    <td>
                                        <?php
                                            $cond = $inv['current_condition'];
                                            $badge_class = (strpos($cond, 'Good') !== false || strpos($cond, 'Excellent') !== false) ? 'bg-success' : ((strpos($cond, 'Requires') !== false || strpos($cond, 'Needs') !== false) ? 'bg-warning text-dark' : 'bg-danger');
                                        ?>
                                        <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($cond) ?></span>
                                    </td>
                                    <td class="small text-muted"><?= htmlspecialchars($inv['remarks'] ?: '-') ?></td>
                                    <td class="text-center text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary me-1 btn-edit-inv"
                                            data-id="<?= $inv['id'] ?>"
                                            data-land_id="<?= $inv['land_asset_id'] ?>"
                                            data-item="<?= htmlspecialchars($inv['inventory_item']) ?>"
                                            data-spec="<?= htmlspecialchars($inv['specification']) ?>"
                                            data-qty="<?= $inv['available_quantity'] ?>"
                                            data-cond="<?= htmlspecialchars($inv['current_condition']) ?>"
                                            data-rem="<?= htmlspecialchars($inv['remarks']) ?>"
                                            data-bs-toggle="modal" data-bs-target="#editBuildingInventoryModal"
                                            title="Edit Inventory">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <a href="processors/office_assets_crud.php?action=delete_inventory&id=<?= $inv['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal 1: Add Land Property -->
<div class="modal fade" id="addLandModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #370709;">
                <h5 class="modal-title fw-bold"><i class="bi bi-building-add me-2"></i>Register Specialist Facility Property</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="save_land">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Facility / Property Name <span class="text-danger">*</span></label>
                            <input type="text" name="property_name" class="form-control" placeholder="e.g. SMS Cold Chain Depot & Laboratory" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Ownership Status</label>
                            <select name="land_status" class="form-select">
                                <option value="State Owned (DAPH)" selected>State Owned (DAPH)</option>
                                <option value="Leased by Government">Leased by Government</option>
                                <option value="Vested Property">Vested Property</option>
                                <option value="Departmental Allocation">Departmental Allocation</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Land Extent (Perches / Acres)</label>
                            <input type="text" name="land_extent" class="form-control" placeholder="e.g. 1 Acre 20 Perches">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Floor / Building Area (Sq Ft)</label>
                            <input type="text" name="building_area" class="form-control" placeholder="e.g. 3,500 Sq Ft">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Deed Reference / Plan Number</label>
                            <input type="text" name="deed_reference" class="form-control" placeholder="e.g. DAPH/SMS/PLN/2026/04">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Operational Purpose / Description</label>
                            <textarea name="deed_description" class="form-control" rows="2" placeholder="e.g. Vaccine storage depot, mobile clinic bay and technical surveillance wing"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: #370709;">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Property
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Edit Land Property -->
<div class="modal fade" id="editLandModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #370709;">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Specialist Property</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="update_land">
                <input type="hidden" name="id" id="edit_land_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Facility / Property Name <span class="text-danger">*</span></label>
                            <input type="text" name="property_name" id="edit_property_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Ownership Status</label>
                            <select name="land_status" id="edit_land_status" class="form-select">
                                <option value="State Owned (DAPH)">State Owned (DAPH)</option>
                                <option value="Leased by Government">Leased by Government</option>
                                <option value="Vested Property">Vested Property</option>
                                <option value="Departmental Allocation">Departmental Allocation</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Land Extent</label>
                            <input type="text" name="land_extent" id="edit_land_extent" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Floor / Building Area</label>
                            <input type="text" name="building_area" id="edit_building_area" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Deed Reference</label>
                            <input type="text" name="deed_reference" id="edit_deed_reference" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Operational Purpose / Description</label>
                            <textarea name="deed_description" id="edit_deed_description" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: #370709;">
                        <i class="bi bi-check-circle-fill me-1"></i>Update Property
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Add Building Inventory -->
<div class="modal fade" id="addBuildingInventoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-box-seam me-2"></i>Log Building Inventory Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="save_inventory">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Associated Property</label>
                            <select name="land_asset_id" class="form-select">
                                <option value="0">SMS Central Facility / Unassigned</option>
                                <?php foreach ($lands_list as $l): ?>
                                    <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['property_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Inventory Item / Room <span class="text-danger">*</span></label>
                            <input type="text" name="inventory_item" class="form-control" placeholder="e.g. Cold Chain Storage Room / Vaccine Storage Walk-in" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Specification / Dimensions</label>
                            <input type="text" name="specification" class="form-control" placeholder="e.g. 18ft x 12ft, Insulated PUF Walls">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Quantity / Unit Count</label>
                            <input type="number" name="available_quantity" class="form-control" value="1" min="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Current Physical Condition</label>
                            <select name="current_condition" class="form-select">
                                <option value="Excellent Condition">Excellent Condition</option>
                                <option value="Good Condition" selected>Good Condition</option>
                                <option value="Needs Minor Maintenance">Needs Minor Maintenance</option>
                                <option value="Requires Urgent Repair">Requires Urgent Repair</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Remarks / Inspection Notes</label>
                            <input type="text" name="remarks" class="form-control" placeholder="e.g. Temperature monitored 24/7">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark fw-bold px-4">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 4: Edit Building Inventory -->
<div class="modal fade" id="editBuildingInventoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Building Inventory Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="update_inventory">
                <input type="hidden" name="id" id="edit_inv_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Associated Property</label>
                            <select name="land_asset_id" id="edit_inv_land_id" class="form-select">
                                <option value="0">SMS Central Facility / Unassigned</option>
                                <?php foreach ($lands_list as $l): ?>
                                    <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['property_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Inventory Item / Room <span class="text-danger">*</span></label>
                            <input type="text" name="inventory_item" id="edit_inv_item" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Specification</label>
                            <input type="text" name="specification" id="edit_inv_spec" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Quantity</label>
                            <input type="number" name="available_quantity" id="edit_inv_qty" class="form-control" min="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Current Condition</label>
                            <select name="current_condition" id="edit_inv_cond" class="form-select">
                                <option value="Excellent Condition">Excellent Condition</option>
                                <option value="Good Condition">Good Condition</option>
                                <option value="Needs Minor Maintenance">Needs Minor Maintenance</option>
                                <option value="Requires Urgent Repair">Requires Urgent Repair</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Remarks</label>
                            <input type="text" name="remarks" id="edit_inv_rem" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark fw-bold px-4">
                        <i class="bi bi-check-circle-fill me-1"></i>Update Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $(document).on('click', '.btn-edit-land', function() {
        const btn = $(this);
        $('#edit_land_id').val(btn.data('id'));
        $('#edit_property_name').val(btn.data('name'));
        $('#edit_land_extent').val(btn.data('extent'));
        $('#edit_building_area').val(btn.data('area'));
        $('#edit_land_status').val(btn.data('status'));
        $('#edit_deed_reference').val(btn.data('deed'));
        $('#edit_deed_description').val(btn.data('desc'));
    });

    $(document).on('click', '.btn-edit-inv', function() {
        const btn = $(this);
        $('#edit_inv_id').val(btn.data('id'));
        $('#edit_inv_land_id').val(btn.data('land_id'));
        $('#edit_inv_item').val(btn.data('item'));
        $('#edit_inv_spec').val(btn.data('spec'));
        $('#edit_inv_qty').val(btn.data('qty'));
        $('#edit_inv_cond').val(btn.data('cond'));
        $('#edit_inv_rem').val(btn.data('rem'));
    });

    if ($.fn.DataTable) {
        $('#landsTable').DataTable({ responsive: true, pageLength: 10 });
        $('#inventoryTable').DataTable({ responsive: true, pageLength: 10 });
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
