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

// Fetch distinct inventory types across database for dynamic filtering and auto-suggestions
$inv_types_res = $mysqli->query("
    SELECT DISTINCT inventory_type 
    FROM building_inventories 
    WHERE inventory_type IS NOT NULL AND TRIM(inventory_type) != '' 
    ORDER BY inventory_type ASC
");
$existing_inventory_types = [];
if ($inv_types_res) {
    while ($t_row = $inv_types_res->fetch_assoc()) {
        $clean_type = trim($t_row['inventory_type']);
        if (!empty($clean_type) && !in_array($clean_type, $existing_inventory_types)) {
            $existing_inventory_types[] = $clean_type;
        }
    }
}
$default_inventory_types = ['Medical Equipment', 'Cold Chain Equipment', 'Office Equipment', 'Furniture & Fixtures', 'Lab & Diagnostic Tool', 'Vehicle / Transport', 'Consumables'];
$all_inventory_type_suggestions = array_values(array_unique(array_merge($existing_inventory_types, $default_inventory_types)));
sort($all_inventory_type_suggestions);

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
                <!-- Inventory Type Filter Bar -->
                <div class="row align-items-center mb-3 g-2">
                    <div class="col-md-5 col-lg-4">
                        <div class="input-group">
                            <label class="input-group-text bg-white fw-bold text-muted small" for="smsInventoryTypeFilter">
                                <i class="bi bi-funnel-fill text-danger me-1"></i> Filter Type:
                            </label>
                            <select id="smsInventoryTypeFilter" class="form-select form-select-sm">
                                <option value="">All Inventory Types (<?= count($existing_inventory_types) ?> available)</option>
                                <?php foreach ($existing_inventory_types as $itype): ?>
                                    <option value="<?= htmlspecialchars($itype) ?>"><?= htmlspecialchars($itype) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="resetSmsTypeFilter" title="Reset filter to show all types">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="inventoryTable" class="table table-hover align-middle w-100">
                        <thead class="table-dark" style="background-color: #370709;">
                            <tr>
                                <th>Inventory Number</th>
                                <th>Inventory Type</th>
                                <th>Item</th>
                                <th>Issue Order No.</th>
                                <th>Received From</th>
                                <th>Receipt No.</th>
                                <th class="text-center">Quantity</th>
                                <th>Specification / Remarks</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventory_list as $inv): 
                                $inv_num = !empty($inv['inventory_number']) ? $inv['inventory_number'] : ('INV-' . str_pad($inv['id'], 4, '0', STR_PAD_LEFT));
                                $inv_type = !empty($inv['inventory_type']) ? $inv['inventory_type'] : 'Equipment';
                                $cond = $inv['current_condition'] ?? 'Good Condition';
                                $badge_class = (strpos($cond, 'Good') !== false || strpos($cond, 'Excellent') !== false) ? 'bg-success' : ((strpos($cond, 'Requires') !== false || strpos($cond, 'Needs') !== false) ? 'bg-warning text-dark' : 'bg-danger');
                            ?>
                                <tr>
                                    <td><span class="fw-bold text-dark"><i class="bi bi-hash text-muted me-1"></i><?= htmlspecialchars($inv_num) ?></span></td>
                                    <td data-search="<?= htmlspecialchars($inv_type) ?>" data-filter="<?= htmlspecialchars($inv_type) ?>">
                                        <span class="badge bg-secondary-subtle text-secondary border px-2 py-1"><?= htmlspecialchars($inv_type) ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-primary"><?= htmlspecialchars($inv['inventory_item']) ?></span><br>
                                        <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($inv['property_name'] ?: 'SMS Central Unit') ?> <span class="badge <?= $badge_class ?> rounded-pill px-2 py-0 ms-1" style="font-size:10px;"><?= htmlspecialchars($cond) ?></span></small>
                                    </td>
                                    <td><span class="text-dark fw-semibold"><?= htmlspecialchars($inv['issue_order_no'] ?: '-') ?></span></td>
                                    <td><span class="text-dark small"><?= htmlspecialchars($inv['received_from'] ?: '-') ?></span></td>
                                    <td><span class="text-dark fw-semibold"><?= htmlspecialchars($inv['receipt_no'] ?: '-') ?></span></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary fs-6 px-2 py-1"><?= sprintf("%02d", $inv['available_quantity']) ?></span><br>
                                        <small class="text-muted" style="font-size:10px;">Base: <?= intval($inv['initial_count']) ?> | Recv: <?= intval($inv['received_quantity'] ?? 0) ?></small>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-dark"><?= htmlspecialchars($inv['specification'] ?: '-') ?></div>
                                        <?php if (!empty($inv['remarks'])): ?>
                                            <small class="text-muted d-block mt-1"><i class="bi bi-chat-left-text me-1"></i><?= htmlspecialchars($inv['remarks']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary me-1 btn-edit-inv"
                                            data-id="<?= $inv['id'] ?>"
                                            data-land_id="<?= $inv['land_asset_id'] ?>"
                                            data-property_name="<?= htmlspecialchars($inv['property_name'] ?? '') ?>"
                                            data-item="<?= htmlspecialchars($inv['inventory_item']) ?>"
                                            data-inventory_number="<?= htmlspecialchars($inv_num) ?>"
                                            data-inventory_type="<?= htmlspecialchars($inv_type) ?>"
                                            data-issue_order_no="<?= htmlspecialchars($inv['issue_order_no'] ?? '') ?>"
                                            data-received_from="<?= htmlspecialchars($inv['received_from'] ?? '') ?>"
                                            data-receipt_no="<?= htmlspecialchars($inv['receipt_no'] ?? '') ?>"
                                            data-spec="<?= htmlspecialchars($inv['specification'] ?? '') ?>"
                                            data-cond="<?= htmlspecialchars($inv['current_condition']) ?>"
                                            data-initial_count="<?= intval($inv['initial_count']) ?>"
                                            data-received_quantity="<?= intval($inv['received_quantity'] ?? 0) ?>"
                                            data-qty="<?= intval($inv['available_quantity']) ?>"
                                            data-rem="<?= htmlspecialchars($inv['remarks'] ?? '') ?>"
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
                            <label class="form-label small fw-bold">Associated Property / Facility <span class="text-danger">*</span></label>
                            <input type="text" name="property_name" class="form-control" list="smsPropertyDatalist" placeholder="e.g. Central Veterinary Clinic & Cold Depot" required>
                            <datalist id="smsPropertyDatalist">
                                <?php foreach ($lands_list as $l): ?>
                                    <option value="<?= htmlspecialchars($l['property_name']) ?>">
                                <?php endforeach; ?>
                            </datalist>
                            <div class="form-text">Enter property name manually or select from suggestions.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Inventory Number</label>
                            <input type="text" name="inventory_number" class="form-control" placeholder="e.g. INV-SMS-2026-001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Inventory Type <span class="text-danger">*</span></label>
                            <input type="text" name="inventory_type" id="add_sms_inv_type" class="form-control" list="sms_inventory_type_list" placeholder="e.g. Medical Equipment, Cold Chain" autocomplete="off" required>
                            <datalist id="sms_inventory_type_list">
                                <?php foreach ($all_inventory_type_suggestions as $itype): ?>
                                    <option value="<?= htmlspecialchars($itype) ?>">
                                <?php endforeach; ?>
                            </datalist>
                            <small class="text-muted" style="font-size: 11px;"><i class="bi bi-lightbulb me-1"></i>Suggests existing types from database or enter new.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Item Name / Room <span class="text-danger">*</span></label>
                            <input type="text" name="inventory_item" class="form-control" placeholder="e.g. Mobile Vaccine Cooler / Diagnostic Kit" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Issue Order No.</label>
                            <input type="text" name="issue_order_no" class="form-control" placeholder="e.g. IO-2026/09">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Received From</label>
                            <input type="text" name="received_from" class="form-control" placeholder="e.g. Provincial Medical Supplies Depot">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Receipt No.</label>
                            <input type="text" name="receipt_no" class="form-control" placeholder="e.g. RN-89421">
                        </div>

                        <!-- Auto-Calculation Block -->
                        <div class="col-12">
                            <div class="card bg-light border-0 p-3 rounded-3">
                                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-calculator me-1"></i>Stock & Availability Calculation</h6>
                                <p class="small text-muted mb-3">Availability is dynamically auto-calculated as <strong>Baseline Stock + Received Quantity</strong>.</p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Initial Baseline Stock</label>
                                        <input type="number" name="initial_count" id="sms_add_initial_count" class="form-control" value="0" min="0" oninput="calcSmsAddAvailability()">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Received Quantity</label>
                                        <input type="number" name="received_quantity" id="sms_add_received_quantity" class="form-control" value="1" min="0" oninput="calcSmsAddAvailability()">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-primary">Current Availability (Total)</label>
                                        <input type="number" name="available_quantity" id="sms_add_available_quantity" class="form-control bg-white fw-bold text-primary" value="1" readonly>
                                    </div>
                                </div>
                            </div>
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
                            <label class="form-label small fw-bold">Specification / Remarks (Brand, Model, Specs)</label>
                            <input type="text" name="specification" class="form-control" placeholder="e.g. Dometic TCX 21, 12V DC/230V AC Portable">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">General Remarks / Notes</label>
                            <input type="text" name="remarks" class="form-control" placeholder="e.g. Tested and calibrated upon delivery">
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
                            <label class="form-label small fw-bold">Associated Property / Facility <span class="text-danger">*</span></label>
                            <input type="text" name="property_name" id="edit_inv_property_name" class="form-control" list="smsPropertyDatalist" required>
                            <div class="form-text">Enter property name manually or select from suggestions.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Inventory Number</label>
                            <input type="text" name="inventory_number" id="edit_inv_number" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Inventory Type <span class="text-danger">*</span></label>
                            <input type="text" name="inventory_type" id="edit_inv_type" class="form-control" list="sms_edit_inventory_type_list" autocomplete="off" required>
                            <datalist id="sms_edit_inventory_type_list">
                                <?php foreach ($all_inventory_type_suggestions as $itype): ?>
                                    <option value="<?= htmlspecialchars($itype) ?>">
                                <?php endforeach; ?>
                            </datalist>
                            <small class="text-muted" style="font-size: 11px;"><i class="bi bi-lightbulb me-1"></i>Suggests existing types from database or enter new.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Item Name / Room <span class="text-danger">*</span></label>
                            <input type="text" name="inventory_item" id="edit_inv_item" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Issue Order No.</label>
                            <input type="text" name="issue_order_no" id="edit_inv_issue_order_no" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Received From</label>
                            <input type="text" name="received_from" id="edit_inv_received_from" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Receipt No.</label>
                            <input type="text" name="receipt_no" id="edit_inv_receipt_no" class="form-control">
                        </div>

                        <!-- Auto-Calculation Block -->
                        <div class="col-12">
                            <div class="card bg-light border-0 p-3 rounded-3">
                                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-calculator me-1"></i>Stock & Availability Calculation</h6>
                                <p class="small text-muted mb-3">Availability is dynamically auto-calculated as <strong>Baseline Stock + Received Quantity</strong>.</p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Initial Baseline Stock</label>
                                        <input type="number" name="initial_count" id="edit_inv_initial_count" class="form-control" value="0" min="0" oninput="calcSmsEditAvailability()">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Received Quantity</label>
                                        <input type="number" name="received_quantity" id="edit_inv_received_quantity" class="form-control" value="0" min="0" oninput="calcSmsEditAvailability()">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-primary">Current Availability (Total)</label>
                                        <input type="number" name="available_quantity" id="edit_inv_qty" class="form-control bg-white fw-bold text-primary" value="0" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Current Physical Condition</label>
                            <select name="current_condition" id="edit_inv_cond" class="form-select">
                                <option value="Excellent Condition">Excellent Condition</option>
                                <option value="Good Condition">Good Condition</option>
                                <option value="Needs Minor Maintenance">Needs Minor Maintenance</option>
                                <option value="Requires Urgent Repair">Requires Urgent Repair</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Specification / Remarks (Brand, Model, Specs)</label>
                            <input type="text" name="specification" id="edit_inv_spec" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">General Remarks / Notes</label>
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
function calcSmsAddAvailability() {
    const base = parseInt(document.getElementById('sms_add_initial_count').value) || 0;
    const recv = parseInt(document.getElementById('sms_add_received_quantity').value) || 0;
    document.getElementById('sms_add_available_quantity').value = base + recv;
}

function calcSmsEditAvailability() {
    const base = parseInt(document.getElementById('edit_inv_initial_count').value) || 0;
    const recv = parseInt(document.getElementById('edit_inv_received_quantity').value) || 0;
    document.getElementById('edit_inv_qty').value = base + recv;
}

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
        $('#edit_inv_property_name').val(btn.data('property_name') || '');
        $('#edit_inv_number').val(btn.data('inventory_number') || '');
        $('#edit_inv_type').val(btn.data('inventory_type') || 'Medical Equipment');
        $('#edit_inv_item').val(btn.data('item') || '');
        $('#edit_inv_issue_order_no').val(btn.data('issue_order_no') || '');
        $('#edit_inv_received_from').val(btn.data('received_from') || '');
        $('#edit_inv_receipt_no').val(btn.data('receipt_no') || '');
        $('#edit_inv_initial_count').val(btn.data('initial_count') || 0);
        $('#edit_inv_received_quantity').val(btn.data('received_quantity') || 0);
        $('#edit_inv_qty').val(btn.data('qty') || 0);
        $('#edit_inv_cond').val(btn.data('cond') || 'Good Condition');
        $('#edit_inv_spec').val(btn.data('spec') || '');
        $('#edit_inv_rem').val(btn.data('rem') || '');
    });

    if ($.fn.DataTable) {
        $('#landsTable').DataTable({ responsive: true, pageLength: 10 });
        var invTable = $('#inventoryTable').DataTable({ responsive: true, pageLength: 10 });

        $('#smsInventoryTypeFilter').on('change', function() {
            var val = $(this).val();
            if (val) {
                invTable.column(1).search('^' + $.fn.dataTable.util.escapeRegex(val) + '$', true, false).draw();
            } else {
                invTable.column(1).search('').draw();
            }
        });

        $('#resetSmsTypeFilter').on('click', function() {
            $('#smsInventoryTypeFilter').val('').trigger('change');
        });
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>
