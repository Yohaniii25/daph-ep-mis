<?php
// pages/modules/training/lands_buildings.php -> Training Centre Lands & Buildings Asset Registry
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

$allowed_roles = ['training_officer', 'administrator', 'provincial_director', 'district_dd'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    die("Access denied");
}

$user_id = $_SESSION['user_id'] ?? 1;
$current_center_id = $_SESSION['training_center_id'] ?? null;
if (empty($current_center_id) && isset($_GET['center_id'])) {
    $current_center_id = intval($_GET['center_id']);
}
if (empty($current_center_id)) {
    $c_res = $mysqli->query("SELECT id FROM training_centers WHERE is_active = 1 LIMIT 1");
    if ($c_res && $row = $c_res->fetch_assoc()) {
        $current_center_id = $row['id'];
    } else {
        $current_center_id = 1;
    }
}

// Fetch Land Assets for current Training Centre
$lands_stmt = $mysqli->prepare("SELECT * FROM land_assets WHERE (training_center_id = ? OR (user_id = ? AND user_category = 'training_centers')) AND is_active = 1 ORDER BY id DESC");
$lands_stmt->bind_param("ii", $current_center_id, $user_id);
$lands_stmt->execute();
$lands_list = $lands_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$lands_stmt->close();

// Fetch Building Inventories for current Training Centre
$inv_stmt = $mysqli->prepare("SELECT bi.*, la.property_name FROM building_inventories bi LEFT JOIN land_assets la ON bi.land_asset_id = la.id WHERE (bi.training_center_id = ? OR (bi.user_id = ? AND bi.user_category = 'training_centers')) ORDER BY bi.id DESC");
$inv_stmt->bind_param("ii", $current_center_id, $user_id);
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
        <p class="text-muted small mb-0">Property deeds and building inventory management for Training Centre</p>
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
            <i class="bi bi-geo-alt-fill me-2"></i>Land Profiles &amp; Deeds (<?= count($lands_list) ?>)
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-bold <?= ($active_tab === 'inventory') ? 'active' : '' ?>" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory-content" type="button" role="tab" style="--bs-nav-pills-link-active-bg: #370709;">
            <i class="bi bi-boxes me-2"></i>Building Inventory Items (<?= count($inventory_list) ?>)
        </button>
    </li>
</ul>

