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

// Fetch distinct inventory types from the database for dynamic filtering and auto-suggestions
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
$default_inventory_types = ['Equipment', 'Furniture', 'Machinery', 'Office Equipment', 'Medical Equipment', 'Cold Chain Equipment', 'Electronics & IT', 'Clinical & Lab Tools', 'Vehicle / Transport', 'Consumables'];
$all_inventory_type_suggestions = array_values(array_unique(array_merge($existing_inventory_types, $default_inventory_types)));
sort($all_inventory_type_suggestions);

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">



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
            <li class="nav-item">
                <button class="nav-link" id="bos-tab" data-bs-toggle="tab" data-bs-target="#bos-content" type="button" role="tab" style="--bs-nav-pills-link-active-bg: #820100;">
                    <i class="bi bi-shield-check me-2"></i>Board of Survey Archive
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
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-outline-info me-1" title="View Details" onclick='viewLand(<?= json_encode($row) ?>)'>
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-primary me-1" title="Edit Property" onclick='editLand(<?= json_encode($row) ?>)'>
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="handleAssetDelete(<?= $row['id'] ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
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
                        <!-- Inventory Type Filter Bar -->
                        <div class="row align-items-center mb-3 g-2">
                            <div class="col-md-5 col-lg-4">
                                <div class="input-group">
                                    <label class="input-group-text bg-white fw-bold text-muted small" for="inventoryTypeFilter">
                                        <i class="bi bi-funnel-fill text-danger me-1"></i> Filter Type:
                                    </label>
                                    <select id="inventoryTypeFilter" class="form-select form-select-sm">
                                        <option value="">All Inventory Types (<?= count($existing_inventory_types) ?> available)</option>
                                        <?php foreach ($existing_inventory_types as $itype): ?>
                                            <option value="<?= htmlspecialchars($itype) ?>"><?= htmlspecialchars($itype) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="resetInventoryTypeFilter" title="Reset filter to show all types">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="inventoryTable" class="table table-hover align-middle w-100">
                                <thead class="table-light text-uppercase small">
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
                                        $cond = $row['current_condition'];
                                        $badge_class = 'bg-secondary';
                                        if ($cond === 'Good' || $cond === 'Excellent') $badge_class = 'bg-success';
                                        elseif ($cond === 'Fair' || $cond === 'Fair (Needs Service)') $badge_class = 'bg-warning text-dark';
                                        elseif ($cond === 'Damaged' || $cond === 'Critical Failure') $badge_class = 'bg-danger';

                                        $inv_num = !empty($row['inventory_number']) ? $row['inventory_number'] : ('INV-' . str_pad($row['id'], 4, '0', STR_PAD_LEFT));
                                        $inv_type = !empty($row['inventory_type']) ? $row['inventory_type'] : 'Equipment';
                                    ?>
                                        <tr id="inventory-row-<?= $row['id'] ?>">
                                            <td>
                                                <span class="fw-bold text-dark"><i class="bi bi-hash text-muted me-1"></i><?= htmlspecialchars($inv_num) ?></span>
                                            </td>
                                            <td data-search="<?= htmlspecialchars($inv_type) ?>" data-filter="<?= htmlspecialchars($inv_type) ?>">
                                                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1"><?= htmlspecialchars($inv_type) ?></span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-primary"><?= htmlspecialchars($row['inventory_item']) ?></span>
                                                <br>
                                                <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($row['property_name']) ?> <span class="badge <?= $badge_class ?> rounded-pill px-2 py-0 ms-1" style="font-size:10px;"><?= htmlspecialchars($row['current_condition']) ?></span></small>
                                            </td>
                                            <td>
                                                <span class="text-dark fw-semibold"><?= htmlspecialchars($row['issue_order_no'] ?: '-') ?></span>
                                            </td>
                                            <td>
                                                <span class="text-dark small"><?= htmlspecialchars($row['received_from'] ?: '-') ?></span>
                                            </td>
                                            <td>
                                                <span class="text-dark fw-semibold"><?= htmlspecialchars($row['receipt_no'] ?: '-') ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary fs-6 px-2 py-1"><?= sprintf("%02d", $row['available_quantity']) ?></span>
                                                <br>
                                                <small class="text-muted" style="font-size:10px;" title="Baseline + Received">Base: <?= intval($row['initial_count']) ?> | Recv: <?= intval($row['received_quantity'] ?? 0) ?></small>
                                            </td>
                                            <td>
                                                <div class="small fw-semibold text-dark"><?= htmlspecialchars($row['specification'] ?: '-') ?></div>
                                                <?php if (!empty($row['remarks'])): ?>
                                                    <small class="text-muted d-block mt-1"><i class="bi bi-chat-left-text me-1"></i><?= htmlspecialchars($row['remarks']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-outline-info me-1" title="View Details" onclick='viewInventory(<?= json_encode($row) ?>)'>
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-primary me-1" title="Edit Item" onclick='editInventory(<?= json_encode($row) ?>)'>
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" title="Board of Survey Decommission" onclick='openBoardOfSurveyModal(<?= json_encode($row) ?>)'>
                                                        <i class="bi bi-shield-x me-1"></i>Decommission
                                                    </button>
                                                </div>
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

            <!-- TAB 3: BOARD OF SURVEY DECOMMISSIONED ARCHIVE -->
            <div class="tab-pane fade" id="bos-content" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="fw-bold text-dark mb-1"><i class="bi bi-file-earmark-check-fill text-danger me-2"></i>Board of Survey Audited Disposals</h5>
                                <p class="text-muted small mb-0">Statutory record of inventory items formally removed from active circulation under authorized Board of Survey proceedings.</p>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="bosTable" class="table table-hover align-middle w-100">
                                <thead class="table-light text-uppercase small">
                                    <tr>
                                        <th>Inventory Item</th>
                                        <th>Located Property</th>
                                        <th>Removal Status</th>
                                        <th class="text-center">Initial Count</th>
                                        <th class="text-center">Decommissioned Qty</th>
                                        <th>Board of Survey Ref</th>
                                        <th>Removal Date</th>
                                        <th>Disposal Findings / Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $bos_query = $mysqli->prepare("
                                        SELECT bi.*, la.property_name 
                                        FROM building_inventories bi
                                        JOIN land_assets la ON bi.land_asset_id = la.id
                                        WHERE la.district_id = ? AND la.range_id = ? AND bi.removal_status != 'Active'
                                        ORDER BY bi.removal_date DESC, bi.id DESC
                                    ");
                                    $bos_query->bind_param("ii", $district_id, $range_id);
                                    $bos_query->execute();
                                    $bos_result = $bos_query->get_result();

                                    while ($brow = $bos_result->fetch_assoc()):
                                        $s_badge = 'bg-secondary';
                                        if ($brow['removal_status'] === 'Destroyed') $s_badge = 'bg-danger';
                                        elseif ($brow['removal_status'] === 'Repaired') $s_badge = 'bg-info text-dark';
                                        elseif ($brow['removal_status'] === 'Sold') $s_badge = 'bg-success';
                                    ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($brow['inventory_item']) ?></td>
                                            <td><span class="text-secondary small fw-semibold"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($brow['property_name']) ?></span></td>
                                            <td><span class="badge <?= $s_badge ?> rounded-pill px-2"><?= htmlspecialchars($brow['removal_status']) ?></span></td>
                                            <td class="text-center fw-semibold text-secondary"><?= sprintf("%02d", $brow['initial_count']) ?></td>
                                            <td class="text-center fw-bold text-danger"><?= sprintf("%02d", $brow['available_quantity']) ?></td>
                                            <td><code class="text-dark fw-bold"><?= htmlspecialchars($brow['board_of_survey_ref'] ?? '-') ?></code></td>
                                            <td><small class="text-muted"><?= htmlspecialchars($brow['removal_date'] ?? '-') ?></small></td>
                                            <td><small class="text-muted"><?= htmlspecialchars($brow['removal_remarks'] ?? '-') ?></small></td>
                                        </tr>
                                    <?php endwhile;
                                    $bos_query->close(); ?>
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
<?php include 'models/edit_land_property.php'; ?>
<?php include 'models/view_land_property.php'; ?>

<?php include 'models/add_building_inventory.php'; ?>
<?php include 'models/edit_building_inventory.php'; ?>
<?php include 'models/view_building_inventory.php'; ?>
<?php include 'models/modal_board_of_survey.php'; ?>


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
    var landsTable, inventoryTable, bosTable;
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

        // Dynamic Inventory Type Filter Handler
        $('#inventoryTypeFilter').on('change', function() {
            var val = $(this).val();
            if (val) {
                inventoryTable.column(1).search('^' + $.fn.dataTable.util.escapeRegex(val) + '$', true, false).draw();
            } else {
                inventoryTable.column(1).search('').draw();
            }
        });

        $('#resetInventoryTypeFilter').on('click', function() {
            $('#inventoryTypeFilter').val('').trigger('change');
        });

        bosTable = $('#bosTable').DataTable({
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
                    title: 'Board of Survey Decommissioned Archive'
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
        // Submit Edit Land Form
        $('#editAssetForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'processors/update_land_asset.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (response.staged) {
                            Swal.fire({
                                icon: 'info',
                                title: 'Pending Authorization',
                                text: response.message,
                                confirmButtonColor: '#500707'
                            }).then(() => { location.reload(); });
                        } else {
                            Swal.fire('Updated!', response.message, 'success').then(() => {
                                location.reload();
                            });
                        }
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }
            });
        });

        // Submit Edit Inventory Form
        $('#editInventoryForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'processors/update_building_inventory.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (response.staged) {
                            Swal.fire({
                                icon: 'info',
                                title: 'Pending Authorization',
                                text: response.message,
                                confirmButtonColor: '#500707'
                            }).then(() => { location.reload(); });
                        } else {
                            Swal.fire('Updated!', response.message, 'success').then(() => {
                                location.reload();
                            });
                        }
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }
            });
        });

        // Initialize Auto-suggest for Building Inventory Item Names
        setupInventoryAutocomplete('#add_inventory_item', '#add_inventory_item_suggestions');
        setupInventoryAutocomplete('#edit_inventory_item', '#edit_inventory_item_suggestions');
    });

    // Auto-suggest logic for Inventory Item Name querying database
    function setupInventoryAutocomplete(inputSelector, dropdownSelector) {
        var timer = null;
        var activeIndex = -1;

        $(inputSelector).on('input focus', function() {
            var term = $(this).val().trim();
            var $dropdown = $(dropdownSelector);

            clearTimeout(timer);
            timer = setTimeout(function() {
                $.ajax({
                    url: 'processors/get_inventory_suggestions.php',
                    type: 'GET',
                    data: { q: term },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success && res.suggestions && res.suggestions.length > 0) {
                            var html = '';
                            res.suggestions.forEach(function(item) {
                                var re = new RegExp('(' + escapeRegex(term) + ')', 'gi');
                                var highlighted = term.length > 0 ? escapeHtml(item).replace(re, '<strong class="text-danger">$1</strong>') : escapeHtml(item);
                                html += '<a class="dropdown-item py-2 px-3 d-flex align-items-center suggestion-item" href="javascript:void(0)" data-value="' + escapeHtml(item) + '">' +
                                        '<i class="bi bi-box-seam me-2 text-muted" style="font-size: 13px;"></i>' +
                                        '<span>' + highlighted + '</span>' +
                                        '</a>';
                            });
                            $dropdown.html(html).show();
                            activeIndex = -1;
                        } else if (term.length > 0) {
                            $dropdown.html('<div class="dropdown-header text-muted py-2 px-3 small"><i class="bi bi-pencil me-1"></i>New item: "' + escapeHtml(term) + '" (manual entry)</div>').show();
                            activeIndex = -1;
                        } else {
                            $dropdown.hide();
                        }
                    },
                    error: function() {
                        $dropdown.hide();
                    }
                });
            }, 200);
        });

        // Click suggestion item
        $(document).on('click', dropdownSelector + ' .suggestion-item', function(e) {
            e.preventDefault();
            var selectedVal = $(this).data('value');
            $(inputSelector).val(selectedVal);
            $(dropdownSelector).hide();
            $(inputSelector).focus();
        });

        // Keyboard navigation (Up/Down/Enter/Escape)
        $(inputSelector).on('keydown', function(e) {
            var $dropdown = $(dropdownSelector);
            if (!$dropdown.is(':visible')) return;

            var $items = $dropdown.find('.suggestion-item');
            if ($items.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIndex = (activeIndex + 1) < $items.length ? activeIndex + 1 : 0;
                $items.removeClass('active bg-light');
                $items.eq(activeIndex).addClass('active bg-light');
                $items.eq(activeIndex)[0].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = (activeIndex - 1) >= 0 ? activeIndex - 1 : $items.length - 1;
                $items.removeClass('active bg-light');
                $items.eq(activeIndex).addClass('active bg-light');
                $items.eq(activeIndex)[0].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter') {
                if (activeIndex >= 0 && activeIndex < $items.length) {
                    e.preventDefault();
                    $items.eq(activeIndex).trigger('click');
                }
            } else if (e.key === 'Escape') {
                $dropdown.hide();
            }
        });

        // Hide when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest(inputSelector + ', ' + dropdownSelector).length) {
                $(dropdownSelector).hide();
            }
        });
    }

    function escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function viewLand(data) {
        document.getElementById('view_property_name').textContent = data.property_name || '-';
        document.getElementById('view_land_extent').textContent = data.land_extent || '-';
        document.getElementById('view_building_area').textContent = data.building_area || '-';
        document.getElementById('view_land_status').textContent = data.land_status || '-';
        document.getElementById('view_deed_reference').textContent = data.deed_reference || '-';
        document.getElementById('view_deed_description').textContent = data.deed_description || '-';
        var modal = new bootstrap.Modal(document.getElementById('viewAssetModal'));
        modal.show();
    }

    function editLand(data) {
        document.getElementById('edit_land_id').value = data.id || '';
        document.getElementById('edit_land_unit').value = data.unit || '';
        
        var propInput = document.getElementById('edit_property_name');
        if (propInput) {
            propInput.value = data.property_name || '';
        }

        document.getElementById('edit_land_extent').value = data.land_extent || '';
        if (document.getElementById('edit_building_area')) {
            document.getElementById('edit_building_area').value = data.building_area || '';
        }
        document.getElementById('edit_land_status').value = data.land_status || 'State Owned';
        document.getElementById('edit_deed_reference').value = data.deed_reference || '';
        document.getElementById('edit_deed_description').value = data.deed_description || '';
        var modal = new bootstrap.Modal(document.getElementById('editAssetModal'));
        modal.show();
    }

    function viewInventory(data) {
        var invNum = data.inventory_number || ('INV-' + String(data.id || '').padStart(4, '0'));
        var invType = data.inventory_type || 'Equipment';

        if (document.getElementById('view_inventory_number')) {
            document.getElementById('view_inventory_number').textContent = invNum;
        }
        if (document.getElementById('view_inventory_type')) {
            document.getElementById('view_inventory_type').textContent = invType;
        }
        if (document.getElementById('view_inventory_item')) {
            document.getElementById('view_inventory_item').textContent = data.inventory_item || '-';
        }
        if (document.getElementById('view_inventory_property')) {
            document.getElementById('view_inventory_property').textContent = data.property_name || '-';
        }
        if (document.getElementById('view_issue_order_no')) {
            document.getElementById('view_issue_order_no').textContent = data.issue_order_no || '-';
        }
        if (document.getElementById('view_received_from')) {
            document.getElementById('view_received_from').textContent = data.received_from || '-';
        }
        if (document.getElementById('view_receipt_no')) {
            document.getElementById('view_receipt_no').textContent = data.receipt_no || '-';
        }
        if (document.getElementById('view_initial_count')) {
            document.getElementById('view_initial_count').textContent = (data.initial_count !== undefined && data.initial_count !== null) ? data.initial_count : (data.available_quantity || 0);
        }
        if (document.getElementById('view_received_quantity')) {
            document.getElementById('view_received_quantity').textContent = data.received_quantity || 0;
        }
        if (document.getElementById('view_available_quantity')) {
            document.getElementById('view_available_quantity').textContent = data.available_quantity || 0;
        }
        if (document.getElementById('view_inventory_condition')) {
            document.getElementById('view_inventory_condition').textContent = data.current_condition || '-';
        }
        if (document.getElementById('view_inventory_specification')) {
            document.getElementById('view_inventory_specification').textContent = data.specification || '-';
        }
        if (document.getElementById('view_inventory_remarks')) {
            document.getElementById('view_inventory_remarks').textContent = data.remarks || '-';
        }
        var modal = new bootstrap.Modal(document.getElementById('viewInventoryModal'));
        modal.show();
    }

    var originalInvQty = 0;
    var originalInvCondition = '';

    function editInventory(data) {
        document.getElementById('edit_inventory_id').value = data.id || '';
        document.getElementById('edit_inventory_unit').value = data.unit || '';
        document.getElementById('edit_land_asset_id').value = data.land_asset_id || '';
        
        var invNum = data.inventory_number || ('INV-' + String(data.id || '').padStart(4, '0'));
        var invType = data.inventory_type || 'Equipment';

        if (document.getElementById('edit_inventory_number')) {
            document.getElementById('edit_inventory_number').value = invNum;
        }
        if (document.getElementById('edit_inventory_type')) {
            document.getElementById('edit_inventory_type').value = invType;
        }
        if (document.getElementById('edit_inventory_location')) {
            document.getElementById('edit_inventory_location').value = data.property_name || 'Office';
        }
        if (document.getElementById('edit_issue_order_no')) {
            document.getElementById('edit_issue_order_no').value = data.issue_order_no || '';
        }
        if (document.getElementById('edit_received_from')) {
            document.getElementById('edit_received_from').value = data.received_from || '';
        }
        if (document.getElementById('edit_receipt_no')) {
            document.getElementById('edit_receipt_no').value = data.receipt_no || '';
        }
        document.getElementById('edit_inventory_item').value = data.inventory_item || '';

        var baseStock = (data.initial_count !== undefined && data.initial_count !== null) ? parseInt(data.initial_count) : parseInt(data.available_quantity || 1);
        var recvStock = parseInt(data.received_quantity || 0);

        if (document.getElementById('edit_initial_count')) {
            document.getElementById('edit_initial_count').value = baseStock;
        }
        if (document.getElementById('edit_received_quantity')) {
            document.getElementById('edit_received_quantity').value = recvStock;
        }
        
        originalInvQty = parseInt(data.available_quantity) || (baseStock + recvStock);
        originalInvCondition = data.current_condition || 'Good';

        document.getElementById('edit_available_quantity').value = originalInvQty;
        document.getElementById('edit_current_condition').value = originalInvCondition;
        document.getElementById('edit_specification').value = data.specification || '';
        document.getElementById('edit_remarks').value = data.remarks || '';
        
        var noticeEl = document.getElementById('edit_condition_damaged_notice');
        if (noticeEl) {
            if (originalInvCondition === 'Damaged') {
                noticeEl.classList.remove('d-none');
                noticeEl.style.display = 'block';
            } else {
                noticeEl.classList.add('d-none');
                noticeEl.style.display = 'none';
            }
        }

        var modal = new bootstrap.Modal(document.getElementById('editInventoryModal'));
        modal.show();
    }

    $('#edit_current_condition').on('change', function() {
        var selectedCond = $(this).val();
        var noticeEl = document.getElementById('edit_condition_damaged_notice');
        var qtyInput = document.getElementById('edit_available_quantity');
        
        if (selectedCond === 'Damaged') {
            if (noticeEl) {
                noticeEl.classList.remove('d-none');
                noticeEl.style.display = 'block';
            }
            if (qtyInput && originalInvCondition !== 'Damaged') {
                var base = parseInt(document.getElementById('edit_initial_count').value) || 0;
                var recv = parseInt(document.getElementById('edit_received_quantity').value) || 0;
                qtyInput.value = Math.max(0, base + recv - 1);
            }
        } else {
            if (noticeEl) {
                noticeEl.classList.add('d-none');
                noticeEl.style.display = 'none';
            }
            if (qtyInput) {
                var base = parseInt(document.getElementById('edit_initial_count').value) || 0;
                var recv = parseInt(document.getElementById('edit_received_quantity').value) || 0;
                qtyInput.value = base + recv;
            }
        }
    });

    function openBoardOfSurveyModal(data) {
        document.getElementById('bos_asset_type').value = 'building_inventory';
        document.getElementById('bos_item_id').value = data.id || '';
        document.getElementById('bos_item_name').textContent = data.inventory_item || '-';
        document.getElementById('bos_item_location').textContent = data.property_name || '-';
        document.getElementById('bos_item_available_qty').textContent = data.available_quantity || '0';
        
        var availQty = parseInt(data.available_quantity) || 1;
        var qtyInput = document.getElementById('bos_removal_quantity');
        qtyInput.max = availQty;
        qtyInput.value = availQty;
        
        document.getElementById('bos_removal_status').value = 'Destroyed';
        document.getElementById('bos_ref').value = '';
        document.getElementById('bos_removal_date').value = new Date().toISOString().split('T')[0];
        document.getElementById('bos_remarks').value = '';
        
        var modal = new bootstrap.Modal(document.getElementById('boardOfSurveyModal'));
        modal.show();
    }

    $('#boardOfSurveyForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Processing...');

        $.ajax({
            url: 'processors/process_board_of_survey.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(res) {
                submitBtn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Execute Formal Removal');
                if (res.success) {
                    var modalEl = document.getElementById('boardOfSurveyModal');
                    var modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    Swal.fire({
                        icon: 'success',
                        title: 'Item Decommissioned',
                        text: res.message,
                        confirmButtonColor: '#820100'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Removal Failed', res.message, 'error');
                }
            },
            error: function(xhr, status, err) {
                submitBtn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Execute Formal Removal');
                Swal.fire('Error', 'Server processing failure: ' + err, 'error');
            }
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
            icon: 'warning',
            title: 'Direct Deletion Prohibited',
            text: "Direct deletions are permanently disabled per formal auditing procedures. Items must be formally decommissioned under an authorized Board of Survey reference.",
            confirmButtonColor: '#820100',
            confirmButtonText: 'Understood'
        });
    }
</script>

<?php require_once '../../../includes/footer.php'; ?>