<div class="tab-content" id="propertyTabsContent">

    <!-- TAB 1: LAND PROFILES -->
    <div class="tab-pane fade <?= ($active_tab === 'lands') ? 'show active' : '' ?>" id="lands-content" role="tabpanel">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body p-4">
                <div class="row mb-3 align-items-center">
                    <div class="col-md-5 col-lg-4">
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold text-dark"><i class="bi bi-funnel-fill me-1" style="color: #370709;"></i>Land Status</span>
                            <select id="filterLandStatus" class="form-select fw-bold border-secondary shadow-sm">
                                <option value="">-- All Land Statuses --</option>
                                <option value="State Owned">State Owned</option>
                                <option value="Leased">Leased</option>
                                <option value="Vested">Vested</option>
                                <option value="Private">Private</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="landsTable" class="table table-hover align-middle w-100">
                        <thead class="table-dark" style="background-color: #370709;">
                            <tr>
                                <th>Property Name</th>
                                <th>Land Extent</th>
                                <th>Building Area</th>
                                <th>Ownership Status</th>
                                <th>Deed / Plan Ref</th>
                                <th>Description / Bounds</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lands_list as $land): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($land['property_name']) ?></td>
                                    <td><?= htmlspecialchars($land['land_extent'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($land['building_area'] ?: '-') ?></td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1">
                                            <?= htmlspecialchars($land['land_status']) ?>
                                        </span>
                                    </td>
                                    <td><span class="font-monospace fw-bold text-dark"><?= htmlspecialchars($land['deed_reference'] ?: '-') ?></span></td>
                                    <td class="small text-muted"><?= htmlspecialchars($land['deed_description'] ?: '-') ?></td>
                                    <td class="text-center text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary me-1 btn-edit-land"
                                            data-id="<?= $land['id'] ?>"
                                            data-property_name="<?= htmlspecialchars($land['property_name']) ?>"
                                            data-land_extent="<?= htmlspecialchars($land['land_extent'] ?? '') ?>"
                                            data-building_area="<?= htmlspecialchars($land['building_area'] ?? '') ?>"
                                            data-land_status="<?= htmlspecialchars($land['land_status']) ?>"
                                            data-deed_reference="<?= htmlspecialchars($land['deed_reference'] ?? '') ?>"
                                            data-deed_description="<?= htmlspecialchars($land['deed_description'] ?? '') ?>"
                                            data-bs-toggle="modal" data-bs-target="#editLandModal"
                                            title="Edit Property">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <a href="processors/office_assets_crud.php?action=delete_land&id=<?= $land['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete Property">
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
                                <th>Associated Property</th>
                                <th>Inventory Item / Facility</th>
                                <th>Specification</th>
                                <th>Condition</th>
                                <th>Quantity</th>
                                <th>Remarks</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventory_list as $inv): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($inv['property_name'] ?? 'General') ?></td>
                                    <td><strong class="text-primary"><?= htmlspecialchars($inv['inventory_item']) ?></strong></td>
                                    <td><?= htmlspecialchars($inv['specification'] ?: '-') ?></td>
                                    <td>
                                        <?php
                                            $c_class = (strpos($inv['current_condition'], 'Good') !== false) ? 'bg-success' : ((strpos($inv['current_condition'], 'Fair') !== false) ? 'bg-warning text-dark' : 'bg-danger');
                                        ?>
                                        <span class="badge <?= $c_class ?>"><?= htmlspecialchars($inv['current_condition']) ?></span>
                                    </td>
                                    <td class="fw-bold"><?= intval($inv['available_quantity']) ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars($inv['remarks'] ?: '-') ?></td>
                                    <td class="text-center text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary me-1 btn-edit-inv"
                                            data-id="<?= $inv['id'] ?>"
                                            data-land_asset_id="<?= $inv['land_asset_id'] ?>"
                                            data-inventory_item="<?= htmlspecialchars($inv['inventory_item']) ?>"
                                            data-specification="<?= htmlspecialchars($inv['specification'] ?? '') ?>"
                                            data-current_condition="<?= htmlspecialchars($inv['current_condition']) ?>"
                                            data-available_quantity="<?= $inv['available_quantity'] ?>"
                                            data-remarks="<?= htmlspecialchars($inv['remarks'] ?? '') ?>"
                                            data-bs-toggle="modal" data-bs-target="#editBuildingInventoryModal"
                                            title="Edit Inventory">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <a href="processors/office_assets_crud.php?action=delete_building_inventory&id=<?= $inv['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete Inventory">
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

<!-- Modal 1: Register Land Property -->
<div class="modal fade" id="addLandModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: #370709;">
                <h5 class="modal-title fw-bold"><i class="bi bi-building-add me-2"></i>Register New Land Property</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="save_land">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Property / Section Name <span class="text-danger">*</span></label>
                            <input type="text" name="property_name" class="form-control fw-bold" placeholder="e.g. Training Complex Grounds" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ownership Status <span class="text-danger">*</span></label>
                            <select name="land_status" class="form-select fw-bold" required>
                                <option value="State Owned">State Owned</option>
                                <option value="Leased">Leased</option>
                                <option value="Vested">Vested</option>
                                <option value="Private">Private</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Land Extent</label>
                            <input type="text" name="land_extent" class="form-control" placeholder="e.g. 5 Acres 2 Roods">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Building Area / Floor Plan</label>
                            <input type="text" name="building_area" class="form-control" placeholder="e.g. 12,000 sq ft">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Deed / Survey Plan Reference</label>
                            <input type="text" name="deed_reference" class="form-control" placeholder="e.g. Plan No 4821/A">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Description / Boundary Details</label>
                            <input type="text" name="deed_description" class="form-control" placeholder="e.g. North: Main Road, South: Canal">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: #370709;">
                        <i class="bi bi-check-circle-fill me-1"></i>Save Property Record
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
            <div class="modal-header text-light" style="background-color: var(--color-c10, #185dbd);">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Land Property Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="update_land">
                <input type="hidden" name="id" id="edit_land_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Property / Section Name <span class="text-danger">*</span></label>
                            <input type="text" name="property_name" id="edit_property_name" class="form-control fw-bold" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ownership Status <span class="text-danger">*</span></label>
                            <select name="land_status" id="edit_land_status" class="form-select fw-bold" required>
                                <option value="State Owned">State Owned</option>
                                <option value="Leased">Leased</option>
                                <option value="Vested">Vested</option>
                                <option value="Private">Private</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Land Extent</label>
                            <input type="text" name="land_extent" id="edit_land_extent" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Building Area / Floor Plan</label>
                            <input type="text" name="building_area" id="edit_building_area" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Deed / Survey Plan Reference</label>
                            <input type="text" name="deed_reference" id="edit_deed_reference" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Description / Boundary Details</label>
                            <input type="text" name="deed_description" id="edit_deed_description" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--color-c10, #185dbd);">
                        <i class="bi bi-check-circle-fill me-1"></i>Update Property Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Log Building Inventory -->
<div class="modal fade" id="addBuildingInventoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-box-seam-fill me-2"></i>Log Building Inventory Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="save_building_inventory">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Associated Property <span class="text-danger">*</span></label>
                        <select name="land_asset_id" class="form-select fw-bold" required>
                            <?php foreach ($lands_list as $l): ?>
                                <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['property_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Inventory Item / Room <span class="text-danger">*</span></label>
                        <input type="text" name="inventory_item" class="form-control fw-bold" placeholder="e.g. Lecture Hall A / Audio Room" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="available_quantity" class="form-control fw-bold" value="1" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Condition <span class="text-danger">*</span></label>
                            <select name="current_condition" class="form-select fw-bold" required>
                                <option value="Good Condition">Good Condition</option>
                                <option value="Fair Condition">Fair Condition</option>
                                <option value="Requires Repair">Requires Repair</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Specification</label>
                        <input type="text" name="specification" class="form-control" placeholder="e.g. Air-conditioned, Capacity 50">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
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
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-light" style="background-color: var(--color-c10, #185dbd);">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Building Inventory</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="processors/office_assets_crud.php" method="POST">
                <input type="hidden" name="action" value="update_building_inventory">
                <input type="hidden" name="id" id="edit_inv_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Associated Property <span class="text-danger">*</span></label>
                        <select name="land_asset_id" id="edit_inv_land_id" class="form-select fw-bold" required>
                            <?php foreach ($lands_list as $l): ?>
                                <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['property_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Inventory Item / Room <span class="text-danger">*</span></label>
                        <input type="text" name="inventory_item" id="edit_inv_item" class="form-control fw-bold" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="available_quantity" id="edit_inv_qty" class="form-control fw-bold" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Condition <span class="text-danger">*</span></label>
                            <select name="current_condition" id="edit_inv_cond" class="form-select fw-bold" required>
                                <option value="Good Condition">Good Condition</option>
                                <option value="Fair Condition">Fair Condition</option>
                                <option value="Requires Repair">Requires Repair</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Specification</label>
                        <input type="text" name="specification" id="edit_inv_spec" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks</label>
                        <textarea name="remarks" id="edit_inv_remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-light fw-bold px-4" style="background-color: var(--color-c10, #185dbd);">
                        <i class="bi bi-check-circle-fill me-1"></i>Update Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit Land Handler
    $(document).on('click', '.btn-edit-land', function() {
        const btn = $(this);
        $('#edit_land_id').val(btn.data('id'));
        $('#edit_property_name').val(btn.data('property_name'));
        $('#edit_land_extent').val(btn.data('land_extent'));
        $('#edit_building_area').val(btn.data('building_area'));
        $('#edit_land_status').val(btn.data('land_status'));
        $('#edit_deed_reference').val(btn.data('deed_reference'));
        $('#edit_deed_description').val(btn.data('deed_description'));
    });

    // Edit Building Inventory Handler
    $(document).on('click', '.btn-edit-inv', function() {
        const btn = $(this);
        $('#edit_inv_id').val(btn.data('id'));
        $('#edit_inv_land_id').val(btn.data('land_asset_id'));
        $('#edit_inv_item').val(btn.data('inventory_item'));
        $('#edit_inv_qty').val(btn.data('available_quantity'));
        $('#edit_inv_cond').val(btn.data('current_condition'));
        $('#edit_inv_spec').val(btn.data('specification'));
        $('#edit_inv_remarks').val(btn.data('remarks'));
    });

    // DataTables init
    if ($.fn.DataTable) {
        var landsTable = $('#landsTable').DataTable({
            responsive: true,
            pageLength: 10
        });
        $('#filterLandStatus').on('change', function() {
            landsTable.column(3).search(this.value).draw();
        });
        $('#inventoryTable').DataTable({
            responsive: true,
            pageLength: 10
        });
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
